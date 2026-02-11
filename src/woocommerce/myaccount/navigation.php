<?php
/**
 * My Account navigation
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/navigation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get all WooCommerce account menu items (includes favorites and other endpoints)
$nav_items = wc_get_account_menu_items();

// Remove logout from nav_items as we handle it separately
unset( $nav_items['customer-logout'] );

$current_endpoint = WC()->query->get_current_endpoint();
if ( empty( $current_endpoint ) ) {
    $current_endpoint = 'dashboard';
}

do_action( 'woocommerce_before_account_navigation' );
?>

<div class="rfs-ref-account-sidebar w-full lg:w-56 flex-shrink-0">
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden sticky top-24">
        <div class="px-5 pt-5 pb-3">
            <h3 class="text-base font-bold text-gray-900"><?php esc_html_e( 'Navigation', 'woocommerce' ); ?></h3>
        </div>
        <nav class="flex flex-col pb-2">
            <?php foreach ( $nav_items as $endpoint => $label ) : ?>
                <?php
                $url = wc_get_endpoint_url( $endpoint );
                $is_active = ( $current_endpoint === $endpoint );
                ?>
                <a
                    href="<?php echo esc_url( $url ); ?>"
                    class="rfs-ref-nav-item text-left px-6 py-3 text-sm font-medium transition-all border-l-4 <?php echo $is_active ? 'border-ats-yellow text-gray-900 bg-ats-yellow/5' : 'border-transparent text-gray-500 hover:text-gray-900 hover:bg-gray-50'; ?>"
                >
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>

            <a
                href="<?php echo esc_url( wc_logout_url() ); ?>"
                class="rfs-ref-logout-btn text-left px-6 py-3 text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 border-l-4 border-transparent transition-all"
            >
                <?php esc_html_e( 'Logout', 'woocommerce' ); ?>
            </a>
        </nav>
    </div>
</div>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
