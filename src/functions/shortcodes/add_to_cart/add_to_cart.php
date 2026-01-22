<?php
/**
 * Mini Cart Shortcode
 *
 * Displays a mini shopping cart with AJAX functionality.
 * When empty: Shows "Visit Shop" button
 * When has items: Shows item count, total, and "View Basket" button
 * Includes a modal popup with cart items list
 *
 * @package SkylineWP Dev Child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Track whether the modal has been output
 */
global $ats_mini_cart_modal_rendered;
$ats_mini_cart_modal_rendered = false;

/**
 * Mini Cart Shortcode
 *
 * @return string HTML output
 */
function ats_mini_cart_shortcode() {
    global $ats_mini_cart_modal_rendered;

    // Ensure modal is rendered once in footer
    if ( ! $ats_mini_cart_modal_rendered ) {
        add_action( 'wp_footer', 'ats_render_mini_cart_modal', 50 );
        $ats_mini_cart_modal_rendered = true;
    }

    // Start output buffer
    ob_start();
    ?>
    <div class="rfs-ref-mini-cart-wrapper js-mini-cart-wrapper relative" data-ats-mini-cart data-cart-url="<?php echo esc_url( wc_get_cart_url() ); ?>" data-checkout-url="<?php echo esc_url( wc_get_checkout_url() ); ?>" data-shop-url="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">

        <!-- Empty Cart State (Visit Shop Button) -->
        <div class="rfs-ref-mini-cart-empty js-mini-cart-empty" style="display: none;">
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="rfs-ref-empty-cart-link flex items-center gap-2 ats-btn ats-btn-md ats-btn-yellow">
                <!-- Mobile: Only show icon -->
                <div class="rfs-ref-empty-cart-icon relative">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM246-720l96 200h280l110-200H246Zm-38-80h590q23 0 35 20.5t1 41.5L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68-39.5t-2-78.5l54-98-144-304H40v-80h130l38 80Zm134 280h280-280Z"/>
                    </svg>
                </div>
                <!-- Desktop: Show text -->
                <span class="hidden lg:inline text-sm font-semibold"><?php esc_html_e( 'Visit Shop', 'skylinewp-dev-child' ); ?></span>
            </a>
        </div>

        <!-- Cart Has Items State -->
        <div class="rfs-ref-mini-cart-filled js-mini-cart-filled" style="display: none;">
            <button type="button"
                    class="rfs-ref-mini-cart-toggle js-mini-cart-toggle flex items-center gap-2 lg:gap-6 cursor-pointer"
                    data-modal-target="ats-mini-cart-modal"
                    data-modal-toggle="ats-mini-cart-modal">
                <!-- Cart Icon with Badge -->
                <div class="rfs-ref-mini-cart-icon relative">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" class="text-ats-text">
                        <path d="M280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM246-720l96 200h280l110-200H246Zm-38-80h590q23 0 35 20.5t1 41.5L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68-39.5t-2-78.5l54-98-144-304H40v-80h130l38 80Zm134 280h280-280Z"/>
                    </svg>
                    <span class="rfs-ref-mini-cart-count js-mini-cart-count absolute -top-2 -right-2 bg-ats-yellow text-ats-dark text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">0</span>
                </div>

                <!-- Cart Summary (hidden on mobile, shown on desktop) -->
                <div class="rfs-ref-mini-cart-summary hidden lg:block text-left">
                    <div class="rfs-ref-mini-cart-items-line text-sm font-semibold text-ats-text">
                        <span class="js-mini-cart-items-text">0 items</span>:
                        <span class="js-mini-cart-subtotal text-ats-dark"><?php echo wc_price( 0 ); ?></span>
                    </div>
                    <div class="rfs-ref-mini-cart-total-line text-xs text-ats-text">
                        <?php esc_html_e( 'Total:', 'skylinewp-dev-child' ); ?>
                        <span class="js-mini-cart-total"><?php echo wc_price( 0 ); ?></span>
                        <span class="js-mini-cart-tax">(<?php esc_html_e( 'inc', 'skylinewp-dev-child' ); ?> <?php echo wc_price( 0 ); ?> <?php esc_html_e( 'VAT', 'skylinewp-dev-child' ); ?>)</span>
                    </div>
                </div>
            </button>
        </div>

        <!-- Loading State -->
        <div class="rfs-ref-mini-cart-loading js-mini-cart-loading">
            <div class="flex items-center gap-2 px-4 py-2">
                <svg class="animate-spin h-5 w-5 text-ats-yellow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
					<span class="text-sm text-ats-text whitespace-nowrap"><?php esc_html_e( 'Loading cart...', 'skylinewp-dev-child' ); ?></span>
            </div>
        </div>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'ats_add_to_cart', 'ats_mini_cart_shortcode' );

/**
 * Render the Mini Cart Modal in footer (only once)
 *
 * @return void
 */
