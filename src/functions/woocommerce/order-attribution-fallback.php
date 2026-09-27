<?php
/**
 * Order attribution fallback for the classic checkout.
 *
 * WooCommerce fills hidden wc_order_attribution_* inputs with JavaScript and
 * saves them on woocommerce_checkout_order_created. On this site the classic
 * checkout POST has arrived without those fields for every real order since
 * launch (Feb 2026), so every card and PayPal order shows origin "Unknown".
 * The same sourcebuster.js data is also kept in sbjs_* cookies, which do reach
 * the server with the checkout request, so rebuild the attribution from them
 * when the fields are missing. Each order writes one line to
 * WooCommerce > Status > Logs (source "ats-attribution") to trace the cause.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parse one sourcebuster cookie ("typ=utm|||src=google|||...") into an array.
 */
function ats_attribution_parse_sbjs_cookie( string $name ): array {
	if ( empty( $_COOKIE[ $name ] ) ) {
		return array();
	}
	$raw = (string) wp_unslash( $_COOKIE[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( false === strpos( $raw, '|||' ) ) {
		$decoded = base64_decode( $raw, true );
		if ( false !== $decoded && false !== strpos( $decoded, '|||' ) ) {
			$raw = $decoded;
		}
	}
	$out = array();
	foreach ( explode( '|||', $raw ) as $pair ) {
		$parts = explode( '=', $pair, 2 );
		if ( 2 === count( $parts ) && '' !== $parts[0] ) {
			$out[ $parts[0] ] = sanitize_text_field( $parts[1] );
		}
	}
	return $out;
}

/**
 * Rebuild WooCommerce's attribution fields from the sbjs_* cookies.
 * Mapping mirrors OrderAttributionMeta::$default_fields.
 */
function ats_attribution_from_cookies(): array {
	$map = array(
		'source_type'          => array( 'sbjs_current', 'typ' ),
		'referrer'             => array( 'sbjs_current_add', 'rf' ),
		'utm_campaign'         => array( 'sbjs_current', 'cmp' ),
		'utm_source'           => array( 'sbjs_current', 'src' ),
		'utm_medium'           => array( 'sbjs_current', 'mdm' ),
		'utm_content'          => array( 'sbjs_current', 'cnt' ),
		'utm_id'               => array( 'sbjs_current', 'id' ),
		'utm_term'             => array( 'sbjs_current', 'trm' ),
		'utm_source_platform'  => array( 'sbjs_current', 'plt' ),
		'utm_creative_format'  => array( 'sbjs_current', 'fmt' ),
		'utm_marketing_tactic' => array( 'sbjs_current', 'tct' ),
		'session_entry'        => array( 'sbjs_current_add', 'ep' ),
		'session_start_time'   => array( 'sbjs_current_add', 'fd' ),
		'session_pages'        => array( 'sbjs_session', 'pgs' ),
		'session_count'        => array( 'sbjs_udata', 'vst' ),
		'user_agent'           => array( 'sbjs_udata', 'uag' ),
	);
	$cookies = array();
	$values  = array();
	foreach ( $map as $field => list( $cookie, $key ) ) {
		if ( ! isset( $cookies[ $cookie ] ) ) {
			$cookies[ $cookie ] = ats_attribution_parse_sbjs_cookie( $cookie );
		}
		if ( isset( $cookies[ $cookie ][ $key ] ) && '' !== $cookies[ $cookie ][ $key ] ) {
			$values[ $field ] = $cookies[ $cookie ][ $key ];
		}
	}
	return $values;
}

/**
 * Runs after WooCommerce's own listener (priority 10): fill in from cookies
 * when the form fields did not arrive, and log what happened.
 */
function ats_attribution_fallback( $order ) {
	if ( ! $order instanceof WC_Order ) {
		$order = wc_get_order( $order );
	}
	if ( ! $order ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification -- nonce checked by WooCommerce before this hook.
	$posted    = preg_grep( '/^wc_order_attribution_/', array_keys( $_POST ) );
	$non_empty = count( array_filter( array_intersect_key( $_POST, array_flip( $posted ) ), 'strlen' ) );
	// phpcs:enable
	$sbjs = preg_grep( '/^sbjs_/', array_keys( $_COOKIE ) );
	$used = 'form';

	if ( ! $order->meta_exists( '_wc_order_attribution_source_type' ) ) {
		$used   = 'none';
		$values = ats_attribution_from_cookies();
		if ( ! empty( $values['source_type'] ) ) {
			/** This action is documented in woocommerce/src/Internal/Orders/OrderAttributionController.php */
			do_action( 'woocommerce_order_save_attribution_data', $order, $values );
			$used = $order->meta_exists( '_wc_order_attribution_source_type' ) ? 'cookies' : 'cookies-rejected';
		}
	}

	wc_get_logger()->info(
		sprintf(
			'Order #%d attribution=%s posted_fields=%d non_empty=%d sbjs_cookies=%s ajax=%s',
			$order->get_id(),
			$used,
			count( $posted ),
			$non_empty,
			$sbjs ? implode( ',', $sbjs ) : '-',
			wp_doing_ajax() ? 'yes' : 'no'
		),
		array( 'source' => 'ats-attribution' )
	);
}
add_action( 'woocommerce_checkout_order_created', 'ats_attribution_fallback', 20 );
