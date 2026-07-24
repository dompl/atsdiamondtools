<?php
/**
 * Satellite bridge — pricing engine (ATS side).
 *
 * Pure, integer-pence port of the platform's pricing engine. This is the
 * authoritative markup calculation applied to satellite ("channel") requests at
 * checkout, so ATS charges the satellite's marked-up price rather than its own
 * base price. It deliberately depends on nothing in WordPress or WooCommerce so
 * it can be reasoned about and validated in isolation, and it mirrors the
 * TypeScript reference implementation (src/lib/pricing) one-for-one.
 *
 * All money is integer pence. Never float arithmetic on prices.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'SATELLITE_ENGINE_STANDALONE' ) ) {
	exit;
}

if ( ! function_exists( 'skylinewp_child_satellite_to_pence' ) ) {
	/**
	 * Convert a decimal-string price ("29.99", "155") to integer pence.
	 *
	 * @param string|null $value Decimal price string.
	 * @return int|null Pence, or null for empty input.
	 */
	function skylinewp_child_satellite_to_pence( $value ) {
		if ( $value === '' || $value === null ) {
			return null;
		}
		$parts  = explode( '.', (string) $value );
		$pounds = ( isset( $parts[0] ) && $parts[0] !== '' ) ? $parts[0] : '0';
		$dec    = isset( $parts[1] ) ? $parts[1] : '';
		$pence  = substr( $dec . '00', 0, 2 );
		return (int) $pounds * 100 + (int) $pence;
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_round_pence' ) ) {
	/**
	 * Apply a rounding rule to a pence figure.
	 *
	 * @param int    $pence Amount in pence.
	 * @param string $rule  'none' | 'nearest_pound' | 'charm'.
	 * @return int Rounded pence.
	 */
	function skylinewp_child_satellite_round_pence( $pence, $rule ) {
		switch ( $rule ) {
			case 'nearest_pound':
				return (int) ( round( $pence / 100 ) * 100 );
			case 'charm':
				// Nearest value ending in .99, never below 99p.
				return (int) max( 99, round( ( $pence - 99 ) / 100 ) * 100 + 99 );
			case 'none':
			default:
				return (int) $pence;
		}
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_price_one' ) ) {
	/**
	 * Turn an ATS base price into the satellite selling price.
	 *
	 * @param int      $regular_pence Regular price in pence.
	 * @param int|null $sale_pence    Sale price in pence, or null.
	 * @param bool     $follow_sales  Whether this channel follows ATS sales.
	 * @param array    $rule          {
	 *     @type string $type       'percent' (basis points) or 'fixed' (pence).
	 *     @type int    $value      Markup value.
	 *     @type int    $maxUplift  Optional max uplift, pence.
	 *     @type int    $minUplift  Optional min uplift, pence.
	 *     @type string $rounding   'none' | 'nearest_pound' | 'charm'.
	 * }
	 * @return array { base, charged, uplift, effectivePct, onSale }
	 */
	function skylinewp_child_satellite_price_one( $regular_pence, $sale_pence, $follow_sales, $rule ) {
		$on_sale = $follow_sales && $sale_pence !== null && $sale_pence < $regular_pence;
		$base    = $on_sale ? (int) $sale_pence : (int) $regular_pence;

		if ( isset( $rule['type'] ) && 'percent' === $rule['type'] ) {
			$uplift = (int) round( $base * (int) $rule['value'] / 10000 );
		} else {
			$uplift = (int) $rule['value'];
		}

		if ( isset( $rule['minUplift'] ) ) {
			$uplift = max( $uplift, (int) $rule['minUplift'] );
		}
		if ( isset( $rule['maxUplift'] ) ) {
			$uplift = min( $uplift, (int) $rule['maxUplift'] );
		}

		$rounding     = isset( $rule['rounding'] ) ? $rule['rounding'] : 'none';
		$charged      = skylinewp_child_satellite_round_pence( $base + $uplift, $rounding );
		$uplift_final = $charged - $base;
		$effective    = $base > 0 ? round( $uplift_final / $base * 1000 ) / 10 : 0;

		return array(
			'base'         => $base,
			'charged'      => $charged,
			'uplift'       => $uplift_final,
			'effectivePct' => $effective,
			'onSale'       => $on_sale,
		);
	}
}
