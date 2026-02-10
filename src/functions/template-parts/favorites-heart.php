<?php
/**
 * Favorites Heart Icon
 *
 * @package SkylineWP Dev Child
 *
 * @var int $product_id Product ID
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract args if passed via get_template_part
if ( isset( $args ) && is_array( $args ) ) {
	extract( $args );
}

// Get product ID from variable or global
if ( ! isset( $product_id ) ) {
	global $product;
	if ( ! $product ) {
		return;
	}
	$product_id = $product->get_id();
}

// Check if product is in favorites
$is_favorite = false;
if ( is_user_logged_in() ) {
	$is_favorite = ats_is_product_favorite( $product_id );
}

$heart_classes = $is_favorite ? 'ats-favorite-active' : '';
?>

<button
	type="button"
	class="rfs-ref-favorite-heart ats-favorite-btn group <?php echo esc_attr( $heart_classes ); ?>"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
	aria-label="<?php echo $is_favorite ? esc_attr__( 'Remove from favorites', 'skylinewp-dev-child' ) : esc_attr__( 'Add to favorites', 'skylinewp-dev-child' ); ?>"
>
	<!-- Heart Icon - Outline (not favorite) -->
	<svg class="ats-heart-outline w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors duration-200"
		fill="none"
		viewBox="0 0 24 24"
		stroke="currentColor"
		stroke-width="2">
		<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
	</svg>

	<!-- Heart Icon - Filled (favorite) -->
	<svg class="ats-heart-filled hidden w-5 h-5 text-red-500 transition-colors duration-200"
		fill="currentColor"
		viewBox="0 0 24 24">
		<path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/>
	</svg>
</button>
