<?php
/**
 * Edit Address Form
 *
 * @package skylinewp-dev-child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = ( 'billing' === $load_address ) ? esc_html__( 'Billing Address', 'woocommerce' ) : esc_html__( 'Shipping Address', 'woocommerce' );

do_action( 'woocommerce_before_edit_account_address_form' );
?>

<div class="rfs-ref-edit-address-wrapper">

    <h2 class="text-2xl font-bold text-gray-900 mb-6"><?php echo esc_html( $page_title ); ?></h2>

    <?php if ( ! $load_address ) : ?>
        <?php wc_get_template( 'myaccount/my-address.php' ); ?>
    <?php else : ?>

        <form method="post" class="rfs-ref-edit-address-form space-y-6">

            <?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

            <div class="space-y-6">
                <?php
                foreach ( $address as $key => $field ) :
                    $field_value = wc_get_post_data_by_key( $key, get_user_meta( get_current_user_id(), $key, true ) );
                    $field_key = $key;

                    // Determine if this should be in a 2-column layout
                    $is_half_width = in_array(
                        $key,
                        [
                            $load_address . '_first_name',
                            $load_address . '_last_name',
                            $load_address . '_email',
                            $load_address . '_phone'
                        ]
                    );
                    ?>

                    <div class="<?php echo $is_half_width ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : ''; ?>">
                        <?php if ( $is_half_width ) : ?>
                            <div class="space-y-2">
                        <?php endif; ?>

                        <label for="<?php echo esc_attr( $field_key ); ?>" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <?php echo esc_html( $field['label'] ); ?>
                            <?php if ( ! empty( $field['required'] ) ) : ?>
                                <span class="text-red-500">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ( $field['type'] === 'country' ) : ?>
                            <select
                                name="<?php echo esc_attr( $field_key ); ?>"
                                id="<?php echo esc_attr( $field_key ); ?>"
                                class="country_to_state w-full h-12 px-4 bg-white border border-gray-200 rounded-md focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-all"
                                autocomplete="country"
                                data-no-selectwoo="true"
                                <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
                            >
                                <option value=""><?php esc_html_e( 'Select a country / region&hellip;', 'woocommerce' ); ?></option>
                                <?php
                                foreach ( $field['options'] as $option_key => $option_value ) :
                                    echo '<option value="' . esc_attr( $option_key ) . '" ' . selected( $field_value, $option_key, false ) . '>' . esc_html( $option_value ) . '</option>';
                                endforeach;
                                ?>
                            </select>

                        <?php elseif ( $field['type'] === 'state' ) : ?>
                            <?php
                            $country_key = $load_address . '_country';
                            $current_country = wc_get_post_data_by_key( $country_key, get_user_meta( get_current_user_id(), $country_key, true ) );
                            $states = WC()->countries->get_states( $current_country );

                            if ( is_array( $states ) && ! empty( $states ) ) : ?>
                                <select
                                    name="<?php echo esc_attr( $field_key ); ?>"
                                    id="<?php echo esc_attr( $field_key ); ?>"
                                    class="state_select w-full h-12 px-4 bg-white border border-gray-200 rounded-md focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-all"
                                    autocomplete="address-level1"
                                    data-placeholder="<?php esc_attr_e( 'Select an option&hellip;', 'woocommerce' ); ?>"
                                    <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
                                >
                                    <option value=""><?php esc_html_e( 'Select an option&hellip;', 'woocommerce' ); ?></option>
                                    <?php
                                    foreach ( $states as $state_key => $state_value ) :
                                        echo '<option value="' . esc_attr( $state_key ) . '" ' . selected( $field_value, $state_key, false ) . '>' . esc_html( $state_value ) . '</option>';
                                    endforeach;
                                    ?>
                                </select>
                            <?php else : ?>
                                <input
                                    type="text"
                                    name="<?php echo esc_attr( $field_key ); ?>"
                                    id="<?php echo esc_attr( $field_key ); ?>"
                                    value="<?php echo esc_attr( $field_value ); ?>"
                                    placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
                                    class="w-full h-12 px-4 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors"
                                    autocomplete="address-level1"
                                    <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
                                />
                            <?php endif; ?>

                        <?php elseif ( $field['type'] === 'textarea' ) : ?>
                            <textarea
                                name="<?php echo esc_attr( $field_key ); ?>"
                                id="<?php echo esc_attr( $field_key ); ?>"
                                placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
                                rows="3"
                                class="w-full px-4 py-3 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors resize-none"
                                <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
                            ><?php echo esc_textarea( $field_value ); ?></textarea>

                        <?php elseif ( $field['type'] === 'select' ) : ?>
                            <select
                                name="<?php echo esc_attr( $field_key ); ?>"
                                id="<?php echo esc_attr( $field_key ); ?>"
                                class="w-full h-12 px-4 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors"
                                <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
                            >
                                <?php foreach ( $field['options'] as $option_key => $option_value ) : ?>
                                    <option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $field_value, $option_key ); ?>>
                                        <?php echo esc_html( $option_value ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php else : ?>
                            <input
                                type="<?php echo esc_attr( $field['type'] ); ?>"
                                name="<?php echo esc_attr( $field_key ); ?>"
                                id="<?php echo esc_attr( $field_key ); ?>"
                                value="<?php echo esc_attr( $field_value ); ?>"
                                placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
                                class="w-full h-12 px-4 bg-white border border-gray-200 rounded-sm focus:border-ats-yellow focus:ring-2 focus:ring-ats-yellow/20 outline-none transition-colors"
                                <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
                                <?php if ( isset( $field['autocomplete'] ) ) : ?>autocomplete="<?php echo esc_attr( $field['autocomplete'] ); ?>"<?php endif; ?>
                            />
                        <?php endif; ?>

                        <?php if ( $is_half_width ) : ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php endforeach; ?>
            </div>

            <?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

            <div class="flex gap-4 pt-4">
                <?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
                <button
                    type="submit"
                    name="save_address"
                    value="<?php esc_attr_e( 'Save address', 'woocommerce' ); ?>"
                    class="bg-ats-yellow hover:bg-[#e6bd00] text-black font-bold uppercase text-sm tracking-widest px-8 py-4 rounded-sm shadow-sm shadow-ats-yellow/20 hover:shadow-md transition-colors duration-200"
                >
                    <?php esc_html_e( 'Save Address', 'woocommerce' ); ?>
                </button>
                <a
                    href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address' ) ); ?>"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-900 font-bold uppercase text-sm tracking-widest px-8 py-4 rounded-sm transition-colors duration-200 inline-flex items-center"
                >
                    <?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
                </a>
            </div>

        </form>

    <?php endif; ?>

</div>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
