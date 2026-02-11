<?php
/**
 * ATS Price Manager Shortcode
 *
 * Lists all products and variations with inline-editable prices.
 * Prices auto-save via AJAX on input change. Admin-only.
 *
 * Usage: [ats_price_manager]
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'ats_price_manager', 'ats_price_manager_shortcode' );

/**
 * Allow input/label/button elements through wp_kses_post when price manager is active.
 * The simple_content component runs wp_kses_post() which strips form elements.
 */
add_filter( 'wp_kses_allowed_html', 'ats_price_manager_allow_form_elements', 10, 2 );
function ats_price_manager_allow_form_elements( $tags, $context ) {
	if ( 'post' !== $context ) {
		return $tags;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return $tags;
	}

	$tags['input'] = array(
		'type'            => true,
		'id'              => true,
		'class'           => true,
		'value'           => true,
		'name'            => true,
		'placeholder'     => true,
		'step'            => true,
		'min'             => true,
		'max'             => true,
		'checked'         => true,
		'data-*'          => true,
		'data-product-id' => true,
		'data-field'      => true,
		'data-original'   => true,
	);

	$tags['label'] = array(
		'for'   => true,
		'class' => true,
	);

	$tags['button'] = array(
		'type'            => true,
		'class'           => true,
		'data-*'          => true,
		'data-product-id' => true,
		'data-product-name' => true,
		'title'           => true,
	);

	return $tags;
}

/**
 * Render the price manager shortcode
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function ats_price_manager_shortcode( $atts = array() ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '<p class="rfs-ref-price-manager-denied text-red-600 font-bold p-4">You do not have permission to manage prices.</p>';
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return '<!-- ATS Price Manager: WooCommerce is required -->';
	}

	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$products_query = new WP_Query( $args );
	$total_products = $products_query->found_posts;

	ob_start();
	?>
<div class="rfs-ref-price-manager" data-component="price-manager">
<div class="rfs-ref-price-manager-header flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
<div>
<h2 class="rfs-ref-price-manager-title text-2xl font-bold text-ats-dark">Price Manager</h2>
<p class="rfs-ref-price-manager-count text-sm text-gray-500 mt-1"><span class="js-product-count"><?php echo esc_html( $total_products ); ?></span> products</p>
</div>
<div class="rfs-ref-price-manager-search flex flex-wrap items-center gap-3">
<input type="text" id="price-manager-search" class="rfs-ref-price-manager-search-input border border-gray-300 rounded px-3 py-2 text-sm w-64 focus:border-ats-yellow focus:ring-1 focus:ring-ats-yellow focus:outline-none" placeholder="Search by name or SKU..." />
<label class="rfs-ref-price-manager-filter-label flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="checkbox" id="price-manager-sale-only" class="rfs-ref-price-manager-sale-checkbox rounded border-gray-300 text-ats-yellow focus:ring-ats-yellow" /> On sale only</label>
<label class="rfs-ref-price-manager-filter-label flex items-center gap-2 text-sm text-gray-600 cursor-pointer"><input type="checkbox" id="price-manager-duplicates" class="rfs-ref-price-manager-dupes-checkbox rounded border-gray-300 text-red-500 focus:ring-red-500" /> <span class="text-red-600 font-medium">Duplicates only <span class="js-duplicate-count"></span></span></label>
</div>
</div>
<div class="rfs-ref-price-manager-table-wrap overflow-x-auto border border-gray-200 rounded-lg">
<table class="rfs-ref-price-manager-table w-full text-sm text-left">
<thead class="rfs-ref-price-manager-thead bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
<tr>
<th class="rfs-ref-price-manager-th-product px-4 py-3 font-medium">Product</th>
<th class="rfs-ref-price-manager-th-sku px-4 py-3 font-medium w-32">SKU</th>
<th class="rfs-ref-price-manager-th-regular px-4 py-3 font-medium w-36">Regular Price</th>
<th class="rfs-ref-price-manager-th-sale px-4 py-3 font-medium w-36">Sale Price</th>
<th class="rfs-ref-price-manager-th-status px-4 py-3 font-medium w-20 text-center">Status</th>
<th class="rfs-ref-price-manager-th-actions px-4 py-3 font-medium w-16 text-center">Action</th>
</tr>
</thead>
<tbody class="rfs-ref-price-manager-tbody divide-y divide-gray-100">
<?php
if ( $products_query->have_posts() ) :
	while ( $products_query->have_posts() ) :
		$products_query->the_post();
		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			continue;
		}

		$is_variable = $product->is_type( 'variable' );

		ats_price_manager_render_row( $product, false, $is_variable );

		if ( $is_variable ) {
			$variations = $product->get_available_variations();
			foreach ( $variations as $variation_data ) {
				$variation = wc_get_product( $variation_data['variation_id'] );
				if ( $variation ) {
					ats_price_manager_render_row( $variation, true, false );
				}
			}
		}

	endwhile;
	wp_reset_postdata();
else :
?>
<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No products found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="rfs-ref-price-manager-toast js-price-toast fixed bottom-6 right-6 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium transition-all duration-300 opacity-0 pointer-events-none z-50">Price updated</div>
<div class="rfs-ref-price-manager-error-toast js-price-error-toast fixed bottom-6 right-6 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium transition-all duration-300 opacity-0 pointer-events-none z-50">Error updating price</div>
</div>
	<?php
	return ob_get_clean();
}

/**
 * Render a single product/variation row in the price manager table
 *
 * @param WC_Product $product      Product or variation object.
 * @param bool       $is_variation Whether this is a variation row.
 * @param bool       $is_parent    Whether this is a variable parent (no price inputs).
 * @return void
 */
