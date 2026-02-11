<?php
/**
 * Hero Banner Component
 *
 * A flexible content component for hero banners with heading, description, button, and background image
 *
 * @package SkylineWP Dev Child
 */

use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Link;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;

function hero_banner_fields() {
	return [
		Tab::make( 'Content', wp_unique_id() )->placement( 'left' ),

		Text::make( 'Heading', 'heading' )
			->helperText( 'Main heading for the hero banner' )
			->required(),

		Textarea::make( 'Description', 'description' )
			->helperText( 'Description text below the heading' )
			->rows( 3 ),

		Link::make( 'Button', 'button' )
			->helperText( 'Call-to-action button with text and URL' )
			->format( 'array' ),

		Image::make( 'Background Image', 'background_image' )
			->helperText( 'Background image for the hero banner (recommended: **1920x600px** or larger)' )
			->acceptedFileTypes( ['jpg', 'jpeg', 'png', 'webp'] )
			->previewSize( 'medium' )
			->format( 'id' )
			->required(),

		Tab::make( 'Spacing Settings', wp_unique_id() )->placement( 'left' ),

		Select::make( 'Padding Top', 'padding_top' )
			->helperText( 'Spacing from the top of the banner' )
			->choices( [
				'none'    => 'None',
				'small'   => 'Small',
				'default' => 'Default',
				'large'   => 'Large',
			] )
			->default( 'default' )
			->format( 'value' )
			->required(),

		Select::make( 'Padding Bottom', 'padding_bottom' )
			->helperText( 'Spacing from the bottom of the banner' )
			->choices( [
				'none'    => 'None',
				'small'   => 'Small',
				'default' => 'Default',
				'large'   => 'Large',
			] )
			->default( 'default' )
			->format( 'value' )
			->required(),
	];
}

function component_hero_banner_html( string $output, string $layout ): string {
	if ( $layout !== 'hero_banner' ) {
		return $output;
	}

	// Get field values
	$heading          = get_sub_field( 'heading' );
	$description      = get_sub_field( 'description' );
	$button           = get_sub_field( 'button' );
	$background_image = get_sub_field( 'background_image' );
	$padding_top      = get_sub_field( 'padding_top' ) ?: 'default';
	$padding_bottom   = get_sub_field( 'padding_bottom' ) ?: 'default';

	// Define padding classes
	$padding_classes = [
		'none'    => '',
		'small'   => '6',
		'default' => '12',
		'large'   => '20',
	];

	// Get the appropriate padding values
	$pt_class = $padding_classes[ $padding_top ] ?? $padding_classes['default'];
	$pb_class = $padding_classes[ $padding_bottom ] ?? $padding_classes['default'];

	// Build padding class string
	$padding_class = '';
	if ( ! empty( $pt_class ) ) {
		$padding_class .= ' pt-' . $pt_class;
	}
	if ( ! empty( $pb_class ) ) {
		$padding_class .= ' pb-' . $pb_class;
	}

	// Get background image URL using wpimage()
	$background_url = $background_image ? wpimage( $background_image, [1920, 600], false, true, true, true, 85 ) : '';

	// Parse button
	$button_url    = '';
	$button_text   = '';
	$button_target = '_self';

	if ( is_array( $button ) && ! empty( $button ) ) {
		$button_url    = isset( $button['url'] ) ? $button['url'] : '';
		$button_text   = isset( $button['title'] ) ? $button['title'] : '';
		$button_target = isset( $button['target'] ) && $button['target'] ? $button['target'] : '_self';
	}

	ob_start();
	?>

	<div class="rfs-ref-hero-banner-wrapper container mx-auto px-4<?php echo esc_attr( $padding_class ); ?>">
		<div class="rfs-ref-hero-banner relative overflow-hidden bg-ats-dark rounded-lg">
			<!-- Background Image -->
			<?php if ( $background_url ) : ?>
				<div class="absolute inset-0">
					<img src="<?php echo esc_url( $background_url ); ?>"
					     alt="<?php echo esc_attr( $heading ); ?>"
					     class="w-full h-full object-cover opacity-40 rounded-lg" />
					<div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-transparent rounded-lg"></div>
				</div>
			<?php endif; ?>

			<!-- Content -->
			<div class="rfs-ref-hero-content relative z-10 py-12 px-6 md:py-12 md:px-12 text-center">
				<div class="max-w-10xl mx-auto">
					<?php if ( ! empty( $heading ) ) : ?>
						<h1 class="rfs-ref-hero-title text-xl md:text-2xl lg:text-5xl font-bold text-white mb-6 leading-tight">
							<?php echo esc_html( $heading ); ?>
						</h1>
					<?php endif; ?>

					<?php if ( ! empty( $description ) ) : ?>
						<p class="rfs-ref-hero-description text-lg md:text-lg text-gray-200 mb-8 leading-relaxed max-w-8xl mx-auto">
							<?php echo esc_html( $description ); ?>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $button_url ) && ! empty( $button_text ) ) : ?>
						<a href="<?php echo esc_url( $button_url ); ?>"
						   target="<?php echo esc_attr( $button_target ); ?>"
						   class="ats-btn ats-btn-lg ats-btn-primary-300">
							<?php echo esc_html( $button_text ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<?php
	return ob_get_clean();
}
add_filter( 'skylinewp_flexible_content_output', 'component_hero_banner_html', 10, 2 );

// Define the custom layout for flexible content
return Layout::make( 'Hero Banner', 'hero_banner' )
	->layout( 'block' )
	->fields( hero_banner_fields() );
