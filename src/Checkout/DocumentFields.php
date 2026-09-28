<?php
/**
 * Person type + CPF/CNPJ fields for the classic (shortcode) checkout.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Checkout;

use BrazilCheckoutEssentials\Validation\CnpjValidator;
use BrazilCheckoutEssentials\Validation\CpfValidator;
use BrazilCheckoutEssentials\Validation\Validator;
use WC_Order;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the fields, validates them server-side, stores them on the order and
 * shows them in the admin order screen and in emails.
 *
 * Meta keys (`_billing_persontype`, `_billing_cpf`, `_billing_cnpj`) follow
 * the naming convention most Brazilian WooCommerce integrations (invoicing,
 * payment gateways) already read, so data flows to them without mapping.
 *
 * Security notes:
 *  - Posted data is read from WooCommerce's already-sanitized `$data` array
 *    after core verified the `woocommerce-process-checkout-nonce`.
 *  - Admin editing reuses core's order meta box, which enforces its own nonce
 *    and the `edit_shop_orders` capability.
 */
final class DocumentFields {

	public const PERSON_INDIVIDUAL = '1';
	public const PERSON_COMPANY    = '2';

	public const META_PERSON_TYPE = '_billing_persontype';
	public const META_CPF         = '_billing_cpf';
	public const META_CNPJ        = '_billing_cnpj';

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
	 * @param CpfValidator  $cpf  CPF validator.
	 * @param CnpjValidator $cnpj CNPJ validator.
	 */
	public function __construct( CpfValidator $cpf, CnpjValidator $cnpj ) {
		$this->cpf  = $cpf;
		$this->cnpj = $cnpj;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_checkout_fields', [ $this, 'add_checkout_fields' ] );
		add_filter( 'woocommerce_checkout_posted_data', [ $this, 'format_posted_data' ] );
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate' ], 10, 2 );
		add_action( 'woocommerce_checkout_create_order', [ $this, 'save_to_order' ], 10, 2 );
		add_filter( 'woocommerce_admin_billing_fields', [ $this, 'add_admin_fields' ] );
		add_filter( 'woocommerce_email_order_meta_fields', [ $this, 'add_email_fields' ], 10, 3 );
	}

	/**
	 * Person type labels.
	 *
	 * @return array<string, string>
	 */
	public static function person_types(): array {
		return [
			self::PERSON_INDIVIDUAL => __( 'Individual (Pessoa Física)', 'wc-brazil-checkout-essentials' ),
			self::PERSON_COMPANY    => __( 'Company (Pessoa Jurídica)', 'wc-brazil-checkout-essentials' ),
		];
	}

	/**
	 * Adds the fields to the billing section.
	 *
	 * CPF and CNPJ are declared optional for WooCommerce because only one of
	 * them applies; the conditional requirement is enforced in validate().
	 *
	 * @param array<string, array<string, mixed>> $fields Checkout fields.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_checkout_fields( array $fields ): array {
		$required_marker = ' <abbr class="required" title="' . esc_attr__( 'required', 'wc-brazil-checkout-essentials' ) . '">*</abbr>';

		$document_fields = [
			'billing_persontype' => [
				'type'     => 'select',
				'label'    => __( 'Person type', 'wc-brazil-checkout-essentials' ),
				'required' => true,
				'class'    => [ 'form-row-wide', 'wcbce-persontype' ],
				'options'  => self::person_types(),
				'default'  => self::PERSON_INDIVIDUAL,
				'priority' => 25,
			],
			'billing_cpf'        => [
				'type'              => 'text',
				'label'             => __( 'CPF', 'wc-brazil-checkout-essentials' ) . $required_marker,
				'required'          => false,
				'class'             => [ 'form-row-wide', 'wcbce-conditional', 'wcbce-cpf' ],
				'placeholder'       => '000.000.000-00',
				'priority'          => 26,
				'custom_attributes' => [
					'inputmode'       => 'numeric',
					'maxlength'       => '14',
					'autocomplete'    => 'off',
					'data-wcbce-mask' => 'cpf',
				],
			],
			'billing_cnpj'       => [
				'type'              => 'text',
				'label'             => __( 'CNPJ', 'wc-brazil-checkout-essentials' ) . $required_marker,
				'required'          => false,
				'class'             => [ 'form-row-wide', 'wcbce-conditional', 'wcbce-cnpj' ],
				'placeholder'       => '00.000.000/0000-00',
				'priority'          => 27,
				'custom_attributes' => [
					'maxlength'       => '18',
					'autocomplete'    => 'off',
					'autocapitalize'  => 'characters',
					'data-wcbce-mask' => 'cnpj',
				],
			],
		];

		/**
		 * Filters the person type / CPF / CNPJ checkout field definitions
		 * before they are merged into the billing section.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, mixed>> $document_fields Field definitions keyed by field name.
		 */
		$document_fields = (array) apply_filters( 'wcbce_checkout_document_fields', $document_fields );

