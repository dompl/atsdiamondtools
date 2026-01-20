<?php
/**
 * WooCommerce Account Functions
 * Handles avatar uploads and other account-related functionality
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle avatar upload via AJAX
 */
add_action( 'wp_ajax_upload_user_avatar', 'handle_user_avatar_upload' );
function handle_user_avatar_upload() {
    // Verify nonce
    if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'wc_account_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    // Check if user is logged in
    if ( !is_user_logged_in() ) {
        wp_send_json_error( 'User not logged in' );
    }

    // Check if file was uploaded
    if ( !isset( $_FILES['avatar_file'] ) || $_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK ) {
        wp_send_json_error( 'No file uploaded or upload error' );
    }

    $file = $_FILES['avatar_file'];

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if ( !in_array( $file['type'], $allowed_types ) ) {
        wp_send_json_error( 'Invalid file type. Only JPG, PNG, and GIF are allowed.' );
    }

    // Validate file size (2MB max)
    if ( $file['size'] > 2 * 1024 * 1024 ) {
        wp_send_json_error( 'File size exceeds 2MB limit' );
    }

    // Upload file
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    $user_id = get_current_user_id();

    // Handle the upload
    $uploaded = wp_handle_upload( $file, ['test_form' => false] );

    if ( isset( $uploaded['error'] ) ) {
        wp_send_json_error( $uploaded['error'] );
    }

    // Create attachment
    $attachment = [
        'post_mime_type' => $uploaded['type'],
        'post_title' => sanitize_file_name( $file['name'] ),
        'post_content' => '',
        'post_status' => 'inherit'
    ];

    $attachment_id = wp_insert_attachment( $attachment, $uploaded['file'] );

    if ( is_wp_error( $attachment_id ) ) {
        wp_send_json_error( 'Failed to create attachment' );
    }

    // Generate attachment metadata
    $attachment_data = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
    wp_update_attachment_metadata( $attachment_id, $attachment_data );

    // Update user meta
    update_user_meta( $user_id, 'user_avatar', $attachment_id );

    wp_send_json_success([
        'attachment_id' => $attachment_id,
        'url' => wp_get_attachment_url( $attachment_id )
    ]);
}

/**
 * Use custom avatar if available
 */
add_filter( 'get_avatar_url', 'use_custom_avatar_url', 99, 3 );
function use_custom_avatar_url( $url, $id_or_email, $args ) {
    $user_id = 0;

    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
        $user_id = $user ? $user->ID : 0;
    } elseif ( is_a( $id_or_email, 'WP_User' ) ) {
        $user_id = $id_or_email->ID;
    } elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
        $user_id = (int) $id_or_email->user_id;
    }

    if ( !empty( $user_id ) ) {
        $avatar_id = get_user_meta( $user_id, 'user_avatar', true );
        if ( $avatar_id ) {
            $avatar_url = wp_get_attachment_url( $avatar_id );
            if ( $avatar_url ) {
                return $avatar_url;
            }
        }
    }

    return $url;
}

/**
 * Filter pre_get_avatar_data to inject custom avatar url early
 */
add_filter( 'pre_get_avatar_data', 'use_custom_avatar_data', 99, 2 );
function use_custom_avatar_data( $args, $id_or_email ) {
    if ( ! empty( $args['url'] ) ) {
        return $args;
    }

    $url = use_custom_avatar_url( '', $id_or_email, $args );

    if ( ! empty( $url ) ) {
        $args['url'] = $url;
    }

    return $args;
}


/**
 * Redirect /my-account/dashboard/ to /dashboard/
 */
add_action( 'template_redirect', function() {
    if ( is_account_page() && is_wc_endpoint_url( 'dashboard' ) ) {
        wp_safe_redirect( home_url( '/dashboard/' ) );
        exit;
    }
} );
