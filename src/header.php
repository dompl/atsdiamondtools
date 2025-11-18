<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js scroll-smooth">
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="//www.google-analytics.com" rel="dns-prefetch">
        <?php wp_head(); ?>
    </head>
    <body class="text-brand_text font-body">
    <?php echo get_field( 'wrap', 'option' ) ? '<div class="max-w-[1600px] px-5 mx-auto">' : ''; ?>
	 <div class="container"><?php do_action( 'skyline_after_body' )?></div>