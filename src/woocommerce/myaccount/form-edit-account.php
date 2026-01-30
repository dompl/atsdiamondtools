<?php
/**
 * Form Edit Account
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.7.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_before_edit_account_form' );
?>

<div class="rfs-ref-edit-account-wrapper">

    <h2 class="text-2xl font-bold text-gray-900 mb-6"><?php esc_html_e( 'Edit Profile', 'woocommerce' ); ?></h2>

    <form class="space-y-8 max-w-2xl" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?>>

        <?php do_action( 'woocommerce_edit_account_form_start' ); ?>

        <!-- Avatar Upload Section -->
        <div class="flex items-center gap-6 pb-6 border-b border-gray-100">
            <div class="relative group">
                <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-100 bg-gray-50">
                    <?php echo get_avatar( get_current_user_id(), 96, '', '', [ 'class' => 'w-full h-full object-cover' ] ); ?>
                </div>
                <button
                    type="button"
                    class="absolute bottom-0 right-0 bg-ats-yellow text-black p-2 rounded-full shadow-sm hover:bg-[#e6bd00] transition-colors"
                    onclick="document.getElementById('avatar-upload').click()"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </button>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider mb-1"><?php esc_html_e( 'Profile Photo', 'woocommerce' ); ?></h3>
                <p class="text-xs text-gray-500 mb-3"><?php esc_html_e( 'Allowed formats: JPG, PNG. Max size: 2MB.', 'woocommerce' ); ?></p>
                <div class="flex gap-2">
                    <button type="button" class="text-xs font-bold text-ats-dark hover:text-ats-yellow transition-colors" onclick="document.getElementById('avatar-upload').click()">
                        <?php esc_html_e( 'Change Photo', 'woocommerce' ); ?>
                    </button>
                </div>
                <input type="file" id="avatar-upload" class="hidden" accept="image/*" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label for="account_first_name" class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <?php esc_html_e( 'First Name', 'woocommerce' ); ?> <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="account_first_name"
                    id="account_first_name"
                    value="<?php echo esc_attr( $user->first_name ); ?>"
                    class="w-full h-12 px-4 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors"
                    required
                />
            </div>

            <div class="space-y-2">
                <label for="account_last_name" class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <?php esc_html_e( 'Last Name', 'woocommerce' ); ?> <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="account_last_name"
                    id="account_last_name"
                    value="<?php echo esc_attr( $user->last_name ); ?>"
                    class="w-full h-12 px-4 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors"
                    required
                />
            </div>
        </div>

        <div class="space-y-2">
            <label for="account_display_name" class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center">
                <?php esc_html_e( 'Display Name', 'woocommerce' ); ?> <span class="text-red-500 mr-1">*</span>
            </label>
            <input
                type="text"
                name="account_display_name"
                id="account_display_name"
                value="<?php echo esc_attr( $user->display_name ); ?>"
                class="w-full h-12 px-4 bg-white border border-gray-200 rounded-md focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-all"
                required
            />
        </div>

        <div class="space-y-2">
            <label for="account_email" class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                <?php esc_html_e( 'Email Address', 'woocommerce' ); ?> <span class="text-red-500">*</span>
            </label>
            <input
                type="email"
                name="account_email"
                id="account_email"
                value="<?php echo esc_attr( $user->user_email ); ?>"
                class="w-full h-12 px-4 bg-white border border-gray-200 rounded-md focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-all"
                required
            />
        </div>

        <?php do_action( 'woocommerce_edit_account_form' ); ?>

        <div class="pt-4">
            <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
            <input type="hidden" name="action" value="save_account_details" />
            <div id="woocommerce-account-data" data-nonce="<?php echo wp_create_nonce( 'wc_account_nonce' ); ?>"></div>
            <button
                type="submit"
                name="save_account_details"
                value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"
                class="bg-ats-yellow hover:bg-[#e6bd00] text-black font-bold uppercase text-sm tracking-widest px-8 py-4 rounded-sm shadow-sm shadow-ats-yellow/20 hover:shadow-md transition-colors duration-200"
            >
                <?php esc_html_e( 'Save Changes', 'woocommerce' ); ?>
            </button>
        </div>

        <?php do_action( 'woocommerce_edit_account_form_end' ); ?>

    </form>

</div>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
