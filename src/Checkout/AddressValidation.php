<?php
/**
 * CEP and phone validation/formatting for Brazilian addresses.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Checkout;

use BrazilCheckoutEssentials\Settings;
use BrazilCheckoutEssentials\Support\BrazilianStates;
use BrazilCheckoutEssentials\Validation\CepValidator;
use BrazilCheckoutEssentials\Validation\PhoneValidator;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Extends core's postcode check with CEP/state consistency and replaces the
 * permissive phone check with Brazilian area-code aware rules. Only applies
 * when the address country is Brazil.
 */
final class AddressValidation {

	/**
	 * CEP validator.
	 *
	 * @var CepValidator
	 */
	private $cep;

	/**
	 * Phone validator.
	 *
	 * @var PhoneValidator
	 */
	private $phone;

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param CepValidator   $cep      CEP validator.
	 * @param PhoneValidator $phone    Phone validator.
	 * @param Settings       $settings Settings.
	 */
	public function __construct( CepValidator $cep, PhoneValidator $phone, Settings $settings ) {
		$this->cep      = $cep;
		$this->phone    = $phone;
		$this->settings = $settings;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_checkout_posted_data', [ $this, 'format_posted_data' ] );
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate' ], 10, 2 );
	}

	/**
	 * Normalizes CEP and phone to their canonical masks before saving.
	 *
	 * @param array<string, mixed> $data Posted checkout data.
	 * @return array<string, mixed>
	 */
	public function format_posted_data( array $data ): array {
		foreach ( [ 'billing', 'shipping' ] as $type ) {
			if ( 'BR' !== ( $data[ $type . '_country' ] ?? '' ) ) {
				continue;
			}

			if ( ! empty( $data[ $type . '_postcode' ] ) ) {
				$data[ $type . '_postcode' ] = $this->cep->format( (string) $data[ $type . '_postcode' ] );
			}

			if ( ! empty( $data[ $type . '_phone' ] ) ) {
				$data[ $type . '_phone' ] = $this->phone->format( (string) $data[ $type . '_phone' ] );
			}
		}

		return $data;
	}

	/**
	 * Server-side validation.
	 *
	 * @param array<string, mixed> $data   Posted checkout data (sanitized by core).
	 * @param WP_Error             $errors Error collector.
	 * @return void
	 */
	public function validate( array $data, WP_Error $errors ): void {
		$types = [ 'billing' ];

		if ( ! empty( $data['ship_to_different_address'] ) ) {
			$types[] = 'shipping';
		}

		foreach ( $types as $type ) {
			if ( 'BR' !== ( $data[ $type . '_country' ] ?? '' ) ) {
				continue;
			}

			$this->validate_postcode( $type, $data, $errors );
			$this->validate_phone( $type, $data, $errors );
		}
	}

	/**
	 * CEP format and, optionally, CEP ↔ state consistency.
	 *
	 * Core already rejects malformed BR postcodes, so we only add an error when
	 * core would accept the value (e.g. an unallocated range) to avoid
	 * duplicated messages.
	 *
	 * @param string               $type   'billing' or 'shipping'.
	 * @param array<string, mixed> $data   Posted data.
	 * @param WP_Error             $errors Error collector.
	 * @return void
	 */
	private function validate_postcode( string $type, array $data, WP_Error $errors ): void {
		$postcode = (string) ( $data[ $type . '_postcode' ] ?? '' );
		$state    = (string) ( $data[ $type . '_state' ] ?? '' );
		$field_id = $type . '_postcode';

		if ( '' === $postcode ) {
			return; // Required-field check belongs to core.
		}

		if ( 8 !== strlen( $this->cep->normalize( $postcode ) ) ) {
			return; // Core reports "not a valid postcode / ZIP".
		}

		if ( ! $this->cep->is_valid( $postcode ) ) {
			$errors->add(
				'validation',
				sprintf(
					/* translators: %s: CEP as typed by the customer. */
					__( 'The CEP %s does not exist. Please check your postcode.', 'wc-brazil-checkout-essentials' ),
					'<strong>' . esc_html( $postcode ) . '</strong>'
				),
				[ 'id' => $field_id ]
			);
			return;
		}

		if ( '' === $state || ! $this->settings->is_enabled( Settings::ENABLE_CEP_STATE ) ) {
			return;
		}

		if ( ! $this->cep->matches_state( $postcode, $state ) ) {
			$errors->add(
				'validation',
				sprintf(
					/* translators: 1: CEP, 2: state name selected, 3: state the CEP belongs to. */
					__( 'The CEP %1$s belongs to %3$s, but the selected state is %2$s.', 'wc-brazil-checkout-essentials' ),
					'<strong>' . esc_html( $postcode ) . '</strong>',
					esc_html( BrazilianStates::name( $state ) ),
					esc_html( BrazilianStates::name( (string) $this->cep->state_for( $postcode ) ) )
				),
				[ 'id' => $field_id ]
			);
		}
	}

	/**
	 * Phone format with valid DDD.
	 *
	 * @param string               $type   'billing' or 'shipping'.
	 * @param array<string, mixed> $data   Posted data.
	 * @param WP_Error             $errors Error collector.
	 * @return void
	 */
	private function validate_phone( string $type, array $data, WP_Error $errors ): void {
		$phone = (string) ( $data[ $type . '_phone' ] ?? '' );

		if ( '' === $phone || $this->phone->is_valid( $phone ) ) {
			return;
		}

		$errors->add(
			'validation',
			sprintf(
				/* translators: %s: field label wrapped in <strong>. */
				__( '%s must be a Brazilian number with area code, e.g. (11) 91234-5678.', 'wc-brazil-checkout-essentials' ),
				'<strong>' . esc_html__( 'Phone', 'wc-brazil-checkout-essentials' ) . '</strong>'
			),
			[ 'id' => $type . '_phone' ]
		);
	}
}
