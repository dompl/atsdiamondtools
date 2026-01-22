<?php
/**
 * The template for displaying single blog posts
 *
 * @package skylinewp-dev-child
 */

defined('ABSPATH') || exit;

// Only use this template for regular blog posts, not products or other post types
if (!is_singular('post')) {
	// Load the parent theme's single template for other post types
	$parent_template = get_template_directory() . '/src/single.php';
	if (file_exists($parent_template)) {
		include($parent_template);
		return;
	}

	// Fallback with proper container structure
	get_header();
	?>
	<!-- Main Content Area -->
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>
			<section class="container mx-auto relative av-padding-small">
				<?php the_content(); ?>
			</section>
		<?php endwhile; ?>
	<?php endif; ?>
	<?php get_template_part('aside'); ?>
	<?php
	get_footer();
	return;
}

get_header();
?>

<?php while (have_posts()) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class('rfs-ref-single-post'); ?>>

		<!-- Hero Header with Featured Image -->
		<?php if (has_post_thumbnail()) : ?>
			<div class="rfs-ref-post-hero relative bg-ats-dark overflow-hidden">
				<div class="rfs-ref-post-hero-image absolute inset-0">
					<img
						src="<?php echo wpimage(image: get_post_thumbnail_id(), size: [1920, 600], retina: true, quality: 90); ?>"
						alt="<?php echo esc_attr(get_the_title()); ?>"
						class="w-full h-full object-cover opacity-40"
					>
					<div class="absolute inset-0 bg-gradient-to-t from-ats-dark via-ats-dark/70 to-transparent"></div>
				</div>

				<div class="rfs-ref-post-hero-content relative container mx-auto px-4 py-20 md:py-32">
					<div class="max-w-4xl">
						<!-- Categories -->
						<?php
						$categories = get_the_category();
						if (!empty($categories)) : ?>
							<div class="rfs-ref-post-categories flex flex-wrap gap-2 mb-6">
								<?php foreach (array_slice($categories, 0, 3) as $category) : ?>
									<a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
									   class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide bg-ats-yellow text-ats-dark hover:bg-accent-yellow transition-colors">
										<?php echo esc_html($category->name); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<!-- Title -->
						<h1 class="rfs-ref-post-title text-3xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
							<?php the_title(); ?>
						</h1>

						<!-- Meta Info -->
						<div class="rfs-ref-post-meta flex flex-wrap items-center gap-4 md:gap-6 text-gray-300">
							<!-- Author -->
							<div class="rfs-ref-post-author flex items-center gap-2">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
								</svg>
								<span class="font-medium text-white"><?php the_author(); ?></span>
							</div>

							<span class="text-gray-400">•</span>

							<!-- Date -->
							<div class="rfs-ref-post-date flex items-center gap-2">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
								</svg>
								<time datetime="<?php echo get_the_date('c'); ?>" class="font-medium">
									<?php echo get_the_date('F j, Y'); ?>
								</time>
							</div>

							<?php
							$reading_time = ats_get_reading_time(get_the_content());
							if ($reading_time) : ?>
								<span class="text-gray-400">•</span>
								<div class="rfs-ref-post-reading-time flex items-center gap-2">
									<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
									</svg>
									<span class="font-medium"><?php echo esc_html($reading_time); ?> min read</span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		<?php else : ?>
			<!-- Fallback Header Without Image -->
			<div class="rfs-ref-post-header bg-gradient-to-br from-primary-500 via-primary-300 to-white border-b border-gray-200">
				<div class="container mx-auto px-4 py-16">
					<div class="max-w-4xl">
						<?php
						$categories = get_the_category();
						if (!empty($categories)) : ?>
							<div class="rfs-ref-post-categories flex flex-wrap gap-2 mb-6">
								<?php foreach (array_slice($categories, 0, 3) as $category) : ?>
									<a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
									   class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide bg-ats-yellow text-ats-dark hover:bg-accent-yellow transition-colors">
										<?php echo esc_html($category->name); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h1 class="rfs-ref-post-title text-3xl md:text-5xl font-bold text-ats-dark mb-6">
							<?php the_title(); ?>
						</h1>

						<div class="rfs-ref-post-meta flex flex-wrap items-center gap-4 md:gap-6 text-gray-600">
							<div class="rfs-ref-post-author flex items-center gap-2">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
								</svg>
								<span class="font-medium"><?php the_author(); ?></span>
							</div>
							<span class="text-gray-400">•</span>
							<div class="rfs-ref-post-date flex items-center gap-2">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
								</svg>
								<time datetime="<?php echo get_the_date('c'); ?>" class="font-medium">
									<?php echo get_the_date('F j, Y'); ?>
								</time>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Main Content Area -->
		<div class="rfs-ref-post-content-wrapper bg-white">
			<div class="container mx-auto px-4 py-12">
				<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

					<!-- Main Content Column -->
					<div class="lg:col-span-8 xl:col-span-8">
						<div class="rfs-ref-post-content prose prose-lg max-w-none">
							<?php the_content(); ?>
						</div>

						<!-- Tags -->
						<?php
						$tags = get_the_tags();
						if ($tags) : ?>
							<div class="rfs-ref-post-tags mt-12 pt-8 border-t border-gray-200">
								<div class="flex flex-wrap items-center gap-2">
									<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
									</svg>
									<?php foreach ($tags as $tag) : ?>
										<a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
										   class="inline-flex items-center px-3 py-1.5 rounded-full text-sm bg-gray-100 text-gray-700 hover:bg-ats-yellow hover:text-ats-dark transition-colors">
											<?php echo esc_html($tag->name); ?>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

						<!-- Author Bio -->
						<div class="rfs-ref-post-author-bio mt-12 bg-gray-50 rounded-lg p-8 border border-gray-200">
							<div class="flex gap-6">
								<div class="rfs-ref-author-avatar flex-shrink-0">
									<?php echo get_avatar(get_the_author_meta('ID'), 80, '', get_the_author(), array('class' => 'rounded-full')); ?>
								</div>
								<div class="rfs-ref-author-info flex-1">
									<h3 class="text-xl font-bold text-ats-dark mb-2">
										About <?php the_author(); ?>
									</h3>
									<div class="text-gray-600 prose prose-sm max-w-none">
										<?php echo get_the_author_meta('description') ?: 'This author has not added a bio yet.'; ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Post Navigation -->
						<div class="rfs-ref-post-navigation mt-12 pt-8 border-t border-gray-200">
							<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
								<?php
								$prev_post = get_previous_post();
								$next_post = get_next_post();
								?>

								<!-- Previous Post -->
								<?php if ($prev_post) : ?>
									<a href="<?php echo get_permalink($prev_post); ?>"
									   class="rfs-ref-nav-prev group flex flex-col p-6 bg-gray-50 rounded-lg border border-gray-200 hover:border-primary-500 hover:shadow-md transition-all">
										<span class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Previous Post</span>
										<span class="text-lg font-bold text-ats-dark group-hover:text-primary-700 transition-colors line-clamp-2">
											<?php echo get_the_title($prev_post); ?>
										</span>
									</a>
								<?php endif; ?>

								<!-- Next Post -->
								<?php if ($next_post) : ?>
									<a href="<?php echo get_permalink($next_post); ?>"
									   class="rfs-ref-nav-next group flex flex-col p-6 bg-gray-50 rounded-lg border border-gray-200 hover:border-primary-500 hover:shadow-md transition-all <?php echo !$prev_post ? 'md:col-start-2' : ''; ?>">
										<span class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Next Post</span>
										<span class="text-lg font-bold text-ats-dark group-hover:text-primary-700 transition-colors line-clamp-2">
											<?php echo get_the_title($next_post); ?>
										</span>
									</a>
								<?php endif; ?>
							</div>
						</div>

					</div>

					<!-- Sidebar Column -->
					<div class="lg:col-span-4 xl:col-span-4">
						<div class="rfs-ref-post-sidebar space-y-8 lg:sticky lg:top-8">

							<!-- Related Posts by Category -->
							<?php get_template_part('template-parts/sidebar', 'related-posts'); ?>

							<!-- Categories Widget -->
							<?php get_template_part('template-parts/sidebar', 'categories'); ?>

						</div>
					</div>

				</div>
			</div>
		</div>

	</article>

<?php endwhile; ?>

<?php get_footer(); ?>
