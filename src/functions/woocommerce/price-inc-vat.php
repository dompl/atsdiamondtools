<?php
/**
 * Visible VAT-inclusive price on single product pages.
 *
 * The shop shows prices ex VAT ("£67.74 +VAT") but Google Merchant Center
 * requires UK prices including VAT, so the feed sends VAT-inclusive prices.
 * Google compares the feed against the price it can read on the landing page
 * and was flagging every product as a price mismatch. This adds a small
 * "£81.29 inc VAT" line under the main price (per variation, updated by JS)
 * so the page and the feed agree. Pairs with the VAT-inclusive offers in the
 * ats-product-schema mu-plugin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a product should show a VAT-inclusive line at all.
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function ats_price_inc_vat_applies( $product ) {
	return $product instanceof WC_Product && wc_tax_enabled() && 'taxable' === $product->get_tax_status();
}

/**
 * "£81.29 inc VAT" for a simple product/variation, "From £81.29 inc VAT" for a variable one.
 *
 * @param WC_Product $product Product.
 * @return string Plain text with the price markup, or '' when not applicable.
 */
function ats_price_inc_vat_text( $product ) {
	if ( ! ats_price_inc_vat_applies( $product ) ) {
		return '';
	}
	if ( $product->is_type( 'variable' ) ) {
		$min = $product->get_variation_price( 'min', false );
		if ( '' === $min ) {
			return '';
		}
		$children = $product->get_visible_children();
		$first    = $children ? wc_get_product( $children[0] ) : null;
		$price    = $first ? wc_get_price_including_tax( $first, array( 'price' => $min ) ) : $min;
		return 'From ' . wc_price( $price ) . ' inc VAT';
	}
	if ( '' === $product->get_price() ) {
		return '';
	}
	return wc_price( wc_get_price_including_tax( $product ) ) . ' inc VAT';
}

/**
 * Output the line under the main price (hooked from content-single-product.php).
 *
 * @param WC_Product $product Product.
 */
function ats_price_inc_vat_line( $product ) {
	$text = ats_price_inc_vat_text( $product );
	if ( '' === $text ) {
		return;
	}
	echo '<p id="ats-product-price-inc-vat" class="ats-price-inc-vat" style="margin:-.5rem 0 .75rem;font-size:.875rem;color:#6b7280">'
		. wp_kses_post( $text ) . '</p>';
}
add_action( 'ats_after_main_price', 'ats_price_inc_vat_line' );

/**
 * Give the variation JS the VAT-inclusive price for the selected variation.
 *
 * @param array                $data      Variation data for the front end.
 * @param WC_Product_Variable  $product   Parent.
 * @param WC_Product_Variation $variation Variation.
 * @return array
 */
function ats_price_inc_vat_variation_data( $data, $product, $variation ) {
	$data['ats_inc_vat_html'] = ats_price_inc_vat_text( $variation );
	return $data;
}
add_filter( 'woocommerce_available_variation', 'ats_price_inc_vat_variation_data', 10, 3 );

/**
 * Swap the line to the selected variation's price, and back on reset.
 */
function ats_price_inc_vat_js() {
	if ( ! is_product() ) {
		return;
	}
	$js = <<<'JS'
(function ($) {
	var $el = $('#ats-product-price-inc-vat');
	if (!$el.length) { return; }
	var original = $el.html();
	$('form.variations_form')
		.on('found_variation', function (e, v) { if (v && v.ats_inc_vat_html) { $el.html(v.ats_inc_vat_html); } })
		.on('reset_data', function () { $el.html(original); });
})(jQuery);
JS;
	wp_add_inline_script( 'wc-add-to-cart-variation', $js );
}
add_action( 'wp_enqueue_scripts', 'ats_price_inc_vat_js', 30 );
