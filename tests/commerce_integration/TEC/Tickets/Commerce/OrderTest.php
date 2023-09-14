<?php

namespace TEC\Tickets\Commerce;

use DateTime;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class OrderTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		add_filter(
			"tribe_tickets_ticket_object_is_ticket_cache_enabled",
			"__return_false"
		);
	}

	/**
	 * Helper method to create an event, ticket, and order.
	 *
	 * @param array $ticket_overrides Overrides for the ticket.
	 * @param array $order_overrides  Overrides for the order.
	 *
	 * @return array An array containing the event ID, ticket ID, and order ID.
	 */
	protected function setup_scenario(
		$ticket_overrides = [],
		$order_overrides = [],
		$default_slug = 'Created'
	) {
		$event_factory = new Event();
		$event_id      = $event_factory->create();

		$ticket_id = $this->create_tc_ticket( $event_id, 10, $ticket_overrides );

		codecept_debug("Setting Event status to: ". $default_slug);
		$order    = $this->create_order(
			[ $ticket_id => $order_overrides["quantity"] ?? 5 ],
			$default_slug
		);
		$order_id = $order->ID;

		codecept_debug($order);

		return [ $event_id, $ticket_id, $order_id ];
	}

	public function status_provider() {
		yield 'testing Action Required status' => [ 'Action_Required', 'expectedResultForApproved' ];
		/*yield 'testing Approved status' => [ 'Approved', 'expectedResultForApproved' ];
		yield 'testing Completed status' => [ 'Completed', 'expectedResultForApproved' ];
		yield 'testing Created status' => [ 'Created', 'expectedResultForApproved' ];
		yield 'testing Denied status' => [ 'Denied', 'expectedResultForApproved' ];
		yield 'testing Not Completed status' => [ 'Not_Completed', 'expectedResultForApproved' ];
		yield 'testing Pending status' => [ 'Pending', 'expectedResultForApproved' ];
		yield 'testing Refunded status' => [ 'Refunded', 'expectedResultForApproved' ];
		yield 'testing Reversed status' => [ 'Reversed', 'expectedResultForApproved' ];
		yield 'testing Undefined status' => [ 'Undefined', 'expectedResultForApproved' ];
		yield 'testing Voided status' => [ 'Voided', 'expectedResultForApproved' ];*/
	}

	/**
	 * @test
	 * @dataProvider status_provider
	 */
	public function it_should_handle_status_correctly( $status, $expectedResult ) {
		[ $event_id, $ticket_id, $order_id ] = $this->setup_scenario();
		$status_modification_result = tribe( Order::class )->modify_status(
			$order_id,
			( "TEC\\Tickets\\Commerce\\Status\\{$status}" )::SLUG
		);

		$this->assertTrue( $status_modification_result );
	}

	/**
	 * @test
	 * @dataProvider status_provider
	 */
	public function it_should_return_false_when_updating_to_same_status($status, $expectedResult) {
		codecept_debug('----------------------');
		[ $event_id, $ticket_id, $order_id ] = $this->setup_scenario( [], [], $status );
		codecept_debug("Switching to status {$status}");

		codecept_debug('||||||||||||| Update 2 |||||||||||||');
		$status_modification_result = tribe( Order::class )->modify_status(
			$order_id,
			( "TEC\\Tickets\\Commerce\\Status\\{$status}" )::SLUG
		);

		codecept_debug("NEw Status: ". $status_modification_result);
		codecept_debug('----------------------');
	}


	/**
	 * @test
	 *
	 * @return void
	 * @throws \Tribe__Repository__Usage_Error
	 */
	public function it_should_return_wp_error_on_status_modification_due_to_invalid_event() {
		[ $event_id, $ticket_id, $order_id ] = $this->setup_scenario();

		// Delete the event to make it invalid.
		$is_event_deleted = wp_delete_post( $event_id, true );

		if ( ! $is_event_deleted ) {
			codecept_debug( "Event with ID {$event_id} could not be deleted." );
		}

		// Attempt to modify the order status, expecting a WP_Error due to the deleted event.
		$status_modification_result = tribe( Order::class )->modify_status(
			$order_id,
			( "TEC\\Tickets\\Commerce\\Status\\Pending" )::SLUG
		);

		// Assert that the status modification result is a WP_Error.
		$this->assertInstanceOf( "WP_Error", $status_modification_result );

		// Assert that the error code is as expected.
		$this->assertEquals(
			"tec-tc-invalid-event-id",
			$status_modification_result->get_error_code()
		);

		// Assert that the error message is as expected.
		$this->assertEquals(
			"This order contained a Ticket with an invalid Event (Event ID: {$ticket_id})",
			$status_modification_result->get_error_message()
		);
	}

	/**
	 * @test
	 *
	 * @return void
	 * @throws \Tribe__Repository__Usage_Error
	 */
	public function it_should_return_wp_error_on_status_modification_due_to_past_dates() {
		$ticket_overrides = [
			"ticket_start_date" => "01-01-2021",
			"ticket_end_date"   => "01-01-2021",
		];
		$order_overrides  = [
			"quantity" => 1,
		];

		[ $event_id, $ticket_id, $order_id ] = $this->setup_scenario(
			$ticket_overrides,
			$order_overrides
		);

		// Attempt to modify the order status, expecting a WP_Error due to no tickets.
		$status_modification_result = tribe( Order::class )->modify_status(
			$order_id,
			( "TEC\\Tickets\\Commerce\\Status\\Pending" )::SLUG
		);

		// Assert that the status modification result is a WP_Error.
		$this->assertInstanceOf( "WP_Error", $status_modification_result );

		// Assert that the error code is as expected.
		$this->assertEquals(
			"tec-tc-ticket-unavailable",
			$status_modification_result->get_error_code()
		);

		// Assert that the error message is as expected.
		$this->assertEquals(
			"Tickets are no longer available.",
			$status_modification_result->get_error_message()
		);
	}

	/**
	 * @test
	 *
	 * @return void
	 * @throws \Tribe__Repository__Usage_Error
	 */
	public function it_should_return_wp_error_on_status_modification_due_to_future_dates() {
		$ticket_overrides = [
			"ticket_start_date" => "2050-01-01",
			"ticket_start_time" => "08:00:00",
			"ticket_end_date"   => "2050-01-01",
		];
		$order_overrides  = [
			"quantity" => 1,
		];

		[ $event_id, $ticket_id, $order_id ] = $this->setup_scenario(
			$ticket_overrides,
			$order_overrides
		);

		// Attempt to modify the order status, expecting a WP_Error due to no tickets.
		$status_modification_result = tribe( Order::class )->modify_status(
			$order_id,
			( "TEC\\Tickets\\Commerce\\Status\\Pending" )::SLUG
		);

		// Assert that the status modification result is a WP_Error.
		$this->assertInstanceOf( "WP_Error", $status_modification_result );

		// Assert that the error code is as expected.
		$this->assertEquals(
			"tec-tc-ticket-unavailable",
			$status_modification_result->get_error_code()
		);

		// Create the time to use in the expected error message.
		$time_object    = DateTime::createFromFormat(
			"H:i:s",
			$ticket_overrides["ticket_start_time"]
		);
		$formatted_time = $time_object->format( "g:i a" );

		// Assert that the error message is as expected.
		$this->assertEquals(
			"Tickets will be available on {$ticket_overrides["ticket_start_date"]} at $formatted_time",
			$status_modification_result->get_error_message()
		);
	}

	/**
	 * @test
	 *
	 * @return void
	 * @throws \Tribe__Repository__Usage_Error
	 */
	public function it_should_return_true_when_status_is_final() {
		[ $event_id, $ticket_id, $order_id ] = $this->setup_scenario();

		// Set the initial status to 'Refunded' (A 'Final' status).
		$set_status_to_final = tribe( Order::class )->modify_status(
			$order_id,
			( "TEC\\Tickets\\Commerce\\Status\\Refunded" )::SLUG
		);

		// Update it to 'Pending'.
		$status_modification_result = tribe( Order::class )->modify_status(
			$order_id,
			( "TEC\\Tickets\\Commerce\\Status\\Pending" )::SLUG
		);

		$this->assertTrue( $status_modification_result );
	}

	/**
	 * @test
	 *
	 * @return void
	 * @throws \Tribe__Repository__Usage_Error
	 */
	public function it_should_return_true_early_if_no_tickets_in_order() {
		$order_overrides = [
			"quantity" => 0, // No tickets in the order.
		];

		[ $event_id, $ticket_id, $order_id ] = $this->setup_scenario(
			[],
			$order_overrides
		);

		// Attempt to modify the order status.
		$status_modification_result = tribe( Order::class )->modify_status(
			$order_id,
			"Pending"
		);

		// Assert that the status modification result is true.
		$this->assertFalse( $status_modification_result );
	}
}
