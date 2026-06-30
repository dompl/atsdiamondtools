<?php
/**
 * Product Bundles — Dedicated admin screen.
 *
 * A purpose-built "Bundles" admin page (not the standard WooCommerce product
 * editor). It lists existing kits and provides a simple guided form to create /
 * edit one. On save it upserts the underlying WooCommerce simple product and
 * its bundle meta, then flushes the product-card cache.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the "Bundles" screen as a submenu under WooCommerce → Products.
 *
 * @return void
 */
function ats_bundle_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=product',
		__( 'Bundles', 'woocommerce' ),
		__( 'Bundles', 'woocommerce' ),
		'manage_woocommerce',
		'ats-bundles',
		'ats_bundle_render_admin_page'
	);
}
add_action( 'admin_menu', 'ats_bundle_admin_menu' );

/**
 * Enqueue admin assets on the Bundles page only.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function ats_bundle_admin_assets( $hook ) {
	// Submenu under Products → screen hook is "product_page_ats-bundles".
	if ( 'product_page_ats-bundles' !== $hook ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'selectWoo' );
	wp_enqueue_style( 'select2' );

	wp_register_script( 'ats-bundle-admin', false, array( 'jquery', 'selectWoo' ), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
	wp_enqueue_script( 'ats-bundle-admin' );
	wp_localize_script(
		'ats-bundle-admin',
		'ATS_BUNDLE',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ats_bundle_admin' ),
		)
	);
	wp_add_inline_script( 'ats-bundle-admin', ats_bundle_admin_js() );

	wp_register_style( 'ats-bundle-admin', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'ats-bundle-admin' );
	wp_add_inline_style( 'ats-bundle-admin', ats_bundle_admin_css() );
}
add_action( 'admin_enqueue_scripts', 'ats_bundle_admin_assets' );

/**
 * Route the Bundles page to the list or the edit form.
 *
 * @return void
 */
function ats_bundle_render_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage bundles.', 'woocommerce' ) );
	}
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'edit' === $action || 'new' === $action ) {
		ats_bundle_render_edit_form();
	} else {
		ats_bundle_render_list();
	}
}

/**
 * Render the list of existing bundles.
 *
 * @return void
 */
