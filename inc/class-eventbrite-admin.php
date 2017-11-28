<?php
/**
 * Eventbrite_Templates class, for handling Eventbrite template redirection, file includes, and rewrite rules.
 *
 * @package Eventbrite_API
 */

 class Eventbrite_Admin {
	/**
	 * Our constructor.
	 *
	 * @access public
	 */
	public function __construct() {
 		// Register hooks.
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );

		add_action( 'current_screen', array( $this, 'save_settings' ) );

		add_action( 'eventbrite-admin-options', array( $this, 'admin_options' ) );
 	}

	public function add_menu_pages(){
		$options = $this->get_settings();

		// Save the default values
		$this->set_settings_defaults( $options );

		// Display the settings page only if it has registered options
    	if( $options ){
			add_options_page(
		        __( 'Eventbrite Settings', 'eventbrite-api' ),
		        __( 'Eventbrite Settings', 'eventbrite-api' ),
		        'manage_options',
		        'eventbrite-api',
		        array( $this, 'display_settings_page')
		    );
		}
	}

	/**
	 * Save the default values for the settings page
	 *
	 * @access public
	 * 
	 * @param array $options Array of options
	 */
	public function set_settings_defaults( $options ){

		// Abort if options are empty or is not an array
		if( empty( $options ) || !is_array( $options ) ){
			return;
		}

		foreach( $options as $name => $option ){
			if( isset( $option['default'] ) && get_option( 'eb_settings_' . $name ) === false ){
				update_option( 'eb_settings_' . $name, $option['default'] );
			}
		}
	}

	/**
	 * Display the main settings page
	 * 
	 * @access public
	 * 
	 */
	public function display_settings_page(){
		include_once( 'admin/settings-page.php' );
	}

	/** 
	 * Get settings
	 *
	 * @access public
	 * @return array
	 */
	public function get_settings(){
		$options = apply_filters( 'eventbrite-admin-options', array() );
		if( !is_array( $options ) ){
			$options = array();
		}
		return $options;
	}

	/**
	 * Get settings option values
	 * 
	 * @access public
	 * 
	 * @param  array $options Array of settings options
	 * @return array          Array of values
	 */
	public function get_setting_values(){
		$options = $this->get_settings();
		$values = array();
		foreach( $options as $name => $option ){
			$value = get_option( 'eb_settings_' . $name );
			if( isset( $option['default'] ) && $value === false ){
				$value = $option['default'];
			}
			$values[$name] = $value;
		}
		return $values;
	}


	/**
	 * Save settings on form submission
	 * 
	 * @access public
	 * 
	 */
	public function save_settings(){
		// Abort if current section doesn't support this function
		if( !function_exists( 'get_current_screen' ) ){
			return;
		}
		$screen = get_current_screen();

		// Check if current screen is the eventbrite api settings page and if the form was submitted
		if( $screen->id == 'settings_page_eventbrite-api' && !empty( $_POST['eb_save_settings'] ) ){
			$options = $this->get_settings();
			foreach( $options as $name => $option ){
				$value = '';

				// Set the default value
				if( isset( $option['default'] ) && $value === false ){
					$value = $option['default'];
				}

				// Set the POST value if exists
				if( isset( $_POST[$name] ) ){
					$value = $_POST[$name];
				}

				// Sanitize value
				if( $option['type'] == 'text' ){
					$value = sanitize_text_field( $value );
				}
				if( $option['type'] == 'boolean' ){
					$value = (boolean)$value;
				}

				// Save value
				update_option( 'eb_settings_' . $name, $value );

				// Clean POST variables by redirecting to the same page
				wp_redirect( '?page=eventbrite-api' );
			}
		}
	}

	/**
	 * Add admin options
	 * @param array $options
	 * @return array
	 */
	public function admin_options( $options ){
		$options['show_private_events'] = array(
			'name' => __( 'Show Private Events', 'eventbrite-api' ),
			'default' => false,
			'type' => 'boolean'
		);
		return $options;
	}
 }

 new Eventbrite_Admin();
