<?php
/**
 * Product Applications Taxonomy
 *
 * Registers the 'product_application' taxonomy for Wood, Metal, Stone labels.
 *
 * @package skylinewp-dev-child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'register_product_application_taxonomy' );

function register_product_application_taxonomy() {
    $labels = [
        'name'              => _x( 'Applications', 'taxonomy general name', 'woocommerce' ),
        'singular_name'     => _x( 'Application', 'taxonomy singular name', 'woocommerce' ),
        'search_items'      => __( 'Search Applications', 'woocommerce' ),
        'all_items'         => __( 'All Applications', 'woocommerce' ),
        'parent_item'       => __( 'Parent Application', 'woocommerce' ),
        'parent_item_colon' => __( 'Parent Application:', 'woocommerce' ),
        'edit_item'         => __( 'Edit Application', 'woocommerce' ),
        'update_item'       => __( 'Update Application', 'woocommerce' ),
        'add_new_item'      => __( 'Add New Application', 'woocommerce' ),
        'new_item_name'     => __( 'New Application Name', 'woocommerce' ),
        'menu_name'         => __( 'Applications', 'woocommerce' ),
    ];

    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => [ 'slug' => 'application' ],
        'show_in_rest'      => true,
    ];

    register_taxonomy( 'product_application', [ 'product' ], $args );
}

// Pre-create terms if they don't exist
add_action( 'init', 'populate_product_application_terms' );
function populate_product_application_terms() {
    if ( !taxonomy_exists( 'product_application' ) ) {
        return;
    }

    $terms = [ 'Wood', 'Metal', 'Stone' ];
    foreach ( $terms as $term ) {
        if ( !term_exists( $term, 'product_application' ) ) {
            wp_insert_term( $term, 'product_application' );
        }
    }
}
