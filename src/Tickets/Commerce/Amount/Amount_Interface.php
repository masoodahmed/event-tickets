<?php

namespace TEC\Tickets\Commerce\Amount;

interface Amount_Interface {

	public static function get_instance();

	public function set_integer( $amount );

	public function get_integer( Abstract_Amount $amount );

	public function set_float( $amount );

	public function get_float( Abstract_Amount $amount );

	public function set_precision( $amount );

	public function get_precision( Abstract_Amount $amount );

	public function sum( $amounts );

	public function multiply( Abstract_Amount $amount, $quantity );

	public function normalize( $amount );
}