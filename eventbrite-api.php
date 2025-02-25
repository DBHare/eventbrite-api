<?php

/*
Plugin Name: Eventbrite API
Plugin URI: https://github.com/Automattic/eventbrite-api
Description: A WordPress plugin that integrates the Eventbrite API with WordPress and Keyring.
Version: 1.0.14
Author: Automattic/EstherGodoy/Dave
Author URI: http://automattic.com
License: GPL v2 or newer <https://www.gnu.org/licenses/gpl.txt>
*/

/**
 * Set our token option on activation if an Eventbrite connection already exists in Keyring.
 */
function eventbrite_check_existing_token() {
	// Bail if Keyring isn't activated.
	if ( ! class_exists( 'Keyring_SingleStore' ) ) {
		return;
	}

	// Get any Eventbrite tokens we may already have.
	$tokens = Keyring_SingleStore::init()->get_tokens( array( 'service'=>'eventbrite' ) );

	// If we have one, just use the first.
	if ( ! empty( $tokens[0] ) ) {
		update_option( 'eventbrite_api_token', $tokens[0]->unique_id );
	}

	// mark activation
	update_option( 'eventbrite_api_plugin_actived', true );
}
register_activation_hook( __FILE__, 'eventbrite_check_existing_token' );

/**
 * Load our API on top of the Keyring Eventbrite service.
 */
function eventbrite_api_load_keyring_service() {
	require_once( 'inc/class-eventbrite-api.php' );
}
add_action( 'keyring_load_services', 'eventbrite_api_load_keyring_service', 11 );

/**
 * Load classes.
 */
function eventbrite_api_init() {
	// Load Eventbrite_Requirements.
	require_once( 'inc/class-eventbrite-requirements.php' );

	// No point loading unless we have an active Eventbrite connection.
	if ( Eventbrite_Requirements::has_active_connection() ) {
		require_once( 'inc/functions.php' );
		require_once( 'inc/class-eventbrite-manager.php' );
		require_once( 'inc/class-eventbrite-query.php' );
		require_once( 'inc/class-eventbrite-templates.php' );
		require_once( 'inc/class-eventbrite-event.php' );
		require_once( 'inc/class-eventbrite-calendar.php' );
		require_once( 'inc/class-eventbrite-custom-events.php' );
		require_once( 'inc/class-eventbrite-webhook.php' );

		if( is_admin() ){
			require_once( 'inc/class-eventbrite-admin.php' );
		}

		// remove activation mark
		update_option( 'eventbrite_api_plugin_actived', false );

		// Remove webhook on plugin deactivation
		register_deactivation_hook( __FILE__, array( new Eventbrite_Webhook(), 'remove_webhook' ) );
	}
}
add_action( 'init', 'eventbrite_api_init' );
