<?php
/**
 * Product Bundles — Frontend (single product + listings).
 *
 * Rendered entirely through WooCommerce hooks so we don't fork the theme's
 * single-product template. For a bundle product we:
 *   - replace the default price with a kit price + "Save £X" block,
 *   - inject a radio option selector into the add-to-cart form (when options
 *     exist) that live-updates the price/save via a small vanilla-JS snippet,
 *   - render a "What's in the box" grid after the summary,
 *   - prefix the listing price with "From" when the bundle has options.
 *
 * CSS/JS are registered as inline-only assets (no build-pipeline dependency)
 * and only enqueued on bundle product pages.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the kit price + "Save £X" block for the single product page.
 *
 * Hooks the theme's `ats_product_price_html` filter (fired inside
 * ats_get_product_price_html() in shortcodes/product.php). The amount is given
 * a data hook so the option selector JS can live-update it.
 *
 * @param string     $html    Default price HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function ats_bundle_price_markup( $html, $product ) {
	if ( ! $product instanceof WC_Product || ! ats_is_bundle( $product ) ) {
		return $html;
	}
	$id = $product->get_id();

	// Only the single-product main view gets the full price + Save block. On
	// listing cards the price stays plain (with a "From" for option kits) and the
	// saving is shown as an overlay badge on the card instead.
	$is_single_main = function_exists( 'is_product' ) && is_product() && (int) get_queried_object_id() === (int) $id;
	if ( ! $is_single_main ) {
		if ( ats_bundle_has_options( $id ) ) {
			/* translators: %s: from price. */
			return sprintf( _x( 'From %s', 'bundle price', 'woocommerce' ), $html );
		}
		return $html;
	}

	$price = ats_bundle_default_price( $id );
	$save  = ats_bundle_default_save( $id );

	$out  = '<span class="ats-bundle-price">';
	$out .= '<span class="ats-bundle-price__tag">' . esc_html__( 'Product Bundle', 'woocommerce' ) . '</span>';
	$out .= '<span class="ats-bundle-price__amount" data-bundle-price-amount>' . wc_price( $price ) . '</span>';
	$out .= '<span class="ats-bundle-price__vat">+VAT</span>';
	$out .= '<span class="ats-bundle-price__save" data-bundle-save>';
	if ( $save > 0 ) {
		/* translators: %s: saving amount. */
		$out .= sprintf( __( 'Save %s', 'woocommerce' ), wc_price( $save ) );
	}
	$out .= '</span>';
	$out .= '</span>';
	return $out;
}
add_filter( 'ats_product_price_html', 'ats_bundle_price_markup', 10, 2 );

/**
 * Inject the option selector into the add-to-cart form.
 *
 * @return void
 */
function ats_bundle_render_options_field() {
	global $product;
	if ( ! $product instanceof WC_Product || ! ats_is_bundle( $product ) || ! ats_bundle_has_options( $product->get_id() ) ) {
		return;
	}

	$options = ats_bundle_get_options( $product->get_id() );

	echo '<div class="ats-bundle-options" data-bundle-options>';
	echo '<span class="ats-bundle-options__heading">' . esc_html__( 'Choose your option', 'woocommerce' ) . '</span>';
	foreach ( $options as $opt ) {
		$save_html = $opt['save'] > 0
			/* translators: %s: saving amount. */
			? sprintf( __( 'Save %s', 'woocommerce' ), wc_price( $opt['save'] ) )
			: '';

		printf(
			'<label class="ats-bundle-options__opt">' .
				'<input type="radio" name="ats_bundle_option" value="%1$d" %2$s data-price-html="%3$s" data-save-html="%4$s">' .
				'<span class="ats-bundle-options__name">%5$s</span>' .
				'<span class="ats-bundle-options__price">%6$s</span>' .
			'</label>',
			(int) $opt['index'],
			checked( 0, (int) $opt['index'], false ),
			esc_attr( wc_price( $opt['price'] ) ),
			esc_attr( $save_html ),
			esc_html( '' !== $opt['label'] ? $opt['label'] : __( 'Option', 'woocommerce' ) . ' ' . ( $opt['index'] + 1 ) ),
			wp_kses_post( wc_price( $opt['price'] ) )
		);
	}
	echo '</div>';
}
add_action( 'woocommerce_before_add_to_cart_button', 'ats_bundle_render_options_field' );

/**
 * Render the "What's in the box" grid after the product summary.
 *
 * @return void
 */
