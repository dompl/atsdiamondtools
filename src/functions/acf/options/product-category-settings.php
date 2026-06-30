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

use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Location;

/**
 * Register the Product Category Settings field group.
 */
if ( function_exists( 'register_extended_field_group' ) ) {
    register_extended_field_group( [
        'title'    => 'Category Banner Settings',
        'key'      => 'group_product_category_banner',
        'fields'   => [
            Text::make( 'Category H1 Heading', 'category_h1' )
                ->helperText( 'Optional. Overrides the visible H1 heading in the category banner. The navigation label and breadcrumb still use the category name, so keep nav short. Use a keyword-rich heading, e.g. "Diamond Cutting Blades". Leave blank to use the category name.' )
                ->maxLength( 80 ),
            Textarea::make( 'Category Navigation Short Description', 'category_nav_short_description' )
                ->helperText( 'Short description displayed in the banner category navigation sidebar. Keep it concise (e.g., "High-performance core drills and bits").' )
                ->rows( 2 )
                ->maxLength( 100 )
                ->newLines( 'br' ),
            Textarea::make( 'Category Banner Description', 'category_banner_description' )
                ->helperText( 'Short additional line shown under the category title in the banner. Keep it to one short sentence. The full category description appears below the banner.' )
                ->rows( 2 )
                ->maxLength( 200 )
                ->newLines( 'br' ),
        ],
        'location' => [
            Location::where( 'taxonomy', 'product_cat' ),
        ],
        'style'    => 'default',
        'position' => 'normal',
    ] );
}
