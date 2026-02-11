<?php
/**
 * Shipping Class Column for Product List
 *
 * Adds a "Shipping Class" column to the WooCommerce product list table
 * with an inline AJAX dropdown to assign shipping classes without saving.
 * For variable products, a clickable badge expands to show individual
 * variation shipping class dropdowns.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add Shipping Class column to product list table.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function ats_add_shipping_class_column( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		if ( 'price' === $key ) {
			$new_columns['shipping_class'] = __( 'Shipping Class', 'skylinewp-dev-child' );
		}
	}

	if ( ! isset( $new_columns['shipping_class'] ) ) {
		$new_columns['shipping_class'] = __( 'Shipping Class', 'skylinewp-dev-child' );
	}

	return $new_columns;
}
add_filter( 'manage_edit-product_columns', 'ats_add_shipping_class_column', 20 );

/**
 * Render the shipping class dropdown in the column.
 *
 * @param string $column  Column name.
 * @param int    $post_id Product post ID.
 */
function ats_render_shipping_class_column( $column, $post_id ) {
	if ( 'shipping_class' !== $column ) {
		return;
	}

	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return;
	}

	$current_class_id = $product->get_shipping_class_id();
	$shipping_classes = WC()->shipping()->get_shipping_classes();
	$is_variable      = $product->is_type( 'variable' );
	$variation_count  = $is_variable ? count( $product->get_children() ) : 0;
	?>
	<select class="ats-shipping-class-select" data-product-id="<?php echo esc_attr( $post_id ); ?>" style="width:100%;max-width:150px;">
		<option value="0"><?php esc_html_e( 'No shipping class', 'skylinewp-dev-child' ); ?></option>
		<?php foreach ( $shipping_classes as $sc ) : ?>
			<option value="<?php echo esc_attr( $sc->term_id ); ?>" <?php selected( $current_class_id, $sc->term_id ); ?>>
				<?php echo esc_html( $sc->name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php if ( $is_variable && $variation_count > 0 ) : ?>
		<a href="#" class="ats-variation-toggle" data-product-id="<?php echo esc_attr( $post_id ); ?>"><?php echo esc_html( $variation_count ); ?> variations</a>
		<div class="ats-variations-panel" data-product-id="<?php echo esc_attr( $post_id ); ?>" style="display:none;"></div>
	<?php endif; ?>
	<?php
}
add_action( 'manage_product_posts_custom_column', 'ats_render_shipping_class_column', 10, 2 );

/**
 * AJAX handler to update a single product or variation shipping class.
 */
function ats_ajax_update_shipping_class() {
	check_ajax_referer( 'ats_shipping_class_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied.' ) );
	}

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$class_id   = isset( $_POST['shipping_class_id'] ) ? intval( $_POST['shipping_class_id'] ) : 0;

	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => 'Invalid product.' ) );
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		wp_send_json_error( array( 'message' => 'Product not found.' ) );
	}

	if ( $class_id > 0 ) {
		$term = get_term( $class_id, 'product_shipping_class' );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => 'Invalid shipping class.' ) );
		}
		$class_name = $term->name;
		wp_set_object_terms( $product_id, $class_id, 'product_shipping_class' );
	} else {
		$class_name = __( 'No shipping class', 'skylinewp-dev-child' );
		wp_set_object_terms( $product_id, array(), 'product_shipping_class' );
	}
	wc_delete_product_transients( $product_id );

	wp_send_json_success( array(
		'message'    => sprintf( __( 'Shipping class updated to "%s"', 'skylinewp-dev-child' ), $class_name ),
		'class_name' => $class_name,
	) );
}
add_action( 'wp_ajax_ats_update_shipping_class', 'ats_ajax_update_shipping_class' );

/**
 * AJAX handler to get variations with their shipping classes.
 */
function ats_ajax_get_variation_shipping() {
	check_ajax_referer( 'ats_shipping_class_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied.' ) );
	}

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$product    = wc_get_product( $product_id );

	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid variable product.' ) );
	}

	$shipping_classes = WC()->shipping()->get_shipping_classes();
	$variations       = array();

	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation ) {
			continue;
		}

		// Build a useful label from attributes, SKU, or price
		$attributes  = $variation->get_attributes();
		$attr_labels = array();
		foreach ( $attributes as $attr_value ) {
			if ( $attr_value ) {
				$attr_labels[] = ucfirst( str_replace( '-', ' ', $attr_value ) );
			}
		}

		$label = implode( ' / ', $attr_labels );
		if ( ! $label ) {
			$sku   = $variation->get_sku();
			$price = $variation->get_price();
			if ( $sku ) {
				$label = $sku;
			} elseif ( $price ) {
				$label = '#' . $variation_id . ' - ' . wp_strip_all_tags( wc_price( $price ) );
			} else {
				$label = '#' . $variation_id;
			}
		}

		$variations[] = array(
			'id'                => $variation_id,
			'name'              => $label,
			'shipping_class_id' => $variation->get_shipping_class_id(),
		);
	}

	$classes_data = array();
	foreach ( $shipping_classes as $sc ) {
		$classes_data[] = array(
			'id'   => $sc->term_id,
			'name' => $sc->name,
		);
	}

	wp_send_json_success( array(
		'variations'       => $variations,
		'shipping_classes' => $classes_data,
	) );
}
add_action( 'wp_ajax_ats_get_variation_shipping', 'ats_ajax_get_variation_shipping' );

