<?php

namespace TEC\Tickets\Emails\Email;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use TEC\Tickets\Emails\Email\RSVP_Not_Going;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Class Email_TemplateTest
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class RSVP_Not_GoingTest extends WPTestCase {
	use With_Uopz;
	use MatchesSnapshots;

	/**
	 * @test
	 */
	public function it_should_match_snapshot_with_tickets(): void {
		$email = tribe( RSVP_Not_Going::class );
		$email->set( 'post_id', $event_id );
		$email->set( 'tickets', $event_tickets );
		$email->recipient = 'mock@example.com';

		$content = $email->get_content();
		$this->assertMatchesSnapshot( $content );
	}
}