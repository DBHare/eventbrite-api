<?php
/**
 * Eventbrite_Templates class, for handling Eventbrite template redirection, file includes, and rewrite rules.
 *
 * @package Eventbrite_API
 */

 class Eventbrite_Templates {
	/**
	 * Our constructor.
	 *
	 * @access public
	 */
	public function __construct() {
 		// Register hooks.
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'theme_page_templates', array( $this, 'inject_page_template' ), 10, 3 );
		add_action( 'template_include', array( $this, 'check_templates' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'body_class', array( $this, 'adjust_body_classes' ) );
		add_action( 'save_post_page', array( $this, 'flush_rewrite_rules_on_save_post' ), 10, 2 );
		add_action( 'updated_postmeta', array( $this, 'flush_rewrite_rules_on_updated_postmeta' ), 10, 3 );

		// Register image size for eventbrite events
		add_image_size( 'eventbrite-event', 400, 200, true );
 	}

	/**
	 * Add Eventbrite-specific query vars so they are recognized by WP_Query.
	 *
	 * @access public
	 *
	 * @param array $query_vars Core default query vars.
	 * @return array Query vars including our Eventbrite-specific vars.
	 */
	public function add_query_vars( $query_vars ) {
		$query_vars = array_merge( $query_vars, array( 'eventbrite_id', 'organizer_id', 'venue_id' ) );

		return $query_vars;
	}

	/**
	 * Add rewrite rules for Eventbrite views.
	 *
	 * @access public
	 *
	 * @param  array $wp_rules WordPress rewrite rules.
	 * @return array All rewrite rules (WordPress and Eventbrite rules combined).
	 */
	public function add_rewrite_rules( $wp_rules ) {
		$eb_rules = array();

		// Get all pages that are using the Eventbrite page template.
		$pages = get_pages(array(
			'meta_key' => '_wp_page_template',
			'meta_value' => 'eventbrite-index.php',
		));

		// If any pages are using the template, add rewrite rules for each of them.
		if ( $pages ) {
			foreach ( $pages as $page ) {
				// Add rules for "author" archives (meaning all events by an organizer).
				$eb_rules_key = sprintf( '(%s)/organizer/[0-9a-z-]+-(\d+)/?$', $page->post_name );
				$eb_rules[$eb_rules_key] = 'index.php?pagename=$matches[1]&organizer_id=$matches[2]';
				$eb_rules_key = sprintf( '(%s)/organizer/[0-9a-z-]+-(\d+)/page/([0-9]{1,})/?$', $page->post_name );
				$eb_rules[$eb_rules_key] = 'index.php?pagename=$matches[1]&organizer_id=$matches[2]&paged=$matches[3]';

				// Add rules for venue archives (meaning all events at a given venue).
				$eb_rules_key = sprintf( '(%s)/venue/[0-9a-z-]+-(\d+)/?$', $page->post_name );
				$eb_rules[$eb_rules_key] = 'index.php?pagename=$matches[1]&venue_id=$matches[2]';
				$eb_rules_key = sprintf( '(%s)/venue/[0-9a-z-]+-(\d+)/page/([0-9]{1,})/?$', $page->post_name );
				$eb_rules[$eb_rules_key] = 'index.php?pagename=$matches[1]&venue_id=$matches[2]&paged=$matches[3]';

				// Add a rule for event single views. Event IDs are 11 digits long (for the foreseeable future).
				$eb_rules_key = sprintf( '(%s)/[0-9a-z-]+-(\d{11})/?$', $page->post_name );
				$eb_rules[$eb_rules_key] = 'index.php?pagename=$matches[1]&eventbrite_id=$matches[2]';
			}
		}

		// Combine all rules and return.
		$rules = array_merge( $eb_rules + $wp_rules );
		return $rules;
	}

	/**
	 * Make the Eventbrite Events page template available to the templates dropdown.
	 *
	 * @access public
	 *
	 * @param  array $templates The theme's registered page templates.
	 * @return array $templates Page templates with the Eventbrite index template added.
	 */
	public function inject_page_template( $templates ) {
		$templates['eventbrite-index.php'] = __( 'Eventbrite Events', 'eventbrite-api' );
		return $templates;
	}

	/**
	 * Check if we need to use Eventbrite page templates, either from the theme or our plugin.
	 *
	 * @access public
	 *
	 * @param  string $template The template to be used according to the Template Hierarchy.
	 * @return string Template file name.
	 */
	public function check_templates( $template ) {
		// If we have an 'eventbrite_id' query var, we're dealing with an event single view.
		// The filter allows custom post types to be displayed in the same template
		if ( get_query_var( 'eventbrite_id' ) ) {
			$template = $this->get_default_template_path( 'eventbrite-single' );
		}

		// Check if we have a page using the Eventbrite event listing template.
		// The filter allows custom post types to be displayed in the same template
		elseif ( 'eventbrite-index.php' == get_post_meta( get_the_ID(), '_wp_page_template', true ) ) {
			$template = $this->get_default_template_path( 'eventbrite-index' );
		}

		return $template;
	}


	/**
	 * Retrieves the default template path based on the template name
	 * @param  string $template_name  Name of file template
	 * @return string                 The path to the template with the template name
	 */
	public static function get_default_template_path( $template_name ){
		// We're using a default theme. We've got special template files for those.
		if ( self::default_theme_activated() ) {
			$template = plugin_dir_path( __DIR__ ) . 'tmpl/compat/' . get_template() . '/' . $template_name . '.php';
		}

		// The theme declares support, looks for an Eventbrite template.
		elseif ( current_theme_supports( 'eventbrite' ) && file_exists( get_stylesheet_directory() . '/eventbrite/' . $template_name . '.php' ) ) {
			$template = esc_url( get_stylesheet_directory() . '/eventbrite/' . $template_name . '.php' );
		}

		// Let a child theme inherit its parent's template.
		elseif ( current_theme_supports( 'eventbrite' ) && file_exists( get_template_directory() . '/eventbrite/' . $template_name . '.php' ) ) {
			$template = esc_url( get_template_directory() . '/eventbrite/' . $template_name . '.php' );
		}

		// Nothing in the theme, and it's not a default theme; just use our regular template.
		else {
			$template = plugin_dir_path( __DIR__ ) . 'tmpl/' . $template_name . '.php';
		}

		return $template;
	}

	/**
	 * Enqueue any styles required for default themes.
	 *
	 * @access public
	 */
	public function enqueue_styles() {
		// If we're not using a default theme.
		if ( ! $this->default_theme_activated() ) {
			$style_rel_path = 'tmpl/eventbrite-style.css';
		}else{
			// If there's a stylesheet for this default theme, enqueue it.
			$style_rel_path = 'tmpl/compat/' . get_template() . '/eventbrite-style.css';
		}
		if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . $style_rel_path ) ) {
			wp_enqueue_style(
				'eventbrite-styles',
				plugins_url( $style_rel_path, dirname( __FILE__ ) )
			);
		}
	}

	/**
	 * Adjust body classes when our Eventbrite templates are in use.
	 *
	 * @access public
	 *
	 * @param  array $classes Unfiltered body classes
	 * @return array Filtered body classes
	 */
	public function adjust_body_classes( $classes ) {
		// Check if we're loading an Eventbrite single view.
		if ( eventbrite_is_single() ) {
			$classes[] = 'single';
			$classes[] = 'single-event';
			$key = array_search( 'page', $classes );
			unset( $classes[ $key ] );
		}

		// Check for an Eventbrite index view.
		elseif ( 'eventbrite-index.php' == get_post_meta( get_the_ID(), '_wp_page_template', true ) ) {
			$classes[] = 'archive';
			$classes[] = 'archive-eventbrite';
			foreach ( array( 'page', 'singular' ) as $value ) {
				$key = array_search( $value, $classes );
				unset( $classes[ $key ] );
			}
		}

		return $classes;
	}

	/**
	 * Check if a default theme is active.
	 *
	 * @access protected
	 *
	 * @return bool True if a default theme is active, false otherwise.
	 */
	protected static function default_theme_activated() {
		// Our supported default themes.
		$default_themes = array(
			'twentyten',
			'twentyeleven',
			'twentytwelve',
			'twentythirteen',
			'twentyfourteen',
			'twentyfifteen',
		);

		return in_array( get_template(), $default_themes );
	}

	/**
	 * Flush rewrite rules when the Eventbrite Event page template is active on a saved page.
	 *
	 * @access public
	 *
	 * @param int $post_id Post ID
	 * @param object $post WP_Post object
	 */
	public function flush_rewrite_rules_on_save_post( $post_id, $post ) {
		if ( 'eventbrite-index.php' == $post->page_template ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * Flush rewrite rules when a page template change is registered.
	 *
	 * @access public
	 *
	 * @param int $meta_id ID of updated metadata entry
	 * @param int $object_id Object ID
	 * @param string $meta_key Meta key
	 */
	public function flush_rewrite_rules_on_updated_postmeta( $meta_id, $object_id, $meta_key ) {
		if ( '_wp_page_template' == $meta_key ) {
			flush_rewrite_rules();
		}
	}
 }

 new Eventbrite_Templates();
