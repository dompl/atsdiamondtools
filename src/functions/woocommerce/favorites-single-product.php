<?php
/**
 * Add Favorites Heart to Single Product Page
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add favorites heart button to single product page
 * Positioned after the product title
 */
function ats_add_favorites_heart_single_product() {
	global $product;

	if ( ! $product ) {
		return;
	}

	echo '<div class="rfs-ref-single-product-favorite-heart my-4">';
	get_template_part( 'functions/template-parts/favorites-heart', null, array( 'product_id' => $product->get_id() ) );
	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'ats_add_favorites_heart_single_product', 7 );
