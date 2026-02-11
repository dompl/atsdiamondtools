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
			<?php
			// Get the T&C page
			$terms_page_id = wc_terms_and_conditions_page_id();
			$terms_page = $terms_page_id ? get_post( $terms_page_id ) : null;
			?>
			<p class="rfs-ref-terms-checkbox form-row validate-required mb-0">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox flex items-start gap-2 cursor-pointer">
					<input
						type="checkbox"
						class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox w-4 h-4 text-ats-yellow bg-white border-gray-300 rounded focus:ring-ats-yellow focus:ring-2 mt-0.5 flex-shrink-0"
						name="terms"
						<?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); ?>
						id="terms"
					/>
					<span class="rfs-ref-terms-text woocommerce-terms-and-conditions-checkbox-text text-sm text-ats-text flex-grow whitespace-nowrap">
						I agree to the
						<?php if ( $terms_page ) : ?>
							<button
								type="button"
								class="rfs-ref-terms-modal-trigger text-ats-brand hover:text-brand-dark underline font-medium"
								data-modal-target="terms-modal"
								data-modal-toggle="terms-modal">
								Terms & Conditions
							</button>
						<?php else : ?>
							<?php wc_terms_and_conditions_checkbox_text(); ?>
						<?php endif; ?>
						<abbr class="required text-red-500 ml-1" title="<?php esc_attr_e( 'required', 'woocommerce' ); ?>">*</abbr>
					</span>
				</label>
				<input type="hidden" name="terms-field" value="1" />
			</p>

			<?php if ( $terms_page ) : ?>
				<!-- Terms & Conditions Modal -->
				<div id="terms-modal" tabindex="-1" aria-hidden="true" class="rfs-ref-terms-modal hidden fixed inset-0 z-50 bg-black bg-opacity-60 backdrop-blur-sm transition-opacity">
					<div class="rfs-ref-modal-container flex items-center justify-center min-h-screen p-4">
						<!-- Modal content -->
						<div class="rfs-ref-modal-content relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col transform transition-transform">
							<!-- Modal header -->
							<div class="rfs-ref-modal-header flex items-center justify-between px-6 py-4 border-b border-gray-100">
								<h3 class="text-lg font-semibold text-ats-dark">
									<?php echo esc_html( $terms_page->post_title ); ?>
								</h3>
								<button type="button" class="rfs-ref-modal-close group ml-4 p-2 text-gray-400 hover:text-ats-dark hover:bg-gray-100 rounded-lg transition-all" data-modal-hide="terms-modal" aria-label="Close modal">
									<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
									</svg>
								</button>
							</div>
							<!-- Modal body -->
							<div class="rfs-ref-modal-body px-6 py-5 overflow-y-auto flex-grow custom-scrollbar">
								<div class="prose prose-sm max-w-none text-ats-text leading-relaxed">
									<?php echo apply_filters( 'the_content', $terms_page->post_content ); ?>
								</div>
							</div>
							<!-- Modal footer -->
							<div class="rfs-ref-modal-footer flex items-center justify-center px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
								<button data-modal-accept="terms-modal" type="button" class="ats-btn ats-btn-md ats-btn-dark px-8 shadow-sm hover:shadow-md transition-shadow">
									I Understand
								</button>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php

	do_action( 'woocommerce_checkout_after_terms_and_conditions' );
}
