<?php
/**
 * Favorites AJAX Handlers
 *
 * Handles AJAX requests for adding/removing products from user favorites
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register AJAX actions for favorites
 */
add_action( 'wp_ajax_ats_toggle_favorite', 'ats_handle_toggle_favorite' );
add_action( 'wp_ajax_nopriv_ats_toggle_favorite', 'ats_handle_toggle_favorite' );
add_action( 'wp_ajax_ats_get_favorites', 'ats_handle_get_favorites' );
add_action( 'wp_ajax_nopriv_ats_get_favorites', 'ats_handle_get_favorites' );
add_action( 'wp_ajax_ats_remove_favorite', 'ats_handle_remove_favorite' );
add_action( 'wp_ajax_nopriv_ats_remove_favorite', 'ats_handle_remove_favorite' );

/**
 * Handle AJAX request to toggle product favorite status
 *
 * @return void
 */
function ats_handle_toggle_favorite() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_favorites_nonce' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed.', 'skylinewp-dev-child' ),
			),
			403
		);
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error(
			array(
				'message' => __( 'You must be logged in to add favorites.', 'skylinewp-dev-child' ),
				'login_required' => true,
			)
		);
	}

	// Get product ID
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

	if ( ! $product_id ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid product ID.', 'skylinewp-dev-child' ),
			),
			400
		);
	}

	// Verify product exists
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		wp_send_json_error(
			array(
				'message' => __( 'Product not found.', 'skylinewp-dev-child' ),
			),
			404
		);
	}

	$user_id = get_current_user_id();
	$favorites = get_user_meta( $user_id, 'ats_favorite_products', true );

	// Initialize favorites array if it doesn't exist
	if ( ! is_array( $favorites ) ) {
		$favorites = array();
	}

	// Toggle favorite status
	$is_favorite = false;
	if ( in_array( $product_id, $favorites, true ) ) {
		// Remove from favorites
		$favorites = array_diff( $favorites, array( $product_id ) );
		$message = __( 'Removed from favorites.', 'skylinewp-dev-child' );
	} else {
		// Add to favorites
		$favorites[] = $product_id;
		$is_favorite = true;
		$message = __( 'Added to favorites!', 'skylinewp-dev-child' );
	}

	// Update user meta
	update_user_meta( $user_id, 'ats_favorite_products', array_values( $favorites ) );

	wp_send_json_success(
		array(
			'message' => $message,
			'is_favorite' => $is_favorite,
			'favorites_count' => count( $favorites ),
		)
	);
}

/**
 * Handle AJAX request to get user's favorite products
 *
 * @return void
 */
function ats_handle_get_favorites() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_favorites_nonce' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed.', 'skylinewp-dev-child' ),
			),
			403
		);
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error(
			array(
				'message' => __( 'You must be logged in.', 'skylinewp-dev-child' ),
			),
			401
		);
	}

	$user_id = get_current_user_id();
	$favorites = get_user_meta( $user_id, 'ats_favorite_products', true );

	if ( ! is_array( $favorites ) ) {
		$favorites = array();
	}

	wp_send_json_success(
		array(
			'favorites' => $favorites,
			'count' => count( $favorites ),
		)
	);
}

/**
 * Handle AJAX request to remove product from favorites
 *
 * @return void
 */
function ats_handle_remove_favorite() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_favorites_nonce' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed.', 'skylinewp-dev-child' ),
			),
			403
		);
	}

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json_error(
			array(
				'message' => __( 'You must be logged in.', 'skylinewp-dev-child' ),
			),
			401
		);
	}

	// Get product ID
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

	if ( ! $product_id ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid product ID.', 'skylinewp-dev-child' ),
			),
			400
		);
	}

	$user_id = get_current_user_id();
	$favorites = get_user_meta( $user_id, 'ats_favorite_products', true );

	if ( ! is_array( $favorites ) ) {
		$favorites = array();
	}

	// Remove from favorites
	$favorites = array_diff( $favorites, array( $product_id ) );
	update_user_meta( $user_id, 'ats_favorite_products', array_values( $favorites ) );

	wp_send_json_success(
		array(
			'message' => __( 'Removed from favorites.', 'skylinewp-dev-child' ),
			'favorites_count' => count( $favorites ),
		)
	);
}

/**
 * Add favorites nonce to themeData localization
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_favorites_nonce( $scripts_localize ) {
	$scripts_localize['favorites_nonce'] = wp_create_nonce( 'ats_favorites_nonce' );

	// Add user favorites if logged in
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		$favorites = get_user_meta( $user_id, 'ats_favorite_products', true );
		$scripts_localize['user_favorites'] = is_array( $favorites ) ? $favorites : array();
	} else {
		$scripts_localize['user_favorites'] = array();
	}

	// Add account URL for login redirect
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$scripts_localize['account_url'] = wc_get_page_permalink( 'myaccount' );
	}

	return $scripts_localize;
}
add_filter( 'skyline_child_localizes', 'ats_add_favorites_nonce' );

/**
 * Check if a product is in user's favorites
 *
 * @param int $product_id Product ID.
 * @param int $user_id User ID (optional, defaults to current user).
 * @return bool
 */
function ats_is_product_favorite( $product_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return false;
	}

	$favorites = get_user_meta( $user_id, 'ats_favorite_products', true );

	if ( ! is_array( $favorites ) ) {
		return false;
	}

	return in_array( (int) $product_id, $favorites, true );
}
