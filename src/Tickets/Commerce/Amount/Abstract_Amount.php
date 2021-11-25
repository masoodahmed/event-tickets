<?php

namespace TEC\Tickets\Commerce\Amount;

abstract class Abstract_Amount implements Amount_Interface {

	public static $instance;

	private $initial_value;

	private $normalized_amount;

	private $integer = 0;

	private $float = 0;

	private $precision = 2;

	use ValueCalculation;

	public function __construct( $amount = 0 ) {
		$this->set_initial_value( $amount );
		$this->set_normalized_amount( $amount );

		$this->hydrate();
	}

	private function set_initial_value( $amount ) {
		if ( empty( $this->initial_value ) ) {
			$this->initial_value = $amount;
		}
	}

	public function set_normalized_amount( $amount ) {
		$this->normalized_amount = $this->normalize( $amount );
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

	public function get_normalized_amount( Abstract_Amount $amount ) {
		return $amount->normalized_amount;
	}

	public function get_initial_value() {
		return $this->initial_value;
	}

	public function normalize( $amount ) {

		// If we can split the amount by spaces, remove any blocks that don't contain any digits
		// This is important in case the currency unit contains the same characters as the decimal/thousands
		// separators such as in Moroccan Dirham (1,234.56 .د.م.) or Danish Krone (kr. 1.234,56)
		foreach ( explode( ' ', $amount ) as $block ) {
			if ( $this->is_character_block( $block ) ) {
				$amount = str_replace( $block, '', $amount );
			}
		}

		// Remove encoded html entities
		$amount = preg_replace( '/&[^;]+;/', '', trim( $amount ) );

		// Get all non-digits from the amount
		preg_match_all( '/[^\d]/', $amount, $matches );

		// if the string is all digits, it is numeric
		if ( empty( $matches ) ) {
			return (float) $amount;
		}

		$tokens = array_unique( $matches[0] );

		foreach ( $tokens as $token ) {
			if ( $this->is_decimal_separator( $token, $amount ) ) {
				$amount = str_replace( $token, '.', trim( $amount, $token ) );
				continue;
			}

			$amount = str_replace( $token, '', $amount );
		}

		return (float) $amount;
	}

	/**
	 * Tries to determine if a token is serving as a decimal separator or something else
	 * in a string;
	 *
	 * The rule to determine a decimal is straightforward. It needs to exist only once
	 * in the string and the piece of the string after the separator cannot be longer
	 * than 2 digits. Anything else is serving another purpose.
	 *
	 * @since 5.2.0
	 *
	 * @param $separator string a separator token, like . or ,
	 * @param $value     string a number formatted as a string
	 *
	 * @return bool
	 */
	public function is_decimal_separator( $separator, $value ) {
		$pieces = array_filter( explode( $separator, $value ) );

		foreach ( $pieces as $i => $block ) {
			if ( $this->is_character_block( $block ) ) {
				unset( $pieces[ $i ] );
			}
		}

		if ( 2 === count( $pieces ) ) {
			return strlen( $pieces[1] ) < 3;
		}

		return false;
	}

	private function is_character_block( $block ) {
		return empty( preg_replace( '/\D/', '', $block ) );
	}

	private function hydrate() {
		$amount = $this->get_normalized_amount( $this );

		$this->set_formatted( $amount );
		$this->set_integer( $amount );
		$this->set_decimal( $amount );
	}
}