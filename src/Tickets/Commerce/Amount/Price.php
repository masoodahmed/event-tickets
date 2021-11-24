<?php

namespace TEC\Tickets\Commerce\Amount;

class Price extends Abstract_Currency {

	public static function get_instance() {
		if ( ! static::$instance ) {
			static::$instance = new Price( 10 );
		}

		return static::$instance;
	}

	public function __construct( $amount = 0 ) {
		parent::__construct( $amount );
	}

}