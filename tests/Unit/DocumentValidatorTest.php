<?php
/**
 * Tests for DocumentValidator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Tests\Unit;

use BrazilCheckoutEssentials\Validation\DocumentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BrazilCheckoutEssentials\Validation\DocumentValidator
 */
final class DocumentValidatorTest extends TestCase {

	/**
	 * System under test.
	 *
	 * @var DocumentValidator
	 */
	private $validator;

	protected function setUp(): void {
		$this->validator = new DocumentValidator();
	}

	/**
	 * @dataProvider detection_cases
	 *
	 * @param string      $value    Input.
	 * @param string|null $expected Expected type.
	 */
	public function test_detects_type( string $value, ?string $expected ): void {
		$this->assertSame( $expected, $this->validator->detect_type( $value ) );
	}

	/**
	 * @return array<string, array{0:string, 1:string|null}>
	 */
	public static function detection_cases(): array {
		return [
			'cpf'                  => [ '529.982.247-25', DocumentValidator::TYPE_CPF ],
			'cnpj numeric'         => [ '11.222.333/0001-81', DocumentValidator::TYPE_CNPJ ],
			'cnpj alphanumeric'    => [ '12.ABC.345/01DE-35', DocumentValidator::TYPE_CNPJ ],
			'11 chars with letter' => [ '529A8224725', null ],
			'unknown length'       => [ '12345', null ],
		];
	}

	public function test_validates_and_formats_both_types(): void {
		$this->assertTrue( $this->validator->is_valid( '52998224725' ) );
		$this->assertTrue( $this->validator->is_valid( '12ABC34501DE35' ) );
		$this->assertFalse( $this->validator->is_valid( '52998224726' ) );
		$this->assertFalse( $this->validator->is_valid( '' ) );

		$this->assertSame( '529.982.247-25', $this->validator->format( '52998224725' ) );
		$this->assertSame( '12.ABC.345/01DE-35', $this->validator->format( '12abc34501de35' ) );
		$this->assertSame( 'abc', $this->validator->format( 'abc' ) );
	}
}
