<?php
/**
 * Tickets Commerce: Checkout Cart Fees Section
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart-fees.php
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
 * @var array[]          $active_fees [Global] List of active fees to be displayed, where each fee contains:
 * @var float            $sum_of_fees [Global] The total sum of all active fees.
 */

?>

<div class="tribe-tickets__commerce-checkout-cart-footer-order-modifier-fees">
	<ul>
		<?php
		foreach ( $active_fees as $fee ) :
			?>
			<li>
		<span
			class="tribe-tickets__commerce-checkout-cart-footer-quantity-label"><?php echo $fee['display_name']; ?>:</span>
				<span
					class="tribe-tickets__commerce-checkout-cart-footer-quantity-number"><?php echo $fee['fee_amount']; ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
