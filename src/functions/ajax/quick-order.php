<?php
/**
 * Admin Quick Order AJAX Handlers
 *
 * Handles product search and cart operations for the quick order panel.
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register AJAX actions for quick order
 */
add_action( 'wp_ajax_ats_quick_order_search', 'ats_handle_quick_order_search' );
add_action( 'wp_ajax_ats_get_cart_contents', 'ats_handle_get_cart_contents' );
add_action( 'wp_ajax_ats_search_customers', 'ats_handle_search_customers' );
add_action( 'wp_ajax_ats_set_order_customer', 'ats_handle_set_order_customer' );
add_action( 'wp_ajax_ats_clear_order_customer', 'ats_handle_clear_order_customer' );
add_action( 'wp_ajax_ats_clear_cart', 'ats_handle_clear_cart' );

/**
 * Handle quick order product search
 *
 * @return void
 */
function ats_handle_quick_order_search() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_quick_order' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ),
			403
		);
	}

	// Get search parameters
	$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$category = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;
	$brand    = isset( $_POST['brand'] ) ? absint( $_POST['brand'] ) : 0;
	$stock    = isset( $_POST['stock'] ) ? sanitize_text_field( wp_unslash( $_POST['stock'] ) ) : '';
	$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
	$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 12;

	// Build query args
	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	// Search query
	if ( ! empty( $search ) ) {
		$args['s'] = $search;

		// Also search in SKU
		add_filter( 'posts_search', 'ats_search_by_sku', 10, 2 );
	}

	// Tax query for category and brand
	$tax_query = array();

	if ( $category > 0 ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $category,
		);
	}

	if ( $brand > 0 ) {
		$tax_query[] = array(
			'taxonomy' => 'pwb-brand',
			'field'    => 'term_id',
			'terms'    => $brand,
		);
	}

	if ( ! empty( $tax_query ) ) {
		$tax_query['relation'] = 'AND';
		$args['tax_query']     = $tax_query;
	}

	// Meta query for stock status
	if ( ! empty( $stock ) ) {
		$args['meta_query'] = array(
			array(
				'key'   => '_stock_status',
				'value' => $stock,
			),
		);
	}

	// Execute query
	$query = new WP_Query( $args );

	// Remove SKU search filter
	remove_filter( 'posts_search', 'ats_search_by_sku', 10 );

	// Build products array
	$products = array();

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$product = wc_get_product( get_the_ID() );

			if ( ! $product ) {
				continue;
			}

			$products[] = array(
				'id'             => $product->get_id(),
				'name'           => $product->get_name(),
				'sku'            => $product->get_sku(),
				'price'          => $product->get_price_html(),
				'image'          => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ),
				'in_stock'       => $product->is_in_stock(),
				'stock_quantity' => $product->get_stock_quantity(),
				'variation_id'   => 0,
			);
		}
	}

	wp_reset_postdata();

	wp_send_json_success(
		array(
			'products' => $products,
			'total'    => $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
		)
	);
}

/**
 * Search by SKU in addition to title/content
 *
 * @param string   $search Search SQL.
 * @param WP_Query $query  Query object.
 * @return string Modified search SQL.
 */
function ats_search_by_sku( $search, $query ) {
	global $wpdb;

	if ( ! is_admin() && $query->is_main_query() && ! empty( $query->query_vars['s'] ) ) {
		$search_term = $wpdb->esc_like( $query->query_vars['s'] );

		$search .= " OR (
			{$wpdb->posts}.ID IN (
				SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key = '_sku'
				AND meta_value LIKE '%{$search_term}%'
			)
		)";
	}

	return $search;
}

/**
 * Get cart contents for display
 *
 * @return void
 */
function ats_handle_get_cart_contents() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_quick_order' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ),
			403
		);
	}

	$cart = WC()->cart;

	if ( ! $cart ) {
		wp_send_json_success(
			array(
				'items'    => array(),
				'count'    => 0,
				'subtotal' => wc_price( 0 ),
			)
		);
		return;
	}

	$items = array();

	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = $cart_item['data'];

		if ( ! $product ) {
			continue;
		}

		$items[] = array(
			'key'      => $cart_item_key,
			'name'     => $product->get_name(),
			'quantity' => $cart_item['quantity'],
			'price'    => wc_price( $product->get_price() ),
			'image'    => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ),
		);
	}

	wp_send_json_success(
		array(
			'items'    => $items,
			'count'    => $cart->get_cart_contents_count(),
			'subtotal' => wc_price( $cart->get_subtotal() ),
		)
	);
}

/**
 * Search for customers by email or last name
 *
 * @return void
 */
function ats_handle_search_customers() {
	// Verify nonce and admin capability
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_quick_order' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'skylinewp-dev-child' ) ), 403 );
	}

	$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

	if ( empty( $search ) ) {
		wp_send_json_success( array( 'customers' => array() ) );
	}

	// Search for customers
	$args = array(
		'search'         => '*' . $search . '*',
		'search_columns' => array( 'user_email', 'user_login', 'display_name' ),
		'number'         => 10,
		'orderby'        => 'display_name',
		'order'          => 'ASC',
		'fields'         => array( 'ID', 'user_email', 'display_name' ),
	);

	// Also search by last name
	add_action( 'pre_user_query', 'ats_search_users_by_last_name' );
	$users = get_users( $args );
	remove_action( 'pre_user_query', 'ats_search_users_by_last_name' );

	$customers = array();
	foreach ( $users as $user ) {
		$customers[] = array(
			'id'           => $user->ID,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
		);
	}

	wp_send_json_success( array( 'customers' => $customers ) );
}

