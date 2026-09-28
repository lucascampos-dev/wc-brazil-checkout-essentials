<?php
/**
 * Contract shared by every Brazilian identifier validator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Validation;

/**
 * A validator knows how to clean, check and pretty-print one kind of value.
 *
 * Implementations MUST be free of WordPress/WooCommerce calls so they can be
 * unit-tested in isolation and reused outside the plugin.
 */
interface Validator {

	/**
	 * Strips presentation characters (dots, dashes, slashes, spaces...).
	 *
	 * @param string $value Raw user input.
	 * @return string
	 */
	public function normalize( string $value ): string;

	/**
	 * Tells whether the given raw input is a valid value.
	 *
	 * @param string $value Raw user input, masked or not.
	 * @return bool
	 */
	public function is_valid( string $value ): bool;

	/**
	 * Returns the canonical, human-friendly representation of the value.
	 *
	 * Invalid input is returned untouched so the caller never loses data.
	 *
	 * @param string $value Raw user input.
	 * @return string
	 */
	public function format( string $value ): string;
}
