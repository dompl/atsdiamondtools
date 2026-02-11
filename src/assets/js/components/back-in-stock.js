/**
 * Back in Stock Notifications
 *
 * Handles subscription form submission for out-of-stock products and variations.
 * For variable products, listens to WooCommerce variation events to show/hide
 * the form based on the selected variation's stock status.
 *
 * @package SkylineWP Dev Child
 */

import $ from 'jquery';

export function initBackInStock() {
	// Only run on product pages
	if (!document.body.classList.contains('single-product')) {
		return;
	}

	const wrapper = document.querySelector('.ats-stock-notification-form-wrapper');
	const form = document.querySelector('.ats-stock-notification-form');
	const stockBadge = document.querySelector('.rfs-ref-out-of-stock-badge');

	if (!form) {
		return;
	}

	// Handle form submission
	form.addEventListener('submit', handleFormSubmit);

	// Handle variation changes for variable products
	const variationsForm = document.querySelector('.variations_form');
	if (variationsForm) {
		const $variationsForm = $(variationsForm);

		// When a variation is found (user selected all options)
		$variationsForm.on('found_variation', function (event, variation) {
			const variationIdInput = form.querySelector('input[name="variation_id"]');

			if (!variation.is_in_stock) {
				// Out of stock variation - show the form and badge
				if (variationIdInput) {
					variationIdInput.value = variation.variation_id;
				}
				resetFormState(form);
				wrapper.classList.remove('hidden');
				if (stockBadge) {
					stockBadge.classList.remove('hidden');
				}
			} else {
				// In stock variation - hide the form and badge
				wrapper.classList.add('hidden');
				if (variationIdInput) {
					variationIdInput.value = 0;
				}
				if (stockBadge) {
					stockBadge.classList.add('hidden');
				}
			}
		});

		// When variation selection is reset (user clears a dropdown)
		$variationsForm.on('reset_data', function () {
			wrapper.classList.add('hidden');
			const variationIdInput = form.querySelector('input[name="variation_id"]');
			if (variationIdInput) {
				variationIdInput.value = 0;
			}
			resetFormState(form);
			if (stockBadge) {
				stockBadge.classList.add('hidden');
			}
		});
	}
}

/**
 * Reset the form to its initial state (clear messages, re-enable button).
 */
function resetFormState(form) {
	const submitBtn = form.querySelector('.rfs-ref-stock-notification-submit');
	const messageDiv = form.querySelector('.rfs-ref-stock-notification-message');
	const agreeCheckbox = form.querySelector('input[name="agree"]');

	if (submitBtn) {
		submitBtn.disabled = false;
		submitBtn.textContent = 'Notify Me';
	}
	if (messageDiv) {
		messageDiv.classList.add('hidden');
		messageDiv.textContent = '';
	}
	if (agreeCheckbox) {
		agreeCheckbox.checked = false;
	}

	// Show the form element itself (in case it was hidden after a previous subscription)
	form.style.display = '';
}

/**
 * Handle form submission via AJAX.
 */
function handleFormSubmit(e) {
	e.preventDefault();

	const form = e.currentTarget;
	const submitBtn = form.querySelector('.rfs-ref-stock-notification-submit');
	const messageDiv = form.querySelector('.rfs-ref-stock-notification-message');
	const productId = form.querySelector('input[name="product_id"]').value;
	const variationId = form.querySelector('input[name="variation_id"]')?.value || 0;
	const emailInput = form.querySelector('input[name="email"]');
	const agreeCheckbox = form.querySelector('input[name="agree"]');

	// Validate
	if (emailInput && !emailInput.value) {
		showMessage(messageDiv, 'Please enter your email address', 'error');
		return;
	}

	if (agreeCheckbox && !agreeCheckbox.checked) {
		showMessage(messageDiv, 'Please check the box to subscribe', 'error');
		return;
	}

	// Disable submit button
	submitBtn.disabled = true;
	submitBtn.textContent = 'Subscribing...';

	// AJAX request
	$.ajax({
		url: window.themeData?.ajax_url || '/wp-admin/admin-ajax.php',
		type: 'POST',
		data: {
			action: 'ats_subscribe_back_in_stock',
			nonce: window.themeData?.back_in_stock_nonce || '',
			product_id: productId,
			variation_id: variationId,
			email: emailInput ? emailInput.value : '',
		},
		success(response) {
			if (response.success) {
				showMessage(messageDiv, response.data.message, 'success');
				setTimeout(() => {
					form.style.display = 'none';
				}, 3000);
			} else {
				showMessage(messageDiv, response.data?.message || 'Failed to subscribe', 'error');
				submitBtn.disabled = false;
				submitBtn.textContent = 'Notify Me';
			}
		},
		error() {
			showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
			submitBtn.disabled = false;
			submitBtn.textContent = 'Notify Me';
		},
	});
}

function showMessage(container, message, type) {
	container.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'bg-red-50', 'text-red-800', 'border-green-200', 'border-red-200');

	if (type === 'success') {
		container.classList.add('bg-green-50', 'text-green-800', 'border', 'border-green-200');
	} else {
		container.classList.add('bg-red-50', 'text-red-800', 'border', 'border-red-200');
	}

	container.classList.add('rounded-lg', 'p-4', 'text-sm');
	container.textContent = message;
}
