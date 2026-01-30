<?php
/**
 * My Account page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="rfs-ref-my-account-wrapper w-full max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-ats-dark mb-6"><?php esc_html_e( 'My Account', 'woocommerce' ); ?></h1>

    <div class="flex flex-col lg:flex-row gap-6">

        <!-- Sidebar Navigation -->
        <?php
        /**
         * My Account navigation.
         *
         * @since 2.6.0
         */
        do_action( 'woocommerce_account_navigation' );
        ?>

        <!-- Main Content Area -->
        <div class="rfs-ref-account-content flex-grow">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-h-[500px] p-6 md:p-8">
                <?php
                    /**
                     * My Account content.
                     *
                     * @since 2.6.0
                     */
                    do_action( 'woocommerce_account_content' );
                ?>
            </div>
        </div>

    </div>
</div>
