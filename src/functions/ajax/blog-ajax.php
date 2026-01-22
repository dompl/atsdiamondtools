<?php
/**
 * Blog Ajax Handlers
 *
 * @package skylinewp-dev-child
 */

defined('ABSPATH') || exit;

/**
 * Load blog posts via Ajax
 */
function ats_ajax_load_blog_posts() {
	// Verify nonce
	check_ajax_referer('ats_blog_nonce', 'nonce');

	// Get parameters
	$paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
	$category = isset($_POST['category']) ? absint($_POST['category']) : 0;
	$posts_per_page = isset($_POST['posts_per_page']) ? absint($_POST['posts_per_page']) : get_option('posts_per_page', 10);

	// Build query args
	$args = array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'posts_per_page' => $posts_per_page,
		'paged' => $paged,
		'orderby' => 'date',
		'order' => 'DESC',
	);

	// Add category filter if specified
	if ($category > 0) {
		$args['cat'] = $category;
	}

	// Execute query
	$query = new WP_Query($args);

	// Prepare response
	$response = array(
		'success' => false,
		'posts' => '',
		'pagination' => '',
		'found_posts' => 0,
		'max_pages' => 0,
	);

	if ($query->have_posts()) {
		ob_start();

		while ($query->have_posts()) {
			$query->the_post();
			get_template_part('template-parts/content', 'blog-card');
		}

		$response['posts'] = ob_get_clean();
		$response['found_posts'] = $query->found_posts;
		$response['max_pages'] = $query->max_num_pages;

		// Generate pagination
		if ($query->max_num_pages > 1) {
			$big = 999999999;

			ob_start();

			echo paginate_links(array(
				'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
				'format' => '?paged=%#%',
				'current' => $paged,
				'total' => $query->max_num_pages,
				'prev_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>',
				'next_text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
				'before_page_number' => '<span class="sr-only">Page </span>',
				'type' => 'list',
				'end_size' => 2,
				'mid_size' => 2,
			));

			$response['pagination'] = ob_get_clean();
		}

		$response['success'] = true;
	}

	wp_reset_postdata();

	wp_send_json($response);
}

add_action('wp_ajax_ats_load_blog_posts', 'ats_ajax_load_blog_posts');
add_action('wp_ajax_nopriv_ats_load_blog_posts', 'ats_ajax_load_blog_posts');

/**
 * Add blog nonce to localized script data
 *
 * @param array $scripts_localize Existing localized data
 * @return array Modified localized data
 */
function ats_add_blog_nonce($scripts_localize) {
	$scripts_localize['blog_nonce'] = wp_create_nonce('ats_blog_nonce');
	return $scripts_localize;
}
add_filter('skyline_child_localizes', 'ats_add_blog_nonce');
