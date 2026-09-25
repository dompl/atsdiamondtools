<?php
/**
 * Front-end performance tweaks (LCP, CLS, JS deferral).
 *
 * - Stops WP Rocket lazy-loading above-the-fold images (hero banner,
 *   category banner, header logo). Rocket otherwise wraps even
 *   fetchpriority="high" images in a placeholder, which is the main
 *   reason the mobile LCP sits around 6-7 seconds.
 * - Keeps WordPress core "dist" scripts (wp-data etc.) out of Rocket's
 *   defer list: Stripe's express-checkout inline "after" scripts run
 *   synchronously and throw when wp-data has been deferred.
 * - Emits a <link rel="preload"> for the LCP image on the front page
 *   (first banner slide) and on product-category archives (banner).
 *
 * @package skylinewp-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attributes that, when present on an <img>, exclude it from Rocket lazyload.
 */
add_filter( 'rocket_lazyload_excluded_attributes', function ( $attributes ) {
	$attributes[] = 'fetchpriority="high"';
	$attributes[] = 'rfs-ref-header-logo';
	$attributes[] = 'rfs-ref-category-banner-image';
	$attributes[] = 'data-no-lazy="1"';
	return array_values( array_unique( (array) $attributes ) );
} );

/**
 * Scripts Rocket must not defer.
 */
add_filter( 'rocket_exclude_defer_js', function ( $excluded ) {
	$excluded[] = '/wp-includes/js/dist/';
	$excluded[] = '/wp-includes/js/dist/vendor/';
	return array_values( array_unique( (array) $excluded ) );
} );

/**
 * Widths used by the hero banner and the category banner. Shared so the
 * preload hint and the rendered <img> always agree.
 */
function ats_banner_srcset_widths() {
	return array( 800, 1200, 1600, 2400 );
}

function ats_category_banner_srcset_widths() {
	return array( 1200, 1920, 2560 );
}

function ats_hero_banner_sizes() {
	return '(min-width: 1280px) 1248px, 100vw';
}

function ats_category_banner_sizes() {
	return '(min-width: 1280px) 1248px, 100vw';
}

/**
 * Build a width-based srcset for an attachment at a given aspect ratio.
 *
 * @param int   $image_id Attachment ID.
 * @param int[] $widths   Target widths.
 * @param float $ratio    width / height.
 * @return array{src:string,srcset:string,width:int,height:int}
 */
function ats_build_banner_srcset( $image_id, array $widths, $ratio, $quality = 80 ) {
	$parts = array();
	$src   = '';
	$w0    = 0;
	$h0    = 0;
	foreach ( $widths as $w ) {
		$h   = (int) round( $w / $ratio );
		$q   = $w >= 1600 ? min( $quality, 72 ) : $quality;
		$url = wpimage( image: $image_id, size: array( $w, $h ), quality: $q );
		if ( ! $url ) {
			continue;
		}
		$parts[] = $url . ' ' . $w . 'w';
		if ( ! $src || $w === 1200 ) {
			$src = $url;
			$w0  = $w;
			$h0  = $h;
		}
	}
	return array(
		'src'    => $src,
		'srcset' => implode( ', ', $parts ),
		'width'  => $w0,
		'height' => $h0,
	);
}

/**
 * Preload the LCP image early in <head>.
 */
add_action( 'wp_head', function () {
	if ( is_admin() || ! function_exists( 'wpimage' ) ) {
		return;
	}

	$preload = null;

	if ( is_tax( 'product_cat' ) && function_exists( 'ats_category_banner_image_id' ) ) {
		$image_id = ats_category_banner_image_id();
		if ( $image_id ) {
			$set     = ats_build_banner_srcset( $image_id, ats_category_banner_srcset_widths(), 1920 / 400, 78 );
			$preload = array( $set, ats_category_banner_sizes() );
		}
	} elseif ( is_singular() && function_exists( 'get_field' ) ) {
		$blocks = get_field( 'content_blocks', get_queried_object_id() );
		if ( is_array( $blocks ) && ! empty( $blocks[0]['acf_fc_layout'] ) && $blocks[0]['acf_fc_layout'] === 'banner' ) {
			$slides = $blocks[0]['banner_slides'] ?? array();
			$image  = $slides[0]['image'] ?? null;
			$id     = is_array( $image ) ? ( $image['ID'] ?? $image['id'] ?? 0 ) : (int) $image;
			if ( $id ) {
				$set     = ats_build_banner_srcset( $id, ats_banner_srcset_widths(), 1200 / 500, 80 );
				$preload = array( $set, ats_hero_banner_sizes() );
			}
		}
	}

	if ( ! $preload || empty( $preload[0]['srcset'] ) ) {
		return;
	}

	printf(
		"<link rel=\"preload\" as=\"image\" href=\"%s\" imagesrcset=\"%s\" imagesizes=\"%s\" fetchpriority=\"high\">\n",
		esc_url( $preload[0]['src'] ),
		esc_attr( $preload[0]['srcset'] ),
		esc_attr( $preload[1] )
	);
}, 2 );

/**
 * The clearance bar is rendered visible in the HTML (no slide-down, which was
 * the site's largest layout shift). Visitors who dismissed it get a class on
 * <html> before first paint so it never appears for them.
 */
add_action( 'wp_head', function () {
	echo "<script>try{if(localStorage.getItem('ats_clearance_bar_dismissed')==='1'){document.documentElement.classList.add('ats-clearance-bar-dismissed');}}catch(e){}</script>\n";
}, 0 );
