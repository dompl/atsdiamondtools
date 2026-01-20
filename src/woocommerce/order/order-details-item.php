<?php
/**
 * Order Item Details
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
	return;
}
?>
<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">

	<td class="px-6 py-4 border-b border-gray-50">
		<div class="flex items-start gap-4">
			<?php
			$is_visible        = $product && $product->is_visible();
			$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $order );

			// Product Image
			$thumbnail = $product ? $product->get_image( array( 64, 64 ), array( 'class' => 'w-16 h-16 object-cover rounded-md bg-gray-50 border border-gray-100' ) ) : '';
			if ( $thumbnail ) {
				echo $is_visible ? '<a href="' . esc_url( $product_permalink ) . '">' . $thumbnail . '</a>' : $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<div class="flex-1">
				<h4 class="text-sm font-bold text-gray-900 mb-1">
					<?php
					echo apply_filters( 'woocommerce_order_item_name', $product_permalink ? sprintf( '<a href="%s" class="hover:text-ats-yellow transition-colors">%s</a>', $product_permalink, $item->get_name() ) : $item->get_name(), $item, $is_visible ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</h4>

				<?php
				$qty = $item->get_quantity();
				$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

				if ( $refunded_qty ) {
					$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
				} else {
					$qty_display = esc_html( $qty );
				}
				?>

				<p class="text-xs text-gray-500 mb-1">
					<?php echo esc_html__( 'Qty:', 'woocommerce' ); ?>
					<span class="font-medium text-gray-900"><?php echo wp_kses_post( $qty_display ); ?></span>
				</p>

				<?php
				do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );
				wc_display_item_meta( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
				?>
			</div>
		</div>
	</td>

	<td class="px-6 py-4 text-right border-b border-gray-50 text-sm font-medium text-gray-900 align-top">
		<?php echo $order->get_formatted_line_subtotal( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</td>

</tr>

<?php if ( $show_purchase_note && $purchase_note ) : ?>

<tr class="purchase-note">
	<td colspan="2" class="px-6 py-4 border-b border-gray-50 text-xs italic text-gray-600 bg-gray-50/50">
		<?php echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) ); ?>
	</td>
</tr>

<?php endif; ?>
