<?php
/**
 * ATS Settings Options Page
 *
 * Global settings for ATS functionality including out-of-stock notifications
 *
 * @package skylinewp-dev-child
 */

use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\Text;
use Extended\ACF\Location;

if ( ! function_exists( 'register_ats_settings_options' ) ) {
	function register_ats_settings_options() {
		register_extended_field_group(
			[
				'title'    => 'ATS Settings',
				'fields'   => [
					Tab::make( 'Out of Stock Notifications' )
						->placement( 'left' ),

					Text::make( 'Out of Stock Button Text', 'out_of_stock_button_text' )
						->helperText( 'Text displayed on product cards when out of stock' )
						->defaultValue( 'Out of Stock' )
						->required(),

					Text::make( 'Back in Stock Form Heading', 'back_in_stock_heading' )
						->helperText( 'Heading shown above the notification form on product page' )
						->defaultValue( 'Notify me when back in stock' )
						->required(),

					Textarea::make( 'Back in Stock Form Description', 'back_in_stock_description' )
						->helperText( 'Description text shown on the notification form' )
						->defaultValue( 'Enter your email address below and we\'ll notify you when this product is available again.' )
						->rows( 3 ),

					Text::make( 'Email Subject Line', 'back_in_stock_email_subject' )
						->helperText( 'Subject line for back-in-stock notification emails' )
						->defaultValue( '{product_name} is back in stock!' )
						->required(),

					Textarea::make( 'Email Message Template', 'back_in_stock_email_message' )
						->helperText( 'Email message template. Available variables: {user_name}, {product_name}, {product_link}' )
						->defaultValue( "Hello {user_name},\n\nGreat news! {product_name} has returned to stock.\n\nClick here to view: {product_link}\n\nThank you for your patience!" )
						->rows( 8 )
						->required(),

					Text::make( 'Notification Success Message', 'back_in_stock_success_message' )
						->helperText( 'Message shown after successful subscription' )
						->defaultValue( 'Thank you! We\'ll notify you when this product is back in stock.' )
						->required(),
				],
				'location' => [
					Location::where( 'options_page', '==', 'ats-settings' ),
				],
			]
		);
	}
}

// Register options page
if ( function_exists( 'acf_add_options_page' ) ) {
	acf_add_options_page(
		[
			'page_title' => 'ATS Settings',
			'menu_title' => 'ATS Settings',
			'menu_slug'  => 'ats-settings',
			'capability' => 'manage_options',
			'icon_url'   => 'dashicons-admin-settings',
			'position'   => 30,
		]
	);
}

add_action( 'init', 'register_ats_settings_options', 20 );
