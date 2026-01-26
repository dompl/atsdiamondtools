/**
 * Product Quick View Modal JavaScript
 *
 * Handles opening product quick view modal and loading product content via AJAX
 *
 * @package SkylineWP Dev Child
 */

import { Modal } from 'flowbite';
import Splide from '@splidejs/splide';
import $ from 'jquery';

(function () {
	'use strict';

	/**
	 * Product Quick View Module
	 */
	const ProductQuickView = {
		// DOM Elements
		elements: {
			modal: null,
			modalContent: null,
			loadingSpinner: null,
			expandButtons: null,
		},

		// Flowbite modal instance
		modalInstance: null,

		// State
		isLoading: false,
		currentProductId: null,

		/**
		 * Initialize the module
		 */
		init: function () {
			this.cacheElements();
			this.bindEvents();
			this.initFlowbiteModal();
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements: function () {
			this.elements.modal = document.getElementById('ats-product-quick-view-modal');
			this.elements.modalContent = document.querySelector('.rfs-ref-quick-view-product-content');
			this.elements.loadingSpinner = document.querySelector('.rfs-ref-quick-view-loading');
			this.elements.expandButtons = document.querySelectorAll('.ats-expand-product');
		},

		/**
		 * Initialize Flowbite Modal
		 */
		initFlowbiteModal: function () {
			if (!this.elements.modal) {
				console.error('Modal element not found');
				return;
			}

			const options = {
				placement: 'center',
				backdrop: 'static',
				backdropClasses: 'bg-gray-900/50 fixed inset-0 z-40',
				closable: true,
				onHide: () => {
					this.onModalClose();
				},
			};

			this.modalInstance = new Modal(this.elements.modal, options);
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Expand button clicks
			this.elements.expandButtons.forEach(function (button) {
				button.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();

					const productCard = this.closest('[data-product-id]');
					if (productCard) {
						const productId = productCard.getAttribute('data-product-id');
						if (productId) {
							self.openQuickView(productId);
						}
					}
				});
			});

			// Close button click
			const closeButton = this.elements.modal?.querySelector('[data-modal-hide]');
			if (closeButton) {
				closeButton.addEventListener('click', function (e) {
					e.preventDefault();
					self.closeModal();
				});
			}

			// Close on backdrop click
			if (this.elements.modal) {
				this.elements.modal.addEventListener('click', function (e) {
					// Only close if clicking the backdrop (modal itself, not its children)
					if (e.target === self.elements.modal) {
						self.closeModal();
					}
				});
			}
		},

		/**
		 * Close the modal
		 */
		closeModal: function () {
			if (this.modalInstance) {
				this.modalInstance.hide();
			}
		},

		/**
		 * Open quick view modal and load product
		 * @param {number} productId - WooCommerce product ID
		 */
		openQuickView: function (productId) {
			if (this.isLoading) {
				return;
			}

			// Check if modal instance is ready
			if (!this.modalInstance) {
				console.error('Modal instance not initialized');
				// Try to initialize it now
				this.initFlowbiteModal();
				// Wait a bit and try again
				setTimeout(() => {
					if (this.modalInstance) {
						this.openQuickView(productId);
					}
				}, 600);
				return;
			}

			this.currentProductId = productId;
			this.showLoading();
			this.modalInstance.show();
			this.loadProductData(productId);
		},

		/**
		 * Load product data via AJAX
		 * @param {number} productId - WooCommerce product ID
		 */
		loadProductData: function (productId) {
			const self = this;

			// Check if themeData exists
			if (typeof themeData === 'undefined' || !themeData.ajax_url) {
				console.error('themeData not found');
				this.showError('Configuration error. Please refresh the page.');
				return;
			}

			$.ajax({
				url: themeData.ajax_url,
				type: 'POST',
				data: {
					action: 'ats_product_quick_view',
					product_id: productId,
					nonce: themeData.product_quick_view_nonce,
				},
				success: function (response) {
					if (response.success && response.data.html) {
						self.renderProduct(response.data.html);
					} else {
						self.showError(response.data?.message || 'Failed to load product.');
					}
				},
				error: function (xhr, status, error) {
					console.error('AJAX Error:', error);
					self.showError('Failed to load product. Please try again.');
				},
				complete: function () {
					self.hideLoading();
				},
			});
		},

		/**
		 * Render product HTML in modal
		 * @param {string} html - Product HTML content
		 */
		renderProduct: function (html) {
			if (!this.elements.modalContent) {
				return;
			}

			this.elements.modalContent.innerHTML = html;

			// Reinitialize WooCommerce variation forms if needed
			if (typeof $.fn.wc_variation_form !== 'undefined') {
				$(this.elements.modalContent).find('.variations_form').each(function () {
					$(this).wc_variation_form();
				});
			}

			// Trigger custom event for other scripts
			$(document).trigger('ats_quick_view_loaded', [this.currentProductId]);

			// Reinitialize image gallery if present
			this.initializeGallery();
		},

		/**
		 * Initialize product image gallery
		 */
		initializeGallery: function () {
			const mainSlider = this.elements.modalContent.querySelector('#product-main-splide');
			const thumbnailSlider = this.elements.modalContent.querySelector('#product-thumbnail-splide');

			if (mainSlider) {
				// Initialize main slider
				const main = new Splide(mainSlider, {
					type: 'fade',
					rewind: false,
					pagination: false,
					arrows: false,
				});

				// Only initialize thumbnails if they exist (multiple images)
				if (thumbnailSlider) {
					const thumbnails = new Splide(thumbnailSlider, {
						fixedWidth: 100,
						fixedHeight: 64,
						gap: 10,
						rewind: false,
						pagination: false,
						isNavigation: true,
						breakpoints: {
							640: {
								fixedWidth: 60,
								fixedHeight: 40,
							},
						},
					});

					// Sync sliders
					main.sync(thumbnails);
					thumbnails.mount();
				}

				main.mount();
			}
		},

		/**
		 * Show loading state
		 */
		showLoading: function () {
			this.isLoading = true;
			if (this.elements.loadingSpinner) {
				this.elements.loadingSpinner.style.display = 'flex';
			}
			if (this.elements.modalContent) {
				this.elements.modalContent.innerHTML = '';
			}
		},

		/**
		 * Hide loading state
		 */
		hideLoading: function () {
			this.isLoading = false;
			if (this.elements.loadingSpinner) {
				this.elements.loadingSpinner.style.display = 'none';
			}
		},

		/**
		 * Show error message
		 * @param {string} message - Error message to display
		 */
		showError: function (message) {
			if (!this.elements.modalContent) {
				return;
			}

			this.elements.modalContent.innerHTML = `
				<div class="rfs-ref-quick-view-error flex items-center justify-center py-12">
					<div class="text-center">
						<svg class="mx-auto h-12 w-12 text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
						</svg>
						<p class="text-gray-600">${message}</p>
					</div>
				</div>
			`;
		},

		/**
		 * Handle modal close event
		 */
		onModalClose: function () {
			this.currentProductId = null;
			// Clear content after a delay to prevent flash during close animation
			setTimeout(() => {
				if (this.elements.modalContent) {
					this.elements.modalContent.innerHTML = '';
				}
			}, 300);
		},
	};

	/**
	 * Initialize on DOM ready
	 */
	$(document).ready(function () {
		ProductQuickView.init();

		// Re-bind events when new products are loaded dynamically
		$(document).on('ats_products_loaded', function () {
			ProductQuickView.cacheElements();
			ProductQuickView.bindEvents();
		});
	});

	// Expose to window for external access if needed
	window.ProductQuickView = ProductQuickView;
})();
