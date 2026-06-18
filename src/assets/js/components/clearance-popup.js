/**
 * Clearance campaign — pop-up + top bar.
 *
 * Pop-up shows after a configurable delay, capped per session / per N days.
 * Closing the pop-up reveals a persistent top announcement bar; visitors who
 * don't get the pop-up (already capped) get the bar straight away. The bar has
 * its own (separate, longer-lived) dismissal. Markup + eligibility live in
 * functions/template-parts/clearance-popup-modal.php and clearance-bar.php.
 *
 * @package skylinewp-dev-child
 */

import { Modal } from 'flowbite';

(function () {
	'use strict';

	const POPUP_ID = 'ats-clearance-popup';
	const BAR_ID = 'ats-clearance-bar';

	function storageGet(type, key) {
		try {
			return window[type].getItem(key);
		} catch (e) {
			return null;
		}
	}
	function storageSet(type, key, val) {
		try {
			window[type].setItem(key, val);
		} catch (e) {
			// Storage unavailable (private mode etc.) — ignore.
		}
	}

	/**
	 * Whether the pop-up was already shown within the capping window.
	 */
	function popupCapped(key, mode, days) {
		if (mode === 'days') {
			const stored = parseInt(storageGet('localStorage', key), 10);
			if (!stored) return false;
			return Date.now() - stored < Math.max(1, days) * 86400000;
		}
		return storageGet('sessionStorage', key) === '1';
	}

	function popupMarkSeen(key, mode) {
		if (mode === 'days') {
			storageSet('localStorage', key, String(Date.now()));
		} else {
			storageSet('sessionStorage', key, '1');
		}
	}

	/**
	 * Wire up the top bar. Returns a controller with reveal(), or null if the
	 * bar isn't on the page / has been dismissed.
	 */
	function setupBar() {
		const bar = document.getElementById(BAR_ID);
		if (!bar) return null;

		const dismissKey = bar.dataset.storageKey || 'ats_clearance_bar_dismissed';
		if (storageGet('localStorage', dismissKey) === '1') return null;

		const closeBtn = bar.querySelector('.ats-clearance-bar__close');
		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				bar.classList.remove('is-visible');
				storageSet('localStorage', dismissKey, '1');
				// Remove from layout once the collapse transition finishes.
				window.setTimeout(function () {
					bar.hidden = true;
				}, 450);
			});
		}

		return {
			reveal: function () {
				if (bar.classList.contains('is-visible')) return;
				bar.hidden = false;
				// Force reflow so the max-height transition runs from 0.
				void bar.offsetHeight;
				bar.classList.add('is-visible');
			},
		};
	}

	function init() {
		try {
			const bar = setupBar();
			const root = document.getElementById(POPUP_ID);

			// No pop-up on this page — show the bar straight away (if eligible).
			if (!root) {
				if (bar) bar.reveal();
				return;
			}

			const key = root.dataset.storageKey || 'ats_clearance_popup_dismissed';
			const mode = root.dataset.frequencyMode || 'session';
			const days = parseInt(root.dataset.frequencyDays, 10) || 30;
			const delayMs = (parseInt(root.dataset.delay, 10) || 0) * 1000;

			// Already saw the pop-up this window — skip it, show the bar.
			if (popupCapped(key, mode, days)) {
				if (bar) bar.reveal();
				return;
			}

			let lastFocused = null;

			const modal = new Modal(root, {
				placement: 'center',
				backdrop: 'dynamic',
				backdropClasses: 'bg-black/60 fixed inset-0 z-40',
				closable: true,
				onHide: function () {
					popupMarkSeen(key, mode);
					if (lastFocused && typeof lastFocused.focus === 'function') {
						lastFocused.focus();
					}
					// Closing the pop-up reveals the persistent bar.
					if (bar) bar.reveal();
				},
			});

			const closeBtn = root.querySelector('[data-modal-hide]');
			if (closeBtn) {
				closeBtn.addEventListener('click', function (e) {
					e.preventDefault();
					modal.hide();
				});
			}

			root.addEventListener('click', function (e) {
				if (e.target === root) {
					modal.hide();
				}
			});

			window.setTimeout(function () {
				lastFocused = document.activeElement;
				modal.show();
			}, delayMs);
		} catch (e) {
			// Never break the page over an announcement.
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