function ats_bundle_render_whats_inside() {
	global $product;
	if ( ! $product instanceof WC_Product || ! ats_is_bundle( $product ) ) {
		return;
	}

	$items = ats_bundle_get_items( $product->get_id() );
	if ( empty( $items ) ) {
		return;
	}

	echo '<section class="ats-bundle-inside">';
	echo '<h2 class="ats-bundle-inside__title">' . esc_html__( "What's in the box", 'woocommerce' ) . '</h2>';
	echo '<ul class="ats-bundle-inside__list">';

	foreach ( $items as $item ) {
		// wpimage() returns a resized/WebP URL, so wrap it in an <img>. Fall back
		// to the full attachment markup, then the WooCommerce placeholder.
		$img = '';
		if ( function_exists( 'wpimage' ) && $item['image_id'] ) {
			$url = wpimage( image: $item['image_id'], size: array( 160, 160 ), retina: true );
			if ( $url ) {
				$img = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $item['title'] ) . '" loading="lazy" />';
			}
		}
		if ( ! $img && $item['image_id'] ) {
			$img = wp_get_attachment_image( $item['image_id'], 'thumbnail' );
		}
		if ( ! $img ) {
			$img = wc_placeholder_img( 'woocommerce_thumbnail' );
		}

		// Items tied to a specific option carry data-item-option so the option
		// selector can show only the relevant ones (e.g. single- vs double-row cup).
		$pid      = (int) $item['id'];
		$opt_attr = '' !== $item['option'] ? ' data-item-option="' . esc_attr( (int) $item['option'] ) . '"' : '';

		// The image + name open the theme's quick-view modal (.ats-expand-product
		// trigger + data-product-id); the href is a no-JS fallback to the product.
		echo '<li class="ats-bundle-line" data-product-id="' . esc_attr( $pid ) . '"' . $opt_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attrs escaped above.
		echo '<a class="ats-bundle-line__media ats-expand-product" href="' . esc_url( $item['url'] ) . '" data-product-id="' . esc_attr( $pid ) . '">' . $img . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted image markup.
		echo '<div class="ats-bundle-line__body">';
		echo '<a class="ats-bundle-line__name ats-expand-product" href="' . esc_url( $item['url'] ) . '" data-product-id="' . esc_attr( $pid ) . '">' . esc_html( $item['title'] ) . '</a>';
		if ( ! empty( $item['variation_labels'] ) ) {
			echo '<span class="ats-bundle-line__variations">' . esc_html( implode( ', ', $item['variation_labels'] ) ) . '</span>';
		} elseif ( '' !== $item['description'] ) {
			echo '<span class="ats-bundle-line__variations">' . esc_html( wp_strip_all_tags( $item['description'] ) ) . '</span>';
		}
		echo '</div>';
		echo '</li>';
	}

	echo '</ul>';
	echo '</section>';
}

/**
 * Render an "also available in a bundle" badge + link on a product's own page.
 *
 * Only shows for regular (non-bundle) products that belong to one or more
 * published bundles. Called directly from content-single-product.php (outputs
 * nothing for bundles or stand-alone products).
 *
 * @return void
 */
function ats_bundle_render_in_bundle_notice() {
	global $product;
	if ( ! $product instanceof WC_Product || ats_is_bundle( $product ) ) {
		return;
	}

	$bundles = ats_bundle_get_bundles_for_product( $product->get_id() );
	if ( empty( $bundles ) ) {
		return;
	}

	echo '<div class="ats-in-bundle">';
	echo '<span class="ats-in-bundle__badge">' . esc_html__( 'Bundle deal', 'woocommerce' ) . '</span>';
	echo '<div class="ats-in-bundle__body">';
	echo '<span class="ats-in-bundle__lead">' . esc_html__( 'Also available in a kit — buy together and save:', 'woocommerce' ) . '</span>';
	echo '<ul class="ats-in-bundle__list">';
	foreach ( $bundles as $bundle_id ) {
		$save     = ats_bundle_max_save( $bundle_id );
		$save_txt = '';
		if ( $save > 0 ) {
			/* translators: %s: saving amount. */
			$save_txt = ' <span class="ats-in-bundle__save">' . sprintf( esc_html__( 'save %s', 'woocommerce' ), wp_strip_all_tags( wc_price( $save ) ) ) . '</span>';
		}
		echo '<li><a href="' . esc_url( get_permalink( $bundle_id ) ) . '">' . esc_html( get_the_title( $bundle_id ) ) . '</a>' . $save_txt . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- save_txt built from escaped parts.
	}
	echo '</ul>';
	echo '</div>';
	echo '</div>';
}

