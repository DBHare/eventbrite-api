<?php
/**
 * Eventrite_calendar class, for displaying events in a calendar format
 *
 * @package Eventbrite_API
 */

class Eventrite_Calendar{

	/**
	 * Our constructor.
	 *
	 * @access public
	 */
	public function __construct() {

		// Register shortcodes
		add_shortcode( 'eventbrite_calendar', array( $this, 'display_calendar' ) );

		// Load necessary scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
	}


	/**
	 * Register scripts
	 *
	 * @access public
	 */
	public function register_scripts(){
		//plugin style definitions
	    wp_register_style('eventbrite-calendar', plugins_url( 'assets/css/calendar.css', dirname( __FILE__ ) ) );
	    wp_register_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
	    
	    //plugin scripts
		wp_register_script('moment', plugins_url( 'assets/js/moment-2.8.3.js', dirname( __FILE__ ) ), array('jquery'));
		wp_register_script('clndr', plugins_url( 'assets/js/clndr.js', dirname( __FILE__ ) ), array('jquery', 'moment', 'underscore'));
	 	wp_register_script('eventbrite-calendar', plugins_url( 'assets/js/eventbrite-calendar.js', dirname( __FILE__ ) ), array('jquery', 'clndr'));
	}


	/**
	 * Enqueue scripts when needed
	 *
	 * @access public
	 */
	public function enqueue_scripts(){
		wp_enqueue_style('eventbrite-calendar');
		wp_enqueue_style('font-awesome');
		wp_enqueue_script('moment');
		wp_enqueue_script('clndr');
		wp_enqueue_script('eventbrite-calendar');
	}
	

	/**
	 * Format the event data to be compatible with clndr.js
	 *
	 * @access public 
	 * 
	 * @param  object $eb_events Eventbrite_Query object
	 * @return array
	 */
	public function format_eventbrite_data( $eb_events ){
		// Create $event_holder. Will contain formatted event information for Clndr.js
		$event_holder = array();

		$events_array = get_object_vars( $eb_events );
		// All Event information from Eventbrite
		$all_events = $events_array['posts'];


		foreach ($all_events as &$value) {
			$temp_event = get_object_vars( $value );
			$image_url = $temp_event['logo_url'];
			$url = $temp_event['url'];

			// Check if not custom event
			if( empty( $temp_event['post_type'] ) ){
				$tickets_array = $temp_event['tickets'];
			
				// Determine if event is sold out
				$event_status = __( 'Coming Soon', 'eventbrite-api' );
				$sale_status = 'NOT_YET_ON_SALE';
				
				foreach ($tickets_array as &$ticket) {
					// Get Ticket Status
					$on_sale_status = get_object_vars($ticket)['on_sale_status'];
					
					// Traverse visible tickets
					switch ( $on_sale_status ) {
					    case 'AVAILABLE':
					        $event_status = __( 'Tickets', 'eventbrite-api' );
					        $sale_status = $on_sale_status;
					        break;
					    case 'SOLD_OUT':
					    	if($event_status != __( 'Tickets', 'eventbrite-api' ) ){
						    	$event_status = __( 'Sold Out', 'eventbrite-api' );
						    	$sale_status = $on_sale_status;
					    	}
					    	
					    	break;
					        
					    case 'UNAVAILABLE':
					    	if($event_status != __( 'Tickets', 'eventbrite-api' ) ){
						    	$event_status = __( 'Sold Out', 'eventbrite-api' );
						    	$sale_status = $on_sale_status;
					    	}
					    case 'NOT_YET_ON_SALE':
					    	if($event_status != __( 'Tickets', 'eventbrite-api' ) ){
						    	$event_status = __( 'Coming Soon', 'eventbrite-api' );
						    	$sale_status = $on_sale_status;
					    	}
					}
				}

				// Add affiliate code for Eventbrite links
				$url = $url . '?aff=odwdwdwordpress';
			}else{
				$event_status = __( 'Tickets', 'eventbrite-api' );
				$sale_status = 'AVAILABLE';
			}
			
			$date_index = 'eb-'.date( "m.d.y", strtotime( $temp_event['post_date'] ) );
			
			if( empty( $url ) ){
				$url = "#";
				$sale_status = 'NO_LINK';
			}
			$temp = array(
				"date" => $temp_event['post_date'],
				"title" => substr( $temp_event['post_title'], 0, 20 ),
				"url" => $url,
				"status" => $event_status,
				"status_class" => sanitize_title( $sale_status ),
				"event_image" => $image_url
			);
						
		    $event_holder[$date_index] = $temp;
		}
		
		return $event_holder;
	}


