<?php
/**
 * The template for displaying blog archive pages
 *
 * @package skylinewp-dev-child
 */

defined('ABSPATH') || exit;

// Only use this template for blog post archives, not products or other post types
// Check if this is a WooCommerce archive (products, product categories, etc.)
if (function_exists('is_woocommerce') && (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy())) {
	// Load WooCommerce archive template
	wc_get_template('archive-product.php');
	return;
}

// Check if this is a product archive
if (is_post_type_archive('product') || is_tax(array('product_cat', 'product_tag'))) {
	// Load the parent theme's archive template
	get_template_part('archive');
	return;
}

get_header();
?>

<div class="rfs-ref-blog-archive bg-white">
	<!-- Blog Header -->
	<div class="rfs-ref-blog-header bg-gradient-to-br from-primary-500 via-primary-300 to-white border-b border-gray-200">
		<div class="container mx-auto px-4 py-16">
			<div class="max-w-4xl">
				<h1 class="rfs-ref-blog-title text-4xl md:text-5xl font-bold text-ats-dark mb-4">
					<?php
					if (is_category()) {
						single_cat_title();
					} elseif (is_tag()) {
						single_tag_title();
					} elseif (is_author()) {
						the_author();
					} elseif (is_day()) {
						echo get_the_date();
					} elseif (is_month()) {
						echo get_the_date('F Y');
					} elseif (is_year()) {
						echo get_the_date('Y');
					} else {
						echo 'Blog';
					}
					?>
				</h1>
				<?php if (is_category() && category_description()) : ?>
					<div class="rfs-ref-blog-description text-lg text-gray-700 prose prose-lg max-w-none">
						<?php echo category_description(); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Main Content Area -->
	<div class="rfs-ref-blog-content container mx-auto px-4 py-12">
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

			<!-- Main Blog Posts Column -->
			<div class="lg:col-span-8 xl:col-span-8">

				<!-- Posts Container -->
				<div id="ats-blog-posts-container" class="rfs-ref-blog-posts-grid space-y-8">
					<?php if (have_posts()) : ?>
						<?php while (have_posts()) : the_post(); ?>
							<?php get_template_part('template-parts/content', 'blog-card'); ?>
						<?php endwhile; ?>
					<?php else : ?>
						<div class="rfs-ref-no-posts text-center py-16">
							<svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
							</svg>
							<h3 class="text-2xl font-bold text-gray-700 mb-2">No posts found</h3>
							<p class="text-gray-500">Check back later for new content.</p>
						</div>
					<?php endif; ?>
				</div>

				<!-- Pagination -->
				<?php if (have_posts()) : ?>
					<div class="rfs-ref-blog-pagination mt-12 pt-8 border-t border-gray-200">
						<?php
						global $wp_query;
						$big = 999999999;

						echo paginate_links(array(
							'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
							'format' => '?paged=%#%',
							'current' => max(1, get_query_var('paged')),
							'total' => $wp_query->max_num_pages,
							'prev_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>',
							'next_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
							'before_page_number' => '<span class="sr-only">Page </span>',
							'type' => 'list',
							'end_size' => 2,
							'mid_size' => 2,
						));
						?>
					</div>
				<?php endif; ?>

			</div>

			<!-- Sidebar Column -->
			<div class="lg:col-span-4 xl:col-span-4">
				<div class="rfs-ref-blog-sidebar space-y-8 lg:sticky lg:top-8">

					<!-- Categories Widget -->
					<?php get_template_part('template-parts/sidebar', 'categories'); ?>

					<!-- Popular Posts Widget -->
					<?php get_template_part('template-parts/sidebar', 'popular-posts'); ?>

				</div>
			</div>

		</div>
	</div>
</div>

<?php get_footer(); ?>
