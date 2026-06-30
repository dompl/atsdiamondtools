<?php
/**
 * Mini-cart "Upgrade to the kit" upsell.
 *
 * When the cart contains a product that is a component of one of our bundles
 * ("Pro Kits"), show a banner under that line in the slide-out mini-cart offering
 * to swap the single item for the kit (one click: remove the item, add the kit).
 *
 * - The banner is rendered server-side from inside `ats_get_cart_items_html()`
 *   (see the one-line hook in functions/shortcodes/add_to_cart/ajax-handler.php),
 *   so it stays in sync on add / open / qty-change / remove, and covers every
 *   add-to-cart path (product page, quick-view, listing) for free.
 * - The swap runs through a dedicated AJAX endpoint that mirrors the existing
 *   ats_remove_cart_item / ats_update_cart_item handlers (same nonce, same
 *   `ats_get_updated_cart_data()` response shape).
 * - Self-contained inline CSS/JS, no build step (same pattern as the sale-badge,
 *   exit-intent and screen-reader-fix files). Deploy by rsync of this file (plus
 *   the ajax-handler.php hook) into the active theme.
 *
 * Design: docs/specs/2026-06-24-bundle-upsell-design.md
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Find the best (highest-saving) published kit a product belongs to.
 *
 * Skips kits that are already in the cart (no point upselling them).
 *
 * @param int $product_id Component product ID.
 * @return array|null { id:int, save:float } or null when there's no eligible kit.
 */
function ats_bundle_best_kit_for_product( $product_id ) {
	$product_id = (int) $product_id;
	if ( ! $product_id || ! function_exists( 'ats_bundle_get_bundles_for_product' ) ) {
		return null;
	}

	$bundle_ids = ats_bundle_get_bundles_for_product( $product_id ); // Published only.
	if ( empty( $bundle_ids ) ) {
		return null;
	}

	// Kits already in the cart — exclude them.
	$in_cart = array();
	if ( ! is_null( WC()->cart ) ) {
		foreach ( WC()->cart->get_cart() as $ci ) {
			$in_cart[ (int) $ci['product_id'] ] = true;
		}
	}

	$best = null;
	foreach ( $bundle_ids as $bid ) {
		$bid = (int) $bid;
		if ( isset( $in_cart[ $bid ] ) || 'publish' !== get_post_status( $bid ) ) {
			continue;
		}
		$save = (float) ats_bundle_max_save( $bid );
		if ( null === $best || $save > $best['save'] ) {
			$best = array(
				'id'   => $bid,
				'save' => $save,
			);
		}
	}

	return $best;
}

/**
 * Render the mini-cart upsell banner for a single cart line.
 *
 * @param int    $product_id The line item's product ID.
 * @param string $cart_key   The cart item key.
 * @return string Banner HTML, or '' when no banner applies.
 */
