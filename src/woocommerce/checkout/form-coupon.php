<?php
/**
 * Checkout coupon form
 *
 * Styled with Tailwind CSS
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) {
	return;
}

?>
<div class="rfs-ref-coupon-toggle-wrapper woocommerce-form-coupon-toggle mb-6">
	<div class="rfs-ref-coupon-notice bg-gray-50 border border-gray-200 rounded-lg p-4">
		<p class="text-sm text-ats-text mb-0">
			<?php esc_html_e( 'Have a coupon?', 'woocommerce' ); ?>
			<button type="button" role="button" aria-label="<?php esc_attr_e( 'Enter your coupon code', 'woocommerce' ); ?>" aria-controls="woocommerce-checkout-form-coupon" aria-expanded="false" class="showcoupon text-ats-yellow hover:text-yellow-600 font-semibold ml-1 underline">
				<?php esc_html_e( 'Click here to enter your code', 'woocommerce' ); ?>
			</button>
		</p>
	</div>
</div>

<div class="rfs-ref-checkout-coupon-wrapper" style="display: none;" id="woocommerce-checkout-form-coupon">
	<form class="checkout_coupon woocommerce-form-coupon bg-white border border-gray-200 rounded-lg p-6 mb-6" method="post">
		<div class="flex gap-3">
			<div class="flex-grow">
				<label for="coupon_code" class="screen-reader-text hidden"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
				<input type="text" name="coupon_code" class="input-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow text-sm text-ats-dark" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" id="coupon_code" value="" />
			</div>

			<div class="flex-shrink-0">
				<button type="submit" class="button inline-flex items-center px-6 py-3 bg-ats-yellow hover:bg-yellow-500 text-ats-dark font-bold rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ats-yellow whitespace-nowrap h-full" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>">
					<?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?>
				</button>
			</div>
		</div>
	</form>
</div>
