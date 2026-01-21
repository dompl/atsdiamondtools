<?php
/**
 * Single Product Image - Custom Splide Integration
 *
 * @package skylinewp-dev-child
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

<div class="woocommerce-product-gallery custom-splide-gallery relative group">
    <!-- Main Slider -->
    <section id="product-main-splide" class="splide bg-gray-50 rounded-lg overflow-hidden mb-4" aria-label="Product Images">
        <div class="splide__track">
            <ul class="splide__list">
                <?php foreach ($attachment_ids as $attachment_id) : ?>
                    <?php
                    $full_src = wp_get_attachment_image_url($attachment_id, 'full');
                    $img_src = wp_get_attachment_image_url($attachment_id, 'woocommerce_single');
						$img_srcset = wp_get_attachment_image_srcset($attachment_id, 'woocommerce_single');
						$img_sizes = wp_get_attachment_image_sizes($attachment_id, 'woocommerce_single');
                    $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                    ?>
                    <li class="splide__slide flex items-center justify-center p-4 h-[400px] md:h-[500px]" data-image-id="<?php echo esc_attr($attachment_id); ?>">
                        <?php
                        $full_src = wp_get_attachment_image_url($attachment_id, 'full');
                        ?>
                        <a href="<?php echo esc_url($full_src); ?>" class="product-gallery-lightbox-trigger block w-full h-full flex items-center justify-center cursor-zoom-in">
                            <?php echo wp_get_attachment_image($attachment_id, 'woocommerce_single', false, [
                                'class' => 'max-w-full max-h-full object-contain mx-auto transition-transform duration-300 transform group-hover:scale-105',
                                'loading' => 'lazy'
                            ]); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>


    <!-- Thumbnails Slider -->
    <section id="product-thumbnail-splide" class="splide px-12 relative" aria-label="Product Thumbnails">
        <div class="splide__track">
            <ul class="splide__list">
                <?php foreach ($attachment_ids as $attachment_id) : ?>
                    <li class="splide__slide opacity-80 transition-opacity [&.is-active]:opacity-100 border border-transparent rounded cursor-pointer overflow-hidden" data-image-id="<?php echo esc_attr($attachment_id); ?>">
							<img decoding="async" src="<?php echo wpimage(image: $attachment_id, size:[95, 95])?>" srcset="<?php wpimage(image: $attachment_id, size:[95, 95])?> 1x, <?php echo wpimage(image: $attachment_id, size:[95, 95], retina:true)?> 2x" class="w-full h-16 sm:h-24 object-cover">
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