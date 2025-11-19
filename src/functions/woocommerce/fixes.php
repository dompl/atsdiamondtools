<?php
/**
 * =============================================================================
 * WooCommerce Fixes and Patches
 * =============================================================================
 * Fixes for WooCommerce core issues and edge cases.
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fix WP_Error in ProductFilterAttribute block
 *
 * This prevents the error "Object of class WP_Error could not be converted to int"
 * that occurs in ProductFilterAttribute.php when a product attribute taxonomy
 * doesn't exist or has been deleted.
 *
 * The error happens because wp_count_terms() can return WP_Error, but the code
 * tries to convert it to int without checking.
 */
add_filter( 'woocommerce_attribute_taxonomies', function( $taxonomies ) {
	if ( empty( $taxonomies ) || !is_array( $taxonomies ) ) {
		return $taxonomies;
	}

	// Validate each taxonomy exists before allowing WooCommerce to use it
	$validated_taxonomies = array_filter( $taxonomies, function( $taxonomy ) {
		// Check if this is a valid taxonomy object
		if ( !is_object( $taxonomy ) || !isset( $taxonomy->attribute_name ) ) {
			return false;
		}

		// Build the full taxonomy name
		$taxonomy_name = 'pa_' . $taxonomy->attribute_name;

		// Check if the taxonomy actually exists in WordPress
		if ( !taxonomy_exists( $taxonomy_name ) ) {
			// Log the issue for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'[WooCommerce] Invalid product attribute taxonomy "%s" removed from attributes list. The taxonomy may need to be re-registered.',
					$taxonomy_name
				) );
			}
			return false;
		}

		return true;
	} );

	return array_values( $validated_taxonomies );
}, 10, 1 );

/**
 * Additional safety: Validate taxonomies before term counting
 *
 * This adds an extra layer of protection by checking the taxonomy
 * exists before wp_count_terms() is called.
 */
add_filter( 'pre_count_terms', function( $value, $taxonomies, $args ) {
	// Only process for product attribute taxonomies
	if ( !is_array( $taxonomies ) ) {
		$taxonomies = [ $taxonomies ];
	}

	foreach ( $taxonomies as $taxonomy ) {
		// Check if this is a product attribute taxonomy
		if ( strpos( $taxonomy, 'pa_' ) === 0 ) {
			// If the taxonomy doesn't exist, return 0 to prevent the error
			if ( !taxonomy_exists( $taxonomy ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf(
						'[WooCommerce] Prevented term count for non-existent taxonomy "%s"',
						$taxonomy
					) );
				}
				return 0;
			}
		}
	}

	// Return null to allow normal processing
	return $value;
}, 10, 3 );
