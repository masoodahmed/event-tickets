<?php


interface Tribe__Tickets__REST__Interfaces__Post_Repository {

	/**
	 * Returns the array representation of a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id A ticket post or post ID.
	 * @param string $context Context of data.
	 *
	 * @return array|WP_Error The ticket data or a `WP_Error` detailing the issue on failure.
	 */
	public function get_ticket_data( $ticket_id, $context = '' );

	/**
	 * Returns an attendee data.
	 *
	 * @since  TBD
	 *
	 * @param int $attendee_id An attendee post or post ID.
	 * @param string $context Context of data.
	 *
	 * @return array|WP_Error The attendee data or a `WP_Error` detailing the issue on failure.
	 */
	public function get_attendee_data( $attendee_id, $context = '' );
}