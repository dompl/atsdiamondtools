/**
 * Checkout Page Functionality
 *
 * Handles:
 * - Form validation feedback
 * - Shipping address toggle animation
 * - Error scrolling
 * - Loading states
 *
 * @package SkylineWP Dev Child
 */

export function initCheckout() {
	console.log('Checkout: Init function called');
	console.log('Checkout: Body classes:', document.body.className);

	// Only run on checkout page
	if (!document.body.classList.contains('woocommerce-checkout')) {
		console.log('Checkout: Not on checkout page, exiting');
		return;
	}

	console.log('Checkout: On checkout page, initializing...');

	const checkout = {
		// DOM Elements
		elements: {
			form: null,
			shipToDifferentCheckbox: null,
			shippingAddress: null,
			placeOrderButton: null,
		},

		/**
		 * Initialize checkout
		 */
		init() {
			this.cacheElements();
			this.bindEvents();
			this.handleShippingToggle();
			this.initShippingMethods();
			this.initLoginModal();
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements() {
			this.elements.form = document.querySelector('.rfs-ref-checkout-form');
			this.elements.shipToDifferentCheckbox = document.getElementById('ship-to-different-address-checkbox');
			this.elements.shippingAddress = document.querySelector('.rfs-ref-shipping-address');
			this.elements.placeOrderButton = document.getElementById('place_order');
		},

		/**
		 * Bind event listeners
		 */
		bindEvents() {
			const self = this;

			// Coupon toggle functionality
			jQuery(document).on('click', '.showcoupon', function (e) {
				e.preventDefault();
				const couponWrapper = jQuery('#woocommerce-checkout-form-coupon');
				const button = jQuery(this);

				couponWrapper.slideToggle(300, function() {
					if (couponWrapper.is(':visible')) {
						button.attr('aria-expanded', 'true');
						// Focus on the coupon input when it opens
						jQuery('#coupon_code').focus();
					} else {
						button.attr('aria-expanded', 'false');
					}
				});
			});

			// Ship to different address toggle
			if (this.elements.shipToDifferentCheckbox) {
				this.elements.shipToDifferentCheckbox.addEventListener('change', function () {
					self.handleShippingToggle();
				});
			}

			// Listen for WooCommerce checkout update events
			if (this.elements.form) {
				// Show loading state when checkout is updating
				jQuery(document.body).on('update_checkout', function () {
					console.log('Checkout: WooCommerce update_checkout event fired');
					self.showLoadingState();
				});

				// Hide loading state when checkout update is complete
				jQuery(document.body).on('updated_checkout', function () {
					console.log('Checkout: WooCommerce updated_checkout event fired');
					self.hideLoadingState();
					// Re-initialize shipping methods after AJAX update
					self.initShippingMethods();
				});

				// Scroll to error messages
				jQuery(document.body).on('checkout_error', function () {
					console.log('Checkout: WooCommerce checkout_error event fired');
					self.scrollToError();
				});
			}
		},

		/**
		 * Handle shipping address toggle animation
		 */
		handleShippingToggle() {
			if (!this.elements.shippingAddress || !this.elements.shipToDifferentCheckbox) return;

			if (this.elements.shipToDifferentCheckbox.checked) {
				// Show shipping address with animation
				this.elements.shippingAddress.style.display = 'block';
				this.elements.shippingAddress.style.opacity = '0';
				this.elements.shippingAddress.style.transform = 'translateY(-10px)';

				// Trigger animation
				setTimeout(() => {
					this.elements.shippingAddress.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
					this.elements.shippingAddress.style.opacity = '1';
					this.elements.shippingAddress.style.transform = 'translateY(0)';
				}, 10);
			} else {
				// Hide shipping address with animation
				this.elements.shippingAddress.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
				this.elements.shippingAddress.style.opacity = '0';
				this.elements.shippingAddress.style.transform = 'translateY(-10px)';

				setTimeout(() => {
					this.elements.shippingAddress.style.display = 'none';
				}, 300);
			}
		},

		/**
		 * Show loading state during checkout update
		 */
		showLoadingState() {
			const orderReview = document.querySelector('.rfs-ref-order-review-wrapper');
			if (orderReview) {
				orderReview.style.opacity = '0.5';
				orderReview.style.pointerEvents = 'none';
			}
		},

		/**
		 * Hide loading state after checkout update
		 */
		hideLoadingState() {
			const orderReview = document.querySelector('.rfs-ref-order-review-wrapper');
			if (orderReview) {
				orderReview.style.opacity = '1';
				orderReview.style.pointerEvents = '';
			}
		},

		/**
		 * Show loading state on place order button
		 */
		showPlaceOrderLoading() {
			if (!this.elements.placeOrderButton) return;

			this.elements.placeOrderButton.disabled = true;
			this.elements.placeOrderButton.style.opacity = '0.7';
			this.elements.placeOrderButton.style.cursor = 'not-allowed';

			// Save original text
			const originalText = this.elements.placeOrderButton.textContent;
			this.elements.placeOrderButton.setAttribute('data-original-text', originalText);

			// Show processing text
			this.elements.placeOrderButton.textContent = 'Processing...';
		},

		/**
		 * Scroll to first error message
		 */
		scrollToError() {
			const errorElement = document.querySelector('.woocommerce-error, .woocommerce-NoticeGroup-checkout');
			if (errorElement) {
				const offset = 100; // Offset from top
				const elementPosition = errorElement.getBoundingClientRect().top + window.pageYOffset;
				const offsetPosition = elementPosition - offset;

				window.scrollTo({
					top: offsetPosition,
					behavior: 'smooth',
				});

				// Reset place order button if it's in loading state
				if (this.elements.placeOrderButton && this.elements.placeOrderButton.disabled) {
					this.elements.placeOrderButton.disabled = false;
					this.elements.placeOrderButton.style.opacity = '1';
					this.elements.placeOrderButton.style.cursor = 'pointer';

					const originalText = this.elements.placeOrderButton.getAttribute('data-original-text');
					if (originalText) {
						this.elements.placeOrderButton.textContent = originalText;
					}
				}
			}
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
		 * Initialize login modal functionality
		 */
		initLoginModal() {
			const modal = document.getElementById('checkout-login-modal');
			if (!modal) return;

			const self = this;
			const openButton = document.querySelector('[data-modal-toggle="checkout-login-modal"]');
			const closeButton = modal.querySelector('[data-modal-hide="checkout-login-modal"]');

			// Open modal
			if (openButton) {
				openButton.addEventListener('click', function(e) {
					e.preventDefault();
					self.openLoginModal();
				});
			}

			// Close modal on close button
			if (closeButton) {
				closeButton.addEventListener('click', function(e) {
					e.preventDefault();
					self.closeLoginModal();
				});
			}

			// Close modal on backdrop click
			modal.addEventListener('click', function(e) {
				if (e.target === modal) {
					self.closeLoginModal();
				}
			});

			// Close modal on Escape key
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
					self.closeLoginModal();
				}
			});
		},

		/**
		 * Open login modal
		 */
		openLoginModal() {
			const modal = document.getElementById('checkout-login-modal');
			if (!modal) return;

			modal.classList.remove('hidden');
			modal.classList.add('flex');
			document.body.style.overflow = 'hidden';

			// Focus on username field
			setTimeout(() => {
				const usernameField = modal.querySelector('#modal_username');
				if (usernameField) {
					usernameField.focus();
				}
			}, 100);
		},

		/**
		 * Close login modal
		 */
		closeLoginModal() {
			const modal = document.getElementById('checkout-login-modal');
			if (!modal) return;

			modal.classList.add('hidden');
			modal.classList.remove('flex');
			document.body.style.overflow = '';
		},
	};

	// Initialize
	checkout.init();
}