	/**
	 * Function to get information about events for calendar
	 * Outputs json format
	 *
	 * @access public
	 */
	public function get_eventbrite_events( $query ) {
		
		$paged = 1;

		// Reset some query variables if set
		if( isset( $query['nopaging'] ) ){
			$query['nopaging'] = false;
		}
		$query['paged'] = $paged;

		// Make the query
		$events = new Eventbrite_Query( apply_filters( 'eventbrite_query_args', $query ) );
		$max_num_pages = $events->max_num_pages;

		$all_events = $this->format_eventbrite_data( $events );
		$paged++;

		while( $paged <= $max_num_pages ){
			$query['paged'] = $paged;
		
			$events = new Eventbrite_Query( apply_filters( 'eventbrite_query_args', $query ) );
			if( $events->post_count <= 0 ){
				break;
			}

			$events = $this->format_eventbrite_data( $events );
			$all_events = array_merge( $all_events, $events );

			if( count( $all_events ) >= 200 ){
				break;
			}
			$paged++;
		}
		
		//Remove Duplicates
		$final_events = array();
		$loop_counter = 0;
	
		foreach($all_events as $key=>$value){
			$final_events[$loop_counter] = $value;
			$loop_counter++;
		}
		
		return $final_events;
	}


	/**
	 * Display calendar data and html
	 *
	 * @access public
	 * 
	 * @param  object $query Eventbrite_Query object
	 * @return void Outputs HTML
	 */
	public function display_calendar( $query ) {
		// Enqueue necessary assets
		$this->enqueue_scripts();

		// Display calendar template HTML
		$template = $this->display_calendar_template();
		$template = apply_filters( 'eventbrite_calendar_template', $template );
		echo $template;

		// Save events data in a variable for javascript processing
		?>
		<script type="text/javascript">
			var eventbrite_calendar_data = <?php echo json_encode( $this->get_eventbrite_events( $query ) ) ?>;
		</script>
		<?php 
	}

	/**
	 * Get the calendar template for Clndr.js
	 *
	 * @access public
	 * 
	 * @return string HTML
	 */
	public function display_calendar_template() {
		ob_start();
		?>
		<div class="eventbrite-full-clndr">
          <script type="text/template" class="full-clndr-template">
            <div class="clndr-controls">
              <div class="clndr-previous-button"><i class="fa fa-chevron-left"></i></div>
              <div class="clndr-next-button"><i class="fa fa-chevron-right"></i></div>
              <div class="current-month"><%= month %> <%= year %></div>

            </div>
            <div class="clndr-grid">
              <div class="days-of-the-week">
                <% _.each(daysOfTheWeek, function(day) { %>
                  <div class="header-day"><%= day %></div>
                <% }); %>
              </div>
              <div class="days">
                <% _.each(days, function(day) { %>
                  <div class="<%= day.classes %>" id="<%= day.id %>">
                  <span class="day-number"><%= day.day %></span>
                  
                  <!--List events in calendar block-->
                  <div class="clndr-event">
					<% _.each(day.events, function(event){ %>
						<div class="nectar-calendar event <%= event.type %>">
						<a href="<%= event.url %>" target="_blank"><img src="<%= event.event_image %>" alt="Nectar calendar image"/></a>
						<div class="event-title">
							<strong><%= event.title %></strong>
						</div>
						<div class="buy-tickets">
							<a class="get-ticket <%= event.status_class %>" href="<%= event.url %>" target="_blank"> <%= event.status %></a>
						</div>
						</div>
					<% }) %>
                  </div>
               </div><!--Day-->
                <% }); %>
              </div>
            </div>
          </script>
        </div>
		<?php
		return ob_get_clean();
	}
}

new Eventrite_Calendar();