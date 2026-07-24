<?php
/**
 * Satellite bridge — account endpoints (ATS side).
 *
 * Shared accounts live on ATS; the platform proxies login, registration,
 * password reset and order history to these signed REST routes. The load-bearing
 * guarantee is isolation: order history is filtered to the requesting channel,
 * and the channel is taken from the authenticated (HMAC-signed) request, never
 * from a client parameter — so one satellite can never read another's, or ATS's,
 * orders, even with a crafted request.
 *
 * Routes (namespace satellite/v1), all requiring a valid channel signature:
 *   POST login          { login, password }        -> { token, customer }
 *   POST register       { email, password, ... }   -> { token, customer }
 *   POST reset-request  { email }                   -> { exists, key, login }
 *   POST reset          { login, key, password }    -> { ok }
 *   GET  orders         (X-EA-Auth: <token>)        -> { orders: [...] }
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ *
 * Pure helpers (the isolation guarantee)
 * ------------------------------------------------------------------ */

if ( ! function_exists( 'skylinewp_child_satellite_customer_orders_args' ) ) {
	/**
	 * wc_get_orders() args scoped to one customer. Channel enforcement is applied
	 * to the results (see filter_orders_by_channel), not trusted to the query.
	 *
	 * @param int $customer_id Customer id.
	 * @param int $limit       Max orders.
	 * @return array
	 */
	function skylinewp_child_satellite_customer_orders_args( $customer_id, $limit = 25 ) {
		return array(
			'customer_id' => (int) $customer_id,
			'limit'       => (int) $limit,
			'orderby'     => 'date',
			'order'       => 'DESC',
		);
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_filter_orders_by_channel' ) ) {
	/**
	 * Keep only the orders belonging to the given channel. Pure. This is the
	 * enforcement point for shared-account isolation.
	 *
	 * @param array  $orders      List of arrays each having 'channel' => string.
	 * @param string $channel_key The authenticated channel.
	 * @return array Filtered list (order preserved).
	 */
	function skylinewp_child_satellite_filter_orders_by_channel( $orders, $channel_key ) {
		$channel_key = (string) $channel_key;
		$out         = array();
		foreach ( (array) $orders as $order ) {
			$order_channel = isset( $order['channel'] ) ? (string) $order['channel'] : '';
			if ( '' !== $channel_key && $order_channel === $channel_key ) {
				$out[] = $order;
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_public_order' ) ) {
	/**
	 * Reduce an internal order row to the customer-safe fields. Pure. Drops the
	 * channel marker so it never leaves ATS.
	 *
	 * @param array $order Internal order row.
	 * @return array
	 */
	function skylinewp_child_satellite_public_order( $order ) {
		return array(
			'id'       => isset( $order['id'] ) ? (int) $order['id'] : 0,
			'number'   => isset( $order['number'] ) ? (string) $order['number'] : '',
			'status'   => isset( $order['status'] ) ? (string) $order['status'] : '',
			'total'    => isset( $order['total'] ) ? (string) $order['total'] : '',
			'date'     => isset( $order['date'] ) ? (string) $order['date'] : '',
			'tracking' => isset( $order['tracking'] ) ? $order['tracking'] : array(),
		);
	}
}

/* ------------------------------------------------------------------ *
 * Tokens (login session held by the platform)
 * ------------------------------------------------------------------ */

if ( ! function_exists( 'skylinewp_child_satellite_issue_token' ) ) {
	/**
	 * Issue an opaque login token mapping to a customer id (24h).
	 *
	 * @param int $customer_id Customer id.
	 * @return string Token.
	 */
	function skylinewp_child_satellite_issue_token( $customer_id ) {
		$token = wp_generate_password( 43, false, false );
		set_transient( 'sat_auth_' . $token, (int) $customer_id, DAY_IN_SECONDS );
		return $token;
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_customer_for_token' ) ) {
	/**
	 * Resolve a token to a customer id, or 0.
	 *
	 * @param string $token Token.
	 * @return int
	 */
	function skylinewp_child_satellite_customer_for_token( $token ) {
		if ( '' === (string) $token ) {
			return 0;
		}
		$id = get_transient( 'sat_auth_' . $token );
		return $id ? (int) $id : 0;
	}
}

/* ------------------------------------------------------------------ *
 * REST routes
 * ------------------------------------------------------------------ */

if ( ! function_exists( 'skylinewp_child_satellite_account_permission' ) ) {
	/**
	 * Permission callback: a valid channel signature is required for every
	 * account route.
	 *
	 * @return true|WP_Error
	 */
	function skylinewp_child_satellite_account_permission() {
		if ( null !== skylinewp_child_satellite_current_channel() ) {
			return true;
		}
		return new WP_Error( 'satellite_forbidden', 'Invalid channel signature.', array( 'status' => 401 ) );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_register_account_routes' ) ) {
	/**
	 * Register the satellite/v1 account routes.
	 */
	function skylinewp_child_satellite_register_account_routes() {
		$perm = 'skylinewp_child_satellite_account_permission';

		register_rest_route( 'satellite/v1', '/login', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => 'skylinewp_child_satellite_route_login',
		) );
		register_rest_route( 'satellite/v1', '/register', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => 'skylinewp_child_satellite_route_register',
		) );
		register_rest_route( 'satellite/v1', '/reset-request', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => 'skylinewp_child_satellite_route_reset_request',
		) );
		register_rest_route( 'satellite/v1', '/reset', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => 'skylinewp_child_satellite_route_reset',
		) );
		register_rest_route( 'satellite/v1', '/orders', array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => 'skylinewp_child_satellite_route_orders',
		) );
	}
	add_action( 'rest_api_init', 'skylinewp_child_satellite_register_account_routes' );
}

if ( ! function_exists( 'skylinewp_child_satellite_customer_payload' ) ) {
	/**
	 * Shape a WP_User as the customer payload returned to the platform.
	 *
	 * @param WP_User $user User.
	 * @return array
	 */
	function skylinewp_child_satellite_customer_payload( $user ) {
		return array(
			'id'         => (int) $user->ID,
			'email'      => (string) $user->user_email,
			'first_name' => (string) get_user_meta( $user->ID, 'first_name', true ),
			'last_name'  => (string) get_user_meta( $user->ID, 'last_name', true ),
		);
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_route_login' ) ) {
	/**
	 * POST /login — authenticate and issue a token.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function skylinewp_child_satellite_route_login( $request ) {
		$login    = trim( (string) $request->get_param( 'login' ) );
		$password = (string) $request->get_param( 'password' );
		if ( '' === $login || '' === $password ) {
			return new WP_Error( 'satellite_bad_request', 'Missing credentials.', array( 'status' => 400 ) );
		}
		$user = wp_authenticate( $login, $password );
		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'satellite_login_failed', 'Incorrect email or password.', array( 'status' => 401 ) );
		}
		return rest_ensure_response( array(
			'token'    => skylinewp_child_satellite_issue_token( $user->ID ),
			'customer' => skylinewp_child_satellite_customer_payload( $user ),
		) );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_route_register' ) ) {
	/**
	 * POST /register — create a WooCommerce customer stamped with its channel.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function skylinewp_child_satellite_route_register( $request ) {
		$channel = skylinewp_child_satellite_current_channel();
		$email   = sanitize_email( (string) $request->get_param( 'email' ) );
		$pass    = (string) $request->get_param( 'password' );
		if ( ! is_email( $email ) || '' === $pass ) {
			return new WP_Error( 'satellite_bad_request', 'A valid email and password are required.', array( 'status' => 400 ) );
		}
		if ( email_exists( $email ) ) {
			return new WP_Error( 'satellite_exists', 'An account with that email already exists.', array( 'status' => 409 ) );
		}
		$customer_id = wc_create_new_customer( $email, '', $pass );
		if ( is_wp_error( $customer_id ) ) {
			return new WP_Error( 'satellite_register_failed', 'Could not create the account.', array( 'status' => 400 ) );
		}
		$first = sanitize_text_field( (string) $request->get_param( 'first_name' ) );
		$last  = sanitize_text_field( (string) $request->get_param( 'last_name' ) );
		if ( '' !== $first ) {
			update_user_meta( $customer_id, 'first_name', $first );
		}
		if ( '' !== $last ) {
			update_user_meta( $customer_id, 'last_name', $last );
		}
		update_user_meta( $customer_id, '_origin_channel', $channel['key'] );

		$user = get_user_by( 'id', $customer_id );
		return rest_ensure_response( array(
			'token'    => skylinewp_child_satellite_issue_token( $customer_id ),
			'customer' => skylinewp_child_satellite_customer_payload( $user ),
		) );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_route_reset_request' ) ) {
	/**
	 * POST /reset-request — issue a reset key so the satellite can email its own
	 * branded link to its own reset page. The platform is responsible for a
	 * uniform, non-enumerating response to the browser.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function skylinewp_child_satellite_route_reset_request( $request ) {
		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		$user  = $email ? get_user_by( 'email', $email ) : false;
		if ( ! $user ) {
			return rest_ensure_response( array( 'exists' => false ) );
		}
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return rest_ensure_response( array( 'exists' => false ) );
		}
		return rest_ensure_response( array(
			'exists' => true,
			'key'    => $key,
			'login'  => $user->user_login,
		) );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_route_reset' ) ) {
	/**
	 * POST /reset — validate the key and set the new password.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function skylinewp_child_satellite_route_reset( $request ) {
		$login = (string) $request->get_param( 'login' );
		$key   = (string) $request->get_param( 'key' );
		$pass  = (string) $request->get_param( 'password' );
		if ( '' === $login || '' === $key || '' === $pass ) {
			return new WP_Error( 'satellite_bad_request', 'Missing reset details.', array( 'status' => 400 ) );
		}
		$user = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'satellite_reset_invalid', 'This reset link has expired. Please request a new one.', array( 'status' => 400 ) );
		}
		reset_password( $user, $pass );
		return rest_ensure_response( array( 'ok' => true ) );
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_order_tracking' ) ) {
	/**
	 * Best-effort tracking rows from the Shipment Tracking plugin meta.
	 *
	 * @param WC_Order $order Order.
	 * @return array
	 */
	function skylinewp_child_satellite_order_tracking( $order ) {
		$items = $order->get_meta( '_wc_shipment_tracking_items' );
		if ( ! is_array( $items ) ) {
			return array();
		}
		$out = array();
		foreach ( $items as $item ) {
			$out[] = array(
				'carrier' => isset( $item['tracking_provider'] ) ? (string) $item['tracking_provider'] : '',
				'number'  => isset( $item['tracking_number'] ) ? (string) $item['tracking_number'] : '',
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'skylinewp_child_satellite_route_orders' ) ) {
	/**
	 * GET /orders — the authenticated customer's orders, filtered to this channel.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function skylinewp_child_satellite_route_orders( $request ) {
		$channel     = skylinewp_child_satellite_current_channel();
		$token       = trim( (string) $request->get_header( 'x-ea-auth' ) );
		$customer_id = skylinewp_child_satellite_customer_for_token( $token );
		if ( ! $customer_id ) {
			return new WP_Error( 'satellite_unauthenticated', 'Please sign in again.', array( 'status' => 401 ) );
		}

		$orders = wc_get_orders( skylinewp_child_satellite_customer_orders_args( $customer_id ) );
		$rows   = array();
		foreach ( $orders as $order ) {
			if ( ! ( $order instanceof WC_Order ) ) {
				continue;
			}
			$rows[] = array(
				'id'       => $order->get_id(),
				'number'   => $order->get_order_number(),
				'status'   => $order->get_status(),
				'total'    => $order->get_total(),
				'date'     => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
				'channel'  => (string) $order->get_meta( '_sales_channel' ),
				'tracking' => skylinewp_child_satellite_order_tracking( $order ),
			);
		}

		// The isolation enforcement: only this channel's orders, channel taken
		// from the signed request — never a client parameter.
		$rows    = skylinewp_child_satellite_filter_orders_by_channel( $rows, $channel['key'] );
		$public  = array_map( 'skylinewp_child_satellite_public_order', $rows );

		return rest_ensure_response( array( 'orders' => array_values( $public ) ) );
	}
}
