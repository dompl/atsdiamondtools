<?php
/**
 * Product Bundles — Cart, checkout & order behaviour.
 *
 * A bundle is added to the cart as a SINGLE line item at the kit price (its own
 * SKU). When the bundle has price options, the chosen option index is stored on
 * the cart item so that:
 *   - the correct option price is applied,
 *   - the option label shows in the cart/checkout,
 *   - the option label + SKU are written to the order line for picking.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Capture the chosen option when a bundle is added to the cart.
 *
 * Different options produce different cart_item_data, so WooCommerce keeps them
 * as separate cart lines automatically.
 *
 * @param array $cart_item_data Existing cart item data.
 * @param int   $product_id     Product ID being added.
 * @return array
 */
function ats_bundle_add_cart_item_data( $cart_item_data, $product_id ) {
	if ( ! ats_is_bundle( $product_id ) || ! ats_bundle_has_options( $product_id ) ) {
		return $cart_item_data;
	}

	$options = ats_bundle_get_options( $product_id );
	$index   = isset( $_REQUEST['ats_bundle_option'] ) ? (int) $_REQUEST['ats_bundle_option'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $options[ $index ] ) ) {
		$index = 0;
	}

	$cart_item_data['ats_bundle_option'] = $index;
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'ats_bundle_add_cart_item_data', 10, 2 );

/**
 * Restore the stored option when the cart is loaded from the session.
 *
 * @param array $cart_item Cart item.
 * @param array $values    Stored session values.
 * @return array
 */
function ats_bundle_get_cart_item_from_session( $cart_item, $values ) {
	if ( isset( $values['ats_bundle_option'] ) ) {
		$cart_item['ats_bundle_option'] = (int) $values['ats_bundle_option'];
	}
	return $cart_item;
}
add_filter( 'woocommerce_get_cart_item_from_session', 'ats_bundle_get_cart_item_from_session', 10, 2 );

/**
 * Apply the chosen option's price to bundle cart lines.
 *
 * @param WC_Cart $cart Cart object.
 * @return void
 */
function ats_bundle_set_cart_prices( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	if ( ! $cart instanceof WC_Cart ) {
		return;
	}

	foreach ( $cart->get_cart() as $cart_item ) {
		$product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
		if ( ! $product_id || ! ats_is_bundle( $product_id ) ) {
			continue;
		}
		if ( ! isset( $cart_item['ats_bundle_option'] ) || ! ats_bundle_has_options( $product_id ) ) {
			continue;
		}
		$options = ats_bundle_get_options( $product_id );
		$index   = (int) $cart_item['ats_bundle_option'];
		if ( isset( $options[ $index ] ) && isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ) {
			$cart_item['data']->set_price( (float) $options[ $index ]['price'] );
		}
	}
}
add_action( 'woocommerce_before_calculate_totals', 'ats_bundle_set_cart_prices', 20, 1 );

/**
 * Show the chosen option in the cart / checkout line item.
 *
 * @param array $item_data Existing item data rows.
 * @param array $cart_item Cart item.
 * @return array
 */
function ats_bundle_cart_item_data( $item_data, $cart_item ) {
	$product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
	if ( ! $product_id || ! ats_is_bundle( $product_id ) || ! isset( $cart_item['ats_bundle_option'] ) ) {
		return $item_data;
	}

	$options = ats_bundle_get_options( $product_id );
	$index   = (int) $cart_item['ats_bundle_option'];
	if ( isset( $options[ $index ] ) && '' !== $options[ $index ]['label'] ) {
		$item_data[] = array(
			'key'   => __( 'Option', 'woocommerce' ),
			'value' => esc_html( $options[ $index ]['label'] ),
		);
	}
	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'ats_bundle_cart_item_data', 10, 2 );

/**
 * Persist the chosen option (label + SKU) onto the order line item.
 *
 * @param WC_Order_Item_Product $item          Order line item.
 * @param string                $cart_item_key Cart item key.
 * @param array                 $values        Cart item values.
 * @param WC_Order              $order         Order.
 * @return void
 */
function ats_bundle_order_line_item( $item, $cart_item_key, $values, $order ) {
	$product_id = isset( $values['product_id'] ) ? (int) $values['product_id'] : 0;
	if ( ! $product_id || ! ats_is_bundle( $product_id ) || ! isset( $values['ats_bundle_option'] ) ) {
		return;
	}

	$options = ats_bundle_get_options( $product_id );
	$index   = (int) $values['ats_bundle_option'];
	if ( ! isset( $options[ $index ] ) ) {
		return;
	}
	if ( '' !== $options[ $index ]['label'] ) {
		$item->add_meta_data( __( 'Option', 'woocommerce' ), $options[ $index ]['label'], true );
	}
	if ( '' !== $options[ $index ]['sku'] ) {
		$item->add_meta_data( __( 'Kit SKU', 'woocommerce' ), $options[ $index ]['sku'], true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'ats_bundle_order_line_item', 10, 4 );
