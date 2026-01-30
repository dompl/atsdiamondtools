<?php
/**
 * Contact Form AJAX Handler
 *
 * Handles contact form submissions with email notifications
 *
 * @package SkylineWP Dev Child
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX actions for contact form
 */
add_action('wp_ajax_ats_contact_form_submit', 'ats_handle_contact_form_submit');
add_action('wp_ajax_nopriv_ats_contact_form_submit', 'ats_handle_contact_form_submit');

/**
 * Handle contact form AJAX request
 *
 * @return void
 */
function ats_handle_contact_form_submit() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ats_contact_form')) {
        wp_send_json_error(
            array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'skylinewp-dev-child'),
            ),
            403
        );
    }

    // Validate required fields
    $required_fields = array(
        'name' => __('Name is required.', 'skylinewp-dev-child'),
        'email' => __('Email is required.', 'skylinewp-dev-child'),
        'message' => __('Message is required.', 'skylinewp-dev-child'),
        'recipient' => __('Recipient email is not configured.', 'skylinewp-dev-child'),
    );

    foreach ($required_fields as $field => $error_message) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            wp_send_json_error(
                array(
                    'message' => $error_message,
                ),
                400
            );
        }
    }

    // Sanitize input data
    $name = sanitize_text_field(wp_unslash($_POST['name']));
    $email = sanitize_email(wp_unslash($_POST['email']));
    $telephone = isset($_POST['telephone']) ? sanitize_text_field(wp_unslash($_POST['telephone'])) : '';
    $message = sanitize_textarea_field(wp_unslash($_POST['message']));
    $recipient = sanitize_email(wp_unslash($_POST['recipient']));
    $newsletter = isset($_POST['newsletter']) && $_POST['newsletter'] === 'true';
    $consent = isset($_POST['consent']) && $_POST['consent'] === 'true';

    // Validate email format
    if (!is_email($email)) {
        wp_send_json_error(
            array(
                'message' => __('Please provide a valid email address.', 'skylinewp-dev-child'),
            ),
            400
        );
    }

    if (!is_email($recipient)) {
        wp_send_json_error(
            array(
                'message' => __('Recipient email is not properly configured.', 'skylinewp-dev-child'),
            ),
            500
        );
    }

    // Validate consent
    if (!$consent) {
        wp_send_json_error(
            array(
                'message' => __('You must consent to data collection to submit this form.', 'skylinewp-dev-child'),
            ),
            400
        );
    }

    // Verify reCAPTCHA if enabled
    if (isset($_POST['recaptcha_response']) && !empty($_POST['recaptcha_response'])) {
        $recaptcha_response = sanitize_text_field(wp_unslash($_POST['recaptcha_response']));
        $recaptcha_secret = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '';

        if (empty($recaptcha_secret)) {
            error_log('ATS Contact Form: RECAPTCHA_SECRET_KEY is not defined in wp-config.php.');
            wp_send_json_error(
                array(
                    'message' => __('reCAPTCHA is not properly configured. Please contact the administrator.', 'skylinewp-dev-child'),
                ),
                500
            );
        }

        $verify_result = ats_verify_recaptcha($recaptcha_response, $recaptcha_secret);
        if (is_wp_error($verify_result)) {
            wp_send_json_error(
                array(
                    'message' => $verify_result->get_error_message(),
                ),
                400
            );
        }
    }

    // Prepare email content
    $subject = sprintf(
        '[%s] New Contact Form Submission from %s',
        get_bloginfo('name'),
        $name
    );

    $email_body = "New contact form submission received:\n\n";
    $email_body .= "Name: " . $name . "\n";
    $email_body .= "Email: " . $email . "\n";

    if (!empty($telephone)) {
        $email_body .= "Telephone: " . $telephone . "\n";
    }

    $email_body .= "\nMessage:\n" . $message . "\n\n";

    if ($newsletter) {
        $email_body .= "Newsletter subscription: Yes\n";
    }

    $email_body .= "\n---\n";
    $email_body .= "Submitted from: " . home_url($_SERVER['REQUEST_URI']) . "\n";
    $email_body .= "User IP: " . ats_get_user_ip() . "\n";
    $email_body .= "Date: " . current_time('mysql') . "\n";

    // Email headers
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
        'Reply-To: ' . $name . ' <' . $email . '>',
    );

    // Send email
    $sent = wp_mail($recipient, $subject, $email_body, $headers);

    if (!$sent) {
        error_log('ATS Contact Form: Failed to send email to ' . $recipient);
        wp_send_json_error(
            array(
                'message' => __('There was a problem sending your message. Please try again later or contact us directly.', 'skylinewp-dev-child'),
            ),
            500
        );
    }

    // If newsletter subscription was requested, add to newsletter
    if ($newsletter) {
        $api_key = defined('BREVO_API') ? BREVO_API : '';
        $list_id = get_field('ats_footer_brevo_list_id', 'option');

        if (!empty($api_key) && !empty($list_id)) {
            // Reuse the newsletter subscription function if it exists
            if (function_exists('ats_subscribe_to_brevo')) {
                $newsletter_result = ats_subscribe_to_brevo($email, $api_key, $list_id);
                if (is_wp_error($newsletter_result)) {
                    error_log('ATS Contact Form: Newsletter subscription failed - ' . $newsletter_result->get_error_message());
                }
            }
        }
    }

    // Log successful submission
    error_log(sprintf(
        'ATS Contact Form: Successful submission from %s (%s)',
        $name,
        $email
    ));

    wp_send_json_success(
        array(
            'message' => isset($_POST['success_message']) ? sanitize_text_field(wp_unslash($_POST['success_message'])) : __('Thank you! We\'ll get back to you soon.', 'skylinewp-dev-child'),
        )
    );
}