function ats_bundle_render_list() {
	$ids      = ats_bundle_get_all_ids();
	$new_url  = admin_url( 'admin.php?page=ats-bundles&action=new' );
	$message  = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( $_GET['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap ats-bundles">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Bundles', 'woocommerce' ); ?></h1>
		<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Bundle', 'woocommerce' ); ?></a>
		<hr class="wp-header-end">

		<?php if ( 'saved' === $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Bundle saved.', 'woocommerce' ); ?></p></div>
		<?php elseif ( 'deleted' === $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Bundle deleted.', 'woocommerce' ); ?></p></div>
		<?php elseif ( 'error' === $message ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Could not save the bundle. Please try again.', 'woocommerce' ); ?></p></div>
		<?php endif; ?>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:64px"><?php esc_html_e( 'Image', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Name', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Products', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Status', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $ids ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No bundles yet. Click “Add New Bundle” to create one.', 'woocommerce' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $ids as $id ) : ?>
					<?php
					$product   = wc_get_product( $id );
					$edit_url  = admin_url( 'admin.php?page=ats-bundles&action=edit&bundle_id=' . $id );
					$image_id  = (int) get_post_meta( $id, ATS_BUNDLE_META_IMAGE, true );
					$items     = ats_bundle_get_items( $id );
					$has_opts  = ats_bundle_has_options( $id );
					$price_txt = $has_opts
						/* translators: %s: lowest option price. */
						? sprintf( __( 'From %s', 'woocommerce' ), wp_strip_all_tags( wc_price( ats_bundle_default_price( $id ) ) ) )
						: wp_strip_all_tags( wc_price( ats_bundle_default_price( $id ) ) );
					?>
					<tr>
						<td>
							<?php if ( $image_id ) : ?>
								<?php echo wp_get_attachment_image( $image_id, array( 48, 48 ), false, array( 'style' => 'border-radius:4px;object-fit:cover;' ) ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-format-image" style="font-size:32px;color:#ccc"></span>
							<?php endif; ?>
						</td>
						<td><strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $product ? $product->get_name() : __( '(missing)', 'woocommerce' ) ); ?></a></strong></td>
						<td><?php echo esc_html( $price_txt ); ?></td>
						<td><?php echo (int) count( $items ); ?></td>
						<td><?php echo esc_html( $product ? ucfirst( $product->get_status() ) : '—' ); ?></td>
						<td>
							<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a>
							<?php if ( $product ) : ?>
								| <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'woocommerce' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render the add / edit bundle form.
 *
 * @return void
 */
function ats_bundle_render_edit_form() {
	$bundle_id = isset( $_GET['bundle_id'] ) ? (int) $_GET['bundle_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$product   = $bundle_id ? wc_get_product( $bundle_id ) : null;
	$is_edit   = $product && ats_is_bundle( $bundle_id );

	$title       = $is_edit ? $product->get_name() : '';
	$slug        = $is_edit ? get_post_field( 'post_name', $bundle_id ) : '';
	$description = $is_edit ? $product->get_description() : '';
	$status      = $is_edit ? $product->get_status() : 'draft';
	$image_id    = $is_edit ? (int) get_post_meta( $bundle_id, ATS_BUNDLE_META_IMAGE, true ) : 0;
	$gallery_ids = $is_edit ? array_map( 'intval', (array) $product->get_gallery_image_ids() ) : array();
	$sku         = $is_edit ? $product->get_sku() : '';
	$ship_class  = $is_edit ? (int) $product->get_shipping_class_id() : 0;
	$weight      = $is_edit ? $product->get_weight() : '';
	$items       = $is_edit ? ats_bundle_get_items( $bundle_id ) : array();
	$has_options = $is_edit ? ats_bundle_has_options( $bundle_id ) : false;
	$options     = $is_edit ? ats_bundle_get_options( $bundle_id ) : array();
	$price       = $is_edit && ! $has_options ? ats_bundle_default_price( $bundle_id ) : '';
	$save        = $is_edit && ! $has_options ? ats_bundle_default_save( $bundle_id ) : '';

	$list_url = admin_url( 'admin.php?page=ats-bundles' );
	?>
	<div class="wrap ats-bundles ats-bundle-edit">
		<h1><?php echo $is_edit ? esc_html__( 'Edit Bundle', 'woocommerce' ) : esc_html__( 'Add New Bundle', 'woocommerce' ); ?></h1>
		<a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to bundles', 'woocommerce' ); ?></a>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ats-bundle-form">
			<input type="hidden" name="action" value="ats_save_bundle">
			<input type="hidden" name="bundle_id" value="<?php echo esc_attr( $bundle_id ); ?>">
			<?php wp_nonce_field( 'ats_save_bundle' ); ?>

			<div class="ats-bundle-grid">
				<div class="ats-bundle-main">

					<p class="ats-field">
						<label for="bundle_title"><strong><?php esc_html_e( 'Kit name', 'woocommerce' ); ?></strong></label>
						<input type="text" id="bundle_title" name="bundle_title" class="widefat" value="<?php echo esc_attr( $title ); ?>" required>
					</p>

					<p class="ats-field">
						<label for="bundle_slug"><strong><?php esc_html_e( 'URL slug', 'woocommerce' ); ?></strong> <span class="description"><?php esc_html_e( '(optional — leave blank to auto-generate)', 'woocommerce' ); ?></span></label>
						<input type="text" id="bundle_slug" name="bundle_slug" class="widefat" value="<?php echo esc_attr( $slug ); ?>" placeholder="tilers-cutting-drilling-kit">
					</p>

					<p class="ats-field">
						<label for="bundle_description"><strong><?php esc_html_e( 'Description', 'woocommerce' ); ?></strong></label>
					</p>
					<?php
					wp_editor(
						$description,
						'bundle_description',
						array(
							'textarea_name' => 'bundle_description',
							'textarea_rows' => 6,
							'media_buttons' => false,
						)
					);
					?>

					<h2 class="ats-section-title"><?php esc_html_e( 'Included products', 'woocommerce' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Search and add the products in this kit. Each one can have its own short description shown in the “What’s in the box” section.', 'woocommerce' ); ?></p>

					<div id="ats-bundle-items">
						<?php
						if ( $items ) {
							foreach ( $items as $item ) {
								ats_bundle_render_item_row( $item['id'], $item['title'], $item['price'], $item['description'], isset( $item['variations'] ) ? $item['variations'] : array(), isset( $item['option'] ) ? $item['option'] : '' );
							}
						}
						?>
					</div>
					<p><button type="button" class="button" id="ats-bundle-add-item">+ <?php esc_html_e( 'Add product', 'woocommerce' ); ?></button></p>
					<p class="ats-components-readout"><?php esc_html_e( 'Components total:', 'woocommerce' ); ?> <strong data-components-total>£0.00</strong></p>

					<h2 class="ats-section-title"><?php esc_html_e( 'Pricing', 'woocommerce' ); ?></h2>

					<p class="ats-field">
						<label><input type="checkbox" id="bundle_has_options" name="bundle_has_options" value="yes" <?php checked( $has_options ); ?>> <?php esc_html_e( 'This kit has price options (e.g. Single row / Double row)', 'woocommerce' ); ?></label>
					</p>

					<div class="ats-single-price" <?php echo $has_options ? 'style="display:none"' : ''; ?>>
						<div class="ats-price-row">
							<p class="ats-field ats-field--inline">
								<label for="bundle_price"><strong><?php esc_html_e( 'Kit price (£)', 'woocommerce' ); ?></strong></label>
								<input type="text" id="bundle_price" name="bundle_price" value="<?php echo esc_attr( '' !== $price ? wc_format_localized_price( $price ) : '' ); ?>" class="wc_input_price" inputmode="decimal">
							</p>
							<p class="ats-field ats-field--inline">
								<label for="bundle_save"><strong><?php esc_html_e( 'Save (£)', 'woocommerce' ); ?></strong></label>
								<input type="text" id="bundle_save" name="bundle_save" value="<?php echo esc_attr( '' !== $save ? wc_format_localized_price( $save ) : '' ); ?>" class="wc_input_price" inputmode="decimal">
								<span class="ats-suggest" data-suggest-single></span>
							</p>
						</div>
					</div>

					<div class="ats-options-block" <?php echo $has_options ? '' : 'style="display:none"'; ?>>
						<div id="ats-bundle-options">
							<?php
							if ( $options ) {
								foreach ( $options as $opt ) {
									ats_bundle_render_option_row( $opt['label'], $opt['price'], $opt['save'], $opt['sku'] );
								}
							}
							?>
						</div>
						<p><button type="button" class="button" id="ats-bundle-add-option">+ <?php esc_html_e( 'Add option', 'woocommerce' ); ?></button></p>
					</div>

				</div>

				<div class="ats-bundle-side">
					<div class="ats-card">
						<h2><?php esc_html_e( 'Publish', 'woocommerce' ); ?></h2>
						<p class="ats-field">
							<label for="bundle_status"><strong><?php esc_html_e( 'Status', 'woocommerce' ); ?></strong></label>
							<select id="bundle_status" name="bundle_status" class="widefat">
								<option value="draft" <?php selected( $status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'woocommerce' ); ?></option>
								<option value="publish" <?php selected( $status, 'publish' ); ?>><?php esc_html_e( 'Published', 'woocommerce' ); ?></option>
							</select>
						</p>
						<p class="ats-field">
							<label for="bundle_sku"><strong><?php esc_html_e( 'Base SKU', 'woocommerce' ); ?></strong> <span class="description"><?php esc_html_e( '(optional)', 'woocommerce' ); ?></span></label>
							<input type="text" id="bundle_sku" name="bundle_sku" class="widefat" value="<?php echo esc_attr( $sku ); ?>">
						</p>
						<p class="ats-field">
							<label for="bundle_shipping_class"><strong><?php esc_html_e( 'Shipping class', 'woocommerce' ); ?></strong></label>
							<select id="bundle_shipping_class" name="bundle_shipping_class" class="widefat">
								<option value="0"><?php esc_html_e( 'No shipping class', 'woocommerce' ); ?></option>
								<?php foreach ( get_terms( array( 'taxonomy' => 'product_shipping_class', 'hide_empty' => false ) ) as $sc ) : ?>
									<?php if ( is_wp_error( $sc ) ) { continue; } ?>
									<option value="<?php echo esc_attr( $sc->term_id ); ?>" <?php selected( $ship_class, $sc->term_id ); ?>><?php echo esc_html( $sc->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p class="ats-field">
							<label for="bundle_weight"><strong><?php esc_html_e( 'Weight (kg)', 'woocommerce' ); ?></strong> <span class="description"><?php esc_html_e( '(optional)', 'woocommerce' ); ?></span></label>
							<input type="text" id="bundle_weight" name="bundle_weight" class="widefat" value="<?php echo esc_attr( $weight ); ?>" inputmode="decimal">
						</p>
						<p>
							<button type="submit" class="button button-primary button-large"><?php echo $is_edit ? esc_html__( 'Update bundle', 'woocommerce' ) : esc_html__( 'Create bundle', 'woocommerce' ); ?></button>
						</p>
						<?php if ( $is_edit ) : ?>
							<p><a href="<?php echo esc_url( get_permalink( $bundle_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View bundle on site →', 'woocommerce' ); ?></a></p>
						<?php endif; ?>
					</div>

					<div class="ats-card">
						<h2><?php esc_html_e( 'Custom image', 'woocommerce' ); ?></h2>
						<div class="ats-image-preview" data-image-preview>
							<?php
							if ( $image_id ) {
								echo wp_get_attachment_image( $image_id, 'medium', false, array( 'style' => 'max-width:100%;height:auto;border-radius:6px;' ) );
							}
							?>
						</div>
						<input type="hidden" id="bundle_image_id" name="bundle_image_id" value="<?php echo esc_attr( $image_id ); ?>">
						<p>
							<button type="button" class="button" id="ats-bundle-select-image"><?php esc_html_e( 'Select image', 'woocommerce' ); ?></button>
							<button type="button" class="button-link delete" id="ats-bundle-remove-image" style="<?php echo $image_id ? '' : 'display:none'; ?>"><?php esc_html_e( 'Remove', 'woocommerce' ); ?></button>
						</p>
					</div>

					<div class="ats-card">
						<h2><?php esc_html_e( 'Gallery images', 'woocommerce' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Optional. Extra photos shown in the product image gallery (with thumbnails).', 'woocommerce' ); ?></p>
						<div class="ats-gallery-preview" data-gallery-preview>
							<?php foreach ( $gallery_ids as $gid ) : ?>
								<span class="ats-gallery-thumb" data-id="<?php echo esc_attr( $gid ); ?>">
									<?php echo wp_get_attachment_image( $gid, array( 60, 60 ), false, array( 'style' => 'width:60px;height:60px;object-fit:cover;border-radius:4px;display:block;' ) ); ?>
									<button type="button" class="ats-gallery-remove" aria-label="<?php esc_attr_e( 'Remove', 'woocommerce' ); ?>">&times;</button>
								</span>
							<?php endforeach; ?>
						</div>
						<input type="hidden" id="bundle_gallery_ids" name="bundle_gallery_ids" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>">
						<p><button type="button" class="button" id="ats-bundle-add-gallery"><?php esc_html_e( 'Add gallery images', 'woocommerce' ); ?></button></p>
					</div>
				</div>
			</div>
		</form>

		<?php ats_bundle_render_js_templates(); ?>
	</div>
	<?php
}

/**
 * Render a single included-product row (used server-side and as a JS template).
 *
 * @param int    $id          Product ID (0 for empty template).
 * @param string $title       Product title.
 * @param float  $price       Product price (for the save suggestion).
 * @param string $description Per-item description.
 * @return void
 */
function ats_bundle_render_item_row( $id = 0, $title = '', $price = 0, $description = '', $variations = array(), $option = '' ) {
	$variations_csv = implode( ',', array_map( 'intval', (array) $variations ) );
	?>
	<div class="ats-item-row">
		<div class="ats-item-row__main">
			<div class="ats-item-row__select">
				<select name="bundle_item_id[]" class="ats-product-search" style="width:100%">
					<?php if ( $id ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" selected data-price="<?php echo esc_attr( $price ); ?>"><?php echo esc_html( $title . ' (#' . $id . ')' ); ?></option>
					<?php endif; ?>
				</select>
			</div>
			<div class="ats-item-row__desc">
				<textarea name="bundle_item_desc[]" rows="2" placeholder="<?php esc_attr_e( 'Short description for this product in the kit…', 'woocommerce' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
			</div>
			<button type="button" class="button-link delete ats-remove-item" title="<?php esc_attr_e( 'Remove', 'woocommerce' ); ?>">&times;</button>
		</div>
		<input type="hidden" name="bundle_item_variations[]" value="<?php echo esc_attr( $variations_csv ); ?>" data-variations-input>
		<div class="ats-item-row__variations" data-variations></div>
		<div class="ats-item-row__option" data-item-option-wrap style="display:none">
			<label><?php esc_html_e( 'Show for:', 'woocommerce' ); ?>
				<select name="bundle_item_option[]" class="ats-item-option" data-saved="<?php echo esc_attr( '' !== $option && null !== $option ? (int) $option : '' ); ?>">
					<option value=""><?php esc_html_e( 'All options', 'woocommerce' ); ?></option>
				</select>
			</label>
		</div>
	</div>
	<?php
}

/**
 * Render a single price-option row.
 *
 * @param string $label Option label.
 * @param float  $price Option price.
 * @param float  $save  Option save figure.
 * @param string $sku   Option SKU.
 * @return void
 */
function ats_bundle_render_option_row( $label = '', $price = '', $save = '', $sku = '' ) {
	?>
	<div class="ats-option-row">
		<input type="text" name="bundle_opt_label[]" placeholder="<?php esc_attr_e( 'Label (e.g. Single row)', 'woocommerce' ); ?>" value="<?php echo esc_attr( $label ); ?>">
		<input type="text" name="bundle_opt_price[]" class="ats-opt-price" placeholder="<?php esc_attr_e( 'Price £', 'woocommerce' ); ?>" value="<?php echo '' !== $price ? esc_attr( wc_format_localized_price( $price ) ) : ''; ?>" inputmode="decimal">
		<span class="ats-opt-save-wrap">
			<input type="text" name="bundle_opt_save[]" class="ats-opt-save" placeholder="<?php esc_attr_e( 'Save £', 'woocommerce' ); ?>" value="<?php echo '' !== $save ? esc_attr( wc_format_localized_price( $save ) ) : ''; ?>" inputmode="decimal">
			<span class="ats-suggest" data-suggest-option></span>
		</span>
		<input type="text" name="bundle_opt_sku[]" placeholder="<?php esc_attr_e( 'SKU', 'woocommerce' ); ?>" value="<?php echo esc_attr( $sku ); ?>">
		<button type="button" class="button-link delete ats-remove-option" title="<?php esc_attr_e( 'Remove', 'woocommerce' ); ?>">&times;</button>
	</div>
	<?php
}

/**
 * Hidden HTML templates cloned by the admin JS for new rows.
 *
 * @return void
 */
function ats_bundle_render_js_templates() {
	echo '<script type="text/template" id="ats-tmpl-item">';
	ats_bundle_render_item_row();
	echo '</script>';
	echo '<script type="text/template" id="ats-tmpl-option">';
	ats_bundle_render_option_row();
	echo '</script>';
}

/**
 * Handle the bundle save (create or update).
 *
 * @return void
 */
function ats_bundle_handle_save() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage bundles.', 'woocommerce' ) );
	}
	check_admin_referer( 'ats_save_bundle' );

	$bundle_id = isset( $_POST['bundle_id'] ) ? (int) $_POST['bundle_id'] : 0;
	$title     = isset( $_POST['bundle_title'] ) ? sanitize_text_field( wp_unslash( $_POST['bundle_title'] ) ) : '';
	if ( '' === $title ) {
		$title = __( 'Untitled Bundle', 'woocommerce' );
	}
	$description = isset( $_POST['bundle_description'] ) ? wp_kses_post( wp_unslash( $_POST['bundle_description'] ) ) : '';
	$status      = ( isset( $_POST['bundle_status'] ) && 'publish' === $_POST['bundle_status'] ) ? 'publish' : 'draft';
	$slug        = isset( $_POST['bundle_slug'] ) ? sanitize_title( wp_unslash( $_POST['bundle_slug'] ) ) : '';
	$image_id    = isset( $_POST['bundle_image_id'] ) ? (int) $_POST['bundle_image_id'] : 0;
	$gallery_ids = array();
	if ( isset( $_POST['bundle_gallery_ids'] ) && '' !== $_POST['bundle_gallery_ids'] ) {
		$gallery_ids = array_values( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['bundle_gallery_ids'] ) ) ) ) ) );
	}
	$sku         = isset( $_POST['bundle_sku'] ) ? sanitize_text_field( wp_unslash( $_POST['bundle_sku'] ) ) : '';
	$ship_class  = isset( $_POST['bundle_shipping_class'] ) ? (int) $_POST['bundle_shipping_class'] : 0;
	$weight      = isset( $_POST['bundle_weight'] ) ? wc_format_decimal( wp_unslash( $_POST['bundle_weight'] ) ) : '';
	$has_options = isset( $_POST['bundle_has_options'] ) ? 'yes' : 'no';
	$price       = isset( $_POST['bundle_price'] ) ? wc_format_decimal( wp_unslash( $_POST['bundle_price'] ) ) : '';
	$save        = isset( $_POST['bundle_save'] ) ? wc_format_decimal( wp_unslash( $_POST['bundle_save'] ) ) : '';

	// Included products.
	$items = array();
	if ( isset( $_POST['bundle_item_id'] ) && is_array( $_POST['bundle_item_id'] ) ) {
		$ids      = array_map( 'intval', wp_unslash( $_POST['bundle_item_id'] ) );
		$descs    = isset( $_POST['bundle_item_desc'] ) ? (array) wp_unslash( $_POST['bundle_item_desc'] ) : array();
		$var_csvs = isset( $_POST['bundle_item_variations'] ) ? (array) wp_unslash( $_POST['bundle_item_variations'] ) : array();
		$opt_idx  = isset( $_POST['bundle_item_option'] ) ? (array) wp_unslash( $_POST['bundle_item_option'] ) : array();
		foreach ( $ids as $i => $pid ) {
			if ( ! $pid ) {
				continue;
			}
			$variations = array();
			if ( isset( $var_csvs[ $i ] ) && '' !== $var_csvs[ $i ] ) {
				$variations = array_values( array_filter( array_map( 'absint', explode( ',', $var_csvs[ $i ] ) ) ) );
			}
			$item_option = ( isset( $opt_idx[ $i ] ) && '' !== $opt_idx[ $i ] ) ? (int) $opt_idx[ $i ] : '';
			$items[]     = array(
				'id'          => $pid,
				'description' => isset( $descs[ $i ] ) ? wp_kses_post( $descs[ $i ] ) : '',
				'variations'  => $variations,
				'option'      => $item_option,
			);
		}
	}

	// Price options.
	$options = array();
	if ( 'yes' === $has_options && isset( $_POST['bundle_opt_label'] ) && is_array( $_POST['bundle_opt_label'] ) ) {
		$labels = (array) wp_unslash( $_POST['bundle_opt_label'] );
		$prices = isset( $_POST['bundle_opt_price'] ) ? (array) wp_unslash( $_POST['bundle_opt_price'] ) : array();
		$saves  = isset( $_POST['bundle_opt_save'] ) ? (array) wp_unslash( $_POST['bundle_opt_save'] ) : array();
		$skus   = isset( $_POST['bundle_opt_sku'] ) ? (array) wp_unslash( $_POST['bundle_opt_sku'] ) : array();
		foreach ( $labels as $i => $label ) {
			$label     = sanitize_text_field( $label );
			$opt_price = isset( $prices[ $i ] ) ? wc_format_decimal( $prices[ $i ] ) : '';
			if ( '' === $label && '' === $opt_price ) {
				continue;
			}
			$options[] = array(
				'label' => $label,
				'price' => '' !== $opt_price ? (float) $opt_price : 0.0,
				'save'  => isset( $saves[ $i ] ) && '' !== $saves[ $i ] ? (float) wc_format_decimal( $saves[ $i ] ) : 0.0,
				'sku'   => isset( $skus[ $i ] ) ? sanitize_text_field( $skus[ $i ] ) : '',
			);
		}
	}
	if ( empty( $options ) ) {
		$has_options = 'no';
	}

	// Upsert the product post.
	$postarr = array(
		'post_type'    => 'product',
		'post_title'   => $title,
		'post_content' => $description,
		'post_status'  => $status,
	);
	if ( '' !== $slug ) {
		$postarr['post_name'] = $slug;
	}
	if ( $bundle_id ) {
		$postarr['ID'] = $bundle_id;
		$result        = wp_update_post( $postarr, true );
	} else {
		$result = wp_insert_post( $postarr, true );
	}
	if ( is_wp_error( $result ) || ! $result ) {
		wp_safe_redirect( admin_url( 'admin.php?page=ats-bundles&message=error' ) );
		exit;
	}
	$bundle_id = (int) ( $bundle_id ? $bundle_id : $result );

	// Ensure it is a simple product.
	wp_set_object_terms( $bundle_id, 'simple', 'product_type' );

	$effective_price = ( 'yes' === $has_options && $options ) ? (float) $options[0]['price'] : ( '' !== $price ? (float) $price : 0.0 );

	$product = wc_get_product( $bundle_id );
	if ( $product ) {
		$product->set_regular_price( $effective_price );
		$product->set_price( $effective_price );
		if ( '' !== $sku ) {
			try {
				$product->set_sku( $sku );
			} catch ( Exception $e ) {
				// Ignore duplicate-SKU errors; keep saving the rest.
			}
		}
		if ( $image_id ) {
			$product->set_image_id( $image_id );
		}
		$product->set_gallery_image_ids( $gallery_ids );
		$product->set_shipping_class_id( $ship_class );
		$product->set_weight( '' !== $weight ? $weight : '' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_status( $status );
		$product->save();
	}

	// Bundle meta.
	update_post_meta( $bundle_id, ATS_BUNDLE_META_FLAG, 'yes' );
	update_post_meta( $bundle_id, ATS_BUNDLE_META_IMAGE, $image_id );
	update_post_meta( $bundle_id, ATS_BUNDLE_META_ITEMS, $items );
	update_post_meta( $bundle_id, ATS_BUNDLE_META_HASOPT, $has_options );
	update_post_meta( $bundle_id, ATS_BUNDLE_META_OPTIONS, $options );
	update_post_meta( $bundle_id, ATS_BUNDLE_META_SAVE, '' !== $save ? (float) $save : 0.0 );

	// Keep the bundle in the "Bundles" category so it shows in the shop sidebar + archive.
	ats_bundle_assign_category( $bundle_id );

	if ( function_exists( 'ats_clear_product_cache' ) ) {
		ats_clear_product_cache( $bundle_id );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=ats-bundles&action=edit&bundle_id=' . $bundle_id . '&message=saved' ) );
	exit;
}
add_action( 'admin_post_ats_save_bundle', 'ats_bundle_handle_save' );

/**
 * AJAX: product search for the included-products picker.
 *
 * @return void
 */
function ats_bundle_ajax_product_search() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json( array( 'results' => array() ) );
	}
	check_ajax_referer( 'ats_bundle_admin', 'nonce' );

	$term = isset( $_GET['q'] ) ? wc_clean( wp_unslash( $_GET['q'] ) ) : '';

	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $term,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => ATS_BUNDLE_META_FLAG,
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	$results = array();
	foreach ( $query->posts as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}
		$results[] = array(
			'id'    => $pid,
			'text'  => $p->get_name() . ' (#' . $pid . ')',
			'price' => (float) $p->get_price(),
		);
	}

	wp_send_json( array( 'results' => $results ) );
}
add_action( 'wp_ajax_ats_bundle_product_search', 'ats_bundle_ajax_product_search' );

/**
 * AJAX: fetch the variations of a variable product for the included-products picker.
 *
 * @return void
 */
function ats_bundle_ajax_variations() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json( array( 'variations' => array() ) );
	}
	check_ajax_referer( 'ats_bundle_admin', 'nonce' );

	$pid     = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0;
	$product = $pid ? wc_get_product( $pid ) : null;
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		wp_send_json( array( 'variations' => array() ) );
	}

	$out = array();
	foreach ( $product->get_children() as $vid ) {
		$variation = wc_get_product( $vid );
		if ( ! $variation || 'publish' !== $variation->get_status() ) {
			continue;
		}
		$label = wc_get_formatted_variation( $variation, true, false, false );
		if ( '' === $label ) {
			$label = $variation->get_name();
		}
		$out[] = array(
			'id'    => (int) $vid,
			'label' => $label,
			'price' => wp_strip_all_tags( wc_price( (float) $variation->get_price() ) ),
		);
	}

	wp_send_json( array( 'variations' => $out ) );
}
add_action( 'wp_ajax_ats_bundle_variations', 'ats_bundle_ajax_variations' );

