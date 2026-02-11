/**
 * About Us - Testimonial Carousel
 * Initializes Splide carousel for the testimonials section
 */

import Splide from '@splidejs/splide';

function initTestimonialCarousel() {
	const testimonialCarousels = document.querySelectorAll('.rfs-ref-testimonial-carousel');

	if (testimonialCarousels.length === 0) {
		return;
	}

	testimonialCarousels.forEach((carousel) => {
		try {
			const splide = new Splide(carousel, {
				type: 'loop',
				perPage: 1,
				perMove: 1,
				gap: '1.5rem',
				padding: 0,
				pagination: false,
				arrows: false,
				autoplay: true,
				interval: 6000,
				pauseOnHover: true,
				pauseOnFocus: true,
			});

			splide.mount();

			// Connect custom navigation buttons
			const prevBtn = carousel.querySelector('.rfs-ref-testimonial-prev');
			const nextBtn = carousel.querySelector('.rfs-ref-testimonial-next');

			if (prevBtn) {
				prevBtn.addEventListener('click', (e) => {
					e.preventDefault();
					splide.go('<');
				});
			}

			if (nextBtn) {
				nextBtn.addEventListener('click', (e) => {
					e.preventDefault();
					splide.go('>');
				});
			}
		} catch (error) {
			console.error('Testimonial carousel error:', error);
		}
	});
}

// Initialize on DOMContentLoaded
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initTestimonialCarousel);
} else {
	initTestimonialCarousel();
}
