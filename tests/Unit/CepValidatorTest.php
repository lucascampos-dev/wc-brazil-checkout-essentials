<?php
/**
 * Tests for CepValidator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Tests\Unit;

use BrazilCheckoutEssentials\Validation\CepValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BrazilCheckoutEssentials\Validation\CepValidator
 */
final class CepValidatorTest extends TestCase {

	/**
	 * System under test.
	 *
	 * @var CepValidator
	 */
	private $validator;

	protected function setUp(): void {
		$this->validator = new CepValidator();
	}

	/**
	 * @dataProvider valid_ceps
	 *
	 * @param string $cep   Valid CEP.
	 * @param string $state Expected UF.
	 */
	public function test_accepts_valid_cep_and_resolves_state( string $cep, string $state ): void {
		$this->assertTrue( $this->validator->is_valid( $cep ) );
		$this->assertSame( $state, $this->validator->state_for( $cep ) );
		$this->assertTrue( $this->validator->matches_state( $cep, $state ) );
	}

	/**
	 * @return array<string, array{0:string, 1:string}>
	 */
	public static function valid_ceps(): array {
		return [
			'SP lower bound'      => [ '01000-000', 'SP' ],
			'SP unmasked'         => [ '04567890', 'SP' ],
			'RJ'                  => [ '22041-001', 'RJ' ],
			'MG'                  => [ '30130-010', 'MG' ],
			'DF first range'      => [ '70040-010', 'DF' ],
			'DF second range'     => [ '73000-000', 'DF' ],
			'GO between DF gaps'  => [ '72800-000', 'GO' ],
			'GO second range'     => [ '74000-000', 'GO' ],
			'AM first range'      => [ '69005-000', 'AM' ],
			'RR inside AM ranges' => [ '69301-000', 'RR' ],
			'AM second range'     => [ '69400-000', 'AM' ],
			'AP'                  => [ '68900-000', 'AP' ],
			'RS upper bound'      => [ '99999-999', 'RS' ],
			'with dot mask'       => [ '01.310-100', 'SP' ],
		];
	}

	/**
	 * @dataProvider invalid_ceps
	 *
	 * @param string $cep Invalid CEP.
	 */
	public function test_rejects_invalid_cep( string $cep ): void {
		$this->assertFalse( $this->validator->is_valid( $cep ) );
		$this->assertNull( $this->validator->state_for( $cep ) );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function invalid_ceps(): array {
		return [
			'empty'             => [ '' ],
			'all zeros'         => [ '00000-000' ],
			'below first range' => [ '00999-999' ],
			'too short'         => [ '0131-010' ],
			'too long'          => [ '01310-1000' ],
			'letters'           => [ '01310-10A' ],
		];
	}

	public function test_detects_state_mismatch(): void {
		$this->assertFalse( $this->validator->matches_state( '01310-100', 'RJ' ) );
		$this->assertFalse( $this->validator->matches_state( '69301-000', 'AM' ) );
		$this->assertTrue( $this->validator->matches_state( '01310-100', 'sp' ) );
	}

	public function test_unknown_state_never_blocks(): void {
		$this->assertTrue( $this->validator->matches_state( '01310-100', 'XX' ) );
	}

	public function test_format(): void {
		$this->assertSame( '01310-100', $this->validator->format( '01310100' ) );
		$this->assertSame( '0131', $this->validator->format( '0131' ) );
	}
}
