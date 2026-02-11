<?php
/**
 * Price Manager AJAX Handler
 *
 * Handles inline price updates for the [ats_price_manager] shortcode.
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register AJAX action for price updates
 */
add_action( 'wp_ajax_ats_update_product_price', 'ats_handle_update_product_price' );

/**
 * Handle product price update via AJAX
 *
 * @return void
 */
function ats_handle_update_product_price() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_price_manager' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
	}

	// Verify admin capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
	}

	$product_id    = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$regular_price = isset( $_POST['regular_price'] ) ? sanitize_text_field( wp_unslash( $_POST['regular_price'] ) ) : '';
	$sale_price    = isset( $_POST['sale_price'] ) ? sanitize_text_field( wp_unslash( $_POST['sale_price'] ) ) : '';
	$field         = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';

	if ( $product_id <= 0 ) {
		wp_send_json_error( array( 'message' => 'Invalid product ID.' ) );
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		wp_send_json_error( array( 'message' => 'Product not found.' ) );
	}

	// Update the specific field
	if ( 'regular_price' === $field ) {
		if ( '' === $regular_price ) {
			wp_send_json_error( array( 'message' => 'Regular price cannot be empty.' ) );
		}
		$product->set_regular_price( $regular_price );

		// If sale price is higher than new regular price, clear it
		$current_sale = $product->get_sale_price();
		if ( '' !== $current_sale && (float) $current_sale >= (float) $regular_price ) {
			$product->set_sale_price( '' );
		}
	} elseif ( 'sale_price' === $field ) {
		// Sale price can be empty (to remove sale)
		if ( '' !== $sale_price ) {
			$current_regular = $product->get_regular_price();
			if ( (float) $sale_price >= (float) $current_regular ) {
				wp_send_json_error( array( 'message' => 'Sale price must be less than regular price.' ) );
			}
		}
		$product->set_sale_price( $sale_price );
	}

	$product->save();

	// Clear product cache
	wc_delete_product_transients( $product_id );

	// If this is a variation, also clear the parent cache
	if ( $product->is_type( 'variation' ) ) {
		$parent_id = $product->get_parent_id();
		if ( $parent_id ) {
			wc_delete_product_transients( $parent_id );
		}
	}

	wp_send_json_success( array(
		'message'       => 'Price updated.',
		'product_id'    => $product_id,
		'regular_price' => $product->get_regular_price(),
		'sale_price'    => $product->get_sale_price(),
		'price_html'    => $product->get_price_html(),
	) );
}

/**
 * Register AJAX action for variation deletion
 */
add_action( 'wp_ajax_ats_delete_variation', 'ats_handle_delete_variation' );

/**
 * Handle variation deletion via AJAX
 *
 * @return void
 */
function ats_handle_delete_variation() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_price_manager' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
	}

	// Verify admin capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
	}

	$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

	if ( $variation_id <= 0 ) {
		wp_send_json_error( array( 'message' => 'Invalid variation ID.' ) );
	}

	$variation = wc_get_product( $variation_id );

	if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
		wp_send_json_error( array( 'message' => 'Variation not found.' ) );
	}

	$parent_id = $variation->get_parent_id();

	// Delete the variation
	$variation->delete( true );

	// Clear parent product cache
	if ( $parent_id ) {
		wc_delete_product_transients( $parent_id );
	}

	wp_send_json_success( array(
		'message'      => 'Variation deleted.',
		'variation_id' => $variation_id,
		'parent_id'    => $parent_id,
	) );
}

/**
 * Add price manager nonce to themeData localization
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_price_manager_nonce( $scripts_localize ) {
	if ( current_user_can( 'manage_options' ) ) {
		$scripts_localize['price_manager_nonce'] = wp_create_nonce( 'ats_price_manager' );
	}
	return $scripts_localize;
}
add_filter( 'skyline_child_localizes', 'ats_add_price_manager_nonce' );
