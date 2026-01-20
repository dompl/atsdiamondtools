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
        'redirect' => '', // Optional redirect URL after login/logout
        'mobile'   => false // Optional redirect URL after login/logout
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
			<a href="<?php echo esc_url( $account_url ); ?>" class="rfs-ref-my-account-link inline-flex items-center gap-2 text-[13px] font-medium text-ats-text transition-colors" aria-label="My Account">
				<span>My Account</span>
			</a>
			<a
				href="<?php echo esc_url( $logout_url ); ?>"
				class="rfs-ref-logout-link inline-flex items-center gap-2 text-sm font-medium text-ats-text transition-colors"
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
		<div class="rfs-ref-email-login-container inline-flex items-center space-x-2 text-ats-text">
			<a href="<?php echo esc_url( $login_url ); ?>" class="rfs-ref-login-register-link inline-flex items-center gap-2 text-sm font-medium text-ats-text hover:text-ats-yellow transition-colors" aria-label="Login or Register">
				<?php if ( $atts['mobile'] ): ?>
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M240.92-268.31q51-37.84 111.12-59.77Q412.15-350 480-350t127.96 21.92q60.12 21.93 111.12 59.77 37.3-41 59.11-94.92Q800-417.15 800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 62.85 21.81 116.77 21.81 53.92 59.11 94.92ZM480.01-450q-54.78 0-92.39-37.6Q350-525.21 350-579.99t37.6-92.39Q425.21-710 479.99-710t92.39 37.6Q610-634.79 610-580.01t-37.6 92.39Q534.79-450 480.01-450ZM480-100q-79.15 0-148.5-29.77t-120.65-81.08q-51.31-51.3-81.08-120.65Q100-400.85 100-480t29.77-148.5q29.77-69.35 81.08-120.65 51.3-51.31 120.65-81.08Q400.85-860 480-860t148.5 29.77q69.35 29.77 120.65 81.08 51.31 51.3 81.08 120.65Q860-559.15 860-480t-29.77 148.5q-29.77 69.35-81.08 120.65-51.3 51.31-120.65 81.08Q559.15-100 480-100Zm0-60q54.15 0 104.42-17.42 50.27-17.43 89.27-48.73-39-30.16-88.11-47Q536.46-290 480-290t-105.77 16.65q-49.31 16.66-87.92 47.2 39 31.3 89.27 48.73Q425.85-160 480-160Zm0-350q29.85 0 49.92-20.08Q550-550.15 550-580t-20.08-49.92Q509.85-650 480-650t-49.92 20.08Q410-609.85 410-580t20.08 49.92Q450.15-510 480-510Zm0-70Zm0 355Z"/></svg>
					<span class="text-white font-bold text-md">Login / Register</span>
					<?php else: ?>
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"><path d="M240.92-268.31q51-37.84 111.12-59.77Q412.15-350 480-350t127.96 21.92q60.12 21.93 111.12 59.77 37.3-41 59.11-94.92Q800-417.15 800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 62.85 21.81 116.77 21.81 53.92 59.11 94.92ZM480.01-450q-54.78 0-92.39-37.6Q350-525.21 350-579.99t37.6-92.39Q425.21-710 479.99-710t92.39 37.6Q610-634.79 610-580.01t-37.6 92.39Q534.79-450 480.01-450ZM480-100q-79.15 0-148.5-29.77t-120.65-81.08q-51.31-51.3-81.08-120.65Q100-400.85 100-480t29.77-148.5q29.77-69.35 81.08-120.65 51.3-51.31 120.65-81.08Q400.85-860 480-860t148.5 29.77q69.35 29.77 120.65 81.08 51.31 51.3 81.08 120.65Q860-559.15 860-480t-29.77 148.5q-29.77 69.35-81.08 120.65-51.3 51.31-120.65 81.08Q559.15-100 480-100Zm0-60q54.15 0 104.42-17.42 50.27-17.43 89.27-48.73-39-30.16-88.11-47Q536.46-290 480-290t-105.77 16.65q-49.31 16.66-87.92 47.2 39 31.3 89.27 48.73Q425.85-160 480-160Zm0-350q29.85 0 49.92-20.08Q550-550.15 550-580t-20.08-49.92Q509.85-650 480-650t-49.92 20.08Q410-609.85 410-580t20.08 49.92Q450.15-510 480-510Zm0-70Zm0 355Z"/></svg>
						<span class="text-[13px] font-bold hidden md:inline">Login / Register</span>
				<?php endif; ?>
			</a>
		</div>
		<?php
}

    return ob_get_clean();
}
add_shortcode( 'ats_email_login', 'ats_email_login_shortcode' );
