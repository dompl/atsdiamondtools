<?php
/**
 * Cross-sells - Related Products on Cart Page
 *
 * Displays related products using the list view layout from shop page
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

if ( $cross_sells ) : ?>

	<div class="rfs-ref-cart-cross-sells cross-sells container mx-auto px-4 mt-12 mb-8">
		<?php
		$heading = apply_filters( 'woocommerce_product_cross_sells_products_heading', __( 'You may also like', 'woocommerce' ) );

		if ( $heading ) :
			?>
			<h2 class="text-2xl font-bold text-ats-dark mb-6"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<div class="rfs-ref-cross-sells-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
			<?php foreach ( $cross_sells as $cross_sell ) : ?>
				<?php
				$product = wc_get_product( $cross_sell->get_id() );
				if ( ! $product || $product->get_status() !== 'publish' ) {
					continue;
				}

				// Get product data (same as product shortcode)
				$product_title = $product->get_name();
				$product_url   = $product->get_permalink();
				$image_id      = get_post_thumbnail_id( $product->get_id() );
				$is_variable   = $product->is_type( 'variable' );

				// Get categories
				$categories    = wp_get_post_terms( $product->get_id(), 'product_cat', ['fields' => 'names'] );
				$category_text = !empty( $categories ) ? implode( ', ', $categories ) : '';

				// Get price with VAT suffix
				$price_html = ats_get_product_price_html( $product );

				// Get rating
				$rating_count   = $product->get_review_count();
				$average_rating = $product->get_average_rating();
				$rating_html    = ats_get_star_rating_html( $average_rating, $rating_count );

				// Button text
				$button_text = $is_variable ? 'Select Size' : 'Add to Cart';

				// Render using list layout (display type 2)
				echo ats_render_product_list( $product, $image_id, $category_text, $product_title, $rating_html, $price_html, $button_text, $product_url );
				?>
			<?php endforeach; ?>
		</div>

	</div>
	<?php
endif;

wp_reset_postdata();
