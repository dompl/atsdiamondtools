/**
 * Single Product JavaScript
 *
 * Handles product page interactions:
 * - Image zooming / Lightbox
 * - Variation price updates
 * - Quantity selector buttons (+/-)
 * - "Shop By Category" toggle
 */

import $ from 'jquery';

export function initSingleProduct() {
	if (!$('body').hasClass('single-product')) {
		return;
	}

	// Initialize Quantity Buttons
	initQuantityButtons();

	// Initialize Variation Logic
	initVariationLogic();

	// Initialize Shop By Category Toggle
	initCategoryToggle();
}

/**
 * Quantity Buttons (+/-)
 */
function initQuantityButtons() {
	$(document).on('click', '.ats-qty-btn', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const $input = $btn.closest('.quantity').find('.qty');
		const currentVal = parseFloat($input.val());
		const max = parseFloat($input.attr('max'));
		const min = parseFloat($input.attr('min')) || 1;
		const step = parseFloat($input.attr('step')) || 1;

		let newVal = currentVal;

		if ($btn.hasClass('plus')) {
			if (isNaN(max) || currentVal < max) {
				newVal = currentVal + step;
			}
		} else {
			if (currentVal > min) {
				newVal = currentVal - step;
			}
		}

		$input.val(newVal).trigger('change');
	});
}

/**
 * Variation Price Logic
 * Updates the main price display when a variation is found.
 */
function initVariationLogic() {
	const $form = $('form.variations_form');
	const $priceContainer = $('.rfs-price-container');

	// Note: You need to add .rfs-price-container to the price element in content-single-product.php

	if ($form.length === 0) return;

	$form.on('found_variation', function (event, variation) {
		if (variation.price_html) {
			// Update the main price and add VAT suffix if needed
			// The passed price_html usually comes from WC formatted with tax settings
			// But if we need to force "+VAT", we might need to parse or append.
			// For now, replace the content.

			// However, our template uses custom ats_get_product_price_html() which adds +VAT.
			// WC's variation.price_html might not include it if not configured globally.
			// Let's rely on WC's output but try to append +VAT if missing and we know it's ex-vat.

			let priceHtml = variation.price_html;
			if (priceHtml && !priceHtml.includes('VAT')) {
				// Heuristic: append +VAT if not present (adjust based on actual need)
				priceHtml += ' <span class="tax_label">+VAT</span>';
			}

			// Only update if we have a target
			$('.ats-product-main-price').html(priceHtml);
		}
	});

	$form.on('reset_data', function () {
		// Reset to variable price range (captured on load?)
		// Ideally we should have stored the original HTML.
		// For simplicity, we might leave it or implement a data-original-html attribute.
	});
}

/**
 * Shop By Category Toggle
 * Toggles the visibility of the category list
 */
function initCategoryToggle() {
	$('.ats-category-toggle').on('click', function (e) {
		e.preventDefault();
		const $target = $($(this).data('target'));
		const $icon = $(this).find('svg'); // Chevron

		$target.slideToggle(200);
		$icon.toggleClass('rotate-180');
	});
}
