<?php
/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 6.1.0
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
							wc_dropdown_variation_attribute_options( array(
								'options'   => $options,
								'attribute' => $attribute_name,
								'product'   => $product,
                                'class'     => 'hidden-original-select opacity-0 absolute w-0 h-0 pointer-events-none' // Hide but keep accessible? Or display:none via class.
							) );
                            // Also output the clear link hiddenly or standard way?
                            // Standard WC usually puts it after the select.
						?>

                        <!-- Flowbite Dropdown Trigger -->
                        <button id="<?php echo esc_attr($dropdown_id); ?>_button"
                                data-dropdown-toggle="<?php echo esc_attr($dropdown_id); ?>"
                                class="ats-dropdown-trigger text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-50 focus:ring-2 focus:ring-primary-600 font-medium rounded-lg text-sm px-5 py-2.5 inline-flex items-center justify-between w-full"
                                type="button">
                            <span class="dropdown-selected-text"><?php esc_html_e( 'Choose an option', 'woocommerce' ); ?></span>
                            <svg class="w-2.5 h-2.5 ml-2.5 transform group-hover:rotate-180 transition-transform" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                        </button>

                        <!-- Flowbite Dropdown Menu -->
                        <div id="<?php echo esc_attr($dropdown_id); ?>" class="z-20 hidden bg-ats-brand divide-y divide-gray-100 rounded-lg shadow w-full">
                            <ul class="py-2 text-sm text-white dropdown-options-list" aria-labelledby="<?php echo esc_attr($dropdown_id); ?>_button">
                                <!-- Options will be populated by JS from the select -->
                            </ul>
                        </div>
					</div>
				</div>
			<?php endforeach; ?>

            <div class="variation-reset-link hidden mt-2">
                <?php echo wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations text-xs text-red-600 underline" href="#">' . esc_html__( 'Clear selection', 'woocommerce' ) . '</a>' ) ); ?>
            </div>

		</div>

		<div class="single_variation_wrap">
			<?php
				/**
				 * Hook: woocommerce_before_single_variation.
				 */
				   do_action( 'woocommerce_before_single_variation' );

                // Add flex wrapper logic via CSS or wrapper here.
                // Since woocommerce_single_variation outputs the button, we can wrap it or style the button form if we can target it.
                // The hook outputs `woocommerce_single_variation_add_to_cart_button`.
				/**
				 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10
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
