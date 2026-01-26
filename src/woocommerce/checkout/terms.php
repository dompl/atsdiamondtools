<?php
/**
 * Checkout terms and conditions area.
 *
 * Styled with Tailwind CSS
 *
 * @package skylinewp-dev-child
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( apply_filters( 'woocommerce_checkout_show_terms', true ) && function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) ) {
	do_action( 'woocommerce_checkout_before_terms_and_conditions' );

	?>
	<div class="rfs-ref-terms-wrapper woocommerce-terms-and-conditions-wrapper">
		<?php
		/**
		 * Terms and conditions hook used to inject content.
		 *
		 * @since 3.4.0.
		 * @hooked wc_checkout_privacy_policy_text() Shows custom privacy policy text. Priority 20.
		 * @hooked wc_terms_and_conditions_page_content() Shows t&c page content. Priority 30.
		 */
		do_action( 'woocommerce_checkout_terms_and_conditions' );
		?>

		<?php if ( wc_terms_and_conditions_checkbox_enabled() ) : ?>
			<p class="rfs-ref-terms-checkbox form-row validate-required mb-0">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox flex items-start gap-2 cursor-pointer">
					<input
						type="checkbox"
						class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox w-4 h-4 text-ats-yellow bg-white border-gray-300 rounded focus:ring-ats-yellow focus:ring-2 mt-0.5 flex-shrink-0"
						name="terms"
						<?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); ?>
						id="terms"
					/>
					<span class="rfs-ref-terms-text woocommerce-terms-and-conditions-checkbox-text text-sm text-ats-text flex-grow">
						<?php wc_terms_and_conditions_checkbox_text(); ?>
						<abbr class="required text-red-500 ml-1" title="<?php esc_attr_e( 'required', 'woocommerce' ); ?>">*</abbr>
					</span>
				</label>
				<input type="hidden" name="terms-field" value="1" />
			</p>
		<?php endif; ?>
	</div>
	<?php

	do_action( 'woocommerce_checkout_after_terms_and_conditions' );
}
