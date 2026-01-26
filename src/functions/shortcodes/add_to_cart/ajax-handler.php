<?php
/**
 * Mini Cart AJAX Handlers
 *
 * Handles AJAX requests for the mini cart functionality.
 * Designed to work with cached pages by fetching cart data via JS.
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register AJAX actions for mini cart
 */
add_action( 'wp_ajax_ats_add_to_cart', 'ats_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_ats_add_to_cart', 'ats_ajax_add_to_cart' );

add_action( 'wp_ajax_ats_get_mini_cart', 'ats_ajax_get_mini_cart' );
add_action( 'wp_ajax_nopriv_ats_get_mini_cart', 'ats_ajax_get_mini_cart' );

add_action( 'wp_ajax_ats_update_cart_item', 'ats_ajax_update_cart_item' );
add_action( 'wp_ajax_nopriv_ats_update_cart_item', 'ats_ajax_update_cart_item' );

add_action( 'wp_ajax_ats_remove_cart_item', 'ats_ajax_remove_cart_item' );
add_action( 'wp_ajax_nopriv_ats_remove_cart_item', 'ats_ajax_remove_cart_item' );

/**
 * Add product to cart via AJAX
 *
 * Handles AJAX add to cart requests from single product pages.
 * Supports both simple and variable products.
 *
 * @return void
 */
function ats_ajax_add_to_cart() {
	// Ensure WooCommerce is loaded
	if ( ! function_exists( 'WC' ) ) {
		wp_send_json_error( array( 'error' => __( 'WooCommerce is not available.', 'skylinewp-dev-child' ) ) );
	}

	// Ensure cart is loaded
	if ( is_null( WC()->cart ) ) {
		wc_load_cart();
	}

	// Get product ID
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

	if ( ! $product_id ) {
		wp_send_json_error( array( 'error' => __( 'Invalid product.', 'skylinewp-dev-child' ) ) );
	}

	// Collect variation data if this is a variable product
	$variation = array();
	if ( $variation_id ) {
		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'attribute_' ) === 0 ) {
				$variation[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
			}
		}
	}

	// Add to cart
	$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );

	if ( ! $passed_validation ) {
		wp_send_json_error( array( 'error' => __( 'Product validation failed.', 'skylinewp-dev-child' ) ) );
	}

	// Add the product to cart
	$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

	if ( ! $cart_item_key ) {
		wp_send_json_error( array( 'error' => __( 'Failed to add product to cart.', 'skylinewp-dev-child' ) ) );
	}

	// Calculate cart totals
	WC()->cart->calculate_totals();

	// Get updated cart data
	$cart = WC()->cart;
	$cart_count = $cart->get_cart_contents_count();
	$cart_subtotal = $cart->get_subtotal();
	$cart_total = $cart->get_total( 'edit' );
	$cart_tax = $cart->get_total_tax();

	// Generate cart hash for fragments
	$cart_hash = WC()->cart->get_cart_hash();

	// Prepare fragments for response (similar to WooCommerce's default)
	$fragments = apply_filters(
		'woocommerce_add_to_cart_fragments',
		array(
			'ats_mini_cart_data' => array(
				'count'      => $cart_count,
				'count_text' => sprintf(
					_n( '%d item', '%d items', $cart_count, 'skylinewp-dev-child' ),
					$cart_count
				),
				'subtotal'   => wc_price( $cart_subtotal ),
				'total'      => wc_price( $cart_total ),
				'tax'        => wc_price( $cart_tax ),
				'is_empty'   => $cart->is_empty(),
			),
		)
	);

	// Send success response
	wp_send_json_success(
		array(
			'fragments'  => $fragments,
			'cart_hash'  => $cart_hash,
			'cart_count' => $cart_count,
		)
	);
}

/**
 * Get mini cart data via AJAX
 *
 * Returns cart count, totals, and item list for cached pages
 *
 * @return void
 */
