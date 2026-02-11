<?php
/**
 * Checkout Form
 *
 * Styled with Tailwind CSS
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo '<div class="rfs-ref-checkout-login-required woocommerce-info bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-6 text-center">';
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	echo '</div>';
	return;
}

?>

<div class="rfs-ref-checkout-page">
	<div class="rfs-ref-checkout-container container mx-auto px-4 py-8 max-w-7xl">

		<!-- Checkout Notices -->
		<div class="rfs-ref-checkout-notices mb-6">
			<?php wc_print_notices(); ?>
		</div>

		<form name="checkout" method="post" class="rfs-ref-checkout-form checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<!-- Headers Row -->
				<div class="rfs-ref-checkout-headers grid grid-cols-1 lg:grid-cols-3 gap-8 mb-6">
					<div class="lg:col-span-2">
						<div class="rfs-ref-checkout-header-wrapper flex items-center justify-between">
							<h2 class="rfs-ref-checkout-title text-2xl font-bold text-ats-dark">Checkout</h2>
							<?php if ( ! is_user_logged_in() ) : ?>
								<button type="button" class="rfs-ref-login-toggle text-sm text-ats-text hover:text-ats-dark font-semibold underline" data-modal-target="checkout-login-modal" data-modal-toggle="checkout-login-modal">
									Returning customer? Log in here
								</button>
							<?php endif; ?>
						</div>
					</div>
					<div class="lg:col-span-1">
						<h2 class="rfs-ref-order-summary-title text-2xl font-bold text-ats-dark">Order Summary</h2>
					</div>
				</div>

				<!-- Content Row -->
				<div class="rfs-ref-checkout-layout grid grid-cols-1 lg:grid-cols-3 gap-8">

					<!-- Customer Details Section (Left 2/3) -->
					<div class="rfs-ref-customer-details lg:col-span-2" id="customer_details">
						<div class="space-y-6">
							<!-- Coupon Section -->
							<?php wc_get_template( 'checkout/form-coupon.php' ); ?>

							<!-- Billing Section -->
							<div class="rfs-ref-billing-section">
								<?php do_action( 'woocommerce_checkout_billing' ); ?>
							</div>

							<!-- Shipping Section -->
							<div class="rfs-ref-shipping-section">
								<?php do_action( 'woocommerce_checkout_shipping' ); ?>
							</div>
						</div>
					</div>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

					<!-- Order Review Section (Right 1/3) -->
					<div class="rfs-ref-order-review-sidebar lg:col-span-1">
						<div class="rfs-ref-order-review-wrapper">

							<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

							<div id="order_review" class="rfs-ref-order-review woocommerce-checkout-review-order">
								<?php do_action( 'woocommerce_checkout_order_review' ); ?>
							</div>

							<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

						</div>
					</div>

				</div>

			<?php endif; ?>

		</form>

		<!-- Login Modal -->
		<?php if ( ! is_user_logged_in() ) : ?>
			<div id="checkout-login-modal" tabindex="-1" aria-hidden="true" class="rfs-ref-login-modal hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-gray-900 bg-opacity-50">
				<div class="relative p-4 w-full max-w-md max-h-full mx-auto mt-20">
					<!-- Modal content -->
					<div class="relative bg-white rounded-lg shadow">
						<!-- Modal header -->
						<div class="flex items-center justify-between p-4 md:p-5 border-b border-gray-200 rounded-t">
							<h3 class="text-xl font-semibold text-ats-dark">
								Login to Your Account
							</h3>
							<button type="button" class="rfs-ref-modal-close text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="checkout-login-modal">
								<svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
									<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
								</svg>
								<span class="sr-only">Close modal</span>
							</button>
						</div>
						<!-- Modal body -->
						<div class="p-4 md:p-5">
							<form class="rfs-ref-checkout-login-form space-y-4" method="post" action="<?php echo esc_url( wp_login_url( wc_get_checkout_url() ) ); ?>">

								<div class="rfs-ref-login-messages"></div>

								<div>
									<label for="modal_username" class="block mb-2 text-sm font-medium text-ats-dark">Email or Username</label>
									<input type="text" name="username" id="modal_username" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm text-ats-dark focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow transition-colors duration-200" placeholder="Enter your email or username" required>
								</div>

								<div>
									<label for="modal_password" class="block mb-2 text-sm font-medium text-ats-dark">Password</label>
									<input type="password" name="password" id="modal_password" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm text-ats-dark focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow transition-colors duration-200" placeholder="Enter your password" required>
								</div>

								<div class="flex items-center justify-between">
									<div class="flex items-start">
										<div class="flex items-center h-5">
											<input id="modal_remember" name="rememberme" type="checkbox" value="forever" class="w-4 h-4 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-ats-yellow text-ats-yellow">
										</div>
										<label for="modal_remember" class="ms-2 text-sm font-medium text-ats-text">Remember me</label>
									</div>
									<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="text-sm text-ats-yellow hover:text-yellow-600 font-medium">Forgot password?</a>
								</div>

								<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
								<input type="hidden" name="redirect" value="<?php echo esc_url( wc_get_checkout_url() ); ?>">

								<button type="submit" name="login" class="w-full px-6 py-3 bg-ats-yellow hover:bg-yellow-500 text-ats-dark font-bold rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ats-yellow">
									Login
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

	</div>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
