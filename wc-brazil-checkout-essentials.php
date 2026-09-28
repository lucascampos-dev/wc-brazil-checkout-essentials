<?php
/**
 * Plugin Name:          Brazil Checkout Essentials for WooCommerce
 * Plugin URI:           https://github.com/lucasthobias/wc-brazil-checkout-essentials
 * Description:          CPF/CNPJ billing field with check-digit validation, Brazilian CEP and phone validation, and minimum order amounts per state (UF).
 * Version:              1.0.0
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Lucas Campos
 * Author URI:           https://github.com/lucasthobias
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          wc-brazil-checkout-essentials
 * Domain Path:          /languages
 * WC requires at least: 8.9
 * WC tested up to:      10.2
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'WCBCE_VERSION', '1.0.0' );
define( 'WCBCE_PLUGIN_FILE', __FILE__ );

/*
 * Autoloading: Composer when available (development / CI), otherwise a tiny
 * PSR-4 fallback so the plugin also works from a plain ZIP without vendor/.
 */
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = 'BrazilCheckoutEssentials\\';

			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}

			$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';

			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

add_action(
	'before_woocommerce_init',
	static function (): void {
		\BrazilCheckoutEssentials\Compatibility::declare_features( WCBCE_PLUGIN_FILE );
	}
);

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'wc-brazil-checkout-essentials', false, dirname( plugin_basename( WCBCE_PLUGIN_FILE ) ) . '/languages' );
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}

					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'Brazil Checkout Essentials for WooCommerce requires WooCommerce to be installed and active.', 'wc-brazil-checkout-essentials' )
					);
				}
			);
			return;
		}

		( new \BrazilCheckoutEssentials\Plugin( WCBCE_PLUGIN_FILE ) )->boot();
	}
);
