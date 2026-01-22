/**
 * Favorites Functionality
 *
 * Handles adding/removing products from user favorites
 *
 * @package SkylineWP Dev Child
 */

import $ from 'jquery';

(function () {
	'use strict';

	/**
	 * Favorites Module
	 */
	const Favorites = {
		// User's favorite product IDs
		userFavorites: [],

		// State
		isProcessing: false,

		/**
		 * Initialize the module
		 */
		init: function () {
			// Get initial favorites from localized data
			if (typeof themeData !== 'undefined' && themeData.user_favorites) {
				this.userFavorites = themeData.user_favorites.map((id) => parseInt(id));
			}

			this.bindEvents();
			this.updateAllHeartStates();
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Toggle favorite on heart click
			$(document).on('click', '.ats-favorite-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();

				const $button = $(this);
				const productId = parseInt($button.data('product-id'));

				if (!productId) {
					return;
				}

				self.toggleFavorite(productId, $button);
			});

			// Remove from favorites page
			$(document).on('click', '.ats-remove-favorite', function (e) {
				e.preventDefault();
				e.stopPropagation();

				const $button = $(this);
				const productId = parseInt($button.data('product-id'));

				if (!productId) {
					return;
				}

				self.removeFavorite(productId, $button);
			});

			// Re-initialize when new products are loaded dynamically
			$(document).on('ats_products_loaded', function () {
				self.updateAllHeartStates();
			});

			// Add to cart from favorites page
			$(document).on('click', '.rfs-ref-favorite-add-to-cart', function (e) {
				e.preventDefault();
				e.stopPropagation();

				const $button = $(this);
				const productId = parseInt($button.data('product-id'));

				if (!productId) {
					return;
				}

				self.addToCartFromFavorites(productId, $button);
			});
		},

		/**
		 * Add product to cart from favorites page
		 * @param {number} productId - Product ID
		 * @param {jQuery} $button - Button element
		 */
		addToCartFromFavorites: function (productId, $button) {
			const self = this;
			const originalText = $button.text();

			$button.prop('disabled', true).text('Adding...');

			$.ajax({
				url: wc_add_to_cart_params?.wc_ajax_url?.toString().replace('%%endpoint%%', 'add_to_cart'),
				type: 'POST',
				data: {
					product_id: productId,
					quantity: 1,
				},
				success: function (response) {
					if (response.error) {
						self.showMessage(response.error_message || 'Failed to add to cart', 'error');
						$button.prop('disabled', false).text(originalText);
					} else {
						// Trigger WooCommerce added to cart event
						$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

						self.showMessage('Product added to cart', 'success');
						$button.text('Added!');

						setTimeout(() => {
							$button.prop('disabled', false).text(originalText);
						}, 2000);
					}
				},
				error: function () {
					self.showMessage('Failed to add to cart. Please try again.', 'error');
					$button.prop('disabled', false).text(originalText);
				},
			});
		},

		/**
		 * Toggle favorite status
		 * @param {number} productId - Product ID
		 * @param {jQuery} $button - Button element
		 */
		toggleFavorite: function (productId, $button) {
			const self = this;

			// Check if user is logged in
			if (typeof themeData === 'undefined' || !themeData.favorites_nonce) {
				this.showLoginMessage();
				return;
			}

			// Prevent multiple clicks
			if (this.isProcessing) {
				return;
			}

			this.isProcessing = true;
			$button.addClass('ats-processing');

			$.ajax({
				url: themeData.ajax_url,
				type: 'POST',
				data: {
					action: 'ats_toggle_favorite',
					nonce: themeData.favorites_nonce,
					product_id: productId,
				},
				success: function (response) {
					if (response.success) {
						const isFavorite = response.data.is_favorite;

						// Update local favorites array
						if (isFavorite) {
							if (!self.userFavorites.includes(productId)) {
								self.userFavorites.push(productId);
							}
						} else {
							self.userFavorites = self.userFavorites.filter((id) => id !== productId);
						}

						// Update all heart buttons for this product
						self.updateHeartState(productId, isFavorite);

						// Show success message
						self.showMessage(response.data.message, 'success');
					} else {
						if (response.data && response.data.login_required) {
							self.showLoginMessage();
						} else {
							self.showMessage(response.data?.message || 'An error occurred.', 'error');
						}
					}
				},
				error: function () {
					self.showMessage('Failed to update favorites. Please try again.', 'error');
				},
				complete: function () {
					self.isProcessing = false;
					$button.removeClass('ats-processing');
				},
			});
		},

		/**
		 * Remove from favorites (on favorites page)
		 * @param {number} productId - Product ID
		 * @param {jQuery} $button - Button element
		 */
		removeFavorite: function (productId, $button) {
			const self = this;

			// Confirm removal
			if (!confirm('Remove this product from your favorites?')) {
				return;
			}

			// Check if user is logged in
			if (typeof themeData === 'undefined' || !themeData.favorites_nonce) {
				return;
			}

			// Prevent multiple clicks
			if (this.isProcessing) {
				return;
			}

			this.isProcessing = true;
			const $productItem = $button.closest('.rfs-ref-favorite-item');

			$.ajax({
				url: themeData.ajax_url,
				type: 'POST',
				data: {
					action: 'ats_remove_favorite',
					nonce: themeData.favorites_nonce,
					product_id: productId,
				},
				success: function (response) {
					if (response.success) {
						// Update local favorites array
						self.userFavorites = self.userFavorites.filter((id) => id !== productId);

						// Update all heart buttons for this product
						self.updateHeartState(productId, false);

						// Remove product from grid with animation
						$productItem.fadeOut(300, function () {
							$(this).remove();

							// Check if there are no more favorites
							if ($('.rfs-ref-favorite-item').length === 0) {
								// Reload page to show empty state
								location.reload();
							}
						});

						// Show success message
						self.showMessage(response.data.message, 'success');
					} else {
						self.showMessage(response.data?.message || 'An error occurred.', 'error');
					}
				},
				error: function () {
					self.showMessage('Failed to remove from favorites. Please try again.', 'error');
				},
				complete: function () {
					self.isProcessing = false;
				},
			});
		},

		/**
		 * Update heart button state for a specific product
		 * @param {number} productId - Product ID
		 * @param {boolean} isFavorite - Is favorite
		 */
		updateHeartState: function (productId, isFavorite) {
			const $buttons = $(`.ats-favorite-btn[data-product-id="${productId}"]`);

			$buttons.each(function () {
				const $button = $(this);
				const $outline = $button.find('.ats-heart-outline');
				const $filled = $button.find('.ats-heart-filled');
				const $tooltipAdd = $button.find('.ats-tooltip-add');
				const $tooltipRemove = $button.find('.ats-tooltip-remove');

				if (isFavorite) {
					$button.addClass('ats-favorite-active');
					$outline.addClass('hidden');
					$filled.removeClass('hidden');
					$tooltipAdd.addClass('hidden');
					$tooltipRemove.removeClass('hidden');
					$button.attr('aria-label', 'Remove from favorites');
				} else {
					$button.removeClass('ats-favorite-active');
					$outline.removeClass('hidden');
					$filled.addClass('hidden');
					$tooltipAdd.removeClass('hidden');
					$tooltipRemove.addClass('hidden');
					$button.attr('aria-label', 'Add to favorites');
				}
			});
		},

		/**
		 * Update all heart button states based on user favorites
		 */
		updateAllHeartStates: function () {
			const self = this;

			$('.ats-favorite-btn').each(function () {
				const $button = $(this);
				const productId = parseInt($button.data('product-id'));

				if (productId) {
					const isFavorite = self.userFavorites.includes(productId);
					self.updateHeartState(productId, isFavorite);
				}
			});
		},

		/**
		 * Show success/error message
		 * @param {string} message - Message text
		 * @param {string} type - Message type (success/error)
		 */
		showMessage: function (message, type = 'success') {
			// Create message element
			const $message = $(`
				<div class="ats-favorite-message fixed top-20 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg ${
					type === 'success'
						? 'bg-green-50 border border-green-200 text-green-800'
						: 'bg-red-50 border border-red-200 text-red-800'
				} animate-slide-in-right">
					<div class="flex items-start gap-3">
						<svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
							${
								type === 'success'
									? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
									: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
							}
						</svg>
						<p class="flex-1 text-sm font-medium">${message}</p>
					</div>
				</div>
			`);

			// Append to body
			$('body').append($message);

			// Auto-remove after 3 seconds
			setTimeout(() => {
				$message.fadeOut(300, function () {
					$(this).remove();
				});
			}, 3000);
		},

		/**
		 * Show login required message
		 */
		showLoginMessage: function () {
			const loginUrl = typeof themeData !== 'undefined' && themeData.account_url ? themeData.account_url : '/my-account';

			const $message = $(`
				<div class="ats-favorite-message fixed top-20 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg bg-blue-50 border border-blue-200 text-blue-800 animate-slide-in-right">
					<div class="flex items-start gap-3">
						<svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
						</svg>
						<div class="flex-1">
							<p class="text-sm font-medium mb-2">Please log in to add favorites.</p>
							<a href="${loginUrl}" class="text-sm font-semibold underline hover:no-underline">Log in now</a>
						</div>
					</div>
				</div>
			`);

			// Append to body
			$('body').append($message);

			// Auto-remove after 5 seconds
			setTimeout(() => {
				$message.fadeOut(300, function () {
					$(this).remove();
				});
			}, 5000);
		},
	};

	/**
	 * Initialize on DOM ready
	 */
	$(document).ready(function () {
		Favorites.init();
	});

	// Expose to window for external access if needed
	window.Favorites = Favorites;
})();
