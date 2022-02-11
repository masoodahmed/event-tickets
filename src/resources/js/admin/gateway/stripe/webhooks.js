/* global ClipboardJS, URLSearchParams */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.2.0
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway = tribe.tickets.commerce.gateway || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe = tribe.tickets.commerce.gateway.stripe || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe.webhooks = {};

( ( $, obj, ajaxurl ) => {
	"use strict";

	/**
	 * Stores the all selectors used on this module.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		button: '.tribe-field-tickets-commerce-stripe-webhooks-copy',
		signingKey: '[name="tickets-commerce-stripe-webhooks-signing-key"]',
		statusLabel: '.tribe-field-tickets-commerce-stripe-webhooks-signing-key-status',
		tooltip: '.tooltip' ,
		genericDashicon: '.dashicon',
	};

	/**
	 * Stores the ClipboardJS instance for later reference.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.clipboardButton = null;

	/**
	 * Configures the Copy URL UI.
	 *
	 * @since TBD
	 */
	obj.setupCopyUrl = () => {
		obj.clipboardButton = new ClipboardJS( obj.selectors.button );
		$( obj.selectors.button ).on( 'click', event => event.preventDefault() );
	};

	/**
	 * Configures the signing key input events.
	 *
	 * @since TBD
	 */
	obj.setupSigningValidation = () => {
		$( obj.selectors.signingKey ).on( 'change', obj.onSigningFieldChange );
	};

	/**
	 * When the signing field changes.
	 *
	 * @since TBD
	 *
	 * @param event {Event}
	 *
	 * @return {Promise<*>}
	 */
	obj.onSigningFieldChange = async ( event ) => {
		const $field = $( event.target );
		const $tooltip = $field.siblings( obj.selectors.tooltip );
		const $statusIcon = $tooltip.find( obj.selectors.genericDashicon );
		const $statusLabel = $tooltip.find( obj.selectors.statusLabel );

		const params = new URLSearchParams();
		params.set( 'signing_key', $field.val() );
		params.set( 'action', 'tec_tickets_commerce_gateway_stripe_test_webhooks' );

		$field.prop( 'disabled', true );

		const args = {
			timeout: 30000,
			body: params,
			headers: {
				'X-WP-Nonce': $field.data( 'ajaxNonce' ),
			},
			hooks: {
				beforeRequest: [
					request => {
						$statusLabel.text( $field.data( 'loadingText' ) );
						$statusIcon.removeClass( 'dashicons-no dashicons-yes' ).addClass( 'dashicons-update' );
					},
				],
			},
		};

		const response = await tribe.ky.post( ajaxurl, args ).json();

		$field.prop( 'disabled', false );
		if ( response.data.is_valid_webhook ) {
			$statusIcon.removeClass( 'dashicons-update' ).addClass( 'dashicons-yes' );
			$statusLabel.text( response.data.status );
		} else {
			$statusIcon.removeClass( 'dashicons-update' ).addClass( 'dashicons-no' );
			$statusLabel.text( response.data.status );
		}

		return response;
	};

	/**
	 * Runs when jQuery determines that the document is ready.
	 */
	obj.ready = () => {
		obj.setupCopyUrl();
		obj.setupSigningValidation();
	};

	$( document ).ready( obj.ready );
} )( jQuery, {}, window.ajaxurl );