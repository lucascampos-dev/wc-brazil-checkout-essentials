<?php
/**
 * Brazilian phone number validator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Validation;

/**
 * Validates Brazilian landline and mobile numbers.
 *
 * Accepted shapes (national significant number, after cleaning):
 *  - Mobile:   DDD + 9 + 8 digits  → (11) 91234-5678
 *  - Landline: DDD + [2-5] + 7 digits → (11) 2345-6789
 *
 * The +55 country code and the legacy trunk prefix "0" are tolerated and
 * stripped during normalization.
 */
final class PhoneValidator implements Validator {

	/**
	 * Area codes (DDD) currently allocated by the telecom regulator, grouped
	 * by their leading digit (region) for readability.
	 *
	 * @var array<string, string>
	 */
	private const AREA_CODES = [
		'1' => '11 12 13 14 15 16 17 18 19', // SP.
		'2' => '21 22 24 27 28',             // RJ, ES.
		'3' => '31 32 33 34 35 37 38',       // MG.
		'4' => '41 42 43 44 45 46 47 48 49', // PR, SC.
		'5' => '51 53 54 55',                // RS.
		'6' => '61 62 63 64 65 66 67 68 69', // DF, GO, TO, MT, MS, AC, RO.
		'7' => '71 73 74 75 77 79',          // BA, SE.
		'8' => '81 82 83 84 85 86 87 88 89', // PE, AL, PB, RN, CE, PI.
		'9' => '91 92 93 94 95 96 97 98 99', // PA, AM, RR, AP, MA.
	];

	/**
	 * {@inheritDoc}
	 *
	 * Returns the national number (DDD + subscriber number), digits only.
	 *
	 * @param string $value Raw user input.
	 */
	public function normalize( string $value ): string {
		$digits = (string) preg_replace( '/\D+/', '', $value );
		$length = strlen( $digits );

		// Country code: +55 11 91234-5678 (13) or +55 11 2345-6789 (12).
		if ( ( 12 === $length || 13 === $length ) && 0 === strpos( $digits, '55' ) ) {
			return substr( $digits, 2 );
		}

		// Trunk prefix: 0 11 91234-5678 (12) or 0 11 2345-6789 (11).
		if ( ( 11 === $length || 12 === $length ) && '0' === $digits[0] ) {
			return substr( $digits, 1 );
		}

		return $digits;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input, masked or not.
	 */
	public function is_valid( string $value ): bool {
		if ( 1 === preg_match( '/[^\d\s().+\-]/', $value ) ) {
			return false;
		}

		return $this->is_mobile( $value ) || $this->is_landline( $value );
	}

	/**
	 * Tells whether the value is a valid mobile number.
	 *
	 * @param string $value Raw user input.
	 * @return bool
	 */
	public function is_mobile( string $value ): bool {
		$number = $this->normalize( $value );

		return 1 === preg_match( '/^\d{2}9\d{8}$/', $number ) && $this->has_valid_area_code( $number );
	}

	/**
	 * Tells whether the value is a valid landline number.
	 *
	 * @param string $value Raw user input.
	 * @return bool
	 */
	public function is_landline( string $value ): bool {
		$number = $this->normalize( $value );

		return 1 === preg_match( '/^\d{2}[2-5]\d{7}$/', $number ) && $this->has_valid_area_code( $number );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input.
	 */
	public function format( string $value ): string {
		if ( ! $this->is_valid( $value ) ) {
			return $value;
		}

		$number = $this->normalize( $value );
		$split  = strlen( $number ) - 4;

		return sprintf( '(%s) %s-%s', substr( $number, 0, 2 ), substr( $number, 2, $split - 2 ), substr( $number, $split ) );
	}

	/**
	 * Returns the number in E.164 format (+5511912345678), or an empty string
	 * when the value is not a valid Brazilian number.
	 *
	 * @param string $value Raw user input.
	 * @return string
	 */
	public function to_e164( string $value ): string {
		return $this->is_valid( $value ) ? '+55' . $this->normalize( $value ) : '';
	}

	/**
	 * Checks the two leading digits against the allocated DDD list.
	 *
	 * @param string $number National number, digits only.
	 * @return bool
	 */
	private function has_valid_area_code( string $number ): bool {
		$area_code = substr( $number, 0, 2 );
		$region    = self::AREA_CODES[ $area_code[0] ] ?? '';

		return in_array( $area_code, explode( ' ', $region ), true );
	}
}
