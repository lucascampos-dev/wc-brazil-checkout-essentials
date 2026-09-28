<?php
/**
 * Locale-tolerant money amount parser.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Support;

/**
 * Turns admin-typed amounts such as "1.234,56", "1,234.56", "R$ 150" or
 * "99,9" into a float rounded to cents. Anything unparseable becomes 0.0,
 * which the plugin treats as "no minimum".
 */
final class AmountParser {

	/**
	 * Parses a human-typed amount.
	 *
	 * @param string|int|float|null $raw Raw value.
	 * @return float Non-negative amount rounded to two decimals.
	 */
	public static function parse( $raw ): float {
		if ( is_int( $raw ) || is_float( $raw ) ) {
			return max( 0.0, round( (float) $raw, 2 ) );
		}

		$value = (string) preg_replace( '/[^\d.,\-]/', '', (string) $raw );

		if ( '' === $value || 0 === strpos( $value, '-' ) ) {
			return 0.0;
		}

		$last_comma = strrpos( $value, ',' );
		$last_dot   = strrpos( $value, '.' );

		if ( false !== $last_comma && false !== $last_dot ) {
			// Both present: whichever comes last is the decimal separator.
			$decimal   = $last_comma > $last_dot ? ',' : '.';
			$thousands = ',' === $decimal ? '.' : ',';
			$value     = str_replace( $thousands, '', $value );
			$value     = str_replace( $decimal, '.', $value );
		} elseif ( false !== $last_comma ) {
			$value = self::resolve_single_separator( $value, ',' );
		} elseif ( false !== $last_dot ) {
			$value = self::resolve_single_separator( $value, '.' );
		}

		if ( ! is_numeric( $value ) ) {
			return 0.0;
		}

		return max( 0.0, round( (float) $value, 2 ) );
	}

	/**
	 * Decides whether a lone separator is a decimal or a thousands mark.
	 *
	 * "1.000" / "1,000" / "1.000.000" are thousands; "10,5" / "10.50" are decimals.
	 *
	 * @param string $value     Value containing only digits and $separator.
	 * @param string $separator ',' or '.'.
	 * @return string Value using '.' as the decimal separator.
	 */
	private static function resolve_single_separator( string $value, string $separator ): string {
		$parts       = explode( $separator, $value );
		$is_grouping = count( $parts ) > 2 || 3 === strlen( (string) end( $parts ) );

		if ( $is_grouping ) {
			return implode( '', $parts );
		}

		return str_replace( $separator, '.', $value );
	}
}
