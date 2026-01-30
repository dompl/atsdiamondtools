<?php
/**
 * Product Caching System
 *
 * Implements HTML caching for product cards to improve AJAX loading performance
 *
 * @package SkylineWP Dev Child
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get cached product HTML or generate and cache it
 *
 * @param int $product_id Product ID
 * @param string $display_type Display type (1=grid, 2=list, 3=compact)
 * @param bool $is_favorite Whether product is in user's favorites
 * @return string|false Cached HTML or false if not cached
 */
function ats_get_cached_product_html($product_id, $display_type = '1', $is_favorite = false) {
    // Generate unique cache key
    $cache_key = ats_generate_product_cache_key($product_id, $display_type, $is_favorite);

    // Try to get from cache
    $cached_html = get_transient($cache_key);

    if ($cached_html !== false) {
        return $cached_html;
    }

    return false;
}

/**
 * Set cached product HTML
 *
 * @param int $product_id Product ID
 * @param string $display_type Display type
 * @param bool $is_favorite Whether product is in favorites
 * @param string $html HTML to cache
 * @param int $expiration Cache expiration in seconds (default: 12 hours)
 * @return bool True on success
 */
function ats_set_cached_product_html($product_id, $display_type, $is_favorite, $html, $expiration = 43200) {
    $cache_key = ats_generate_product_cache_key($product_id, $display_type, $is_favorite);

    // Store in transient with expiration
    return set_transient($cache_key, $html, $expiration);
}

/**
 * Generate cache key for product
 *
 * @param int $product_id Product ID
 * @param string $display_type Display type
 * @param bool $is_favorite Whether in favorites
 * @return string Cache key
 */
function ats_generate_product_cache_key($product_id, $display_type, $is_favorite) {
    $product = wc_get_product($product_id);
    if (!$product) {
        return '';
    }

    // Include product version in key for automatic invalidation on product changes
    $product_modified = $product->get_date_modified() ? $product->get_date_modified()->getTimestamp() : time();

    // Include stock status and review count in key
    $stock_status = $product->get_stock_status();
    $review_count = $product->get_review_count();

    // Build cache key with version info
    $favorite_suffix = $is_favorite ? '_fav' : '';

    return sprintf(
        'ats_product_%d_d%s%s_v%d_s%s_r%d',
        $product_id,
        $display_type,
        $favorite_suffix,
        $product_modified,
        $stock_status,
        $review_count
    );
}

/**
 * Clear all cached versions of a product
 *
 * @param int $product_id Product ID
 * @return void
 */
function ats_clear_product_cache($product_id) {
    global $wpdb;

    // Delete all transients for this product using SQL for efficiency
    $transient_pattern = $wpdb->esc_like('_transient_ats_product_' . $product_id . '_') . '%';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
            OR option_name LIKE %s",
            $transient_pattern,
            $wpdb->esc_like('_transient_timeout_ats_product_' . $product_id . '_') . '%'
        )
    );

    // Clear object cache if enabled
    if (function_exists('wp_cache_delete_multiple')) {
        wp_cache_flush();
    }
}

/**
 * Clear cache for multiple products
 *
 * @param array $product_ids Array of product IDs
 * @return void
 */
function ats_clear_products_cache($product_ids) {
    if (empty($product_ids) || !is_array($product_ids)) {
        return;
    }

    foreach ($product_ids as $product_id) {
        ats_clear_product_cache($product_id);
    }
}

/**
 * Hook: Clear cache when product is saved/updated
 */
add_action('woocommerce_update_product', 'ats_clear_product_cache');
add_action('woocommerce_new_product', 'ats_clear_product_cache');
add_action('save_post_product', 'ats_clear_product_cache_on_save');

function ats_clear_product_cache_on_save($post_id) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    ats_clear_product_cache($post_id);
}

/**
 * Hook: Clear cache when product stock changes
 */
add_action('woocommerce_product_set_stock', 'ats_clear_cache_on_stock_change');
add_action('woocommerce_variation_set_stock', 'ats_clear_cache_on_stock_change');

function ats_clear_cache_on_stock_change($product) {
    if (is_numeric($product)) {
        $product_id = $product;
    } elseif (is_object($product)) {
        $product_id = $product->get_id();

        // If it's a variation, also clear parent product cache
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
            if ($parent_id) {
                ats_clear_product_cache($parent_id);
            }
        }
    } else {
        return;
    }

    ats_clear_product_cache($product_id);
}

/**
 * Hook: Clear cache when order is processed and stock is reduced
 */
