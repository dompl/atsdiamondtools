<?php
/**
 * Customer Insights — Task 2 verification.
 * Run: cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
 *   wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task2.php
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

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID', 'orderby' => 'ID', 'order' => 'ASC' ) );
wp_set_current_user( (int) $admins[0] );

$_GET                   = array( 'page' => 'ats-customer-insights', 'range' => 'all' );
$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=ats-customer-insights&range=all';
ob_start();
ats_ci_render_page();
$html = ob_get_clean();

ci_check( 'renders wp-list-table', false !== strpos( $html, 'wp-list-table' ) );
ci_check( 'renders 25 customer rows', 25 === substr_count( $html, 'class="ats-ci-row"' ) );
ci_check( 'filter form present', false !== strpos( $html, 'name="range"' ) );
ci_check( 'coupon dropdown includes free24', false !== stripos( $html, 'free24' ) );
ci_check( 'segment badges present', false !== strpos( $html, 'ats-ci-badge--' ) );
ci_check( 'menu hook registered', false !== has_action( 'admin_menu', 'ats_ci_admin_menu' ) );
ci_check( 'summary shows total customers', 1 === preg_match( '/of\s+<strong>[\d,]+<\/strong>\s+customers/', $html ) );
ci_check( 'sortable spend column link', false !== strpos( $html, 'orderby=spend' ) );

// Filtered view: dormant + any coupon still renders.
$_GET = array( 'page' => 'ats-customer-insights', 'range' => 'all', 'dormant' => '180', 'coupon' => 'any' );
ob_start();
ats_ci_render_page();
$html2 = ob_get_clean();
ci_check( 'filtered view renders rows', false !== strpos( $html2, 'class="ats-ci-row"' ) );
ci_check( 'filtered view keeps dormant value', false !== strpos( $html2, 'value="180"' ) );

echo $GLOBALS['ci_fail'] ? "RESULT: {$GLOBALS['ci_fail']} FAILURES\n" : "RESULT: ALL PASS\n";