function ats_bundle_render_minicart_upsell( $product_id, $cart_key ) {
	$product_id = (int) $product_id;
	if ( ! $product_id || ! function_exists( 'ats_is_bundle' ) ) {
		return '';
	}

	// The mini-cart passes the variation ID for variable products, but bundles
	// reference the PARENT product ID — resolve it so the lookup matches.
	$line_product = wc_get_product( $product_id );
	if ( $line_product && $line_product->is_type( 'variation' ) ) {
		$product_id = (int) $line_product->get_parent_id();
	}

	// Never upsell on a kit line itself.
	if ( ! $product_id || ats_is_bundle( $product_id ) ) {
		return '';
	}

	$best = ats_bundle_best_kit_for_product( $product_id );
	if ( ! $best ) {
		return '';
	}

	$bundle_id = (int) $best['id'];
	$save      = (float) $best['save'];
	$title     = get_the_title( $bundle_id );
	$save_txt  = $save > 0 ? html_entity_decode( wp_strip_all_tags( wc_price( $save ) ), ENT_QUOTES, 'UTF-8' ) : '';
	$items     = function_exists( 'ats_bundle_get_items' ) ? ats_bundle_get_items( $bundle_id ) : array();

	ob_start();
	?>
	<div class="ats-kit-upsell" data-kit-id="<?php echo esc_attr( $bundle_id ); ?>" data-cart-key="<?php echo esc_attr( $cart_key ); ?>">
		<button type="button" class="ats-kit-upsell__dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'skylinewp-dev-child' ); ?>">&times;</button>
		<div class="ats-kit-upsell__head">
			<span class="ats-kit-upsell__icon" aria-hidden="true">&#128154;</span>
			<span class="ats-kit-upsell__copy">
				<span class="ats-kit-upsell__lead">
					<?php
					/* translators: %s: kit name. */
					printf( esc_html__( 'Part of the %s', 'skylinewp-dev-child' ), '<strong>' . esc_html( $title ) . '</strong>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- name escaped.
					?>
				</span>
				<?php if ( '' !== $save_txt ) : ?>
					<span class="ats-kit-upsell__save">
						<?php
						/* translators: %s: saving amount. */
						printf( esc_html__( 'Buy as a kit and save %s', 'skylinewp-dev-child' ), esc_html( $save_txt ) );
						?>
					</span>
				<?php endif; ?>
			</span>
		</div>

		<?php if ( ! empty( $items ) ) : ?>
			<button type="button" class="ats-kit-upsell__toggle" aria-expanded="false">
				<?php esc_html_e( "See what's in it", 'skylinewp-dev-child' ); ?> <span class="ats-kit-upsell__chev" aria-hidden="true">&#9662;</span>
			</button>
			<ul class="ats-kit-upsell__contents" hidden>
				<?php
				foreach ( $items as $it ) :
					$label = isset( $it['title'] ) ? $it['title'] : '';
					if ( ! empty( $it['variation_labels'] ) && is_array( $it['variation_labels'] ) ) {
						$label .= ' (' . implode( ', ', array_map( 'wp_strip_all_tags', $it['variation_labels'] ) ) . ')';
					}
					?>
					<li><?php echo esc_html( $label ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<button type="button" class="ats-kit-upsell__btn ats-kit-upsell__upgrade" data-kit-id="<?php echo esc_attr( $bundle_id ); ?>" data-cart-key="<?php echo esc_attr( $cart_key ); ?>"><?php esc_html_e( 'Upgrade to the kit', 'skylinewp-dev-child' ); ?> &rarr;</button>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * AJAX: swap a single component line for its kit.
 *
 * Mirrors ats_ajax_remove_cart_item: same `ats_mini_cart_nonce`, returns the
 * shared `ats_get_updated_cart_data()` payload (items_html + totals + count).
 *
 * @return void
 */
function ats_bundle_ajax_upgrade_to_kit() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ats_mini_cart_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'skylinewp-dev-child' ) ), 403 );
	}

	if ( is_null( WC()->cart ) ) {
		wc_load_cart();
	}

	$cart_key  = isset( $_POST['cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) : '';
	$bundle_id = isset( $_POST['bundle_id'] ) ? absint( $_POST['bundle_id'] ) : 0;

	if ( '' === $cart_key || ! $bundle_id ) {
		wp_send_json_error( array( 'message' => __( 'Missing parameters.', 'skylinewp-dev-child' ) ), 400 );
	}

	$cart     = WC()->cart;
	$contents = $cart->get_cart();

	if ( ! isset( $contents[ $cart_key ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Item not found in cart.', 'skylinewp-dev-child' ) ), 404 );
	}

	// Validate the target is a real published kit …
	if ( ! ats_is_bundle( $bundle_id ) || 'publish' !== get_post_status( $bundle_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid kit.', 'skylinewp-dev-child' ) ), 400 );
	}

	// … and that the line item is genuinely a component of that kit (no arbitrary swaps).
	$line_product_id   = (int) $contents[ $cart_key ]['product_id'];
	$component_bundles = array_map( 'intval', ats_bundle_get_bundles_for_product( $line_product_id ) );
	if ( ! in_array( $bundle_id, $component_bundles, true ) ) {
		wp_send_json_error( array( 'message' => __( 'That item is not part of this kit.', 'skylinewp-dev-child' ) ), 400 );
	}

	// For option-kits, pre-select the option that delivers the advertised (max) saving so the
	// swap stays one-click and matches the "save £X" shown in the banner. The customer can still
	// change the option afterwards. `ats_bundle_add_cart_item_data()` reads this from $_REQUEST.
	if ( ats_bundle_has_options( $bundle_id ) ) {
		$best_index = 0;
		$best_save  = -1.0;
		foreach ( ats_bundle_get_options( $bundle_id ) as $opt ) {
			if ( (float) $opt['save'] > $best_save ) {
				$best_save  = (float) $opt['save'];
				$best_index = (int) $opt['index'];
			}
		}
		$_REQUEST['ats_bundle_option'] = $best_index; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_POST['ats_bundle_option']    = $best_index; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	// Add the kit first; only remove the single line once the kit is safely in.
	$added = $cart->add_to_cart( $bundle_id, 1 );
	if ( ! $added ) {
		$message = __( 'Could not add the kit.', 'skylinewp-dev-child' );
		$notices = wc_get_notices( 'error' );
		if ( ! empty( $notices ) ) {
			$parts = array();
			foreach ( $notices as $n ) {
				$parts[] = isset( $n['notice'] ) ? wp_strip_all_tags( $n['notice'] ) : wp_strip_all_tags( $n );
			}
			if ( $parts ) {
				$message = implode( ' ', $parts );
			}
		}
		wc_clear_notices();
		wp_send_json_error( array( 'message' => $message ), 500 );
	}

	$cart->remove_cart_item( $cart_key );
	$cart->calculate_totals();

	if ( function_exists( 'ats_get_updated_cart_data' ) ) {
		wp_send_json_success( ats_get_updated_cart_data() );
	}

	// Fallback (should not happen — ats_get_updated_cart_data lives in ajax-handler.php).
	wp_send_json_success( array( 'count' => $cart->get_cart_contents_count() ) );
}
add_action( 'wp_ajax_ats_bundle_upgrade_to_kit', 'ats_bundle_ajax_upgrade_to_kit' );
add_action( 'wp_ajax_nopriv_ats_bundle_upgrade_to_kit', 'ats_bundle_ajax_upgrade_to_kit' );

/**
 * Enqueue the upsell banner CSS/JS (front-end; the mini-cart is global).
 *
 * @return void
 */
function ats_bundle_cart_upsell_assets() {
	if ( is_admin() ) {
		return;
	}

	wp_register_style( 'ats-kit-upsell', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'ats-kit-upsell' );
	wp_add_inline_style(
		'ats-kit-upsell',
		'.ats-kit-upsell{position:relative;margin:.45rem 0 .2rem;padding:.6rem .7rem;border:1px solid #bbf7d0;background:#f0fdf4;border-radius:6px;font-size:.8rem;color:#374151}'
		. '.ats-kit-upsell__dismiss{position:absolute;top:.15rem;right:.35rem;border:0;background:none;font-size:1.1rem;line-height:1;color:#9ca3af;cursor:pointer;padding:.15rem}'
		. '.ats-kit-upsell__dismiss:hover{color:#6b7280}'
		. '.ats-kit-upsell__head{display:flex;gap:.4rem;align-items:flex-start;padding-right:1rem}'
		. '.ats-kit-upsell__icon{flex:0 0 auto;font-size:.95rem;line-height:1.3}'
		. '.ats-kit-upsell__copy{display:flex;flex-direction:column;gap:.05rem;min-width:0}'
		. '.ats-kit-upsell__lead{line-height:1.3}'
		. '.ats-kit-upsell__save{font-weight:700;color:#15803d}'
		. '.ats-kit-upsell__toggle{margin-top:.3rem;border:0;background:none;padding:0;color:#15803d;font-weight:600;font-size:.72rem;cursor:pointer;display:inline-flex;align-items:center;gap:.2rem}'
		. '.ats-kit-upsell__chev{display:inline-block;transition:transform .15s}'
		. '.ats-kit-upsell__toggle[aria-expanded="true"] .ats-kit-upsell__chev{transform:rotate(180deg)}'
		. '.ats-kit-upsell__contents{margin:.35rem 0 0;padding:0 0 0 1.1rem;list-style:disc;color:#4b5563;font-size:.74rem}'
		. '.ats-kit-upsell__contents li{margin:.12rem 0}'
		. '.ats-kit-upsell__btn{display:inline-flex;align-items:center;margin-top:.5rem;background:#15803d;color:#fff;font-weight:700;font-size:.76rem;text-transform:uppercase;letter-spacing:.02em;padding:.45rem .7rem;border:0;border-radius:4px;cursor:pointer;text-decoration:none;line-height:1}'
		. '.ats-kit-upsell__btn:hover{background:#166534;color:#fff}'
		. '.ats-kit-upsell__btn[disabled]{opacity:.6;cursor:default}'
	);

	wp_register_script( 'ats-kit-upsell', false, array( 'jquery' ), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_script( 'ats-kit-upsell' );
	wp_add_inline_script( 'ats-kit-upsell', ats_bundle_cart_upsell_js() );
}
add_action( 'wp_enqueue_scripts', 'ats_bundle_cart_upsell_assets', 20 );

/**
 * Inline behaviour for the upsell banner.
 *
 * @return string
 */
function ats_bundle_cart_upsell_js() {
	return <<<'JS'
(function ($) {
	var SS = 'ats_kit_upsell_dismissed_';

	function hideDismissed(scope) {
		$('.ats-kit-upsell', scope || document).each(function () {
			try {
				if (sessionStorage.getItem(SS + $(this).data('kit-id'))) {
					$(this).hide();
				}
			} catch (e) {}
		});
	}

	function refreshDrawer(d) {
		if (!d) { return; }
		// Modal (the open drawer) — selectors/format per MiniCartModal.updateContent().
		if (typeof d.items_html !== 'undefined') { $('.js-mini-cart-items').html(d.items_html); }
		if (typeof d.subtotal !== 'undefined') { $('.js-modal-subtotal').html(d.subtotal); }
		if (typeof d.tax !== 'undefined') { $('.js-modal-tax').html(d.tax); }
		if (typeof d.total !== 'undefined') { $('.js-modal-total').html(d.total); }
		if (typeof d.count_text !== 'undefined') { $('.js-modal-item-count').text('(' + d.count_text + ')'); }
		// Header mini-cart summary — selectors/format per the MiniCart instance.
		if (typeof d.subtotal !== 'undefined') { $('.js-mini-cart-subtotal').html(d.subtotal); }
		if (typeof d.total !== 'undefined') { $('.js-mini-cart-total').html(d.total); }
		if (typeof d.tax !== 'undefined') { $('.js-mini-cart-tax').html('(inc ' + d.tax + ' VAT)'); }
		if (typeof d.count_text !== 'undefined') { $('.js-mini-cart-items-text').text(d.count_text); }
		if (typeof d.count !== 'undefined') { $('.js-mini-cart-count, .cart-count').text(d.count); }
		hideDismissed();
	}

	// Dismiss — remember per kit for the session.
	$(document).on('click', '.ats-kit-upsell__dismiss', function () {
		var $b = $(this).closest('.ats-kit-upsell');
		try { sessionStorage.setItem(SS + $b.data('kit-id'), '1'); } catch (e) {}
		$b.slideUp(120);
	});

	// Expand / collapse the kit contents.
	$(document).on('click', '.ats-kit-upsell__toggle', function () {
		var $t = $(this);
		var open = $t.attr('aria-expanded') === 'true';
		$t.attr('aria-expanded', open ? 'false' : 'true');
		$t.siblings('.ats-kit-upsell__contents').prop('hidden', open);
	});

	// Upgrade / swap to the kit.
	$(document).on('click', '.ats-kit-upsell__upgrade', function () {
		var $btn = $(this);
		if ($btn.prop('disabled')) { return; }
		var cfg = window.themeData || {};
		var original = $btn.html();
		$btn.prop('disabled', true).text('Upgrading…');

		$.ajax({
			url: cfg.ajax_url || '/wp-admin/admin-ajax.php',
			type: 'POST',
			data: {
				action: 'ats_bundle_upgrade_to_kit',
				nonce: cfg.mini_cart_nonce || '',
				cart_key: $btn.data('cart-key'),
				bundle_id: $btn.data('kit-id')
			},
			success: function (resp) {
				if (resp && resp.success) {
					refreshDrawer(resp.data);
				} else {
					$btn.prop('disabled', false).html(original);
				}
			},
			error: function () {
				$btn.prop('disabled', false).html(original);
			}
		});
	});

	$(function () {
		hideDismissed();
		// Re-apply dismissals whenever the drawer re-renders its items.
		var box = document.querySelector('.js-mini-cart-items');
		if (box && window.MutationObserver) {
			new MutationObserver(function () { hideDismissed(box); }).observe(box, { childList: true, subtree: true });
		}
	});
})(jQuery);
JS;
}
