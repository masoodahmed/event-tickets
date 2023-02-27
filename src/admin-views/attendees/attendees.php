<?php
/**
 * Global Attendees screen.
 *
 * @since  TBD
 *
 * @var Tribe_Template            $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

if ( ! empty( $event_id ) ) {
	return;
}
?>

<h1>
	<?php esc_html_e( 'Attendees', 'event-tickets' ); ?>
</h1>


<?php $this->template( 'attendees/attendees-table' ); ?>
