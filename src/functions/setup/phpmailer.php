<?php

function avolve_smtp_settings( $settings ) {

    $settings['Host']     = 'froghost.co.uk';
    $settings['Username'] = 'avolvesoftware@rfsmail.co.uk';
    $settings['Password'] = 'oXN1!2vh7tfNhxi9w';
    $settings['reply_to'] = 'sales@avolvesoftware.com';
    $settings['From']     = 'avolvesoftware@rfsmail.co.uk';
    $settings['FromName'] = 'Avolve';
    return $settings;
}

add_filter( 'skylinewp_smtp_settings', 'avolve_smtp_settings' );

// skylinewp_send_email(
//     'info@redfrogstudio.co.uk',
//     'Test Subject',
//     '<p>HTML message</p>',
//     ['Reply-To: support@example.com', 'Cc: cc1@example.com,cc2@example.com', 'Bcc: bcc@example.com'],
//     [WP_CONTENT_DIR . '/uploads/file.pdf'],
//     'Plain text version'
// );