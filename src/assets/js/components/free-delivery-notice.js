/**
 * Free Delivery Notice Component
 *
 * Updates header free delivery notices via AJAX when cart contents change.
 * Cart/checkout notices are re-rendered server-side by their respective
 * AJAX refreshes; this JS handles the header notices.
 *
 * @package SkylineWP Dev Child
 */

import $ from 'jquery';

export function initFreeDeliveryNotice() {
	// Early return if no free shipping configured.
	if (typeof themeData === 'undefined' || !themeData.free_shipping_threshold) {
		return;
	}

	const ajaxUrl = themeData.ajax_url || '/wp-admin/admin-ajax.php';
	const nonce = themeData.nonce || '';

	let isUpdating = false;

	/**
	 * Update all header notice elements with fresh data.
	 */
	function updateNotices(data) {
		const messages = document.querySelectorAll('.js-free-delivery-message');
		const bars = document.querySelectorAll('.js-free-delivery-bar');
		const notices = document.querySelectorAll('.js-free-delivery-notice');

		messages.forEach(function (el) {
			el.innerHTML = data.message;
		});

		bars.forEach(function (el) {
			el.style.width = data.percent + '%';

			// Update bar color.
			el.classList.remove('bg-green-500', 'bg-ats-brand', 'bg-gray-400');
			if (data.status === 'qualified') {
				el.classList.add('bg-green-500');
			} else if (data.status === 'remaining') {
				el.classList.add('bg-ats-brand');
			} else {
				el.classList.add('bg-gray-400');
			}
		});

		// Update notice wrapper color classes per context.
		notices.forEach(function (el) {
			const context = el.dataset.context;

			if (context === 'header') {
				el.classList.remove('text-green-600', 'text-ats-brand');
				el.classList.add(data.status === 'qualified' ? 'text-green-600' : 'text-ats-brand');
			} else if (context === 'mobile') {
				el.classList.remove('text-green-400', 'text-ats-brand');
				el.classList.add(data.status === 'qualified' ? 'text-green-400' : 'text-ats-brand');
			}
		});
	}

	/**
	 * Fetch fresh free delivery data from server.
	 */
	function fetchAndUpdate() {
		if (isUpdating) {
			return;
		}
		isUpdating = true;

		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'ats_get_free_delivery_data',
				nonce: nonce,
			},
			success: function (response) {
				if (response.success && response.data) {
					updateNotices(response.data);
				}
			},
			complete: function () {
				isUpdating = false;
			},
		});
	}

	// WooCommerce native events.
	$(document.body).on('added_to_cart removed_from_cart updated_cart_totals updated_checkout', fetchAndUpdate);

	// Custom cart AJAX actions - listen on ajaxComplete.
	$(document).on('ajaxComplete', function (event, xhr, settings) {
		if (!settings || !settings.data) {
			return;
		}

		const data = typeof settings.data === 'string' ? settings.data : '';
		const customActions = [
			'ats_update_cart_quantity',
			'ats_apply_coupon',
			'ats_remove_coupon',
			'ats_remove_cart_item',
		];

		const hasCustomAction = customActions.some(function (action) {
			return data.indexOf('action=' + action) !== -1;
		});

		if (hasCustomAction) {
			// Small delay to let the cart update complete first.
			setTimeout(fetchAndUpdate, 300);
		}
	});
}
