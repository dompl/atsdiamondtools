<?php
/**
 * Output: Admin Quick Order Panel
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only render when called from the component (not during flex registration)
if ( get_row_layout() !== 'admin_quick_order' ) {
	return;
}

// Get field values
$section_heading     = get_sub_field('section_heading') ?: 'Quick Order Panel';
$instructions        = get_sub_field('instructions');
$show_customer_email = get_sub_field('show_customer_email');
$show_filters        = get_sub_field('show_filters');
$products_per_row    = get_sub_field('products_per_row') ?: '3';
$admin_only          = get_sub_field('admin_only');

// Check if admin only and user is not admin
if ( $admin_only && ! current_user_can( 'manage_options' ) ) {
	?>
	<div class="container mx-auto px-4 py-8">
		<div class="rfs-ref-admin-quick-order-restricted bg-red-50 border border-red-200 rounded-lg p-8 text-center">
			<p class="text-red-800 font-semibold">This tool is restricted to administrators only.</p>
		</div>
	</div>
	<?php
	return;
}

// Grid column classes based on products per row
$grid_cols = [
	'2' => 'grid-cols-1 md:grid-cols-2',
	'3' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
	'4' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
];
$grid_class = $grid_cols[$products_per_row] ?? $grid_cols['3'];

// Get all product categories and brands for filters
$categories = get_terms([
	'taxonomy'   => 'product_cat',
	'hide_empty' => true,
	'orderby'    => 'name',
]);

$brands = get_terms([
	'taxonomy'   => 'pwb-brand',
	'hide_empty' => true,
	'orderby'    => 'name',
]);
?>

<div class="container mx-auto px-4 py-8">
	<div class="rfs-ref-admin-quick-order-panel bg-white rounded-lg border-2 border-gray-300 p-6 lg:p-8 mb-8" data-component="admin-quick-order">

		<!-- Header -->
		<div class="rfs-ref-quick-order-header mb-6">
			<h2 class="text-3xl font-bold text-ats-dark mb-2"><?php echo esc_html( $section_heading ); ?></h2>
			<?php if ( $instructions ) : ?>
				<p class="text-gray-600 text-sm"><?php echo esc_html( $instructions ); ?></p>
			<?php endif; ?>
		</div>

		<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

			<!-- Left Column: Search & Products -->
			<div class="lg:col-span-2">

				<?php if ( $show_customer_email ) : ?>
					<!-- Customer Search Section -->
					<div class="rfs-ref-customer-search-section mb-6 bg-white border-2 border-gray-300 rounded-lg p-5">
						<label for="quick-order-customer-search" class="block text-base font-semibold text-ats-dark mb-3">
							Customer Search (Optional)
						</label>
						<div class="relative">
							<input
								type="text"
								id="quick-order-customer-search"
								class="w-full px-4 py-3 pr-12 text-base border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow transition-colors"
								placeholder="Search by email or last name..."
								autocomplete="off"
							/>
							<div class="rfs-ref-customer-search-loading absolute right-3 top-1/2 transform -translate-y-1/2 hidden">
								<svg class="animate-spin h-5 w-5 text-ats-yellow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
								</svg>
							</div>
						</div>
						<!-- Customer Search Results -->
						<div class="rfs-ref-customer-results mt-3 space-y-2 max-h-60 overflow-y-auto hidden"></div>
						<!-- Selected Customer Display -->
						<div class="rfs-ref-selected-customer mt-3 hidden">
							<div class="bg-green-50 border-2 border-green-300 rounded-lg p-4 flex items-center justify-between">
								<div>
									<p class="text-base font-semibold text-green-900">
										<span class="rfs-ref-customer-name"></span>
									</p>
									<p class="text-sm text-green-700">
										<span class="rfs-ref-customer-email"></span>
									</p>
								</div>
								<button type="button" class="rfs-ref-clear-customer px-3 py-1 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded transition-colors">
									Clear
								</button>
							</div>
							<input type="hidden" id="selected-customer-id" value="" />
						</div>
						<p class="text-sm text-gray-600 mt-3">
							<strong>Guest Checkout:</strong> Leave empty to checkout without customer data.
							<br>
							<strong>Customer Order:</strong> Search and select a customer to auto-fill checkout and assign order.
						</p>
					</div>
				<?php endif; ?>

			<?php if ( $show_filters ) : ?>
				<!-- Filters -->
				<div class="rfs-ref-quick-order-filters mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">

					<!-- Category Filter -->
					<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
						<div>
							<label for="filter-category" class="block text-sm font-semibold text-ats-dark mb-2">Category</label>
							<select id="filter-category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow">
								<option value="">All Categories</option>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->term_id ); ?>">
										<?php echo esc_html( $category->name ); ?> (<?php echo $category->count; ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<!-- Brand Filter -->
					<?php if ( ! empty( $brands ) && ! is_wp_error( $brands ) ) : ?>
						<div>
							<label for="filter-brand" class="block text-sm font-semibold text-ats-dark mb-2">Brand</label>
							<select id="filter-brand" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow">
								<option value="">All Brands</option>
								<?php foreach ( $brands as $brand ) : ?>
									<option value="<?php echo esc_attr( $brand->term_id ); ?>">
										<?php echo esc_html( $brand->name ); ?> (<?php echo $brand->count; ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<!-- Stock Filter -->
					<div>
						<label for="filter-stock" class="block text-sm font-semibold text-ats-dark mb-2">Stock Status</label>
						<select id="filter-stock" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow">
							<option value="">All Products</option>
							<option value="instock">In Stock Only</option>
							<option value="outofstock">Out of Stock</option>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<!-- Search Bar -->
			<div class="rfs-ref-quick-order-search mb-6">
				<div class="relative">
					<input
						type="text"
						id="quick-order-search"
						class="w-full px-4 py-3 pr-12 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow text-lg"
						placeholder="Search products by name, SKU, or description..."
						autocomplete="off"
					/>
					<div class="absolute right-3 top-1/2 transform -translate-y-1/2">
						<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
						</svg>
					</div>
					<!-- Loading Spinner -->
					<div class="rfs-ref-search-loading absolute right-12 top-1/2 transform -translate-y-1/2 hidden">
						<svg class="animate-spin h-5 w-5 text-ats-yellow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
						</svg>
					</div>
				</div>
			</div>

			<!-- Results Count & Clear -->
			<div class="rfs-ref-results-header flex items-center justify-between mb-4">
				<p class="rfs-ref-results-count text-sm text-gray-600">
					<span class="font-semibold">0</span> products found
				</p>
				<button type="button" class="rfs-ref-clear-search text-sm text-ats-yellow hover:text-ats-dark font-semibold hidden">
					Clear Search
				</button>
			</div>

			<!-- Products Grid - Ultra-compact layout -->
			<div class="rfs-ref-products-grid grid <?php echo esc_attr( $grid_class ); ?> gap-1.5 min-h-[200px]">
				<!-- Products will be loaded here via AJAX -->
				<div class="col-span-full text-center text-gray-400 py-12">
					<svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
					</svg>
					<p class="text-lg font-medium">Start typing to search products</p>
				</div>
			</div>

			<!-- Infinite Scroll Loading Indicator -->
			<div class="rfs-ref-infinite-scroll-trigger mt-8"></div>
			<div class="rfs-ref-infinite-scroll-loading hidden mt-6 text-center">
				<svg class="animate-spin h-10 w-10 mx-auto text-ats-yellow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
					<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
				</svg>
				<p class="text-sm text-gray-600 mt-2">Loading more products...</p>
			</div>

		</div>

		<!-- Right Column: Cart Summary -->
		<div class="lg:col-span-1">
			<div class="rfs-ref-quick-order-cart bg-gray-50 border border-gray-200 rounded-lg p-4 sticky top-4">

				<h3 class="text-xl font-bold text-ats-dark mb-4 flex items-center justify-between">
					<span>Current Cart</span>
					<button type="button" class="rfs-ref-clear-cart text-sm text-red-600 hover:text-red-800 font-normal hidden">
						Clear All
					</button>
				</h3>

				<!-- Cart Items -->
				<div class="rfs-ref-cart-items space-y-3 mb-4 max-h-96 overflow-y-auto">
					<p class="text-gray-400 text-sm text-center py-8">Cart is empty</p>
				</div>

				<!-- Cart Totals -->
				<div class="rfs-ref-cart-totals border-t border-gray-300 pt-4 mb-4 hidden">
					<div class="flex justify-between mb-2">
						<span class="text-sm text-gray-600">Subtotal:</span>
						<span class="rfs-ref-cart-subtotal text-sm font-semibold"></span>
					</div>
					<div class="flex justify-between mb-2">
						<span class="text-sm text-gray-600">Items:</span>
						<span class="rfs-ref-cart-count text-sm font-semibold">0</span>
					</div>
				</div>

				<!-- Action Buttons -->
				<div class="rfs-ref-cart-actions space-y-2 hidden">
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="block w-full px-4 py-3 bg-ats-dark text-white text-center font-semibold rounded-lg hover:bg-gray-800 transition-colors">
						View Cart
					</a>
					<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="block w-full px-4 py-3 bg-ats-yellow text-ats-dark text-center font-semibold rounded-lg hover:bg-yellow-400 transition-colors">
						Proceed to Checkout
					</a>
				</div>

			</div>
		</div>

	</div>

	</div>
</div>
