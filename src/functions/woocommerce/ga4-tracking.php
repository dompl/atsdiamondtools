<?php
/**
 * Google Analytics 4 - Ecommerce & Event Tracking
 *
 * Server-side tracking for GA4 ecommerce events.
 * Fires dataLayer pushes for page-level events and provides
 * product data to the client-side JS for interaction events.
 *
 * Measurement ID is defined in wp-config.php as ATS_GA4_MEASUREMENT_ID.
 *
 * @package ATS Diamond Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'ATS_GA4_MEASUREMENT_ID' ) || empty( ATS_GA4_MEASUREMENT_ID ) ) {
    return;
}

/**
 * Build a GA4 item array from a WC_Product.
 *
 * @param WC_Product $product  WooCommerce product object.
 * @param int        $quantity Item quantity.
 * @param int        $index    Position in list (0-based).
 * @param string     $list_name List name for item_list_name.
 * @return array GA4 item.
 */
function ats_ga4_build_item( $product, $quantity = 1, $index = 0, $list_name = '' ) {
    if ( ! $product instanceof WC_Product ) {
        return [];
    }

    $categories = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] );
    $category   = ! is_wp_error( $categories ) && ! empty( $categories ) ? $categories[0] : '';

    $item = [
        'item_id'   => $product->get_sku() ?: (string) $product->get_id(),
        'item_name' => $product->get_name(),
        'price'     => (float) $product->get_price(),
        'quantity'  => $quantity,
        'index'     => $index,
    ];

    if ( $category ) {
        $item['item_category'] = $category;

        // Add additional categories
        if ( count( $categories ) > 1 ) {
            for ( $i = 1; $i < min( count( $categories ), 5 ); $i++ ) {
                $item[ 'item_category' . ( $i + 1 ) ] = $categories[ $i ];
            }
        }
    }

    if ( $list_name ) {
        $item['item_list_name'] = $list_name;
    }

    // Add brand
    $item['item_brand'] = 'ATS Diamond Tools';

    return $item;
}

/**
 * Output GA4 page-level ecommerce events in the footer.
 */
add_action( 'wp_footer', 'ats_ga4_page_events', 50 );
function ats_ga4_page_events() {
    if ( ! function_exists( 'WC' ) ) {
        return;
    }

    // Single product — view_item
    if ( is_product() ) {
        ats_ga4_view_item();
        return;
    }

    // Shop / category / tag — view_item_list
    if ( is_shop() || is_product_category() || is_product_tag() ) {
        ats_ga4_view_item_list();
        return;
    }

    // Search results — search + view_item_list
    if ( is_search() ) {
        ats_ga4_search();
        return;
    }

    // Cart — view_cart
    if ( is_cart() && ! WC()->cart->is_empty() ) {
        ats_ga4_view_cart();
        return;
    }

    // Checkout — begin_checkout
    if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! WC()->cart->is_empty() ) {
        ats_ga4_begin_checkout();
        return;
    }

    // Thank you page — purchase
    if ( is_wc_endpoint_url( 'order-received' ) ) {
        ats_ga4_purchase();
        return;
    }
}

/**
 * view_item — Single product page.
 */
function ats_ga4_view_item() {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $item  = ats_ga4_build_item( $product );
    $value = (float) $product->get_price();

    ats_ga4_push_event( 'view_item', [
        'currency' => get_woocommerce_currency(),
        'value'    => $value,
        'items'    => [ $item ],
    ] );
}

/**
 * view_item_list — Shop, category, tag pages.
 */
function ats_ga4_view_item_list() {
    global $wp_query;

    if ( empty( $wp_query->posts ) ) {
        return;
    }

    $list_name = 'Shop';
    if ( is_product_category() ) {
        $term      = get_queried_object();
        $list_name = $term ? $term->name : 'Category';
    } elseif ( is_product_tag() ) {
        $term      = get_queried_object();
        $list_name = $term ? 'Tag: ' . $term->name : 'Tag';
    }

    $items = [];
    foreach ( $wp_query->posts as $index => $post ) {
        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            continue;
        }
        $items[] = ats_ga4_build_item( $product, 1, $index, $list_name );
    }

    if ( empty( $items ) ) {
        return;
    }

    ats_ga4_push_event( 'view_item_list', [
        'item_list_name' => $list_name,
        'items'          => $items,
    ] );
}

