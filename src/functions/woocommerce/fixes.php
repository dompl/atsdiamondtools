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
			// Log suppressed to avoid flooding debug.log with "pa_variants-XXX" issues from migration
			/*
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'[WooCommerce] Invalid product attribute taxonomy "%s" removed from attributes list. The taxonomy may need to be re-registered.',
					$taxonomy_name
				) );
			}
			*/
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

/**
 * Enhance variation data with proper image information
 *
 * Ensures that variation images are properly included in the available_variations
 * JSON data so that the frontend can switch images when variations are selected.
 */
add_filter( 'woocommerce_available_variation', function( $data, $product, $variation ) {
	// Get the variation's own image ID
	$variation_image_id = $variation->get_image_id();

	// If variation has its own image, use that
	if ( $variation_image_id ) {
		$data['image_id'] = $variation_image_id;

		// Build complete image data
		$image = wp_get_attachment_image_src( $variation_image_id, 'woocommerce_single' );
		$full_image = wp_get_attachment_image_src( $variation_image_id, 'full' );
		$thumb_image = wp_get_attachment_image_src( $variation_image_id, 'woocommerce_thumbnail' );
		$gallery_thumb = wp_get_attachment_image_src( $variation_image_id, 'woocommerce_gallery_thumbnail' );

		if ( $image ) {
			$data['image'] = array(
				'title'                   => get_the_title( $variation_image_id ),
				'caption'                 => get_post_field( 'post_excerpt', $variation_image_id ),
				'url'                     => $full_image ? $full_image[0] : '',
				'alt'                     => get_post_meta( $variation_image_id, '_wp_attachment_image_alt', true ),
				'src'                     => $image[0],
				'srcset'                  => wp_get_attachment_image_srcset( $variation_image_id, 'woocommerce_single' ),
				'sizes'                   => wp_get_attachment_image_sizes( $variation_image_id, 'woocommerce_single' ),
				'full_src'                => $full_image ? $full_image[0] : '',
				'full_src_w'              => $full_image ? $full_image[1] : '',
				'full_src_h'              => $full_image ? $full_image[2] : '',
				'gallery_thumbnail_src'   => $gallery_thumb ? $gallery_thumb[0] : '',
				'gallery_thumbnail_src_w' => $gallery_thumb ? $gallery_thumb[1] : '',
				'gallery_thumbnail_src_h' => $gallery_thumb ? $gallery_thumb[2] : '',
				'thumb_src'               => $thumb_image ? $thumb_image[0] : '',
				'thumb_src_w'             => $thumb_image ? $thumb_image[1] : '',
				'thumb_src_h'             => $thumb_image ? $thumb_image[2] : '',
				'src_w'                   => $image[1],
				'src_h'                   => $image[2],
				'image_id'                => $variation_image_id,
				'id'                      => $variation_image_id,
			);
		}
	} else {
		// Enhance existing image data if present
		if ( ! empty( $data['image_id'] ) ) {
			$image_id = $data['image_id'];

			// Add additional image data for better matching
			$data['image']['image_id'] = $image_id;
			$data['image']['id'] = $image_id;

			// Ensure full_src is set
			if ( empty( $data['image']['full_src'] ) ) {
				$full_src = wp_get_attachment_image_url( $image_id, 'full' );
				if ( $full_src ) {
					$data['image']['full_src'] = $full_src;
				}
			}

			// Ensure thumb_src is set
			if ( empty( $data['image']['thumb_src'] ) ) {
				$thumb_src = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
				if ( $thumb_src ) {
					$data['image']['thumb_src'] = $thumb_src;
				}
			}

			// Ensure gallery_thumbnail_src is set
			if ( empty( $data['image']['gallery_thumbnail_src'] ) ) {
				$gallery_thumb = wp_get_attachment_image_url( $image_id, 'woocommerce_gallery_thumbnail' );
				if ( $gallery_thumb ) {
					$data['image']['gallery_thumbnail_src'] = $gallery_thumb;
				}
			}
		}
	}

	return $data;
}, 10, 3 );
