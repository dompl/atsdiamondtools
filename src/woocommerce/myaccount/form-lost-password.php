<?php
/**
 * Modern WooCommerce Lost Password Form Template
 *
 * Matches the Modern ATS Diamond Tools Design
 * Location: your-theme/woocommerce/myaccount/form-lost-password.php
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' );
?>

<div class="rfs-ref-lost-password-wrapper w-full max-w-xl mx-auto py-10 px-4">

    <div class="rfs-ref-lost-password-card bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden p-8 md:p-12">

        <div class="rfs-ref-lost-password-header mb-8">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-2"><?php esc_html_e( 'Lost Password', 'woocommerce' ); ?></h2>
            <p class="text-sm text-gray-500 leading-relaxed"><?php esc_html_e( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'woocommerce' ); ?></p>
        </div>

        <form method="post" class="rfs-ref-lost-password-form space-y-6">

            <div class="rfs-ref-user-login-field space-y-2">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider" for="user_login"><?php esc_html_e( 'Username or email', 'woocommerce' ); ?></label>
                <input class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-300 focus:bg-white focus:border-[#FFD200] focus:ring-2 focus:ring-[#FFD200]/20 outline-none transition-all duration-200" type="text" name="user_login" id="user_login" autocomplete="username" placeholder="user@example.com" />
            </div>

            <?php do_action( 'woocommerce_lostpassword_form' ); ?>

            <div class="rfs-ref-submit-wrapper">
                <input type="hidden" name="wc_reset_password" value="true" />
                <button type="submit" class="rfs-ref-reset-password-button w-full bg-[#FFD200] hover:bg-[#e6bd00] text-black font-bold uppercase text-sm tracking-widest py-4 rounded-md shadow-lg shadow-[#FFD200]/20 hover:shadow-xl hover:shadow-[#FFD200]/30 transition-all duration-200" value="<?php esc_attr_e( 'Reset password', 'woocommerce' ); ?>"><?php esc_html_e( 'Reset password', 'woocommerce' ); ?></button>
            </div>

            <?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

        </form>

        <div class="rfs-ref-back-to-login mt-8 pt-6 border-t border-gray-100 text-center">
            <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-[#222222] transition-colors">
                &larr; <?php esc_html_e( 'Back to Login', 'woocommerce' ); ?>
            </a>
        </div>

    </div>
</div>

<?php do_action( 'woocommerce_after_lost_password_form' ); ?>
