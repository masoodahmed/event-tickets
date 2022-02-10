<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Utils\Value;

/**
 * Stripe orders aka Payment Intents class.
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Payment_Intent {

	/**
	 * Create a simple payment intent with the designated payment methods to check for errors.
	 *
	 * If the payment intent succeeds it is cancelled. If it fails we display a notice to the user and not apply their
	 * settings.
	 *
	 * @since TBD
	 *
	 * @param array $payment_methods a list of payment_methods to allow in the Payment Intent.
	 *
	 * @return bool|\WP_Error
	 */
	public static function test_payment_intent_creation( $payment_methods ) {

		// Payment Intents for cards only are always valid.
		if ( 1 === count( $payment_methods ) && in_array( 'card', $payment_methods, true ) ) {
			return true;
		}

		$value = Value::create( 10 );
		$fee   = Application_Fee::calculate( $value );

		$query_args = [];
		$body       = [
			'currency'               => $value->get_currency_code(),
			'amount'                 => (string) $value->get_integer(),
			'payment_method_types'   => $payment_methods,
			'application_fee_amount' => (string) $fee->get_integer(),
		];

		$args = [
			'body' => $body,
		];

		$url = 'payment_intents';

		$payment_intent = Requests::post( $url, $query_args, $args );

		if ( ! isset( $payment_intent['id'] ) && ! empty( $payment_intent['errors'] ) ) {
			$compiled_errors = static::compile_errors( $payment_intent );

			return new \WP_Error(
				'test-payment-intent-failed',
				__( sprintf( 'Your changes to payment methods accepted were not saved: It was not possible to create a Stripe PaymentIntent with the current configuration. The errors you see below were returned from Stripe, please check for any inconsistencies, or contact Stripe support to fix them and try again: %s', $compiled_errors ), 'event-tickets' )
			);
		}

		static::cancel_payment_intent( $payment_intent['id'] );

		return true;
	}

	/**
	 * Issue an API request to cancel a Payment Intent.
	 *
	 * @since TBD
	 *
	 * @param string $payment_intent_id the payment intent to cancel.
	 */
	public static function cancel_payment_intent( $payment_intent_id ) {
		$query_args = [];
		$body       = [
		];
		$args       = [
			'body' => $body,
		];

		$payment_intent_id = urlencode( $payment_intent_id );
		$url               = '/payment_intents/{payment_intent_id}/cancel';
		$url               = str_replace( '{payment_intent_id}', $payment_intent_id, $url );

		Requests::post( $url, $query_args, $args );
	}

	/**
	 * Compile errors caught when creating a Payment Intent into a proper html notice for the admin.
	 *
	 * @since TBD
	 *
	 * @param array $errors list of errors returned from Stripe.
	 *
	 * @return string
	 */
	public static function compile_errors( $errors ) {
		$compiled = '';

		if ( empty( $errors['errors'] ) ) {
			return $compiled;
		}

		if ( ! is_array( $errors['errors'] ) ) {
			return $errors['errors'];
		}

		foreach ( $errors['errors'] as $error ) {
			$compiled .= sprintf( '<div>%s<p>%s</p></div>', $error[0], $error[1] );
		}

		return $compiled;
	}

}