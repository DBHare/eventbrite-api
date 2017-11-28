<h1><?php _e( 'Eventbrite Settings', 'eventbrite-api' ) ?></h1>
<form action="" method="post">
	<input type="hidden" name="eb_save_settings" value="1">
	<table class="form-table">
		<?php
		// Get registered options 
		$options = $this->get_settings();
		$values = $this->get_setting_values();
		foreach( $options as $name => $option ){
			?>
			<tr>
				<th><?php echo $option["name"] ?></th>
				<td>
					<?php 
					if( $option['type'] == 'text' ){
						?><input type='text' name='<?php echo $name ?>' value='<?php echo $values[$name] ?>'><?php 
					}
					if( $option['type'] == 'boolean' ){
						?><input type='checkbox' name='<?php echo $name ?>' <?php if( $values[$name] ){ ?>checked="checked"<?php } ?> value='1'><?php 
					}
					?>
				</td>
			</tr>
			<?php 
		}

		?>
		<tr>
			<th></th>
			<td>
				<input type="submit" class="button button-primary button-large" value="<?php _e( 'Save Settings', 'eventbrite-api' ) ?>">
			</td>
		</tr>
	</table>
</form>