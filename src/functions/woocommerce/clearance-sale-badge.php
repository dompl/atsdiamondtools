<?php
/**
 * Single-product sale presentation: green "Sale" flash + "Save £X" badge.
 *
 * On a clearance / on-sale product page the theme previously showed:
 *   - WooCommerce's default `.onsale` flash as unstyled black "Sale!" text
 *     floating above the gallery image (position:static — looked broken), and
 *   - the price as "£56.67 £28.33 +VAT" with the "+VAT" in bold black and no
 *     indication of how much the customer actually saves.
 *
 * This file (self-contained inline CSS + JS, no build step) turns the sale flash
 * into a small green badge pinned to the image corner, adds a small green
 * "Save £X" badge next to the price, and de-emphasises the "+VAT" note.
 *
 * Simple products: the saving is computed in PHP and passed to JS.
 * Variable products: the theme swaps the price client-side on `found_variation`,
 * so the saving is recomputed in JS from the variation's display prices and the
 * badge is re-inserted (and removed again on `reset_data`).
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the sale-flash / save-badge styles and behaviour on single products.
 *
 * @return void
 */
function ats_clearance_sale_badge_assets() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	// ---- CSS -------------------------------------------------------------
	wp_register_style( 'ats-sale-badge', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'ats-sale-badge' );
	wp_add_inline_style(
		'ats-sale-badge',
		// Green "Sale" flash pinned to the gallery image (no shadow, lightly rounded like the buttons).
		'#product-main-splide{position:relative}'
		. '.onsale{position:absolute;top:.75rem;left:.75rem;z-index:10;margin:0;display:inline-flex;align-items:center;'
		. 'background:#16a34a;color:#fff;font-size:.6875rem;font-weight:700;line-height:1;letter-spacing:.03em;'
		. 'text-transform:uppercase;padding:.4em .65em;border-radius:.25rem;box-shadow:none;min-height:0}'
		// Small green "Save £X" badge in the price row.
		. '.ats-save-badge{display:inline-flex;align-items:center;align-self:center;background:#16a34a;color:#fff;'
		. 'font-size:.6875rem;font-weight:700;line-height:1;letter-spacing:.02em;text-transform:uppercase;'
		. 'padding:.4em .6em;border-radius:.25rem;white-space:nowrap;box-shadow:none}'
		// De-emphasise the "+VAT" note (small, light, normal weight) in the main price.
		. '#ats-product-main-price .ats-vat-note,#ats-product-main-price .tax_label,'
		. '#ats-product-main-price .woocommerce-price-suffix{font-size:.75rem;font-weight:400;'
		. 'color:#6b7280;letter-spacing:0;text-transform:none}'
		// Struck-through "was" price (variation <del> + the simple-product one we inject), muted.
		. '#ats-product-main-price del{color:#9ca3af;font-weight:600;text-decoration:line-through}'
	);

	// ---- JS --------------------------------------------------------------
	wp_register_script( 'ats-sale-badge', false, array( 'jquery' ), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_script( 'ats-sale-badge' );

	// Saving + struck "was" price for a SIMPLE on-sale product, computed server-side.
	// (The theme's price helper only outputs the sale price for simple products, so
	// without this the badge would read "£24.75 … Save £24.75" with no visible original.
	// Variable products already show the struck price via WooCommerce's variation markup.)
	$simple_save         = '';
	$simple_regular_html = '';
	if ( ! $product->is_type( 'variable' ) && $product->is_on_sale() ) {
		$regular = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
		$active  = (float) wc_get_price_to_display( $product );
		$diff    = $regular - $active;
		if ( $diff > 0.001 ) {
			$simple_save         = html_entity_decode( wp_strip_all_tags( wc_price( $diff ) ), ENT_QUOTES, 'UTF-8' );
			$simple_regular_html = '<del class="ats-was-price" aria-hidden="true">' . wc_price( $regular ) . '</del>';
		}
	}

	wp_add_inline_script(
		'ats-sale-badge',
		'window.atsSaleBadge=' . wp_json_encode(
			array(
				'simpleSave'        => $simple_save,
				'simpleRegularHtml' => $simple_regular_html,
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
			)
		) . ';',
		'before'
	);

	wp_add_inline_script( 'ats-sale-badge', ats_clearance_sale_badge_js() );
}
add_action( 'wp_enqueue_scripts', 'ats_clearance_sale_badge_assets', 20 );

/**
 * The inline behaviour script (kept out of the enqueue function for readability).
 *
 * @return string
 */
function ats_clearance_sale_badge_js() {
	return <<<'JS'
(function ($) {
	var cfg = window.atsSaleBadge || {};
	var symbol = cfg.symbol || '£';

	function fmt(n) {
		return symbol + Number(n).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}
	function badge(text) {
		return '<span class="ats-save-badge">Save ' + text + '</span>';
	}

	// Wrap a bare "+VAT" text node so it can be visually de-emphasised.
	function muteVat($scope) {
		if (!$scope.length) { return; }
		$scope.contents().filter(function () {
			return this.nodeType === 3 && /\+\s*VAT/i.test(this.nodeValue);
		}).each(function () {
			var span = document.createElement('span');
			span.className = 'ats-vat-note';
			span.textContent = this.nodeValue.trim();
			this.parentNode.replaceChild(span, this);
		});
	}

	$(function () {
		var $price = $('#ats-product-main-price');

		// 1. Pin the WooCommerce sale flash onto the gallery image.
		var $flash = $('.onsale').first();
		var $gallery = $('#product-main-splide');
		if ($flash.length && $gallery.length) {
			$gallery.append($flash);
		}

		// 2. Simple product: show the struck "was" price + the server-computed saving.
		if (cfg.simpleSave) {
			if (cfg.simpleRegularHtml && !$price.find('.ats-was-price').length) {
				$price.prepend(cfg.simpleRegularHtml);
			}
			if (!$price.find('.ats-save-badge').length) {
				$price.append(badge(cfg.simpleSave));
			}
		}
		muteVat($price);

		// 3. Variable product: recompute the saving per selected variation.
		var $form = $('form.variations_form');
		if ($form.length) {
			$form.on('found_variation', function (event, variation) {
				// The theme replaces the price HTML synchronously in its own
				// found_variation handler; run after it so our badge survives.
				setTimeout(function () {
					$price.find('.ats-save-badge').remove();
					if (variation && variation.display_regular_price && variation.display_price) {
						var save = variation.display_regular_price - variation.display_price;
						if (save > 0.001) {
							$price.append(badge(fmt(save)));
						}
					}
					muteVat($price);
				}, 0);
			});
			$form.on('reset_data', function () {
				// The theme rebuilds the "From: £X +VAT" range here too; run after it
				// so the +VAT note stays de-emphasised and no stale save badge lingers.
				setTimeout(function () {
					$price.find('.ats-save-badge').remove();
					muteVat($price);
				}, 0);
			});
		}
	});
})(jQuery);
JS;
}
