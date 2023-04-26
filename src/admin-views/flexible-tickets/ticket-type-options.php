<?php
/**
 * The dropdown to select the ticket type.
 *
 * @since TBD
 */

$origin = \Tribe__Tickets__Main::instance();
?>

<div id="ticket_type_options" class="input_block">
	<label class="ticket_form_label ticket_form_left" id="ticket_type_label" for="ticket_type">
		<?php echo esc_html_x( 'Type:', 'The label used in the ticket edit form for the type of the ticket.', 'event-tickets' ); ?>
	</label>
	<input
			type='hidden'
			id='ticket_type'
			name='ticket_type'
			value="series_pass"
	/>
	<div class="ticket_form_right"
		 style="display: flex; align-items: center">
		<img
				class="tribe-tickets-svgicon tec-tickets-icon tec-tickets-icon__ticket-type"
				src="<?php echo esc_url( tribe_resource_url( 'icons/series-pass.svg', false, null, $origin ) ); ?>"
				alt="<?php echo esc_html( tec_tickets_get_series_pass_singular_uppercase( 'admin_ticket_type_alt_text' ) ); ?>"
		/>
		<span class="ticket-type__text">
		<?php echo esc_html( tec_tickets_get_series_pass_singular_uppercase( 'admin_ticket_type_name' ) ); ?>
		</span>
		<span class="dashicons dashicons-editor-help ticket-type__help-icon"
			  title="<?php echo esc_attr(
					  sprintf(
							  // Translators: %s is the singular uppercase name of the Series Pass ticket type.
							  _x(
									  'A %s provides an attendee with access to all events in a Series.',
									  'The help text for the Series Pass icon in the ticket form.',
									  'event-tickets'
							  ),
							  tec_tickets_get_series_pass_singular_uppercase( 'admin_ticket_type_help_text' )
					  )
			  ); ?>">
		</span>
	</div>
</div>
