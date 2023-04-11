<?php
/**
 * Provides methods to create Series Pass tickets in the context of tests.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Test\Traits;
 */

namespace TEC\Tickets\Flexible_Tickets\Test\Traits;

use TEC\Tickets\Commerce;
use TEC\Tickets\Flexible_Tickets\Series_Passes;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Ticket_Object as Ticket;

/**
 * Class Series_Pass_Factory.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Test\Traits;
 */
trait Series_Pass_Factory {
	use Ticket_Maker;

	protected function create_tc_series_pass( int $post_id, int $price, array $overrides = [] ): Ticket {
		$ticket_id = $this->create_tc_ticket( $post_id, $price, $overrides );
		update_post_meta( $ticket_id, '_type', Series_Passes::HANDLED_TICKET_TYPE );

		return Commerce\Module::get_instance()->get_ticket( $post_id, $ticket_id );
	}
}