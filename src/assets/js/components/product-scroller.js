/**
 * Product Scroller Component
 * Initializes Splide carousel for product scrollers
 */

import Splide from '@splidejs/splide';
import { Grid } from '@splidejs/splide-extension-grid';

// Initialize all product scroller carousels
document.addEventListener('DOMContentLoaded', function () {
	const productWrappers = document.querySelectorAll('.rfs-ref-product-scroller-wrapper');


	if (productWrappers.length === 0) {
		return;
	}

	productWrappers.forEach((wrapper, index) => {
		const carouselElement = wrapper.querySelector('.rfs-ref-product-carousel');

		if (!carouselElement) return;

		const wrapperId = wrapper.getAttribute('id');
		if (!wrapperId) {
			return;
		}

		// Get the number of rows from the wrapper data attribute or default to 2
		const rowsCount = wrapper.dataset.rows ? parseInt(wrapper.dataset.rows) : 2;


		const carousel = new Splide(carouselElement, {
			type: 'slide',
			perMove: 1,
			autoWidth: false,
			width: '100%',
			padding: 0,
			focus: 0,
			trimSpace: false,
			rewind: true,
			clones: 0,
			grid: {
				rows: rowsCount,
				cols: 5,
				gap: {
					row: '1.5rem',
					col: '1.5rem',
				},
			},
			pagination: false,
			arrows: false,
			breakpoints: {
				1280: {
					grid: {
						rows: rowsCount > 1 ? 2 : 1,
						cols: 4,
						gap: {
							row: '1.5rem',
							col: '1.5rem',
						},
					},
				},
				1024: {
					grid: {
						rows: rowsCount > 1 ? 2 : 1,
						cols: 3,
						gap: {
							row: '1.5rem',
							col: '1.5rem',
						},
					},
				},
				768: {
					grid: {
						rows: 1,
						cols: 2,
						gap: {
							row: '1.25rem',
							col: '1.25rem',
						},
					},
				},
				640: {
					grid: {
						rows: 1,
						cols: 1,
						gap: {
							row: '1.5rem',
							col: '1.5rem',
						},
					},
					padding: {
						left: 0,
						right: 0,
					},
					gap: '1.5rem', // Gap between slides
				},
			},
		});

		// Mount with Grid extension
		try {
			carousel.mount({ Grid });

			// Connect custom navigation buttons
			// const prevButton = wrapper.querySelector('.rfs-ref-prev-arrow');
			// const nextButton = wrapper.querySelector('.rfs-ref-next-arrow');
			const prevButton = wrapper.querySelector('.ats-heading-prev-arrow');
			const nextButton = wrapper.querySelector('.ats-heading-next-arrow');

			if (prevButton) {
				prevButton.addEventListener('click', (e) => {
					e.preventDefault();
					carousel.go('<');
				});
			}

			if (nextButton) {
				nextButton.addEventListener('click', (e) => {
					e.preventDefault();
					carousel.go('>');
				});
			}

		} catch (error) {
		}
	});
});
