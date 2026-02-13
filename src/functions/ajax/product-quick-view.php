<?php
/**
 * Product Quick View AJAX Handler
 *
 * Handles fetching product information for quick view modal
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register AJAX actions for product quick view
 */
add_action( 'wp_ajax_ats_product_quick_view', 'ats_handle_product_quick_view' );
add_action( 'wp_ajax_nopriv_ats_product_quick_view', 'ats_handle_product_quick_view' );

/**
 * Handle product quick view AJAX request
 *
 * @return void
 */
function ats_handle_product_quick_view() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_product_quick_view' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'skylinewp-dev-child' ),
            ),
            403
        );
    }

    // Validate product ID
    if ( ! isset( $_POST['product_id'] ) || empty( $_POST['product_id'] ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Product ID is required.', 'skylinewp-dev-child' ),
            ),
            400
        );
    }

    $product_id = absint( $_POST['product_id'] );

    if ( $product_id <= 0 ) {
        wp_send_json_error(
            array(
                'message' => __( 'Invalid product ID.', 'skylinewp-dev-child' ),
            ),
            400
        );
    }

    // Get the product
    $product = wc_get_product( $product_id );

    if ( ! $product || $product->get_status() !== 'publish' ) {
        wp_send_json_error(
            array(
                'message' => __( 'Product not found.', 'skylinewp-dev-child' ),
            ),
            404
        );
    }

    // Generate product HTML
    ob_start();
    ats_render_product_quick_view( $product );
    $html = ob_get_clean();

    wp_send_json_success(
        array(
            'html' => $html,
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
        )
    );
}

/**
 * Render product gallery for quick view
 *
 * @param WC_Product $product Product object.
 * @return void
 */
