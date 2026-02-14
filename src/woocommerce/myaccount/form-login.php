<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' );

$social_providers = [
	'google'   => [
		'enabled' => get_field( 'ats_social_google_enabled', 'option' ),
		'label'   => 'Google',
		'classes' => 'border border-gray-200 hover:bg-gray-50',
		'icon'    => '<svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>',
	],
	'facebook' => [
		'enabled' => get_field( 'ats_social_facebook_enabled', 'option' ),
		'label'   => 'Facebook',
		'classes' => 'bg-[#1877F2] text-white hover:bg-blue-700',
		'icon'    => '<svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
	],
	'apple'    => [
		'enabled' => get_field( 'ats_social_apple_enabled', 'option' ),
		'label'   => 'Apple',
		'classes' => 'bg-black text-white hover:bg-gray-800',
		'icon'    => '<svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.24.65-.62 1.28-1.03 1.91-.75 1.16-1.54 2.29-1.5 4.17zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>',
	],
];

$active_providers = array_filter( $social_providers, fn( $p ) => ! empty( $p['enabled'] ) );
$col_count        = count( $active_providers );
?>
<div class="w-full max-w-6xl mx-auto py-10 px-4">

    <!-- Card Container -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
<div class="rfs-ref-customer-login-wrapper grid grid-cols-1 lg:grid-cols-2 min-h-[600px]" id="customer_login">

    <!-- Login Section -->
    <div class="rfs-ref-login-section p-8 md:p-12 lg:p-16 flex flex-col lg:border-r border-gray-100">

        <div class="rfs-ref-login-header mb-8">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-2"><?php esc_html_e( 'Welcome Back', 'woocommerce' ); ?></h2>
            <p class="text-sm text-gray-500"><?php esc_html_e( 'Sign in to access your account details and orders.', 'woocommerce' ); ?></p>
        </div>

        <form class="rfs-ref-login-form flex flex-col flex-grow space-y-6" method="post">

            <?php do_action( 'woocommerce_login_form_start' ); ?>

            <div class="rfs-ref-login-username-field space-y-2">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider" for="username"><?php esc_html_e( 'Username or Email', 'woocommerce' ); ?>&nbsp;<span class="required text-red-500">*</span></label>
                <input type="text" class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-300 focus:bg-white focus:border-[#FFD200] focus:ring-2 focus:ring-[#FFD200]/20 outline-none transition-all duration-200" name="username" id="username" autocomplete="username" value="<?php echo( !empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" placeholder="user@example.com" /><?php // @codingStandardsIgnoreLine ?>
            </div>

            <div class="rfs-ref-login-password-field space-y-2">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider" for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required text-red-500">*</span></label>
                    <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="rfs-ref-forgot-password-link text-xs font-medium text-gray-400 hover:text-[#222222] transition-colors"><?php esc_html_e( 'Forgot?', 'woocommerce' ); ?></a>
                </div>
                <div class="relative">
                    <input class="w-full h-12 px-4 pr-12 bg-gray-50 border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-300 focus:bg-white focus:border-[#FFD200] focus:ring-2 focus:ring-[#FFD200]/20 outline-none transition-all duration-200" type="password" name="password" id="password" autocomplete="current-password" placeholder="••••••••" />
                    <button type="button" class="rfs-ref-password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Toggle password visibility">
                        <svg class="rfs-ref-eye-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <svg class="rfs-ref-eye-off-icon w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                    </button>
                </div>
            </div>

            <?php do_action( 'woocommerce_login_form' ); ?>

            <div class="rfs-ref-remember-me-wrapper flex items-center">
                <label class="inline flex items-center gap-3 cursor-pointer group">
                    <div class="rfs-ref-checkbox-wrapper relative flex items-center">
                        <input class="peer sr-only" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                        <div class="w-5 h-5 border-2 border-gray-300 rounded bg-white peer-checked:bg-[#FFD200] peer-checked:border-[#FFD200] transition-all"></div>
                        <svg class="absolute w-3 h-3 text-black hidden peer-checked:block left-1 top-1 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors"><?php esc_html_e( 'Keep me logged in', 'woocommerce' ); ?></span>
                </label>
            </div>

            <div class="rfs-ref-login-submit-wrapper mt-auto">
                <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                <button type="submit" class="rfs-ref-login-submit-button w-full bg-[#FFD200] hover:bg-[#e6bd00] text-black font-bold uppercase text-sm tracking-widest py-4 rounded-md shadow-lg shadow-[#FFD200]/20 hover:shadow-xl hover:shadow-[#FFD200]/30 transition-all duration-200" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
            </div>

            <?php if ( ! empty( $active_providers ) ) : ?>
            <div class="rfs-ref-social-login-divider relative py-2">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-100"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-2 bg-white text-xs text-gray-400 uppercase tracking-wider font-bold"><?php esc_html_e( 'Or continue with', 'woocommerce' ); ?></span>
                </div>
            </div>

            <div class="rfs-ref-social-login-buttons grid grid-cols-<?php echo esc_attr( $col_count ); ?> gap-3">
                <?php foreach ( $active_providers as $key => $provider ) : ?>
                    <a href="<?php echo esc_url( home_url( '/ats-auth/' . $key ) ); ?>"
                       class="rfs-ref-<?php echo esc_attr( $key ); ?>-login flex items-center justify-center h-10 rounded transition-colors <?php echo esc_attr( $provider['classes'] ); ?>"
                       aria-label="<?php echo esc_attr( 'Sign in with ' . $provider['label'] ); ?>">
                        <?php echo $provider['icon']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php do_action( 'woocommerce_login_form_end' ); ?>

        </form>

    </div>

    <!-- Register Section -->
    <div class="rfs-ref-register-section p-8 md:p-12 lg:p-16 flex flex-col bg-gray-50/50">

        <div class="rfs-ref-register-header mb-8">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-2"><?php esc_html_e( 'New Customer?', 'woocommerce' ); ?></h2>
            <p class="text-sm text-gray-500"><?php esc_html_e( 'Create an account for faster checkout and exclusive offers.', 'woocommerce' ); ?></p>
        </div>

        <form method="post" class="rfs-ref-register-form flex flex-col flex-grow space-y-6" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

            <?php do_action( 'woocommerce_register_form_start' ); ?>

            <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ): ?>
                <div class="rfs-ref-register-username-field space-y-2">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider" for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required text-red-500">*</span></label>
                    <input type="text" class="w-full h-12 px-4 bg-white border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-300 focus:border-[#FFD200] focus:ring-2 focus:ring-[#FFD200]/20 outline-none transition-all duration-200" name="username" id="reg_username" autocomplete="username" value="<?php echo( !empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
                </div>
            <?php endif; ?>

            <div class="rfs-ref-register-email-field space-y-2">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider" for="reg_email"><?php esc_html_e( 'Email Address', 'woocommerce' ); ?>&nbsp;<span class="required text-red-500">*</span></label>
                <input type="email" class="w-full h-12 px-4 bg-white border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-300 focus:border-[#FFD200] focus:ring-2 focus:ring-[#FFD200]/20 outline-none transition-all duration-200" name="email" id="reg_email" autocomplete="email" value="<?php echo( !empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" placeholder="you@company.com" /><?php // @codingStandardsIgnoreLine ?>
            </div>

            <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ): ?>
                <div class="rfs-ref-register-password-field space-y-2">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider" for="reg_password"><?php esc_html_e( 'Set Password', 'woocommerce' ); ?>&nbsp;<span class="required text-red-500">*</span></label>
                    <input type="password" class="w-full h-12 px-4 bg-white border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-300 focus:border-[#FFD200] focus:ring-2 focus:ring-[#FFD200]/20 outline-none transition-all duration-200" name="password" id="reg_password" autocomplete="new-password" placeholder="••••••••" />
                    <p class="rfs-ref-password-hint text-xs text-gray-400 text-right"><?php esc_html_e( 'At least 8 characters', 'woocommerce' ); ?></p>
                </div>
            <?php else: ?>
                <div class="rfs-ref-password-notice p-4 bg-[#FFD200]/10 rounded-md border border-[#FFD200]/20">
                    <p class="text-xs text-gray-600"><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></p>
                </div>
            <?php endif; ?>

            <?php do_action( 'woocommerce_register_form' ); ?>

            <div class="rfs-ref-privacy-notice text-xs text-gray-500 leading-relaxed mt-auto p-4 bg-[#FFD200]/10 rounded-md border border-[#FFD200]/20">
                <p class="woocommerce-privacy-policy-text">
                    <?php
