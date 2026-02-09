/**
 * Cart Page AJAX Functionality
 *
 * Handles:
 * - Quantity updates
 * - Item removal
 * - Coupon application/removal
 * - Cart totals refresh
 *
 * @package SkylineWP Dev Child
 */

import $ from 'jquery';

export function initCart() {

	// Only run on cart page
	if (!document.body.classList.contains('woocommerce-cart')) {
		return;
	}


	const cart = {
		// DOM Elements
		elements: {
			form: null,
			itemsList: null,
			totalsWrapper: null,
			couponInput: null,
			applyButton: null,
			couponMessage: null,
		},

		// State
		isUpdating: false,

		/**
		 * Initialize cart
		 */
		init() {
			this.cacheElements();
			this.bindEvents();
			this.initShippingMethods();
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements() {
			this.elements.form = document.querySelector('.rfs-ref-cart-form');
			this.elements.itemsList = document.querySelector('.rfs-ref-cart-items-list');
			this.elements.totalsWrapper = document.querySelector('.rfs-ref-cart-totals-wrapper');
			this.elements.couponInput = document.getElementById('coupon_code');
			this.elements.applyButton = document.querySelector('.ats-apply-coupon');
			this.elements.couponMessage = document.querySelector('.rfs-ref-coupon-message');
		},

		/**
		 * Bind event listeners
		 */
		bindEvents() {
			const self = this;

			// Listen for products added to cart from cross-sells
			$(document.body).on('added_to_cart', function(e, fragments, cart_hash, $button) {
				// If the add to cart was from cross-sells area, refresh cart items and totals
				if ($button && $button.closest('.rfs-ref-cart-cross-sells').length) {
					// Reload the page to show updated cart with new item
					setTimeout(function() {
						window.location.reload();
					}, 800);
				}
			});

			// Ensure cross-sells stay visible on WooCommerce cart updates
			$(document.body).on('updated_cart_totals updated_wc_div', function() {
				self.ensureCrossSellsVisible();
			});

			// Quantity decrease button
			if (this.elements.itemsList) {
				this.elements.itemsList.addEventListener('click', function (e) {
					const decreaseBtn = e.target.closest('.ats-qty-decrease');
					if (decreaseBtn) {
						e.preventDefault();
						const cartKey = decreaseBtn.dataset.cartKey;
						const input = decreaseBtn.parentElement.querySelector('.ats-qty-input');
						const currentQty = parseInt(input.value);
						if (currentQty > 1) {
							self.updateQuantity(cartKey, currentQty - 1);
						}
					}

					// Quantity increase button
					const increaseBtn = e.target.closest('.ats-qty-increase');
					if (increaseBtn) {
						e.preventDefault();
						const cartKey = increaseBtn.dataset.cartKey;
						const input = increaseBtn.parentElement.querySelector('.ats-qty-input');
						const currentQty = parseInt(input.value);
						const maxQty = parseInt(input.getAttribute('max'));
						// Allow increase if no max (-1 or 0 means unlimited) or current is below max
						if (maxQty <= 0 || currentQty < maxQty) {
							self.updateQuantity(cartKey, currentQty + 1);
						} else {
						}
					}

					// Remove item button
					const removeBtn = e.target.closest('.ats-remove-item');
					if (removeBtn) {
						e.preventDefault();
						const cartKey = removeBtn.dataset.cartKey;
						self.removeItem(cartKey);
					}
				});

				// Manual quantity input change
				this.elements.itemsList.addEventListener('change', function (e) {
					if (e.target.classList.contains('ats-qty-input')) {
						e.preventDefault();
						const cartKey = e.target.dataset.cartKey;
						const newQty = parseInt(e.target.value);
						if (newQty > 0) {
							self.updateQuantity(cartKey, newQty);
						}
					}
				});
			}

			// Apply coupon
			if (this.elements.applyButton) {
				this.elements.applyButton.addEventListener('click', function (e) {
					e.preventDefault();
					self.applyCoupon();
				});
			}

			// Coupon input enter key
			if (this.elements.couponInput) {
				this.elements.couponInput.addEventListener('keypress', function (e) {
					if (e.key === 'Enter') {
						e.preventDefault();
						self.applyCoupon();
					}
				});
			}

			// Listen for coupon removal from totals section
			document.addEventListener('click', function (e) {
				const removeBtn = e.target.closest('.woocommerce-remove-coupon');
				if (removeBtn) {
					e.preventDefault();
					const couponCode = removeBtn.dataset.coupon || removeBtn.getAttribute('data-coupon');
					if (couponCode) {
						self.removeCoupon(couponCode);
					}
				}
			});
		},

		/**
		 * Update item quantity
		 * @param {string} cartKey - Cart item key
		 * @param {number} quantity - New quantity
		 */
		updateQuantity(cartKey, quantity) {
			const self = this;


			if (this.isUpdating) {
				return;
			}
			this.isUpdating = true;

			const item = document.querySelector(`[data-cart-item-key="${cartKey}"]`);
			if (!item) {
				return;
			}


			// Show loading state
			item.style.opacity = '0.5';
			item.style.pointerEvents = 'none';

			$.ajax({
				url: window.themeData?.ajax_url || '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'ats_update_cart_quantity',
					nonce: window.themeData?.cart_nonce || '',
					cart_key: cartKey,
					quantity: quantity,
				},
				success(response) {
					if (response.success) {
						// Update the item subtotal
						const subtotal = item.querySelector('.rfs-ref-cart-item-subtotal div:last-child');
						if (subtotal && response.data.item_subtotal) {
							subtotal.innerHTML = response.data.item_subtotal;
						}

						// Update quantity input
						const input = item.querySelector('.ats-qty-input');
						if (input) {
							input.value = quantity;
						}

						// Update button states
						const decreaseBtn = item.querySelector('.ats-qty-decrease');
						if (decreaseBtn) {
							decreaseBtn.disabled = quantity <= 1;
						}

						const increaseBtn = item.querySelector('.ats-qty-increase');
						if (increaseBtn) {
							const maxQty = parseInt(input?.getAttribute('max'));
							increaseBtn.disabled = maxQty > 0 && quantity >= maxQty;
						}

						// Refresh cart totals
						self.refreshCartTotals();

						// Update cart count in header
						self.updateCartCount(response.data.cart_count);
					} else {
						self.showError(response.data?.message || 'Failed to update quantity');
					}
				},
				error(xhr, status, error) {
					self.showError('Failed to update cart. Please try again.');
				},
				complete() {
					self.isUpdating = false;
					item.style.opacity = '1';
					item.style.pointerEvents = '';
				},
			});
		},

		/**
		 * Remove item from cart
		 * @param {string} cartKey - Cart item key
		 */
		removeItem(cartKey) {
			const self = this;

			if (this.isUpdating) return;
			this.isUpdating = true;

			const item = document.querySelector(`[data-cart-item-key="${cartKey}"]`);
			if (!item) return;

			// Animate out
			item.style.transition = 'opacity 0.3s, transform 0.3s';
			item.style.opacity = '0';
			item.style.transform = 'translateX(20px)';

			$.ajax({
				url: window.themeData?.ajax_url || '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'ats_remove_cart_item',
					nonce: window.themeData?.cart_nonce || '',
					cart_key: cartKey,
				},
				success(response) {
					if (response.success) {
						// Remove item from DOM after animation
						setTimeout(() => {
							item.remove();

							// If cart is empty, reload page to show empty state
							if (response.data.is_empty) {
								window.location.reload();
							} else {
								// Refresh cart totals
								self.refreshCartTotals();

								// Update cart count
								self.updateCartCount(response.data.cart_count);
							}
						}, 300);
					} else {
						// Restore item on error
						item.style.opacity = '1';
						item.style.transform = 'translateX(0)';
						self.showError(response.data?.message || 'Failed to remove item');
					}
				},
				error() {
					// Restore item on error
					item.style.opacity = '1';
					item.style.transform = 'translateX(0)';
					self.showError('Failed to remove item. Please try again.');
				},
				complete() {
					self.isUpdating = false;
				},
			});
		},

		/**
		 * Apply coupon code
		 */
		applyCoupon() {
			const self = this;

			if (!this.elements.couponInput) return;

			const couponCode = this.elements.couponInput.value.trim();
			if (!couponCode) {
				this.showCouponMessage('Please enter a coupon code', 'error');
				return;
			}

			if (this.isUpdating) return;
			this.isUpdating = true;

			// Show loading state
			if (this.elements.applyButton) {
				this.elements.applyButton.disabled = true;
				this.elements.applyButton.textContent = 'Applying...';
			}

			$.ajax({
				url: window.themeData?.ajax_url || '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'ats_apply_coupon',
					nonce: window.themeData?.cart_nonce || '',
					coupon_code: couponCode,
				},
				success(response) {
					if (response.success) {
						self.showCouponMessage(response.data.message, 'success');
						self.elements.couponInput.value = '';
						self.refreshCartTotals();
					} else {
						self.showCouponMessage(response.data?.message || 'Invalid coupon code', 'error');
					}
				},
				error() {
					self.showCouponMessage('Failed to apply coupon. Please try again.', 'error');
				},
				complete() {
					self.isUpdating = false;
					if (self.elements.applyButton) {
						self.elements.applyButton.disabled = false;
						self.elements.applyButton.textContent = 'Apply coupon';
					}
				},
			});
		},

		/**
		 * Remove coupon code
		 * @param {string} couponCode - Coupon code to remove
		 */
		removeCoupon(couponCode) {
			const self = this;

			if (this.isUpdating) return;
			this.isUpdating = true;

			$.ajax({
				url: window.themeData?.ajax_url || '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'ats_remove_coupon',
					nonce: window.themeData?.cart_nonce || '',
					coupon_code: couponCode,
				},
				success(response) {
					if (response.success) {
						self.showCouponMessage('Coupon removed successfully', 'success');
						self.refreshCartTotals();
					} else {
						self.showError(response.data?.message || 'Failed to remove coupon');
					}
				},
				error() {
					self.showError('Failed to remove coupon. Please try again.');
				},
				complete() {
					self.isUpdating = false;
				},
			});
		},

		/**
		 * Refresh cart totals section
		 */
		refreshCartTotals() {
			if (!this.elements.totalsWrapper) return;

			const self = this;

			// Show loading state
			this.elements.totalsWrapper.style.opacity = '0.5';

			$.ajax({
				url: window.themeData?.ajax_url || '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'ats_get_cart_totals',
					nonce: window.themeData?.cart_nonce || '',
				},
				success(response) {
					if (response.success && response.data.html) {
						self.elements.totalsWrapper.innerHTML = response.data.html;

						// Update cart count in header
						if (response.data.cart_count !== undefined) {
							const countEl = document.querySelector('.rfs-ref-cart-count');
							if (countEl && response.data.cart_count_text) {
								countEl.textContent = response.data.cart_count_text;
							}
						}

						// Re-initialize shipping methods after updating HTML
						self.initShippingMethods();

						// Ensure cross-sells remain visible (defensive fix)
						self.ensureCrossSellsVisible();
					}
				},
				complete() {
					self.elements.totalsWrapper.style.opacity = '1';
				},
			});
		},

		/**
		 * Ensure cross-sells section remains visible after AJAX updates
		 */
		ensureCrossSellsVisible() {
			const crossSells = document.querySelector('.rfs-ref-cart-cross-sells');
			if (crossSells) {
				// Make sure it's visible and not affected by AJAX updates
				crossSells.style.display = '';
				crossSells.style.opacity = '1';
			}
		},

		/**
		 * Update cart count in header/mini-cart
		 * @param {number} count - New cart count
		 */
		updateCartCount(count) {
			// Trigger WooCommerce event to update mini cart
			$(document.body).trigger('updated_cart_totals');

			// Update any cart count badges
			const countBadges = document.querySelectorAll('.js-mini-cart-count, .cart-count');
			countBadges.forEach((badge) => {
				badge.textContent = count;
			});
		},

		/**
		 * Initialize shipping method visual states
		 */
		initShippingMethods() {
			const self = this;

			// Find all shipping method radio inputs
			const shippingInputs = document.querySelectorAll('#shipping_method input[type="radio"]');

			shippingInputs.forEach(function(input) {
				// Set initial state
				self.updateShippingMethodVisual(input);

				// Listen for changes
				input.addEventListener('change', function() {
					// Update all shipping methods
					shippingInputs.forEach(function(otherInput) {
						self.updateShippingMethodVisual(otherInput);
					});
				});
			});
		},

		/**
		 * Update shipping method visual state
		 * @param {HTMLElement} input - Radio input element
		 */
		updateShippingMethodVisual(input) {
			const label = input.closest('label');
			if (!label) return;

			if (input.checked) {
				label.classList.add('bg-ats-yellow', 'bg-opacity-10', 'border-ats-yellow');
				label.classList.remove('border-gray-200');
			} else {
				label.classList.remove('bg-ats-yellow', 'bg-opacity-10', 'border-ats-yellow');
				label.classList.add('border-gray-200');
			}
		},

		/**
		 * Show coupon message
		 * @param {string} message - Message text
		 * @param {string} type - Message type (success/error)
		 */
		showCouponMessage(message, type = 'success') {
			if (!this.elements.couponMessage) return;

			this.elements.couponMessage.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'border-green-200', 'bg-red-50', 'text-red-800', 'border-red-200');

			if (type === 'success') {
				this.elements.couponMessage.classList.add('bg-green-50', 'text-green-800', 'border-green-200');
			} else {
				this.elements.couponMessage.classList.add('bg-red-50', 'text-red-800', 'border-red-200');
			}

			this.elements.couponMessage.classList.add('border', 'rounded-lg', 'p-4', 'text-sm');
			this.elements.couponMessage.textContent = message;

			// Auto-hide after 5 seconds
			setTimeout(() => {
				this.elements.couponMessage.classList.add('hidden');
			}, 5000);
		},

		/**
		 * Show error message
		 * @param {string} message - Error message
		 */
		showError(message) {
			// You can implement a global error notification here
			alert(message);
		},
	};

	// Initialize
	cart.init();
}
