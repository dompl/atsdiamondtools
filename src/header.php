<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js scroll-smooth">
    <head>
        <title><?php wp_title( '' ); ?></title>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="//www.googletagmanager.com" rel="dns-prefetch">
        <?php
        // Suppress all marketing/analytics tags for logged-in staff (internal traffic).
        $ats_tracking_excluded = function_exists( 'ats_ga4_is_excluded_user' ) && ats_ga4_is_excluded_user();
        ?>
        <?php if ( defined( 'ATS_GA4_MEASUREMENT_ID' ) && ATS_GA4_MEASUREMENT_ID && ! $ats_tracking_excluded ) : ?>
        <script>
        /* Third-party tags are queued inline and the external scripts are fetched on
           first interaction or window load (whichever comes first) so they don't
           block first paint on mobile. Events fired before then are replayed. */
        window.atsLoadDeferred = window.atsLoadDeferred || function (src) {
            var fired = false, evs = ['touchstart','keydown','mousemove','wheel','scroll'];
            function go(){ if (fired) return; fired = true;
                evs.forEach(function(e){ window.removeEventListener(e, go, {passive:true}); });
                var t = document.createElement('script'); t.async = true; t.src = src;
                document.head.appendChild(t); }
            evs.forEach(function(e){ window.addEventListener(e, go, {passive:true}); });
            if (document.readyState === 'complete') { setTimeout(go, 1500); }
            else { window.addEventListener('load', function(){ setTimeout(go, 1500); }); }
        };
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc_js( ATS_GA4_MEASUREMENT_ID ); ?>');
        atsLoadDeferred('https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( ATS_GA4_MEASUREMENT_ID ); ?>');
        </script>
        <?php endif; ?>
        <?php if ( defined( 'ATS_META_PIXEL_ID' ) && ATS_META_PIXEL_ID && ! $ats_tracking_excluded ) : ?>
        <!-- Meta Pixel Code -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];(f.atsLoadDeferred||function(u){t=b.createElement(e);t.async=!0;
        t.src=u;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)})(v)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?php echo esc_js( ATS_META_PIXEL_ID ); ?>');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=<?php echo esc_attr( ATS_META_PIXEL_ID ); ?>&ev=PageView&noscript=1"/></noscript>
        <!-- End Meta Pixel Code -->
        <?php endif; ?>
        <?php wp_head(); ?>
    </head>
    <body <?php body_class( 'text-brand_text font-body' ); ?>>
        <?php wp_body_open(); ?>
        <?php do_action( 'skyline_after_body' ); ?>
        <?php get_template_part( 'functions/template-parts/clearance-bar' ); ?>
		  <?php if ( current_user_can( 'manage_options' ) ) : ?>
		  	<?php get_template_part( 'functions/template-parts/colours' ); ?>
		  <?php endif; ?>
		  <?php get_template_part( 'functions/template-parts/header-main-3' ); ?>
        <div id="content" class="site-content">