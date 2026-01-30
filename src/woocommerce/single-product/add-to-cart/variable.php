<?php
/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<div class="variations space-y-4 mb-6">
			<?php foreach ( $attributes as $attribute_name => $options ) : ?>
                <?php
                    $sanitized_name = sanitize_title( $attribute_name );
                    $dropdown_id = 'dropdown_' . $sanitized_name;
                ?>
				<div class="variation-row flowbite-dropdown-wrapper group" data-attribute-name="<?php echo esc_attr( $attribute_name ); ?>">
					<!-- Label Removed as requested -->
					<div class="value relative">
						<?php
							wc_dropdown_variation_attribute_options(
								array(
									'options'   => $options,
									'attribute' => $attribute_name,
									'product'   => $product,
									'class'     => 'hidden-original-select opacity-0 absolute w-0 h-0 pointer-events-none',
								)
							);
							/**
							 * Filters the reset variation button.
							 *
							 * @since 2.5.0
							 *
							 * @param string  $button The reset variation button HTML.
							 */
							// Note: Reset link is output below after all variations
						?>

                        <!-- Flowbite Dropdown Trigger -->
                        <button id="<?php echo esc_attr( $dropdown_id ); ?>_button"
                                data-dropdown-toggle="<?php echo esc_attr( $dropdown_id ); ?>"
                                class="ats-dropdown-trigger text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-50 focus:ring-2 focus:ring-primary-600 font-medium rounded-lg text-sm px-5 py-2.5 inline-flex items-center justify-between w-full"
                                type="button">
                            <span class="dropdown-selected-text"><?php esc_html_e( 'Choose an option', 'woocommerce' ); ?></span>
                            <svg class="w-2.5 h-2.5 ml-2.5 transform group-hover:rotate-180 transition-transform" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                        </button>

                        <!-- Flowbite Dropdown Menu -->
                        <div id="<?php echo esc_attr( $dropdown_id ); ?>" class="absolute z-50 hidden bg-ats-dark divide-y divide-gray-700 rounded-lg shadow-lg w-full mt-1 max-h-60 overflow-y-auto">
                            <ul class="py-2 text-sm text-white dropdown-options-list" aria-labelledby="<?php echo esc_attr( $dropdown_id ); ?>_button">
                                <!-- Options will be populated by JS from the select -->
                            </ul>
                        </div>
					</div>
				</div>
			<?php endforeach; ?>

            <div class="variation-reset-link hidden mt-2">
                <?php echo wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations text-xs text-red-600 underline" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear selection', 'woocommerce' ) . '</a>' ) ); ?>
            </div>

		</div>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>


		<div class="single_variation_wrap">
			<?php
				/**
				 * Hook: woocommerce_before_single_variation.
				 */
				do_action( 'woocommerce_before_single_variation' );

				/**
				 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
				 *
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
				 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
				 */
				do_action( 'woocommerce_single_variation' );

				/**
				 * Hook: woocommerce_after_single_variation.
				 */
				do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
        <style>
             /* Force flex layout for variation add to cart form */
            .woocommerce-variation-add-to-cart.variations_button {
                display: flex !important;
                align-items: flex-end;
                gap: 1rem;
            }
        </style>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