/**
 * AJAX handler to bulk-update all variations of a product.
 */
function ats_ajax_bulk_update_variation_shipping() {
	check_ajax_referer( 'ats_shipping_class_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied.' ) );
	}

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$class_id   = isset( $_POST['shipping_class_id'] ) ? intval( $_POST['shipping_class_id'] ) : 0;

	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid variable product.' ) );
	}

	if ( $class_id > 0 ) {
		$term = get_term( $class_id, 'product_shipping_class' );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => 'Invalid shipping class.' ) );
		}
		$class_name = $term->name;
	} else {
		$class_name = __( 'No shipping class', 'skylinewp-dev-child' );
	}

	$updated = 0;
	foreach ( $product->get_children() as $variation_id ) {
		if ( $class_id > 0 ) {
			wp_set_object_terms( $variation_id, $class_id, 'product_shipping_class' );
		} else {
			wp_set_object_terms( $variation_id, array(), 'product_shipping_class' );
		}
		wc_delete_product_transients( $variation_id );
		$updated++;
	}

	wp_send_json_success( array(
		'message'    => sprintf( __( 'All %d variations set to "%s"', 'skylinewp-dev-child' ), $updated, $class_name ),
		'class_id'   => $class_id,
		'class_name' => $class_name,
		'updated'    => $updated,
	) );
}
add_action( 'wp_ajax_ats_bulk_update_variation_shipping', 'ats_ajax_bulk_update_variation_shipping' );

/**
 * Output CSS for the shipping class column on the product list screen.
 */
