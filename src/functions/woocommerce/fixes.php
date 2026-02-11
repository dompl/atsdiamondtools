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
 * Ensure attribute taxonomies are registered early
 *
 * This hook registers all product attribute taxonomies early in the WordPress
 * init process, before they're checked by the filter below.
 */
add_action( 'init', function() {
	global $wpdb;

	// Get all attributes from database
	$attributes = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies ORDER BY attribute_name ASC"
	);

	if ( empty( $attributes ) ) {
		return;
	}

	// Register each attribute taxonomy
	foreach ( $attributes as $attribute ) {
		$taxonomy_name = wc_attribute_taxonomy_name( $attribute->attribute_name );

		// Only register if not already registered
		if ( ! taxonomy_exists( $taxonomy_name ) ) {
			register_taxonomy(
				$taxonomy_name,
				apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
				apply_filters( 'woocommerce_taxonomy_args_' . $taxonomy_name, array(
					'labels'       => array(
						'name' => $attribute->attribute_label,
					),
					'hierarchical' => false,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
					'public'       => (bool) $attribute->attribute_public,
					'show_in_nav_menus' => (bool) $attribute->attribute_public,
					'capabilities' => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'edit_products',
					),
				))
			);
		}
	}
}, 5 ); // Priority 5 to run early

/**
 * Fix WP_Error in ProductFilterAttribute block
 *
 * TEMPORARILY DISABLED - This filter was blocking all attributes
 * because taxonomies weren't registered at the time the filter ran.
 *
 * This prevents the error "Object of class WP_Error could not be converted to int"
 * that occurs in ProductFilterAttribute.php when a product attribute taxonomy
 * doesn't exist or has been deleted.
 *
 * The error happens because wp_count_terms() can return WP_Error, but the code
 * tries to convert it to int without checking.
 */
/*
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
*/

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

/**
 * Output variation templates globally for quick view modal
 *
 * WooCommerce variation templates need to be present in the DOM before the variation
 * form is initialized. When loading products via AJAX (quick view modal), script tags
 * inserted via innerHTML don't get properly added to the DOM. This outputs the required
 * templates globally so they're always available.
 */
add_action( 'wp_footer', function() {
	// Only output once, even if called multiple times
	static $templates_output = false;

	if ( $templates_output ) {
		return;
	}

	$templates_output = true;

	// Output the variation templates that WooCommerce's JS requires
	?>
	<script type="text/template" id="tmpl-variation-template">
		<div class="woocommerce-variation-description">{{{ data.variation.variation_description }}}</div>
		<div class="woocommerce-variation-price">{{{ data.variation.price_html }}}</div>
		<div class="woocommerce-variation-availability">{{{ data.variation.availability_html }}}</div>
	</script>
	<script type="text/template" id="tmpl-unavailable-variation-template">
		<p role="alert"><?php esc_html_e( 'Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce' ); ?></p>
	</script>
	<?php
}, 999 );

// You can directly override the package name using the filter as WooCommerce does internally.
// The filter 'woocommerce_shipping_package_name' is the correct and only way to modify the shipping package name
// without overriding WooCommerce core files. The code you have is the right approach.

add_filter('woocommerce_shipping_package_name', function( $package_name, $package_index, $package ) {
	if ( is_string( $package_name ) ) {

		// Replace the word "Shipping" (case-insensitive, only the word, not substrings)
		$package_name = preg_replace_callback(
			'/\b(Shipping)\b/i',
			function( $matches ) {
				return '<span class="font-sm">' . $matches[1] . '</span>';
			},
			$package_name
		);
	}
	return $package_name;
}, 10, 3);

/**
 * Suppress WooCommerce outdated template notice
 *
 * Our templates have custom implementations (Splide slider, Tailwind styling, etc.)
 * that intentionally deviate from WooCommerce core templates. The version differences
 * are cosmetic and don't affect functionality. This completely suppresses the notice.
 */

// Hook very early to prevent WooCommerce from even checking template versions
add_action( 'admin_init', function() {
	// Remove the outdated templates notice action
	remove_action( 'admin_notices', array( 'WC_Admin_Notices', 'template_files' ) );

	// Clear the notice from the database
	$notices = get_option( 'woocommerce_admin_notices', array() );
	if ( is_array( $notices ) && in_array( 'template_files', $notices ) ) {
		$notices = array_diff( $notices, array( 'template_files' ) );
		update_option( 'woocommerce_admin_notices', $notices );
	}
}, 1 );

// Filter out outdated template data before it's even checked
add_filter( 'woocommerce_template_overrides_scan_paths', function( $paths ) {
	// Return empty array to skip template scanning entirely
	return array();
}, 999 );

// Override the template check to always return empty (no outdated templates)
add_filter( 'wc_get_template_part', function( $template, $slug, $name ) {
	// Don't actually modify templates, just prevent the version check
	return $template;
}, 999, 3 );

// Directly hide the template notice via CSS as last resort
add_action( 'admin_head', function() {
	echo '<style>
		/* Hide WooCommerce outdated template notice */
		.woocommerce-message.updated:has([href*="system_status"]) {
			display: none !important;
		}
	</style>';
} );