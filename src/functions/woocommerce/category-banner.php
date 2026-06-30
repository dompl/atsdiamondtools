<?php
/**
 * Product category banner — shared renderer.
 *
 * Single source of truth for the category banner region (the hero banner plus
 * the full-description band below it). Both the full-page archive template and
 * the AJAX shop-filter handler call this, so browsing categories with AJAX
 * produces byte-identical markup to a full page load.
 *
 * It is built entirely from the term (it does NOT rely on the main query,
 * is_product_category() or woocommerce_breadcrumb()), so it is safe to call
 * during an admin-ajax request where that query context does not exist.
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build a term-based breadcrumb trail (Home / Shop / ancestors / current).
 *
 * Mirrors the default WooCommerce breadcrumb but is derived from the term so it
 * renders correctly outside the category query context (e.g. during AJAX).
 *
 * @param WP_Term $term Product category term.
 * @return string Breadcrumb HTML, current crumb unlinked.
 */
function ats_category_breadcrumb_html( $term ) {
	$delimiter = '<span class="mx-2 opacity-60">/</span>';
	$crumbs    = array();

	// Home.
	$crumbs[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'skylinewp-dev-child' ) . '</a>';

	// Shop.
	$shop_id = wc_get_page_id( 'shop' );
	if ( $shop_id && get_post( $shop_id ) ) {
		$crumbs[] = '<a href="' . esc_url( get_permalink( $shop_id ) ) . '">' . esc_html( get_the_title( $shop_id ) ) . '</a>';
	}

	// Ancestor categories, top-down.
	$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
	foreach ( $ancestors as $ancestor_id ) {
		$ancestor = get_term( $ancestor_id, 'product_cat' );
		if ( $ancestor && ! is_wp_error( $ancestor ) ) {
			$link = get_term_link( $ancestor );
			if ( ! is_wp_error( $link ) ) {
				$crumbs[] = '<a href="' . esc_url( $link ) . '">' . esc_html( $ancestor->name ) . '</a>';
			}
		}
	}

	// Current category (not linked).
	$crumbs[] = '<span>' . esc_html( $term->name ) . '</span>';

	return implode( $delimiter, $crumbs );
}

/**
 * Render the category banner region (hero banner + full-description band).
 *
 * @param int $term_id Product category term ID.
 * @return string HTML, or empty string when the term is invalid.
 */
function ats_get_category_banner_html( $term_id ) {
	$term_id = (int) $term_id;
	$term    = $term_id ? get_term( $term_id, 'product_cat' ) : null;
	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	$category_name = $term->name;
	$category_desc = $term->description;

	// Banner image: term thumbnail, fallback to default image ID 43462.
	$thumbnail_id     = get_term_meta( $term_id, 'thumbnail_id', true );
	$banner_image_id  = $thumbnail_id ? $thumbnail_id : 43462;
	$banner_image_url = wpimage( $banner_image_id, array( 1920, 400 ), false, true, true, true, 85 );

	// ACF short banner blurb (reads term meta; query-context independent).
	$category_banner_desc = function_exists( 'get_field' ) ? get_field( 'category_banner_description', $term ) : '';

	ob_start();
	?>
	<div class="rfs-ref-category-banner-region" data-cat="<?php echo esc_attr( $term_id ); ?>">

		<!-- Category Banner -->
		<div class="rfs-ref-shop-container container mx-auto px-4 pt-4 mb-6">
			<div class="rfs-ref-category-banner relative h-[200px] md:h-[250px] overflow-hidden rounded-lg">
				<!-- Background Image -->
				<div class="absolute inset-0">
					<img src="<?php echo esc_url( $banner_image_url ); ?>"
					     alt="<?php echo esc_attr( $category_name ); ?>"
					     class="w-full h-full object-cover" />
					<!-- Overlay Gradient -->
					<div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-black/30"></div>
				</div>

				<!-- Decorative Brand Elements -->
				<div class="rfs-ref-banner-decorations absolute inset-0 pointer-events-none opacity-20">
					<div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-primary-600 blur-3xl"></div>
					<div class="absolute -bottom-16 -left-16 w-48 h-48 rounded-full bg-ats-yellow blur-2xl"></div>
					<div class="absolute top-1/2 right-1/4 w-32 h-32 rounded-full bg-primary-300 blur-xl"></div>
				</div>

				<!-- Content -->
				<div class="rfs-ref-category-banner-content relative z-10 h-full flex flex-col justify-center px-8 md:px-12">
					<div class="max-w-3xl">
						<div class="rfs-ref-category-breadcrumbs text-xs md:text-sm text-gray-200 mb-3 drop-shadow [&_a]:text-gray-200 [&_a:hover]:text-white [&_a:hover]:underline">
							<nav class="woocommerce-breadcrumb flex flex-wrap items-center" aria-label="Breadcrumb"><?php echo wp_kses_post( ats_category_breadcrumb_html( $term ) ); ?></nav>
						</div>

						<h1 class="rfs-ref-category-title text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-2 drop-shadow-lg">
							<?php echo esc_html( $category_name ); ?>
						</h1>

						<?php if ( ! empty( $category_banner_desc ) ) : ?>
							<div class="rfs-ref-category-banner-description text-sm md:text-base text-gray-200 leading-relaxed max-w-2xl drop-shadow-md">
								<?php echo wp_kses_post( $category_banner_desc ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<?php if ( ! empty( $category_desc ) ) : ?>
			<!-- Full Category Description (below banner) -->
			<div class="rfs-ref-shop-container container mx-auto px-4 mb-6">
				<div class="rfs-ref-category-full-description relative overflow-hidden rounded-lg border border-gray-200 bg-gradient-to-r from-gray-50 via-white to-gray-50 px-5 py-4 md:px-7 md:py-4">
					<span class="absolute inset-y-0 left-0 w-1 bg-ats-yellow" aria-hidden="true"></span>
					<div class="flex items-start gap-3 md:gap-4">
						<span class="hidden sm:flex shrink-0 items-center justify-center w-9 h-9 rounded-full bg-primary-600/10 text-primary-700 mt-0.5" aria-hidden="true">
							<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
						</span>
						<div class="rfs-ref-category-description-text text-sm md:text-base text-gray-600 leading-relaxed [&_p]:m-0 [&_a]:text-primary-700 [&_a]:font-semibold [&_a:hover]:underline">
							<?php echo wp_kses_post( $category_desc ); ?>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

	</div>
	<?php
	return (string) ob_get_clean();
}
