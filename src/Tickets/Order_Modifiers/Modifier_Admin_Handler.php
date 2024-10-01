<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers
 */

namespace TEC\Tickets\Order_Modifiers;

use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager;

/**
 * Class Modifier_Settings.
 *
 * Manages the admin settings UI in relation to Order Modifiers.
 *
 * @since TBD
 */
class Modifier_Admin_Handler {

	/**
	 * Event Tickets menu page slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets Order Modifiers page slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $slug = 'tec-tickets-order-modifiers';

	/**
	 * Event Tickets Order Modifiers page hook suffix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $hook_suffix = 'tickets_page_tec-tickets-order-modifiers';

	/**
	 * Retrieves the page slug associated with the modifier settings.
	 *
	 * This method returns the page slug.
	 *
	 * @since TBD
	 *
	 * @return string The page slug for the modifier settings.
	 */
	public static function get_page_slug(): string {
		return self::$slug;
	}

	/**
	 * Register hooks and actions.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'add_tec_tickets_order_modifiers_page' ], 15 );
		add_action( 'admin_init', [ $this, 'handle_delete_modifier' ] );
	}

	/**
	 * Defines whether the current page is the Event Tickets Order Modifiers page.
	 *
	 * @since TBD
	 *
	 * @return bool True if on the Order Modifiers page, false otherwise.
	 */
	public function is_on_page(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$slug === $admin_page;
	}

	/**
	 * Returns the main admin order modifiers URL.
	 *
	 * @since TBD
	 *
	 * @param array $args Arguments to pass to the URL.
	 *
	 * @return string The URL for the Order Modifiers admin page.
	 */
	public function get_url( array $args = [] ): string {
		$defaults = [
			'page' => static::$slug,
		];

		// Merge default args and passed args.
		$args = wp_parse_args( $args, $defaults );

		// Generate the admin URL.
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		/**
		 * Filters the URL to the Event Tickets Order Modifiers page.
		 *
		 * @since TBD
		 *
		 * @param string $url The URL to the Order Modifiers page.
		 */
		return apply_filters( 'tec_tickets_order_modifiers_page_url', $url );
	}

