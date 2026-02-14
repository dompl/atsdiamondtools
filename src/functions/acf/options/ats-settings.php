<?php
/**
 * ATS Settings Options Page
 *
 * Global settings for ATS functionality including out-of-stock notifications
 *
 * @package skylinewp-dev-child
 */

use Extended\ACF\Fields\Accordion;
use Extended\ACF\Fields\Message;
use Extended\ACF\Fields\Number;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Location;

if ( ! function_exists( 'register_ats_settings_options' ) ) {
	function register_ats_settings_options() {
		$redirect_base = home_url( '/ats-auth/' );

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
						->default( 3 ),

					Tab::make( 'PDF Invoices' )
						->placement( 'left' ),

					TrueFalse::make( 'Enable New Subtotal Calculation', 'enable_new_subtotal_calculation' )
						->helperText( 'When enabled, adds shipping cost to subtotal on PDF invoices and moves shipping line to the top.' ),

					// ── Social Logins ──
					Tab::make( 'Social Logins' )
						->placement( 'left' ),

					// Google Login
					Accordion::make( 'Google Login' )
						->open()
						->multiExpand(),

					TrueFalse::make( 'Enable Google Login', 'ats_social_google_enabled' )
						->helperText( 'Show the "Sign in with Google" button on the login/register page.' ),

					Text::make( 'Google Client ID', 'ats_social_google_client_id' )
						->helperText( 'OAuth 2.0 Client ID from Google Cloud Console.' ),

					Text::make( 'Google Client Secret', 'ats_social_google_client_secret' )
						->helperText( 'OAuth 2.0 Client Secret from Google Cloud Console.' ),

					Message::make( 'Google Setup Instructions', 'ats_social_google_instructions' )
						->body(
							"**How to set up Google Login:**\n\n" .
							"1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)\n" .
							"2. Create a new project (or select existing)\n" .
							"3. Go to **APIs & Services > Credentials**\n" .
							"4. Click **Create Credentials > OAuth 2.0 Client ID**\n" .
							"5. Application type: **Web application**\n" .
							"6. Add Authorized redirect URI: `{$redirect_base}google/callback`\n" .
							"7. Copy the **Client ID** and **Client Secret** into the fields above\n" .
							"8. Make sure the **Google People API** is enabled in your project"
						),

					// Facebook Login
					Accordion::make( 'Facebook Login' )
						->multiExpand(),

					TrueFalse::make( 'Enable Facebook Login', 'ats_social_facebook_enabled' )
						->helperText( 'Show the "Sign in with Facebook" button on the login/register page.' ),

					Text::make( 'Facebook App ID', 'ats_social_facebook_app_id' )
						->helperText( 'App ID from Facebook Developer portal.' ),

					Text::make( 'Facebook App Secret', 'ats_social_facebook_app_secret' )
						->helperText( 'App Secret from Facebook Developer portal.' ),

					Message::make( 'Facebook Setup Instructions', 'ats_social_facebook_instructions' )
						->body(
							"**How to set up Facebook Login:**\n\n" .
							"1. Go to [Facebook Developers](https://developers.facebook.com/apps/)\n" .
							"2. Create a new app (type: **Consumer**)\n" .
							"3. Add the **Facebook Login** product\n" .
							"4. In **Settings > Basic**, copy the **App ID** and **App Secret**\n" .
							"5. In **Facebook Login > Settings**, add Valid OAuth Redirect URI: `{$redirect_base}facebook/callback`\n" .
							"6. Switch the app from **Development** to **Live** mode\n" .
							"7. Paste credentials into the fields above"
						),

					// Apple Login
					Accordion::make( 'Apple Login' )
						->multiExpand(),

					TrueFalse::make( 'Enable Apple Login', 'ats_social_apple_enabled' )
						->helperText( 'Show the "Sign in with Apple" button on the login/register page.' ),

					Text::make( 'Apple Service ID', 'ats_social_apple_service_id' )
						->helperText( 'Services ID identifier from Apple Developer portal.' ),

					Text::make( 'Apple Team ID', 'ats_social_apple_team_id' )
						->helperText( '10-character Team ID from Apple Developer account.' ),

					Text::make( 'Apple Key ID', 'ats_social_apple_key_id' )
						->helperText( 'Key ID of the private key created for Sign in with Apple.' ),

					Textarea::make( 'Apple Private Key', 'ats_social_apple_private_key' )
						->helperText( 'Paste the full contents of your .p8 private key file here (including BEGIN/END lines).' )
						->rows( 6 ),

					Message::make( 'Apple Setup Instructions', 'ats_social_apple_instructions' )
						->body(
							"**How to set up Apple Login:**\n\n" .
							"1. Go to [Apple Developer - Identifiers](https://developer.apple.com/account/resources/identifiers/list/serviceId)\n" .
							"2. Create a new **Services ID** and enable **Sign in with Apple**\n" .
							"3. Configure the service: add your domain and return URL: `{$redirect_base}apple/callback`\n" .
							"4. Go to **Keys** and create a new key with **Sign in with Apple** enabled\n" .
							"5. Download the `.p8` key file and paste its contents into the **Private Key** field above\n" .
							"6. Note your **Team ID** (top-right of Apple Developer portal) and **Key ID** (shown on the key page)\n" .
							"7. The **Service ID** is the identifier you created in step 2"
						),

					Accordion::make( 'Social Logins Endpoint' )
						->endpoint(),
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
