<?php
/**
 * CEP (Código de Endereçamento Postal) validator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Validation;

/**
 * Validates Brazilian postcodes and checks them against the state (UF)
 * ranges published by the national postal service.
 */
final class CepValidator implements Validator {

	public const LENGTH = 8;

	/**
	 * Inclusive ranges of the 5-digit CEP prefix allocated to each UF.
	 *
	 * @var array<string, array<int, array{0:int, 1:int}>>
	 */
	private const STATE_RANGES = [
		'SP' => [ [ 1000, 19999 ] ],
		'RJ' => [ [ 20000, 28999 ] ],
		'ES' => [ [ 29000, 29999 ] ],
		'MG' => [ [ 30000, 39999 ] ],
		'BA' => [ [ 40000, 48999 ] ],
		'SE' => [ [ 49000, 49999 ] ],
		'PE' => [ [ 50000, 56999 ] ],
		'AL' => [ [ 57000, 57999 ] ],
		'PB' => [ [ 58000, 58999 ] ],
		'RN' => [ [ 59000, 59999 ] ],
		'CE' => [ [ 60000, 63999 ] ],
		'PI' => [ [ 64000, 64999 ] ],
		'MA' => [ [ 65000, 65999 ] ],
		'PA' => [ [ 66000, 68899 ] ],
		'AP' => [ [ 68900, 68999 ] ],
		'AM' => [ [ 69000, 69299 ], [ 69400, 69899 ] ],
		'RR' => [ [ 69300, 69399 ] ],
		'AC' => [ [ 69900, 69999 ] ],
		'DF' => [ [ 70000, 72799 ], [ 73000, 73699 ] ],
		'GO' => [ [ 72800, 72999 ], [ 73700, 76799 ] ],
		'RO' => [ [ 76800, 76999 ] ],
		'TO' => [ [ 77000, 77999 ] ],
		'MT' => [ [ 78000, 78899 ] ],
		'MS' => [ [ 79000, 79999 ] ],
		'PR' => [ [ 80000, 87999 ] ],
		'SC' => [ [ 88000, 89999 ] ],
		'RS' => [ [ 90000, 99999 ] ],
	];

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
		if ( 1 === preg_match( '/[^\d.\-\s]/', $value ) ) {
			return false;
		}

		return null !== $this->state_for( $value );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input.
	 */
	public function format( string $value ): string {
		$cep = $this->normalize( $value );

		if ( self::LENGTH !== strlen( $cep ) ) {
			return $value;
		}

		return substr( $cep, 0, 5 ) . '-' . substr( $cep, 5, 3 );
	}

	/**
	 * Returns the UF a CEP belongs to.
	 *
	 * @param string $value Raw user input.
	 * @return string|null Two-letter UF, or null when the CEP is malformed or unallocated.
	 */
	public function state_for( string $value ): ?string {
		$cep = $this->normalize( $value );

		if ( self::LENGTH !== strlen( $cep ) ) {
			return null;
		}

		$prefix = (int) substr( $cep, 0, 5 );

		foreach ( self::STATE_RANGES as $state => $ranges ) {
			foreach ( $ranges as $range ) {
				if ( $prefix >= $range[0] && $prefix <= $range[1] ) {
					return $state;
				}
			}
		}

		return null;
	}

	/**
	 * Tells whether a CEP is consistent with the selected state.
	 *
	 * Returns true when the state is unknown to the lookup table, so a missing
	 * mapping never blocks a legitimate order.
	 *
	 * @param string $cep   Raw CEP.
	 * @param string $state Two-letter UF.
	 * @return bool
	 */
	public function matches_state( string $cep, string $state ): bool {
		$state = strtoupper( trim( $state ) );

		if ( ! isset( self::STATE_RANGES[ $state ] ) ) {
			return true;
		}

		return $this->state_for( $cep ) === $state;
	}
}
