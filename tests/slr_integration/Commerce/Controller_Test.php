<?php

namespace TEC\Tickets\Seating\Commerce;

use Closure;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Seating\Meta;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Controller_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Order_Maker;

	protected string $controller_class = Controller::class;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 */
	public function ensure_tickets_commerce_active(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter(
			'tribe_tickets_get_modules',
			function ( $modules ) {
				$modules[ Module::class ] = tribe( Module::class )->plugin_name;

				return $modules;
			} 
		);

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API() );
	}

	public function filter_timer_token_object_id_entries_data_provider(): \Generator {
		yield 'no entries' => [
			function (): array {
				return [
					[],
					[],
				];
			},
		];

		yield 'not on checkout page' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_false' );

				return [
					[ $post_id => 'test-token' ],
					[ $post_id => 'test-token' ],
				];
			},
		];

		yield 'on checkout page but no ASC post in cart' => [
			function (): array {
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_true' );
				$no_asc_post_id = static::factory()->post->create();
				$ticket_id      = $this->create_tc_ticket( $no_asc_post_id, 10 );
				/** @var Cart $cart */
				$cart = tribe( Cart::class );
				$cart->add_ticket( $ticket_id, 1 );
				$asc_post_id = static::factory()->post->create();
				update_post_meta( $asc_post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
				$asc_ticket_id = $this->create_tc_ticket( $asc_post_id, 10 );
				$this->create_tc_ticket( $asc_post_id, 10 );

				return [
					[ $asc_post_id => 'test-token' ],
					[],
				];
			},
		];

		yield 'on checkout page with ASC post in cart' => [
			function (): array {
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_true' );
				$no_asc_post_id   = static::factory()->post->create();
				$no_asc_ticket_id = $this->create_tc_ticket( $no_asc_post_id, 10 );
				/** @var Cart $cart */
				$cart = tribe( Cart::class );
				$cart->add_ticket( $no_asc_ticket_id, 1 );
				$asc_post_id = static::factory()->post->create();
				update_post_meta( $asc_post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
				$asc_ticket_id = $this->create_tc_ticket( $asc_post_id, 10 );
				$cart->add_ticket( $asc_ticket_id, 1 );

				return [
					[ $asc_post_id => 'test-token' ],
					[ $asc_post_id => 'test-token' ],
				];
			},
		];
	}

	public function test_tc_shared_capacity_purchase(): void {
		$controller = $this->make_controller();
		$controller->register();

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		// Set the Event global stock level to 100.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );

		$ticket_id1 = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		$ticket_id2 = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		$ticket_id3 = $this->create_tc_ticket(
			$event_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 50,
				],
			]
		);

		$ticket_id4 = $this->create_tc_ticket(
			$event_id,
			40,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 50,
				],
			]
		);

		$ticket_id5 = $this->create_tc_ticket(
			$event_id,
			50,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);

		// Group A.
		update_post_meta( $ticket_id1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-A' );
		update_post_meta( $ticket_id2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-A' );
		// Group B.
		update_post_meta( $ticket_id3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-B' );
		update_post_meta( $ticket_id4, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-B' );
		// Group C.
		update_post_meta( $ticket_id5, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-C' );

		// Get the ticket objects.
		$ticket_1 = tribe( Module::class )->get_ticket( $event_id, $ticket_id1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $event_id, $ticket_id2 );
		$ticket_3 = tribe( Module::class )->get_ticket( $event_id, $ticket_id3 );
		$ticket_4 = tribe( Module::class )->get_ticket( $event_id, $ticket_id4 );
		$ticket_5 = tribe( Module::class )->get_ticket( $event_id, $ticket_id5 );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket_1 );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_2 );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_3 );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_4 );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_5 );

		$this->assertEquals( 30, $ticket_1->capacity() );
		$this->assertEquals( 30, $ticket_1->stock() );
		$this->assertEquals( 30, $ticket_1->available() );
		$this->assertEquals( 30, $ticket_1->inventory() );

		$this->assertEquals( 30, $ticket_2->capacity() );
		$this->assertEquals( 30, $ticket_2->stock() );
		$this->assertEquals( 30, $ticket_2->available() );
		$this->assertEquals( 30, $ticket_2->inventory() );

		$this->assertEquals( 50, $ticket_3->capacity() );
		$this->assertEquals( 50, $ticket_3->stock() );
		$this->assertEquals( 50, $ticket_3->available() );
		$this->assertEquals( 50, $ticket_3->inventory() );

		$this->assertEquals( 50, $ticket_4->capacity() );
		$this->assertEquals( 50, $ticket_4->stock() );
		$this->assertEquals( 50, $ticket_4->available() );
		$this->assertEquals( 50, $ticket_4->inventory() );

		$this->assertEquals( 20, $ticket_5->capacity() );
		$this->assertEquals( 20, $ticket_5->stock() );
		$this->assertEquals( 20, $ticket_5->available() );
		$this->assertEquals( 20, $ticket_5->inventory() );


		$global_stock = new Global_Stock( $event_id );

		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( 100, tribe_get_event_capacity( $event_id ), 'Total Event capacity should be 100' );
		$this->assertEquals( 100, $global_stock->get_stock_level(), 'Global stock should be 100' );

		// Create an Order for 5 on each Ticket.
		$order = $this->create_order(
			[
				$ticket_id1 => 2,
				$ticket_id2 => 3, // Group A total 5!
				$ticket_id3 => 4,
				$ticket_id4 => 3, // Group B total 7!
				$ticket_id5 => 5, // Group C total 5!
			]
		);

		// Refresh the ticket objects.
		$ticket_1 = tribe( Module::class )->get_ticket( $event_id, $ticket_id1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $event_id, $ticket_id2 );
		$ticket_3 = tribe( Module::class )->get_ticket( $event_id, $ticket_id3 );
		$ticket_4 = tribe( Module::class )->get_ticket( $event_id, $ticket_id4 );
		$ticket_5 = tribe( Module::class )->get_ticket( $event_id, $ticket_id5 );

		$this->assertEquals( 30, $ticket_1->capacity() );
		$this->assertEquals( 30 - 5, $ticket_1->stock() );
		$this->assertEquals( 30 - 5, $ticket_1->available() );
		$this->assertEquals( 30 - 5, $ticket_1->inventory() );

		$this->assertEquals( 30, $ticket_2->capacity() );
		$this->assertEquals( 30 - 5, $ticket_2->stock() );
		$this->assertEquals( 30 - 5, $ticket_2->available() );
		$this->assertEquals( 30 - 5, $ticket_2->inventory() );

		$this->assertEquals( 50, $ticket_3->capacity() );
		$this->assertEquals( 50 - 7, $ticket_3->stock() );
		$this->assertEquals( 50 - 7, $ticket_3->available() );
		$this->assertEquals( 50 - 7, $ticket_3->inventory() );

		$this->assertEquals( 50, $ticket_4->capacity() );
		$this->assertEquals( 50 - 7, $ticket_4->stock() );
		$this->assertEquals( 50 - 7, $ticket_4->available() );
		$this->assertEquals( 50 - 7, $ticket_4->inventory() );

		$this->assertEquals( 20, $ticket_5->capacity() );
		$this->assertEquals( 20 - 5, $ticket_5->stock() );
		$this->assertEquals( 20 - 5, $ticket_5->available() );
		$this->assertEquals( 20 - 5, $ticket_5->inventory() );

		$this->assertEquals( 100 - 17, $global_stock->get_stock_level(), 'Global stock should be 100-17 = 83' );

		update_post_meta( $ticket_id1, Ticket::$stock_meta_key, -1 );

		// Refresh the ticket objects.
		$ticket_1 = tribe( Module::class )->get_ticket( $event_id, $ticket_id1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $event_id, $ticket_id2 );
		// Make sure we are not syncing infinite seats.
		$this->assertEquals( 0, $ticket_1->stock() );
		$this->assertEquals( 30 - 5, $ticket_2->stock() );
	}

	/**
	 * @dataProvider filter_timer_token_object_id_entries_data_provider
	 * @return void
	 */
	public function test_filter_timer_token_object_id_entries( Closure $fixture ): void {
		[ $input_entries, $expected_entries ] = $fixture();

		$controller = $this->make_controller();
		$controller->register();

		$filtered_entries = apply_filters( 'tec_tickets_seating_timer_token_object_id_entries', $input_entries );

		$this->assertEquals(
			$expected_entries,
			$filtered_entries,
		);
	}
	
	public function test_stock_count_for_seated_tickets() {
		$controller = $this->make_controller();
		$controller->register();
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		
		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );
		
		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );
		
		// Add a ticket with 5 capacity.
		$vip = $this->create_tc_ticket(
			$event_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 5,
				],
			]
		);
		
		update_post_meta( $vip, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-uuid' );
		
		// Only`vip` ticket should be available.
		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		
		$this->assertEquals( 5, $counts['tickets']['stock'] );
		$this->assertEquals( 5, $counts['tickets']['available'] );
		
		$general = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 15,
				],
			]
		);
		
		update_post_meta( $general, Meta::META_KEY_SEAT_TYPE, 'other-seat-type-uuid' );
		
		// Both `vip` and `general` tickets should be available.
		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		
		$this->assertEquals( 20, $counts['tickets']['stock'] );
		$this->assertEquals( 20, $counts['tickets']['available'] );
		
		$order = $this->create_order(
			[
				$vip => 5,
			]
		);
		
		// Stock should be reduced for `vip` ticket.
		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		
		$this->assertEquals( 15, $counts['tickets']['stock'] );
		$this->assertEquals( 15, $counts['tickets']['available'] );
		
		$order_2 = $this->create_order(
			[
				$general => 15,
			]
		);
		
		// Stock should be reduced for `general` ticket and no tickets should be available.
		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		
		$this->assertEquals( 0, $counts['tickets']['stock'] );
		$this->assertEquals( 0, $counts['tickets']['available'] );
	}
	
	public function test_stock_count_for_multiple_same_seated_types() {
		$controller = $this->make_controller();
		$controller->register();
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		
		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );
		
		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );
		
		// Add a ticket with 5 capacity.
		$vip = $this->create_tc_ticket(
			$event_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 10,
				],
			]
		);
		
		update_post_meta( $vip, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid' );
		
		$general = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 10,
				],
			]
		);
		
		update_post_meta( $general, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid' );
		
		$count = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEquals( 10, $count['tickets']['stock'] );
		$this->assertEquals( 10, $count['tickets']['available'] );
		
		$order = $this->create_order(
			[
				$vip => 5,
			]
		);
		
		$count = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		
		$this->assertEquals( 5, $count['tickets']['stock'] );
		$this->assertEquals( 5, $count['tickets']['available'] );
	}
	
	public function test_stock_count_for_multiple_seat_typed_tickets() {
		$controller = $this->make_controller();
		$controller->register();
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		
		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 50 );
		
		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );
		
		// Add a ticket with 5 capacity.
		$vip = $this->create_tc_ticket(
			$event_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);
		
		update_post_meta( $vip, Meta::META_KEY_SEAT_TYPE, 'seat-type-vip' );
		
		$general = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);
		
		update_post_meta( $general, Meta::META_KEY_SEAT_TYPE, 'seat-type-general' );
		
		$child = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);
		
		update_post_meta( $child, Meta::META_KEY_SEAT_TYPE, 'seat-type-general' );
		
		$count = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		
		// Should have full stock.
		$this->assertEquals( 50, $count['tickets']['stock'] );
		$this->assertEquals( 50, $count['tickets']['available'] );
		
		$order = $this->create_order(
			[
				$vip => 5,
			]
		);
		
		$count = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		
		// Should have full stock.
		$this->assertEquals( 45, $count['tickets']['stock'] );
		$this->assertEquals( 45, $count['tickets']['available'] );
		
		$order_2 = $this->create_order(
			[
				$vip     => 15,
				$general => 10,
				$child   => 10,
			]
		);
		
		$count = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		
		// Should be 45-25.
		$this->assertEquals( 45 - 35, $count['tickets']['stock'] );
		$this->assertEquals( 45 - 35, $count['tickets']['available'] );
	}
}
