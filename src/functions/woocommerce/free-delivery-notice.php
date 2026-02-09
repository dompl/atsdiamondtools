<?php
/**
 * Free Delivery Notice
 *
 * Dynamic "Free Delivery" notification that reads from WooCommerce
 * free shipping settings. Displays in header, cart, and checkout.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get free shipping configuration from WooCommerce shipping zones.
 *
 * Finds the first enabled free shipping method that has a min_amount requirement.
 *
 * @return array { enabled: bool, threshold: float, ignore_discounts: string }
 */
function ats_get_free_shipping_data() {
	static $cached = null;
	if ( $cached !== null ) {
		return $cached;
	}

	$result = [
		'enabled'          => false,
		'threshold'        => 0,
		'ignore_discounts' => 'no',
	];

	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		$cached = $result;
		return $result;
	}

	$zones = \WC_Shipping_Zones::get_zones();
	// Also check the "Rest of the World" zone (ID 0).
	$zones[0] = ( new \WC_Shipping_Zone( 0 ) )->get_data();
	$zones[0]['shipping_methods'] = ( new \WC_Shipping_Zone( 0 ) )->get_shipping_methods();

	foreach ( $zones as $zone ) {
		$methods = isset( $zone['shipping_methods'] ) ? $zone['shipping_methods'] : [];
		foreach ( $methods as $method ) {
			if ( 'free_shipping' !== $method->id ) {
				continue;
			}
			if ( 'yes' !== $method->enabled ) {
				continue;
			}

			$requires   = $method->get_option( 'requires', '' );
			$min_amount = (float) $method->get_option( 'min_amount', 0 );

			// Only handle methods that require a minimum order amount.
			if ( ! in_array( $requires, [ 'min_amount', 'either', 'both' ], true ) || $min_amount <= 0 ) {
				continue;
			}

			$result = [
				'enabled'          => true,
				'threshold'        => $min_amount,
				'ignore_discounts' => $method->get_option( 'ignore_discounts', 'no' ),
			];
			break 2;
		}
	}

	$cached = $result;
	return $result;
}

/**
 * Calculate current cart state against the free shipping threshold.
 *
 * Replicates the subtotal logic from WC_Shipping_Free_Shipping::is_available().
 *
 * @return array { show: bool, status: string, message: string, remaining: float, percent: float }
 */
function ats_get_free_delivery_notice_data() {
	$shipping = ats_get_free_shipping_data();

	if ( ! $shipping['enabled'] ) {
		return [
			'show'      => false,
			'status'    => 'disabled',
			'message'   => '',
			'remaining' => 0,
			'percent'   => 0,
		];
	}

	$threshold = $shipping['threshold'];

	// No cart or empty cart.
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return [
			'show'      => true,
			'status'    => 'no_cart',
			'message'   => sprintf( 'Free delivery on orders over %s', wc_price( $threshold ) ),
			'remaining' => $threshold,
			'percent'   => 0,
		];
	}

	// Calculate subtotal matching WC_Shipping_Free_Shipping logic.
	if ( 'yes' === $shipping['ignore_discounts'] ) {
		$total = WC()->cart->get_subtotal();
		if ( WC()->cart->display_prices_including_tax() ) {
			$total += WC()->cart->get_subtotal_tax();
		}
	} else {
		$total = WC()->cart->get_subtotal();
		if ( WC()->cart->display_prices_including_tax() ) {
			$total += WC()->cart->get_subtotal_tax();
		}
		$total -= WC()->cart->get_discount_total();
		if ( WC()->cart->display_prices_including_tax() ) {
			$total -= WC()->cart->get_discount_tax();
		}
	}

	$remaining = max( 0, $threshold - $total );
	$percent   = min( 100, ( $total / $threshold ) * 100 );

	if ( $total >= $threshold ) {
		return [
			'show'      => true,
			'status'    => 'qualified',
			'message'   => 'You qualify for free delivery!',
			'remaining' => 0,
			'percent'   => 100,
		];
	}

	return [
		'show'      => true,
		'status'    => 'remaining',
		'message'   => sprintf( 'Add %s more to get free delivery', wc_price( $remaining ) ),
		'remaining' => $remaining,
		'percent'   => round( $percent, 1 ),
	];
}

/**
 * Render the free delivery notice HTML.
 *
 * @param string $context One of: header, mobile, cart, checkout.
 */
function ats_render_free_delivery_notice( $context = 'header' ) {
	$data = ats_get_free_delivery_notice_data();

	if ( ! $data['show'] ) {
		return;
	}

	$status  = $data['status'];
	$message = $data['message'];
	$percent = $data['percent'];

	switch ( $context ) {
		case 'header':
			ats_render_header_notice( $message, $status, $percent );
			break;
		case 'mobile':
			ats_render_mobile_notice( $message, $status, $percent );
			break;
		case 'cart':
			ats_render_cart_notice( $message, $status, $percent );
			break;
		case 'checkout':
			ats_render_checkout_notice( $message, $status, $percent );
			break;
	}
}

/**
 * Header (desktop top bar) - compact inline notice.
 */
