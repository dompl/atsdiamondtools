<?php
/**
 * Header Settings Options Page
 *
 * Creates an options page under Appearance menu for header configuration
 * using Extended ACF.
 *
 * @package ATS Diamond Tools
 */

// Prevent duplicate file inclusion.
if ( defined( 'ATS_HEADER_SETTINGS_LOADED' ) ) {
    return;
}
define( 'ATS_HEADER_SETTINGS_LOADED', true );

use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Email;
use Extended\ACF\Fields\Link;
use Extended\ACF\Location;

/**
 * Register the Header Settings options page.
 */
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( [
        'page_title'  => 'Header Settings',
        'menu_title'  => 'Header Settings',
        'menu_slug'   => 'header-settings',
        'parent_slug' => 'themes.php',
        'capability'  => 'edit_theme_options',
        'redirect'    => false,
        'autoload'    => true,
        'position'    => 61,
    ] );
}

/**
 * Register the Header Settings field group.
 */
if ( function_exists( 'register_extended_field_group' ) ) {
    register_extended_field_group( [
        'title'    => 'ATS Header Settings',
        'key'      => 'group_ats_header_settings',
        'fields'   => [
            /**
             * Logo Tab
             * Contains logo images and favicon settings.
             */
            Tab::make( 'Logo', 'ats_logo_tab' )
                ->placement( 'left' ),

            Image::make( 'Logo Image', 'ats_header_logo' )
                ->helperText( 'Upload the main logo image for the website header.' )
                ->format( 'array' )
                ->previewSize( 'medium' )
                ->library( 'all' ),

            Image::make( 'Sub Logo Image', 'ats_header_sublogo' )
                ->helperText( 'Upload a secondary logo or tagline image.' )
                ->format( 'array' )
                ->previewSize( 'medium' )
                ->library( 'all' ),

            Image::make( 'Favicon', 'ats_site_favicon' )
                ->helperText( 'Upload the favicon (recommended size: 32x32 or 512x512 pixels).' )
                ->format( 'array' )
                ->previewSize( 'thumbnail' )
                ->library( 'all' ),

            /**
             * Top Navigation Tab
             * Contains repeater for navigation links.
             */
            Tab::make( 'Top Navigation', 'ats_top_nav_tab' )
                ->placement( 'left' ),

            Repeater::make( 'Navigation Links', 'ats_top_navigation_links' )
                ->helperText( 'Add links for the top navigation menu.' )
                ->fields( [
                    Link::make( 'Navigation Link', 'ats_link' )
                        ->helperText( 'Select a page or enter a custom URL.' )
                        ->format( 'array' ),
                ] )
                ->button( 'Add Navigation Link' )
                ->layout( 'block' ),

            /**
             * Information Tab
             * Contains contact information fields.
             */
            Tab::make( 'Information', 'ats_info_tab' )
                ->placement( 'left' ),

            Text::make( 'Telephone Number', 'ats_info_telephone' )
                ->helperText( 'Enter the contact telephone number (e.g., +44 123 456 7890).' ),

            Email::make( 'Email Address', 'ats_info_email' )
                ->helperText( 'Enter the contact email address.' ),
        ],
        'location' => [
            Location::where( 'options_page', 'header-settings' ),
        ],
        'style'    => 'default',
        'position' => 'normal',
    ] );
}
