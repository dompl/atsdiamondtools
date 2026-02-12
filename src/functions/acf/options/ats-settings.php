<?php
/**
 * ATS Settings Options Page
 *
 * Global settings for ATS functionality including out-of-stock notifications
 *
 * @package skylinewp-dev-child
 */

use Extended\ACF\Fields\Number;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\TrueFalse;
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
						->helperText( 'Text displayed on product cards when out of stock. Default: Out of Stock' )
						->required(),

					Text::make( 'Back in Stock Form Heading', 'back_in_stock_heading' )
						->helperText( 'Heading shown above the notification form. Default: Notify me when back in stock' )
						->required(),

					Textarea::make( 'Back in Stock Form Description', 'back_in_stock_description' )
						->helperText( 'Description text shown on the form. Default: Enter your email address below and we\'ll notify you when this product is available again.' )
						->rows( 3 ),

					Text::make( 'Email Subject Line', 'back_in_stock_email_subject' )
						->helperText( 'Subject for notification emails. Use {product_name} variable. Default: {product_name} is back in stock!' )
						->required(),

					Textarea::make( 'Email Message Template', 'back_in_stock_email_message' )
						->helperText( 'Email template. Variables: {user_name}, {product_name}, {product_link}. Default: Hello {user_name}, Great news! {product_name} has returned to stock. Click here to view: {product_link}' )
						->rows( 8 )
						->required(),

					Text::make( 'Notification Success Message', 'back_in_stock_success_message' )
						->helperText( 'Message after successful subscription. Default: Thank you! We\'ll notify you when this product is back in stock.' )
						->required(),

					Tab::make( 'Newsletter' )
						->placement( 'left' ),

					Text::make( 'Newsletter Title', 'ats_footer_newsletter_title' )
						->helperText( 'Eye-catching headline to encourage newsletter signups. Displayed in uppercase, bold text.' )
						->default( "ATS EXCLUSIVE: OFFERS YOU CAN'T MISS!" ),

					Textarea::make( 'Newsletter Description', 'ats_footer_newsletter_description' )
						->helperText( 'Compelling copy explaining the benefits of subscribing. Keep it concise and action-oriented.' )
						->rows( 3 )
						->newLines( 'br' )
						->default( "Dive into a world of exclusive offers tailored just for you. Don't let these unbeatable ATS deals pass you by!" ),

					Text::make( 'Button Text', 'ats_footer_newsletter_button' )
						->helperText( 'Call-to-action button label. Keep it short and action-oriented (e.g., SUBSCRIBE, JOIN NOW, SIGN UP).' )
						->default( 'SUBSCRIBE' ),

					Textarea::make( 'Privacy Disclaimer', 'ats_footer_newsletter_disclaimer' )
						->helperText( 'GDPR compliance text. Use %privacy_policy% to insert a link to your Privacy Policy page automatically.' )
						->rows( 2 )
						->newLines( 'br' )
						->default( "By signing up, I agree to ATS Diamond Tools' %privacy_policy% and consent to my data being collected and stored." ),

					Text::make( 'Brevo List ID', 'ats_footer_brevo_list_id' )
						->helperText( 'Numeric ID of the contact list in Brevo where subscribers will be added. Find in Brevo > Contacts > Lists. API key is configured in wp-config.php as BREVO_API.' )
						->required(),

					Textarea::make( 'Success Message', 'ats_footer_newsletter_success_message' )
						->helperText( 'Message displayed after successful newsletter subscription. Keep it friendly and reassuring.' )
						->rows( 2 )
						->newLines( 'br' )
						->default( 'Thank you for subscribing! Check your inbox to confirm your subscription.' ),

					Tab::make( 'Checkout Newsletter' )
						->placement( 'left' ),

					TrueFalse::make( 'Enable Checkout Newsletter', 'ats_checkout_newsletter_enabled' )
						->helperText( 'When enabled, displays an opt-out newsletter checkbox on the checkout page. Customers are subscribed unless they tick the box.' ),

					Text::make( 'Checkbox Label', 'ats_checkout_newsletter_label' )
						->helperText( 'Label text displayed next to the opt-out checkbox on the checkout page.' )
						->default( 'I do not wish to sign up for the ATS Diamond Tools newsletter' ),

					Number::make( 'Checkout Brevo List ID', 'ats_checkout_newsletter_list_id' )
						->helperText( 'Numeric ID of the Brevo contact list for checkout newsletter subscribers. Find in Brevo > Contacts > Lists.' )
						->default( 2 ),

					Tab::make( 'PDF Invoices' )
						->placement( 'left' ),

					TrueFalse::make( 'Enable New Subtotal Calculation', 'enable_new_subtotal_calculation' )
						->helperText( 'When enabled, adds shipping cost to subtotal on PDF invoices and moves shipping line to the top.' ),
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
