<?php
/**
 * Shop Page Settings
 *
 * Adds custom fields to the WooCommerce shop page for banner display
 *
 * @package ATS Diamond Tools
 */

use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Location;

/**
 * Register the Shop Page Settings field group
 */
if ( function_exists( 'register_extended_field_group' ) ) {
    // Get the shop page ID
    $shop_page_id = wc_get_page_id( 'shop' );

    if ( $shop_page_id ) {
        register_extended_field_group( [
            'title'    => 'Shop Page Banner Settings',
            'key'      => 'group_shop_page_banner',
            'fields'   => [
                Image::make( 'Banner Image', 'shop_banner_image' )
                    ->helperText( 'Banner background image (recommended: 1920x400px or larger)' )
                    ->format( 'id' ),

                Textarea::make( 'Banner Description', 'shop_banner_description' )
                    ->helperText( 'Description text displayed on the shop banner' )
                    ->rows( 3 ),
            ],
            'location' => [
                Location::where( 'page', $shop_page_id ),
            ],
            'style'    => 'default',
            'position' => 'normal',
        ] );
    }
}
