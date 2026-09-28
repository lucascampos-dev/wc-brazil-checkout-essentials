<?php
/**
 * Tests for AmountParser.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Tests\Unit;

use BrazilCheckoutEssentials\Support\AmountParser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BrazilCheckoutEssentials\Support\AmountParser
 */
final class AmountParserTest extends TestCase {

	/**
	 * @dataProvider amounts
	 *
	 * @param mixed $raw      Input.
	 * @param float $expected Parsed amount.
	 */
	public function test_parse( $raw, float $expected ): void {
		$this->assertSame( $expected, AmountParser::parse( $raw ) );
	}

	/**
	 * @return array<string, array{0:mixed, 1:float}>
	 */
	public static function amounts(): array {
		return [
			'brazilian format'       => [ '1.234,56', 1234.56 ],
			'us format'              => [ '1,234.56', 1234.56 ],
			'decimal comma'          => [ '99,9', 99.9 ],
			'decimal dot'            => [ '99.90', 99.9 ],
			'currency symbol'        => [ 'R$ 150,00', 150.0 ],
			'thousands dot only'     => [ '1.000', 1000.0 ],
			'thousands comma only'   => [ '1,000', 1000.0 ],
			'multiple thousands'     => [ '1.000.000', 1000000.0 ],
			'integer string'         => [ '250', 250.0 ],
			'integer'                => [ 300, 300.0 ],
			'float rounded to cents' => [ 10.005, 10.01 ],
			'empty'                  => [ '', 0.0 ],
			'null'                   => [ null, 0.0 ],
			'garbage'                => [ 'abc', 0.0 ],
			'negative string'        => [ '-10', 0.0 ],
			'negative number'        => [ -10, 0.0 ],
			'malformed separators'   => [ '1,2,3.4.5', 0.0 ],
		];
	}
}
