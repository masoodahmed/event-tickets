<?php

namespace TEC\Tickets\Commerce\Amount;

interface Currency_Interface {

	public function set_formatted( $amount );

	public function get_formatted( Abstract_Currency $amount );

	public function set_decimal( $amount );

	public function get_decimal( Abstract_Currency $amount );

	public function get_currency_code();

	public function get_currency_code_fallback();

	public function get_currency_symbol();

	public function get_currency_symbol_position();

}