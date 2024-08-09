<?php
/**
 * Singular order page.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin
 */

namespace TEC\Tickets\Commerce\Admin;

use TEC\Common\Contracts\Service_Provider;
use Tec\Tickets\Commerce\Order;
use Tribe__Template;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe__Tickets__Main;
use WP_Post;

/**
 * Class Singular_Order_Page
 *
 * @since 5.2.0
 *
 * @package TEC\Tickets\Commerce\Admin
 */
class Singular_Order_Page extends Service_Provider {

	/**
	 * Stores the instance of the template engine that we will use for rendering the metaboxes.
	 *
	 * @since TBD
	 *
	 * @var ?Tribe__Template
	 */
	protected $template = null;

	/**
	 * Stores the parent file that we are hijacking.
	 *
	 * @since TBD
	 *
	 * @var ?string
	 */
	protected static $stored_parent_file = null;

	/**
	 * @inheritdoc
	 */
	public function register() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 10, 2 );
		add_action( 'save_post', [ $this, 'update_order_status' ], 10, 2 );

		add_filter( 'submenu_file', [ $this, 'hijack_current_parent_file' ] );
		add_action( 'adminmenu', [ $this, 'restore_current_parent_file' ] );

		add_filter( 'post_updated_messages', [ $this, 'add_order_messages' ] );
	}

	/**
	 * Hijacks the current parent file.
	 *
	 * This is used so in order when a single order is being viewed the Tickets admin menu item is open.
	 *
	 * @since TBD
	 *
	 * @param string $submenu_file The submenu file.
	 *
	 * @return string
	 */
	public function hijack_current_parent_file( $submenu_file ) {
		global $parent_file;

		if ( 'edit.php?post_type=' . Order::POSTTYPE !== $parent_file ) {
			return $submenu_file;
		}

		self::$stored_parent_file = $parent_file;

		$parent_file = 'tec-tickets'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $submenu_file;
	}

	/**
	 * Restores the current parent file.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function restore_current_parent_file() {
		if ( ! isset( self::$stored_parent_file ) ) {
			return;
		}

		global $parent_file;

		$parent_file = self::$stored_parent_file; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * ET Template class instance.
	 *
	 * @since TBD
	 *
	 * @param string $name    The name of the template to load.
	 * @param array  $context The context to pass to the template.
	 * @param bool   $echo    Whether to echo the template or return it.
	 *
	 * @return string|void
	 */
	public function template( $name, $context = [], $echo = true ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound
		if ( ! $this->template ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/commerce/orders/single' );
			$this->template->set_template_context_extract( true );
			$this->template->set_template_folder_lookup( true );
		}

		return $this->template->template( $name, $context, $echo );
	}

	/**
	 * Adds the metaboxes to the order post type.
	 *
	 * @since TBD
	 *
	 * @param string  $post_type The post type.
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function add_meta_boxes( $post_type, $post ): void {
		if ( Order::POSTTYPE !== $post_type ) {
			return;
		}

		add_meta_box(
			'tribe-tickets-order-details',
			__( 'Order Details', 'event-tickets' ),
			[ $this, 'render_order_details' ],
			$post_type,
			'advanced',
			'high'
		);

		add_meta_box(
			'tribe-tickets-order-items',
			__( 'Items', 'event-tickets' ),
			[ $this, 'render_order_items' ],
			$post_type,
			'normal',
			'high'
		);

		global $wp_meta_boxes;

		$meta_box = $wp_meta_boxes[ get_current_screen()->id ]['side']['core']['submitdiv'] ?? false;

		// Remove core's Publish metabox and add our own.
		remove_meta_box( 'submitdiv', $post_type, 'side' );
		add_meta_box(
			'submitdiv',
			__( 'Actions', 'event-tickets' ),
			[ $this, 'render_actions' ],
			$post_type,
			'side',
			'high',
			$meta_box['args'] ?? []
		);
	}

	/**
	 * Renders the actions metabox.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function render_actions( $post ): void {
		$this->template(
			'order-actions-metabox',
			[
				'order'       => tec_tc_get_order( $post ),
				'single_page' => $this,
			]
		);
	}

	/**
	 * Renders the order details metabox.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function render_order_details( $post ): void {
		$this->template(
			'order-details-metabox',
			[
				'order'       => tec_tc_get_order( $post ),
				'single_page' => $this,
			]
		);
	}

	/**
	 * Renders the order items metabox.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function render_order_items( $post ): void {
		$this->template(
			'order-items-metabox',
			[
				'order'       => tec_tc_get_order( $post ),
				'single_page' => $this,
			]
		);
	}

	/**
	 * Get the gateway label for the order.
	 *
	 * @since TBD
	 *
	 * @param WP_Post|int $order The order post object or ID.
	 *
	 * @return string
	 */
	public function get_gateway_label( $order ): string {
		if ( is_numeric( $order ) ) {
			$order = tec_tc_get_order( $order );
		}

		if ( ! $order instanceof WP_Post ) {
			return '';
		}

		$gateway = tribe( Manager::class )->get_gateway_by_key( $order->gateway );

		if ( $gateway instanceof Free_Gateway ) {
			return esc_html__( 'Free', 'event-tickets' );
		}

		if ( ! $gateway ) {
			return esc_html( $order->gateway );
		}

		$order_url = $gateway->get_order_controller()->get_gateway_dashboard_url_by_order( $order );

		if ( empty( $order_url ) ) {
			return esc_html( $gateway::get_label() );
		}

		ob_start();
		tribe_copy_to_clipboard_button( $order->gateway_order_id, true, __( 'Copy Payment\'s Gateway Transaction ID to your Clipboard', 'event-tickets' ) );
		$copy_button = ob_get_clean();

		return sprintf(
			'%1$s%2$s%3$s%4$s<br>%5$s',
			'<a class="tribe-dashicons" href="' . esc_url( $order_url ) . '" target="_blank" rel="noopener noreferrer">',
			esc_html( $gateway::get_label() ),
			'<span class="dashicons dashicons-external"></span>',
			'</a>',
			$copy_button
		);
	}

	/**
	 * Updates the order status.
	 *
	 * @since TBD
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function update_order_status( $post_id, $post ) {
		if ( Order::POSTTYPE !== $post->post_type ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$new_status = tribe_get_request_var( 'tribe-tickets-commerce-status', false );

		if ( ! $new_status ) {
			return;
		}

		$order = tec_tc_get_order( $post );

		if ( ! $order instanceof WP_Post ) {
			return;
		}

		$new_status = tribe( Status_Handler::class )->get_by_slug( $new_status );

		if ( $new_status->get_wp_slug() === $order->post_status ) {
			return;
		}

		$current_status = tribe( Status_Handler::class )->get_by_wp_slug( $order->post_status );

		if ( ! $current_status->can_apply_to( $order, $new_status ) ) {
			$this->redirect_with_message( $post_id, 1001 );
			return;
		}

		$result = tribe( Order::class )->modify_status( $order->ID, $new_status->get_slug() );

		if ( ! $result || is_wp_error( $result ) ) {
			$this->redirect_with_message( $post_id, 1001 );
			return;
		}

		$this->redirect_with_message( $post_id, 1000 );
	}

	/**
	 * Adds the order messages.
	 *
	 * @since TBD
	 *
	 * @param array $messages The messages.
	 *
	 * @return array
	 */
	public function add_order_messages( $messages ) {
		global $post_type;

		if ( Order::POSTTYPE !== $post_type ) {
			return $messages;
		}

		$messages[ Order::POSTTYPE ] = [
			1000 => __( 'Order status updated!', 'event-tickets' ),
			1001 => __( 'Order status could not be updated.', 'event-tickets' ),
		];

		return $messages;
	}

	/**
	 * Redirects to the order page with a message.
	 *
	 * Takes advantage of WP's core way of displaying messages in admin through the message query arg.
	 * The message codes need to be high int in order to not conflict with WP's core messages.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id      The post ID.
	 * @param string $message_code The message code.
	 *
	 * @return void
	 */
	protected function redirect_with_message( $post_id, $message_code ) {
		// Failure.
		if ( $message_code > 1000 ) {
			$callback = function () use ( $message_code ) {
				$messages = apply_filters( 'post_updated_messages', [] );

				if ( ! isset( $messages[ Order::POSTTYPE ][ $message_code ] ) ) {
					return;
				}

				$message = $messages[ Order::POSTTYPE ][ $message_code ];

				echo wp_kses_post(
					wp_get_admin_notice(
						$message,
						[
							'type'               => 'error',
							'dismissible'        => true,
							'id'                 => 'message',
							'additional_classes' => [ 'error' ],
						]
					)
				);
			};

			add_action( 'admin_notices', $callback );
			return;
		}


		// Success.
		add_filter(
			'redirect_post_location',
			function ( $location, $pid ) use ( $post_id, $message_code ) {
				if ( (int) $pid !== $post_id ) {
					return $location;
				}

				return add_query_arg( 'message', $message_code, $location );
			},
			10,
			2
		);
	}
}
