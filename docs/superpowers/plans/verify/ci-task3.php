<?php
/**
 * Customer Insights — Task 3 verification.
 * Run: cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
 *   wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task3.php
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

// Use the all-time top buyer as the test subject.
$top = ats_ci_get_customers( array( 'orderby' => 'units', 'per_page' => 1, 'nocache' => true ) );
$cid = (int) $top['rows'][0]->customer_id;

$d = ats_ci_customer_detail( $cid );
ci_check( 'detail returns array for top buyer', is_array( $d ) );
ci_check( 'customer email present', ! empty( $d['customer']->email ) );

// Stats cross-check against raw SQL.
$raw_orders = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$p}wc_order_stats
	 WHERE customer_id = %d AND parent_id = 0 AND status IN ('wc-completed','wc-processing')",
	$cid
) );
ci_check( 'att_orders matches raw count', (int) $d['stats']->att_orders === $raw_orders );
ci_check( 'ranking row agrees with detail stats', (int) $top['rows'][0]->att_orders === (int) $d['stats']->att_orders );

// Order list sane.
ci_check( 'orders list non-empty', ! empty( $d['orders'] ) );
$latest = $d['orders'][0];
ci_check( 'orders sorted newest first', strtotime( $latest->date_created ) >= strtotime( end( $d['orders'] )->date_created ) );
ci_check( 'items summary exists for latest order', isset( $d['items'][ (int) $latest->order_id ] ) );
ci_check( 'top products non-empty', ! empty( $d['top_products'] ) );

// HTML fragment.
$html = ats_ci_detail_html( $d );
ci_check( 'detail html contains email', false !== strpos( $html, $d['customer']->email ) );
ci_check( 'detail html contains order link', false !== strpos( $html, 'action=edit' ) );
ci_check( 'detail html has stats grid', false !== strpos( $html, 'ats-ci-stats' ) );

// AJAX + footer hooks registered.
ci_check( 'ajax action registered', false !== has_action( 'wp_ajax_ats_ci_customer' ) );
ci_check( 'footer modal hooked', false !== has_action( 'ats_ci_page_footer', 'ats_ci_footer_detail_modal' ) );

// Unknown customer returns null.
ci_check( 'unknown customer returns null', null === ats_ci_customer_detail( 999999999 ) );

echo $GLOBALS['ci_fail'] ? "RESULT: {$GLOBALS['ci_fail']} FAILURES\n" : "RESULT: ALL PASS\n";
