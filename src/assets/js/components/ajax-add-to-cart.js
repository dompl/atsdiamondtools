/**
 * AJAX Add to Cart for Simple Products
 *
 * Handles adding simple products to cart via AJAX without page navigation
 * Opens the mini cart modal after successful add to cart
 *
 * @package SkylineWP Dev Child
 */

(function ($) {
	'use strict';

	/**
	 * AJAX Add to Cart Handler
	 */
	const AJAXAddToCart = {
		/**
		 * Initialize
		 */
		init: function () {
			this.bindEvents();
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Delegate click events for AJAX add to cart buttons
			$(document).on('click', '.ats-ajax-add-to-cart', function (e) {
				e.preventDefault();
				e.stopPropagation();

				const $btn = $(this);
				const productId = $btn.data('product-id');

				if (!productId) {
					console.error('[AJAX Add to Cart] No product ID found');
					return;
				}

				self.addToCart($btn, productId);
			});
		},

		/**
		 * Add product to cart via AJAX
		 *
		 * @param {jQuery} $btn - Button element
		 * @param {number} productId - Product ID
		 */
		addToCart: function ($btn, productId) {
			const self = this;

			// Disable button and show loading state
			const originalText = $btn.html();
			$btn.prop('disabled', true)
				.addClass('opacity-50 cursor-not-allowed')
				.html('Adding...');

			// Determine the correct AJAX URL
			let ajaxUrl;
			if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
				ajaxUrl = wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart');
			} else {
				// Fallback to standard WooCommerce AJAX URL
				ajaxUrl = window.location.origin + '/?wc-ajax=add_to_cart';
			}

			// WooCommerce AJAX add to cart
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					product_id: productId,
					quantity: 1,
				},
				success: function (response) {
					if (response.error) {
						console.error('[AJAX Add to Cart] Error:', response.error);
						self.showError($btn, originalText);
						return;
					}

					// Success! Update button state
					$btn.html('Added!').removeClass('opacity-50 cursor-not-allowed');

					// Trigger WooCommerce events to update cart fragments
					$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);

					// Open mini cart modal after a short delay
					setTimeout(function () {
						if (typeof window.ATSMiniCartModal !== 'undefined') {
							window.ATSMiniCartModal.open();
						}
					}, 300);

					// Reset button after 2 seconds
					setTimeout(function () {
						$btn.html(originalText).prop('disabled', false);
					}, 2000);
				},
				error: function (xhr, status, error) {
					console.error('[AJAX Add to Cart] AJAX Error:', error);
					self.showError($btn, originalText);
				},
			});
		},

		/**
		 * Show error state on button
		 *
		 * @param {jQuery} $btn - Button element
		 * @param {string} originalText - Original button text
		 */
		showError: function ($btn, originalText) {
			$btn.html('Error!').removeClass('opacity-50 cursor-not-allowed');

			// Reset button after 2 seconds
			setTimeout(function () {
				$btn.html(originalText).prop('disabled', false);
			}, 2000);
		},
	};

	// Initialize on DOM ready
	$(document).ready(function () {
		AJAXAddToCart.init();
	});
})(jQuery);
