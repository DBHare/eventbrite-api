<?php
/**
 * Eventbrite_Custom_Events class, for creating a section in administration for custom events and displaying them in the front end
 */

 class Eventbrite_Custom_Events {

 	// The unique name of the post type
 	public $post_type;

 	// The singular name of the post type
 	public $post_name_singular;

 	// The plural name of the post type
 	public $post_name_plural;

	/**
	 * Our constructor.
	 *
	 * @access public
	 */
	public function __construct() {

		// save variables
		$this->post_type = 'eb-custom-event';
		$this->post_name_singular = __( 'Custom Event', 'eventbrite-api' );
		$this->post_name_plural = __( 'Custom Events', 'eventbrite-api' );

		$this->organizer_taxonomy = 'eb-organizer';
		$this->organizer_taxonomy_singular = __( 'Organizer', 'eventbrite-api' );
		$this->organizer_taxonomy_plural = __( 'Organizers', 'eventbrite-api' );

		$this->venue_taxonomy = 'eb-venue';
		$this->venue_taxonomy_singular = __( 'Venue', 'eventbrite-api' );
		$this->venue_taxonomy_plural = __( 'Venues', 'eventbrite-api' );


		// Add admin settings
		add_action( 'eventbrite-admin-options', array( $this, 'admin_options' ) );

		// Save setting to flush rewrite rules if the custom events are enabled or disabled
		add_action( 'add_option_eb_settings_enable_custom_events', array( $this, 'flush_rules' ), 10, 2 );
		add_action( 'update_option_eb_settings_enable_custom_events', array( $this, 'flush_rules' ), 10, 2 );

		// Flush rules if setting was set
	    if( eventbrite_get_setting( 'flush_rules' ) ){
		    flush_rewrite_rules( true );
		    eventbrite_set_setting( 'flush_rules', false );
		}

		// If the custom events are disabled then abort
		if( ! eventbrite_get_setting( 'enable_custom_events' ) ){
			return;
		}

 		// Register new post type to display custom events
		$this->register_post_type();

		// Register custom taxonomies
		$this->register_custom_taxonomies();

		if( is_admin() ){
			//Add a meta box where to display additional fields for the new post type 
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

			// Save values for custom fields
			add_action( 'save_post', array( $this, 'save_field_values' ), 10, 2 );
		}

		// Add custom events to Eventbrite results
		add_filter( 'eventbrite_query_post_api_filters_after', array( $this, 'add_custom_events_to_results' ), 10, 2 );

		// Filter events based on custom taxonomies
		add_filter( 'eventbrite_query_post_api_filters_after', array( $this, 'filter_events' ), 10, 2 );

		// Remove ticket widget from custom events
		add_filter( 'eventbrite_ticket_form_widget', array( $this, 'remove_ticket_form_widget' ), 11, 2 );

		// Add call to action for custom events
		add_filter( 'eventbrite_ticket_form_widget', array( $this, 'add_call_to_action' ), 12, 2 );

		// Add link to thumbnail of custom events
		add_filter( 'post_thumbnail_html', array( $this, 'filter_event_logo' ), 9, 2 );

		// Set custom template for custom events post type
		add_filter( 'template_include', array( $this, 'display_template' ), 11, 2 );
		
		// Set custom classes for custom event post type
		add_filter( 'post_class', array( $this, 'filter_post_classes' ) );

		// Change venue link with customm taxnomy link
		add_filter( 'eventbrite_venue_get_archive_link', array( $this, 'venue_url' ) );

		// Change organizer link with customm taxnomy link
		add_filter( 'author_link', array( $this, 'organizer_url' ), 11, 2 );
 	}

 	/**
 	 * Save setting to flush rewrite rules if the custom events are enabled or disabled
 	 *
 	 * @access public
 	 * 
 	 * @param  string $option The name of the option
 	 * @param  mixed $value   The value of the option
 	 */
 	public function flush_rules( $option, $value ){
 		eventbrite_set_setting( 'flush_rules', true );
 	}

	/**
	 * Register new post type to display custom events
	 *
	 * @access public
	 */
	public function register_post_type() {
		register_post_type( $this->post_type,
	        array(
	            'labels' => array(
	                'name' => $this->post_name_plural,
	                'singular_name' => $this->post_name_singular,
	                'add_new' => __( 'Add New', 'eventbrite-api' ),
	                'add_new_item' => sprintf( __( 'Add New %s', 'eventbrite-api' ), $this->post_name_singular ),
	                'edit' => __( 'Edit', 'eventbrite-api' ),
	                'edit_item' => sprintf( __( 'Edit %s', 'eventbrite-api' ), $this->post_name_singular ),
	                'new_item' => sprintf( __( 'New %s', 'eventbrite-api' ), $this->post_name_singular ),
	                'view' => __( 'View', 'eventbrite-api' ),
	                'view_item' => sprintf( __( 'View %s', 'eventbrite-api' ), $this->post_name_singular ),
	                'search_items' => sprintf( __( 'Search %s', 'eventbrite-api' ), $this->post_name_plural ),
	                'not_found' => sprintf( __( 'No %s found', 'eventbrite-api' ), $this->post_name_plural ),
	                'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'eventbrite-api' ), $this->post_name_plural ),
	                'parent' => sprintf( __( 'Parent %s', 'eventbrite-api' ), $this->post_name_singular ),
	            ),
	 
	            'menu_position' => 15,
	            'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
	            'taxonomies' => array( '' ),
	            'menu_icon' => 'dashicons-calendar',
	            'has_archive' => false,
				'public' => false,
				'show_ui' => true,
				'show_in_menu' => true,
				'show_in_nav_menus' => false,
				'show_in_admin_bar' => false,
				'can_export' => true,
				'has_archive' => false,
				'exclude_from_search' => true,
				'publicly_queryable' => true,
				'rewrite' => array( 'slug' => 'event' ) 
			)
	    );
	}

	/**
	 * Register new taxonomies
	 *
	 * @access public
	 */
	public function register_custom_taxonomies() {

		// Register the organizer taxonomy
		register_taxonomy( 
			$this->organizer_taxonomy,
			$this->post_type,
	        array(
	            'labels' => array(
	                'name'              => $this->organizer_taxonomy_plural,
					'singular_name'     => $this->organizer_taxonomy_singular,
					'search_items'      => sprintf( __( 'Search %s', 'eventbrite-api' ), $this->organizer_taxonomy_plural ),
					'all_items'         => sprintf( __( 'All %s', 'eventbrite-api' ), $this->organizer_taxonomy_plural ),
					'parent_item'       => sprintf( __( 'Parent %s', 'eventbrite-api' ), $this->organizer_taxonomy_singular ),
					'parent_item_colon' => sprintf( __( 'Parent %s:', 'eventbrite-api' ), $this->organizer_taxonomy_singular ),
					'edit_item'         => sprintf( __( 'Edit %s', 'eventbrite-api' ), $this->organizer_taxonomy_singular ),
					'update_item'       => sprintf( __( 'Update %s', 'eventbrite-api' ), $this->organizer_taxonomy_singular ),
					'add_new_item'      => sprintf( __( 'Add New %s', 'eventbrite-api' ), $this->organizer_taxonomy_singular ),
					'new_item_name'     => sprintf( __( 'New %s Name', 'eventbrite-api' ), $this->organizer_taxonomy_singular ),
					'menu_name'         => $this->organizer_taxonomy_singular,
	            ),
	 
            	'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'organizer' ),
			)
	    );


		// Register the venue taxonomy
	    register_taxonomy( 
			$this->venue_taxonomy,
			$this->post_type,
	        array(
	            'labels' => array(
	                'name'              => $this->venue_taxonomy_plural,
					'singular_name'     => $this->venue_taxonomy_singular,
					'search_items'      => sprintf( __( 'Search %s', 'eventbrite-api' ), $this->venue_taxonomy_plural ),
					'all_items'         => sprintf( __( 'All %s', 'eventbrite-api' ), $this->venue_taxonomy_plural ),
					'parent_item'       => sprintf( __( 'Parent %s', 'eventbrite-api' ), $this->venue_taxonomy_singular ),
					'parent_item_colon' => sprintf( __( 'Parent %s:', 'eventbrite-api' ), $this->venue_taxonomy_singular ),
					'edit_item'         => sprintf( __( 'Edit %s', 'eventbrite-api' ), $this->venue_taxonomy_singular ),
					'update_item'       => sprintf( __( 'Update %s', 'eventbrite-api' ), $this->venue_taxonomy_singular ),
					'add_new_item'      => sprintf( __( 'Add New %s', 'eventbrite-api' ), $this->venue_taxonomy_singular ),
					'new_item_name'     => sprintf( __( 'New %s Name', 'eventbrite-api' ), $this->venue_taxonomy_singular ),
					'menu_name'         => $this->venue_taxonomy_singular,
	            ),
	 
            	'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'venue' ),
			)
	    );
	}


	/**
	 * Add a meta box where to display additional fields for the new post type
	 * 
	 * @access public
	 */
	public function add_meta_box(){
		add_meta_box( $this->post_type . '_fields',
	        __( 'Event Details', 'eventbrite-api' ),
	        array( $this, 'display_fields' ),
	        $this->post_type, 'normal', 'high'
	    );
	}


	/**
	 * Display custom fields
	 * 
	 * @access public
	 * 
	 * @param  object $event Event post object
	 * @return void
	 */
	public function display_fields( $event ){

		$defaults = array(
			'eb_event_start_date' => date( 'Y-m-d', strtotime( '+1 day' ) ),
			'eb_event_start_time' => '',
			'eb_event_end_date' => '',
			'eb_event_end_time' => '',
			'eb_event_url' => '',
		);

		$values = $this->get_field_values( $event->ID, $defaults );
	    require 'admin/custom-event-metabox.php';
	}


	/**
	 * Get values from post meta base on an array.
	 * 
	 * @access public
	 * 
	 * @param  int $pid    Post ID
	 * @param  array $fields Array of meta keys and default values
	 * @return array
	 */
	public function get_field_values( $pid, $fields ){
		foreach( $fields as $meta_key => $default ){
			// Add meta value to array only if it has a value, else keep the default value
			if( get_post_meta( $pid, $meta_key, true ) ){
				$fields[ $meta_key ] = get_post_meta( $pid, $meta_key, true );
			}
		}
		return $fields;
	}


	/**
	 * Save custom field values
	 * 
	 * @access public
	 * 
	 * @param  int] $event_id Event post ID
	 * @param  object $event    Event post object
	 * @return void
	 */
	public function save_field_values( $event_id, $event ) {
	    // Check post type for Other Events
	    if ( $event->post_type == $this->post_type ) {
	        
	        // Add all key to an array for easier validation and manipulation
	        $keys = array(
	        	'eb_event_url',
	        	'eb_event_start_date',
	        	'eb_event_start_time',
	        	'eb_event_end_date',
	        	'eb_event_end_time',
	    	);

	        // Add all values from POST
	        $values = array();
	    	foreach( $keys as $key ){
	    		if( isset( $_POST[ $key ] ) ){
	    			$values[ $key ] = stripslashes_deep( (string)$_POST[ $key ] );
	    		}else{
	    			$values[ $key ] = '';
	    		}
	    	}

	        // Sanitize the user inputs before saving
	        $this->sanitize_values( $values );

	   		// Save the values
	   		foreach( $values as $key => $value ){
	   			update_post_meta( $event_id, $key, $value );
	   		}
	    }
	}


	/**
	 * Sanitize the user inputs before saving
	 * 
	 * @access public
	 * 
	 * @param  array $values Array of values
	 * @return array The sanitized array
	 */
	public function sanitize_values( $values ){
		foreach( $values as $key => $value ){
			if( ! $value ){
				continue;
			}

			if( $key == 'eb_event_url' ){
				if( !filter_var ( $value, FILTER_SANITIZE_URL) ){
					$values[ $key ] = '';
				}
			}

			if( $key == 'eb_event_start_date' ){
				$values[ $key ] = date( 'Y-m-d', strtotime( $value ) );
			}

			if( $key == 'eb_event_start_time' ){
				$values[ $key ] = date( 'H:i', strtotime( date( 'Y-m-d ' ) . $value . ':00' ) );
			}

			if( $key == 'eb_event_end_date' ){
				$values[ $key ] = date( 'Y-m-d', strtotime( $value ) );
			}

			if( $key == 'eb_event_end_time' ){
				$values[ $key ] = date( 'H:i', strtotime( date( 'Y-m-d ' ) . $value . ':00' ) );
			}

			if( $key == 'eb_event_organizer' || $key == 'eb_event_venue' ){
				$values[ $key ] = sanitize_text_field( $value );
			}
		}
		return $value;
	}


	/**
	 * Add custom events to the Eventbrite events result.
	 * 
	 * @access public
	 *
	 * @param object $query api_results object
	 * @param object $object WP_Query object
	 * @return void
	 */
	public function add_custom_events_to_results( $results, $object ){//global $wp_query; print_r($wp_query);exit();

		// Abort if the query is for a single event
		if( !empty( $object->query['p'] ) ){
			return $results;
		}

		// Abort if certain filters are applied
		// This can be bypassed by adding the query variable: allow_custom_events
		if( empty( $object->query['allow_custom_events'] ) && ( !empty( $object->query['venue_id'] ) ||  !empty( $object->query['category_id'] ) ||  !empty( $object->query['subcategory_id'] ) ||  !empty( $object->query['format_id'] ) ) ){
			return $results;
		}

		// Abort if user has set custom events to be disallowed for this query
		if( !empty( $object->query['disallow_custom_events'] ) ){
			return $results;
		}

		$query = array(
			'post_type' => $this->post_type,
			'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'eb_event_start_date',
					'value' => date( 'Y-m-d' ),
					'compare' => '>=',
					'type' => 'date'
				),
				array(
					'key' => 'eb_event_end_date',
					'value' => date( 'Y-m-d' ),
					'compare' => '>=',
					'type' => 'date'
				)
			)
		);

		if( get_post_type() == $this->post_type ){
			$query['p'] = get_the_ID();
		}

		// Query existing custom events that are today or in the future
		$events = new WP_Query( $query );

		// We need the filter for displaying content
		if( ! has_filter( 'the_contnet', 'wpautop' ) ){
			add_filter( 'the_content', 'wpautop' );
		}


		// Add events
		foreach( $events->posts as $event ){

			// Apply the_content filter to the content as eventbrite-api listings expects it to have paragraphs
			$event->post_content = apply_filters( 'the_content', $event->post_content );

			// Get the start date
			$start_date = '';
			if( get_post_meta( $event->ID, 'eb_event_start_date', true ) && get_post_meta( $event->ID, 'eb_event_start_time', true ) ){
				$start_date = get_post_meta( $event->ID, 'eb_event_start_date', true ) . " " . get_post_meta( $event->ID, 'eb_event_start_time', true ) . ':00';
			} else if( get_post_meta( $event->ID, 'eb_event_start_date', true ) ){
				$start_date = get_post_meta( $event->ID, 'eb_event_start_date', true );
			}

			// Get the end date
			$end_date = '';
			if( get_post_meta( $event->ID, 'eb_event_end_date', true ) && get_post_meta( $event->ID, 'eb_event_end_time', true ) ){
				$end_date = get_post_meta( $event->ID, 'eb_event_end_date', true ) . " " . get_post_meta( $event->ID, 'eb_event_end_time', true ) . ':00';
			} else if( get_post_meta( $event->ID, 'eb_event_end_date', true ) ){
				$end_date = get_post_meta( $event->ID, 'eb_event_end_date', true );
			}

			// Show the event only if it has a date set
			if( $start_date ){
				$event->post_date = date( 'Y-m-d\TH:i:s', strtotime( $start_date ) );
				$event->post_date_gmt = date( 'Y-m-d\TH:i:s\z', strtotime( $start_date ) );
				$event->start = (object) array(
					'timezone' => date_default_timezone_get(),
					'local' => $event->post_date,
					'utc' => $event->post_date_gmt
				);

				if( $end_date ){
					$event->end = (object) array(
						'timezone' => date_default_timezone_get(),
						'local' => date( 'Y-m-d\TH:i:s', strtotime( $end_date ) ),
						'utc' => date( 'Y-m-d\TH:i:s\z', strtotime( $end_date ) )
					);
				}else{
					$event->end = (object) array(
						'timezone' => date_default_timezone_get(),
						'local' => $event->post_date,
						'utc' => $event->post_date_gmt
					);
				}

				$event->url = get_post_meta( $event->ID, 'eb_event_url', true );
				$event->logo_url = get_the_post_thumbnail_url( $event->ID, 'eventbrite-event' );
				$event->category = get_post_meta( $event->ID, 'eb_event_category', true );
				$event->public = 1;

				// Add organizer info
				$organizers = wp_get_post_terms( $event->ID, $this->organizer_taxonomy );
				$organizer = reset( $organizers );
				if( $organizer ){
					$event->organizer = (object) array(
						'description' => (object) array(
							'text' => '',
							'html' => ''
						),
						'long_description' => (object) array(
							'text' => '',
							'html' => ''
						),
						'logo' => '',
						'resource_url' => '',
						'id' => $organizer->name,
						'name' => $organizer->name,
						'url' => '',
						'vanity_url' => '',
						'num_past_events' => 0,
						'num_future_events' => 0,
						'logo_id' => ''
					);
				}

				// Add venue info
				$venues = wp_get_post_terms( $event->ID, $this->venue_taxonomy );
				$venue = reset( $venues );
				if( $venue ){
					$event->venue = (object) array(
						'address' => (object) array(),
						'long_description' => (object) array(
							'text' => '',
							'html' => ''
						),
						'resource_url' => '',
						'id' => '',
						'name' => $venue->name,
						'latitude' => 0,
						'longitute' => 0
					);
				}
				
				$results->events[] = $event;
			}
		}

		// Order events by date ascending
		usort( $results->events, function( $a, $b ){
			return strtotime( $a->post_date ) > strtotime( $b->post_date );
		} );

		return $results;
	}



	public function filter_events( $results, $object ){

		// Filter events by organizer taxonomy
		if( is_tax( $this->organizer_taxonomy ) ){
			$object = get_queried_object();
			foreach( $results->events as $index => $event ){
				if( $event->organizer->name != $object->name ){
					unset( $results->events[ $index ] );
				}
			}
		}

		// Filter events by venue taxonomy
		if( is_tax( $this->venue_taxonomy ) ){
			$object = get_queried_object();
			foreach( $results->events as $index => $event ){
				if( $event->venue->name != $object->name ){
					unset( $results->events[ $index ] );
				}
			}
		}

		// Filter out specified IDs: 'post__not_in'
		if ( isset( $object->query['post__not_in'] ) && is_array( $object->query['post__not_in'] ) ) {
			foreach( $results->events as $index => $event ){
				if( in_array( $event->id, $object->query['post__not_in'] ) ){
					unset( $results->events[ $index ] );
				}
			}
		}

		return $results;
	}


	/**
	 * Remove ticket form widget from custom events and display the excerpt
	 * 
	 * @access public
	 *
	 * @param  string $ticket_html HTML containing the form
	 * @param  array $src          Options used to generate the html
	 * @return string
	 */
	public function remove_ticket_form_widget( $ticket_html, $src ){
		if( get_post_type() == $this->post_type ){
			if( is_single() ){
				$ticket_html = "";
			}else{
				$ticket_html = wpautop( get_the_excerpt() );
			}
		}
		return $ticket_html;
	}


	/**
	 * Add call to action for custom event listings
	 * 
	 * @access public
	 *
	 * @param  string $ticket_html HTML containing the form
	 * @param  array $src          Options used to generate the html
	 * @return string
	 */
	public function add_call_to_action( $ticket_html, $src ){
		// Proceed only if the url is defined
		if( get_post_meta( get_the_ID(), 'eb_event_url', true ) ){
			$label = __( 'Tickets', 'eventbrite-api' );
			$url = get_post_meta( get_the_ID(), 'eb_event_url', true );

			// Allow option to change label
			$label = apply_filters( 'eventbrite_custom_events_call_to_action_label', $label );

			// Allow option to change url
			$url = apply_filters( 'eventbrite_custom_events_call_to_action_url', $url );
			
			// Add the button
			$ticket_html .= sprintf(
				'<p class="eventbrite-join-btn-wrap"><a class="button eventbrite-join-btn" href="%1$s" target="_blank">%2$s</a></p>',
				$url,
				$label
			);
		}
		return $ticket_html;
	}

	/**
	 * Replace featured images with the Eventbrite event logo.
	 *
	 * @access public
	 *
	 * @param  string $html Original unfiltered HTML for a featured image.
	 * @param  int $post_id The current event ID.
	 * @return string HTML <img> tag for the Eventbrite logo linked to the event single view.
	 */
	public function filter_event_logo( $html, $post_id ){
		// Are we dealing with an Eventbrite custom event?
		if ( !empty( $this->post_type ) && get_post_type( $post_id ) == $this->post_type ) {
			// Does the event have a logo set?
			if ( $html ) {
				// No need for a permalink on event single views.
				if ( !is_single() ) {
					$html = sprintf( '<a class="post-thumbnail" href="%1$s">%2$s</a>',
						esc_url( get_the_permalink() ),
						$html
					);
				}
			}
		}
		return $html;
	}


	/**
	 * If current page is single and is this post type then display it on the eventbrite-api single template
	 *
	 * @access public
	 *
	 * @param boolean $display If returns true then the eventbrite-api single template will be used
	 * @return boolean
	 */
	public function display_template( $template ){
		if ( is_single() && get_post_type() == $this->post_type ) {
			// Display template only if a custom single template isn't available
			if( basename( $template ) !=  'single-' . $this->post_type . '.php' ){
				$template = Eventbrite_Templates::get_default_template_path( 'eventbrite-single' );
			}
		}

		if ( is_tax( $this->organizer_taxonomy ) ) {
			// Display template only if a custom single template isn't available
			if( basename( $template ) !=  'archive-' . $this->organizer_taxonomy . '.php' ){
				$template = Eventbrite_Templates::get_default_template_path( 'eventbrite-archive' );
			}
		}
		if ( is_tax( $this->venue_taxonomy ) ) {
			// Display template only if a custom single template isn't available
			if( basename( $template ) !=  'archive-' . $this->venue_taxonomy . '.php' ){
				$template = Eventbrite_Templates::get_default_template_path( 'eventbrite-archive' );
			}
		}
		return $template;
	}


	/**
	 * Adjust classes for Event <article>s.
	 *
	 * @access public
	 *
	 * @param  array $classes Unfiltered post classes
	 * @return array Filtered post classes
	 */
	public function filter_post_classes( $classes ) {
		if ( get_post_type() == $this->post_type ) {
			$classes[] = 'eventbrite-event';

			if ( ! empty( get_post()->logo_url ) ) {
				$classes[] = 'has-post-thumbnail';
			}
		}

		return $classes;
	}


	/**
	 * Replace venue url with custom taxnomy url
	 *
	 * @access public
	 *
	 * @param  string $url The original venue url
	 * @return string      The new url
	 */
	public function venue_url( $url ){
		if( get_post_type() === $this->post_type ){
			$venues = wp_get_post_terms( get_the_ID(), $this->venue_taxonomy );
			$venue = reset( $venues );
			if( $venue ){
				$url = get_term_link( $venue );
			}
		}
		return $url;
	}

	/**
	 * Replace organizer url with custom taxnomy url
	 *
	 * @access public
	 *
	 * @param  string $url The original venue url
	 * @return string      The new url
	 */
	public function organizer_url( $url, $uid ){
		// Replace only for empty user IDs and in the custom post type
		if( get_post_type() === $this->post_type && !$uid ){
			$organizers = wp_get_post_terms( get_the_ID(), $this->organizer_taxonomy );
			$organizer = reset( $organizers );
			if( $organizer ){
				$url = get_term_link( $organizer );
			}
		}
		return $url;
	}

	/**
	 * Add admin settings for custom events
	 *
	 * @access public
	 * 
	 * @param  array $options Array of options
	 * @return array
	 */
	public function admin_options( $options ){
		$options['enable_custom_events'] = array(
			'name' => __( 'Enable Custom Events', 'eventbrite-api' ),
			'default' => true,
			'type' => 'boolean'
		);
		return $options;
	}
 }

 new Eventbrite_Custom_Events();
