/**
 * Product Review Form
 * Handles interactive star rating, AJAX form submission, and pagination
 */

import $ from 'jquery';

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

	// AJAX Form Submission
	initAjaxFormSubmission();

	// AJAX Pagination
	initAjaxPagination();
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
			button.classList.remove('text-gray-300');
			button.classList.add('text-ats-yellow');
		} else {
			button.classList.remove('text-ats-yellow');
			button.classList.add('text-gray-300');
		}
	});
}

/**
 * Initialize AJAX form submission with validation
 */
function initAjaxFormSubmission() {
	const reviewForm = document.getElementById('commentform');
	if (!reviewForm) {
		return;
	}

	reviewForm.addEventListener('submit', function (e) {
		e.preventDefault();

		// Validate form
		if (!validateForm()) {
			return false;
		}

		// Get form data
		const formData = new FormData(reviewForm);
		const productId = document.querySelector('[data-product-id]')?.getAttribute('data-product-id') ||
						  document.querySelector('input[name="comment_post_ID"]')?.value;

		// Prepare AJAX data
		const data = {
			action: 'ats_submit_review',
			nonce: window.themeData?.reviews_nonce || '',
			product_id: productId,
			rating: formData.get('rating'),
			comment: formData.get('comment'),
			author: formData.get('author') || '',
			email: formData.get('email') || '',
		};

		// Get submit button
		const submitBtn = reviewForm.querySelector('button[type="submit"]');
		const originalText = submitBtn ? submitBtn.textContent : '';

		// Disable button and show loading state
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = 'Submitting...';
		}

		// Submit via AJAX
		$.ajax({
			url: window.themeData?.ajax_url || '/wp-admin/admin-ajax.php',
			type: 'POST',
			data: data,
			success: function (response) {
				if (response.success) {
					// Show success message
					showMessage(response.data.message, 'success');

					// Reset form
					reviewForm.reset();
					updateStarColors(0);

					// Reload reviews if approved
					if (response.data.approved) {
						setTimeout(() => {
							location.reload();
						}, 2000);
					}
				} else {
					// Show error message
					const errorData = response.data || {};
					if (errorData.field) {
						showFieldError(errorData.field, errorData.message);
					} else {
						showMessage(errorData.message || 'An error occurred.', 'error');
					}
				}
			},
			error: function () {
				showMessage('Failed to submit review. Please try again.', 'error');
			},
			complete: function () {
				// Re-enable button
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
				}
			},
		});

		return false;
	});
}

/**
 * Validate form fields
 * @returns {boolean} True if valid
 */
function validateForm() {
	let isValid = true;

	// Clear all errors first
	clearAllErrors();

	// Validate rating
	const ratingInput = document.getElementById('rating');
	if (ratingInput && !ratingInput.value) {
		showFieldError('rating', 'Please select a rating');
		isValid = false;
	}

	// Validate comment
	const commentField = document.getElementById('comment');
	if (commentField && !commentField.value.trim()) {
		showFieldError('comment', 'Please enter your review');
		isValid = false;
	}

	// Validate name (if field exists and is visible)
	const authorField = document.getElementById('author');
	if (authorField && !authorField.value.trim()) {
		showFieldError('author', 'Please enter your name');
		isValid = false;
	}

	// Validate email (if field exists and is visible)
	const emailField = document.getElementById('email');
	if (emailField) {
		const emailValue = emailField.value.trim();
		if (!emailValue) {
			showFieldError('email', 'Please enter your email');
			isValid = false;
		} else if (!isValidEmail(emailValue)) {
			showFieldError('email', 'Please enter a valid email address');
			isValid = false;
		}
	}

	return isValid;
}

/**
 * Show field-specific error
 * @param {string} fieldName - Field name
 * @param {string} message - Error message
 */
