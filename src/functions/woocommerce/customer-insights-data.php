<?php
/**
 * Customer Insights — data layer
 *
 * Ranking query, filters, segments, and cache over WooCommerce's analytics
 * lookup tables. If figures ever look stale, WooCommerce → Status → Tools
 * has a "regenerate order lookup tables" action.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order statuses counted in rankings.
 */
function ats_ci_counted_statuses() {
	return array( 'wc-completed', 'wc-processing' );
}

/**
 * Statuses as a quoted SQL list. Values come from the fixed list above.
 */
function ats_ci_statuses_sql() {
	return "'" . implode( "','", array_map( 'esc_sql', ats_ci_counted_statuses() ) ) . "'";
}

/**
 * Segment thresholds.
 */
function ats_ci_thresholds() {
	return apply_filters(
		'ats_customer_insights_segment_thresholds',
		array(
			'dormant_days'       => 180,
			'at_risk_multiplier' => 1.5,
			'champion_orders'    => 5,
		)
	);
}

/**
 * Segment for a ranking row. Rules evaluated top-down, first match wins.
 *
 * @param object $row Row with att_orders, avg_gap_days, days_since_last.
 */
function ats_ci_segment( $row ) {
	$t    = ats_ci_thresholds();
	$days = null === $row->days_since_last ? null : (int) $row->days_since_last;

	if ( null !== $days && $days > (int) $t['dormant_days'] ) {
		return 'dormant';
	}
	if ( (int) $row->att_orders >= 3 && (float) $row->avg_gap_days > 0
		&& $days > (float) $t['at_risk_multiplier'] * (float) $row->avg_gap_days ) {
		return 'at-risk';
	}
	if ( 1 === (int) $row->att_orders ) {
		return 'one-time';
	}
	if ( (int) $row->att_orders >= (int) $t['champion_orders'] ) {
		return 'champion';
	}
	return 'loyal';
}

/**
 * Normalise request params ($_GET-shaped) into query args.
 *
 * @param array $source Raw params.
 */
function ats_ci_parse_args( array $source ) {
	$range = isset( $source['range'] ) ? sanitize_key( $source['range'] ) : '90d';
	if ( ! in_array( $range, array( '30d', '90d', '12m', 'all', 'custom' ), true ) ) {
		$range = '90d';
	}

	$now  = current_time( 'timestamp' );
	$from = '';
	$to   = '';
	if ( '30d' === $range ) {
		$from = gmdate( 'Y-m-d', $now - 30 * DAY_IN_SECONDS );
	} elseif ( '90d' === $range ) {
		$from = gmdate( 'Y-m-d', $now - 90 * DAY_IN_SECONDS );
	} elseif ( '12m' === $range ) {
		$from = gmdate( 'Y-m-d', $now - 365 * DAY_IN_SECONDS );
	} elseif ( 'custom' === $range ) {
		$df   = isset( $source['date_from'] ) ? sanitize_text_field( wp_unslash( $source['date_from'] ) ) : '';
		$dt   = isset( $source['date_to'] ) ? sanitize_text_field( wp_unslash( $source['date_to'] ) ) : '';
		$from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $df ) ? $df : '';
		$to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dt ) ? $dt : '';
	}

	$orderby = isset( $source['orderby'] ) ? sanitize_key( $source['orderby'] ) : 'units';
	if ( ! in_array( $orderby, array( 'units', 'spend', 'orders', 'last_order' ), true ) ) {
		$orderby = 'units';
	}
	$per_page = isset( $source['per_page'] ) ? absint( $source['per_page'] ) : 25;
	if ( ! in_array( $per_page, array( 25, 50, 100 ), true ) ) {
		$per_page = 25;
	}
	$buyer = isset( $source['buyer'] ) ? sanitize_key( $source['buyer'] ) : 'all';
	if ( ! in_array( $buyer, array( 'all', 'repeat', 'one-time' ), true ) ) {
		$buyer = 'all';
	}
	$account = isset( $source['account'] ) ? sanitize_key( $source['account'] ) : 'all';
	if ( ! in_array( $account, array( 'all', 'registered', 'guest' ), true ) ) {
		$account = 'all';
	}

	return array(
		'range'        => $range,
		'date_from'    => $from,
		'date_to'      => $to,
		'min_units'    => isset( $source['min_units'] ) ? absint( $source['min_units'] ) : 0,
		'min_orders'   => isset( $source['min_orders'] ) ? absint( $source['min_orders'] ) : 0,
		'min_spend'    => isset( $source['min_spend'] ) ? (float) $source['min_spend'] : 0.0,
		'dormant_days' => isset( $source['dormant'] ) ? absint( $source['dormant'] ) : 0,
		'at_risk'      => ! empty( $source['at_risk'] ),
		'coupon'       => isset( $source['coupon'] ) ? sanitize_text_field( wp_unslash( $source['coupon'] ) ) : '',
		'product'      => isset( $source['product'] ) ? sanitize_text_field( wp_unslash( $source['product'] ) ) : '',
		'category_id'  => isset( $source['category'] ) ? absint( $source['category'] ) : 0,
		'buyer'        => $buyer,
		'account'      => $account,
		'orderby'      => $orderby,
		'per_page'     => $per_page,
		'paged'        => isset( $source['paged'] ) ? max( 1, absint( $source['paged'] ) ) : 1,
		'nocache'      => ! empty( $source['nocache'] ),
	);
}

