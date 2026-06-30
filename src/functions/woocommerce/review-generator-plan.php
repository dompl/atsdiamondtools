<?php
/**
 * Review Generator — Plan builder
 *
 * Builds the full queue of pending reviews: per-product counts scaled to sales
 * rank, rating split (top sellers pristine 5-star, a few 4-star elsewhere),
 * backdated display dates spread over a year, and publish times jittered across
 * the 30-day drip window. Pending rows are invisible to shoppers until the
 * publisher surfaces them.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch published products with their sales figures, ranked high to low.
 *
 * @return array List of array{ id:int, sales:int }.
 */
function ats_reviews_get_ranked_products() {
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$products = array();
	foreach ( $ids as $id ) {
		$sales      = (int) get_post_meta( $id, 'total_sales', true );
		$products[] = array( 'id' => (int) $id, 'sales' => $sales );
	}

	usort(
		$products,
		static function ( $a, $b ) {
			return $b['sales'] <=> $a['sales'];
		}
	);

	return $products;
}

/**
 * Decide a target review count for a product at a given sales-rank percentile.
 *
 * @param float $percentile 0 (top seller) .. 1 (slowest).
 * @param array $config     ats_reviews_config().
 * @return int
 */
function ats_reviews_target_count( $percentile, array $config ) {
	$prev_p = 0.0;
	foreach ( $config['tiers'] as $tier ) {
		if ( $percentile <= $tier['p'] ) {
			$span = max( 0.0001, $tier['p'] - $prev_p );
			$frac = ( $percentile - $prev_p ) / $span; // 0 at tier top .. 1 at tier bottom.
			$base = $tier['max'] - $frac * ( $tier['max'] - $tier['min'] );
			// Heavy +/- ~30% jitter and a global [1,9] clamp (not tier bounds) so
			// counts vary widely and overlap between tiers — best sellers still
			// trend highest on average, but it never looks uniform/tiered.
			$jitter = $base * ( mt_rand( -30, 30 ) / 100 );
			$count  = (int) round( $base + $jitter );
			return max( 1, min( 9, $count ) );
		}
		$prev_p = $tier['p'];
	}
	return 1;
}

/**
 * Decide how many of a product's reviews are 4-star (rest are 5-star), capped so
 * the product average stays at or above config min_avg.
 *
 * @param int   $count    Review count.
 * @param bool  $is_top   Whether this is a top-tier seller (always 5-star).
 * @param array $config   ats_reviews_config().
 * @return int Number of 4-star reviews.
 */
function ats_reviews_four_star_count( $count, $is_top, array $config ) {
	if ( $is_top || $count < 1 ) {
		return 0;
	}
	$target = (int) round( $count * $config['four_star_ratio'] );
	$max    = (int) floor( $count * ( 5 - $config['min_avg'] ) );
	return max( 0, min( $target, $max ) );
}

/**
 * Build the array of ratings for one product.
 *
 * Top sellers are pure 5-star. Others get a few 4-star (capped to keep the
 * average up), and the least-selling products additionally pick up the odd
 * 3-star / 2-star. At least half of every product's reviews stay 5-star so even
 * a weak seller reads "good, not perfect" rather than damning.
 *
 * @param int   $count      Number of reviews.
 * @param float $percentile Sales-rank percentile (0 = top seller).
 * @param bool  $is_top     Whether this is a top-tier seller.
 * @param array $config     ats_reviews_config().
 * @return int[] Ratings.
 */
function ats_reviews_build_ratings( $count, $percentile, $is_top, array $config ) {
	$ratings = array_fill( 0, max( 0, $count ), 5 );
	if ( $is_top || $count < 1 ) {
		return $ratings;
	}

	$max_down = (int) floor( $count / 2 ); // keep at least half at 5 stars
	$pos      = 0;

	// A few 4-star (bounded so the average stays >= min_avg).
	$four = min( ats_reviews_four_star_count( $count, false, $config ), $max_down );
	for ( $i = 0; $i < $four; $i++ ) {
		$ratings[ $pos++ ] = 4;
	}

	// Least-selling products pick up the odd lower rating.
	if ( $percentile > $config['low_tier_p'] ) {
		if ( $pos < $max_down && ( mt_rand( 1, 100 ) / 100 ) <= $config['three_star_pct'] ) {
			$ratings[ $pos++ ] = 3;
		}
		if ( $pos < $max_down && ( mt_rand( 1, 100 ) / 100 ) <= $config['two_star_pct'] ) {
			$ratings[ $pos++ ] = 2;
		}
	}

	return $ratings;
}

/**
 * Pick a backdated display date (local MySQL string) spread roughly evenly
 * across the past year: oldest ~365 days ago, newest ~2 days ago.
 *
 * @param int   $now_ts Local "now" timestamp.
 * @param array $config ats_reviews_config().
 * @return string
 */
function ats_reviews_random_display_date( $now_ts, array $config ) {
	$min    = $config['recent_min_days'] * DAY_IN_SECONDS;
	$max    = $config['backdate_days'] * DAY_IN_SECONDS;
	$offset = mt_rand( $min, $max );
	return date( 'Y-m-d H:i:s', $now_ts - $offset ); // phpcs:ignore WordPress.DateTime
}

