<?php
/**
 * Checkout Newsletter Opt-Out
 *
 * Adds an opt-out newsletter checkbox to the checkout page.
 * When left unticked (default), customers are subscribed to Brevo.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display opt-out newsletter checkbox after terms and conditions
 *
 * @return void
 */
function ats_checkout_newsletter_checkbox() {
	$enabled = get_field( 'ats_checkout_newsletter_enabled', 'option' );

	if ( ! $enabled ) {
		return;
	}

	$label = get_field( 'ats_checkout_newsletter_label', 'option' );
	if ( empty( $label ) ) {
		$label = 'I do not wish to sign up for the ATS Diamond Tools newsletter';
	}

	?>
	<p class="rfs-ref-checkout-newsletter form-row mt-3">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox flex items-start gap-2 cursor-pointer">
			<input
				type="checkbox"
				class="rfs-ref-checkout-newsletter-input woocommerce-form__input woocommerce-form__input-checkbox input-checkbox w-4 h-4 text-ats-yellow bg-white border-gray-300 rounded focus:ring-ats-yellow focus:ring-2 mt-0.5 flex-shrink-0"
				name="ats_newsletter_opt_out"
				value="1"
			/>
			<span class="rfs-ref-checkout-newsletter-text text-sm text-ats-text flex-grow">
				<?php echo esc_html( $label ); ?>
			</span>
		</label>
	</p>
	<?php
}
add_action( 'woocommerce_checkout_after_terms_and_conditions', 'ats_checkout_newsletter_checkbox' );

/**
 * Process newsletter subscription after order is created
 *
 * If the opt-out checkbox was NOT ticked, subscribe the customer to Brevo.
 *
 * @param int      $order_id    The order ID.
 * @param array    $posted_data Posted checkout data.
 * @param WC_Order $order       The order object.
 * @return void
 */
function ats_checkout_process_newsletter( $order_id, $posted_data, $order ) {
	$enabled = get_field( 'ats_checkout_newsletter_enabled', 'option' );

	if ( ! $enabled ) {
		return;
	}

	// If checkbox is ticked, customer opted OUT - do not subscribe
	if ( ! empty( $posted_data['ats_newsletter_opt_out'] ) ) {
		return;
	}

	$email      = $order->get_billing_email();
	$first_name = $order->get_billing_first_name();
	$last_name  = $order->get_billing_last_name();

	if ( empty( $email ) || ! is_email( $email ) ) {
		return;
	}

	$api_key = defined( 'BREVO_API' ) ? BREVO_API : '';
	$list_id = get_field( 'ats_checkout_newsletter_list_id', 'option' );

	if ( empty( $api_key ) || empty( $list_id ) ) {
		error_log( 'ATS Checkout Newsletter: Missing API key or list ID configuration.' );
		return;
	}

	if ( ! function_exists( 'ats_subscribe_to_brevo' ) ) {
		error_log( 'ATS Checkout Newsletter: ats_subscribe_to_brevo() function not available.' );
		return;
	}

	$result = ats_subscribe_to_brevo( $email, $api_key, $list_id, $first_name, $last_name );

	if ( is_wp_error( $result ) ) {
		error_log( 'ATS Checkout Newsletter Error: ' . $result->get_error_message() );
		return;
	}

	$order->update_meta_data( '_ats_newsletter_subscribed', 'yes' );
	$order->save();
}
add_action( 'woocommerce_checkout_order_processed', 'ats_checkout_process_newsletter', 10, 3 );