/**
 * Admin CSS for the Bundles screen.
 *
 * @return string
 */
function ats_bundle_admin_css() {
	return <<<'CSS'
.ats-bundle-grid{display:grid;grid-template-columns:1fr 300px;gap:24px;margin-top:16px;align-items:start}
.ats-bundle-main{background:#fff;border:1px solid #e2e4e7;border-radius:6px;padding:20px}
.ats-field{margin:0 0 16px}
.ats-field label{display:block;margin-bottom:4px}
.ats-section-title{margin-top:28px;padding-top:18px;border-top:1px solid #eee;font-size:1.1rem}
.ats-item-row{margin-bottom:10px;padding:10px;background:#fafafa;border:1px solid #eee;border-radius:5px}
.ats-item-row__main{display:grid;grid-template-columns:1fr 1fr 28px;gap:10px;align-items:start}
.ats-item-row__desc textarea{width:100%}
.ats-item-row__variations:empty{display:none}
.ats-item-row__variations{margin-top:10px;padding-top:8px;border-top:1px dashed #e0e0e0}
.ats-bundle-variations__title{font-size:11px;font-weight:600;color:#646970;margin:0 0 5px;text-transform:uppercase;letter-spacing:.03em}
.ats-bundle-variations__list{display:flex;flex-wrap:wrap;gap:5px 16px}
.ats-bundle-variations__list label{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#1d2327;margin:0;cursor:pointer}
.ats-bundle-variations__loading{font-size:11px;color:#646970}
.ats-item-row__option{margin-top:8px;font-size:12px;color:#1d2327}
.ats-item-row__option select{margin-left:6px;max-width:240px}
.ats-option-row{display:grid;grid-template-columns:1.2fr .8fr 1.2fr .9fr 28px;gap:8px;align-items:center;margin-bottom:8px}
.ats-option-row input{width:100%}
.ats-opt-save-wrap{display:flex;flex-direction:column}
.ats-price-row{display:flex;gap:24px;flex-wrap:wrap}
.ats-field--inline{min-width:160px}
.ats-suggest{display:block;font-size:11px;color:#646970;margin-top:3px;min-height:14px}
.ats-suggest a{cursor:pointer}
.ats-components-readout{color:#646970}
.ats-remove-item,.ats-remove-option{font-size:20px;line-height:1;color:#b32d2e;text-decoration:none}
.ats-card{background:#fff;border:1px solid #e2e4e7;border-radius:6px;padding:16px;margin-bottom:16px}
.ats-card h2{margin-top:0;font-size:1rem}
.ats-image-preview img{display:block}
.ats-gallery-preview{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0}
.ats-gallery-thumb{position:relative;display:inline-block;line-height:0}
.ats-gallery-thumb .ats-gallery-remove{position:absolute;top:-6px;right:-6px;width:18px;height:18px;line-height:16px;border-radius:50%;border:none;background:#b32d2e;color:#fff;cursor:pointer;font-size:12px;padding:0;text-align:center}
@media(max-width:900px){.ats-bundle-grid{grid-template-columns:1fr}}
CSS;
}

/**
 * Admin JS for the Bundles screen (NOWDOC — uses window.ATS_BUNDLE config).
 *
 * @return string
 */
function ats_bundle_admin_js() {
	return <<<'JS'
jQuery(function($){
	var cfg = window.ATS_BUNDLE || {};

	function initProductSelect($sel){
		$sel.selectWoo({
			width: '100%',
			placeholder: 'Search products…',
			minimumInputLength: 2,
			ajax: {
				url: cfg.ajaxurl,
				dataType: 'json',
				delay: 250,
				data: function(params){ return { action:'ats_bundle_product_search', nonce: cfg.nonce, q: params.term }; },
				processResults: function(data){ return { results: (data && data.results) || [] }; }
			}
		}).on('select2:select', function(e){
			var d = e.params.data || {};
			// Persist the chosen product's price on its <option> for the totals calc.
			$(this).find('option[value="'+d.id+'"]').attr('data-price', d.price || 0);
			loadVariations($(this).closest('.ats-item-row'), d.id, '__all__');
			recalc();
		}).on('change', recalc);
	}

	// Variation checkboxes for variable components.
	function syncVariationsInput($row){
		var ids = [];
		$row.find('[data-variations] input[type="checkbox"]:checked').each(function(){ ids.push(this.value); });
		$row.find('[data-variations-input]').val(ids.join(','));
	}

	function loadVariations($row, productId, mode){
		var $cont = $row.find('[data-variations]');
		var $input = $row.find('[data-variations-input]');
		if(!productId){ $cont.empty(); $input.val(''); return; }
		$cont.html('<span class="ats-bundle-variations__loading">Loading variations…</span>');
		$.ajax({
			url: cfg.ajaxurl, dataType: 'json',
			data: { action:'ats_bundle_variations', nonce: cfg.nonce, product_id: productId },
			success: function(data){
				var vars = (data && data.variations) || [];
				if(!vars.length){ $cont.empty(); $input.val(''); return; } // simple product
				var saved = (mode && mode !== '__all__') ? String(mode).split(',').filter(Boolean) : null;
				var checkAll = (mode === '__all__') || !saved || saved.length === 0;
				var html = '<div class="ats-bundle-variations__title">Variations included</div><div class="ats-bundle-variations__list">';
				vars.forEach(function(v){
					var checked = (checkAll || saved.indexOf(String(v.id)) !== -1) ? 'checked' : '';
					html += '<label><input type="checkbox" class="ats-variation-cb" value="'+v.id+'" '+checked+'> '+v.label+' <span style="color:#646970">('+v.price+')</span></label>';
				});
				html += '</div>';
				$cont.html(html);
				syncVariationsInput($row);
			},
			error: function(){ $cont.empty(); }
		});
	}
	$(document).on('change','.ats-variation-cb',function(){ syncVariationsInput($(this).closest('.ats-item-row')); });

	// Per-item "Show for option" dropdowns — populated from the defined options.
	function refreshItemOptionSelects(){
		var hasOpts = $('#bundle_has_options').is(':checked');
		var labels = [];
		$('#ats-bundle-options .ats-option-row').each(function(){
			labels.push($(this).find('input[name="bundle_opt_label[]"]').val() || '');
		});
		$('.ats-item-option').each(function(){
			var $sel = $(this);
			var cur = ($sel.val() != null && $sel.val() !== '') ? $sel.val() : ($sel.attr('data-saved') || '');
			$sel.empty().append($('<option>').val('').text('All options'));
			labels.forEach(function(lbl, i){
				$sel.append($('<option>').val(i).text('Option ' + (i+1) + (lbl ? ': ' + lbl : '')));
			});
			$sel.val((cur !== '' && parseInt(cur,10) < labels.length) ? cur : '');
		});
		$('[data-item-option-wrap]').toggle(hasOpts && labels.length > 0);
	}
	$(document).on('input','.ats-option-row input[name="bundle_opt_label[]"]', refreshItemOptionSelects);

	function num(v){ v = parseFloat(String(v).replace(/[^0-9.\-]/g,'')); return isNaN(v) ? 0 : v; }

	function componentsTotal(){
		var total = 0;
		$('#ats-bundle-items .ats-product-search').each(function(){
			var opt = this.options[this.selectedIndex];
			if(opt){ total += num(opt.getAttribute('data-price')); }
		});
		return total;
	}

	function fmt(n){ return '£' + n.toFixed(2); }

	function recalc(){
		var total = componentsTotal();
		$('[data-components-total]').text(fmt(total));

		// Single price suggestion.
		var price = num($('#bundle_price').val());
		var $s = $('[data-suggest-single]');
		if(total > 0 && price > 0 && total > price){
			var sug = (total - price);
			$s.html('Suggested: <a data-fill="'+sug.toFixed(2)+'">'+fmt(sug)+'</a>');
		} else { $s.empty(); }

		// Per-option suggestions.
		$('#ats-bundle-options .ats-option-row').each(function(){
			var op = num($(this).find('.ats-opt-price').val());
			var $os = $(this).find('[data-suggest-option]');
			if(total > 0 && op > 0 && total > op){
				var s = (total - op);
				$os.html('Suggested: <a data-fill-option="'+s.toFixed(2)+'">'+fmt(s)+'</a>');
			} else { $os.empty(); }
		});
	}

	// Fill suggested values.
	$(document).on('click','[data-fill]',function(){ $('#bundle_save').val($(this).data('fill')); });
	$(document).on('click','[data-fill-option]',function(){ $(this).closest('.ats-option-row').find('.ats-opt-save').val($(this).data('fillOption')); });

	// Init existing product selects + load their variation checkboxes (checking saved ones).
	$('#ats-bundle-items .ats-product-search').each(function(){ initProductSelect($(this)); });
	$('#ats-bundle-items .ats-item-row').each(function(){
		var $row = $(this);
		var pid = $row.find('.ats-product-search').val();
		if(pid){ loadVariations($row, pid, $row.find('[data-variations-input]').val()); }
	});
	refreshItemOptionSelects();

	// Add product row.
	$('#ats-bundle-add-item').on('click', function(){
		var html = $('#ats-tmpl-item').html();
		var $row = $(html);
		$('#ats-bundle-items').append($row);
		initProductSelect($row.find('.ats-product-search'));
		refreshItemOptionSelects();
	});
	$(document).on('click','.ats-remove-item',function(){
		var $row = $(this).closest('.ats-item-row');
		$row.find('.ats-product-search').selectWoo('destroy');
		$row.remove();
		recalc();
	});

	// Options toggle.
	$('#bundle_has_options').on('change', function(){
		if(this.checked){
			$('.ats-options-block').show(); $('.ats-single-price').hide();
			if($('#ats-bundle-options .ats-option-row').length === 0){ $('#ats-bundle-add-option').trigger('click'); }
		} else {
			$('.ats-options-block').hide(); $('.ats-single-price').show();
		}
		refreshItemOptionSelects();
	});

	// Add option row.
	$('#ats-bundle-add-option').on('click', function(){
		$('#ats-bundle-options').append($('#ats-tmpl-option').html());
		refreshItemOptionSelects();
	});
	$(document).on('click','.ats-remove-option',function(){ $(this).closest('.ats-option-row').remove(); recalc(); refreshItemOptionSelects(); });

	$(document).on('input','#bundle_price, .ats-opt-price', recalc);

	// Media uploader.
	var frame;
	$('#ats-bundle-select-image').on('click', function(e){
		e.preventDefault();
		if(frame){ frame.open(); return; }
		frame = wp.media({ title:'Select bundle image', button:{text:'Use this image'}, multiple:false });
		frame.on('select', function(){
			var att = frame.state().get('selection').first().toJSON();
			var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
			$('#bundle_image_id').val(att.id);
			$('[data-image-preview]').html('<img src="'+url+'" style="max-width:100%;height:auto;border-radius:6px;">');
			$('#ats-bundle-remove-image').show();
		});
		frame.open();
	});
	$('#ats-bundle-remove-image').on('click', function(){
		$('#bundle_image_id').val('');
		$('[data-image-preview]').empty();
		$(this).hide();
	});

	// Gallery images (multi-select).
	var galleryFrame;
	function galleryIds(){ var v = $('#bundle_gallery_ids').val(); return v ? v.split(',').filter(Boolean) : []; }
	function setGalleryIds(ids){ $('#bundle_gallery_ids').val(ids.join(',')); }
	$('#ats-bundle-add-gallery').on('click', function(e){
		e.preventDefault();
		galleryFrame = wp.media({ title:'Select gallery images', button:{text:'Add to gallery'}, multiple:true });
		galleryFrame.on('select', function(){
			var ids = galleryIds();
			galleryFrame.state().get('selection').toJSON().forEach(function(att){
				if(ids.indexOf(String(att.id)) === -1){
					ids.push(String(att.id));
					var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
					$('[data-gallery-preview]').append('<span class="ats-gallery-thumb" data-id="'+att.id+'"><img src="'+url+'" style="width:60px;height:60px;object-fit:cover;border-radius:4px;display:block;"><button type="button" class="ats-gallery-remove" aria-label="Remove">&times;</button></span>');
				}
			});
			setGalleryIds(ids);
		});
		galleryFrame.open();
	});
	$(document).on('click','.ats-gallery-remove',function(){
		var $thumb = $(this).closest('.ats-gallery-thumb');
		var id = String($thumb.data('id'));
		setGalleryIds(galleryIds().filter(function(x){ return x !== id; }));
		$thumb.remove();
	});

	recalc();
});
JS;
}
