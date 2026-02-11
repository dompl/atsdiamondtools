<?php
/**
 * Footer Settings Options Page
 *
 * Creates an options page under Appearance menu for footer configuration
 * using Extended ACF.
 *
 * @package ATS Diamond Tools
 */

// Prevent duplicate file inclusion.
if ( defined( 'ATS_FOOTER_SETTINGS_LOADED' ) ) {
    return;
}
define( 'ATS_FOOTER_SETTINGS_LOADED', true );

use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Email;
use Extended\ACF\Fields\Link;
use Extended\ACF\Location;

/**
 * Register the Footer Settings options page.
 */
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( [
        'page_title'  => 'Footer Settings',
        'menu_title'  => 'Footer Settings',
        'menu_slug'   => 'footer-settings',
        'parent_slug' => 'themes.php',
        'capability'  => 'edit_theme_options',
        'redirect'    => false,
        'autoload'    => true,
        'position'    => 62,
    ] );
}

/**
 * Register the Footer Settings field group.
 */
if ( function_exists( 'register_extended_field_group' ) ) {
    register_extended_field_group( [
        'title'    => 'ATS Footer Settings',
        'key'      => 'group_ats_footer_settings',
        'fields'   => [
            /**
             * Information Tab
             * Contains company logo, description, and contact details.
             */
            Tab::make( 'Information', 'ats_footer_info_tab' )
                ->placement( 'left' ),

            Image::make( 'Company Logo', 'ats_footer_logo' )
                ->helperText( 'Footer logo displayed at the top of the first column. Use SVG or PNG with transparent background for best results.' )
                ->format( 'array' )
                ->previewSize( 'medium' )
                ->library( 'all' ),

            Textarea::make( 'Company Description', 'ats_footer_description' )
                ->helperText( 'Brief company overview shown below the logo. Describe your business, products, and value proposition.' )
                ->rows( 4 )
                ->newLines( 'br' )
                ->default( 'At ATS Diamond Tools we pride ourselves on manufacturing stone masonry tools to the highest standards. With competitive pricing, ATS Diamond Tools supplies tools to polish, grind, cut or drill enabling a professional finish to any granite, quartz, marble or concrete project.' ),

            Text::make( 'Telephone Number', 'ats_footer_telephone' )
                ->helperText( 'Main contact number with phone icon. Format: 0203 130 1720 or +44 203 130 1720.' )
                ->default( '0203 130 1720' ),

            Email::make( 'Email Address', 'ats_footer_email' )
                ->helperText( 'Main contact email with envelope icon. Visitors can click to open their email client.' )
                ->default( 'info@atsdiamondtools.co.uk' ),

            /**
             * Links Tab
             * Contains two columns of navigation links.
             */
            Tab::make( 'Links', 'ats_footer_links_tab' )
                ->placement( 'left' ),

            Text::make( 'Useful Links Title', 'ats_footer_links_title_1' )
                ->helperText( 'Column heading for general website links. Displayed in uppercase, bold text.' )
                ->default( 'USEFUL LINKS' ),

            Repeater::make( 'Useful Links', 'ats_footer_useful_links' )
                ->helperText( 'Important pages like About Us, Delivery, Contact, Terms & Conditions, Privacy Statement. Links appear vertically in a single column.' )
                ->fields( [
                    Link::make( 'Link', 'ats_footer_link' )
                        ->helperText( 'Choose page/post or enter custom URL. Link text and target (new tab) are configurable.' )
                        ->format( 'array' ),
                ] )
                ->button( 'Add Link' )
                ->layout( 'block' ),

            Text::make( 'Product Categories Title', 'ats_footer_links_title_2' )
                ->helperText( 'Column heading for product categories. Displayed in uppercase, bold text.' )
                ->default( 'PRODUCT CATEGORIES' ),

            Repeater::make( 'Product Category Links', 'ats_footer_category_links' )
                ->helperText( 'Product category links like Cutting, Grinding, Drilling, Polishing, etc. Links are displayed in two sub-columns for better space utilization.' )
                ->fields( [
                    Link::make( 'Link', 'ats_footer_category_link' )
                        ->helperText( 'Link to product category page. Can be WooCommerce category or custom page.' )
                        ->format( 'array' ),
                ] )
                ->button( 'Add Category Link' )
                ->layout( 'block' ),

            /**
             * Bottom Tab
             * Contains copyright and company registration information.
             */
            Tab::make( 'Bottom', 'ats_footer_bottom_tab' )
                ->placement( 'left' ),

            Text::make( 'Copyright Text', 'ats_footer_copyright' )
                ->helperText( 'Copyright statement in the footer bottom bar. Use %year% to automatically display the current year (e.g., 2025).' )
                ->default( '©%year% ATS Diamond Tools. All rights reserved.' ),

            Text::make( 'Company Registration Number', 'ats_footer_company_reg' )
                ->helperText( 'UK Companies House registration number. Displayed in the footer bottom bar for legal compliance.' )
                ->default( 'Company Registration Number 7624346' ),

            Text::make( 'VAT Number', 'ats_footer_vat_number' )
                ->helperText( 'UK VAT registration number. Required for B2B transactions and displayed for legal transparency.' )
                ->default( 'VAT Number GB 113 4705 48' ),
        ],
        'location' => [
            Location::where( 'options_page', 'footer-settings' ),
        ],
        'style'    => 'default',
        'position' => 'normal',
    ] );
}
