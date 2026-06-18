<?php
/**
 * Clearance Top Bar
 *
 * Full-width announcement strip rendered as the very first element inside
 * <body> (hooked via header.php after `skyline_after_body`), so it pushes the
 * page down rather than overlaying the header. Collapsed by default; revealed
 * by assets/js/components/clearance-popup.js once the pop-up is closed (or
 * immediately for visitors who don't get the pop-up). Own dismissal, separate
 * from the pop-up.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Eligibility — mirrors the pop-up's exclusions (kept self-contained because
// this renders before the pop-up template part loads).
$ats_bar_ok = function_exists( 'get_field' ) && get_field( 'clearance_popup_enabled', 'option' );
if ( $ats_bar_ok && function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
	$ats_bar_ok = false;
}
if ( $ats_bar_ok && function_exists( 'is_product_category' ) && is_product_category( 'clearance' ) ) {
	$ats_bar_ok = false;
}
if ( $ats_bar_ok && function_exists( 'is_product' ) && is_product() && has_term( 'clearance', 'product_cat', get_the_ID() ) ) {
	$ats_bar_ok = false;
}

/**
 * Filter whether the clearance top bar renders for the current request.
 *
 * @param bool $ats_bar_ok Whether to render.
 */
$ats_bar_ok = (bool) apply_filters( 'ats_clearance_bar_should_render', $ats_bar_ok );

if ( ! $ats_bar_ok ) {
	return;
}

// Settings (fall back to sensible defaults so it works before options are saved).
$ats_bar_text   = (string) get_field( 'clearance_bar_text', 'option' );
$ats_bar_button = (string) get_field( 'clearance_bar_button_label', 'option' );
$ats_bar_link   = get_field( 'clearance_popup_link', 'option' );

if ( '' === $ats_bar_text ) {
	$ats_bar_text = 'Clearance Sale now on — limited stock while it lasts.';
}
if ( '' === $ats_bar_button ) {
	$ats_bar_button = 'Shop Clearance';
}
if ( empty( $ats_bar_link ) ) {
	$ats_bar_term = get_term_by( 'slug', 'clearance', 'product_cat' );
	$ats_bar_link = ( $ats_bar_term && ! is_wp_error( $ats_bar_term ) ) ? get_term_link( $ats_bar_term ) : home_url( '/product-category/clearance/' );
}
if ( is_wp_error( $ats_bar_link ) ) {
	$ats_bar_link = home_url( '/product-category/clearance/' );
}
?>
<div id="ats-clearance-bar" class="ats-clearance-bar" data-storage-key="ats_clearance_bar_dismissed" hidden>
	<div class="ats-clearance-bar__inner">
		<span class="ats-clearance-bar__flash" aria-hidden="true">⚡</span>
		<span class="ats-clearance-bar__text"><?php echo esc_html( $ats_bar_text ); ?></span>
		<a class="ats-clearance-bar__cta" href="<?php echo esc_url( $ats_bar_link ); ?>"><?php echo esc_html( $ats_bar_button ); ?></a>
	</div>
	<button type="button" class="ats-clearance-bar__close" aria-label="Dismiss clearance announcement">
		<svg class="ats-clearance-bar__close-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 14 14">
			<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
		</svg>
	</button>
</div>
