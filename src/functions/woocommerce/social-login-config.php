<?php
/**
 * =============================================================================
 * Social Login Configuration for WooCommerce
 * =============================================================================
 *
 * This file contains configuration and setup for social login providers
 * (Google, Facebook, Apple) for the WooCommerce My Account pages.
 *
 * IMPORTANT: Store your API keys securely. Never commit real keys to version control!
 *
 * =============================================================================
 * SETUP INSTRUCTIONS
 * =============================================================================
 *
 * Recommended Plugin: Nextend Social Login
 * https://wordpress.org/plugins/nextend-social-login/
 *
 * Alternative: Super Socializer
 * https://wordpress.org/plugins/super-socializer/
 *
 * =============================================================================
 *
 * 1. GOOGLE OAUTH SETUP
 * ----------------------
 * - Go to: https://console.developers.google.com/
 * - Create a new project or select existing
 * - Enable "Google+ API"
 * - Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"
 * - Application type: Web application
 * - Authorized redirect URIs: Add your WordPress site URL + social login callback
 *   Example: https://yourdomain.com/wp-login.php?loginSocial=google
 * - Copy Client ID and Client Secret
 *
 * 2. FACEBOOK APP SETUP
 * ----------------------
 * - Go to: https://developers.facebook.com/apps/
 * - Create new app → Select "Consumer" type
 * - Add "Facebook Login" product
 * - Settings → Basic: Copy App ID and App Secret
 * - Facebook Login → Settings:
 *   Valid OAuth Redirect URIs: https://yourdomain.com/wp-login.php?loginSocial=facebook
 * - Make app live (switch from development mode)
 *
 * 3. APPLE SIGN IN SETUP
 * ----------------------
 * - Go to: https://developer.apple.com/account/resources/identifiers/list/serviceId
 * - Create new Service ID
 * - Enable "Sign in with Apple"
 * - Configure: Add your domain and return URLs
 * - Create Private Key for Sign in with Apple
 * - Download .p8 key file (keep this secure!)
 *
 * =============================================================================
 */

// Prevent direct access
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Social Login API Keys Configuration
 *
 * SECURITY WARNING: Use WordPress constants or environment variables
 * for production. This is a template file.
 */

// Option 1: Define in wp-config.php (Recommended for production)
// define( 'GOOGLE_CLIENT_ID', 'your-google-client-id' );
// define( 'GOOGLE_CLIENT_SECRET', 'your-google-client-secret' );
// define( 'FACEBOOK_APP_ID', 'your-facebook-app-id' );
// define( 'FACEBOOK_APP_SECRET', 'your-facebook-app-secret' );
// define( 'APPLE_SERVICE_ID', 'your-apple-service-id' );
// define( 'APPLE_TEAM_ID', 'your-apple-team-id' );
// define( 'APPLE_KEY_ID', 'your-apple-key-id' );
// define( 'APPLE_PRIVATE_KEY', 'path-to-private-key.p8' );

// Option 2: Store in this file (Development only - DO NOT commit real keys!)
$social_login_config = [
    'google' => [
        'enabled' => true,
        'client_id' => defined( 'GOOGLE_CLIENT_ID' ) ? GOOGLE_CLIENT_ID : 'YOUR_GOOGLE_CLIENT_ID_HERE',
        'client_secret' => defined( 'GOOGLE_CLIENT_SECRET' ) ? GOOGLE_CLIENT_SECRET : 'YOUR_GOOGLE_CLIENT_SECRET_HERE',
        'button_text' => 'Continue with Google',
    ],
    'facebook' => [
        'enabled' => true,
        'app_id' => defined( 'FACEBOOK_APP_ID' ) ? FACEBOOK_APP_ID : 'YOUR_FACEBOOK_APP_ID_HERE',
        'app_secret' => defined( 'FACEBOOK_APP_SECRET' ) ? FACEBOOK_APP_SECRET : 'YOUR_FACEBOOK_APP_SECRET_HERE',
        'button_text' => 'Continue with Facebook',
    ],
    'apple' => [
        'enabled' => true,
        'service_id' => defined( 'APPLE_SERVICE_ID' ) ? APPLE_SERVICE_ID : 'YOUR_APPLE_SERVICE_ID_HERE',
        'team_id' => defined( 'APPLE_TEAM_ID' ) ? APPLE_TEAM_ID : 'YOUR_APPLE_TEAM_ID_HERE',
        'key_id' => defined( 'APPLE_KEY_ID' ) ? APPLE_KEY_ID : 'YOUR_APPLE_KEY_ID_HERE',
        'private_key' => defined( 'APPLE_PRIVATE_KEY' ) ? APPLE_PRIVATE_KEY : '/path/to/AuthKey_XXXXX.p8',
        'button_text' => 'Continue with Apple',
    ],
];

/**
 * Hook into social login buttons
 * This function will be called by the form-login.php template
 */
add_action( 'woocommerce_login_form_social_buttons', function() use ( $social_login_config ) {

    // Check if Nextend Social Login plugin is active
    if ( class_exists( 'NextendSocialLogin' ) ) {
        // Plugin will handle the buttons automatically
        // Remove our static buttons via JS or CSS if needed
        echo '<style>.rfs-ref-google-login, .rfs-ref-facebook-login, .rfs-ref-apple-login { display: none; }</style>';
        return;
    }

    // If no plugin, buttons are already rendered in the template
    // JavaScript handlers are in woocommerce-account.js

    // Add placeholder message for developers
    if ( current_user_can( 'administrator' ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        echo '<div style="grid-column: span 3; text-align: center; font-size: 11px; color: #666; margin-top: 8px;">';
        echo 'ℹ️ Social login buttons are placeholders. Install <a href="https://wordpress.org/plugins/nextend-social-login/" target="_blank">Nextend Social Login</a> and configure API keys.';
        echo '</div>';
    }
});

/**
 * Custom Social Login Handler (if not using plugin)
 *
 * This is a basic example. For production, use a plugin like Nextend Social Login
 * which handles OAuth flow, security, and user creation properly.
 */
function ats_handle_social_login() {
    // This would handle the OAuth callback
    // For production, use Nextend Social Login or similar plugin

    // Example structure (DO NOT USE IN PRODUCTION):
    /*
    if ( isset( $_GET['social_login'] ) && isset( $_GET['code'] ) ) {
        $provider = sanitize_text_field( $_GET['social_login'] );
        $code = sanitize_text_field( $_GET['code'] );

        // Exchange code for access token
        // Get user info from provider
        // Create or login WordPress user
        // Redirect to my account page
    }
    */
}
// add_action( 'init', 'ats_handle_social_login' );

/**
 * Utility function to check if social login is configured
 */
function ats_is_social_login_configured() {
    return class_exists( 'NextendSocialLogin' ) || class_exists( 'Super_Socializer' );
}

/**
 * Get social login configuration
 *
 * @return array Configuration array
 */
function ats_get_social_login_config() {
    global $social_login_config;
    return $social_login_config;
}