	/**
	 * Adds the Event Tickets Order Modifiers page.
	 *
	 * @since TBD
	 */
	public function add_tec_tickets_order_modifiers_page(): void {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => static::$slug,
				'path'     => static::$slug,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Coupon &amp; Fees', 'event-tickets' ),
				'position' => 1.5,
				'callback' => [ $this, 'render_tec_order_modifiers_page' ],
			]
		);
	}

	/**
	 * Render the `Order Modifiers` page for the selected strategy.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_tec_order_modifiers_page(): void {
		// Enqueue required assets for the page.
		tribe_asset_enqueue_group( 'event-tickets-admin-order-modifiers' );

		// Get and sanitize request vars for modifier and modifier_id.
		$modifier_type = sanitize_key( tribe_get_request_var( 'modifier', 'coupon' ) );
		$modifier_id   = absint( tribe_get_request_var( 'modifier_id', '0' ) );
		$is_edit       = tribe_is_truthy( tribe_get_request_var( 'edit', '0' ) );

		// Prepare the context for the page.
		$context = [
			'event_id'    => 0,
			'modifier'    => $modifier_type,
			'modifier_id' => $modifier_id,
			'is_edit'     => $is_edit,
		];

		// Check if form is submitted and process the save.
		$this->handle_form_submission( $context );

		// Get the appropriate strategy for the selected modifier.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// If the strategy doesn't exist, show an error message.
		if ( ! $modifier_strategy ) {
			$this->render_error_message( __( 'Invalid modifier.', 'event-tickets' ) );
			return;
		}

		// Create a Modifier Manager with the selected strategy.
		$manager = new Modifier_Manager( $modifier_strategy );

		if ( ! $is_edit ) {
			$this->render_table_view( $manager, $context );
			return;
		}
		$this->render_edit_view( $manager, $context );
	}

	/**
	 * Retrieves the modifier data by ID.
	 *
	 * @since TBD
	 *
	 * @param int $modifier_id The ID of the modifier to retrieve.
	 *
	 * @return array|null The modifier data or null if not found.
	 */
	protected function get_modifier_data_by_id( int $modifier_id ): ?array {
		// Get the modifier type from the request or default to 'coupon'.
		$modifier_type = tribe_get_request_var( 'modifier', 'coupon' );

		// Get the appropriate strategy for the selected modifier type.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		if ( ! $modifier_strategy ) {
			return null; // Return null if the strategy is not found.
		}

		// Use the strategy to retrieve the modifier data by ID.
		return $modifier_strategy->get_modifier_by_id( $modifier_id, $modifier_type );
	}

	/**
	 * Render the table view for the selected modifier.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Manager $manager The modifier manager.
	 * @param array            $context The context for rendering the table.
	 *
	 * @return void
	 */
	protected function render_table_view( Modifier_Manager $manager, array $context ): void {
		$manager->render_table( $context );
	}

	/**
	 * Render the edit view for the selected modifier.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Manager $manager The modifier manager.
	 * @param array            $context The context for rendering the edit screen.
	 *
	 * @return void
	 */
	protected function render_edit_view( Modifier_Manager $manager, array $context ): void {
		// Get modifier ID from the context.
		$modifier_id = (int) $context['modifier_id'];

		// Merge the modifier data into the context to be passed to the form rendering logic.
		// If a valid modifier ID is provided, fetch the data to populate the form.
		if ( $modifier_id > 0 ) {
			$modifier_data = $this->get_modifier_data_by_id( $modifier_id );

			// Only merge if modifier data is not null.
			if ( ! is_null( $modifier_data ) ) {
				$context = array_merge( $context, $modifier_data );
			} else {
				// @todo redscar - If a modifier ID is sent, and we are unable to find the data, do we display a message?
				echo '<div class="notice notice-error"><p>' . esc_html__( 'We are unable to find that Modifier.', 'event-tickets' ) . '</p></div>';
				return;
			}
		}

		// Render the edit screen, passing the populated context.
		$manager->render_edit_screen( $context );
	}

	/**
	 * Handles the form submission and saves the modifier data.
	 *
	 * @since TBD
	 *
	 * @param array $context The context for rendering the page.
	 *
	 * @return void
	 */
	protected function handle_form_submission( array $context ): void {
		// Check if the form was submitted and verify nonce.
		if ( ! isset( $_POST['order_modifier_form_save'] ) || ! check_admin_referer( 'order_modifier_save_action', 'order_modifier_save_action' ) ) {
			return;
		}

		// Get the appropriate strategy for the selected modifier.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $context['modifier'] );

		// Early bail if the strategy doesn't exist.
		if ( ! $modifier_strategy ) {
			$this->render_error_message( __( 'Invalid modifier.', 'event-tickets' ) );
			return;
		}

		// Set the modifier ID in the post data.
		$_POST['order_modifier_id'] = $context['modifier_id'];

		// Use the Modifier Manager to sanitize and save the data.
		$manager       = new Modifier_Manager( $modifier_strategy );
		$modifier_data = $modifier_strategy->map_form_data_to_model( $_POST );
		$result        = $manager->save_modifier( $modifier_data );

		// Early bail if saving the modifier failed.
		if ( empty( $result ) ) {
			$this->render_error_message( __( 'Failed to save modifier.', 'event-tickets' ) );
			return;
		}

		// If a new modifier was created, redirect to the edit page of the new modifier.
		if ( empty( $context['modifier_id'] ) || 0 === (int) $context['modifier_id'] ) {
			$this->redirect_to_table_page( $result->id, $context );
			return;
		}

		// Show success message for updating an existing modifier.
		$this->render_success_message( __( 'Modifier saved successfully!', 'event-tickets' ) );
	}

	/**
	 * Redirects to the table page after creating the modifier.
	 *
	 * @since TBD
	 *
	 * @param int   $modifier_id The ID of the new modifier.
	 * @param array $context     The context for rendering the page.
	 *
	 * @return void
	 */
	protected function redirect_to_table_page( int $modifier_id, array $context ): void {
		// Manually build the URL.
		$new_url = add_query_arg(
			[
				'page'     => self::$slug,
				'modifier' => $context['modifier'],
			],
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( esc_url_raw( html_entity_decode( $new_url ) ) );
		exit;
	}

	/**
	 * Shows a success message.
	 *
	 * @since TBD
	 *
	 * @param string $message The success message to display.
	 *
	 * @return void
	 */
	protected function render_success_message( string $message ): void {
		printf(
			'<div class="notice notice-success"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Shows an error message.
	 *
	 * @since TBD
	 *
	 * @param string $message The error message to display.
	 *
	 * @return void
	 */
	protected function render_error_message( string $message ): void {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Handles the deletion of a modifier.
	 *
	 * This function checks for the 'delete_modifier' action in the query parameters, verifies the nonce, and
	 * deletes the modifier if the nonce is valid. It also redirects the user back to the referring page after
	 * performing the deletion to avoid form resubmission.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function handle_delete_modifier(): void {
		// Check if the action is 'delete_modifier' and nonce is set.
		$action        = tribe_get_request_var( 'action', '' );
		$modifier_id   = absint( tribe_get_request_var( 'modifier_id', '' ) );
		$nonce         = tribe_get_request_var( '_wpnonce', '' );
		$modifier_type = sanitize_key( tribe_get_request_var( 'modifier', '' ) );

		// Early bail if the action is not 'delete_modifier'.
		if ( 'delete_modifier' !== $action ) {
			return;
		}

		// Bail if the modifier ID or type is empty.
		if ( empty( $modifier_id ) || empty( $modifier_type ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'delete_modifier_' . $modifier_id ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'event-tickets' ) );
		}

		// Get the appropriate strategy for the selected modifier type.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// Handle invalid modifier strategy.
		if ( ! $modifier_strategy ) {
			wp_die( esc_html__( 'Invalid modifier type.', 'event-tickets' ) );
		}

		// Perform the deletion logic.
		$deletion_success = $modifier_strategy->delete_modifier( $modifier_id );

		// Construct the redirect URL with a success or failure flag.
		$redirect_url = remove_query_arg( [ 'action', 'modifier_id', '_wpnonce' ], wp_get_referer() );
		$redirect_url = add_query_arg( 'deleted', $deletion_success ? 'success' : 'fail', $redirect_url );

		// Redirect to the original page to avoid resubmitting the form upon refresh.
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
