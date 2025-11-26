<?php
/**
 * ATS Email/Login Shortcode
 *
 * Displays login/register link when logged out, or account/logout links when logged in.
 *
 * Usage: [ats_email_login]
 *
 * @package ATS Diamond Tools
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the ats_email_login shortcode
 *
 * @param array $atts Shortcode attributes (none currently used)
 * @return string HTML output
 */
function ats_email_login_shortcode( $atts ) {
    // Check if WooCommerce is active
    if ( !class_exists( 'WooCommerce' ) ) {
        return '<!-- ATS Email/Login: WooCommerce is required -->';
    }

    // Parse shortcode attributes (for future extensibility)
    $atts = shortcode_atts( [
        'redirect' => '' // Optional redirect URL after login/logout
    ], $atts, 'ats_email_login' );

    ob_start();

    if ( is_user_logged_in() ) {
        // User is logged in - show My Account and Logout links
        $current_user = wp_get_current_user();
        $account_url  = wc_get_page_permalink( 'myaccount' );
        $logout_url   = wp_logout_url( !empty( $atts['redirect'] ) ? $atts['redirect'] : home_url() );
        $display_name = $current_user->display_name;

        ?>
		<div class="rfs-ref-email-login-container flex items-center gap-4 text-ats-text">
			<a
				href="<?php echo esc_url( $account_url ); ?>"
				class="rfs-ref-my-account-link inline-flex items-center gap-2 text-sm font-medium text-ats-text hover:text-ats-yellow transition-colors"
				aria-label="My Account"
			>
				<span>My Account</span>
			</a>
			<a
				href="<?php echo esc_url( $logout_url ); ?>"
				class="rfs-ref-logout-link inline-flex items-center gap-2 text-sm font-medium text-ats-text hover:text-ats-yellow transition-colors"
				aria-label="Logout"
			>
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
				</svg>
				<span>Logout</span>
			</a>
		</div>
		<?php
} else {
        // User is logged out - show Login/Register link
        $login_url = wc_get_page_permalink( 'myaccount' );

        // If redirect parameter is set, add it to the login URL
        if ( !empty( $atts['redirect'] ) ) {
            $login_url = add_query_arg( 'redirect_to', urlencode( $atts['redirect'] ), $login_url );
        }

        ?>
		<div class="rfs-ref-email-login-container">
			<a
				href="<?php echo esc_url( $login_url ); ?>"
				class="rfs-ref-login-register-link inline-flex items-center gap-2 text-sm font-medium text-ats-text hover:text-ats-yellow transition-colors"
				aria-label="Login or Register"
			>
				<span class="text-[13px] font-bold">Login / Register</span>
			</a>
		</div>
		<?php
}

    return ob_get_clean();
}
add_shortcode( 'ats_email_login', 'ats_email_login_shortcode' );
