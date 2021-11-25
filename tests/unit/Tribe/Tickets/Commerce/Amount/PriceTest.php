<?php

namespace Tribe\Tickets\Commerce\Amount;

use TEC\Tickets\Commerce\Amount\Price;

class PriceTest extends \Codeception\Test\Unit {

	/**
	 * @skip
	 */
	public function test_get_initial_value_returns_unchanged() {
		$initial_value = 10;
		$price         = new Price( $initial_value );
		$this->assertEquals( $initial_value, $price->get_initial_value() );
	}

	/**
	 * @dataProvider numerical_values
	 */
	public function test_normalize_returns_float( $value, $expected ) {
		$price      = new Price();
		$normalized = $price->normalize( $value );
		$this->assertEquals( $expected, $normalized );
	}

	public function numerical_values() {
		return [
#			[ 10, 10.0 ],
#			[ 10.0, 10.0 ],
#			[ '10', 10.0 ],
#			[ '$ 1.234,56', 1234.56 ],
#			[ '$1,234.56', 1234.56 ],
			[ 'R.1,234.56', 1234.56 ],
			[ '1,234.56 .د.م.', 1234.56 ],
			[ '1,234.56 ฿', 1234.56 ],
			[ '1,234.56 ₺', 1234.56 ],
			[ '1,234.56 ﷼', 1234.56 ],
			[ '1.234,56 $', 1234.56 ],
			[ '1.234,56 Ft', 1234.56 ],
			[ '1.234,56 kr', 1234.56 ],
			[ '1.234,56 Kč', 1234.56 ],
			[ '1.234,56 p.', 1234.56 ],
			[ '1.234,56 zł', 1234.56 ],
			[ '1.234,56 ₫', 1234.56 ],
			[ 'HK$ 1,234.56', 1234.56 ],
			[ 'kr. 1.234,56', 1234.56 ],
			[ 'R.1,234.56', 1234.56 ],
			[ 'R$ 1.234,56', 1234.56 ],
			[ 'RM 1,234.56', 1234.56 ],
			[ '£1,234.56', 1234.56 ],
			[ '¥ 1,234.56', 1234.56 ],
			[ '¥ 1,234.56', 1234.56 ],
			[ '₩ 1,234.56', 1234.56 ],
			[ '₪ 1.234,56', 1234.56 ],
			[ '€1.234,56', 1234.56 ],
			[ '₱ 1,234.56', 1234.56 ],
			[ '₹ 1,234.56', 1234.56 ],
			[ '元 1,234.56', 1234.56 ],
		];
	}
}
