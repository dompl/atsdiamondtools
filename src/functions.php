<?php
/**
 * Class SkylineWPChildThemeSetup
 *
 * This class sets up the child theme by hooking into the WordPress lifecycle.
 * It ensures that the child theme has access to the parent theme's Composer
 * autoloader and adds custom ACF directories.
 */
class SkylineWPChildThemeSetup {

    /**
     * Constructor: Hook into WordPress lifecycle.
     *
     * Hooks into 'after_setup_theme' to initialize functionality and
     * 'skylinewp_additional_acf_directories' to add custom ACF directories.
     */
    public function __construct() {
        // Hook into after_setup_theme to initialize functionality
        add_action( 'after_setup_theme', [$this, 'load_parent_autoloader'], 0 );
        add_filter( 'skylinewp_additional_acf_directories', [$this, 'skyline_add_custom_acf_directories'], 0 );

        // Note: Child theme functions are auto-loaded by the parent theme's ACF system
        // via the skylinewp_additional_acf_directories filter above.
        // No additional auto-loading is needed here.
    }

    /**
     * Add custom ACF directories.
     *
     * @param array $directories Existing ACF directories.
     * @return array Modified ACF directories with custom directories added.
     */
    public function skyline_add_custom_acf_directories( $directories ) {
        // Add your custom directories to the array
        $directories[] = get_stylesheet_directory() . '/functions/theme';
        $directories[] = get_stylesheet_directory() . '/functions/options';
        $directories[] = get_stylesheet_directory() . '/functions/shortcodes';
        $directories[] = get_stylesheet_directory() . '/functions/acf/shared/output';
        $directories[] = get_stylesheet_directory() . '/functions/acf/options';
        $directories[] = get_stylesheet_directory() . '/functions/setup';
        $directories[] = get_stylesheet_directory() . '/functions/plugins';
        $directories[] = get_stylesheet_directory() . '/functions/ajax';
        $directories[] = get_stylesheet_directory() . '/functions/shortcodes/search';
        $directories[] = get_stylesheet_directory() . '/functions/woocommerce';
        return $directories;
    }

    /**
     * Load the parent theme's Composer autoloader.
     *
     * Ensures the child theme has access to all dependencies managed
     * by the parent theme's Composer setup.
     */
    public function load_parent_autoloader() {
        $parent_autoloader = get_template_directory() . '/vendor/autoload.php';
        if ( file_exists( $parent_autoloader ) ) {
            require_once $parent_autoloader;
        } else {
            error_log( 'Parent theme Composer autoloader not found at ' . $parent_autoloader );
        }
    }


}

// Initialise the class
new SkylineWPChildThemeSetup();