		$fields['billing'] = array_merge( $fields['billing'] ?? [], $document_fields );

		return $fields;
	}

	/**
	 * Stores documents in their canonical masked format.
	 *
	 * @param array<string, mixed> $data Posted checkout data.
	 * @return array<string, mixed>
	 */
	public function format_posted_data( array $data ): array {
		if ( ! empty( $data['billing_cpf'] ) ) {
			$data['billing_cpf'] = $this->cpf->format( (string) $data['billing_cpf'] );
		}

		if ( ! empty( $data['billing_cnpj'] ) ) {
			$data['billing_cnpj'] = $this->cnpj->format( (string) $data['billing_cnpj'] );
		}

		return $data;
	}

	/**
	 * Server-side validation. Runs for Brazilian billing addresses only.
	 *
	 * @param array<string, mixed> $data   Posted checkout data (sanitized by core).
	 * @param WP_Error             $errors Error collector.
	 * @return void
	 */
	public function validate( array $data, WP_Error $errors ): void {
		if ( 'BR' !== ( $data['billing_country'] ?? '' ) ) {
			return;
		}

		if ( self::PERSON_COMPANY === $this->person_type( $data ) ) {
			$this->validate_document( (string) ( $data['billing_cnpj'] ?? '' ), $this->cnpj, 'billing_cnpj', __( 'CNPJ', 'wc-brazil-checkout-essentials' ), $errors );

			/**
			 * Whether the company name is mandatory for legal entities (CNPJ).
			 *
			 * @since 1.0.0
			 *
			 * @param bool                 $required Default true.
			 * @param array<string, mixed> $data     Posted checkout data.
			 */
			$company_required = (bool) apply_filters( 'wcbce_require_company_for_legal_entity', true, $data );

			if ( $company_required && '' === trim( (string) ( $data['billing_company'] ?? '' ) ) ) {
				$errors->add(
					'validation',
					sprintf(
						/* translators: %s: field label wrapped in <strong>. */
						__( '%s is required for companies.', 'wc-brazil-checkout-essentials' ),
						'<strong>' . esc_html__( 'Company name', 'wc-brazil-checkout-essentials' ) . '</strong>'
					),
					[ 'id' => 'billing_company' ]
				);
			}

			return;
		}

		$this->validate_document( (string) ( $data['billing_cpf'] ?? '' ), $this->cpf, 'billing_cpf', __( 'CPF', 'wc-brazil-checkout-essentials' ), $errors );
	}

	/**
	 * Persists only the document that matches the person type.
	 *
	 * WooCommerce already copied the posted `billing_*` values to order meta
	 * before this hook; here we drop the one that does not apply so orders
	 * never carry a stale CPF and CNPJ at the same time.
	 *
	 * @param WC_Order             $order Order being created.
	 * @param array<string, mixed> $data  Posted checkout data.
	 * @return void
	 */
	public function save_to_order( WC_Order $order, array $data ): void {
		if ( 'BR' !== ( $data['billing_country'] ?? '' ) ) {
			$order->delete_meta_data( self::META_PERSON_TYPE );
			$order->delete_meta_data( self::META_CPF );
			$order->delete_meta_data( self::META_CNPJ );
			return;
		}

		$type = $this->person_type( $data );
		$order->update_meta_data( self::META_PERSON_TYPE, $type );

		if ( self::PERSON_COMPANY === $type ) {
			$order->update_meta_data( self::META_CNPJ, (string) ( $data['billing_cnpj'] ?? '' ) );
			$order->delete_meta_data( self::META_CPF );
		} else {
			$order->update_meta_data( self::META_CPF, (string) ( $data['billing_cpf'] ?? '' ) );
			$order->delete_meta_data( self::META_CNPJ );
		}
	}

	/**
	 * Shows (and allows editing of) the fields in the admin order screen.
	 *
	 * Core renders fields flagged `show` in the billing address column and
	 * saves them through `$order->update_meta_data()` — HPOS-safe.
	 *
	 * @param array<string, array<string, mixed>> $fields Admin billing fields.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_admin_fields( array $fields ): array {
		$fields['persontype'] = [
			'label'   => __( 'Person type', 'wc-brazil-checkout-essentials' ),
			'type'    => 'select',
			'options' => [ '' => '—' ] + self::person_types(),
			'show'    => false,
		];
		$fields['cpf']        = [
			'label' => __( 'CPF', 'wc-brazil-checkout-essentials' ),
			'show'  => true,
		];
		$fields['cnpj']       = [
			'label' => __( 'CNPJ', 'wc-brazil-checkout-essentials' ),
			'show'  => true,
		];

		return $fields;
	}

	/**
	 * Adds the document to order emails.
	 *
	 * @param array<string, array<string, string>> $fields        Existing fields.
	 * @param bool                                 $sent_to_admin Whether the email goes to the store admin.
	 * @param mixed                                $order         Order object.
	 * @return array<string, array<string, string>>
	 */
	public function add_email_fields( array $fields, $sent_to_admin, $order ): array {
		unset( $sent_to_admin );

		if ( ! $order instanceof WC_Order ) {
			return $fields;
		}

		$cpf  = (string) $order->get_meta( self::META_CPF );
		$cnpj = (string) $order->get_meta( self::META_CNPJ );

		if ( '' !== $cpf ) {
			$fields['wcbce_cpf'] = [
				'label' => __( 'CPF', 'wc-brazil-checkout-essentials' ),
				'value' => esc_html( $cpf ),
			];
		}

		if ( '' !== $cnpj ) {
			$fields['wcbce_cnpj'] = [
				'label' => __( 'CNPJ', 'wc-brazil-checkout-essentials' ),
				'value' => esc_html( $cnpj ),
			];
		}

		return $fields;
	}

	/**
	 * Resolves the person type, defaulting to individual.
	 *
	 * @param array<string, mixed> $data Posted checkout data.
	 * @return string
	 */
	private function person_type( array $data ): string {
		return self::PERSON_COMPANY === (string) ( $data['billing_persontype'] ?? '' )
			? self::PERSON_COMPANY
			: self::PERSON_INDIVIDUAL;
	}

	/**
	 * Adds an error when the document is missing or invalid.
	 *
	 * @param string    $value     Posted value.
	 * @param Validator $validator Validator to use.
	 * @param string    $field_id  Field id (for inline error focus).
	 * @param string    $label     Human label.
	 * @param WP_Error  $errors    Error collector.
	 * @return void
	 */
	private function validate_document( string $value, Validator $validator, string $field_id, string $label, WP_Error $errors ): void {
		$strong_label = '<strong>' . esc_html( $label ) . '</strong>';

		if ( '' === trim( $value ) ) {
			$errors->add(
				'validation',
				/* translators: %s: field label wrapped in <strong>. */
				sprintf( __( '%s is a required field.', 'wc-brazil-checkout-essentials' ), $strong_label ),
				[ 'id' => $field_id ]
			);
			return;
		}

		/**
		 * Filters the result of the CPF/CNPJ check-digit validation.
		 *
		 * Useful to accept documents from a sandbox environment, or to add an
		 * extra check such as a lookup against an external registry.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $is_valid Result of the built-in validation.
		 * @param string $value    Posted value (masked).
		 * @param string $type     'cpf' or 'cnpj'.
		 */
		$is_valid = (bool) apply_filters(
			'wcbce_document_is_valid',
			$validator->is_valid( $value ),
			$value,
			'billing_cnpj' === $field_id ? 'cnpj' : 'cpf'
		);

		if ( ! $is_valid ) {
			$errors->add(
				'validation',
				/* translators: %s: field label wrapped in <strong>. */
				sprintf( __( '%s is not valid. Please check the number and try again.', 'wc-brazil-checkout-essentials' ), $strong_label ),
				[ 'id' => $field_id ]
			);
		}
	}
}
