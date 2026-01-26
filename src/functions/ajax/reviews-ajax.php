<?php
/**
 * Reviews AJAX Handlers
 *
 * Handles AJAX pagination and form submission for product reviews
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register AJAX actions for reviews
 */
add_action( 'wp_ajax_ats_load_reviews', 'ats_handle_load_reviews' );
add_action( 'wp_ajax_nopriv_ats_load_reviews', 'ats_handle_load_reviews' );
add_action( 'wp_ajax_ats_submit_review', 'ats_handle_submit_review' );
add_action( 'wp_ajax_nopriv_ats_submit_review', 'ats_handle_submit_review' );

/**
 * Handle AJAX request to load reviews (pagination)
 *
 * @return void
 */
function ats_handle_load_reviews() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_reviews_nonce' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed.', 'skylinewp-dev-child' ),
            ),
            403
        );
    }

    // Get product ID and page number
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

    if ( ! $product_id ) {
        wp_send_json_error(
            array(
                'message' => __( 'Invalid product ID.', 'skylinewp-dev-child' ),
            ),
            400
        );
    }

    // Get product
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error(
            array(
                'message' => __( 'Product not found.', 'skylinewp-dev-child' ),
            ),
            404
        );
    }

    // Set up comment query
    $comments_per_page = 5;
    $offset = ( $page - 1 ) * $comments_per_page;

    $args = array(
        'post_id' => $product_id,
        'status'  => 'approve',
        'type'    => 'review',
        'number'  => $comments_per_page,
        'offset'  => $offset,
        'orderby' => 'comment_date_gmt',
        'order'   => 'DESC',
    );

    $comments = get_comments( $args );

    if ( empty( $comments ) ) {
        wp_send_json_success(
            array(
                'html' => '<p class="text-gray-500 text-center py-4">' . __( 'No reviews found.', 'skylinewp-dev-child' ) . '</p>',
            )
        );
    }

    // Generate HTML for reviews
    ob_start();
    wp_list_comments(
        array(
            'callback' => 'woocommerce_comments',
            'style'    => 'ol',
        ),
        $comments
    );
    $html = ob_get_clean();

    wp_send_json_success(
        array(
            'html' => $html,
            'page' => $page,
        )
    );
}

/**
 * Handle AJAX review submission
 *
 * @return void
 */
function ats_handle_submit_review() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_reviews_nonce' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed.', 'skylinewp-dev-child' ),
            ),
            403
        );
    }

    // Get form data
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $rating = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
    $comment = isset( $_POST['comment'] ) ? wp_kses_post( wp_unslash( $_POST['comment'] ) ) : '';
    $author = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '';
    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

    // Validate required fields
    if ( ! $product_id ) {
        wp_send_json_error(
            array(
                'field' => 'general',
                'message' => __( 'Invalid product.', 'skylinewp-dev-child' ),
            ),
            400
        );
    }

    if ( ! $comment ) {
        wp_send_json_error(
            array(
                'field' => 'comment',
                'message' => __( 'Please enter your review.', 'skylinewp-dev-child' ),
            ),
            400
        );
    }

    if ( wc_review_ratings_required() && ! $rating ) {
        wp_send_json_error(
            array(
                'field' => 'rating',
                'message' => __( 'Please select a rating.', 'skylinewp-dev-child' ),
            ),
            400
        );
    }

    // Check if user is logged in
    $user = wp_get_current_user();

    // If not logged in, validate name and email
    if ( ! $user->ID ) {
        $name_email_required = (bool) get_option( 'require_name_email', 1 );

        if ( $name_email_required ) {
            if ( ! $author ) {
                wp_send_json_error(
                    array(
                        'field' => 'author',
                        'message' => __( 'Please enter your name.', 'skylinewp-dev-child' ),
                    ),
                    400
                );
            }

            if ( ! $email ) {
                wp_send_json_error(
                    array(
                        'field' => 'email',
                        'message' => __( 'Please enter your email.', 'skylinewp-dev-child' ),
                    ),
                    400
                );
            }

            if ( ! is_email( $email ) ) {
                wp_send_json_error(
                    array(
                        'field' => 'email',
                        'message' => __( 'Please enter a valid email address.', 'skylinewp-dev-child' ),
                    ),
                    400
                );
            }
        }
    }

    // Prepare comment data
    $comment_data = array(
        'comment_post_ID'      => $product_id,
        'comment_content'      => $comment,
        'comment_type'         => 'review',
        'comment_parent'       => 0,
        'comment_approved'     => 0, // Will be moderated
    );

    if ( $user->ID ) {
        $comment_data['user_id'] = $user->ID;
        $comment_data['comment_author'] = $user->display_name;
        $comment_data['comment_author_email'] = $user->user_email;
    } else {
        $comment_data['comment_author'] = $author;
        $comment_data['comment_author_email'] = $email;
    }

    // Insert comment
    $comment_id = wp_insert_comment( $comment_data );

    if ( ! $comment_id ) {
        wp_send_json_error(
            array(
                'field' => 'general',
                'message' => __( 'Failed to submit review. Please try again.', 'skylinewp-dev-child' ),
            ),
            500
        );
    }

    // Add rating meta
    if ( $rating ) {
        update_comment_meta( $comment_id, 'rating', $rating );
    }

    // Check if comment needs moderation
    $is_approved = wp_get_comment_status( $comment_id ) === 'approved';

    wp_send_json_success(
        array(
            'message' => $is_approved
                ? __( 'Your review has been submitted successfully!', 'skylinewp-dev-child' )
                : __( 'Your review has been submitted and is awaiting moderation.', 'skylinewp-dev-child' ),
            'approved' => $is_approved,
        )
    );
}

/**
 * Add reviews nonce to themeData localization
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_reviews_nonce( $scripts_localize ) {
    $scripts_localize['reviews_nonce'] = wp_create_nonce( 'ats_reviews_nonce' );
    return $scripts_localize;
}
add_filter( 'skyline_child_localizes', 'ats_add_reviews_nonce' );
