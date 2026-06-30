<?php
/**
 * Product Bundles — Core data model & helpers.
 *
 * A "bundle" (kit) is a standard WooCommerce simple product flagged with the
 * meta key `_ats_is_bundle`. Being a real product means it gets a /product/
 * permalink, native cart, checkout, payment, emails and PDF invoices for free.
 *
 * On top of the product we store:
 *   - a custom image (also set as the featured image)
 *   - a list of included products, each with its own description
 *   - either a single fixed price + "Save" figure, OR a set of price options
 *     (e.g. Single row / Double row) each with their own price, save & SKU.
 *
 * This file only defines constants and read helpers. Admin, cart and frontend
 * behaviour live in bundle-admin.php, bundle-cart.php and bundle-frontend.php.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ATS_BUNDLE_META_FLAG' ) ) {
	define( 'ATS_BUNDLE_META_FLAG', '_ats_is_bundle' );      // 'yes' when product is a bundle.
	define( 'ATS_BUNDLE_META_IMAGE', '_ats_bundle_image_id' ); // Attachment ID of the custom image.
	define( 'ATS_BUNDLE_META_ITEMS', '_ats_bundle_items' );    // [ ['id'=>int,'description'=>string], ... ].
	define( 'ATS_BUNDLE_META_HASOPT', '_ats_bundle_has_options' ); // 'yes'|'no'.
	define( 'ATS_BUNDLE_META_OPTIONS', '_ats_bundle_options' );    // [ ['label','price','save','sku'], ... ].
	define( 'ATS_BUNDLE_META_SAVE', '_ats_bundle_save' );          // Single-mode "Save" figure (float).
}

/**
 * Is the given product (ID, WC_Product or WP_Post) a bundle?
 *
 * @param int|WC_Product|WP_Post $product Product reference.
 * @return bool
 */
function ats_is_bundle( $product ) {
	$id = 0;
	if ( is_numeric( $product ) ) {
		$id = (int) $product;
	} elseif ( $product instanceof WC_Product ) {
		$id = $product->get_id();
	} elseif ( $product instanceof WP_Post ) {
		$id = (int) $product->ID;
	}
	return $id > 0 && 'yes' === get_post_meta( $id, ATS_BUNDLE_META_FLAG, true );
}

/**
 * Resolve a bundle's included products into a render-ready array.
 *
 * Each entry: id, description, product (WC_Product), title, url, price (float),
 * price_html, image_id. Products that no longer exist are skipped.
 *
 * @param int $bundle_id Bundle product ID.
 * @return array<int,array>
 */
function ats_bundle_get_items( $bundle_id ) {
	$raw = get_post_meta( (int) $bundle_id, ATS_BUNDLE_META_ITEMS, true );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$items = array();
	foreach ( $raw as $row ) {
		$pid = isset( $row['id'] ) ? (int) $row['id'] : 0;
		if ( ! $pid ) {
			continue;
		}
		$product = wc_get_product( $pid );
		if ( ! $product ) {
			continue;
		}

		// Admin can pick specific variations of a variable component (empty = all).
		$variations = isset( $row['variations'] ) && is_array( $row['variations'] ) ? array_map( 'intval', $row['variations'] ) : array();

		// Variable components show a clean "From £min" and use the min price for the
		// savings calc — based on the SELECTED variations (or all, if none chosen).
		// We also collect the variation labels so the front end can list exactly
		// which sizes/grits are included. The kit itself is always a fixed-price
		// simple product, so a variable component never affects the kit price.
		$variation_labels = array();
		if ( $product->is_type( 'variable' ) ) {
			$candidate_ids = $variations ? $variations : $product->get_children();
			$prices        = array();
			foreach ( $candidate_ids as $vid ) {
				$v = wc_get_product( $vid );
				if ( ! $v ) {
					continue;
				}
				if ( '' !== $v->get_price() ) {
					$prices[] = (float) $v->get_price();
				}
				$lbl                = wc_get_formatted_variation( $v, true, false, false );
				$variation_labels[] = '' !== $lbl ? $lbl : $v->get_name();
			}
			$price      = $prices ? min( $prices ) : (float) $product->get_variation_price( 'min', true );
			/* translators: %s: lowest price of a variable component. */
			$price_html = sprintf( _x( 'From %s', 'component price', 'woocommerce' ), wc_price( $price ) ) . ' +VAT';
		} else {
			$variations = array();
			$price      = (float) $product->get_price();
			$price_html = wc_price( $price ) . ' +VAT';
		}

		// Which option this item belongs to ('' = shown for all options).
		$option = ( isset( $row['option'] ) && '' !== $row['option'] && null !== $row['option'] ) ? (int) $row['option'] : '';

		$items[] = array(
			'id'               => $pid,
			'description'      => isset( $row['description'] ) ? (string) $row['description'] : '',
			'product'          => $product,
			'title'            => $product->get_name(),
			'url'              => get_permalink( $pid ),
			'price'            => $price,
			'price_html'       => $price_html,
			'image_id'         => $product->get_image_id(),
			'variations'       => $variations,
			'variation_labels' => $variation_labels,
			'option'           => $option,
		);
	}
	return $items;
}

/**
 * Sum of the live prices of a bundle's included products.
 *
 * Used to suggest the "Save" figure in the admin.
 *
 * @param int $bundle_id Bundle product ID.
 * @return float
 */
