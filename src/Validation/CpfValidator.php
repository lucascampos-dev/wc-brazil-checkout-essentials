<?php
/**
 * CPF (Cadastro de Pessoas Físicas) validator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Validation;

/**
 * Validates the 11-digit individual taxpayer number using the official
 * modulo-11 check-digit algorithm.
 *
 * Example of a well-formed (fictional) value: 529.982.247-25.
 */
final class CpfValidator implements Validator {

	public const LENGTH = 11;

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input.
	 */
	public function normalize( string $value ): string {
		return (string) preg_replace( '/\D+/', '', $value );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input, masked or not.
	 */
	public function is_valid( string $value ): bool {
		// Only digits and the usual mask characters are accepted.
		if ( 1 === preg_match( '/[^\d.\-\s]/', $value ) ) {
			return false;
		}

		$cpf = $this->normalize( $value );

		if ( self::LENGTH !== strlen( $cpf ) ) {
			return false;
		}

		// Sequences such as 000.000.000-00 pass the math but are not issued.
		if ( 1 === preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
			return false;
		}

		return substr( $cpf, 9, 2 ) === self::check_digits( substr( $cpf, 0, 9 ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input.
	 */
	public function format( string $value ): string {
		$cpf = $this->normalize( $value );

		if ( self::LENGTH !== strlen( $cpf ) ) {
			return $value;
		}

		return sprintf(
			'%s.%s.%s-%s',
			substr( $cpf, 0, 3 ),
			substr( $cpf, 3, 3 ),
			substr( $cpf, 6, 3 ),
			substr( $cpf, 9, 2 )
		);
	}

	/**
	 * Computes both check digits for a 9-digit CPF base.
	 *
	 * @param string $base The first nine digits.
	 * @return string The two check digits.
	 * @throws \InvalidArgumentException When the base is not exactly nine digits.
	 */
	public static function check_digits( string $base ): string {
		if ( 1 !== preg_match( '/^\d{9}$/', $base ) ) {
			throw new \InvalidArgumentException( 'A CPF base must have exactly 9 digits.' );
		}

		$digits = $base;

		for ( $position = 9; $position < 11; $position++ ) {
			$sum = 0;

			for ( $i = 0; $i < $position; $i++ ) {
				$sum += (int) $digits[ $i ] * ( $position + 1 - $i );
			}

			$digits .= (string) ( ( $sum * 10 ) % 11 % 10 );
		}

		return substr( $digits, 9, 2 );
	}
}
