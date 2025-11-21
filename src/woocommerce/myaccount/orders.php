<?php
/**
 * Orders
 *
 * @package skylinewp-dev-child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$customer_orders = wc_get_orders( [
    'customer' => get_current_user_id(),
    'limit' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
] );

do_action( 'woocommerce_before_account_orders', $has_orders );
?>

<div class="rfs-ref-orders-wrapper">

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-900"><?php esc_html_e( 'Order History', 'woocommerce' ); ?></h2>
    </div>

    <!-- Filters Section -->
    <div class="rfs-ref-filters-section bg-gray-50 p-4 rounded-lg border border-gray-100 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'Status', 'woocommerce' ); ?></label>
                <select class="rfs-ref-status-filter w-full h-10 px-3 bg-white border border-gray-200 rounded text-sm focus:border-ats-yellow focus:ring-1 focus:ring-ats-yellow outline-none cursor-pointer">
                    <option value="all"><?php esc_html_e( 'All Statuses', 'woocommerce' ); ?></option>
                    <option value="pending"><?php esc_html_e( 'Pending', 'woocommerce' ); ?></option>
                    <option value="processing"><?php esc_html_e( 'Processing', 'woocommerce' ); ?></option>
                    <option value="on-hold"><?php esc_html_e( 'On hold', 'woocommerce' ); ?></option>
                    <option value="completed"><?php esc_html_e( 'Completed', 'woocommerce' ); ?></option>
                    <option value="cancelled"><?php esc_html_e( 'Cancelled', 'woocommerce' ); ?></option>
                    <option value="refunded"><?php esc_html_e( 'Refunded', 'woocommerce' ); ?></option>
                    <option value="failed"><?php esc_html_e( 'Failed', 'woocommerce' ); ?></option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'Start Date', 'woocommerce' ); ?></label>
                <input
                    type="date"
                    class="rfs-ref-start-date w-full h-10 px-3 bg-white border border-gray-200 rounded text-sm focus:border-ats-yellow focus:ring-1 focus:ring-ats-yellow outline-none text-gray-600 placeholder-gray-400"
                />
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'End Date', 'woocommerce' ); ?></label>
                <input
                    type="date"
                    class="rfs-ref-end-date w-full h-10 px-3 bg-white border border-gray-200 rounded text-sm focus:border-ats-yellow focus:ring-1 focus:ring-ats-yellow outline-none text-gray-600 placeholder-gray-400"
                />
            </div>

            <div>
                <button
                    type="button"
                    class="rfs-ref-clear-filters w-full h-10 px-3 rounded text-sm font-bold flex items-center justify-center gap-2 transition-colors bg-white border border-gray-200 text-gray-600 hover:bg-gray-100 hover:text-red-500"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <?php esc_html_e( 'Clear Filters', 'woocommerce' ); ?>
                </button>
            </div>
        </div>
    </div>

    <?php if ( ! empty( $customer_orders ) ) : ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-4"><?php esc_html_e( 'Order', 'woocommerce' ); ?></th>
                        <th class="py-4"><?php esc_html_e( 'Date', 'woocommerce' ); ?></th>
                        <th class="py-4"><?php esc_html_e( 'Status', 'woocommerce' ); ?></th>
                        <th class="py-4"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-600">
                    <?php foreach ( $customer_orders as $customer_order ) : ?>
                        <?php
                        $order = wc_get_order( $customer_order );
                        $item_count = $order->get_item_count();
                        $status = $order->get_status();

                        // Status text colors matching React
                        $status_class = 'text-gray-600';
                        if ( $status === 'completed' ) {
                            $status_class = 'text-green-600 font-medium';
                        } elseif ( $status === 'pending' || $status === 'on-hold' ) {
                            $status_class = 'text-yellow-600 font-medium';
                        } elseif ( $status === 'processing' ) {
                            $status_class = 'text-blue-600 font-medium';
                        }
                        ?>
                        <tr class="rfs-ref-order-row border-b border-gray-50 hover:bg-gray-50 transition-colors cursor-pointer" data-status="<?php echo esc_attr( $status ); ?>" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
                            <td class="py-4">
                                <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="font-bold text-ats-dark hover:text-ats-yellow">
                                    <?php echo esc_html( '#' . $order->get_order_number() ); ?>
                                </a>
                            </td>
                            <td class="py-4"><?php echo esc_html( $order->get_date_created()->date_i18n( 'd F, Y' ) ); ?></td>
                            <td class="py-4">
                                <span class="<?php echo esc_attr( $status_class ); ?>">
                                    <?php echo esc_html( wc_get_order_status_name( $status ) ); ?>
                                </span>
                            </td>
                            <td class="py-4">
                                <?php
                                /* translators: 1: total 2: item count */
                                printf(
                                    esc_html__( '%1$s for %2$s item(s)', 'woocommerce' ),
                                    wp_kses_post( $order->get_formatted_order_total() ),
                                    esc_html( $item_count )
                                );
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else : ?>

        <div class="rfs-ref-no-orders py-12 text-center text-gray-400">
            <div class="flex flex-col items-center justify-center gap-2">
                <svg class="w-6 h-6 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <p><?php esc_html_e( 'No orders found matching your filters.', 'woocommerce' ); ?></p>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
