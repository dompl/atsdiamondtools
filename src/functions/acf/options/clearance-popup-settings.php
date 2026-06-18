<?php
/**
 * Clearance Pop-up Settings
 *
 * Registers a "Clearance Pop-up" options sub-page (under ATS Settings) that
 * lets staff control the site-wide clearance announcement modal: copy, image,
 * link, trigger delay and how often it reappears.
 *
 * @package skylinewp-dev-child
 */

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Number;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Location;

// Register the options sub-page under the existing "ATS Settings" menu.
if ( function_exists( 'acf_add_options_sub_page' ) ) {
	acf_add_options_sub_page(
		[
			'page_title'  => 'Clearance Pop-up',
			'menu_title'  => 'Clearance Pop-up',
			'menu_slug'   => 'clearance-popup-settings',
			'parent_slug' => 'ats-settings',
			'capability'  => 'manage_options',
		]
	);
}

if ( ! function_exists( 'register_ats_clearance_popup_options' ) ) {
	/**
	 * Register the Clearance Pop-up field group.
	 */
	function register_ats_clearance_popup_options() {
		if ( ! function_exists( 'register_extended_field_group' ) ) {
			return;
		}

		register_extended_field_group(
			[
				'title'    => 'Clearance Pop-up',
				'key'      => 'group_clearance_popup',
				'fields'   => [
					TrueFalse::make( 'Enable Pop-up', 'clearance_popup_enabled' )
						->helperText( 'Master switch. When off, the pop-up never appears anywhere on the site.' ),

					Text::make( 'Tag (optional)', 'clearance_popup_tag' )
						->helperText( 'Small label above the heading, e.g. "LIMITED STOCK". Leave blank to hide.' ),

					Text::make( 'Heading', 'clearance_popup_heading' )
						->helperText( 'Main headline.' )
						->default( 'Clearance Sale Now On' ),

					Textarea::make( 'Description', 'clearance_popup_description' )
						->helperText( 'One or two sentences of supporting copy.' )
						->rows( 3 )
						->default( 'Genuine diamond tools at clearance prices — limited stock, while it lasts.' ),

					Text::make( 'Button Label', 'clearance_popup_button_label' )
						->helperText( 'Call-to-action button text.' )
						->default( 'Shop Clearance' ),

					Text::make( 'Button Link', 'clearance_popup_link' )
						->helperText( 'Where the button goes. Leave blank to default to the Clearance category page.' ),

					Image::make( 'Image', 'clearance_popup_image' )
						->helperText( 'Left-hand image (a clearance product photo or sale graphic). Leave blank for a text-only card.' )
						->format( 'id' ),

					Number::make( 'Delay (seconds)', 'clearance_popup_delay' )
						->helperText( 'How long after the page loads before the pop-up appears.' )
						->default( 2 ),

					Select::make( 'Show Frequency', 'clearance_popup_frequency_mode' )
						->helperText( 'How often a visitor sees the pop-up.' )
						->choices(
							[
								'session' => 'Once per browsing session',
								'days'    => 'Once every N days',
							]
						)
						->default( 'session' ),

					Number::make( 'Days Between Shows', 'clearance_popup_frequency_days' )
						->helperText( 'Used only with "Once every N days".' )
						->default( 30 )
						->conditionalLogic(
							[
								ConditionalLogic::where( 'clearance_popup_frequency_mode', '==', 'days' ),
							]
						),

					Text::make( 'Top Bar Text', 'clearance_bar_text' )
						->helperText( 'Message shown in the full-width top announcement bar (appears after the pop-up is closed).' )
						->default( 'Clearance Sale now on — limited stock while it lasts.' ),

					Text::make( 'Top Bar Button Label', 'clearance_bar_button_label' )
						->helperText( 'Label for the link button in the top announcement bar.' )
						->default( 'Shop Clearance' ),

					Text::make( 'Nav Badge Text', 'clearance_nav_badge_text' )
						->helperText( 'Small badge shown on the Clearance item in the category navigation.' )
						->default( 'Limited stock' ),
				],
				'location' => [
					Location::where( 'options_page', '==', 'clearance-popup-settings' ),
				],
				'style'    => 'default',
			]
		);
	}
}

add_action( 'init', 'register_ats_clearance_popup_options', 20 );
