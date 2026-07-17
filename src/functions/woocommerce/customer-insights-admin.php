<?php
/**
 * Customer Insights — admin page
 *
 * WooCommerce submenu: ranked customer browser with grouped filters,
 * fully sortable columns (asc/desc, native list-table markup), summary
 * stat cards and a help modal. Detail popup and Brevo actions hook in
 * via the ats_ci_toolbar / ats_ci_page_footer actions.
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
 * Sortable column header using core list-table markup, so every column
 * is a full-width click target with native asc/desc indicators.
 *
 * @param string $key     Orderby key (must be in the data-layer whitelist).
 * @param string $label   Column label.
 * @param array  $args    Parsed filter args (orderby, order).
 * @param bool   $numeric Right-align the column.
 * @param string $width   Optional CSS width.
 */
function ats_ci_sort_th( $key, $label, array $args, $numeric = true, $width = '' ) {
	$is_current = $args['orderby'] === $key;
	$next       = $is_current
		? ( 'desc' === $args['order'] ? 'asc' : 'desc' )
		: ( 'name' === $key ? 'asc' : 'desc' );
	$classes    = 'manage-column' . ( $numeric ? ' ats-ci-num' : '' ) . ' '
		. ( $is_current ? 'sorted ' . $args['order'] : 'sortable ' . $next );
	$url        = ats_ci_url(
		array(
			'orderby' => $key,
			'order'   => $next,
			'paged'   => 1,
		)
	);
	$style      = $width ? ' style="width:' . esc_attr( $width ) . '"' : '';
	return '<th scope="col" class="' . esc_attr( $classes ) . '"' . $style . '>'
		. '<a href="' . esc_url( $url ) . '"><span>' . esc_html( $label ) . '</span>'
		. '<span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span>'
		. '<span class="sorting-indicator desc" aria-hidden="true"></span></span></a></th>';
}

/**
 * One summary stat card.
 *
 * @param string $icon  Dashicon slug.
 * @param string $label Card label.
 * @param string $value Pre-formatted value HTML.
 */
