/**
 * GA4 Client-Side Event Tracking
 *
 * Handles interaction-based GA4 events that require JavaScript:
 * - add_to_cart (AJAX and form-based)
 * - remove_from_cart
 * - select_item (product clicks)
 * - add_to_wishlist (favorites)
 * - add_shipping_info
 * - add_payment_info
 * - generate_lead (contact form)
 * - newsletter_signup (custom event)
 *
 * Product data is provided via themeData.ga4 from the server.
 *
 * @package ATS Diamond Tools
 */

(function ($) {
	'use strict';

	/**
	 * Check if GA4 is available
	 */
	function isGA4Ready() {
		return typeof gtag === 'function' && typeof themeData !== 'undefined' && themeData.ga4;
	}

	/**
	 * Get currency from GA4 config
	 */
	function getCurrency() {
		return themeData.ga4.currency || 'GBP';
	}

	/**
	 * Fire a gtag event
	 */
	function trackEvent(eventName, params) {
		if (typeof gtag !== 'function') return;
		gtag('event', eventName, params);
	}

	/**
	 * Get product data by ID from the products map
	 */
	function getProductById(productId) {
		if (!themeData.ga4.products) return null;
		return themeData.ga4.products[productId] || null;
	}

	/**
	 * Track add_to_cart — AJAX add to cart from product listings
	 *
	 * Listens for WooCommerce's added_to_cart event which fires
	 * after any successful AJAX add to cart.
	 */
	function initAddToCart() {
		// WooCommerce AJAX add to cart (archive pages, cross-sells, quick view)
		$(document.body).on('added_to_cart', function (e, fragments, cart_hash, $button) {
			if (!$button || !$button.length) return;

			var productId = $button.data('product-id') || $button.data('product_id');
			if (!productId) return;

			var item = getProductById(productId);

			// If we have the single product data (single product page)
			if (!item && themeData.ga4.product) {
				item = themeData.ga4.product;
			}

			// Fallback: build minimal item from button data
			if (!item) {
				item = {
					item_id: String(productId),
					item_name: $button.closest('.product, [data-product-name]').find('.woocommerce-loop-product__title, [data-product-name]').first().text() || 'Product ' + productId,
					price: parseFloat($button.closest('.product').find('.woocommerce-Price-amount').first().text().replace(/[^0-9.]/g, '')) || 0,
					quantity: 1,
					item_brand: 'ATS Diamond Tools',
				};
			}

			// Get quantity (default 1 for archive, check input for single)
			var qty = parseInt($button.closest('form').find('input[name="quantity"]').val()) || item.quantity || 1;
			item.quantity = qty;

			trackEvent('add_to_cart', {
				currency: getCurrency(),
				value: item.price * qty,
				items: [item],
			});
		});

		// Single product page form submission (non-AJAX fallback)
		$('form.cart').on('submit', function () {
			if (!themeData.ga4.product) return;

			var item = Object.assign({}, themeData.ga4.product);
			var qty = parseInt($(this).find('input[name="quantity"]').val()) || 1;
			item.quantity = qty;

			// For variable products, get the selected variation price
			var variationPrice = $(this).find('input[name="variation_id"]').length
				? parseFloat($('.woocommerce-variation-price .woocommerce-Price-amount').first().text().replace(/[^0-9.]/g, ''))
				: 0;

			if (variationPrice > 0) {
				item.price = variationPrice;
			}

			trackEvent('add_to_cart', {
				currency: getCurrency(),
				value: item.price * qty,
				items: [item],
			});
		});
	}

	/**
	 * Track remove_from_cart — Cart page item removal
	 */
	function initRemoveFromCart() {
		if (!themeData.ga4.cart_items) return;

		$(document).on('click', '.ats-remove-item', function () {
			var cartKey = $(this).data('cart-key');
			if (!cartKey || !themeData.ga4.cart_items[cartKey]) return;

			var item = themeData.ga4.cart_items[cartKey];

			trackEvent('remove_from_cart', {
				currency: getCurrency(),
				value: item.price * item.quantity,
				items: [item],
			});
		});
	}

	/**
	 * Track select_item — Product clicks from listings
	 */
	function initSelectItem() {
		if (!themeData.ga4.products) return;

		$(document).on('click', 'a[href*="/product/"]', function () {
			// Find the product ID from the closest product card
			var $card = $(this).closest('[data-product-id], .product');
			var productId = $card.data('product-id') || $card.find('.ats-ajax-add-to-cart, .ats-favorite-btn').first().data('product-id');

			if (!productId) return;

			var item = getProductById(productId);
			if (!item) return;

			trackEvent('select_item', {
				item_list_name: themeData.ga4.list_name || 'Shop',
				items: [item],
			});
		});
	}

	/**
	 * Track add_to_wishlist — Favorites button clicks
	 */
	function initAddToWishlist() {
		$(document).on('click', '.ats-favorite-btn', function () {
			var $btn = $(this);
			var productId = parseInt($btn.data('product-id'));
			if (!productId) return;

			// Only track adding, not removing (check if it's currently NOT a favorite)
			if ($btn.hasClass('ats-favorite-active')) return;

			var item = getProductById(productId);

			// Single product page
			if (!item && themeData.ga4.product && themeData.ga4.product.item_id) {
				item = themeData.ga4.product;
			}

			// Fallback
			if (!item) {
				item = {
					item_id: String(productId),
					item_name: 'Product ' + productId,
					item_brand: 'ATS Diamond Tools',
				};
			}

			trackEvent('add_to_wishlist', {
				currency: getCurrency(),
				value: item.price || 0,
				items: [item],
			});
		});
	}

	/**
	 * Track add_shipping_info — Shipping method selection on checkout
	 */
	function initAddShippingInfo() {
		if (!themeData.ga4.checkout_items) return;

		$(document.body).on('change', 'input[name^="shipping_method"]', function () {
			var shippingTier = $(this).closest('label').text().trim() || $(this).val();

			trackEvent('add_shipping_info', {
				currency: getCurrency(),
				value: themeData.ga4.checkout_value || 0,
				shipping_tier: shippingTier,
				items: themeData.ga4.checkout_items,
			});
		});
	}

	/**
	 * Track add_payment_info — Payment method selection on checkout
	 */
	function initAddPaymentInfo() {
		if (!themeData.ga4.checkout_items) return;

		$(document.body).on('change', 'input[name="payment_method"]', function () {
			var paymentType = $(this).closest('label, li').find('label').first().text().trim() || $(this).val();

			trackEvent('add_payment_info', {
				currency: getCurrency(),
				value: themeData.ga4.checkout_value || 0,
				payment_type: paymentType,
				items: themeData.ga4.checkout_items,
			});
		});
	}

	/**
	 * Track generate_lead — Contact form submission
	 */
	function initContactFormTracking() {
		$(document).on('submit', '.rfs-ref-contact-form', function () {
			trackEvent('generate_lead', {
				currency: getCurrency(),
				value: 0,
			});
		});
	}

	/**
	 * Track newsletter_signup — Newsletter form submission
	 */
	function initNewsletterTracking() {
		$(document).on('submit', '.rfs-ref-newsletter-form', function () {
			var email = $(this).find('.rfs-ref-newsletter-email').val();
			if (!email) return;

			trackEvent('newsletter_signup', {
				method: 'footer_form',
			});
		});
	}

	/**
	 * Track outbound link clicks
	 */
	function initOutboundLinkTracking() {
		$(document).on('click', 'a[href^="http"]', function () {
			var href = $(this).attr('href');
			if (!href) return;

			try {
				var url = new URL(href);
				if (url.hostname !== window.location.hostname) {
					trackEvent('click', {
						event_category: 'outbound',
						event_label: href,
						transport_type: 'beacon',
					});
				}
			} catch (e) {
				// Invalid URL, skip
			}
		});
	}

	/**
	 * Track phone number clicks
	 */
	function initPhoneClickTracking() {
		$(document).on('click', 'a[href^="tel:"]', function () {
			trackEvent('click', {
				event_category: 'contact',
				event_label: $(this).attr('href'),
			});
		});
	}

	/**
	 * Track email clicks
	 */
	function initEmailClickTracking() {
		$(document).on('click', 'a[href^="mailto:"]', function () {
			trackEvent('click', {
				event_category: 'contact',
				event_label: $(this).attr('href'),
			});
		});
	}

	/**
	 * Initialize all tracking
	 */
	$(document).ready(function () {
		if (!isGA4Ready()) return;

		initAddToCart();
		initRemoveFromCart();
		initSelectItem();
		initAddToWishlist();
		initAddShippingInfo();
		initAddPaymentInfo();
		initContactFormTracking();
		initNewsletterTracking();
		initOutboundLinkTracking();
		initPhoneClickTracking();
		initEmailClickTracking();
	});
})(jQuery);
