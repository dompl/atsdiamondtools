<?php
/**
 * Page Content Variable System
 *
 * Allows using %page_content% in any ACF field to pull the main WordPress page content
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render raw post content for a %page_content% replacement.
 *
 * The parent theme appends the flexible content blocks to `the_content`, and
 * the blocks themselves are what call this helper, so running the plain
 * `the_content` filter here rendered the page three times over (Terms page,
 * September 2026). Run the normal filters with the block appender removed
 * and refuse to re-enter while a replacement is already in progress.
 *
 * @param string $raw Raw post_content.
 * @return string Rendered HTML.
 */
function ats_render_page_content_for_variable( $raw ) {
	static $rendering = false;
	if ( $rendering ) {
		return '';
	}
	$rendering = true;

	$appender = array( 'SkylineWPFlexibleContent', 'append_content_blocks_to_content' );
	$had      = has_filter( 'the_content', $appender );
	if ( $had ) {
		remove_filter( 'the_content', $appender, $had );
	}
	remove_filter( 'the_content', 'ats_suppress_raw_content_when_used_by_block', 1 );

	$html = apply_filters( 'the_content', $raw );

	add_filter( 'the_content', 'ats_suppress_raw_content_when_used_by_block', 1 );
	if ( $had ) {
		add_filter( 'the_content', $appender, $had );
	}

	$rendering = false;
	return $html;
}

/**
 * Whether any flexible content block on a post pulls in %page_content%.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function ats_post_uses_page_content_variable( $post_id ) {
	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}
	global $wpdb;
	$found = (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'content\\_blocks\\_%%' AND meta_value LIKE %s LIMIT 1",
			$post_id,
			'%' . $wpdb->esc_like( '%page_content%' ) . '%'
		)
	);
	$cache[ $post_id ] = $found;
	return $found;
}

/**
 * When a block on the page renders %page_content%, the main loop must not
 * print the raw content a second time above the blocks.
 *
 * @param string $content Post content.
 * @return string
 */
function ats_suppress_raw_content_when_used_by_block( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ats_post_uses_page_content_variable( get_the_ID() ) ) {
		return '';
	}
	return $content;
}
add_filter( 'the_content', 'ats_suppress_raw_content_when_used_by_block', 1 );

/**
 * Process ACF field values and replace %page_content% with actual page content
 *
 * @param mixed  $value   The field value
 * @param mixed  $post_id The post ID where the value was loaded from
 * @param array  $field   The field array containing all settings
 * @return mixed The processed value
 */
function ats_process_page_content_variable( $value, $post_id, $field ) {
	// Only process string values
	if ( ! is_string( $value ) || empty( $value ) ) {
		return $value;
	}

	// Check if the value contains the page_content variable
	if ( strpos( $value, '%page_content%' ) === false ) {
		return $value;
	}

	// Get the current post/page content
	$page_content = '';

	// If we have a post ID, get that post's content
	if ( $post_id && is_numeric( $post_id ) ) {
		$post = get_post( $post_id );
		if ( $post && ! empty( $post->post_content ) ) {
			$page_content = $post->post_content;
		}
	}

	// If no content yet, try to get from current global post
	if ( empty( $page_content ) ) {
		global $post;
		if ( isset( $post ) && ! empty( $post->post_content ) ) {
			$page_content = $post->post_content;
		}
	}

	// If we found content, apply WordPress content filters
	if ( ! empty( $page_content ) ) {
		// Apply the_content filters to process shortcodes, embeds, etc.
		$page_content = ats_render_page_content_for_variable( $page_content );

		// Remove any wrapping <p> tags if the entire value is just the variable
		if ( trim( $value ) === '%page_content%' ) {
			$page_content = trim( $page_content );
		}
	}

	// Replace the variable with the actual content
	$value = str_replace( '%page_content%', $page_content, $value );

	return $value;
}
add_filter( 'acf/format_value', 'ats_process_page_content_variable', 10, 3 );

/**
 * Also process flexible content layouts
 * This ensures the variable works in flexible content fields
 */
function ats_process_flexible_content_variable( $value, $post_id, $field ) {
	// Only process flexible content fields
	if ( ! isset( $field['type'] ) || $field['type'] !== 'flexible_content' ) {
		return $value;
	}

	// If value is an array (flexible content layouts), process each layout's fields
	if ( is_array( $value ) ) {
		foreach ( $value as $layout_key => $layout ) {
			if ( is_array( $layout ) ) {
				foreach ( $layout as $field_key => $field_value ) {
					if ( is_string( $field_value ) && strpos( $field_value, '%page_content%' ) !== false ) {
						// Get page content
						$page_content = '';
						if ( $post_id && is_numeric( $post_id ) ) {
							$post = get_post( $post_id );
							if ( $post && ! empty( $post->post_content ) ) {
								$page_content = ats_render_page_content_for_variable( $post->post_content );
							}
						}

						if ( empty( $page_content ) ) {
							global $post;
							if ( isset( $post ) && ! empty( $post->post_content ) ) {
								$page_content = ats_render_page_content_for_variable( $post->post_content );
							}
						}

						// Replace the variable
						$value[ $layout_key ][ $field_key ] = str_replace( '%page_content%', $page_content, $field_value );
					}
				}
			}
		}
	}

	return $value;
}
add_filter( 'acf/format_value/type=flexible_content', 'ats_process_flexible_content_variable', 10, 3 );

/**
 * Process WYSIWYG and textarea fields specifically
 * This provides additional coverage for rich text fields
 */
function ats_process_wysiwyg_variable( $value, $post_id, $field ) {
	// Only process WYSIWYG and textarea fields
	if ( ! isset( $field['type'] ) || ! in_array( $field['type'], array( 'wysiwyg', 'textarea', 'text' ), true ) ) {
		return $value;
	}

	return ats_process_page_content_variable( $value, $post_id, $field );
}
add_filter( 'acf/format_value/type=wysiwyg', 'ats_process_wysiwyg_variable', 10, 3 );
add_filter( 'acf/format_value/type=textarea', 'ats_process_wysiwyg_variable', 10, 3 );
add_filter( 'acf/format_value/type=text', 'ats_process_wysiwyg_variable', 10, 3 );
