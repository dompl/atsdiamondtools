<?php
/**
 * Exclude premium shipping methods from the free-shipping-coupon offset.
 *
 * The "Free Shipping Coupons Behavior for WooCommerce" plugin (running in its
 * "discount" mode) hooks `woocommerce_cart_calculate_fees` and, whenever a
 * free-shipping coupon such as `free24` is applied, adds a negative
 * "Free Shipping Discount" fee equal to the chosen shipping cost (incl. its
 * VAT). It does this for EVERY shipping method, with no exclusions — so premium
 * services such as "Special Delivery next working day before 1pm" (a Table Rate
 * method, rate id `table_rate:8`, UK zone) are effectively handed out for free.
 * We never want free shipping to apply to those premium methods.
 *
 * Rather than edit the third-party plugin (which is unversioned and would be
 * lost on update), this file swaps the plugin's offset handler for a gated
 * wrapper: it removes the plugin's `apply_coupon_as_discount` callback from
 * `woocommerce_cart_calculate_fees` and re-registers our own at the same
 * priority. Ours simply delegates to the plugin's original logic — UNLESS the
 * customer has selected an excluded (premium) method, in which case it returns
 * early so NO offset fee is added at all (neither the discount amount nor its
 * VAT line), and the customer pays the full method cost + VAT.
 *
 * Standard / economy methods (1st Class `table_rate:3`, 2nd Class
 * `table_rate:4`, Free Shipping `free_shipping:11`) are unaffected and continue
 * to receive free shipping exactly as before.
 *
 * Matching is done on the stable rate ID (`<method_id>:<instance_id>`), never
 * the human-readable method title, and the excluded list is filterable so
 * further premium methods can be added without touching this logic.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the list of shipping rate IDs that must NOT receive the free-shipping
 * coupon offset.
 *
 * Defaults to both premium "Special Delivery" services in the United Kingdom
 * zone:
 *   - table_rate:8  = Special Delivery next working day before 1pm
 *   - table_rate:12 = Special Delivery Saturday Guarantee
 *
 * Configurable two ways:
 *   1. Define an ATS_FREE_SHIPPING_EXCLUDED_METHODS array constant to override
 *      the defaults, or
 *   2. Filter `ats_free_shipping_excluded_methods` to add/remove entries.
 *
 * @return string[] Array of rate IDs, e.g. array( 'table_rate:8' ).
 */
function ats_free_shipping_excluded_methods() {
	$excluded = array( 'table_rate:8', 'table_rate:12' );

	if ( defined( 'ATS_FREE_SHIPPING_EXCLUDED_METHODS' ) && is_array( ATS_FREE_SHIPPING_EXCLUDED_METHODS ) ) {
		$excluded = ATS_FREE_SHIPPING_EXCLUDED_METHODS;
	}

	return (array) apply_filters( 'ats_free_shipping_excluded_methods', $excluded );
}

/**
 * Store / retrieve the live "Free Shipping Coupons Behavior" plugin instance.
 *
 * We capture the instance when we swap its hook so the gated wrapper can
 * delegate back to its original `apply_coupon_as_discount()` logic for eligible
 * methods (reusing the plugin's coupon detection, discount text, language and
 * taxable-fee handling rather than duplicating it).
 *
 * @param object|null $set Instance to store, when swapping the hook.
 * @return object|null The stored plugin instance, or null if not captured.
 */
function ats_free_shipping_plugin_instance( $set = null ) {
	static $instance = null;

	if ( null !== $set ) {
		$instance = $set;
	}

	return $instance;
}

/**
 * Replace the plugin's unconditional offset handler with our gated wrapper.
 *
 * Runs on `wp_loaded` — after all plugins have registered their hooks, but long
 * before the cart calculates its fees — so the plugin's `apply_coupon_as_discount`
 * callback is guaranteed to be present and can be swapped out cleanly.
 *
 * If the plugin is inactive, or is configured in a mode other than "discount"
 * (so it never registers this callback), nothing is found and we no-op,
 * leaving default behaviour untouched.
 *
 * @return void
 */
function ats_free_shipping_swap_offset_handler() {
	global $wp_filter;

	if ( ! class_exists( 'Woo_Coupon_Free_Shipping' ) || empty( $wp_filter['woocommerce_cart_calculate_fees'] ) ) {
		return;
	}

	foreach ( $wp_filter['woocommerce_cart_calculate_fees']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$fn = isset( $callback['function'] ) ? $callback['function'] : null;

			if (
				is_array( $fn )
				&& isset( $fn[0], $fn[1] )
				&& $fn[0] instanceof Woo_Coupon_Free_Shipping
				&& 'apply_coupon_as_discount' === $fn[1]
			) {
				ats_free_shipping_plugin_instance( $fn[0] );
				remove_action( 'woocommerce_cart_calculate_fees', array( $fn[0], 'apply_coupon_as_discount' ), $priority );
				add_action( 'woocommerce_cart_calculate_fees', 'ats_free_shipping_gated_discount', $priority );
				return;
			}
		}
	}
}
add_action( 'wp_loaded', 'ats_free_shipping_swap_offset_handler' );

/**
 * Gated replacement for the plugin's free-shipping offset.
 *
 * Delegates to the plugin's original `apply_coupon_as_discount()` for eligible
 * (standard) methods; for excluded premium methods it returns early so the
 * negative "Free Shipping Discount" fee — and therefore both its amount and its
 * VAT line — is never added.
 *
 * @param WC_Cart $cart The cart being calculated.
 * @return void
 */
function ats_free_shipping_gated_discount( $cart ) {
	$plugin = ats_free_shipping_plugin_instance();

	if ( ! $plugin instanceof Woo_Coupon_Free_Shipping ) {
		return;
	}

	// Premium method selected — do not offset its cost; customer pays in full.
	if ( ats_free_shipping_has_excluded_method() ) {
		return;
	}

	$plugin->apply_coupon_as_discount( $cart );
}

/**
 * Whether the customer's currently chosen shipping method is on the excluded
 * (premium) list.
 *
 * Matching is by stable rate ID. WooCommerce stores the chosen method as the
 * full rate ID (`<method_id>:<instance_id>`); some Table Rate setups append a
 * sub-rate segment (e.g. `table_rate:8:2`), so we also treat any chosen ID that
 * begins with an excluded ID followed by ':' as a match.
 *
 * @return bool True if an excluded method is selected.
 */
function ats_free_shipping_has_excluded_method() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return false;
	}

	$chosen = WC()->session->get( 'chosen_shipping_methods' );

	if ( empty( $chosen ) || ! is_array( $chosen ) ) {
		return false;
	}

	$excluded = ats_free_shipping_excluded_methods();

	foreach ( $chosen as $rate_id ) {
		foreach ( $excluded as $excluded_id ) {
			if ( $rate_id === $excluded_id || 0 === strpos( (string) $rate_id, $excluded_id . ':' ) ) {
				return true;
			}
		}
	}

	return false;
}
