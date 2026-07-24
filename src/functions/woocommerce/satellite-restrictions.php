<?php
/**
 * Satellite bridge — per-channel restrictions (ATS side).
 *
 * Enforces a channel's ruleset restrictions on ATS, so they hold even against a
 * crafted request rather than merely being absent from the satellite UI:
 *   - excluded products cannot be purchased;
 *   - only allowlisted coupon codes are accepted;
 *   - free-shipping methods are withheld when the channel has free delivery off;
 *   - explicitly hidden shipping methods are removed.
 *
 * Everything no-ops unless a valid channel is present.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'skylinewp_child_satellite_coupon_allowed' ) ) {
	/**
	 * Whether a coupon code is on the channel's allowlist (case-insensitive). Pure.
	 *
	 * @param array  $ruleset Ruleset.
	 * @param string $code    Coupon code.
	 * @return bool
	 */
	function skylinewp_child_satellite_coupon_allowed( $ruleset, $code ) {
		$allow = isset( $ruleset['coupons'] ) && is_array( $ruleset['coupons'] ) ? $ruleset['coupons'] : array();
		$allow = array_map( static function ( $c ) {
			return strtolower( trim( (string) $c ) );
		}, $allow );
		return in_array( strtolower( trim( (string) $code ) ), $allow, true );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_rate_hidden' ) ) {
	/**
	 * Whether a shipping rate should be withheld from the channel. Pure.
	 *
	 * @param string $method_id WooCommerce method id (e.g. 'free_shipping', 'table_rate').
	 * @param string $rate_id   Full rate id (e.g. 'table_rate:8').
	 * @param array  $ruleset   Ruleset.
	 * @return bool
	 */
	function skylinewp_child_satellite_rate_hidden( $method_id, $rate_id, $ruleset ) {
		$free_on = ! isset( $ruleset['freeShipping'] ) || (bool) $ruleset['freeShipping'];
		if ( ! $free_on && 'free_shipping' === $method_id ) {
			return true;
		}
		$hidden = isset( $ruleset['hiddenMethods'] ) && is_array( $ruleset['hiddenMethods'] ) ? $ruleset['hiddenMethods'] : array();
		return in_array( (string) $rate_id, array_map( 'strval', $hidden ), true );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_ruleset_or_null' ) ) {
	/**
	 * The current channel's ruleset if a channel is present and a ruleset is
	 * obtainable; otherwise null (nothing to enforce here — pricing.php handles
	 * the checkout refusal when a ruleset is missing).
	 *
	 * @return array|null
	 */
	function skylinewp_child_satellite_ruleset_or_null() {
		$channel = skylinewp_child_satellite_current_channel();
		if ( null === $channel ) {
			return null;
		}
		$ruleset = skylinewp_child_satellite_get_ruleset( $channel['key'] );
		return is_wp_error( $ruleset ) ? null : $ruleset;
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_register_restrictions' ) ) {
	/**
	 * Register the restriction hooks — only for channel requests.
	 */
	function skylinewp_child_satellite_register_restrictions() {
		if ( null === skylinewp_child_satellite_current_channel() ) {
			return;
		}

		// Excluded products are not purchasable (respected by both the classic
		// and Store API add-to-cart paths, and hides the buy button).
		add_filter(
			'woocommerce_is_purchasable',
			static function ( $purchasable, $product ) {
				$ruleset = skylinewp_child_satellite_ruleset_or_null();
				if ( null === $ruleset || ! ( $product instanceof WC_Product ) ) {
					return $purchasable;
				}
				$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
				return skylinewp_child_satellite_is_excluded( $ruleset, $id ) ? false : $purchasable;
			},
			20,
			2
		);

		// Belt-and-braces on the classic add-to-cart path.
		add_filter(
			'woocommerce_add_to_cart_validation',
			static function ( $passed, $product_id ) {
				$ruleset = skylinewp_child_satellite_ruleset_or_null();
				if ( null !== $ruleset && skylinewp_child_satellite_is_excluded( $ruleset, $product_id ) ) {
					wc_add_notice( __( 'Sorry, that product isn’t available here.', 'skylinewp-child' ), 'error' );
					return false;
				}
				return $passed;
			},
			20,
			2
		);

		// Only allowlisted coupons apply.
		add_filter(
			'woocommerce_coupon_is_valid',
			static function ( $valid, $coupon ) {
				$ruleset = skylinewp_child_satellite_ruleset_or_null();
				if ( null === $ruleset || ! ( $coupon instanceof WC_Coupon ) ) {
					return $valid;
				}
				return skylinewp_child_satellite_coupon_allowed( $ruleset, $coupon->get_code() ) ? $valid : false;
			},
			20,
			2
		);

		// Withhold free shipping (when off) and any hidden methods.
		add_filter(
			'woocommerce_package_rates',
			static function ( $rates ) {
				$ruleset = skylinewp_child_satellite_ruleset_or_null();
				if ( null === $ruleset || ! is_array( $rates ) ) {
					return $rates;
				}
				foreach ( $rates as $rate_id => $rate ) {
					if ( skylinewp_child_satellite_rate_hidden( $rate->get_method_id(), $rate->get_id(), $ruleset ) ) {
						unset( $rates[ $rate_id ] );
					}
				}
				return $rates;
			},
			20,
			1
		);
	}
	add_action( 'woocommerce_init', 'skylinewp_child_satellite_register_restrictions' );
}
