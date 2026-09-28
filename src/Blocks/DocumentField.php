<?php
/**
 * CPF/CNPJ field for the Cart & Checkout Blocks.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Blocks;

use BrazilCheckoutEssentials\Validation\DocumentValidator;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a single "CPF or CNPJ" field through the WooCommerce Additional
 * Checkout Fields API (WooCommerce 8.9+). The document type is detected
 * from the number itself and validated server-side by the Store API.
 *
 * WooCommerce stores the value on the order (HPOS-safe) and shows it in the
 * admin order screen and emails on its own.
 *
 * Limitations compared with the classic checkout (see README):
 *  - no person type selector / conditional fields;
 *  - no client-side input mask (the value is formatted on save);
 *  - value lives under WooCommerce's own meta key, not `_billing_cpf`.
 */
final class DocumentField {

	public const FIELD_ID = 'wcbce/document';

	/**
	 * Combined validator.
	 *
	 * @var DocumentValidator
	 */
	private $validator;

	/**
	 * Constructor.
	 *
	 * @param DocumentValidator $validator Combined validator.
	 */
	public function __construct( DocumentValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_init', [ $this, 'register_field' ] );
	}

	/**
	 * Registers the field when the API is available.
	 *
	 * @return void
	 */
	public function register_field(): void {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		/**
		 * Whether the Blocks CPF/CNPJ field is mandatory.
		 *
		 * The Additional Checkout Fields API cannot (portably) make a field
		 * conditional on the billing country, so stores that also sell abroad
		 * should return false here; an empty value is then accepted.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $required Default true.
		 */
		$required = (bool) apply_filters( 'wcbce_blocks_document_required', true );

		woocommerce_register_additional_checkout_field(
			[
				'id'                => self::FIELD_ID,
				'label'             => __( 'CPF or CNPJ', 'wc-brazil-checkout-essentials' ),
				'location'          => 'contact',
				'type'              => 'text',
				'required'          => $required,
				'attributes'        => [
					'autocomplete' => 'off',
					'maxLength'    => 18,
				],
				'sanitize_callback' => [ $this, 'sanitize' ],
				'validate_callback' => [ $this, 'validate' ],
			]
		);
	}

	/**
	 * Formats the value with its canonical mask.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize( $value ): string {
		$value = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );

		return $this->validator->format( $value );
	}

	/**
	 * Validates the check digits.
	 *
	 * @param mixed $value Sanitized value.
	 * @return WP_Error|null
	 */
	public function validate( $value ): ?WP_Error {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		if ( '' === $value ) {
			return null; // Presence is enforced by the API's own "required" flag.
		}

		$type = (string) $this->validator->detect_type( $value );

		/** This filter is documented in src/Checkout/DocumentFields.php */
		$is_valid = (bool) apply_filters( 'wcbce_document_is_valid', $this->validator->is_valid( $value ), $value, $type );

		if ( $is_valid ) {
			return null;
		}

		return new WP_Error(
			'wcbce_invalid_document',
			__( 'Please enter a valid CPF (11 digits) or CNPJ (14 characters).', 'wc-brazil-checkout-essentials' )
		);
	}
}
