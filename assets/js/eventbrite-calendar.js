var $ = jQuery;

$(document).ready( function() {
	$("body").on("click",".eventbrite-calendar-icons a",function(e){
		e.preventDefault();
		$( this ).parent().find( '.active' ).removeClass( 'active' );
		$( this ).addClass( 'active' );
		if( $( this ).is( '.event-display-list' ) ){
			$( '.eventbrite-event-calendar' ).hide();
			$( '.eventbrite-event-list' ).show();
		}

		if( $( this ).is( '.event-display-calendar' ) ){
			init_eventbrite_calendar();
			$( '.eventbrite-event-calendar' ).show();
			$( '.eventbrite-event-list' ).hide();
		}
	});
});

function init_eventbrite_calendar(){
	if( typeof eventbrite_calendar_data != 'undefined' ){
		$( '.eventbrite-full-clndr' ).each( function(){
			$( this ).clndr({
				template: $( this ).find( '.full-clndr-template' ).html(),
				events: eventbrite_calendar_data,
				forceSixRows: true
			});
		});
	}
}