<?php
/**
 * Tickets Commerce: Checkout Page Purchaser Info Email Field
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/purchaser-info/email.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this [Global] Template object.
 */

$label_classes = [
	'tribe-common-b3',
	'tribe-tickets__commerce-checkout-purchaser-info-email-field-label',
];

$field_classes = [
	'tribe-tickets__commerce-checkout-purchaser-info-form-field',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field-email',
];
?>
<div class="tribe-tickets__commerce-checkout-purchaser-info-field">
	<label for="tec-tc-purchaser-email" <?php tribe_classes( $label_classes ); ?>>
		<?php esc_html_e( 'Purchaser Email', 'event-tickets' ); ?>
	</label>
	<input
		type="email"
		id="tec-tc-purchaser-email"
		name="purchaser-email"
		autocomplete="off"
		<?php tribe_classes( $field_classes ); ?>
		placeholder="<?php esc_attr_e( 'Email', 'event-tickets' ); ?>"
		required
	/>
</div>
