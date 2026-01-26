<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Empty cart message with custom styling
 */
?>

<div class="rfs-ref-cart-empty woocommerce-cart-empty bg-white border border-gray-200 rounded-lg p-12 text-center">
	<!-- Shopping Cart Icon -->
	<div class="rfs-ref-empty-cart-icon mb-6">
		<svg xmlns="http://www.w3.org/2000/svg" height="80px" viewBox="0 -960 960 960" width="80px" fill="#9CA3AF" class="mx-auto opacity-50">
			<path d="M280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM246-720l96 200h280l110-200H246Zm-38-80h590q23 0 35 20.5t1 41.5L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68-39.5t-2-78.5l54-98-144-304H40v-80h130l38 80Zm134 280h280-280Z"/>
		</svg>
	</div>

	<!-- Empty Message -->
	<h2 class="rfs-ref-empty-cart-title text-2xl font-bold text-ats-dark mb-3">
		<?php esc_html_e( 'Your cart is empty', 'woocommerce' ); ?>
	</h2>
	<p class="rfs-ref-empty-cart-text text-ats-text mb-8">
		<?php esc_html_e( 'Looks like you haven\'t added anything to your cart yet.', 'woocommerce' ); ?>
	</p>

	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<!-- Return to Shop Button -->
		<div class="rfs-ref-return-to-shop return-to-shop">
			<a class="rfs-ref-shop-button ats-btn ats-btn-lg ats-btn-yellow inline-flex items-center gap-2 px-8 py-4 rounded-lg font-semibold transition-all hover:shadow-lg" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
				<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
				</svg>
				<?php
				/**
				 * Filter "Return To Shop" text.
				 *
				 * @since 4.6.0
				 * @param string $default_text Default text.
				 */
				echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Start shopping', 'woocommerce' ) ) );
				?>
			</a>
		</div>
	<?php endif; ?>
</div>

<?php
