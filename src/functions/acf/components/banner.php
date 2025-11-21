<?php

use Extended\ACF\Fields\Checkbox;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Link;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;

function banner_fields() {
    // Get product categories for checkbox choices
    $product_categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ] );

    $category_choices = [];
    if ( !is_wp_error( $product_categories ) && !empty( $product_categories ) ) {
        foreach ( $product_categories as $category ) {
            $category_choices[ $category->term_id ] = $category->name;
        }
    }

    return [
        Tab::make( 'Categories', wp_unique_id() )->placement( 'left' ),

        Checkbox::make( 'Exclude Categories', 'excluded_categories' )
            ->helperText( 'Select product categories to **exclude** from the banner sidebar navigation. All other categories will be displayed.' )
            ->choices( $category_choices )
            ->layout( 'vertical' )
            ->format( 'value' ),

        Tab::make( 'Banner Settings', wp_unique_id() )->placement( 'left' ),

        Repeater::make( 'Banner Slides', 'banner_slides' )
            ->helperText( 'Add banner slides to display in the carousel' )
            ->fields( [
                Image::make( 'Image', 'image' )
                    ->helperText( 'Background image for the slide (recommended: **2070x500px** or larger)' )
                    ->acceptedFileTypes( ['jpg', 'jpeg', 'png', 'webp'] )
                    ->previewSize( 'medium' )
                    ->format( 'array' )
                    ->required(),

                Text::make( 'Prefix', 'prefix' )
                    ->helperText( 'Small text above the title (e.g., "Professional Grade", "High Efficiency")' ),

                Text::make( 'Title', 'title' )
                    ->helperText( 'Main heading for the slide' ),

                Textarea::make( 'Subtitle', 'subtitle' )
                    ->helperText( 'Description text for the slide' )
                    ->rows( 3 ),

                Link::make( 'Button', 'button' )
                    ->helperText( 'Button link with text and URL. The link text will be displayed on the button.' )
                    ->format( 'array' ),
            ] )
            ->minRows( 1 )
            ->button( 'Add Slide' )
            ->layout( 'block' )
            ->required(),
    ];
}

