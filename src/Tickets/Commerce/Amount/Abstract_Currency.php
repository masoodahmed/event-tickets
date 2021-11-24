<?php

namespace TEC\Tickets\Commerce\Amount;

abstract class Abstract_Currency extends Abstract_Amount implements Currency_Interface {

	private $formatted;

	private $currency_code;

	private $currency_code_fallback;

	private $currency_symbol;

	private $currency_symbol_position;

	use ValueFormatting;

	public function get_currency_code() {
		return $this->currency_code;
	}

	public function get_currency_code_fallback() {
		return $this->currency_code_fallback;
	}

	public function get_currency_symbol() {
		return $this->currency_symbol;
	}

	public function get_currency_symbol_position() {
		return $this->currency_symbol_position;
	}

	public function set_formatted( $amount ) {
		$this->formatted = $amount;
	}

	public function set_decimal( $amount ) {
		$this->decimal = $amount;
	}

	public function get_formatted( Abstract_Currency $amount ) {
		return $amount->formatted;
	}

	public function get_decimal( Abstract_Currency $amount ) {
		return $amount->decimal;
	}

}