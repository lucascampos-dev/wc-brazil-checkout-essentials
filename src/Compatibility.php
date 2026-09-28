<?php
/**
 * WooCommerce feature compatibility declarations.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Declares support for High-Performance Order Storage and Cart/Checkout Blocks.
 *
 * HPOS: all order reads/writes go through WC_Order CRUD methods
 * ($order->get_meta() / update_meta_data()), never through post meta.
 *
 * Blocks: the per-state minimum is enforced through the Store API and a
 * combined CPF/CNPJ field is registered with the Additional Checkout Fields
 * API. See README "Cart & Checkout Blocks" for the documented limitations.
 */
final class Compatibility {

	/**
	 * Hooked on `before_woocommerce_init`.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @return void
	 */
	public static function declare_features( string $plugin_file ): void {
		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}

		FeaturesUtil::declare_compatibility( 'custom_order_tables', $plugin_file, true );
		FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', $plugin_file, true );
	}
}
