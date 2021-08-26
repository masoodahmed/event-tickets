<?php
/**
 * Tickets Commerce: Checkout Cart Item
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/item.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var string           $paypal_attribution_id [Global] What is our PayPal Attribution ID.
 * @var bool             $must_login            [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url             [Global] The site's login URL.
 * @var string           $registration_url      [Global] The site's registration URL.
 * @var int              $section               Which Section that we are going to render for this table.
 * @var \WP_Post         $post                  Which Section that we are going to render for this table.
 * @var array            $item                  Which item this row will be for.
 */

// Bail if there's no ticket id.
if ( empty( $item['ticket_id'] ) ) {
	return;
}

$classes = [
	'tribe-tickets__commerce-checkout-cart-item',
	get_post_class( '', $item['ticket_id'] ),
	'tribe-common-b1',
];

$attributes = [
	'data-ticket-id'       => (string) $item['ticket_id'],
	'data-ticket-quantity' => (string) $item['quantity'],
	'data-ticket-price'    => (string) $provider->get_price_value( $item['ticket_id'] ),
];

?>
<article
	<?php tribe_classes( $classes ); ?>
	<?php tribe_attributes( $attributes ); ?>
>

	<?php $this->template( 'checkout/cart/item/details', [ 'item' => $item ] ); ?>

	<?php $this->template( 'checkout/cart/item/price', [ 'item' => $item ] ); ?>

	<?php $this->template( 'checkout/cart/item/quantity', [ 'item' => $item ] ); ?>

	<?php $this->template( 'checkout/cart/item/sub-total', [ 'item' => $item ] ); ?>

</article>