/**
 * Run the ranking query (uncached).
 *
 * @param array $args Fully-populated args (see ats_ci_get_customers defaults).
 * @return array { rows: object[], total: int }
 */
function ats_ci_query_customers( array $args ) {
	global $wpdb;
	$p        = $wpdb->prefix;
	$statuses = ats_ci_statuses_sql();
	$t        = ats_ci_thresholds();

	$where  = array( '1=1' );
	$having = array();

	if ( $args['date_from'] ) {
		$where[] = $wpdb->prepare( 'os.date_created >= %s', $args['date_from'] . ' 00:00:00' );
	}
	if ( $args['date_to'] ) {
		$where[] = $wpdb->prepare( 'os.date_created <= %s', $args['date_to'] . ' 23:59:59' );
	}
	if ( 'registered' === $args['account'] ) {
		$where[] = 'cl.user_id IS NOT NULL';
	} elseif ( 'guest' === $args['account'] ) {
		$where[] = 'cl.user_id IS NULL';
	}

	if ( 'any' === $args['coupon'] ) {
		$having[] = 'coupon_orders > 0';
	} elseif ( '' !== $args['coupon'] ) {
		$where[] = $wpdb->prepare(
			"EXISTS ( SELECT 1 FROM {$p}wc_order_coupon_lookup ocl
			          JOIN {$p}posts cp ON cp.ID = ocl.coupon_id
			          WHERE ocl.order_id = os.order_id AND cp.post_title = %s )",
			$args['coupon']
		);
	}

	if ( '' !== $args['product'] ) {
		$like    = '%' . $wpdb->esc_like( $args['product'] ) . '%';
		$where[] = $wpdb->prepare(
			"EXISTS ( SELECT 1 FROM {$p}wc_order_product_lookup opl
			          JOIN {$p}posts pp ON pp.ID = opl.product_id
			          WHERE opl.order_id = os.order_id AND pp.post_title LIKE %s )",
			$like
		);
	}
	if ( $args['category_id'] ) {
		$where[] = $wpdb->prepare(
			"EXISTS ( SELECT 1 FROM {$p}wc_order_product_lookup opl
			          JOIN {$p}term_relationships tr ON tr.object_id = opl.product_id
			          JOIN {$p}term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			          WHERE opl.order_id = os.order_id
			            AND tt.taxonomy = 'product_cat' AND tt.term_id = %d )",
			$args['category_id']
		);
	}

	if ( $args['min_units'] ) {
		$having[] = $wpdb->prepare( 'units >= %d', $args['min_units'] );
	}
	if ( $args['min_orders'] ) {
		$having[] = $wpdb->prepare( 'orders_count >= %d', $args['min_orders'] );
	}
	if ( $args['min_spend'] > 0 ) {
		$having[] = $wpdb->prepare( 'total_spend >= %f', $args['min_spend'] );
	}
	if ( $args['dormant_days'] ) {
		$having[] = $wpdb->prepare( 'days_since_last >= %d', $args['dormant_days'] );
	}
	if ( $args['at_risk'] ) {
		$having[] = $wpdb->prepare(
			'att_orders >= 3 AND avg_gap_days > 0 AND days_since_last > %f * avg_gap_days',
			(float) $t['at_risk_multiplier']
		);
	}
	if ( 'repeat' === $args['buyer'] ) {
		$having[] = 'att_orders >= 2';
	} elseif ( 'one-time' === $args['buyer'] ) {
		$having[] = 'att_orders = 1';
	}

	$orderby_map = array(
		'units'      => 'units DESC',
		'spend'      => 'total_spend DESC',
		'orders'     => 'orders_count DESC',
		'last_order' => 'last_order DESC',
	);
	$order_sql   = $orderby_map[ $args['orderby'] ];

	$where_sql  = implode( ' AND ', $where );
	$having_sql = $having ? 'HAVING ' . implode( ' AND ', $having ) : '';

	$select = "SELECT cl.customer_id, cl.user_id, cl.first_name, cl.last_name, cl.email, cl.country, cl.city,
		COUNT(DISTINCT os.order_id) AS orders_count,
		SUM(os.num_items_sold)      AS units,
		SUM(os.total_sales)         AS total_spend,
		MIN(os.date_created)        AS first_order,
		MAX(os.date_created)        AS last_order,
		SUM( CASE WHEN cpn.order_id IS NULL THEN 0 ELSE 1 END ) AS coupon_orders,
		att.att_orders, att.att_units, att.att_spend, att.att_first, att.att_last,
		CASE WHEN att.att_orders > 1
		     THEN DATEDIFF( att.att_last, att.att_first ) / ( att.att_orders - 1 )
		     ELSE NULL END          AS avg_gap_days,
		DATEDIFF( NOW(), att.att_last ) AS days_since_last";

	$core = "FROM {$p}wc_customer_lookup cl
		JOIN {$p}wc_order_stats os
		  ON os.customer_id = cl.customer_id
		 AND os.parent_id = 0
		 AND os.status IN ( {$statuses} )
		LEFT JOIN ( SELECT DISTINCT order_id FROM {$p}wc_order_coupon_lookup ) cpn
		  ON cpn.order_id = os.order_id
		JOIN (
			SELECT customer_id,
			       COUNT(*)            AS att_orders,
			       SUM(num_items_sold) AS att_units,
			       SUM(total_sales)    AS att_spend,
			       MIN(date_created)   AS att_first,
			       MAX(date_created)   AS att_last
			FROM {$p}wc_order_stats
			WHERE parent_id = 0 AND status IN ( {$statuses} )
			GROUP BY customer_id
		) att ON att.customer_id = cl.customer_id
		WHERE {$where_sql}
		GROUP BY cl.customer_id";

	$limit  = (int) $args['per_page'];
	$offset = ( max( 1, (int) $args['paged'] ) - 1 ) * $limit;

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- fragments prepared above; LIMIT/OFFSET
	// are cast ints. An outer prepare() here would mangle literal % in LIKE fragments.
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ( {$select} {$core} {$having_sql} ) x" );
	$rows  = $wpdb->get_results( "{$select} {$core} {$having_sql} ORDER BY {$order_sql} LIMIT {$limit} OFFSET {$offset}" );
	// phpcs:enable

	return array(
		'rows'  => $rows,
		'total' => $total,
	);
}

