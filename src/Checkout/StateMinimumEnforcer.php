<?php
/**
 * Enforces the minimum order amount per state.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Checkout;

use BrazilCheckoutEssentials\Rules\MinimumCheckResult;
use BrazilCheckoutEssentials\Settings;
use BrazilCheckoutEssentials\Support\BrazilianStates;
use WC_Cart;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Glue between StateMinimumRule and WooCommerce, covering:
 *  - Cart page (classic):          notice via `woocommerce_check_cart_items`.
 *  - Checkout page (classic):      live notice above "Place order", refreshed
 *                                  on every `update_order_review` AJAX call.
 *  - Checkout submit (classic):    hard block in `woocommerce_after_checkout_validation`
 *                                  using the address actually posted.
 *  - Cart/Checkout Blocks:         hard block via `woocommerce_store_api_cart_errors`.
 */
final class StateMinimumEnforcer {

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_check_cart_items', [ $this, 'check_cart' ] );
		add_action( 'woocommerce_review_order_before_submit', [ $this, 'render_checkout_notice' ] );
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_checkout' ], 20, 2 );
		add_action( 'woocommerce_store_api_cart_errors', [ $this, 'add_store_api_error' ], 10, 2 );
	}

	/**
	 * Cart page notice (classic cart).
	 *
	 * Skipped on the checkout page (render_checkout_notice() shows a live notice
	 * there) and while a checkout is being processed (validate_checkout() uses
	 * the posted address instead of the session one).
	 *
	 * @return void
	 */
	public function check_cart(): void {
		if ( is_checkout() || did_action( 'woocommerce_checkout_process' ) || null === WC()->cart ) {
			return;
		}

		$result = $this->evaluate( WC()->cart, $this->state_from_session() );

		if ( null !== $result && ! $result->is_satisfied() ) {
			wc_add_notice( $this->message( $result ), 'error' );
		}
	}

	/**
	 * Inline notice inside the checkout review fragment.
	 *
	 * @return void
	 */
	public function render_checkout_notice(): void {
		if ( null === WC()->cart ) {
			return;
		}

		$result = $this->evaluate( WC()->cart, $this->state_from_session() );

		if ( null !== $result && ! $result->is_satisfied() ) {
			echo '<div class="wcbce-minimum-notice">';
			wc_print_notice( $this->message( $result ), 'error' );
			echo '</div>';
		}
	}

	/**
	 * Blocks the classic checkout submission.
	 *
	 * @param array<string, mixed> $data   Posted checkout data.
	 * @param WP_Error             $errors Error collector.
	 * @return void
	 */
	public function validate_checkout( array $data, WP_Error $errors ): void {
		if ( null === WC()->cart ) {
			return;
		}

		$result = $this->evaluate( WC()->cart, $this->state_from_posted( $data ) );

		if ( null !== $result && ! $result->is_satisfied() ) {
			$errors->add( 'wcbce_minimum_not_met', $this->message( $result ) );
			$this->notify_not_met( $result );
		}
	}

	/**
	 * Blocks the Store API (Cart/Checkout Blocks) checkout.
	 *
	 * @param WP_Error $errors Error collector.
	 * @param mixed    $cart   Cart instance.
	 * @return void
	 */
	public function add_store_api_error( $errors, $cart ): void {
		if ( ! $errors instanceof WP_Error || ! $cart instanceof WC_Cart ) {
			return;
		}

		$result = $this->evaluate( $cart, $this->state_from_session() );

		if ( null !== $result && ! $result->is_satisfied() ) {
			// Store API messages are rendered as plain text.
			$errors->add( 'wcbce_minimum_not_met', html_entity_decode( wp_strip_all_tags( $this->message( $result ) ), ENT_QUOTES, 'UTF-8' ) );
			$this->notify_not_met( $result );
		}
	}

	/**
	 * Runs the rule for a cart and state.
	 *
	 * @param WC_Cart     $cart  Cart.
	 * @param string|null $state UF, or null when unknown / not Brazil.
	 * @return MinimumCheckResult|null Null when no rule applies.
	 */
	private function evaluate( WC_Cart $cart, ?string $state ): ?MinimumCheckResult {
		if ( null === $state || $cart->is_empty() ) {
			return null;
		}

		$rule = $this->settings->state_minimum_rule();

		/**
		 * Filters the minimum order amount for a state.
		 *
		 * Return 0 to disable the minimum for this request (e.g. for
		 * wholesale customers or a specific coupon).
		 *
		 * @since 1.0.0
		 *
		 * @param float   $minimum Configured minimum (0.0 = none).
		 * @param string  $state   Two-letter UF.
		 * @param WC_Cart $cart    Current cart.
		 */
		$minimum = (float) apply_filters( 'wcbce_state_minimum_amount', $rule->minimum_for( $state ), $state, $cart );

		if ( $minimum <= 0 ) {
			return null;
		}

		/**
		 * Filters the amount compared against the state minimum.
		 *
		 * Defaults to the items subtotal minus discounts, excluding shipping
		 * and fees; taxes are included when the store displays prices with tax.
		 *
		 * @since 1.0.0
		 *
		 * @param float   $subtotal Amount to compare.
		 * @param WC_Cart $cart     Current cart.
		 */
		$subtotal = (float) apply_filters( 'wcbce_minimum_order_subtotal', $this->default_subtotal( $cart ), $cart );

		return $rule->evaluate( $state, $subtotal, $minimum );
	}

	/**
	 * Items subtotal minus discounts, tax-inclusive when prices are shown with tax.
	 *
	 * @param WC_Cart $cart Cart.
	 * @return float
	 */
	private function default_subtotal( WC_Cart $cart ): float {
		$subtotal = (float) $cart->get_subtotal() - (float) $cart->get_discount_total();

		if ( $cart->display_prices_including_tax() ) {
			$subtotal += (float) $cart->get_subtotal_tax() - (float) $cart->get_discount_tax();
		}

		return max( 0.0, $subtotal );
	}

	/**
	 * State from the customer session (cart page, blocks, order review).
	 *
	 * @return string|null
	 */
	private function state_from_session(): ?string {
		$customer = WC()->customer;

		if ( null === $customer ) {
			return null;
		}

		if ( Settings::SOURCE_SHIPPING === $this->settings->minimum_state_source() && '' !== $customer->get_shipping_state() ) {
			return $this->brazilian_state( $customer->get_shipping_country(), $customer->get_shipping_state() );
		}

		return $this->brazilian_state( $customer->get_billing_country(), $customer->get_billing_state() );
	}

	/**
	 * State from posted checkout data.
	 *
	 * @param array<string, mixed> $data Posted data.
	 * @return string|null
	 */
	private function state_from_posted( array $data ): ?string {
		$use_shipping = Settings::SOURCE_SHIPPING === $this->settings->minimum_state_source()
			&& ! empty( $data['ship_to_different_address'] )
			&& ! empty( $data['shipping_state'] );

		$prefix = $use_shipping ? 'shipping' : 'billing';

		return $this->brazilian_state( (string) ( $data[ $prefix . '_country' ] ?? '' ), (string) ( $data[ $prefix . '_state' ] ?? '' ) );
	}

	/**
	 * Returns the UF only for valid Brazilian addresses.
	 *
	 * @param string $country Country code.
	 * @param string $state   State code.
	 * @return string|null
	 */
	private function brazilian_state( string $country, string $state ): ?string {
		return 'BR' === $country && BrazilianStates::is_valid( $state ) ? strtoupper( $state ) : null;
	}

	/**
	 * Customer-facing message (HTML, via wc_price()).
	 *
	 * @param MinimumCheckResult $result Result.
	 * @return string
	 */
	private function message( MinimumCheckResult $result ): string {
		return sprintf(
			/* translators: 1: state name, 2: minimum amount, 3: amount missing. */
			__( 'The minimum order amount for delivery to %1$s is %2$s. Add %3$s more to your cart to continue.', 'wc-brazil-checkout-essentials' ),
			esc_html( BrazilianStates::name( $result->state() ) ),
			wc_price( $result->minimum() ),
			wc_price( $result->shortfall() )
		);
	}

	/**
	 * Fires the developer action.
	 *
	 * @param MinimumCheckResult $result Result.
	 * @return void
	 */
	private function notify_not_met( MinimumCheckResult $result ): void {
		/**
		 * Fires when a checkout attempt is blocked by the state minimum.
		 *
		 * @since 1.0.0
		 *
		 * @param MinimumCheckResult $result State, minimum, subtotal and shortfall.
		 */
		do_action( 'wcbce_minimum_order_not_met', $result );
	}
}
