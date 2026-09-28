<?php
/**
 * Tests for BrazilianStates.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Tests\Unit;

use BrazilCheckoutEssentials\Support\BrazilianStates;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BrazilCheckoutEssentials\Support\BrazilianStates
 */
final class BrazilianStatesTest extends TestCase {

	public function test_has_all_27_federative_units(): void {
		$this->assertCount( 27, BrazilianStates::ALL );
	}

	public function test_validation_is_case_insensitive(): void {
		$this->assertTrue( BrazilianStates::is_valid( 'SP' ) );
		$this->assertTrue( BrazilianStates::is_valid( 'df' ) );
		$this->assertFalse( BrazilianStates::is_valid( 'XX' ) );
		$this->assertFalse( BrazilianStates::is_valid( '' ) );
	}

	public function test_name(): void {
		$this->assertSame( 'São Paulo', BrazilianStates::name( 'sp' ) );
		$this->assertSame( 'XX', BrazilianStates::name( 'XX' ) );
	}
}
