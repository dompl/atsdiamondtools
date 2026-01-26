<?php
/**
 * Cart totals
 *
 * Styled with Tailwind CSS
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 2.3.6
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="rfs-ref-cart-totals cart_totals bg-white border border-gray-200 rounded-lg p-6 <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">

	<?php do_action( 'woocommerce_before_cart_totals' ); ?>

	<div class="rfs-ref-cart-totals-table space-y-4">

		<!-- Subtotal -->
		<div class="rfs-ref-cart-subtotal cart-subtotal flex items-center justify-between pb-4 border-b border-gray-100">
			<span class="text-base text-ats-text"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
			<span class="text-base font-semibold text-ats-dark">
				<?php wc_cart_totals_subtotal_html(); ?>
			</span>
		</div>

		<!-- Coupons -->
		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="rfs-ref-cart-discount cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?> flex items-center justify-between pb-4 border-b border-gray-100">
				<div class="flex items-center gap-2">
					<svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
					</svg>
					<span class="text-sm text-ats-text"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
				</div>
				<div class="flex items-center gap-2">
					<span class="text-sm font-semibold text-green-600">
						<?php wc_cart_totals_coupon_html( $coupon ); ?>
					</span>
				</div>
			</div>
		<?php endforeach; ?>

		<!-- Shipping -->
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

			<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>

			<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>

		<?php elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) : ?>

			<div class="rfs-ref-cart-shipping shipping flex items-start justify-between pb-4 border-b border-gray-100">
				<span class="text-base text-ats-text"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span>
				<div class="text-right">
					<?php woocommerce_shipping_calculator(); ?>
				</div>
			</div>

		<?php endif; ?>

		<!-- Fees -->
		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="rfs-ref-cart-fee fee flex items-center justify-between pb-4 border-b border-gray-100">
				<span class="text-sm text-ats-text"><?php echo esc_html( $fee->name ); ?></span>
				<span class="text-sm font-semibold text-ats-dark">
					<?php wc_cart_totals_fee_html( $fee ); ?>
				</span>
			</div>
		<?php endforeach; ?>

		<!-- Tax -->
		<?php
		if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
			$taxable_address = WC()->customer->get_taxable_address();
			$estimated_text  = '';

			if ( WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping() ) {
				/* translators: %s location. */
				$estimated_text = sprintf( ' <small>' . esc_html__( '(estimated for %s)', 'woocommerce' ) . '</small>', WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] );
			}

			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
					?>
					<div class="rfs-ref-cart-tax tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?> flex items-center justify-between pb-4 border-b border-gray-100">
						<span class="text-sm text-ats-text"><?php echo esc_html( $tax->label ) . $estimated_text; ?></span>
						<span class="text-sm font-semibold text-ats-dark"><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
					</div>
					<?php
				}
			} else {
				?>
				<div class="rfs-ref-cart-tax tax-total flex items-center justify-between pb-4 border-b border-gray-100">
					<span class="text-sm text-ats-text"><?php echo esc_html( WC()->countries->tax_or_vat() ) . $estimated_text; ?></span>
					<span class="text-sm font-semibold text-ats-dark"><?php wc_cart_totals_taxes_total_html(); ?></span>
				</div>
				<?php
			}
		}
		?>

		<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

		<!-- Total -->
		<div class="rfs-ref-cart-total order-total flex items-center justify-between pt-4 border-t-2 border-ats-yellow">
			<span class="text-lg font-bold text-ats-dark"><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
			<span class="text-2xl font-bold text-ats-dark">
				<?php wc_cart_totals_order_total_html(); ?>
			</span>
		</div>

		<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

	</div>

	<!-- Proceed to Checkout Button -->
	<div class="rfs-ref-proceed-to-checkout wc-proceed-to-checkout mt-6">
		<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
	</div>

	<?php do_action( 'woocommerce_after_cart_totals' ); ?>

</div>
