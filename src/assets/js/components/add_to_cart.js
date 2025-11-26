/**
 * Mini Cart JavaScript
 *
 * Handles AJAX cart updates, modal functionality, and quantity controls.
 * Designed to work with cached pages by fetching cart data on page load.
 *
 * @package SkylineWP Dev Child
 */

(function($) {
    'use strict';

    // Mini Cart Controller
    const MiniCart = {
        // DOM Elements
        elements: {
            wrapper: null,
            emptyState: null,
            filledState: null,
            loadingState: null,
            modal: null,
            modalBackdrop: null,
            modalItems: null,
            toggleBtn: null,
            closeBtn: null,
        },

        // State
        isLoading: false,
        isModalOpen: false,

        /**
         * Initialize the mini cart
         */
        init: function() {
            this.cacheElements();

            if (!this.elements.wrapper) {
                return; // Mini cart not on this page
            }

            this.bindEvents();
            this.loadCart();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements.wrapper = document.getElementById('ats-mini-cart-wrapper');
            this.elements.emptyState = document.getElementById('ats-mini-cart-empty');
            this.elements.filledState = document.getElementById('ats-mini-cart-filled');
            this.elements.loadingState = document.getElementById('ats-mini-cart-loading');
            this.elements.modal = document.getElementById('ats-mini-cart-modal');
            this.elements.modalItems = document.getElementById('ats-mini-cart-items');
            this.elements.toggleBtn = document.getElementById('ats-mini-cart-toggle');
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            const self = this;

            // Toggle button click
            if (this.elements.toggleBtn) {
                this.elements.toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.openModal();
                });
            }

            // Modal close buttons
            if (this.elements.modal) {
                // Close on backdrop click
                const backdrop = this.elements.modal.querySelector('.js-mini-cart-backdrop');
                if (backdrop) {
                    backdrop.addEventListener('click', function() {
                        self.closeModal();
                    });
                }

                // Close buttons
                const closeButtons = this.elements.modal.querySelectorAll('[data-modal-hide="ats-mini-cart-modal"]');
                closeButtons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        self.closeModal();
                    });
                });

                // Close on ESC key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && self.isModalOpen) {
                        self.closeModal();
                    }
                });
            }

            // Delegate events for cart items (quantity controls, remove)
            if (this.elements.modalItems) {
                this.elements.modalItems.addEventListener('click', function(e) {
                    const target = e.target.closest('button');
                    if (!target) return;

                    const cartKey = target.dataset.cartKey;

                    if (target.classList.contains('js-qty-decrease')) {
                        e.preventDefault();
                        self.updateQuantity(cartKey, 'decrease');
                    } else if (target.classList.contains('js-qty-increase')) {
                        e.preventDefault();
                        self.updateQuantity(cartKey, 'increase');
                    } else if (target.classList.contains('js-remove-item')) {
                        e.preventDefault();
                        self.removeItem(cartKey);
                    }
                });
            }

            // Listen for WooCommerce add to cart events
            $(document.body).on('added_to_cart removed_from_cart updated_cart_totals', function() {
                self.loadCart();
            });

            // Also listen for WooCommerce AJAX complete
            $(document).ajaxComplete(function(event, xhr, settings) {
                // Check if this was a WooCommerce cart action
                if (settings.url && settings.url.includes('wc-ajax') &&
                    (settings.url.includes('add_to_cart') ||
                     settings.url.includes('remove_from_cart') ||
                     settings.url.includes('apply_coupon') ||
                     settings.url.includes('remove_coupon'))) {
                    self.loadCart();
                }
            });
        },

        /**
         * Load cart data via AJAX
         */
        loadCart: function() {
            const self = this;

            if (this.isLoading) return;

            this.isLoading = true;
            this.showLoading();

            $.ajax({
                url: themeData.ajax_url,
                type: 'POST',
                data: {
                    action: 'ats_get_mini_cart',
                    nonce: themeData.mini_cart_nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDisplay(response.data);
                    } else {
                        console.error('Mini cart error:', response.data);
                        self.showEmpty();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Mini cart AJAX error:', error);
                    self.showEmpty();
                },
                complete: function() {
                    self.isLoading = false;
                    self.hideLoading();
                }
            });
        },

        /**
         * Update display with cart data
         * @param {Object} data - Cart data from AJAX
         * @param {boolean} skipModalItemsUpdate - If true, don't replace modal items HTML
         */
        updateDisplay: function(data, skipModalItemsUpdate) {
            if (data.is_empty) {
                this.showEmpty();
            } else {
                this.showFilled(data);
            }

            // Update modal content
            this.updateModalContent(data, skipModalItemsUpdate);
        },

        /**
         * Show empty cart state
         */
        showEmpty: function() {
            if (this.elements.emptyState) {
                this.elements.emptyState.style.display = 'block';
            }
            if (this.elements.filledState) {
                this.elements.filledState.style.display = 'none';
            }
        },

        /**
         * Show filled cart state
         */
        showFilled: function(data) {
            if (this.elements.emptyState) {
                this.elements.emptyState.style.display = 'none';
            }
            if (this.elements.filledState) {
                this.elements.filledState.style.display = 'block';
            }

            // Update count badge
            const countEl = document.getElementById('ats-mini-cart-count');
            if (countEl) {
                countEl.textContent = data.count;
            }

            // Update items text
            const itemsTextEl = document.getElementById('ats-mini-cart-items-text');
            if (itemsTextEl) {
                itemsTextEl.textContent = data.count_text;
            }

            // Update subtotal
            const subtotalEl = document.getElementById('ats-mini-cart-subtotal');
            if (subtotalEl) {
                subtotalEl.innerHTML = data.subtotal;
            }

            // Update total
            const totalEl = document.getElementById('ats-mini-cart-total');
            if (totalEl) {
                totalEl.innerHTML = data.total;
            }

            // Update tax
            const taxEl = document.getElementById('ats-mini-cart-tax');
            if (taxEl) {
                taxEl.innerHTML = '(inc ' + data.tax + ' VAT)';
            }
        },

        /**
         * Update modal content
         * @param {Object} data - Cart data from AJAX
         * @param {boolean} skipItemsUpdate - If true, don't replace items HTML (for quantity updates)
         */
        updateModalContent: function(data, skipItemsUpdate) {
            // Update items HTML only if not skipping (e.g., during quantity updates we update individual items)
            if (this.elements.modalItems && !skipItemsUpdate) {
                this.elements.modalItems.innerHTML = data.items_html;
            }

            // Update modal item count
            const modalCountEl = document.getElementById('ats-modal-item-count');
            if (modalCountEl) {
                modalCountEl.textContent = '(' + data.count_text + ')';
            }

            // Update modal subtotal
            const modalSubtotalEl = document.getElementById('ats-modal-subtotal');
            if (modalSubtotalEl) {
                modalSubtotalEl.innerHTML = data.subtotal;
            }

            // Update modal tax
            const modalTaxEl = document.getElementById('ats-modal-tax');
            if (modalTaxEl) {
                modalTaxEl.innerHTML = data.tax;
            }

            // Update modal total
            const modalTotalEl = document.getElementById('ats-modal-total');
            if (modalTotalEl) {
                modalTotalEl.innerHTML = data.total;
            }
        },

        /**
         * Show loading state
         */
        showLoading: function() {
            if (this.elements.loadingState) {
                this.elements.loadingState.style.display = 'block';
            }
            if (this.elements.emptyState) {
                this.elements.emptyState.style.display = 'none';
            }
            if (this.elements.filledState) {
                this.elements.filledState.style.display = 'none';
            }
        },

        /**
         * Hide loading state
         */
        hideLoading: function() {
            if (this.elements.loadingState) {
                this.elements.loadingState.style.display = 'none';
            }
        },

        /**
         * Open modal
         */
        openModal: function() {
            if (!this.elements.modal) return;

            this.elements.modal.classList.remove('hidden');
            this.elements.modal.classList.add('flex');
            this.elements.modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            this.isModalOpen = true;

            // Animate modal in from top
            const container = this.elements.modal.querySelector('.js-mini-cart-modal-container');
            if (container) {
                container.style.transform = 'translateY(-100%)';
                setTimeout(function() {
                    container.style.transition = 'transform 0.3s ease-out';
                    container.style.transform = 'translateY(0)';
                }, 10);
            }
        },

        /**
         * Close modal
         */
        closeModal: function() {
            const self = this;
            if (!this.elements.modal) return;

            // Animate modal out to top
            const container = this.elements.modal.querySelector('.js-mini-cart-modal-container');
            if (container) {
                container.style.transform = 'translateY(-100%)';
                setTimeout(function() {
                    self.elements.modal.classList.add('hidden');
                    self.elements.modal.classList.remove('flex');
                    self.elements.modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('overflow-hidden');
                    container.style.transition = '';
                    self.isModalOpen = false;
                }, 300);
            } else {
                this.elements.modal.classList.add('hidden');
                this.elements.modal.classList.remove('flex');
                this.elements.modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                this.isModalOpen = false;
            }
        },

        /**
         * Update item quantity
         */
        updateQuantity: function(cartKey, action) {
            const self = this;
            const itemEl = this.elements.modalItems.querySelector('[data-cart-key="' + cartKey + '"]');

            if (!itemEl) return;

            const qtyValueEl = itemEl.querySelector('.js-qty-value');
            if (!qtyValueEl) return;

            let currentQty = parseInt(qtyValueEl.textContent, 10);
            let newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

            if (newQty < 1) {
                this.removeItem(cartKey);
                return;
            }

            // Show loading on item
            itemEl.classList.add('opacity-50', 'pointer-events-none');

            $.ajax({
                url: themeData.ajax_url,
                type: 'POST',
                data: {
                    action: 'ats_update_cart_item',
                    nonce: themeData.mini_cart_nonce,
                    cart_key: cartKey,
                    quantity: newQty
                },
                success: function(response) {
                    if (response.success) {
                        // Update the individual item in the modal
                        self.updateModalItem(itemEl, cartKey, response.data);
                        // Update display but skip replacing modal items HTML
                        self.updateDisplay(response.data, true);
                    } else {
                        console.error('Update quantity error:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Update quantity AJAX error:', error);
                },
                complete: function() {
                    itemEl.classList.remove('opacity-50', 'pointer-events-none');
                }
            });
        },

        /**
         * Update individual modal item after quantity change
         */
        updateModalItem: function(itemEl, cartKey, data) {
            // Find the item in the response data
            const itemData = data.items.find(function(item) {
                return item.key === cartKey;
            });

            if (!itemData) return;

            // Update quantity value
            const qtyValueEl = itemEl.querySelector('.js-qty-value');
            if (qtyValueEl) {
                qtyValueEl.textContent = itemData.quantity;
            }

            // Update subtotal
            const subtotalEl = itemEl.querySelector('.rfs-ref-mini-cart-item-subtotal span');
            if (subtotalEl) {
                subtotalEl.innerHTML = itemData.subtotal;
            }

            // Update decrease button disabled state
            const decreaseBtn = itemEl.querySelector('.js-qty-decrease');
            if (decreaseBtn) {
                decreaseBtn.disabled = itemData.quantity <= 1;
            }

            // Update increase button disabled state
            const increaseBtn = itemEl.querySelector('.js-qty-increase');
            if (increaseBtn) {
                increaseBtn.disabled = itemData.quantity >= itemData.max_qty;
            }
        },

        /**
         * Remove item from cart
         */
        removeItem: function(cartKey) {
            const self = this;
            const itemEl = this.elements.modalItems.querySelector('[data-cart-key="' + cartKey + '"]');

            if (!itemEl) return;

            // Animate out
            itemEl.style.transition = 'opacity 0.3s, transform 0.3s';
            itemEl.style.opacity = '0';
            itemEl.style.transform = 'translateX(20px)';

            $.ajax({
                url: themeData.ajax_url,
                type: 'POST',
                data: {
                    action: 'ats_remove_cart_item',
                    nonce: themeData.mini_cart_nonce,
                    cart_key: cartKey
                },
                success: function(response) {
                    if (response.success) {
                        // For remove, we need to update the full items list
                        self.updateDisplay(response.data);

                        // Close modal if cart is empty
                        if (response.data.is_empty) {
                            setTimeout(function() {
                                self.closeModal();
                            }, 300);
                        }
                    } else {
                        console.error('Remove item error:', response.data);
                        // Restore item visibility
                        itemEl.style.opacity = '1';
                        itemEl.style.transform = 'translateX(0)';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Remove item AJAX error:', error);
                    // Restore item visibility
                    itemEl.style.opacity = '1';
                    itemEl.style.transform = 'translateX(0)';
                }
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        MiniCart.init();
    });

    // Also initialize on window load for late-loading content
    $(window).on('load', function() {
        if (!MiniCart.elements.wrapper) {
            MiniCart.init();
        }
    });

    // Export for external use
    window.ATSMiniCart = MiniCart;

})(jQuery);
