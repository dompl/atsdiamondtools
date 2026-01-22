<?php
/**
 * Enable Product Reviews by Default
 *
 * Ensures that all new products have comments/reviews enabled by default
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add WooCommerce theme support
 * This enables WooCommerce to use its template loader for product pages
 */
function ats_add_woocommerce_support() {
	add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'ats_add_woocommerce_support' );

/**
 * Set default comment status to 'open' for new products
 *
 * @param array $args Post type registration arguments
 * @return array Modified arguments
 */
function ats_enable_product_reviews_by_default( $args ) {
	// Enable comments/reviews for products by default
	$args['supports'][] = 'comments';

	return $args;
}
add_filter( 'woocommerce_register_post_type_product', 'ats_enable_product_reviews_by_default' );

/**
 * Add comments support to product post type immediately
 * This enables the Discussion metabox in WordPress admin
 */
function ats_add_comments_support_to_products() {
	add_post_type_support( 'product', 'comments' );
}
add_action( 'init', 'ats_add_comments_support_to_products', 20 );

/**
 * Ensure new products have comments open by default
 *
 * @param array $data Post data
 * @param array $postarr Raw post data
 * @return array Modified post data
 */
function ats_default_product_comment_status( $data, $postarr ) {
	// Only for new products (no ID yet)
	if ( $data['post_type'] === 'product' && empty( $postarr['ID'] ) ) {
		$data['comment_status'] = 'open';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'ats_default_product_comment_status', 10, 2 );

/**
 * Override parent theme's comments_open filter for products
 * Parent theme disables comments site-wide, but we need them for product reviews
 *
 * @param bool $open Whether comments are open
 * @param int $post_id Post ID
 * @return bool Modified comments open status
 */
function ats_enable_comments_for_products( $open, $post_id ) {
	// If no post_id provided, use global $post
	if ( ! $post_id ) {
		global $post, $product;

		// Try to get post from global $product first (for WooCommerce context)
		if ( ! $post && $product && is_callable( array( $product, 'get_id' ) ) ) {
			$post_id = $product->get_id();
		} elseif ( $post ) {
			$post_id = $post->ID;
		} else {
			return $open;
		}
	}

	$post = get_post( $post_id );

	// Enable comments for products if comment_status is 'open'
	if ( $post && $post->post_type === 'product' && $post->comment_status === 'open' ) {
		return true;
	}

	return $open;
}
add_filter( 'comments_open', 'ats_enable_comments_for_products', 30, 2 );
add_filter( 'pings_open', 'ats_enable_comments_for_products', 30, 2 );

/**
 * Override parent theme's comments_array filter for products
 * Parent theme hides all comments, but we need to show product reviews
 *
 * @param array $comments Array of comments
 * @param int $post_id Post ID
 * @return array Modified comments array
 */
function ats_show_comments_for_products( $comments, $post_id ) {
	// If no post_id provided, use global $post
	if ( ! $post_id ) {
		global $post;
		if ( ! $post ) {
			return $comments;
		}
		$post_id = $post->ID;
	}

	$post = get_post( $post_id );

	// Show comments for products
	if ( $post && $post->post_type === 'product' ) {
		// Remove this filter temporarily to get the real comments
		remove_filter( 'comments_array', '__return_empty_array', 10 );

		// Get the actual comments
		$comments = get_comments( array(
			'post_id' => $post_id,
			'status' => 'approve',
			'type' => 'review',
		) );
	}

	return $comments;
}
add_filter( 'comments_array', 'ats_show_comments_for_products', 30, 2 );

/**
 * Force add reviews tab to product tabs if it's missing
 * This is a failsafe in case comments_open() doesn't work correctly during tab generation
 *
 * @param array $tabs Product tabs
 * @return array Modified tabs with reviews tab added
 */
function ats_force_add_reviews_tab( $tabs ) {
	global $product;

	// Only for products
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return $tabs;
	}

	$product_id = $product->get_id();
	$post = get_post( $product_id );

	// Only add if product has comments open
	if ( ! $post || $post->post_type !== 'product' || $post->comment_status !== 'open' ) {
		return $tabs;
	}

	// If reviews tab already exists, don't add it again
	if ( isset( $tabs['reviews'] ) ) {
		return $tabs;
	}

	// Add reviews tab
	$tabs['reviews'] = array(
		'title'    => sprintf( __( 'Reviews (%d)', 'woocommerce' ), $product->get_review_count() ),
		'priority' => 30,
		'callback' => 'comments_template',
	);

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'ats_force_add_reviews_tab', 98 );

/**
 * Load custom WooCommerce reviews template
 * Override WordPress comments template for products to use WooCommerce template
 *
 * @param string $template Path to comments template
 * @return string Modified template path
 */
function ats_load_product_reviews_template( $template ) {
	if ( get_post_type() !== 'product' ) {
		return $template;
	}

	// Check for template in child theme
	$child_template = get_stylesheet_directory() . '/woocommerce/single-product-reviews.php';
	if ( file_exists( $child_template ) ) {
		return $child_template;
	}

	// Check for template in parent theme
	$parent_template = get_template_directory() . '/woocommerce/single-product-reviews.php';
	if ( file_exists( $parent_template ) ) {
		return $parent_template;
	}

	// Fallback to WooCommerce default
	return $template;
}
add_filter( 'comments_template', 'ats_load_product_reviews_template', 1 );

/**
 * Customize WooCommerce rating options text to use star symbols
 * Override the default "1 of 5 stars" text with actual star symbols
 */
function ats_customize_rating_options() {
	wp_add_inline_script( 'wc-single-product', '
		if ( typeof wc_single_product_params !== "undefined" && wc_single_product_params.i18n_rating_options ) {
			wc_single_product_params.i18n_rating_options = [
				"★☆☆☆☆",
				"★★☆☆☆",
				"★★★☆☆",
				"★★★★☆",
				"★★★★★"
			];
		}
	', 'before' );
}
add_action( 'wp_enqueue_scripts', 'ats_customize_rating_options', 99 );
