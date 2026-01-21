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
<div class="ats-quantity relative flex items-center border border-gray-300 rounded-md w-[180px] h-12 overflow-hidden bg-white">
    <button type="button" class="ats-qty-btn ats-qty-minus w-20 h-full flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors">
        <span class="text-xl font-bold leading-none">&minus;</span>
    </button>

    <label class="sr-only" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
    <input
        type="number"
        id="<?php echo esc_attr( $input_id ); ?>"
        class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?> w-full h-full text-center border-none p-0 text-ats-text font-bold focus:ring-0 appearance-none bg-white quantity-input-no-spinners text-xl"
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
        style="-moz-appearance: textfield;"
    />

    <button type="button" class="ats-qty-btn ats-qty-plus w-20 h-full flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors">
        <span class="text-xl font-bold leading-none">&plus;</span>
    </button>
</div>
<style>
    /* Chrome, Safari, Edge, Opera */
    .quantity-input-no-spinners::-webkit-outer-spin-button,
    .quantity-input-no-spinners::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
</style>
<?php