function component_banner_html( string $output, string $layout ): string {
    if ( $layout !== 'banner' ) {
        return $output;
    }

    $excluded_category_ids = get_sub_field( 'excluded_categories' ) ?: [];
    $slides                = get_sub_field( 'banner_slides' );

    // Get WooCommerce product categories
    $categories_args = [
        'taxonomy'   => 'product_cat',
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => true,
        'exclude'    => $excluded_category_ids,
    ];

    $product_categories = get_terms( $categories_args );

    // Generate unique ID for this banner instance
    $banner_id = 'banner-' . uniqid();

    // Start output buffering
    ob_start();

    if ( empty( $slides ) ) {
        ?>
        <div class="container mx-auto px-4">
            <div class="rfs-ref-banner-empty bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
                <p class="text-gray-600">No banner slides found. Please add at least one slide to display the banner.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    ?>

    <div class="rfs-ref-banner-container flex items-center justify-center p-4 lg:p-8" id="<?php echo esc_attr( $banner_id ); ?>" data-slides-count="<?php echo count( $slides ); ?>">
        <!-- Banner Container -->
        <div class="rfs-ref-banner-wrapper w-full container mx-auto flex flex-col lg:flex-row gap-4 lg:gap-5 p-4 lg:p-0">

            <!-- Sidebar (Navigation) -->
            <div class="rfs-ref-banner-sidebar w-full lg:w-[320px] flex-shrink-0 bg-[#594652] text-white rounded-lg overflow-hidden shadow-xl flex flex-col relative z-20 h-fit">

                <!-- Toggle Button -->
                <button class="rfs-ref-category-btn w-full flex items-center justify-between p-5 border-b border-white/10 lg:cursor-default cursor-pointer text-left outline-none focus:bg-white/5 bg-[#594652] relative z-20">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <h2 class="text-lg font-bold tracking-wide text-white uppercase">Shop By Category</h2>
                    </div>
                    <!-- Chevron -->
                    <svg class="rfs-ref-category-chevron lg:hidden h-5 w-5 text-white/70 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- List Container -->
                <div class="rfs-ref-category-list grid transition-[grid-template-rows] duration-500 ease-out grid-rows-0 lg:grid-rows-1">
                    <div class="overflow-hidden">
                        <div class="flex flex-col py-2">
                            <?php if ( !empty( $product_categories ) && !is_wp_error( $product_categories ) ): ?>
                                <?php foreach ( $product_categories as $category ):
                                    $category_link = get_term_link( $category );
                                    $short_description = get_field( 'category_nav_short_description', $category );
                                ?>
                                    <a href="<?php echo esc_url( $category_link ); ?>" class="rfs-ref-category-item group px-6 py-3 hover:bg-white/10 cursor-pointer transition-colors duration-200 border-l-4 border-transparent hover:border-[#fbbf24]">
                                        <h3 class="text-[13px] font-bold uppercase tracking-wider text-white mb-0.5 group-hover:text-[#fbbf24] transition-colors">
                                            <?php echo esc_html( $category->name ); ?>
                                        </h3>
                                        <?php if ( $short_description ): ?>
                                            <p class="text-[11px] text-gray-300 font-light leading-tight opacity-80 group-hover:opacity-100">
                                                <?php echo esc_html( $short_description ); ?>
                                            </p>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carousel -->
            <div class="rfs-ref-banner-carousel w-full lg:flex-1 relative h-[500px] lg:h-auto rounded-lg overflow-hidden shadow-xl bg-gray-900 group">
                <div class="rfs-ref-carousel-slides absolute inset-0 w-full h-full">
                    <?php foreach ( $slides as $index => $slide ):
                        $image = $slide['image'];
                        $image_url = is_array( $image ) ? $image['url'] : wp_get_attachment_image_url( $image, 'full' );
                        $image_alt = is_array( $image ) ? ( $image['alt'] ?: $slide['title'] ) : get_post_meta( $image, '_wp_attachment_image_alt', true );

                        $button = $slide['button'];
                        $button_url = '';
                        $button_text = '';
                        $button_target = '_self';

                        if ( is_array( $button ) ) {
                            $button_url = isset( $button['url'] ) ? $button['url'] : '';
                            $button_text = isset( $button['title'] ) ? $button['title'] : '';
                            $button_target = isset( $button['target'] ) && $button['target'] ? $button['target'] : '_self';
                        }
                    ?>
                        <div class="rfs-ref-slide-item absolute inset-0 transition-opacity duration-1000 ease-in-out <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0'; ?>">
                            <div class="absolute inset-0">
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
                            </div>
                            <div class="absolute inset-0 flex flex-col justify-center p-8 lg:p-16 max-w-2xl">
                                <?php if ( !empty( $slide['prefix'] ) ): ?>
                                    <span class="inline-block py-1 px-3 mb-4 text-xs font-bold tracking-widest text-white uppercase bg-[#594652] w-fit rounded">
                                        <?php echo esc_html( $slide['prefix'] ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( !empty( $slide['title'] ) ): ?>
                                    <h2 class="text-3xl lg:text-5xl font-bold text-white mb-4 leading-tight shadow-sm">
                                        <?php echo esc_html( $slide['title'] ); ?>
                                    </h2>
                                <?php endif; ?>

                                <?php if ( !empty( $slide['subtitle'] ) ): ?>
                                    <p class="text-gray-200 text-base lg:text-lg mb-8 leading-relaxed max-w-md drop-shadow-md">
                                        <?php echo esc_html( $slide['subtitle'] ); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ( !empty( $button_url ) && !empty( $button_text ) ): ?>
                                    <a href="<?php echo esc_url( $button_url ); ?>"
                                       target="<?php echo esc_attr( $button_target ); ?>"
                                       class="px-8 py-3 bg-[#fbbf24] hover:bg-[#f59e0b] text-gray-900 font-bold uppercase tracking-wide text-sm rounded w-fit">
                                        <?php echo esc_html( $button_text ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ( count( $slides ) > 1 ): ?>
                    <!-- Controls -->
                    <button class="rfs-ref-prev-btn absolute left-4 top-1/2 -translate-y-1/2 z-20 p-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 lg:opacity-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button class="rfs-ref-next-btn absolute right-4 top-1/2 -translate-y-1/2 z-20 p-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 lg:opacity-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <!-- Dots -->
                    <div class="rfs-ref-carousel-dots absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-2">
                        <?php foreach ( $slides as $dot_index => $slide ): ?>
                            <button class="rfs-ref-carousel-dot h-2 transition-all duration-300 rounded-full <?php echo $dot_index === 0 ? 'w-8 bg-[#fbbf24]' : 'w-2 bg-white/50 hover:bg-white'; ?>" data-slide-index="<?php echo esc_attr( $dot_index ); ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_filter( 'skylinewp_flexible_content_output', 'component_banner_html', 10, 2 );

// Define the custom layout for flexible content
return Layout::make( 'Banner', 'banner' )
    ->layout( 'block' )
    ->fields( banner_fields() );
