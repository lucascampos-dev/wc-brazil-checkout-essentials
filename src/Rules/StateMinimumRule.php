<?php
/**
 * Minimum order amount per Brazilian state.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Rules;

use BrazilCheckoutEssentials\Support\AmountParser;
use BrazilCheckoutEssentials\Support\BrazilianStates;

/**
 * Pure business rule: "orders shipped to UF X must reach at least R$ Y".
 *
 * It knows nothing about carts or WooCommerce; the integration layer feeds
 * it a state and a subtotal and acts on the returned result object.
 */
final class StateMinimumRule {

	/**
	 * Minimum amounts keyed by UF. Only positive amounts are kept.
	 *
	 * @var array<string, float>
	 */
	private $minimums = [];

	/**
	 * Constructor.
	 *
	 * @param array<string, string|int|float|null> $minimums Raw amounts keyed by UF (any case).
	 *                                                       Unknown UFs and non-positive amounts are ignored.
	 */
	public function __construct( array $minimums ) {
		foreach ( $minimums as $state => $amount ) {
			$state  = strtoupper( trim( (string) $state ) );
			$amount = AmountParser::parse( $amount );

			if ( $amount > 0 && BrazilianStates::is_valid( $state ) ) {
				$this->minimums[ $state ] = $amount;
			}
		}
	}

	/**
	 * Minimum configured for a state (0.0 when none).
	 *
	 * @param string $state Two-letter UF.
	 * @return float
	 */
	public function minimum_for( string $state ): float {
		return $this->minimums[ strtoupper( trim( $state ) ) ] ?? 0.0;
	}

	/**
	 * Whether at least one state has a minimum configured.
	 *
	 * @return bool
	 */
	public function has_any(): bool {
		return [] !== $this->minimums;
	}

	/**
	 * Normalized configuration, handy for debugging and admin previews.
	 *
	 * @return array<string, float>
	 */
	public function to_array(): array {
		return $this->minimums;
	}

	/**
	 * Evaluates a subtotal against the state's minimum.
	 *
	 * @param string     $state    Two-letter UF.
	 * @param float      $subtotal Amount to compare.
	 * @param float|null $minimum  Optional override (e.g. after a filter ran).
	 * @return MinimumCheckResult
	 */
	public function evaluate( string $state, float $subtotal, ?float $minimum = null ): MinimumCheckResult {
		$state = strtoupper( trim( $state ) );

		return new MinimumCheckResult(
			$state,
			null === $minimum ? $this->minimum_for( $state ) : max( 0.0, $minimum ),
			$subtotal
		);
	}
}
