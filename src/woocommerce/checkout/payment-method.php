<?php
/**
 * Output a single payment method
 *
 * Styled with Tailwind CSS
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     skylinewp-dev-child
 * @version     3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<li class="rfs-ref-payment-method wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?> border border-gray-200 rounded-lg hover:border-ats-yellow transition-colors duration-200">
	<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>" class="rfs-ref-payment-method-label flex items-center gap-3 p-4 cursor-pointer">
		<input
			id="payment_method_<?php echo esc_attr( $gateway->id ); ?>"
			type="radio"
			class="rfs-ref-payment-radio input-radio w-4 h-4 text-ats-yellow bg-white border-gray-300 focus:ring-ats-yellow focus:ring-2"
			name="payment_method"
			value="<?php echo esc_attr( $gateway->id ); ?>"
			<?php checked( $gateway->chosen, true ); ?>
		/>
		<span class="rfs-ref-payment-method-title flex-grow text-sm font-medium text-ats-dark">
			<?php echo $gateway->get_title(); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?>
		</span>
		<?php if ( $gateway->get_icon() ) : ?>
			<span class="rfs-ref-payment-method-icon flex-shrink-0">
				<?php echo $gateway->get_icon(); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?>
			</span>
		<?php endif; ?>
	</label>

	<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
		<div class="rfs-ref-payment-box payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?> px-4 pb-4" <?php if ( ! $gateway->chosen ) : ?>style="display:none;"<?php endif; ?>>
			<div class="rfs-ref-payment-fields pt-3 border-t border-gray-200 text-sm text-ats-text">
				<?php $gateway->payment_fields(); ?>
			</div>
		</div>
	<?php endif; ?>
</li>
