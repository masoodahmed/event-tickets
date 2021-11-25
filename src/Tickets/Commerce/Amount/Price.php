<?php

namespace TEC\Tickets\Commerce\Amount;

class Price extends Abstract_Currency {

	public function __construct( $amount = 0 ) {
		parent::__construct( $amount );
	}

}