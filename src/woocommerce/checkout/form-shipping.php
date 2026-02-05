<?php
/**
 * Checkout shipping information form
 *
 * Styled with Tailwind CSS
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="rfs-ref-shipping-fields woocommerce-shipping-fields">
	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>

		<div class="rfs-ref-ship-to-different bg-white border-2 border-gray-300 rounded-lg p-6 mb-6" id="ship-to-different-address">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox flex items-center gap-3 cursor-pointer">
				<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox w-5 h-5 text-ats-yellow bg-white border-gray-300 rounded focus:ring-ats-yellow focus:ring-2" type="checkbox" name="ship_to_different_address" value="1" />
				<span class="text-base font-semibold text-ats-dark"><?php esc_html_e( 'Ship to a different address?', 'woocommerce' ); ?></span>
			</label>
		</div>

		<div class="rfs-ref-shipping-address shipping_address bg-white border-2 border-gray-300 rounded-lg p-6 lg:p-8" style="display:none;">

			<h3 class="text-xl font-bold text-ats-dark mb-6"><?php esc_html_e( 'Shipping details', 'woocommerce' ); ?></h3>

			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

			<div class="rfs-ref-shipping-field-wrapper woocommerce-shipping-fields__field-wrapper grid grid-cols-1 lg:grid-cols-2 gap-x-4 gap-y-5">
				<?php
				$fields = $checkout->get_checkout_fields( 'shipping' );

				foreach ( $fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>

			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>

		</div>

	<?php endif; ?>
</div>
<div class="rfs-ref-additional-fields woocommerce-additional-fields">
	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>

		<div class="bg-white border-2 border-gray-300 rounded-lg p-6 lg:p-8 mt-6">

			<?php if ( ! WC()->cart->needs_shipping() || wc_ship_to_billing_address_only() ) : ?>

				<h3 class="text-xl font-bold text-ats-dark mb-6"><?php esc_html_e( 'Additional information', 'woocommerce' ); ?></h3>

			<?php endif; ?>

			<div class="rfs-ref-additional-field-wrapper woocommerce-additional-fields__field-wrapper">
				<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
			</div>

		</div>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</div>
