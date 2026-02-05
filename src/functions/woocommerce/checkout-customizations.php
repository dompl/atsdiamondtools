<?php
/**
 * Checkout Customizations
 *
 * Custom styling and modifications for the checkout page
 *
 * @package skylinewp-dev-child
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove default coupon form location
 * We're manually adding it in form-checkout.php
 */
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

/**
 * Remove default login form location
 * We're using a custom modal login instead
 */
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );

/**
 * Reorder checkout fields for clean 2-column layout
 */
add_filter( 'woocommerce_checkout_fields', 'ats_reorder_checkout_fields' );
function ats_reorder_checkout_fields( $fields ) {
	// Reorder billing fields for logical flow in 2-column grid
	if ( isset( $fields['billing'] ) ) {
		$fields['billing']['billing_first_name']['priority'] = 10;
		$fields['billing']['billing_last_name']['priority']  = 20;
		$fields['billing']['billing_company']['priority']    = 30;
		$fields['billing']['billing_country']['priority']    = 40;
		$fields['billing']['billing_address_1']['priority']  = 50;
		$fields['billing']['billing_address_2']['priority']  = 60;
		$fields['billing']['billing_city']['priority']       = 70;
		$fields['billing']['billing_state']['priority']      = 80;
		$fields['billing']['billing_postcode']['priority']   = 90;
		$fields['billing']['billing_phone']['priority']      = 100;
		$fields['billing']['billing_email']['priority']      = 110;
	}

	// Reorder shipping fields
	if ( isset( $fields['shipping'] ) ) {
		$fields['shipping']['shipping_first_name']['priority'] = 10;
		$fields['shipping']['shipping_last_name']['priority']  = 20;
		$fields['shipping']['shipping_company']['priority']    = 30;
		$fields['shipping']['shipping_country']['priority']    = 40;
		$fields['shipping']['shipping_address_1']['priority']  = 50;
		$fields['shipping']['shipping_address_2']['priority']  = 60;
		$fields['shipping']['shipping_city']['priority']       = 70;
		$fields['shipping']['shipping_state']['priority']      = 80;
		$fields['shipping']['shipping_postcode']['priority']   = 90;
	}

	return $fields;
}

/**
 * Add Tailwind classes to checkout form fields
 */
add_filter( 'woocommerce_form_field_args', 'ats_custom_checkout_field_args', 10, 3 );
function ats_custom_checkout_field_args( $args, $key, $value ) {
	// Only apply on checkout page
	if ( ! is_checkout() ) {
		return $args;
	}

	// Add Tailwind input classes
	$input_classes = array(
		'w-full',
		'px-4',
		'py-3',
		'border',
		'border-gray-300',
		'rounded-lg',
		'text-sm',
		'text-ats-dark',
		'focus:ring-2',
		'focus:ring-ats-yellow',
		'focus:border-ats-yellow',
		'transition-colors',
		'duration-200',
	);

	if ( isset( $args['input_class'] ) ) {
		$args['input_class'] = array_merge( $args['input_class'], $input_classes );
	} else {
		$args['input_class'] = $input_classes;
	}

	// Add Tailwind label classes
	$label_classes = array(
		'block',
		'text-sm',
		'font-medium',
		'text-ats-dark',
		'mb-2',
	);

	if ( isset( $args['label_class'] ) ) {
		$args['label_class'] = array_merge( $args['label_class'], $label_classes );
	} else {
		$args['label_class'] = $label_classes;
	}

	// Define which fields should span full width on desktop
	$full_width_fields = array(
		'billing_country',
		'billing_company',
		'billing_city',
		'shipping_country',
		'shipping_company',
		'shipping_city',
		'order_comments',
	);

	// Add wrapper class and grid column span
	if ( isset( $args['class'] ) ) {
		$args['class'][] = 'mb-0'; // Remove bottom margin (grid gap handles spacing)
	} else {
		$args['class'] = array( 'mb-0' );
	}

	// Add full-width class for specific fields on desktop
	if ( in_array( $key, $full_width_fields, true ) ) {
		$args['class'][] = 'lg:col-span-2';
	}

	// CraftyClicks postcode lookup fields - always full width
	if ( strpos( $key, 'crafty_' ) === 0 ||
	     $key === 'billing_postcode_lookup' ||
	     $key === 'shipping_postcode_lookup' ) {
		$args['class'][] = 'lg:col-span-2';
	}

	return $args;
}
