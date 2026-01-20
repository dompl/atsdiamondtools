<?php
/**
 * Product quantity inputs
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Quantity. */
$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s quantity', 'woocommerce' ), $args['product_name'] ) : esc_html__( 'Quantity', 'woocommerce' );

?>
<div class="quantity relative flex items-center border border-gray-300 rounded-sm w-[140px] h-12 overflow-hidden bg-white">
    <button type="button" class="ats-qty-btn minus w-10 h-full flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors border-r border-gray-200">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
    </button>

    <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
    <input
        type="number"
        id="<?php echo esc_attr( $input_id ); ?>"
        class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?> w-full h-full text-center border-none p-0 text-gray-900 font-bold focus:ring-0 appearance-none bg-white"
        step="<?php echo esc_attr( $step ); ?>"
        min="<?php echo esc_attr( $min_value ); ?>"
        max="<?php echo esc_attr( 0 < $max_value ? $max_value : '' ); ?>"
        name="<?php echo esc_attr( $input_name ); ?>"
        value="<?php echo esc_attr( $input_value ); ?>"
        title="<?php echo esc_attr_x( 'Qty', 'Product quantity input tooltip', 'woocommerce' ); ?>"
        size="4"
        placeholder="<?php echo esc_attr( $placeholder ); ?>"
        inputmode="<?php echo esc_attr( $inputmode ); ?>"
        autocomplete="<?php echo esc_attr( $autocomplete ); ?>"
    />

    <button type="button" class="ats-qty-btn plus w-10 h-full flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors border-l border-gray-200">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
    </button>
</div>
