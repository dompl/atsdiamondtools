<?php
/**
 * ATS Diamond Tools - AJAX Search Handler
 *
 * @package ATS
 * @since 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register AJAX handlers for search
 */
add_action( 'wp_ajax_ats_product_search', 'ats_handle_product_search' );
add_action( 'wp_ajax_nopriv_ats_product_search', 'ats_handle_product_search' );

/**
 * Register REST API endpoint for faster search
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'ats/v1', '/search', array(
        'methods'             => 'GET',
        'callback'            => 'ats_rest_product_search',
        'permission_callback' => '__return_true',
        'args'                => array(
            'query'    => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'category' => array(
                'required'          => false,
                'sanitize_callback' => 'absint'
            ),
            'page'     => array(
                'required'          => false,
                'default'           => 1,
                'sanitize_callback' => 'absint'
            )
        )
    ) );
} );

/**
 * Handle REST API product search (faster than AJAX)
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function ats_rest_product_search( $request ) {
    $search_query = $request->get_param( 'query' ) ?? '';
    $category_id  = $request->get_param( 'category' ) ?? 0;
    $page         = $request->get_param( 'page' ) ?? 1;
    $per_page     = 10;

    // Minimum query length
    if ( strlen( $search_query ) < 2 && empty( $category_id ) ) {
        return new WP_REST_Response( array(
            'products'     => array(),
            'total_found'  => 0,
            'max_pages'    => 0,
            'current_page' => $page,
            'has_more'     => false
        ), 200 );
    }

    // Generate cache key
    $cache_key       = 'ats_search_' . md5( $search_query . '_' . $category_id . '_' . $page );
    $cached_response = get_transient( $cache_key );

    if ( false !== $cached_response ) {
        return new WP_REST_Response( $cached_response, 200 );
    }

    // Build query arguments - optimized for speed
    $args = array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => $per_page,
        'paged'                  => $page,
        'fields'                 => 'ids',
        'no_found_rows'          => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false
    );

    // Add search query
    if ( !empty( $search_query ) ) {
        $args['s']       = $search_query;
        $args['orderby'] = 'relevance';
        $args['order']   = 'DESC';
    } else {
        $args['orderby'] = 'menu_order title';
        $args['order']   = 'ASC';
    }

    // Add category filter
    if ( !empty( $category_id ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id
            )
        );
    }

    // Execute query
    $query = new WP_Query( $args );

    // Build results array
    $results = array();

    // Check if prices are displayed excluding tax
    $price_suffix = '';
    if ( wc_tax_enabled() && 'excl' === get_option( 'woocommerce_tax_display_shop' ) ) {
        $price_suffix = ' ' . __( '+ VAT', 'ats' );
    }

    if ( $query->have_posts() ) {
        // Batch load product data for better performance
        $product_ids = $query->posts;

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );

            if ( !$product ) {
                continue;
            }

            // Get product image with retina support
            $image_id     = $product->get_image_id();
            $image_url    = '';
            $image_url_2x = '';

            if ( $image_id ) {
                if ( function_exists( 'wpimage' ) ) {
                    $image_url    = wpimage( $image_id, array( 50, 50 ), false, false, true, true, 85 );
                    $image_url_2x = wpimage( $image_id, array( 100, 100 ), false, false, true, true, 85 );
                } else {
                    $image_url    = wp_get_attachment_image_url( $image_id, 'thumbnail' );
                    $image_url_2x = wp_get_attachment_image_url( $image_id, 'medium' );
                }
            } else {
                $image_url    = wc_placeholder_img_src( 'thumbnail' );
                $image_url_2x = $image_url;
            }

            // Get short description - minimal processing
            $short_description = $product->get_short_description();
            if ( empty( $short_description ) ) {
                $short_description = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 15, '...' );
            } else {
                $short_description = wp_trim_words( wp_strip_all_tags( $short_description ), 15, '...' );
            }

            // Get price - show "from" for variable products + VAT suffix
            $price_html = '';
            if ( $product->is_type( 'variable' ) ) {
                $min_price  = $product->get_variation_price( 'min', true );
                $price_html = sprintf(
                    /* translators: %s: minimum price */
                    __( 'From: %s', 'ats' ),
                    wc_price( $min_price )
                ) . $price_suffix;
            } else {
                $price_html = wc_price( $product->get_price() ) . $price_suffix;
            }

            $results[] = array(
                'id'                => $product_id,
                'title'             => get_the_title( $product_id ),
                'url'               => get_permalink( $product_id ),
                'image'             => $image_url,
                'image_2x'          => $image_url_2x,
                'short_description' => $short_description,
                'price'             => $price_html
            );
        }
    }

    wp_reset_postdata();

    // Prepare response
    $response = array(
        'products'     => $results,
        'total_found'  => $query->found_posts,
        'max_pages'    => $query->max_num_pages,
        'current_page' => $page,
        'has_more'     => $page < $query->max_num_pages
    );

    // Cache for 5 minutes
    set_transient( $cache_key, $response, 5 * MINUTE_IN_SECONDS );

    return new WP_REST_Response( $response, 200 );
}