function ats_shipping_class_column_css() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-product' !== $screen->id ) {
		return;
	}
	?>
	<style>
		.column-shipping_class { width: 180px !important; }
		.ats-shipping-class-select { font-size: 13px; padding: 4px 6px; border: 1px solid #ddd; border-radius: 3px; background: #fff; cursor: pointer; width: 100%; max-width: 150px; }
		.ats-shipping-class-select.ats-saving { opacity: 0.5; pointer-events: none; }
		.ats-shipping-class-select.ats-saved { border-color: #46b450; box-shadow: 0 0 0 1px #46b450; transition: border-color 0.3s, box-shadow 0.3s; }
		.ats-shipping-class-select.ats-error { border-color: #dc3232; box-shadow: 0 0 0 1px #dc3232; }
		.ats-variation-toggle { display: inline-block; margin-top: 4px; font-size: 11px; color: #2271b1; cursor: pointer; text-decoration: none; }
		.ats-variation-toggle:hover { color: #135e96; text-decoration: underline; }
		.ats-variation-toggle.ats-open { color: #d63638; }
		.ats-variations-panel { margin-top: 6px; padding: 8px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 4px; max-height: 300px; overflow-y: auto; }
		.ats-var-row { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; padding: 3px 0; border-bottom: 1px solid #eee; }
		.ats-var-row:last-child { margin-bottom: 0; border-bottom: none; }
		.ats-var-label { font-size: 11px; color: #50575e; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.ats-var-select { font-size: 11px; padding: 2px 4px; border: 1px solid #ddd; border-radius: 3px; background: #fff; flex-shrink: 0; max-width: 110px; }
		.ats-var-select.ats-saving { opacity: 0.5; }
		.ats-var-select.ats-saved { border-color: #46b450; }
		.ats-var-select.ats-error { border-color: #dc3232; }
		.ats-var-bulk { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; padding-bottom: 6px; border-bottom: 2px solid #ddd; }
		.ats-var-bulk label { font-size: 11px; font-weight: 600; color: #1d2327; white-space: nowrap; }
		.ats-var-bulk select { font-size: 11px; padding: 2px 4px; }
		.ats-var-bulk-btn { font-size: 11px; padding: 2px 8px; background: #2271b1; color: #fff; border: none; border-radius: 3px; cursor: pointer; }
		.ats-var-bulk-btn:hover { background: #135e96; }
		.ats-var-bulk-btn.ats-saving { opacity: 0.5; pointer-events: none; }
		.ats-panel-loading { font-size: 11px; color: #999; padding: 8px 0; text-align: center; }
	</style>
	<?php
}
add_action( 'admin_head', 'ats_shipping_class_column_css' );

/**
 * Output JS for the shipping class column on the product list screen.
 */
function ats_shipping_class_column_js() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-product' !== $screen->id ) {
		return;
	}

	$nonce = wp_create_nonce( 'ats_shipping_class_nonce' );
	?>
	<script>
	jQuery(function($) {
		var nonce = '<?php echo esc_js( $nonce ); ?>';

		// Parent product shipping class change
		$(document).on('change', '.ats-shipping-class-select', function() {
			var $select = $(this);
			var productId = $select.data('product-id');
			var classId = $select.val();

			$select.removeClass('ats-saved ats-error').addClass('ats-saving');

			$.post(ajaxurl, {
				action: 'ats_update_shipping_class',
				nonce: nonce,
				product_id: productId,
				shipping_class_id: classId
			}, function(response) {
				$select.removeClass('ats-saving');
				if (response.success) {
					$select.addClass('ats-saved');
					setTimeout(function() { $select.removeClass('ats-saved'); }, 2000);
				} else {
					$select.addClass('ats-error');
					setTimeout(function() { $select.removeClass('ats-error'); }, 3000);
				}
			}).fail(function() {
				$select.removeClass('ats-saving').addClass('ats-error');
				setTimeout(function() { $select.removeClass('ats-error'); }, 3000);
			});
		});

		// Toggle variation panel
		$(document).on('click', '.ats-variation-toggle', function(e) {
			e.preventDefault();
			var $toggle = $(this);
			var productId = $toggle.data('product-id');
			var $panel = $toggle.siblings('.ats-variations-panel[data-product-id="' + productId + '"]');

			if ($panel.is(':visible')) {
				$panel.slideUp(150);
				$toggle.removeClass('ats-open');
				return;
			}

			$toggle.addClass('ats-open');

			// Load variations if not yet loaded
			if (!$panel.data('loaded')) {
				$panel.html('<div class="ats-panel-loading">Loading variations...</div>').slideDown(150);

				$.post(ajaxurl, {
					action: 'ats_get_variation_shipping',
					nonce: nonce,
					product_id: productId
				}, function(response) {
					if (!response.success) {
						$panel.html('<div class="ats-panel-loading">Error loading variations.</div>');
						return;
					}

					var html = '';
					var classes = response.data.shipping_classes;
					var variations = response.data.variations;

					// Bulk set row
					html += '<div class="ats-var-bulk">';
					html += '<label>Set all:</label>';
					html += '<select class="ats-var-bulk-select">';
					html += '<option value="0">No shipping class</option>';
					for (var i = 0; i < classes.length; i++) {
						html += '<option value="' + classes[i].id + '">' + classes[i].name + '</option>';
					}
					html += '</select>';
					html += '<button type="button" class="ats-var-bulk-btn" data-product-id="' + productId + '">Apply</button>';
					html += '</div>';

					// Individual variation rows
					for (var v = 0; v < variations.length; v++) {
						var vr = variations[v];
						html += '<div class="ats-var-row">';
						html += '<span class="ats-var-label" title="' + vr.name + '">' + vr.name + '</span>';
						html += '<select class="ats-var-select" data-variation-id="' + vr.id + '">';
						html += '<option value="0"' + (vr.shipping_class_id == 0 ? ' selected' : '') + '>No class</option>';
						for (var j = 0; j < classes.length; j++) {
							var sel = (vr.shipping_class_id == classes[j].id) ? ' selected' : '';
							html += '<option value="' + classes[j].id + '"' + sel + '>' + classes[j].name + '</option>';
						}
						html += '</select>';
						html += '</div>';
					}

					$panel.html(html).data('loaded', true);
				}).fail(function() {
					$panel.html('<div class="ats-panel-loading">Failed to load.</div>');
				});
			} else {
				$panel.slideDown(150);
			}
		});

		// Individual variation shipping class change
		$(document).on('change', '.ats-var-select', function() {
			var $select = $(this);
			var variationId = $select.data('variation-id');
			var classId = $select.val();

			$select.removeClass('ats-saved ats-error').addClass('ats-saving');

			$.post(ajaxurl, {
				action: 'ats_update_shipping_class',
				nonce: nonce,
				product_id: variationId,
				shipping_class_id: classId
			}, function(response) {
				$select.removeClass('ats-saving');
				if (response.success) {
					$select.addClass('ats-saved');
					setTimeout(function() { $select.removeClass('ats-saved'); }, 2000);
				} else {
					$select.addClass('ats-error');
					setTimeout(function() { $select.removeClass('ats-error'); }, 3000);
				}
			}).fail(function() {
				$select.removeClass('ats-saving').addClass('ats-error');
				setTimeout(function() { $select.removeClass('ats-error'); }, 3000);
			});
		});

		// Bulk apply to all variations
		$(document).on('click', '.ats-var-bulk-btn', function() {
			var $btn = $(this);
			var productId = $btn.data('product-id');
			var $panel = $btn.closest('.ats-variations-panel');
			var classId = $panel.find('.ats-var-bulk-select').val();

			$btn.addClass('ats-saving');

			$.post(ajaxurl, {
				action: 'ats_bulk_update_variation_shipping',
				nonce: nonce,
				product_id: productId,
				shipping_class_id: classId
			}, function(response) {
				$btn.removeClass('ats-saving');
				if (response.success) {
					// Update all variation dropdowns in this panel
					$panel.find('.ats-var-select').val(classId).addClass('ats-saved');
					setTimeout(function() { $panel.find('.ats-var-select').removeClass('ats-saved'); }, 2000);
				}
			}).fail(function() {
				$btn.removeClass('ats-saving');
			});
		});
	});
	</script>
	<?php
}
add_action( 'admin_footer', 'ats_shipping_class_column_js' );
