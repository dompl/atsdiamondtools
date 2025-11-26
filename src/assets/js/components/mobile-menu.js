/**
 * Mobile Menu JavaScript
 *
 * Handles mobile menu modal functionality for the header.
 * Opens as a full-screen overlay with smooth slide-in panel from left
 * with staggered menu item animations for a premium feel.
 *
 * @package SkylineWP Dev Child
 */

(function($) {
    'use strict';

    // Mobile Menu Controller
    const MobileMenu = {
        // DOM Elements
        elements: {
            toggle: null,
            menu: null,
            backdrop: null,
            panel: null,
            closeBtn: null,
            navItems: null,
            contactSection: null,
            loginSection: null,
        },

        // State
        isOpen: false,
        isAnimating: false,

        // Animation settings
        settings: {
            panelDuration: 400,
            backdropDuration: 300,
            staggerDelay: 50,
            itemDuration: 300,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)', // Tailwind's ease-out
        },

        /**
         * Initialize the mobile menu
         */
        init: function() {
            this.cacheElements();

            if (!this.elements.toggle || !this.elements.menu) {
                return; // Mobile menu not on this page
            }

            this.setupInitialStyles();
            this.bindEvents();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements.toggle = document.getElementById('ats-mobile-menu-toggle');
            this.elements.menu = document.getElementById('ats-mobile-menu');
            this.elements.backdrop = document.querySelector('.js-mobile-menu-backdrop');
            this.elements.panel = document.querySelector('.js-mobile-menu-panel');
            this.elements.closeBtn = document.querySelector('.js-mobile-menu-close');

            // Cache animatable content items
            if (this.elements.panel) {
                this.elements.navItems = this.elements.panel.querySelectorAll('.rfs-ref-mobile-nav li, .rfs-ref-mobile-nav > div');
                this.elements.contactSection = this.elements.panel.querySelector('.rfs-ref-mobile-contacts');
                this.elements.loginSection = this.elements.panel.querySelector('.rfs-ref-mobile-login');
            }
        },

        /**
         * Setup initial styles for animations
         */
        setupInitialStyles: function() {
            // Set initial panel position
            if (this.elements.panel) {
                this.elements.panel.style.transform = 'translateX(-100%)';
                this.elements.panel.style.willChange = 'transform';
            }

            // Set initial backdrop opacity
            if (this.elements.backdrop) {
                this.elements.backdrop.style.opacity = '0';
                this.elements.backdrop.style.willChange = 'opacity';
            }
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            const self = this;

            // Toggle button click
            this.elements.toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!self.isAnimating) {
                    if (self.isOpen) {
                        self.closeMenu();
                    } else {
                        self.openMenu();
                    }
                }
            });

            // Close button click
            if (this.elements.closeBtn) {
                this.elements.closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!self.isAnimating) {
                        self.closeMenu();
                    }
                });
            }

            // Close on backdrop click
            if (this.elements.backdrop) {
                this.elements.backdrop.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!self.isAnimating) {
                        self.closeMenu();
                    }
                });
            }

            // Close menu on ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && self.isOpen && !self.isAnimating) {
                    self.closeMenu();
                }
            });

            // Close menu on window resize to desktop
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    if (window.innerWidth >= 1024 && self.isOpen) {
                        self.closeMenu(true); // Skip animation on resize
                    }
                }, 100);
            });
        },

        /**
         * Open menu with fancy animations
         */
        openMenu: function() {
            const self = this;
            this.isAnimating = true;

            // Show the menu container
            this.elements.menu.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            this.elements.toggle.setAttribute('aria-expanded', 'true');

            // Animate backdrop fade in
            if (this.elements.backdrop) {
                this.elements.backdrop.style.transition = `opacity ${this.settings.backdropDuration}ms ${this.settings.easing}`;
                requestAnimationFrame(function() {
                    self.elements.backdrop.style.opacity = '1';
                });
            }

            // Animate panel slide in with spring-like effect
            if (this.elements.panel) {
                this.elements.panel.style.transition = `transform ${this.settings.panelDuration}ms ${this.settings.easing}`;
                requestAnimationFrame(function() {
                    self.elements.panel.style.transform = 'translateX(0)';
                });
            }

            // Prepare content items for staggered animation
            this.prepareContentForAnimation();

            // Stagger animate content items after panel slides in
            setTimeout(function() {
                self.animateContentIn();
            }, this.settings.panelDuration * 0.5);

            // Update toggle icon with rotation animation
            this.animateToggleIcon(true);

            // Mark animation complete
            setTimeout(function() {
                self.isOpen = true;
                self.isAnimating = false;

                // Focus first interactive element for accessibility
                if (self.elements.closeBtn) {
                    self.elements.closeBtn.focus();
                }
            }, this.settings.panelDuration + 100);
        },

        /**
         * Prepare content items for staggered animation
         */
        prepareContentForAnimation: function() {
            const items = this.getAnimatableItems();
            items.forEach(function(item) {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
            });
        },

        /**
         * Get all items that should be animated
         */
        getAnimatableItems: function() {
            const items = [];

            // Get nav items
            if (this.elements.navItems) {
                this.elements.navItems.forEach(function(item) {
                    items.push(item);
                });
            }

            // Get sections
            if (this.elements.contactSection) {
                items.push(this.elements.contactSection);
            }
            if (this.elements.loginSection) {
                items.push(this.elements.loginSection);
            }

            return items;
        },

        /**
         * Animate content items in with stagger effect
         */
        animateContentIn: function() {
            const self = this;
            const items = this.getAnimatableItems();

            items.forEach(function(item, index) {
                setTimeout(function() {
                    item.style.transition = `opacity ${self.settings.itemDuration}ms ${self.settings.easing}, transform ${self.settings.itemDuration}ms ${self.settings.easing}`;
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * self.settings.staggerDelay);
            });
        },

        /**
         * Close menu with fancy animations
         * @param {boolean} skipAnimation - Skip animation (for resize)
         */
        closeMenu: function(skipAnimation) {
            const self = this;

            if (skipAnimation) {
                this.closeMenuImmediate();
                return;
            }

            this.isAnimating = true;

            // Animate content items out (reverse stagger)
            const items = this.getAnimatableItems();
            const reversedItems = items.reverse();

            reversedItems.forEach(function(item, index) {
                setTimeout(function() {
                    item.style.transition = `opacity ${self.settings.itemDuration * 0.5}ms ${self.settings.easing}, transform ${self.settings.itemDuration * 0.5}ms ${self.settings.easing}`;
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                }, index * (self.settings.staggerDelay * 0.5));
            });

            // Animate panel out after content
            setTimeout(function() {
                if (self.elements.panel) {
                    self.elements.panel.style.transition = `transform ${self.settings.panelDuration}ms ${self.settings.easing}`;
                    self.elements.panel.style.transform = 'translateX(-100%)';
                }

                // Fade out backdrop
                if (self.elements.backdrop) {
                    self.elements.backdrop.style.transition = `opacity ${self.settings.backdropDuration}ms ${self.settings.easing}`;
                    self.elements.backdrop.style.opacity = '0';
                }
            }, items.length * (self.settings.staggerDelay * 0.5));

            // Update toggle icon
            this.animateToggleIcon(false);

            // Wait for all animations to complete before hiding
            const totalDuration = (items.length * self.settings.staggerDelay * 0.5) + self.settings.panelDuration + 50;

            setTimeout(function() {
                self.closeMenuImmediate();
                self.isAnimating = false;
            }, totalDuration);

            this.elements.toggle.setAttribute('aria-expanded', 'false');
            this.isOpen = false;
        },

        /**
         * Close menu immediately without animation
         */
        closeMenuImmediate: function() {
            this.elements.menu.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');

            // Reset all styles
            if (this.elements.panel) {
                this.elements.panel.style.transition = '';
                this.elements.panel.style.transform = 'translateX(-100%)';
            }

            if (this.elements.backdrop) {
                this.elements.backdrop.style.transition = '';
                this.elements.backdrop.style.opacity = '0';
            }

            // Reset content items
            const items = this.getAnimatableItems();
            items.forEach(function(item) {
                item.style.transition = '';
                item.style.opacity = '';
                item.style.transform = '';
            });

            this.elements.toggle.setAttribute('aria-expanded', 'false');
            this.isOpen = false;
            this.isAnimating = false;

            // Reset toggle icon
            this.setHamburgerIcon();
        },

        /**
         * Animate toggle icon between hamburger and X
         * @param {boolean} toClose - True for X icon, false for hamburger
         */
        animateToggleIcon: function(toClose) {
            const toggle = this.elements.toggle;

            // Add rotation animation
            toggle.style.transition = 'transform 0.3s ease';
            toggle.style.transform = 'rotate(90deg)';

            setTimeout(() => {
                if (toClose) {
                    this.setCloseIcon();
                } else {
                    this.setHamburgerIcon();
                }
                toggle.style.transform = 'rotate(0deg)';
            }, 150);

            setTimeout(() => {
                toggle.style.transition = '';
            }, 300);
        },

        /**
         * Set hamburger icon
         */
        setHamburgerIcon: function() {
            this.elements.toggle.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z"/>
                </svg>
            `;
        },

        /**
         * Set close (X) icon
         */
        setCloseIcon: function() {
            this.elements.toggle.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/>
                </svg>
            `;
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        MobileMenu.init();
    });

    // Export for external use
    window.ATSMobileMenu = MobileMenu;

})(jQuery);
