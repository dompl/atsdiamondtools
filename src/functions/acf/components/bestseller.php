<?php
/**
 * ACF Flex Field: Bestseller Section
 *
 * Displays 2 featured products on the left (vertical stack) and 4 compact products in a 2x2 grid on the right
 * Compact products have image on LEFT, text on RIGHT (horizontal layout)
 *
 * @package ATS Diamond Tools
 */

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\PostObject;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;

function bestseller_fields() {
    return [
        Tab::make( 'Content', wp_unique_id() )->placement( 'left' ),

        Text::make( 'Title', 'title' )
            ->helperText( 'Enter the section title (e.g., "Bestsellers")' )
            ->default( 'Bestsellers' )
            ->required(),

        Tab::make( 'Featured Products (Left)', wp_unique_id() )->placement( 'left' ),

        PostObject::make( 'Featured Product', 'featured_product' )
            ->helperText( 'Select the first featured product (top left)' )
            ->postTypes( ['product'] )
            ->format( 'object' )
            ->required(),

        PostObject::make( 'Featured Product 2', 'featured_product_2' )
            ->helperText( 'Select the second featured product (bottom left)' )
            ->postTypes( ['product'] )
            ->format( 'object' ),

        Tab::make( 'Grid Products (Right)', wp_unique_id() )->placement( 'left' ),

        Select::make( 'Grid Products Source', 'grid_source' )
            ->helperText( 'Choose how to populate the 4 grid products on the right' )
            ->choices( [
                'best_selling' => 'Best Selling Products',
                'selected'     => 'Manually Selected Products'
            ] )
            ->default( 'best_selling' )
            ->format( 'value' )
            ->required(),

        PostObject::make( 'Selected Grid Products', 'grid_products' )
            ->helperText( 'Select exactly 4 products for the grid' )
            ->postTypes( ['product'] )
            ->format( 'object' )
            ->multiple()
            ->conditionalLogic( [
                ConditionalLogic::where( 'grid_source', '==', 'selected' )
            ] ),

        Tab::make( 'Settings', wp_unique_id() )->placement( 'left' ),

        Select::make( 'Background Color', 'bg_color' )
            ->helperText( 'Choose the section background color' )
            ->choices( [
                'white'      => 'White',
                'gray-50'    => 'Light Gray',
                'primary-50' => 'Light Primary'
            ] )
            ->default( 'white' )
            ->format( 'value' ),
    ];
}

