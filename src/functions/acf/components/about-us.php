<?php
/**
 * About Us Component - Modern Structured Layout
 *
 * @package SkylineWP Dev Child
 */

use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Link;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\WYSIWYGEditor;

function about_us_fields() {
	return [
		// Hero Section
		Tab::make( 'Hero Section', wp_unique_id() )->placement( 'left' ),

		Text::make( 'Hero Heading', 'hero_heading' )
			->helperText( 'Main heading displayed prominently at the top of the page' )
			->required(),

		Textarea::make( 'Hero Introduction', 'hero_intro' )
			->helperText( 'Short introductory paragraph below the heading' )
			->rows( 3 )
			->required(),

		Image::make( 'Hero Background Image', 'hero_bg_image' )
			->helperText( 'Background image for the hero section (recommended: **1920x500px**). Leave empty for brand color background.' )
			->acceptedFileTypes( [ 'jpg', 'jpeg', 'png', 'webp' ] )
			->previewSize( 'medium' )
			->format( 'id' ),

		// Key Highlights
		Tab::make( 'Key Highlights', wp_unique_id() )->placement( 'left' ),

		Repeater::make( 'Highlights', 'highlights' )
			->helperText( 'Key selling points displayed as cards below the hero (recommended: 3 items)' )
			->fields( [
				Text::make( 'Title', 'title' )
					->required(),
				Textarea::make( 'Description', 'description' )
					->rows( 2 )
					->required(),
			] )
			->minRows( 1 )
			->button( 'Add Highlight' )
			->layout( 'block' ),

		// Our Story
		Tab::make( 'Our Story', wp_unique_id() )->placement( 'left' ),

		Text::make( 'Story Section Title', 'story_title' )
			->helperText( 'Title for the story section' )
			->required(),

		WYSIWYGEditor::make( 'Story Content', 'story_content' )
			->helperText( 'The founding story and mission of ATS Diamond Tools' )
			->tabs( 'all' )
			->toolbar( 'full' )
			->required(),

		Image::make( 'Story Image', 'story_image' )
			->helperText( 'Image displayed alongside the story content (recommended: **800x600px**)' )
			->acceptedFileTypes( [ 'jpg', 'jpeg', 'png', 'webp' ] )
			->previewSize( 'medium' )
			->format( 'id' ),

		// Customer Focus
		Tab::make( 'Customer Focus', wp_unique_id() )->placement( 'left' ),

		Text::make( 'Customer Section Title', 'customer_title' )
			->helperText( 'Title for the customer focus section' )
			->required(),

		WYSIWYGEditor::make( 'Customer Content', 'customer_content' )
			->helperText( 'Content about customer service philosophy' )
			->tabs( 'all' )
			->toolbar( 'full' )
			->required(),

		Image::make( 'Customer Image', 'customer_image' )
			->helperText( 'Image displayed alongside customer content (recommended: **800x600px**)' )
			->acceptedFileTypes( [ 'jpg', 'jpeg', 'png', 'webp' ] )
			->previewSize( 'medium' )
			->format( 'id' ),

		// Testimonials
		Tab::make( 'Testimonials', wp_unique_id() )->placement( 'left' ),

		Text::make( 'Testimonials Section Title', 'testimonials_title' )
			->helperText( 'Title above the testimonials carousel' ),

		Repeater::make( 'Testimonials', 'testimonials' )
			->helperText( 'Customer testimonials shown in a horizontal scroller' )
			->fields( [
				Textarea::make( 'Quote', 'quote' )
					->rows( 4 )
					->required(),
				Text::make( 'Author Name', 'author_name' )
					->required(),
				Text::make( 'Company', 'company' ),
			] )
			->minRows( 1 )
			->button( 'Add Testimonial' )
			->layout( 'block' ),

		// Location
		Tab::make( 'Location & Contact', wp_unique_id() )->placement( 'left' ),

		Text::make( 'Location Section Title', 'location_title' )
			->helperText( 'Title for the location section' ),

		WYSIWYGEditor::make( 'Location Content', 'location_content' )
			->helperText( 'Information about location and delivery' )
			->tabs( 'all' )
			->toolbar( 'full' ),

		Link::make( 'Contact Button', 'contact_button' )
			->helperText( 'Call-to-action button (e.g., Contact Us)' )
			->format( 'array' ),

		// Partners
		Tab::make( 'Partners', wp_unique_id() )->placement( 'left' ),

		Text::make( 'Partners Section Title', 'partners_title' )
			->helperText( 'Title for the partners section' ),

		Repeater::make( 'Partners', 'partners' )
			->helperText( 'List of valued partner companies' )
			->fields( [
				Text::make( 'Partner Name', 'name' )
					->required(),
				Text::make( 'Description', 'description' ),
				Image::make( 'Logo', 'logo' )
					->helperText( 'Partner logo (recommended: **200x100px**)' )
					->acceptedFileTypes( [ 'jpg', 'jpeg', 'png', 'webp', 'svg' ] )
					->previewSize( 'thumbnail' )
					->format( 'id' ),
			] )
			->minRows( 1 )
			->button( 'Add Partner' )
			->layout( 'block' ),
	];
}

