// import $ from 'jquery';
// window.jQuery = $;
// window.$ = $;
// prettier-ignore
import 'flowbite';

// Active Components
import './components/add_to_cart.js';
import './components/newsletter.js';
import './components/product-scroller.js';
import './components/product-quick-view.js';
import './components/favorites.js';
import './components/blog.js';
import './components/shop-filter.js';
import { initATSSearch } from './components/search.js';
import { initSingleProduct } from './components/single-product.js';
import { initWooCommerceAccount } from './components/woocommerce-account.js';
import { initProductTabs } from './components/product-tabs.js';
import { initReviewForm } from './components/review-form.js';
import { initCart } from './components/cart.js';
import { initCheckout } from './components/checkout.js';

// Inactive Components
import './components/banner.js';
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
	initATSSearch();
	initWooCommerceAccount();
	initSingleProduct();
	initProductTabs();
	initReviewForm();
	initCart();
	initCheckout();
});
