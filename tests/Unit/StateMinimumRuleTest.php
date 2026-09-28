<?php
/**
 * Tests for StateMinimumRule and MinimumCheckResult.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Tests\Unit;

use BrazilCheckoutEssentials\Rules\StateMinimumRule;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BrazilCheckoutEssentials\Rules\StateMinimumRule
 * @covers \BrazilCheckoutEssentials\Rules\MinimumCheckResult
 * @uses   \BrazilCheckoutEssentials\Support\AmountParser
 * @uses   \BrazilCheckoutEssentials\Support\BrazilianStates
 */
final class StateMinimumRuleTest extends TestCase {

	public function test_ignores_unknown_states_and_non_positive_amounts(): void {
		$rule = new StateMinimumRule(
			[
				'SP' => '150,00',
				'rj' => 200,
				'XX' => '999',
				'MG' => '0',
				'BA' => '-10',
				'PR' => '',
				'SC' => null,
			]
		);

		$this->assertSame(
			[
				'SP' => 150.0,
				'RJ' => 200.0,
			],
			$rule->to_array()
		);
		$this->assertTrue( $rule->has_any() );
	}

	public function test_minimum_lookup_is_case_insensitive(): void {
		$rule = new StateMinimumRule( [ 'SP' => '150' ] );

		$this->assertSame( 150.0, $rule->minimum_for( 'sp' ) );
		$this->assertSame( 150.0, $rule->minimum_for( ' SP ' ) );
		$this->assertSame( 0.0, $rule->minimum_for( 'RJ' ) );
	}

	public function test_empty_configuration(): void {
		$rule = new StateMinimumRule( [] );

		$this->assertFalse( $rule->has_any() );
		$this->assertTrue( $rule->evaluate( 'SP', 0.0 )->is_satisfied() );
	}

	public function test_below_minimum_reports_shortfall(): void {
		$result = ( new StateMinimumRule( [ 'SP' => '150,00' ] ) )->evaluate( 'SP', 120.5 );

		$this->assertFalse( $result->is_satisfied() );
		$this->assertSame( 'SP', $result->state() );
		$this->assertSame( 150.0, $result->minimum() );
		$this->assertSame( 120.5, $result->subtotal() );
		$this->assertSame( 29.5, $result->shortfall() );
	}

	public function test_exactly_the_minimum_is_satisfied(): void {
		$result = ( new StateMinimumRule( [ 'RJ' => '100' ] ) )->evaluate( 'rj', 100.0 );

		$this->assertTrue( $result->is_satisfied() );
		$this->assertSame( 0.0, $result->shortfall() );
	}

	public function test_comparison_is_done_in_cents(): void {
		// 0.1 + 0.2 = 0.30000000000000004 in IEEE-754.
		$result = ( new StateMinimumRule( [ 'MG' => '0.30' ] ) )->evaluate( 'MG', 0.1 + 0.2 );
		$this->assertTrue( $result->is_satisfied() );

		$result = ( new StateMinimumRule( [ 'MG' => '0.30' ] ) )->evaluate( 'MG', 0.29 );
		$this->assertFalse( $result->is_satisfied() );
		$this->assertSame( 0.01, $result->shortfall() );
	}

	public function test_state_without_minimum_is_always_satisfied(): void {
		$result = ( new StateMinimumRule( [ 'SP' => '150' ] ) )->evaluate( 'AC', 1.0 );

		$this->assertTrue( $result->is_satisfied() );
		$this->assertSame( 0.0, $result->minimum() );
	}

	public function test_minimum_override_takes_precedence(): void {
		$rule = new StateMinimumRule( [ 'SP' => '150' ] );

		$this->assertTrue( $rule->evaluate( 'SP', 60.0, 50.0 )->is_satisfied() );
		$this->assertFalse( $rule->evaluate( 'AC', 60.0, 80.0 )->is_satisfied() );
		$this->assertTrue( $rule->evaluate( 'SP', 0.0, -5.0 )->is_satisfied() );
	}
}