add_action('woocommerce_reduce_order_stock', 'ats_clear_cache_on_order_stock_reduction');
add_action('woocommerce_restore_order_stock', 'ats_clear_cache_on_order_stock_restoration');

function ats_clear_cache_on_order_stock_reduction($order) {
    if (!$order) {
        return;
    }

    // Get order object if ID was passed
    if (is_numeric($order)) {
        $order = wc_get_order($order);
    }

    if (!$order) {
        return;
    }

    // Clear cache for all products in the order
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();

        if ($product_id) {
            ats_clear_product_cache($product_id);
        }

        if ($variation_id) {
            ats_clear_product_cache($variation_id);
        }
    }
}

function ats_clear_cache_on_order_stock_restoration($order) {
    // Same logic as reduction - clear cache when stock is restored (refunds, cancellations)
    ats_clear_cache_on_order_stock_reduction($order);
}

/**
 * Hook: Clear cache when order status changes (alternative trigger for stock updates)
 */
add_action('woocommerce_order_status_changed', 'ats_clear_cache_on_order_status_change', 10, 4);

function ats_clear_cache_on_order_status_change($order_id, $old_status, $new_status, $order) {
    // Clear cache when order moves to processing or completed (stock might be reduced)
    // Or when order is cancelled/refunded (stock might be restored)
    $stock_affecting_statuses = array('processing', 'completed', 'cancelled', 'refunded');

    if (in_array($new_status, $stock_affecting_statuses) || in_array($old_status, $stock_affecting_statuses)) {
        ats_clear_cache_on_order_stock_reduction($order);
    }
}

/**
 * Hook: Clear cache when product price changes
 */
add_action('woocommerce_product_object_updated_props', 'ats_clear_cache_on_price_change', 10, 2);

function ats_clear_cache_on_price_change($product, $updated_props) {
    // Check if price-related properties were updated
    $price_props = array('price', 'regular_price', 'sale_price');

    if (array_intersect($price_props, $updated_props)) {
        ats_clear_product_cache($product->get_id());
    }
}

/**
 * Hook: Clear cache when review is added/updated
 */
add_action('comment_post', 'ats_clear_cache_on_review', 10, 2);
add_action('edit_comment', 'ats_clear_cache_on_review_edit');

function ats_clear_cache_on_review($comment_id, $comment_approved) {
    $comment = get_comment($comment_id);

    if ($comment && $comment->comment_type === 'review') {
        ats_clear_product_cache($comment->comment_post_ID);
    }
}

function ats_clear_cache_on_review_edit($comment_id) {
    $comment = get_comment($comment_id);

    if ($comment && $comment->comment_type === 'review') {
        ats_clear_product_cache($comment->comment_post_ID);
    }
}

/**
 * Hook: Clear cache when product image changes
 */
add_action('updated_post_meta', 'ats_clear_cache_on_thumbnail_change', 10, 4);

function ats_clear_cache_on_thumbnail_change($meta_id, $object_id, $meta_key, $meta_value) {
    if ($meta_key === '_thumbnail_id' && get_post_type($object_id) === 'product') {
        ats_clear_product_cache($object_id);
    }
}

/**
 * Admin: Add button to clear all product caches
 */
add_action('admin_bar_menu', 'ats_add_clear_cache_admin_button', 100);

function ats_add_clear_cache_admin_button($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $wp_admin_bar->add_node(array(
        'id'    => 'ats_clear_product_cache',
        'title' => 'Clear Product Cache',
        'href'  => wp_nonce_url(admin_url('admin-post.php?action=ats_clear_all_product_cache'), 'ats_clear_cache'),
        'meta'  => array(
            'title' => 'Clear all cached product HTML'
        )
    ));
}

/**
 * Handle admin request to clear all product caches
 */
add_action('admin_post_ats_clear_all_product_cache', 'ats_handle_clear_all_cache');

function ats_handle_clear_all_cache() {
    // Verify nonce and permissions
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ats_clear_cache')) {
        wp_die('Security check failed');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    global $wpdb;

    // Delete all product cache transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_ats_product_%'
        OR option_name LIKE '_transient_timeout_ats_product_%'"
    );

    // Redirect back with success message
    wp_redirect(add_query_arg('cache_cleared', '1', wp_get_referer()));
    exit;
}

/**
 * Show admin notice after cache clear
 */
add_action('admin_notices', 'ats_cache_cleared_notice');

function ats_cache_cleared_notice() {
    if (isset($_GET['cache_cleared']) && $_GET['cache_cleared'] === '1') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Product cache cleared successfully!</strong></p>
        </div>
        <?php
    }
}
