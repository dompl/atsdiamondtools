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
 * Popup HTML fragment for one customer — redesigned layout.
 *
 * @param array $d Output of ats_ci_customer_detail().
 */
function ats_ci_detail_html( array $d ) {
	$c       = $d['customer'];
	$s       = $d['stats'];
	$name    = trim( $c->first_name . ' ' . $c->last_name );
	$display = $name ? $name : $c->email;
	$loc     = trim( implode( ', ', array_filter( array( $c->city, $c->country ) ) ) );
	$aov     = $s->att_orders ? (float) $s->att_spend / (int) $s->att_orders : 0;
	$row     = (object) array(
		'att_orders'      => $s->att_orders,
		'avg_gap_days'    => $s->avg_gap_days,
		'days_since_last' => $s->days_since_last,
	);
	$segment = ats_ci_segment( $row );
	$color   = ats_ci_segment_color( $segment );
	$initial = strtoupper( mb_substr( $display, 0, 1 ) );
	$due     = (int) $s->att_orders >= 3 && (float) $s->avg_gap_days > 0
		&& (int) $s->days_since_last >= (float) $s->avg_gap_days;

	$metrics = array(
		array( 'money-alt', 'Lifetime spend', wp_kses_post( wc_price( (float) $s->att_spend ) ) ),
		array( 'cart', 'Orders', esc_html( number_format_i18n( (int) $s->att_orders ) ) ),
		array( 'products', 'Units', esc_html( number_format_i18n( (int) $s->att_units ) ) ),
		array( 'chart-bar', 'Avg order', wp_kses_post( wc_price( $aov ) ) ),
		array( 'calendar-alt', 'First order', esc_html( mysql2date( 'j M Y', $s->att_first ) ) ),
		array( 'calendar-alt', 'Last order', esc_html( mysql2date( 'j M Y', $s->att_last ) ) ),
		array( 'update', 'Avg gap', $s->avg_gap_days ? esc_html( number_format_i18n( (float) $s->avg_gap_days ) . ' days' ) : '&mdash;' ),
		array( 'clock', 'Days since', esc_html( number_format_i18n( (int) $s->days_since_last ) ) ),
	);

	ob_start();
	?>
	<div class="ats-ci-detail">
		<div class="ats-ci-detail-head">
			<span class="ats-ci-avatar" style="background:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $initial ); ?></span>
			<div class="ats-ci-detail-id">
				<h2><?php echo esc_html( $display ); ?>
					<?php echo wp_kses_post( ats_ci_badge( $segment ) ); ?>
					<?php if ( $due ) : ?><span class="ats-ci-due">Due to reorder</span><?php endif; ?>
				</h2>
				<p>
					<a href="mailto:<?php echo esc_attr( $c->email ); ?>"><?php echo esc_html( $c->email ); ?></a>
					<?php if ( $loc ) : ?> &middot; <?php echo esc_html( $loc ); ?><?php endif; ?>
					&middot; <?php echo null === $c->user_id ? 'Guest checkout' : 'Registered account'; ?>
				</p>
			</div>
		</div>

		<div class="ats-ci-detail-metrics">
			<?php foreach ( $metrics as $m ) : ?>
				<div class="ats-ci-metric">
					<span class="dashicons dashicons-<?php echo esc_attr( $m[0] ); ?>"></span>
					<div><span class="l"><?php echo esc_html( $m[1] ); ?></span><strong><?php echo wp_kses_post( $m[2] ); ?></strong></div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="ats-ci-detail-cols">
			<section class="ats-ci-card">
				<h3>Most bought</h3>
				<?php if ( $d['top_products'] ) : ?>
					<ul class="ats-ci-top">
						<?php foreach ( $d['top_products'] as $tp ) : ?>
							<li><span><?php echo esc_html( $tp->name ); ?></span><strong>&times;<?php echo esc_html( number_format_i18n( (int) $tp->qty ) ); ?></strong></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="ats-ci-empty">No product history.</p>
				<?php endif; ?>
			</section>

			<section class="ats-ci-card">
				<h3>Coupons used</h3>
				<?php if ( $d['coupons'] ) : ?>
					<table class="ats-ci-mini">
						<thead><tr><th>Code</th><th class="ats-ci-num">Used</th><th class="ats-ci-num">Discount</th></tr></thead>
						<tbody>
						<?php foreach ( $d['coupons'] as $cp ) : ?>
							<tr>
								<td><?php echo esc_html( $cp->code ); ?></td>
								<td class="ats-ci-num"><?php echo esc_html( number_format_i18n( (int) $cp->times_used ) ); ?></td>
								<td class="ats-ci-num"><?php echo wp_kses_post( wc_price( (float) $cp->total_discount ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="ats-ci-empty">No coupons used.</p>
				<?php endif; ?>
			</section>
		</div>

		<section class="ats-ci-card ats-ci-orders-card">
			<h3>Order history <span class="ats-ci-count"><?php echo esc_html( number_format_i18n( count( $d['orders'] ) ) ); ?></span></h3>
			<div class="ats-ci-orders">
				<table class="ats-ci-mini">
					<thead><tr><th>Date</th><th>Order</th><th>Items</th><th class="ats-ci-num">Total</th><th>Status</th><th>Coupon</th></tr></thead>
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
							<td class="ats-ci-items"><?php echo esc_html( isset( $d['items'][ $oid ] ) ? $d['items'][ $oid ] : '—' ); ?></td>
							<td class="ats-ci-num"><?php echo wp_kses_post( wc_price( (float) $o->total_sales ) ); ?></td>
							<td><span class="ats-ci-status is-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status ); ?></span></td>
							<td><?php echo esc_html( isset( $d['order_coupons'][ $oid ] ) ? $d['order_coupons'][ $oid ] : '' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
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

/**
 * AJAX: re-render the results block (stat cards + summary + table + pagination)
 * for a set of filters. Powers live reloads when the period (or any filter)
 * changes without a full page navigation.
 */
function ats_ci_ajax_results() {
	check_ajax_referer( 'ats_ci', 'nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}
	$filters = array();
	if ( isset( $_POST['filters'] ) ) {
		parse_str( sanitize_text_field( wp_unslash( $_POST['filters'] ) ), $filters );
	}
	// Expose the parsed filters as $_GET so ats_ci_url() rebuilds correct
	// sort/pagination links inside the returned fragment.
	$_GET   = $filters; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$args   = ats_ci_parse_args( $filters );
	$result = ats_ci_get_customers( $args );
	ob_start();
	ats_ci_render_results( $args, $result );
	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}
add_action( 'wp_ajax_ats_ci_results', 'ats_ci_ajax_results' );

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
