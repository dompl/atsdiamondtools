<?php
/**
 * Social Login OAuth Handler
 *
 * Custom OAuth 2.0 implementation for Google, Facebook, and Apple login.
 * Credentials are managed via ATS Settings (ACF options page).
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register rewrite rules for OAuth endpoints.
 *
 * /ats-auth/{provider}          → initiates OAuth redirect
 * /ats-auth/{provider}/callback → handles provider callback
 */
function ats_social_login_rewrite_rules() {
	add_rewrite_rule(
		'^ats-auth/(google|facebook|apple)/callback/?$',
		'index.php?ats_auth_provider=$matches[1]&ats_auth_action=callback',
		'top'
	);
	add_rewrite_rule(
		'^ats-auth/(google|facebook|apple)/?$',
		'index.php?ats_auth_provider=$matches[1]&ats_auth_action=redirect',
		'top'
	);
}
add_action( 'init', 'ats_social_login_rewrite_rules' );

/**
 * Register custom query vars.
 */
function ats_social_login_query_vars( $vars ) {
	$vars[] = 'ats_auth_provider';
	$vars[] = 'ats_auth_action';
	return $vars;
}
add_filter( 'query_vars', 'ats_social_login_query_vars' );

/**
 * Handle OAuth requests on template_redirect.
 */
function ats_social_login_handle_request() {
	$provider = get_query_var( 'ats_auth_provider' );
	$action   = get_query_var( 'ats_auth_action' );

	if ( ! $provider || ! $action ) {
		return;
	}

	if ( is_user_logged_in() ) {
		wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
		exit;
	}

	if ( 'redirect' === $action ) {
		ats_social_login_redirect( $provider );
	} elseif ( 'callback' === $action ) {
		ats_social_login_callback( $provider );
	}
}
add_action( 'template_redirect', 'ats_social_login_handle_request' );

/**
 * Flush rewrite rules on theme activation.
 */
function ats_social_login_flush_rules() {
	ats_social_login_rewrite_rules();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'ats_social_login_flush_rules' );

// ─── Provider Configuration ─────────────────────────────────────────────────

/**
 * Get provider config from ACF options.
 *
 * @param string $provider Provider key (google|facebook|apple).
 * @return array|false Config array or false if not enabled/configured.
 */
function ats_social_get_provider_config( $provider ) {
	$configs = [
		'google'   => [
			'enabled'      => get_field( 'ats_social_google_enabled', 'option' ),
			'client_id'    => get_field( 'ats_social_google_client_id', 'option' ),
			'client_secret'=> get_field( 'ats_social_google_client_secret', 'option' ),
			'auth_url'     => 'https://accounts.google.com/o/oauth2/v2/auth',
			'token_url'    => 'https://oauth2.googleapis.com/token',
			'profile_url'  => 'https://www.googleapis.com/oauth2/v3/userinfo',
			'scope'        => 'openid email profile',
		],
		'facebook' => [
			'enabled'      => get_field( 'ats_social_facebook_enabled', 'option' ),
			'client_id'    => get_field( 'ats_social_facebook_app_id', 'option' ),
			'client_secret'=> get_field( 'ats_social_facebook_app_secret', 'option' ),
			'auth_url'     => 'https://www.facebook.com/v19.0/dialog/oauth',
			'token_url'    => 'https://graph.facebook.com/v19.0/oauth/access_token',
			'profile_url'  => 'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name',
			'scope'        => 'email,public_profile',
		],
		'apple'    => [
			'enabled'      => get_field( 'ats_social_apple_enabled', 'option' ),
			'client_id'    => get_field( 'ats_social_apple_service_id', 'option' ),
			'team_id'      => get_field( 'ats_social_apple_team_id', 'option' ),
			'key_id'       => get_field( 'ats_social_apple_key_id', 'option' ),
			'private_key'  => get_field( 'ats_social_apple_private_key', 'option' ),
			'auth_url'     => 'https://appleid.apple.com/auth/authorize',
			'token_url'    => 'https://appleid.apple.com/auth/token',
			'scope'        => 'name email',
		],
	];

	if ( ! isset( $configs[ $provider ] ) ) {
		return false;
	}

	$config = $configs[ $provider ];

	if ( empty( $config['enabled'] ) || empty( $config['client_id'] ) ) {
		return false;
	}

	$config['redirect_uri'] = home_url( '/ats-auth/' . $provider . '/callback' );

	return $config;
}

