<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
$customer_orders = wc_get_orders( [
    'customer' => get_current_user_id(),
    'limit' => 3,
    'orderby' => 'date',
    'order' => 'DESC',
] );

// Get default address
$billing_first_name = get_user_meta( get_current_user_id(), 'billing_first_name', true );
$billing_last_name = get_user_meta( get_current_user_id(), 'billing_last_name', true );
$billing_country = get_user_meta( get_current_user_id(), 'billing_country', true );
$billing_postcode = get_user_meta( get_current_user_id(), 'billing_postcode', true );
$billing_city = get_user_meta( get_current_user_id(), 'billing_city', true );
$billing_address_1 = get_user_meta( get_current_user_id(), 'billing_address_1', true );
$billing_phone = get_user_meta( get_current_user_id(), 'billing_phone', true );
$billing_email = get_user_meta( get_current_user_id(), 'billing_email', true );
?>

<div class="rfs-ref-dashboard-wrapper">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        <!-- Profile Summary Card -->
        <div class="rfs-ref-profile-card border border-gray-100 rounded-lg p-8 flex flex-col items-center text-center hover:shadow-md transition-shadow">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                <?php echo get_avatar( get_current_user_id(), 96, '', '', [ 'class' => 'w-full h-full rounded-full object-cover' ] ); ?>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-1"><?php echo esc_html( $current_user->display_name ); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html( $current_user->user_email ); ?></p>
            <a
                href="<?php echo esc_url( wc_get_endpoint_url( 'edit-account' ) ); ?>"
                class="bg-gray-100 hover:bg-gray-200 text-gray-900 font-bold text-xs uppercase tracking-wider px-6 py-3 rounded-sm transition-colors"
            >
                <?php esc_html_e( 'Edit Profile', 'woocommerce' ); ?>
            </a>
        </div>

        <!-- Address Summary Card -->
        <div class="rfs-ref-address-card border border-gray-100 rounded-lg p-8 hover:shadow-md transition-shadow relative">
            <div class="absolute top-0 right-0 bg-ats-yellow text-black text-[10px] font-bold px-2 py-1 uppercase tracking-widest rounded-bl-md rounded-tr-md">
                <?php esc_html_e( 'Default Address', 'woocommerce' ); ?>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-4"><?php echo esc_html( $billing_first_name . ' ' . $billing_last_name ); ?></h3>
            <div class="text-sm text-gray-500 space-y-1 mb-6">
                <?php if ( $billing_country ) : ?><p><?php echo esc_html( WC()->countries->countries[ $billing_country ] ?? $billing_country ); ?></p><?php endif; ?>
                <?php if ( $billing_postcode || $billing_city ) : ?><p><?php echo esc_html( trim( $billing_postcode . ', ' . $billing_city, ', ' ) ); ?></p><?php endif; ?>
                <?php if ( $billing_address_1 ) : ?><p><?php echo esc_html( $billing_address_1 ); ?></p><?php endif; ?>
            </div>
            <div class="space-y-1 mb-6">
                <?php if ( $billing_phone ) : ?>
                <p class="text-sm text-gray-900"><span class="text-gray-400 text-xs uppercase tracking-wider font-bold block"><?php esc_html_e( 'Phone Number', 'woocommerce' ); ?></span> <?php echo esc_html( $billing_phone ); ?></p>
                <?php endif; ?>
                <p class="text-sm text-gray-900"><span class="text-gray-400 text-xs uppercase tracking-wider font-bold block"><?php esc_html_e( 'Email Address', 'woocommerce' ); ?></span> <?php echo esc_html( $billing_email ?: $current_user->user_email ); ?></p>
            </div>
            <a
                href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address' ) ); ?>"
                class="text-ats-dark font-bold text-sm hover:text-ats-yellow transition-colors"
            >
                <?php esc_html_e( 'Edit Address', 'woocommerce' ); ?>
            </a>
        </div>

    </div>

    <div class="mt-12">
         <h3 class="text-2xl font-bold text-gray-900 mb-6"><?php esc_html_e( 'Recent Orders', 'woocommerce' ); ?></h3>
         <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-4"><?php esc_html_e( 'Order', 'woocommerce' ); ?></th>
                        <th class="py-4"><?php esc_html_e( 'Date', 'woocommerce' ); ?></th>
                        <th class="py-4"><?php esc_html_e( 'Status', 'woocommerce' ); ?></th>
                        <th class="py-4"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-600">
                    <?php if ( ! empty( $customer_orders ) ) : ?>
                        <?php foreach ( $customer_orders as $customer_order ) : ?>
                            <?php
                            $order = wc_get_order( $customer_order );
                            $item_count = $order->get_item_count();
                            ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="py-4">
                                    <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="font-bold text-ats-dark hover:text-ats-yellow cursor-pointer">
                                        <?php echo esc_html( '#' . $order->get_order_number() ); ?>
                                    </a>
                                </td>
                                <td class="py-4"><?php echo esc_html( $order->get_date_created()->date_i18n( 'd F, Y' ) ); ?></td>
                                <td class="py-4"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
                                <td class="py-4">
                                    <?php
                                    /* translators: 1: total 2: item count */
                                    printf(
                                        esc_html__( '%1$s for %2$s item(s)', 'woocommerce' ),
                                        wp_kses_post( $order->get_formatted_order_total() ),
                                        esc_html( $item_count )
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="py-8 text-center text-gray-400">
                                <?php esc_html_e( 'No orders yet.', 'woocommerce' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
         </div>
    </div>

</div>
