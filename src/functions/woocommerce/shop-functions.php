<?php
/**
 * Shop Page Helper Functions
 *
 * Utility functions for shop and category pages
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get product categories for sidebar display
 *
 * @param int $current_category_id Currently selected category ID.
 * @return array Categories with counts and hierarchy.
 */
function ats_get_product_categories_for_sidebar( $current_category_id = 0 ) {
	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'parent'     => 0, // Only top-level categories.
		)
	);

	if ( is_wp_error( $categories ) || empty( $categories ) ) {
		return array();
	}

	$formatted_categories = array();

	foreach ( $categories as $category ) {
		$formatted_categories[] = array(
			'id'         => $category->term_id,
			'name'       => $category->name,
			'slug'       => $category->slug,
			'count'      => $category->count,
			'url'        => get_term_link( $category ),
			'is_current' => ( $current_category_id === $category->term_id ),
			'children'   => ats_get_child_categories( $category->term_id, $current_category_id ),
		);
	}

	return $formatted_categories;
}

/**
 * Get child categories
 *
 * @param int $parent_id Parent category ID.
 * @param int $current_category_id Currently selected category ID.
 * @return array Child categories.
 */
function ats_get_child_categories( $parent_id, $current_category_id = 0 ) {
	$children = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'parent'     => $parent_id,
		)
	);

	if ( is_wp_error( $children ) || empty( $children ) ) {
		return array();
	}

	$formatted_children = array();

	foreach ( $children as $child ) {
		$formatted_children[] = array(
			'id'         => $child->term_id,
			'name'       => $child->name,
			'slug'       => $child->slug,
			'count'      => $child->count,
			'url'        => get_term_link( $child ),
			'is_current' => ( $current_category_id === $child->term_id ),
		);
	}

	return $formatted_children;
}

/**
 * Get price range for products
 *
 * @param int $category_id Category ID (0 for all products).
 * @return array Min and max prices.
 */
function ats_get_price_range_for_products( $category_id = 0 ) {
	global $wpdb;

	$where_clause = '';

	if ( $category_id > 0 ) {
		$where_clause = $wpdb->prepare(
			"AND {$wpdb->posts}.ID IN (
				SELECT object_id FROM {$wpdb->term_relationships}
				WHERE term_taxonomy_id = %d
			)",
			$category_id
		);
	}

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$prices = $wpdb->get_row(
		"SELECT
			MIN(CAST(meta_value AS DECIMAL(10,2))) as min_price,
			MAX(CAST(meta_value AS DECIMAL(10,2))) as max_price
		FROM {$wpdb->postmeta}
		INNER JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
		WHERE meta_key = '_price'
		AND {$wpdb->posts}.post_type = 'product'
		AND {$wpdb->posts}.post_status = 'publish'
		{$where_clause}
		AND meta_value != ''
		AND meta_value > 0"
	);
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! $prices || ( $prices->min_price === null && $prices->max_price === null ) ) {
		return array(
			'min' => 0,
			'max' => 1000,
		);
	}

	return array(
		'min' => floor( (float) $prices->min_price ),
		'max' => ceil( (float) $prices->max_price ),
	);
}

/**
 * Render products grid using WP_Query and ats_product shortcode
 *
 * @param array $args Query arguments.
 * @return string Products HTML.
 */
function ats_render_product_grid( $args = array() ) {
	$defaults = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'paged'          => 1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$query_args = wp_parse_args( $args, $defaults );

	// Handle category filtering.
	if ( ! empty( $args['category'] ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => absint( $args['category'] ),
			),
		);
	}

	// Handle price filtering.
	if ( isset( $args['min_price'] ) || isset( $args['max_price'] ) ) {
		$query_args['meta_query'] = array(
			'relation' => 'AND',
		);

		if ( isset( $args['min_price'] ) && $args['min_price'] > 0 ) {
			$query_args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => floatval( $args['min_price'] ),
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		if ( isset( $args['max_price'] ) && $args['max_price'] > 0 ) {
			$query_args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => floatval( $args['max_price'] ),
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}
	}

	// Handle custom orderby.
	if ( ! empty( $args['orderby'] ) ) {
		$query_args = array_merge( $query_args, ats_get_catalog_ordering_args( $args['orderby'] ) );
	}

	$products_query = new WP_Query( $query_args );

	ob_start();

	if ( $products_query->have_posts() ) {
		while ( $products_query->have_posts() ) {
			$products_query->the_post();
			echo do_shortcode( '[ats_product id="' . get_the_ID() . '" display="1"]' );
		}
		wp_reset_postdata();
	} else {
		echo '<div class="rfs-ref-no-products col-span-full text-center py-12">';
		echo '<p class="text-gray-600 text-lg">' . esc_html__( 'No products found matching your criteria.', 'skylinewp-dev-child' ) . '</p>';
		echo '</div>';
	}

	return ob_get_clean();
}

/**
 * Get catalog ordering arguments
 *
 * @param string $orderby Ordering option.
 * @return array WP_Query arguments.
 */
function ats_get_catalog_ordering_args( $orderby = 'default' ) {
	$ordering_args = array();

	switch ( $orderby ) {
		case 'popularity':
			$ordering_args['orderby']  = 'meta_value_num';
			$ordering_args['order']    = 'DESC';
			$ordering_args['meta_key'] = 'total_sales';
			break;

		case 'rating':
			$ordering_args['orderby']  = 'meta_value_num';
			$ordering_args['order']    = 'DESC';
			$ordering_args['meta_key'] = '_wc_average_rating';
			break;

		case 'date':
			$ordering_args['orderby'] = 'date';
			$ordering_args['order']   = 'DESC';
			break;

		case 'price':
			$ordering_args['orderby']  = 'meta_value_num';
			$ordering_args['order']    = 'ASC';
			$ordering_args['meta_key'] = '_price';
			break;

		case 'price-desc':
			$ordering_args['orderby']  = 'meta_value_num';
			$ordering_args['order']    = 'DESC';
			$ordering_args['meta_key'] = '_price';
			break;

		case 'default':
		default:
			$ordering_args['orderby'] = 'menu_order title';
			$ordering_args['order']   = 'ASC';
			break;
	}

	return $ordering_args;
}

/**
 * Get available sorting options for dropdown
 *
 * @return array Sorting options.
 */
function ats_get_sorting_options() {
	return array(
		'default'    => __( 'Default sorting', 'skylinewp-dev-child' ),
		'popularity' => __( 'Sort by popularity', 'skylinewp-dev-child' ),
		'rating'     => __( 'Sort by average rating', 'skylinewp-dev-child' ),
		'date'       => __( 'Sort by latest', 'skylinewp-dev-child' ),
		'price'      => __( 'Price: Low to High', 'skylinewp-dev-child' ),
		'price-desc' => __( 'Price: High to Low', 'skylinewp-dev-child' ),
	);
}

/**
 * Get current sorting label
 *
 * @param string $current_sort Current sorting option.
 * @return string Sorting label.
 */
function ats_get_current_sorting_label( $current_sort = 'default' ) {
	$options = ats_get_sorting_options();
	return isset( $options[ $current_sort ] ) ? $options[ $current_sort ] : $options['default'];
}
