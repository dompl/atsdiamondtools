<?php
/**
 * Template part for blog categories sidebar
 *
 * @package skylinewp-dev-child
 */

defined('ABSPATH') || exit;

$categories = get_categories(array(
	'orderby' => 'count',
	'order' => 'DESC',
	'hide_empty' => true,
	'number' => 10,
));

if (empty($categories)) {
	return;
}
?>

<div class="rfs-ref-sidebar-categories bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
	<!-- Widget Header -->
	<div class="rfs-ref-sidebar-categories-header flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
		<div class="rfs-ref-sidebar-icon-wrapper bg-primary-500 rounded-lg p-2">
			<svg class="w-5 h-5 text-primary-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
			</svg>
		</div>
		<h3 class="rfs-ref-sidebar-title text-xl font-bold text-ats-dark">Categories</h3>
	</div>

	<!-- Categories List -->
	<ul class="rfs-ref-categories-list space-y-2">
		<?php foreach ($categories as $category) : ?>
			<li class="rfs-ref-category-item">
				<a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
				   class="rfs-ref-category-link group flex items-center justify-between py-2 px-3 rounded-lg hover:bg-primary-500 transition-colors <?php echo is_category($category->term_id) ? 'bg-primary-500 text-primary-900' : 'text-gray-700'; ?>">
					<span class="rfs-ref-category-name font-medium group-hover:text-primary-900 transition-colors">
						<?php echo esc_html($category->name); ?>
					</span>
					<span class="rfs-ref-category-count inline-flex items-center justify-center min-w-[28px] h-7 px-2 rounded-full text-xs font-bold bg-gray-100 text-gray-700 group-hover:bg-ats-yellow group-hover:text-ats-dark transition-colors">
						<?php echo esc_html($category->count); ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<!-- View All Link -->
	<?php if (count($categories) >= 10) : ?>
		<div class="rfs-ref-view-all-categories mt-6 pt-4 border-t border-gray-200">
			<a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>"
			   class="rfs-ref-view-all-link inline-flex items-center gap-2 text-sm font-semibold text-primary-700 hover:text-primary-900 transition-colors">
				<span>View All Categories</span>
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
				</svg>
			</a>
		</div>
	<?php endif; ?>
</div>
