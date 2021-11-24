<?php

namespace TEC\Tickets\Commerce\Amount;

abstract class Abstract_Amount implements Amount_Interface {

	public static $instance;

	private static $initial_amount;

	private static $normalized_amount;

	private $integer = 0;

	private $float = 0;

	private $precision = 2;

	use ValueCalculation;

	public function __construct( $amount = 0 ) {
		static::$initial_amount = $amount;

		static::$normalized_amount = $this->normalize( $amount );
		$this->hydrate();
	}

	public function set_integer( $amount ) {
		$this->integer = $amount;
	}

	public function set_float( $amount ) {
		$this->float = $amount;
	}

	public function set_precision( $amount ) {
		$this->precision = $amount;
	}

	public function get_integer( Abstract_Amount $amount ) {
		return $amount->integer;
	}

	public function get_float( Abstract_Amount $amount ) {
		return $amount->float;
	}

	public function get_precision( Abstract_Amount $amount ) {
		return $amount->precision;
	}

	public function normalize( $amount ) {

		$amount = preg_replace( '/&[^;]+;/', '', $amount );

		// Get all non-digits from the amount
		preg_match_all( '/[^\d]/', $amount, $matches );

		// if the string is all digits, it is numeric
		if ( empty( $matches ) ) {
			return $amount;
		}

		$tokens = array_unique( $matches[0] );

		foreach ( $tokens as $token ) {
			if ( static::is_decimal_separator( $token, $amount ) ) {
				$amount = str_replace( $token, '.', $amount );
				continue;
			}

			$amount = str_replace( $token, '', $amount );
		}

		return (float) $amount;
	}

	private function hydrate() {
		$amount = static::$normalized_amount;

		$this->set_formatted( $amount );
		$this->set_integer( $amount );
		$this->set_decimal( $amount );
	}
}