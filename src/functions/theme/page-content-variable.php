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
		$page_content = apply_filters( 'the_content', $page_content );

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
								$page_content = apply_filters( 'the_content', $post->post_content );
							}
						}

						if ( empty( $page_content ) ) {
							global $post;
							if ( isset( $post ) && ! empty( $post->post_content ) ) {
								$page_content = apply_filters( 'the_content', $post->post_content );
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