function ats_ajax_get_mini_cart() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_mini_cart_nonce' ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ),
            403
        );
    }

    // Ensure WooCommerce cart is loaded
    if ( is_null( WC()->cart ) ) {
        wc_load_cart();
    }

    $cart = WC()->cart;

    // Get cart data
    $cart_count    = $cart->get_cart_contents_count();
    $cart_subtotal = $cart->get_subtotal();
    $cart_total    = $cart->get_total( 'edit' );
    $cart_tax      = $cart->get_total_tax();

    // Build cart items array
    $cart_items = array();

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        $product = $cart_item['data'];

        if ( ! $product || ! $product->exists() ) {
            continue;
        }

        // Get product image
        $image_id  = $product->get_image_id();
        $image_url = '';
        if ( $image_id ) {
            // Use wpimage if available, fallback to standard
            if ( function_exists( 'wpimage' ) ) {
                $image_url = wpimage( $image_id, array( 80, 80 ), false, true, true );
            } else {
                $image_src = wp_get_attachment_image_src( $image_id, array( 80, 80 ) );
                $image_url = $image_src ? $image_src[0] : wc_placeholder_img_src();
            }
        } else {
            $image_url = wc_placeholder_img_src();
        }

        // Get product details
        $item_subtotal = $cart->get_product_subtotal( $product, $cart_item['quantity'] );

        $cart_items[] = array(
            'key'          => $cart_item_key,
            'product_id'   => $product->get_id(),
            'name'         => $product->get_name(),
            'quantity'     => $cart_item['quantity'],
            'price'        => wc_price( $product->get_price() ),
            'subtotal'     => $item_subtotal,
            'subtotal_raw' => $cart_item['line_subtotal'],
            'image'        => $image_url,
            'permalink'    => $product->get_permalink(),
            'max_qty'      => $product->get_stock_quantity() ? $product->get_stock_quantity() : 999,
            'sold_individually' => $product->is_sold_individually(),
        );
    }

    // Build response
    $response = array(
        'count'         => $cart_count,
        'count_text'    => sprintf(
            _n( '%d item', '%d items', $cart_count, 'skylinewp-dev-child' ),
            $cart_count
        ),
        'subtotal'      => wc_price( $cart_subtotal ),
        'subtotal_raw'  => $cart_subtotal,
        'total'         => wc_price( $cart_total ),
        'total_raw'     => $cart_total,
        'tax'           => wc_price( $cart_tax ),
        'tax_raw'       => $cart_tax,
        'items'         => $cart_items,
        'cart_url'      => wc_get_cart_url(),
        'checkout_url'  => wc_get_checkout_url(),
        'shop_url'      => wc_get_page_permalink( 'shop' ),
        'is_empty'      => $cart->is_empty(),
        'items_html'    => ats_get_cart_items_html( $cart_items ),
    );

    wp_send_json_success( $response );
}

/**
 * Generate HTML for cart items in modal
 *
 * @param array $cart_items Array of cart items.
 * @return string HTML markup
 */