/**
 * Pick a publish time (local MySQL string) jittered across the drip window.
 * A small share lands in the past so an initial batch is due immediately.
 *
 * @param int   $now_ts Local "now" timestamp.
 * @param array $config ats_reviews_config().
 * @return string
 */
function ats_reviews_random_publish_at( $now_ts, array $config ) {
	$offset = mt_rand( 0, $config['drip_days'] * DAY_IN_SECONDS ) - DAY_IN_SECONDS;
	return date( 'Y-m-d H:i:s', $now_ts + $offset ); // phpcs:ignore WordPress.DateTime
}

/**
 * Build the full review plan. Generates pending queue rows; publishes nothing.
 *
 * @return array Summary { products, with_reviews, zero, total, five, four, tiers }.
 */
function ats_reviews_build_plan() {
	global $wpdb;

	ats_reviews_install_table();
	$table  = ats_reviews_table();
	$config = ats_reviews_config();

	// Start clean so re-building never doubles up.
	$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore

	$products = ats_reviews_get_ranked_products();
	$n        = count( $products );
	if ( 0 === $n ) {
		return array( 'products' => 0, 'with_reviews' => 0, 'zero' => 0, 'total' => 0, 'five' => 0, 'four' => 0 );
	}

	// Slowest ~1% get no reviews at all.
	$zero_count = max( 1, (int) ceil( $n * $config['zero_pct'] ) );
	$zero_ids   = array();
	for ( $i = $n - $zero_count; $i < $n; $i++ ) {
		$zero_ids[ $products[ $i ]['id'] ] = true;
	}

	$now_ts   = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
	$created  = current_time( 'mysql' );
	$top_cut  = (int) floor( $n * $config['top_five_star_pct'] );
	$seen     = array();
	$summary  = array(
		'products'     => $n,
		'with_reviews' => 0,
		'zero'         => 0,
		'total'        => 0,
		'five'         => 0,
		'four'         => 0,
		'three'        => 0,
		'two'          => 0,
	);

	$rows   = array();
	$insert = static function () use ( &$rows, $wpdb, $table ) {
		if ( empty( $rows ) ) {
			return;
		}
		$placeholders = implode( ',', array_fill( 0, count( $rows ), '(%d,%s,%s,%d,%s,%s,%s,%s,%s)' ) );
		$values       = array();
		foreach ( $rows as $r ) {
			array_push( $values, $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7], $r[8] );
		}
		$sql = "INSERT INTO {$table} (product_id,author_name,author_email,rating,content,display_date,publish_at,status,created_at) VALUES {$placeholders}";
		$wpdb->query( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore
		$rows = array();
	};

	foreach ( $products as $index => $row ) {
		$product_id = $row['id'];

		if ( isset( $zero_ids[ $product_id ] ) ) {
			$summary['zero']++;
			continue;
		}

		$percentile = ( $n > 1 ) ? ( $index / ( $n - 1 ) ) : 0.0;
		$count      = ats_reviews_target_count( $percentile, $config );
		if ( $count < 1 ) {
			$summary['zero']++;
			continue;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			continue;
		}

		$flavour    = ats_reviews_flavour_for_product( $product );
		$is_top     = $index < $top_cut;
		$ratings    = ats_reviews_build_ratings( $count, $percentile, $is_top, $config );
		$used_names = array();

		$summary['with_reviews']++;

		for ( $j = 0; $j < $count; $j++ ) {
			$rating = $ratings[ $j ];

			// Unique-ish identity per product.
			$tries = 0;
			do {
				$identity = ats_reviews_generate_identity( $config );
				$tries++;
			} while ( isset( $used_names[ $identity['display'] ] ) && $tries < 6 );
			$used_names[ $identity['display'] ] = true;

			// Unique content store-wide.
			$tries = 0;
			do {
				$content = ats_reviews_compose( $product, $rating, $flavour, $config );
				$hash    = md5( $content );
				$tries++;
			} while ( isset( $seen[ $hash ] ) && $tries < 8 );
			$seen[ $hash ] = true;

			$rows[] = array(
				$product_id,
				$identity['display'],
				$identity['email'],
				$rating,
				$content,
				ats_reviews_random_display_date( $now_ts, $config ),
				ats_reviews_random_publish_at( $now_ts, $config ),
				'pending',
				$created,
			);

			$summary['total']++;
			$rating_key = array( 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five' );
			if ( isset( $rating_key[ $rating ] ) ) {
				$summary[ $rating_key[ $rating ] ]++;
			}

			if ( count( $rows ) >= 200 ) {
				$insert();
			}
		}
	}

	$insert();

	ats_reviews_set_state(
		array(
			'status'        => 'paused', // Built but not yet dripping (preview-first).
			'built_at'      => $created,
			'total_planned' => $summary['total'],
			'window_start'  => $created,
			'window_end'    => date( 'Y-m-d H:i:s', $now_ts + $config['drip_days'] * DAY_IN_SECONDS ), // phpcs:ignore WordPress.DateTime
		)
	);

	return $summary;
}