function ats_ci_stat_card( $icon, $label, $value ) {
	return '<div class="ats-ci-stat-card"><span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>'
		. '<div><span>' . esc_html( $label ) . '</span><strong>' . wp_kses_post( $value ) . '</strong></div></div>';
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
	$avg     = $result['sum_orders'] ? $result['sum_spend'] / $result['sum_orders'] : 0;
	$coupons = ats_ci_get_coupon_options();
	$cats    = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	if ( is_wp_error( $cats ) ) {
		$cats = array();
	}
	$t = ats_ci_thresholds();
	?>
	<div class="wrap ats-ci-wrap">
		<h1 class="wp-heading-inline">Customer Insights</h1>
		<button type="button" class="page-title-action" id="ats-ci-help-open">How to use</button>
		<hr class="wp-header-end">

		<div class="ats-ci-stat-cards">
			<?php
			echo wp_kses_post( ats_ci_stat_card( 'groups', 'Customers', number_format_i18n( $total ) ) );
			echo wp_kses_post( ats_ci_stat_card( 'cart', 'Orders', number_format_i18n( $result['sum_orders'] ) ) );
			echo wp_kses_post( ats_ci_stat_card( 'products', 'Units bought', number_format_i18n( $result['sum_units'] ) ) );
			echo wp_kses_post( ats_ci_stat_card( 'money-alt', 'Revenue', wc_price( $result['sum_spend'] ) ) );
			echo wp_kses_post( ats_ci_stat_card( 'chart-line', 'Avg order', wc_price( $avg ) ) );
			?>
		</div>

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ats-ci-filter-card">
			<input type="hidden" name="page" value="ats-customer-insights">
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $args['orderby'] ); ?>">
			<input type="hidden" name="order" value="<?php echo esc_attr( $args['order'] ); ?>">
			<div class="ats-ci-fieldsets">

				<fieldset>
					<legend>Period</legend>
					<label>Date range
						<select name="range" id="ats-ci-range">
							<?php
							$ranges = array(
								'30d'    => 'Last 30 days',
								'90d'    => 'Last 90 days',
								'12m'    => 'Last 12 months',
								'all'    => 'All time',
								'custom' => 'Custom&hellip;',
							);
							foreach ( $ranges as $val => $lab ) {
								printf(
									'<option value="%s"%s>%s</option>',
									esc_attr( $val ),
									selected( $args['range'], $val, false ),
									wp_kses_post( $lab )
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
				</fieldset>

				<fieldset>
					<legend>Minimums</legend>
					<label>Products bought
						<input type="number" name="min_units" min="0" placeholder="any" value="<?php echo esc_attr( $args['min_units'] ? $args['min_units'] : '' ); ?>">
					</label>
					<label>Orders placed
						<input type="number" name="min_orders" min="0" placeholder="any" value="<?php echo esc_attr( $args['min_orders'] ? $args['min_orders'] : '' ); ?>">
					</label>
					<label>Total spend (&pound;)
						<input type="number" name="min_spend" min="0" step="0.01" placeholder="any" value="<?php echo esc_attr( $args['min_spend'] ? $args['min_spend'] : '' ); ?>">
					</label>
				</fieldset>

				<fieldset>
					<legend>Activity</legend>
					<label>Dormant for (days)
						<input type="number" name="dormant" min="0" placeholder="e.g. 180" value="<?php echo esc_attr( $args['dormant_days'] ? $args['dormant_days'] : '' ); ?>">
					</label>
					<label>Buyer type
						<select name="buyer">
							<option value="all" <?php selected( $args['buyer'], 'all' ); ?>>All buyers</option>
							<option value="repeat" <?php selected( $args['buyer'], 'repeat' ); ?>>Repeat buyers</option>
							<option value="one-time" <?php selected( $args['buyer'], 'one-time' ); ?>>One-time buyers</option>
						</select>
					</label>
					<label class="ats-ci-check"><input type="checkbox" name="at_risk" value="1" <?php checked( $args['at_risk'] ); ?>> At-risk only</label>
				</fieldset>

				<fieldset>
					<legend>Purchases</legend>
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
						<input type="text" name="product" placeholder="e.g. blade" value="<?php echo esc_attr( $args['product'] ); ?>">
					</label>
					<label>Category
						<select name="category">
							<option value="">All categories</option>
							<?php foreach ( $cats as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $args['category_id'], $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</fieldset>

				<fieldset>
					<legend>Customers</legend>
					<label>Account
						<select name="account">
							<option value="all" <?php selected( $args['account'], 'all' ); ?>>All</option>
							<option value="registered" <?php selected( $args['account'], 'registered' ); ?>>Registered</option>
							<option value="guest" <?php selected( $args['account'], 'guest' ); ?>>Guest</option>
						</select>
					</label>
					<label>Rows per page
						<select name="per_page">
							<?php foreach ( array( 25, 50, 100 ) as $pp ) : ?>
								<option value="<?php echo esc_attr( $pp ); ?>" <?php selected( $args['per_page'], $pp ); ?>><?php echo esc_html( $pp ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</fieldset>

			</div>
			<p class="ats-ci-filter-actions">
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
					<th scope="col" style="width:44px">#</th>
					<?php
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- helper output is escaped.
					echo ats_ci_sort_th( 'name', 'Customer', $args, false );
					// phpcs:enable
					?>
					<th scope="col" style="width:86px">Segment</th>
					<?php
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- helper output is escaped.
					echo ats_ci_sort_th( 'orders', 'Orders', $args, true, '72px' );
					echo ats_ci_sort_th( 'units', 'Units', $args, true, '72px' );
					echo ats_ci_sort_th( 'spend', 'Spend', $args, true, '104px' );
					echo ats_ci_sort_th( 'aov', 'Avg order', $args, true, '94px' );
					echo ats_ci_sort_th( 'last_order', 'Last order', $args, true, '108px' );
					echo ats_ci_sort_th( 'days_since', 'Days ago', $args, true, '86px' );
					echo ats_ci_sort_th( 'coupon_orders', 'Coupons', $args, true, '86px' );
					// phpcs:enable
					?>
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
						<td class="ats-ci-rank"><?php echo esc_html( number_format_i18n( ( ( $paged - 1 ) * $args['per_page'] ) + $i + 1 ) ); ?></td>
						<td>
							<strong><?php echo esc_html( $name ? $name : $row->email ); ?></strong>
							<?php if ( null === $row->user_id ) : ?><span class="ats-ci-guest">guest</span><?php endif; ?>
							<br><span class="ats-ci-email"><?php echo esc_html( $row->email ); ?></span>
						</td>
						<td><?php echo wp_kses_post( ats_ci_badge( $row->segment ) ); ?></td>
						<td class="ats-ci-num"><?php echo esc_html( number_format_i18n( (int) $row->orders_count ) ); ?></td>
						<td class="ats-ci-num"><?php echo esc_html( number_format_i18n( (int) $row->units ) ); ?></td>
						<td class="ats-ci-num"><?php echo wp_kses_post( wc_price( (float) $row->total_spend ) ); ?></td>
						<td class="ats-ci-num"><?php echo wp_kses_post( wc_price( $aov ) ); ?></td>
						<td class="ats-ci-num"><?php echo esc_html( mysql2date( 'j M Y', $row->last_order ) ); ?></td>
						<td class="ats-ci-num"><?php echo esc_html( number_format_i18n( (int) $row->days_since_last ) ); ?></td>
						<td class="ats-ci-num"><?php echo esc_html( number_format_i18n( (int) $row->coupon_orders ) ); ?></td>
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

		<div id="ats-ci-help-modal" class="ats-ci-modal" style="display:none">
			<div class="ats-ci-modal-inner" style="width:min(640px,92vw)">
				<button type="button" class="ats-ci-modal-close" aria-label="Close">&times;</button>
				<h2>How to use Customer Insights</h2>
				<p>Every customer, ranked by what they buy. The <strong>date range</strong> controls the numbers
				in the table and the stat cards; the segment badge and &ldquo;days ago&rdquo; always reflect the
				customer&rsquo;s full history.</p>

				<h3>Sorting</h3>
				<p>Click any column heading to sort by it; click it again to flip between highest-first and
				lowest-first. The arrow shows the current direction.</p>

				<h3>Filters</h3>
				<table class="widefat striped">
					<tbody>
						<tr><td><strong>Period</strong></td><td>Preset ranges, or &ldquo;Custom&rdquo; for exact from/to dates &ndash; e.g. 1 July 2025 to 30 June 2026.</td></tr>
						<tr><td><strong>Minimums</strong></td><td>Only show customers with at least this many products, orders or spend in the period.</td></tr>
						<tr><td><strong>Dormant for</strong></td><td>No order in the last N days &ndash; e.g. 180 finds customers quiet for six months.</td></tr>
						<tr><td><strong>At-risk only</strong></td><td>Regulars (3+ orders) who are overdue compared with their own usual gap between orders.</td></tr>
						<tr><td><strong>Purchases</strong></td><td>Customers who used a coupon, or bought a given product or category.</td></tr>
						<tr><td><strong>Customers</strong></td><td>Registered accounts vs guest checkouts; repeat vs one-time buyers under Activity.</td></tr>
					</tbody>
				</table>

				<h3>Segments</h3>
				<table class="widefat striped">
					<tbody>
						<tr><td><?php echo wp_kses_post( ats_ci_badge( 'champion' ) ); ?></td><td><?php echo esc_html( (int) $t['champion_orders'] ); ?>+ orders and still active.</td></tr>
						<tr><td><?php echo wp_kses_post( ats_ci_badge( 'loyal' ) ); ?></td><td>Repeat buyer, ordering within their normal rhythm.</td></tr>
						<tr><td><?php echo wp_kses_post( ats_ci_badge( 'at-risk' ) ); ?></td><td>Regular buyer overdue by more than <?php echo esc_html( $t['at_risk_multiplier'] ); ?>&times; their usual gap.</td></tr>
						<tr><td><?php echo wp_kses_post( ats_ci_badge( 'dormant' ) ); ?></td><td>No order in <?php echo esc_html( (int) $t['dormant_days'] ); ?>+ days.</td></tr>
						<tr><td><?php echo wp_kses_post( ats_ci_badge( 'one-time' ) ); ?></td><td>Exactly one order ever.</td></tr>
					</tbody>
				</table>

				<h3>Customer popup</h3>
				<p>Click any row for the customer&rsquo;s full story: lifetime stats, most-bought products,
				coupons used, every order, and a &ldquo;Due to reorder&rdquo; flag when they&rsquo;re overdue.</p>

				<h3>Send to Brevo</h3>
				<p>When the filtered list is the audience you want, press <strong>Send to Brevo</strong>.
				It creates (or adds to) a Brevo mailing list containing exactly those customers, ready
				for a campaign.</p>
			</div>
		</div>

		<style>
			.ats-ci-stat-cards { display: flex; flex-wrap: wrap; gap: 12px; margin: 16px 0 4px; }
			.ats-ci-stat-card { flex: 1 1 150px; display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 12px 16px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
			.ats-ci-stat-card .dashicons { font-size: 26px; width: 26px; height: 26px; color: #2271b1; }
			.ats-ci-stat-card span:not(.dashicons) { display: block; color: #646970; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; }
			.ats-ci-stat-card strong { font-size: 17px; line-height: 1.3; }
			.ats-ci-filter-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 2px 20px 14px; margin: 12px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
			.ats-ci-fieldsets { display: flex; flex-wrap: wrap; gap: 0 32px; }
			.ats-ci-fieldsets fieldset { border: 0; padding: 0; margin: 14px 0 0; min-width: 170px; flex: 1 1 170px; }
			.ats-ci-fieldsets legend { font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: .5px; color: #646970; padding-bottom: 2px; }
			.ats-ci-fieldsets label { display: flex; flex-direction: column; gap: 2px; font-size: 12px; font-weight: 500; margin-top: 8px; }
			.ats-ci-fieldsets label.ats-ci-check { flex-direction: row; align-items: center; gap: 6px; margin-top: 12px; }
			.ats-ci-fieldsets input, .ats-ci-fieldsets select { max-width: 100%; }
			.ats-ci-filter-actions { margin: 14px 0 2px; }
			.ats-ci-summary { display: flex; align-items: center; gap: 10px; }
			.ats-ci-table th.ats-ci-num, .ats-ci-table td.ats-ci-num { text-align: right; }
			.ats-ci-table thead th.ats-ci-num a { justify-content: flex-end; }
			.ats-ci-table thead th.sortable a, .ats-ci-table thead th.sorted a { display: flex; align-items: center; gap: 2px; }
			.ats-ci-table tbody tr:hover { background: #f0f6fc; }
			.ats-ci-rank { color: #8c8f94; }
			.ats-ci-row { cursor: pointer; }
			.ats-ci-email { color: #646970; font-size: 12px; }
			.ats-ci-guest { background: #f0f0f1; border-radius: 3px; padding: 1px 5px; font-size: 11px; margin-left: 4px; }
			.ats-ci-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
			.ats-ci-badge--champion { background: #2271b1; color: #fff; }
			.ats-ci-badge--loyal { background: #00a32a; color: #fff; }
			.ats-ci-badge--at-risk { background: #dba617; color: #1d2327; }
			.ats-ci-badge--dormant { background: #d63638; color: #fff; }
			.ats-ci-badge--one-time { background: #f0f0f1; color: #1d2327; }
			#ats-ci-help-modal h3 { margin: 18px 0 6px; }
			#ats-ci-help-modal table { margin: 6px 0 4px; }
		</style>

		<script>
		(function () {
			var range = document.getElementById('ats-ci-range');
			function toggleCustom() {
				document.querySelectorAll('.ats-ci-custom-date').forEach(function (el) {
					el.style.display = 'custom' === range.value ? '' : 'none';
				});
			}
			range.addEventListener('change', toggleCustom);
			toggleCustom();

			var help = document.getElementById('ats-ci-help-modal');
			document.getElementById('ats-ci-help-open').addEventListener('click', function () {
				help.style.display = 'flex';
			});
			help.querySelector('.ats-ci-modal-close').addEventListener('click', function () {
				help.style.display = 'none';
			});
			help.addEventListener('click', function (e) { if (e.target === help) { help.style.display = 'none'; } });
		})();
		</script>

		<?php do_action( 'ats_ci_page_footer', $args, $result ); ?>
	</div>
	<?php
}

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
					if (!res || !res.success) { throw new Error('lists'); }
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
