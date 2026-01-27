<?php
/**
 * Shop Filter AJAX Handler
 *
 * Handles product filtering via AJAX for shop and category pages
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register AJAX actions for shop filtering
 */
add_action( 'wp_ajax_ats_filter_products', 'ats_handle_filter_products' );
add_action( 'wp_ajax_nopriv_ats_filter_products', 'ats_handle_filter_products' );

/**
 * Handle product filtering AJAX request
 *
 * @return void
 */
function ats_handle_filter_products() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_shop_filter' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed. Please refresh the page.', 'skylinewp-dev-child' ),
			),
			403
		);
	}

	// Get filter parameters.
	$category        = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;
	$min_price       = isset( $_POST['min_price'] ) ? floatval( $_POST['min_price'] ) : 0;
	$max_price       = isset( $_POST['max_price'] ) ? floatval( $_POST['max_price'] ) : 0;
	$orderby         = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'default';
	$paged           = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
	$per_page        = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 12;
	$favourites_only = isset( $_POST['favourites_only'] ) && $_POST['favourites_only'] === '1';

	// Validate per_page to prevent abuse.
	if ( $per_page < 1 || $per_page > 48 ) {
		$per_page = 12;
	}

	// Build query arguments.
	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
	);

	// Add category filter.
	if ( $category > 0 ) {
		$query_args['category'] = $category;
	}

	// Add price filter.
	if ( $min_price > 0 || $max_price > 0 ) {
		$query_args['min_price'] = $min_price;
		$query_args['max_price'] = $max_price;
	}

	// Add orderby.
	if ( ! empty( $orderby ) ) {
		$query_args['orderby'] = $orderby;
	}

	// Add favourites filter.
	if ( $favourites_only ) {
		$query_args['favourites_only'] = true;
	}

	// Render products HTML.
	$products_html = ats_render_product_grid( $query_args );

	// Get total product count for this query (without pagination).
	$count_args              = $query_args;
	$count_args['paged']     = 1;
	$count_args['nopaging']  = false;
	$count_args['fields']    = 'ids';
	unset( $count_args['posts_per_page'] );

	$count_query  = new WP_Query( $count_args );
	$total_count  = $count_query->found_posts;
	$max_pages    = ceil( $total_count / $per_page );
	$current_page = $paged;

	// Calculate current showing count.
	$showing_start = ( ( $current_page - 1 ) * $per_page ) + 1;
	$showing_end   = min( $current_page * $per_page, $total_count );

	// Handle zero results
	if ( $total_count === 0 ) {
		$showing_start = 0;
		$showing_end   = 0;
	}

	wp_reset_postdata();

	// Get banner data for category pages
	$banner_data = array(
		'show_banner'   => false,
		'category_name' => '',
		'category_desc' => '',
		'banner_image'  => '',
	);

	if ( $category > 0 ) {
		$category_term = get_term( $category, 'product_cat' );
		if ( $category_term && ! is_wp_error( $category_term ) ) {
			$thumbnail_id        = get_term_meta( $category, 'thumbnail_id', true );
			$banner_image_id     = $thumbnail_id ? $thumbnail_id : 43462;
			$banner_image_url    = wpimage( $banner_image_id, array( 1920, 400 ), false, true, true, true, 85 );

			$banner_data = array(
				'show_banner'   => true,
				'category_name' => $category_term->name,
				'category_desc' => $category_term->description,
				'banner_image'  => $banner_image_url,
			);
		}
	}

	// Send success response.
	wp_send_json_success(
		array(
			'products_html'  => $products_html,
			'total_products' => $total_count,
			'showing_start'  => $showing_start,
			'showing_end'    => $showing_end,
			'max_pages'      => $max_pages,
			'current_page'   => $current_page,
			'has_prev'       => $current_page > 1,
			'has_next'       => $current_page < $max_pages,
			'banner_data'    => $banner_data,
		)
	);
}

/**
 * Add shop filter nonce to themeData localization
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_shop_filter_nonce( $scripts_localize ) {
	$scripts_localize['shop_filter_nonce'] = wp_create_nonce( 'ats_shop_filter' );
	return $scripts_localize;
}
add_filter( 'skyline_child_localizes', 'ats_add_shop_filter_nonce' );
