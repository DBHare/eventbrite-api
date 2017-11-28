<?php
/**
 * Eventbrite_Hook class, for handling instant changes.
 *
 * @package Eventbrite_API
 */

 class Eventbrite_Webhook {

 	// Parameter key for wehook
 	public $webhook_param;

 	// Webhook url
 	public $webhook_url;

	/**
	 * Our constructor.
	 *
	 * @access public
	 */
	public function __construct() {

		$this->webhook_param = 'eventbrite_webhook';
		$this->webhook_url = get_bloginfo( 'url' ) . '?' . $this->webhook_param;

		// Check to see if current page is called by Eventbrite
		$this->check_for_webhook();

 		// Register hooks.
		if( get_option( 'eventbrite_api_plugin_actived' ) ){
			$this->create_webhook();
		}
 	}

	/**
	 * Check to see if current page is called by Eventbrite
	 *
	 * @access private
	 */
	private function check_for_webhook() {
		// check if webhook variable is present
		if( isset( $_GET[ $this->webhook_param ] ) ){
			// delete all plugin cache
			$this->delete_transients();

			// confirmation message for webhook tests
			echo 'Eventbrite API webhook is working';

			// stop execution
			exit();
		}
	}

	/**
	 * If no webhook has been set up in Eventbrite then create one
	 * @return boolean Returns true if webhook has been setup
	 */
	public function create_webhook() {
		if( !$this->is_webhook_created() ){
			$url = $this->webhook_url;
			// There is a bug on Eventbrite where if you delete a webhook and try to created it again it will not create it.
			// To solve this we are adding a string at the end with the current time to create a new endpoint
			$url = $url . '#'. strtotime( 'now' );

			$params = array( 
				'endpoint_url' => $url,
				'actions' => 'event.published,event.updated,event.unpublished,order.placed'
			);
			
			$response = Eventbrite_Manager::$instance->request( 'create_webhook', $params );
			return is_object( $response ) && !empty( $response->id );
		}

		if( $this->is_webhook_created() ){
			return true;
		}
	}

	/**
	 * Check if webhook is created on Eventbrite
	 * @return boolean Returns true if webhook has been setup
	 */
	public function is_webhook_created() {
		$response = Eventbrite_Manager::$instance->request( 'get_webhooks', array(), 0, true ); 
		if( $response && !is_wp_error( $response ) && isset( $response->webhooks ) ){
			foreach( $response->webhooks as $webhook ){
				$url = strstr( $webhook->endpoint_url, '#', true );
				if( $url == $this->webhook_url ){
					return $webhook->id;
				}
			}
		}
	}

	/**
	 * Remove webhook from Eventbrite
	 * @return boolean Returns true if webhook has been deleted
	 */
	public function remove_webhook() {
		if( $id = $this->is_webhook_created() ){
			$response = Eventbrite_Manager::$instance->request( 'remove_webhook', array( 'id' => $id ), 0, true );
			if( !is_wp_error( $response ) && is_object( $response ) && isset( $response->success ) ){
				return $response->success;
			}
		}
	}
 }

new Eventbrite_Webhook();
