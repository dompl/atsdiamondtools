/**
 * Contact Form Handler
 *
 * Handles contact form submissions with smooth UX
 *
 * @package SkylineWP Dev Child
 */

(function () {
	'use strict';

	// Wait for DOM to be ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initContactForms);
	} else {
		initContactForms();
	}

	/**
	 * Initialize all contact forms on the page
	 */
	function initContactForms() {
		const forms = document.querySelectorAll('.rfs-ref-contact-form');

		forms.forEach((form) => {
			form.addEventListener('submit', handleFormSubmit);
		});
	}

	/**
	 * Handle form submission
	 *
	 * @param {Event} e - Submit event
	 */
	async function handleFormSubmit(e) {
		e.preventDefault();

		const form = e.target;
		const submitButton = form.querySelector('.rfs-ref-submit-button');
		const buttonText = form.querySelector('.rfs-ref-button-text');
		const buttonLoading = form.querySelector('.rfs-ref-button-loading');

		// Get form data
		const formData = new FormData(form);

		// Get data attributes
		const recipientEmail = form.dataset.recipient;
		const successMessage = form.dataset.successMessage;

		// Validate required fields
		const name = formData.get('name');
		const email = formData.get('email');
		const message = formData.get('message');
		const consent = formData.get('consent');

		if (!name || !email || !message) {
			showError(form, 'Please fill in all required fields.');
			return;
		}

		if (!consent) {
			showError(form, 'You must consent to data collection to submit this form.');
			return;
		}

		// Validate email format
		if (!isValidEmail(email)) {
			showError(form, 'Please provide a valid email address.');
			return;
		}

		// Get reCAPTCHA response if enabled
		let recaptchaResponse = '';
		const recaptchaElement = form.querySelector('.g-recaptcha');
		if (recaptchaElement && typeof grecaptcha !== 'undefined') {
			recaptchaResponse = grecaptcha.getResponse();
			if (!recaptchaResponse) {
				showError(form, 'Please complete the reCAPTCHA verification.');
				return;
			}
		}

		// Disable submit button and show loading state
		submitButton.disabled = true;
		buttonText.classList.add('hidden');
		buttonLoading.classList.remove('hidden');

		// Hide any previous messages
		hideMessages(form);

		// Prepare data for AJAX request
		const ajaxData = {
			action: 'ats_contact_form_submit',
			nonce: window.themeData?.contact_form_nonce || '',
			name: name,
			email: email,
			telephone: formData.get('telephone') || '',
			message: message,
			newsletter: formData.get('newsletter') ? 'true' : 'false',
			consent: 'true',
			recipient: recipientEmail,
			success_message: successMessage,
			recaptcha_response: recaptchaResponse,
		};

		try {
			const response = await fetch(window.themeData?.ajaxurl || '/wp-admin/admin-ajax.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams(ajaxData),
			});

			const result = await response.json();

			if (result.success) {
				showSuccess(form, result.data.message);
				form.reset();

				// Reset reCAPTCHA if present
				if (recaptchaElement && typeof grecaptcha !== 'undefined') {
					grecaptcha.reset();
				}

				// Scroll to success message
				setTimeout(() => {
					const messagesContainer = form.closest('.rfs-ref-contact-form-card').querySelector('.rfs-ref-form-messages');
					if (messagesContainer) {
						messagesContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					}
				}, 100);
			} else {
				showError(form, result.data?.message || 'An error occurred. Please try again.');
			}
		} catch (error) {
			console.error('Contact form error:', error);
			showError(form, 'An unexpected error occurred. Please try again later.');
		} finally {
			// Re-enable submit button and restore normal state
			submitButton.disabled = false;
			buttonText.classList.remove('hidden');
			buttonLoading.classList.add('hidden');
		}
	}

	/**
	 * Show success message
	 *
	 * @param {HTMLElement} form - Form element
	 * @param {string} message - Success message
	 */
	function showSuccess(form, message) {
		const messagesContainer = form.closest('.rfs-ref-contact-form-card').querySelector('.rfs-ref-form-messages');
		const successMessage = messagesContainer.querySelector('.rfs-ref-form-success');
		const errorMessage = messagesContainer.querySelector('.rfs-ref-form-error');

		// Hide error message
		errorMessage.classList.add('hidden');

		// Show success message
		successMessage.querySelector('p').textContent = message;
		successMessage.classList.remove('hidden');
		messagesContainer.classList.remove('hidden');

		// Auto-hide after 10 seconds
		setTimeout(() => {
			successMessage.classList.add('hidden');
			if (errorMessage.classList.contains('hidden')) {
				messagesContainer.classList.add('hidden');
			}
		}, 10000);
	}

	/**
	 * Show error message
	 *
	 * @param {HTMLElement} form - Form element
	 * @param {string} message - Error message
	 */
	function showError(form, message) {
		const messagesContainer = form.closest('.rfs-ref-contact-form-card').querySelector('.rfs-ref-form-messages');
		const successMessage = messagesContainer.querySelector('.rfs-ref-form-success');
		const errorMessage = messagesContainer.querySelector('.rfs-ref-form-error');

		// Hide success message
		successMessage.classList.add('hidden');

		// Show error message
		errorMessage.querySelector('p').textContent = message;
		errorMessage.classList.remove('hidden');
		messagesContainer.classList.remove('hidden');

		// Scroll to error message
		setTimeout(() => {
			messagesContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}, 100);

		// Auto-hide after 8 seconds
		setTimeout(() => {
			errorMessage.classList.add('hidden');
			if (successMessage.classList.contains('hidden')) {
				messagesContainer.classList.add('hidden');
			}
		}, 8000);
	}

	/**
	 * Hide all messages
	 *
	 * @param {HTMLElement} form - Form element
	 */
	function hideMessages(form) {
		const messagesContainer = form.closest('.rfs-ref-contact-form-card').querySelector('.rfs-ref-form-messages');
		const successMessage = messagesContainer.querySelector('.rfs-ref-form-success');
		const errorMessage = messagesContainer.querySelector('.rfs-ref-form-error');

		successMessage.classList.add('hidden');
		errorMessage.classList.add('hidden');
		messagesContainer.classList.add('hidden');
	}

	/**
	 * Validate email format
	 *
	 * @param {string} email - Email address to validate
	 * @return {boolean} True if valid
	 */
	function isValidEmail(email) {
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test(email);
	}
})();
