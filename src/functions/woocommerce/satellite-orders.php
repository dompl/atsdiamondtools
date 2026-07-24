<?php
/**
 * Satellite bridge — order tagging (ATS side).
 *
 * Stamps every order placed through a channel with its sales channel (and the
 * ruleset version in force at the time, for audit/reconciliation), and surfaces
 * the channel as a column in the WooCommerce orders list. The order otherwise
 * flows through ATS fulfilment exactly as any other.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'skylinewp_child_satellite_tag_order' ) ) {
	/**
	 * Tag an order with the current channel, if any.
	 *
	 * @param WC_Order|int $order Order or order id.
	 */
	function skylinewp_child_satellite_tag_order( $order ) {
		$channel = skylinewp_child_satellite_current_channel();
		if ( null === $channel ) {
			return;
		}
		if ( ! ( $order instanceof WC_Order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! ( $order instanceof WC_Order ) ) {
			return;
		}
		$order->update_meta_data( '_sales_channel', $channel['key'] );

		$ruleset = skylinewp_child_satellite_get_ruleset( $channel['key'] );
		if ( is_array( $ruleset ) && isset( $ruleset['version'] ) ) {
			$order->update_meta_data( '_sales_channel_ruleset_version', (string) $ruleset['version'] );
		}
		$order->save();
	}
	add_action( 'woocommerce_checkout_order_processed', 'skylinewp_child_satellite_tag_order', 20, 1 );
	add_action( 'woocommerce_store_api_checkout_order_processed', 'skylinewp_child_satellite_tag_order', 20, 1 );
}

if ( ! function_exists( 'skylinewp_child_satellite_channel_label' ) ) {
	/**
	 * Human label for a channel key, from the registry. Pure-ish (reads config).
	 *
	 * @param string $channel_key Channel key.
	 * @return string
	 */
	function skylinewp_child_satellite_channel_label( $channel_key ) {
		$registry = skylinewp_child_satellite_channels();
		if ( isset( $registry[ $channel_key ]['label'] ) && '' !== $registry[ $channel_key ]['label'] ) {
			return (string) $registry[ $channel_key ]['label'];
		}
		return (string) $channel_key;
	}
}

/* -- Admin orders list column (legacy post-based storage; HPOS-safe too) -- */

if ( ! function_exists( 'skylinewp_child_satellite_add_order_column' ) ) {
	/**
	 * Insert a "Channel" column after the order status.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	function skylinewp_child_satellite_add_order_column( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$new['sales_channel'] = __( 'Channel', 'skylinewp-child' );
			}
		}
		if ( ! isset( $new['sales_channel'] ) ) {
			$new['sales_channel'] = __( 'Channel', 'skylinewp-child' );
		}
		return $new;
	}
	add_filter( 'manage_edit-shop_order_columns', 'skylinewp_child_satellite_add_order_column', 20, 1 );
	add_filter( 'manage_woocommerce_page_wc-orders_columns', 'skylinewp_child_satellite_add_order_column', 20, 1 );
}

if ( ! function_exists( 'skylinewp_child_satellite_render_order_column' ) ) {
	/**
	 * Render the channel cell for one order.
	 *
	 * @param WC_Order $order Order.
	 */
	function skylinewp_child_satellite_render_order_column_for( $order ) {
		if ( ! ( $order instanceof WC_Order ) ) {
			echo '—';
			return;
		}
		$channel = (string) $order->get_meta( '_sales_channel' );
		echo '' === $channel
			? '<span aria-hidden="true">—</span>'
			: '<span class="sales-channel-badge">' . esc_html( skylinewp_child_satellite_channel_label( $channel ) ) . '</span>';
	}

	// Legacy post-based orders list.
	add_action(
		'manage_shop_order_posts_custom_column',
		static function ( $column, $post_id ) {
			if ( 'sales_channel' === $column ) {
				skylinewp_child_satellite_render_order_column_for( wc_get_order( $post_id ) );
			}
		},
		20,
		2
	);

	// HPOS orders list.
	add_action(
		'woocommerce_shop_order_list_table_custom_column',
		static function ( $column, $order ) {
			if ( 'sales_channel' === $column ) {
				skylinewp_child_satellite_render_order_column_for( $order );
			}
		},
		20,
		2
	);
}
