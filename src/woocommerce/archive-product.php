<?php
/**
 * The Template for displaying product archives
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Get current filters.
// Check if we're on a category page
$current_category = 0;
if ( is_product_category() ) {
	$queried_object   = get_queried_object();
	$current_category = $queried_object->term_id;
}
$current_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'default';

// Get categories for sidebar.
$categories = ats_get_product_categories_for_sidebar( $current_category );

// Get price range.
$price_range = ats_get_price_range_for_products( $current_category );

// Get sorting options.
$sorting_options    = ats_get_sorting_options();
$current_sort_label = ats_get_current_sorting_label( $current_orderby );

// Get products per page - will load 12 initially, then 8 at a time on scroll.
$products_per_page = 12;
?>

<div class="rfs-ref-shop-page bg-white min-h-screen">

	<?php
	// Category Banner (for category pages only)
	if ( is_product_category() ) :
		$queried_object = get_queried_object();
		$category_name  = $queried_object->name;
		$category_desc  = $queried_object->description;

		// Get category image or fallback to default
		$thumbnail_id = get_term_meta( $queried_object->term_id, 'thumbnail_id', true );
		$banner_image_id = $thumbnail_id ? $thumbnail_id : 43462; // Fallback to image ID 43462

		// Use wpimage() to get the image URL with retina support
		$banner_image_url = wpimage( $banner_image_id, [1920, 400], false, true, true, true, 85 );
		?>

		<!-- Category Banner -->
		<div class="rfs-ref-shop-container container mx-auto px-4 pt-4 mb-6">
			<div class="rfs-ref-category-banner relative h-[200px] md:h-[250px] overflow-hidden rounded-lg">
				<!-- Background Image -->
				<div class="absolute inset-0">
					<img src="<?php echo esc_url( $banner_image_url ); ?>"
					     alt="<?php echo esc_attr( $category_name ); ?>"
					     class="w-full h-full object-cover" />
					<!-- Overlay Gradient -->
					<div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-black/30"></div>
				</div>

				<!-- Decorative Brand Elements -->
				<div class="rfs-ref-banner-decorations absolute inset-0 pointer-events-none opacity-20">
					<!-- Large Circle - Top Right -->
					<div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-primary-600 blur-3xl"></div>
					<!-- Medium Circle - Bottom Left -->
					<div class="absolute -bottom-16 -left-16 w-48 h-48 rounded-full bg-ats-yellow blur-2xl"></div>
					<!-- Small Accent - Middle -->
					<div class="absolute top-1/2 right-1/4 w-32 h-32 rounded-full bg-primary-300 blur-xl"></div>
				</div>

				<!-- Content -->
				<div class="rfs-ref-category-banner-content relative z-10 h-full flex flex-col justify-center px-8 md:px-12">
					<div class="max-w-3xl">
						<h1 class="rfs-ref-category-title text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-2 drop-shadow-lg">
							<?php echo esc_html( $category_name ); ?>
						</h1>

						<?php if ( ! empty( $category_desc ) ) : ?>
							<div class="rfs-ref-category-description text-sm md:text-base text-gray-200 leading-relaxed max-w-2xl drop-shadow-md">
								<?php echo wp_kses_post( $category_desc ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

	<?php else : ?>

		<!-- Shop Page Header (for main shop page) -->
		<div class="rfs-ref-shop-container container mx-auto px-4 pt-8">
			<div class="rfs-ref-shop-header mb-8">
				<h1 class="rfs-ref-shop-title text-3xl md:text-4xl font-bold text-ats-dark mb-2">
					<?php woocommerce_page_title(); ?>
				</h1>
				<?php if ( category_description() ) : ?>
					<div class="rfs-ref-shop-description text-gray-600 leading-relaxed">
						<?php echo wp_kses_post( category_description() ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

	<?php endif; ?>

	<div class="rfs-ref-shop-container container mx-auto px-4 <?php echo is_product_category() ? 'pt-4' : 'pt-0'; ?>">

		<!-- Main Grid: Sidebar + Products -->
		<div class="rfs-ref-shop-grid grid grid-cols-1 lg:grid-cols-12 gap-8">

			<!-- LEFT SIDEBAR -->
			<aside class="rfs-ref-shop-sidebar lg:col-span-3">
				<div class="rfs-ref-sidebar-sticky lg:sticky lg:top-4 space-y-6">

					<!-- Categories Section -->
					<div class="rfs-ref-sidebar-section rfs-ref-sidebar-categories bg-white border border-gray-200 rounded-lg p-6">
						<h3 class="rfs-ref-sidebar-title text-lg font-bold text-ats-dark mb-4 pb-3 border-b border-gray-200">
							<?php esc_html_e( 'Categories', 'skylinewp-dev-child' ); ?>
						</h3>

						<?php if ( ! empty( $categories ) ) : ?>
							<ul class="rfs-ref-category-list space-y-2">
								<!-- All Products -->
								<li class="rfs-ref-category-item">
									<button type="button"
									   class="rfs-ref-category-link w-full text-left flex items-center justify-between py-2 px-3 rounded-lg text-sm transition-colors duration-200 hover:bg-primary-600 hover:text-white <?php echo $current_category === 0 ? 'bg-primary-600 text-white font-bold' : 'text-gray-700'; ?>"
									   data-category-id="0">
										<span class="rfs-ref-category-name"><?php esc_html_e( 'All Products', 'skylinewp-dev-child' ); ?></span>
									</button>
								</li>

								<?php foreach ( $categories as $category ) : ?>
									<li class="rfs-ref-category-item">
										<button type="button"
										   class="rfs-ref-category-link w-full text-left flex items-center justify-between py-2 px-3 rounded-lg text-sm transition-colors duration-200 hover:bg-primary-600 hover:text-white <?php echo $category['is_current'] ? 'bg-primary-600 text-white font-bold' : 'text-gray-700'; ?>"
										   data-category-id="<?php echo esc_attr( $category['id'] ); ?>">
											<span class="rfs-ref-category-name"><?php echo esc_html( $category['name'] ); ?></span>
											<span class="rfs-ref-category-count text-xs <?php echo $category['is_current'] ? 'text-white opacity-80' : 'text-gray-500'; ?>">(<?php echo esc_html( $category['count'] ); ?>)</span>
										</button>

										<?php if ( ! empty( $category['children'] ) ) : ?>
											<ul class="rfs-ref-category-children ml-4 mt-2 space-y-1">
												<?php foreach ( $category['children'] as $child ) : ?>
													<li class="rfs-ref-category-child-item">
														<button type="button"
														   class="rfs-ref-category-link w-full text-left flex items-center justify-between py-1.5 px-3 rounded-lg text-sm transition-colors duration-200 hover:bg-primary-600 hover:text-white <?php echo $child['is_current'] ? 'bg-primary-600 text-white font-bold' : 'text-gray-600'; ?>"
														   data-category-id="<?php echo esc_attr( $child['id'] ); ?>">
															<span class="rfs-ref-category-name"><?php echo esc_html( $child['name'] ); ?></span>
															<span class="rfs-ref-category-count text-xs <?php echo $child['is_current'] ? 'text-white opacity-80' : 'text-gray-400'; ?>">(<?php echo esc_html( $child['count'] ); ?>)</span>
														</button>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>

					<!-- Applications Filter Section -->
					<div class="rfs-ref-sidebar-section rfs-ref-sidebar-applications bg-white border border-gray-200 rounded-lg p-6">
						<h3 class="rfs-ref-sidebar-title text-lg font-bold text-ats-dark mb-4 pb-3 border-b border-gray-200">
							<?php esc_html_e( 'Applications', 'skylinewp-dev-child' ); ?>
						</h3>

						<?php
						// Get all application terms
						$applications = get_terms( array(
							'taxonomy'   => 'product_application',
							'hide_empty' => true,
						) );
						?>

						<?php if ( ! empty( $applications ) && ! is_wp_error( $applications ) ) : ?>
							<ul class="rfs-ref-application-list space-y-2">
								<!-- All Applications -->
								<li class="rfs-ref-application-item">
									<button type="button"
									   class="rfs-ref-application-link w-full text-left flex items-center justify-between py-2 px-3 rounded-lg text-sm transition-colors duration-200 hover:bg-primary-600 hover:text-white bg-primary-600 text-white font-bold"
									   data-application-id="0">
										<span class="rfs-ref-application-name"><?php esc_html_e( 'All Applications', 'skylinewp-dev-child' ); ?></span>
									</button>
								</li>

								<?php foreach ( $applications as $app ) : ?>
									<li class="rfs-ref-application-item">
										<button type="button"
										   class="rfs-ref-application-link w-full text-left flex items-center justify-between py-2 px-3 rounded-lg text-sm transition-colors duration-200 hover:bg-primary-600 hover:text-white text-gray-700"
										   data-application-id="<?php echo esc_attr( $app->term_id ); ?>">
											<span class="rfs-ref-application-name"><?php echo esc_html( $app->name ); ?></span>
											<span class="rfs-ref-application-count text-xs text-gray-500">(<?php echo esc_html( $app->count ); ?>)</span>
										</button>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>

					<!-- Price Filter Section -->
					<div class="rfs-ref-sidebar-section rfs-ref-sidebar-price-filter bg-white border border-gray-200 rounded-lg p-6">
						<h3 class="rfs-ref-sidebar-title text-lg font-bold text-ats-dark mb-4 pb-3 border-b border-gray-200">
							<?php esc_html_e( 'Price Range', 'skylinewp-dev-child' ); ?>
						</h3>

						<div class="rfs-ref-price-slider-container">
							<!-- Dual Range Slider -->
							<div class="rfs-ref-price-slider-wrapper relative h-2 bg-gray-200 rounded-full mb-8">
								<div class="rfs-ref-price-slider-track absolute h-full bg-primary-600 rounded-full"></div>
								<input type="range"
								       class="rfs-ref-price-slider-min absolute w-full pointer-events-none appearance-none bg-transparent"
								       min="<?php echo esc_attr( $price_range['min'] ); ?>"
								       max="<?php echo esc_attr( $price_range['max'] ); ?>"
								       value="<?php echo esc_attr( $price_range['min'] ); ?>"
								       step="1">
								<input type="range"
								       class="rfs-ref-price-slider-max absolute w-full pointer-events-none appearance-none bg-transparent"
								       min="<?php echo esc_attr( $price_range['min'] ); ?>"
								       max="<?php echo esc_attr( $price_range['max'] ); ?>"
								       value="<?php echo esc_attr( $price_range['max'] ); ?>"
								       step="1">
							</div>

							<div class="rfs-ref-price-values flex items-center justify-between text-sm">
								<div class="rfs-ref-price-min-container">
									<span class="text-gray-600"><?php esc_html_e( 'Min:', 'skylinewp-dev-child' ); ?></span>
									<span class="rfs-ref-price-min-value font-bold text-ats-dark">£<?php echo esc_html( $price_range['min'] ); ?></span>
								</div>
								<div class="rfs-ref-price-max-container">
									<span class="text-gray-600"><?php esc_html_e( 'Max:', 'skylinewp-dev-child' ); ?></span>
									<span class="rfs-ref-price-max-value font-bold text-ats-dark">£<?php echo esc_html( $price_range['max'] ); ?></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Newsletter Widget -->
					<div class="rfs-ref-sidebar-section rfs-ref-sidebar-newsletter">
						<?php echo ats_render_newsletter_widget(); ?>
					</div>

				</div>
			</aside>

			<!-- RIGHT CONTENT AREA -->
			<main class="rfs-ref-shop-content lg:col-span-9">

				<!-- Toolbar: Results Count + Sort Dropdown -->
				<div class="rfs-ref-shop-toolbar flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-4 border-b border-gray-200">
					<!-- Results Count -->
					<div class="rfs-ref-results-count text-sm text-gray-600">
						<?php
						$total   = $GLOBALS['wp_query']->found_posts;
						$showing = min( $products_per_page, $total );
						?>
						<span class="rfs-ref-showing-text">
							<?php
							echo esc_html__( 'Showing ', 'skylinewp-dev-child' );
							?>
							<span class="rfs-ref-showing-count font-bold text-ats-dark"><?php echo esc_html( $showing ); ?></span>
							<?php echo esc_html__( ' of ', 'skylinewp-dev-child' ); ?>
							<span class="rfs-ref-total-count font-bold text-ats-dark"><?php echo esc_html( $total ); ?></span>
							<?php echo esc_html__( ' products', 'skylinewp-dev-child' ); ?>
						</span>
					</div>

					<!-- Favourites + Sort Dropdown Container (right side) -->
					<div class="rfs-ref-toolbar-right flex items-center gap-3">
						<!-- Show Favourite Products Button -->
						<div class="rfs-ref-favourites-filter">
							<button type="button"
							        class="rfs-ref-show-favourites-btn text-ats-dark bg-white border border-gray-300 hover:bg-ats-brand hover:text-white focus:outline-none font-medium rounded-lg text-sm px-5 py-2.5 inline-flex items-center gap-2 transition-colors duration-200"
							        data-filter-favourites="false">
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
								</svg>
								<span><?php esc_html_e( 'Favourite Products', 'skylinewp-dev-child' ); ?></span>
							</button>
						</div>

						<!-- Sort Dropdown (Flowbite) -->
					<div class="rfs-ref-sort-dropdown-container">
						<button id="dropdownSortButton"
						        data-dropdown-toggle="dropdownSort"
						        class="rfs-ref-sort-dropdown-button text-ats-dark bg-white border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center transition-colors duration-200"
						        type="button">
							<span class="rfs-ref-sort-label"><?php esc_html_e( 'Order by:', 'skylinewp-dev-child' ); ?></span>
							<span class="rfs-ref-current-sort ml-2 font-bold"><?php echo esc_html( $current_sort_label ); ?></span>
							<svg class="w-2.5 h-2.5 ml-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
								<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
							</svg>
						</button>

						<!-- Dropdown menu -->
						<div id="dropdownSort" class="rfs-ref-sort-dropdown-menu z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-lg w-56">
							<ul class="py-2 text-sm text-gray-700" aria-labelledby="dropdownSortButton">
								<?php foreach ( $sorting_options as $sort_key => $sort_label ) : ?>
									<li>
										<button type="button"
										   class="rfs-ref-sort-option w-full text-left block px-4 py-2 hover:bg-ats-gray transition-colors duration-200 <?php echo $current_orderby === $sort_key ? 'bg-ats-yellow font-bold' : ''; ?>"
										   data-sort="<?php echo esc_attr( $sort_key ); ?>">
											<?php echo esc_html( $sort_label ); ?>
										</button>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
					</div><!-- /.rfs-ref-toolbar-right -->
				</div>

				<!-- Products Grid Container -->
				<div class="rfs-ref-products-container relative" data-current-category="<?php echo esc_attr( $current_category ); ?>">

					<!-- Products Grid: 4 columns on desktop, 2 on tablet, 1 on mobile, centered -->
					<div class="rfs-ref-products-grid grid grid-cols-1 sm:grid-cols-2 sm:justify-items-center lg:grid-cols-2 xl:grid-cols-4 xl:justify-items-start gap-3">
						<?php
						if ( woocommerce_product_loop() ) {
							while ( have_posts() ) {
								the_post();
								echo do_shortcode( '[ats_product id="' . get_the_ID() . '" display="1"]' );
							}
							wp_reset_postdata();
						} else {
							?>
							<div class="rfs-ref-no-products col-span-full text-center py-16">
								<p class="text-gray-600 text-lg"><?php esc_html_e( 'No products found.', 'skylinewp-dev-child' ); ?></p>
							</div>
							<?php
						}
						?>
					</div>

					<!-- Loading Overlay -->
					<div class="rfs-ref-loading-overlay hidden absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
						<div role="status" class="rfs-ref-loading-spinner">
							<svg aria-hidden="true" class="w-12 h-12 text-gray-200 animate-spin fill-ats-yellow" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
								<path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
							</svg>
							<span class="sr-only"><?php esc_html_e( 'Loading...', 'skylinewp-dev-child' ); ?></span>
						</div>
					</div>

					<!-- Infinite Scroll Trigger (Hidden) -->
					<div class="rfs-ref-infinite-scroll-trigger h-px" data-page="1" data-max-pages="<?php echo esc_attr( $GLOBALS['wp_query']->max_num_pages ); ?>"></div>

				</div>

			</main>

		</div>

	</div>
</div>

<?php
get_footer();
