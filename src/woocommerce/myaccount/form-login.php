<?php
/**
 * Modern WooCommerce Login/Register Form Template
 *
 * Matches the Modern ATS Diamond Tools Design
 * Location: your-theme/woocommerce/myaccount/form-login.php
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' ); ?>
<div class="w-full max-w-6xl mx-auto py-10 px-4">

    <!-- Card Container -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
<div class="rfs-ref-customer-login-wrapper grid grid-cols-1 lg:grid-cols-2 min-h-[600px]" id="customer_login">

    <!-- Login Section -->
    <div class="rfs-ref-login-section p-8 md:p-12 lg:p-16 flex flex-col lg:border-r border-gray-100">

        <div class="rfs-ref-login-header mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-2"><?php esc_html_e( 'Welcome Back', 'woocommerce' ); ?></h2>
            <p class="text-gray-500"><?php esc_html_e( 'Sign in to access your account details and orders.', 'woocommerce' ); ?></p>
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
                <input class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-300 focus:bg-white focus:border-[#FFD200] focus:ring-2 focus:ring-[#FFD200]/20 outline-none transition-all duration-200" type="password" name="password" id="password" autocomplete="current-password" placeholder="••••••••" />
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

            <?php do_action( 'woocommerce_login_form_end' ); ?>

        </form>

    </div>

    <!-- Register Section -->
    <div class="rfs-ref-register-section p-8 md:p-12 lg:p-16 flex flex-col bg-gray-50/50">

        <div class="rfs-ref-register-header mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-2"><?php esc_html_e( 'New Customer?', 'woocommerce' ); ?></h2>
            <p class="text-gray-500"><?php esc_html_e( 'Create an account for faster checkout and exclusive offers.', 'woocommerce' ); ?></p>
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

            <div class="rfs-ref-privacy-notice text-xs text-gray-500 leading-relaxed mt-auto p-4 p-t-0 bg-[#FFD200]/10 rounded-md border border-[#FFD200]/20">
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

            <?php do_action( 'woocommerce_register_form_end' ); ?>

        </form>

    </div>

</div>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