function component_bestseller_html( string $output, string $layout ): string {
    if ( $layout !== 'bestseller' ) {
        return $output;
    }

    $title              = get_sub_field( 'title' ) ?: 'Bestsellers';
    $featured_product_1 = get_sub_field( 'featured_product' );
    $featured_product_2 = get_sub_field( 'featured_product_2' );
    $grid_source        = get_sub_field( 'grid_source' ) ?: 'best_selling';
    $grid_products      = get_sub_field( 'grid_products' );
    $bg_color           = get_sub_field( 'bg_color' ) ?: 'white';

    // Get featured product IDs for exclusion
    $exclude_ids = [];
    if ( $featured_product_1 ) {
        $exclude_ids[] = is_object( $featured_product_1 ) ? $featured_product_1->ID : $featured_product_1;
    }
    if ( $featured_product_2 ) {
        $exclude_ids[] = is_object( $featured_product_2 ) ? $featured_product_2->ID : $featured_product_2;
    }

    // Get grid product IDs
    $grid_product_ids = [];

    if ( $grid_source === 'selected' && !empty( $grid_products ) ) {
        foreach ( array_slice( $grid_products, 0, 4 ) as $product_post ) {
            $grid_product_ids[] = is_object( $product_post ) ? $product_post->ID : $product_post;
        }
    } else {
        // Get best-selling products (excluding featured products)
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => 4,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'post__not_in'   => $exclude_ids,
            'meta_query'     => [
                [
                    'key'     => 'total_sales',
                    'compare' => 'EXISTS'
                ]
            ],
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'total_sales',
            'order'          => 'DESC'
        ];

        $grid_product_ids = get_posts( $args );

        // Fallback to recent products if no sales data
        if ( empty( $grid_product_ids ) ) {
            $args = [
                'post_type'      => 'product',
                'posts_per_page' => 4,
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'post__not_in'   => $exclude_ids,
                'orderby'        => 'date',
                'order'          => 'DESC'
            ];
            $grid_product_ids = get_posts( $args );
        }
    }

    // Background class
    $bg_class = 'bg-' . $bg_color;

    ob_start();
    ?>
    <section class="ats-bestseller-section py-8 lg:py-12 <?php echo esc_attr( $bg_class ); ?>">
        <div class="container mx-auto px-4">
            <!-- Section Title -->
            <div class="flex items-center w-full gap-5 mb-6">
                <h2 class="text-lg xl:text-xl font-bold text-primary-600 whitespace-nowrap tracking-tight">
                    <?php echo esc_html( $title ); ?>
                </h2>
                <div class="flex-grow h-[1px] bg-neutral-300"></div>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Featured Products (Left) - 2 vertical cards stacked -->
                <div class="lg:w-1/3 flex flex-col gap-4">
                    <?php
                    // Render Featured Product 1
                    if ( $featured_product_1 ) {
                        $product_id = is_object( $featured_product_1 ) ? $featured_product_1->ID : $featured_product_1;
                        echo do_shortcode( '[ats_product id="' . $product_id . '" display="1"]' );
                    }

                    // Render Featured Product 2
                    if ( $featured_product_2 ) {
                        $product_id = is_object( $featured_product_2 ) ? $featured_product_2->ID : $featured_product_2;
                        echo do_shortcode( '[ats_product id="' . $product_id . '" display="1"]' );
                    }
                    ?>
                </div>

                <!-- Grid Products (Right) - 4 compact horizontal cards in 2x2 grid -->
                <div class="lg:w-2/3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ( $grid_product_ids as $product_id ) :
                            $product = wc_get_product( $product_id );
                            if ( !$product ) continue;

                            $product_url   = $product->get_permalink();
                            $product_title = $product->get_name();
                            $image_id      = get_post_thumbnail_id( $product_id );
                            $image_url     = $image_id ? wpimage( $image_id, [120, 120], false, true, true ) : wc_placeholder_img_src( 'thumbnail' );
                            $is_variable   = $product->is_type( 'variable' );
                            $button_text   = $is_variable ? 'Select Size' : 'Add to Cart';

                            // Get categories
                            $categories    = wp_get_post_terms( $product_id, 'product_cat', ['fields' => 'names'] );
                            $category_text = !empty( $categories ) ? implode( ', ', $categories ) : '';

                            // Get price
                            if ( $product->is_type( 'variable' ) ) {
                                $price_html = 'From: ' . wc_price( $product->get_variation_price( 'min', true ) ) . ' +VAT';
                            } else {
                                $price_html = wc_price( $product->get_price() ) . ' +VAT';
                            }

                            // Get rating
                            $rating_count   = $product->get_review_count();
                            $average_rating = $product->get_average_rating();
                        ?>
                            <div class="ats-bestseller-compact flex gap-4 p-4 bg-white border border-neutral-200 hover:border-accent-yellow rounded-lg transition-colors">
                                <!-- Image on LEFT -->
                                <a href="<?php echo esc_url( $product_url ); ?>" class="flex-shrink-0">
                                    <img
                                        src="<?php echo esc_url( $image_url ); ?>"
                                        alt="<?php echo esc_attr( $product_title ); ?>"
                                        class="w-24 h-24 lg:w-28 lg:h-28 object-contain"
                                        loading="lazy"
                                    />
                                </a>

                                <!-- Content on RIGHT -->
                                <div class="flex flex-col justify-between flex-grow min-w-0">
                                    <div>
                                        <div class="flex items-center gap-1 text-[10px] text-gray-500 mb-1">
                                            <svg class="w-3 h-3 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                                            </svg>
                                            <span class="truncate"><?php echo esc_html( $category_text ); ?></span>
                                        </div>

                                        <h3 class="text-sm font-bold text-primary-700 leading-tight mb-1 line-clamp-2">
                                            <a href="<?php echo esc_url( $product_url ); ?>" class="hover:text-primary-800">
                                                <?php echo esc_html( $product_title ); ?>
                                            </a>
                                        </h3>

                                        <!-- Star Rating -->
                                        <div class="flex items-center gap-1 mb-2">
                                            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                <svg class="w-3 h-3 <?php echo $i <= round( $average_rating ) ? 'text-accent-yellow' : 'text-neutral-300'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            <?php endfor; ?>
                                            <span class="text-[10px] text-gray-500 ml-1"><?php echo esc_html( $rating_count ); ?> Reviews</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-bold text-black whitespace-nowrap"><?php echo wp_kses_post( $price_html ); ?></span>
                                        <a
                                            href="<?php echo esc_url( $product_url ); ?>"
                                            class="ats-btn ats-btn-xs ats-btn-yellow whitespace-nowrap"
                                        >
                                            <?php echo esc_html( $button_text ); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_filter( 'skylinewp_flexible_content_output', 'component_bestseller_html', 10, 2 );

// Define the custom layout for flexible content
return Layout::make( 'Bestseller', 'bestseller' )
    ->layout( 'block' )
    ->fields( bestseller_fields() );