// ─── OAuth Flow ──────────────────────────────────────────────────────────────

/**
 * Redirect user to provider's OAuth consent screen.
 *
 * @param string $provider Provider key.
 */
function ats_social_login_redirect( $provider ) {
	$config = ats_social_get_provider_config( $provider );

	if ( ! $config ) {
		ats_social_login_error( 'This login provider is not configured.' );
		return;
	}

	// Generate state nonce for CSRF protection.
	$state = wp_generate_password( 32, false );
	set_transient( 'ats_social_state_' . $state, $provider, 10 * MINUTE_IN_SECONDS );

	$params = [
		'client_id'     => $config['client_id'],
		'redirect_uri'  => $config['redirect_uri'],
		'scope'         => $config['scope'],
		'state'         => $state,
		'response_type' => 'code',
	];

	// Apple requires response_mode=form_post and needs the name scope.
	if ( 'apple' === $provider ) {
		$params['response_mode'] = 'form_post';
	}

	$auth_url = $config['auth_url'] . '?' . http_build_query( $params );

	wp_redirect( $auth_url );
	exit;
}

/**
 * Handle the OAuth callback from the provider.
 *
 * @param string $provider Provider key.
 */
function ats_social_login_callback( $provider ) {
	$config = ats_social_get_provider_config( $provider );

	if ( ! $config ) {
		ats_social_login_error( 'This login provider is not configured.' );
		return;
	}

	// Apple uses POST for callback (response_mode=form_post).
	$code  = isset( $_REQUEST['code'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['code'] ) ) : '';
	$state = isset( $_REQUEST['state'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['state'] ) ) : '';
	$error = isset( $_REQUEST['error'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['error'] ) ) : '';

	if ( $error ) {
		$error_desc = isset( $_REQUEST['error_description'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['error_description'] ) ) : 'Authentication was cancelled or failed.';
		ats_social_login_error( $error_desc );
		return;
	}

	if ( ! $code || ! $state ) {
		ats_social_login_error( 'Invalid authentication response.' );
		return;
	}

	// Verify CSRF state.
	$stored_provider = get_transient( 'ats_social_state_' . $state );
	delete_transient( 'ats_social_state_' . $state );

	if ( $stored_provider !== $provider ) {
		ats_social_login_error( 'Security verification failed. Please try again.' );
		return;
	}

	// Exchange code for access token.
	$token_data = ats_social_exchange_token( $provider, $config, $code );

	if ( is_wp_error( $token_data ) ) {
		ats_social_login_error( $token_data->get_error_message() );
		return;
	}

	// Get user profile from provider.
	$profile = ats_social_get_user_profile( $provider, $config, $token_data );

	if ( is_wp_error( $profile ) ) {
		ats_social_login_error( $profile->get_error_message() );
		return;
	}

	// Find or create WordPress user.
	$user_id = ats_social_find_or_create_user( $profile, $provider );

	if ( is_wp_error( $user_id ) ) {
		ats_social_login_error( $user_id->get_error_message() );
		return;
	}

	// Log the user in.
	wp_set_auth_cookie( $user_id, true );
	do_action( 'wp_login', get_userdata( $user_id )->user_login, get_userdata( $user_id ) );

	wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
	exit;
}

// ─── Token Exchange ──────────────────────────────────────────────────────────

/**
 * Exchange authorization code for access token.
 *
 * @param string $provider Provider key.
 * @param array  $config   Provider config.
 * @param string $code     Authorization code.
 * @return array|WP_Error  Token data or error.
 */
function ats_social_exchange_token( $provider, $config, $code ) {
	$body = [
		'code'         => $code,
		'redirect_uri' => $config['redirect_uri'],
		'grant_type'   => 'authorization_code',
	];

	if ( 'apple' === $provider ) {
		$body['client_id']     = $config['client_id'];
		$body['client_secret'] = ats_social_generate_apple_secret( $config );
	} else {
		$body['client_id']     = $config['client_id'];
		$body['client_secret'] = $config['client_secret'];
	}

	$response = wp_remote_post( $config['token_url'], [
		'body'    => $body,
		'timeout' => 30,
	] );

	if ( is_wp_error( $response ) ) {
		error_log( 'ATS Social Login: Token exchange failed for ' . $provider . ' - ' . $response->get_error_message() );
		return new WP_Error( 'token_exchange_failed', 'Could not connect to ' . ucfirst( $provider ) . '. Please try again.' );
	}

	$status = wp_remote_retrieve_response_code( $response );
	$data   = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $status !== 200 || empty( $data ) ) {
		$error_msg = isset( $data['error_description'] ) ? $data['error_description'] : ( isset( $data['error'] ) ? $data['error'] : 'Unknown error' );
		error_log( 'ATS Social Login: Token exchange error for ' . $provider . ' - ' . $error_msg );
		return new WP_Error( 'token_exchange_error', 'Authentication failed. Please try again.' );
	}

	return $data;
}

