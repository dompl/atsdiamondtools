/**
 * WooCommerce My Account - Interactive Features
 * Handles password visibility toggle, form validation, and other account page interactions
 */

'use strict';

/**
 * Password Visibility Toggle
 * Toggles password field visibility with eye icon
 */
function initPasswordToggle() {
        const toggleButtons = document.querySelectorAll('.rfs-ref-password-toggle');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const passwordField = this.closest('.relative').querySelector('input[type="password"], input[type="text"]');
                const eyeIcon = this.querySelector('.rfs-ref-eye-icon');
                const eyeOffIcon = this.querySelector('.rfs-ref-eye-off-icon');

                if (passwordField) {
                    // Toggle input type
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        eyeIcon.classList.add('hidden');
                        eyeOffIcon.classList.remove('hidden');
                    } else {
                        passwordField.type = 'password';
                        eyeIcon.classList.remove('hidden');
                        eyeOffIcon.classList.add('hidden');
                    }
                }
            });
        });
    }

    /**
     * Form Validation
     * Add basic client-side validation for specific forms only
     * Skip WooCommerce forms that have their own validation
     */
    function initFormValidation() {
        // Only apply to custom forms, not WooCommerce native forms
        const forms = document.querySelectorAll('form:not(.woocommerce-form):not(.rfs-ref-edit-address-form)');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('border-red-500');

                        // Remove error class on input
                        field.addEventListener('input', function() {
                            this.classList.remove('border-red-500');
                        }, { once: true });
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = form.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
        });
    }

    /**
     * Social Login Button Handlers
     * Placeholder handlers for social login buttons
     */
    function initSocialLogins() {
        const googleBtn = document.querySelector('.rfs-ref-google-login');
        const facebookBtn = document.querySelector('.rfs-ref-facebook-login');
        const appleBtn = document.querySelector('.rfs-ref-apple-login');

        if (googleBtn) {
            googleBtn.addEventListener('click', function() {
                console.log('Google login clicked - integrate with WordPress social login plugin');
                // This will be handled by the social login plugin
            });
        }

        if (facebookBtn) {
            facebookBtn.addEventListener('click', function() {
                console.log('Facebook login clicked - integrate with WordPress social login plugin');
                // This will be handled by the social login plugin
            });
        }

        if (appleBtn) {
            appleBtn.addEventListener('click', function() {
                console.log('Apple login clicked - integrate with WordPress social login plugin');
                // This will be handled by the social login plugin
            });
        }
    }

    /**
     * Address Selection
     * Highlight selected address for editing
     */
    function initAddressSelection() {
        const addressCards = document.querySelectorAll('.rfs-ref-address-card');

        addressCards.forEach(card => {
            const editButton = card.querySelector('.rfs-ref-edit-address-btn');
            if (editButton) {
                editButton.addEventListener('click', function() {
                    // Add visual feedback
                    addressCards.forEach(c => c.classList.remove('ring-2', 'ring-[#FFD200]'));
                    card.classList.add('ring-2', 'ring-[#FFD200]');
                });
            }
        });
    }

    /**
     * Order Filters
     * Handle order status and date filtering
     */
    function initOrderFilters() {
        const statusFilter = document.querySelector('.rfs-ref-status-filter');
        const startDateFilter = document.querySelector('.rfs-ref-start-date');
        const endDateFilter = document.querySelector('.rfs-ref-end-date');
        const clearFiltersBtn = document.querySelector('.rfs-ref-clear-filters');
        const orderRows = document.querySelectorAll('.rfs-ref-order-row');

        // Debug logging
        console.log('Order filters initialized');
        console.log('Found order rows:', orderRows.length);
        console.log('Status filter:', statusFilter);
        console.log('Start date filter:', startDateFilter);
        console.log('End date filter:', endDateFilter);
        console.log('Clear button:', clearFiltersBtn);

        if (!orderRows.length) {
            console.log('No order rows found');
            return;
        }

        function parseOrderDate(dateString) {
            // Parse date format like "02 April, 2019" or "15 March, 2019"
            if (!dateString) return null;

            try {
                // Remove extra spaces and convert to standard format
                const cleanDate = dateString.trim();
                const parsedDate = new Date(cleanDate);

                // If that doesn't work, try manual parsing
                if (isNaN(parsedDate.getTime())) {
                    // Try to parse "DD Month, YYYY" format
                    const parts = cleanDate.match(/(\d+)\s+(\w+),?\s+(\d+)/);
                    if (parts) {
                        const day = parseInt(parts[1]);
                        const month = parts[2];
                        const year = parseInt(parts[3]);
                        const monthMap = {
                            'January': 0, 'February': 1, 'March': 2, 'April': 3,
                            'May': 4, 'June': 5, 'July': 6, 'August': 7,
                            'September': 8, 'October': 9, 'November': 10, 'December': 11
                        };
                        const monthNum = monthMap[month];
                        if (monthNum !== undefined) {
                            return new Date(year, monthNum, day);
                        }
                    }
                }

                return isNaN(parsedDate.getTime()) ? null : parsedDate;
            } catch (e) {
                console.error('Date parsing error:', e);
                return null;
            }
        }

        function applyFilters() {
            const statusValue = statusFilter ? statusFilter.value : 'all';
            const startDate = startDateFilter && startDateFilter.value ? new Date(startDateFilter.value) : null;
            const endDate = endDateFilter && endDateFilter.value ? new Date(endDateFilter.value) : null;

            console.log('Applying filters:', { statusValue, startDate, endDate });

            let visibleCount = 0;

            orderRows.forEach(row => {
                const rowStatus = row.dataset.status;
                const dateCell = row.querySelector('td:nth-child(2)');
                const rowDateText = dateCell ? dateCell.textContent.trim() : '';
                let show = true;

                // Status filter
                if (statusValue !== 'all' && rowStatus !== statusValue) {
                    show = false;
                }

                // Date filters
                if (show && rowDateText && (startDate || endDate)) {
                    const orderDate = parseOrderDate(rowDateText);
                    if (orderDate) {
                        // Reset time to start of day for comparison
                        orderDate.setHours(0, 0, 0, 0);
                        if (startDate) {
                            const compareStart = new Date(startDate);
                            compareStart.setHours(0, 0, 0, 0);
                            if (orderDate < compareStart) show = false;
                        }
                        if (endDate) {
                            const compareEnd = new Date(endDate);
                            compareEnd.setHours(23, 59, 59, 999);
                            if (orderDate > compareEnd) show = false;
                        }
                    }
                }

                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            console.log('Visible orders:', visibleCount);

            // Show "no orders" message if all filtered out
            const noOrdersMsg = document.querySelector('.rfs-ref-no-orders');
            if (noOrdersMsg) {
                noOrdersMsg.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        // Add event listeners
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                console.log('Status changed to:', this.value);
                applyFilters();
            });
        }
        if (startDateFilter) {
            startDateFilter.addEventListener('change', function() {
                console.log('Start date changed to:', this.value);
                applyFilters();
            });
        }
        if (endDateFilter) {
            endDateFilter.addEventListener('change', function() {
                console.log('End date changed to:', this.value);
                applyFilters();
            });
        }
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Clearing filters');
                if (statusFilter) statusFilter.value = 'all';
                if (startDateFilter) startDateFilter.value = '';
                if (endDateFilter) endDateFilter.value = '';
                applyFilters();
            });
        }
    }

    /**
     * Profile Picture Upload
     * Handle avatar image preview and upload
     */
    function initProfilePictureUpload() {
        const avatarUpload = document.getElementById('avatar-upload');
        const avatarDisplay = document.querySelector('.rfs-ref-edit-account-wrapper .w-24.h-24 img');

        if (avatarUpload && avatarDisplay) {
            avatarUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    // Check file size (max 2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('File size must be less than 2MB');
                        return;
                    }

                    // Preview image
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        avatarDisplay.src = event.target.result;
                    };
                    reader.readAsDataURL(file);

                    // Upload via AJAX
                    const formData = new FormData();
                    formData.append('avatar_file', file);
                    formData.append('action', 'upload_user_avatar');
                    formData.append('nonce', wc_account_params && wc_account_params.nonce ? wc_account_params.nonce : '');

                    fetch(wc_account_params && wc_account_params.ajax_url ? wc_account_params.ajax_url : '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Avatar uploaded successfully');
                        } else {
                            alert('Upload failed: ' + (data.data || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Upload error:', error);
                    });
                }
            });
        }
    }

    /**
     * Smooth Transitions
     * Add smooth transitions to interactive elements
     */
    function initSmoothTransitions() {
        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    }

    /**
     * Prevent SelectWoo Enhancement on Address Fields
     * Keep country/state as simple dropdowns
     */
    function preventSelectWooEnhancement() {
        // Wait for WooCommerce to load
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent SelectWoo initialization on country and state fields
            const addressSelects = document.querySelectorAll('.country_to_state, .state_select');

            addressSelects.forEach(function(select) {
                // Remove selectWoo/select2 classes if they were added
                select.classList.remove('selectWoo', 'select2-hidden-accessible');

                // Remove any selectWoo/select2 wrapper
                const wrapper = select.closest('.selectWoo-container, .select2-container');
                if (wrapper && wrapper.parentNode) {
                    wrapper.parentNode.insertBefore(select, wrapper);
                    wrapper.remove();
                }
            });
        });
    }

/**
 * Initialize all WooCommerce account functions
 */
export function initWooCommerceAccount() {
    // Only run on WooCommerce account pages
    if (!document.body.classList.contains('woocommerce-account')) {
        return;
    }

    initPasswordToggle();
    initFormValidation();
    initSocialLogins();
    initAddressSelection();
    initOrderFilters();
    initProfilePictureUpload();
    initSmoothTransitions();
    preventSelectWooEnhancement();
}