/**
 * Prefix the listing price with "From" for option bundles.
 *
 * Single product price is rendered by ats_bundle_render_price() (which uses
 * wc_price directly), so this only affects archives / shortcodes.
 *
 * @param string     $html    Price HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function ats_bundle_filter_price_html( $html, $product ) {
	if ( is_product() && is_main_query() ) {
		return $html;
	}
	if ( $product instanceof WC_Product && ats_is_bundle( $product ) && ats_bundle_has_options( $product->get_id() ) ) {
		/* translators: %s: lowest price. */
		return sprintf( _x( 'From %s', 'bundle price', 'woocommerce' ), $html );
	}
	return $html;
}
add_filter( 'woocommerce_get_price_html', 'ats_bundle_filter_price_html', 10, 2 );

/**
 * Enqueue inline CSS/JS on bundle product pages only.
 *
 * @return void
 */
function ats_bundle_enqueue_frontend() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$id = get_queried_object_id();
	if ( ! $id || ! ats_is_bundle( $id ) ) {
		return;
	}

	wp_register_style( 'ats-bundle', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'ats-bundle' );
	wp_add_inline_style( 'ats-bundle', ats_bundle_frontend_css() );

	wp_register_script( 'ats-bundle', false, array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
	wp_enqueue_script( 'ats-bundle' );
	wp_add_inline_script( 'ats-bundle', ats_bundle_frontend_js() );
}
add_action( 'wp_enqueue_scripts', 'ats_bundle_enqueue_frontend', 20 );

/**
 * Enqueue the "Save" card-badge CSS wherever product cards can appear (shop,
 * category, search, related, home). The single-product styles only load on
 * bundle product pages, so the overlay badge needs its own lightweight style.
 *
 * @return void
 */
function ats_bundle_enqueue_card_badge_css() {
	if ( is_admin() ) {
		return;
	}
	wp_register_style( 'ats-bundle-card', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'ats-bundle-card' );
	wp_add_inline_style(
		'ats-bundle-card',
		'.rfs-ref-product-image-link,.rfs-ref-product-list-image-link{position:relative}' .
		'.ats-bundle-save-badge{position:absolute;right:8px;bottom:8px;z-index:6;display:inline-flex;align-items:center;gap:.28em;' .
		'padding:3px 9px;font-size:11px;font-weight:800;letter-spacing:.02em;color:#fff;background:#15803d;' .
		'border-radius:4px;pointer-events:none}' .
		'.ats-bundle-save-badge .amount{font-size:inherit}' .
		'.ats-bundle-save-badge--sm{right:6px;bottom:6px;padding:2px 7px;font-size:10px}' .
		// "Also in a bundle" notice on a product's own page.
		'.ats-in-bundle{display:flex;gap:.7rem;align-items:flex-start;border:1px solid #cfe8d6;background:#f3faf5;border-radius:6px;padding:.75rem .9rem;margin:0 0 1.1rem}' .
		'.ats-in-bundle__badge{flex-shrink:0;background:#15803d;color:#fff;font-size:.62rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;padding:.3rem .5rem;border-radius:4px;white-space:nowrap}' .
		'.ats-in-bundle__body{font-size:.85rem;color:#373737;line-height:1.45}' .
		'.ats-in-bundle__lead{display:block;font-weight:600;margin-bottom:.1rem}' .
		'.ats-in-bundle__list{margin:0;padding:0;list-style:none}' .
		'.ats-in-bundle__list li{margin:0}' .
		'.ats-in-bundle__list a{color:#594652;font-weight:700;text-decoration:underline}' .
		'.ats-in-bundle__save{color:#15803d;font-weight:700}'
	);
}
add_action( 'wp_enqueue_scripts', 'ats_bundle_enqueue_card_badge_css' );

/**
 * Frontend CSS for bundle components.
 *
 * @return string
 */