// ─── User Profile Retrieval ──────────────────────────────────────────────────

/**
 * Fetch user profile from provider API.
 *
 * @param string $provider   Provider key.
 * @param array  $config     Provider config.
 * @param array  $token_data Token response data.
 * @return array|WP_Error    Profile array [email, first_name, last_name] or error.
 */
function ats_social_get_user_profile( $provider, $config, $token_data ) {
	if ( 'apple' === $provider ) {
		return ats_social_get_apple_profile( $token_data );
	}

	$access_token = isset( $token_data['access_token'] ) ? $token_data['access_token'] : '';

	if ( ! $access_token ) {
		return new WP_Error( 'no_access_token', 'No access token received.' );
	}

	$profile_url = $config['profile_url'];

	// For Facebook, append access_token to URL.
	if ( 'facebook' === $provider ) {
		$profile_url .= '&access_token=' . urlencode( $access_token );
	}

	$response = wp_remote_get( $profile_url, [
		'headers' => [
			'Authorization' => 'Bearer ' . $access_token,
		],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		error_log( 'ATS Social Login: Profile fetch failed for ' . $provider . ' - ' . $response->get_error_message() );
		return new WP_Error( 'profile_fetch_failed', 'Could not retrieve your profile. Please try again.' );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $data['email'] ) ) {
		error_log( 'ATS Social Login: No email in profile for ' . $provider . ' - ' . wp_json_encode( $data ) );
		return new WP_Error( 'no_email', 'Your ' . ucfirst( $provider ) . ' account does not have an email address, or email permission was not granted.' );
	}

	if ( 'google' === $provider ) {
		return [
			'email'      => sanitize_email( $data['email'] ),
			'first_name' => isset( $data['given_name'] ) ? sanitize_text_field( $data['given_name'] ) : '',
			'last_name'  => isset( $data['family_name'] ) ? sanitize_text_field( $data['family_name'] ) : '',
		];
	}

	if ( 'facebook' === $provider ) {
		return [
			'email'      => sanitize_email( $data['email'] ),
			'first_name' => isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '',
			'last_name'  => isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '',
		];
	}

	return new WP_Error( 'unsupported_provider', 'Unsupported provider.' );
}

/**
 * Extract profile from Apple's id_token JWT.
 *
 * @param array $token_data Token response from Apple.
 * @return array|WP_Error   Profile array or error.
 */
function ats_social_get_apple_profile( $token_data ) {
	$id_token = isset( $token_data['id_token'] ) ? $token_data['id_token'] : '';

	if ( ! $id_token ) {
		return new WP_Error( 'no_id_token', 'No identity token received from Apple.' );
	}

	// Decode the JWT payload (middle segment).
	$parts = explode( '.', $id_token );
	if ( count( $parts ) < 3 ) {
		return new WP_Error( 'invalid_jwt', 'Invalid token received from Apple.' );
	}

	$payload = json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) ), true );

	if ( ! $payload || empty( $payload['email'] ) ) {
		return new WP_Error( 'no_email', 'Could not retrieve email from Apple. Please ensure you have shared your email.' );
	}

	// Apple sends user name only on first authorization (via POST body).
	$first_name = '';
	$last_name  = '';

	if ( ! empty( $_POST['user'] ) ) {
		$user_data = json_decode( wp_unslash( $_POST['user'] ), true );
		if ( isset( $user_data['name'] ) ) {
			$first_name = isset( $user_data['name']['firstName'] ) ? sanitize_text_field( $user_data['name']['firstName'] ) : '';
			$last_name  = isset( $user_data['name']['lastName'] ) ? sanitize_text_field( $user_data['name']['lastName'] ) : '';
		}
	}

	return [
		'email'      => sanitize_email( $payload['email'] ),
		'first_name' => $first_name,
		'last_name'  => $last_name,
	];
}

// ─── User Creation / Lookup ──────────────────────────────────────────────────

/**
 * Find existing user by email or create a new one.
 *
 * @param array  $profile  User profile [email, first_name, last_name].
 * @param string $provider Provider key.
 * @return int|WP_Error    User ID or error.
 */