$privacy_policy_url = get_privacy_policy_url();
if ( $privacy_policy_url ) {
    printf(
        esc_html__( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our %s.', 'woocommerce' ),
        '<a href="' . esc_url( $privacy_policy_url ) . '" class="underline hover:text-gray-900 transition-colors" target="_blank">' . esc_html__( 'privacy policy', 'woocommerce' ) . '</a>'
    );
} else {
    esc_html_e( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our privacy policy.', 'woocommerce' );
}
?>
                </p>
            </div>
            <div class="rfs-ref-register-submit-wrapper">
                <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
                <button type="submit" class="rfs-ref-register-submit-button w-full bg-gray-900 hover:bg-black text-white font-bold uppercase text-sm tracking-widest py-4 rounded-md shadow-lg hover:shadow-xl transition-all duration-200" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Create Account', 'woocommerce' ); ?></button>
            </div>

            <?php if ( ! empty( $active_providers ) ) : ?>
            <div class="rfs-ref-social-register-divider relative py-2">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-2 bg-gray-50 text-xs text-gray-400 uppercase tracking-wider font-bold"><?php esc_html_e( 'Or register with', 'woocommerce' ); ?></span>
                </div>
            </div>

            <div class="rfs-ref-social-register-buttons grid grid-cols-<?php echo esc_attr( $col_count ); ?> gap-3">
                <?php foreach ( $active_providers as $key => $provider ) : ?>
                    <a href="<?php echo esc_url( home_url( '/ats-auth/' . $key ) ); ?>"
                       class="rfs-ref-<?php echo esc_attr( $key ); ?>-register flex items-center justify-center h-10 rounded transition-colors <?php echo esc_attr( $provider['classes'] ); ?>"
                       aria-label="<?php echo esc_attr( 'Register with ' . $provider['label'] ); ?>">
                        <?php echo $provider['icon']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php do_action( 'woocommerce_register_form_end' ); ?>

        </form>

    </div>

</div>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
