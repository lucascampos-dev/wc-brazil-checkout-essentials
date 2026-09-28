<?php
/**
 * Uninstall routine: removes plugin options.
 *
 * Order data (CPF/CNPJ stored on orders) is intentionally kept: it is part
 * of the store's fiscal records and may be required by law or by invoicing
 * integrations long after this plugin is removed.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes every option the plugin created on the current site.
 *
 * Kept in sync with BrazilCheckoutEssentials\Settings::defaults(); the list
 * is duplicated on purpose so uninstall does not depend on the autoloader.
 *
 * @return void
 */
function wcbce_uninstall_site(): void {
	$options = [
		'wcbce_enable_document',
		'wcbce_enable_address_validation',
		'wcbce_enable_cep_state_check',
		'wcbce_enable_state_minimums',
		'wcbce_minimum_state_source',
		'wcbce_state_minimums',
	];

	foreach ( $options as $option ) {
		delete_option( $option );
	}
}

if ( is_multisite() ) {
	$wcbce_site_ids = get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	);

	foreach ( $wcbce_site_ids as $wcbce_site_id ) {
		switch_to_blog( (int) $wcbce_site_id );
		wcbce_uninstall_site();
		restore_current_blog();
	}
} else {
	wcbce_uninstall_site();
}
