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
	class="rfs-ref-favorite-heart ats-favorite-btn <?php echo esc_attr( $heart_classes ); ?> group relative"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
	aria-label="<?php echo $is_favorite ? esc_attr__( 'Remove from favorites', 'skylinewp-dev-child' ) : esc_attr__( 'Add to favorites', 'skylinewp-dev-child' ); ?>"
	title="<?php echo $is_favorite ? esc_attr__( 'Remove from favorites', 'skylinewp-dev-child' ) : esc_attr__( 'Add to favorites', 'skylinewp-dev-child' ); ?>"
>
	<!-- Tooltip -->
	<span class="rfs-ref-favorite-tooltip absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">
		<span class="ats-tooltip-add"><?php esc_html_e( 'Add to favourites', 'skylinewp-dev-child' ); ?></span>
		<span class="ats-tooltip-remove hidden"><?php esc_html_e( 'Remove from favourites', 'skylinewp-dev-child' ); ?></span>
		<!-- Tooltip arrow -->
		<span class="absolute top-full left-1/2 -translate-x-1/2 -mt-px">
			<svg width="8" height="4" viewBox="0 0 8 4" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M4 4L0 0H8L4 4Z" fill="#1F2937"/>
			</svg>
		</span>
	</span>

	<!-- Heart Icon - Outline (not favorite) -->
	<svg class="ats-heart-outline w-6 h-6 transition-all duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
		<path d="m480-120-58-52q-101-91-167-157T150-447.5Q111-500 95.5-544T80-634q0-94 63-157t157-63q52 0 99 22t81 62q34-40 81-62t99-22q94 0 157 63t63 157q0 46-15.5 90T810-447.5Q771-395 705-329T538-172l-58 52Zm0-108q96-86 158-147.5t98-107q36-45.5 50-81t14-70.5q0-60-40-100t-100-40q-47 0-87 26.5T518-680h-76q-15-41-55-67.5T300-774q-60 0-100 40t-40 100q0 35 14 70.5t50 81q36 45.5 98 107T480-228Zm0-273Z"/>
	</svg>

	<!-- Heart Icon - Filled (favorite) -->
	<svg class="ats-heart-filled w-6 h-6 transition-all duration-200 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
		<path d="m480-120-58-52q-101-91-167-157T150-447.5Q111-500 95.5-544T80-634q0-94 63-157t157-63q52 0 99 22t81 62q34-40 81-62t99-22q94 0 157 63t63 157q0 46-15.5 90T810-447.5Q771-395 705-329T538-172l-58 52Z"/>
	</svg>
</button>
