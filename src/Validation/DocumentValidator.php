<?php
/**
 * Facade that validates "CPF or CNPJ" inputs.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Validation;

/**
 * Detects whether a single free-text value is a CPF or a CNPJ and delegates
 * to the right validator. Used where the UI offers one combined field (for
 * example the Cart/Checkout Blocks integration).
 */
final class DocumentValidator implements Validator {

	public const TYPE_CPF  = 'cpf';
	public const TYPE_CNPJ = 'cnpj';

	/**
	 * CPF validator.
	 *
	 * @var CpfValidator
	 */
	private $cpf;

	/**
	 * CNPJ validator.
	 *
	 * @var CnpjValidator
	 */
	private $cnpj;

	/**
	 * Constructor.
	 *
	 * @param CpfValidator|null  $cpf  CPF validator.
	 * @param CnpjValidator|null $cnpj CNPJ validator.
	 */
	public function __construct( ?CpfValidator $cpf = null, ?CnpjValidator $cnpj = null ) {
		$this->cpf  = $cpf ?? new CpfValidator();
		$this->cnpj = $cnpj ?? new CnpjValidator();
	}

	/**
	 * Detects the document type from its normalized length.
	 *
	 * @param string $value Raw user input.
	 * @return string|null One of the TYPE_* constants, or null when unknown.
	 */
	public function detect_type( string $value ): ?string {
		$normalized = $this->cnpj->normalize( $value );

		if ( CpfValidator::LENGTH === strlen( $normalized ) && ctype_digit( $normalized ) ) {
			return self::TYPE_CPF;
		}

		if ( CnpjValidator::LENGTH === strlen( $normalized ) ) {
			return self::TYPE_CNPJ;
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input.
	 */
	public function normalize( string $value ): string {
		return $this->cnpj->normalize( $value );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input, masked or not.
	 */
	public function is_valid( string $value ): bool {
		$validator = $this->validator_for( $value );

		return null !== $validator && $validator->is_valid( $value );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value Raw user input.
	 */
	public function format( string $value ): string {
		$validator = $this->validator_for( $value );

		return null === $validator ? $value : $validator->format( $value );
	}

	/**
	 * Picks the validator matching the detected type.
	 *
	 * @param string $value Raw user input.
	 * @return Validator|null
	 */
	private function validator_for( string $value ): ?Validator {
		switch ( $this->detect_type( $value ) ) {
			case self::TYPE_CPF:
				return $this->cpf;
			case self::TYPE_CNPJ:
				return $this->cnpj;
			default:
				return null;
		}
	}
}
