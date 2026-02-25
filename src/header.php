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
        <script type="text/javascript">
        //<![CDATA[
        (function(i, s, o, g, r, a, m) {
            i["GoogleAnalyticsObject"] = r;
            i[r] = i[r] || function() {
                (i[r].q = i[r].q || []).push(arguments)
            }, i[r].l = 1 * new Date();
            a = s.createElement(o), m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, "script", "//www.google-analytics.com/analytics.js", "ga");
        ga("create", "UA-56621-9", {
            "cookieDomain": "auto"
        });
        ga("send", "pageview");
        //]]>
        </script>
    </head>
    <body <?php body_class( 'text-brand_text font-body' ); ?>>
        <?php wp_body_open(); ?>
        <?php do_action( 'skyline_after_body' ); ?>
		  <?php if ( current_user_can( 'manage_options' ) ) : ?>
		  	<?php get_template_part( 'functions/template-parts/colours' ); ?>
		  <?php endif; ?>
		  <?php get_template_part( 'functions/template-parts/header-main-3' ); ?>
        <div id="content" class="site-content">