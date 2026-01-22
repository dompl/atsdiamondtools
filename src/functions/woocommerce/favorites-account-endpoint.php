<?php
/**
 * My Account Favorites Endpoint
 *
 * Adds "My Favourite Products" endpoint to WooCommerce My Account
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register new endpoint for favorites
 */
function ats_add_favorites_endpoint() {
	add_rewrite_endpoint( 'favorites', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'ats_add_favorites_endpoint' );

/**
 * Add favorites to My Account menu
 *
 * @param array $items Menu items.
 * @return array Modified menu items.
 */
function ats_add_favorites_menu_item( $items ) {
	// Insert after dashboard but before orders
	$new_items = array();

	foreach ( $items as $key => $item ) {
		$new_items[ $key ] = $item;

		// Insert favorites after dashboard
		if ( 'dashboard' === $key ) {
			$new_items['favorites'] = __( 'My Favourite Products', 'skylinewp-dev-child' );
		}
	}

	return $new_items;
}
add_filter( 'woocommerce_account_menu_items', 'ats_add_favorites_menu_item' );

/**
 * Set endpoint title
 *
 * @param string $title Original title.
 * @return string Modified title.
 */
function ats_favorites_endpoint_title( $title ) {
	global $wp_query;

	$is_endpoint = isset( $wp_query->query_vars['favorites'] );

	if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
		$title = __( 'My Favourite Products', 'skylinewp-dev-child' );

		remove_filter( 'the_title', 'ats_favorites_endpoint_title' );
	}

	return $title;
}
add_filter( 'the_title', 'ats_favorites_endpoint_title' );

/**
 * Favorites endpoint content
 */
function ats_favorites_endpoint_content() {
	wc_get_template( 'myaccount/favorites.php' );
}
add_action( 'woocommerce_account_favorites_endpoint', 'ats_favorites_endpoint_content' );

/**
 * Flush rewrite rules on theme switch
 * This ensures the favorites endpoint is registered properly
 */
function ats_flush_favorites_rewrite_rules() {
	ats_add_favorites_endpoint();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'ats_flush_favorites_rewrite_rules' );

/**
 * Check and flush rewrite rules if favorites endpoint is missing
 * This runs on admin_init to ensure endpoint is always available
 */
function ats_check_favorites_endpoint() {
	$rules = get_option( 'rewrite_rules' );

	// If favorites endpoint is not in rewrite rules, flush
	if ( ! isset( $rules['favorites/?$'] ) ) {
		ats_add_favorites_endpoint();
		flush_rewrite_rules();
	}
}
add_action( 'admin_init', 'ats_check_favorites_endpoint' );
