<?php
/**
 * Shipping Calculator
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/shipping-calculator.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_shipping_calculator' ); ?>

<form class="rfs-ref-shipping-calculator woocommerce-shipping-calculator mt-3" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

	<?php printf( '<a href="#" class="shipping-calculator-button text-xs text-ats-yellow hover:text-ats-dark transition-colors underline" aria-expanded="false" aria-controls="shipping-calculator-form" role="button">%s</a>', esc_html( ! empty( $button_text ) ? $button_text : __( 'Calculate shipping', 'woocommerce' ) ) ); ?>

	<section class="rfs-ref-shipping-calculator-form shipping-calculator-form mt-4 p-4 bg-gray-50 rounded-lg space-y-4" id="shipping-calculator-form" style="display:none;">

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_country', true ) ) : ?>
			<div class="rfs-ref-calc-field form-row form-row-wide" id="calc_shipping_country_field">
				<label for="calc_shipping_country" class="block text-xs font-medium text-ats-text mb-1"><?php esc_html_e( 'Country / region', 'woocommerce' ); ?></label>
				<select name="calc_shipping_country" id="calc_shipping_country" class="country_to_state country_select w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow" rel="calc_shipping_state">
					<option value="default"><?php esc_html_e( 'Select a country / region&hellip;', 'woocommerce' ); ?></option>
					<?php
					foreach ( WC()->countries->get_shipping_countries() as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '"' . selected( WC()->customer->get_shipping_country(), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
					}
					?>
				</select>
			</div>
		<?php endif; ?>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_state', true ) ) : ?>
			<div class="rfs-ref-calc-field form-row form-row-wide" id="calc_shipping_state_field">
				<?php
				$current_cc = WC()->customer->get_shipping_country();
				$current_r  = WC()->customer->get_shipping_state();
				$states     = WC()->countries->get_states( $current_cc );

				if ( is_array( $states ) && empty( $states ) ) {
					?>
					<input type="hidden" name="calc_shipping_state" id="calc_shipping_state" />
					<?php
				} elseif ( is_array( $states ) ) {
					?>
					<div>
						<label for="calc_shipping_state" class="block text-xs font-medium text-ats-text mb-1"><?php esc_html_e( 'State / County', 'woocommerce' ); ?></label>
						<select name="calc_shipping_state" class="state_select w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow" id="calc_shipping_state">
							<option value=""><?php esc_html_e( 'Select an option&hellip;', 'woocommerce' ); ?></option>
							<?php
							foreach ( $states as $ckey => $cvalue ) {
								echo '<option value="' . esc_attr( $ckey ) . '" ' . selected( $current_r, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
							}
							?>
						</select>
					</div>
					<?php
				} else {
					?>
					<label for="calc_shipping_state" class="block text-xs font-medium text-ats-text mb-1"><?php esc_html_e( 'State / County', 'woocommerce' ); ?></label>
					<input type="text" class="input-text w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow" value="<?php echo esc_attr( $current_r ); ?>" name="calc_shipping_state" id="calc_shipping_state" />
					<?php
				}
				?>
			</div>
		<?php endif; ?>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_city', true ) ) : ?>
			<div class="rfs-ref-calc-field form-row form-row-wide" id="calc_shipping_city_field">
				<label for="calc_shipping_city" class="block text-xs font-medium text-ats-text mb-1"><?php esc_html_e( 'City', 'woocommerce' ); ?></label>
				<input type="text" class="input-text w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow" value="<?php echo esc_attr( WC()->customer->get_shipping_city() ); ?>" name="calc_shipping_city" id="calc_shipping_city" />
			</div>
		<?php endif; ?>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_postcode', true ) ) : ?>
			<div class="rfs-ref-calc-field form-row form-row-wide" id="calc_shipping_postcode_field">
				<label for="calc_shipping_postcode" class="block text-xs font-medium text-ats-text mb-1"><?php esc_html_e( 'Postcode / ZIP', 'woocommerce' ); ?></label>
				<input type="text" class="input-text w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow" value="<?php echo esc_attr( WC()->customer->get_shipping_postcode() ); ?>" name="calc_shipping_postcode" id="calc_shipping_postcode" />
			</div>
		<?php endif; ?>

		<div><button type="submit" name="calc_shipping" value="1" class="ats-btn ats-btn-sm ats-btn-yellow w-full"><?php esc_html_e( 'Update', 'woocommerce' ); ?></button></div>
		<?php wp_nonce_field( 'woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce' ); ?>
	</section>
</form>

<?php do_action( 'woocommerce_after_shipping_calculator' ); ?>
