<?php
/**
 * Cart AJAX Handlers
 *
 * Handles AJAX requests for cart page:
 * - Update quantity
 * - Apply coupon
 * - Remove coupon
 * - Get cart totals
 *
 * Note: ats_remove_cart_item is handled by mini cart ajax-handler.php
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update cart item quantity via AJAX
 */
function ats_ajax_update_cart_quantity() {
	// Verify nonce
	check_ajax_referer( 'ats-cart-nonce', 'nonce' );

	$cart_key = isset( $_POST['cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) : '';
	$quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 0;

	if ( empty( $cart_key ) || $quantity < 0 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request', 'woocommerce' ) ) );
	}

	// Update cart
	WC()->cart->set_quantity( $cart_key, $quantity );

	// Get updated item
	$cart_item = WC()->cart->get_cart_item( $cart_key );
	if ( ! $cart_item ) {
		wp_send_json_error( array( 'message' => __( 'Item not found', 'woocommerce' ) ) );
	}

	// Get product
	$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_key );

	wp_send_json_success(
		array(
			'item_subtotal' => WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ),
			'cart_count'    => WC()->cart->get_cart_contents_count(),
			'cart_total'    => WC()->cart->get_cart_total(),
		)
	);
}
add_action( 'wp_ajax_ats_update_cart_quantity', 'ats_ajax_update_cart_quantity' );
add_action( 'wp_ajax_nopriv_ats_update_cart_quantity', 'ats_ajax_update_cart_quantity' );

/**
 * Apply coupon via AJAX
 */
function ats_ajax_apply_coupon() {
	// Verify nonce
	check_ajax_referer( 'ats-cart-nonce', 'nonce' );

	$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';

	if ( empty( $coupon_code ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a coupon code', 'woocommerce' ) ) );
	}

	// Apply coupon
	$result = WC()->cart->apply_coupon( $coupon_code );

	if ( $result ) {
		// Get success message from WooCommerce notices
		$notices = wc_get_notices( 'success' );
		$message = ! empty( $notices ) ? $notices[0]['notice'] : __( 'Coupon applied successfully', 'woocommerce' );
		wc_clear_notices();

		wp_send_json_success(
			array(
				'message' => $message,
			)
		);
	} else {
		// Get error message from WooCommerce notices
		$notices = wc_get_notices( 'error' );
		$message = ! empty( $notices ) ? $notices[0]['notice'] : __( 'Coupon is not valid', 'woocommerce' );
		wc_clear_notices();

		wp_send_json_error( array( 'message' => $message ) );
	}
}
add_action( 'wp_ajax_ats_apply_coupon', 'ats_ajax_apply_coupon' );
add_action( 'wp_ajax_nopriv_ats_apply_coupon', 'ats_ajax_apply_coupon' );

/**
 * Remove coupon via AJAX
 */
function ats_ajax_remove_coupon() {
	// Verify nonce
	check_ajax_referer( 'ats-cart-nonce', 'nonce' );

	$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';

	if ( empty( $coupon_code ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a coupon code', 'woocommerce' ) ) );
	}

	// Remove coupon
	WC()->cart->remove_coupon( $coupon_code );

	wp_send_json_success(
		array(
			'message' => __( 'Coupon removed', 'woocommerce' ),
		)
	);
}
add_action( 'wp_ajax_ats_remove_coupon', 'ats_ajax_remove_coupon' );
add_action( 'wp_ajax_nopriv_ats_remove_coupon', 'ats_ajax_remove_coupon' );

/**
 * Get cart totals HTML via AJAX
 * Includes cart totals and cross-sells to prevent disappearing
 */
function ats_ajax_get_cart_totals() {
	// Verify nonce
	check_ajax_referer( 'ats-cart-nonce', 'nonce' );

	// Recalculate totals
	WC()->cart->calculate_totals();

	// Get cart totals HTML (this includes cross-sells via woocommerce_after_cart_totals hook)
	ob_start();
	woocommerce_cart_totals();
	$html = ob_get_clean();

	wp_send_json_success(
		array(
			'html'             => $html,
			'cart_count'       => WC()->cart->get_cart_contents_count(),
			'cart_count_text'  => sprintf( _n( '%s item', '%s items', WC()->cart->get_cart_contents_count(), 'woocommerce' ), WC()->cart->get_cart_contents_count() ),
			'cart_total'       => WC()->cart->get_cart_total(),
		)
	);
}
add_action( 'wp_ajax_ats_get_cart_totals', 'ats_ajax_get_cart_totals' );
add_action( 'wp_ajax_nopriv_ats_get_cart_totals', 'ats_ajax_get_cart_totals' );

/**
 * Update shipping method via AJAX
 */
function ats_ajax_update_shipping_method() {
	// Verify nonce
	check_ajax_referer( 'ats-cart-nonce', 'nonce' );

	$shipping_method = isset( $_POST['shipping_method'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_method'] ) ) : '';

	if ( empty( $shipping_method ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid shipping method', 'woocommerce' ) ) );
	}

	// Set the chosen shipping method
	$chosen_shipping_methods = array( $shipping_method );
	WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

	// Recalculate cart totals with new shipping method
	WC()->cart->calculate_shipping();
	WC()->cart->calculate_totals();

	wp_send_json_success(
		array(
			'message'    => __( 'Shipping method updated', 'woocommerce' ),
			'cart_total' => WC()->cart->get_cart_total(),
		)
	);
}
add_action( 'wp_ajax_ats_update_shipping_method', 'ats_ajax_update_shipping_method' );
add_action( 'wp_ajax_nopriv_ats_update_shipping_method', 'ats_ajax_update_shipping_method' );

/**
 * Enqueue cart AJAX nonce
 */
function ats_enqueue_cart_nonce() {
	if ( is_cart() ) {
		// Add nonce to themeData
		add_filter(
			'skylinewp_localize',
			function ( $data ) {
				$data['cart_nonce'] = wp_create_nonce( 'ats-cart-nonce' );
				return $data;
			}
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ats_enqueue_cart_nonce' );