function ats_get_cart_items_html( $cart_items ) {
    if ( empty( $cart_items ) ) {
        return '<div class="rfs-ref-mini-cart-empty-message js-mini-cart-empty-message text-center py-8 text-ats-text">' .
               '<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="currentColor" class="mx-auto mb-4 opacity-50"><path d="M280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM246-720l96 200h280l110-200H246Zm-38-80h590q23 0 35 20.5t1 41.5L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68-39.5t-2-78.5l54-98-144-304H40v-80h130l38 80Zm134 280h280-280Z"/></svg>' .
               '<p>' . esc_html__( 'Your basket is empty', 'skylinewp-dev-child' ) . '</p>' .
               '</div>';
    }

    ob_start();
    ?>
    <div class="rfs-ref-mini-cart-items-list js-mini-cart-items-list space-y-4">
        <?php foreach ( $cart_items as $item ) : ?>
				<div class="rfs-ref-mini-cart-item js-mini-cart-item grid grid-cols-12 gap-1 pb-4 border-b border-ats-gray last:border-0" data-cart-key="<?php echo esc_attr( $item['key'] ); ?>">
					<!-- Product Image (col-span-3) -->
					<div class="col-span-2 flex items-center">
						<a href="<?php echo esc_url( $item['permalink'] ); ?>" class="rfs-ref-mini-cart-item-image js-mini-cart-item-image flex-shrink-0">
							<img src="<?php echo esc_url( $item['image'] ); ?>"
								 alt="<?php echo esc_attr( $item['name'] ); ?>"
								 class="w-16 h-16 object-cover rounded border border-ats-gray">
						</a>
					</div>

					<!-- Product Details & Controls (col-span-6) -->
					<div class="rfs-ref-mini-cart-item-details flex flex-col min-w-0 col-span-8">
						<a href="<?php echo esc_url( $item['permalink'] ); ?>" class="rfs-ref-mini-cart-item-name text-sm font-medium text-ats-dark hover:text-ats-yellow transition-colors line-clamp-2">
							<?php echo esc_html( $item['name'] ); ?>
						</a>

						<div class="rfs-ref-mini-cart-item-price text-xs text-ats-text mt-2 mb-1">
							<?php echo wp_kses_post( $item['price'] ); ?> <?php esc_html_e( 'each', 'skylinewp-dev-child' ); ?>
						</div>

						<!-- Quantity Controls -->
						<div class="rfs-ref-mini-cart-item-qty flex items-center gap-2 mt-2">
							<?php if ( ! $item['sold_individually'] ) : ?>
								<div class="flex items-center border border-ats-gray rounded">
									<button type="button"
											class="rfs-ref-qty-decrease js-qty-decrease w-7 h-7 flex items-center justify-center text-ats-text hover:bg-ats-gray transition-colors"
											data-cart-key="<?php echo esc_attr( $item['key'] ); ?>"
											data-action="decrease"
											<?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
										<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="currentColor">
											<path d="M200-440v-80h560v80H200Z"/>
										</svg>
									</button>
									<span class="rfs-ref-qty-value js-qty-value w-10 text-center text-sm font-medium"><?php echo esc_html( $item['quantity'] ); ?></span>
									<button type="button"
											class="rfs-ref-qty-increase js-qty-increase w-7 h-7 flex items-center justify-center text-ats-text hover:bg-ats-gray transition-colors"
											data-cart-key="<?php echo esc_attr( $item['key'] ); ?>"
											data-action="increase"
											<?php echo $item['quantity'] >= $item['max_qty'] ? 'disabled' : ''; ?>>
										<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="currentColor">
											<path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
										</svg>
									</button>
								</div>
							<?php else : ?>
								<span class="text-sm text-ats-text"><?php esc_html_e( 'Qty:', 'skylinewp-dev-child' ); ?> <?php echo esc_html( $item['quantity'] ); ?></span>
							<?php endif; ?>

							<!-- Remove Button -->
							<button type="button"
									class="rfs-ref-remove-item js-remove-item ml-auto text-ats-text hover:text-ats-yellow transition-colors"
									data-cart-key="<?php echo esc_attr( $item['key'] ); ?>"
									title="<?php esc_attr_e( 'Remove item', 'skylinewp-dev-child' ); ?>">
								<svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="18px" fill="currentColor">
									<path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/>
								</svg>
							</button>
						</div>
					</div>

					<!-- Item Subtotal (col-span-3) -->
					<div class="rfs-ref-mini-cart-item-subtotal text-right flex  justify-end col-span-2">
						<span class="text-sm font-semibold text-ats-dark"><?php echo wp_kses_post( $item['subtotal'] ); ?></span>
					</div>
				</div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Update cart item quantity via AJAX
 *
 * @return void
 */
function ats_ajax_update_cart_item() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_mini_cart_nonce' ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ),
            403
        );
    }

    // Validate inputs
    if ( ! isset( $_POST['cart_key'] ) || ! isset( $_POST['quantity'] ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Missing required parameters.', 'skylinewp-dev-child' ) ),
            400
        );
    }

    $cart_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );
    $quantity = absint( $_POST['quantity'] );

    // Ensure WooCommerce cart is loaded
    if ( is_null( WC()->cart ) ) {
        wc_load_cart();
    }

    // Update cart item
    $result = WC()->cart->set_quantity( $cart_key, $quantity, true );

    if ( $result ) {
        // Recalculate cart totals
        WC()->cart->calculate_totals();

        // Get updated cart data
        wp_send_json_success( ats_get_updated_cart_data() );
    } else {
        wp_send_json_error(
            array( 'message' => __( 'Failed to update cart.', 'skylinewp-dev-child' ) ),
            500
        );
    }
}

/**
 * Remove cart item via AJAX
 *
 * @return void
 */
