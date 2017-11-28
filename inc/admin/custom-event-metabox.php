<table>  
    <tr>
        <td width="150">
        	<?php _e( 'Start Date', 'eventbrite-api' ) ?>
        </td>
         <td>
         	<input type="date" name="eb_event_start_date" value="<?php echo $values['eb_event_start_date']; ?>">
		</td>
    </tr>

    <tr>
        <td>
        	<?php _e( 'Start Time', 'eventbrite-api' ) ?>
        </td>
        <td>
            <input type="time" name="eb_event_start_time" value="<?php echo $values['eb_event_start_time']; ?>">
		</td>
    </tr>

    <tr>
        <td>
        	<?php _e( 'End Date', 'eventbrite-api' ) ?>
        </td>
         <td>
         	<input type="date" name="eb_event_end_date" value="<?php echo $values['eb_event_end_date']; ?>">
		</td>
    </tr>

    <tr>
        <td>
        	<?php _e( 'End Time', 'eventbrite-api' ) ?>
        </td>
        <td>
            <input type="time" name="eb_event_end_time" value="<?php echo $values['eb_event_end_time']; ?>">
		</td>
    </tr>

    <tr>
        <td>
        	<?php _e( 'URL', 'eventbrite-api' ) ?>
        </td>
        <td>
        	<input type="text" size="80" name="eb_event_url" value="<?php echo $values['eb_event_url']; ?>" />
        </td>
    </tr>
</table>