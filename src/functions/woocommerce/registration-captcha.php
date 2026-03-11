<?php
/**
 * Registration Anti-Spam Protection
 *
 * Adds Google reCAPTCHA v3 (invisible), honeypot field, and timestamp
 * validation to the WooCommerce registration form to prevent bot signups.
 *
 * Keys are defined in wp-config.php:
 *   ATS_RECAPTCHA_SITE_KEY
 *   ATS_RECAPTCHA_SECRET_KEY
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue reCAPTCHA v3 script on the my-account page.
 */
add_action( 'wp_enqueue_scripts', 'ats_enqueue_recaptcha' );
function ats_enqueue_recaptcha() {
    if ( ! is_account_page() || is_user_logged_in() ) {
        return;
    }

    if ( ! defined( 'ATS_RECAPTCHA_SITE_KEY' ) || empty( ATS_RECAPTCHA_SITE_KEY ) ) {
        return;
    }

    wp_enqueue_script(
        'google-recaptcha-v3',
        'https://www.google.com/recaptcha/api.js?render=' . ATS_RECAPTCHA_SITE_KEY,
        [],
        null,
        true
    );

    wp_add_inline_script( 'google-recaptcha-v3', ats_get_recaptcha_inline_js() );
}

/**
 * Return the inline JS that executes reCAPTCHA on form submit.
 */
function ats_get_recaptcha_inline_js() {
    $site_key = ATS_RECAPTCHA_SITE_KEY;

    return <<<JS
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form.register, form[class*="register"]');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        var tokenField = form.querySelector('input[name="ats_recaptcha_token"]');
        if (tokenField && tokenField.value) return;

        e.preventDefault();

        grecaptcha.ready(function() {
            grecaptcha.execute('{$site_key}', { action: 'register' }).then(function(token) {
                if (!tokenField) {
                    tokenField = document.createElement('input');
                    tokenField.type = 'hidden';
                    tokenField.name = 'ats_recaptcha_token';
                    form.appendChild(tokenField);
                }
                tokenField.value = token;

                // form.submit() omits the submit button name/value,
                // so we inject a hidden "register" field for WooCommerce.
                if (!form.querySelector('input[type="hidden"][name="register"]')) {
                    var regInput = document.createElement('input');
                    regInput.type = 'hidden';
                    regInput.name = 'register';
                    regInput.value = 'Register';
                    form.appendChild(regInput);
                }
                form.submit();
            });
        });
    });
});
JS;
}

/**
 * Render honeypot, timestamp, and reCAPTCHA hidden token field in the registration form.
 */
add_action( 'woocommerce_register_form', 'ats_render_registration_captcha', 15 );
function ats_render_registration_captcha() {
    $ts      = time();
    $ts_hash = wp_hash( $ts . 'ats_registration_timestamp' );
    ?>
    <!-- Honeypot — invisible to real users -->
    <div style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;" aria-hidden="true">
        <label for="ats_website_url">Website</label>
        <input type="text" name="ats_website_url" id="ats_website_url" value="" tabindex="-1" autocomplete="off" />
    </div>

    <!-- Signed timestamp -->
    <input type="hidden" name="ats_reg_ts" value="<?php echo esc_attr( $ts ); ?>" />
    <input type="hidden" name="ats_reg_ts_hash" value="<?php echo esc_attr( $ts_hash ); ?>" />

    <!-- reCAPTCHA v3 token (populated by JS on submit) -->
    <input type="hidden" name="ats_recaptcha_token" value="" />
    <?php
}

/**
 * Validate honeypot, timestamp, and reCAPTCHA v3 on registration.
 */
add_filter( 'woocommerce_registration_errors', 'ats_validate_registration_captcha', 10, 3 );
function ats_validate_registration_captcha( $errors, $username, $email ) {
    // 1. Honeypot check — if filled, it's a bot
    if ( ! empty( $_POST['ats_website_url'] ) ) {
        $errors->add( 'spam_detected', __( 'Registration failed. Please try again.', 'woocommerce' ) );
        return $errors;
    }

    // 2. Timestamp check — signed to prevent tampering
    $ts      = isset( $_POST['ats_reg_ts'] ) ? intval( $_POST['ats_reg_ts'] ) : 0;
    $ts_hash = isset( $_POST['ats_reg_ts_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['ats_reg_ts_hash'] ) ) : '';

    if ( wp_hash( $ts . 'ats_registration_timestamp' ) !== $ts_hash ) {
        $errors->add( 'timestamp_invalid', __( 'Registration failed. Please refresh the page and try again.', 'woocommerce' ) );
        return $errors;
    }

    if ( ( time() - $ts ) < 3 ) {
        $errors->add( 'too_fast', __( 'Registration failed. Please wait a moment and try again.', 'woocommerce' ) );
        return $errors;
    }

    if ( ( time() - $ts ) > 600 ) {
        $errors->add( 'form_expired', __( 'The form has expired. Please refresh the page and try again.', 'woocommerce' ) );
        return $errors;
    }

    // 3. reCAPTCHA v3 verification
    if ( ! defined( 'ATS_RECAPTCHA_SECRET_KEY' ) || empty( ATS_RECAPTCHA_SECRET_KEY ) ) {
        return $errors; // Skip if keys not configured
    }

    $token = isset( $_POST['ats_recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['ats_recaptcha_token'] ) ) : '';

    if ( empty( $token ) ) {
        $errors->add( 'recaptcha_missing', __( 'Security verification failed. Please ensure JavaScript is enabled and try again.', 'woocommerce' ) );
        return $errors;
    }

    $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'timeout' => 10,
        'body'    => [
            'secret'   => ATS_RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ],
    ] );

    if ( is_wp_error( $response ) ) {
        // Don't block registration if Google is unreachable — honeypot + timestamp still protect
        error_log( 'ATS reCAPTCHA: verification request failed — ' . $response->get_error_message() );
        return $errors;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['success'] ) ) {
        $error_codes = ! empty( $body['error-codes'] ) ? implode( ', ', $body['error-codes'] ) : 'unknown';
        error_log( 'ATS reCAPTCHA: verification failed — ' . $error_codes );
        $errors->add( 'recaptcha_failed', __( 'Security verification failed. Please try again.', 'woocommerce' ) );
        return $errors;
    }

    // Check the action matches what we expect
    if ( ! isset( $body['action'] ) || $body['action'] !== 'register' ) {
        $errors->add( 'recaptcha_action', __( 'Security verification failed. Please try again.', 'woocommerce' ) );
        return $errors;
    }

    // Score threshold — 0.5 is Google's recommended default. Lower = more likely a bot.
    $score = isset( $body['score'] ) ? (float) $body['score'] : 0.0;
    if ( $score < 0.5 ) {
        error_log( sprintf( 'ATS reCAPTCHA: low score %.1f for %s', $score, $email ) );
        $errors->add( 'recaptcha_score', __( 'Registration blocked for security reasons. Please contact us if you believe this is an error.', 'woocommerce' ) );
        return $errors;
    }

    return $errors;
}

/**
 * Hide the reCAPTCHA badge with CSS (Google allows this if you include attribution text).
 * Attribution is already covered by the privacy policy link in the form.
 */
add_action( 'wp_head', 'ats_recaptcha_hide_badge' );
function ats_recaptcha_hide_badge() {
    if ( ! is_account_page() || is_user_logged_in() ) {
        return;
    }
    echo '<style>.grecaptcha-badge { visibility: hidden !important; }</style>';
}
