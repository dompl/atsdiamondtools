<?php
/**
 * =============================================================================
 * WooCommerce Asset Dequeue
 * =============================================================================
 * Remove all default WooCommerce styles and scripts to allow custom styling
 * via Tailwind CSS. This ensures complete control over the store's appearance.
 */

// Disable WooCommerce default CSS entirely via filter.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

// Dequeue WooCommerce styles and scripts with high priority (999).
add_action( 'wp_enqueue_scripts', function () {
    // Only run if WooCommerce is active.
    if ( !class_exists( 'WooCommerce' ) ) {
        return;
    }

    /**
     * -------------------------------------------------------------------------
     * Dequeue Default WooCommerce Styles
     * -------------------------------------------------------------------------
     */

    // Core WooCommerce stylesheets.
    wp_dequeue_style( 'woocommerce-layout' );
    wp_dequeue_style( 'woocommerce-smallscreen' );
    wp_dequeue_style( 'woocommerce-general' );
    wp_deregister_style( 'woocommerce-layout' );
    wp_deregister_style( 'woocommerce-smallscreen' );
    wp_deregister_style( 'woocommerce-general' );

    /**
     * -------------------------------------------------------------------------
     * Dequeue WooCommerce Block Styles
     * -------------------------------------------------------------------------
     */

    // Main blocks style.
    wp_dequeue_style( 'wc-blocks-style' );
    wp_deregister_style( 'wc-blocks-style' );

    // Individual block styles.
    $wc_block_styles = [
        'wc-blocks-style-active-filters',
        'wc-blocks-style-add-to-cart-form',
        'wc-blocks-style-all-products',
        'wc-blocks-style-all-reviews',
        'wc-blocks-style-attribute-filter',
        'wc-blocks-style-breadcrumbs',
        'wc-blocks-style-catalog-sorting',
        'wc-blocks-style-customer-account',
        'wc-blocks-style-featured-category',
        'wc-blocks-style-featured-product',
        'wc-blocks-style-mini-cart',
        'wc-blocks-style-price-filter',
        'wc-blocks-style-product-add-to-cart',
        'wc-blocks-style-product-button',
        'wc-blocks-style-product-categories',
        'wc-blocks-style-product-image',
        'wc-blocks-style-product-image-gallery',
        'wc-blocks-style-product-query',
        'wc-blocks-style-product-results-count',
        'wc-blocks-style-product-reviews',
        'wc-blocks-style-product-sale-badge',
        'wc-blocks-style-product-search',
        'wc-blocks-style-product-sku',
        'wc-blocks-style-product-stock-indicator',
        'wc-blocks-style-product-summary',
        'wc-blocks-style-product-title',
        'wc-blocks-style-rating-filter',
        'wc-blocks-style-reviews-by-category',
        'wc-blocks-style-reviews-by-product',
        'wc-blocks-style-product-details',
        'wc-blocks-style-single-product',
        'wc-blocks-style-stock-filter',
        'wc-blocks-style-cart',
        'wc-blocks-style-checkout',
        'wc-blocks-style-mini-cart-contents'
    ];

    foreach ( $wc_block_styles as $style_handle ) {
        wp_dequeue_style( $style_handle );
        wp_deregister_style( $style_handle );
    }

    /**
     * -------------------------------------------------------------------------
     * Dequeue WooCommerce Scripts
     * -------------------------------------------------------------------------
     * Note: We preserve essential scripts needed for cart/checkout functionality
     * but remove styling-related and unnecessary scripts.
     */

    // Remove WooCommerce frontend scripts (we'll implement custom ones as needed).
    wp_dequeue_script( 'wc-add-to-cart' );
    wp_dequeue_script( 'wc-add-to-cart-variation' );
    wp_dequeue_script( 'wc-cart' );
    wp_dequeue_script( 'wc-cart-fragments' );

    // Keep country-select and address scripts on account pages for address forms
    if ( ! is_account_page() ) {
        wp_dequeue_script( 'wc-country-select' );
        wp_dequeue_script( 'wc-address-i18n' );
    }

    wp_dequeue_script( 'wc-checkout' );
    wp_dequeue_script( 'wc-credit-card-form' );
    wp_dequeue_script( 'wc-single-product' );
    wp_dequeue_script( 'woocommerce' );
    wp_dequeue_script( 'prettyPhoto' );
    wp_dequeue_script( 'prettyPhoto-init' );
    wp_dequeue_script( 'jquery-blockui' );
    wp_dequeue_script( 'jquery-placeholder' );
    wp_dequeue_script( 'jquery-payment' );
    wp_dequeue_script( 'jqueryui' );
    wp_dequeue_script( 'fancybox' );
    wp_dequeue_script( 'flexslider' );
    wp_dequeue_script( 'zoom' );
    wp_dequeue_script( 'photoswipe' );
    wp_dequeue_script( 'photoswipe-ui-default' );
    wp_dequeue_script( 'wc-password-strength-meter' );

    // Dequeue WooCommerce block scripts.
    wp_dequeue_script( 'wc-blocks' );
    wp_dequeue_script( 'wc-blocks-registry' );
    wp_dequeue_script( 'wc-blocks-middleware' );
    wp_dequeue_script( 'wc-blocks-data-store' );

}, 999 );

/**
 * Remove WooCommerce block styles from the block editor.
 */
add_action( 'enqueue_block_assets', function () {
    if ( !class_exists( 'WooCommerce' ) ) {
        return;
    }

    // Remove block editor specific WooCommerce styles.
    wp_dequeue_style( 'wc-blocks-editor-style' );
    wp_deregister_style( 'wc-blocks-editor-style' );
    wp_dequeue_style( 'wc-block-editor' );
    wp_deregister_style( 'wc-block-editor' );
}, 999 );

/**
 * Disable WooCommerce block styles loading.
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( !class_exists( 'WooCommerce' ) ) {
        return;
    }

    // Remove inline styles added by WooCommerce.
    wp_dequeue_style( 'woocommerce-inline' );
    wp_deregister_style( 'woocommerce-inline' );
}, 999 );

/**
 * Remove WooCommerce generator tag from head.
 */
add_action( 'wp_head', function () {
    remove_action( 'wp_head', [WC(), 'generator'] );
}, 1 );

/**
 * Localize WooCommerce account scripts
 * The actual JS is loaded via main.js bundle
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( !class_exists( 'WooCommerce' ) || !is_account_page() ) {
        return;
    }

    // Add AJAX parameters to the main bundle script
    wp_localize_script(
        'main',
        'wc_account_params',
        [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wc_account_nonce' )
        ]
    );
}, 100 );
