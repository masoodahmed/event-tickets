<?php
/**
 * Handles the caching of Order Summary objects.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Reports\Data;
 */
namespace TEC\Tickets\Commerce\Reports\Data;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Interface;
use WP_Post;

class Order_Summary_Cache_Controller extends Controller {
	
	/**
	 * The action to fire when the provider is registered.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_commerce_order_status_transition', [ $this, 'clear_cache_on_order_status_change' ], 10, 3 );
		add_action( 'tec_tickets_ticket_update', [ $this, 'clear_cache_on_ticket_add_or_update' ] );
		add_action( 'tec_tickets_ticket_add', [ $this, 'clear_cache_on_ticket_add_or_update' ] );
		add_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'clear_cache_on_ticket_add_or_update' ] );
	}
	
	/**
	 * Unregister the cache controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_commerce_order_status_transition', [ $this, 'clear_cache_on_order_status_change' ], 10, 3 );
		remove_action( 'tec_tickets_ticket_update', [ $this, 'clear_cache_on_ticket_add_or_update' ] );
		remove_action( 'tec_tickets_ticket_add', [ $this, 'clear_cache_on_ticket_add_or_update' ] );
		remove_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'clear_cache_on_ticket_add_or_update' ] );
	}
	
	/**
	 * Clear the cache when an order status changes.
	 *
	 * @since TBD
	 *
	 * @param Status_Interface      $new_status New post status.
	 * @param Status_Interface|null $old_status Old post status.
	 * @param WP_Post               $order The order post object.
	 *
	 * @return void
	 */
	public function clear_cache_on_order_status_change( $new_status, $old_status, $order ) {
		if ( Order::POSTTYPE !== get_post_type( $order->ID ) ) {
			return;
		}
		
		$event_ids = get_post_meta( $order->ID, Order::$events_in_order_meta_key, true );
		
		if ( empty( $event_ids ) ) {
			return;
		}
		
		foreach ( $event_ids as $event_id ) {
			$this->clear_cache( $event_id );
		}
	}
	
	/**
	 * Clear the cache when a ticket is added or updated.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function clear_cache_on_ticket_add_or_update( $post_id ) {
		$this->clear_cache( (int) $post_id );
	}
	
	/**
	 * Clear the order summary cache for a specific post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function clear_cache( int $post_id ): void {
		wp_cache_delete( $post_id, 'tec_tickets_commerce_order_summary' );
	}
}