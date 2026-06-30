<?php
/**
 * Review Generator — Core
 *
 * Shared constants, configuration, queue-table install, run-state, rating
 * recount + cache flush, and teardown for the automated review system.
 *
 * The system pre-builds a plan of believable WooCommerce reviews (one row per
 * review in a dedicated queue table) and drips them live over a 30-day window so
 * they do not all appear at once. Every generated review is tagged with the
 * meta flag below so the whole set can be removed cleanly.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ATS_REVIEWS_META_FLAG' ) ) {
	define( 'ATS_REVIEWS_META_FLAG', '_ats_generated_review' );
}
if ( ! defined( 'ATS_REVIEWS_CRON_HOOK' ) ) {
	define( 'ATS_REVIEWS_CRON_HOOK', 'ats_reviews_drip_event' );
}
if ( ! defined( 'ATS_REVIEWS_DB_VERSION' ) ) {
	define( 'ATS_REVIEWS_DB_VERSION', '2' );
}

/**
 * Central tunables for the whole system. One place to adjust volume, ratings,
 * cadence and flavour ratios.
 *
 * @return array
 */
function ats_reviews_config() {
	return array(
		'drip_days'         => 30,   // Window over which reviews surface.
		'backdate_days'     => 912,  // Oldest displayed date (~2.5 years ago).
		'recent_min_days'   => 2,    // Newest displayed date (~2 days ago).
		'cron_batch'        => 60,   // Max reviews published per cron tick.
		'pageload_batch'    => 12,   // Max reviews published per page-load fallback.
		'pageload_throttle' => 15 * MINUTE_IN_SECONDS,
		// Per-product target count by sales-rank percentile (0 = top seller).
		// Each tier: products with percentile <= 'p' get a count in [min,max],
		// interpolated by intra-tier position so the very top sellers get the most.
		// Ranges overlap deliberately + extra jitter (see ats_reviews_target_count)
		// so counts vary a lot and don't look tiered, while best sellers still
		// trend highest. Hard cap stays at 9.
		'tiers'             => array(
			array( 'p' => 0.10, 'min' => 6, 'max' => 9 ),
			array( 'p' => 0.30, 'min' => 4, 'max' => 8 ),
			array( 'p' => 0.70, 'min' => 2, 'max' => 6 ),
			array( 'p' => 1.01, 'min' => 1, 'max' => 4 ),
		),
		'zero_pct'          => 0.01, // ~1% of slowest sellers get no reviews.
		'top_five_star_pct' => 0.20, // Top 20% of sellers are 100% 5-star.
		'four_star_ratio'   => 0.10, // ~10% of reviews are 4-star (others).
		'min_avg'           => 4.8,  // Cap 4-stars so each product stays >= this.
		// Least-selling products (sales-rank percentile beyond low_tier_p) also
		// pick up the odd 3-star / 2-star so they don't all look flawless.
		'low_tier_p'        => 0.60,
		'three_star_pct'    => 0.35, // Chance a low-tier product gets a 3-star.
		'two_star_pct'      => 0.15, // Chance a low-tier product gets a 2-star.
		'typo_pct'          => 0.35, // Share of reviews that get spelling mistakes.
		'caps_ignore_pct'   => 0.30, // Share with sloppy capitalisation.
		'male_pct'          => 0.80, // Share of (non-foreign) names that are male.
		'name_mention_pct'  => 0.33, // Share that mention the product by name.
		'foreign_pct'       => 0.04, // ~4% foreign-sounding names.
	);
}

/**
 * Fully-qualified queue table name.
 *
 * @return string
 */
function ats_reviews_table() {
	global $wpdb;
	return $wpdb->prefix . 'ats_review_queue';
}

/**
 * Create the queue table if it is missing or out of date.
 */
function ats_reviews_install_table() {
	global $wpdb;

	if ( get_option( 'ats_reviews_db_version' ) === ATS_REVIEWS_DB_VERSION ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table           = ats_reviews_table();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		product_id BIGINT UNSIGNED NOT NULL,
		author_name VARCHAR(120) NOT NULL DEFAULT '',
		author_email VARCHAR(180) NOT NULL DEFAULT '',
		rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
		content TEXT NOT NULL,
		display_date DATETIME NOT NULL,
		publish_at DATETIME NOT NULL,
		status VARCHAR(64) NOT NULL DEFAULT 'pending',
		comment_id BIGINT UNSIGNED NULL DEFAULT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY product_id (product_id),
		KEY status_publish (status, publish_at)
	) {$charset_collate};";

	dbDelta( $sql );

	// Ensure the status column is wide enough for claim tokens on existing
	// installs (was VARCHAR(20) in db version 1, which truncated tokens).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "ALTER TABLE {$table} MODIFY status VARCHAR(64) NOT NULL DEFAULT 'pending'" );

	update_option( 'ats_reviews_db_version', ATS_REVIEWS_DB_VERSION, false );
}
add_action( 'admin_init', 'ats_reviews_install_table' );

