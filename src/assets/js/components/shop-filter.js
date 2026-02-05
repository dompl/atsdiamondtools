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
	const loadingSpinner = document.querySelector('.rfs-ref-loading-spinner-container');
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

	// Get saved view mode from localStorage or default to grid
	const savedViewMode = localStorage.getItem('ats_product_view_mode') || 'grid';

	// Current filter state
	let currentFilters = {
		category: initialCategory,
		application: 0,
		min_price: priceSliderMin ? parseInt(priceSliderMin.min) : 0,
		max_price: priceSliderMax ? parseInt(priceSliderMax.max) : 1000,
		orderby: 'default',
		paged: 1,
		favourites_only: false,
		view_mode: savedViewMode
	};

	/**
	 * Show loading state
	 * @param {boolean} scrollToTop - Whether to scroll to products container
	 */
	function showLoading(scrollToTop = true) {
		if (loadingSpinner) {
			loadingSpinner.classList.remove('hidden');
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
		if (loadingSpinner) {
			loadingSpinner.classList.add('hidden');
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

				// Append each product card with staggered animation
				products.forEach(function(product, index) {
					// Only append actual product cards (not error messages)
					if (product.hasAttribute('data-product-id') || product.classList.contains('rfs-ref-product-card')) {
						// Add initial hidden state
						product.style.opacity = '0';
						product.style.transform = 'translateY(20px)';
						product.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';

						productsContainer.appendChild(product);

						// Animate in with stagger
						setTimeout(function() {
							product.style.opacity = '1';
							product.style.transform = 'translateY(0)';
						}, index * 50);
					}
				});

				// Re-initialize product quick view for newly appended products
				reinitializeQuickView();
			} else {
				// Fade out existing products first
				const existingProducts = productsContainer.children;
				const fadeOutDuration = 200;

				// Quick fade out
				Array.from(existingProducts).forEach(function(product) {
					product.style.transition = 'opacity 0.2s ease-out';
					product.style.opacity = '0';
				});

				// After fade out, replace and animate in
				setTimeout(function() {
					productsContainer.innerHTML = html;

					// Animate new products in with stagger
					const newProducts = productsContainer.children;
					Array.from(newProducts).forEach(function(product, index) {
						product.style.opacity = '0';
						product.style.transform = 'translateY(20px)';
						product.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';

						setTimeout(function() {
							product.style.opacity = '1';
							product.style.transform = 'translateY(0)';
						}, index * 30);
					});

					// Re-initialize product quick view after products are replaced
					reinitializeQuickView();
				}, fadeOutDuration);
			}

			// Reinitialize moved inside conditional blocks to ensure DOM is updated
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
		formData.append('application', currentFilters.application);
		formData.append('min_price', currentFilters.min_price);
		formData.append('max_price', currentFilters.max_price);
		formData.append('orderby', currentFilters.orderby);
		formData.append('paged', currentFilters.paged);
		formData.append('favourites_only', currentFilters.favourites_only ? '1' : '0');
		formData.append('view_mode', currentFilters.view_mode);

		// For non-logged-in users, send localStorage favorites
		if (currentFilters.favourites_only && !themeData.is_user_logged_in) {
			try {
				const localFavorites = JSON.parse(localStorage.getItem('ats_favorites') || '[]');
				if (localFavorites.length > 0) {
					formData.append('favorite_ids', localFavorites.join(','));
				}
			} catch (e) {
			}
		}

		// Load 8 initially, then 4 at a time for infinite scroll (faster perceived performance)
		formData.append('per_page', loadMore ? 4 : 8);

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
			}
		} catch (error) {
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

				// If "All Products" is clicked, always redirect to shop page
				if (categoryId === 0) {
					const shopUrl = window.themeData?.shop_url || '/shop/';
					window.location.href = shopUrl;
					return;
				}

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
	 * Application button click handler
	 */
	const applicationButtons = document.querySelectorAll('.rfs-ref-application-link');
	if (applicationButtons.length) {
		applicationButtons.forEach(function(button) {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const applicationId = parseInt(this.dataset.applicationId) || 0;

				// Update active state - use primary brand color (same as categories)
				applicationButtons.forEach(function(btn) {
					btn.classList.remove('bg-primary-600', 'text-white', 'font-bold');
					btn.classList.add('text-gray-700');
					// Also update count text color
					const count = btn.querySelector('.rfs-ref-application-count');
					if (count) {
						count.classList.remove('text-white', 'opacity-80');
						count.classList.add('text-gray-500');
					}
				});
				this.classList.remove('text-gray-700');
				this.classList.add('bg-primary-600', 'text-white', 'font-bold');
				// Update count text color for active button
				const activeCount = this.querySelector('.rfs-ref-application-count');
				if (activeCount) {
					activeCount.classList.remove('text-gray-500');
					activeCount.classList.add('text-white', 'opacity-80');
				}

				// Filter by application
				filterProducts({ application: applicationId });
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

	/**
	 * View Toggle Functionality (Grid/List View)
	 */
	const viewToggleBtn = document.querySelector('.rfs-ref-toggle-view-btn');
	const productsGrid = document.querySelector('.rfs-ref-products-grid');
	const gridIcon = document.querySelector('.rfs-ref-grid-icon');
	const listIcon = document.querySelector('.rfs-ref-list-icon');
	const viewLabel = document.querySelector('.rfs-ref-view-label');

	if (viewToggleBtn && productsGrid && gridIcon && listIcon && viewLabel) {
		// Get saved view mode from localStorage or default to grid
		let currentViewMode = localStorage.getItem('ats_product_view_mode') || 'grid';

		// Apply saved view mode on page load
		function applyViewMode(mode) {
			if (mode === 'list') {
				// List view: 1 column on mobile, 2 columns on desktop
				productsGrid.classList.remove('grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-2', 'xl:grid-cols-4', 'justify-items-center');
				productsGrid.classList.add('grid-cols-1', 'lg:grid-cols-2', 'gap-6');
				gridIcon.classList.add('hidden');
				listIcon.classList.remove('hidden');
				viewLabel.textContent = 'Grid View';
				viewToggleBtn.dataset.viewMode = 'list';

				// Replace all product cards with list view (display="2")
				const productCards = productsGrid.querySelectorAll('[data-product-id]');
				productCards.forEach(function(card) {
					const productId = card.dataset.productId;
					// We'll need to reload products in list view via AJAX
					card.dataset.needsReload = 'true';
				});

				// Trigger a filter to reload products in list view
				filterProducts({ view_mode: 'list' });
			} else {
				// Grid view: responsive grid, use display="1" (vertical cards)
				productsGrid.classList.remove('grid-cols-1', 'lg:grid-cols-2', 'gap-6');
				productsGrid.classList.add('grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-2', 'xl:grid-cols-4', 'justify-items-center', 'gap-3');
				gridIcon.classList.remove('hidden');
				listIcon.classList.add('hidden');
				viewLabel.textContent = 'List View';
				viewToggleBtn.dataset.viewMode = 'grid';

				// Trigger a filter to reload products in grid view
				if (currentViewMode === 'list') {
					filterProducts({ view_mode: 'grid' });
				}
			}
			currentViewMode = mode;
			localStorage.setItem('ats_product_view_mode', mode);
		}

		// Initialize with saved view mode
		applyViewMode(currentViewMode);

		// Toggle view on button click
		viewToggleBtn.addEventListener('click', function() {
			const newMode = currentViewMode === 'grid' ? 'list' : 'grid';
			applyViewMode(newMode);
		});
	}

	/**
	 * Sidebar Accordion Functionality (Mobile Only)
	 * Collapses Categories, Applications, and Price Range sections on mobile
	 */
	const accordionToggles = document.querySelectorAll('.rfs-ref-accordion-toggle');

	// Check if mobile (window width < 1024px for lg breakpoint)
	function isMobile() {
		return window.innerWidth < 1024;
	}

	if (accordionToggles.length) {
		accordionToggles.forEach(function(toggle) {
			const accordionSection = toggle.closest('.rfs-ref-sidebar-accordion');
			const accordionContent = accordionSection ? accordionSection.querySelector('.rfs-ref-accordion-content') : null;
			const accordionChevron = toggle.querySelector('.rfs-ref-accordion-chevron');

			if (!accordionContent) return;

			// Close accordion on mobile by default
			function closeAccordionOnMobile() {
				if (isMobile() && accordionContent && accordionChevron) {
					accordionContent.classList.remove('grid-rows-1');
					accordionContent.classList.add('grid-rows-0');
					accordionChevron.classList.remove('rotate-180');
				}
			}

			// Initialize: close on mobile, open on desktop
			closeAccordionOnMobile();

			// Click handler
			toggle.addEventListener('click', function(e) {
				// Only allow toggle on mobile
				if (!isMobile()) {
					return;
				}

				e.preventDefault();
				e.stopPropagation();

				const isOpen = accordionContent.classList.contains('grid-rows-1');

				if (isOpen) {
					accordionContent.classList.remove('grid-rows-1');
					accordionContent.classList.add('grid-rows-0');
					accordionChevron.classList.remove('rotate-180');
				} else {
					accordionContent.classList.remove('grid-rows-0');
					accordionContent.classList.add('grid-rows-1');
					accordionChevron.classList.add('rotate-180');
				}
			});

			// Resize handler
			let resizeTimer;
			window.addEventListener('resize', function() {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function() {
					closeAccordionOnMobile();
				}, 250);
			});
		});
	}
});
