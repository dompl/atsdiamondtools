/**
 * Newsletter Subscription Component
 *
 * Handles newsletter subscription form submission via AJAX
 * Integrates with Brevo API through WordPress AJAX handlers
 *
 * @package ATS Diamond Tools
 */

document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.querySelector('.rfs-ref-newsletter-form');

    if (!newsletterForm) {
        return;
    }

    const emailInput = newsletterForm.querySelector('.rfs-ref-newsletter-email');
    const submitButton = newsletterForm.querySelector('.rfs-ref-newsletter-submit');
    const messageContainer = newsletterForm.querySelector('.rfs-ref-newsletter-message');

    if (!emailInput || !submitButton) {
        return;
    }

    /**
     * Show message to user
     *
     * @param {string} message - Message text to display
     * @param {string} type - Message type: 'success' or 'error'
     */
    function showMessage(message, type = 'success') {
        if (!messageContainer) {
            return;
        }

        messageContainer.innerHTML = message;
        messageContainer.className = 'rfs-ref-newsletter-message mt-4 p-4 rounded';

        if (type === 'success') {
            messageContainer.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-300');
        } else {
            messageContainer.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-300');
        }

        messageContainer.style.display = 'block';

        // Hide message after 8 seconds
        setTimeout(() => {
            messageContainer.style.display = 'none';
        }, 8000);
    }

    /**
     * Set loading state on submit button
     *
     * @param {boolean} isLoading - Loading state
     */
    function setLoading(isLoading) {
        if (isLoading) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            submitButton.dataset.originalText = submitButton.textContent;
            submitButton.textContent = 'Subscribing...';
        } else {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            if (submitButton.dataset.originalText) {
                submitButton.textContent = submitButton.dataset.originalText;
            }
        }
    }

    /**
     * Validate email format
     *
     * @param {string} email - Email address to validate
     * @returns {boolean} - Valid email or not
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Handle form submission
     */
    newsletterForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = emailInput.value.trim();

        // Validate email
        if (!email) {
            showMessage('Please enter your email address.', 'error');
            emailInput.focus();
            return;
        }

        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address.', 'error');
            emailInput.focus();
            return;
        }

        // Get nonce from form data attribute
        const nonce = newsletterForm.dataset.nonce;

        if (!nonce) {
            showMessage('Security check failed. Please refresh the page.', 'error');
            return;
        }

        setLoading(true);

        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'ats_newsletter_subscribe');
        formData.append('nonce', nonce);
        formData.append('email', email);

        try {
            const response = await fetch(themeData.ajax_url, {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                showMessage(data.data.message, 'success');
                emailInput.value = ''; // Clear the input on success
            } else {
                showMessage(data.data.message || 'An error occurred. Please try again.', 'error');
            }
        } catch (error) {
            showMessage('Connection error. Please check your internet and try again.', 'error');
        } finally {
            setLoading(false);
        }
    });

    // Clear message when user starts typing
    emailInput.addEventListener('input', function() {
        if (messageContainer && messageContainer.style.display !== 'none') {
            messageContainer.style.display = 'none';
        }
    });
});
