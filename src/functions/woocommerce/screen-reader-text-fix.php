<?php
/**
 * Visually hide `.screen-reader-text`.
 *
 * The theme is missing the standard WordPress/WooCommerce "visually hidden" rule
 * for `.screen-reader-text` (computed position:static / overflow:visible), so the
 * accessibility-only text leaks into the page. It's most visible on sale/clearance
 * VARIABLE products: selecting a size injects WooCommerce's sale-price markup, and
 * its "Original price was: …" / "Current price is: …" screen-reader spans showed up
 * inside the price. This restores the standard rule front-end-wide so prices read
 * cleanly (struck original + discounted) while keeping the text for screen readers.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the visually-hidden rule for .screen-reader-text on the front end.
 *
 * @return void
 */
function ats_screen_reader_text_fix() {
	if ( is_admin() ) {
		return;
	}
	wp_register_style( 'ats-a11y-fix', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'ats-a11y-fix' );
	wp_add_inline_style(
		'ats-a11y-fix',
		'.screen-reader-text{border:0!important;clip:rect(1px,1px,1px,1px)!important;clip-path:inset(50%)!important;height:1px!important;width:1px!important;margin:-1px!important;padding:0!important;overflow:hidden!important;position:absolute!important;white-space:nowrap!important;word-wrap:normal!important}'
	);
}
add_action( 'wp_enqueue_scripts', 'ats_screen_reader_text_fix', 20 );
