<?php
/**
 * Cart Cross-Sells Customization
 *
 * Moves cross-sells to appear inside cart sidebar after cart totals
 * to prevent them from disappearing on AJAX updates
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove default cross-sells position (after cart)
 */
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );

/**
 * Add cross-sells in sidebar after cart totals (as separate container)
 * Hooked to a custom action we'll add in cart.php
 */
add_action( 'ats_cart_sidebar_after_totals', 'ats_display_cart_cross_sells', 10 );

function ats_display_cart_cross_sells() {
	// Get cross-sells
	$cross_sells = array_filter( array_map( 'wc_get_product', WC()->cart->get_cross_sells() ), 'wc_products_array_filter_visible' );

	// Limit to 4 products
	$cross_sells = array_slice( $cross_sells, 0, 4 );

	if ( $cross_sells ) {
		wc_get_template(
			'cart/cross-sells.php',
			array(
				'cross_sells'    => $cross_sells,
				'posts_per_page' => 4,
				'orderby'        => 'rand',
				'columns'        => 1,
			)
		);
	}
}