/**
 * Cached ranking query. Accepts partial args; fills defaults.
 *
 * @param array $args See defaults below.
 * @return array { rows: object[], total: int }
 */
function ats_ci_get_customers( array $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'range'        => 'all',
			'date_from'    => '',
			'date_to'      => '',
			'min_units'    => 0,
			'min_orders'   => 0,
			'min_spend'    => 0.0,
			'dormant_days' => 0,
			'at_risk'      => false,
			'coupon'       => '',
			'product'      => '',
			'category_id'  => 0,
			'buyer'        => 'all',
			'account'      => 'all',
			'orderby'      => 'units',
			'per_page'     => 25,
			'paged'        => 1,
			'nocache'      => false,
		)
	);

	$key = 'ats_ci_' . md5( wp_json_encode( array_diff_key( $args, array( 'nocache' => 1 ) ) ) );

	if ( ! $args['nocache'] ) {
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}
	}

	$result = ats_ci_query_customers( $args );
	foreach ( $result['rows'] as $row ) {
		$row->segment = ats_ci_segment( $row );
	}

	if ( ! $args['nocache'] ) {
		set_transient( $key, $result, 15 * MINUTE_IN_SECONDS );
	}
	return $result;
}

/**
 * Distinct coupon codes actually used in orders, for the filter dropdown.
 */
function ats_ci_get_coupon_options() {
	global $wpdb;
	$p = $wpdb->prefix;
	return $wpdb->get_col(
		"SELECT DISTINCT cp.post_title
		 FROM {$p}wc_order_coupon_lookup ocl
		 JOIN {$p}posts cp ON cp.ID = ocl.coupon_id
		 ORDER BY cp.post_title"
	);
}

/**
 * Everything about one customer, for the popup.
 *
 * @param int $customer_id wc_customer_lookup PK.
 * @return array|null Null when the customer doesn't exist.
 */