function showFieldError(fieldName, message) {
	const errorElement = document.getElementById(`${fieldName}-error`);
	if (errorElement) {
		errorElement.textContent = message;
		errorElement.classList.remove('hidden');

		// Scroll to first error
		const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
		if (field) {
			field.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	}
}

/**
 * Clear all error messages
 */
function clearAllErrors() {
	const errorElements = document.querySelectorAll('[id$="-error"]');
	errorElements.forEach((el) => {
		el.classList.add('hidden');
	});
}

/**
 * Validate email format
 * @param {string} email - Email address
 * @returns {boolean} True if valid
 */
function isValidEmail(email) {
	const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return re.test(email);
}

/**
 * Show general message
 * @param {string} message - Message text
 * @param {string} type - Message type (success/error)
 */
function showMessage(message, type = 'success') {
	const formWrapper = document.getElementById('review_form');
	if (!formWrapper) {
		return;
	}

	// Remove existing messages
	const existingMessage = formWrapper.querySelector('.ats-review-message');
	if (existingMessage) {
		existingMessage.remove();
	}

	// Create message element
	const messageDiv = document.createElement('div');
	messageDiv.className = `ats-review-message p-4 rounded-lg mb-4 ${
		type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'
	}`;
	messageDiv.textContent = message;

	// Insert at top of form
	formWrapper.insertBefore(messageDiv, formWrapper.firstChild);

	// Scroll to message
	messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

	// Auto-remove after 5 seconds
	setTimeout(() => {
		messageDiv.remove();
	}, 5000);
}

/**
 * Initialize AJAX pagination for reviews
 */
function initAjaxPagination() {
	const pagination = document.getElementById('reviews-pagination');
	if (!pagination) {
		return;
	}

	pagination.addEventListener('click', function (e) {
		const button = e.target.closest('.ats-reviews-page, .ats-reviews-prev, .ats-reviews-next');
		if (!button) {
			return;
		}

		e.preventDefault();

		const page = parseInt(button.getAttribute('data-page'));
		const productId = pagination.getAttribute('data-product-id');

		if (!page || !productId) {
			return;
		}

		// Load reviews for this page
		loadReviews(productId, page);
	});
}

/**
 * Load reviews via AJAX
 * @param {number} productId - Product ID
 * @param {number} page - Page number
 */
function loadReviews(productId, page) {
	const reviewsList = document.getElementById('reviews-list');
	const pagination = document.getElementById('reviews-pagination');

	if (!reviewsList) {
		return;
	}

	// Show loading state
	reviewsList.innerHTML = '<li class="text-center py-8"><svg class="animate-spin h-8 w-8 text-accent-yellow mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></li>';

	$.ajax({
		url: window.themeData?.ajax_url || '/wp-admin/admin-ajax.php',
		type: 'POST',
		data: {
			action: 'ats_load_reviews',
			nonce: window.themeData?.reviews_nonce || '',
			product_id: productId,
			page: page,
		},
		success: function (response) {
			if (response.success) {
				reviewsList.innerHTML = response.data.html;

				// Update pagination active state
				updatePaginationState(page);

				// Scroll to reviews
				reviewsList.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		},
		error: function () {
			reviewsList.innerHTML = '<li class="text-center py-8 text-red-600">Failed to load reviews.</li>';
		},
	});
}

/**
 * Update pagination button states
 * @param {number} currentPage - Current page number
 */
function updatePaginationState(currentPage) {
	const pageButtons = document.querySelectorAll('.ats-reviews-page');
	const pagination = document.getElementById('reviews-pagination');

	if (!pagination) {
		return;
	}

	const totalPages = parseInt(pagination.getAttribute('data-total-pages')) || 1;

	// Update page number buttons
	pageButtons.forEach((btn) => {
		const btnPage = parseInt(btn.getAttribute('data-page'));
		if (btnPage === currentPage) {
			btn.classList.add('current', 'bg-ats-yellow', 'text-ats-dark', 'border-ats-yellow', 'font-semibold');
			btn.classList.remove('hover:bg-gray-50');
		} else {
			btn.classList.remove('current', 'bg-ats-yellow', 'text-ats-dark', 'border-ats-yellow', 'font-semibold');
			btn.classList.add('hover:bg-gray-50');
		}
	});

	// Update prev/next buttons
	const prevBtn = pagination.querySelector('.ats-reviews-prev');
	const nextBtn = pagination.querySelector('.ats-reviews-next');

	if (prevBtn) {
		prevBtn.setAttribute('data-page', currentPage - 1);
		if (currentPage <= 1) {
			prevBtn.style.display = 'none';
		} else {
			prevBtn.style.display = 'inline-flex';
		}
	}

	if (nextBtn) {
		nextBtn.setAttribute('data-page', currentPage + 1);
		if (currentPage >= totalPages) {
			nextBtn.style.display = 'none';
		} else {
			nextBtn.style.display = 'inline-flex';
		}
	}
}
