<?php
/**
 * Review Generator — WP-CLI
 *
 * Mirrors the admin panel for headless control:
 *   wp ats-reviews build    Build (or rebuild) the plan; nothing goes live.
 *   wp ats-reviews start     Start/resume the drip.
 *   wp ats-reviews pause     Pause the drip.
 *   wp ats-reviews run       Publish all currently-due reviews now.
 *   wp ats-reviews status    Show counts and state.
 *   wp ats-reviews purge     Remove all generated reviews and reset.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

/**
 * Manage the automated product review generator.
 */
class ATS_Reviews_CLI {

	/**
	 * Build (or rebuild) the review plan. Generates pending rows; publishes nothing.
	 */
	public function build() {
		$summary = ats_reviews_build_plan();
		WP_CLI::log( sprintf( 'Products: %d', $summary['products'] ) );
		WP_CLI::log( sprintf( 'With reviews: %d', $summary['with_reviews'] ) );
		WP_CLI::log( sprintf( 'Zero-review products: %d', $summary['zero'] ) );
		WP_CLI::log( sprintf(
			'Total reviews queued: %d (5★:%d 4★:%d 3★:%d 2★:%d)',
			$summary['total'],
			$summary['five'],
			$summary['four'],
			isset( $summary['three'] ) ? $summary['three'] : 0,
			isset( $summary['two'] ) ? $summary['two'] : 0
		) );
		WP_CLI::success( 'Plan built (paused). Run "wp ats-reviews start" to begin the drip.' );
	}

	/**
	 * Start / resume the drip.
	 */
	public function start() {
		ats_reviews_schedule_cron();
		ats_reviews_set_state( array( 'status' => 'active' ) );
		WP_CLI::success( 'Drip started.' );
	}

	/**
	 * Pause the drip.
	 */
	public function pause() {
		ats_reviews_set_state( array( 'status' => 'paused' ) );
		WP_CLI::success( 'Drip paused.' );
	}

	/**
	 * Publish all currently-due reviews immediately.
	 *
	 * ## OPTIONS
	 *
	 * [--max=<n>]
	 * : Maximum reviews to publish this run. Default 500.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function run( $args, $assoc_args ) {
		$state = ats_reviews_get_state();
		if ( 'active' !== $state['status'] ) {
			WP_CLI::warning( 'State is "' . $state['status'] . '". Run "wp ats-reviews start" first.' );
			return;
		}
		$max       = isset( $assoc_args['max'] ) ? (int) $assoc_args['max'] : 500;
		$published = ats_reviews_publish_due( $max );
		WP_CLI::success( sprintf( 'Published %d due review(s).', $published ) );
	}

	/**
	 * Publish every remaining queued review immediately (keeps backdated dates).
	 *
	 * @subcommand publish-all
	 */
	public function publish_all() {
		$n = ats_reviews_publish_all();
		WP_CLI::success( sprintf( 'Published %d review(s). Queue drained.', $n ) );
	}

	/**
	 * Show status.
	 */
	public function status() {
		$state  = ats_reviews_get_state();
		$counts = ats_reviews_queue_counts();
		WP_CLI::log( 'Status:    ' . $state['status'] );
		WP_CLI::log( 'Planned:   ' . $counts['total'] );
		WP_CLI::log( 'Published: ' . $counts['published'] );
		WP_CLI::log( 'Pending:   ' . $counts['pending'] );
		if ( $state['window_end'] ) {
			WP_CLI::log( 'Window end: ' . $state['window_end'] );
		}
	}

	/**
	 * Remove all generated reviews and reset.
	 */
	public function purge() {
		$result = ats_reviews_purge_all();
		WP_CLI::success( sprintf( 'Removed %d reviews across %d products. Reset to idle.', $result['reviews_deleted'], $result['products_affected'] ) );
	}
}

WP_CLI::add_command( 'ats-reviews', 'ATS_Reviews_CLI' );
