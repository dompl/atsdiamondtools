<?php
/**
 * Customer Insights — AJAX endpoints
 *
 * Customer detail popup fragment, plus the Brevo client and its
 * endpoints (send filtered customers to a Brevo list).
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
	$c    = $d['customer'];
	$s    = $d['stats'];
	$name = trim( $c->first_name . ' ' . $c->last_name );
	$loc  = trim( implode( ', ', array_filter( array( $c->city, $c->country ) ) ) );
	$aov  = $s->att_orders ? (float) $s->att_spend / (int) $s->att_orders : 0;
	$row  = (object) array(
		'att_orders'      => $s->att_orders,
		'avg_gap_days'    => $s->avg_gap_days,
		'days_since_last' => $s->days_since_last,
	);
	$due  = (int) $s->att_orders >= 3 && (float) $s->avg_gap_days > 0
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
