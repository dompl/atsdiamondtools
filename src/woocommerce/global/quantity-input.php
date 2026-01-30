<?php
/**
 * Product quantity inputs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/quantity-input.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 *
 * @var bool   $readonly If the input should be set to readonly mode.
 * @var string $type     The input type attribute.
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Quantity. */
$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s quantity', 'woocommerce' ), wp_strip_all_tags( $args['product_name'] ) ) : esc_html__( 'Quantity', 'woocommerce' );

?>
<div class="ats-quantity relative flex items-center border border-gray-300 rounded-md w-[180px] h-12 overflow-hidden bg-white">
	<?php
	/**
	 * Hook to output something before the quantity input field.
	 *
	 * @since 7.2.0
	 */
	do_action( 'woocommerce_before_quantity_input_field' );
	?>

    <button type="button" class="ats-qty-btn ats-qty-minus w-20 h-full flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors" <?php echo $readonly ? 'disabled' : ''; ?>>
        <span class="text-xl font-bold leading-none">&minus;</span>
    </button>

    <label class="sr-only screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $label ); ?></label>
    <input
        type="<?php echo esc_attr( $type ); ?>"
        <?php echo $readonly ? 'readonly="readonly"' : ''; ?>
        id="<?php echo esc_attr( $input_id ); ?>"
        class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?> w-full h-full text-center border-none p-0 text-ats-text font-bold focus:ring-0 appearance-none bg-white quantity-input-no-spinners text-xl"
        name="<?php echo esc_attr( $input_name ); ?>"
        value="<?php echo esc_attr( $input_value ); ?>"
        aria-label="<?php esc_attr_e( 'Product quantity', 'woocommerce' ); ?>"
        size="4"
        min="<?php echo esc_attr( $min_value ); ?>"
        <?php if ( 0 < $max_value ) : ?>
            max="<?php echo esc_attr( $max_value ); ?>"
        <?php endif; ?>
        <?php if ( ! $readonly ) : ?>
            step="<?php echo esc_attr( $step ); ?>"
            placeholder="<?php echo esc_attr( $placeholder ); ?>"
            inputmode="<?php echo esc_attr( $inputmode ); ?>"
            autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'on' ); ?>"
        <?php endif; ?>
        title="<?php echo esc_attr_x( 'Qty', 'Product quantity input tooltip', 'woocommerce' ); ?>"
        style="-moz-appearance: textfield;"
    />

    <button type="button" class="ats-qty-btn ats-qty-plus w-20 h-full flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors" <?php echo $readonly ? 'disabled' : ''; ?>>
        <span class="text-xl font-bold leading-none">&plus;</span>
    </button>

	<?php
	/**
	 * Hook to output something after quantity input field
	 *
	 * @since 3.6.0
	 */
	do_action( 'woocommerce_after_quantity_input_field' );
	?>
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
