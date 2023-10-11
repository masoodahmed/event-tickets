<?php

namespace Tribe\Admin;

use Closure;
use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Reports\Orders as Order_Report;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class OrderReportTest extends WPTestCase {

	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;

	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			$ids,
			array_fill( 0, count( $ids ), '{{ID}}' ),
			$snapshot
		);
	}

	public function tc_order_report_data_provider(): Generator {
		yield 'event with no orders' => [
			function (): array {
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );

				return [ $event_id, [ $event_id, $ticket_id ] ];
			}
		];

		yield 'event with one order' => [
			function (): array {
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );

				$this->set_fn_return( 'current_time', '2020-02-22 22:22:22' );
				$order     = $this->create_order( [ $ticket_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				return [ $event_id, [ $event_id, $ticket_id, $order->ID ] ];
			}
		];

		yield 'event with 1 pending and 1 completed order' => [
			function (): array {
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id, 10 );

				$this->set_fn_return( 'current_time', '2020-02-22 22:22:22' );

				$order_a = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_b = $this->create_order( [ $ticket_id => 3 ], [ 'purchaser_email' => 'purchaser@test.com', 'order_status' => Pending::SLUG ] );

				return [ $event_id, [ $event_id, $ticket_id, $order_a->ID, $order_b->ID ] ];
			}
		];

		yield 'event with multiple tickets and orders' => [
			function (): array {
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id_a = $this->create_tc_ticket( $event_id, 10 );
				$ticket_id_b = $this->create_tc_ticket( $event_id, 20.50 );

				$this->set_fn_return( 'current_time', '2020-02-22 22:22:22' );

				$order_a = $this->create_order( [ $ticket_id_a => 2 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_b = $this->create_order( [ $ticket_id_a => 3 ], [ 'purchaser_email' => 'purchaser@test.com', 'order_status' => Pending::SLUG ] );
				$order_c = $this->create_order( [ $ticket_id_b => 1 ], [ 'purchaser_email' => 'purchaser@test.com', 'order_status' => Completed::SLUG ] );
				$order_d = $this->create_order( [ $ticket_id_b => 4 ], [ 'purchaser_email' => 'purchaser@test.com', 'order_status' => Pending::SLUG ] );

				return [ $event_id, [ $event_id, $ticket_id_a, $ticket_id_b, $order_a->ID, $order_b->ID, $order_c->ID, $order_d->ID ] ];
			}
		];
	}

	/**
	 * @dataProvider tc_order_report_data_provider
	 */
	public function test_tc_order_report_display( Closure $fixture ) {
		// The global hook suffix is used to set the table static cache, randomize it to avoid collisions with other tests.
		$GLOBALS['hook_suffix'] = uniqid( 'tec-tc-reports-orders', true );
		// Ensure we're using a user that can manage posts.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		[ $post_id, $post_ids ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		$_GET['event_id'] = $post_id;
		$_GET['search']   = '';

		$order_report = tribe( Order_Report::class );
		$order_report->attendees_page_screen_setup();

		ob_start();
		$order_report->render_page();
		$html = ob_get_clean();

		$html = $this->placehold_post_ids( $html, $post_ids );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}