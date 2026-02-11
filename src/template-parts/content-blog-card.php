<?php
/**
 * Template part for displaying blog post cards
 *
 * @package skylinewp-dev-child
 */

defined('ABSPATH') || exit;

$post_id = get_the_ID();
$categories = get_the_category();
$post_date = get_the_date('F j, Y');
$reading_time = ats_get_reading_time(get_the_content());
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('rfs-ref-blog-card group'); ?>>
	<div class="rfs-ref-blog-card-inner bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">

		<div class="grid grid-cols-1 md:grid-cols-12 gap-0">

			<!-- Featured Image -->
			<?php if (has_post_thumbnail()) : ?>
				<div class="rfs-ref-blog-card-image md:col-span-5 relative overflow-hidden">
					<a href="<?php the_permalink(); ?>" class="block relative overflow-hidden aspect-[16/10] md:aspect-auto md:h-full">
						<img
							src="<?php echo wpimage(image: get_post_thumbnail_id(), size: [600, 400], retina: true, quality: 85); ?>"
							alt="<?php echo esc_attr(get_the_title()); ?>"
							class="rfs-ref-blog-card-img w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
							loading="lazy"
						>
						<div class="rfs-ref-blog-card-overlay absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					</a>

					<!-- Primary Category Badge -->
					<?php if (!empty($categories)) : ?>
						<div class="rfs-ref-blog-card-category absolute top-4 left-4 z-10">
							<a href="<?php echo esc_url(get_category_link($categories[0]->term_id)); ?>"
							   class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide bg-ats-yellow text-ats-dark hover:bg-accent-yellow transition-colors">
								<?php echo esc_html($categories[0]->name); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Content -->
			<div class="rfs-ref-blog-card-content <?php echo has_post_thumbnail() ? 'md:col-span-7' : 'md:col-span-12'; ?> p-6 md:p-8 flex flex-col">

				<!-- Meta Info -->
				<div class="rfs-ref-blog-card-meta flex items-center gap-4 text-sm text-gray-500 mb-4">
					<div class="rfs-ref-blog-card-date flex items-center gap-2">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
						</svg>
						<time datetime="<?php echo get_the_date('c'); ?>"><?php echo esc_html($post_date); ?></time>
					</div>
					<?php if ($reading_time) : ?>
						<span class="text-gray-300">•</span>
						<div class="rfs-ref-blog-card-reading-time flex items-center gap-2">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
							</svg>
							<span><?php echo esc_html($reading_time); ?> min read</span>
						</div>
					<?php endif; ?>
				</div>

				<!-- Title -->
				<h2 class="rfs-ref-blog-card-title text-2xl md:text-3xl font-bold text-ats-dark mb-4 group-hover:text-primary-700 transition-colors">
					<a href="<?php the_permalink(); ?>" class="hover:underline">
						<?php the_title(); ?>
					</a>
				</h2>

				<!-- Excerpt -->
				<div class="rfs-ref-blog-card-excerpt text-gray-600 mb-6 flex-grow prose prose-sm max-w-none">
					<?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?>
				</div>

				<!-- Read More Button -->
				<div class="rfs-ref-blog-card-footer mt-auto">
					<a href="<?php the_permalink(); ?>"
					   class="rfs-ref-blog-card-link inline-flex items-center gap-2 text-primary-700 hover:text-primary-900 font-semibold group/link transition-colors">
						<span>Read More</span>
						<svg class="w-5 h-5 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
						</svg>
					</a>
				</div>

			</div>

		</div>

	</div>
</article>
