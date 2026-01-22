<?php
/**
 * Template part for popular posts sidebar
 *
 * @package skylinewp-dev-child
 */

defined('ABSPATH') || exit;

// Get popular posts by comment count (or use a custom meta key for views)
$popular_posts = new WP_Query(array(
	'post_type' => 'post',
	'posts_per_page' => 5,
	'orderby' => 'comment_count',
	'order' => 'DESC',
	'post_status' => 'publish',
	'ignore_sticky_posts' => true,
));

if (!$popular_posts->have_posts()) {
	wp_reset_postdata();
	return;
}
?>

<div class="rfs-ref-sidebar-popular bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
	<!-- Widget Header -->
	<div class="rfs-ref-sidebar-popular-header flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
		<div class="rfs-ref-sidebar-icon-wrapper bg-accent-yellow rounded-lg p-2">
			<svg class="w-5 h-5 text-ats-dark" fill="currentColor" viewBox="0 0 24 24">
				<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
			</svg>
		</div>
		<h3 class="rfs-ref-sidebar-title text-xl font-bold text-ats-dark">Popular Posts</h3>
	</div>

	<!-- Popular Posts List -->
	<div class="rfs-ref-popular-posts-list space-y-5">
		<?php while ($popular_posts->have_posts()) : $popular_posts->the_post(); ?>
			<article class="rfs-ref-popular-post-item group">
				<a href="<?php the_permalink(); ?>" class="rfs-ref-popular-post-link flex gap-4 hover:bg-gray-50 rounded-lg p-2 -m-2 transition-colors">

					<!-- Thumbnail -->
					<?php if (has_post_thumbnail()) : ?>
						<div class="rfs-ref-popular-post-thumb flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-gray-100">
							<img
								src="<?php echo wpimage(image: get_post_thumbnail_id(), size: 120, retina: true, quality: 85); ?>"
								alt="<?php echo esc_attr(get_the_title()); ?>"
								class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
								loading="lazy"
							>
						</div>
					<?php endif; ?>

					<!-- Content -->
					<div class="rfs-ref-popular-post-content flex-1 min-w-0">
						<!-- Title -->
						<h4 class="rfs-ref-popular-post-title text-sm font-bold text-ats-dark mb-2 line-clamp-2 group-hover:text-primary-700 transition-colors">
							<?php the_title(); ?>
						</h4>

						<!-- Meta -->
						<div class="rfs-ref-popular-post-meta flex items-center gap-2 text-xs text-gray-500">
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