if ( ! function_exists( 'ats_ajax_remove_cart_item' ) ) {
    function ats_ajax_remove_cart_item() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_mini_cart_nonce' ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ),
                403
            );
        }

        // Validate inputs
        if ( ! isset( $_POST['cart_key'] ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Missing cart item key.', 'skylinewp-dev-child' ) ),
                400
            );
        }

        $cart_key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ) );

        // Ensure WooCommerce cart is loaded
        if ( is_null( WC()->cart ) ) {
            wc_load_cart();
        }

        // Remove cart item
        $result = WC()->cart->remove_cart_item( $cart_key );

        if ( $result ) {
            // Recalculate cart totals
            WC()->cart->calculate_totals();

            // Get updated cart data
            wp_send_json_success( ats_get_updated_cart_data() );
        } else {
            wp_send_json_error(
                array( 'message' => __( 'Failed to remove item.', 'skylinewp-dev-child' ) ),
                500
            );
        }
    }
}

/**
 * Get updated cart data after modification
 *
 * @return array Cart data
 */
function ats_get_updated_cart_data() {
    $cart = WC()->cart;

    $cart_count    = $cart->get_cart_contents_count();
    $cart_subtotal = $cart->get_subtotal();
    $cart_total    = $cart->get_total( 'edit' );
    $cart_tax      = $cart->get_total_tax();

    // Build cart items array
    $cart_items = array();

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        $product = $cart_item['data'];

        if ( ! $product || ! $product->exists() ) {
            continue;
        }

        // Get product image
        $image_id  = $product->get_image_id();
        $image_url = '';
        if ( $image_id ) {
            if ( function_exists( 'wpimage' ) ) {
                $image_url = wpimage( $image_id, array( 80, 80 ), false, true, true );
            } else {
                $image_src = wp_get_attachment_image_src( $image_id, array( 80, 80 ) );
                $image_url = $image_src ? $image_src[0] : wc_placeholder_img_src();
            }
        } else {
            $image_url = wc_placeholder_img_src();
        }

        $item_subtotal = $cart->get_product_subtotal( $product, $cart_item['quantity'] );

        $cart_items[] = array(
            'key'          => $cart_item_key,
            'product_id'   => $product->get_id(),
            'name'         => $product->get_name(),
            'quantity'     => $cart_item['quantity'],
            'price'        => wc_price( $product->get_price() ),
            'subtotal'     => $item_subtotal,
            'subtotal_raw' => $cart_item['line_subtotal'],
            'image'        => $image_url,
            'permalink'    => $product->get_permalink(),
            'max_qty'      => $product->get_stock_quantity() ? $product->get_stock_quantity() : 999,
            'sold_individually' => $product->is_sold_individually(),
        );
    }

    return array(
        'count'         => $cart_count,
        'count_text'    => sprintf(
            _n( '%d item', '%d items', $cart_count, 'skylinewp-dev-child' ),
            $cart_count
        ),
        'subtotal'      => wc_price( $cart_subtotal ),
        'subtotal_raw'  => $cart_subtotal,
        'total'         => wc_price( $cart_total ),
        'total_raw'     => $cart_total,
        'tax'           => wc_price( $cart_tax ),
        'tax_raw'       => $cart_tax,
        'items'         => $cart_items,
        'is_empty'      => $cart->is_empty(),
        'items_html'    => ats_get_cart_items_html( $cart_items ),
    );
}

/**
 * Add mini cart nonce to themeData localization
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_mini_cart_nonce( $scripts_localize ) {
    $scripts_localize['mini_cart_nonce'] = wp_create_nonce( 'ats_mini_cart_nonce' );
    return $scripts_localize;
}
add_filter( 'skyline_child_localizes', 'ats_add_mini_cart_nonce' );

/**
 * Hook into WooCommerce add to cart fragments to update mini cart
 *
 * @param array $fragments Cart fragments.
 * @return array Modified fragments.
 */
function ats_mini_cart_fragments( $fragments ) {
    $cart = WC()->cart;

    $cart_count    = $cart->get_cart_contents_count();
    $cart_subtotal = $cart->get_subtotal();
    $cart_total    = $cart->get_total( 'edit' );
    $cart_tax      = $cart->get_total_tax();

    // Add mini cart data as a fragment
    $fragments['ats_mini_cart_data'] = array(
        'count'        => $cart_count,
        'count_text'   => sprintf(
            _n( '%d item', '%d items', $cart_count, 'skylinewp-dev-child' ),
            $cart_count
        ),
        'subtotal'     => wc_price( $cart_subtotal ),
        'total'        => wc_price( $cart_total ),
        'tax'          => wc_price( $cart_tax ),
        'is_empty'     => $cart->is_empty(),
    );

    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'ats_mini_cart_fragments' );
