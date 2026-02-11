/**
 * Price Manager Component
 *
 * Handles inline price editing with auto-save via AJAX.
 * Includes duplicate variation detection and deletion.
 */

import $ from 'jquery';

const PriceManager = {
	elements: {
		container: null,
		searchInput: null,
		saleOnlyCheckbox: null,
		duplicatesCheckbox: null,
		toast: null,
		errorToast: null,
		rows: null,
	},

	state: {
		debounceTimers: {},
		searchTerm: '',
		saleOnly: false,
		duplicatesOnly: false,
		duplicateKeys: new Set(),
	},

	init: function () {
		this.elements.container = document.querySelector('[data-component="price-manager"]');

		if (!this.elements.container) {
			return;
		}

		this.cacheElements();
		this.detectDuplicates();
		this.bindEvents();
	},

	cacheElements: function () {
		const c = this.elements.container;
		this.elements.searchInput = c.querySelector('#price-manager-search');
		this.elements.saleOnlyCheckbox = c.querySelector('#price-manager-sale-only');
		this.elements.duplicatesCheckbox = c.querySelector('#price-manager-duplicates');
		this.elements.toast = c.querySelector('.js-price-toast');
		this.elements.errorToast = c.querySelector('.js-price-error-toast');
		this.elements.rows = c.querySelectorAll('.js-price-row');
	},

	/**
	 * Scan variation rows and detect duplicates by variation key
	 */
	detectDuplicates: function () {
		const keyCounts = {};
		this.state.duplicateKeys.clear();

		this.elements.rows.forEach(function (row) {
			const key = row.dataset.variationKey;
			if (!key) return;
			keyCounts[key] = (keyCounts[key] || 0) + 1;
		});

		let duplicateCount = 0;
		for (const key in keyCounts) {
			if (keyCounts[key] > 1) {
				this.state.duplicateKeys.add(key);
				duplicateCount += keyCounts[key];
			}
		}

		// Mark duplicate rows visually
		this.elements.rows.forEach(function (row) {
			const key = row.dataset.variationKey;
			if (key && this.state.duplicateKeys.has(key)) {
				row.classList.add('rfs-ref-price-manager-duplicate');
				row.dataset.isDuplicate = '1';
			} else {
				row.classList.remove('rfs-ref-price-manager-duplicate');
				row.dataset.isDuplicate = '0';
			}
		}.bind(this));

		// Update duplicate count display
		const countEl = this.elements.container.querySelector('.js-duplicate-count');
		if (countEl) {
			countEl.textContent = duplicateCount > 0 ? '(' + duplicateCount + ')' : '';
		}
	},

	bindEvents: function () {
		const self = this;

		// Price input change - auto-save
		this.elements.container.addEventListener('change', function (e) {
			if (e.target.classList.contains('js-price-input')) {
				self.handlePriceChange(e.target);
			}
		});

		// Search filter
		if (this.elements.searchInput) {
			this.elements.searchInput.addEventListener('input', function (e) {
				self.state.searchTerm = e.target.value.toLowerCase().trim();
				self.filterRows();
			});
		}

		// Sale only filter
		if (this.elements.saleOnlyCheckbox) {
			this.elements.saleOnlyCheckbox.addEventListener('change', function (e) {
				self.state.saleOnly = e.target.checked;
				self.filterRows();
			});
		}

		// Duplicates only filter
		if (this.elements.duplicatesCheckbox) {
			this.elements.duplicatesCheckbox.addEventListener('change', function (e) {
				self.state.duplicatesOnly = e.target.checked;
				self.filterRows();
			});
		}

		// Delete variation button
		this.elements.container.addEventListener('click', function (e) {
			const btn = e.target.closest('.js-delete-variation');
			if (!btn) return;
			self.handleDeleteVariation(btn);
		});
	},

	/**
	 * Handle price input change - save via AJAX
	 */
	handlePriceChange: function (input) {
		const productId = input.dataset.productId;
		const field = input.dataset.field;
		const value = input.value.trim();
		const original = input.dataset.original;

		// Skip if value hasn't changed
		if (value === original) {
			return;
		}

		const row = input.closest('.js-price-row');
		const statusEl = row.querySelector('.js-price-status');

		// Show loading spinner
		this.setStatus(statusEl, 'loading');

		// Highlight input as pending
		input.classList.add('border-yellow-400', 'bg-yellow-50');
		input.classList.remove('border-gray-300', 'border-green-400', 'bg-green-50', 'border-red-400', 'bg-red-50');

		const self = this;

		$.ajax({
			url: themeData.ajax_url,
			type: 'POST',
			data: {
				action: 'ats_update_product_price',
				nonce: themeData.price_manager_nonce,
				product_id: productId,
				field: field,
				regular_price: field === 'regular_price' ? value : '',
				sale_price: field === 'sale_price' ? value : '',
			},
			success: function (response) {
				if (response.success) {
					// Update original value
					input.dataset.original = value;

					// Flash green
					input.classList.remove('border-yellow-400', 'bg-yellow-50');
					input.classList.add('border-green-400', 'bg-green-50');
					self.setStatus(statusEl, 'success');

					// Update sale status on row
					const saleInput = row.querySelector('.rfs-ref-price-manager-sale-input');
					if (saleInput) {
						row.dataset.hasSale = saleInput.value.trim() !== '' ? '1' : '0';
					}

					// If regular price was updated and sale price was auto-cleared
					if (field === 'regular_price' && response.data.sale_price === '') {
						const salePriceInput = row.querySelector('[data-field="sale_price"]');
						if (salePriceInput && salePriceInput.value !== '') {
							salePriceInput.value = '';
							salePriceInput.dataset.original = '';
							row.dataset.hasSale = '0';
						}
					}

					self.showToast('Price updated');

					// Reset styles after delay
					setTimeout(function () {
						input.classList.remove('border-green-400', 'bg-green-50');
						input.classList.add('border-gray-300');
						self.setStatus(statusEl, 'idle');
					}, 2000);
				} else {
					// Error
					input.classList.remove('border-yellow-400', 'bg-yellow-50');
					input.classList.add('border-red-400', 'bg-red-50');
					self.setStatus(statusEl, 'error');

					self.showErrorToast(response.data.message || 'Error updating price');

					// Revert value
					input.value = original;

					setTimeout(function () {
						input.classList.remove('border-red-400', 'bg-red-50');
						input.classList.add('border-gray-300');
						self.setStatus(statusEl, 'idle');
					}, 3000);
				}
			},
			error: function () {
				input.classList.remove('border-yellow-400', 'bg-yellow-50');
				input.classList.add('border-red-400', 'bg-red-50');
				self.setStatus(statusEl, 'error');
				self.showErrorToast('Network error. Please try again.');

				// Revert value
				input.value = original;

				setTimeout(function () {
					input.classList.remove('border-red-400', 'bg-red-50');
					input.classList.add('border-gray-300');
					self.setStatus(statusEl, 'idle');
				}, 3000);
			},
		});
	},

	/**
	 * Handle variation delete button click
	 */
	handleDeleteVariation: function (btn) {
		const variationId = btn.dataset.productId;
		const variationName = btn.dataset.productName || 'this variation';

		if (!confirm('Delete "' + variationName + '"?\n\nThis cannot be undone.')) {
			return;
		}

		const row = btn.closest('.js-price-row');
		const statusEl = row.querySelector('.js-price-status');
		const self = this;

		// Disable button and show loading
		btn.disabled = true;
		btn.classList.add('opacity-50');
		this.setStatus(statusEl, 'loading');

		$.ajax({
			url: themeData.ajax_url,
			type: 'POST',
			data: {
				action: 'ats_delete_variation',
				nonce: themeData.price_manager_nonce,
				variation_id: variationId,
			},
			success: function (response) {
				if (response.success) {
					// Fade out and remove row
					row.style.transition = 'opacity 0.3s, transform 0.3s';
					row.style.opacity = '0';
					row.style.transform = 'translateX(20px)';

					setTimeout(function () {
						row.remove();
						// Re-cache rows and re-detect duplicates
						self.elements.rows = self.elements.container.querySelectorAll('.js-price-row');
						self.detectDuplicates();
						self.filterRows();
					}, 300);

					self.showToast('Variation deleted');
				} else {
					btn.disabled = false;
					btn.classList.remove('opacity-50');
					self.setStatus(statusEl, 'error');
					self.showErrorToast(response.data.message || 'Error deleting variation');

					setTimeout(function () {
						self.setStatus(statusEl, 'idle');
					}, 3000);
				}
			},
			error: function () {
				btn.disabled = false;
				btn.classList.remove('opacity-50');
				self.setStatus(statusEl, 'error');
				self.showErrorToast('Network error. Please try again.');

				setTimeout(function () {
					self.setStatus(statusEl, 'idle');
				}, 3000);
			},
		});
	},

	/**
	 * Set status indicator on a row
	 */
	setStatus: function (el, status) {
		if (!el) return;

		el.className = 'js-price-status rfs-ref-price-manager-status inline-block w-5 h-5';

		if (status === 'loading') {
			el.innerHTML =
				'<svg class="animate-spin w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';
		} else if (status === 'success') {
			el.innerHTML =
				'<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
		} else if (status === 'error') {
			el.innerHTML =
				'<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
		} else {
			el.innerHTML = '';
		}
	},

	/**
	 * Filter visible rows based on search, sale, and duplicate filters
	 */
	filterRows: function () {
		const search = this.state.searchTerm;
		const saleOnly = this.state.saleOnly;
		const duplicatesOnly = this.state.duplicatesOnly;
		let visibleCount = 0;

		// First pass: determine which non-parent rows are visible
		const visibleParentIds = new Set();

		this.elements.rows.forEach(function (row) {
			const name = row.dataset.productName || '';
			const sku = row.dataset.sku || '';
			const hasSale = row.dataset.hasSale === '1';
			const isParent = row.dataset.isParent === '1';
			const isDuplicate = row.dataset.isDuplicate === '1';

			if (isParent) return; // Handle parents in second pass

			let visible = true;

			if (search) {
				visible = name.includes(search) || sku.includes(search);
			}

			if (visible && saleOnly) {
				visible = hasSale;
			}

			if (visible && duplicatesOnly) {
				visible = isDuplicate;
			}

			if (visible) {
				row.style.display = '';
				visibleCount++;
				// Track parent ID from variation key
				const key = row.dataset.variationKey;
				if (key) {
					const parentId = key.split('|')[0];
					visibleParentIds.add(parentId);
				}
			} else {
				row.style.display = 'none';
			}
		});

		// Second pass: show/hide parent rows
		this.elements.rows.forEach(function (row) {
			const isParent = row.dataset.isParent === '1';
			if (!isParent) return;

			const productId = row.dataset.productId;
			const name = row.dataset.productName || '';
			const sku = row.dataset.sku || '';

			// In duplicates-only mode, only show parents with visible duplicate variations
			if (duplicatesOnly) {
				row.style.display = visibleParentIds.has(productId) ? '' : 'none';
				return;
			}

			// In sale-only mode, hide all parents
			if (saleOnly) {
				row.style.display = 'none';
				return;
			}

			// In search mode, match against name/sku
			if (search) {
				row.style.display = name.includes(search) || sku.includes(search) ? '' : 'none';
			} else {
				row.style.display = '';
			}
		});

		// Update count display
		const countEl = this.elements.container.querySelector('.js-product-count');
		if (countEl) {
			countEl.textContent = visibleCount;
		}
	},

	/**
	 * Show success toast
	 */
	showToast: function (message) {
		const toast = this.elements.toast;
		if (!toast) return;

		toast.textContent = message;
		toast.classList.remove('opacity-0', 'pointer-events-none');
		toast.classList.add('opacity-100');

		setTimeout(function () {
			toast.classList.remove('opacity-100');
			toast.classList.add('opacity-0', 'pointer-events-none');
		}, 2000);
	},

	/**
	 * Show error toast
	 */
	showErrorToast: function (message) {
		const toast = this.elements.errorToast;
		if (!toast) return;

		toast.textContent = message;
		toast.classList.remove('opacity-0', 'pointer-events-none');
		toast.classList.add('opacity-100');

		setTimeout(function () {
			toast.classList.remove('opacity-100');
			toast.classList.add('opacity-0', 'pointer-events-none');
		}, 3000);
	},
};

// Initialize
document.addEventListener('DOMContentLoaded', function () {
	PriceManager.init();
});

export default PriceManager;
