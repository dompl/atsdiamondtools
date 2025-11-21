<?php
/**
 * My Addresses
 *
 * @package skylinewp-dev-child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        [
            'billing' => __( 'Billing address', 'woocommerce' ),
            'shipping' => __( 'Shipping address', 'woocommerce' ),
        ],
        $customer_id
    );
} else {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        [
            'billing' => __( 'Billing address', 'woocommerce' ),
        ],
        $customer_id
    );
}

do_action( 'woocommerce_before_account_addresses' );
?>

<div class="rfs-ref-addresses-wrapper">

    <h2 class="text-2xl font-bold text-gray-900 mb-6"><?php esc_html_e( 'Addresses', 'woocommerce' ); ?></h2>
    <p class="text-gray-500 mb-8 text-sm"><?php esc_html_e( 'The following addresses will be used on the checkout page by default.', 'woocommerce' ); ?></p>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        <?php foreach ( $get_addresses as $name => $address_title ) : ?>
            <?php
            $address = wc_get_account_formatted_address( $name );
            $address_fields = WC()->countries->get_address_fields( '', $name . '_' );

            // Get individual address fields
            $first_name = get_user_meta( $customer_id, $name . '_first_name', true );
            $last_name = get_user_meta( $customer_id, $name . '_last_name', true );
            $phone = get_user_meta( $customer_id, $name . '_phone', true );
            $email = get_user_meta( $customer_id, $name . '_email', true );

            $is_default = ( $name === 'billing' );
            ?>

            <!-- Address Card -->
            <div class="rfs-ref-address-card border border-gray-100 rounded-lg p-8 hover:shadow-md transition-shadow relative flex flex-col h-full">
                <?php if ( $is_default && $address ) : ?>
                <div class="absolute top-0 right-0 bg-ats-yellow text-black text-[10px] font-bold px-2 py-1 uppercase tracking-widest rounded-bl-md rounded-tr-md">
                    <?php esc_html_e( 'Default', 'woocommerce' ); ?>
                </div>
                <?php endif; ?>

                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <?php echo esc_html( trim( $first_name . ' ' . $last_name ) ?: __( 'No name set', 'woocommerce' ) ); ?>
                </h3>

                <div class="text-sm text-gray-500 space-y-1 mb-6 flex-grow">
                    <?php if ( $address ) : ?>
                        <?php echo wp_kses_post( $address ); ?>
                        <?php if ( $phone ) : ?>
                        <p class="mt-4">
                            <span class="text-gray-300 text-xs uppercase tracking-wider font-bold"><?php esc_html_e( 'Phone', 'woocommerce' ); ?></span><br/>
                            <?php echo esc_html( $phone ); ?>
                        </p>
                        <?php endif; ?>
                        <?php if ( $email ) : ?>
                        <p class="mt-2">
                            <span class="text-gray-300 text-xs uppercase tracking-wider font-bold"><?php esc_html_e( 'Email', 'woocommerce' ); ?></span><br/>
                            <?php echo esc_html( $email ); ?>
                        </p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="text-gray-400 italic"><?php esc_html_e( 'You have not set up this type of address yet.', 'woocommerce' ); ?></p>
                    <?php endif; ?>
                </div>

                <div class="flex gap-4 mt-4 border-t border-gray-100 pt-4">
                    <a
                        href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>"
                        class="text-ats-dark font-bold text-sm hover:text-ats-yellow transition-colors"
                    >
                        <?php esc_html_e( 'Edit', 'woocommerce' ); ?>
                    </a>
                    <?php if ( $address && ! $is_default ) : ?>
                    <button class="text-red-500 font-bold text-sm hover:text-red-700 transition-colors">
                        <?php esc_html_e( 'Remove', 'woocommerce' ); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; ?>

        <!-- Add New Card -->
        <button
            onclick="location.href='<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing' ) ); ?>'"
            class="rfs-ref-add-new-card border-2 border-dashed border-gray-200 rounded-lg p-8 flex flex-col items-center justify-center text-center hover:border-ats-yellow hover:bg-ats-yellow/5 transition-all group h-full min-h-[300px]"
        >
            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center text-gray-300 mb-4 group-hover:bg-white group-hover:text-ats-yellow transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <span class="font-bold text-gray-900 group-hover:text-ats-dark"><?php esc_html_e( 'Add New', 'woocommerce' ); ?></span>
        </button>

    </div>

    <?php do_action( 'woocommerce_after_account_addresses' ); ?>

</div>
