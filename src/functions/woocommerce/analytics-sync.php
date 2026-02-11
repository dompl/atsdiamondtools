<?php
/**
 * WooCommerce Analytics Auto-Sync
 *
 * Automatically rebuilds WooCommerce analytics lookup tables after
 * a WooCommerce Data Sync operation completes.
 *
 * Hooks into the Data Sync plugin's batch process and runs after
 * the finalize phase, so the user never has to manually sync analytics.
 *
 * Handles:
 * - Syncing missing orders to wc_order_stats
 * - Fixing NULL date_paid values (required by Analytics queries)
 * - Recalculating total_sales postmeta (bestseller queries)
 * - Regenerating wc_product_meta_lookup (stock counts)
 * - Clearing analytics cache and transients
 *
 * @package ATS Diamond Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent duplicate loading.
if ( defined( 'ATS_ANALYTICS_SYNC_LOADED' ) ) {
	return;
}
define( 'ATS_ANALYTICS_SYNC_LOADED', true );

/**
 * Hook into the Data Sync plugin's batch process AJAX handler.
 *
 * Runs at priority 1 (before the plugin's handler at 10) to check
 * if the batch is about to finalize. If so, registers a shutdown
 * function that runs analytics sync after the response is sent.
 */
add_action( 'wp_ajax_wc_sync_process_batch', function () {
	$state = get_option( 'wc_data_sync_batch_state' );

	if ( ! empty( $state ) && is_array( $state ) && 'finalize' === ( $state['phase'] ?? '' ) ) {
		register_shutdown_function( 'ats_run_analytics_sync_background' );
	}
}, 1 );

/**
 * Also hook into the legacy single-step execute sync.
 *
 * The Data Sync plugin may also use ajax_execute for non-batch syncs.
 * Register a shutdown function to run analytics sync after it completes.
 */
add_action( 'wp_ajax_wc_sync_execute', function () {
	register_shutdown_function( 'ats_run_analytics_sync_background' );
}, 1 );

/**
 * Run analytics sync in the background (after AJAX response is sent).
 *
 * This runs via register_shutdown_function, so the Data Sync response
 * has already been sent to the browser. The user sees "Sync complete!"
 * while this rebuilds analytics tables silently.
 */
function ats_run_analytics_sync_background() {
	// Increase limits since we're running post-response.
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 );
	}

	$log = ats_run_analytics_sync( 3 );

	// Log results for debugging.
	if ( function_exists( 'error_log' ) ) {
		error_log( 'ATS Analytics Auto-Sync: ' . wp_json_encode( $log ) );
	}
}

/**
 * Run the full analytics sync process.
 *
 * Can be called manually or automatically after Data Sync completes.
 *
 * @param int $months Number of months to look back for missing orders. 0 = all.
 * @return array Log of actions taken.
 */