function ats_render_quick_view_product_gallery( $product ) {
    $post_thumbnail_id = $product->get_image_id();
    $attachment_ids = $product->get_gallery_image_ids();

    // Create a unified list of images: Main Image + Gallery Images
    if ( $post_thumbnail_id ) {
        array_unshift( $attachment_ids, $post_thumbnail_id );
    }

    // Ensure unique images
    $attachment_ids = array_unique( $attachment_ids );

    if ( empty( $attachment_ids ) ) {
        // Placeholder fallback
        $placeholder = wc_placeholder_img_src( 'woocommerce_single' );
        echo '<div class="woocommerce-product-gallery"><img src="' . esc_url( $placeholder ) . '" alt="Awaiting product image" class="wp-post-image" /></div>';
        return;
    }

    $has_multiple_images = count( $attachment_ids ) > 1;
    ?>

    <div class="woocommerce-product-gallery custom-splide-gallery relative group">
        <!-- Main Slider -->
        <section id="product-main-splide" class="rfs-ref-product-main-slider splide rounded-lg overflow-hidden mb-4" aria-label="Product Images">
            <div class="splide__track">
                <ul class="splide__list">
                    <?php foreach ( $attachment_ids as $attachment_id ) :
                        $full_src  = wp_get_attachment_image_url( $attachment_id, 'full' );
                        $alt_text  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                        $meta      = wp_get_attachment_metadata( $attachment_id );
                        $orig_w    = $meta['width'] ?? 0;
                        $orig_h    = $meta['height'] ?? 0;
                        $is_square = ( $orig_w > 0 && $orig_h > 0 && abs( $orig_w - $orig_h ) <= 10 );

                        if ( $is_square ) {
                            $img_1x    = wpimage( image: $attachment_id, size: [542, 542], quality: 85 );
                            $img_2x    = wpimage( image: $attachment_id, size: [542, 542], retina: true, quality: 85 );
                            $img_class = 'rfs-ref-product-main-image w-full h-full object-cover';
                        } else {
                            $img_1x    = wpimage( image: $attachment_id, size: 542, quality: 85 );
                            $img_2x    = wpimage( image: $attachment_id, size: 542, retina: true, quality: 85 );
                            $img_class = 'rfs-ref-product-main-image max-w-full max-h-full object-contain';
                        }
                    ?>
                        <li class="rfs-ref-product-slide splide__slide aspect-square" data-image-id="<?php echo esc_attr( $attachment_id ); ?>">
                            <a href="<?php echo esc_url( $full_src ); ?>" class="rfs-ref-product-lightbox-trigger product-gallery-lightbox-trigger flex items-center justify-center w-full h-full cursor-zoom-in">
                                <img src="<?php echo esc_url( $img_1x ); ?>"
                                     srcset="<?php echo esc_url( $img_1x ); ?> 1x, <?php echo esc_url( $img_2x ); ?> 2x"
                                     alt="<?php echo esc_attr( $alt_text ); ?>"
                                     <?php echo ( $attachment_id === $attachment_ids[0] ) ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
                                     class="<?php echo esc_attr( $img_class ); ?>">
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ( $has_multiple_images ) : ?>
            <!-- Main Slider Arrows (hover-visible) -->
            <div class="rfs-ref-product-main-arrows splide__arrows">
                <button class="splide__arrow splide__arrow--prev !absolute !left-2 !top-1/2 !-translate-y-1/2 !w-10 !h-10 !bg-white/80 hover:!bg-white !rounded-full !shadow-md !opacity-0 group-hover:!opacity-100 !transition-opacity !duration-200 !z-10">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#434343" style="transform: rotate(180deg);"><path d="M521.33-480.67 328-674l47.33-47.33L616-480.67 375.33-240 328-287.33l193.33-193.34Z"/></svg>
                </button>
                <button class="splide__arrow splide__arrow--next !absolute !right-2 !top-1/2 !-translate-y-1/2 !w-10 !h-10 !bg-white/80 hover:!bg-white !rounded-full !shadow-md !opacity-0 group-hover:!opacity-100 !transition-opacity !duration-200 !z-10">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#434343"><path d="M521.33-480.67 328-674l47.33-47.33L616-480.67 375.33-240 328-287.33l193.33-193.34Z"/></svg>
                </button>
            </div>
            <?php endif; ?>
        </section>

        <?php if ( $has_multiple_images ) : ?>
            <!-- Thumbnails Slider (only show if multiple images) -->
            <section id="product-thumbnail-splide" class="splide px-12 relative" aria-label="Product Thumbnails">
                <div class="splide__track">
                    <ul class="splide__list">
                        <?php foreach ( $attachment_ids as $attachment_id ) : ?>
                            <li class="splide__slide opacity-80 transition-opacity [&.is-active]:opacity-100 border border-transparent rounded cursor-pointer overflow-hidden" data-image-id="<?php echo esc_attr( $attachment_id ); ?>">
                                <img decoding="async" src="<?php echo wpimage( image: $attachment_id, size: [95, 95] ); ?>" srcset="<?php echo wpimage( image: $attachment_id, size: [95, 95] ); ?> 1x, <?php echo wpimage( image: $attachment_id, size: [95, 95], retina: true ); ?> 2x" class="w-full h-16 sm:h-24 object-cover">
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Custom Arrows -->
                <div class="splide__arrows">
                    <button class="splide__arrow splide__arrow--prev !bg-transparent !w-10 !h-10 !-left-4 !shadow-none !opacity-100 hover:!opacity-70">
                        <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#434343" style="transform: rotate(180deg);"><path d="M521.33-480.67 328-674l47.33-47.33L616-480.67 375.33-240 328-287.33l193.33-193.34Z"/></svg>
                    </button>
                    <button class="splide__arrow splide__arrow--next !bg-transparent !w-10 !h-10 !-right-4 !shadow-none !opacity-100 hover:!opacity-70">
                        <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#434343"><path d="M521.33-480.67 328-674l47.33-47.33L616-480.67 375.33-240 328-287.33l193.33-193.34Z"/></svg>
                    </button>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <?php
}

/**
 * Render product quick view HTML
 *
 * @param WC_Product $product Product object.
 * @return void
 */
