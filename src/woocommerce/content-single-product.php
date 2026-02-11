<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}

$product_id = $product->get_id();
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

    <div class="rfs-ref-single-product-container container mx-auto px-4 pb-12 pt-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

            <!-- Main Content Area: Images + Summary + Tabs (Col 9) -->
            <div class="lg:col-span-9 xl:col-span-9">

                <!-- Top Section: Images & Summary Split -->
                <div class="grid grid-cols-1 lg:grid-cols-10 gap-8 mb-12">

                    <!-- Left Column: Product Images (Inner Col 5) -->
                    <div class="lg:col-span-5">
								<div class="mb-10">
                        <?php echo do_shortcode('[category_navigation width="w-full" collapsed=true]'); ?>
								</div>
                        <?php
                        /**
                         * Hook: woocommerce_before_single_product_summary.
                         *
                         * @hooked woocommerce_show_product_images - 20
                         */
                        do_action( 'woocommerce_before_single_product_summary' );
                        ?>
                    </div>

                    <!-- Middle Column: Product Summary (Inner Col 4) -->
                    <div class="lg:col-span-5" id="ats-single-product-info">
                        <div class="space-y-4">
                            <!-- Title with Favorite Button -->
                            <div class="flex items-center justify-between gap-4">
                                <h1 class="single-product-title flex-grow">
                                    <?php the_title(); ?>
                                </h1>
                                <!-- Favorite Button -->
                                <div class="flex-shrink-0">
                                    <?php ats_render_favorite_button( $product_id, 'lg', true ); ?>
                                </div>
                            </div>

                            <!-- Reviews (Star Rating) -->
                            <div class="flex items-center gap-2 mb-2">
                                <?php echo ats_get_star_rating_html( $product->get_average_rating(), $product->get_review_count() ); ?>
                            </div>

                            <!-- Short Description -->
                            <div class="text-sm text-gray-600 prose prose-sm max-w-none">
                                <?php woocommerce_template_single_excerpt(); ?>
                            </div>

                            <div class="h-px bg-gray-200 my-6"></div>

                            <!-- Meta Data Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-gray-500 border-b border-gray-200 pb-6 mb-6">

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
                            // Get selected applications for this product
                            $selected_apps = get_the_terms( $product_id, 'product_application' );
                            $selected_slugs = array();
                            if ( $selected_apps && ! is_wp_error( $selected_apps ) ) {
                                $selected_slugs = wp_list_pluck( $selected_apps, 'slug' );
                            }

                            // Only show application badges if product has applications assigned
                            if ( ! empty( $selected_slugs ) ) :
                                // Get all application types
                                $all_applications = array(
                                    array( 'name' => 'Metal', 'slug' => 'metal' ),
                                    array( 'name' => 'Stone', 'slug' => 'stone' ),
                                    array( 'name' => 'Wood', 'slug' => 'wood' ),
                                );
                            ?>
                            <div class="flex gap-2 mb-6">
                                <?php foreach ( $all_applications as $app ) :
                                    $is_selected = in_array( $app['slug'], $selected_slugs );
                                    $badge_classes = $is_selected
                                        ? 'bg-green-600 text-white'
                                        : 'bg-gray-200 text-gray-400';
                                ?>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wide <?php echo esc_attr( $badge_classes ); ?>">
                                        <?php echo esc_html( $app['name'] ); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Back in Stock Notification Form -->
                            <?php
                            if ( ! $product->is_in_stock() || $product->is_type( 'variable' ) ) {
                                wc_get_template( 'single-product/back-in-stock-form.php' );
                            }
                            ?>

                            <!-- Price + Stock Badge -->
                            <div class="rfs-ref-price-stock-row flex items-center justify-between py-4">
                                <p class="text-2xl font-bold text-gray-900 flex items-baseline gap-2" id="ats-product-main-price">
                                    <?php echo ats_get_product_price_html( $product ); ?>
                                </p>
                                <?php if ( ! $product->is_in_stock() ) : ?>
                                    <span class="rfs-ref-out-of-stock-badge inline-flex items-center px-2.5 py-1 rounded text-[11px] font-semibold uppercase tracking-wide bg-red-700 text-white">
                                        <?php esc_html_e( 'Out of Stock', 'woocommerce' ); ?>
                                    </span>
                                <?php elseif ( $product->is_type( 'variable' ) ) : ?>
                                    <span class="rfs-ref-out-of-stock-badge inline-flex items-center px-2.5 py-1 rounded text-[11px] font-semibold uppercase tracking-wide bg-red-700 text-white hidden">
                                        <?php esc_html_e( 'Out of Stock', 'woocommerce' ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Add to Cart Form -->
                            <div class="ats-product-form">
                                <?php woocommerce_template_single_add_to_cart(); ?>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Bottom Section: Tabs (Inside Main Content) -->
                <div class="border-t border-gray-200 pt-12">
                    <?php woocommerce_output_product_data_tabs(); ?>
                </div>

            </div>

            <!-- Right Column: Related Products (Col 3, spans full height) -->
            <div class="lg:col-span-3 xl:col-span-3 hidden lg:block border-l border-gray-100 pl-8">
                <?php
                // Get related products
                $related_ids = wc_get_related_products( $product_id, 3 );

                // Fallback: If no related products found, get 3 from current category
                if ( empty( $related_ids ) ) {
                    $terms = get_the_terms( $product_id, 'product_cat' );
                    if ( $terms && ! is_wp_error( $terms ) ) {
                        $term_ids = wp_list_pluck( $terms, 'term_id' );
                        $related_ids = get_posts( array(
                            'post_type'      => 'product',
                            'posts_per_page' => 3,
                            'post__not_in'   => array( $product_id ),
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'product_cat',
                                    'field'    => 'term_id',
                                    'terms'    => $term_ids,
                                ),
                            ),
                            'fields'         => 'ids',
                            'orderby'        => 'rand',
                        ) );
                    }
                }

                if ( ! empty( $related_ids ) ) : ?>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between mb-6">
                             <h3 class="text-lg font-bold">
                                <?php esc_html_e( 'Related Products', 'woocommerce' ); ?>
                            </h3>
                        </div>

                        <div class="space-y-8">
                            <?php foreach ( $related_ids as $val_id ) : ?>
                                <div class="w-full">
                                     <?php echo do_shortcode( '[ats_product id="' . $val_id . '" display="1"]' ); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
