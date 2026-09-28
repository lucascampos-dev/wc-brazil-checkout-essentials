<?php
/**
 * Tests for CpfValidator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Tests\Unit;

use BrazilCheckoutEssentials\Validation\CpfValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BrazilCheckoutEssentials\Validation\CpfValidator
 */
final class CpfValidatorTest extends TestCase {

	/**
	 * System under test.
	 *
	 * @var CpfValidator
	 */
	private $validator;

	protected function setUp(): void {
		$this->validator = new CpfValidator();
	}

	/**
	 * @dataProvider valid_cpfs
	 *
	 * @param string $cpf Valid CPF.
	 */
	public function test_accepts_valid_cpf( string $cpf ): void {
		$this->assertTrue( $this->validator->is_valid( $cpf ) );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function valid_cpfs(): array {
		return [
			'masked'           => [ '529.982.247-25' ],
			'digits only'      => [ '52998224725' ],
			'another masked'   => [ '111.444.777-35' ],
			'first digit zero' => [ '987.654.321-00' ],
			'with spaces'      => [ ' 123 456 789 09 ' ],
		];
	}

	/**
	 * @dataProvider invalid_cpfs
	 *
	 * @param string $cpf Invalid CPF.
	 */
	public function test_rejects_invalid_cpf( string $cpf ): void {
		$this->assertFalse( $this->validator->is_valid( $cpf ) );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function invalid_cpfs(): array {
		return [
			'empty'                => [ '' ],
			'wrong first digit'    => [ '529.982.247-35' ],
			'wrong second digit'   => [ '529.982.247-26' ],
			'too short'            => [ '529.982.247-2' ],
			'too long'             => [ '529.982.247-250' ],
			'repeated digits'      => [ '111.111.111-11' ],
			'all zeros'            => [ '000.000.000-00' ],
			'letters mixed in'     => [ '529.982.247-2A' ],
			'unexpected separator' => [ '529/982/247-25' ],
		];
	}

	public function test_normalize_strips_mask(): void {
		$this->assertSame( '52998224725', $this->validator->normalize( '529.982.247-25' ) );
	}

	public function test_format_applies_mask(): void {
		$this->assertSame( '529.982.247-25', $this->validator->format( '52998224725' ) );
	}

	public function test_format_keeps_input_with_wrong_length(): void {
		$this->assertSame( '1234', $this->validator->format( '1234' ) );
	}

	public function test_check_digits(): void {
		$this->assertSame( '25', CpfValidator::check_digits( '529982247' ) );
		$this->assertSame( '00', CpfValidator::check_digits( '987654321' ) );
	}

	public function test_check_digits_rejects_malformed_base(): void {
		$this->expectException( InvalidArgumentException::class );

		CpfValidator::check_digits( '12345' );
	}
}
