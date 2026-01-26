<?php
/**
 * Thankyou page
 *
 * Styled with Tailwind CSS
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="rfs-ref-thankyou-page woocommerce-order">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<!-- Failed Order -->
			<div class="rfs-ref-order-failed bg-white border border-red-200 rounded-lg p-8 text-center">
				<div class="rfs-ref-failed-icon mb-4">
					<svg class="w-16 h-16 mx-auto text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
					</svg>
				</div>
				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed text-base text-red-800 mb-6">
					<?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?>
				</p>

				<div class="rfs-ref-failed-actions woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions flex flex-wrap gap-4 justify-center">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay inline-flex items-center px-6 py-3 bg-ats-yellow hover:bg-yellow-500 text-ats-dark font-semibold rounded-lg transition-colors duration-200">
						<?php esc_html_e( 'Pay', 'woocommerce' ); ?>
					</a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay inline-flex items-center px-6 py-3 border border-gray-300 hover:border-gray-400 text-ats-dark font-semibold rounded-lg transition-colors duration-200">
							<?php esc_html_e( 'My account', 'woocommerce' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

		<?php else : ?>

			<!-- Success Message -->
			<?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>

			<!-- Order Details -->
			<div class="rfs-ref-order-overview-wrapper bg-white border border-gray-200 rounded-lg p-6 mt-6">
				<h2 class="rfs-ref-order-details-title text-xl font-bold text-ats-dark mb-6"><?php esc_html_e( 'Order details', 'woocommerce' ); ?></h2>
				<ul class="rfs-ref-order-overview woocommerce-order-overview woocommerce-thankyou-order-details order_details grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

					<li class="rfs-ref-order-number woocommerce-order-overview__order order">
						<span class="block text-xs text-ats-text mb-1"><?php esc_html_e( 'Order number:', 'woocommerce' ); ?></span>
						<strong class="block text-base font-semibold text-ats-dark"><?php echo $order->get_order_number(); ?></strong>
					</li>

					<li class="rfs-ref-order-date woocommerce-order-overview__date date">
						<span class="block text-xs text-ats-text mb-1"><?php esc_html_e( 'Date:', 'woocommerce' ); ?></span>
						<strong class="block text-base font-semibold text-ats-dark"><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
					</li>

					<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
						<li class="rfs-ref-order-email woocommerce-order-overview__email email">
							<span class="block text-xs text-ats-text mb-1"><?php esc_html_e( 'Email:', 'woocommerce' ); ?></span>
							<strong class="block text-base font-semibold text-ats-dark"><?php echo $order->get_billing_email(); ?></strong>
						</li>
					<?php endif; ?>

					<li class="rfs-ref-order-total woocommerce-order-overview__total total">
						<span class="block text-xs text-ats-text mb-1"><?php esc_html_e( 'Total:', 'woocommerce' ); ?></span>
						<strong class="block text-base font-semibold text-ats-dark"><?php echo $order->get_formatted_order_total(); ?></strong>
					</li>

					<?php if ( $order->get_payment_method_title() ) : ?>
						<li class="rfs-ref-order-payment woocommerce-order-overview__payment-method method">
							<span class="block text-xs text-ats-text mb-1"><?php esc_html_e( 'Payment method:', 'woocommerce' ); ?></span>
							<strong class="block text-base font-semibold text-ats-dark"><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
						</li>
					<?php endif; ?>

				</ul>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

	<?php endif; ?>

</div>
