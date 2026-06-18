<?php
/**
 * Clearance Preview Reset Tool (STAGING ONLY)
 *
 * Adds a small floating button (bottom-right) on the staging site so the client
 * can re-preview the clearance pop-up repeatedly. It clears the product-card
 * transient cache + WP Rocket caches (server-side, via AJAX) and the pop-up/bar
 * "seen" flags (browser), then reloads with a cache-buster so the pop-up
 * replays. Gated to staging by host — the button never renders and the AJAX
 * endpoint hard-refuses on production.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ats_is_staging' ) ) {
	/**
	 * Whether this is the staging environment (rfsdev host).
	 *
	 * @return bool
	 */
	function ats_is_staging() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return is_string( $host ) && false !== strpos( $host, 'rfsdev' );
	}
}

/**
 * Render the floating reset button in the footer (staging only).
 */
function ats_clearance_preview_button() {
	if ( ! ats_is_staging() ) {
		return;
	}

	$nonce    = wp_create_nonce( 'ats_clearance_reset' );
	$ajax_url = admin_url( 'admin-ajax.php' );
	?>
	<button type="button" id="ats-preview-reset" class="ats-preview-reset" title="Staging only — clears the clearance cache and replays the pop-up">
		<svg class="ats-preview-reset__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
			<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
		</svg>
		<span class="ats-preview-reset__label">Reset clearance preview</span>
	</button>
	<script>
	(function () {
		var btn = document.getElementById('ats-preview-reset');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			btn.classList.add('is-busy');
			try {
				sessionStorage.removeItem('ats_clearance_popup_dismissed');
				localStorage.removeItem('ats_clearance_popup_dismissed');
				localStorage.removeItem('ats_clearance_bar_dismissed');
			} catch (e) {}
			var reload = function () {
				var u = new URL(window.location.href);
				u.searchParams.set('preview_reset', String(Date.now()));
				window.location.href = u.toString();
			};
			var fd = new FormData();
			fd.append('action', 'ats_clearance_reset');
			fd.append('nonce', '<?php echo esc_js( $nonce ); ?>');
			fetch('<?php echo esc_url_raw( $ajax_url ); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(reload).catch(reload);
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'ats_clearance_preview_button', 99 );

/**
 * AJAX: clear clearance-related caches (staging only).
 */
function ats_clearance_reset_handler() {
	if ( ! ats_is_staging() ) {
		wp_send_json_error( 'disabled', 403 );
	}
	check_ajax_referer( 'ats_clearance_reset', 'nonce' );

	global $wpdb;

	// Clear product-card HTML transients (same pattern as the admin clear tool).
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		WHERE option_name LIKE '_transient_ats_product_%'
		OR option_name LIKE '_transient_timeout_ats_product_%'"
	);

	// Clear WP Rocket page + minify caches so the fresh render is served.
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'rocket_clean_minify' ) ) {
		rocket_clean_minify();
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_ats_clearance_reset', 'ats_clearance_reset_handler' );
add_action( 'wp_ajax_nopriv_ats_clearance_reset', 'ats_clearance_reset_handler' );
