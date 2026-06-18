<?php
/**
 * Clearance Pop-up Modal
 *
 * Site-wide clearance announcement modal. Rendering is gated by
 * ats_clearance_popup_should_render(); behaviour (delay, frequency capping,
 * open/close, focus restore) lives in
 * assets/js/components/clearance-popup.js.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ats_clearance_popup_should_render' ) ) {
	/**
	 * Decide whether the clearance pop-up should render for this request.
	 *
	 * Suppressed when disabled, on the clearance category/products themselves,
	 * and on cart/checkout/account pages.
	 *
	 * @return bool
	 */
	function ats_clearance_popup_should_render() {
		$should = true;

		if ( ! function_exists( 'get_field' ) || ! get_field( 'clearance_popup_enabled', 'option' ) ) {
			$should = false;
		} elseif ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			$should = false;
		} elseif ( function_exists( 'is_product_category' ) && is_product_category( 'clearance' ) ) {
			$should = false;
		} elseif ( function_exists( 'is_product' ) && is_product() && has_term( 'clearance', 'product_cat', get_the_ID() ) ) {
			$should = false;
		}

		/**
		 * Filter whether the clearance pop-up renders for the current request.
		 *
		 * @param bool $should Whether to render.
		 */
		return (bool) apply_filters( 'ats_clearance_popup_should_render', $should );
	}
}

if ( ! ats_clearance_popup_should_render() ) {
	return;
}

// --- Gather settings -----------------------------------------------------
$ats_cp_tag         = (string) get_field( 'clearance_popup_tag', 'option' );
$ats_cp_heading     = (string) get_field( 'clearance_popup_heading', 'option' );
$ats_cp_description = (string) get_field( 'clearance_popup_description', 'option' );
$ats_cp_button      = (string) get_field( 'clearance_popup_button_label', 'option' );
$ats_cp_link        = get_field( 'clearance_popup_link', 'option' );
$ats_cp_image_id    = (int) get_field( 'clearance_popup_image', 'option' );
$ats_cp_delay       = (int) get_field( 'clearance_popup_delay', 'option' );
$ats_cp_freq_mode   = (string) get_field( 'clearance_popup_frequency_mode', 'option' );
$ats_cp_freq_days   = (int) get_field( 'clearance_popup_frequency_days', 'option' );

// Fallbacks.
if ( '' === $ats_cp_heading ) {
	$ats_cp_heading = 'Clearance Sale Now On';
}
if ( '' === $ats_cp_button ) {
	$ats_cp_button = 'Shop Clearance';
}
if ( empty( $ats_cp_link ) ) {
	$ats_cp_term = get_term_by( 'slug', 'clearance', 'product_cat' );
	$ats_cp_link = ( $ats_cp_term && ! is_wp_error( $ats_cp_term ) ) ? get_term_link( $ats_cp_term ) : home_url( '/product-category/clearance/' );
}
if ( is_wp_error( $ats_cp_link ) ) {
	$ats_cp_link = home_url( '/product-category/clearance/' );
}
if ( $ats_cp_delay < 0 ) {
	$ats_cp_delay = 2;
}
if ( '' === $ats_cp_freq_mode ) {
	$ats_cp_freq_mode = 'session';
}
if ( $ats_cp_freq_days < 1 ) {
	$ats_cp_freq_days = 30;
}

// Image (optional).
$ats_cp_image_url = '';
$ats_cp_image_alt = '';
if ( $ats_cp_image_id && function_exists( 'wpimage' ) ) {
	$ats_cp_image_url = (string) wpimage( $ats_cp_image_id, [ 600, 800 ], false, true );
	$ats_cp_image_alt = (string) get_post_meta( $ats_cp_image_id, '_wp_attachment_image_alt', true );
}

$ats_cp_has_image   = '' !== $ats_cp_image_url;
$ats_cp_panel_class = 'ats-clearance-popup' . ( $ats_cp_has_image ? '' : ' ats-clearance-popup--no-image' );
?>
<div
	id="ats-clearance-popup"
	class="ats-clearance-popup-overlay hidden"
	tabindex="-1"
	aria-hidden="true"
	data-delay="<?php echo esc_attr( $ats_cp_delay ); ?>"
	data-frequency-mode="<?php echo esc_attr( $ats_cp_freq_mode ); ?>"
	data-frequency-days="<?php echo esc_attr( $ats_cp_freq_days ); ?>"
	data-storage-key="ats_clearance_popup_dismissed"
>
	<div class="<?php echo esc_attr( $ats_cp_panel_class ); ?>" role="dialog" aria-modal="true" aria-labelledby="ats-clearance-popup-heading">
		<button type="button" class="ats-clearance-popup__close" data-modal-hide="ats-clearance-popup" aria-label="Close">
			<svg class="ats-clearance-popup__close-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14">
				<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
			</svg>
		</button>

		<?php if ( $ats_cp_has_image ) : ?>
			<div class="ats-clearance-popup__media">
				<img src="<?php echo esc_url( $ats_cp_image_url ); ?>" alt="<?php echo esc_attr( $ats_cp_image_alt ); ?>" />
			</div>
		<?php endif; ?>

		<div class="ats-clearance-popup__content">
			<?php if ( '' !== $ats_cp_tag ) : ?>
				<span class="ats-clearance-popup__tag"><?php echo esc_html( $ats_cp_tag ); ?></span>
			<?php endif; ?>

			<h2 id="ats-clearance-popup-heading" class="ats-clearance-popup__heading"><?php echo esc_html( $ats_cp_heading ); ?></h2>

			<?php if ( '' !== $ats_cp_description ) : ?>
				<div class="ats-clearance-popup__description"><?php echo wp_kses_post( wpautop( $ats_cp_description ) ); ?></div>
			<?php endif; ?>

			<a href="<?php echo esc_url( $ats_cp_link ); ?>" class="ats-clearance-popup__cta">
				<?php echo esc_html( $ats_cp_button ); ?>
			</a>
		</div>
	</div>
</div>