function ats_run_analytics_sync( $months = 3 ) {
	global $wpdb;
	$prefix = $wpdb->prefix;
	$log    = [];

	// --- Step 1: Sync missing orders to wc_order_stats ---
	$date_filter = '';
	if ( $months > 0 ) {
		$since       = gmdate( 'Y-m-d', strtotime( "-{$months} months" ) );
		$date_filter = $wpdb->prepare( ' AND p.post_date >= %s', $since );
	}

	$missing_ids = $wpdb->get_col(
		"SELECT p.ID
		 FROM {$prefix}posts p
		 WHERE p.post_type = 'shop_order'
		   AND p.post_status IN ('wc-completed','wc-processing','wc-refunded','wc-cancelled','wc-on-hold')
		   {$date_filter}
		   AND p.ID NOT IN (SELECT order_id FROM {$prefix}wc_order_stats)"
	);

	$synced = 0;
	if ( ! empty( $missing_ids ) ) {
		$sync_class = 'Automattic\\WooCommerce\\Admin\\API\\Reports\\Orders\\Stats\\DataStore';

		if ( class_exists( $sync_class ) ) {
			foreach ( $missing_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$sync_class::sync_order( $order->get_id() );
					$synced++;
				}
			}
		}
	}
	$log['orders_synced'] = $synced;

	// --- Step 2: Fix NULL date_paid values ---
	$date_filter_stats = '';
	if ( $months > 0 ) {
		$since             = gmdate( 'Y-m-d', strtotime( "-{$months} months" ) );
		$date_filter_stats = $wpdb->prepare( ' AND date_created >= %s', $since );
	}

	$fixed_paid = (int) $wpdb->query(
		"UPDATE {$prefix}wc_order_stats
		 SET date_paid = date_created
		 WHERE (date_paid IS NULL OR date_paid = '0000-00-00 00:00:00')
		   AND status IN ('wc-completed','wc-processing')
		   {$date_filter_stats}"
	);
	$log['dates_fixed'] = $fixed_paid;

	$fixed_completed = (int) $wpdb->query(
		"UPDATE {$prefix}wc_order_stats
		 SET date_completed = date_created
		 WHERE (date_completed IS NULL OR date_completed = '0000-00-00 00:00:00')
		   AND status = 'wc-completed'
		   {$date_filter_stats}"
	);
	$log['completion_dates_fixed'] = $fixed_completed;

	// --- Step 3: Recalculate total_sales postmeta ---
	// After DB sync, product IDs change but total_sales meta is 0/missing.
	// Calculate from actual order item meta (_product_id + _qty) for
	// completed/processing orders, then update postmeta and lookup table.
	$sales_data = $wpdb->get_results(
		"SELECT CAST(oim_pid.meta_value AS UNSIGNED) as product_id,
		        SUM(CAST(oim_qty.meta_value AS UNSIGNED)) as total_sold
		 FROM {$prefix}woocommerce_order_items oi
		 INNER JOIN {$prefix}woocommerce_order_itemmeta oim_pid
		     ON oim_pid.order_item_id = oi.order_item_id AND oim_pid.meta_key = '_product_id'
		 INNER JOIN {$prefix}woocommerce_order_itemmeta oim_qty
		     ON oim_qty.order_item_id = oi.order_item_id AND oim_qty.meta_key = '_qty'
		 INNER JOIN {$prefix}posts o
		     ON o.ID = oi.order_id AND o.post_status IN ('wc-completed','wc-processing')
		 INNER JOIN {$prefix}posts p
		     ON p.ID = CAST(oim_pid.meta_value AS UNSIGNED) AND p.post_type = 'product'
		 WHERE oi.order_item_type = 'line_item'
		 GROUP BY product_id"
	);

	$sales_updated = 0;
	if ( ! empty( $sales_data ) ) {
		foreach ( $sales_data as $row ) {
			update_post_meta( (int) $row->product_id, 'total_sales', $row->total_sold );
			$sales_updated++;
		}

		// Sync total_sales into wc_product_meta_lookup for fast queries.
		$wpdb->query(
			"UPDATE {$prefix}wc_product_meta_lookup pml
			 INNER JOIN {$prefix}postmeta pm
			     ON pm.post_id = pml.product_id AND pm.meta_key = 'total_sales'
			 SET pml.total_sales = CAST(pm.meta_value AS DECIMAL(10,0))"
		);
	}
	$log['total_sales_updated'] = $sales_updated;

	// --- Step 4: Regenerate product lookup tables ---
	// Note: Shipping classes are now handled by the Data Sync plugin
	// (fix added to import_product in class-wc-sync-data.php).
	if ( function_exists( 'wc_update_product_lookup_tables' ) ) {
		wc_update_product_lookup_tables();
		$log['product_lookup'] = 'triggered';
	}

	if ( class_exists( 'WC_REST_System_Status_Tools_V2_Controller' ) ) {
		$tools = new WC_REST_System_Status_Tools_V2_Controller();
		$tools->execute_tool( 'regenerate_product_lookup_tables' );
		$log['product_lookup_wc_tool'] = 'executed';
	}

	// --- Step 5: Clear caches ---
	if ( class_exists( 'WC_REST_System_Status_Tools_V2_Controller' ) ) {
		$tools = new WC_REST_System_Status_Tools_V2_Controller();
		$tools->execute_tool( 'clear_woocommerce_analytics_cache' );
		$log['analytics_cache'] = 'cleared';
	}

	$transients_cleared = (int) $wpdb->query(
		"DELETE FROM {$prefix}options
		 WHERE option_name LIKE '_transient_wc%'
		    OR option_name LIKE '_transient_timeout_wc%'"
	);
	$log['transients_cleared'] = $transients_cleared;

	$wpdb->query(
		"DELETE FROM {$prefix}options
		 WHERE option_name LIKE '_transient_rest_api%'
		    OR option_name LIKE '_transient_timeout_rest_api%'"
	);

	// Invalidate shipping cache so zones/methods work after sync.
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::invalidate_cache_group( 'shipping_zones' );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	wp_cache_flush();
	$log['cache_flushed'] = true;

	return $log;
}

/**
 * WP-CLI command for manual analytics sync.
 *
 * Usage: wp eval 'ats_run_analytics_sync(3);'
 * Or:    wp eval 'print_r(ats_run_analytics_sync(6));'
 */
