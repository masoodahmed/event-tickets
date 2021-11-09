<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Commerce\Shortcodes\Success_Shortcode;
use TEC\Tickets\Settings as Tickets_Settings;
use \tad_DI52_ServiceProvider;

/**
 * Class Payments_Tab
 *
 * @since 5.2.0
 *
 * @package TEC\Tickets\Commerce
 */
class Payments_Tab extends tad_DI52_ServiceProvider {

	/**
	 * Slug for the tab.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $slug = 'payments';

	/**
	 * @inheritdoc
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
	}

	/**
	 * Create the Tickets Commerce Payments Settings Tab.
	 *
	 * @since 5.2.0
	 */
	public function register_tab() {
		$tab_settings = [
			'priority'  => 25,
			'fields'    => $this->get_top_level_settings(),
			'show_save' => true,
		];

		$tab_settings = apply_filters( 'tec_tickets_commerce_payments_tab_settings', $tab_settings );

		new \Tribe__Settings_Tab( static::$slug, esc_html__( 'Payments', 'event-tickets' ), $tab_settings );
	}


	/**
	 * Gets the top level settings for Tickets Commerce.
	 *
	 * @since 5.2.0
	 *
	 * @return array[]
	 */
	public function get_top_level_settings() {

		$plus_link    = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Event Tickets Plus', 'event-tickets' )
		);
		$plus_link_2  = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Check it out!', 'event-tickets' )
		);
		$plus_message = sprintf(
		// Translators: %1$s: The Event Tickets Plus link, %2$s: The word "ticket" in lowercase, %3$s: The "Check it out!" link.
			esc_html_x( 'Tickets Commerce is a light implementation of a commerce gateway using PayPal and simplified stock handling. If you need more advanced features, take a look at %1$s. In addition to integrating with your favorite ecommerce provider, Event Tickets Plus includes options to collect custom information for attendees, check attendees in via QR codes, and share stock between %2$s. %3$s', 'about Tickets Commerce', 'event-tickets' ),
			$plus_link,
			esc_html( tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_about_tribe_commerce' ) ),
			$plus_link_2
		);

		$is_tickets_commerce_enabled = tec_tickets_commerce_is_enabled();

		$top_level_settings = [
			'tribe-form-content-start'     => [
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			'tickets-commerce-header'      => [
				'type' => 'html',
				'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-toggle-wrapper">
								<label class="tec-tickets__admin-settings-tickets-commerce-toggle">
									<input
										type="checkbox"
										name="' . Tickets_Settings::$tickets_commerce_enabled . '"
										' . checked( $is_tickets_commerce_enabled, true, false ) . '
										id="tickets-commerce-enable-input"
										class="tec-tickets__admin-settings-tickets-commerce-toggle-checkbox tribe-dependency tribe-dependency-verified">
										<span class="tec-tickets__admin-settings-tickets-commerce-toggle-switch"></span>
										<span class="tec-tickets__admin-settings-tickets-commerce-toggle-label">' . esc_html__( 'Enable Tickets Commerce', 'event-tickets' ) . '</span>
								</label>
							</div>',

			],
			'tickets-commerce-description' => [
				'type' => 'html',
				'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-description">' . $plus_message . '</div>',
			],
			Tickets_Settings::$tickets_commerce_enabled => [
				'type'            => 'hidden',
				'validation_type' => 'boolean',
			],
		];

		/**
		 * Hook to modify the top level settings for Tickets Commerce.
		 *
		 * @since 5.2.0
		 *
		 * @param array[] $top_level_settings Top level settings.
		 */
		return apply_filters( 'tec_tickets_commerce_settings_top_level', $top_level_settings );
	}

	/**
	 * Generate Chceckout and Success page if not found.
	 *
	 * @since TBD
	 */
	public function maybe_generate_pages() {

		$tc_enabled = tribe_get_request_var( Tickets_Settings::$tickets_commerce_enabled );

		if ( ! tribe_is_truthy( $tc_enabled ) ) {
			return;
		}

		$this->maybe_auto_generate_checkout_page();
		$this->maybe_auto_generate_order_success_page();
	}

	/**
	 * Generate Checkout page with shortcode if not available.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function maybe_auto_generate_checkout_page() {
		if ( tribe( Checkout::class )->page_has_shortcode() ) {
			return false;
		}

		$page_slug   = 'tickets-checkout';
		$page_exists = get_page_by_path( $page_slug );

		if ( $page_exists ) {
			return false;
		}

		$page_data = [
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $page_slug,
			'post_title'     => 'Tickets Checkout',
			'post_content'   => '<!-- wp:shortcode -->[' . Checkout_Shortcode::get_wp_slug() . ']<!-- /wp:shortcode -->',
			'post_parent'    => 0,
			'comment_status' => 'closed',
		];

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			return false;
		}

		return tribe_update_option( Settings::$option_checkout_page, $page_id );
	}

	/**
	 * Generate Order Success page with shortcode if not available.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function maybe_auto_generate_order_success_page() {
		if ( tribe( Success::class )->page_has_shortcode() ) {
			return false;
		}

		$page_slug   = 'tickets-order';
		$page_exists = get_page_by_path( $page_slug );

		if ( $page_exists ) {
			return false;
		}

		$page_data = [
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $page_slug,
			'post_title'     => __( 'Order Completed', 'event-tickets' ),
			'post_content'   => '<!-- wp:shortcode -->[' . Success_Shortcode::get_wp_slug() . ']<!-- /wp:shortcode -->',
			'post_parent'    => 0,
			'comment_status' => 'closed',
		];

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			return false;
		}

		return tribe_update_option( Settings::$option_success_page, $page_id );
	}
}