function ats_bundle_components_total( $bundle_id ) {
	$total = 0.0;
	foreach ( ats_bundle_get_items( $bundle_id ) as $item ) {
		$total += (float) $item['price'];
	}
	return $total;
}

/**
 * Get a bundle's price options (normalised).
 *
 * @param int $bundle_id Bundle product ID.
 * @return array<int,array{index:int,label:string,price:float,save:float,sku:string}>
 */
function ats_bundle_get_options( $bundle_id ) {
	$raw = get_post_meta( (int) $bundle_id, ATS_BUNDLE_META_OPTIONS, true );
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$out = array();
	$i   = 0;
	foreach ( $raw as $opt ) {
		$out[] = array(
			'index' => $i,
			'label' => isset( $opt['label'] ) ? (string) $opt['label'] : '',
			'price' => isset( $opt['price'] ) ? (float) $opt['price'] : 0.0,
			'save'  => isset( $opt['save'] ) ? (float) $opt['save'] : 0.0,
			'sku'   => isset( $opt['sku'] ) ? (string) $opt['sku'] : '',
		);
		$i++;
	}
	return $out;
}

/**
 * Does this bundle use the multi-option (e.g. Single/Double row) model?
 *
 * @param int $bundle_id Bundle product ID.
 * @return bool
 */
function ats_bundle_has_options( $bundle_id ) {
	return 'yes' === get_post_meta( (int) $bundle_id, ATS_BUNDLE_META_HASOPT, true )
		&& count( ats_bundle_get_options( $bundle_id ) ) > 0;
}

/**
 * The default (shown-first) price for a bundle.
 *
 * @param int $bundle_id Bundle product ID.
 * @return float
 */
function ats_bundle_default_price( $bundle_id ) {
	if ( ats_bundle_has_options( $bundle_id ) ) {
		$opts = ats_bundle_get_options( $bundle_id );
		return $opts ? (float) $opts[0]['price'] : 0.0;
	}
	$product = wc_get_product( $bundle_id );
	return $product ? (float) $product->get_price() : 0.0;
}

/**
 * The default (shown-first) "Save" figure for a bundle.
 *
 * @param int $bundle_id Bundle product ID.
 * @return float
 */
function ats_bundle_default_save( $bundle_id ) {
	if ( ats_bundle_has_options( $bundle_id ) ) {
		$opts = ats_bundle_get_options( $bundle_id );
		return $opts ? (float) $opts[0]['save'] : 0.0;
	}
	return (float) get_post_meta( (int) $bundle_id, ATS_BUNDLE_META_SAVE, true );
}

/**
 * Ensure the "Bundles" product category exists and assign a bundle to it.
 *
 * Keeps bundles discoverable in the shop "Shop By Category" sidebar (which auto
 * lists non-empty top-level product categories) and gives them a category
 * archive at /product-category/bundles/.
 *
 * @param int $bundle_id Bundle product ID.
 * @return int The Bundles term ID (0 on failure).
 */
function ats_bundle_assign_category( $bundle_id ) {
	$term = term_exists( 'bundles', 'product_cat' );
	if ( ! $term ) {
		$term = wp_insert_term( 'Bundles', 'product_cat', array( 'slug' => 'bundles' ) );
	}
	if ( is_wp_error( $term ) ) {
		return 0;
	}
	$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	if ( $term_id && $bundle_id ) {
		// Append (true) so any other categories on the product are preserved.
		wp_set_object_terms( (int) $bundle_id, array( $term_id ), 'product_cat', true );
	}
	return $term_id;
}

/**
 * Find published bundles that include a given product.
 *
 * Reverse of ats_bundle_get_items(). Used to show an "also in a bundle" badge on
 * the included product's own page. Result is cached per request.
 *
 * @param int $product_id Product ID.
 * @return int[] Bundle product IDs that contain this product.
 */
function ats_bundle_get_bundles_for_product( $product_id ) {
	static $cache = array();
	$product_id = (int) $product_id;
	if ( ! $product_id ) {
		return array();
	}
	if ( isset( $cache[ $product_id ] ) ) {
		return $cache[ $product_id ];
	}

	$found = array();
	foreach ( ats_bundle_get_all_ids( 'publish' ) as $bundle_id ) {
		$raw = get_post_meta( $bundle_id, ATS_BUNDLE_META_ITEMS, true );
		if ( ! is_array( $raw ) ) {
			continue;
		}
		foreach ( $raw as $row ) {
			if ( isset( $row['id'] ) && (int) $row['id'] === $product_id ) {
				$found[] = (int) $bundle_id;
				break;
			}
		}
	}

	$cache[ $product_id ] = $found;
	return $found;
}

/**
 * The largest "Save" figure for a bundle (across options) — for the card badge.
 *
 * @param int $bundle_id Bundle product ID.
 * @return float
 */
function ats_bundle_max_save( $bundle_id ) {
	if ( ats_bundle_has_options( $bundle_id ) ) {
		$max = 0.0;
		foreach ( ats_bundle_get_options( $bundle_id ) as $opt ) {
			$max = max( $max, (float) $opt['save'] );
		}
		return $max;
	}
	return ats_bundle_default_save( $bundle_id );
}

/**
 * Return all bundle product IDs (published + draft), newest first.
 *
 * @param string $status Post status filter ('any' by default).
 * @return int[]
 */
function ats_bundle_get_all_ids( $status = 'any' ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => $status,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => ATS_BUNDLE_META_FLAG,
					'value' => 'yes',
				),
			),
		)
	);
	return array_map( 'intval', $query->posts );
}
