<?php

namespace Tribe\Tickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Metabox as Metabox;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class MetaboxTest extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use With_Uopz;

	public function get_panels_provider(): Generator {
		yield 'post without ticket' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = null;

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with ticket' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 23 );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event without ticket' => [
			function (): array {
				$post_id   = tribe_events()->set_args( [
					'title'      => 'Test event',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
				] )->create()->ID;
				$ticket_id = null;

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event with ticket' => [
			function (): array {
				$post_id   = tribe_events()->set_args( [
					'title'      => 'Test event',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $post_id, 23 );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with RSVP' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = $this->create_rsvp_ticket( $post_id, [
					'meta_input' => [
						'_ticket_start_date' => '2021-01-01 10:00:00',
						'_ticket_end_date'   => '2021-01-31 12:00:00',
					]
				] );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event with RSVP' => [
			function (): array {
				$post_id   = tribe_events()->set_args( [
					'title'      => 'Test event',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
				] )->create()->ID;
				$ticket_id = $this->create_rsvp_ticket( $post_id, [
					'meta_input' => [
						'_ticket_start_date' => '2021-01-01 10:00:00',
						'_ticket_end_date'   => '2021-01-31 12:00:00',
					]
				] );

				return [ $post_id, $ticket_id ];
			},
		];
	}

	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			array_values( $ids ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
	}

	/**
	 * @dataProvider get_panels_provider
	 */
	public function test_get_panels( Closure $fixture ): void {
		[ $post_id, $ticket_id ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '33333333' );

		$metabox = tribe( Metabox::class );
		$panels  = $metabox->get_panels( $post_id, $ticket_id );
		$html    = implode( '', $panels );
		$html    = $this->placehold_post_ids( $html, [
			'post_id'   => $post_id,
			'ticket_id' => $ticket_id,
		] );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}