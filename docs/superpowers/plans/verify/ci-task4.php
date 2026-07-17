<?php
/**
 * Customer Insights — Task 4 verification.
 * LIVE Brevo test: creates a clearly-labelled test list, imports ONE test
 * contact (info@redfrogstudio.co.uk), then deletes the list again.
 * Run: cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
 *   wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task4.php
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

ci_check( 'BREVO_API constant defined', defined( 'BREVO_API' ) && '' !== BREVO_API );

// Folder find-or-create is idempotent.
$folder = ats_ci_brevo_folder_id();
ci_check( 'folder id returned', is_int( $folder ) && $folder > 0 );
if ( is_wp_error( $folder ) ) {
	echo 'Brevo error: ' . $folder->get_error_message() . "\n";
}

// Create test list.
$list_id = ats_ci_brevo_create_list( 'ZZ TEST Customer Insights — safe to delete' );
ci_check( 'create list returns id', is_int( $list_id ) && $list_id > 0 );

if ( is_int( $list_id ) && $list_id > 0 ) {
	// Import one known-safe contact.
	$sent = ats_ci_brevo_import_contacts(
		array( array( 'email' => 'info@redfrogstudio.co.uk', 'first_name' => 'CI', 'last_name' => 'Test' ) ),
		$list_id
	);
	ci_check( 'import submitted 1 contact', 1 === $sent );
	if ( is_wp_error( $sent ) ) {
		echo 'Brevo error: ' . $sent->get_error_message() . "\n";
	}

	// Cleanup: delete the test list (contact remains in Brevo, just unlisted).
	$del = ats_ci_brevo_request( 'DELETE', '/contacts/lists/' . $list_id );
	ci_check( 'cleanup: test list deleted', ! is_wp_error( $del ) );
}

// Lists fetch works.
$lists = ats_ci_brevo_get_lists();
ci_check( 'get lists returns array', is_array( $lists ) );

// Endpoints + UI hooks registered.
ci_check( 'brevo lists ajax registered', false !== has_action( 'wp_ajax_ats_ci_brevo_lists' ) );
ci_check( 'brevo send ajax registered', false !== has_action( 'wp_ajax_ats_ci_brevo_send' ) );
ci_check( 'toolbar button hooked', false !== has_action( 'ats_ci_toolbar', 'ats_ci_toolbar_brevo_button' ) );
ci_check( 'brevo modal hooked', false !== has_action( 'ats_ci_page_footer', 'ats_ci_footer_brevo_modal' ) );

echo $GLOBALS['ci_fail'] ? "RESULT: {$GLOBALS['ci_fail']} FAILURES\n" : "RESULT: ALL PASS\n";
