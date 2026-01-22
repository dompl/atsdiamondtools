/**
 * Product Review Form
 * Handles interactive star rating and inline form validation
 */

export function initReviewForm() {
	// Only run on single product pages
	if (!document.body.classList.contains('single-product')) {
		return;
	}

	const reviewForm = document.getElementById('commentform');
	if (!reviewForm) {
		return;
	}

	// Star Rating Interaction
	initStarRating();

	// Form Validation
	initFormValidation();
}

/**
 * Initialize interactive star rating buttons
 */
function initStarRating() {
	const starButtons = document.querySelectorAll('.ats-star-rating-btn');
	const ratingInput = document.getElementById('rating');
	const ratingError = document.getElementById('rating-error');

	if (!starButtons.length || !ratingInput) {
		return;
	}

	starButtons.forEach((button) => {
		button.addEventListener('click', function (e) {
			e.preventDefault();
			const rating = parseInt(this.getAttribute('data-rating'));

			// Update hidden input
			ratingInput.value = rating;

			// Hide error message
			if (ratingError) {
				ratingError.classList.add('hidden');
			}

			// Update star colors
			updateStarColors(rating);
		});

		// Hover effect
		button.addEventListener('mouseenter', function () {
			const rating = parseInt(this.getAttribute('data-rating'));
			updateStarColors(rating, true);
		});
	});

	// Reset to selected rating on mouse leave
	const starContainer = document.querySelector('.ats-star-rating-btn')?.parentElement;
	if (starContainer) {
		starContainer.addEventListener('mouseleave', function () {
			const currentRating = parseInt(ratingInput.value) || 0;
			updateStarColors(currentRating);
		});
	}
}

/**
 * Update star button colors based on rating
 * @param {number} rating - Rating value (1-5)
 * @param {boolean} isHover - Whether this is a hover state
 */
function updateStarColors(rating, isHover = false) {
	const starButtons = document.querySelectorAll('.ats-star-rating-btn');

	starButtons.forEach((button, index) => {
		const starRating = index + 1;

		if (starRating <= rating) {
			// Gold/yellow for selected stars
			button.classList.remove('text-gray-300');
			button.classList.add('text-ats-yellow');
		} else {
			// Grey for unselected stars
			button.classList.remove('text-ats-yellow');
			button.classList.add('text-gray-300');
		}
	});
}

/**
 * Initialize inline form validation
 */
function initFormValidation() {
	const reviewForm = document.getElementById('commentform');
	const ratingInput = document.getElementById('rating');
	const ratingError = document.getElementById('rating-error');
	const commentField = document.getElementById('comment');
	const commentError = document.getElementById('comment-error');

	if (!reviewForm) {
		return;
	}

	reviewForm.addEventListener('submit', function (e) {
		let hasErrors = false;

		// Validate rating
		if (ratingInput && (!ratingInput.value || ratingInput.value === '')) {
			e.preventDefault();
			hasErrors = true;

			if (ratingError) {
				ratingError.classList.remove('hidden');
			}

			// Scroll to rating field
			const ratingField = document.querySelector('.rfs-ref-review-rating-field');
			if (ratingField) {
				ratingField.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		} else if (ratingError) {
			ratingError.classList.add('hidden');
		}

		// Validate comment
		if (commentField && commentField.value.trim() === '') {
			e.preventDefault();
			hasErrors = true;

			if (commentError) {
				commentError.classList.remove('hidden');
			}

			// If rating validation passed, scroll to comment field
			if (!ratingError || ratingError.classList.contains('hidden')) {
				commentField.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		} else if (commentError) {
			commentError.classList.add('hidden');
		}

		// Don't submit if there are errors
		if (hasErrors) {
			return false;
		}
	});

	// Hide errors on input
	if (commentField && commentError) {
		commentField.addEventListener('input', function () {
			if (this.value.trim() !== '') {
				commentError.classList.add('hidden');
			}
		});
	}
}