function ats_bundle_frontend_css() {
	return <<<'CSS'
#ats-product-main-price{flex:1 1 auto}
.ats-bundle-price{display:flex;flex-wrap:wrap;align-items:center;gap:.3rem .5rem;width:100%;margin:0}
.ats-bundle-price__tag{flex:0 0 100%;font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#15803d}
.ats-bundle-price__amount{font-size:1.9rem;font-weight:800;line-height:1.1;color:#373737}
.ats-bundle-price__amount .amount{font-size:inherit}
.ats-bundle-price__vat{font-size:.85rem;font-weight:600;color:#6b7280}
.ats-bundle-price__save:not(:empty){margin-left:auto;display:inline-flex;align-items:center;gap:.28em;background:#15803d;color:#fff;font-weight:700;font-size:.78rem;padding:.34rem .7rem;border-radius:4px;white-space:nowrap;letter-spacing:.01em}
.ats-bundle-price__save .amount{color:#fff;font-size:inherit}
form.cart:has(.ats-bundle-options){flex-wrap:wrap;row-gap:.9rem}
form.cart:has(.ats-bundle-options) .ats-quantity{flex:0 0 auto}
form.cart:has(.ats-bundle-options) .single_add_to_cart_button{width:auto;flex:1 1 auto}
.ats-bundle-options{display:flex;flex-wrap:wrap;gap:.5rem;margin:0;flex:0 0 100%;width:100%}
.ats-bundle-options__heading{flex:0 0 100%;font-weight:700;margin:0 0 .1rem;color:#373737}
.ats-bundle-options__opt{flex:1 1 200px;min-width:0;display:flex;align-items:center;gap:.4rem;border:1px solid #e2e2e2;border-radius:4px;padding:.5rem .7rem;margin:0;cursor:pointer;transition:border-color .15s,background .15s}
.ats-bundle-options__opt:hover{border-color:#594652}
.ats-bundle-options__opt:has(input:checked){border-color:#594652;background:#f4eff3}
.ats-bundle-options__name{font-weight:600;font-size:.85rem;white-space:nowrap}
.ats-bundle-options__price{font-weight:700;font-size:.85rem;white-space:nowrap;margin-left:auto}
.ats-bundle-inside{margin:0 0 1.25rem}
.ats-bundle-inside__title{font-size:1.05rem;font-weight:800;margin:0 0 .7rem;color:#373737}
.ats-bundle-inside__list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.7rem}
.ats-bundle-line{display:flex;gap:.7rem;align-items:flex-start}
.ats-bundle-line__media{flex:0 0 52px;width:52px;height:52px;border:1px solid #ececec;border-radius:6px;overflow:hidden;background:#f7f7f7;display:block}
.ats-bundle-line__media img{width:100%;height:100%;object-fit:cover;display:block}
.ats-bundle-line__body{display:flex;flex-direction:column;gap:.1rem;min-width:0;padding-top:.1rem}
.ats-bundle-line__name{font-weight:700;font-size:.9rem;color:#373737;text-decoration:none;line-height:1.3}
.ats-bundle-line__name:hover{text-decoration:underline;color:#594652}
.ats-bundle-line__variations{font-size:.8rem;color:#5b5b5b;line-height:1.45}
CSS;
}

/**
 * Frontend JS — live-update price/save when a bundle option is selected.
 *
 * @return string
 */
function ats_bundle_frontend_js() {
	return <<<'JS'
(function(){
	function init(){
		// Show only the "What's in the box" items for the selected option
		// (items with no data-item-option are common and always shown).
		function filterItems(opt){
			document.querySelectorAll('.ats-bundle-line[data-item-option]').forEach(function(li){
				li.style.display = (li.getAttribute('data-item-option') === String(opt)) ? '' : 'none';
			});
		}

		var wrap = document.querySelector('[data-bundle-options]');
		if(wrap){
			var amount = document.querySelector('[data-bundle-price-amount]');
			var save = document.querySelector('[data-bundle-save]');
			wrap.addEventListener('change', function(e){
				var input = e.target;
				if(!input || input.name !== 'ats_bundle_option'){return;}
				if(amount && input.dataset.priceHtml){amount.innerHTML = input.dataset.priceHtml;}
				if(save){save.innerHTML = input.dataset.saveHtml || '';}
				filterItems(input.value);
			});
			// Apply the initially-selected option on load.
			var checked = wrap.querySelector('input[name="ats_bundle_option"]:checked');
			if(checked){ filterItems(checked.value); }
		}
		// The theme adds to cart via the custom `ats_add_to_cart` AJAX action, which
		// does not include our option field. Append the selected option to that
		// request so the correct price/SKU is captured server-side.
		if(window.jQuery){
			window.jQuery.ajaxPrefilter(function(options){
				var d = options.data;
				var isAdd = (typeof d === 'string' && d.indexOf('ats_add_to_cart') > -1) || (d && d.action === 'ats_add_to_cart');
				if(!isAdd){return;}
				var sel = document.querySelector('input[name="ats_bundle_option"]:checked');
				if(!sel){return;}
				if(typeof d === 'string'){
					options.data = d + '&ats_bundle_option=' + encodeURIComponent(sel.value);
				}else if(d && typeof d === 'object'){
					d.ats_bundle_option = sel.value;
				}
			});
		}
	}
	if(document.readyState === 'loading'){
		document.addEventListener('DOMContentLoaded', init);
	}else{
		init();
	}
})();
JS;
}
