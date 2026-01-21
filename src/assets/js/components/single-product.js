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

	// Initialize Custom Dropdowns
	initCustomDropdowns();

	// Initialize AJAX Add to Cart
	initAjaxAddToCart();
}

/**
 * AJAX Add to Cart for Single Product
 */
function initAjaxAddToCart() {
	const $form = $('form.cart');

	$form.on('submit', function (e) {
		e.preventDefault();

		const $this = $(this);
		const $btn = $this.find('.single_add_to_cart_button');

		if ($btn.hasClass('disabled') || $btn.hasClass('loading')) {
			return;
		}

		// Add loading state
		$btn.addClass('loading');

		const formData = new FormData($this[0]);
		formData.append('add-to-cart', $this.find('[name="add-to-cart"]').val() || $this.find('[name="product_id"]').val() || $('input[name="product_id"]').val());

		$.ajax({
			url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (response) {
				if (response.error && response.product_url) {
					window.location = response.product_url;
					return;
				}

				// Trigger event so minicart updates
				$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);

				// Open MiniCart if available
				if (window.ATSMiniCartModal && typeof window.ATSMiniCartModal.open === 'function') {
					window.ATSMiniCartModal.open();
				} else if (window.ATSMiniCart && window.ATSMiniCart.modal) {
					// Fallback check
					window.ATSMiniCart.modal.open();
				}

				$btn.removeClass('loading');
			},
			error: function () {
				// Fallback to standard submit
				$this.off('submit').submit();
			},
		});
	});
}

/**
 * Quantity Buttons (+/-)
 */
function initQuantityButtons() {
	$(document).on('click', '.ats-qty-btn', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const $input = $btn.closest('.ats-quantity, .quantity').find('input[type="number"], .qty');
		const currentVal = parseFloat($input.val());
		const max = parseFloat($input.attr('max'));
		const min = parseFloat($input.attr('min')) || 1;
		const step = parseFloat($input.attr('step')) || 1;

		let newVal = currentVal;

		if ($btn.hasClass('ats-qty-plus')) {
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
	const $priceHtml = $('#ats-product-main-price');

	// Store original price html
	if ($priceHtml.length) {
		$priceHtml.data('original-html', $priceHtml.html());
	}

	if ($form.length === 0) return;

	$form.on('found_variation', function (event, variation) {
		if (variation.price_html) {
			// Update the main price and add VAT suffix if needed
			let priceHtml = variation.price_html;

			// Check if tax_label is already there (some plugins add it)
			if (priceHtml && !priceHtml.includes('VAT') && !priceHtml.includes('tax_label')) {
				priceHtml += ' <span class="tax_label">+VAT</span>';
			}

			// Only update if we have a target
			if ($priceHtml.length) {
				$priceHtml.html(priceHtml);
			}
		}
	});

	$form.on('reset_data', function () {
		// Reset to variable price range
		if ($priceHtml.length && $priceHtml.data('original-html')) {
			$priceHtml.html($priceHtml.data('original-html'));
		}
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

/**
 * Initialize Flowbite Dropdowns for Variations
 */
function initCustomDropdowns() {
	const $form = $('form.variations_form');

	// Helper to refresh options from select
	const refreshDropdown = ($wrapper) => {
		const $select = $wrapper.find('select');
		const $list = $wrapper.find('.dropdown-options-list');
		const $btnText = $wrapper.find('.dropdown-selected-text');

		$list.empty();

		// Update selected text based on current value
		const currentVal = $select.val();
		if (currentVal) {
			const $selectedOpt = $select.find('option[value="' + currentVal.replace(/"/g, '\\"') + '"]');
			if ($selectedOpt.length) {
				$btnText.text($selectedOpt.text());
			}
		} else {
			$btnText.text('Choose an option');
		}

		// Rebuild list
		$select.find('option').each(function () {
			const $opt = $(this);
			const value = $opt.val();
			const text = $opt.text();

			if (!value) return; // Skip placeholder

			const li = $('<li>');
			const btn = $('<button type="button">').addClass('ats-dropdown-option w-full text-left inline-flex px-4 py-2 hover:bg-gray-100 transition-colors duration-150').data('value', value).text(text);

			// Active state
			if (currentVal === value) {
				btn.addClass('bg-gray-100 text-primary-600 font-bold');
			} else {
				btn.addClass('text-gray-700 dark:text-gray-200');
			}

			li.append(btn);
			$list.append(li);
		});
	};

	// Initial Population
	$('.flowbite-dropdown-wrapper').each(function () {
		refreshDropdown($(this));
	});

	// Listen for WC updates
	$form.on('woocommerce_update_variation_values', function () {
		$('.flowbite-dropdown-wrapper').each(function () {
			refreshDropdown($(this));
		});
	});

	// Handle Option Click
	$(document).on('click', '.ats-dropdown-option', function (e) {
		e.preventDefault();
		const $option = $(this);
		const value = $option.data('value');
		const $wrapper = $option.closest('.flowbite-dropdown-wrapper');
		const $select = $wrapper.find('select');
		const $btn = $wrapper.find('.ats-dropdown-trigger');

		// Logic to close dropdown (simulate click on trigger if using Flowbite toggle)
		$btn.click();

		// Update Select
		$select.val(value).trigger('change');
	});

	// Sync if select changes elsewhere (e.g. reset)
	$('.flowbite-dropdown-wrapper select').on('change', function () {
		const $wrapper = $(this).closest('.flowbite-dropdown-wrapper');
		refreshDropdown($wrapper);
	});
}
