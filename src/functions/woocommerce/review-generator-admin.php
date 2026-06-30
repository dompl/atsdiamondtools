<?php
/**
 * Review Generator — Admin control panel
 *
 * WooCommerce submenu page to build the plan (preview before it drips),
 * start/pause the drip, watch progress, and remove all generated reviews.
 * All actions are capability-gated and nonce-protected.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the admin page.
 */
function ats_reviews_admin_menu() {
	add_submenu_page(
		'woocommerce',
		'Review Generator',
		'Review Generator',
		'manage_woocommerce',
		'ats-review-generator',
		'ats_reviews_admin_page'
	);
}
add_action( 'admin_menu', 'ats_reviews_admin_menu' );

/**
 * Handle posted actions (build / start / pause / purge), then redirect back.
 */
function ats_reviews_handle_post() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Insufficient permissions.' );
	}
	check_admin_referer( 'ats_reviews_action' );

	$op     = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
	$notice = '';

	switch ( $op ) {
		case 'build':
			$summary = ats_reviews_build_plan();
			$notice  = sprintf(
				'built:%d',
				(int) $summary['total']
			);
			break;

		case 'start':
			ats_reviews_schedule_cron();
			ats_reviews_set_state( array( 'status' => 'active' ) );
			$notice = 'started';
			break;

		case 'pause':
			ats_reviews_set_state( array( 'status' => 'paused' ) );
			$notice = 'paused';
			break;

		case 'publish_all':
			$n      = ats_reviews_publish_all();
			$notice = sprintf( 'publishedall:%d', (int) $n );
			break;

		case 'purge':
			$result = ats_reviews_purge_all();
			$notice = sprintf( 'purged:%d', (int) $result['reviews_deleted'] );
			break;
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'   => 'ats-review-generator',
				'notice' => rawurlencode( $notice ),
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_ats_reviews', 'ats_reviews_handle_post' );

/**
 * Top products by planned review count, for the preview table.
 *
 * @param int $limit Rows.
 * @return array
 */
function ats_reviews_top_planned( $limit = 12 ) {
	global $wpdb;
	$table = ats_reviews_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return array();
	}

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT product_id, COUNT(*) AS n,
				SUM(status = 'published') AS live
			FROM {$table} GROUP BY product_id ORDER BY n DESC LIMIT %d",
			(int) $limit
		),
		ARRAY_A
	);
	return (array) $rows;
}

/**
 * Render the admin page.
 */
