<?php
/**
 * Review Generator — Publisher (drip)
 *
 * Surfaces due reviews from the queue as live WooCommerce review comments.
 * Driven by an hourly cron event and a throttled page-load fallback so it keeps
 * working even when WP-Cron is unreliable. The fallback is time-boxed and fully
 * guarded so it can never break a front-end render.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Insert one queue row as an approved, verified WooCommerce review comment.
 *
 * @param object $row Queue row.
 * @return int|false New comment ID, or false on failure.
 */
function ats_reviews_insert_comment( $row ) {
	$commentdata = array(
		'comment_post_ID'      => (int) $row->product_id,
		'comment_author'       => $row->author_name,
		'comment_author_email' => $row->author_email,
		'comment_author_url'   => '',
		'comment_content'      => $row->content,
		'comment_type'         => 'review',
		'comment_parent'       => 0,
		'user_id'              => 0,
		'comment_author_IP'    => '',
		'comment_agent'        => 'Mozilla/5.0',
		'comment_date'         => $row->display_date,
		'comment_date_gmt'     => get_gmt_from_date( $row->display_date ),
		'comment_approved'     => 1,
	);

	$comment_id = wp_insert_comment( $commentdata );
	if ( ! $comment_id ) {
		return false;
	}

	update_comment_meta( $comment_id, 'rating', (int) $row->rating );
	// Not marked as a verified purchase — these are not from real buyers, so we
	// deliberately do not assert the WooCommerce "Verified owner" badge.
	update_comment_meta( $comment_id, 'verified', 0 );
	update_comment_meta( $comment_id, ATS_REVIEWS_META_FLAG, 1 );

	return (int) $comment_id;
}

/**
 * Publish all reviews whose publish_at has passed, up to a cap.
 *
 * @param int $cap Maximum reviews to publish this run.
 * @return int Number published.
 */
function ats_reviews_publish_due( $cap ) {
	global $wpdb;

	$state = ats_reviews_get_state();
	if ( 'active' !== $state['status'] ) {
		return 0;
	}

	$table = ats_reviews_table();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return 0;
	}

	$now = current_time( 'mysql' );

	// Recover any rows left claimed by a crashed run (token carries its unix time).
	$wpdb->query( // phpcs:ignore
		"UPDATE {$table} SET status = 'pending'
		 WHERE status LIKE 'claim:%'
		 AND CAST( SUBSTRING_INDEX( SUBSTRING_INDEX( status, ':', 2 ), ':', -1 ) AS UNSIGNED ) < ( UNIX_TIMESTAMP() - 300 )"
	);

	// Atomically claim a disjoint batch so concurrent publishers (cron +
	// page-load + CLI) can never grab the same rows and double-publish.
	$token   = 'claim:' . time() . ':' . wp_rand( 100000, 999999 );
	$claimed = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table} SET status = %s WHERE status = 'pending' AND publish_at <= %s ORDER BY publish_at ASC LIMIT %d",
			$token,
			$now,
			(int) $cap
		)
	);

	if ( ! $claimed ) {
		ats_reviews_maybe_complete();
		return 0;
	}

	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s", $token ) ); // phpcs:ignore

	$affected  = array();
	$published = 0;

	foreach ( $rows as $row ) {
		$comment_id = ats_reviews_insert_comment( $row );
		if ( $comment_id ) {
			$wpdb->update(
				$table,
				array( 'status' => 'published', 'comment_id' => $comment_id ),
				array( 'id' => (int) $row->id ),
				array( '%s', '%d' ),
				array( '%d' )
			);
			$affected[] = (int) $row->product_id;
			$published++;
		} else {
			// Insert failed — release the claim so it retries later.
			$wpdb->update( $table, array( 'status' => 'pending' ), array( 'id' => (int) $row->id ), array( '%s' ), array( '%d' ) );
		}
	}

	if ( ! empty( $affected ) ) {
		ats_reviews_recount_products( $affected );
	}

	ats_reviews_maybe_complete();

	return $published;
}

/**
 * If no pending reviews remain, mark the run complete and stop the cron.
 */
function ats_reviews_maybe_complete() {
	global $wpdb;
	$table = ats_reviews_table();

	$pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending' OR status LIKE 'claim:%'" ); // phpcs:ignore
	if ( 0 === $pending ) {
		wp_clear_scheduled_hook( ATS_REVIEWS_CRON_HOOK );
		$state = ats_reviews_get_state();
		if ( 'active' === $state['status'] ) {
			ats_reviews_set_state( array( 'status' => 'completed' ) );
		}
	}
}

/**
 * Publish every remaining queued review immediately.
 *
 * Brings forward all pending publish times to now and drains the queue in
 * chunks. Display dates are left untouched, so the reviews still read as
 * spread across the past year. Used by "Publish All Now" / the CLI.
 *
 * @return int Total published.
 */
function ats_reviews_publish_all() {
	global $wpdb;
	$table = ats_reviews_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return 0;
	}

	// Make everything still pending due now (publish_at only — not display_date).
	$wpdb->query( "UPDATE {$table} SET publish_at = DATE_SUB( NOW(), INTERVAL 2 HOUR ) WHERE status = 'pending' AND publish_at > DATE_SUB( NOW(), INTERVAL 2 HOUR )" ); // phpcs:ignore

	// Must be active for the publisher to run.
	$state = ats_reviews_get_state();
	if ( 'active' !== $state['status'] ) {
		ats_reviews_set_state( array( 'status' => 'active' ) );
	}

	$total = 0;
	do {
		$n      = ats_reviews_publish_due( 200 );
		$total += $n;
	} while ( $n > 0 );

	return $total;
}

/**
 * Cron callback.
 */
function ats_reviews_cron_publish() {
	$config = ats_reviews_config();
	ats_reviews_publish_due( $config['cron_batch'] );
}
add_action( ATS_REVIEWS_CRON_HOOK, 'ats_reviews_cron_publish' );

/**
 * Page-load fallback. Throttled, time-boxed, and wrapped so a failure here can
 * never affect the page being rendered.
 */
function ats_reviews_pageload_drip() {
	if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}
	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$state = ats_reviews_get_state();
	if ( 'active' !== $state['status'] ) {
		return;
	}

	if ( get_transient( 'ats_reviews_pageload_lock' ) ) {
		return;
	}
	$config = ats_reviews_config();
	set_transient( 'ats_reviews_pageload_lock', 1, $config['pageload_throttle'] );

	try {
		ats_reviews_publish_due( $config['pageload_batch'] );
	} catch ( \Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ats_reviews_pageload_drip: ' . $e->getMessage() );
		}
	}
}
add_action( 'wp_loaded', 'ats_reviews_pageload_drip' );
