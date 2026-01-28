<?php
/**
 * Review order table
 *
 * Styled with Tailwind CSS
 * IMPORTANT: Must use table structure for WooCommerce AJAX to work properly!
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table woocommerce-checkout-review-order-table bg-white border border-gray-200 rounded-lg">
<tbody>

	<!-- Products Header -->
	<tr class="rfs-ref-products-header">
		<td colspan="2" class="p-6 pb-0">
			<div class="space-y-4">
				<?php
				do_action( 'woocommerce_review_order_before_cart_contents' );

				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

					if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( array( 60, 60 ) ), $cart_item, $cart_item_key );
						?>
						<div class="rfs-ref-review-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?> flex gap-3">
							<div class="rfs-ref-review-item-thumbnail flex-shrink-0">
								<a href="<?php echo esc_url( $_product->get_permalink( $cart_item ) ); ?>" class="block w-16 h-16 rounded border border-gray-200 overflow-hidden">
									<?php echo $thumbnail; ?>
								</a>
							</div>
							<div class="rfs-ref-review-item-details flex-grow flex justify-between gap-4">
								<div class="rfs-ref-review-item-name">
									<a href="<?php echo esc_url( $_product->get_permalink( $cart_item ) ); ?>" class="text-sm font-medium text-ats-dark hover:text-ats-yellow transition-colors no-underline">
										<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
									</a>
									<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <span class="text-ats-text font-normal text-xs">&times;&nbsp;' . $cart_item['quantity'] . '</span>', $cart_item, $cart_item_key ); ?>
									<?php if ( wc_get_formatted_cart_item_data( $cart_item ) ) : ?>
										<div class="rfs-ref-review-item-meta text-xs text-ats-text mt-1">
											<?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
										</div>
									<?php endif; ?>
								</div>
								<div class="rfs-ref-review-item-total flex-shrink-0 text-sm font-semibold text-ats-dark">
									<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
								</div>
							</div>
						</div>
						<?php
					}
				}

				do_action( 'woocommerce_review_order_after_cart_contents' );
				?>
			</div>
			<div class="border-b border-gray-200 my-6"></div>
		</td>
	</tr>

	<!-- Subtotal -->
	<tr class="cart-subtotal">
		<th class="px-6 py-3 text-sm text-ats-text font-normal text-left"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
		<td class="px-6 py-3 text-sm font-medium text-ats-dark text-right"><?php wc_cart_totals_subtotal_html(); ?></td>
	</tr>

	<!-- Coupons -->
	<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
		<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
			<th class="px-6 py-3 text-sm text-ats-text font-normal text-left"><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
			<td class="px-6 py-3 text-sm font-medium text-green-600 text-right"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
		</tr>
	<?php endforeach; ?>

	<!-- Shipping -->
	<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
		<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
		<?php wc_cart_totals_shipping_html(); ?>
		<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
	<?php endif; ?>

	<!-- Fees -->
	<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
		<tr class="fee">
			<th class="px-6 py-3 text-sm text-ats-text font-normal text-left"><?php echo esc_html( $fee->name ); ?></th>
			<td class="px-6 py-3 text-sm font-medium text-ats-dark text-right"><?php wc_cart_totals_fee_html( $fee ); ?></td>
		</tr>
	<?php endforeach; ?>

	<!-- Tax -->
	<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
		<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
			<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
				<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<th class="px-6 py-3 text-sm text-ats-text font-normal text-left"><?php echo esc_html( $tax->label ); ?></th>
					<td class="px-6 py-3 text-sm font-medium text-ats-dark text-right"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr class="tax-total">
				<th class="px-6 py-3 text-sm text-ats-text font-normal text-left"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
				<td class="px-6 py-3 text-sm font-medium text-ats-dark text-right"><?php wc_cart_totals_taxes_total_html(); ?></td>
			</tr>
		<?php endif; ?>
	<?php endif; ?>

	<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

	<!-- Total -->
	<tr class="order-total border-t border-gray-200">
		<th class="px-6 py-4 text-base font-bold text-ats-dark text-left"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
		<td class="px-6 py-4 text-lg font-bold text-ats-dark text-right"><?php wc_cart_totals_order_total_html(); ?></td>
	</tr>

	<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

</tbody>
</table>
