<?php
/**
 * Class for handling Eventbrite rewrite rules and template redirection.
 */

 class Eventbrite_Rewrite {
 	/**
	 * Our constructor.
	 *
	 * @uses add_filter()
	 * @uses add_action()
	 */
 	public function __construct() {
 		// Register hooks.
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ) );
		add_action( 'template_include', array( $this, 'event_single_view' ) );
 	}

	/**
	 * Add Eventbrite-specific query vars so they are recognized by WP_Query.
	 *
	 * @param array $query_vars
	 * @return array Query vars
	 */
	function add_query_vars( $query_vars ) {
		$query_vars[] = 'eventbrite_id';

		return $query_vars;
	}

	/**
	 * Add rewrite rules for event single views.
	 *
	 * @param
	 * @uses
	 * @return
	 */
	public function add_rewrite_rules( $wp_rules ) {
		$eb_rules = array();

		// Get all pages that are using the Eventbrite page template.
		$support = get_theme_support('eventbrite');
		$template = ( isset( $support[0]['template'] ) ) ? $support[0]['template'] : 'eventbrite.php';
		$pages = get_pages(array(
			'meta_key' => '_wp_page_template',
			'meta_value' => $template,
		));

		// If any pages are using the template, add rewrite rules for each of them.
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$eb_rules_key = sprintf( '%s/(\d+)/?$', $page->post_name );
				$eb_rules[$eb_rules_key] = 'index.php?eventbrite_id=$matches[1]';
			}
		}

		// Combine all rules and return.
		$rules = array_merge( $eb_rules + $wp_rules );
		return $rules;
	}

	/**
	 * Redirect to the event single view if necessary.
	 *
	 * @param
	 * @uses
	 * @return
	 */
	public function event_single_view( $template ) {
		if ( get_query_var( 'eventbrite_id' ) ) {
			$template = esc_url( trailingslashit( get_stylesheet_directory() ) . eventbrite_get_support_args()->single );
		}
		return $template;
	}
 }

 new Eventbrite_Rewrite();