function ats_ci_customer_detail( $customer_id ) {
	global $wpdb;
	$p           = $wpdb->prefix;
	$customer_id = (int) $customer_id;
	$statuses    = ats_ci_statuses_sql();

	$customer = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$p}wc_customer_lookup WHERE customer_id = %d", $customer_id )
	);
	if ( ! $customer ) {
		return null;
	}

	$stats = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COUNT(*)            AS att_orders,
			        SUM(num_items_sold) AS att_units,
			        SUM(total_sales)    AS att_spend,
			        MIN(date_created)   AS att_first,
			        MAX(date_created)   AS att_last,
			        CASE WHEN COUNT(*) > 1
			             THEN DATEDIFF( MAX(date_created), MIN(date_created) ) / ( COUNT(*) - 1 )
			             ELSE NULL END  AS avg_gap_days,
			        DATEDIFF( NOW(), MAX(date_created) ) AS days_since_last
			 FROM {$p}wc_order_stats
			 WHERE customer_id = %d AND parent_id = 0 AND status IN ( {$statuses} )",
			$customer_id
		)
	);

	$orders = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT os.order_id, os.date_created, os.status, os.total_sales, os.num_items_sold
			 FROM {$p}wc_order_stats os
			 WHERE os.customer_id = %d AND os.parent_id = 0 AND os.status <> 'wc-trash'
			 ORDER BY os.date_created DESC
			 LIMIT 200",
			$customer_id
		)
	);

	$items         = array();
	$order_coupons = array();
	$order_ids     = array_map( 'intval', wp_list_pluck( $orders, 'order_id' ) );
	if ( $order_ids ) {
		$ids_sql = implode( ',', $order_ids );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ids are intval'd above.
		$item_rows = $wpdb->get_results(
			"SELECT opl.order_id,
			        GROUP_CONCAT( CONCAT( COALESCE( pp.post_title, '(deleted product)' ), ' ×', opl.product_qty )
			                      ORDER BY pp.post_title SEPARATOR ', ' ) AS summary
			 FROM {$p}wc_order_product_lookup opl
			 LEFT JOIN {$p}posts pp ON pp.ID = opl.product_id
			 WHERE opl.order_id IN ( {$ids_sql} )
			 GROUP BY opl.order_id"
		);
		foreach ( $item_rows as $ir ) {
			$items[ (int) $ir->order_id ] = $ir->summary;
		}
		$cpn_rows = $wpdb->get_results(
			"SELECT ocl.order_id,
			        GROUP_CONCAT( COALESCE( cp.post_title, '(deleted)' ) SEPARATOR ', ' ) AS codes
			 FROM {$p}wc_order_coupon_lookup ocl
			 LEFT JOIN {$p}posts cp ON cp.ID = ocl.coupon_id
			 WHERE ocl.order_id IN ( {$ids_sql} )
			 GROUP BY ocl.order_id"
		);
		foreach ( $cpn_rows as $cr ) {
			$order_coupons[ (int) $cr->order_id ] = $cr->codes;
		}
		// phpcs:enable
	}

	$top_products = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT MAX( COALESCE( pp.post_title, '(deleted product)' ) ) AS name,
			        SUM( opl.product_qty ) AS qty
			 FROM {$p}wc_order_product_lookup opl
			 JOIN {$p}wc_order_stats os
			   ON os.order_id = opl.order_id AND os.parent_id = 0 AND os.status IN ( {$statuses} )
			 LEFT JOIN {$p}posts pp ON pp.ID = opl.product_id
			 WHERE os.customer_id = %d
			 GROUP BY opl.product_id
			 ORDER BY qty DESC
			 LIMIT 5",
			$customer_id
		)
	);

	$coupons = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT MAX( COALESCE( cp.post_title, '(deleted)' ) ) AS code,
			        COUNT(*) AS times_used,
			        SUM( ocl.discount_amount ) AS total_discount
			 FROM {$p}wc_order_coupon_lookup ocl
			 JOIN {$p}wc_order_stats os
			   ON os.order_id = ocl.order_id AND os.parent_id = 0 AND os.status <> 'wc-trash'
			 LEFT JOIN {$p}posts cp ON cp.ID = ocl.coupon_id
			 WHERE os.customer_id = %d
			 GROUP BY ocl.coupon_id
			 ORDER BY times_used DESC",
			$customer_id
		)
	);

	return compact( 'customer', 'stats', 'orders', 'items', 'order_coupons', 'top_products', 'coupons' );
}
