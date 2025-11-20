<?php
/**
 * Product Category Settings
 *
 * Adds custom fields to WooCommerce product category taxonomy
 * for enhanced category navigation and banner display.
 *
 * @package ATS Diamond Tools
 */

// Prevent duplicate file inclusion.
if ( defined( 'ATS_PRODUCT_CATEGORY_SETTINGS_LOADED' ) ) {
    return;
}
define( 'ATS_PRODUCT_CATEGORY_SETTINGS_LOADED', true );

use Extended\ACF\Fields\Textarea;
use Extended\ACF\Location;

/**
 * Register the Product Category Settings field group.
 */
add_action( 'acf/init', function () {
    if ( ! function_exists( 'register_extended_field_group' ) ) {
        return;
    }

    register_extended_field_group( [
        'title'    => 'Category Banner Settings',
        'key'      => 'group_product_category_banner',
        'fields'   => [
            Textarea::make( 'Category Navigation Short Description', 'category_nav_short_description' )
                ->helperText( 'Short description displayed in the banner category navigation sidebar. Keep it concise (e.g., "High-performance core drills and bits").' )
                ->rows( 2 )
                ->maxLength( 100 )
                ->newLines( 'br' ),
        ],
        'location' => [
            Location::where( 'taxonomy', 'product_cat' ),
        ],
        'style'    => 'default',
        'position' => 'normal',
    ] );
} );
