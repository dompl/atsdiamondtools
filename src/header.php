<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js scroll-smooth">
    <head>
        <title><?php wp_title( '' ); ?></title>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="//www.google-analytics.com" rel="dns-prefetch">
        <link href="//www.googletagmanager.com" rel="dns-prefetch">
        <link href="//www.google.com" rel="dns-prefetch">
        <?php wp_head(); ?>
    </head>
    <body <?php body_class( 'text-brand_text font-body' ); ?>>
        <?php wp_body_open(); ?>
        <?php do_action( 'skyline_after_body' ); ?>
		  <?php if ( current_user_can( 'manage_options' ) ) : ?>
		  	<?php get_template_part( 'functions/template-parts/colours' ); ?>
		  <?php endif; ?>
		  <?php get_template_part( 'functions/template-parts/header-main-3' ); ?>
        <div id="content" class="site-content">