<?php
/**
 * Prevent WooCommerce from auto-cancelling orders that have a payment link.
 *
 * WooCommerce cancels unpaid orders after `woocommerce_hold_stock_minutes`.
 * Payment link orders need to stay pending until the customer pays via the link,
 * which may take hours or days.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prevent auto-cancellation of orders with an active payment link.
 *
 * @param bool     $cancel Whether to cancel the order.
 * @param WC_Order $order  The order being evaluated.
 * @return bool
 */
function ats_prevent_payment_link_order_cancellation( $cancel, $order ) {
	if ( ! $cancel || ! $order ) {
		return $cancel;
	}

	$payment_token = $order->get_meta( '_wcvt_payment_token' );

	if ( ! empty( $payment_token ) ) {
		return false;
	}

	return $cancel;
}
add_filter( 'woocommerce_cancel_unpaid_order', 'ats_prevent_payment_link_order_cancellation', 10, 2 );
