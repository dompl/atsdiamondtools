<?php
/**
 * ATS Product Display Shortcode
 *
 * Displays a single WooCommerce product in grid or list layout.
 *
 * @package ATS Diamond Tools
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the ats_product shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ats_product_shortcode( $atts ) {
	// Check if WooCommerce is active
	if ( !class_exists( 'WooCommerce' ) ) {
		return '<!-- ATS Product: WooCommerce is required -->';
	}

	// Parse shortcode attributes
	$atts = shortcode_atts( [
		'id'      => '',
		'display' => '1'
	], $atts, 'ats_product' );

	// Validate required product ID
	$product_id = absint( $atts['id'] );
	if ( empty( $atts['id'] ) || !is_numeric( $atts['id'] ) || $product_id <= 0 ) {
		return '<!-- ATS Product: Product ID is required -->';
	}

	// Get the product object
	$product = wc_get_product( $product_id );

	// Check if product exists and is published
	if ( !$product || $product->get_status() !== 'publish' ) {
		return '<!-- ATS Product: Product not found or not published -->';
	}

	// Allow modification of product before rendering
	$product = apply_filters( 'ats_product_before_render', $product, $atts );

	// Get product data
	$display_type  = sanitize_text_field( $atts['display'] );
	$product_title = $product->get_name();
	$product_url   = $product->get_permalink();
	$image_id      = get_post_thumbnail_id( $product->get_id() );
	$is_variable   = $product->is_type( 'variable' );

	// Get categories
	$categories    = wp_get_post_terms( $product->get_id(), 'product_cat', ['fields' => 'names'] );
	$category_text = !empty( $categories ) ? implode( ', ', $categories ) : '';

	// Get price with VAT suffix
	$price_html = ats_get_product_price_html( $product );

	// Get rating
	$rating_count   = $product->get_review_count();
	$average_rating = $product->get_average_rating();
	$rating_html    = ats_get_star_rating_html( $average_rating, $rating_count );

	// Button text
	$button_text = $is_variable ? 'Select Size' : 'Add to Cart';

	// Render based on display type
	if ( $display_type === '2' ) {
		$html = ats_render_product_list( $product, $image_id, $category_text, $product_title, $rating_html, $price_html, $button_text, $product_url );
	} else {
		$html = ats_render_product_card( $product, $image_id, $category_text, $product_title, $rating_html, $price_html, $button_text, $product_url );
	}

	// Allow filtering of the output
	return apply_filters( 'ats_product_html', $html, $product, $atts );
}
add_shortcode( 'ats_product', 'ats_product_shortcode' );

/**
 * Get formatted price HTML with VAT suffix
 *
 * @param WC_Product $product Product object
 * @return string Formatted price HTML
 */
function ats_get_product_price_html( $product ) {
	if ( $product->is_type( 'variable' ) ) {
		$min_price = $product->get_variation_price( 'min', true );
		return 'From: ' . wc_price( $min_price ) . ' +VAT';
	} else {
		return wc_price( $product->get_price() ) . ' +VAT';
	}
}

/**
 * Generate star rating HTML
 *
 * @param float $rating Average rating (0-5)
 * @param int $count Number of reviews
 * @return string Star rating HTML
 */
function ats_get_star_rating_html( $rating, $count ) {
	$full_stars  = floor( $rating );
	$half_star   = ( $rating - $full_stars ) >= 0.5;
	$empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );

	$html = '<div class="flex items-center gap-2.5">';
	$html .= '<div class="flex justify-start items-center gap-0.5">';

	// Full star SVG
	$full_star_svg = '<svg class="w-4 h-4 text-accent-yellow" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>';

	// Empty star SVG
	$empty_star_svg = '<svg class="w-4 h-4 text-neutral-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>';

	// Full stars
	for ( $i = 0; $i < $full_stars; $i++ ) {
		$html .= $full_star_svg;
	}

	// Half star (render as full for simplicity)
	if ( $half_star ) {
		$html .= $full_star_svg;
	}

	// Empty stars
	for ( $i = 0; $i < $empty_stars; $i++ ) {
		$html .= $empty_star_svg;
	}

	$html .= '</div>';
	$html .= '<span class="text-neutral-500 text-xs font-light">' . esc_html( $count ) . ' Reviews</span>';
	$html .= '</div>';

	return $html;
}

/**
 * Render product card (Display 1 - Grid layout)
 *
 * @param WC_Product $product Product object
 * @param int $image_id Image attachment ID
 * @param string $category_text Category names
 * @param string $product_title Product title
 * @param string $rating_html Rating HTML
 * @param string $price_html Price HTML
 * @param string $button_text Button text
 * @param string $product_url Product URL
 * @return string HTML output
 */
