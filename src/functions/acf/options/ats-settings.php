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
						->instructions( 'Text displayed on product cards when out of stock. Default: Out of Stock' )
						->required(),

					Text::make( 'Back in Stock Form Heading', 'back_in_stock_heading' )
						->instructions( 'Heading shown above the notification form. Default: Notify me when back in stock' )
						->required(),

					Textarea::make( 'Back in Stock Form Description', 'back_in_stock_description' )
						->instructions( 'Description text shown on the form. Default: Enter your email address below and we\'ll notify you when this product is available again.' )
						->rows( 3 ),

					Text::make( 'Email Subject Line', 'back_in_stock_email_subject' )
						->instructions( 'Subject for notification emails. Use {product_name} variable. Default: {product_name} is back in stock!' )
						->required(),

					Textarea::make( 'Email Message Template', 'back_in_stock_email_message' )
						->instructions( 'Email template. Variables: {user_name}, {product_name}, {product_link}. Default: Hello {user_name}, Great news! {product_name} has returned to stock. Click here to view: {product_link}' )
						->rows( 8 )
						->required(),

					Text::make( 'Notification Success Message', 'back_in_stock_success_message' )
						->instructions( 'Message after successful subscription. Default: Thank you! We\'ll notify you when this product is back in stock.' )
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
