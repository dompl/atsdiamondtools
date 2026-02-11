<?php
/**
 * Newsletter Subscription AJAX Handler
 *
 * Handles newsletter subscription via Brevo API
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register AJAX actions for newsletter subscription
 */
add_action( 'wp_ajax_ats_newsletter_subscribe', 'ats_handle_newsletter_subscribe' );
add_action( 'wp_ajax_nopriv_ats_newsletter_subscribe', 'ats_handle_newsletter_subscribe' );

/**
 * Handle newsletter subscription AJAX request
 *
 * @return void
 */
function ats_handle_newsletter_subscribe() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_newsletter_subscribe' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'skylinewp-dev-child' ),
            ),
            403
        );
    }

    // Validate email
    if ( ! isset( $_POST['email'] ) || empty( $_POST['email'] ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Please provide an email address.', 'skylinewp-dev-child' ),
            ),
            400
        );
    }

    $email = sanitize_email( wp_unslash( $_POST['email'] ) );

    if ( ! is_email( $email ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Please provide a valid email address.', 'skylinewp-dev-child' ),
            ),
            400
        );
    }

    // Get Brevo API key from wp-config.php constant
    $api_key = defined( 'BREVO_API' ) ? BREVO_API : '';

    // Get Brevo List ID from ACF options
    $list_id = get_field( 'ats_footer_brevo_list_id', 'option' );

    if ( empty( $api_key ) ) {
        error_log( 'ATS Newsletter: BREVO_API constant is not defined in wp-config.php.' );
        wp_send_json_error(
            array(
                'message' => __( 'Newsletter service is not properly configured. Please contact the administrator.', 'skylinewp-dev-child' ),
            ),
            500
        );
    }

    if ( empty( $list_id ) ) {
        error_log( 'ATS Newsletter: Brevo List ID is not configured in Footer Settings.' );
        wp_send_json_error(
            array(
                'message' => __( 'Newsletter service is not properly configured. Please contact the administrator.', 'skylinewp-dev-child' ),
            ),
            500
        );
    }

    // Make request to Brevo API
    $result = ats_subscribe_to_brevo( $email, $api_key, $list_id );

    if ( is_wp_error( $result ) ) {
        error_log( 'ATS Newsletter Error: ' . $result->get_error_message() );
        wp_send_json_error(
            array(
                'message' => $result->get_error_message(),
            ),
            500
        );
    }

    // Get success message from ACF options
    $success_message = get_field( 'ats_footer_newsletter_success_message', 'option' );
    if ( empty( $success_message ) ) {
        $success_message = __( 'Thank you for subscribing to our newsletter!', 'skylinewp-dev-child' );
    }

    wp_send_json_success(
        array(
            'message' => wp_kses_post( $success_message ),
        )
    );
}

/**
 * Subscribe email to Brevo list
 *
 * @param string $email   Email address to subscribe.
 * @param string $api_key Brevo API key.
 * @param int    $list_id Brevo list ID.
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function ats_subscribe_to_brevo( $email, $api_key, $list_id ) {
    $api_url = 'https://api.brevo.com/v3/contacts';

    $body = array(
        'email'            => $email,
        'listIds'          => array( (int) $list_id ),
        'updateEnabled'    => true,
    );

    $args = array(
        'method'  => 'POST',
        'headers' => array(
            'accept'       => 'application/json',
            'content-type' => 'application/json',
            'api-key'      => $api_key,
        ),
        'body'    => wp_json_encode( $body ),
        'timeout' => 30,
    );

    $response = wp_remote_post( $api_url, $args );

    if ( is_wp_error( $response ) ) {
        return new WP_Error(
            'brevo_connection_error',
            __( 'Could not connect to the newsletter service. Please try again later.', 'skylinewp-dev-child' )
        );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $response_data = json_decode( $response_body, true );

    // Success codes: 201 (created), 204 (updated)
    if ( in_array( $response_code, array( 201, 204 ), true ) ) {
        return true;
    }

    // Handle specific error codes
    if ( $response_code === 401 ) {
        error_log( 'ATS Newsletter: Brevo API returned 401 Unauthorized. Server IP may need to be whitelisted at https://app.brevo.com/security/authorised_ips' );
        return new WP_Error(
            'brevo_unauthorized',
            __( 'Newsletter service authorization failed. Please contact the site administrator.', 'skylinewp-dev-child' )
        );
    }

    if ( $response_code === 400 && isset( $response_data['code'] ) ) {
        switch ( $response_data['code'] ) {
            case 'duplicate_parameter':
                // Contact already exists, which is fine
                return true;

            case 'invalid_parameter':
                return new WP_Error(
                    'brevo_invalid_email',
                    __( 'The email address appears to be invalid. Please check and try again.', 'skylinewp-dev-child' )
                );

            default:
                return new WP_Error(
                    'brevo_api_error',
                    isset( $response_data['message'] )
                        ? sanitize_text_field( $response_data['message'] )
                        : __( 'An error occurred while subscribing. Please try again.', 'skylinewp-dev-child' )
                );
        }
    }

    // Log unexpected errors
    error_log(
        sprintf(
            'ATS Newsletter - Brevo API Error: Code %d, Response: %s',
            $response_code,
            $response_body
        )
    );

    return new WP_Error(
        'brevo_unexpected_error',
        __( 'An unexpected error occurred. Please try again later.', 'skylinewp-dev-child' )
    );
}

/**
 * Add newsletter nonce to themeData localization
 * Newsletter JS is bundled into bundle.js (child-bundle handle)
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_newsletter_nonce( $scripts_localize ) {
    $scripts_localize['newsletter_nonce'] = wp_create_nonce( 'ats_newsletter_subscribe' );
    return $scripts_localize;
}
add_filter( 'skyline_child_localizes', 'ats_add_newsletter_nonce' );
