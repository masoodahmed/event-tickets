<?php
/**
 * Handles registering Providers for the TEC\Events_Community\Custom_Tables\V1 (RBE) namespace.
 *
 * @since   5.5.0
 *
 * @package TEC\Events_Community\Custom_Tables\V1;
 */

namespace TEC\Tickets\Custom_Tables\V1;

use tad_DI52_ServiceProvider;
use TEC\Events\Custom_Tables\V1\Migration\State;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Tickets\Custom_Tables\V1\WP_Query\WP_Meta_Query_Modifier;
use Tribe__Utils__Array as Arr;
use WP_Query;

/**
 * Class Provider.
 *
 * @since   5.5.0
 *
 * @package TEC\Tickets\Custom_Tables\V1;
 */
class Provider extends tad_DI52_ServiceProvider {
	/**
	 * @var bool
	 */
	protected $has_registered = false;

	/**
	 * Registers any dependent providers.
	 *
	 * @since 5.5.0
	 *
	 * @return bool Whether the Event-wide maintenance mode was activated or not.
	 */
	public function register() {
		if ( $this->has_registered ) {
			return false;
		}

		if ( ! defined( 'TEC_ET_CUSTOM_TABLES_V1_ROOT' ) ) {
			define( 'TEC_ET_CUSTOM_TABLES_V1_ROOT', __DIR__ );
		}

		$this->lock_for_maintenance();

		$this->container->singleton(WP_Meta_Query_Modifier::class);

		add_filter( 'admin_body_class', [ $this, 'prevent_tickets_on_recurring_events' ] );
		add_filter( 'body_class', [ $this, 'prevent_tickets_on_recurring_events_front_end' ] );
		add_filter( 'pre_get_posts', [ $this, 'modify_tickets_meta_query' ] );
		add_filter( 'tec_tickets_filter_event_id', [ $this, 'normalize_event_id' ] );

		$this->has_registered = true;

		return true;
	}

	/**
	 * Modify ticket meta query for Custom Tables.
	 *
	 * @since TBD
	 *
	 * @param WP_Query $wp_query The WP_Query to inspect and modify for Custom Table queries.
	 */
	public function modify_tickets_meta_query( $wp_query ) {
		$this->container->make( WP_Meta_Query_Modifier::class )->modify_tickets_meta_query( $wp_query );
	}

	/**
	 * Will normalize the event ID, converting provisional ID's to their Post ID counterpart. Non-destructive, will
	 * retain original value if a provisional ID is not found.
	 *
	 * @since TBD
	 *
	 * @param mixed $id Event ID to attempt converting to a post ID.
	 *
	 * @return mixed The post ID or whatever was passed.
	 */
	public function normalize_event_id( $id ) {
		return Occurrence::normalize_id( $id );
	}

	/**
	 * Registers the filters required to lock Ticket editing while the
	 * migration to the Custom Tables V1 is running.
	 *
	 * @since 5.5.0
	 */
	private function lock_for_maintenance(): void {
		$state = $this->container->make( State::class );

		if ( $state->should_lock_for_maintenance() ) {
			$this->container->register( Migration\Maintenance_Mode\Provider::class );
		}
	}

	/**
	 * Filter the body classes in admin context to prevent tickets from being added to
	 * recurring Events or ticketed Events from being made recurring.
	 *
	 * @since 5.5.0
	 *
	 * @param string $admin_body_classes A space-separated list of classes.
	 *
	 * @return string A space-separated list of classes, updated to include the
	 *                `tec-no-tickets-on-recurring` class.
	 */
	public function prevent_tickets_on_recurring_events( ?string $admin_body_classes ): string {
		$state = $this->container->make( State::class );

		if ( ! $state->is_migrated() ) {
			return $admin_body_classes;
		}

		$classes = array_unique(
			array_merge(
				Arr::list_to_array( $admin_body_classes ), [ 'tec-no-tickets-on-recurring' ]
			)
		);

		return implode( ' ', $classes );
	}

	/**
	 * A wrapper for `prevent_tickets_on_recurring_events` that can be used
	 * on front-end body tags.
	 *
	 * @since 5.5.0
	 *
	 * @param array $body_classes A list of classes.
	 *
	 * @return array A list of classes, updated to include the `tec-no-tickets-on-recurring` class.
	 */
	public function prevent_tickets_on_recurring_events_front_end( array $body_classes ): array {
		$classes = implode( ' ', $body_classes );
		$classes = $this->prevent_tickets_on_recurring_events( $classes );
		return explode( ' ', $classes );
	}

	/**
	 * Do cleanup stuff.
	 *
	 * @since TBD
	 */
	public function unregister() {
		remove_filter( 'admin_body_class', [ $this, 'prevent_tickets_on_recurring_events' ] );
		remove_filter( 'body_class', [ $this, 'prevent_tickets_on_recurring_events_front_end' ] );
		remove_filter( 'pre_get_posts', [ $this, 'modify_tickets_meta_query' ] );
		remove_filter( 'tec_tickets_filter_event_id', [ $this, 'normalize_event_id' ] );
	}
}
