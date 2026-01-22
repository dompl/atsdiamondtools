<?php
/**
 * Favorite Button Template Part
 *
 * Displays a heart icon button to add/remove products from favorites
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render favorite button
 *
 * @param int    $product_id Product ID
 * @param string $size Button size (sm, md, lg)
 * @param bool   $show_tooltip Show tooltip on hover
 */
function ats_render_favorite_button( $product_id, $size = 'md', $show_tooltip = true ) {
	if ( ! $product_id ) {
		return;
	}

	// Size classes
	$size_classes = array(
		'sm' => 'w-8 h-8',
		'md' => 'w-10 h-10',
		'lg' => 'w-12 h-12',
	);

	$icon_size_classes = array(
		'sm' => 'w-4 h-4',
		'md' => 'w-5 h-5',
		'lg' => 'w-6 h-6',
	);

	$button_size = $size_classes[ $size ] ?? $size_classes['md'];
	$icon_size   = $icon_size_classes[ $size ] ?? $icon_size_classes['md'];

	?>
	<button type="button"
		class="rfs-ref-favorite-btn ats-favorite-btn group relative inline-flex items-center justify-center <?php echo esc_attr( $button_size ); ?> transition-all duration-200 focus:outline-none"
		data-product-id="<?php echo esc_attr( $product_id ); ?>"
		aria-label="<?php esc_attr_e( 'Add to favorites', 'woocommerce' ); ?>">

		<!-- Heart Outline (Default State) -->
		<svg class="ats-heart-outline <?php echo esc_attr( $icon_size ); ?> text-gray-400 group-hover:text-red-500 transition-colors duration-200"
			fill="none"
			viewBox="0 0 24 24"
			stroke="currentColor"
			stroke-width="2">
			<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
		</svg>

		<!-- Heart Filled (Active State) -->
		<svg class="ats-heart-filled hidden <?php echo esc_attr( $icon_size ); ?> text-red-500"
			fill="currentColor"
			viewBox="0 0 24 24">
			<path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/>
		</svg>

		<!-- Processing Spinner -->
		<svg class="ats-processing-spinner hidden absolute <?php echo esc_attr( $icon_size ); ?> animate-spin text-gray-400"
			fill="none"
			viewBox="0 0 24 24">
			<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
			<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
		</svg>

		<?php if ( $show_tooltip ) : ?>
			<!-- Tooltip: Add to Favorites -->
			<span class="ats-tooltip-add absolute -top-10 left-1/2 -translate-x-1/2 px-3 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
				<?php esc_html_e( 'Add to favorites', 'woocommerce' ); ?>
				<span class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></span>
			</span>

			<!-- Tooltip: Remove from Favorites -->
			<span class="ats-tooltip-remove hidden absolute -top-10 left-1/2 -translate-x-1/2 px-3 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
				<?php esc_html_e( 'Remove from favorites', 'woocommerce' ); ?>
				<span class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></span>
			</span>
		<?php endif; ?>
	</button>
	<?php
}

/**
 * Add processing spinner handling via CSS
 */
function ats_favorite_button_styles() {
	?>
	<style>
		.ats-favorite-btn.ats-processing .ats-heart-outline,
		.ats-favorite-btn.ats-processing .ats-heart-filled {
			display: none;
		}
		.ats-favorite-btn.ats-processing .ats-processing-spinner {
			display: block;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'ats_favorite_button_styles' );
