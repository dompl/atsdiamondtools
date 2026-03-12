<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js scroll-smooth">
    <head>
        <title><?php wp_title( '' ); ?></title>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="//www.googletagmanager.com" rel="dns-prefetch">
        <?php if ( defined( 'ATS_GA4_MEASUREMENT_ID' ) && ATS_GA4_MEASUREMENT_ID ) : ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( ATS_GA4_MEASUREMENT_ID ); ?>"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc_js( ATS_GA4_MEASUREMENT_ID ); ?>');
        </script>
        <?php endif; ?>
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