<?php
/**
 * Favicon Output
 *
 * Outputs favicon link tags using the ACF 'ats_site_favicon' field
 * from Header Settings. Uses wpimage() for all sizes.
 *
 * @package ATS Diamond Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output favicon link tags in wp_head.
 */
function ats_output_favicon() {
	$favicon = get_field( 'ats_site_favicon', 'option' );

	if ( ! $favicon ) {
		return;
	}

	// Get attachment ID from ACF array or numeric value
	if ( is_array( $favicon ) ) {
		$favicon_id = isset( $favicon['ID'] ) ? $favicon['ID'] : ( isset( $favicon['id'] ) ? $favicon['id'] : 0 );
	} else {
		$favicon_id = is_numeric( $favicon ) ? (int) $favicon : 0;
	}

	if ( ! $favicon_id ) {
		return;
	}

	// Standard favicon sizes
	$sizes = [
		16  => 'image/png',
		32  => 'image/png',
		96  => 'image/png',
		192 => 'image/png',
	];

	// Apple touch icon sizes
	$apple_sizes = [ 57, 60, 72, 76, 114, 120, 144, 152, 180 ];

	// Output standard favicons
	foreach ( $sizes as $size => $type ) {
		$icon_url = wpimage( image: $favicon_id, size: [ $size, $size ], retina: false, webp: false );
		if ( $icon_url ) {
			echo '<link rel="icon" type="' . esc_attr( $type ) . '" sizes="' . esc_attr( $size . 'x' . $size ) . '" href="' . esc_url( $icon_url ) . '">' . "\n";
		}
	}

	// Output Apple touch icons
	foreach ( $apple_sizes as $size ) {
		$icon_url = wpimage( image: $favicon_id, size: [ $size, $size ], retina: false, webp: false );
		if ( $icon_url ) {
			echo '<link rel="apple-touch-icon" sizes="' . esc_attr( $size . 'x' . $size ) . '" href="' . esc_url( $icon_url ) . '">' . "\n";
		}
	}

	// MS Tile icon (144x144)
	$ms_icon_url = wpimage( image: $favicon_id, size: [ 144, 144 ], retina: false, webp: false );
	if ( $ms_icon_url ) {
		echo '<meta name="msapplication-TileImage" content="' . esc_url( $ms_icon_url ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'ats_output_favicon', 1 );

/**
 * Disable the default WordPress and parent theme site icon output to prevent duplicates.
 */
function ats_disable_default_site_icon() {
	$favicon = get_field( 'ats_site_favicon', 'option' );
	if ( $favicon ) {
		remove_action( 'wp_head', 'wp_site_icon', 99 );
		remove_action( 'wp_head', 'skylinewp_output_site_icon', 1 );
	}
}
add_action( 'wp_head', 'ats_disable_default_site_icon', 0 );
