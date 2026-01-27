<?php
/**
 * The Template for displaying product category archives
 *
 * This template uses the same structure as archive-product.php
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the main shop template which handles both shop and category pages
// The archive-product.php will detect we're on a category page via is_product_category()
locate_template( 'woocommerce/archive-product.php', true, false );
