<?php
/**
 * CNPJ (Cadastro Nacional da Pessoa Jurídica) validator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Validation;

/**
 * Validates the 14-character company taxpayer number.
 *
 * Supports both the classic numeric format (11.222.333/0001-81) and the
 * alphanumeric format introduced by the Brazilian federal revenue service
 * in July 2026 (12.ABC.345/01DE-35). In the alphanumeric format the first
 * twelve characters may be [0-9A-Z], the two check digits stay numeric, and
 * every character is weighted by its ASCII code minus 48 — which keeps the
 * algorithm backwards compatible with purely numeric CNPJs.
 */
final class CnpjValidator implements Validator {

	public const LENGTH = 14;

	private const WEIGHTS_FIRST  = [ 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
	private const WEIGHTS_SECOND = [ 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input.
	 */
	public function normalize( string $value ): string {
		return (string) preg_replace( '/[^0-9A-Z]/', '', strtoupper( $value ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input, masked or not.
	 */
	public function is_valid( string $value ): bool {
		if ( 1 === preg_match( '/[^0-9A-Za-z.\/\-\s]/', $value ) ) {
			return false;
		}

		$cnpj = $this->normalize( $value );

		if ( 1 !== preg_match( '/^[0-9A-Z]{12}\d{2}$/', $cnpj ) ) {
			return false;
		}

		if ( 1 === preg_match( '/^(.)\1{13}$/', $cnpj ) ) {
			return false;
		}

		return substr( $cnpj, 12, 2 ) === self::check_digits( substr( $cnpj, 0, 12 ) );
	}

	/**
	 * Tells whether the value uses the alphanumeric (2026+) format.
	 *
	 * @param string $value Raw user input.
	 * @return bool
	 */
	public function is_alphanumeric( string $value ): bool {
		return 1 === preg_match( '/[A-Z]/', $this->normalize( $value ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input.
	 */
	public function format( string $value ): string {
		$cnpj = $this->normalize( $value );

		if ( self::LENGTH !== strlen( $cnpj ) ) {
			return $value;
		}

		return sprintf(
			'%s.%s.%s/%s-%s',
			substr( $cnpj, 0, 2 ),
			substr( $cnpj, 2, 3 ),
			substr( $cnpj, 5, 3 ),
			substr( $cnpj, 8, 4 ),
			substr( $cnpj, 12, 2 )
		);
	}

	/**
	 * Computes both check digits for a 12-character CNPJ base.
	 *
	 * @param string $base The first twelve characters, uppercase.
	 * @return string The two check digits.
	 * @throws \InvalidArgumentException When the base is not 12 characters of [0-9A-Z].
	 */
	public static function check_digits( string $base ): string {
		if ( 1 !== preg_match( '/^[0-9A-Z]{12}$/', $base ) ) {
			throw new \InvalidArgumentException( 'A CNPJ base must have exactly 12 characters in [0-9A-Z].' );
		}

		$first  = self::digit_for( $base, self::WEIGHTS_FIRST );
		$second = self::digit_for( $base . $first, self::WEIGHTS_SECOND );

		return $first . $second;
	}

	/**
	 * Weighted modulo-11 digit.
	 *
	 * @param string $chars   Characters to weigh.
	 * @param int[]  $weights Weights, same length as $chars.
	 * @return string
	 */
	private static function digit_for( string $chars, array $weights ): string {
		$sum = 0;

		foreach ( $weights as $index => $weight ) {
			$sum += ( ord( $chars[ $index ] ) - 48 ) * $weight;
		}

		$remainder = $sum % 11;

		return (string) ( $remainder < 2 ? 0 : 11 - $remainder );
	}
}
