/**
 * Shop Filter Component
 *
 * Handles AJAX filtering for shop and category pages
 * PURE VANILLA JS - NO IMPORTS
 *
 * @package ATS Diamond Tools
 */

document.addEventListener('DOMContentLoaded', function() {
	// Check if we're on shop/category page
	const productsContainer = document.querySelector('.rfs-ref-products-grid');
	if (!productsContainer) {
		return; // Not on shop page
	}

	// Elements
	const loadingOverlay = document.querySelector('.rfs-ref-loading-overlay');
	const categoryButtons = document.querySelectorAll('.rfs-ref-category-link');
	const sortOptions = document.querySelectorAll('.rfs-ref-sort-option');
	const currentSortLabel = document.querySelector('.rfs-ref-current-sort');
	const showingCount = document.querySelector('.rfs-ref-showing-count');
	const totalCount = document.querySelector('.rfs-ref-total-count');

	// Price slider elements
	const priceSliderMin = document.querySelector('.rfs-ref-price-slider-min');
	const priceSliderMax = document.querySelector('.rfs-ref-price-slider-max');
	const priceSliderTrack = document.querySelector('.rfs-ref-price-slider-track');
	const priceMinValue = document.querySelector('.rfs-ref-price-min-value');
	const priceMaxValue = document.querySelector('.rfs-ref-price-max-value');

	// Current filter state
	let currentFilters = {
		category: 0,
		min_price: priceSliderMin ? parseInt(priceSliderMin.min) : 0,
		max_price: priceSliderMax ? parseInt(priceSliderMax.max) : 1000,
		orderby: 'default',
		paged: 1
	};

	/**
	 * Show loading state
	 */
	function showLoading() {
		if (loadingOverlay) {
			loadingOverlay.classList.remove('hidden');
		}
		const container = document.querySelector('.rfs-ref-products-container');
		if (container) {
			const offset = container.getBoundingClientRect().top + window.pageYOffset - 100;
			window.scrollTo({ top: offset, behavior: 'smooth' });
		}
	}

	/**
	 * Hide loading state
	 */
	function hideLoading() {
		if (loadingOverlay) {
			loadingOverlay.classList.add('hidden');
		}
	}

	/**
	 * Update products grid with new HTML
	 */
	function updateProductsGrid(html) {
		if (productsContainer) {
			productsContainer.innerHTML = html;
		}
	}

	/**
	 * Update results count display
	 */
	function updateResultsCount(showing, total) {
		if (showingCount) {
			showingCount.textContent = showing;
		}
		if (totalCount) {
			totalCount.textContent = total;
		}
	}

	/**
	 * Main filter function - sends AJAX request
	 */
	async function filterProducts(newFilters) {
		if (newFilters) {
			currentFilters = Object.assign({}, currentFilters, newFilters);
		}

		// Reset pagination on filter change
		if (!newFilters || !newFilters.hasOwnProperty('paged')) {
			currentFilters.paged = 1;
		}

		showLoading();

		const formData = new FormData();
		formData.append('action', 'ats_filter_products');
		formData.append('nonce', themeData.shop_filter_nonce);
		formData.append('category', currentFilters.category);
		formData.append('min_price', currentFilters.min_price);
		formData.append('max_price', currentFilters.max_price);
		formData.append('orderby', currentFilters.orderby);
		formData.append('paged', currentFilters.paged);
		formData.append('per_page', 12);

		try {
			const response = await fetch(themeData.ajax_url, {
				method: 'POST',
				body: formData
			});

			const data = await response.json();

			if (data.success) {
				updateProductsGrid(data.data.products_html);
				updateResultsCount(data.data.showing_end, data.data.total_products);
			} else {
				console.error('Filter error:', data.data ? data.data.message : 'Unknown error');
			}
		} catch (error) {
			console.error('Filter AJAX error:', error);
		} finally {
			hideLoading();
		}
	}

	/**
	 * Category button click handler
	 */
	if (categoryButtons.length) {
		categoryButtons.forEach(function(button) {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const categoryId = parseInt(this.dataset.categoryId) || 0;

				// Update active state
				categoryButtons.forEach(function(btn) {
					btn.classList.remove('bg-ats-yellow', 'text-ats-dark', 'font-bold');
					btn.classList.add('text-gray-700');
				});
				this.classList.remove('text-gray-700');
				this.classList.add('bg-ats-yellow', 'text-ats-dark', 'font-bold');

				// Filter by category
				filterProducts({ category: categoryId });
			});
		});
	}

	/**
	 * Sort option click handler
	 */
	if (sortOptions.length) {
		sortOptions.forEach(function(option) {
			option.addEventListener('click', function(e) {
				e.preventDefault();
				const sortValue = this.dataset.sort;
				const sortLabel = this.textContent;

				// Update dropdown label
				if (currentSortLabel) {
					currentSortLabel.textContent = sortLabel;
				}

				// Update active state
				sortOptions.forEach(function(opt) {
					opt.classList.remove('bg-ats-yellow', 'font-bold');
				});
				this.classList.add('bg-ats-yellow', 'font-bold');

				// Filter by sort
				filterProducts({ orderby: sortValue });
			});
		});
	}

	/**
	 * Price slider functionality
	 */
	if (priceSliderMin && priceSliderMax) {
		const minGap = 10;
		let debounceTimer;

		function updateTrack() {
			const minVal = parseInt(priceSliderMin.value);
			const maxVal = parseInt(priceSliderMax.value);
			const rangeMin = parseInt(priceSliderMin.min);
			const rangeMax = parseInt(priceSliderMax.max);

			const percentMin = ((minVal - rangeMin) / (rangeMax - rangeMin)) * 100;
			const percentMax = ((maxVal - rangeMin) / (rangeMax - rangeMin)) * 100;

			if (priceSliderTrack) {
				priceSliderTrack.style.left = percentMin + '%';
				priceSliderTrack.style.width = (percentMax - percentMin) + '%';
			}
		}

		function updateValues() {
			const minVal = parseInt(priceSliderMin.value);
			const maxVal = parseInt(priceSliderMax.value);

			if (priceMinValue) {
				priceMinValue.textContent = '£' + minVal;
			}
			if (priceMaxValue) {
				priceMaxValue.textContent = '£' + maxVal;
			}
		}

		function debounceFilter() {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(function() {
				filterProducts({
					min_price: parseInt(priceSliderMin.value),
					max_price: parseInt(priceSliderMax.value)
				});
			}, 800);
		}

		priceSliderMin.addEventListener('input', function() {
			const minVal = parseInt(this.value);
			const maxVal = parseInt(priceSliderMax.value);

			if (maxVal - minVal < minGap) {
				this.value = maxVal - minGap;
			}

			updateTrack();
			updateValues();
			debounceFilter();
		});

		priceSliderMax.addEventListener('input', function() {
			const minVal = parseInt(priceSliderMin.value);
			const maxVal = parseInt(this.value);

			if (maxVal - minVal < minGap) {
				this.value = minVal + minGap;
			}

			updateTrack();
			updateValues();
			debounceFilter();
		});

		// Initialize track position
		updateTrack();
		updateValues();
	}
});
