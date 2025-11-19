<?php

use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\PostObject;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\ConditionalLogic;

function product_scroller_fields() {
    return [
        Tab::make( 'Content', wp_unique_id() )->placement( 'left' ),

        Text::make( 'Title', 'title' )
            ->helperText( 'Enter the title for this product section (e.g., "Featured Products")' )
            ->required(),

        Select::make( 'Product Type', 'product_type' )
            ->helperText( 'Select whether to display best-selling products or manually selected products' )
            ->choices( [
                'best_selling' => 'Best Selling Products',
                'selected'     => 'Selected Products'
            ] )
            ->default( 'best_selling' )
            ->format( 'value' )
            ->required(),

        PostObject::make( 'Selected Products', 'selected_products' )
            ->helperText( 'Select specific products to display in the carousel' )
            ->postTypes( ['product'] )
            ->format( 'object' )
            ->multiple()
            ->conditionalLogic( [
                ConditionalLogic::where( 'product_type', '==', 'selected' )
            ] ),

        Tab::make( 'Settings', wp_unique_id() )->placement( 'left' ),

        Select::make( 'Number of Products', 'products_limit' )
            ->helperText( 'Select how many products to display' )
            ->choices( [
                '5'  => '5 Products',
                '10' => '10 Products',
                '15' => '15 Products',
                '20' => '20 Products',
                '25' => '25 Products'
            ] )
            ->default( '10' )
            ->format( 'value' )
            ->required(),

        Select::make( 'Number of Rows', 'rows' )
            ->helperText( 'Select how many rows to display in the carousel' )
            ->choices( [
                '1' => '1 Row',
                '2' => '2 Rows',
                '3' => '3 Rows'
            ] )
            ->default( '2' )
            ->format( 'value' )
            ->required()
    ];
}

function component_product_scroller_html( string $output, string $layout ): string {
    if ( $layout !== 'product_scroller' ) {
        return $output;
    }

    $title           = get_sub_field( 'title' );
    $product_type    = get_sub_field( 'product_type' );
    $selected_prods  = get_sub_field( 'selected_products' );
    $products_limit  = get_sub_field( 'products_limit' ) ?: '10';
    $rows            = get_sub_field( 'rows' ) ?: '2';

    // Generate unique ID for this carousel instance
    $carousel_id = 'product-scroller-' . uniqid();

    // Build product IDs for shortcode
    $product_ids = [];

    if ( $product_type === 'selected' && !empty( $selected_prods ) ) {
        // Use selected products
        $selected_prods = array_slice( $selected_prods, 0, intval( $products_limit ) );
        foreach ( $selected_prods as $product_post ) {
            $product_id = is_object( $product_post ) ? $product_post->ID : $product_post;
            $product_ids[] = $product_id;
        }
    } else {
        // Get best-selling products
        // First try with sales data
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => intval( $products_limit ),
            'post_status'    => 'publish',
            'fields'         => 'ids',
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

        $product_ids = get_posts( $args );

        // If no products with sales data, get any published products
        if ( empty( $product_ids ) ) {
            $args = [
                'post_type'      => 'product',
                'posts_per_page' => intval( $products_limit ),
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'orderby'        => 'date',
                'order'          => 'DESC'
            ];
            $product_ids = get_posts( $args );
        }
    }

    // Start output buffering
    ob_start();

    if ( empty( $product_ids ) ) {
        ?>
        <div class="container mx-auto px-4">
            <div class="rfs-ref-product-scroller-empty bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
                <p class="text-gray-600">No products found. Please add some products or select specific products to display.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    ?>

    <div class="container mx-auto px-4">
        <div class="rfs-ref-product-scroller-wrapper" id="<?php echo esc_attr( $carousel_id ); ?>" data-rows="<?php echo esc_attr( $rows ); ?>">
        <?php if ( $title ): ?>
            <div class="rfs-ref-product-scroller-header relative mb-8 flex items-center justify-between">
                <h2 class="rfs-ref-product-scroller-title text-2xl font-bold text-gray-700 bg-white pr-4 relative z-10">
                    <?php echo esc_html( $title ); ?>
                </h2>
                <div class="rfs-ref-product-scroller-nav splide__arrows flex gap-2 bg-white pl-4 relative z-10">
                    <button class="splide__arrow splide__arrow--prev rfs-ref-prev-arrow w-8 h-8 rounded-md border border-gray-300 hover:border-gray-400 transition-all flex items-center justify-center bg-gray-50 text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <button class="splide__arrow splide__arrow--next rfs-ref-next-arrow w-8 h-8 rounded-md border border-gray-300 hover:border-gray-400 transition-all flex items-center justify-center bg-gray-50 text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="rfs-ref-product-carousel splide">
            <div class="splide__track">
                <ul class="splide__list">
                    <?php
                    // Use the existing ats_product shortcode
                    foreach ( $product_ids as $product_id ) {
                        echo '<li class="splide__slide">';
                        echo do_shortcode( '[ats_product id="' . $product_id . '"]' );
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_filter( 'skylinewp_flexible_content_output', 'component_product_scroller_html', 10, 2 );

// Define the custom layout for flexible content
return Layout::make( 'Product Scroller', 'product_scroller' )
    ->layout( 'block' )
    ->fields( product_scroller_fields() );
