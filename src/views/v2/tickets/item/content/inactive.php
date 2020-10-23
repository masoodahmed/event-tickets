<?php
/**
 * Block: Tickets
 * Ticket Item Inactive Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/content/inactive.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var bool $is_sale_past True if tickets are past sale.
 */


$message = $is_sale_past
	/* translators: %s: Tickets label */
	? sprintf( __( '%s are no longer available', 'event-tickets' ), tribe_get_ticket_label_plural( 'event-tickets' ) )
	/* translators: %s: Tickets label */
	: sprintf( __( '%s are not yet available', 'event-tickets' ), tribe_get_ticket_label_plural( 'event-tickets' ) );
?>
<div
	class="tribe-tickets__tickets-item-content tribe-tickets__tickets-item-content--inactive"
>
	<?php echo esc_html( $message ); ?>
</div>
