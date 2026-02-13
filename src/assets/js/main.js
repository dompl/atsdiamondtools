// import $ from 'jquery';
// window.jQuery = $;
// window.$ = $;
// prettier-ignore
import { initDropdowns } from 'flowbite/lib/esm/components/dropdown';
import { initModals } from 'flowbite/lib/esm/components/modal';
import { initDrawers } from 'flowbite/lib/esm/components/drawer';
import { initTabs } from 'flowbite/lib/esm/components/tabs';
import { initDismisses } from 'flowbite/lib/esm/components/dismiss';

// Active Components
import './components/add_to_cart.js';
import './components/ajax-add-to-cart.js';
import './components/newsletter.js';
import './components/product-scroller.js';
import './components/product-quick-view.js';
import './components/favorites.js';
import './components/blog.js';
import './components/shop-filter.js';
import './components/contact-form.js';
import './components/admin-quick-order.js';
import { initATSSearch } from './components/search.js';
import { initSingleProduct, initQuantityButtons, initAjaxAddToCart } from './components/single-product.js';
import { initWooCommerceAccount } from './components/woocommerce-account.js';
import { initProductTabs } from './components/product-tabs.js';
import { initReviewForm } from './components/review-form.js';
import { initCart } from './components/cart.js';
import { initCheckout } from './components/checkout.js';
import { initFreeDeliveryNotice } from './components/free-delivery-notice.js';
import { initBackInStock } from './components/back-in-stock.js';

// Inactive Components
import './components/banner.js';
import './components/about-us.js';
// import './components/price-manager.js'; // Admin-only tool, removed from frontend bundle
// import './components/container.js';
// import './components/hero-slider.js';
// import './components/icon-bar.js';
// import './components/logo-scroller.js';
// import './components/numbers.js';
// import './components/particles.js';
// import './components/pillared-features.js';
// import './components/post_grid.js';
// import './components/resources.js';

// import './components/search-page.js';
// import './components/team-grid.js';
// import './components/testimonials.js';
// import './tailwind/playground.js';

// Initialize modules when the document is ready.
document.addEventListener('DOMContentLoaded', function () {
	// Initialize Flowbite components
	initDropdowns();
	initModals();
	initDrawers();
	initTabs();
	initDismisses();

	initATSSearch();
	initWooCommerceAccount();
	initSingleProduct();
	initProductTabs();
	initReviewForm();
	initCart();
	initCheckout();

	// Initialize quantity buttons globally (for product pages, quick view, cart, etc.)
	initQuantityButtons();

	// Initialize AJAX add to cart globally (for product pages, quick view, etc.)
	initAjaxAddToCart();

	// Initialize free delivery notice updates
	initFreeDeliveryNotice();

	// Initialize back-in-stock notifications
	initBackInStock();
});
