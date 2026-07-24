<?php
/**
 * Satellite bridge — channel recognition (ATS side).
 *
 * The single security gate for the whole bridge. A satellite ("channel") signs
 * every request to ATS with its shared secret; this file verifies that
 * signature and identifies which channel is calling. Every other satellite-*.php
 * file no-ops unless skylinewp_child_satellite_current_channel() returns a
 * channel here, so normal atsdiamondtools.co.uk traffic is never touched.
 *
 * Channel credentials (key + secret) are configuration, not markup: they live in
 * a SATELLITE_CHANNELS constant (JSON) or the skylinewp_child_satellite_channels
 * filter — never in a WP admin screen. The markup itself is never stored here;
 * it is fetched as a signed ruleset (see satellite-ruleset.php).
 *
 * Request headers expected from the platform:
 *   X-EA-Channel    the channel key (identifies the site)
 *   X-EA-Timestamp  unix seconds (replay window)
 *   X-EA-Sign       hex HMAC-SHA256 over "key\n timestamp\n body"
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'skylinewp_child_satellite_channels' ) ) {
	/**
	 * The configured channel registry: channel key => [ 'secret' => ..., 'label' => ... ].
	 *
	 * @return array
	 */
	function skylinewp_child_satellite_channels() {
		$channels = array();
		if ( defined( 'SATELLITE_CHANNELS' ) ) {
			$decoded = json_decode( (string) SATELLITE_CHANNELS, true );
			if ( is_array( $decoded ) ) {
				$channels = $decoded;
			}
		}
		/**
		 * Filter the satellite channel registry.
		 *
		 * @param array $channels channel key => [ 'secret' => string, 'label' => string ].
		 */
		return (array) apply_filters( 'skylinewp_child_satellite_channels', $channels );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_sign' ) ) {
	/**
	 * Compute the canonical HMAC-SHA256 signature for a request.
	 *
	 * @param string $channel_key Channel key.
	 * @param int    $timestamp   Unix seconds.
	 * @param string $body        Raw request body.
	 * @param string $secret      Channel shared secret.
	 * @return string Hex signature.
	 */
	function skylinewp_child_satellite_sign( $channel_key, $timestamp, $body, $secret ) {
		$payload = $channel_key . "\n" . $timestamp . "\n" . $body;
		return hash_hmac( 'sha256', $payload, $secret );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_signature_valid' ) ) {
	/**
	 * Verify a signature and its replay window. Pure — no request/WP state.
	 *
	 * @param string $channel_key Channel key.
	 * @param int    $timestamp   Unix seconds from the request.
	 * @param string $sign        Provided hex signature.
	 * @param string $body        Raw request body.
	 * @param string $secret      Channel shared secret.
	 * @param int    $now         Current unix seconds.
	 * @param int    $window      Allowed clock skew in seconds.
	 * @return bool
	 */
	function skylinewp_child_satellite_signature_valid( $channel_key, $timestamp, $sign, $body, $secret, $now, $window = 300 ) {
		if ( '' === (string) $secret || '' === (string) $sign ) {
			return false;
		}
		if ( abs( (int) $now - (int) $timestamp ) > (int) $window ) {
			return false;
		}
		$expected = skylinewp_child_satellite_sign( $channel_key, $timestamp, $body, $secret );
		return hash_equals( $expected, (string) $sign );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_request_headers' ) ) {
	/**
	 * Pull the channel headers from the current request.
	 *
	 * @return array { channel, timestamp, sign }
	 */
	function skylinewp_child_satellite_request_headers() {
		$get = static function ( $key ) {
			$server = 'HTTP_' . strtoupper( str_replace( '-', '_', $key ) );
			return isset( $_SERVER[ $server ] ) ? trim( (string) wp_unslash( $_SERVER[ $server ] ) ) : '';
		};
		return array(
			'channel'   => $get( 'X-EA-Channel' ),
			'timestamp' => $get( 'X-EA-Timestamp' ),
			'sign'      => $get( 'X-EA-Sign' ),
		);
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_current_channel' ) ) {
	/**
	 * Resolve and verify the channel for the current request.
	 *
	 * Result is memoised for the request. Returns the channel descriptor
	 * ([ 'key' => ..., 'label' => ... ]) on a valid signature, or null.
	 *
	 * @return array|null
	 */
	function skylinewp_child_satellite_current_channel() {
		static $resolved = false;
		static $channel  = null;

		if ( $resolved ) {
			return $channel;
		}
		$resolved = true;

		$headers = skylinewp_child_satellite_request_headers();
		if ( '' === $headers['channel'] || '' === $headers['sign'] ) {
			return $channel;
		}

		$registry = skylinewp_child_satellite_channels();
		if ( ! isset( $registry[ $headers['channel'] ]['secret'] ) ) {
			return $channel;
		}

		$body   = file_get_contents( 'php://input' );
		$secret = $registry[ $headers['channel'] ]['secret'];
		$valid  = skylinewp_child_satellite_signature_valid(
			$headers['channel'],
			(int) $headers['timestamp'],
			$headers['sign'],
			(string) $body,
			$secret,
			time()
		);

		if ( $valid ) {
			$channel = array(
				'key'   => $headers['channel'],
				'label' => isset( $registry[ $headers['channel'] ]['label'] ) ? $registry[ $headers['channel'] ]['label'] : $headers['channel'],
			);
		}
		return $channel;
	}
}
