<?php
/**
 * Single Product tabs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/tabs.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter tabs and allow third parties to add their own.
 *
 * Each tab is an array containing title, callback and priority.
 *
 * @see woocommerce_default_product_tabs()
 */
$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

if ( ! empty( $product_tabs ) ) : ?>

    <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="product-tabs-list" data-tabs-toggle="#product-tabs-content" role="tablist">
            <?php $i = 0; foreach ( $product_tabs as $key => $product_tab ) : $i++; ?>
                <li class="me-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-ats-brand hover:border-ats-brand aria-selected:text-ats-brand aria-selected:border-ats-brand"
                            id="tab-title-<?php echo esc_attr( $key ); ?>"
                            data-tabs-target="#tab-<?php echo esc_attr( $key ); ?>"
                            type="button"
                            role="tab"
                            aria-controls="tab-<?php echo esc_attr( $key ); ?>"
                            aria-selected="<?php echo $i === 1 ? 'true' : 'false'; ?>">
                        <?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key ) ); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div id="product-tabs-content">
        <?php $i = 0; foreach ( $product_tabs as $key => $product_tab ) : $i++; ?>
            <div class="<?php echo $i === 1 ? '' : 'hidden'; ?>  text-sm text-ats-text prose prose-sm w-full max-w-full" id="tab-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="tab-title-<?php echo esc_attr( $key ); ?>">
                <?php
                if ( isset( $product_tab['callback'] ) ) {
                    call_user_func( $product_tab['callback'], $key, $product_tab );
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