/**
 * Modify user query to search by last name meta
 *
 * @param WP_User_Query $query User query object.
 * @return void
 */
function ats_search_users_by_last_name( $query ) {
	global $wpdb;

	$search_term = $query->get( 'search' );
	if ( ! empty( $search_term ) ) {
		$search_term = trim( $search_term, '*' );
		$query->query_where .= " OR EXISTS (
			SELECT 1 FROM {$wpdb->usermeta}
			WHERE {$wpdb->usermeta}.user_id = {$wpdb->users}.ID
			AND {$wpdb->usermeta}.meta_key IN ('last_name', 'billing_last_name')
			AND {$wpdb->usermeta}.meta_value LIKE '%" . $wpdb->esc_like( $search_term ) . "%'
		)";
	}
}

/**
 * Set the customer for the order in session
 *
 * @return void
 */
function ats_handle_set_order_customer() {
	// Verify nonce and admin capability
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_quick_order' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'skylinewp-dev-child' ) ), 403 );
	}

	$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;

	if ( $customer_id > 0 ) {
		WC()->session->set( 'ats_order_for_customer_id', $customer_id );
		wp_send_json_success();
	}

	wp_send_json_error( array( 'message' => __( 'Invalid customer ID.', 'skylinewp-dev-child' ) ) );
}

/**
 * Clear the customer selection from session
 *
 * @return void
 */
function ats_handle_clear_order_customer() {
	// Verify nonce and admin capability
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_quick_order' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'skylinewp-dev-child' ) ), 403 );
	}

	WC()->session->set( 'ats_order_for_customer_id', null );
	wp_send_json_success();
}

/**
 * Clear the cart
 *
 * @return void
 */
function ats_handle_clear_cart() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats-cart-nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ), 403 );
	}

	WC()->cart->empty_cart();
	wp_send_json_success();
}

/**
 * Populate checkout fields with selected customer data
 *
 * @param mixed  $value     Field value.
 * @param string $input     Field key.
 * @return mixed Modified field value.
 */
function ats_populate_checkout_customer_fields( $value, $input ) {
	if ( ! is_admin() && is_checkout() ) {
		$customer_id = WC()->session->get( 'ats_order_for_customer_id' );

		if ( $customer_id ) {
			$customer = new WC_Customer( $customer_id );

			// Map fields - always return customer value even if empty
			// This ensures admin's data is overridden/cleared
			$field_map = array(
				'billing_first_name'  => $customer->get_billing_first_name(),
				'billing_last_name'   => $customer->get_billing_last_name(),
				'billing_email'       => $customer->get_billing_email(),
				'billing_phone'       => $customer->get_billing_phone(),
				'billing_company'     => $customer->get_billing_company(),
				'billing_address_1'   => $customer->get_billing_address_1(),
				'billing_address_2'   => $customer->get_billing_address_2(),
				'billing_city'        => $customer->get_billing_city(),
				'billing_state'       => $customer->get_billing_state(),
				'billing_postcode'    => $customer->get_billing_postcode(),
				'billing_country'     => $customer->get_billing_country(),
				'shipping_first_name' => $customer->get_shipping_first_name(),
				'shipping_last_name'  => $customer->get_shipping_last_name(),
				'shipping_company'    => $customer->get_shipping_company(),
				'shipping_address_1'  => $customer->get_shipping_address_1(),
				'shipping_address_2'  => $customer->get_shipping_address_2(),
				'shipping_city'       => $customer->get_shipping_city(),
				'shipping_state'      => $customer->get_shipping_state(),
				'shipping_postcode'   => $customer->get_shipping_postcode(),
				'shipping_country'    => $customer->get_shipping_country(),
			);

			// Return customer value if field exists in map
			// This will be empty string if customer doesn't have data for this field
			if ( isset( $field_map[ $input ] ) ) {
				return $field_map[ $input ];
			}
		}
	}

	return $value;
}
add_filter( 'woocommerce_checkout_get_value', 'ats_populate_checkout_customer_fields', 10, 2 );

/**
 * Assign order to the selected customer
 *
 * @param int $order_id Order ID.
 * @return void
 */
function ats_assign_order_to_customer( $order_id ) {
	$customer_id = WC()->session->get( 'ats_order_for_customer_id' );

	if ( $customer_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->set_customer_id( $customer_id );
			$order->save();

			// Clear the session
			WC()->session->set( 'ats_order_for_customer_id', null );
		}
	}
}
add_action( 'woocommerce_checkout_order_created', 'ats_assign_order_to_customer', 10, 1 );

/**
 * Add quick order nonce to themeData localization
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_quick_order_nonce( $scripts_localize ) {
	$scripts_localize['quick_order_nonce'] = wp_create_nonce( 'ats_quick_order' );
	return $scripts_localize;
}
add_filter( 'skyline_child_localizes', 'ats_add_quick_order_nonce' );