function ats_price_manager_render_row( $product, $is_variation = false, $is_parent = false ) {
	$product_id    = $product->get_id();
	$regular_price = $product->get_regular_price();
	$sale_price    = $product->get_sale_price();
	$sku           = $product->get_sku();
	$name          = $product->get_name();

	// For variations, build attribute string and variation key for duplicate detection
	$variation_label = '';
	$variation_key   = '';
	if ( $is_variation && $product->is_type( 'variation' ) ) {
		$attributes = $product->get_attributes();
		$attr_parts = array();
		foreach ( $attributes as $attr_name => $attr_value ) {
			$label = wc_attribute_label( $attr_name, $product );
			$value = $attr_value;

			$term = get_term_by( 'slug', $attr_value, $attr_name );
			if ( $term && ! is_wp_error( $term ) ) {
				$value = $term->name;
			}

			$attr_parts[] = $label . ': ' . $value;
		}
		$variation_label = implode( ', ', $attr_parts );

		// Build a key: parent_id + sorted attribute values for duplicate detection
		$parent_id     = $product->get_parent_id();
		$variation_key = $parent_id . '|' . implode( '|', array_values( $attributes ) );
	}

	$row_classes = $is_variation ? 'rfs-ref-price-manager-variation-row bg-gray-50/50' : 'rfs-ref-price-manager-product-row bg-white';
	$has_sale    = '' !== $sale_price && null !== $sale_price;

	$esc_id   = esc_attr( $product_id );
	$esc_name = esc_attr( strtolower( $name ) );
	$esc_sku  = esc_attr( strtolower( $sku ) );

	// Build name cell
	if ( $is_variation ) {
		$name_html = '<span class="rfs-ref-price-manager-variation-indent text-gray-300 pl-4">&#8627;</span> <span class="rfs-ref-price-manager-variation-label text-xs text-gray-600">' . esc_html( $variation_label ) . '</span>';
	} else {
		$bold      = $is_parent ? ' font-bold' : '';
		$name_html = '<span class="rfs-ref-price-manager-product-name font-medium text-ats-dark' . $bold . '">' . esc_html( $name ) . '</span>';
		if ( $is_parent ) {
			$name_html .= ' <span class="rfs-ref-price-manager-variable-badge text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-medium">Variable</span>';
		}
	}

	// Build price cells
	if ( ! $is_parent ) {
		$regular_cell = '<div class="rfs-ref-price-manager-input-wrap relative flex items-center"><span class="rfs-ref-price-manager-currency absolute left-2.5 text-gray-400 text-xs pointer-events-none">&pound;</span><input type="number" step="0.01" min="0" class="rfs-ref-price-manager-regular-input js-price-input w-full border border-gray-300 rounded pl-6 pr-2 py-1.5 text-sm text-right focus:border-ats-yellow focus:ring-1 focus:ring-ats-yellow focus:outline-none" value="' . esc_attr( $regular_price ) . '" data-product-id="' . $esc_id . '" data-field="regular_price" data-original="' . esc_attr( $regular_price ) . '" /></div>';

		$sale_cell = '<div class="rfs-ref-price-manager-input-wrap relative flex items-center"><span class="rfs-ref-price-manager-currency absolute left-2.5 text-gray-400 text-xs pointer-events-none">&pound;</span><input type="number" step="0.01" min="0" class="rfs-ref-price-manager-sale-input js-price-input w-full border border-gray-300 rounded pl-6 pr-2 py-1.5 text-sm text-right focus:border-ats-yellow focus:ring-1 focus:ring-ats-yellow focus:outline-none" value="' . esc_attr( $sale_price ) . '" data-product-id="' . $esc_id . '" data-field="sale_price" data-original="' . esc_attr( $sale_price ) . '" placeholder="No sale" /></div>';
	} else {
		$regular_cell = '<span class="rfs-ref-price-manager-parent-notice text-xs text-gray-400 italic">Set on variations</span>';
		$sale_cell    = '<span class="rfs-ref-price-manager-parent-notice text-xs text-gray-400 italic">Set on variations</span>';
	}

	// Build action cell - delete button for variations only
	if ( $is_variation ) {
		$action_cell = '<button type="button" class="js-delete-variation rfs-ref-price-manager-delete-btn text-gray-400 hover:text-red-600 transition-colors p-1" data-product-id="' . $esc_id . '" data-product-name="' . esc_attr( $name . ' - ' . $variation_label ) . '" title="Delete variation"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>';
	} else {
		$action_cell = '';
	}

	// Output row
	echo '<tr class="' . esc_attr( $row_classes ) . ' js-price-row hover:bg-yellow-50/30 transition-colors" data-product-id="' . $esc_id . '" data-product-name="' . $esc_name . '" data-sku="' . $esc_sku . '" data-has-sale="' . ( $has_sale ? '1' : '0' ) . '" data-is-parent="' . ( $is_parent ? '1' : '0' ) . '" data-variation-key="' . esc_attr( $variation_key ) . '">';
	echo '<td class="rfs-ref-price-manager-td-product px-4 py-2.5"><div class="flex items-center gap-2">' . $name_html . '</div></td>';
	echo '<td class="rfs-ref-price-manager-td-sku px-4 py-2.5 text-xs text-gray-500 font-mono">' . esc_html( $sku ?: '-' ) . '</td>';
	echo '<td class="rfs-ref-price-manager-td-regular px-4 py-2.5">' . $regular_cell . '</td>';
	echo '<td class="rfs-ref-price-manager-td-sale px-4 py-2.5">' . $sale_cell . '</td>';
	echo '<td class="rfs-ref-price-manager-td-status px-4 py-2.5 text-center"><span class="js-price-status rfs-ref-price-manager-status inline-block w-5 h-5"></span></td>';
	echo '<td class="rfs-ref-price-manager-td-actions px-4 py-2.5 text-center">' . $action_cell . '</td>';
	echo '</tr>';
}
