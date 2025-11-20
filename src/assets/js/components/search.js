/**
 * ATS Diamond Tools - AJAX Search Component
 *
 * Provides real-time product search with category filtering and infinite scroll.
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
 * Initialize the ATS Search functionality
 */
export function initATSSearch() {
	const searchContainer = document.querySelector('[data-ats-search]');

	if (!searchContainer) {
		return;
	}

	// Check if atsSearch is localized
	if (typeof atsSearch === 'undefined') {
		console.error('ATS Search: Missing localized data');
		return;
	}

	// State management
	const state = {
		query: '',
		categoryId: '',
		page: 1,
		isLoading: false,
		hasMore: true,
		isMobile: false,
	};

	// Desktop elements
	const desktopInput = document.getElementById('ats-search-input');
	const desktopCategoryBtn = document.getElementById('ats-search-category-btn');
	const desktopCategoryDropdown = document.getElementById('ats-search-category-dropdown');
	const selectedCategoryText = document.getElementById('ats-selected-category-text');
	const selectedCategoryInput = document.getElementById('ats-selected-category');
	const resultsContainer = document.getElementById('ats-search-results');
	const resultsInner = document.getElementById('ats-search-results-inner');
	const loadingIndicator = document.getElementById('ats-search-loading');
	const noResultsMessage = document.getElementById('ats-search-no-results');
	const searchSentinel = document.getElementById('ats-search-sentinel');

	// Mobile elements
	const mobileSearchTrigger = document.getElementById('ats-mobile-search-trigger');
	const mobileSearchModal = document.getElementById('ats-mobile-search-modal');
	const mobileSearchClose = document.getElementById('ats-mobile-search-close');
	const mobileSearchInput = document.getElementById('ats-mobile-search-input');
	const mobileCategorySelect = document.getElementById('ats-mobile-category-select');
	const mobileResultsInner = document.getElementById('ats-mobile-search-results-inner');
	const mobileLoadingIndicator = document.getElementById('ats-mobile-search-loading');
	const mobileNoResultsMessage = document.getElementById('ats-mobile-search-no-results');
	const mobileSearchSentinel = document.getElementById('ats-mobile-search-sentinel');

	/**
	 * Show loading state
	 */
	function showLoading() {
		if (state.isMobile) {
			mobileLoadingIndicator?.classList.remove('hidden');
			mobileNoResultsMessage?.classList.add('hidden');
		} else {
			loadingIndicator?.classList.remove('hidden');
			noResultsMessage?.classList.add('hidden');
		}
	}

	/**
	 * Hide loading state
	 */
	function hideLoading() {
		loadingIndicator?.classList.add('hidden');
		mobileLoadingIndicator?.classList.add('hidden');
	}

	/**
	 * Show no results message
	 */
	function showNoResults() {
		if (state.isMobile) {
			mobileNoResultsMessage?.classList.remove('hidden');
		} else {
			noResultsMessage?.classList.remove('hidden');
		}
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
		if (mobileResultsInner) {
			mobileResultsInner.innerHTML = '';
		}
		noResultsMessage?.classList.add('hidden');
		mobileNoResultsMessage?.classList.add('hidden');
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
		const container = state.isMobile ? mobileResultsInner : resultsInner;

		if (container) {
			if (append) {
				container.insertAdjacentHTML('beforeend', html);
			} else {
				container.innerHTML = html;
			}
		}
	}

	/**
	 * Perform REST API search (faster than AJAX)
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
	 * Handle category selection (desktop)
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
		selectedCategoryInput.value = categoryId;
		selectedCategoryText.textContent = categoryName;

		// Close the category dropdown
		desktopCategoryDropdown?.classList.add('hidden');

		// Trigger search with new category
		performSearch(false);
	}

	/**
	 * Handle mobile category change
	 *
	 * @param {Event} event - Change event
	 */
	function handleMobileCategoryChange(event) {
		state.categoryId = event.target.value;
		performSearch(false);
	}

	/**
	 * Set up Intersection Observer for infinite scroll
	 *
	 * @param {HTMLElement} sentinel - Sentinel element to observe
	 */
	function setupInfiniteScroll(sentinel) {
		if (!sentinel) {
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

		observer.observe(sentinel);
	}

	/**
	 * Open mobile search modal
	 */
	function openMobileModal() {
		state.isMobile = true;
		mobileSearchModal?.classList.remove('hidden');
		document.body.style.overflow = 'hidden';
		mobileSearchInput?.focus();
	}

	/**
	 * Close mobile search modal
	 */
	function closeMobileModal() {
		state.isMobile = false;
		mobileSearchModal?.classList.add('hidden');
		document.body.style.overflow = '';
		clearResults();
	}

	/**
	 * Handle click outside to close results
	 *
	 * @param {Event} event - Click event
	 */
	function handleClickOutside(event) {
		if (!state.isMobile && !searchContainer.contains(event.target)) {
			hideResults();
		}
	}

	/**
	 * Handle escape key to close results/modal
	 *
	 * @param {KeyboardEvent} event - Keyboard event
	 */
	function handleEscapeKey(event) {
		if (event.key === 'Escape') {
			if (state.isMobile) {
				closeMobileModal();
			} else {
				hideResults();
			}
		}
	}

	// Event listeners - Desktop
	desktopInput?.addEventListener('input', handleSearchInput);
	desktopInput?.addEventListener('focus', () => {
		if (state.query.length >= 2 || state.categoryId) {
			showResults();
		}
	});

	// Category dropdown clicks
	desktopCategoryDropdown?.addEventListener('click', handleCategorySelect);

	// Hide results when clicking category button
	desktopCategoryBtn?.addEventListener('click', () => {
		hideResults();
	});

	// Event listeners - Mobile
	mobileSearchTrigger?.addEventListener('click', openMobileModal);
	mobileSearchClose?.addEventListener('click', closeMobileModal);
	mobileSearchInput?.addEventListener('input', handleSearchInput);
	mobileCategorySelect?.addEventListener('change', handleMobileCategoryChange);

	// Close modal on backdrop click
	mobileSearchModal?.addEventListener('click', (event) => {
		if (event.target === mobileSearchModal) {
			closeMobileModal();
		}
	});

	// Global event listeners
	document.addEventListener('click', handleClickOutside);
	document.addEventListener('keydown', handleEscapeKey);

	// Set up infinite scroll for both desktop and mobile
	setupInfiniteScroll(searchSentinel);
	setupInfiniteScroll(mobileSearchSentinel);

	// Sync desktop and mobile inputs
	desktopInput?.addEventListener('input', () => {
		if (mobileSearchInput) {
			mobileSearchInput.value = desktopInput.value;
		}
	});

	mobileSearchInput?.addEventListener('input', () => {
		if (desktopInput) {
			desktopInput.value = mobileSearchInput.value;
		}
	});
}
