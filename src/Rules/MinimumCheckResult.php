<?php
/**
 * Value object returned by StateMinimumRule.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Rules;

/**
 * Immutable outcome of a minimum-amount check.
 *
 * Comparisons are done in integer cents to avoid floating-point surprises
 * such as 0.1 + 0.2 !== 0.3.
 */
final class MinimumCheckResult {

	/**
	 * Two-letter UF.
	 *
	 * @var string
	 */
	private $state;

	/**
	 * Minimum amount required (0.0 = no minimum).
	 *
	 * @var float
	 */
	private $minimum;

	/**
	 * Subtotal that was evaluated.
	 *
	 * @var float
	 */
	private $subtotal;

	/**
	 * Constructor.
	 *
	 * @param string $state    Two-letter UF.
	 * @param float  $minimum  Minimum amount.
	 * @param float  $subtotal Evaluated subtotal.
	 */
	public function __construct( string $state, float $minimum, float $subtotal ) {
		$this->state    = $state;
		$this->minimum  = $minimum;
		$this->subtotal = $subtotal;
	}

	/**
	 * Evaluated UF.
	 *
	 * @return string
	 */
	public function state(): string {
		return $this->state;
	}

	/**
	 * Minimum amount required.
	 *
	 * @return float
	 */
	public function minimum(): float {
		return $this->minimum;
	}

	/**
	 * Evaluated subtotal.
	 *
	 * @return float
	 */
	public function subtotal(): float {
		return $this->subtotal;
	}

	/**
	 * True when there is no minimum or the subtotal reaches it.
	 *
	 * @return bool
	 */
	public function is_satisfied(): bool {
		return self::to_cents( $this->subtotal ) >= self::to_cents( $this->minimum );
	}

	/**
	 * How much is missing to reach the minimum (0.0 when satisfied).
	 *
	 * @return float
	 */
	public function shortfall(): float {
		$missing = self::to_cents( $this->minimum ) - self::to_cents( $this->subtotal );

		return $missing > 0 ? $missing / 100 : 0.0;
	}

	/**
	 * Converts an amount to integer cents.
	 *
	 * @param float $amount Amount.
	 * @return int
	 */
	private static function to_cents( float $amount ): int {
		return (int) round( $amount * 100 );
	}
}
