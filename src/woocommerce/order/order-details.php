<?php
/**
 * Order details
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id ); // phpcs:ignore

if ( ! $order ) {
	return;
}

$order_items           = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note    = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
$downloads             = $order->get_downloadable_items();
$show_downloads        = $order->has_downloadable_item() && $order->is_download_permitted();

if ( $show_downloads ) {
	wc_get_template(
		'order/order-downloads.php',
		array(
			'downloads'  => $downloads,
			'show_title' => true,
		)
	);
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Order Details (Left Column) -->
    <div class="lg:col-span-2 space-y-8">

        <!-- Order Items Table -->
        <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider"><?php esc_html_e( 'Order Details', 'woocommerce' ); ?></h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <th class="px-6 py-3"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
                            <th class="px-6 py-3 text-right"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                        do_action( 'woocommerce_order_details_before_order_table_items', $order );

                        foreach ( $order_items as $item_id => $item ) {
                            $product = $item->get_product();

                            wc_get_template(
                                'order/order-details-item.php',
                                array(
                                    'order'              => $order,
                                    'item_id'            => $item_id,
                                    'item'               => $item,
                                    'show_purchase_note' => $show_purchase_note,
                                    'purchase_note'      => $product ? $product->get_purchase_note() : '',
                                    'product'            => $product,
                                )
                            );
                        }

                        do_action( 'woocommerce_order_details_after_order_table_items', $order );
                        ?>
                    </tbody>
                    <tfoot class="bg-gray-50 font-medium text-gray-900 border-t border-gray-100">
                        <?php
                        foreach ( $order->get_order_item_totals() as $key => $total ) {
                            ?>
                            <tr class="<?php echo 'order_total' === $key ? 'bg-gray-100 font-bold border-t border-gray-200' : ''; ?>">
                                <th scope="row" class="px-6 py-3 text-sm <?php echo 'order_total' === $key ? 'text-gray-900' : 'text-gray-500'; ?> text-right"><?php echo esc_html( $total['label'] ); ?></th>
                                <td class="px-6 py-3 text-sm text-right <?php echo 'order_total' === $key ? 'text-xl' : ''; ?>"><?php echo ( 'payment_method' === $key ) ? esc_html( $total['value'] ) : wp_kses_post( $total['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        <?php if ( $order->get_customer_note() ) : ?>
                            <tr>
                                <th class="px-6 py-3 text-sm text-gray-500 text-right"><?php esc_html_e( 'Note:', 'woocommerce' ); ?></th>
                                <td class="px-6 py-3 text-sm text-gray-900 text-right italic"><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

    <!-- Customer Details (Right Column) -->
    <div class="lg:col-span-1 space-y-6">

        <?php if ( $show_customer_details ) : ?>

            <!-- Billing Details -->
            <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider"><?php esc_html_e( 'Billing Address', 'woocommerce' ); ?></h3>
                </div>
                <div class="p-6 text-sm text-gray-600 leading-relaxed">
                    <address class="not-italic">
                        <?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>
                        <?php if ( $order->get_billing_phone() ) : ?>
                            <p class="mt-3 flex items-center gap-2 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <?php echo esc_html( $order->get_billing_phone() ); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ( $order->get_billing_email() ) : ?>
                            <p class="mt-1 flex items-center gap-2 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <?php echo esc_html( $order->get_billing_email() ); ?>
                            </p>
                        <?php endif; ?>
                    </address>
                </div>
            </div>

            <!-- Shipping Details -->
            <?php if ( ! $order->get_formatted_shipping_address() ) : ?>
                <!-- No shipping address needed -->
            <?php else : ?>
                <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider"><?php esc_html_e( 'Shipping Address', 'woocommerce' ); ?></h3>
                    </div>
                    <div class="p-6 text-sm text-gray-600 leading-relaxed">
                        <address class="not-italic">
                            <?php echo wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>
                            <?php if ( $order->get_shipping_phone() ) : ?>
                                <p class="mt-3 flex items-center gap-2 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <?php echo esc_html( $order->get_shipping_phone() ); ?>
                                </p>
                            <?php endif; ?>
                        </address>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>
