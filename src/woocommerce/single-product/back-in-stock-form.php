<?php
/**
 * Back in Stock Notification Form
 *
 * Displayed on single product pages when the product (or a variation) is out of stock.
 * For variable products, the form is hidden by default and shown/hidden by JS
 * based on the selected variation's stock status.
 *
 * Works with src/assets/js/components/back-in-stock.js
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product ) {
	return;
}

$is_variable  = $product->is_type( 'variable' );
$product_id   = $product->get_id();
$is_logged_in = is_user_logged_in();
$user_email   = $is_logged_in ? wp_get_current_user()->user_email : '';

// For simple products: only show if out of stock.
// For variable products: always render (JS controls visibility).
if ( ! $is_variable && $product->is_in_stock() ) {
	return;
}

// Hidden by default for variable products (JS shows it when out-of-stock variation is selected).
$wrapper_hidden = $is_variable ? ' hidden' : '';
?>

<div class="rfs-ref-stock-notification-wrapper ats-stock-notification-form-wrapper mt-4<?php echo esc_attr( $wrapper_hidden ); ?>">
	<form class="ats-stock-notification-form bg-gray-50 border border-gray-200 rounded-lg p-4">
		<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
		<input type="hidden" name="variation_id" value="0">

		<div class="flex items-center gap-2 mb-3">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-ats-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
			</svg>
			<h4 class="rfs-ref-stock-notification-heading text-sm font-semibold text-ats-dark">
				<?php esc_html_e( 'Notify Me When Available', 'skylinewp-dev-child' ); ?>
			</h4>
		</div>

		<p class="rfs-ref-stock-notification-desc text-xs text-gray-500 mb-3">
			<?php esc_html_e( 'Enter your email and we\'ll notify you when this product is back in stock.', 'skylinewp-dev-child' ); ?>
		</p>

		<?php if ( ! $is_logged_in ) : ?>
			<div class="mb-3">
				<input
					type="email"
					name="email"
					placeholder="<?php esc_attr_e( 'Your email address', 'skylinewp-dev-child' ); ?>"
					class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-ats-brand focus:border-ats-brand"
					required
				>
			</div>
		<?php else : ?>
			<div class="mb-3">
				<input type="hidden" name="email" value="<?php echo esc_attr( $user_email ); ?>">
				<p class="text-xs text-gray-500">
					<?php
					printf(
						/* translators: %s: user email */
						esc_html__( 'Notification will be sent to: %s', 'skylinewp-dev-child' ),
						'<strong>' . esc_html( $user_email ) . '</strong>'
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<div class="mb-3">
			<label class="flex items-start gap-2 cursor-pointer">
				<input type="checkbox" name="agree" class="mt-0.5 rounded border-gray-300 text-ats-brand focus:ring-ats-brand" required>
				<span class="text-xs text-gray-500">
					<?php esc_html_e( 'I agree to be notified via email when this product becomes available.', 'skylinewp-dev-child' ); ?>
				</span>
			</label>
		</div>

		<button type="submit" class="rfs-ref-stock-notification-submit ats-btn ats-btn-sm ats-btn-yellow w-full">
			<?php esc_html_e( 'Notify Me', 'skylinewp-dev-child' ); ?>
		</button>

		<div class="rfs-ref-stock-notification-message hidden mt-3"></div>
	</form>
</div>