/**
 * Handle AJAX product search request
 */
function ats_handle_product_search() {
    // Verify nonce
    if ( !check_ajax_referer( 'ats_search_nonce', 'security', false ) ) {
        wp_send_json_error( array(
            'message' => __( 'Security check failed', 'ats' )
        ) );
    }

    // Get and sanitize parameters
    $search_query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
    $category_id  = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;
    $page         = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
    $per_page     = 10;

    // Minimum query length
    if ( strlen( $search_query ) < 2 && empty( $category_id ) ) {
        wp_send_json_error( array(
            'message' => __( 'Please enter at least 2 characters', 'ats' )
        ) );
    }

    // Build query arguments
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'relevance',
        'order'          => 'DESC'
    );

    // Add search query
    if ( !empty( $search_query ) ) {
        $args['s'] = $search_query;
    }

    // Add category filter
    if ( !empty( $category_id ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id
            )
        );
    }

    // Execute query
    $query = new WP_Query( $args );

    // Build results array
    $results = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $product_id = get_the_ID();
            $product    = wc_get_product( $product_id );

            if ( !$product ) {
                continue;
            }

            // Get product image
            $image_id  = $product->get_image_id();
            $image_url = '';

            if ( $image_id ) {
                // Use wpimage() function if available
                if ( function_exists( 'wpimage' ) ) {
                    $image_url = wpimage( $image_id, array( 120, 120 ), false, false, true, true, 85 );
                } else {
                    $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
                }
            } else {
                $image_url = wc_placeholder_img_src( 'thumbnail' );
            }

            // Get short description
            $short_description = $product->get_short_description();
            if ( empty( $short_description ) ) {
                $short_description = wp_trim_words( $product->get_description(), 20, '...' );
            } else {
                $short_description = wp_trim_words( $short_description, 20, '...' );
            }

            $results[] = array(
                'id'                => $product_id,
                'title'             => get_the_title(),
                'url'               => get_permalink(),
                'image'             => $image_url,
                'short_description' => wp_strip_all_tags( $short_description ),
                'price'             => $product->get_price_html()
            );
        }
    }

    wp_reset_postdata();

    // Prepare response
    $response = array(
        'products'     => $results,
        'total_found'  => $query->found_posts,
        'max_pages'    => $query->max_num_pages,
        'current_page' => $page,
        'has_more'     => $page < $query->max_num_pages
    );

    wp_send_json_success( $response );
}

/**
 * Localize search script with AJAX data
 */
add_action( 'wp_enqueue_scripts', 'ats_localize_search_script', 20 );

function ats_localize_search_script() {
    // Only localize if the shortcode might be used
    wp_localize_script( 'child-bundle', 'atsSearch', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'rest_url' => rest_url( 'ats/v1/search' ),
        'nonce'    => wp_create_nonce( 'ats_search_nonce' ),
        'i18n'     => array(
            'searching'    => __( 'Searching...', 'ats' ),
            'no_results'   => __( 'No products found', 'ats' ),
            'error'        => __( 'An error occurred. Please try again.', 'ats' ),
            'view_product' => __( 'View Product', 'ats' )
        )
    ) );
}
