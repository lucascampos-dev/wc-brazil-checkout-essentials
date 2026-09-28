<?php
/**
 * WooCommerce → Settings → Brazil Checkout tab.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Admin;

use BrazilCheckoutEssentials\Settings;
use BrazilCheckoutEssentials\Support\AmountParser;
use BrazilCheckoutEssentials\Support\BrazilianStates;
use WC_Admin_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings tab built entirely on the WooCommerce Settings API, so nonce
 * verification (`woocommerce-settings`), rendering and persistence follow
 * core conventions. A capability check is added on top as defense in depth.
 */
final class SettingsTab {

	public const TAB_ID = 'wcbce';

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_tab' ], 50 );
		add_action( 'woocommerce_settings_tabs_' . self::TAB_ID, [ $this, 'output' ] );
		add_action( 'woocommerce_update_options_' . self::TAB_ID, [ $this, 'save' ] );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . Settings::STATE_MINIMUMS, [ $this, 'sanitize_amount' ], 10, 3 );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), [ $this, 'action_links' ] );
	}

	/**
	 * Adds the tab label.
	 *
	 * @param array<string, string> $tabs Existing tabs.
	 * @return array<string, string>
	 */
	public function add_tab( array $tabs ): array {
		$tabs[ self::TAB_ID ] = __( 'Brazil Checkout', 'wc-brazil-checkout-essentials' );

		return $tabs;
	}

	/**
	 * Renders the fields.
	 *
	 * @return void
	 */
	public function output(): void {
		WC_Admin_Settings::output_fields( $this->fields() );
	}

	/**
	 * Persists the fields. Core already verified the nonce at this point.
	 *
	 * @return void
	 */
	public function save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		WC_Admin_Settings::save_fields( $this->fields() );
	}

	/**
	 * Normalizes each per-state amount to a plain decimal string.
	 *
	 * @param mixed                $value     Value after core sanitization.
	 * @param array<string, mixed> $option    Field definition.
	 * @param mixed                $raw_value Raw posted value.
	 * @return string Empty string when no minimum applies.
	 */
	public function sanitize_amount( $value, $option, $raw_value ): string {
		unset( $value, $option );

		$amount = AmountParser::parse( is_scalar( $raw_value ) ? (string) $raw_value : '' );

		return $amount > 0 ? wc_format_decimal( $amount, 2 ) : '';
	}

	/**
	 * Adds a "Settings" shortcut on the Plugins screen.
	 *
	 * @param array<int|string, string> $links Existing links.
	 * @return array<int|string, string>
	 */
	public function action_links( array $links ): array {
		$url = admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB_ID );

		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'wc-brazil-checkout-essentials' ) )
		);

		return $links;
	}

	/**
	 * Field definitions for the WC Settings API.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function fields(): array {
		$defaults = Settings::defaults();

		$fields = [
			[
				'id'    => 'wcbce_general',
				'type'  => 'title',
				'title' => __( 'Checkout fields', 'wc-brazil-checkout-essentials' ),
				'desc'  => __( 'Brazilian taxpayer ID, postcode and phone handling for the checkout.', 'wc-brazil-checkout-essentials' ),
			],
			[
				'id'      => Settings::ENABLE_DOCUMENT,
				'type'    => 'checkbox',
				'title'   => __( 'CPF / CNPJ', 'wc-brazil-checkout-essentials' ),
				'desc'    => __( 'Add a person type selector and CPF / CNPJ fields with check-digit validation.', 'wc-brazil-checkout-essentials' ),
				'default' => $defaults[ Settings::ENABLE_DOCUMENT ],
			],
			[
				'id'      => Settings::ENABLE_ADDRESS_CHECKS,
				'type'    => 'checkbox',
				'title'   => __( 'CEP and phone', 'wc-brazil-checkout-essentials' ),
				'desc'    => __( 'Validate and format Brazilian postcodes (CEP) and phone numbers.', 'wc-brazil-checkout-essentials' ),
				'default' => $defaults[ Settings::ENABLE_ADDRESS_CHECKS ],
			],
			[
				'id'       => Settings::ENABLE_CEP_STATE,
				'type'     => 'checkbox',
				'title'    => __( 'CEP matches state', 'wc-brazil-checkout-essentials' ),
				'desc'     => __( 'Reject orders whose CEP does not belong to the selected state.', 'wc-brazil-checkout-essentials' ),
				'desc_tip' => __( 'Uses the national CEP ranges per state. Requires "CEP and phone" to be enabled.', 'wc-brazil-checkout-essentials' ),
				'default'  => $defaults[ Settings::ENABLE_CEP_STATE ],
			],
			[
				'id'   => 'wcbce_general',
				'type' => 'sectionend',
			],
			[
				'id'    => 'wcbce_minimums',
				'type'  => 'title',
				'title' => __( 'Minimum order amount per state', 'wc-brazil-checkout-essentials' ),
				'desc'  => __( 'Leave a state empty (or 0) for no minimum. The amount is compared with the cart subtotal after discounts, excluding shipping.', 'wc-brazil-checkout-essentials' ),
			],
			[
				'id'      => Settings::ENABLE_MINIMUMS,
				'type'    => 'checkbox',
				'title'   => __( 'Enable', 'wc-brazil-checkout-essentials' ),
				'desc'    => __( 'Block checkout when the order is below the minimum for the destination state.', 'wc-brazil-checkout-essentials' ),
				'default' => $defaults[ Settings::ENABLE_MINIMUMS ],
			],
			[
				'id'      => Settings::MINIMUM_STATE_SOURCE,
				'type'    => 'select',
				'title'   => __( 'State taken from', 'wc-brazil-checkout-essentials' ),
				'default' => $defaults[ Settings::MINIMUM_STATE_SOURCE ],
				'options' => [
					Settings::SOURCE_SHIPPING => __( 'Shipping address (falls back to billing)', 'wc-brazil-checkout-essentials' ),
					Settings::SOURCE_BILLING  => __( 'Billing address', 'wc-brazil-checkout-essentials' ),
				],
			],
		];

		foreach ( BrazilianStates::ALL as $code => $name ) {
			$fields[] = [
				'id'                => Settings::STATE_MINIMUMS . '[' . $code . ']',
				'type'              => 'text',
				'css'               => 'width: 8em;',
				'title'             => sprintf( '%s (%s)', $name, $code ),
				'placeholder'       => '0,00',
				'custom_attributes' => [ 'inputmode' => 'decimal' ],
			];
		}

		$fields[] = [
			'id'   => 'wcbce_minimums',
			'type' => 'sectionend',
		];

		/**
		 * Filters the settings fields of the "Brazil Checkout" tab.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, array<string, mixed>> $fields WC Settings API field definitions.
		 */
		return (array) apply_filters( 'wcbce_settings_fields', $fields );
	}
}
