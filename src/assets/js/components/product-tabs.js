/**
 * Product Tabs Initialization
 *
 * Initialize Flowbite tabs for WooCommerce product tabs
 */

import Tabs from 'flowbite/lib/esm/components/tabs';

export function initProductTabs() {
	// Only run on single product pages
	if (!document.body.classList.contains('single-product')) {
		return;
	}

	// Wait for DOM to be ready
	const tabsList = document.getElementById('product-tabs-list');
	const tabsContent = document.getElementById('product-tabs-content');

	if (!tabsList || !tabsContent) {
		return;
	}

	// Get all tab buttons
	const tabButtons = tabsList.querySelectorAll('[data-tabs-target]');

	if (tabButtons.length === 0) {
		return;
	}

	// Build tabs array for Flowbite
	const tabsArray = [];
	tabButtons.forEach((button, index) => {
		const targetId = button.getAttribute('data-tabs-target');
		const targetElement = document.querySelector(targetId);

		if (targetElement) {
			tabsArray.push({
				id: targetId.replace('#', ''),
				triggerEl: button,
				targetEl: targetElement,
			});
		}
	});

	// Initialize Flowbite Tabs
	const options = {
		defaultTabId: tabsArray[0]?.id,
		activeClasses: 'text-ats-yellow border-ats-yellow',
		inactiveClasses: 'text-gray-500 hover:text-ats-brand hover:border-ats-brand border-transparent',
		onShow: (tab) => {
		},
	};

	const tabs = new Tabs(tabsList, tabsArray, options);

}
