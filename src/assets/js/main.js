// import $ from 'jquery';
// window.jQuery = $;
// window.$ = $;
// prettier-ignore
import 'flowbite';

// Active Components
import './components/add_to_cart.js';
import './components/newsletter.js';
import './components/product-scroller.js';
import { initATSSearch } from './components/search.js';
import { initSingleProduct } from './components/single-product.js';
import { initWooCommerceAccount } from './components/woocommerce-account.js';

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
});
