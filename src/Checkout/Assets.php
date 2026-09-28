<?php
/**
 * Front-end assets for the classic checkout.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Checkout;

use BrazilCheckoutEssentials\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the dependency-free mask script and a tiny stylesheet, only on
 * the checkout page (not on the order-received endpoint).
 */
final class Assets {

	public const HANDLE = 'wcbce-checkout';

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param string   $plugin_file Absolute path to the main plugin file.
	 * @param Settings $settings    Settings.
	 */
	public function __construct( string $plugin_file, Settings $settings ) {
		$this->plugin_file = $plugin_file;
		$this->settings    = $settings;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Enqueues assets when needed.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}

		$document = $this->settings->is_enabled( Settings::ENABLE_DOCUMENT );
		$address  = $this->settings->is_enabled( Settings::ENABLE_ADDRESS_CHECKS );

		if ( ! $document && ! $address ) {
			return;
		}

		$base_url = plugin_dir_url( $this->plugin_file );
		$version  = defined( 'WCBCE_VERSION' ) ? WCBCE_VERSION : false;

		wp_enqueue_style( self::HANDLE, $base_url . 'assets/css/checkout.css', [], $version );

		wp_enqueue_script(
			self::HANDLE,
			$base_url . 'assets/js/checkout.js',
			[],
			$version,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);

		wp_add_inline_script(
			self::HANDLE,
			'window.wcbceCheckout = ' . wp_json_encode(
				[
					'document'      => $document,
					'address'       => $address,
					'companyPerson' => DocumentFields::PERSON_COMPANY,
				]
			) . ';',
			'before'
		);
	}
}
