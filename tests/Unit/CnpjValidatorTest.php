<?php
/**
 * Tests for CnpjValidator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Tests\Unit;

use BrazilCheckoutEssentials\Validation\CnpjValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BrazilCheckoutEssentials\Validation\CnpjValidator
 */
final class CnpjValidatorTest extends TestCase {

	/**
	 * System under test.
	 *
	 * @var CnpjValidator
	 */
	private $validator;

	protected function setUp(): void {
		$this->validator = new CnpjValidator();
	}

	/**
	 * @dataProvider valid_cnpjs
	 *
	 * @param string $cnpj Valid CNPJ.
	 */
	public function test_accepts_valid_cnpj( string $cnpj ): void {
		$this->assertTrue( $this->validator->is_valid( $cnpj ) );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function valid_cnpjs(): array {
		return [
			'numeric masked'             => [ '11.222.333/0001-81' ],
			'numeric digits only'        => [ '11222333000181' ],
			'alphanumeric official spec' => [ '12.ABC.345/01DE-35' ],
			'alphanumeric unmasked'      => [ '12ABC34501DE35' ],
			'alphanumeric lowercase'     => [ '12.abc.345/01de-35' ],
			'alphanumeric letters first' => [ 'A1.B2C.3D4/E5F6-68' ],
		];
	}

	/**
	 * @dataProvider invalid_cnpjs
	 *
	 * @param string $cnpj Invalid CNPJ.
	 */
	public function test_rejects_invalid_cnpj( string $cnpj ): void {
		$this->assertFalse( $this->validator->is_valid( $cnpj ) );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function invalid_cnpjs(): array {
		return [
			'empty'                    => [ '' ],
			'wrong first digit'        => [ '11.222.333/0001-91' ],
			'wrong second digit'       => [ '11.222.333/0001-82' ],
			'too short'                => [ '11.222.333/0001-8' ],
			'too long'                 => [ '11.222.333/0001-810' ],
			'repeated digits'          => [ '11.111.111/1111-11' ],
			'repeated letters'         => [ 'AAAAAAAAAAAAAA' ],
			'letter in check digits'   => [ '12.ABC.345/01DE-3A' ],
			'alphanumeric wrong digit' => [ '12.ABC.345/01DE-36' ],
			'symbols not allowed'      => [ '12.ABC.345/01DE#35' ],
			'accented letter'          => [ '12.ÁBC.345/01DE-35' ],
		];
	}

	public function test_normalize_uppercases_and_strips_mask(): void {
		$this->assertSame( '12ABC34501DE35', $this->validator->normalize( '12.abc.345/01de-35' ) );
	}

	public function test_format_applies_mask(): void {
		$this->assertSame( '11.222.333/0001-81', $this->validator->format( '11222333000181' ) );
		$this->assertSame( '12.ABC.345/01DE-35', $this->validator->format( '12abc34501de35' ) );
	}

	public function test_format_keeps_input_with_wrong_length(): void {
		$this->assertSame( '11.222', $this->validator->format( '11.222' ) );
	}

	public function test_detects_alphanumeric_format(): void {
		$this->assertTrue( $this->validator->is_alphanumeric( '12.ABC.345/01DE-35' ) );
		$this->assertFalse( $this->validator->is_alphanumeric( '11.222.333/0001-81' ) );
	}

	public function test_check_digits(): void {
		$this->assertSame( '81', CnpjValidator::check_digits( '112223330001' ) );
		$this->assertSame( '35', CnpjValidator::check_digits( '12ABC34501DE' ) );
	}

	public function test_check_digits_rejects_malformed_base(): void {
		$this->expectException( InvalidArgumentException::class );

		CnpjValidator::check_digits( '12abc34501de' );
	}
}
