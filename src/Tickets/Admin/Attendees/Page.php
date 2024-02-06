<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Attendees;

/**
 * Class Page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */

/**
 * Manages the admin settings UI in relation to ticket configuration.
 */
class Page {

	/**
	 * Event Tickets menu page slug.
	 *
	 * @var string
	 */
	public static $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets Attendees page slug.
	 *
	 * @var string
	 */
	public static $slug = 'tec-tickets-attendees';

	/**
	 * Event Tickets Attendees page hook suffix.
	 *
	 * @var string
	 */
	public static $hook_suffix = 'tickets_page_tec-tickets-attendees';

	/**
	 * Defines wether the current page is the Event Tickets Attendees page.
	 *
	 * @since TBD
	 *
	 * @return boolean
	 */
	public function is_on_page(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$slug === $admin_page;
	}

	/**
	 * Returns the main admin attendees URL.
	 *
	 * @param array $args Arguments to pass to the URL.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ): string {
		$defaults = [
			'page' => static::$slug,
		];

		// Allow the link to be "changed" on the fly.
		$args = wp_parse_args( $args, $defaults );

		// Keep the resulting URL args clean.
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		/**
		 * Filters the URL to the Event Tickets attendees page.
		 *
		 * @since TBD
		 *
		 * @param string $url The URL to the Event Tickets attendees page.
		 */
		return apply_filters( 'tec_tickets_attendees_page_url', $url );
	}

	/**
	 * Adds the Event Tickets Attendees page.
	 *
	 * @since TBD
	 */
	public function add_tec_tickets_attendees_page() {
		$admin_pages = tribe( 'admin.pages' );

		$attendees_page = $admin_pages->register_page(
			[
				'id'       => static::$slug,
				'path'     => static::$slug,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Attendees', 'event-tickets' ),
				'position' => 1.5,
				'callback' => [
					$this,
					'render_tec_tickets_attendees_page',
				],
			]
		);
	}

	/**
	 * Render the `Attendees` page.
	 *
	 * @since TBD.
	 *
	 * @return void
	 */
	public function render_tec_tickets_attendees_page() {
		tribe_asset_enqueue_group( 'event-tickets-admin-attendees' );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'attendees' => tribe( 'tickets.attendees' ),
			'event_id'  => 0,
		];

		$admin_views->template( 'attendees', $context );
	}

	/**
	 * Filters the columns for the Attendees table.
	 *
	 * @since TBD
	 *
	 * @param array $columns The columns for the Attendees table.
	 *
	 * @return array The filtered columns for the Attendees table.
	 */
	public function filter_attendee_table_columns( $columns ) {
		if ( ! $this->is_on_page() ) {
			return $columns;
		}

		return \Tribe__Main::array_insert_after_key(
			'ticket',
			$columns,
			[ 'attendee_event' => esc_html_x( 'Associated Post', 'attendee table actions column header', 'event-tickets' ) ]
		);
	}

	/**
	 * Render the `Associated post` column value.
	 *
	 * @since TBD
	 *
	 * @param string $value  Row item value.
	 * @param array  $item   Row item data.
	 * @param string $column Column name.
	 *
	 * @return string Link with edit icon for edit column.
	 */
	public function render_column_attendee_event( $value, $item, $column ) {
		if ( 'attendee_event' != $column ) {
			return $value;
		}

		if ( ! tribe( 'tickets.attendees' )->user_can_manage_attendees() ) {
			return '';
		}

		// Check if current user has permission to edit.
		if ( ! is_user_logged_in()  ) {
			return '';
		}

		$event_id           = $item['event_id'];
		$provider           = ! empty( $item['provider'] ) ? $item['provider'] : null;
		$is_provider_active = false;

		$tickets_attendees  = tribe( 'tickets.attendees' );
		$post               = get_post( $event_id );
		$post_attendees_url = $tickets_attendees->get_report_link( $post );

		echo sprintf( '<a href="%s" class="">%s</a>', $post_attendees_url, $post->post_title );
	}

}
