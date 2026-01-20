// Flowbite dropdown init for WooCommerce product options
import { Dropdown } from 'flowbite';

document.addEventListener('DOMContentLoaded', function () {
	// Initialize all dropdowns
	document.querySelectorAll('[data-flowbite-dropdown]').forEach((el) => {
		new Dropdown(el);
	});
});