/**
 * search — Search results page.
 */
function ats_ga4_search() {
    $search_term = get_search_query();

    ats_ga4_push_event( 'search', [
        'search_term' => $search_term,
    ] );

    // Also fire view_item_list for product search results
    if ( ! empty( $GLOBALS['wp_query']->posts ) ) {
        $items = [];
        foreach ( $GLOBALS['wp_query']->posts as $index => $post ) {
            if ( $post->post_type !== 'product' ) {
                continue;
            }
            $product = wc_get_product( $post->ID );
            if ( ! $product ) {
                continue;
            }
            $items[] = ats_ga4_build_item( $product, 1, $index, 'Search Results' );
        }

        if ( ! empty( $items ) ) {
            ats_ga4_push_event( 'view_item_list', [
                'item_list_name' => 'Search Results',
                'items'          => $items,
            ] );
        }
    }
}

/**
 * view_cart — Cart page.
 */
function ats_ga4_view_cart() {
    $cart  = WC()->cart;
    $items = [];
    $index = 0;

    foreach ( $cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        if ( ! $product ) {
            continue;
        }
        $items[] = ats_ga4_build_item( $product, $cart_item['quantity'], $index );
        $index++;
    }

    ats_ga4_push_event( 'view_cart', [
        'currency' => get_woocommerce_currency(),
        'value'    => (float) $cart->get_total( 'edit' ),
        'items'    => $items,
    ] );
}

/**
 * begin_checkout — Checkout page.
 */
function ats_ga4_begin_checkout() {
    $cart  = WC()->cart;
    $items = [];
    $index = 0;

    foreach ( $cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        if ( ! $product ) {
            continue;
        }
        $items[] = ats_ga4_build_item( $product, $cart_item['quantity'], $index );
        $index++;
    }

    $data = [
        'currency' => get_woocommerce_currency(),
        'value'    => (float) $cart->get_total( 'edit' ),
        'items'    => $items,
    ];

    // Add coupon if applied
    $coupons = $cart->get_applied_coupons();
    if ( ! empty( $coupons ) ) {
        $data['coupon'] = implode( ', ', $coupons );
    }

    ats_ga4_push_event( 'begin_checkout', $data );
}

/**
 * purchase — Thank you / order received page.
 */
function ats_ga4_purchase() {
    global $wp;

    $order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Prevent duplicate tracking on page refresh
    if ( $order->get_meta( '_ats_ga4_tracked' ) ) {
        return;
    }
    $order->update_meta_data( '_ats_ga4_tracked', '1' );
    $order->save();

    $items = [];
    $index = 0;
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) {
            continue;
        }
        $items[] = ats_ga4_build_item( $product, $item->get_quantity(), $index );
        $index++;
    }

    $data = [
        'transaction_id' => $order->get_order_number(),
        'value'          => (float) $order->get_total(),
        'tax'            => (float) $order->get_total_tax(),
        'shipping'       => (float) $order->get_shipping_total(),
        'currency'       => $order->get_currency(),
        'items'          => $items,
    ];

    // Add coupon if used
    $coupons = $order->get_coupon_codes();
    if ( ! empty( $coupons ) ) {
        $data['coupon'] = implode( ', ', $coupons );
    }

    // Add payment method
    $data['payment_type'] = $order->get_payment_method_title();

    ats_ga4_push_event( 'purchase', $data );
}

/**
 * Add GA4 product data to themeData for client-side JS events.
 */
