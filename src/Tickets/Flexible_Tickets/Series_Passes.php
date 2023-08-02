<?php
/**
 * Handles the Series Passes integration at different levels.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Common\lucatume\DI52\Container;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Templates\Series_Filters;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Labels;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Meta;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Date_Utils as Dates;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class Repository.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Series_Passes extends Controller {
	/**
	 * The ticket type handled by this class.
	 *
	 * @since TBD
	 */
	public const TICKET_TYPE = 'series_pass';

	/**
	 * A reference to the labels' handler.
	 *
	 * @since TBD
	 *
	 * @var Labels
	 */
	private Labels $labels;
	/**
	 * A reference to the Series Passes' meta handler.
	 *
	 * @since TBD
	 *
	 * @var Meta
	 */
	private Meta $meta;

	public function __construct( Container $container, Labels $labels, Meta $meta ) {
		parent::__construct( $container );
		$this->labels = $labels;
		$this->meta   = $meta;
	}

	/**
	 * The entire provider should not be active if Series are not ticketable.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		return in_array( Series_Post_Type::POSTTYPE, $ticketable_post_types, true );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Series_Passes\Repository::class, Series_Passes\Repository::class );
		$this->container->singleton( Series_Passes\Metadata::class, Series_Passes\Metadata::class );

		add_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
		add_action( 'admin_menu', [ $this, 'enable_reports' ], 20 );
		add_filter( 'tec_tickets_ticket_panel_data', [ $this, 'update_panel_data' ], 10, 3 );

		// Subscribe to the ticket post updates.
		foreach ( Enums\Ticket_Post_Types::all() as $post_type ) {
			add_action( "save_post_{$post_type}", [ $this, 'update_pass' ], 20 );
			add_action( "edit_post_{$post_type}", [ $this, 'update_pass' ], 20 );
		}

		// Subscribe to Tickets' metadata updates.
		add_action( 'added_post_meta', [ $this, 'update_pass_meta' ], 20, 4 );
		add_action( 'updated_post_meta', [ $this, 'update_pass_meta' ], 20, 4 );
		add_action( 'tribe_tickets_ticket_add', [ $this, 'update_pass_meta_on_save' ], 10, 2 );

		// An Event is attached to a Series.
		add_action( 'tec_events_pro_custom_tables_v1_event_relationship_updated', [
			$this,
			'update_passes_for_event'
		], 20, 2 );

		// Multiple Events are attached to a Series.
		add_action( 'tec_events_pro_custom_tables_v1_series_relationships_updated', [
			$this,
			'update_passes_for_series'
		] );

		// Event Occurrences have been updated
		add_action( 'tec_events_custom_tables_v1_after_save_occurrences', [ $this, 'update_passes_for_event' ] );

		add_action( 'tec_tickets_panels_before', [ $this, 'start_filtering_labels' ], 10, 3 );
		add_action( 'tec_tickets_panels_after', [ $this->labels, 'stop_filtering_labels' ] );
		add_action( 'tribe_events_tickets_new_ticket_warnings', [ $this, 'display_pass_notice' ], 5, 2 );

		add_filter( 'tec_tickets_repository_filter_by_event_id', [ $this, 'add_series_to_searched_events' ] );
		add_action( 'added_post_meta', [ $this, 'propagate_ticket_provider_from_series' ], 20, 4 );
		add_action( 'updated_post_meta', [ $this, 'propagate_ticket_provider_from_series' ], 20, 4 );
		add_action( 'deleted_post_meta', [ $this, 'delete_ticket_provider_from_series' ], 20, 4 );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
		remove_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );
		remove_filter( 'tec_tickets_ticket_panel_data', [ $this, 'update_panel_data' ] );
		remove_action( 'admin_menu', [ $this, 'enable_reports' ], 20 );
		foreach ( Enums\Ticket_Post_Types::all() as $post_type ) {
			remove_action( "save_post_{$post_type}", [ $this, 'update_pass' ], 20 );
			remove_action( "edit_post_{$post_type}", [ $this, 'update_pass' ], 20 );
		}
		remove_action( 'added_post_meta', [ $this, 'update_pass_meta' ], 20 );
		remove_action( 'updated_post_meta', [ $this, 'update_pass_meta' ], 20 );
		remove_action( 'tribe_tickets_ticket_add', [ $this, 'update_pass_meta_on_save' ] );
		remove_action( 'tec_events_pro_custom_tables_v1_event_relationship_updated', [
			$this,
			'update_passes_for_event'
		], 20, 2 );
		remove_action( 'tec_events_pro_custom_tables_v1_series_relationships_updated', [
			$this,
			'update_passes_for_series'
		] );
		remove_action( 'tec_events_custom_tables_v1_after_save_occurrences', [ $this, 'update_passes_for_event' ] );
		remove_action( 'tec_tickets_panels_before', [ $this, 'start_filtering_labels' ] );
		remove_action( 'tec_tickets_panels_after', [ $this->labels, 'stop_filtering_labels' ] );
	}

	/**
	 * Adds the toggle to the new ticket form.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void The toggle is added to the new ticket form.
	 */
	public function render_form_toggle( $post_id ): void {
		if ( ! ( is_numeric( $post_id ) && $post_id > 0 ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! ( $post instanceof WP_Post && $post->post_type === Series_Post_Type::POSTTYPE ) ) {
			return;
		}

		$ticket_providing_modules = array_diff_key( Tickets::modules(), [ RSVP::class => true ] );
		$admin_views              = $this->container->get( Admin_Views::class );
		$admin_views->template( 'series-pass-form-toggle', [
			'disabled' => count( $ticket_providing_modules ) === 0,
		] );
	}

	/**
	 * Re-orders the Series content filter to run after the ticket content filter to
	 * have the tickets display after the Series content and before the Series list
	 * of Events.
	 *
	 * This method uses `the_content` filter priority 0 to run once before the Series or Ticket
	 * logic run
	 *
	 * @since TBD
	 *
	 * @param string $content The post content.
	 *
	 * @return string The filtered post content.
	 */
	public function reorder_series_content( $content ) {
		$series_filters = $this->container->make( Series_Filters::class );
		// Move the Series content filter from its default priority of 10 to 20; tickest are injected at 11.
		remove_filter( 'the_content', [ $series_filters, 'inject_content' ] );
		add_filter( 'the_content', [ $series_filters, 'inject_content' ], 20 );
		// It's enough to run this once.
		remove_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );

		return $content;
	}

	/**
	 * Adds Series Passes' admin strings to the list of admin strings.
	 *
	 * @since TBD
	 *
	 * @param array<string> $data      The panel data to filter.
	 * @param int           $post_id   The post ID the panel is being displayed for.
	 * @param int|null      $ticket_id The ticket ID the panel is being displayed for, if any.
	 *
	 * @return array<string> The list of admin strings.
	 */
	public function update_panel_data( array $data, int $post_id, ?int $ticket_id ): array {
		if ( get_post_meta( $ticket_id, '_type', true ) !== self::TICKET_TYPE ) {
			return $data;
		}

		$data['ticket_end_date_help_text'] = esc_attr_x(
			'If you do not set an end sale date, passes will be available until the last event in the Series.',
			'Help text for the end date field in the Series Passes meta box.',
			'event-tickets'
		);

		$set_end_date = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$set_end_time = get_post_meta( $ticket_id, '_ticket_end_time', true );

		$datepicker_format       = Dates::datepicker_formats( Dates::get_datepicker_format_index() );
		$data['ticket_end_date'] = $set_end_date ? Dates::date_only( $set_end_date, false, $datepicker_format ) : '';
		$data['ticket_end_time'] = $set_end_time ? Dates::time_only( $set_end_time ) : '';

		return $data;
	}

	/**
	 * Enables the reports for the Series Passes for all the possible ticket providers.
	 *
	 * Since providers can be set per-Series, all are pre-emptively activated.
	 *
	 * @since TBD
	 *
	 * @return void Reports are enabled for the Series Passes.
	 */
	public function enable_reports(): void {
		global $_registered_pages;

		if ( ! is_array( $_registered_pages ) ) {
			return;
		}

		// The post type is the Event one because in the menu Series are listed under Events.
		$event_post_type = TEC::POSTTYPE;

		// Attendee reports for all providers (ET).
		$_registered_pages[ $event_post_type . '_page_tickets-attendees' ] = true;

		// Order reports for Tickets Commerce provider (ET).
		$_registered_pages[ $event_post_type . '_page_tickets-commerce-orders' ] = true;

		// Order reports for PayPal Tickets provider (ET).
		$_registered_pages[ $event_post_type . '_page_tpp-orders' ] = true;

		// Order reports for WooCommerce provider (ET+).
		$_registered_pages[ $event_post_type . '_page_tickets-orders' ] = true;

		// Order reports for Easy Digital Downloads provider (ET+).
		$_registered_pages[ $event_post_type . '_page_edd-orders' ] = true;
	}

	/*
	 * Updates a Series Pass' end date meta dynamic flag and values, if needed.
	 *
	 * The method wraps the Meta low-level operation to unregister and re-register the provider as required.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void The ticket end date meta and dynamic flag is updated.
	 */
	private function update_ticket_end_meta( int $ticket_id, string $meta_key, bool $dynamic ): void {
		// Unregister to avoid infinite loops.
		remove_action( 'added_post_meta', [ $this, 'update_pass_meta' ], 20 );
		remove_action( 'updated_post_meta', [ $this, 'update_pass_meta' ], 20 );

		$this->meta->update_end_meta( $ticket_id, $meta_key, $dynamic );

		// Re-register the controller.
		add_action( 'added_post_meta', [ $this, 'update_pass_meta' ], 20, 4 );
		add_action( 'updated_post_meta', [ $this, 'update_pass_meta' ], 20, 4 );
	}

	/**
	 * Updates the Series Passes meta when its meta is updated.
	 *
	 * @since TBD
	 *
	 * @param int|null $meta_id    The meta ID, unused.
	 * @param int      $ticket_id  The ticket ID.
	 * @param string   $meta_key   The meta key.
	 * @param mixed    $meta_value The meta value.
	 *
	 * @return void The meta is updated.
	 */
	public function update_pass_meta( $meta_id, $ticket_id, $meta_key, $meta_value ): void {
		if ( get_post_meta( $ticket_id, '_type', true ) !== self::TICKET_TYPE ) {
			return;
		}

		$this->meta->update_pass_meta( $ticket_id, $meta_key, $meta_value );
	}

	/**
	 * Updates a Series Pass post or custom fields when it's saved.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return void The Series Pass post is updated.
	 */
	public function update_pass( $ticket_id ): void {
		if ( get_post_meta( $ticket_id, '_type', true ) !== self::TICKET_TYPE ) {
			return;
		}

		$end_date_is_dynamic = get_post_meta( $ticket_id, '_dynamic_end_date', true )
		                       || empty( get_post_meta( $ticket_id, '_ticket_end_date', true ) );
		$this->update_ticket_end_meta( $ticket_id, '_ticket_end_date', $end_date_is_dynamic );

		// End time follows end date: either they're both dynamic or both manually set.
		$this->update_ticket_end_meta( $ticket_id, '_ticket_end_time', $end_date_is_dynamic );
	}

	/**
	 * Updates a Series Pass meta when created or edited.
	 *
	 * @since TBD
	 *
	 * @param int           $post_id The ID of the post the Ticket is being saved for.
	 * @param Ticket_Object $ticket  The Ticket being saved.
	 *
	 * @return void The Series Pass meta is updated, if the Ticket is a Series Pass and it's required.
	 */
	public function update_pass_meta_on_save( $post_id, Ticket_Object $ticket = null ): void {
		if ( $ticket === null ) {
			return;
		}

		$this->update_pass( $ticket->ID );
	}

	/**
	 * Updates the Series Passes when a Series relationships with Events are updated.
	 *
	 * @since TBD
	 *
	 * @param int $series_id The Series post ID.
	 *
	 * @return void The Series Passes are updated.
	 */
	public function update_passes_for_series( int $series_id ): void {
		// Theoretically unbound; in practice it's unlikely a Series will have more than a few Series Passes attached.
		$passes = tribe_tickets()->where( 'event', $series_id )->get_ids();

		if ( ! count( $passes ) ) {
			return;
		}

		foreach ( $passes as $pass ) {
			$this->update_pass( $pass );
		}
	}

	/**
	 * Updates the Series Passes when an Event relationship with Series are updated.
	 *
	 * @since TBD
	 *
	 * @param int             $event_id   The Event post ID.
	 * @param array<int>|null $series_ids The Series post IDs, if known.
	 *
	 * @return void The Series Passes are updated.
	 */
	public function update_passes_for_event( int $event_id, array $series_ids = null ): void {
		// Get the Series the Event belongs to if not provided.
		$series_ids = $series_ids ?? tec_series()->where( 'event_post_id', $event_id )->fields( 'ids' )->get_ids();

		if ( empty( $series_ids ) ) {
			return;
		}

		$this->update_passes_for_series( reset( $series_ids ) );
	}

	/**
	 * Starts filtering the ticket labels during panel rendering.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post|null $post        The post the panel is being rendered for.
	 * @param int|null         $ticket_id   The ticket ID the panel is being rendered for, if any.
	 * @param string|null      $ticket_type The ticket type the panel is being rendered for, if any.
	 *
	 * @return void
	 */
	public function start_filtering_labels( $post = null, $ticket_id = null, $ticket_type = null ): void {
		if ( $ticket_type !== self::TICKET_TYPE ) {
			return;
		}

		$this->labels->start_filtering_labels();
	}

	public function display_pass_notice( int $post_id, int $total_tickets ): void {
		if ( $total_tickets > 0 || get_post_type( $post_id ) !== TEC::POSTTYPE ) {
			return;
		}

		$series_ids = tec_series()->where( 'event_post_id', $post_id )->get_ids();

		if ( ! count( $series_ids ) ) {
			return;
		}

		$series = reset( $series_ids );

		$admin_views = $this->container->get( Admin_Views::class );
		$admin_views->template( 'series-pass-event-notice', [
			'series_edit_link' => get_edit_post_link( $series ),
			'series_title'     => get_the_title( $series ),
		] );
	}

	public function add_series_to_searched_events( $post_id ) {
		if ( ! ( get_post_type( $post_id ) === TEC::POSTTYPE ) ) {
			return $post_id;
		}

		$series_ids = tec_series()->where( 'event_post_id', $post_id )->get_ids();

		if ( ! count( $series_ids ) ) {
			return $post_id;
		}

		$series = reset( $series_ids );

		return [ $series, ...(array) $post_id ];
	}

	/**
	 * Updates the ticket provider when a Series ticket provider is updated.
	 *
	 * @since TBD
	 *
	 * @param array<int> $meta_ids   Unused, the meta IDs that were updated.
	 * @param int        $object_id  The post ID.
	 * @param string     $meta_key   The meta key.
	 * @param mixed      $meta_value The meta value.
	 *
	 * @return void The ticket provider is updated.
	 */
	public function propagate_ticket_provider_from_series( $meta_ids, $object_id, $meta_key, $meta_value ): void {
		$this->update_ticket_provider_from_series( $object_id, $meta_key, $meta_value, false );
	}

	/**
	 * Deleted the ticket provider when a Series ticket provider is deleted.
	 *
	 * @since TBD
	 *
	 * @param array<int> $meta_ids   Unused, the meta IDs that were deleted.
	 * @param int        $object_id  The post ID.
	 * @param string     $meta_key   The meta key.
	 * @param mixed      $meta_value The meta value.
	 *
	 * @return void The ticket provider is deleted.
	 */
	public function delete_ticket_provider_from_series( $meta_ids, $object_id, $meta_key, $meta_value ): void {
		$this->update_ticket_provider_from_series( $object_id, $meta_key, $meta_value, true );
	}

	/**
	 * Updates the ticket provider when a Series ticket provider is updated or deleted.
	 *
	 * @since TBD
	 *
	 * @param int    $object_id  The post ID.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The meta value.
	 * @param bool   $delete     Whether the meta was deleted.
	 *
	 * @return void The ticket provider is updated.
	 */
	private function update_ticket_provider_from_series( $object_id, $meta_key, $meta_value, $delete = false ): void {
		if ( ! (
			// Hard-coding the meta key to avoid having to retrieve, and possibly build, a controller for it.
			$meta_key === '_tribe_default_ticket_provider'
			&& get_post_type( $object_id ) === Series_Post_Type::POSTTYPE )
		) {
			return;
		}

		foreach ( tribe_events()->where( 'series', $object_id )->get_ids_generator() as $event_id ) {
			if ( $delete ) {
				delete_post_meta( $event_id, $meta_key );
			} else {
				update_post_meta( $event_id, $meta_key, $meta_value );
			}
		}

		foreach ( Series_Relationship::where( 'series_post_id', '=', $object_id )->all() as $relationship ) {
			$event_id = $relationship->event_post_id;
			if ( $delete ) {
				delete_post_meta( $event_id, $meta_key );
			} else {
				update_post_meta( $event_id, $meta_key, $meta_value );
			}
		}
	}
}