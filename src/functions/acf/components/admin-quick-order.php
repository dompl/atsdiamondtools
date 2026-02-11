<?php
/**
 * ACF Component: Admin Quick Order Panel
 *
 * A powerful interface for administrators to quickly search products
 * and create orders without browsing the website. Perfect for phone
 * orders and quick order entry.
 *
 * @package SkylineWP Dev Child
 */

use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Fields\Select;

/**
 * Output function for admin quick order component
 */
function component_admin_quick_order_html( string $output, string $layout ): string {
	if ( $layout !== 'admin_quick_order' ) {
		return $output;
	}

	// Load the output template
	ob_start();
	require get_stylesheet_directory() . '/functions/acf/outputs/admin-quick-order.php';
	return ob_get_clean();
}
add_filter( 'skylinewp_flexible_content_output', 'component_admin_quick_order_html', 10, 2 );

return Layout::make('Admin Quick Order Panel', 'admin_quick_order')
	->fields([
		Text::make('Section Heading', 'section_heading')
			->default('Quick Order Panel')
			->helperText('Heading displayed at the top of the panel'),

		Textarea::make('Instructions', 'instructions')
			->rows(3)
			->helperText('Optional instructions for using the quick order panel')
			->default('Search for products below and add them to your cart. Use this tool to quickly create orders for customers.'),

		TrueFalse::make('Show Customer Email Field', 'show_customer_email')
			->default(1)
			->helperText('Allow entering customer email to associate order with specific customer'),

		TrueFalse::make('Show Product Filters', 'show_filters')
			->default(1)
			->helperText('Display category and brand filters above search'),

		Select::make('Products Per Row', 'products_per_row')
			->choices([
				'2' => '2 Products',
				'3' => '3 Products',
				'4' => '4 Products',
			])
			->default('3')
			->helperText('Number of product cards displayed per row'),

		TrueFalse::make('Admin Only', 'admin_only')
			->default(1)
			->helperText('Restrict this component to administrators only (recommended)'),
	])
	->layout('block');
