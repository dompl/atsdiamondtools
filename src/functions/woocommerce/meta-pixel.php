<?php
/**
 * Meta (Facebook) Pixel - Standard Event Tracking
 *
 * SCAFFOLD — INERT until a Pixel ID is supplied.
 *
 * Mirrors the GA4 implementation (ga4-tracking.php). The base pixel + PageView
 * are printed in header.php; this file fires the server-side standard events
 * (ViewContent, InitiateCheckout, Purchase). AddToCart is fired client-side by
 * assets/js/components/meta-pixel-events.js off the same `added_to_cart` event
 * that GA4 uses.
 *
 * To activate: add the client's Pixel/dataset ID to wp-config.php:
 *
 *     define( 'ATS_META_PIXEL_ID', '0000000000000000' );
 *
 * Conversions API (CAPI) is intentionally NOT implemented here — it needs an
 * access token from the client's Meta Business account. Each browser event is
 * emitted with a deterministic `eventID` so a future CAPI integration can send
 * the matching server event and deduplicate against the browser pixel.
 *
 * Internal/staff traffic is excluded via ats_ga4_is_excluded_user() so GA4 and
 * the Pixel share one source of truth for exclusion.
 *
 * @package ATS Diamond Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Inert until a Pixel ID is configured.
if ( ! defined( 'ATS_META_PIXEL_ID' ) || empty( ATS_META_PIXEL_ID ) ) {
    return;
}

/**
 * Whether the Meta Pixel should fire for this request.
 *
 * Reuses the GA4 staff-exclusion helper so both platforms agree on what counts
 * as internal traffic. Falls back to firing if the helper is unavailable.
 *
 * @return bool
 */
function ats_meta_pixel_is_active() {
    if ( function_exists( 'ats_ga4_is_excluded_user' ) && ats_ga4_is_excluded_user() ) {
        return false;
    }
    return true;
}

/**
 * Build the standard content payload for a set of products.
 *
 * @param array $line_items Array of [ WC_Product $product, int $quantity ].
 * @return array Meta Pixel content parameters (content_ids, contents, num_items).
 */
function ats_meta_pixel_build_contents( $line_items ) {
    $content_ids = [];
    $contents    = [];
    $num_items   = 0;

    foreach ( $line_items as $line ) {
        $product = $line['product'];
        $qty     = isset( $line['quantity'] ) ? (int) $line['quantity'] : 1;
        if ( ! $product instanceof WC_Product ) {
            continue;
        }
        // Match GA4 item_id semantics: SKU when present, else the product ID.
        $id            = $product->get_sku() ?: (string) $product->get_id();
        $content_ids[] = $id;
        $contents[]    = [
            'id'         => $id,
            'quantity'   => $qty,
            'item_price' => (float) $product->get_price(),
        ];
        $num_items += $qty;
    }

    return [
        'content_ids'  => $content_ids,
        'contents'     => $contents,
        'content_type' => 'product',
        'num_items'    => $num_items,
    ];
}

/**
 * Output a Meta Pixel standard-event script tag.
 *
 * @param string $event_name Standard event name (e.g. ViewContent, Purchase).
 * @param array  $params     Event parameters.
 * @param string $event_id   Deterministic event ID for CAPI deduplication.
 */
function ats_meta_pixel_track( $event_name, $params = [], $event_id = '' ) {
    if ( ! ats_meta_pixel_is_active() ) {
        return;
    }

    $json     = wp_json_encode( $params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    $event_id = $event_id ? $event_id : wp_generate_uuid4();
    ?>
    <script>
    if (typeof fbq === 'function') {
        fbq('track', <?php echo wp_json_encode( $event_name ); ?>, <?php echo $json; ?>, { eventID: <?php echo wp_json_encode( $event_id ); ?> });
    }
    </script>
    <?php
}

/**
 * Fire the page-level Meta Pixel standard events in the footer.
 *
 * Runs at priority 51 (just after GA4's page events at 50) so the base pixel
 * snippet in <head> has already defined fbq.
 */
add_action( 'wp_footer', 'ats_meta_pixel_page_events', 51 );
function ats_meta_pixel_page_events() {
    if ( ! function_exists( 'WC' ) || ! ats_meta_pixel_is_active() ) {
        return;
    }

    // Single product — ViewContent.
    if ( is_product() ) {
        ats_meta_pixel_view_content();
        return;
    }

    // Checkout (not the thank-you page) — InitiateCheckout.
    if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! WC()->cart->is_empty() ) {
        ats_meta_pixel_initiate_checkout();
        return;
    }

    // Thank-you / order-received — Purchase.
    if ( is_wc_endpoint_url( 'order-received' ) ) {
        ats_meta_pixel_purchase();
        return;
    }
}

/**
 * ViewContent — single product page.
 */
function ats_meta_pixel_view_content() {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $contents = ats_meta_pixel_build_contents( [ [ 'product' => $product, 'quantity' => 1 ] ] );

    ats_meta_pixel_track(
        'ViewContent',
        array_merge(
            $contents,
            [
                'content_name' => $product->get_name(),
                'value'        => (float) $product->get_price(),
                'currency'     => get_woocommerce_currency(),
            ]
        ),
        'vc_' . $product->get_id()
    );
}

/**
 * InitiateCheckout — checkout page.
 */
function ats_meta_pixel_initiate_checkout() {
    $cart        = WC()->cart;
    $line_items  = [];

    foreach ( $cart->get_cart() as $cart_item ) {
        if ( empty( $cart_item['data'] ) ) {
            continue;
        }
        $line_items[] = [ 'product' => $cart_item['data'], 'quantity' => $cart_item['quantity'] ];
    }

    $contents = ats_meta_pixel_build_contents( $line_items );

    ats_meta_pixel_track(
        'InitiateCheckout',
        array_merge(
            $contents,
            [
                'value'    => (float) $cart->get_total( 'edit' ),
                'currency' => get_woocommerce_currency(),
            ]
        )
    );
}

/**
 * Purchase — thank-you / order-received page.
 */
function ats_meta_pixel_purchase() {
    global $wp;

    $order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Prevent duplicate tracking on refresh (separate key from GA4's).
    if ( $order->get_meta( '_ats_meta_pixel_tracked' ) ) {
        return;
    }
    $order->update_meta_data( '_ats_meta_pixel_tracked', '1' );
    $order->save();

    $line_items = [];
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) {
            continue;
        }
        $line_items[] = [ 'product' => $product, 'quantity' => $item->get_quantity() ];
    }

    $contents = ats_meta_pixel_build_contents( $line_items );

    ats_meta_pixel_track(
        'Purchase',
        array_merge(
            $contents,
            [
                'value'    => (float) $order->get_total(),
                'currency' => $order->get_currency(),
            ]
        ),
        // Deterministic: lets a future CAPI call dedupe against this browser event.
        'order_' . $order->get_id()
    );
}
