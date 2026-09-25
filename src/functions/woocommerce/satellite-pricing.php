<?php
/**
 * Satellite bridge — markup application (ATS side).
 *
 * Wires the pricing engine into WooCommerce's price getters for channel
 * requests only, so ATS shows and charges the satellite's marked-up price while
 * normal traffic is untouched. Filtering at the product-price layer (rather than
 * adding a fee line) makes the marked-up price look native everywhere: line
 * subtotals, VAT, coupon percentages, free-shipping thresholds, the order total
 * and the ATS invoice all fall out of it.
 *
 * Raw prices are read in the 'edit' context, which bypasses these same filters
 * and so avoids recursion.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'skylinewp_child_satellite_pence_to_price_string' ) ) {
	/**
	 * Format integer pence as a WooCommerce price string ("27.99").
	 *
	 * @param int $pence Amount in pence.
	 * @return string
	 */
	function skylinewp_child_satellite_pence_to_price_string( $pence ) {
		return number_format( $pence / 100, 2, '.', '' );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_marked_price_string' ) ) {
	/**
	 * Compute a channel's marked-up price for one price slot. Pure.
	 *
	 * @param string      $regular_str Raw regular price string.
	 * @param string|null $sale_str    Raw sale price string, '' or null if none.
	 * @param string      $which       'active' | 'regular' | 'sale'.
	 * @param bool        $follow_sales Whether the channel follows ATS sales.
	 * @param array       $rule        Markup rule.
	 * @return string Marked price string; '' for a sale slot with no active sale.
	 */
	function skylinewp_child_satellite_marked_price_string( $regular_str, $sale_str, $which, $follow_sales, $rule ) {
		$regular = skylinewp_child_satellite_to_pence( $regular_str );
		if ( null === $regular ) {
			return 'sale' === $which ? '' : (string) $regular_str;
		}
		$sale = ( '' === $sale_str || null === $sale_str ) ? null : skylinewp_child_satellite_to_pence( $sale_str );

		if ( 'regular' === $which ) {
			$res = skylinewp_child_satellite_price_one( $regular, null, false, $rule );
			return skylinewp_child_satellite_pence_to_price_string( $res['charged'] );
		}

		if ( 'sale' === $which ) {
			if ( $follow_sales && null !== $sale && $sale < $regular ) {
				$res = skylinewp_child_satellite_price_one( $sale, null, false, $rule );
				return skylinewp_child_satellite_pence_to_price_string( $res['charged'] );
			}
			return '';
		}

		// active
		$res = skylinewp_child_satellite_price_one( $regular, $sale, (bool) $follow_sales, $rule );
		return skylinewp_child_satellite_pence_to_price_string( $res['charged'] );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_apply_price' ) ) {
	/**
	 * Filter callback core: mark up one price slot for the given product.
	 *
	 * @param mixed       $fallback   Original price to return when not applicable.
	 * @param WC_Product  $product    The product or variation being priced.
	 * @param string      $which      'active' | 'regular' | 'sale'.
	 * @param int|null    $parent_id  Parent product id for variations, else null.
	 * @return mixed
	 */
	function skylinewp_child_satellite_apply_price( $fallback, $product, $which, $parent_id = null ) {
		$channel = skylinewp_child_satellite_current_channel();
		if ( null === $channel || ! ( $product instanceof WC_Product ) ) {
			return $fallback;
		}
		$ruleset = skylinewp_child_satellite_get_ruleset( $channel['key'] );
		if ( is_wp_error( $ruleset ) ) {
			return $fallback; // display fallback only; checkout is refused separately.
		}
		$rule_id = $parent_id ? (int) $parent_id : (int) $product->get_id();
		if ( skylinewp_child_satellite_is_excluded( $ruleset, $rule_id ) ) {
			return $fallback;
		}
		$rule         = skylinewp_child_satellite_rule_for_product( $ruleset, $rule_id );
		$follow_sales = ! isset( $ruleset['followSales'] ) || (bool) $ruleset['followSales'];

		return skylinewp_child_satellite_marked_price_string(
			$product->get_regular_price( 'edit' ),
			$product->get_sale_price( 'edit' ),
			$which,
			$follow_sales,
			$rule
		);
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_register_pricing' ) ) {
	/**
	 * Register the price filters — only when a valid channel is present, so
	 * normal atsdiamondtools.co.uk traffic pays no cost.
	 */
	function skylinewp_child_satellite_register_pricing() {
		if ( null === skylinewp_child_satellite_current_channel() ) {
			return;
		}

		// Simple + variation product getters: ($price, $product).
		$slots = array(
			'price'         => 'active',
			'regular_price' => 'regular',
			'sale_price'    => 'sale',
		);
		foreach ( $slots as $slot => $which ) {
			$cb = static function ( $price, $product ) use ( $which ) {
				return skylinewp_child_satellite_apply_price( $price, $product, $which );
			};
			add_filter( "woocommerce_product_get_{$slot}", $cb, 20, 2 );
			add_filter( "woocommerce_product_variation_get_{$slot}", $cb, 20, 2 );
		}

		// Variation price range (the "from" display + its cache): ($price, $variation, $product).
		$range_slots = array(
			'price'         => 'active',
			'regular_price' => 'regular',
			'sale_price'    => 'sale',
		);
		foreach ( $range_slots as $slot => $which ) {
			add_filter(
				"woocommerce_variation_prices_{$slot}",
				static function ( $price, $variation, $product ) use ( $which ) {
					return skylinewp_child_satellite_apply_price( $price, $variation, $which, $product->get_id() );
				},
				20,
				3
			);
		}

		// Keep the variation-price cache per-channel so ranges never leak.
		add_filter(
			'woocommerce_get_variation_prices_hash',
			static function ( $hash ) {
				$channel = skylinewp_child_satellite_current_channel();
				if ( $channel ) {
					$ruleset            = skylinewp_child_satellite_get_ruleset( $channel['key'] );
					$version            = is_array( $ruleset ) && isset( $ruleset['version'] ) ? $ruleset['version'] : 'na';
					$hash['_satellite'] = $channel['key'] . ':' . $version;
				}
				return $hash;
			},
			20,
			1
		);
	}
	add_action( 'woocommerce_init', 'skylinewp_child_satellite_register_pricing' );
}

if ( ! function_exists( 'skylinewp_child_satellite_guard_checkout' ) ) {
	/**
	 * Refuse checkout when a channel request has no obtainable ruleset, rather
	 * than ever charging ATS base prices. Wired for both the Store API (headless
	 * satellite checkout) and the classic checkout.
	 *
	 * @param WP_Error $errors Accumulating cart errors.
	 */
	function skylinewp_child_satellite_guard_checkout( $errors = null ) {
		$channel = skylinewp_child_satellite_current_channel();
		if ( null === $channel ) {
			return;
		}
		$ruleset = skylinewp_child_satellite_get_ruleset( $channel['key'] );
		if ( is_wp_error( $ruleset ) ) {
			$message = __( 'Sorry, we can’t complete checkout right now. Please try again shortly.', 'skylinewp-child' );
			if ( $errors instanceof WP_Error ) {
				$errors->add( 'satellite_no_ruleset', $message );
			} else {
				wc_add_notice( $message, 'error' );
			}
		}
	}
	add_action( 'woocommerce_store_api_cart_errors', 'skylinewp_child_satellite_guard_checkout', 10, 1 );
	add_action( 'woocommerce_check_cart_items', 'skylinewp_child_satellite_guard_checkout', 10, 0 );
}
