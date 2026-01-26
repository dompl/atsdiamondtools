<?php
/**
 * The Template for displaying product category archives
 *
 * This template uses the same structure as archive-product.php
 * but with category-specific data
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current category from query.
$current_term     = get_queried_object();
$current_category = $current_term->term_id;

// Set current category as GET parameter so archive-product.php logic can use it.
$_GET['current_cat'] = $current_category;

// Include the main shop template which will use the current category.
include get_stylesheet_directory() . '/src/woocommerce/archive-product.php';
