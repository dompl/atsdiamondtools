<?php
/**
 * Product attributes
 *
 * Used by list_attributes() in the products class.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-attributes.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! $product_attributes ) {
	return;
}
?>
<div class="relative overflow-x-auto shadow-sm rounded-lg border border-gray-200">
	<table class="w-full text-sm text-left rtl:text-right text-gray-500 woocommerce-product-attributes shop_attributes m-0" aria-label="<?php esc_attr_e( 'Product Details', 'woocommerce' ); ?>">
		<tbody>
			<?php foreach ( $product_attributes as $product_attribute_key => $product_attribute ) : ?>
				<tr class="border-b border-gray-200 last:border-b-0 woocommerce-product-attributes-item woocommerce-product-attributes-item--<?php echo esc_attr( $product_attribute_key ); ?>">
					<th class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap bg-gray-50 woocommerce-product-attributes-item__label" scope="row">
						<?php echo wp_kses_post( $product_attribute['label'] ); ?>
					</th>
					<td class="px-6 py-4 bg-white woocommerce-product-attributes-item__value">
						<?php echo wp_kses_post( $product_attribute['value'] ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