function ats_render_product_card( $product, $image_id, $category_text, $product_title, $rating_html, $price_html, $button_text, $product_url ) {
	// Get image URL using wpimage() - 224x224 for grid layout with retina support
	$image_url = $image_id ? wpimage( $image_id, [224, 224], false, true, true ) : wc_placeholder_img_src( 'large' );

	ob_start();
	?>
	<article class="rfs-ref-product-card ats-product-card inline-flex flex-col w-72 border border-neutral-200 hover:border-neutral-200 rounded p-4 bg-white relative" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-display-type="1">
		<button class="rfs-ref-product-expand-btn absolute top-2 right-2 z-10 p-0 hover:opacity-70 transition-opacity" aria-label="Expand product">
			<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#DEDEDE"><path d="M200-120q-33 0-56.5-23.5T120-200v-160h80v160h160v80H200Zm400 0v-80h160v-160h80v160q0 33-23.5 56.5T760-120H600ZM120-600v-160q0-33 23.5-56.5T200-840h160v80H200v160h-80Zm640 0v-160H600v-80h160q33 0 56.5 23.5T840-760v160h-80Z"/></svg>
		</button>
		<a href="<?php echo esc_url( $product_url ); ?>" class="rfs-ref-product-image-link relative mb-4 flex justify-center">
			<img
				src="<?php echo esc_url( $image_url ); ?>"
				alt="<?php echo esc_attr( $product_title ); ?>"
				class="rfs-ref-product-image w-56 h-56 object-contain"
				loading="lazy"
			/>
		</a>

		<div class="rfs-ref-product-category flex items-center gap-1.5 text-xs text-black font-light mb-2">
			<svg class="w-3.5 h-3.5 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
			</svg>
			<span><?php echo esc_html( $category_text ); ?></span>
		</div>

		<h3 class="rfs-ref-product-title text-lg font-bold text-neutral-700 leading-6 mb-3">
			<a href="<?php echo esc_url( $product_url ); ?>" class="rfs-ref-product-title-link hover:text-black">
				<?php echo esc_html( $product_title ); ?>
			</a>
		</h3>

		<div class="rfs-ref-product-rating mb-4">
			<?php echo $rating_html; ?>
		</div>

		<div class="rfs-ref-product-footer flex justify-between items-center mt-auto">
			<span class="rfs-ref-product-price text-sm font-bold text-black"><?php echo wp_kses_post( $price_html ); ?></span>
			<a
				href="<?php echo esc_url( $product_url ); ?>"
				class="rfs-ref-product-cta-btn inline-flex justify-center items-center px-4 py-1.5 bg-accent-yellow hover:bg-yellow-500 text-black text-xs font-bold uppercase rounded transition-colors"
			>
				<?php echo esc_html( $button_text ); ?>
			</a>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Render product list item (Display 2 - Horizontal layout)
 *
 * @param WC_Product $product Product object
 * @param int $image_id Image attachment ID
 * @param string $category_text Category names
 * @param string $product_title Product title
 * @param string $rating_html Rating HTML
 * @param string $price_html Price HTML
 * @param string $button_text Button text
 * @param string $product_url Product URL
 * @return string HTML output
 */
function ats_render_product_list( $product, $image_id, $category_text, $product_title, $rating_html, $price_html, $button_text, $product_url ) {
	// Get image URL using wpimage() - 160x160 for list layout with retina support
	$image_url = $image_id ? wpimage( $image_id, [160, 160], false, true, true ) : wc_placeholder_img_src( 'medium' );

	ob_start();
	?>
	<article class="rfs-ref-product-list ats-product-list inline-flex w-[580px] border border-neutral-200 hover:border-neutral-200 rounded p-4 gap-6 bg-white relative" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-display-type="2">
		<button class="rfs-ref-product-list-expand-btn absolute top-2 right-2 z-10 p-0 hover:opacity-70 transition-opacity" aria-label="Expand product">
			<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#DEDEDE"><path d="M200-120q-33 0-56.5-23.5T120-200v-160h80v160h160v80H200Zm400 0v-80h160v-160h80v160q0 33-23.5 56.5T760-120H600ZM120-600v-160q0-33 23.5-56.5T200-840h160v80H200v160h-80Zm640 0v-160H600v-80h160q33 0 56.5 23.5T840-760v160h-80Z"/></svg>
		</button>
		<a href="<?php echo esc_url( $product_url ); ?>" class="rfs-ref-product-list-image-link flex-shrink-0 flex items-center">
			<img
				src="<?php echo esc_url( $image_url ); ?>"
				alt="<?php echo esc_attr( $product_title ); ?>"
				class="rfs-ref-product-list-image w-40 h-40 object-contain"
				loading="lazy"
			/>
		</a>

		<div class="rfs-ref-product-list-content flex flex-col justify-center flex-grow">
			<div class="rfs-ref-product-list-category flex items-center gap-1.5 text-xs text-black font-light mb-2">
				<svg class="w-3.5 h-3.5 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
				</svg>
				<span><?php echo esc_html( $category_text ); ?></span>
			</div>

			<h3 class="rfs-ref-product-list-title text-lg font-bold text-neutral-700 leading-6 mb-3">
				<a href="<?php echo esc_url( $product_url ); ?>" class="rfs-ref-product-list-title-link hover:text-black">
					<?php echo esc_html( $product_title ); ?>
				</a>
			</h3>

			<div class="rfs-ref-product-list-rating mb-4">
				<?php echo $rating_html; ?>
			</div>

			<div class="rfs-ref-product-list-footer flex justify-between items-center">
				<span class="rfs-ref-product-list-price text-sm font-bold text-black"><?php echo wp_kses_post( $price_html ); ?></span>
				<a
					href="<?php echo esc_url( $product_url ); ?>"
					class="rfs-ref-product-list-cta-btn inline-flex justify-center items-center px-4 py-1.5 bg-accent-yellow hover:bg-yellow-500 text-black text-xs font-bold uppercase rounded transition-colors"
				>
					<?php echo esc_html( $button_text ); ?>
				</a>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}