function ats_render_mini_cart_modal() {
    ?>
    <!-- Mini Cart Modal (Flowbite) -->
    <div id="ats-mini-cart-modal" tabindex="-1" aria-hidden="true" class="rfs-ref-mini-cart-modal js-mini-cart-modal hidden fixed inset-0 z-50 justify-center items-center">
        <div class="rfs-ref-mini-cart-modal-backdrop js-mini-cart-backdrop fixed inset-0 bg-black bg-opacity-50"></div>
        <div class="rfs-ref-mini-cart-modal-container js-mini-cart-modal-container relative p-4 w-full max-w-xl mx-auto">
            <!-- Modal content -->
            <div class="rfs-ref-mini-cart-modal-content js-mini-cart-modal-content relative bg-white rounded-lg shadow-xl max-h-[90vh] flex flex-col">
                <!-- Modal header -->
                <div class="rfs-ref-mini-cart-modal-header flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-ats-dark flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#373737">
                            <path d="M280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM246-720l96 200h280l110-200H246Zm-38-80h590q23 0 35 20.5t1 41.5L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68-39.5t-2-78.5l54-98-144-304H40v-80h130l38 80Zm134 280h280-280Z"/>
                        </svg>
                        <?php esc_html_e( 'Your Basket', 'skylinewp-dev-child' ); ?>
                        <span class="text-sm font-normal text-ats-text js-modal-item-count">(0 items)</span>
                    </h3>
                    <button type="button" class="rfs-ref-mini-cart-modal-close js-mini-cart-close text-ats-text bg-transparent hover:bg-ats-gray hover:text-ats-dark rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center" data-modal-hide="ats-mini-cart-modal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only"><?php esc_html_e( 'Close modal', 'skylinewp-dev-child' ); ?></span>
                    </button>
                </div>

                <!-- Modal body - Cart Items with Scroll Wrapper -->
                <div class="relative flex-1 overflow-hidden group">
                    <div class="rfs-ref-mini-cart-modal-body js-mini-cart-modal-body js-mini-cart-items p-4 max-h-[408px] overflow-y-auto">
                        <!-- Cart items will be loaded here via AJAX -->
                        <div class="rfs-ref-mini-cart-items-loading flex items-center justify-center py-8">
                            <svg class="animate-spin h-8 w-8 text-ats-yellow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Scroll Indicator Arrow -->
                    <div class="js-mini-cart-scroll-indicator absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10 pointer-events-none transition-opacity duration-300 opacity-0">
                        <div class="bg-white bg-opacity-90 rounded-full shadow-md p-2 border border-gray-100 animate-bounce">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-ats-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Modal footer - Totals and Actions -->
                <div class="rfs-ref-mini-cart-modal-footer border-t border-gray-200 p-4 bg-gray-50 rounded-b-lg">
                    <!-- Totals -->
                    <div class="rfs-ref-mini-cart-modal-totals space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-ats-text"><?php esc_html_e( 'Subtotal:', 'skylinewp-dev-child' ); ?></span>
                            <span class="font-medium text-ats-dark js-modal-subtotal"><?php echo wc_price( 0 ); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ats-text"><?php esc_html_e( 'VAT:', 'skylinewp-dev-child' ); ?></span>
                            <span class="font-medium text-ats-dark js-modal-tax"><?php echo wc_price( 0 ); ?></span>
                        </div>
                        <div class="flex justify-between text-base font-semibold border-t border-gray-200 pt-2">
                            <span class="text-ats-dark"><?php esc_html_e( 'Total:', 'skylinewp-dev-child' ); ?></span>
                            <span class="text-ats-dark js-modal-total"><?php echo wc_price( 0 ); ?></span>
                        </div>
                        <p class="text-xs text-ats-text text-center">
                            <?php esc_html_e( 'Shipping and discounts will be calculated at checkout.', 'skylinewp-dev-child' ); ?>
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="rfs-ref-mini-cart-modal-actions flex flex-col gap-2">
                        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="ats-btn ats-btn-md ats-btn-yellow w-full text-center">
                            <?php esc_html_e( 'Proceed to Checkout', 'skylinewp-dev-child' ); ?>
                        </a>
                        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="ats-btn ats-btn-md ats-btn-outline w-full text-center">
                            <?php esc_html_e( 'View Full Basket', 'skylinewp-dev-child' ); ?>
                        </a>
                        <button type="button" class="js-mini-cart-close text-sm text-ats-text hover:text-ats-dark underline" data-modal-hide="ats-mini-cart-modal">
                            <?php esc_html_e( 'Continue Shopping', 'skylinewp-dev-child' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Ensure WooCommerce cart is initialized for AJAX requests
 * This is important for cached pages
 */
function ats_ensure_cart_loaded() {
    if ( null === WC()->cart ) {
        wc_load_cart();
    }
}
