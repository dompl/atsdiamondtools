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
				<label>Min products
					<input type="number" name="min_units" min="0" value="<?php echo esc_attr( $args['min_units'] ? $args['min_units'] : '' ); ?>">
				</label>
				<label>Min orders
					<input type="number" name="min_orders" min="0" value="<?php echo esc_attr( $args['min_orders'] ? $args['min_orders'] : '' ); ?>">
				</label>
				<label>Min spend (&pound;)
					<input type="number" name="min_spend" min="0" step="0.01" value="<?php echo esc_attr( $args['min_spend'] ? $args['min_spend'] : '' ); ?>">
				</label>
				<label>Dormant for (days)
					<input type="number" name="dormant" min="0" value="<?php echo esc_attr( $args['dormant_days'] ? $args['dormant_days'] : '' ); ?>" placeholder="e.g. 180">
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