function ats_render_header_notice( $message, $status, $percent ) {
	$icon  = $status === 'qualified'
		? '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
		: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>';

	$color = $status === 'qualified' ? 'text-green-600' : 'text-ats-brand';
	?>
	<div class="flex items-center gap-1.5 text-xs font-semibold <?php echo esc_attr( $color ); ?> js-free-delivery-notice" data-context="header">
		<?php echo $icon; ?>
		<span class="js-free-delivery-message"><?php echo wp_kses_post( $message ); ?></span>
	</div>
	<?php
}

/**
 * Mobile drawer - white text on dark background.
 */
function ats_render_mobile_notice( $message, $status, $percent ) {
	$icon  = $status === 'qualified'
		? '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
		: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>';

	$color = $status === 'qualified' ? 'text-green-400' : 'text-ats-brand';
	?>
	<div class="flex items-center gap-2 text-sm font-semibold <?php echo esc_attr( $color ); ?> js-free-delivery-notice" data-context="mobile">
		<?php echo $icon; ?>
		<span class="js-free-delivery-message"><?php echo wp_kses_post( $message ); ?></span>
	</div>
	<?php
}

/**
 * Cart page - card with progress bar above cart totals.
 */
function ats_render_cart_notice( $message, $status, $percent ) {
	if ( $status === 'qualified' ) {
		$bg    = 'bg-green-50 border-green-200';
		$text  = 'text-green-800';
		$bar   = 'bg-green-500';
	} elseif ( $status === 'remaining' ) {
		$bg    = 'bg-primary-300/20 border-ats-brand/30';
		$text  = 'text-ats-brand';
		$bar   = 'bg-ats-brand';
	} else {
		$bg    = 'bg-gray-50 border-gray-200';
		$text  = 'text-gray-700';
		$bar   = 'bg-gray-400';
	}
	?>
	<div class="mb-4 p-4 border rounded-lg <?php echo esc_attr( "$bg $text" ); ?> js-free-delivery-notice" data-context="cart">
		<div class="flex items-center gap-2 mb-2">
			<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
			</svg>
			<span class="text-sm font-semibold js-free-delivery-message"><?php echo wp_kses_post( $message ); ?></span>
		</div>
		<div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
			<div class="h-2 rounded-full transition-all duration-500 js-free-delivery-bar <?php echo esc_attr( $bar ); ?>" style="width: <?php echo esc_attr( $percent ); ?>%"></div>
		</div>
	</div>
	<?php
}

/**
 * Checkout page - table row in review order table.
 */
function ats_render_checkout_notice( $message, $status, $percent ) {
	$color = $status === 'qualified' ? 'text-green-600' : 'text-ats-brand';
	$bar   = $status === 'qualified' ? 'bg-green-500' : 'bg-ats-brand';
	?>
	<tr class="free-delivery-notice js-free-delivery-notice" data-context="checkout">
		<th><?php esc_html_e( 'Free Delivery', 'woocommerce' ); ?></th>
		<td>
			<div class="<?php echo esc_attr( $color ); ?> text-sm font-semibold js-free-delivery-message"><?php echo wp_kses_post( $message ); ?></div>
			<div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 overflow-hidden">
				<div class="h-1.5 rounded-full transition-all duration-500 js-free-delivery-bar <?php echo esc_attr( $bar ); ?>" style="width: <?php echo esc_attr( $percent ); ?>%"></div>
			</div>
		</td>
	</tr>
	<?php
}

// ---------------------------------------------------------------------------
// WooCommerce Hook Registrations
// ---------------------------------------------------------------------------

/**
 * Cart page: render notice above cart totals.
 */
add_action( 'woocommerce_before_cart_totals', function () {
	ats_render_free_delivery_notice( 'cart' );
} );

/**
 * Checkout page: render notice above the order total row.
 */
add_action( 'woocommerce_review_order_before_order_total', function () {
	ats_render_free_delivery_notice( 'checkout' );
} );

// ---------------------------------------------------------------------------
// AJAX Endpoint
// ---------------------------------------------------------------------------

/**
 * AJAX handler to return current free delivery notice data.
 */
function ats_ajax_get_free_delivery_data() {
	check_ajax_referer( 'theme_nonce', 'nonce' );

	$data = ats_get_free_delivery_notice_data();

	wp_send_json_success( $data );
}
add_action( 'wp_ajax_ats_get_free_delivery_data', 'ats_ajax_get_free_delivery_data' );
add_action( 'wp_ajax_nopriv_ats_get_free_delivery_data', 'ats_ajax_get_free_delivery_data' );

// ---------------------------------------------------------------------------
// Localize Data
// ---------------------------------------------------------------------------

/**
 * Add free shipping threshold and initial notice data to themeData.
 */
add_filter( 'skyline_child_localizes', function ( $data ) {
	$shipping = ats_get_free_shipping_data();

	if ( $shipping['enabled'] ) {
		$data['free_shipping_threshold'] = $shipping['threshold'];
		$data['free_delivery_notice']    = ats_get_free_delivery_notice_data();
	}

	return $data;
} );
