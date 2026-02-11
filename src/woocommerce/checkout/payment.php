<?php
/**
 * Checkout Payment Section
 *
 * Styled with Tailwind CSS
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="rfs-ref-checkout-payment woocommerce-checkout-payment bg-white border border-gray-300 rounded-lg p-6 mt-6">

	<h3 class="rfs-ref-payment-title text-lg font-bold text-ats-dark mb-6"><?php esc_html_e( 'Payment method', 'woocommerce' ); ?></h3>

	<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>
		<ul class="rfs-ref-payment-methods wc_payment_methods payment_methods methods space-y-3 mb-6">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
			} else {
				echo '<li class="rfs-ref-no-payment-methods prose prose-sm">';
				wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ), 'notice' );
				echo '</li>';
			}
			?>
		</ul>
	<?php endif; ?>

	<div class="rfs-ref-place-order-wrapper form-row place-order">
		<noscript>
			<div class="rfs-ref-noscript-warning bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4 mb-4 text-sm">
				<?php
				/* translators: $1 and $2 opening and closing emphasis tags respectively */
				printf( esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ), '<em>', '</em>' );
				?>
				<br/><button type="submit" class="mt-2 inline-flex items-center px-4 py-2 border border-yellow-300 rounded-md shadow-sm text-sm font-medium text-yellow-800 bg-white hover:bg-yellow-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ats-yellow" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
			</div>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="rfs-ref-place-order-btn w-full bg-ats-yellow hover:bg-yellow-500 text-ats-dark font-bold py-4 px-6 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ats-yellow mt-4" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); ?>

		<!-- Secure Payment Notice -->
		<div class="rfs-ref-secure-payments mt-6 text-center">
			<div class="flex items-center justify-center gap-2 mb-3">
				<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
					<path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
				</svg>
				<span class="text-xs font-medium text-ats-text">Secure & encrypted payment</span>
			</div>
			<div class="rfs-ref-payment-icons flex items-center justify-center gap-3 flex-wrap opacity-70">
				<img src="https://raw.githubusercontent.com/datatrans/payment-logos/master/assets/cards/visa.svg" alt="Visa" class="h-6" loading="lazy">
				<img src="https://raw.githubusercontent.com/datatrans/payment-logos/master/assets/cards/mastercard.svg" alt="Mastercard" class="h-6" loading="lazy">
				<img src="https://raw.githubusercontent.com/datatrans/payment-logos/master/assets/cards/american-express.svg" alt="American Express" class="h-6" loading="lazy">
				<img src="https://raw.githubusercontent.com/datatrans/payment-logos/master/assets/apm/paypal.svg" alt="PayPal" class="h-6" loading="lazy">
			</div>
		</div>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
