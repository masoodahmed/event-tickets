<?php

namespace TEC\Tickets\Commerce\Amount;

trait ValueCalculation {

	public function sum( $amounts ) {
	}

	public function multiply( $quantity ) {
	}

	public function to_integer( $value ) {
		return round( $value, $this->precision ) * pow( 10, $this->precision );
	}
}