<?php
/**
 * Back in Stock Notifications System
 *
 * Handles database, subscriptions, and email notifications for out-of-stock products
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Create database table for back-in-stock subscriptions
 */
function ats_create_back_in_stock_table() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'ats_back_in_stock';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		product_id bigint(20) NOT NULL,
		variation_id bigint(20) DEFAULT 0,
		user_id bigint(20) DEFAULT 0,
		email varchar(100) NOT NULL,
		subscribed_date datetime DEFAULT CURRENT_TIMESTAMP,
		notified tinyint(1) DEFAULT 0,
		notified_date datetime DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY product_id (product_id),
		KEY variation_id (variation_id),
		KEY email (email),
		KEY notified (notified)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
add_action( 'after_switch_theme', 'ats_create_back_in_stock_table' );

/**
 * Subscribe user/email to back-in-stock notifications
 */
function ats_ajax_subscribe_back_in_stock() {
	check_ajax_referer( 'ats-back-in-stock-nonce', 'nonce' );

	global $wpdb;
	$table_name   = $wpdb->prefix . 'ats_back_in_stock';
	$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
	$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$user_id      = get_current_user_id();

	// Validate
	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => 'Invalid product' ) );
	}

	// If logged in, use user's email
	if ( $user_id ) {
		$user  = get_userdata( $user_id );
		$email = $user->user_email;
	} elseif ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Please enter a valid email address' ) );
	}

	// Check if already subscribed (match on variation_id too)
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM $table_name WHERE product_id = %d AND variation_id = %d AND email = %s AND notified = 0",
			$product_id,
			$variation_id,
			$email
		)
	);

	if ( $existing ) {
		wp_send_json_error( array( 'message' => 'You are already subscribed to notifications for this product' ) );
	}

	// Insert subscription
	$inserted = $wpdb->insert(
		$table_name,
		array(
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'user_id'      => $user_id,
			'email'        => $email,
		),
		array( '%d', '%d', '%d', '%s' )
	);

	if ( $inserted ) {
		$success_message = get_field( 'back_in_stock_success_message', 'option' ) ?: 'Thank you! We\'ll notify you when this product is back in stock.';
		wp_send_json_success( array( 'message' => $success_message ) );
	} else {
		wp_send_json_error( array( 'message' => 'Failed to subscribe. Please try again.' ) );
	}
}
add_action( 'wp_ajax_ats_subscribe_back_in_stock', 'ats_ajax_subscribe_back_in_stock' );
add_action( 'wp_ajax_nopriv_ats_subscribe_back_in_stock', 'ats_ajax_subscribe_back_in_stock' );

/**
 * Send back-in-stock notifications when product stock changes
 */
function ats_check_stock_change( $product_id ) {
	$product = wc_get_product( $product_id );

	if ( ! $product || ! $product->is_in_stock() ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'ats_back_in_stock';

	// Get all unnotified subscribers.
	// Match on variation_id if this is a variation, or product_id for simple products.
	if ( $product->is_type( 'variation' ) ) {
		$subscribers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE variation_id = %d AND notified = 0",
				$product_id
			)
		);
	} else {
		$subscribers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE product_id = %d AND variation_id = 0 AND notified = 0",
				$product_id
			)
		);
	}

	if ( empty( $subscribers ) ) {
		return;
	}

	// Get email settings
	$subject_template = get_field( 'back_in_stock_email_subject', 'option' ) ?: '{product_name} is back in stock!';
	$message_template = get_field( 'back_in_stock_email_message', 'option' ) ?: "Hello {user_name},\n\nGreat news! {product_name} has returned to stock.\n\nClick here to view: {product_link}\n\nThank you for your patience!";

	$product_name = $product->get_name();
	$product_link = $product->get_permalink();

	// Send emails to all subscribers
	foreach ( $subscribers as $subscriber ) {
		// Get user name
		if ( $subscriber->user_id ) {
			$user      = get_userdata( $subscriber->user_id );
			$user_name = $user ? $user->first_name ?: $user->display_name : 'Customer';
		} else {
			$user_name = 'Customer';
		}

		// Replace variables
		$subject = str_replace( '{product_name}', $product_name, $subject_template );
		$message = str_replace(
			array( '{user_name}', '{product_name}', '{product_link}' ),
			array( $user_name, $product_name, $product_link ),
			$message_template
		);

		// Send email
		$sent = wp_mail( $subscriber->email, $subject, $message );

		// Mark as notified if sent successfully
		if ( $sent ) {
			$wpdb->update(
				$table_name,
				array(
					'notified'      => 1,
					'notified_date' => current_time( 'mysql' ),
				),
				array( 'id' => $subscriber->id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		}
	}
}
add_action( 'woocommerce_product_set_stock', 'ats_check_stock_change' );
add_action( 'woocommerce_variation_set_stock', 'ats_check_stock_change' );

/**
 * Add back-in-stock nonce to localized script data
 */
add_filter( 'skyline_child_localizes', 'ats_add_back_in_stock_nonce', 10 );

function ats_add_back_in_stock_nonce( $data ) {
	if ( is_product() ) {
		$data['back_in_stock_nonce'] = wp_create_nonce( 'ats-back-in-stock-nonce' );
	}
	return $data;
}

