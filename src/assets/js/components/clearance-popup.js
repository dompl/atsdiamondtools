/**
 * Clearance Pop-up
 *
 * Shows the site-wide clearance announcement modal after a configurable delay,
 * capped by sessionStorage (once per session) or localStorage (once every N
 * days). Markup + eligibility live in
 * functions/template-parts/clearance-popup-modal.php.
 *
 * @package skylinewp-dev-child
 */

import { Modal } from 'flowbite';

(function () {
	'use strict';

	const ROOT_ID = 'ats-clearance-popup';

	/**
	 * Whether the pop-up was already shown within the capping window.
	 */
	function isCapped(key, mode, days) {
		try {
			if (mode === 'days') {
				const stored = parseInt(window.localStorage.getItem(key), 10);
				if (!stored) return false;
				const windowMs = Math.max(1, days) * 86400000;
				return Date.now() - stored < windowMs;
			}
			return window.sessionStorage.getItem(key) === '1';
		} catch (e) {
			// Storage unavailable (private mode etc.) — fail open.
			return false;
		}
	}

	/**
	 * Persist the "seen" flag according to the capping mode.
	 */
	function markSeen(key, mode) {
		try {
			if (mode === 'days') {
				window.localStorage.setItem(key, String(Date.now()));
			} else {
				window.sessionStorage.setItem(key, '1');
			}
		} catch (e) {
			// Ignore storage failures.
		}
	}

	function init() {
		try {
			const root = document.getElementById(ROOT_ID);
			if (!root) return;

			const key = root.dataset.storageKey || 'ats_clearance_popup_dismissed';
			const mode = root.dataset.frequencyMode || 'session';
			const days = parseInt(root.dataset.frequencyDays, 10) || 30;
			const delayMs = (parseInt(root.dataset.delay, 10) || 0) * 1000;

			if (isCapped(key, mode, days)) return;

			let lastFocused = null;

			const modal = new Modal(root, {
				placement: 'center',
				backdrop: 'dynamic',
				backdropClasses: 'bg-black/60 fixed inset-0 z-40',
				closable: true,
				onHide: function () {
					markSeen(key, mode);
					if (lastFocused && typeof lastFocused.focus === 'function') {
						lastFocused.focus();
					}
				},
			});

			// Close button (Flowbite doesn't auto-bind for programmatic instances).
			const closeBtn = root.querySelector('[data-modal-hide]');
			if (closeBtn) {
				closeBtn.addEventListener('click', function (e) {
					e.preventDefault();
					modal.hide();
				});
			}

			// Close when the overlay (not the panel) is clicked.
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
			// Never break the page over an announcement modal.
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
