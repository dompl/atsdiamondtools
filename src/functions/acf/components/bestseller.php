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

        Tab::make( 'Products', wp_unique_id() )->placement( 'left' ),

        Select::make( 'Products Source', 'products_source' )
            ->helperText( 'Choose how to populate the 8 products (2 vertical left + 6 horizontal right)' )
            ->choices( [
                'best_selling' => 'Best Selling Products',
                'selected'     => 'Manually Selected Products'
            ] )
            ->default( 'best_selling' )
            ->format( 'value' )
            ->required(),

        PostObject::make( 'Selected Products', 'selected_products' )
            ->helperText( 'Select exactly 8 products. First 2 will be vertical cards (left), last 6 will be horizontal cards (right)' )
            ->postTypes( ['product'] )
            ->format( 'object' )
            ->multiple()
            ->conditionalLogic( [
                ConditionalLogic::where( 'products_source', '==', 'selected' )
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

    $title            = get_sub_field( 'title' ) ?: 'Bestsellers';
    $products_source  = get_sub_field( 'products_source' ) ?: 'best_selling';
    $selected_products = get_sub_field( 'selected_products' );
    $bg_color         = get_sub_field( 'bg_color' ) ?: 'white';

    // Get product IDs
    $product_ids = [];

    if ( $products_source === 'selected' && !empty( $selected_products ) ) {
        foreach ( array_slice( $selected_products, 0, 8 ) as $product_post ) {
            $product_ids[] = is_object( $product_post ) ? $product_post->ID : $product_post;
        }
    } else {
        // Get best-selling products
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => 8,
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

        // Fallback to recent products if no sales data
        if ( empty( $product_ids ) ) {
            $args = [
                'post_type'      => 'product',
                'posts_per_page' => 8,
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'orderby'        => 'date',
                'order'          => 'DESC'
            ];
            $product_ids = get_posts( $args );
        }
    }

    // Ensure we have exactly 8 products
    $product_ids = array_slice( $product_ids, 0, 8 );

    if ( count( $product_ids ) < 8 ) {
        return ''; // Don't display if we don't have 8 products
    }

    // Split products: first 2 for vertical cards (LEFT), last 6 for horizontal cards (RIGHT)
    $vertical_product_ids   = array_slice( $product_ids, 0, 2 );
    $horizontal_product_ids = array_slice( $product_ids, 2, 6 );

    // Background class
    $bg_class = 'bg-' . $bg_color;

    ob_start();
    ?>
    <section class="rfs-ref-bestseller-section ats-bestseller-section py-8 lg:py-12 <?php echo esc_attr( $bg_class ); ?>">
        <div class="rfs-ref-bestseller-container container mx-auto px-4">
            <!-- Section Title -->
            <div class="rfs-ref-bestseller-header flex items-center w-full gap-5 mb-6">
                <h2 class="rfs-ref-bestseller-title text-lg xl:text-xl font-bold text-primary-600 whitespace-nowrap tracking-tight">
                    <?php echo esc_html( $title ); ?>
                </h2>
                <div class="rfs-ref-bestseller-divider flex-grow h-[1px] bg-neutral-300"></div>
            </div>

            <!-- Two Section Layout: Left = 2 vertical products in 1 row, Right = 6 horizontal products in 2x3 grid -->
            <div class="rfs-ref-bestseller-layout flex flex-col lg:flex-row gap-6">

                <!-- LEFT: 2 Vertical Products in ONE ROW (side by side) - 40% width -->
                <div class="rfs-ref-bestseller-left lg:w-[40%] grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <?php foreach ( $vertical_product_ids as $product_id ) :
                        echo do_shortcode( '[ats_product id="' . $product_id . '" display="1"]' );
                    endforeach; ?>
                </div>

                <!-- RIGHT: 6 Horizontal Products in 2x3 Grid (2 columns, 3 rows) - 60% width -->
                <div class="rfs-ref-bestseller-right lg:w-[60%]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <?php foreach ( $horizontal_product_ids as $product_id ) :
                            echo do_shortcode( '[ats_product id="' . $product_id . '" display="3"]' );
                        endforeach; ?>
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
