/**
 * Banner Component - Carousel and Category Toggle
 * Note: HTML and data are now rendered via PHP/ACF in functions/acf/components/banner.php
 * This JS file only handles interactions and carousel logic
 */

// Initialize banner functionality for each banner instance on the page
document.addEventListener('DOMContentLoaded', function () {
	// Find all banner instances (supports multiple banners on one page)
	const bannerContainers = document.querySelectorAll('.rfs-ref-banner-container');

	bannerContainers.forEach(function (bannerContainer) {
		initBanner(bannerContainer);
	});
});

function initBanner(bannerContainer) {
	// Get elements scoped to this banner instance
	const categoryBtn = bannerContainer.querySelector('.rfs-ref-category-btn');
	const categoryList = bannerContainer.querySelector('.rfs-ref-category-list');
	const categoryChevron = bannerContainer.querySelector('.rfs-ref-category-chevron');
	const prevBtn = bannerContainer.querySelector('.rfs-ref-prev-btn');
	const nextBtn = bannerContainer.querySelector('.rfs-ref-next-btn');
	const dotsContainer = bannerContainer.querySelector('.rfs-ref-carousel-dots');
	const slideItems = bannerContainer.querySelectorAll('.rfs-ref-slide-item');
	const slidesCount = parseInt(bannerContainer.getAttribute('data-slides-count')) || slideItems.length;

	let currentSlide = 0;
	let slideInterval;
	const totalSlides = slideItems.length;

	// Check if mobile (window width < 1024px for lg breakpoint)
	function isMobile() {
		return window.innerWidth < 1024;
	}

	// Close category accordion on mobile by default
	function closeCategoryOnMobile() {
		if (isMobile() && categoryList && categoryChevron) {
			categoryList.classList.remove('grid-rows-1');
			categoryList.classList.add('grid-rows-0');
			categoryChevron.classList.remove('rotate-180');
		}
	}

	// Initialize: close on mobile
	closeCategoryOnMobile();

	// --- Match Sidebar Height to Banner Carousel ---
	function matchSidebarHeight() {
		// Only match height on desktop (lg breakpoint and up)
		if (!isMobile()) {
			// Find the carousel within the same banner wrapper
			const bannerWrapper = bannerContainer.closest('.rfs-ref-banner-wrapper');
			if (bannerWrapper) {
				const carousel = bannerWrapper.querySelector('.rfs-ref-banner-carousel');
				if (carousel && bannerContainer) {
					const carouselHeight = carousel.offsetHeight;
					if (carouselHeight > 0) {
						bannerContainer.style.height = carouselHeight + 'px';
					}
				}
			}
		} else {
			// Remove fixed height on mobile
			bannerContainer.style.height = '';
		}
	}

	// Initial height match
	matchSidebarHeight();

	// Rematch on window resize
	let resizeMatchTimer;
	window.addEventListener('resize', function () {
		clearTimeout(resizeMatchTimer);
		resizeMatchTimer = setTimeout(function () {
			matchSidebarHeight();
		}, 250);
	});

	// --- Category Toggle Logic ---
	if (categoryBtn && categoryList && categoryChevron) {
		categoryBtn.addEventListener('click', function () {
			// Check if desktop toggle is allowed via data attribute
			const allowDesktopToggle = categoryBtn.getAttribute('data-allow-desktop-toggle') === 'true';

			// Only allow toggle if mobile OR explicit desktop toggle allowed
			if (!isMobile() && !allowDesktopToggle) return;

			const isOpen = categoryList.classList.contains('grid-rows-1');
			if (isOpen) {
				categoryList.classList.remove('grid-rows-1');
				categoryList.classList.add('grid-rows-0');
				categoryChevron.classList.remove('rotate-180');
			} else {
				categoryList.classList.remove('grid-rows-0');
				categoryList.classList.add('grid-rows-1');
				categoryChevron.classList.add('rotate-180');
			}
		});
	}

	// Return if no slides found (stop Carousel logic)
	if (!slideItems.length) return;

	// --- Carousel Logic (only if more than 1 slide) ---
	if (slidesCount > 1) {
		function updateCarousel() {
			const dotItems = dotsContainer ? dotsContainer.querySelectorAll('.rfs-ref-carousel-dot') : [];

			slideItems.forEach((el, i) => {
				if (i === currentSlide) {
					el.classList.remove('opacity-0', 'z-0');
					el.classList.add('opacity-100', 'z-10');
				} else {
					el.classList.remove('opacity-100', 'z-10');
					el.classList.add('opacity-0', 'z-0');
				}
			});

			dotItems.forEach((el, i) => {
				if (i === currentSlide) {
					el.className = 'rfs-ref-carousel-dot h-2 transition-all duration-300 rounded-full w-8 bg-[#fbbf24]';
				} else {
					el.className = 'rfs-ref-carousel-dot h-2 transition-all duration-300 rounded-full w-2 bg-white/50 hover:bg-white';
				}
			});
		}

		function goToSlide(index) {
			currentSlide = index;
			updateCarousel();
			resetTimer();
		}

		function nextSlide() {
			currentSlide = (currentSlide + 1) % totalSlides;
			updateCarousel();
			resetTimer();
		}

		function prevSlide() {
			currentSlide = currentSlide === 0 ? totalSlides - 1 : currentSlide - 1;
			updateCarousel();
			resetTimer();
		}

		function resetTimer() {
			clearInterval(slideInterval);
			slideInterval = setInterval(nextSlide, 6000);
		}

		// --- Event Listeners ---
		if (prevBtn) {
			prevBtn.onclick = prevSlide;
		}

		if (nextBtn) {
			nextBtn.onclick = nextSlide;
		}

		// Dot click handlers
		if (dotsContainer) {
			const dotItems = dotsContainer.querySelectorAll('.rfs-ref-carousel-dot');
			dotItems.forEach((dot, index) => {
				dot.onclick = function () {
					goToSlide(index);
				};
			});
		}

		// Start auto-rotation
		slideInterval = setInterval(nextSlide, 6000);

		// Pause on hover (optional enhancement)
		if (bannerContainer) {
			bannerContainer.addEventListener('mouseenter', function () {
				clearInterval(slideInterval);
			});

			bannerContainer.addEventListener('mouseleave', function () {
				slideInterval = setInterval(nextSlide, 6000);
			});
		}
	}

	// --- Resize Handler ---
	// Close category accordion when switching to mobile
	let resizeTimer;
	window.addEventListener('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function () {
			closeCategoryOnMobile();
		}, 250);
	});
}