function ats_reviews_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$state  = ats_reviews_get_state();
	$counts = ats_reviews_queue_counts();
	$status = $state['status'];

	$notice = isset( $_GET['notice'] ) ? sanitize_text_field( wp_unslash( $_GET['notice'] ) ) : '';

	echo '<div class="wrap">';
	echo '<h1>Review Generator</h1>';

	if ( '' !== $notice ) {
		$msg = $notice;
		if ( 0 === strpos( $notice, 'built:' ) ) {
			$msg = 'Plan built: ' . (int) substr( $notice, 6 ) . ' reviews queued (paused — nothing is live yet). Review the preview below, then Start the drip.';
		} elseif ( 0 === strpos( $notice, 'purged:' ) ) {
			$msg = 'Removed ' . (int) substr( $notice, 7 ) . ' generated reviews. System reset to idle.';
		} elseif ( 0 === strpos( $notice, 'publishedall:' ) ) {
			$msg = 'Published ' . (int) substr( $notice, 13 ) . ' reviews now. Dates remain spread across the past year, so they read as long-standing reviews.';
		} elseif ( 'started' === $notice ) {
			$msg = 'Drip started. Reviews will surface gradually over the next 30 days.';
		} elseif ( 'paused' === $notice ) {
			$msg = 'Drip paused. No new reviews will surface until you start it again.';
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}

	// Compliance reminder, on screen.
	echo '<div class="notice notice-warning inline"><p><strong>Note:</strong> these are generated reviews, not from real buyers. They are <em>not</em> marked as verified purchases, but generating reviews still carries risk under the UK DMCC Act 2024 fake-review rules. Staging use only unless signed off.</p></div>';

	$labels = array(
		'idle'      => 'Idle (no plan built)',
		'paused'    => 'Paused (plan built, not publishing)',
		'active'    => 'Active (publishing)',
		'completed' => 'Completed (all reviews published)',
	);

	echo '<table class="widefat" style="max-width:640px;margin:16px 0;"><tbody>';
	printf( '<tr><th>Status</th><td>%s</td></tr>', esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $status ) );
	printf( '<tr><th>Total planned</th><td>%d</td></tr>', (int) $counts['total'] );
	printf( '<tr><th>Published (live)</th><td>%d</td></tr>', (int) $counts['published'] );
	printf( '<tr><th>Pending</th><td>%d</td></tr>', (int) $counts['pending'] );
	if ( $state['window_end'] ) {
		printf( '<tr><th>Drip window ends</th><td>%s</td></tr>', esc_html( $state['window_end'] ) );
	}
	echo '</tbody></table>';

	// Action buttons.
	$nonce = wp_nonce_field( 'ats_reviews_action', '_wpnonce', true, false );
	$url   = esc_url( admin_url( 'admin-post.php' ) );

	echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin:8px 0 24px;">';

	// Build / Rebuild.
	echo '<form method="post" action="' . $url . '" onsubmit="return confirm(\'Build the review plan? This replaces any existing un-published plan.\');">';
	echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<input type="hidden" name="action" value="ats_reviews" /><input type="hidden" name="op" value="build" />';
	echo '<button type="submit" class="button button-primary">' . ( 'idle' === $status ? 'Build Plan' : 'Rebuild Plan' ) . '</button>';
	echo '</form>';

	// Start / Pause.
	if ( 'active' === $status ) {
		echo '<form method="post" action="' . $url . '">';
		echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<input type="hidden" name="action" value="ats_reviews" /><input type="hidden" name="op" value="pause" />';
		echo '<button type="submit" class="button">Pause Drip</button>';
		echo '</form>';
	} elseif ( in_array( $status, array( 'paused' ), true ) && $counts['pending'] > 0 ) {
		echo '<form method="post" action="' . $url . '">';
		echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<input type="hidden" name="action" value="ats_reviews" /><input type="hidden" name="op" value="start" />';
		echo '<button type="submit" class="button button-primary">Start / Resume Drip</button>';
		echo '</form>';
	}

	// Publish all now (skip the drip).
	if ( $counts['pending'] > 0 ) {
		echo '<form method="post" action="' . $url . '" onsubmit="return confirm(\'Publish all ' . (int) $counts['pending'] . ' remaining reviews now? Their dates stay spread across the past year.\');">';
		echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<input type="hidden" name="action" value="ats_reviews" /><input type="hidden" name="op" value="publish_all" />';
		echo '<button type="submit" class="button">Publish All Now</button>';
		echo '</form>';
	}

	// Purge.
	if ( 'idle' !== $status || $counts['total'] > 0 ) {
		echo '<form method="post" action="' . $url . '" onsubmit="return confirm(\'Remove ALL generated reviews and reset? This cannot be undone.\');">';
		echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<input type="hidden" name="action" value="ats_reviews" /><input type="hidden" name="op" value="purge" />';
		echo '<button type="submit" class="button button-link-delete" style="color:#b32d2e;">Remove All Generated Reviews</button>';
		echo '</form>';
	}

	echo '</div>';

	// Preview table.
	$top = ats_reviews_top_planned( 15 );
	if ( ! empty( $top ) ) {
		echo '<h2>Top products by planned reviews</h2>';
		echo '<table class="widefat striped" style="max-width:720px;"><thead><tr><th>Product</th><th>Planned</th><th>Live</th></tr></thead><tbody>';
		foreach ( $top as $r ) {
			$title = get_the_title( (int) $r['product_id'] );
			printf(
				'<tr><td>%s</td><td>%d</td><td>%d</td></tr>',
				esc_html( $title ? $title : ( '#' . (int) $r['product_id'] ) ),
				(int) $r['n'],
				(int) $r['live']
			);
		}
		echo '</tbody></table>';
	}

	echo '</div>';
}
