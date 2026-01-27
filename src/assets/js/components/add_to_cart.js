/**
 * Mini Cart JavaScript
 *
 * Handles AJAX cart updates, modal functionality, and quantity controls.
 * Designed to work with cached pages by fetching cart data on page load.
 * Supports multiple mini cart instances (mobile + desktop) on the same page.
 *
 * @package SkylineWP Dev Child
 */

(function ($) {
	'use strict';

	// Store all mini cart instances
	const miniCartInstances = [];

	/**
	 * Create a Mini Cart instance for a specific container
	 * @param {HTMLElement} container - The mini cart wrapper element
	 */
	function createMiniCartInstance(container) {
		const instance = {
			// The container element for this instance
			container: container,

			// DOM Elements (scoped to this container)
			elements: {
				wrapper: container,
				emptyState: null,
				filledState: null,
				loadingState: null,
				toggleBtn: null,
				countBadge: null,
				itemsText: null,
				subtotal: null,
				total: null,
				tax: null,
			},

			// State
			isLoading: false,

			/**
			 * Initialize this mini cart instance
			 */
			init: function () {
				this.cacheElements();
				this.bindEvents();
			},

			/**
			 * Cache DOM elements within this container
			 */
			cacheElements: function () {
				this.elements.emptyState = this.container.querySelector('.js-mini-cart-empty');
				this.elements.filledState = this.container.querySelector('.js-mini-cart-filled');
				this.elements.loadingState = this.container.querySelector('.js-mini-cart-loading');
				this.elements.toggleBtn = this.container.querySelector('.js-mini-cart-toggle');
				this.elements.countBadge = this.container.querySelector('.js-mini-cart-count');
				this.elements.itemsText = this.container.querySelector('.js-mini-cart-items-text');
				this.elements.subtotal = this.container.querySelector('.js-mini-cart-subtotal');
				this.elements.total = this.container.querySelector('.js-mini-cart-total');
				this.elements.tax = this.container.querySelector('.js-mini-cart-tax');
			},

			/**
			 * Bind event listeners for this instance
			 */
			bindEvents: function () {
				const self = this;

				// Toggle button click - open modal
				if (this.elements.toggleBtn) {
					this.elements.toggleBtn.addEventListener('click', function (e) {
						e.preventDefault();
						MiniCartModal.open();
					});
				}
			},

			/**
			 * Show loading state
			 */
			showLoading: function () {
				if (this.elements.loadingState) {
					this.elements.loadingState.style.display = 'block';
				}
				if (this.elements.emptyState) {
					this.elements.emptyState.style.display = 'none';
				}
				if (this.elements.filledState) {
					this.elements.filledState.style.display = 'none';
				}
			},

			/**
			 * Hide loading state
			 */
			hideLoading: function () {
				if (this.elements.loadingState) {
					this.elements.loadingState.style.display = 'none';
				}
			},

			/**
			 * Show empty cart state
			 */
			showEmpty: function () {
				if (this.elements.emptyState) {
					this.elements.emptyState.style.display = 'block';
				}
				if (this.elements.filledState) {
					this.elements.filledState.style.display = 'none';
				}
				if (this.elements.loadingState) {
					this.elements.loadingState.style.display = 'none';
				}
			},

			/**
			 * Show filled cart state with data
			 */
			showFilled: function (data) {
				if (this.elements.emptyState) {
					this.elements.emptyState.style.display = 'none';
				}
				if (this.elements.filledState) {
					this.elements.filledState.style.display = 'block';
				}
				if (this.elements.loadingState) {
					this.elements.loadingState.style.display = 'none';
				}

				// Update count badge
				if (this.elements.countBadge) {
					this.elements.countBadge.textContent = data.count;
				}

				// Update items text
				if (this.elements.itemsText) {
					this.elements.itemsText.textContent = data.count_text;
				}

				// Update subtotal
				if (this.elements.subtotal) {
					this.elements.subtotal.innerHTML = data.subtotal;
				}

				// Update total
				if (this.elements.total) {
					this.elements.total.innerHTML = data.total;
				}

				// Update tax
				if (this.elements.tax) {
					this.elements.tax.innerHTML = '(inc ' + data.tax + ' VAT)';
				}
			},

			/**
			 * Update display with cart data
			 */
			updateDisplay: function (data) {
				if (data.is_empty) {
					this.showEmpty();
				} else {
					this.showFilled(data);
				}
			},
		};

		return instance;
	}

	/**
	 * Mini Cart Modal Controller (shared across all instances)
	 */
	const MiniCartModal = {
		// DOM Elements
		elements: {
			modal: null,
			backdrop: null,
			container: null,
			content: null,
			items: null,
			closeButtons: null,
			itemCount: null,
			subtotal: null,
			tax: null,
			total: null,
		},

		// State
		isOpen: false,

		/**
		 * Initialize the modal
		 */
		init: function () {
			this.cacheElements();
			if (this.elements.modal) {
				this.bindEvents();
			}
		},

		/**
		 * Cache modal DOM elements
		 */
		cacheElements: function () {
			this.elements.modal = document.querySelector('.js-mini-cart-modal');
			if (!this.elements.modal) return;

			this.elements.backdrop = this.elements.modal.querySelector('.js-mini-cart-backdrop');
			this.elements.container = this.elements.modal.querySelector('.js-mini-cart-modal-container');
			this.elements.content = this.elements.modal.querySelector('.js-mini-cart-modal-content');
			this.elements.items = this.elements.modal.querySelector('.js-mini-cart-items');
			this.elements.closeButtons = this.elements.modal.querySelectorAll('.js-mini-cart-close');
			this.elements.itemCount = this.elements.modal.querySelector('.js-modal-item-count');
			this.elements.subtotal = this.elements.modal.querySelector('.js-modal-subtotal');
			this.elements.tax = this.elements.modal.querySelector('.js-modal-tax');
			this.elements.total = this.elements.modal.querySelector('.js-modal-total');
			this.elements.scrollIndicator = this.elements.modal.querySelector('.js-mini-cart-scroll-indicator');
		},

		/**
		 * Check scroll state
		 */
		checkScroll: function () {
			if (!this.elements.items || !this.elements.scrollIndicator) return;

			const el = this.elements.items;
			const hasOverflow = el.scrollHeight > el.clientHeight;
			// 10px buffer
			const isAtBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 10;

			if (hasOverflow && !isAtBottom) {
				this.elements.scrollIndicator.classList.remove('opacity-0');
			} else {
				this.elements.scrollIndicator.classList.add('opacity-0');
			}
		},

		/**
		 * Bind modal event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Close on backdrop click
			if (this.elements.backdrop) {
				this.elements.backdrop.addEventListener('click', function () {
					self.close();
				});
			}

			// Close buttons
			if (this.elements.closeButtons) {
				this.elements.closeButtons.forEach(function (btn) {
					btn.addEventListener('click', function () {
						self.close();
					});
				});
			}

			// Close on ESC key
			document.addEventListener('keydown', function (e) {
				if (e.key === 'Escape' && self.isOpen) {
					self.close();
				}
			});

			// Delegate events for cart items (quantity controls, remove)
			if (this.elements.items) {
				// Scroll Check
				this.elements.items.addEventListener('scroll', function () {
					self.checkScroll();
				});

				this.elements.items.addEventListener('click', function (e) {
					const target = e.target.closest('button');
					if (!target) return;

					const cartKey = target.dataset.cartKey;

					if (target.classList.contains('js-qty-decrease')) {
						e.preventDefault();
						MiniCartController.updateQuantity(cartKey, 'decrease');
					} else if (target.classList.contains('js-qty-increase')) {
						e.preventDefault();
						MiniCartController.updateQuantity(cartKey, 'increase');
					} else if (target.classList.contains('js-remove-item')) {
						e.preventDefault();
						MiniCartController.removeItem(cartKey);
					}
				});
			}
		},

		/**
		 * Open modal
		 */
		open: function () {
			if (!this.elements.modal) return;

			this.elements.modal.classList.remove('hidden');
			this.elements.modal.classList.add('flex');
			this.elements.modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('overflow-hidden');
			this.isOpen = true;

			// Animate modal in (Refunded/Central Style)
			if (this.elements.container) {
				this.elements.container.style.opacity = '0';
				this.elements.container.style.transform = 'scale(0.95)';
				const container = this.elements.container;
				setTimeout(function () {
					container.style.transition = 'all 0.2s ease-out';
					container.style.opacity = '1';
					container.style.transform = 'scale(1)';
				}, 10);
			}
		},

		/**
		 * Close modal
		 */
		close: function () {
			const self = this;
			if (!this.elements.modal) return;

			// Animate modal out
			if (this.elements.container) {
				this.elements.container.style.opacity = '0';
				this.elements.container.style.transform = 'scale(0.95)';
				setTimeout(function () {
					self.elements.modal.classList.add('hidden');
					self.elements.modal.classList.remove('flex');
					self.elements.modal.setAttribute('aria-hidden', 'true');
					document.body.classList.remove('overflow-hidden');
					self.elements.container.style.transition = '';
					self.isOpen = false;
				}, 200);
			} else {
				this.elements.modal.classList.add('hidden');
				this.elements.modal.classList.remove('flex');
				this.elements.modal.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('overflow-hidden');
				this.isOpen = false;
			}
		},

		/**
		 * Update modal content with cart data
		 * @param {Object} data - Cart data from AJAX
		 * @param {boolean} skipItemsUpdate - If true, don't replace items HTML
		 */
		updateContent: function (data, skipItemsUpdate) {
			// Update items HTML
			if (this.elements.items && !skipItemsUpdate) {
				this.elements.items.innerHTML = data.items_html;
			}

			// Update item count
			if (this.elements.itemCount) {
				this.elements.itemCount.textContent = '(' + data.count_text + ')';
			}

			// Update subtotal
			if (this.elements.subtotal) {
				this.elements.subtotal.innerHTML = data.subtotal;
			}

			// Update tax
			if (this.elements.tax) {
				this.elements.tax.innerHTML = data.tax;
			}

			// Update total
			if (this.elements.total) {
				this.elements.total.innerHTML = data.total;
			}
		},

		/**
		 * Update individual item in modal after quantity change
		 */
		updateItem: function (cartKey, itemData) {
			if (!this.elements.items) return;

			const itemEl = this.elements.items.querySelector('[data-cart-key="' + cartKey + '"]');
			if (!itemEl) return;

			// Update quantity value
			const qtyValueEl = itemEl.querySelector('.js-qty-value');
			if (qtyValueEl) {
				qtyValueEl.textContent = itemData.quantity;
			}

			// Update subtotal
			const subtotalEl = itemEl.querySelector('.rfs-ref-mini-cart-item-subtotal span');
			if (subtotalEl) {
				subtotalEl.innerHTML = itemData.subtotal;
			}

			// Update decrease button disabled state
			const decreaseBtn = itemEl.querySelector('.js-qty-decrease');
			if (decreaseBtn) {
				decreaseBtn.disabled = itemData.quantity <= 1;
			}

			// Update increase button disabled state
			const increaseBtn = itemEl.querySelector('.js-qty-increase');
			if (increaseBtn) {
				increaseBtn.disabled = itemData.quantity >= itemData.max_qty;
			}
		},
	};

	/**
	 * Mini Cart Controller (handles AJAX and coordinates instances)
	 */
	const MiniCartController = {
		isLoading: false,

		/**
		 * Initialize the controller
		 */
		init: function () {
			// Check if themeData is available
			if (typeof themeData === 'undefined') {
				console.error('Mini Cart: themeData not found');
				return;
			}

			if (!themeData.mini_cart_nonce) {
				console.error('Mini Cart: mini_cart_nonce not found in themeData');
				return;
			}

			// Find all mini cart containers
			const containers = document.querySelectorAll('[data-ats-mini-cart]');
			if (containers.length === 0) {
				return; // No mini carts on this page
			}

			// Create an instance for each container
			containers.forEach(function (container) {
				const instance = createMiniCartInstance(container);
				instance.init();
				miniCartInstances.push(instance);
			});

			// Initialize the modal (only one modal shared across all instances)
			MiniCartModal.init();

			// Bind global events
			this.bindEvents();

			// Load cart data
			this.loadCart();
		},

		/**
		 * Bind global event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Listen for WooCommerce add to cart events
			$(document.body).on('added_to_cart removed_from_cart updated_cart_totals', function () {
				self.loadCart();
			});

			// Also listen for WooCommerce AJAX complete
			$(document).ajaxComplete(function (event, xhr, settings) {
				// Check if this was a WooCommerce cart action
				if (
					settings.url &&
					settings.url.includes('wc-ajax') &&
					(settings.url.includes('add_to_cart') || settings.url.includes('remove_from_cart') || settings.url.includes('apply_coupon') || settings.url.includes('remove_coupon'))
				) {
					self.loadCart();
				}
			});
		},

		/**
		 * Load cart data via AJAX
		 */
		loadCart: function () {
			const self = this;

			if (this.isLoading) return;

			this.isLoading = true;

			// Show loading on all instances
			miniCartInstances.forEach(function (instance) {
				instance.showLoading();
			});

			$.ajax({
				url: themeData.ajax_url,
				type: 'POST',
				data: {
					action: 'ats_get_mini_cart',
					nonce: themeData.mini_cart_nonce,
				},
				success: function (response) {
					if (response.success) {
						self.updateAllInstances(response.data);
						MiniCartModal.updateContent(response.data);
					} else {
						console.error('Mini cart error:', response.data);
						self.showAllEmpty();
					}
				},
				error: function (xhr, status, error) {
					console.error('Mini cart AJAX error:', error);
					self.showAllEmpty();
				},
				complete: function () {
					self.isLoading = false;
					// Hide loading on all instances
					miniCartInstances.forEach(function (instance) {
						instance.hideLoading();
					});
				},
			});
		},

		/**
		 * Update all mini cart instances with cart data
		 */
		updateAllInstances: function (data) {
			miniCartInstances.forEach(function (instance) {
				instance.updateDisplay(data);
			});
		},

		/**
		 * Show empty state on all instances
		 */
		showAllEmpty: function () {
			miniCartInstances.forEach(function (instance) {
				instance.showEmpty();
			});
		},

		/**
		 * Update item quantity
		 */
		updateQuantity: function (cartKey, action) {
			const self = this;

			if (!MiniCartModal.elements.items) return;

			const itemEl = MiniCartModal.elements.items.querySelector('[data-cart-key="' + cartKey + '"]');
			if (!itemEl) return;

			const qtyValueEl = itemEl.querySelector('.js-qty-value');
			if (!qtyValueEl) return;

			let currentQty = parseInt(qtyValueEl.textContent, 10);
			let newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

			if (newQty < 1) {
				this.removeItem(cartKey);
				return;
			}

			// Show loading on item
			itemEl.classList.add('opacity-50', 'pointer-events-none');

			$.ajax({
				url: themeData.ajax_url,
				type: 'POST',
				data: {
					action: 'ats_update_cart_item',
					nonce: themeData.mini_cart_nonce,
					cart_key: cartKey,
					quantity: newQty,
				},
				success: function (response) {
					if (response.success) {
						// Update the individual item in the modal
						const itemData = response.data.items.find(function (item) {
							return item.key === cartKey;
						});
						if (itemData) {
							MiniCartModal.updateItem(cartKey, itemData);
						}
						// Update modal totals
						MiniCartModal.updateContent(response.data, true);
						// Update all mini cart instances
						self.updateAllInstances(response.data);
					} else {
						console.error('Update quantity error:', response.data);
					}
				},
				error: function (xhr, status, error) {
					console.error('Update quantity AJAX error:', error);
				},
				complete: function () {
					itemEl.classList.remove('opacity-50', 'pointer-events-none');
				},
			});
		},

		/**
		 * Remove item from cart
		 */
		removeItem: function (cartKey) {
			const self = this;

			if (!MiniCartModal.elements.items) return;

			const itemEl = MiniCartModal.elements.items.querySelector('[data-cart-key="' + cartKey + '"]');
			if (!itemEl) return;

			// Prevent multiple removal attempts
			if (self.isLoading) return;
			self.isLoading = true;

			// Animate out
			itemEl.style.transition = 'opacity 0.3s, transform 0.3s';
			itemEl.style.opacity = '0';
			itemEl.style.transform = 'translateX(20px)';

			$.ajax({
				url: themeData.ajax_url,
				type: 'POST',
				data: {
					action: 'ats_remove_cart_item',
					nonce: themeData.mini_cart_nonce,
					cart_key: cartKey,
				},
				success: function (response) {
					if (response.success) {
						// Wait for animation to complete before updating UI
						setTimeout(function() {
							// Close modal first if cart is empty
							if (response.data.is_empty) {
								MiniCartModal.close();
								// Update all instances to show empty state
								self.updateAllInstances(response.data);
								self.isLoading = false;
							} else {
								// Reset loading flag before reloading cart
								self.isLoading = false;
								// Reload the entire cart to ensure fresh data
								self.loadCart();
							}
						}, 300);
					} else {
						console.error('Remove item error:', response.data);
						// Restore item visibility
						itemEl.style.opacity = '1';
						itemEl.style.transform = 'translateX(0)';
						self.isLoading = false;
					}
				},
				error: function (xhr, status, error) {
					console.error('Remove item AJAX error:', error);
					// Restore item visibility
					itemEl.style.opacity = '1';
					itemEl.style.transform = 'translateX(0)';
					self.isLoading = false;
				},
			});
		},
	};

	// Initialize when DOM is ready
	$(document).ready(function () {
		MiniCartController.init();
	});

	// Also initialize on window load for late-loading content
	$(window).on('load', function () {
		if (miniCartInstances.length === 0) {
			MiniCartController.init();
		}
	});

	// Export for external use
	window.ATSMiniCart = MiniCartController;
	window.ATSMiniCartModal = MiniCartModal;
})(jQuery);
