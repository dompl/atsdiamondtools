<?php
/**
 * Satellite bridge — channel ruleset (ATS side).
 *
 * A ruleset is the signed document the platform serves per channel. It carries
 * everything ATS needs to price and restrict a satellite's requests: the markup
 * rule (+ per-product overrides), sale policy, coupon allowlist, excluded
 * products, the free-shipping toggle, and hidden shipping methods. The markup is
 * NEVER stored on ATS — it is fetched from the platform and cached transiently.
 *
 * Never-undercharge guarantee (see skylinewp_child_satellite_resolve_ruleset):
 *   1. a fresh cached ruleset is used if present;
 *   2. otherwise the platform is fetched, and the result cached + persisted as
 *      last-known-good;
 *   3. if the fetch fails, the last-known-good ruleset is used;
 *   4. if there has never been one, a WP_Error is returned and checkout is
 *      refused. Falling back to ATS base prices is forbidden.
 *
 * Ruleset shape (JSON):
 * {
 *   "version": 42,
 *   "markup": { "type":"percent"|"fixed", "value":1500, "maxUplift":5000,
 *               "minUplift":0, "rounding":"none"|"nearest_pound"|"charm" },
 *   "followSales": true,
 *   "productMarkup": { "<product_id>": <rule>|"none" },
 *   "excluded": [ <product_id>, ... ],
 *   "coupons": [ "code", ... ],
 *   "freeShipping": true,
 *   "hiddenMethods": [ "table_rate:8", ... ]
 * }
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'skylinewp_child_satellite_platform_url' ) ) {
	/**
	 * Base URL of the satellite platform's ruleset API.
	 *
	 * @return string Trailing-slashed base URL, or '' if unconfigured.
	 */
	function skylinewp_child_satellite_platform_url() {
		$url = defined( 'SATELLITE_PLATFORM_URL' ) ? (string) SATELLITE_PLATFORM_URL : '';
		$url = (string) apply_filters( 'skylinewp_child_satellite_platform_url', $url );
		return '' === $url ? '' : trailingslashit( $url );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_ruleset_valid' ) ) {
	/**
	 * Validate the essential shape of a ruleset. Pure.
	 *
	 * @param mixed $ruleset Decoded ruleset.
	 * @return bool
	 */
	function skylinewp_child_satellite_ruleset_valid( $ruleset ) {
		if ( ! is_array( $ruleset ) || ! isset( $ruleset['markup'] ) || ! is_array( $ruleset['markup'] ) ) {
			return false;
		}
		$m = $ruleset['markup'];
		if ( ! isset( $m['type'] ) || ! in_array( $m['type'], array( 'percent', 'fixed' ), true ) ) {
			return false;
		}
		if ( ! isset( $m['value'] ) || ! is_numeric( $m['value'] ) ) {
			return false;
		}
		if ( ! isset( $m['rounding'] ) || ! in_array( $m['rounding'], array( 'none', 'nearest_pound', 'charm' ), true ) ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_rule_for_product' ) ) {
	/**
	 * The effective markup rule for a product: a per-product override if set,
	 * a zero-markup rule for the string "none", otherwise the ruleset default. Pure.
	 *
	 * @param array $ruleset    Ruleset.
	 * @param int   $product_id Product (or parent product) id.
	 * @return array Markup rule.
	 */
	function skylinewp_child_satellite_rule_for_product( $ruleset, $product_id ) {
		$per = isset( $ruleset['productMarkup'] ) && is_array( $ruleset['productMarkup'] ) ? $ruleset['productMarkup'] : array();
		$key = (string) $product_id;
		if ( array_key_exists( $key, $per ) ) {
			$override = $per[ $key ];
			if ( 'none' === $override ) {
				return array(
					'type'     => 'fixed',
					'value'    => 0,
					'rounding' => 'none',
				);
			}
			if ( is_array( $override ) ) {
				return $override;
			}
		}
		return $ruleset['markup'];
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_is_excluded' ) ) {
	/**
	 * Whether a product is excluded from this channel. Pure.
	 *
	 * @param array $ruleset    Ruleset.
	 * @param int   $product_id Product id.
	 * @return bool
	 */
	function skylinewp_child_satellite_is_excluded( $ruleset, $product_id ) {
		$excluded = isset( $ruleset['excluded'] ) && is_array( $ruleset['excluded'] ) ? $ruleset['excluded'] : array();
		return in_array( (int) $product_id, array_map( 'intval', $excluded ), true );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_resolve_ruleset' ) ) {
	/**
	 * The never-undercharge decision, with injected storage/fetch so it can be
	 * reasoned about and tested in isolation. Pure orchestration.
	 *
	 * @param string $channel_key Channel key.
	 * @param array  $deps        {
	 *     @type callable $cache_get fn(key): array|null   fresh transient ruleset.
	 *     @type callable $cache_set fn(key, ruleset): void
	 *     @type callable $lkg_get   fn(key): array|null   last-known-good.
	 *     @type callable $lkg_set   fn(key, ruleset): void
	 *     @type callable $fetch     fn(key): array|WP_Error|null
	 * }
	 * @return array|WP_Error Ruleset, or WP_Error when none can be obtained.
	 */
	function skylinewp_child_satellite_resolve_ruleset( $channel_key, array $deps ) {
		$cached = call_user_func( $deps['cache_get'], $channel_key );
		if ( skylinewp_child_satellite_ruleset_valid( $cached ) ) {
			return $cached;
		}

		$fetched = call_user_func( $deps['fetch'], $channel_key );
		if ( skylinewp_child_satellite_ruleset_valid( $fetched ) ) {
			call_user_func( $deps['cache_set'], $channel_key, $fetched );
			call_user_func( $deps['lkg_set'], $channel_key, $fetched );
			return $fetched;
		}

		$lkg = call_user_func( $deps['lkg_get'], $channel_key );
		if ( skylinewp_child_satellite_ruleset_valid( $lkg ) ) {
			return $lkg;
		}

		return new WP_Error(
			'satellite_no_ruleset',
			sprintf( 'No pricing ruleset available for channel "%s"; checkout refused rather than charging base prices.', $channel_key )
		);
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_fetch_ruleset_http' ) ) {
	/**
	 * Fetch a ruleset from the platform over signed HTTP. WP glue.
	 *
	 * @param string $channel_key Channel key.
	 * @return array|null Decoded ruleset, or null on any failure.
	 */
	function skylinewp_child_satellite_fetch_ruleset_http( $channel_key ) {
		$base = skylinewp_child_satellite_platform_url();
		if ( '' === $base ) {
			return null;
		}
		$registry = skylinewp_child_satellite_channels();
		if ( ! isset( $registry[ $channel_key ]['secret'] ) ) {
			return null;
		}
		$secret = $registry[ $channel_key ]['secret'];
		$ts     = time();
		$body   = '';
		$sign   = skylinewp_child_satellite_sign( $channel_key, $ts, $body, $secret );

		$response = wp_remote_get(
			$base . 'ruleset/' . rawurlencode( $channel_key ),
			array(
				'timeout' => 5,
				'headers' => array(
					'X-EA-Channel'   => $channel_key,
					'X-EA-Timestamp' => (string) $ts,
					'X-EA-Sign'      => $sign,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $decoded ) ? $decoded : null;
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_get_ruleset' ) ) {
	/**
	 * The current channel's ruleset, memoised per request. Wires the real
	 * transient cache, last-known-good option, and HTTP fetch into the resolver.
	 *
	 * @param string $channel_key Channel key.
	 * @return array|WP_Error
	 */
	function skylinewp_child_satellite_get_ruleset( $channel_key ) {
		static $memo = array();
		if ( isset( $memo[ $channel_key ] ) ) {
			return $memo[ $channel_key ];
		}

		$transient_key = 'sat_ruleset_' . $channel_key;
		$lkg_key       = 'sat_ruleset_lkg_' . $channel_key;
		$ttl           = (int) apply_filters( 'skylinewp_child_satellite_ruleset_ttl', 5 * MINUTE_IN_SECONDS, $channel_key );

		$result = skylinewp_child_satellite_resolve_ruleset(
			$channel_key,
			array(
				'cache_get' => static function ( $key ) use ( $transient_key ) {
					$v = get_transient( $transient_key );
					return is_array( $v ) ? $v : null;
				},
				'cache_set' => static function ( $key, $ruleset ) use ( $transient_key, $ttl ) {
					set_transient( $transient_key, $ruleset, $ttl );
				},
				'lkg_get'   => static function ( $key ) use ( $lkg_key ) {
					$v = get_option( $lkg_key, null );
					return is_array( $v ) ? $v : null;
				},
				'lkg_set'   => static function ( $key, $ruleset ) use ( $lkg_key ) {
					update_option( $lkg_key, $ruleset, false );
				},
				'fetch'     => 'skylinewp_child_satellite_fetch_ruleset_http',
			)
		);

		$memo[ $channel_key ] = $result;
		return $result;
	}
}
