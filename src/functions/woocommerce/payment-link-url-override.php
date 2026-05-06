<?php
/**
 * Route "Pay for this order" links to the wc-virtual-terminal token-authenticated
 * payment page instead of WooCommerce's /checkout/order-pay/ endpoint.
 *
 * The default WC pay-for-order page calls current_user_can( 'pay_for_order' ),
 * which fails for guest visitors of orders assigned to a registered customer —
 * WC then renders a login form. Customers receiving an emailed invoice with a
 * payment link have no account and cannot proceed.
 *
 * The wc-virtual-terminal plugin already exposes a public, token-authenticated
 * payment page at /virtual-terminal-pay/{token}/ that requires no login. When
 * an order has a `_wcvt_payment_token` meta (set by the "Send invoice with
 * payment link" order action), every "Pay for this order" link site-wide is
 * rewritten to point there instead.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve a wcvt token URL for an order, or return null if the token is missing
 * or the link is not in a payable state.
 *
 * @param WC_Order $order The order.
 * @return string|null wcvt payment URL or null to fall through.
 */
function ats_wcvt_resolve_payment_url( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return null;
	}

	if ( ! class_exists( 'WCVT_Payment_Links' ) ) {
		return null;
	}

	$token = $order->get_meta( '_wcvt_payment_token' );
	if ( empty( $token ) ) {
		return null;
	}

	$wcvt = WCVT_Payment_Links::instance();
	$link = $wcvt->get_link_by_token( $token );

	if ( ! $link || 'pending' !== $link->status ) {
		return null;
	}

	if ( ! empty( $link->expires_at ) && strtotime( $link->expires_at ) < time() ) {
		return null;
	}

	return $wcvt->get_payment_url( $token );
}

/**
 * Replace the WC pay-for-order URL with the wcvt token URL when applicable.
 *
 * Hooks into every call to WC_Order::get_checkout_payment_url() — used in the
 * Customer Invoice email body, My Account orders list, thank-you page, and the
 * admin order screen "Customer payment page" link.
 *
 * @param string   $pay_url Original WC pay-for-order URL.
 * @param WC_Order $order   The order.
 * @return string
 */
function ats_wcvt_filter_checkout_payment_url( $pay_url, $order ) {
	$wcvt_url = ats_wcvt_resolve_payment_url( $order );
	return $wcvt_url ? $wcvt_url : $pay_url;
}
add_filter( 'woocommerce_get_checkout_payment_url', 'ats_wcvt_filter_checkout_payment_url', 20, 2 );

/**
 * Defensive 302 redirect: if a visitor lands on /checkout/order-pay/ for an
 * order that has an active wcvt payment link, send them to the wcvt page.
 *
 * Catches stale links from old emails or admin clicks generated before the
 * URL filter was in place.
 */
function ats_wcvt_redirect_pay_for_order_endpoint() {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}

	global $wp;
	$order_id = isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
	if ( ! $order_id ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Require a valid order key in the URL — never redirect on a request that
	// WC itself would reject.
	$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $order_key || ! hash_equals( $order->get_order_key(), $order_key ) ) {
		return;
	}

	$wcvt_url = ats_wcvt_resolve_payment_url( $order );
	if ( ! $wcvt_url ) {
		return;
	}

	// wp_redirect (not wp_safe_redirect) because the wcvt plugin may be configured
	// with WCVT_PAYMENT_BASE_URL pointing to a different host than the current site.
	wp_redirect( $wcvt_url, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
	exit;
}
add_action( 'template_redirect', 'ats_wcvt_redirect_pay_for_order_endpoint', 5 );
