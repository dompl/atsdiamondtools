# Customer Insights Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the WooCommerce → Customer Insights admin page (ranked customer browser with filters, detail popup, Send-to-Brevo) per the approved spec at `docs/superpowers/specs/2026-07-17-customer-insights-design.md`.

**Architecture:** Three plain-PHP files in `src/functions/woocommerce/` (auto-loaded by the parent theme's ACF directory system). All queries hit WooCommerce's analytics lookup tables (`wc_customer_lookup`, `wc_order_stats`, `wc_order_product_lookup`, `wc_order_coupon_lookup`). Server-rendered admin page with page-scoped inline JS; two AJAX endpoints (customer detail popup, Brevo send).

**Tech Stack:** WordPress admin (core styles, no Tailwind), WP-CLI for verification, `$wpdb` raw SQL, Brevo REST API v3 via `wp_remote_request`.

## Global Constraints

- **Edit ONLY** `skylinewp-dev-child/src/functions/woocommerce/…`. The ACTIVE theme is the compiled dist `/var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/atsdiamondtools-child/` — after every PHP change, `php -l` the file then **copy it** to `atsdiamondtools-child/functions/woocommerce/` so `wp eval` / browser tests exercise it. The src file is the committed source of truth; the next `gulp dist` regenerates the dist copy.
- **NEVER run `gulp dist` or `gulp watch`** — `gulp dist` commits, pushes to origin/master, and re-activates the theme; it requires the user's explicit permission.
- **STAGING ONLY.** Never touch production (`77.68.4.231` / atsdiamondtools.co.uk). Never `git push` unless the user asks.
- Files in `functions/woocommerce/` are included **during `acf/init`** by the parent framework: never wrap code in `add_action( 'acf/init', … )` — plain function definitions plus `add_action` for later hooks (`admin_menu`, `wp_ajax_*`) are correct.
- Function prefix `ats_ci_`. Capability `manage_woocommerce` on the page and every AJAX endpoint. Nonce action string `ats_ci`.
- Escape all output (`esc_html` / `esc_attr` / `esc_url`); `$wpdb->prepare` for every variable SQL value; ORDER BY only via the hardcoded whitelist map.
- Counted order statuses are exactly `wc-completed`,`wc-processing`; every `wc_order_stats` query includes `parent_id = 0` (excludes refund child rows).
- Table prefix always via `$wpdb->prefix` (staging prefix is `XMTBGX_`).
- Run WP-CLI from the install dir: `cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools`. Plugin deprecation notices in WP-CLI output are pre-existing noise — ignore them; only the lines your script prints matter.
- Commit after each task from the theme repo root (`/var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child`), message prefix `feat(customer-insights):`.
- `BREVO_API` constant is confirmed defined on staging. DB is MariaDB 10.11.

## File Structure

| File | Responsibility | Task |
|---|---|---|
| `src/functions/woocommerce/customer-insights-data.php` | Statuses, thresholds, arg parsing, ranking query + cache, segment logic, coupon options, customer detail query | 1, 3 |
| `src/functions/woocommerce/customer-insights-admin.php` | Menu, page render (filters, table, pagination, sorting), modals, inline CSS/JS | 2, 3, 4 |
| `src/functions/woocommerce/customer-insights-ajax.php` | AJAX endpoints (customer detail, Brevo lists, Brevo send), detail HTML fragment, Brevo API client | 3, 4 |
| `docs/superpowers/plans/verify/ci-task{1..4}.php` | `wp eval-file` verification scripts (committed) | 1–4 |

Sync command used throughout (from theme repo root):

```bash
cp src/functions/woocommerce/customer-insights-*.php /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/atsdiamondtools-child/functions/woocommerce/
```

---

### Task 1: Data layer — ranking query, filters, segments, cache

**Files:**
- Create: `src/functions/woocommerce/customer-insights-data.php`
- Test: `docs/superpowers/plans/verify/ci-task1.php`

**Interfaces:**
- Consumes: nothing (first task).
- Produces (later tasks rely on these exact signatures):
  - `ats_ci_counted_statuses(): array` — `['wc-completed','wc-processing']`
  - `ats_ci_thresholds(): array` — keys `dormant_days` (180), `at_risk_multiplier` (1.5), `champion_orders` (5); filter `ats_customer_insights_segment_thresholds`
  - `ats_ci_parse_args( array $source ): array` — normalises `$_GET`-shaped input; returned keys: `range, date_from, date_to, min_units, min_orders, min_spend, dormant_days, at_risk, coupon, product, category_id, buyer, account, orderby, per_page, paged, nocache`
  - `ats_ci_get_customers( array $args ): array` — cached; returns `['rows' => object[], 'total' => int]`; row fields: `customer_id, user_id, first_name, last_name, email, country, city, orders_count, units, total_spend, first_order, last_order, coupon_orders, att_orders, att_units, att_spend, att_first, att_last, avg_gap_days, days_since_last, segment`
  - `ats_ci_segment( object $row ): string` — one of `dormant|at-risk|one-time|champion|loyal`
  - `ats_ci_get_coupon_options(): array` — distinct coupon codes seen in orders

- [ ] **Step 1: Write the failing verification script**

Create `docs/superpowers/plans/verify/ci-task1.php`:

```php
<?php
/**
 * Customer Insights — Task 1 verification.
 * Run: cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
 *   wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task1.php
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

global $wpdb;
$p = $wpdb->prefix;

// 1. Top all-time buyer by units matches raw SQL.
$r   = ats_ci_get_customers( array( 'orderby' => 'units', 'per_page' => 25, 'nocache' => true ) );
$raw = $wpdb->get_row(
	"SELECT os.customer_id, SUM(os.num_items_sold) AS units
	 FROM {$p}wc_order_stats os
	 WHERE os.parent_id = 0 AND os.status IN ('wc-completed','wc-processing')
	 GROUP BY os.customer_id ORDER BY units DESC LIMIT 1"
);
ci_check( 'top buyer id matches raw SQL', (int) $r['rows'][0]->customer_id === (int) $raw->customer_id );
ci_check( 'top buyer units match raw SQL', (int) $r['rows'][0]->units === (int) $raw->units );
ci_check( 'total looks sane (>1000 customers all-time)', $r['total'] > 1000 );
ci_check( 'segments assigned on every row', ! array_filter( $r['rows'], function ( $x ) { return empty( $x->segment ); } ) );

// 2. min_units filter respected.
$r2 = ats_ci_get_customers( array( 'min_units' => 50, 'per_page' => 100, 'nocache' => true ) );
ci_check( 'min_units=50: every row >= 50 units', ! array_filter( $r2['rows'], function ( $x ) { return (int) $x->units < 50; } ) );

// 3. coupon=any: rows have coupon orders; spot-check the first against the lookup table.
$r3 = ats_ci_get_customers( array( 'coupon' => 'any', 'per_page' => 25, 'nocache' => true ) );
ci_check( 'coupon=any returns rows', ! empty( $r3['rows'] ) );
ci_check( 'coupon=any: every row coupon_orders >= 1', ! array_filter( $r3['rows'], function ( $x ) { return (int) $x->coupon_orders < 1; } ) );
$spot = $r3['rows'][0];
$n    = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(DISTINCT ocl.order_id)
	 FROM {$p}wc_order_coupon_lookup ocl
	 JOIN {$p}wc_order_stats os ON os.order_id = ocl.order_id
	  AND os.parent_id = 0 AND os.status IN ('wc-completed','wc-processing')
	 WHERE os.customer_id = %d",
	$spot->customer_id
) );
ci_check( 'spot-check: coupon_orders matches lookup count', (int) $spot->coupon_orders === $n );

// 4. Guest filter returns only NULL user_id.
$r4 = ats_ci_get_customers( array( 'account' => 'guest', 'per_page' => 10, 'nocache' => true ) );
ci_check( 'guest filter: only NULL user_id', ! array_filter( $r4['rows'], function ( $x ) { return null !== $x->user_id; } ) );

// 5. Dormant filter.
$r5 = ats_ci_get_customers( array( 'dormant_days' => 180, 'per_page' => 10, 'nocache' => true ) );
ci_check( 'dormant: every row >= 180 days since last order', ! array_filter( $r5['rows'], function ( $x ) { return (int) $x->days_since_last < 180; } ) );

// 6. One-time buyer filter.
$r6 = ats_ci_get_customers( array( 'buyer' => 'one-time', 'per_page' => 10, 'nocache' => true ) );
ci_check( 'one-time: every row has exactly 1 all-time order', ! array_filter( $r6['rows'], function ( $x ) { return 1 !== (int) $x->att_orders; } ) );

// 7. Date range restricts figures: 30d spend for top row <= all-time spend.
$r7 = ats_ci_get_customers( array( 'range' => '30d', 'per_page' => 5, 'nocache' => true ) );
$a  = ats_ci_parse_args( array( 'range' => '30d' ) );
ci_check( 'parse_args: 30d produces a date_from', '' !== $a['date_from'] );
$ok = true;
foreach ( $r7['rows'] as $row ) {
	if ( (float) $row->total_spend > (float) $row->att_spend + 0.01 ) { $ok = false; }
}
ci_check( '30d range: range spend never exceeds all-time spend', $ok );

// 8. Coupon options list is non-empty and contains free24.
$codes = ats_ci_get_coupon_options();
ci_check( 'coupon options include free24', in_array( 'free24', array_map( 'strtolower', $codes ), true ) );

echo $GLOBALS['ci_fail'] ? "RESULT: {$GLOBALS['ci_fail']} FAILURES\n" : "RESULT: ALL PASS\n";
```

- [ ] **Step 2: Run it to verify it fails**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
  wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task1.php
```

Expected: PHP fatal `Call to undefined function ats_ci_get_customers()`.

- [ ] **Step 3: Write the data layer**

Create `src/functions/woocommerce/customer-insights-data.php`:

```php
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

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- fragments prepared above.
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ( {$select} {$core} {$having_sql} ) x" );
	$rows  = $wpdb->get_results(
		$wpdb->prepare(
			"{$select} {$core} {$having_sql} ORDER BY {$order_sql} LIMIT %d OFFSET %d",
			$args['per_page'],
			( $args['paged'] - 1 ) * $args['per_page']
		)
	);
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
```

- [ ] **Step 4: Lint and sync to the active theme**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child
php -l src/functions/woocommerce/customer-insights-data.php
cp src/functions/woocommerce/customer-insights-*.php /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/atsdiamondtools-child/functions/woocommerce/
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Run verification to verify it passes**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
  wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task1.php
```

Expected: every line `PASS: …`, final line `RESULT: ALL PASS`. If any FAIL, fix the data file (not the checks), re-sync, re-run.

- [ ] **Step 6: Commit**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child
git add src/functions/woocommerce/customer-insights-data.php docs/superpowers/plans/verify/ci-task1.php
git commit -m "feat(customer-insights): data layer — ranking query, filters, segments, cache"
```

---

### Task 2: Admin page — filters, table, sorting, pagination

**Files:**
- Create: `src/functions/woocommerce/customer-insights-admin.php`
- Test: `docs/superpowers/plans/verify/ci-task2.php`

**Interfaces:**
- Consumes: `ats_ci_parse_args()`, `ats_ci_get_customers()`, `ats_ci_get_coupon_options()` from Task 1 (exact signatures in Task 1's Produces block).
- Produces:
  - `ats_ci_render_page(): void` — full page render (reads `$_GET`)
  - `ats_ci_url( array $overrides = array() ): string` — page URL preserving current filters
  - `ats_ci_badge( string $segment ): string` — badge HTML
  - Action hook `ats_ci_toolbar` fired with `( $args, $result )` beside the results summary — Task 4 hooks the Brevo button here
  - Action hook `ats_ci_page_footer` fired with `( $args, $result )` at the end of the page — Tasks 3 & 4 hook modal markup + JS here

- [ ] **Step 1: Write the failing verification script**

Create `docs/superpowers/plans/verify/ci-task2.php`:

```php
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

$_GET                    = array( 'page' => 'ats-customer-insights', 'range' => 'all' );
$_SERVER['REQUEST_URI']  = '/wp-admin/admin.php?page=ats-customer-insights&range=all';
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
```

- [ ] **Step 2: Run it to verify it fails**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
  wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task2.php
```

Expected: PHP fatal `Call to undefined function ats_ci_render_page()`.

- [ ] **Step 3: Write the admin page**

Create `src/functions/woocommerce/customer-insights-admin.php`:

```php
<?php
/**
 * Customer Insights — admin page
 *
 * WooCommerce submenu: ranked customer browser with filters, sortable
 * columns, pagination. Detail popup and Brevo actions hook in via the
 * ats_ci_toolbar / ats_ci_page_footer actions.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the admin page.
 */
function ats_ci_admin_menu() {
	add_submenu_page(
		'woocommerce',
		'Customer Insights',
		'Customer Insights',
		'manage_woocommerce',
		'ats-customer-insights',
		'ats_ci_render_page'
	);
}
add_action( 'admin_menu', 'ats_ci_admin_menu' );

/**
 * Page URL preserving current GET filters, with overrides.
 *
 * @param array $overrides Params to override.
 */
function ats_ci_url( array $overrides = array() ) {
	$params = array();
	foreach ( array_merge( (array) $_GET, $overrides ) as $k => $v ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_scalar( $v ) && '' !== (string) $v ) {
			$params[ sanitize_key( $k ) ] = sanitize_text_field( wp_unslash( (string) $v ) );
		}
	}
	$params['page'] = 'ats-customer-insights';
	return add_query_arg( $params, admin_url( 'admin.php' ) );
}

/**
 * Segment badge HTML.
 *
 * @param string $segment Segment key.
 */
function ats_ci_badge( $segment ) {
	$labels = array(
		'champion' => 'Champion',
		'loyal'    => 'Loyal',
		'at-risk'  => 'At-risk',
		'dormant'  => 'Dormant',
		'one-time' => 'One-time',
	);
	$label  = isset( $labels[ $segment ] ) ? $labels[ $segment ] : ucfirst( $segment );
	return '<span class="ats-ci-badge ats-ci-badge--' . esc_attr( $segment ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * Sortable column header link.
 *
 * @param string $key     Orderby key.
 * @param string $label   Column label.
 * @param string $current Active orderby.
 */
function ats_ci_sort_link( $key, $label, $current ) {
	$arrow = $current === $key ? ' &#9660;' : '';
	return '<a href="' . esc_url( ats_ci_url( array( 'orderby' => $key, 'paged' => 1 ) ) ) . '">'
		. esc_html( $label ) . $arrow . '</a>';
}

/**
 * Render the page.
 */
function ats_ci_render_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Insufficient permissions.' );
	}

	$args    = ats_ci_parse_args( (array) $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$result  = ats_ci_get_customers( $args );
	$rows    = $result['rows'];
	$total   = (int) $result['total'];
	$pages   = max( 1, (int) ceil( $total / $args['per_page'] ) );
	$paged   = min( $args['paged'], $pages );
	$from_n  = $total ? ( ( $paged - 1 ) * $args['per_page'] ) + 1 : 0;
	$to_n    = min( $total, $paged * $args['per_page'] );
	$coupons = ats_ci_get_coupon_options();
	$cats    = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	if ( is_wp_error( $cats ) ) {
		$cats = array();
	}
	?>
	<div class="wrap ats-ci-wrap">
		<h1>Customer Insights</h1>

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ats-ci-filters">
			<input type="hidden" name="page" value="ats-customer-insights">
			<div class="ats-ci-filter-grid">
				<label>Date range
					<select name="range" id="ats-ci-range">
						<?php
						$ranges = array(
							'30d'    => 'Last 30 days',
							'90d'    => 'Last 90 days',
							'12m'    => 'Last 12 months',
							'all'    => 'All time',
							'custom' => 'Custom…',
						);
						foreach ( $ranges as $val => $lab ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $val ),
								selected( $args['range'], $val, false ),
								esc_html( $lab )
							);
						}
						?>
					</select>
				</label>
				<label class="ats-ci-custom-date">From
					<input type="date" name="date_from" value="<?php echo esc_attr( $args['date_from'] ); ?>">
				</label>
				<label class="ats-ci-custom-date">To
					<input type="date" name="date_to" value="<?php echo esc_attr( $args['date_to'] ); ?>">
				</label>
				<label>Min products
					<input type="number" name="min_units" min="0" value="<?php echo esc_attr( $args['min_units'] ?: '' ); ?>">
				</label>
				<label>Min orders
					<input type="number" name="min_orders" min="0" value="<?php echo esc_attr( $args['min_orders'] ?: '' ); ?>">
				</label>
				<label>Min spend (&pound;)
					<input type="number" name="min_spend" min="0" step="0.01" value="<?php echo esc_attr( $args['min_spend'] ?: '' ); ?>">
				</label>
				<label>Dormant for (days)
					<input type="number" name="dormant" min="0" value="<?php echo esc_attr( $args['dormant_days'] ?: '' ); ?>" placeholder="e.g. 180">
				</label>
				<label>Coupon
					<select name="coupon">
						<option value="">Any / no filter</option>
						<option value="any" <?php selected( $args['coupon'], 'any' ); ?>>Used any coupon</option>
						<?php foreach ( $coupons as $code ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $args['coupon'], $code ); ?>><?php echo esc_html( $code ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Product name contains
					<input type="text" name="product" value="<?php echo esc_attr( $args['product'] ); ?>">
				</label>
				<label>Category
					<select name="category">
						<option value="">All categories</option>
						<?php foreach ( $cats as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $args['category_id'], $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Buyer type
					<select name="buyer">
						<option value="all" <?php selected( $args['buyer'], 'all' ); ?>>All buyers</option>
						<option value="repeat" <?php selected( $args['buyer'], 'repeat' ); ?>>Repeat buyers</option>
						<option value="one-time" <?php selected( $args['buyer'], 'one-time' ); ?>>One-time buyers</option>
					</select>
				</label>
				<label>Account
					<select name="account">
						<option value="all" <?php selected( $args['account'], 'all' ); ?>>All</option>
						<option value="registered" <?php selected( $args['account'], 'registered' ); ?>>Registered</option>
						<option value="guest" <?php selected( $args['account'], 'guest' ); ?>>Guest</option>
					</select>
				</label>
				<label class="ats-ci-atrisk"><input type="checkbox" name="at_risk" value="1" <?php checked( $args['at_risk'] ); ?>> At-risk only</label>
				<label>Rows
					<select name="per_page">
						<?php foreach ( array( 25, 50, 100 ) as $pp ) : ?>
							<option value="<?php echo esc_attr( $pp ); ?>" <?php selected( $args['per_page'], $pp ); ?>><?php echo esc_html( $pp ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<p>
				<button type="submit" class="button button-primary">Apply filters</button>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ats-customer-insights' ) ); ?>">Reset</a>
			</p>
		</form>

		<p class="ats-ci-summary">
			Showing <strong><?php echo esc_html( number_format_i18n( $from_n ) ); ?>&ndash;<?php echo esc_html( number_format_i18n( $to_n ) ); ?></strong>
			of <strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong> customers
			<?php do_action( 'ats_ci_toolbar', $args, $result ); ?>
		</p>

		<table class="wp-list-table widefat fixed striped ats-ci-table">
			<thead>
				<tr>
					<th style="width:50px">#</th>
					<th>Customer</th>
					<th style="width:90px">Segment</th>
					<th style="width:70px"><?php echo wp_kses_post( ats_ci_sort_link( 'orders', 'Orders', $args['orderby'] ) ); ?></th>
					<th style="width:70px"><?php echo wp_kses_post( ats_ci_sort_link( 'units', 'Units', $args['orderby'] ) ); ?></th>
					<th style="width:100px"><?php echo wp_kses_post( ats_ci_sort_link( 'spend', 'Spend', $args['orderby'] ) ); ?></th>
					<th style="width:90px">Avg order</th>
					<th style="width:110px"><?php echo wp_kses_post( ats_ci_sort_link( 'last_order', 'Last order', $args['orderby'] ) ); ?></th>
					<th style="width:80px">Days ago</th>
					<th style="width:90px">Coupon orders</th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="10">No customers match the current filters.</td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $i => $row ) : ?>
					<?php
					$name = trim( $row->first_name . ' ' . $row->last_name );
					$aov  = $row->orders_count ? (float) $row->total_spend / (int) $row->orders_count : 0;
					?>
					<tr class="ats-ci-row" data-customer="<?php echo esc_attr( $row->customer_id ); ?>" title="Click for full purchase history">
						<td><?php echo esc_html( number_format_i18n( ( ( $paged - 1 ) * $args['per_page'] ) + $i + 1 ) ); ?></td>
						<td>
							<strong><?php echo esc_html( $name ? $name : $row->email ); ?></strong>
							<?php if ( null === $row->user_id ) : ?><span class="ats-ci-guest">guest</span><?php endif; ?>
							<br><span class="ats-ci-email"><?php echo esc_html( $row->email ); ?></span>
						</td>
						<td><?php echo wp_kses_post( ats_ci_badge( $row->segment ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->orders_count ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->units ) ); ?></td>
						<td><?php echo wp_kses_post( wc_price( (float) $row->total_spend ) ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $aov ) ); ?></td>
						<td><?php echo esc_html( mysql2date( 'j M Y', $row->last_order ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->days_since_last ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->coupon_orders ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $pages > 1 ) : ?>
			<p class="ats-ci-pagination">
				<?php if ( $paged > 1 ) : ?>
					<a class="button" href="<?php echo esc_url( ats_ci_url( array( 'paged' => $paged - 1 ) ) ); ?>">&laquo; Previous</a>
				<?php endif; ?>
				Page <?php echo esc_html( number_format_i18n( $paged ) ); ?> of <?php echo esc_html( number_format_i18n( $pages ) ); ?>
				<?php if ( $paged < $pages ) : ?>
					<a class="button" href="<?php echo esc_url( ats_ci_url( array( 'paged' => $paged + 1 ) ) ); ?>">Next &raquo;</a>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<style>
			.ats-ci-filter-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px 14px; margin: 14px 0; }
			.ats-ci-filter-grid label { display: flex; flex-direction: column; font-weight: 600; gap: 3px; }
			.ats-ci-filter-grid label.ats-ci-atrisk { flex-direction: row; align-items: center; gap: 6px; }
			.ats-ci-row { cursor: pointer; }
			.ats-ci-email { color: #646970; font-size: 12px; }
			.ats-ci-guest { background: #f0f0f1; border-radius: 3px; padding: 1px 5px; font-size: 11px; margin-left: 4px; }
			.ats-ci-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
			.ats-ci-badge--champion { background: #2271b1; color: #fff; }
			.ats-ci-badge--loyal { background: #00a32a; color: #fff; }
			.ats-ci-badge--at-risk { background: #dba617; color: #1d2327; }
			.ats-ci-badge--dormant { background: #d63638; color: #fff; }
			.ats-ci-badge--one-time { background: #f0f0f1; color: #1d2327; }
		</style>

		<?php do_action( 'ats_ci_page_footer', $args, $result ); ?>
	</div>
	<?php
}
```

- [ ] **Step 4: Lint and sync to the active theme**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child
php -l src/functions/woocommerce/customer-insights-admin.php
cp src/functions/woocommerce/customer-insights-*.php /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/atsdiamondtools-child/functions/woocommerce/
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Run verification to verify it passes**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
  wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task2.php
```

Expected: all `PASS`, final `RESULT: ALL PASS`.

- [ ] **Step 6: Commit**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child
git add src/functions/woocommerce/customer-insights-admin.php docs/superpowers/plans/verify/ci-task2.php
git commit -m "feat(customer-insights): admin page — filters, ranked table, sorting, pagination"
```

---

### Task 3: Customer detail popup — query, AJAX endpoint, modal

**Files:**
- Modify: `src/functions/woocommerce/customer-insights-data.php` (append at end of file)
- Create: `src/functions/woocommerce/customer-insights-ajax.php`
- Modify: `src/functions/woocommerce/customer-insights-admin.php` (append at end of file)
- Test: `docs/superpowers/plans/verify/ci-task3.php`

**Interfaces:**
- Consumes: `ats_ci_counted_statuses()`, `ats_ci_statuses_sql()`, `ats_ci_thresholds()` (Task 1); action hook `ats_ci_page_footer( $args, $result )` (Task 2).
- Produces:
  - `ats_ci_customer_detail( int $customer_id ): array|null` — keys: `customer` (object: wc_customer_lookup row), `stats` (object: `att_orders, att_units, att_spend, att_first, att_last, avg_gap_days, days_since_last`), `orders` (object[]: `order_id, date_created, status, total_sales, num_items_sold`), `items` (array: order_id → item summary string), `order_coupons` (array: order_id → codes string), `top_products` (object[]: `name, qty`), `coupons` (object[]: `code, times_used, total_discount`)
  - `ats_ci_detail_html( array $d ): string` — escaped modal-body HTML
  - AJAX action `wp_ajax_ats_ci_customer` — POST `nonce` (action `ats_ci`), `customer_id`; returns `{ success, data: { html } }`
  - JS global `atsCi = { ajaxUrl, nonce }` printed by the footer hook (Task 4's JS reuses it); modal elements `#ats-ci-modal`, `#ats-ci-modal-body`

- [ ] **Step 1: Write the failing verification script**

Create `docs/superpowers/plans/verify/ci-task3.php`:

```php
<?php
/**
 * Customer Insights — Task 3 verification.
 * Run: cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
 *   wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task3.php
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

global $wpdb;
$p = $wpdb->prefix;

// Use the all-time top buyer as the test subject.
$top = ats_ci_get_customers( array( 'orderby' => 'units', 'per_page' => 1, 'nocache' => true ) );
$cid = (int) $top['rows'][0]->customer_id;

$d = ats_ci_customer_detail( $cid );
ci_check( 'detail returns array for top buyer', is_array( $d ) );
ci_check( 'customer email present', ! empty( $d['customer']->email ) );

// Stats cross-check against raw SQL.
$raw_orders = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$p}wc_order_stats
	 WHERE customer_id = %d AND parent_id = 0 AND status IN ('wc-completed','wc-processing')",
	$cid
) );
ci_check( 'att_orders matches raw count', (int) $d['stats']->att_orders === $raw_orders );
ci_check( 'ranking row agrees with detail stats', (int) $top['rows'][0]->att_orders === (int) $d['stats']->att_orders );

// Order list sane.
ci_check( 'orders list non-empty', ! empty( $d['orders'] ) );
$latest = $d['orders'][0];
ci_check( 'orders sorted newest first', strtotime( $latest->date_created ) >= strtotime( end( $d['orders'] )->date_created ) );
ci_check( 'items summary exists for latest order', isset( $d['items'][ (int) $latest->order_id ] ) );
ci_check( 'top products non-empty', ! empty( $d['top_products'] ) );

// HTML fragment.
$html = ats_ci_detail_html( $d );
ci_check( 'detail html contains email', false !== strpos( $html, $d['customer']->email ) );
ci_check( 'detail html contains order link', false !== strpos( $html, 'action=edit' ) );
ci_check( 'detail html has stats grid', false !== strpos( $html, 'ats-ci-stats' ) );

// AJAX + footer hooks registered.
ci_check( 'ajax action registered', false !== has_action( 'wp_ajax_ats_ci_customer' ) );
ci_check( 'footer modal hooked', false !== has_action( 'ats_ci_page_footer', 'ats_ci_footer_detail_modal' ) );

// Unknown customer returns null.
ci_check( 'unknown customer returns null', null === ats_ci_customer_detail( 999999999 ) );

echo $GLOBALS['ci_fail'] ? "RESULT: {$GLOBALS['ci_fail']} FAILURES\n" : "RESULT: ALL PASS\n";
```

- [ ] **Step 2: Run it to verify it fails**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
  wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task3.php
```

Expected: PHP fatal `Call to undefined function ats_ci_customer_detail()`.

- [ ] **Step 3: Append the detail query to the data file**

Append to the END of `src/functions/woocommerce/customer-insights-data.php`:

```php
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
```

- [ ] **Step 4: Create the AJAX file with the detail endpoint + HTML fragment**

Create `src/functions/woocommerce/customer-insights-ajax.php`:

```php
<?php
/**
 * Customer Insights — AJAX endpoints
 *
 * Customer detail popup fragment. Task 4 appends the Brevo client and
 * its endpoints below.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Popup HTML fragment for one customer.
 *
 * @param array $d Output of ats_ci_customer_detail().
 */
function ats_ci_detail_html( array $d ) {
	$c     = $d['customer'];
	$s     = $d['stats'];
	$name  = trim( $c->first_name . ' ' . $c->last_name );
	$loc   = trim( implode( ', ', array_filter( array( $c->city, $c->country ) ) ) );
	$aov   = $s->att_orders ? (float) $s->att_spend / (int) $s->att_orders : 0;
	$row   = (object) array(
		'att_orders'      => $s->att_orders,
		'avg_gap_days'    => $s->avg_gap_days,
		'days_since_last' => $s->days_since_last,
	);
	$due   = (int) $s->att_orders >= 3 && (float) $s->avg_gap_days > 0
		&& (int) $s->days_since_last >= (float) $s->avg_gap_days;

	ob_start();
	?>
	<div class="ats-ci-detail-head">
		<h2><?php echo esc_html( $name ? $name : $c->email ); ?>
			<?php echo wp_kses_post( ats_ci_badge( ats_ci_segment( $row ) ) ); ?>
			<?php if ( $due ) : ?><span class="ats-ci-due">Due to reorder</span><?php endif; ?>
		</h2>
		<p>
			<?php echo esc_html( $c->email ); ?>
			<?php if ( $loc ) : ?> &middot; <?php echo esc_html( $loc ); ?><?php endif; ?>
			&middot; <?php echo null === $c->user_id ? 'Guest' : 'Registered'; ?>
		</p>
	</div>

	<div class="ats-ci-stats">
		<div><span>Lifetime spend</span><strong><?php echo wp_kses_post( wc_price( (float) $s->att_spend ) ); ?></strong></div>
		<div><span>Orders</span><strong><?php echo esc_html( number_format_i18n( (int) $s->att_orders ) ); ?></strong></div>
		<div><span>Units</span><strong><?php echo esc_html( number_format_i18n( (int) $s->att_units ) ); ?></strong></div>
		<div><span>Avg order</span><strong><?php echo wp_kses_post( wc_price( $aov ) ); ?></strong></div>
		<div><span>First order</span><strong><?php echo esc_html( mysql2date( 'j M Y', $s->att_first ) ); ?></strong></div>
		<div><span>Last order</span><strong><?php echo esc_html( mysql2date( 'j M Y', $s->att_last ) ); ?></strong></div>
		<div><span>Avg gap</span><strong><?php echo $s->avg_gap_days ? esc_html( number_format_i18n( (float) $s->avg_gap_days ) . ' days' ) : '&mdash;'; ?></strong></div>
		<div><span>Days since last</span><strong><?php echo esc_html( number_format_i18n( (int) $s->days_since_last ) ); ?></strong></div>
	</div>

	<?php if ( $d['top_products'] ) : ?>
		<h3>Most bought</h3>
		<ul class="ats-ci-top">
			<?php foreach ( $d['top_products'] as $tp ) : ?>
				<li><?php echo esc_html( $tp->name ); ?> <strong>&times;<?php echo esc_html( number_format_i18n( (int) $tp->qty ) ); ?></strong></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $d['coupons'] ) : ?>
		<h3>Coupons used</h3>
		<table class="widefat striped">
			<thead><tr><th>Code</th><th>Times used</th><th>Total discount</th></tr></thead>
			<tbody>
			<?php foreach ( $d['coupons'] as $cp ) : ?>
				<tr>
					<td><?php echo esc_html( $cp->code ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (int) $cp->times_used ) ); ?></td>
					<td><?php echo wp_kses_post( wc_price( (float) $cp->total_discount ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h3>Orders (<?php echo esc_html( number_format_i18n( count( $d['orders'] ) ) ); ?>)</h3>
	<div class="ats-ci-orders">
		<table class="widefat striped">
			<thead><tr><th>Date</th><th>Order</th><th>Items</th><th>Total</th><th>Status</th><th>Coupon</th></tr></thead>
			<tbody>
			<?php
			foreach ( $d['orders'] as $o ) :
				$oid     = (int) $o->order_id;
				$status  = str_replace( 'wc-', '', $o->status );
				$counted = in_array( $o->status, ats_ci_counted_statuses(), true );
				?>
				<tr class="<?php echo $counted ? '' : 'ats-ci-order-void'; ?>">
					<td><?php echo esc_html( mysql2date( 'j M Y', $o->date_created ) ); ?></td>
					<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $oid . '&action=edit' ) ); ?>" target="_blank">#<?php echo esc_html( $oid ); ?></a></td>
					<td><?php echo esc_html( isset( $d['items'][ $oid ] ) ? $d['items'][ $oid ] : '—' ); ?></td>
					<td><?php echo wp_kses_post( wc_price( (float) $o->total_sales ) ); ?></td>
					<td><?php echo esc_html( $status ); ?></td>
					<td><?php echo esc_html( isset( $d['order_coupons'][ $oid ] ) ? $d['order_coupons'][ $oid ] : '' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * AJAX: customer detail popup.
 */
function ats_ci_ajax_customer_detail() {
	check_ajax_referer( 'ats_ci', 'nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}
	$detail = ats_ci_customer_detail( isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0 );
	if ( ! $detail ) {
		wp_send_json_error( 'not_found', 404 );
	}
	wp_send_json_success( array( 'html' => ats_ci_detail_html( $detail ) ) );
}
add_action( 'wp_ajax_ats_ci_customer', 'ats_ci_ajax_customer_detail' );
```

- [ ] **Step 5: Append the modal + JS to the admin file**

Append to the END of `src/functions/woocommerce/customer-insights-admin.php`:

```php
/**
 * Detail modal + page JS, printed at the foot of the Insights page.
 *
 * @param array $args   Parsed filter args (unused here).
 * @param array $result Ranking result (unused here).
 */
function ats_ci_footer_detail_modal( $args, $result ) {
	?>
	<div id="ats-ci-modal" class="ats-ci-modal" style="display:none">
		<div class="ats-ci-modal-inner">
			<button type="button" class="ats-ci-modal-close" aria-label="Close">&times;</button>
			<div id="ats-ci-modal-body"></div>
		</div>
	</div>
	<style>
		.ats-ci-modal { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 100000; display: flex; align-items: center; justify-content: center; }
		.ats-ci-modal-inner { background: #fff; border-radius: 6px; width: min(860px, 92vw); max-height: 86vh; overflow-y: auto; padding: 20px 24px; position: relative; }
		.ats-ci-modal-close { position: absolute; top: 8px; right: 10px; border: 0; background: none; font-size: 26px; cursor: pointer; line-height: 1; }
		.ats-ci-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 14px 0; }
		.ats-ci-stats div { background: #f6f7f7; border-radius: 4px; padding: 8px 10px; }
		.ats-ci-stats span { display: block; color: #646970; font-size: 11px; text-transform: uppercase; }
		.ats-ci-due { background: #d63638; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; font-weight: 600; margin-left: 6px; }
		.ats-ci-orders { max-height: 320px; overflow-y: auto; }
		.ats-ci-order-void { opacity: .45; }
	</style>
	<script>
	var atsCi = {
		ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		nonce:   <?php echo wp_json_encode( wp_create_nonce( 'ats_ci' ) ); ?>
	};
	(function () {
		var modal = document.getElementById('ats-ci-modal');
		var body  = document.getElementById('ats-ci-modal-body');
		function close() { modal.style.display = 'none'; }
		modal.querySelector('.ats-ci-modal-close').addEventListener('click', close);
		modal.addEventListener('click', function (e) { if (e.target === modal) { close(); } });
		document.addEventListener('keydown', function (e) { if ('Escape' === e.key) { close(); } });

		document.querySelectorAll('.ats-ci-row[data-customer]').forEach(function (tr) {
			tr.addEventListener('click', function () {
				body.innerHTML = '<p>Loading&hellip;</p>';
				modal.style.display = 'flex';
				var data = new FormData();
				data.append('action', 'ats_ci_customer');
				data.append('nonce', atsCi.nonce);
				data.append('customer_id', tr.dataset.customer);
				fetch(atsCi.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
					.then(function (r) { return r.json(); })
					.then(function (res) {
						body.innerHTML = res && res.success ? res.data.html : '<p>Could not load customer details.</p>';
					})
					.catch(function () { body.innerHTML = '<p>Could not load customer details.</p>'; });
			});
		});
	})();
	</script>
	<?php
}
add_action( 'ats_ci_page_footer', 'ats_ci_footer_detail_modal', 10, 2 );
```

- [ ] **Step 6: Lint and sync to the active theme**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child
php -l src/functions/woocommerce/customer-insights-data.php
php -l src/functions/woocommerce/customer-insights-admin.php
php -l src/functions/woocommerce/customer-insights-ajax.php
cp src/functions/woocommerce/customer-insights-*.php /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/atsdiamondtools-child/functions/woocommerce/
```

Expected: `No syntax errors detected` three times.

- [ ] **Step 7: Run verification to verify it passes**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
  wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task3.php
```

Expected: all `PASS`, final `RESULT: ALL PASS`.

- [ ] **Step 8: Commit**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child
git add src/functions/woocommerce/customer-insights-data.php \
        src/functions/woocommerce/customer-insights-admin.php \
        src/functions/woocommerce/customer-insights-ajax.php \
        docs/superpowers/plans/verify/ci-task3.php
git commit -m "feat(customer-insights): customer detail popup — query, AJAX endpoint, modal"
```

---

### Task 4: Send to Brevo — API client, endpoints, button + modal

**Files:**
- Modify: `src/functions/woocommerce/customer-insights-ajax.php` (append at end of file)
- Modify: `src/functions/woocommerce/customer-insights-admin.php` (append at end of file)
- Test: `docs/superpowers/plans/verify/ci-task4.php`

**Interfaces:**
- Consumes: `ats_ci_parse_args()`, `ats_ci_get_customers()` (Task 1); hooks `ats_ci_toolbar` / `ats_ci_page_footer` and JS global `atsCi` (Tasks 2–3).
- Produces:
  - `ats_ci_brevo_request( string $method, string $path, array|null $body = null ): array|WP_Error`
  - `ats_ci_brevo_folder_id(): int|WP_Error` — finds/creates the "Customer Insights" Brevo folder
  - `ats_ci_brevo_create_list( string $name ): int|WP_Error`
  - `ats_ci_brevo_get_lists(): array|WP_Error` — `[ { id, name }, … ]` (newest first)
  - `ats_ci_brevo_import_contacts( array $contacts, int $list_id ): int|WP_Error` — `$contacts` items: `{ email, first_name, last_name }`; returns count submitted
  - AJAX `wp_ajax_ats_ci_brevo_lists` (POST `nonce`) and `wp_ajax_ats_ci_brevo_send` (POST `nonce`, `filters` = query string, `mode` = `new|existing`, `list_name`, `list_id`)

**Notes:** Brevo's `/contacts/import` is asynchronous (HTTP 202) — a success response means the import was accepted, contacts appear in the list moments later. Brevo keeps unsubscribe/blacklist state, so importing an unsubscribed contact does not re-enable emails to them. The in-house `brevo-campaign-generator` plugin's client (`class-bcg-brevo.php`) is instance-based and plugin-coupled — the theme gets its own thin wrapper (below) so the page works even if that plugin is deactivated.

- [ ] **Step 1: Write the failing verification script**

Create `docs/superpowers/plans/verify/ci-task4.php`:

```php
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
```

- [ ] **Step 2: Run it to verify it fails**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
  wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task4.php
```

Expected: PHP fatal `Call to undefined function ats_ci_brevo_folder_id()`.

- [ ] **Step 3: Append the Brevo client + endpoints to the AJAX file**

Append to the END of `src/functions/woocommerce/customer-insights-ajax.php`:

```php
/**
 * Minimal Brevo v3 API request.
 *
 * @param string     $method HTTP method.
 * @param string     $path   Path after /v3, e.g. '/contacts/lists'.
 * @param array|null $body   JSON body.
 * @return array|WP_Error Decoded response (array) or error.
 */
function ats_ci_brevo_request( $method, $path, $body = null ) {
	$api_key = defined( 'BREVO_API' ) ? BREVO_API : '';
	if ( '' === $api_key ) {
		return new WP_Error( 'no_key', 'BREVO_API constant is not defined.' );
	}
	$args = array(
		'method'  => $method,
		'headers' => array(
			'accept'       => 'application/json',
			'content-type' => 'application/json',
			'api-key'      => $api_key,
		),
		'timeout' => 30,
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$response = wp_remote_request( 'https://api.brevo.com/v3' . $path, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$json = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $code >= 300 ) {
		$msg = isset( $json['message'] ) ? $json['message'] : 'Brevo HTTP ' . $code;
		return new WP_Error( 'brevo_error', $msg, array( 'status' => $code ) );
	}
	return is_array( $json ) ? $json : array();
}

/**
 * Find or create the "Customer Insights" Brevo folder.
 *
 * @return int|WP_Error
 */
function ats_ci_brevo_folder_id() {
	$res = ats_ci_brevo_request( 'GET', '/contacts/folders?limit=50&offset=0' );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	if ( ! empty( $res['folders'] ) ) {
		foreach ( $res['folders'] as $folder ) {
			if ( 'Customer Insights' === $folder['name'] ) {
				return (int) $folder['id'];
			}
		}
	}
	$created = ats_ci_brevo_request( 'POST', '/contacts/folders', array( 'name' => 'Customer Insights' ) );
	return is_wp_error( $created ) ? $created : (int) $created['id'];
}

/**
 * Create a Brevo list in the Customer Insights folder.
 *
 * @param string $name List name.
 * @return int|WP_Error List id.
 */
function ats_ci_brevo_create_list( $name ) {
	$folder = ats_ci_brevo_folder_id();
	if ( is_wp_error( $folder ) ) {
		return $folder;
	}
	$res = ats_ci_brevo_request(
		'POST',
		'/contacts/lists',
		array(
			'name'     => $name,
			'folderId' => $folder,
		)
	);
	return is_wp_error( $res ) ? $res : (int) $res['id'];
}

/**
 * Existing Brevo lists (newest first) for the picker.
 *
 * @return array|WP_Error [ { id, name }, … ]
 */
function ats_ci_brevo_get_lists() {
	$res = ats_ci_brevo_request( 'GET', '/contacts/lists?limit=50&offset=0&sort=desc' );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$out = array();
	if ( ! empty( $res['lists'] ) ) {
		foreach ( $res['lists'] as $list ) {
			$out[] = array(
				'id'   => (int) $list['id'],
				'name' => (string) $list['name'],
			);
		}
	}
	return $out;
}

/**
 * Bulk-import contacts into a list, 1000 per request.
 * Brevo processes imports asynchronously (HTTP 202).
 *
 * @param array $contacts Items: { email, first_name, last_name }.
 * @param int   $list_id  Target list.
 * @return int|WP_Error Number submitted.
 */
function ats_ci_brevo_import_contacts( array $contacts, $list_id ) {
	$total = 0;
	foreach ( array_chunk( $contacts, 1000 ) as $chunk ) {
		$res = ats_ci_brevo_request(
			'POST',
			'/contacts/import',
			array(
				'listIds'                 => array( (int) $list_id ),
				'updateExistingContacts'  => true,
				'emptyContactsAttributes' => false,
				'jsonBody'                => array_map(
					function ( $c ) {
						return array(
							'email'      => $c['email'],
							'attributes' => array(
								'FIRSTNAME' => (string) $c['first_name'],
								'LASTNAME'  => (string) $c['last_name'],
							),
						);
					},
					$chunk
				),
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$total += count( $chunk );
	}
	return $total;
}

/**
 * AJAX: existing lists for the Brevo modal picker.
 */
function ats_ci_ajax_brevo_lists() {
	check_ajax_referer( 'ats_ci', 'nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}
	$lists = ats_ci_brevo_get_lists();
	if ( is_wp_error( $lists ) ) {
		wp_send_json_error( $lists->get_error_message() );
	}
	wp_send_json_success( $lists );
}
add_action( 'wp_ajax_ats_ci_brevo_lists', 'ats_ci_ajax_brevo_lists' );

/**
 * AJAX: send the entire filtered result set to a Brevo list.
 */
function ats_ci_ajax_brevo_send() {
	check_ajax_referer( 'ats_ci', 'nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}

	$filters = array();
	if ( isset( $_POST['filters'] ) ) {
		parse_str( sanitize_text_field( wp_unslash( $_POST['filters'] ) ), $filters );
	}
	$args             = ats_ci_parse_args( $filters );
	$args['per_page'] = 100000;
	$args['paged']    = 1;
	$args['nocache']  = true;

	$result   = ats_ci_get_customers( $args );
	$contacts = array();
	foreach ( $result['rows'] as $row ) {
		if ( $row->email && is_email( $row->email ) ) {
			$contacts[] = array(
				'email'      => $row->email,
				'first_name' => (string) $row->first_name,
				'last_name'  => (string) $row->last_name,
			);
		}
	}
	if ( ! $contacts ) {
		wp_send_json_error( 'No customers with valid emails match the current filters.' );
	}

	$mode      = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'new';
	$list_name = isset( $_POST['list_name'] ) ? sanitize_text_field( wp_unslash( $_POST['list_name'] ) ) : '';

	if ( 'existing' === $mode ) {
		$list_id = isset( $_POST['list_id'] ) ? absint( $_POST['list_id'] ) : 0;
	} else {
		if ( '' === $list_name ) {
			$list_name = 'Insights — ' . current_time( 'j M Y H:i' );
		}
		$list_id = ats_ci_brevo_create_list( $list_name );
		if ( is_wp_error( $list_id ) ) {
			wp_send_json_error( $list_id->get_error_message() );
		}
	}
	if ( ! $list_id ) {
		wp_send_json_error( 'No Brevo list selected.' );
	}

	$sent = ats_ci_brevo_import_contacts( $contacts, $list_id );
	if ( is_wp_error( $sent ) ) {
		wp_send_json_error( $sent->get_error_message() );
	}
	wp_send_json_success(
		array(
			'count'     => (int) $sent,
			'list_id'   => (int) $list_id,
			'list_name' => $list_name,
		)
	);
}
add_action( 'wp_ajax_ats_ci_brevo_send', 'ats_ci_ajax_brevo_send' );
```

- [ ] **Step 4: Append the toolbar button + Brevo modal to the admin file**

Append to the END of `src/functions/woocommerce/customer-insights-admin.php`:

```php
/**
 * Default Brevo list name describing the active filters.
 *
 * @param array $args Parsed filter args.
 */
function ats_ci_default_list_name( array $args ) {
	$parts  = array();
	$ranges = array(
		'30d'    => 'last 30d',
		'90d'    => 'last 90d',
		'12m'    => 'last 12m',
		'all'    => 'all time',
		'custom' => $args['date_from'] . '→' . $args['date_to'],
	);
	$parts[] = $ranges[ $args['range'] ];
	if ( $args['min_units'] ) {
		$parts[] = $args['min_units'] . '+ units';
	}
	if ( $args['min_orders'] ) {
		$parts[] = $args['min_orders'] . '+ orders';
	}
	if ( $args['dormant_days'] ) {
		$parts[] = 'dormant ' . $args['dormant_days'] . 'd';
	}
	if ( $args['at_risk'] ) {
		$parts[] = 'at-risk';
	}
	if ( 'any' === $args['coupon'] ) {
		$parts[] = 'coupon users';
	} elseif ( '' !== $args['coupon'] ) {
		$parts[] = 'coupon ' . $args['coupon'];
	}
	if ( 'all' !== $args['buyer'] ) {
		$parts[] = $args['buyer'];
	}
	return 'Insights: ' . implode( ' + ', $parts ) . ' — ' . current_time( 'j M Y' );
}

/**
 * "Send to Brevo" button beside the results summary.
 *
 * @param array $args   Parsed filter args.
 * @param array $result Ranking result.
 */
function ats_ci_toolbar_brevo_button( $args, $result ) {
	if ( empty( $result['total'] ) ) {
		return;
	}
	printf(
		' <button type="button" class="button" id="ats-ci-brevo-open" data-name="%s">Send %s to Brevo</button>',
		esc_attr( ats_ci_default_list_name( $args ) ),
		esc_html( number_format_i18n( (int) $result['total'] ) )
	);
}
add_action( 'ats_ci_toolbar', 'ats_ci_toolbar_brevo_button', 10, 2 );

/**
 * Brevo modal + JS.
 *
 * @param array $args   Parsed filter args (unused).
 * @param array $result Ranking result (unused).
 */
function ats_ci_footer_brevo_modal( $args, $result ) {
	?>
	<div id="ats-ci-brevo-modal" class="ats-ci-modal" style="display:none">
		<div class="ats-ci-modal-inner" style="width:min(480px,92vw)">
			<button type="button" class="ats-ci-modal-close" aria-label="Close">&times;</button>
			<h2>Send filtered customers to Brevo</h2>
			<p><label><input type="radio" name="ats_ci_brevo_mode" value="new" checked> Create a new list</label></p>
			<p><input type="text" id="ats-ci-brevo-name" class="widefat"></p>
			<p><label><input type="radio" name="ats_ci_brevo_mode" value="existing"> Add to an existing list</label></p>
			<p><select id="ats-ci-brevo-list" class="widefat" disabled><option>Loading lists&hellip;</option></select></p>
			<p>
				<button type="button" class="button button-primary" id="ats-ci-brevo-send">Send</button>
				<span id="ats-ci-brevo-status"></span>
			</p>
		</div>
	</div>
	<script>
	(function () {
		var open   = document.getElementById('ats-ci-brevo-open');
		if (!open) { return; }
		var modal  = document.getElementById('ats-ci-brevo-modal');
		var name   = document.getElementById('ats-ci-brevo-name');
		var select = document.getElementById('ats-ci-brevo-list');
		var send   = document.getElementById('ats-ci-brevo-send');
		var status = document.getElementById('ats-ci-brevo-status');
		var loaded = false;

		function close() { modal.style.display = 'none'; }
		modal.querySelector('.ats-ci-modal-close').addEventListener('click', close);
		modal.addEventListener('click', function (e) { if (e.target === modal) { close(); } });

		open.addEventListener('click', function () {
			name.value = open.dataset.name;
			status.textContent = '';
			modal.style.display = 'flex';
			if (loaded) { return; }
			var data = new FormData();
			data.append('action', 'ats_ci_brevo_lists');
			data.append('nonce', atsCi.nonce);
			fetch(atsCi.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res || !res.success) { throw new Error(); }
					select.innerHTML = '';
					res.data.forEach(function (l) {
						var o = document.createElement('option');
						o.value = l.id;
						o.textContent = l.name + ' (#' + l.id + ')';
						select.appendChild(o);
					});
					select.disabled = false;
					loaded = true;
				})
				.catch(function () { select.innerHTML = '<option>Could not load lists</option>'; });
		});

		send.addEventListener('click', function () {
			var mode = document.querySelector('input[name="ats_ci_brevo_mode"]:checked').value;
			send.disabled = true;
			status.textContent = 'Sending…';
			var data = new FormData();
			data.append('action', 'ats_ci_brevo_send');
			data.append('nonce', atsCi.nonce);
			data.append('filters', window.location.search.replace(/^\?/, ''));
			data.append('mode', mode);
			data.append('list_name', name.value);
			if ('existing' === mode) { data.append('list_id', select.value); }
			fetch(atsCi.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					send.disabled = false;
					status.textContent = res && res.success
						? 'Sent ' + res.data.count + ' contacts to "' + res.data.list_name + '" (#' + res.data.list_id + ').'
						: 'Error: ' + (res && res.data ? res.data : 'unknown');
				})
				.catch(function () {
					send.disabled = false;
					status.textContent = 'Error: request failed.';
				});
		});
	})();
	</script>
	<?php
}
add_action( 'ats_ci_page_footer', 'ats_ci_footer_brevo_modal', 20, 2 );
```

- [ ] **Step 5: Lint and sync to the active theme**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child
php -l src/functions/woocommerce/customer-insights-admin.php
php -l src/functions/woocommerce/customer-insights-ajax.php
cp src/functions/woocommerce/customer-insights-*.php /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/atsdiamondtools-child/functions/woocommerce/
```

Expected: `No syntax errors detected` twice.

- [ ] **Step 6: Run verification (live Brevo round-trip) to verify it passes**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools && wp eval-file \
  wp-content/themes/skylinewp-dev-child/docs/superpowers/plans/verify/ci-task4.php
```

Expected: all `PASS`, final `RESULT: ALL PASS`. This creates then deletes a Brevo list named `ZZ TEST Customer Insights — safe to delete`; the one test contact (info@redfrogstudio.co.uk, the site owner's address) may remain in Brevo unlisted — harmless. If a Brevo call FAILs, the printed `Brevo error: …` message says why (bad key, IP restriction, etc.) — report it, don't retry blindly.

- [ ] **Step 7: Commit**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child
git add src/functions/woocommerce/customer-insights-admin.php \
        src/functions/woocommerce/customer-insights-ajax.php \
        docs/superpowers/plans/verify/ci-task4.php
git commit -m "feat(customer-insights): send filtered customers to a Brevo list"
```

---

### Task 5: Browser QA on staging + wrap-up

No new feature code. This task is a real-browser sanity pass done from the main session (Playwright MCP), plus cache cleanup. If any step reveals a defect, fix it in `src/`, re-lint, re-sync, re-run the relevant `ci-task*.php` script, and amend the responsible commit message convention with a follow-up `fix(customer-insights): …` commit.

**Interfaces:**
- Consumes: the complete feature from Tasks 1–4, live in the active theme via the synced copies.
- Produces: screenshots in the session scratchpad and a PASS/FAIL report; no code.

- [ ] **Step 1: Create a temporary admin user**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools
CI_QA_PW="CiQa-$(openssl rand -hex 8)"
echo "Temp admin password: ${CI_QA_PW}"
wp user create ci-qa-temp ci-qa-temp@example.com --role=administrator --user_pass="${CI_QA_PW}" --porcelain
```

Expected: the password echoed, then the new user ID. Use both in Step 2's login.

- [ ] **Step 2: Browser walkthrough (Playwright)**

1. Navigate to `http://atsdiamondtools.rfsdev.co.uk/wp-login.php`, log in as `ci-qa-temp`.
2. Open `http://atsdiamondtools.rfsdev.co.uk/wp-admin/admin.php?page=ats-customer-insights` — screenshot. Check: filter grid, ranked table with badges, summary line, "Send N to Brevo" button.
3. Apply filters dormant=180 + coupon=any — screenshot. Check: row count drops, summary updates, every visible badge is plausible.
4. Click the first row — screenshot the modal. Check: headline stats, top products, coupons, order list with links.
5. Click "Send … to Brevo" — screenshot the modal. Check: name prefilled with filter description, existing-lists dropdown populates. **Do NOT click Send** (no live import from QA).
6. Sort by Spend, page to page 2 — check both work and filters persist.

Expected: no JS console errors; all six checks pass visually.

- [ ] **Step 3: Cross-check one on-screen number**

Pick the top row's units figure from the screenshot and confirm:

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools
wp db query "SELECT customer_id, SUM(num_items_sold) units FROM XMTBGX_wc_order_stats WHERE parent_id=0 AND status IN ('wc-completed','wc-processing') GROUP BY customer_id ORDER BY units DESC LIMIT 1"
```

Expected: matches the on-screen top-row units (all-time, units sort).

- [ ] **Step 4: Clean up**

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools
wp user delete ci-qa-temp --yes
wp db query "DELETE FROM XMTBGX_options WHERE option_name LIKE '_transient_ats_ci_%' OR option_name LIKE '_transient_timeout_ats_ci_%'"
```

Expected: user deleted; stale ranking transients cleared.

- [ ] **Step 5: Final report to the user**

Report: feature live on staging (via synced copies in the active `atsdiamondtools-child` theme), all four verify scripts green, browser QA screenshots. Remind the user of the two follow-ups that are **theirs to decide**:
1. Running `gulp dist` (version bump + push) and any production deploy — both need their explicit go-ahead.
2. The client question from the spec: should checkout newsletter opt-outs be excluded from Brevo sends? (Current behaviour: included; Brevo's own blacklist still prevents emailing unsubscribed contacts.)

---

## Execution Notes

- **Order matters:** Tasks 1→2→3→4→5; each later task consumes earlier interfaces.
- **The verify scripts are the regression suite.** After any fix, re-run every `ci-task*.php` that touches the changed file.
- **Caching:** the ranking transient is 15 min; when testing data changes use `nocache` (script arg) or clear `_transient_ats_ci_%` rows as in Task 5 Step 4.
- **Do not** edit anything under `atsdiamondtools-child/` directly — it is build output; the `cp` sync is one-way from `src/`.

