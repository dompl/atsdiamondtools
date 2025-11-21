<?php
/**
 * My Account Navigation
 *
 * @package skylinewp-dev-child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Map WooCommerce endpoints to custom labels
$nav_items = [
    'dashboard' => __( 'Dashboard', 'woocommerce' ),
    'edit-account' => __( 'Edit Profile', 'woocommerce' ),
    'orders' => __( 'Order History', 'woocommerce' ),
    'edit-address' => __( 'Addresses', 'woocommerce' ),
];

$current_endpoint = WC()->query->get_current_endpoint();
if ( empty( $current_endpoint ) ) {
    $current_endpoint = 'dashboard';
}

do_action( 'woocommerce_before_account_navigation' );
?>

<div class="rfs-ref-account-sidebar w-full lg:w-56 flex-shrink-0">
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden sticky top-24">
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
