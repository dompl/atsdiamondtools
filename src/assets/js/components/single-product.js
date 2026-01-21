/**
 * Single Product JavaScript
 *
 * Handles product page interactions:
 * - Image zooming / Lightbox
 * - Variation price updates
 * - Quantity selector buttons (+/-)
 * - "Shop By Category" toggle
 */

import Splide from '@splidejs/splide';
import $ from 'jquery';

// Store splide instance globally for access
let mainSplide = null;

export function initSingleProduct() {
	if (!$('body').hasClass('single-product')) {
		return;
	}

	// Initialize Quantity Buttons
	initQuantityButtons();

	// Initialize Splide Gallery
	initProductGallery();

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
 * Initialize Splide Gallery & Lightbox
 */
function initProductGallery() {
	const mainSliderEl = document.querySelector('#product-main-splide');
	const thumbSliderEl = document.querySelector('#product-thumbnail-splide');

	if (mainSliderEl && thumbSliderEl) {
		// Main Slider
		mainSplide = new Splide(mainSliderEl, {
			type: 'fade', // Fade transition for main image
			rewind: true,
			pagination: false,
			arrows: false, // No arrows on main image
			heightRatio: 0.8, // Fallback aspect ratio
			classes: {
				pagination: 'splide__pagination bottom-4',
				page: 'splide__pagination__page w-2 h-2 bg-gray-300 rounded-full mx-1 opacity-100 [&.is-active]:bg-primary-600 [&.is-active]:scale-125 transition-all',
			},
		});

		// Thumbnail Slider
		const thumbSplide = new Splide(thumbSliderEl, {
			fixedWidth: 95, // Roughly w-20
			fixedHeight: 95, // Roughly h-20
			gap: 10,
			rewind: true,
			pagination: false,
			isNavigation: true, // Acts as nav for main slider
			arrows: true, // Show arrows if needed
			breakpoints: {
				600: {
					fixedWidth: 60,
					fixedHeight: 60,
				},
			},
			classes: {
				arrows: 'splide__arrows splide__arrows--ltr absolute top-1/2 w-full flex justify-between z-10 -translate-y-1/2 pointer-events-none',
				arrow: 'splide__arrow w-10 h-10 bg-transparent shadow-none flex items-center justify-center transition-opacity pointer-events-auto hover:opacity-70',
				prev: 'splide__arrow--prev !-left-12',
				next: 'splide__arrow--next !-right-12',
			},
		});

		// Sync main slider to thumbnails
		mainSplide.sync(thumbSplide);
		mainSplide.mount();
		thumbSplide.mount();
	} else if (mainSliderEl) {
		// Fallback if only one slider exists (e.g. no thumbnails)
		mainSplide = new Splide(mainSliderEl, {
			type: 'slide',
			perPage: 1,
			arrows: true,
			pagination: true,
			heightRatio: 0.8, // Fallback aspect ratio
			autoHeight: true, // Adapt to image height
			classes: {
				arrows: 'splide__arrows absolute top-1/2 w-full flex justify-between px-4 z-10 -translate-y-1/2',
				arrow: 'splide__arrow w-10 h-10 bg-white/80 hover:bg-white rounded-full shadow-md flex items-center justify-center transition-colors',
				prev: 'splide__arrow--prev',
				next: 'splide__arrow--next',
				pagination: 'splide__pagination bottom-4',
				page: 'splide__pagination__page w-2 h-2 bg-gray-300 rounded-full mx-1 opacity-100 [&.is-active]:bg-primary-600 [&.is-active]:scale-125 transition-all',
			},
		}).mount();
	}

	// Lightbox Logic
	const $modal = $('#product-lightbox-modal');
	const $modalImg = $modal.find('img');
	const $closeBtn = $modal.find('.lightbox-close');

	$(document).on('click', '.product-gallery-lightbox-trigger', function (e) {
		e.preventDefault();
		const fullSrc = $(this).attr('href');
		$modalImg.attr('src', fullSrc);

		$modal.removeClass('hidden');
		// Small delay for fade in
		setTimeout(() => {
			$modal.removeClass('opacity-0');
		}, 10);
	});

	const closeModal = () => {
		$modal.addClass('opacity-0');
		setTimeout(() => {
			$modal.addClass('hidden');
			$modalImg.attr('src', '');
		}, 300);
	};

	$closeBtn.on('click', closeModal);
	$modal.on('click', function (e) {
		if (e.target === this || $(e.target).hasClass('lightbox-content')) {
			closeModal();
		}
	});

	// Escape key close
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && !$modal.hasClass('hidden')) {
			closeModal();
		}
	});
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
	// Updated selector to target the Splide slide image directly (first slide of main slider)
	const $mainImg = $('#product-main-splide .splide__slide').first().find('img');
	const $mainLink = $mainImg.closest('a');

	// Store original data
	if ($priceHtml.length) {
		$priceHtml.data('original-html', $priceHtml.html());
	}
	if ($mainImg.length) {
		$mainImg.data('original-src', $mainImg.attr('src'));
		$mainImg.data('original-srcset', $mainImg.attr('srcset'));
		$mainImg.data('original-sizes', $mainImg.attr('sizes'));
		$mainImg.data('original-alt', $mainImg.attr('alt'));
		if ($mainLink.length) {
			$mainImg.data('original-href', $mainLink.attr('href'));
		}
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

		// Update Image (Splide Aware) - Enhanced matching logic
		if (variation.image && variation.image.src && variation.image.src.length > 1 && mainSplide) {
			let foundIndex = -1;

			// Get the variation image ID from multiple possible locations
			const variationImageId = variation.image_id || (variation.image && variation.image.image_id);

			if (variationImageId) {
				// 1. Try matching by data-image-id attribute
				const slides = mainSplide.Components.Slides.get();
				slides.forEach((slide, index) => {
					const slideImageId = $(slide.slide).attr('data-image-id');
					// Compare as strings to avoid type issues
					if (slideImageId && slideImageId == variationImageId) {
						foundIndex = index;
					}
				});
			}

			// 2. Fallback: Try matching by thumbnail URL or gallery URL
			if (foundIndex === -1) {
				const slides = mainSplide.Components.Slides.get();
				const varThumb = variation.image.thumb_src || variation.image.src;
				const varSrc = variation.image.src;
				const varFullSrc = variation.image.full_src;

				slides.forEach((slide, index) => {
					const $img = $(slide.slide).find('img');
					const imgSrc = $img.attr('src');
					const imgSrcset = $img.attr('srcset') || '';

					// Check if any variation image URL matches the slide image
					if (imgSrc === varSrc || imgSrc === varFullSrc || imgSrc === varThumb ||
						imgSrcset.includes(varSrc) || imgSrcset.includes(varThumb)) {
						foundIndex = index;
					}
				});
			}

			if (foundIndex > -1) {
				// Image found in gallery - navigate to it
				mainSplide.go(foundIndex);
			} else {
				// Image not in gallery - replace first slide
				const $firstSlide = $(mainSplide.Components.Slides.getAt(0).slide);
				const $img = $firstSlide.find('img');
				const $link = $firstSlide.find('a');

				// Update main image
				$img.attr('src', variation.image.src);
				if (variation.image.srcset) {
					$img.attr('srcset', variation.image.srcset);
				} else {
					$img.removeAttr('srcset');
				}

				if (variation.image.sizes) $img.attr('sizes', variation.image.sizes);
				if (variation.image.alt) $img.attr('alt', variation.image.alt);

				// Update lightbox link
				if ($link.length) {
					if (variation.image.full_src) {
						$link.attr('href', variation.image.full_src);
					} else {
						$link.attr('href', variation.image.src);
					}
				}

				// Update thumbnail if it exists
				const thumbSlides = $('#product-thumbnail-splide .splide__slide');
				if (thumbSlides.length > 0) {
					const $firstThumb = thumbSlides.first();
					const $thumbImg = $firstThumb.find('img');
					if ($thumbImg.length && variation.image.gallery_thumbnail_src) {
						$thumbImg.attr('src', variation.image.gallery_thumbnail_src);
					} else if ($thumbImg.length) {
						$thumbImg.attr('src', variation.image.thumb_src || variation.image.src);
					}
				}

				mainSplide.go(0);
			}
		}
	});

	$form.on('reset_data', function () {
		// Reset to variable price range
		if ($priceHtml.length && $priceHtml.data('original-html')) {
			$priceHtml.html($priceHtml.data('original-html'));
		}

		if (mainSplide) {
			// Restore original image
			if ($mainImg.length && $mainImg.data('original-src')) {
				$mainImg.attr('src', $mainImg.data('original-src'));
				$mainImg.attr('srcset', $mainImg.data('original-srcset') || '');
				$mainImg.attr('sizes', $mainImg.data('original-sizes') || '');
				$mainImg.attr('alt', $mainImg.data('original-alt') || '');

				if ($mainLink.length && $mainImg.data('original-href')) {
					$mainLink.attr('href', $mainImg.data('original-href'));
				}
			}
			mainSplide.go(0);
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
