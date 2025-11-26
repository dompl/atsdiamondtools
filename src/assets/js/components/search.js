/**
 * ATS Diamond Tools - AJAX Search Component
 *
 * Provides real-time product search with category filtering and infinite scroll.
 * Supports multiple search instances (desktop/mobile) on the same page.
 *
 * @package ATS
 * @since 1.0.0
 */

/**
 * Debounce function to limit how often a function is called
 *
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @return {Function} Debounced function
 */
function debounce(func, wait) {
	let timeout;
	return function executedFunction(...args) {
		const later = () => {
			clearTimeout(timeout);
			func(...args);
		};
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
	};
}

/**
 * Initialize a single search instance
 *
 * @param {HTMLElement} container - The search container element
 */
function initSearchInstance(container) {
	const context = container.dataset.searchContext || 'unknown';

	// Check if atsSearch is localized
	if (typeof atsSearch === 'undefined') {
		console.error(`ATS Search (${context}): Missing localized data (atsSearch)`);
		return;
	}

	if (!atsSearch.rest_url) {
		console.error(`ATS Search (${context}): rest_url not found in atsSearch`);
		return;
	}

	console.log(`ATS Search (${context}): Initialized with REST URL:`, atsSearch.rest_url);

	// State management for this instance
	const state = {
		query: '',
		categoryId: '',
		page: 1,
		isLoading: false,
		hasMore: true,
	};

	// Get elements within this container
	const searchInput = container.querySelector('.js-search-input');
	const categoryBtn = container.querySelector('.js-search-category-btn');
	const categoryDropdown = container.querySelector('.js-search-category-dropdown');
	const selectedCategoryText = container.querySelector('.js-selected-category-text');
	const selectedCategoryInput = container.querySelector('.js-selected-category');
	const resultsContainer = container.querySelector('.js-search-results');
	const resultsInner = container.querySelector('.js-search-results-inner');
	const loadingIndicator = container.querySelector('.js-search-loading');
	const noResultsMessage = container.querySelector('.js-search-no-results');
	const searchSentinel = container.querySelector('.js-search-sentinel');

	// Validate required elements
	if (!searchInput) {
		console.error(`ATS Search (${context}): Search input not found`);
		return;
	}

	/**
	 * Show loading state
	 */
	function showLoading() {
		loadingIndicator?.classList.remove('hidden');
		noResultsMessage?.classList.add('hidden');
	}

	/**
	 * Hide loading state
	 */
	function hideLoading() {
		loadingIndicator?.classList.add('hidden');
	}

	/**
	 * Show no results message
	 */
	function showNoResults() {
		noResultsMessage?.classList.remove('hidden');
	}

	/**
	 * Show results container
	 */
	function showResults() {
		resultsContainer?.classList.remove('hidden');
	}

	/**
	 * Hide results container
	 */
	function hideResults() {
		resultsContainer?.classList.add('hidden');
	}

	/**
	 * Clear results
	 */
	function clearResults() {
		if (resultsInner) {
			resultsInner.innerHTML = '';
		}
		noResultsMessage?.classList.add('hidden');
	}

	/**
	 * Render a single product result
	 *
	 * @param {Object} product - Product data
	 * @return {string} HTML string
	 */
	function renderProduct(product) {
		const srcset = product.image_2x ? `srcset="${product.image} 1x, ${product.image_2x} 2x"` : '';
		return `
			<a href="${product.url}" class="flex gap-3 p-2 border-b border-neutral-100 last:border-b-0 hover:bg-neutral-50 transition-colors block">
				<div class="flex-shrink-0 w-[50px] h-[50px]">
					<img
						src="${product.image}"
						${srcset}
						alt="${product.title}"
						class="w-full h-full object-cover rounded-[5px]"
						loading="lazy"
					>
				</div>
				<div class="flex-1 min-w-0">
					<h4 class="text-sm font-semibold text-neutral-900 mb-0.5 line-clamp-1">
						${product.title}
					</h4>
					<p class="text-xs text-neutral-500 mb-1 line-clamp-2">
						${product.short_description}
					</p>
					<span class="text-xs font-bold text-neutral-900">
						${product.price}
					</span>
				</div>
			</a>
		`;
	}

	/**
	 * Render products to results container
	 *
	 * @param {Array} products - Array of product objects
	 * @param {boolean} append - Whether to append or replace
	 */
	function renderProducts(products, append = false) {
		const html = products.map(renderProduct).join('');

		if (resultsInner) {
			if (append) {
				resultsInner.insertAdjacentHTML('beforeend', html);
			} else {
				resultsInner.innerHTML = html;
			}
		}
	}

	/**
	 * Perform REST API search
	 *
	 * @param {boolean} append - Whether to append results (for infinite scroll)
	 */
	async function performSearch(append = false) {
		if (state.isLoading) {
			return;
		}

		// Require at least 2 characters or a category
		if (state.query.length < 2 && !state.categoryId) {
			if (!append) {
				clearResults();
				hideResults();
			}
			return;
		}

		state.isLoading = true;

		if (!append) {
			state.page = 1;
			state.hasMore = true;
			clearResults();
			showResults();
		}

		showLoading();

		// Build REST API URL with query parameters
		const params = new URLSearchParams();
		if (state.query) {
			params.append('query', state.query);
		}
		if (state.categoryId) {
			params.append('category', state.categoryId);
		}
		params.append('page', state.page);

		const url = `${atsSearch.rest_url}?${params.toString()}`;

		try {
			const response = await fetch(url, {
				method: 'GET',
				headers: {
					'Content-Type': 'application/json',
				},
			});

			const result = await response.json();

			hideLoading();

			if (result.products) {
				const { products, has_more } = result;

				if (products.length === 0 && !append) {
					showNoResults();
				} else {
					renderProducts(products, append);
				}

				state.hasMore = has_more;
			} else {
				if (!append) {
					showNoResults();
				}
				console.error('Search error:', result.message || 'Unknown error');
			}
		} catch (error) {
			hideLoading();
			console.error('Search request failed:', error);
			if (!append) {
				showNoResults();
			}
		} finally {
			state.isLoading = false;
		}
	}

	// Debounced search function
	const debouncedSearch = debounce(() => performSearch(false), 300);

	/**
	 * Handle search input change
	 *
	 * @param {Event} event - Input event
	 */
	function handleSearchInput(event) {
		state.query = event.target.value.trim();
		debouncedSearch();
	}

	/**
	 * Handle category selection
	 *
	 * @param {Event} event - Click event
	 */
	function handleCategorySelect(event) {
		event.preventDefault();
		const link = event.target.closest('a[data-category-id]');

		if (!link) {
			return;
		}

		const categoryId = link.getAttribute('data-category-id');
		const categoryName = link.getAttribute('data-category-name');

		state.categoryId = categoryId;
		if (selectedCategoryInput) {
			selectedCategoryInput.value = categoryId;
		}
		if (selectedCategoryText) {
			selectedCategoryText.textContent = categoryName;
		}

		// Close the category dropdown
		categoryDropdown?.classList.add('hidden');

		// Trigger search with new category
		performSearch(false);
	}

	/**
	 * Toggle category dropdown
	 */
	function toggleCategoryDropdown(event) {
		event.preventDefault();
		event.stopPropagation();
		categoryDropdown?.classList.toggle('hidden');
	}

	/**
	 * Set up Intersection Observer for infinite scroll
	 */
	function setupInfiniteScroll() {
		if (!searchSentinel) {
			return;
		}

		const observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting && state.hasMore && !state.isLoading) {
						state.page += 1;
						performSearch(true);
					}
				});
			},
			{
				root: null,
				rootMargin: '100px',
				threshold: 0.1,
			}
		);

		observer.observe(searchSentinel);
	}

	/**
	 * Handle click outside to close results and dropdown
	 *
	 * @param {Event} event - Click event
	 */
	function handleClickOutside(event) {
		if (!container.contains(event.target)) {
			hideResults();
			categoryDropdown?.classList.add('hidden');
		}
	}

	/**
	 * Handle escape key to close results
	 *
	 * @param {KeyboardEvent} event - Keyboard event
	 */
	function handleEscapeKey(event) {
		if (event.key === 'Escape') {
			hideResults();
			categoryDropdown?.classList.add('hidden');
		}
	}

	// Event listeners
	searchInput.addEventListener('input', handleSearchInput);
	searchInput.addEventListener('focus', () => {
		if (state.query.length >= 2 || state.categoryId) {
			showResults();
		}
	});

	// Category dropdown
	if (categoryBtn) {
		categoryBtn.addEventListener('click', toggleCategoryDropdown);
	}

	if (categoryDropdown) {
		categoryDropdown.addEventListener('click', handleCategorySelect);
	}

	// Global event listeners
	document.addEventListener('click', handleClickOutside);
	document.addEventListener('keydown', handleEscapeKey);

	// Set up infinite scroll
	setupInfiniteScroll();
}

/**
 * Initialize the ATS Search functionality for all instances
 */
export function initATSSearch() {
	const searchContainers = document.querySelectorAll('[data-ats-search]');

	if (searchContainers.length === 0) {
		console.log('ATS Search: No search containers found on page');
		return;
	}

	console.log(`ATS Search: Found ${searchContainers.length} search container(s)`);

	// Initialize each search container
	searchContainers.forEach((container) => {
		// Only initialize visible containers
		const style = window.getComputedStyle(container);
		const parentStyle = window.getComputedStyle(container.parentElement);
		const grandParentStyle = container.parentElement?.parentElement
			? window.getComputedStyle(container.parentElement.parentElement)
			: null;

		// Check if container or its parents are hidden
		const isHidden =
			style.display === 'none' ||
			parentStyle?.display === 'none' ||
			grandParentStyle?.display === 'none';

		if (!isHidden) {
			initSearchInstance(container);
		} else {
			console.log(
				`ATS Search: Skipping hidden container (${container.dataset.searchContext || 'unknown'})`
			);
		}
	});
}
