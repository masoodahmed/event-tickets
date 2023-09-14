<?php

namespace Tribe\Tickets\Test\Commerce\TicketsCommerce;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use Tribe\Tickets\Test\Commerce\Ticket_Maker as Ticket_Maker_Base;
use TEC\Tickets\Commerce\Module as Module;

trait Order_Maker {

	use Ticket_Maker_Base;

	/**
	 * Takes a list of tickets and creates an order for them.
	 *
	 * @param array  $items       An array of ticket_id => quantity pairs.
	 * @param string $status_slug Optional. The slug of the final status for the order. Default is 'completed'.
	 * @param array  $overrides   Optional. An array of values to override, such as 'quantity'.
	 *
	 * @return false|\WP_Post The order post object or false if the order could not be created.
	 */
	protected function create_order( array $items, $status_slug = 'completed', array $overrides = [] ) {
		// Validate the status slug dynamically
		$status_class = "TEC\\Tickets\\Commerce\\Status\\" . ucfirst( $status_slug );

		if ( ! class_exists( $status_class ) ) {
			codecept_debug( 'Order_Maker.php failure. Invalid $status_slug' );
			return false; // Invalid status slug, return false
		}

		$cart = new Cart();

		foreach ( $items as $id => $quantity ) {
			// Check if an override for quantity exists
			if ( isset( $overrides['quantity'] ) ) {
				$quantity = $overrides['quantity'];
			}
			$cart->get_repository()->add_item( $id, $quantity );
		}

		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-' . uniqid() . '@test.com',
		];

		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );

		// Set the order to the status.
		tribe( Order::class )->modify_status( $order->ID, $status_class::SLUG );

		$cart->clear_cart();

		return $order;
	}
}
