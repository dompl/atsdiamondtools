<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.7.0
 *
 * @note    Custom Splide Integration - This template uses Splide slider instead of WooCommerce's default gallery.
 */

defined('ABSPATH') || exit;

global $product;

$post_thumbnail_id = $product->get_image_id();
$attachment_ids = $product->get_gallery_image_ids();

// Create a unified list of images: Main Image + Gallery Images
if ($post_thumbnail_id) {
    array_unshift($attachment_ids, $post_thumbnail_id);
}

// Ensure unique images
$attachment_ids = array_unique($attachment_ids);

if (empty($attachment_ids)) {
	// Placeholder fallback
    $placeholder = wc_placeholder_img_src('woocommerce_single');
    echo '<div class="woocommerce-product-gallery custom-splide-gallery"><img src="' . esc_url($placeholder) . '" alt="Awaiting product image" class="wp-post-image" /></div>';
    return;
}
?>

<div class="rfs-ref-product-gallery woocommerce-product-gallery custom-splide-gallery relative group" data-gallery-count="<?php echo count($attachment_ids); ?>">
    <!-- Main Slider -->
    <section id="product-main-splide" class="rfs-ref-product-main-slider splide rounded-lg overflow-hidden mb-4" aria-label="Product Images">
        <div class="splide__track">
            <ul class="splide__list">
                <?php foreach ($attachment_ids as $attachment_id) :
                    $full_src  = wp_get_attachment_image_url($attachment_id, 'full');
                    $alt_text  = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                    $meta      = wp_get_attachment_metadata($attachment_id);
                    $orig_w    = $meta['width'] ?? 0;
                    $orig_h    = $meta['height'] ?? 0;
                    $is_square = ($orig_w > 0 && $orig_h > 0 && abs($orig_w - $orig_h) <= 10);

                    if ($is_square) {
                        $img_1x    = wpimage(image: $attachment_id, size: [542, 542], quality: 85);
                        $img_2x    = wpimage(image: $attachment_id, size: [542, 542], retina: true, quality: 85);
                        $img_class = 'rfs-ref-product-main-image w-full h-full object-cover';
                    } else {
                        $img_1x    = wpimage(image: $attachment_id, size: 542, quality: 85);
                        $img_2x    = wpimage(image: $attachment_id, size: 542, retina: true, quality: 85);
                        $img_class = 'rfs-ref-product-main-image max-w-full max-h-full object-contain';
                    }
                ?>
                    <li class="rfs-ref-product-slide splide__slide aspect-square" data-image-id="<?php echo esc_attr($attachment_id); ?>">
                        <a href="<?php echo esc_url($full_src); ?>" class="rfs-ref-product-lightbox-trigger product-gallery-lightbox-trigger flex items-center justify-center w-full h-full cursor-zoom-in">
                            <img src="<?php echo esc_url($img_1x); ?>"
                                 srcset="<?php echo esc_url($img_1x); ?> 1x, <?php echo esc_url($img_2x); ?> 2x"
                                 alt="<?php echo esc_attr($alt_text); ?>"
                                 loading="lazy"
                                 class="<?php echo esc_attr($img_class); ?>">
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ( count($attachment_ids) > 1 ) : ?>
        <!-- Main Slider Arrows (hover-visible) -->
        <div class="rfs-ref-product-main-arrows splide__arrows">
            <button class="splide__arrow splide__arrow--prev !absolute !left-2 !top-1/2 !-translate-y-1/2 !w-10 !h-10 !bg-white/80 hover:!bg-white !rounded-full !shadow-md !opacity-0 group-hover:!opacity-100 !transition-opacity !duration-200 !z-10">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#434343" style="transform: rotate(180deg);"><path d="M521.33-480.67 328-674l47.33-47.33L616-480.67 375.33-240 328-287.33l193.33-193.34Z"/></svg>
            </button>
            <button class="splide__arrow splide__arrow--next !absolute !right-2 !top-1/2 !-translate-y-1/2 !w-10 !h-10 !bg-white/80 hover:!bg-white !rounded-full !shadow-md !opacity-0 group-hover:!opacity-100 !transition-opacity !duration-200 !z-10">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#434343"><path d="M521.33-480.67 328-674l47.33-47.33L616-480.67 375.33-240 328-287.33l193.33-193.34Z"/></svg>
            </button>
        </div>
        <?php endif; ?>
    </section>

    <?php if ( count($attachment_ids) > 1 ) : ?>
    <!-- Thumbnails Slider -->
    <section id="product-thumbnail-splide" class="splide px-12 relative" aria-label="Product Thumbnails">
        <div class="splide__track">
            <ul class="splide__list">
                <?php foreach ($attachment_ids as $attachment_id) : ?>
                    <li class="splide__slide opacity-80 transition-opacity [&.is-active]:opacity-100 border border-transparent rounded cursor-pointer overflow-hidden" data-image-id="<?php echo esc_attr($attachment_id); ?>">
							<img decoding="async" src="<?php echo wpimage(image: $attachment_id, size:[95, 95])?>" srcset="<?php echo wpimage(image: $attachment_id, size:[95, 95])?> 1x, <?php echo wpimage(image: $attachment_id, size:[95, 95], retina:true)?> 2x" class="w-full h-16 sm:h-24 object-cover">
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Custom Arrows -->
        <div class="splide__arrows">
			<button class="splide__arrow splide__arrow--prev !bg-transparent !w-10 !h-10 !-left-4 !shadow-none !opacity-100 hover:!opacity-70">
				<svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#434343" style="transform: rotate(180deg);"><path d="M521.33-480.67 328-674l47.33-47.33L616-480.67 375.33-240 328-287.33l193.33-193.34Z"/></svg>
			</button>
			<button class="splide__arrow splide__arrow--next !bg-transparent !w-10 !h-10 !-right-4 !shadow-none !opacity-100 hover:!opacity-70">
				<svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#434343"><path d="M521.33-480.67 328-674l47.33-47.33L616-480.67 375.33-240 328-287.33l193.33-193.34Z"/></svg>
			</button>
		</div>
    </section>
    <?php endif; ?>

    <!-- Custom Lightbox Modal (Hidden) -->
    <div id="product-lightbox-modal" class="fixed inset-0 z-50 hidden bg-ats-brand/90 flex items-center justify-center opacity-0 transition-opacity duration-300">
        <button type="button" class="lightbox-close absolute top-4 right-4 text-white hover:text-gray-300 z-50">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <div class="lightbox-content relative w-full h-full flex items-center justify-center p-4">
             <img src="" alt="Lightbox Preview" class="max-w-full max-h-full object-contain shadow-2xl">
        </div>
    </div>
</div>
<?php