function ats_render_product_quick_view( $product ) {
    global $post;

    $product_id = $product->get_id();

    // Set up global post and product for WooCommerce templates
    $post = get_post( $product_id );
    setup_postdata( $post );

    // Make product available globally for WooCommerce hooks
    $GLOBALS['product'] = $product;
    ?>
    <div class="rfs-ref-quick-view-content grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Left Column: Product Images -->
        <div class="rfs-ref-quick-view-images">
            <?php ats_render_quick_view_product_gallery( $product ); ?>
        </div>

        <!-- Right Column: Product Summary -->
        <div class="rfs-ref-quick-view-summary">
            <div class="space-y-4">
                <!-- Title -->
                <h2 class="rfs-ref-quick-view-title text-2xl font-bold text-gray-900">
                    <?php echo esc_html( $product->get_name() ); ?>
                </h2>

                <!-- Reviews (Star Rating) -->
                <div class="rfs-ref-quick-view-rating flex items-center gap-2 mb-2">
                    <?php echo ats_get_star_rating_html( $product->get_average_rating(), $product->get_review_count() ); ?>
                </div>

                <!-- Short Description -->
                <?php if ( $product->get_short_description() ) : ?>
                    <div class="rfs-ref-quick-view-description text-sm text-gray-600 prose prose-sm max-w-none">
                        <?php echo wp_kses_post( $product->get_short_description() ); ?>
                    </div>
                <?php endif; ?>

                <div class="h-px bg-gray-200 my-6"></div>

                <!-- Meta Data Grid -->
                <div class="rfs-ref-quick-view-meta grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-gray-500 border-b border-gray-200 pb-6 mb-6">

                    <!-- Availability -->
                    <div class="flex flex-col gap-1">
                        <span><?php esc_html_e( 'Availability:', 'woocommerce' ); ?></span>
                        <span class="font-bold <?php echo $product->is_in_stock() ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php
                            if ( $product->is_in_stock() ) {
                                $stock_quantity = $product->get_stock_quantity();
                                if ( $stock_quantity ) {
                                    echo sprintf( esc_html__( '%s in stock', 'woocommerce' ), $stock_quantity );
                                } else {
                                    esc_html_e( 'In Stock', 'woocommerce' );
                                }
                            } else {
                                esc_html_e( 'Out of Stock', 'woocommerce' );
                            }
                            ?>
                        </span>
                    </div>

                    <!-- Brand -->
                    <div class="flex flex-col gap-1">
                        <span><?php esc_html_e( 'Brand:', 'woocommerce' ); ?></span>
                        <span class="font-bold text-gray-900">
                            <?php
                            $brand = get_the_term_list( $product_id, 'pwb-brand', '', ', ', '' );
                            if ( ! is_wp_error( $brand ) && ! empty( $brand ) ) {
                                echo wp_kses_post( $brand );
                            } else {
                                echo 'ATS Diamond Tools';
                            }
                            ?>
                        </span>
                    </div>

                    <!-- SKU -->
                    <div class="flex flex-col gap-1">
                        <span><?php esc_html_e( 'SKU:', 'woocommerce' ); ?></span>
                        <span class="font-bold text-gray-900"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'Per Variation', 'woocommerce' ); ?></span>
                    </div>
                </div>

                <!-- Application Types as Buttons (Wood/Metal/Stone) -->
                <?php
                $apps = get_the_terms( $product_id, 'product_application' );
                if ( $apps && ! is_wp_error( $apps ) ) : ?>
                    <div class="rfs-ref-quick-view-applications flex gap-2 mb-6">
                        <?php foreach ( $apps as $app ) : ?>
                            <span class="inline-flex items-center px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wide bg-gray-200 text-gray-800">
                                <?php echo esc_html( $app->name ); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="rfs-ref-quick-view-price py-4">
                    <p class="text-2xl font-bold text-gray-900 flex items-baseline gap-2">
                        <?php echo ats_get_product_price_html( $product ); ?>
                    </p>
                </div>

                <!-- Add to Cart Form -->
                <div class="rfs-ref-quick-view-cart ats-product-form">
                    <?php woocommerce_template_single_add_to_cart(); ?>
                </div>

            </div>
        </div>
    </div>
    <?php
    // Reset post data
    wp_reset_postdata();
}

/**
 * Add product quick view nonce to themeData localization
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_product_quick_view_nonce( $scripts_localize ) {
    $scripts_localize['product_quick_view_nonce'] = wp_create_nonce( 'ats_product_quick_view' );
    return $scripts_localize;
}
add_filter( 'skyline_child_localizes', 'ats_add_product_quick_view_nonce' );
