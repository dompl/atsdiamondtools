<?php
/**
 * View Order
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id ); // phpcs:ignore
if ( ! $order ) {
	return;
}
?>

<div class="rfs-ref-view-order-wrapper">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">
                <?php printf( esc_html__( 'Order #%s', 'woocommerce' ), $order->get_order_number() ); ?>
            </h2>
            <p class="text-sm text-gray-500">
                <?php printf( esc_html__( 'Placed on %s', 'woocommerce' ), wc_format_datetime( $order->get_date_created() ) ); ?>
                <span class="mx-2">&bull;</span>
                <span class="<?php echo 'completed' === $order->get_status() ? 'text-green-600 font-medium' : 'text-gray-900'; ?>">
                    <?php echo wc_get_order_status_name( $order->get_status() ); ?>
                </span>
            </p>
        </div>

        <a href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>" class="text-sm font-bold text-gray-500 hover:text-gray-900 flex items-center gap-2 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <?php esc_html_e( 'Back to Orders', 'woocommerce' ); ?>
        </a>
    </div>

    <!-- Order Details Section -->
    <div class="rfs-ref-order-details">
        <?php do_action( 'woocommerce_view_order', $order_id ); ?>
    </div>
</div>
