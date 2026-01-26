<?php
/**
 * Review order table
 *
 * Styled with Tailwind CSS
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="rfs-ref-review-order-wrapper bg-white border border-gray-200 rounded-lg p-6">

	<!-- Products List -->
	<div class="rfs-ref-review-products space-y-4 mb-6 pb-6 border-b border-gray-200">
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( array( 60, 60 ) ), $cart_item, $cart_item_key );
				?>
				<div class="rfs-ref-review-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?> flex gap-3">
					<!-- Product Thumbnail -->
					<div class="rfs-ref-review-item-thumbnail flex-shrink-0">
						<a href="<?php echo esc_url( $_product->get_permalink( $cart_item ) ); ?>" class="block w-16 h-16 rounded border border-gray-200 overflow-hidden">
							<?php echo $thumbnail; ?>
						</a>
					</div>

					<!-- Product Details -->
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

	<!-- Totals -->
	<div class="rfs-ref-review-totals space-y-3">

		<!-- Subtotal -->
		<div class="rfs-ref-review-subtotal flex justify-between items-center text-sm">
			<span class="text-ats-text"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
			<span class="font-medium text-ats-dark"><?php wc_cart_totals_subtotal_html(); ?></span>
		</div>

		<!-- Coupons -->
		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="rfs-ref-review-coupon cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?> flex justify-between items-center text-sm">
				<span class="text-ats-text"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
				<span class="font-medium text-green-600"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
			</div>
		<?php endforeach; ?>

		<!-- Shipping -->
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<div class="rfs-ref-review-shipping">
				<?php wc_cart_totals_shipping_html(); ?>
			</div>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>

		<!-- Fees -->
		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="rfs-ref-review-fee fee flex justify-between items-center text-sm">
				<span class="text-ats-text"><?php echo esc_html( $fee->name ); ?></span>
				<span class="font-medium text-ats-dark"><?php wc_cart_totals_fee_html( $fee ); ?></span>
			</div>
		<?php endforeach; ?>

		<!-- Tax -->
		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
					<div class="rfs-ref-review-tax tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?> flex justify-between items-center text-sm">
						<span class="text-ats-text"><?php echo esc_html( $tax->label ); ?></span>
						<span class="font-medium text-ats-dark"><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="rfs-ref-review-tax-total tax-total flex justify-between items-center text-sm">
					<span class="text-ats-text"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
					<span class="font-medium text-ats-dark"><?php wc_cart_totals_taxes_total_html(); ?></span>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<!-- Total -->
		<div class="rfs-ref-review-total order-total flex justify-between items-center pt-4 mt-4 border-t border-gray-200">
			<span class="text-base font-bold text-ats-dark"><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
			<span class="text-lg font-bold text-ats-dark"><?php wc_cart_totals_order_total_html(); ?></span>
		</div>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</div>
</div>
