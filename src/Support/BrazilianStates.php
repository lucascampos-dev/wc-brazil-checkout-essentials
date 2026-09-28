<?php
/**
 * Brazilian federative units (UF).
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Support;

/**
 * Immutable list of the 27 UFs, keyed by the same codes WooCommerce uses
 * for the "BR" country states.
 */
final class BrazilianStates {

	public const ALL = [
		'AC' => 'Acre',
		'AL' => 'Alagoas',
		'AP' => 'Amapá',
		'AM' => 'Amazonas',
		'BA' => 'Bahia',
		'CE' => 'Ceará',
		'DF' => 'Distrito Federal',
		'ES' => 'Espírito Santo',
		'GO' => 'Goiás',
		'MA' => 'Maranhão',
		'MT' => 'Mato Grosso',
		'MS' => 'Mato Grosso do Sul',
		'MG' => 'Minas Gerais',
		'PA' => 'Pará',
		'PB' => 'Paraíba',
		'PR' => 'Paraná',
		'PE' => 'Pernambuco',
		'PI' => 'Piauí',
		'RJ' => 'Rio de Janeiro',
		'RN' => 'Rio Grande do Norte',
		'RS' => 'Rio Grande do Sul',
		'RO' => 'Rondônia',
		'RR' => 'Roraima',
		'SC' => 'Santa Catarina',
		'SP' => 'São Paulo',
		'SE' => 'Sergipe',
		'TO' => 'Tocantins',
	];

	/**
	 * Tells whether the code is a valid UF (case-insensitive).
	 *
	 * @param string $code Two-letter code.
	 * @return bool
	 */
	public static function is_valid( string $code ): bool {
		return isset( self::ALL[ strtoupper( trim( $code ) ) ] );
	}

	/**
	 * Returns the state name, or the code itself when unknown.
	 *
	 * @param string $code Two-letter code.
	 * @return string
	 */
	public static function name( string $code ): string {
		$code = strtoupper( trim( $code ) );

		return self::ALL[ $code ] ?? $code;
	}
}
