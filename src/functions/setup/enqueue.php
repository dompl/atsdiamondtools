<?php
/**
 * enqueue.php
 * Enqueue Theme Styles and Scripts for Child Theme
 *
 * This function enqueues the parent theme styles and scripts along with the child theme's additional assets,
 * ensuring that no styles are loaded twice.
 */
// Enqueue front-end styles and scripts.
add_action( 'wp_enqueue_scripts', function () {
    // If a child theme is active, manually enqueue the parent's stylesheet.
    if ( get_template() !== get_stylesheet() ) {
        wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    }

    // Enqueue the child theme's main stylesheet.
    wp_enqueue_style( 'child-theme-style', get_stylesheet_uri(), ( get_template() !== get_stylesheet() ? ['parent-style'] : [] ) );

    // Conditionally enqueue build.css files in the development environment only.
    if ( wp_get_environment_type() === 'staging' ) {
        // Enqueue the parent's build.css if it exists.
        $parent_build_css_path = get_template_directory() . '/build.css';
        if ( file_exists( $parent_build_css_path ) ) {
            wp_enqueue_style( 'parent-build-css', get_template_directory_uri() . '/build.css', ['parent-style'], filemtime( $parent_build_css_path ) );
        }

        // Enqueue the child's build.css if it exists.
        $child_build_css_path = get_stylesheet_directory() . '/build.css';
        if ( file_exists( $child_build_css_path ) ) {
            $child_build_css_path = get_stylesheet_directory() . '/build.css';
            $version              = file_exists( $child_build_css_path ) ? filemtime( $child_build_css_path ) : time();

            wp_enqueue_style(
                'child-build-css',
                get_stylesheet_directory_uri() . '/build.css',
                ['child-theme-style'],
                $version
            );
        }

        // ====================================================================
        // ADD THIS BLOCK TO ENQUEUE TAILWIND CSS IN DEVELOPMENT
        // ====================================================================
        // Enqueue the child's tailwind.css if it exists.
        $tailwind_css_path = get_stylesheet_directory() . '/tailwind.css';
        if ( file_exists( $tailwind_css_path ) ) {
            $version = filemtime( $tailwind_css_path );
            wp_enqueue_style(
                'child-tailwind-css', // A unique handle for the stylesheet
                get_stylesheet_directory_uri() . '/tailwind.css',
                ['child-theme-style'], // Make it dependent on the main style to control load order
                $version
            );
        }
        // ====================================================================
        // END OF TAILWIND BLOCK
        // ====================================================================

    }

    // Enqueue bundle.js from the child theme's /assets/js folder.
    $bundle_js_path = get_stylesheet_directory() . '/assets/js/bundle.js';
    $bundle_js_uri  = get_stylesheet_directory_uri() . '/assets/js/bundle.js';

    if ( file_exists( $bundle_js_path ) ) {
        $bundle_js_version = filemtime( $bundle_js_path );
    } else {
        $bundle_js_version = '1.0.0'; // Fallback version if file doesn't exist.
    }

    wp_enqueue_script( 'child-bundle', $bundle_js_uri, ['jquery'], $bundle_js_version, true );

    // Localise script to add dynamic data to bundle.js.
    $scripts_localize = [
        'ajax_url'               => admin_url( 'admin-ajax.php' ),
        'nonce'                  => wp_create_nonce( 'theme_nonce' ),
        'mini_cart_nonce'        => wp_create_nonce( 'ats_mini_cart_nonce' ),
        'cart_nonce'             => wp_create_nonce( 'ats-cart-nonce' ),
        'calculator_nonce'       => wp_create_nonce( 'ats_calculator_nonce' ),
        'ats_load_more_nonce' => wp_create_nonce( 'ats_load_more_posts' ),
        'theme_dir'              => get_stylesheet_directory_uri(),
        'shop_url'               => wc_get_page_permalink( 'shop' ),
        'is_admin'               => current_user_can( 'administrator' ) && is_user_logged_in() ? true : false,
        'is_user_logged_in'      => is_user_logged_in(),
    ];

    if ( wp_get_environment_type() === 'development' ) {
        $scripts_localize['dev'] = true;
    } else {
        $scripts_localize['dev'] = false;
    }

    wp_localize_script( 'child-bundle', 'themeData', apply_filters( 'skyline_child_localizes', $scripts_localize ) );

}, 10 );

// Enqueue admin styles and scripts for the child theme.
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    $admin_css = get_stylesheet_directory() . '/admin.css';
    if ( file_exists( $admin_css ) ) {
        wp_enqueue_style(
            'skyline-admin-style',
            get_stylesheet_directory_uri() . '/admin.css',
            [],
            filemtime( get_stylesheet_directory() . '/admin.css' )
        );
    }
    if ( 'edit-tags.php' === $hook && isset( $_GET['taxonomy'] ) && 'category' === $_GET['taxonomy'] ) {
        wp_dequeue_script( 'admin-js' );
        wp_enqueue_media();
    }
    $admin_js_path = get_stylesheet_directory() . '/assets/js/admin.js';
    $admin_js_uri  = get_stylesheet_directory_uri() . '/assets/js/admin.js';
    if ( file_exists( $admin_js_path ) ) {
        $admin_js_version = filemtime( $admin_js_path );
    } else {
        $admin_js_version = '1.0.0';
    }
    wp_enqueue_script( 'child-admin', $admin_js_uri, ['jquery'], $admin_js_version, true );
    wp_localize_script( 'child-admin', 'adminData', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'admin_nonce' )
    ] );
} );

// Add admin body classes.
function skyline_admin_body_class( $classes ) {
    if ( get_current_user_id() === 1 ) {
        $classes .= ' user-is';
    } else {
        $classes .= ' user-is-not';
    }
    return $classes;
}
add_filter( 'admin_body_class', 'skyline_admin_body_class' );