/**
 * Verify reCAPTCHA response
 *
 * @param string $response reCAPTCHA response token.
 * @param string $secret   reCAPTCHA secret key.
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function ats_verify_recaptcha($response, $secret) {
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';

    $args = array(
        'body' => array(
            'secret' => $secret,
            'response' => $response,
            'remoteip' => ats_get_user_ip(),
        ),
        'timeout' => 30,
    );

    $verify_response = wp_remote_post($verify_url, $args);

    if (is_wp_error($verify_response)) {
        return new WP_Error(
            'recaptcha_connection_error',
            __('Could not verify reCAPTCHA. Please try again later.', 'skylinewp-dev-child')
        );
    }

    $response_code = wp_remote_retrieve_response_code($verify_response);
    $response_body = wp_remote_retrieve_body($verify_response);
    $result = json_decode($response_body, true);

    if ($response_code !== 200) {
        error_log('ATS Contact Form: reCAPTCHA verification failed - HTTP ' . $response_code);
        return new WP_Error(
            'recaptcha_verification_failed',
            __('reCAPTCHA verification failed. Please try again.', 'skylinewp-dev-child')
        );
    }

    if (!isset($result['success']) || $result['success'] !== true) {
        $error_codes = isset($result['error-codes']) ? implode(', ', $result['error-codes']) : 'unknown';
        error_log('ATS Contact Form: reCAPTCHA verification failed - ' . $error_codes);
        return new WP_Error(
            'recaptcha_invalid',
            __('reCAPTCHA verification failed. Please try again.', 'skylinewp-dev-child')
        );
    }

    return true;
}

/**
 * Get user IP address
 *
 * @return string User IP address.
 */
function ats_get_user_ip() {
    $ip = '';

    // Check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IPs passing through proxies
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain multiple IPs, take the first one
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    // Standard remote address
    elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return filter_var(trim($ip), FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

/**
 * Add contact form nonce to themeData localization
 *
 * @param array $scripts_localize Existing localized data.
 * @return array Modified localized data.
 */
function ats_add_contact_form_nonce($scripts_localize) {
    $scripts_localize['contact_form_nonce'] = wp_create_nonce('ats_contact_form');
    return $scripts_localize;
}
add_filter('skyline_child_localizes', 'ats_add_contact_form_nonce');
