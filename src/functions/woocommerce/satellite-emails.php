<?php
/**
 * Satellite bridge — customer email suppression (ATS side).
 *
 * A satellite sends its own brand's order emails showing the price the customer
 * paid, so ATS must not also send its own customer-facing emails for channel
 * orders. Suppression is done by blanking the recipient of each customer order
 * email when the order carries a sales channel; ATS admin/fulfilment
 * notifications (new order, cancelled, failed) are untouched and still fire.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'skylinewp_child_satellite_order_is_channel' ) ) {
	/**
	 * Whether an order was placed through a satellite channel. Pure decision on
	 * the stored meta value.
	 *
	 * @param string $channel_meta Value of the order's _sales_channel meta.
	 * @return bool
	 */
	function skylinewp_child_satellite_order_is_channel( $channel_meta ) {
		return '' !== trim( (string) $channel_meta );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_customer_email_ids' ) ) {
	/**
	 * The customer-facing order email ids ATS should not send for channel orders.
	 *
	 * @return string[]
	 */
	function skylinewp_child_satellite_customer_email_ids() {
		return (array) apply_filters(
			'skylinewp_child_satellite_customer_email_ids',
			array(
				'customer_processing_order',
				'customer_completed_order',
				'customer_on_hold_order',
				'customer_refunded_order',
				'customer_partially_refunded_order',
				'customer_invoice',
				'customer_note',
			)
		);
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_blank_channel_recipient' ) ) {
	/**
	 * Blank the recipient for a channel order so the email is not sent.
	 *
	 * @param string $recipient Current recipient.
	 * @param mixed  $object    The email object (WC_Order for order emails).
	 * @return string
	 */
	function skylinewp_child_satellite_blank_channel_recipient( $recipient, $object ) {
		if ( $object instanceof WC_Order
			&& skylinewp_child_satellite_order_is_channel( $object->get_meta( '_sales_channel' ) ) ) {
			return '';
		}
		return $recipient;
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_register_email_suppression' ) ) {
	/**
	 * Attach the recipient filter to every customer order email.
	 */
	function skylinewp_child_satellite_register_email_suppression() {
		foreach ( skylinewp_child_satellite_customer_email_ids() as $email_id ) {
			add_filter(
				"woocommerce_email_recipient_{$email_id}",
				'skylinewp_child_satellite_blank_channel_recipient',
				20,
				2
			);
		}
	}
	add_action( 'woocommerce_init', 'skylinewp_child_satellite_register_email_suppression' );
}