/**
 * Read the run state.
 *
 * status: 'idle' (no plan) | 'paused' (plan built, not publishing) | 'active'.
 *
 * @return array
 */
function ats_reviews_get_state() {
	$defaults = array(
		'status'        => 'idle',
		'built_at'      => '',
		'total_planned' => 0,
		'window_start'  => '',
		'window_end'    => '',
	);
	$state = get_option( 'ats_reviews_state', array() );
	return wp_parse_args( is_array( $state ) ? $state : array(), $defaults );
}

/**
 * Persist the run state (merged with current).
 *
 * @param array $changes Partial state.
 * @return array New state.
 */
function ats_reviews_set_state( array $changes ) {
	$state = array_merge( ats_reviews_get_state(), $changes );
	update_option( 'ats_reviews_state', $state, false );
	return $state;
}

/**
 * Queue counts by status, for the admin dashboard.
 *
 * @return array { pending, published, total }
 */
function ats_reviews_queue_counts() {
	global $wpdb;
	$table = ats_reviews_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return array( 'pending' => 0, 'published' => 0, 'total' => 0 );
	}

	$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS n FROM {$table} GROUP BY status", ARRAY_A ); // phpcs:ignore
	$out  = array( 'pending' => 0, 'published' => 0, 'total' => 0 );
	foreach ( (array) $rows as $row ) {
		$out[ $row['status'] ] = (int) $row['n'];
		$out['total']        += (int) $row['n'];
	}
	return $out;
}

/**
 * Recount WooCommerce rating caches for products and flush the theme's product
 * card transients. Required because wp_insert_comment() does not fire the
 * comment_post hook WooCommerce normally recounts on.
 *
 * @param array $product_ids Affected product IDs.
 */
function ats_reviews_recount_products( array $product_ids ) {
	$product_ids = array_values( array_unique( array_map( 'intval', $product_ids ) ) );
	if ( empty( $product_ids ) ) {
		return;
	}

	if ( class_exists( 'WC_Comments' ) ) {
		foreach ( $product_ids as $pid ) {
			// Recomputes _wc_rating_count, _wc_average_rating, _wc_review_count.
			WC_Comments::clear_transients( $pid );
			if ( function_exists( 'wc_delete_product_transients' ) ) {
				wc_delete_product_transients( $pid );
			}
		}
	}

	// Flush the theme's 12h product-card transients so listing stars update too.
	if ( function_exists( 'ats_clear_products_cache' ) ) {
		ats_clear_products_cache( $product_ids );
	}
}

/**
 * Remove every generated review and reset the system to idle.
 *
 * Deletes only comments tagged with ATS_REVIEWS_META_FLAG (the single real
 * review and any genuine ones are untouched), empties the queue table, recounts
 * affected products, and clears the cron event.
 *
 * @return array { reviews_deleted, products_affected }
 */
function ats_reviews_purge_all() {
	global $wpdb;

	$comment_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key = %s",
			ATS_REVIEWS_META_FLAG
		)
	);

	$product_ids = array();
	foreach ( $comment_ids as $cid ) {
		$comment = get_comment( $cid );
		if ( $comment ) {
			$product_ids[] = (int) $comment->comment_post_ID;
		}
		wp_delete_comment( (int) $cid, true );
	}

	$table = ats_reviews_table();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore
	}

	ats_reviews_recount_products( $product_ids );

	wp_clear_scheduled_hook( ATS_REVIEWS_CRON_HOOK );

	update_option(
		'ats_reviews_state',
		array(
			'status'        => 'idle',
			'built_at'      => '',
			'total_planned' => 0,
			'window_start'  => '',
			'window_end'    => '',
		),
		false
	);

	return array(
		'reviews_deleted'   => count( $comment_ids ),
		'products_affected' => count( array_unique( $product_ids ) ),
	);
}

/**
 * Ensure the hourly drip cron is scheduled.
 */
function ats_reviews_schedule_cron() {
	if ( ! wp_next_scheduled( ATS_REVIEWS_CRON_HOOK ) ) {
		wp_schedule_event( time() + 300, 'hourly', ATS_REVIEWS_CRON_HOOK );
	}
}
