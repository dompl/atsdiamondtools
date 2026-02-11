<?php
/**
 * WooCommerce Template Path Customization
 *
 * Tells WooCommerce to look for template overrides in the build directory
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Override WooCommerce template path to use build directory
 */
add_filter( 'woocommerce_locate_template', 'ats_woocommerce_locate_template', 10, 3 );

function ats_woocommerce_locate_template( $template, $template_name, $template_path ) {
	// Define the build directory path
	$build_template = get_stylesheet_directory() . '/build/woocommerce/' . $template_name;

	// Check if template exists in build directory
	if ( file_exists( $build_template ) ) {
		return $build_template;
	}

	// Return original template if not found in build
	return $template;
}
/**
 * Overwrite default flexible content behavior.
 */
// add_filter( 'skylinewp_flexible_content_overwrite_default', '__return_true' );
