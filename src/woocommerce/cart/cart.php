<?php
/**
 * Cart Page
 *
 * Styled with Tailwind CSS and AJAX functionality
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' ); ?>

<div class="rfs-ref-cart-page woocommerce-cart-page">
	<div class="rfs-ref-cart-container container mx-auto px-4 py-8 max-w-7xl">
		<form class="rfs-ref-cart-form woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<!-- Headers Row -->
		<div class="rfs-ref-cart-headers grid grid-cols-1 lg:grid-cols-3 gap-8 mb-4">
			<div class="lg:col-span-2">
				<div class="rfs-ref-cart-header flex items-center justify-between">
					<h2 class="rfs-ref-cart-title text-xl font-semibold text-ats-dark">Shopping Cart</h2>
					<span class="rfs-ref-cart-count text-xs text-ats-text">
						<?php
						$cart_count = WC()->cart->get_cart_contents_count();
						/* translators: %s: number of items */
						printf( esc_html( _n( '%s item', '%s items', $cart_count, 'woocommerce' ) ), esc_html( $cart_count ) );
						?>
					</span>
				</div>
			</div>
			<div class="lg:col-span-1">
				<h2 class="rfs-ref-cart-totals-title text-xl font-semibold text-ats-dark">Cart Totals</h2>
			</div>
		</div>

		<!-- Content Row -->
		<div class="rfs-ref-cart-layout grid grid-cols-1 lg:grid-cols-3 gap-8">

			<!-- Cart Items Section -->
			<div class="rfs-ref-cart-items lg:col-span-2">

				<div class="rfs-ref-cart-items-list space-y-4">
					<?php do_action( 'woocommerce_before_cart_contents' ); ?>

					<?php
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
						$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );

						if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
							$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
							?>
							<div class="rfs-ref-cart-item woocommerce-cart-form__cart-item bg-white border border-gray-200 rounded-lg p-3 hover:shadow-sm transition-shadow <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
								<div class="rfs-ref-cart-item-content flex gap-3">

									<!-- Product Image -->
									<div class="rfs-ref-cart-item-image flex-shrink-0">
										<?php
										$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );

										if ( ! $product_permalink ) {
											echo '<div class="w-20 h-20 rounded-lg overflow-hidden border border-gray-200">' . $thumbnail . '</div>';
										} else {
											echo '<a href="' . esc_url( $product_permalink ) . '" class="block w-20 h-20 rounded-lg overflow-hidden border border-gray-200 hover:border-ats-yellow transition-colors">' . $thumbnail . '</a>';
										}
										?>
									</div>

									<!-- Product Details -->
									<div class="rfs-ref-cart-item-details flex-grow min-w-0">
										<div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
											<div class="rfs-ref-cart-item-info flex-grow">
												<!-- Product Name -->
												<h3 class="rfs-ref-cart-item-name text-sm font-medium text-ats-dark mb-1">
													<?php
													if ( ! $product_permalink ) {
														echo wp_kses_post( $product_name );
													} else {
														echo wp_kses_post( sprintf( '<a href="%s" class="hover:text-ats-yellow transition-colors">%s</a>', esc_url( $product_permalink ), $_product->get_name() ) );
													}
													?>
												</h3>

												<?php
												do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

												// Meta data
												echo '<div class="rfs-ref-cart-item-meta text-xs text-ats-text">';
												echo wc_get_formatted_cart_item_data( $cart_item );
												echo '</div>';

												// Backorder notification
												if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
													echo '<p class="rfs-ref-cart-item-backorder backorder_notification text-xs text-ats-yellow mt-1">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>';
												}
												?>

												<!-- Price (Mobile) -->
												<div class="rfs-ref-cart-item-price-mobile md:hidden mt-1">
													<span class="text-xs text-ats-text"><?php esc_html_e( 'Price:', 'woocommerce' ); ?></span>
													<span class="text-sm font-semibold text-ats-dark ml-1">
														<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); ?>
													</span>
												</div>
											</div>

											<!-- Price (Desktop) -->
											<div class="rfs-ref-cart-item-price hidden md:block text-right">
												<span class="text-base font-semibold text-ats-dark">
													<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); ?>
												</span>
											</div>
										</div>

										<!-- Quantity and Actions Row -->
										<div class="rfs-ref-cart-item-actions flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
											<!-- Quantity Controls -->
											<div class="rfs-ref-cart-item-quantity flex items-center gap-2">
												<span class="text-xs text-ats-text"><?php esc_html_e( 'Qty:', 'woocommerce' ); ?></span>
												<div class="rfs-ref-quantity-controls flex items-center gap-0.5">
													<?php
													if ( $_product->is_sold_individually() ) {
														$min_quantity = 1;
														$max_quantity = 1;
													} else {
														$min_quantity = 1;
														$max_quantity = $_product->get_max_purchase_quantity();
													}
													?>
													<button type="button"
														class="ats-qty-decrease p-1 text-ats-text hover:text-ats-dark transition-colors focus:outline-none disabled:opacity-30 disabled:cursor-not-allowed"
														data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>"
														<?php echo ( $cart_item['quantity'] <= $min_quantity ) ? 'disabled' : ''; ?>>
														<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
													</button>
													<input type="number"
														class="ats-qty-input w-12 px-2 py-1 text-sm text-center border-0 focus:outline-none focus:ring-0"
														name="cart[<?php echo esc_attr( $cart_item_key ); ?>][qty]"
														value="<?php echo esc_attr( $cart_item['quantity'] ); ?>"
														min="<?php echo esc_attr( $min_quantity ); ?>"
														max="<?php echo esc_attr( $max_quantity ); ?>"
														step="1"
														data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>"
														aria-label="<?php esc_attr_e( 'Product quantity', 'woocommerce' ); ?>" />
													<button type="button"
														class="ats-qty-increase p-1 text-ats-text hover:text-ats-dark transition-colors focus:outline-none disabled:opacity-30 disabled:cursor-not-allowed"
														data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>"
														<?php echo ( $max_quantity > 0 && $cart_item['quantity'] >= $max_quantity ) ? 'disabled' : ''; ?>>
														<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
													</button>
												</div>
											</div>

											<!-- Subtotal and Remove -->
											<div class="rfs-ref-cart-item-right flex items-center gap-3">
												<!-- Subtotal -->
												<div class="rfs-ref-cart-item-subtotal text-right">
													<div class="text-xs text-ats-text"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></div>
													<div class="text-sm font-semibold text-ats-dark">
														<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
													</div>
												</div>

												<!-- Remove Button -->
												<button type="button"
													class="ats-remove-item rfs-ref-cart-item-remove p-1.5 text-red-500 hover:bg-red-50 rounded transition-colors focus:outline-none focus:ring-1 focus:ring-red-500"
													data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>"
													data-product-id="<?php echo esc_attr( $product_id ); ?>"
													data-product-sku="<?php echo esc_attr( $_product->get_sku() ); ?>"
													aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ); ?>">
													<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
														<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
													</svg>
												</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							<?php
						}
					}
					?>

					<?php do_action( 'woocommerce_cart_contents' ); ?>
				</div>

				<!-- Coupon Code Section -->
				<?php if ( wc_coupons_enabled() ) : ?>
					<div class="rfs-ref-cart-coupon bg-white border border-gray-200 rounded-lg p-4 mt-4">
						<h3 class="rfs-ref-coupon-title text-sm font-medium text-ats-dark mb-3">
							<?php esc_html_e( 'Have a coupon code?', 'woocommerce' ); ?>
						</h3>
						<div class="rfs-ref-coupon-form flex flex-col sm:flex-row gap-2">
							<input type="text"
								name="coupon_code"
								class="ats-coupon-input flex-grow px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-ats-yellow focus:border-ats-yellow transition-colors"
								id="coupon_code"
								value=""
								placeholder="<?php esc_attr_e( 'Enter coupon code', 'woocommerce' ); ?>" />
							<button type="button"
								class="ats-apply-coupon ats-btn ats-btn-md ats-btn-yellow px-4 py-2 text-sm rounded font-medium transition-colors"
								name="apply_coupon">
								<?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?>
							</button>
							<?php do_action( 'woocommerce_cart_coupon' ); ?>
						</div>
						<div class="rfs-ref-coupon-message mt-3 hidden"></div>
					</div>
				<?php endif; ?>

				<?php do_action( 'woocommerce_after_cart_contents' ); ?>
				<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			</div>

			<!-- Cart Totals Sidebar -->
			<div class="rfs-ref-cart-sidebar lg:col-span-1">
				<div class="rfs-ref-cart-totals-wrapper sticky top-8">
					<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

					<?php
					/**
					 * Cart collaterals hook.
					 *
					 * @hooked woocommerce_cart_totals - 10
					 */
					do_action( 'woocommerce_cart_collaterals' );
					?>
				</div>

				<?php
				/**
				 * Custom action for cross-sells after cart totals
				 * Separate container from cart totals
				 */
				do_action( 'ats_cart_sidebar_after_totals' );
				?>
			</div>
		</div>

		<?php do_action( 'woocommerce_after_cart_table' ); ?>
		</form>
	</div>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
