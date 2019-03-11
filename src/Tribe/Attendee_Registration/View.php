<?php

/**
 * Class Tribe__Tickets__Attendee_Registration__View
 */
class Tribe__Tickets__Attendee_Registration__View extends Tribe__Template {
	/**
	 * Tribe__Tickets__Attendee_Registration__View constructor.
	 *
	 * @since 4.9
	 */
	public function __construct() {
		$this->set_template_origin( tribe( 'tickets.main' ) );
		$this->set_template_folder( 'src/views' );
		$this->set_template_context_extract( true );
		$this->set_template_folder_lookup( true );
	}

	/**
	 * Display the Attendee Info page when the correct permalink is loaded.
	 *
	 * @since 4.9
	 * @param string $content The original page|post content
	 * @param string $context The context of the rendering
	 * @return string $template The resulting template content
	 */
	public function display_attendee_registration_page( $content = '', $context = 'default' ) {
		global $wp_query;

		// Bail if we don't have the flag to be in the registration page (or we're not using a shortcode to display it)
		if ( 'shortcode' !== $context && ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return $content;
		}
		/**
		 * Filter to add/remove tickets from the global cart
		 *
		 * @since TDB
		 *
		 * @param array  The array containing the cart elements. Format arrat( 'ticket_id' => 'quantity' );
		 */
		$cart_tickets = apply_filters( 'tribe_tickets_tickets_in_cart', array() );
		$events       = array();
		$providers    = array();

		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			// Load the tickets in cart for each event, with their ID, quantity and provider.
			$ticket = tribe( 'tickets.handler' )->get_object_connections( $ticket_id );

			$ticket_data = array(
				'id'       => $ticket_id,
				'qty'      => $quantity,
				'provider' => $ticket->provider,
			);

			/**
			 * Flag for event form to flag TPP. This is used for the AJAX
			 * feature for save attendee information. If the provider is
			 * TPP, then AJAX saving is disabled.
			 *
			 * @todo: This is temporary until we can figure out what to do
			 *        with the Attendee Registration page handling multiple
			 *        payment providers.
			 */
			$provider = '';
			switch ( $ticket->provider->class_name ) {
				case 'Tribe__Tickets__Commerce__PayPal__Main':
					$provider = 'tpp';
					break;
				case 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main':
					$provider = 'woo';
					break;
				case 'Tribe__Tickets_Plus__Commerce__EDD__Main':
					$provider = 'edd';
					break;
				default:
					break;
			}
			$providers[ $ticket->event ] = $provider;
			$events[ $ticket->event ][] = $ticket_data;
		}

		/**
		 * Check if the cart has a ticket with required meta fields
		 *
		 * @since TDB
		 *
		 * @param array  The array containing the cart elements. Format arrat( 'ticket_id' => 'quantity' );
		 */
		$cart_has_required_meta = (bool) apply_filters( 'tribe_tickets_attendee_registration_has_required_meta', $cart_tickets );

		// Get the checkout URL, it'll be added to the checkout button
		$checkout_url = tribe( 'tickets.attendee_registration' )->get_checkout_url();

		/**
		 * Filter to check if there's any required meta that wasn't filled in
		 *
		 * @since TDB
		 *
		 * @param bool
		 */
		$is_meta_up_to_date = (int) apply_filters( 'tribe_tickets_attendee_registration_is_meta_up_to_date', true );

		/**
		 *  Set all the template variables
		 */
		$args = array(
			'events'                 => $events,
			'checkout_url'           => $checkout_url,
			'is_meta_up_to_date'     => $is_meta_up_to_date,
			'cart_has_required_meta' => $cart_has_required_meta,
			'providers'              => $providers,
		);

		// enqueue styles and scripts for this page
		tribe_asset_enqueue( 'event-tickets-registration-page-styles' );
		tribe_asset_enqueue( 'event-tickets-registration-page-scripts' );

		wp_enqueue_style( 'dashicons' );

		$this->add_template_globals( $args );

		return $this->template( 'registration/content', $args, false );
	}

	/**
	 * Get the provider Cart URL if WooCommerce is the provider.
	 * Checks the provider by post id (event)
	 *
	 * @since 4.9
	 *
	 * @param int $post_id
	 * @return bool|string
	 */
	public function get_cart_url( $post_id ) {

		$post_provider = get_post_meta( $post_id, tribe( 'tickets.handler' )->key_provider_field, true );

		if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' !== $post_provider ) {
			return false;
		}

		$provider = new $post_provider;

		return $provider->get_cart_url();
	}
}
