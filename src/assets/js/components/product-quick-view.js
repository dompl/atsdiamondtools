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

			// Wait for DOM to settle before initializing variation forms
			const self = this;
			setTimeout(function() {
				console.log('[VARIATION DEBUG] Starting variation form initialization...');

				// Reinitialize WooCommerce variation forms
				const $forms = $(self.elements.modalContent).find('.variations_form');
				console.log('[VARIATION DEBUG] Forms found:', $forms.length);

				if ($forms.length > 0) {
					// Check if variation form function exists
					console.log('[VARIATION DEBUG] $.fn.wc_variation_form exists:', typeof $.fn.wc_variation_form !== 'undefined');
					console.log('[VARIATION DEBUG] wc_add_to_cart_variation_params exists:', typeof wc_add_to_cart_variation_params !== 'undefined');

					if (typeof wc_add_to_cart_variation_params !== 'undefined') {
						console.log('[VARIATION DEBUG] Variation params:', wc_add_to_cart_variation_params);
					}

					if (typeof $.fn.wc_variation_form !== 'undefined') {
						console.log('[VARIATION DEBUG] Initializing each form...');

						$forms.each(function (index) {
							const $form = $(this);
							console.log('[VARIATION DEBUG] Form', index, '- HTML:', $form.html().substring(0, 200));
							console.log('[VARIATION DEBUG] Form', index, '- Has select elements:', $form.find('select').length);

							// Initialize the variation form
							$form.wc_variation_form();

							console.log('[VARIATION DEBUG] Form', index, '- Initialized');

							// Check if form has data bound
							const formData = $form.data('wc_variation_form');
							console.log('[VARIATION DEBUG] Form', index, '- Data bound:', !!formData);

							// Log select elements
							$form.find('select').each(function(i) {
								console.log('[VARIATION DEBUG] Form', index, '- Select', i, ':', {
									name: $(this).attr('name'),
									options: $(this).find('option').length,
									value: $(this).val()
								});
							});
						});

						console.log('[VARIATION DEBUG] ✓ All forms initialized');

						// Initialize custom dropdowns AFTER WooCommerce forms are ready
						setTimeout(() => {
							console.log('[VARIATION DEBUG] Initializing Flowbite dropdowns...');
							self.initializeVariationDropdowns();
						}, 100);
					} else {
						console.log('[VARIATION DEBUG] ✗ wc_variation_form function NOT loaded - script missing!');
					}
				} else {
					console.log('[VARIATION DEBUG] No variation forms found in modal content');
				}
			}, 250);

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
		 * Initialize variation dropdowns with Flowbite
		 */
		initializeVariationDropdowns: function () {
			console.log('[DROPDOWN DEBUG] initializeVariationDropdowns called');
			const $form = $(this.elements.modalContent).find('.variations_form');
			console.log('[DROPDOWN DEBUG] Form found:', $form.length);
			const dropdownInstances = new Map();

			const dropdownWrappers = $('.flowbite-dropdown-wrapper', this.elements.modalContent);
			console.log('[DROPDOWN DEBUG] Dropdown wrappers found:', dropdownWrappers.length);

			// Helper to refresh options from select
			const refreshDropdown = ($wrapper) => {
				console.log('[DROPDOWN DEBUG] refreshDropdown called for wrapper:', $wrapper);
				const $select = $wrapper.find('select');
				console.log('[DROPDOWN DEBUG] Select found:', $select.length);
				const $list = $wrapper.find('.dropdown-options-list');
				console.log('[DROPDOWN DEBUG] Options list found:', $list.length);
				const $btnText = $wrapper.find('.dropdown-selected-text');
				console.log('[DROPDOWN DEBUG] Button text element found:', $btnText.length);
				const selectName = $select.data('attribute_name') || $select.attr('name');
				console.log('[DROPDOWN DEBUG] Select name:', selectName);
				const variationsData = $form.data('product_variations') || [];
				console.log('[DROPDOWN DEBUG] Variations data:', variationsData.length);

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
					const btn = $('<button type="button">')
						.addClass('ats-dropdown-option w-full text-left inline-flex px-4 py-1 hover:bg-brand-dark transition-colors duration-150')
						.data('value', value)
						.text(text);

					// Active state
					if (currentVal === value) {
						btn.addClass('bg-gray-100 text-primary-600 font-bold');
					} else {
						btn.addClass('text-white');
					}

					li.append(btn);
					$list.append(li);
				});
			};

			// Initial Population
			console.log('[DROPDOWN DEBUG] Starting initial population...');
			$('.flowbite-dropdown-wrapper', this.elements.modalContent).each(function (index) {
				console.log('[DROPDOWN DEBUG] Refreshing dropdown', index);
				refreshDropdown($(this));
			});
			console.log('[DROPDOWN DEBUG] Initial population complete');

			// Initialize Flowbite dropdowns
			setTimeout(() => {
				console.log('[DROPDOWN DEBUG] Starting Flowbite initialization...');
				console.log('[DROPDOWN DEBUG] window.Flowbite exists:', typeof window.Flowbite !== 'undefined');
				console.log('[DROPDOWN DEBUG] window.Flowbite.Dropdown exists:', typeof window.Flowbite !== 'undefined' && typeof window.Flowbite.Dropdown !== 'undefined');

				$('.flowbite-dropdown-wrapper', this.elements.modalContent).each(function (index) {
					console.log('[DROPDOWN DEBUG] Processing dropdown wrapper', index);
					const $wrapper = $(this);
					const $button = $wrapper.find('[data-dropdown-toggle]');
					const $menu = $wrapper.find('[id^="dropdown_"]');

					console.log('[DROPDOWN DEBUG] Wrapper', index, '- Button found:', $button.length);
					console.log('[DROPDOWN DEBUG] Wrapper', index, '- Menu found:', $menu.length);

					if ($button.length && $menu.length) {
						const triggerEl = $button[0];
						const targetEl = $menu[0];

						console.log('[DROPDOWN DEBUG] Wrapper', index, '- Elements ready, initializing Flowbite...');

						// Initialize Flowbite Dropdown
						if (typeof window.Flowbite !== 'undefined' && window.Flowbite.Dropdown) {
							try {
								const dropdown = new window.Flowbite.Dropdown(targetEl, triggerEl, {
									placement: 'bottom',
									triggerType: 'click',
									offsetSkidding: 0,
									offsetDistance: 10,
								});
								dropdownInstances.set(triggerEl, dropdown);
								console.log('[DROPDOWN DEBUG] Wrapper', index, '- Flowbite dropdown initialized successfully');
							} catch (error) {
								console.error('[DROPDOWN DEBUG] Wrapper', index, '- Error initializing Flowbite:', error);
							}
						} else {
							console.error('[DROPDOWN DEBUG] Wrapper', index, '- Flowbite not available');
						}
					}
				});
				console.log('[DROPDOWN DEBUG] Flowbite initialization complete');
			}, 100);

			// Handle Option Click
			$(document).off('click.quickview', '.ats-dropdown-option').on('click.quickview', '.ats-dropdown-option', function (e) {
				e.preventDefault();
				const $option = $(this);
				const value = $option.data('value');
				const $wrapper = $option.closest('.flowbite-dropdown-wrapper');
				const $select = $wrapper.find('select');
				const $btn = $wrapper.find('.ats-dropdown-trigger');

				// Close dropdown using Flowbite instance
				const triggerEl = $btn[0];
				if (dropdownInstances.has(triggerEl)) {
					const dropdown = dropdownInstances.get(triggerEl);
					dropdown.hide();
				}

				// Update Select
				$select.val(value).trigger('change');
			});

			// Listen for WC updates
			$form.on('woocommerce_update_variation_values', function () {
				$('.flowbite-dropdown-wrapper').each(function () {
					refreshDropdown($(this));
				});
			});

			// Sync if select changes elsewhere (e.g. reset)
			$('.flowbite-dropdown-wrapper select', this.elements.modalContent).on('change', function () {
				const $wrapper = $(this).closest('.flowbite-dropdown-wrapper');
				refreshDropdown($wrapper);
			});
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