/**
 * Render image with wpimage() or show placeholder
 */
function about_us_render_image( $image_id, $size, $alt = '', $class = '' ) {
	if ( $image_id ) {
		$img_url = wpimage( image: $image_id, size: $size, retina: true, quality: 85 );
		echo '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $alt ) . '" class="' . esc_attr( $class ) . '" loading="lazy">';
	} else {
		echo '<div class="rfs-ref-about-placeholder ' . esc_attr( $class ) . '">';
		echo '<svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>';
		echo '<span class="text-xs text-gray-400 mt-2">Add Image</span>';
		echo '</div>';
	}
}

function component_about_us_html( string $output, string $layout ): string {
	if ( $layout !== 'about_us' ) {
		return $output;
	}

	// Get all field values
	$hero_heading  = get_sub_field( 'hero_heading' );
	$hero_intro    = get_sub_field( 'hero_intro' );
	$hero_bg_image = get_sub_field( 'hero_bg_image' );

	$highlights = get_sub_field( 'highlights' );

	$story_title   = get_sub_field( 'story_title' );
	$story_content = get_sub_field( 'story_content' );
	$story_image   = get_sub_field( 'story_image' );

	$customer_title   = get_sub_field( 'customer_title' );
	$customer_content = get_sub_field( 'customer_content' );
	$customer_image   = get_sub_field( 'customer_image' );

	$testimonials_title = get_sub_field( 'testimonials_title' );
	$testimonials       = get_sub_field( 'testimonials' );

	$location_title   = get_sub_field( 'location_title' );
	$location_content = get_sub_field( 'location_content' );
	$contact_button   = get_sub_field( 'contact_button' );

	$partners_title = get_sub_field( 'partners_title' );
	$partners       = get_sub_field( 'partners' );

	// Highlight icons
	$highlight_icons = [
		'<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>',
		'<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>',
		'<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" /></svg>',
		'<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>',
	];

	ob_start();
	?>

	<div class="rfs-ref-about-us-wrapper">

		<?php // ===== HERO SECTION ===== ?>
		<?php if ( $hero_heading ) : ?>
		<section class="rfs-ref-about-hero relative overflow-hidden">
			<?php if ( $hero_bg_image ) : ?>
				<div class="absolute inset-0">
					<img src="<?php echo esc_url( wpimage( image: $hero_bg_image, size: [ 1920, 500 ], retina: true, quality: 85 ) ); ?>"
						 alt="" class="w-full h-full object-cover" />
					<div class="absolute inset-0 bg-ats-brand/70"></div>
				</div>
			<?php else : ?>
				<div class="absolute inset-0 bg-ats-brand"></div>
			<?php endif; ?>

			<div class="relative z-10 container mx-auto px-4 pt-16 md:pt-20 pb-28 md:pb-32">
				<div class="max-w-2xl mx-auto text-center">
					<h1 class="rfs-ref-about-hero-title text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-4 leading-tight">
						<?php echo esc_html( $hero_heading ); ?>
					</h1>
					<?php if ( $hero_intro ) : ?>
						<p class="rfs-ref-about-hero-intro text-sm md:text-base text-white/80 leading-relaxed">
							<?php echo esc_html( $hero_intro ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<?php // ===== KEY HIGHLIGHTS ===== ?>
		<?php if ( ! empty( $highlights ) ) : ?>
		<section class="rfs-ref-about-highlights bg-white">
			<div class="container mx-auto px-4 max-w-5xl -mt-20 md:-mt-24 relative z-20">
				<div class="grid grid-cols-1 md:grid-cols-<?php echo min( count( $highlights ), 3 ); ?> gap-0">
					<?php foreach ( $highlights as $index => $highlight ) :
						$icon = $highlight_icons[ $index % count( $highlight_icons ) ];
						$is_first = $index === 0;
						$is_last  = $index === count( $highlights ) - 1;
						$rounded  = '';
						if ( $is_first ) {
							$rounded = 'rounded-t-lg md:rounded-l-lg md:rounded-tr-none';
						} elseif ( $is_last ) {
							$rounded = 'rounded-b-lg md:rounded-r-lg md:rounded-bl-none';
						}
					?>
					<div class="rfs-ref-about-highlight-card bg-white border border-gray-100 p-6 text-center <?php echo esc_attr( $rounded ); ?>">
						<div class="rfs-ref-about-highlight-icon inline-flex items-center justify-center w-10 h-10 rounded-full bg-ats-brand/10 text-ats-brand mb-3">
							<?php echo $icon; ?>
						</div>
						<h3 class="rfs-ref-about-highlight-title text-sm font-bold text-ats-dark mb-1">
							<?php echo esc_html( $highlight['title'] ); ?>
						</h3>
						<p class="rfs-ref-about-highlight-desc text-xs text-gray-500 leading-relaxed">
							<?php echo esc_html( $highlight['description'] ); ?>
						</p>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<?php // ===== OUR STORY ===== ?>
		<?php if ( $story_content ) : ?>
		<section class="rfs-ref-about-story bg-white py-12 md:py-16">
			<div class="container mx-auto px-4 max-w-5xl">
				<div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-12 items-center">
					<div class="rfs-ref-about-story-content">
						<?php if ( $story_title ) : ?>
							<div class="rfs-ref-about-section-label text-xs font-bold uppercase tracking-widest text-ats-brand mb-2">Our Story</div>
							<h2 class="rfs-ref-about-story-title text-xl md:text-2xl font-bold text-ats-dark mb-4 leading-tight">
								<?php echo esc_html( $story_title ); ?>
							</h2>
						<?php endif; ?>
						<div class="rfs-ref-about-story-text text-sm text-gray-600 leading-relaxed [&>p]:mb-3">
							<?php echo wp_kses_post( $story_content ); ?>
						</div>
					</div>
					<div class="rfs-ref-about-story-image">
						<?php about_us_render_image( $story_image, [ 800, 600 ], 'Our Story', 'w-full h-auto rounded-lg object-cover' ); ?>
					</div>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<?php // ===== CUSTOMER FOCUS ===== ?>
		<?php if ( $customer_content ) : ?>
		<section class="rfs-ref-about-customer bg-gray-50 py-12 md:py-16">
			<div class="container mx-auto px-4 max-w-5xl">
				<div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-12 items-center">
					<div class="rfs-ref-about-customer-image order-2 lg:order-1">
						<?php about_us_render_image( $customer_image, [ 800, 600 ], 'Customer Focus', 'w-full h-auto rounded-lg object-cover' ); ?>
					</div>
					<div class="rfs-ref-about-customer-content order-1 lg:order-2">
						<?php if ( $customer_title ) : ?>
							<div class="rfs-ref-about-section-label text-xs font-bold uppercase tracking-widest text-ats-brand mb-2">Our Customers</div>
							<h2 class="rfs-ref-about-customer-title text-xl md:text-2xl font-bold text-ats-dark mb-4 leading-tight">
								<?php echo esc_html( $customer_title ); ?>
							</h2>
						<?php endif; ?>
						<div class="rfs-ref-about-customer-text text-sm text-gray-600 leading-relaxed [&>p]:mb-3">
							<?php echo wp_kses_post( $customer_content ); ?>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<?php // ===== TESTIMONIALS ===== ?>
		<?php if ( ! empty( $testimonials ) ) : ?>
		<section class="rfs-ref-about-testimonials bg-ats-brand py-12 md:py-16 overflow-hidden">
			<div class="container mx-auto px-4 max-w-3xl">
				<?php if ( $testimonials_title ) : ?>
				<div class="text-center mb-8">
					<div class="rfs-ref-about-section-label text-xs font-bold uppercase tracking-widest text-white/50 mb-2">What Our Customers Say</div>
					<h2 class="rfs-ref-about-testimonials-title text-xl md:text-2xl font-bold text-white">
						<?php echo esc_html( $testimonials_title ); ?>
					</h2>
				</div>
				<?php endif; ?>

				<div class="rfs-ref-testimonial-carousel splide" id="about-testimonials-<?php echo uniqid(); ?>">
					<div class="splide__track">
						<ul class="splide__list">
							<?php foreach ( $testimonials as $testimonial ) : ?>
							<li class="splide__slide">
								<div class="rfs-ref-testimonial-card text-center px-4 md:px-8">
									<svg class="rfs-ref-testimonial-quote-icon w-8 h-8 text-ats-yellow mx-auto mb-6" fill="currentColor" viewBox="0 0 24 24">
										<path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
									</svg>
									<blockquote class="rfs-ref-testimonial-text text-white/90 text-sm md:text-base leading-relaxed mb-6 italic">
										<?php echo esc_html( $testimonial['quote'] ); ?>
									</blockquote>
									<div class="rfs-ref-testimonial-author">
										<div class="font-semibold text-white text-sm">
											<?php echo esc_html( $testimonial['author_name'] ); ?>
										</div>
										<?php if ( ! empty( $testimonial['company'] ) ) : ?>
											<div class="text-white/50 text-xs mt-0.5">
												<?php echo esc_html( $testimonial['company'] ); ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<?php if ( count( $testimonials ) > 1 ) : ?>
					<div class="rfs-ref-testimonial-nav flex items-center justify-center gap-4 mt-8">
						<button class="rfs-ref-testimonial-prev w-9 h-9 flex items-center justify-center rounded-full border border-white/30 text-white hover:bg-white/10 transition-colors" aria-label="Previous testimonial">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
							</svg>
						</button>
						<button class="rfs-ref-testimonial-next w-9 h-9 flex items-center justify-center rounded-full border border-white/30 text-white hover:bg-white/10 transition-colors" aria-label="Next testimonial">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
							</svg>
						</button>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<?php // ===== LOCATION & CONTACT ===== ?>
		<?php if ( $location_content ) : ?>
		<section class="rfs-ref-about-location bg-white py-12 md:py-16">
			<div class="container mx-auto px-4 max-w-3xl">
				<div class="text-center">
					<?php if ( $location_title ) : ?>
						<h2 class="rfs-ref-about-location-title text-xl md:text-2xl font-bold text-ats-dark mb-4">
							<?php echo esc_html( $location_title ); ?>
						</h2>
					<?php endif; ?>
					<div class="rfs-ref-about-location-text text-sm text-gray-600 leading-relaxed [&>p]:mb-3">
						<?php echo wp_kses_post( $location_content ); ?>
					</div>
					<?php
					if ( is_array( $contact_button ) && ! empty( $contact_button['url'] ) ) :
						$btn_url    = $contact_button['url'];
						$btn_text   = $contact_button['title'] ?: 'Contact Us';
						$btn_target = ! empty( $contact_button['target'] ) ? $contact_button['target'] : '_self';
					?>
						<div class="mt-6">
							<a href="<?php echo esc_url( $btn_url ); ?>"
							   target="<?php echo esc_attr( $btn_target ); ?>"
							   class="dpn-btn dpn-btn--sm dpn-btn--primary-dark dpn-btn--pill">
								<?php echo esc_html( $btn_text ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<?php // ===== PARTNERS ===== ?>
		<?php if ( ! empty( $partners ) ) : ?>
		<section class="rfs-ref-about-partners bg-gray-50 py-12 md:py-16">
			<div class="container mx-auto px-4 max-w-4xl">
				<?php if ( $partners_title ) : ?>
				<div class="text-center mb-8">
					<h2 class="rfs-ref-about-partners-title text-lg md:text-xl font-bold text-ats-dark">
						<?php echo esc_html( $partners_title ); ?>
					</h2>
				</div>
				<?php endif; ?>
				<div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6">
					<?php foreach ( $partners as $partner ) : ?>
					<div class="rfs-ref-about-partner-card bg-white rounded-lg p-5 flex flex-col items-center justify-center text-center">
						<?php if ( ! empty( $partner['logo'] ) ) : ?>
							<img src="<?php echo esc_url( wpimage( image: $partner['logo'], size: [ 200, 100 ], quality: 90 ) ); ?>"
								 alt="<?php echo esc_attr( $partner['name'] ); ?>"
								 class="rfs-ref-about-partner-logo h-8 w-auto object-contain mb-2" loading="lazy" />
						<?php else : ?>
							<div class="rfs-ref-about-partner-logo-placeholder w-full h-8 flex items-center justify-center mb-2">
								<span class="text-sm font-bold text-ats-dark"><?php echo esc_html( $partner['name'] ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $partner['description'] ) ) : ?>
							<p class="rfs-ref-about-partner-desc text-xs text-gray-500"><?php echo esc_html( $partner['description'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

	</div>

	<?php
	return ob_get_clean();
}
add_filter( 'skylinewp_flexible_content_output', 'component_about_us_html', 10, 2 );

return Layout::make( 'About Us', 'about_us' )
	->layout( 'block' )
	->fields( about_us_fields() );
