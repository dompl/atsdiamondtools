<?php
/**
 * Change Password Form
 *
 * This is a standalone template for changing password.
 * Note: This requires a custom endpoint to be registered.
 *
 * @package skylinewp-dev-child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_before_change_password_form' );
?>

<div class="rfs-ref-change-password-wrapper">

    <h2 class="text-2xl font-bold text-gray-900 mb-6"><?php esc_html_e( 'Change Password', 'woocommerce' ); ?></h2>

    <form class="space-y-6 max-w-2xl" action="" method="post">

        <?php do_action( 'woocommerce_change_password_form_start' ); ?>

        <div class="space-y-2">
            <label for="password_current" class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                <?php esc_html_e( 'Current Password', 'woocommerce' ); ?>
            </label>
            <input
                type="password"
                name="password_current"
                id="password_current"
                autocomplete="off"
                class="w-full h-12 px-4 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors"
            />
        </div>

        <div class="space-y-2">
            <label for="password_1" class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center">
                <?php esc_html_e( 'New Password', 'woocommerce' ); ?>
            </label>
            <input
                type="password"
                name="password_1"
                id="password_1"
                autocomplete="off"
                class="w-full h-12 px-4 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors"
            />
        </div>

        <div class="space-y-2">
            <label for="password_2" class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                <?php esc_html_e( 'Re-enter New Password', 'woocommerce' ); ?>
            </label>
            <input
                type="password"
                name="password_2"
                id="password_2"
                autocomplete="off"
                class="w-full h-12 px-4 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors"
            />
        </div>

        <?php do_action( 'woocommerce_change_password_form' ); ?>

        <div class="pt-4">
            <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
            <button
                type="submit"
                name="save_account_details"
                value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"
                class="bg-ats-yellow hover:bg-[#e6bd00] text-black font-bold uppercase text-sm tracking-widest px-8 py-4 rounded-sm shadow-sm shadow-ats-yellow/20 hover:shadow-md transition-colors duration-200"
            >
                <?php esc_html_e( 'Change Password', 'woocommerce' ); ?>
            </button>
        </div>

        <?php do_action( 'woocommerce_change_password_form_end' ); ?>

    </form>

</div>

<?php do_action( 'woocommerce_after_change_password_form' ); ?>
