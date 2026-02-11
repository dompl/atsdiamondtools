<?php
/**
 * Checkout billing information form
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
<div class="rfs-ref-billing-fields woocommerce-billing-fields bg-white border border-gray-300 rounded-lg p-6 lg:p-8">
	<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>

		<h3 class="text-xl font-bold text-ats-dark mb-6"><?php esc_html_e( 'Billing & Shipping', 'woocommerce' ); ?></h3>

	<?php else : ?>

		<h3 class="text-xl font-bold text-ats-dark mb-6"><?php esc_html_e( 'Billing details', 'woocommerce' ); ?></h3>

	<?php endif; ?>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="rfs-ref-billing-field-wrapper woocommerce-billing-fields__field-wrapper grid grid-cols-1 lg:grid-cols-2 gap-x-4 gap-y-5">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );

		foreach ( $fields as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="rfs-ref-account-fields woocommerce-account-fields bg-white border border-gray-300 rounded-lg p-6 lg:p-8 mt-6">
		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="rfs-ref-create-account-toggle form-row form-row-wide create-account mb-0">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox flex items-center gap-2 cursor-pointer">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox w-4 h-4 text-ats-yellow bg-white border-gray-300 rounded focus:ring-ats-yellow focus:ring-2" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" />
					<span class="text-base text-ats-text"><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<div class="rfs-ref-create-account-fields create-account mt-5 space-y-5">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>
