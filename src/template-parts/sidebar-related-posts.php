<?php
/**
 * Template part for related posts sidebar (single post)
 *
 * @package skylinewp-dev-child
 */

defined('ABSPATH') || exit;

if (!is_single()) {
	return;
}

// Get current post categories
$categories = get_the_category();

if (empty($categories)) {
	return;
}

// Get related posts from the same categories
$related_posts = new WP_Query(array(
	'post_type' => 'post',
	'posts_per_page' => 4,
	'post__not_in' => array(get_the_ID()),
	'category__in' => wp_list_pluck($categories, 'term_id'),
	'orderby' => 'rand',
	'post_status' => 'publish',
));

if (!$related_posts->have_posts()) {
	wp_reset_postdata();
	return;
}
?>

<div class="rfs-ref-sidebar-related bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
	<!-- Widget Header -->
	<div class="rfs-ref-sidebar-related-header flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
		<div class="rfs-ref-sidebar-icon-wrapper bg-primary-600 rounded-lg p-2">
			<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
			</svg>
		</div>
		<h3 class="rfs-ref-sidebar-title text-xl font-bold text-ats-dark">Related Articles</h3>
	</div>

	<!-- Related Posts List -->
	<div class="rfs-ref-related-posts-list space-y-5">
		<?php while ($related_posts->have_posts()) : $related_posts->the_post(); ?>
			<article class="rfs-ref-related-post-item group">
				<a href="<?php the_permalink(); ?>" class="rfs-ref-related-post-link flex gap-4 hover:bg-gray-50 rounded-lg p-2 -m-2 transition-colors">

					<!-- Thumbnail -->
					<?php if (has_post_thumbnail()) : ?>
						<div class="rfs-ref-related-post-thumb flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-gray-100">
							<img
								src="<?php echo wpimage(image: get_post_thumbnail_id(), size: 120, retina: true, quality: 85); ?>"
								alt="<?php echo esc_attr(get_the_title()); ?>"
								class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
								loading="lazy"
							>
						</div>
					<?php endif; ?>

					<!-- Content -->
					<div class="rfs-ref-related-post-content flex-1 min-w-0">
						<!-- Title -->
						<h4 class="rfs-ref-related-post-title text-sm font-bold text-ats-dark mb-2 line-clamp-2 group-hover:text-primary-700 transition-colors">
							<?php the_title(); ?>
						</h4>

						<!-- Meta -->
						<div class="rfs-ref-related-post-meta flex items-center gap-2 text-xs text-gray-500">
							<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
							</svg>
							<time datetime="<?php echo get_the_date('c'); ?>">
								<?php echo get_the_date('M j, Y'); ?>
							</time>
						</div>
					</div>

				</a>
			</article>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</div>
