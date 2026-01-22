<?php
/**
 * Blog Helper Functions
 *
 * @package skylinewp-dev-child
 */

defined('ABSPATH') || exit;

/**
 * Calculate reading time for post content
 *
 * @param string $content Post content
 * @param int $wpm Words per minute (default: 200)
 * @return int Reading time in minutes
 */
function ats_get_reading_time($content, $wpm = 200) {
	if (empty($content)) {
		return 0;
	}

	// Strip all HTML tags and shortcodes
	$content = strip_tags(strip_shortcodes($content));

	// Count words
	$word_count = str_word_count($content);

	// Calculate reading time
	$reading_time = ceil($word_count / $wpm);

	return max(1, $reading_time); // Minimum 1 minute
}

/**
 * Get post view count
 *
 * @param int $post_id Post ID
 * @return int View count
 */
function ats_get_post_views($post_id) {
	$count = get_post_meta($post_id, 'ats_post_views', true);
	return $count ? intval($count) : 0;
}

/**
 * Increment post view count
 *
 * @param int $post_id Post ID
 * @return void
 */
function ats_increment_post_views($post_id) {
	if (!is_singular('post')) {
		return;
	}

	// Don't count views from bots or logged-in admins
	if (is_user_logged_in() && current_user_can('manage_options')) {
		return;
	}

	$count = ats_get_post_views($post_id);
	$count++;

	update_post_meta($post_id, 'ats_post_views', $count);
}

// Track post views on single post pages
add_action('wp_head', function() {
	if (is_singular('post')) {
		ats_increment_post_views(get_the_ID());
	}
});
