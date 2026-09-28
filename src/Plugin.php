<?php
/**
 * Composition root.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials;

use BrazilCheckoutEssentials\Admin\SettingsTab;
use BrazilCheckoutEssentials\Blocks\DocumentField as BlocksDocumentField;
use BrazilCheckoutEssentials\Checkout\AddressValidation;
use BrazilCheckoutEssentials\Checkout\Assets;
use BrazilCheckoutEssentials\Checkout\DocumentFields;
use BrazilCheckoutEssentials\Checkout\StateMinimumEnforcer;
use BrazilCheckoutEssentials\Validation\CepValidator;
use BrazilCheckoutEssentials\Validation\CnpjValidator;
use BrazilCheckoutEssentials\Validation\CpfValidator;
use BrazilCheckoutEssentials\Validation\DocumentValidator;
use BrazilCheckoutEssentials\Validation\PhoneValidator;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the object graph once and registers every hook.
 *
 * No global singletons besides this entry point; each component receives
 * its dependencies through the constructor, which keeps them easy to test.
 */
final class Plugin {

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Guards against double boot.
	 *
	 * @var bool
	 */
	private static $booted = false;

	/**
	 * Constructor.
	 *
	 * @param string $file Absolute path to the main plugin file.
	 */
	public function __construct( string $file ) {
		$this->file = $file;
	}

	/**
	 * Wires components to WordPress/WooCommerce hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		$settings = new Settings();
		$cpf      = new CpfValidator();
		$cnpj     = new CnpjValidator();

		if ( is_admin() ) {
			( new SettingsTab( $this->file ) )->register();
		}

		if ( $settings->is_enabled( Settings::ENABLE_DOCUMENT ) ) {
			( new DocumentFields( $cpf, $cnpj ) )->register();
			( new BlocksDocumentField( new DocumentValidator( $cpf, $cnpj ) ) )->register();
		}

		if ( $settings->is_enabled( Settings::ENABLE_ADDRESS_CHECKS ) ) {
			( new AddressValidation( new CepValidator(), new PhoneValidator(), $settings ) )->register();
		}

		if ( $settings->is_enabled( Settings::ENABLE_MINIMUMS ) ) {
			( new StateMinimumEnforcer( $settings ) )->register();
		}

		( new Assets( $this->file, $settings ) )->register();
	}
}