add_filter( 'skyline_child_localizes', 'ats_ga4_localize_data' );
function ats_ga4_localize_data( $data ) {
    if ( ! function_exists( 'WC' ) ) {
        return $data;
    }

    $ga4 = [
        'measurement_id' => ATS_GA4_MEASUREMENT_ID,
        'currency'       => get_woocommerce_currency(),
    ];

    // Single product page — provide current product data for add_to_cart
    if ( is_product() ) {
        global $product;
        if ( $product instanceof WC_Product ) {
            $ga4['product'] = ats_ga4_build_item( $product );
        }
    }

    // Shop / category / search — provide product map for select_item and add_to_cart
    if ( is_shop() || is_product_category() || is_product_tag() || is_search() ) {
        global $wp_query;

        $list_name = 'Shop';
        if ( is_product_category() ) {
            $term      = get_queried_object();
            $list_name = $term ? $term->name : 'Category';
        } elseif ( is_product_tag() ) {
            $term      = get_queried_object();
            $list_name = $term ? 'Tag: ' . $term->name : 'Tag';
        } elseif ( is_search() ) {
            $list_name = 'Search Results';
        }

        $product_map = [];
        if ( ! empty( $wp_query->posts ) ) {
            foreach ( $wp_query->posts as $index => $post ) {
                $product = wc_get_product( $post->ID );
                if ( ! $product ) {
                    continue;
                }
                $product_map[ $product->get_id() ] = ats_ga4_build_item( $product, 1, $index, $list_name );
            }
        }

        $ga4['products']  = $product_map;
        $ga4['list_name'] = $list_name;
    }

    // Cart page — provide cart items for remove_from_cart
    if ( is_cart() && ! WC()->cart->is_empty() ) {
        $cart_items = [];
        foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
            $product = $cart_item['data'];
            if ( ! $product ) {
                continue;
            }
            $cart_items[ $cart_key ] = ats_ga4_build_item( $product, $cart_item['quantity'] );
            $cart_items[ $cart_key ]['cart_key'] = $cart_key;
        }
        $ga4['cart_items'] = $cart_items;
    }

    // Checkout — provide data for add_shipping_info, add_payment_info
    if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! WC()->cart->is_empty() ) {
        $checkout_items = [];
        $index          = 0;
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            if ( ! $product ) {
                continue;
            }
            $checkout_items[] = ats_ga4_build_item( $product, $cart_item['quantity'], $index );
            $index++;
        }
        $ga4['checkout_items'] = $checkout_items;
        $ga4['checkout_value'] = (float) WC()->cart->get_total( 'edit' );
    }

    $data['ga4'] = $ga4;
    return $data;
}

/**
 * Track login event.
 */
add_action( 'wp_login', 'ats_ga4_track_login', 10, 2 );
function ats_ga4_track_login( $user_login, $user ) {
    if ( ! $user ) {
        return;
    }
    set_transient( 'ats_ga4_login_' . $user->ID, '1', 60 );
}

/**
 * Output login event on next page load after login.
 */
add_action( 'wp_footer', 'ats_ga4_login_event', 51 );
function ats_ga4_login_event() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();
    if ( get_transient( 'ats_ga4_login_' . $user_id ) ) {
        delete_transient( 'ats_ga4_login_' . $user_id );
        ats_ga4_push_event( 'login', [ 'method' => 'site' ] );
    }
}

/**
 * Track sign_up event.
 */
add_action( 'user_register', 'ats_ga4_track_signup' );
function ats_ga4_track_signup( $user_id ) {
    set_transient( 'ats_ga4_signup_' . $user_id, '1', 60 );
}

/**
 * Output sign_up event on next page load after registration.
 */
add_action( 'wp_footer', 'ats_ga4_signup_event', 52 );
function ats_ga4_signup_event() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();
    if ( get_transient( 'ats_ga4_signup_' . $user_id ) ) {
        delete_transient( 'ats_ga4_signup_' . $user_id );
        ats_ga4_push_event( 'sign_up', [ 'method' => 'site' ] );
    }
}

/**
 * Output a gtag event push script tag.
 *
 * @param string $event_name GA4 event name.
 * @param array  $params     Event parameters.
 */
function ats_ga4_push_event( $event_name, $params = [] ) {
    $json = wp_json_encode( $params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    ?>
    <script>
    gtag('event', '<?php echo esc_js( $event_name ); ?>', <?php echo $json; ?>);
    </script>
    <?php
}
