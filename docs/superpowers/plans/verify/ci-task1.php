<?php
/**
 * Customer Insights — Task 1 verification.
 * Run: cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
 *   wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task1.php
 */

$GLOBALS['ci_fail'] = 0;
function ci_check( $label, $ok ) {
	if ( $ok ) {
		echo "PASS: {$label}\n";
	} else {
		$GLOBALS['ci_fail']++;
		echo "FAIL: {$label}\n";
	}
}

global $wpdb;
$p = $wpdb->prefix;

// 1. Top all-time buyer by units matches raw SQL.
$r   = ats_ci_get_customers( array( 'orderby' => 'units', 'per_page' => 25, 'nocache' => true ) );
$raw = $wpdb->get_row(
	"SELECT os.customer_id, SUM(os.num_items_sold) AS units
	 FROM {$p}wc_order_stats os
	 WHERE os.parent_id = 0 AND os.status IN ('wc-completed','wc-processing')
	 GROUP BY os.customer_id ORDER BY units DESC LIMIT 1"
);
ci_check( 'top buyer id matches raw SQL', (int) $r['rows'][0]->customer_id === (int) $raw->customer_id );
ci_check( 'top buyer units match raw SQL', (int) $r['rows'][0]->units === (int) $raw->units );
ci_check( 'total looks sane (>1000 customers all-time)', $r['total'] > 1000 );
ci_check( 'segments assigned on every row', ! array_filter( $r['rows'], function ( $x ) { return empty( $x->segment ); } ) );

// 2. min_units filter respected.
$r2 = ats_ci_get_customers( array( 'min_units' => 50, 'per_page' => 100, 'nocache' => true ) );
ci_check( 'min_units=50: every row >= 50 units', ! array_filter( $r2['rows'], function ( $x ) { return (int) $x->units < 50; } ) );

// 3. coupon=any: rows have coupon orders; spot-check the first against the lookup table.
$r3 = ats_ci_get_customers( array( 'coupon' => 'any', 'per_page' => 25, 'nocache' => true ) );
ci_check( 'coupon=any returns rows', ! empty( $r3['rows'] ) );
ci_check( 'coupon=any: every row coupon_orders >= 1', ! array_filter( $r3['rows'], function ( $x ) { return (int) $x->coupon_orders < 1; } ) );
$spot = $r3['rows'][0];
$n    = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(DISTINCT ocl.order_id)
	 FROM {$p}wc_order_coupon_lookup ocl
	 JOIN {$p}wc_order_stats os ON os.order_id = ocl.order_id
	  AND os.parent_id = 0 AND os.status IN ('wc-completed','wc-processing')
	 WHERE os.customer_id = %d",
	$spot->customer_id
) );
ci_check( 'spot-check: coupon_orders matches lookup count', (int) $spot->coupon_orders === $n );

// 4. Guest filter returns only NULL user_id.
$r4 = ats_ci_get_customers( array( 'account' => 'guest', 'per_page' => 10, 'nocache' => true ) );
ci_check( 'guest filter: only NULL user_id', ! array_filter( $r4['rows'], function ( $x ) { return null !== $x->user_id; } ) );

// 5. Dormant filter.
$r5 = ats_ci_get_customers( array( 'dormant_days' => 180, 'per_page' => 10, 'nocache' => true ) );
ci_check( 'dormant: every row >= 180 days since last order', ! array_filter( $r5['rows'], function ( $x ) { return (int) $x->days_since_last < 180; } ) );

// 6. One-time buyer filter.
$r6 = ats_ci_get_customers( array( 'buyer' => 'one-time', 'per_page' => 10, 'nocache' => true ) );
ci_check( 'one-time: every row has exactly 1 all-time order', ! array_filter( $r6['rows'], function ( $x ) { return 1 !== (int) $x->att_orders; } ) );

// 7. Date range restricts figures: 30d spend for top row <= all-time spend.
$r7 = ats_ci_get_customers( array( 'range' => '30d', 'per_page' => 5, 'nocache' => true ) );
$a  = ats_ci_parse_args( array( 'range' => '30d' ) );
ci_check( 'parse_args: 30d produces a date_from', '' !== $a['date_from'] );
$ok = true;
foreach ( $r7['rows'] as $row ) {
	if ( (float) $row->total_spend > (float) $row->att_spend + 0.01 ) { $ok = false; }
}
ci_check( '30d range: range spend never exceeds all-time spend', $ok );

// 8. Coupon options list is non-empty and contains free24.
$codes = ats_ci_get_coupon_options();
ci_check( 'coupon options include free24', in_array( 'free24', array_map( 'strtolower', $codes ), true ) );

// 9. Product LIKE filter survives the query builder (covers the %-escaping path).
$pt = $wpdb->get_var(
	"SELECT pp.post_title FROM {$p}wc_order_product_lookup opl
	 JOIN {$p}posts pp ON pp.ID = opl.product_id
	 JOIN {$p}wc_order_stats os ON os.order_id = opl.order_id
	  AND os.parent_id = 0 AND os.status IN ('wc-completed','wc-processing')
	 LIMIT 1"
);
$r9 = ats_ci_get_customers( array( 'product' => $pt, 'per_page' => 5, 'nocache' => true ) );
ci_check( 'product name filter returns buyers of a known product', $r9['total'] >= 1 );

echo $GLOBALS['ci_fail'] ? "RESULT: {$GLOBALS['ci_fail']} FAILURES\n" : "RESULT: ALL PASS\n";
