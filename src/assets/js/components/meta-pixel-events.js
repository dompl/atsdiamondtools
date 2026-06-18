/**
 * Meta (Facebook) Pixel — Client-Side AddToCart
 *
 * SCAFFOLD — inert until a Pixel ID is configured (header.php only prints the
 * base pixel when ATS_META_PIXEL_ID is defined, so `fbq` is undefined until
 * then and every call here is a guarded no-op).
 *
 * AddToCart is the one Meta standard event that needs the client side: it hooks
 * WooCommerce's `added_to_cart` event — the same hook GA4 uses (ga4-events.js) —
 * and reuses the product data the server already localized into themeData.ga4.
 * ViewContent / InitiateCheckout / Purchase are fired server-side in
 * functions/woocommerce/meta-pixel.php.
 *
 * @package ATS Diamond Tools
 */

(function ($) {
	'use strict';

	/**
	 * The pixel is only present (and themeData.ga4 only contains product data)
	 * when tracking is active for this request.
	 */
	function pixelReady() {
		return typeof window.fbq === 'function' && typeof themeData !== 'undefined' && themeData.ga4;
	}

	function getCurrency() {
		return (themeData.ga4 && themeData.ga4.currency) || 'GBP';
	}

	/**
	 * Resolve a GA4-shaped item for the product just added, reusing the same
	 * lookups ga4-events.js performs.
	 */
	function resolveItem(productId, $button) {
		var ga4 = themeData.ga4 || {};
		if (ga4.products && ga4.products[productId]) {
			return ga4.products[productId];
		}
		if (ga4.product) {
			// Single product page — reflect the selected variation's shown price.
			var item = Object.assign({}, ga4.product);
			var shownPrice = parseFloat(($('#ats-product-main-price').text() || '').replace(/[^0-9.]/g, ''));
			if (shownPrice > 0) {
				item.price = shownPrice;
			}
			return item;
		}
		// Minimal fallback from the surrounding product card.
		return {
			item_id: String(productId),
			item_name: $button.closest('.product, [data-product-name]').find('.woocommerce-loop-product__title, [data-product-name]').first().text() || 'Product ' + productId,
			price: parseFloat($button.closest('.product').find('.woocommerce-Price-amount').first().text().replace(/[^0-9.]/g, '')) || 0,
		};
	}

	function initAddToCart() {
		$(document.body).on('added_to_cart', function (e, fragments, cart_hash, $button) {
			if (!pixelReady() || !$button || !$button.length) return;

			var productId = $button.data('product-id') || $button.data('product_id');
			if (!productId) return;

			var item = resolveItem(productId, $button);
			var qty = parseInt($button.closest('form').find('input[name="quantity"]').val(), 10) || 1;
			var price = parseFloat(item.price) || 0;

			window.fbq('track', 'AddToCart', {
				content_type: 'product',
				content_ids: [item.item_id],
				content_name: item.item_name,
				contents: [{ id: item.item_id, quantity: qty, item_price: price }],
				value: price * qty,
				currency: getCurrency(),
			});
		});
	}

	$(function () {
		initAddToCart();
	});
})(jQuery);
