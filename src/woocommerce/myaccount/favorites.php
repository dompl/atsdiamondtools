<?php
/**
 * My Account - Favorites
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

$user_id = get_current_user_id();
$favorites = get_user_meta( $user_id, 'ats_favorite_products', true );

if ( ! is_array( $favorites ) ) {
	$favorites = array();
}

// Remove any invalid product IDs
$favorites = array_filter( $favorites, function( $product_id ) {
	$product = wc_get_product( $product_id );
	return $product && $product->exists();
} );

?>

<div class="rfs-ref-favorites-page woocommerce-MyAccount-content">
	<?php if ( empty( $favorites ) ) : ?>
		<div class="rfs-ref-no-favorites woocommerce-message woocommerce-message--info bg-ats-gray border border-gray-200 rounded-lg p-8 text-center">
			<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#9CA3AF" class="mx-auto mb-4 opacity-50">
				<path d="m480-120-58-52q-101-91-167-157T150-447.5Q111-500 95.5-544T80-634q0-94 63-157t157-63q52 0 99 22t81 62q34-40 81-62t99-22q94 0 157 63t63 157q0 46-15.5 90T810-447.5Q771-395 705-329T538-172l-58 52Z"/>
			</svg>
			<h3 class="text-xl font-semibold text-ats-dark mb-2"><?php esc_html_e( 'No Favorite Products Yet', 'skylinewp-dev-child' ); ?></h3>
			<p class="text-ats-text mb-4"><?php esc_html_e( 'Start adding products to your favorites by clicking the heart icon on any product.', 'skylinewp-dev-child' ); ?></p>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="ats-btn ats-btn-yellow ats-btn-md">
				<?php esc_html_e( 'Browse Products', 'skylinewp-dev-child' ); ?>
			</a>
		</div>
	<?php else : ?>
		<div class="rfs-ref-favorites-intro mb-6">
			<p class="text-ats-text">
				<?php
				printf(
					/* translators: %s: number of favorite products */
					esc_html( _n( 'You have %s favorite product.', 'You have %s favorite products.', count( $favorites ), 'skylinewp-dev-child' ) ),
					'<strong>' . count( $favorites ) . '</strong>'
				);
				?>
			</p>
		</div>

		<div class="rfs-ref-favorites-list space-y-4">
			<?php foreach ( $favorites as $product_id ) : ?>
				<?php
				$product = wc_get_product( $product_id );
				if ( ! $product || ! $product->exists() ) {
					continue;
				}

				$is_variable = $product->is_type( 'variable' );
				$thumbnail = $product->get_image_id() ? wp_get_attachment_image( $product->get_image_id(), array( 80, 80 ), false, array( 'class' => 'w-full h-full object-contain' ) ) : '';
				?>
				<div class="rfs-ref-favorite-item bg-white border border-gray-200 rounded-lg p-4 hover:border-ats-yellow transition-colors" data-product-id="<?php echo esc_attr( $product_id ); ?>">
					<div class="flex gap-4 items-center">
						<!-- Product Image -->
						<div class="rfs-ref-favorite-image flex-shrink-0">
							<div class="w-20 h-20 rounded border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
								<?php if ( $thumbnail ) : ?>
									<?php echo wp_kses_post( $thumbnail ); ?>
								<?php else : ?>
									<span class="text-xs text-gray-400"><?php esc_html_e( 'No image', 'skylinewp-dev-child' ); ?></span>
								<?php endif; ?>
							</div>
						</div>

						<!-- Product Details -->
						<div class="rfs-ref-favorite-details flex-grow min-w-0">
							<h3 class="rfs-ref-favorite-title text-sm font-medium text-ats-dark mb-1 line-clamp-1">
								<?php echo esc_html( $product->get_name() ); ?>
							</h3>

							<!-- Price -->
							<div class="rfs-ref-favorite-price text-base font-semibold text-ats-dark mb-1">
								<?php
								if ( $product->is_type( 'variable' ) ) {
									$min_price = $product->get_variation_price( 'min', true );
									echo wp_kses_post( sprintf( __( 'From %s', 'skylinewp-dev-child' ), wc_price( $min_price ) ) );
								} else {
									echo wp_kses_post( $product->get_price_html() );
								}
								?>
							</div>

							<!-- Stock Status -->
							<?php if ( $product->is_in_stock() ) : ?>
								<p class="rfs-ref-favorite-stock text-xs text-green-600">
									<?php esc_html_e( 'In stock', 'skylinewp-dev-child' ); ?>
								</p>
							<?php else : ?>
								<p class="rfs-ref-favorite-stock text-xs text-red-600">
									<?php esc_html_e( 'Out of stock', 'skylinewp-dev-child' ); ?>
								</p>
							<?php endif; ?>
						</div>

						<!-- Actions -->
						<div class="rfs-ref-favorite-actions flex items-center gap-2 flex-shrink-0">
							<?php if ( $is_variable ) : ?>
								<!-- View Product button for variable products (opens quick view modal) -->
								<button type="button"
									class="ats-expand-product px-4 py-2 text-sm font-medium bg-ats-yellow text-ats-dark rounded hover:bg-ats-yellow/90 transition-colors"
									data-product-id="<?php echo esc_attr( $product_id ); ?>">
									<?php esc_html_e( 'View Product', 'skylinewp-dev-child' ); ?>
								</button>
							<?php else : ?>
								<!-- Add to Cart button for simple products -->
								<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
									<button type="button"
										class="rfs-ref-favorite-add-to-cart px-4 py-2 text-sm font-medium bg-ats-yellow text-ats-dark rounded hover:bg-ats-yellow/90 transition-colors"
										data-product-id="<?php echo esc_attr( $product_id ); ?>">
										<?php esc_html_e( 'Add to Cart', 'skylinewp-dev-child' ); ?>
									</button>
								<?php endif; ?>
							<?php endif; ?>

							<!-- Remove from favorites button -->
							<button type="button"
								class="ats-remove-favorite p-2 text-red-500 hover:bg-red-50 rounded transition-colors"
								data-product-id="<?php echo esc_attr( $product_id ); ?>"
								title="<?php esc_attr_e( 'Remove from favorites', 'skylinewp-dev-child' ); ?>">
								<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
									<path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/>
								</svg>
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
