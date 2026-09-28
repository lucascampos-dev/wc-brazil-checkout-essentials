<?php
/**
 * Typed access to the plugin options.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials;

use BrazilCheckoutEssentials\Rules\StateMinimumRule;

defined( 'ABSPATH' ) || exit;

/**
 * Single place that knows option names and their defaults.
 */
final class Settings {

	public const ENABLE_DOCUMENT       = 'wcbce_enable_document';
	public const ENABLE_ADDRESS_CHECKS = 'wcbce_enable_address_validation';
	public const ENABLE_CEP_STATE      = 'wcbce_enable_cep_state_check';
	public const ENABLE_MINIMUMS       = 'wcbce_enable_state_minimums';
	public const MINIMUM_STATE_SOURCE  = 'wcbce_minimum_state_source';
	public const STATE_MINIMUMS        = 'wcbce_state_minimums';

	public const SOURCE_SHIPPING = 'shipping';
	public const SOURCE_BILLING  = 'billing';

	/**
	 * Defaults for every option, also used by uninstall.php.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return [
			self::ENABLE_DOCUMENT       => 'yes',
			self::ENABLE_ADDRESS_CHECKS => 'yes',
			self::ENABLE_CEP_STATE      => 'yes',
			self::ENABLE_MINIMUMS       => 'no',
			self::MINIMUM_STATE_SOURCE  => self::SOURCE_SHIPPING,
			self::STATE_MINIMUMS        => [],
		];
	}

	/**
	 * Reads a yes/no option.
	 *
	 * @param string $option One of the ENABLE_* constants.
	 * @return bool
	 */
	public function is_enabled( string $option ): bool {
		$defaults = self::defaults();

		return 'yes' === get_option( $option, $defaults[ $option ] ?? 'no' );
	}

	/**
	 * Which address (billing or shipping) decides the state for minimums.
	 *
	 * @return string One of the SOURCE_* constants.
	 */
	public function minimum_state_source(): string {
		$source = get_option( self::MINIMUM_STATE_SOURCE, self::SOURCE_SHIPPING );

		return self::SOURCE_BILLING === $source ? self::SOURCE_BILLING : self::SOURCE_SHIPPING;
	}

	/**
	 * Builds the state-minimum rule from the stored option.
	 *
	 * @return StateMinimumRule
	 */
	public function state_minimum_rule(): StateMinimumRule {
		$stored = get_option( self::STATE_MINIMUMS, [] );

		return new StateMinimumRule( is_array( $stored ) ? $stored : [] );
	}
}
