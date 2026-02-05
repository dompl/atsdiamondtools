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
        'order'      => 'ASC'
    ] );

    $category_choices = [];
    if ( !is_wp_error( $product_categories ) && !empty( $product_categories ) ) {
        foreach ( $product_categories as $category ) {
            $category_choices[$category->term_id] = $category->name;
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
                    ->format( 'array' )
            ] )
            ->minRows( 1 )
            ->button( 'Add Slide' )
            ->layout( 'block' )
            ->required()
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
        'exclude'    => $excluded_category_ids
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

	<div class="rfs-ref-banner-container flex items-center justify-center mb-8 mt-4" id="<?php echo esc_attr( $banner_id ); ?>" data-slides-count="<?php echo count( $slides ); ?>">
		<!-- Banner Container -->
		<div class="rfs-ref-banner-wrapper container flex flex-col lg:flex-row gap-6 lg:gap-5 lg:py-0">
			<!-- Sidebar (Navigation) -->
			<?php echo do_shortcode('[category_navigation]'); ?>

			<!-- Carousel -->
			<div class="rfs-ref-banner-carousel w-full lg:flex-1 relative h-[500px] lg:h-auto rounded-lg overflow-hidden shadow-xl bg-gray-900 group">
				<div class="rfs-ref-carousel-slides absolute inset-0 w-full h-full">
					<?php foreach ( $slides as $index => $slide ):
        $image     = $slide['image'];
        $image_url = is_array( $image ) ? $image['url'] : wp_get_attachment_image_url( $image, 'full' );
        $image_alt = is_array( $image ) ? ( $image['alt'] ?: $slide['title'] ): get_post_meta( $image, '_wp_attachment_image_alt', true );

        $button        = $slide['button'];
        $button_url    = '';
        $button_text   = '';
        $button_target = '_self';

        if ( is_array( $button ) ) {
            $button_url    = isset( $button['url'] ) ? $button['url'] : '';
            $button_text   = isset( $button['title'] ) ? $button['title'] : '';
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
														<span class="ats-btn ats-btn-xs ats-btn-primary-300 w-fit mb-2">
															<?php echo esc_html( $slide['prefix'] ); ?>
														</span>
													<?php endif; ?>

								<?php if ( !empty( $slide['title'] ) ): ?>
									<h2 class="text-3xl lg:text-5xl font-bold text-white mb-3 leading-tight shadow-sm">
										<?php echo esc_html( $slide['title'] ); ?>
									</h2>
								<?php endif; ?>

								<?php if ( !empty( $slide['subtitle'] ) ): ?>
									<p class="text-gray-200 text-base lg:text-lg mb-4 leading-relaxed max-w-md drop-shadow-md">
										<?php echo esc_html( $slide['subtitle'] ); ?>
									</p>
								<?php endif; ?>

								<?php if ( !empty( $button_url ) && !empty( $button_text ) ): ?>
									<a href="<?php echo esc_url( $button_url ); ?>"
										target="<?php echo esc_attr( $button_target ); ?>"
										class="ats-btn ats-btn-lg-md ats-btn-yellow w-fit">
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
