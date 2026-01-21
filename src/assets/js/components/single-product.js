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

		// Ensure wc_add_to_cart_params exists
		if (typeof wc_add_to_cart_params === 'undefined') {
			// Fallback to standard submit
			$this.off('submit').submit();
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
	const $productGallery = $('.woocommerce-product-gallery');
	const $mainImg = $productGallery.find('.woocommerce-product-gallery__image').first().find('img');

	// Store original data
	if ($priceHtml.length) {
		$priceHtml.data('original-html', $priceHtml.html());
	}
	if ($mainImg.length) {
		$mainImg.data('original-src', $mainImg.attr('src'));
		$mainImg.data('original-srcset', $mainImg.attr('srcset'));
		$mainImg.data('original-sizes', $mainImg.attr('sizes'));
		$mainImg.data('original-alt', $mainImg.attr('alt'));
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

		// Update Image
		if (variation.image && variation.image.src && variation.image.src.length > 1) {
			if ($mainImg.length) {
				$mainImg.attr('src', variation.image.src);
				if (variation.image.srcset) {
					$mainImg.attr('srcset', variation.image.srcset);
				}
				if (variation.image.sizes) {
					$mainImg.attr('sizes', variation.image.sizes);
				}
				if (variation.image.alt) {
					$mainImg.attr('alt', variation.image.alt);
				}
			}
		}
	});

	$form.on('reset_data', function () {
		// Reset to variable price range
		if ($priceHtml.length && $priceHtml.data('original-html')) {
			$priceHtml.html($priceHtml.data('original-html'));
		}
		// Reset Image
		if ($mainImg.length && $mainImg.data('original-src')) {
			$mainImg.attr('src', $mainImg.data('original-src'));
			$mainImg.attr('srcset', $mainImg.data('original-srcset') || '');
			$mainImg.attr('sizes', $mainImg.data('original-sizes') || '');
			$mainImg.attr('alt', $mainImg.data('original-alt') || '');
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
		const selectName = $select.data('attribute_name') || $select.attr('name');
		const variationsData = $form.data('product_variations') || [];

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

		// Helper to find price for a specific attribute value
		const getPriceForOption = (val) => {
			if (!variationsData.length) return null;

			// Get other selections
			const currentSelections = {};
			$form.find('select').each(function () {
				const name = $(this).data('attribute_name') || $(this).attr('name');
				if (name && name !== selectName) {
					currentSelections[name] = $(this).val();
				}
			});

			// Find matching variation
			const match = variationsData.find((v) => {
				// 1. Matches this option
				const attrVal = v.attributes[selectName];
				// attributes[selectName] can be specific value or empty string (any)
				if (attrVal && attrVal !== val) return false;

				// 2. Matches other selections
				for (const key in currentSelections) {
					const otherVal = currentSelections[key];
					// If other dropdown is "Choose option" (empty), we can't be sure, but we return first possible match
					// If other dropdown HAS value, we must match it
					if (otherVal && v.attributes[key] && v.attributes[key] !== otherVal) {
						return false;
					}
				}
				return true;
			});

			if (match && match.price_html) {
				// Strip HTML tags to get plain text price
				let tmp = document.createElement('DIV');
				tmp.innerHTML = match.price_html;
				let priceText = tmp.textContent || tmp.innerText || '';
				return priceText.trim();
			}

			return null;
		};

		// Rebuild list
		$select.find('option').each(function () {
			const $opt = $(this);
			const value = $opt.val();
			let text = $opt.text();

			if (!value) return; // Skip placeholder

			// Append Price
			const price = getPriceForOption(value);
			if (price) {
				text += ` (${price})`;
			}

			const li = $('<li>');
			const btn = $('<button type="button">').addClass('ats-dropdown-option w-full text-left inline-flex px-4 py-1 hover:bg-brand-dark transition-colors duration-150').data('value', value).text(text);

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
