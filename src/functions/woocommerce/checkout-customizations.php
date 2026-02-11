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

	// CraftyClicks result display fields - full width
	if ( $key === 'billing_postcode_lookup' ||
	     $key === 'shipping_postcode_lookup' ) {
		$args['class'][] = 'lg:col-span-2';
	}

	return $args;
}

/**
 * Move Stripe Express Checkout (Apple Pay, Google Pay) above Place Order button
 *
 * Stripe hooks into 'woocommerce_checkout_before_customer_details' at priority 1,
 * placing Express Checkout at the top of the form. We remove it and re-add to
 * 'woocommerce_review_order_before_submit' so it appears above "Place Order".
 *
 * Uses wp_loaded to ensure all plugins have loaded their hooks.
 */
add_action( 'wp_loaded', function() {
	// Move Express Checkout Element (newer Stripe plugin)
	if ( function_exists( 'woocommerce_gateway_stripe' ) ) {
		$stripe = woocommerce_gateway_stripe();
		if ( $stripe && isset( $stripe->express_checkout_configuration ) ) {
			$express = $stripe->express_checkout_configuration;
			remove_action( 'woocommerce_checkout_before_customer_details', array( $express, 'display_express_checkout_button_html' ), 1 );
			add_action( 'woocommerce_review_order_before_submit', array( $express, 'display_express_checkout_button_html' ), 1 );
		}
	}

	// Also handle legacy Payment Request API (older Stripe versions)
	if ( class_exists( 'WC_Stripe_Payment_Request' ) ) {
		$payment_request = WC_Stripe_Payment_Request::instance();
		remove_action( 'woocommerce_checkout_before_customer_details', array( $payment_request, 'display_payment_request_button_html' ), 1 );
		add_action( 'woocommerce_review_order_before_submit', array( $payment_request, 'display_payment_request_button_html' ), 1 );
	}
} );

/**
 * Make Place Order button text uppercase
 */
add_filter( 'woocommerce_order_button_text', function( $text ) {
	return strtoupper( $text );
} );

/**
 * Add CSS to fix checkout layout issues
 */
add_action( 'wp_head', function() {
	if ( ! is_checkout() ) {
		return;
	}
	?>
	<style>
		/* ============================
		   Checkout Grid Layout
		   ============================ */
		.rfs-ref-checkout-layout {
			display: grid !important;
		}

		/* Prevent grid items from overflowing their columns */
		.rfs-ref-customer-details,
		.rfs-ref-order-review-sidebar {
			min-width: 0;
		}

		/* Ensure payment methods stay within the order review area */
		#order_review {
			width: 100%;
		}

		/* Prevent order review from collapsing */
		.woocommerce-checkout-review-order {
			min-height: 200px;
		}

		/* ============================
		   Express Checkout Styling
		   ============================ */
		#wc-stripe-express-checkout-button-separator {
			margin: 0 !important;
			text-align: center;
			color: #6b7280;
			font-size: 0.875rem;
		}

		/* ============================
		   Dual Sticky Columns
		   ============================ */
		@media (min-width: 1024px) {
			.rfs-ref-checkout-layout {
				align-items: start;
			}
		}
	</style>

	<script>
		(function() {
			// Dual-sticky column behavior
			// The SHORTER column should be sticky so the taller one scrolls naturally
			function updateStickyColumns() {
				if (window.innerWidth < 1024) return;

				var left = document.querySelector('.rfs-ref-customer-details');
				var right = document.querySelector('.rfs-ref-order-review-sidebar');
				if (!left || !right) return;

				// Reset to measure true heights
				left.style.position = 'relative';
				left.style.top = '';
				right.style.position = 'relative';
				right.style.top = '';

				var leftHeight = left.offsetHeight;
				var rightHeight = right.offsetHeight;
				var diff = Math.abs(leftHeight - rightHeight);

				// Only apply sticky if there's a meaningful height difference (>50px)
				if (diff < 50) return;

				if (leftHeight > rightHeight) {
					// Left is taller - right sticks
					right.style.position = 'sticky';
					right.style.top = '2rem';
				} else {
					// Right is taller - left sticks
					left.style.position = 'sticky';
					left.style.top = '2rem';
				}
			}

			// Run on load and checkout updates
			document.addEventListener('DOMContentLoaded', function() {
				updateStickyColumns();
				window.addEventListener('resize', updateStickyColumns);
				jQuery(document.body).on('updated_checkout', function() {
					setTimeout(updateStickyColumns, 100);
				});
			});
		})();
	</script>
	<?php
}, 999 );
