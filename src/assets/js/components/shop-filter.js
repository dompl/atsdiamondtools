/**
 * Shop Filter Component
 *
 * Handles AJAX filtering for shop and category pages with infinite scroll
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
	const infiniteScrollTrigger = document.querySelector('.rfs-ref-infinite-scroll-trigger');

	// Price slider elements
	const priceSliderMin = document.querySelector('.rfs-ref-price-slider-min');
	const priceSliderMax = document.querySelector('.rfs-ref-price-slider-max');
	const priceSliderTrack = document.querySelector('.rfs-ref-price-slider-track');
	const priceMinValue = document.querySelector('.rfs-ref-price-min-value');
	const priceMaxValue = document.querySelector('.rfs-ref-price-max-value');

	// Infinite scroll state
	let isLoadingMore = false;
	let maxPages = infiniteScrollTrigger ? parseInt(infiniteScrollTrigger.dataset.maxPages) : 1;
	let currentPage = 1;

	// Get initial category from page (for category pages)
	const productsContainerEl = document.querySelector('.rfs-ref-products-container');
	const initialCategory = productsContainerEl ? parseInt(productsContainerEl.dataset.currentCategory) || 0 : 0;

	// Current filter state
	let currentFilters = {
		category: initialCategory,
		min_price: priceSliderMin ? parseInt(priceSliderMin.min) : 0,
		max_price: priceSliderMax ? parseInt(priceSliderMax.max) : 1000,
		orderby: 'default',
		paged: 1,
		favourites_only: false
	};

	/**
	 * Show loading state
	 * @param {boolean} scrollToTop - Whether to scroll to products container
	 */
	function showLoading(scrollToTop = true) {
		if (loadingOverlay) {
			loadingOverlay.classList.remove('hidden');
		}
		// Only scroll to top when filtering (not when loading more via infinite scroll)
		if (scrollToTop) {
			const container = document.querySelector('.rfs-ref-products-container');
			if (container) {
				const offset = container.getBoundingClientRect().top + window.pageYOffset - 100;
				window.scrollTo({ top: offset, behavior: 'smooth' });
			}
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
	 * @param {string} html - Products HTML to insert
	 * @param {boolean} append - If true, append instead of replace
	 */
	function updateProductsGrid(html, append = false) {
		if (productsContainer) {
			if (append) {
				// Create temporary container to parse HTML
				const tempDiv = document.createElement('div');
				tempDiv.innerHTML = html.trim();

				// Get all direct children (product cards)
				const products = Array.from(tempDiv.children);

				// Append each product card to the grid
				products.forEach(function(product) {
					// Only append actual product cards (not error messages)
					if (product.hasAttribute('data-product-id') || product.classList.contains('rfs-ref-product-card')) {
						productsContainer.appendChild(product);
					}
				});
			} else {
				productsContainer.innerHTML = html;
			}

			// Re-initialize product quick view for new products
			reinitializeQuickView();
		}
	}

	/**
	 * Re-initialize product quick view handlers
	 * Triggers event for product-quick-view.js to rebind
	 */
	function reinitializeQuickView() {
		// Check if jQuery is available (used by product-quick-view.js)
		if (window.jQuery) {
			// Trigger custom event that product-quick-view.js listens for
			window.jQuery(document).trigger('ats_products_loaded');
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
	 * Update or hide category banner
	 * @param {object} bannerData - Banner data from Ajax response
	 */
	function updateCategoryBanner(bannerData) {
		let bannerContainer = document.querySelector('.rfs-ref-shop-container');
		let existingBanner = document.querySelector('.rfs-ref-category-banner');

		if (bannerData && bannerData.show_banner) {
			// Create or update banner
			const bannerHTML = `
				<div class="rfs-ref-category-banner relative h-[200px] md:h-[250px] overflow-hidden rounded-lg">
					<!-- Background Image -->
					<div class="absolute inset-0">
						<img src="${bannerData.banner_image}"
						     alt="${bannerData.category_name}"
						     class="w-full h-full object-cover" />
						<div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-black/30"></div>
					</div>

					<!-- Decorative Brand Elements -->
					<div class="rfs-ref-banner-decorations absolute inset-0 pointer-events-none opacity-20">
						<!-- Large Circle - Top Right -->
						<div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-primary-600 blur-3xl"></div>
						<!-- Medium Circle - Bottom Left -->
						<div class="absolute -bottom-16 -left-16 w-48 h-48 rounded-full bg-ats-yellow blur-2xl"></div>
						<!-- Small Accent - Middle -->
						<div class="absolute top-1/2 right-1/4 w-32 h-32 rounded-full bg-primary-300 blur-xl"></div>
					</div>

					<!-- Content -->
					<div class="rfs-ref-category-banner-content relative z-10 h-full flex flex-col justify-center px-8 md:px-12">
						<div class="max-w-3xl">
							<h1 class="rfs-ref-category-title text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-2 drop-shadow-lg">
								${bannerData.category_name}
							</h1>
							${bannerData.category_desc ? `
								<div class="rfs-ref-category-description text-sm md:text-base text-gray-200 leading-relaxed max-w-2xl drop-shadow-md">
									${bannerData.category_desc}
								</div>
							` : ''}
						</div>
					</div>
				</div>
			`;

			if (existingBanner) {
				// Update existing banner
				existingBanner.outerHTML = bannerHTML;
			} else {
				// Create banner container if it doesn't exist
				if (!bannerContainer) {
					const productsContainer = document.querySelector('.rfs-ref-products-container');
					if (productsContainer) {
						bannerContainer = document.createElement('div');
						bannerContainer.className = 'rfs-ref-shop-container container mx-auto px-4 pt-8 mb-8';
						productsContainer.parentNode.insertBefore(bannerContainer, productsContainer);
					}
				}

				if (bannerContainer) {
					bannerContainer.innerHTML = bannerHTML;
				}
			}
		} else {
			// Hide banner when "All Products" is selected
			if (existingBanner) {
				const bannerWrapper = existingBanner.parentElement;
				if (bannerWrapper && bannerWrapper.classList.contains('rfs-ref-shop-container')) {
					bannerWrapper.remove();
				} else {
					existingBanner.remove();
				}
			}
		}
	}

	/**
	 * Main filter function - sends AJAX request
	 * @param {object} newFilters - New filter values to apply
	 * @param {boolean} loadMore - If true, append products instead of replace
	 */
	async function filterProducts(newFilters, loadMore = false) {
		if (newFilters) {
			currentFilters = Object.assign({}, currentFilters, newFilters);
		}

		// Reset pagination on filter change (unless explicitly loading more)
		if (!loadMore && (!newFilters || !newFilters.hasOwnProperty('paged'))) {
			currentFilters.paged = 1;
			currentPage = 1;
			// Show the infinite scroll trigger again when resetting filters
			if (infiniteScrollTrigger) {
				infiniteScrollTrigger.style.display = 'block';
			}
		}

		// Don't scroll to top when loading more via infinite scroll
		showLoading(!loadMore);

		const formData = new FormData();
		formData.append('action', 'ats_filter_products');
		formData.append('nonce', themeData.shop_filter_nonce);
		formData.append('category', currentFilters.category);
		formData.append('min_price', currentFilters.min_price);
		formData.append('max_price', currentFilters.max_price);
		formData.append('orderby', currentFilters.orderby);
		formData.append('paged', currentFilters.paged);
		formData.append('favourites_only', currentFilters.favourites_only ? '1' : '0');
		// Load 12 initially, then 8 at a time for infinite scroll
		formData.append('per_page', loadMore ? 8 : 12);

		try {
			const response = await fetch(themeData.ajax_url, {
				method: 'POST',
				body: formData
			});

			const data = await response.json();

			if (data.success) {
				// Update or append products based on loadMore flag
				updateProductsGrid(data.data.products_html, loadMore);

				// Update counts - for infinite scroll, showing_end is cumulative
				updateResultsCount(data.data.showing_end, data.data.total_products);

				// Update banner if data is provided (only on filter change, not on infinite scroll)
				if (!loadMore && data.data.banner_data) {
					updateCategoryBanner(data.data.banner_data);
				}

				// Update max pages from response
				if (data.data.max_pages) {
					maxPages = data.data.max_pages;
					if (infiniteScrollTrigger) {
						infiniteScrollTrigger.dataset.maxPages = maxPages;
					}
				}

				// If we've loaded all products, hide the loading trigger
				if (currentPage >= maxPages) {
					if (infiniteScrollTrigger) {
						infiniteScrollTrigger.style.display = 'none';
					}
				}
			} else {
				console.error('Filter error:', data.data ? data.data.message : 'Unknown error');
			}
		} catch (error) {
			console.error('Filter AJAX error:', error);
		} finally {
			hideLoading();
			isLoadingMore = false;
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

				// Update active state - use primary brand color
				categoryButtons.forEach(function(btn) {
					btn.classList.remove('bg-primary-600', 'text-white', 'font-bold');
					btn.classList.add('text-gray-700');
				});
				this.classList.remove('text-gray-700');
				this.classList.add('bg-primary-600', 'text-white', 'font-bold');

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

	/**
	 * Infinite Scroll - Load more products on scroll
	 */
	let scrollObserver;

	function checkAndLoadMore() {
		// Check if trigger is visible and we have more pages
		if (!isLoadingMore && currentPage < maxPages && infiniteScrollTrigger) {
			const rect = infiniteScrollTrigger.getBoundingClientRect();
			const windowHeight = window.innerHeight || document.documentElement.clientHeight;

			// If trigger is within viewport (no early loading), load more
			// Reduced from +200px margin to 0 to prevent early loading
			if (rect.top <= windowHeight) {
				isLoadingMore = true;
				currentPage++;
				currentFilters.paged = currentPage;

				// Update trigger data attribute
				infiniteScrollTrigger.dataset.page = currentPage;

				// Load more products (append mode)
				filterProducts({ paged: currentPage }, true).then(function() {
					// Only check for more if we haven't reached the end
					if (currentPage < maxPages) {
						// After loading, check if we need to load more immediately
						// (in case trigger is still visible after new products added)
						setTimeout(checkAndLoadMore, 500);
					}
				});
			}
		}
	}

	if (infiniteScrollTrigger && 'IntersectionObserver' in window) {
		scrollObserver = new IntersectionObserver(function(entries) {
			entries.forEach(function(entry) {
				// When the trigger element becomes visible
				if (entry.isIntersecting && !isLoadingMore && currentPage < maxPages) {
					checkAndLoadMore();
				}
			});
		}, {
			root: null, // viewport
			rootMargin: '0px', // Load only when trigger is visible (no early loading)
			threshold: 0
		});

		// Start observing the trigger element
		scrollObserver.observe(infiniteScrollTrigger);

		// Also check on scroll events as backup
		let scrollTimeout;
		window.addEventListener('scroll', function() {
			clearTimeout(scrollTimeout);
			scrollTimeout = setTimeout(checkAndLoadMore, 100);
		});
	}

	/**
	 * Favourite Products Button Handler
	 */
	const favouritesButton = document.querySelector('.rfs-ref-show-favourites-btn');
	if (favouritesButton) {
		favouritesButton.addEventListener('click', function() {
			const isActive = this.dataset.filterFavourites === 'true';

			// Toggle state
			const newState = !isActive;
			this.dataset.filterFavourites = newState.toString();

			// Update button appearance
			if (newState) {
				this.classList.add('bg-ats-brand', 'text-white', 'font-bold');
				this.classList.remove('bg-white', 'text-ats-dark');
			} else {
				this.classList.remove('bg-ats-brand', 'text-white', 'font-bold');
				this.classList.add('bg-white', 'text-ats-dark');
			}

			// Filter products
			filterProducts({ favourites_only: newState });
		});
	}
});
