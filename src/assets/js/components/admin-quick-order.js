/**
 * Admin Quick Order Panel
 *
 * Provides a powerful interface for administrators to quickly search
 * and add products to cart without browsing the website.
 */

import $ from 'jquery';

const AdminQuickOrder = {
	/**
	 * Component elements
	 */
	elements: {
		panel: null,
		searchInput: null,
		productsGrid: null,
		resultsCount: null,
		clearSearch: null,
		categoryFilter: null,
		brandFilter: null,
		stockFilter: null,
		customerSearchInput: null,
		customerSearchLoading: null,
		customerResults: null,
		selectedCustomerDiv: null,
		selectedCustomerName: null,
		selectedCustomerEmail: null,
		selectedCustomerId: null,
		clearCustomerBtn: null,
		cartItems: null,
		cartSubtotal: null,
		cartCount: null,
		cartActions: null,
		cartTotals: null,
		clearCart: null,
		searchLoading: null,
		infiniteScrollTrigger: null,
		infiniteScrollLoading: null,
	},

	/**
	 * Intersection Observer for infinite scroll
	 */
	intersectionObserver: null,

	/**
	 * Component state
	 */
	state: {
		searchTerm: '',
		category: '',
		brand: '',
		stock: '',
		page: 1,
		perPage: 36, // Increased for ultra-compact layout
		totalProducts: 0,
		isLoading: false,
		isInfiniteScrollLoading: false,
		canLoadMore: false,
		searchTimeout: null,
		customerSearchTimeout: null,
		selectedCustomerId: null,
	},

	/**
	 * Initialize the component
	 */
	init: function () {
		this.elements.panel = document.querySelector('[data-component="admin-quick-order"]');

		if (!this.elements.panel) {
			return;
		}

		this.cacheElements();
		this.bindEvents();
		this.setupInfiniteScroll();
		this.updateCartDisplay();
	},

	/**
	 * Cache DOM elements
	 */
	cacheElements: function () {
		const panel = this.elements.panel;

		this.elements.searchInput = panel.querySelector('#quick-order-search');
		this.elements.productsGrid = panel.querySelector('.rfs-ref-products-grid');
		this.elements.resultsCount = panel.querySelector('.rfs-ref-results-count');
		this.elements.clearSearch = panel.querySelector('.rfs-ref-clear-search');
		this.elements.categoryFilter = panel.querySelector('#filter-category');
		this.elements.brandFilter = panel.querySelector('#filter-brand');
		this.elements.stockFilter = panel.querySelector('#filter-stock');
		this.elements.customerSearchInput = panel.querySelector('#quick-order-customer-search');
		this.elements.customerSearchLoading = panel.querySelector('.rfs-ref-customer-search-loading');
		this.elements.customerResults = panel.querySelector('.rfs-ref-customer-results');
		this.elements.selectedCustomerDiv = panel.querySelector('.rfs-ref-selected-customer');
		this.elements.selectedCustomerName = panel.querySelector('.rfs-ref-customer-name');
		this.elements.selectedCustomerEmail = panel.querySelector('.rfs-ref-customer-email');
		this.elements.selectedCustomerId = panel.querySelector('#selected-customer-id');
		this.elements.clearCustomerBtn = panel.querySelector('.rfs-ref-clear-customer');
		this.elements.cartItems = panel.querySelector('.rfs-ref-cart-items');
		this.elements.cartSubtotal = panel.querySelector('.rfs-ref-cart-subtotal');
		this.elements.cartCount = panel.querySelector('.rfs-ref-cart-count');
		this.elements.cartActions = panel.querySelector('.rfs-ref-cart-actions');
		this.elements.cartTotals = panel.querySelector('.rfs-ref-cart-totals');
		this.elements.clearCart = panel.querySelector('.rfs-ref-clear-cart');
		this.elements.searchLoading = panel.querySelector('.rfs-ref-search-loading');
		this.elements.infiniteScrollTrigger = panel.querySelector('.rfs-ref-infinite-scroll-trigger');
		this.elements.infiniteScrollLoading = panel.querySelector('.rfs-ref-infinite-scroll-loading');
	},

	/**
	 * Bind event listeners
	 */
	bindEvents: function () {
		const self = this;

		// Search input with debounce
		if (this.elements.searchInput) {
			this.elements.searchInput.addEventListener('input', function (e) {
				clearTimeout(self.state.searchTimeout);
				self.state.searchTimeout = setTimeout(() => {
					self.state.searchTerm = e.target.value;
					self.state.page = 1;
					self.performSearch();
				}, 300);
			});
		}

		// Clear search
		if (this.elements.clearSearch) {
			this.elements.clearSearch.addEventListener('click', function () {
				self.clearSearchAndFilters();
			});
		}

		// Filters
		if (this.elements.categoryFilter) {
			this.elements.categoryFilter.addEventListener('change', function (e) {
				self.state.category = e.target.value;
				self.state.page = 1;
				self.performSearch();
			});
		}

		if (this.elements.brandFilter) {
			this.elements.brandFilter.addEventListener('change', function (e) {
				self.state.brand = e.target.value;
				self.state.page = 1;
				self.performSearch();
			});
		}

		if (this.elements.stockFilter) {
			this.elements.stockFilter.addEventListener('change', function (e) {
				self.state.stock = e.target.value;
				self.state.page = 1;
				self.performSearch();
			});
		}

		// Clear cart (no confirmation)
		if (this.elements.clearCart) {
			this.elements.clearCart.addEventListener('click', function () {
				self.clearCart();
			});
		}

		// Customer search with debounce
		if (this.elements.customerSearchInput) {
			this.elements.customerSearchInput.addEventListener('input', function (e) {
				clearTimeout(self.state.customerSearchTimeout);
				const searchTerm = e.target.value.trim();

				if (searchTerm.length < 2) {
					self.elements.customerResults.innerHTML = '';
					self.elements.customerResults.classList.add('hidden');
					return;
				}

				self.state.customerSearchTimeout = setTimeout(() => {
					self.searchCustomers(searchTerm);
				}, 300);
			});
		}

		// Clear customer selection
		if (this.elements.clearCustomerBtn) {
			this.elements.clearCustomerBtn.addEventListener('click', function () {
				self.clearCustomerSelection();
			});
		}

		// Product card clicks - open quick view modal
		this.elements.productsGrid.addEventListener('click', function (e) {
			const productCard = e.target.closest('.rfs-ref-product-card');
			if (productCard && !e.target.closest('button')) {
				e.preventDefault();
				const productId = productCard.dataset.productId;
				if (productId && window.ProductQuickView) {
					window.ProductQuickView.openQuickView(productId);
				}
			}
		});

		// Listen for cart updates
		$(document.body).on('added_to_cart removed_from_cart updated_cart_totals', function () {
			self.updateCartDisplay();
		});
	},

	/**
	 * Setup infinite scroll using Intersection Observer
	 */
	setupInfiniteScroll: function () {
		const self = this;

		if (!this.elements.infiniteScrollTrigger) {
			return;
		}

		// Create intersection observer
		const options = {
			root: null,
			rootMargin: '200px',
			threshold: 0,
		};

		this.intersectionObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting && self.state.canLoadMore && !self.state.isInfiniteScrollLoading) {
					self.loadMoreProducts();
				}
			});
		}, options);

		// Observe the trigger element
		this.intersectionObserver.observe(this.elements.infiniteScrollTrigger);
	},

	/**
	 * Load more products for infinite scroll
	 */
	loadMoreProducts: function () {
		if (!this.state.canLoadMore || this.state.isInfiniteScrollLoading) {
			return;
		}

		this.state.page++;
		this.performSearch(true);
	},

	/**
	 * Perform product search
	 */
	performSearch: function (append = false) {
		const self = this;

		// Don't search if already loading
		if (this.state.isLoading) {
			return;
		}

		// Show loading state
		this.state.isLoading = true;
		if (append) {
			// Infinite scroll loading
			this.state.isInfiniteScrollLoading = true;
			if (this.elements.infiniteScrollLoading) {
				this.elements.infiniteScrollLoading.classList.remove('hidden');
			}
		} else {
			// Regular search loading
			if (this.elements.searchLoading) {
				this.elements.searchLoading.classList.remove('hidden');
			}
		}

		// Build search params
		const params = {
			action: 'ats_quick_order_search',
			nonce: themeData.quick_order_nonce,
			search: this.state.searchTerm,
			category: this.state.category,
			brand: this.state.brand,
			stock: this.state.stock,
			page: this.state.page,
			per_page: this.state.perPage,
		};

		$.ajax({
			url: themeData.ajax_url,
			type: 'POST',
			data: params,
			success: function (response) {
				if (response.success) {
					if (append) {
						self.appendProducts(response.data.products);
					} else {
						self.renderProducts(response.data.products);
					}
					self.state.totalProducts = response.data.total;
					self.updateResultsCount();
					self.updateCanLoadMore();
				} else {
					self.showError(response.data.message || 'Search failed');
				}
			},
			error: function () {
				self.showError('Search failed. Please try again.');
			},
			complete: function () {
				self.state.isLoading = false;
				self.state.isInfiniteScrollLoading = false;
				if (self.elements.searchLoading) {
					self.elements.searchLoading.classList.add('hidden');
				}
				if (self.elements.infiniteScrollLoading) {
					self.elements.infiniteScrollLoading.classList.add('hidden');
				}
			},
		});
	},

	/**
	 * Render products in grid
	 */
	renderProducts: function (products) {
		if (!products || products.length === 0) {
			this.elements.productsGrid.innerHTML = `
				<div class="col-span-full text-center text-gray-400 py-12">
					<svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
					</svg>
					<p class="text-lg font-medium">No products found</p>
					<p class="text-sm mt-2">Try adjusting your search or filters</p>
				</div>
			`;
			return;
		}

		this.elements.productsGrid.innerHTML = products.map(product => this.createProductCard(product)).join('');
	},

	/**
	 * Append products to grid (for load more)
	 */
	appendProducts: function (products) {
		if (!products || products.length === 0) {
			return;
		}

		const fragment = document.createDocumentFragment();
		products.forEach(product => {
			const div = document.createElement('div');
			div.innerHTML = this.createProductCard(product);
			fragment.appendChild(div.firstChild);
		});

		this.elements.productsGrid.appendChild(fragment);
	},

	/**
	 * Create product card HTML - Ultra-compact admin layout
	 */
	createProductCard: function (product) {
		const stockClass = product.in_stock ? 'text-green-600' : 'text-red-600';
		const stockText = product.in_stock ? 'In Stock' : 'Out of Stock';

		return `
			<div class="rfs-ref-product-card bg-white border border-gray-300 rounded p-1.5 hover:border-ats-yellow transition-colors cursor-pointer" data-product-id="${product.id}">
				<!-- Tiny Image -->
				<div class="h-16 mb-1.5 rounded overflow-hidden flex items-center justify-center">
					${product.image ? `<img src="${product.image}" alt="${product.name}" class="w-full h-full object-contain">` : '<div class="text-[9px] text-gray-400">No Image</div>'}
				</div>
				<!-- Ultra-Compact Product Info -->
				<h4 class="text-[11px] font-semibold text-ats-dark mb-0.5 line-clamp-2 leading-tight">${product.name}</h4>
				<p class="text-[9px] text-gray-500 mb-0.5">SKU: ${product.sku || 'N/A'}</p>
				<div class="flex items-center justify-between gap-1">
					<p class="text-xs font-bold text-ats-dark">${product.price}</p>
					<p class="text-[9px] ${stockClass} font-medium">${stockText}</p>
				</div>
			</div>
		`;
	},

	/**
	 * Search for customers
	 */
	searchCustomers: function (searchTerm) {
		const self = this;

		// Show loading
		if (this.elements.customerSearchLoading) {
			this.elements.customerSearchLoading.classList.remove('hidden');
		}

		$.ajax({
			url: themeData.ajax_url,
			type: 'POST',
			data: {
				action: 'ats_search_customers',
				search: searchTerm,
				nonce: themeData.quick_order_nonce,
			},
			success: function (response) {
				if (response.success && response.data.customers) {
					self.renderCustomerResults(response.data.customers);
				} else {
					self.renderCustomerResults([]);
				}
			},
			error: function () {
				self.renderCustomerResults([]);
			},
			complete: function () {
				if (self.elements.customerSearchLoading) {
					self.elements.customerSearchLoading.classList.add('hidden');
				}
			},
		});
	},

	/**
	 * Render customer search results
	 */
	renderCustomerResults: function (customers) {
		const self = this;

		if (!this.elements.customerResults) return;

		if (customers.length === 0) {
			this.elements.customerResults.innerHTML = '<p class="text-sm text-gray-500 p-3 border-2 border-gray-300 rounded-lg">No customers found</p>';
			this.elements.customerResults.classList.remove('hidden');
			return;
		}

		const html = customers
			.map(
				(customer) => `
			<div class="rfs-ref-customer-result bg-white border-2 border-gray-300 rounded-lg p-3 hover:border-ats-yellow hover:bg-gray-50 cursor-pointer transition-all" data-customer-id="${customer.id}" data-customer-name="${customer.display_name}" data-customer-email="${customer.email}">
				<p class="text-sm font-semibold text-gray-900">${customer.display_name}</p>
				<p class="text-xs text-gray-600">${customer.email}</p>
			</div>
		`
			)
			.join('');

		this.elements.customerResults.innerHTML = html;
		this.elements.customerResults.classList.remove('hidden');

		// Add click listeners
		this.elements.customerResults.querySelectorAll('.rfs-ref-customer-result').forEach(function (result) {
			result.addEventListener('click', function () {
				const customerId = this.dataset.customerId;
				const customerName = this.dataset.customerName;
				const customerEmail = this.dataset.customerEmail;
				self.selectCustomer(customerId, customerName, customerEmail);
			});
		});
	},

	/**
	 * Select a customer
	 */
	selectCustomer: function (customerId, customerName, customerEmail) {
		this.state.selectedCustomerId = customerId;

		// Update UI
		if (this.elements.selectedCustomerName) {
			this.elements.selectedCustomerName.textContent = customerName;
		}
		if (this.elements.selectedCustomerEmail) {
			this.elements.selectedCustomerEmail.textContent = customerEmail;
		}
		if (this.elements.selectedCustomerId) {
			this.elements.selectedCustomerId.value = customerId;
		}

		// Show selected customer div, hide results
		if (this.elements.selectedCustomerDiv) {
			this.elements.selectedCustomerDiv.classList.remove('hidden');
		}
		if (this.elements.customerResults) {
			this.elements.customerResults.classList.add('hidden');
		}
		if (this.elements.customerSearchInput) {
			this.elements.customerSearchInput.value = '';
		}

		// Save to session
		$.ajax({
			url: themeData.ajax_url,
			type: 'POST',
			data: {
				action: 'ats_set_order_customer',
				customer_id: customerId,
				nonce: themeData.quick_order_nonce,
			},
		});
	},

	/**
	 * Clear customer selection
	 */
	clearCustomerSelection: function () {
		this.state.selectedCustomerId = null;

		if (this.elements.selectedCustomerId) {
			this.elements.selectedCustomerId.value = '';
		}
		if (this.elements.selectedCustomerDiv) {
			this.elements.selectedCustomerDiv.classList.add('hidden');
		}

		// Clear from session
		$.ajax({
			url: themeData.ajax_url,
			type: 'POST',
			data: {
				action: 'ats_clear_order_customer',
				nonce: themeData.quick_order_nonce,
			},
		});
	},

	/**
	 * Update cart display
	 */
	updateCartDisplay: function () {
		const self = this;

		$.ajax({
			url: themeData.ajax_url,
			type: 'POST',
			data: {
				action: 'ats_get_cart_contents',
				nonce: themeData.quick_order_nonce,
			},
			success: function (response) {
				if (response.success) {
					self.renderCart(response.data);
				}
			},
		});
	},

	/**
	 * Render cart items and totals
	 */
	renderCart: function (cartData) {
		if (!cartData.items || cartData.items.length === 0) {
			this.elements.cartItems.innerHTML = '<p class="text-gray-400 text-sm text-center py-8">Cart is empty</p>';
			this.elements.cartActions.classList.add('hidden');
			this.elements.cartTotals.classList.add('hidden');
			this.elements.clearCart.classList.add('hidden');
			return;
		}

		// Render items
		this.elements.cartItems.innerHTML = cartData.items
			.map(
				(item) => `
			<div class="bg-white border border-gray-200 rounded p-2">
				<div class="flex items-start gap-2">
					${item.image ? `<img src="${item.image}" alt="${item.name}" class="w-12 h-12 object-contain rounded">` : ''}
					<div class="flex-1 min-w-0">
						<p class="text-xs font-semibold text-ats-dark line-clamp-2">${item.name}</p>
						<p class="text-xs text-gray-600 mt-1">${item.quantity} × ${item.price}</p>
					</div>
				</div>
			</div>
		`
			)
			.join('');

		// Update totals (use innerHTML to render HTML entities properly)
		this.elements.cartSubtotal.innerHTML = cartData.subtotal;
		this.elements.cartCount.textContent = cartData.count;

		// Show elements
		this.elements.cartActions.classList.remove('hidden');
		this.elements.cartTotals.classList.remove('hidden');
		this.elements.clearCart.classList.remove('hidden');
	},

	/**
	 * Clear entire cart
	 */
	clearCart: function () {
		const self = this;

		$.ajax({
			url: themeData.ajax_url,
			type: 'POST',
			data: {
				action: 'ats_clear_cart',
				nonce: themeData.cart_nonce,
			},
			success: function (response) {
				if (response.success) {
					$(document.body).trigger('wc_fragment_refresh');
					self.updateCartDisplay();
				}
			},
		});
	},

	/**
	 * Update results count
	 */
	updateResultsCount: function () {
		if (!this.elements.resultsCount) return;

		const count = this.state.totalProducts;
		this.elements.resultsCount.innerHTML = `<span class="font-semibold">${count}</span> product${count !== 1 ? 's' : ''} found`;

		// Show/hide clear search button
		if (this.state.searchTerm || this.state.category || this.state.brand || this.state.stock) {
			this.elements.clearSearch.classList.remove('hidden');
		} else {
			this.elements.clearSearch.classList.add('hidden');
		}
	},

	/**
	 * Update canLoadMore flag for infinite scroll
	 */
	updateCanLoadMore: function () {
		const totalPages = Math.ceil(this.state.totalProducts / this.state.perPage);
		this.state.canLoadMore = this.state.page < totalPages;
	},

	/**
	 * Clear search and filters
	 */
	clearSearchAndFilters: function () {
		this.state.searchTerm = '';
		this.state.category = '';
		this.state.brand = '';
		this.state.stock = '';
		this.state.page = 1;

		if (this.elements.searchInput) this.elements.searchInput.value = '';
		if (this.elements.categoryFilter) this.elements.categoryFilter.value = '';
		if (this.elements.brandFilter) this.elements.brandFilter.value = '';
		if (this.elements.stockFilter) this.elements.stockFilter.value = '';

		this.elements.productsGrid.innerHTML = `
			<div class="col-span-full text-center text-gray-400 py-12">
				<svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
				</svg>
				<p class="text-lg font-medium">Start typing to search products</p>
			</div>
		`;
		this.elements.resultsCount.innerHTML = '<span class="font-semibold">0</span> products found';
		this.elements.clearSearch.classList.add('hidden');
	},

	/**
	 * Show error message
	 */
	showError: function (message) {
		this.elements.productsGrid.innerHTML = `
			<div class="col-span-full text-center text-red-600 py-12">
				<svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg>
				<p class="text-lg font-medium">${message}</p>
			</div>
		`;
	},
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
	AdminQuickOrder.init();
});

export default AdminQuickOrder;
