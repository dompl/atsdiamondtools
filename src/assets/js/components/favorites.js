/**
 * Favorites Functionality
 *
 * Handles adding/removing products from user favorites
 * Works for both logged-in users (via AJAX) and guests (via localStorage)
 *
 * @package SkylineWP Dev Child
 */

import $ from 'jquery';

(function () {
	'use strict';

	const STORAGE_KEY = 'ats_favorites';

	/**
	 * Favorites Module
	 */
	const Favorites = {
		// User's favorite product IDs
		userFavorites: [],

		// Is user logged in
		isLoggedIn: false,

		// State
		isProcessing: false,

		/**
		 * Initialize the module
		 */
		init: function () {
			// Check if user is logged in
			this.isLoggedIn = typeof themeData !== 'undefined' && themeData.is_user_logged_in;

			// Get initial favorites
			if (this.isLoggedIn && typeof themeData !== 'undefined' && themeData.user_favorites) {
				// Logged in user - get from server
				this.userFavorites = themeData.user_favorites.map((id) => parseInt(id));
			} else {
				// Guest - get from localStorage
				this.userFavorites = this.getLocalFavorites();
			}

			this.bindEvents();
			this.updateAllHeartStates();
			this.updateFavoritesCount();
		},

		/**
		 * Get favorites from localStorage
		 */
		getLocalFavorites: function () {
			try {
				const stored = localStorage.getItem(STORAGE_KEY);
				if (stored) {
					return JSON.parse(stored).map((id) => parseInt(id));
				}
			} catch (e) {
				console.warn('Error reading favorites from localStorage:', e);
			}
			return [];
		},

		/**
		 * Save favorites to localStorage
		 */
		saveLocalFavorites: function () {
			try {
				localStorage.setItem(STORAGE_KEY, JSON.stringify(this.userFavorites));
			} catch (e) {
				console.warn('Error saving favorites to localStorage:', e);
			}
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

			if (self.isProcessing) {
				return;
			}

			self.isProcessing = true;
			$button.addClass('opacity-50 pointer-events-none');

			$.ajax({
				url: themeData.ajax_url,
				type: 'POST',
				data: {
					action: 'woocommerce_add_to_cart',
					product_id: productId,
					quantity: 1,
				},
				success: function (response) {
					if (response.error) {
						self.showMessage(response.error, 'error');
					} else {
						self.showMessage('Product added to cart!', 'success');
						// Trigger WooCommerce cart update
						$(document.body).trigger('wc_fragment_refresh');
					}
				},
				error: function () {
					self.showMessage('Failed to add to cart. Please try again.', 'error');
				},
				complete: function () {
					self.isProcessing = false;
					$button.removeClass('opacity-50 pointer-events-none');
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

			if (self.isProcessing) {
				return;
			}

			const isFavorite = self.userFavorites.includes(productId);

			// For guests, handle locally
			if (!self.isLoggedIn) {
				self.toggleLocalFavorite(productId, isFavorite);
				return;
			}

			// For logged-in users, use AJAX
			self.isProcessing = true;

			// Optimistic update
			self.updateHeartState(productId, !isFavorite);

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
						// Update local state
						if (response.data.is_favorite) {
							if (!self.userFavorites.includes(productId)) {
								self.userFavorites.push(productId);
							}
						} else {
							self.userFavorites = self.userFavorites.filter((id) => id !== productId);
						}

						// Update UI
						self.updateHeartState(productId, response.data.is_favorite);
						self.updateFavoritesCount();
					} else {
						// Revert on error
						self.updateHeartState(productId, isFavorite);
						self.showMessage(response.data?.message || 'An error occurred.', 'error');
					}
				},
				error: function () {
					// Revert on error
					self.updateHeartState(productId, isFavorite);
					self.showMessage('Failed to update favorites. Please try again.', 'error');
				},
				complete: function () {
					self.isProcessing = false;
				},
			});
		},

		/**
		 * Toggle favorite locally (for guests)
		 * @param {number} productId - Product ID
		 * @param {boolean} isFavorite - Current favorite state
		 */
		toggleLocalFavorite: function (productId, isFavorite) {
			if (isFavorite) {
				// Remove from favorites
				this.userFavorites = this.userFavorites.filter((id) => id !== productId);
				this.showMessage('Removed from favorites', 'success');
			} else {
				// Add to favorites
				this.userFavorites.push(productId);
				this.showMessage('Added to favorites!', 'success');
			}

			// Save to localStorage
			this.saveLocalFavorites();

			// Update UI
			this.updateHeartState(productId, !isFavorite);
			this.updateFavoritesCount();
		},

		/**
		 * Remove product from favorites
		 * @param {number} productId - Product ID
		 * @param {jQuery} $button - Button element
		 */
		removeFavorite: function (productId, $button) {
			const self = this;

			if (self.isProcessing) {
				return;
			}

			// For guests, handle locally
			if (!self.isLoggedIn) {
				self.userFavorites = self.userFavorites.filter((id) => id !== productId);
				self.saveLocalFavorites();

				// Remove product card from favorites page
				const $card = $button.closest('.rfs-ref-favorite-product-card');
				$card.fadeOut(300, function () {
					$(this).remove();
					self.checkEmptyFavorites();
				});

				self.updateHeartState(productId, false);
				self.updateFavoritesCount();
				self.showMessage('Removed from favorites', 'success');
				return;
			}

			// For logged-in users, use AJAX
			self.isProcessing = true;
			$button.addClass('opacity-50');

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
						// Remove from local array
						self.userFavorites = self.userFavorites.filter((id) => id !== productId);

						// Remove product card from favorites page
						const $card = $button.closest('.rfs-ref-favorite-product-card');
						$card.fadeOut(300, function () {
							$(this).remove();
							self.checkEmptyFavorites();
						});

						// Update heart button state on other pages
						self.updateHeartState(productId, false);
						self.updateFavoritesCount();
						self.showMessage('Removed from favorites', 'success');
					} else {
						self.showMessage(response.data?.message || 'An error occurred.', 'error');
					}
				},
				error: function () {
					self.showMessage('Failed to remove from favorites. Please try again.', 'error');
				},
				complete: function () {
					self.isProcessing = false;
					$button.removeClass('opacity-50');
				},
			});
		},

		/**
		 * Check if favorites list is empty and show empty state
		 */
		checkEmptyFavorites: function () {
			const $container = $('.rfs-ref-favorites-grid');
			if ($container.length && $container.children().length === 0) {
				$container.html(`
					<div class="col-span-full text-center py-12">
						<svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
						</svg>
						<h3 class="text-lg font-medium text-gray-900 mb-2">No favorites yet</h3>
						<p class="text-gray-500 mb-4">Start adding products to your favorites!</p>
						<a href="/shop" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
							Browse Products
						</a>
					</div>
				`);
			}
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
		 * Update favorites count in header
		 */
		updateFavoritesCount: function () {
			const count = this.userFavorites.length;
			$('.ats-favorites-count').text(count);

			// Show/hide count badge
			if (count > 0) {
				$('.ats-favorites-count').removeClass('hidden');
			} else {
				$('.ats-favorites-count').addClass('hidden');
			}
		},

		/**
		 * Show success/error message
		 * @param {string} message - Message text
		 * @param {string} type - Message type (success/error)
		 */
		showMessage: function (message, type = 'success') {
			const bgColor = type === 'success' ? '#f0fdf4' : '#fef2f2';
			const borderColor = type === 'success' ? '#bbf7d0' : '#fecaca';
			const textColor = type === 'success' ? '#166534' : '#991b1b';

			// Create message element with inline styles for reliability
			const $message = $(`
				<div class="ats-favorite-message" style="position: fixed; top: 80px; right: 16px; z-index: 9999; max-width: 320px; padding: 16px; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); background-color: ${bgColor}; border: 1px solid ${borderColor}; color: ${textColor}; animation: slideInRight 0.3s ease-out;">
					<div style="display: flex; align-items: flex-start; gap: 12px;">
						<svg style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;" fill="currentColor" viewBox="0 0 20 20">
							${
								type === 'success'
									? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
									: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
							}
						</svg>
						<p style="flex: 1; font-size: 14px; font-weight: 500; margin: 0;">${message}</p>
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
	};

	/**
	 * Initialize on DOM ready
	 */
	$(document).ready(function () {
		Favorites.init();
	});

	// Expose to window for external access
	window.Favorites = Favorites;
})();