function ats_social_find_or_create_user( $profile, $provider ) {
	$email = $profile['email'];

	// Check for existing user by email.
	$existing_user = get_user_by( 'email', $email );

	if ( $existing_user ) {
		// Update social provider meta.
		update_user_meta( $existing_user->ID, '_ats_social_provider', $provider );
		return $existing_user->ID;
	}

	// Create a new user.
	$username = sanitize_user( current( explode( '@', $email ) ), true );

	// Ensure unique username.
	if ( username_exists( $username ) ) {
		$username .= '_' . wp_generate_password( 4, false, false );
	}

	$user_id = wp_insert_user( [
		'user_login' => $username,
		'user_email' => $email,
		'user_pass'  => wp_generate_password( 24, true, true ),
		'first_name' => $profile['first_name'],
		'last_name'  => $profile['last_name'],
		'role'       => 'customer',
	] );

	if ( is_wp_error( $user_id ) ) {
		error_log( 'ATS Social Login: User creation failed - ' . $user_id->get_error_message() );
		return new WP_Error( 'user_creation_failed', 'Could not create your account. Please try registering manually.' );
	}

	// Store provider meta.
	update_user_meta( $user_id, '_ats_social_provider', $provider );

	// Trigger WooCommerce new customer action.
	do_action( 'woocommerce_created_customer', $user_id, [], false );

	return $user_id;
}

// ─── Apple JWT Client Secret ─────────────────────────────────────────────────

/**
 * Generate a JWT client_secret for Apple Sign In.
 *
 * Apple requires a signed JWT as the client_secret for token exchange.
 * Uses PHP openssl functions — no external library needed.
 *
 * @param array $config Apple provider config.
 * @return string       Signed JWT client secret.
 */
function ats_social_generate_apple_secret( $config ) {
	$header = [
		'alg' => 'ES256',
		'kid' => $config['key_id'],
	];

	$now = time();

	$claims = [
		'iss' => $config['team_id'],
		'iat' => $now,
		'exp' => $now + ( 5 * MINUTE_IN_SECONDS ),
		'aud' => 'https://appleid.apple.com',
		'sub' => $config['client_id'],
	];

	$header_encoded  = ats_social_base64url_encode( wp_json_encode( $header ) );
	$claims_encoded  = ats_social_base64url_encode( wp_json_encode( $claims ) );
	$signing_input   = $header_encoded . '.' . $claims_encoded;

	// Sign with the private key.
	$private_key = openssl_pkey_get_private( $config['private_key'] );

	if ( ! $private_key ) {
		error_log( 'ATS Social Login: Invalid Apple private key.' );
		return '';
	}

	$signature = '';
	openssl_sign( $signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256 );

	// Convert DER signature to raw R+S format for ES256.
	$signature = ats_social_der_to_raw( $signature );

	return $signing_input . '.' . ats_social_base64url_encode( $signature );
}

/**
 * Base64url encode (RFC 4648).
 *
 * @param string $data Data to encode.
 * @return string      Base64url encoded string.
 */
function ats_social_base64url_encode( $data ) {
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

/**
 * Convert DER-encoded ECDSA signature to raw R+S format.
 *
 * OpenSSL produces DER-encoded signatures, but JWT ES256 needs raw 64-byte R||S.
 *
 * @param string $der DER-encoded signature.
 * @return string     64-byte raw signature (R || S).
 */
function ats_social_der_to_raw( $der ) {
	$pos = 2;

	// Read R.
	$r_len = ord( $der[ $pos + 1 ] );
	$r     = substr( $der, $pos + 2, $r_len );
	$pos   = $pos + 2 + $r_len;

	// Read S.
	$s_len = ord( $der[ $pos + 1 ] );
	$s     = substr( $der, $pos + 2, $s_len );

	// Pad/trim to 32 bytes each.
	$r = str_pad( ltrim( $r, "\x00" ), 32, "\x00", STR_PAD_LEFT );
	$s = str_pad( ltrim( $s, "\x00" ), 32, "\x00", STR_PAD_LEFT );

	return $r . $s;
}

// ─── Error Handling ──────────────────────────────────────────────────────────

/**
 * Display error via WooCommerce notice and redirect to login page.
 *
 * @param string $message Error message to display.
 */
function ats_social_login_error( $message ) {
	wc_add_notice( $message, 'error' );

	wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
	exit;
}
