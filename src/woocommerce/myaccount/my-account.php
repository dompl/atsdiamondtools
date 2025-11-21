<?php
/**
 * My Account Page
 *
 * @package skylinewp-dev-child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="rfs-ref-my-account-wrapper w-full max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-ats-dark mb-6"><?php esc_html_e( 'My Account', 'woocommerce' ); ?></h1>

    <div class="flex flex-col lg:flex-row gap-6">

        <!-- Sidebar Navigation -->
        <?php wc_get_template( 'myaccount/navigation.php' ); ?>

        <!-- Main Content Area -->
        <div class="rfs-ref-account-content flex-grow">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-h-[500px] p-6 md:p-8">
                <?php
                    /**
                     * My Account content.
                     */
                    do_action( 'woocommerce_account_content' );
                ?>
            </div>
        </div>

    </div>
</div>
