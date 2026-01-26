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

	// Add wrapper class
	if ( isset( $args['class'] ) ) {
		$args['class'][] = 'mb-4';
	} else {
		$args['class'] = array( 'mb-4' );
	}

	return $args;
}
