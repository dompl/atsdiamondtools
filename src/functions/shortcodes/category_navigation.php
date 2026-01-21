<?php

/**
 * Shortcode to display category navigation sidebar.
 * Usage: [category_navigation width="w-[320px]" collapsed="true"]
 */
function shortcode_category_navigation( $atts ) {
	// Start output buffering
	ob_start();

	// extract shortcode attributes
	$atts = shortcode_atts( array(
		'width'     => 'w-[320px]',
		'collapsed' => 'false',
	), $atts );

	// Fetch Product Categories
	// Adjust taxonomy if not using standard WooCommerce 'product_cat'
	$taxonomy     = 'product_cat';
	$orderby      = 'name';
	$show_count   = 0;      // 1 for yes, 0 for no
	$pad_counts   = 0;      // 1 for yes, 0 for no
	$hierarchical = 1;      // 1 for yes, 0 for no
	$title        = '';
	$empty        = 0;
	$width        = $atts['width'];
	$is_collapsed = filter_var( $atts['collapsed'], FILTER_VALIDATE_BOOLEAN );

	$args = array(
		'taxonomy'     => $taxonomy,
		'orderby'      => $orderby,
		'show_count'   => $show_count,
		'pad_counts'   => $pad_counts,
		'hierarchical' => $hierarchical,
		'title_li'     => $title,
		'hide_empty'   => $empty,
	);

	// Add width to args for usage in template
	$args['width'] = $width;

	// Determine initial grid state class based on collapsed attribute
	// If collapsed is true, start with grid-rows-0 on desktop too, otherwise grid-rows-1 on desktop
	$grid_state_class = $is_collapsed ? 'grid-rows-0' : 'grid-rows-0 lg:grid-rows-1';

    // Determine button cursor and chevron visibility based on collapsed state
    // If collapsed: Pointer cursor everywhere, Chevron visible everywhere
    // If not collapsed (Fixed): Default cursor on Desktop, Chevron hidden on Desktop
    $btn_cursor_class = $is_collapsed ? 'cursor-pointer' : 'lg:cursor-default cursor-pointer';
    $chevron_display_class = $is_collapsed ? '' : 'lg:hidden';

    // Data attribute to tell JS if desktop toggle is allowed
    $allow_desktop_toggle = $is_collapsed ? 'true' : 'false';

	// Get all top-level categories
	$args['parent'] = 0;
	$product_categories = get_terms( $args );

	?>
	<div class="rfs-ref-banner-container rfs-ref-banner-sidebar w-full lg:<?php echo esc_attr( $args['width'] ); ?> flex-shrink-0 bg-ats-brand text-white rounded-md overflow-hidden flex flex-col relative z-20 h-fit mb-10">

		<!-- Toggle Button -->
		<button
            class="rfs-ref-category-btn w-full flex items-center justify-between p-2 lg:p-4 border-b border-white/10 <?php echo esc_attr( $btn_cursor_class ); ?> text-left outline-none focus:bg-white/5 bg-ats-brand relative z-20"
            data-allow-desktop-toggle="<?php echo esc_attr( $allow_desktop_toggle ); ?>"
        >
			<div class="flex items-center gap-3">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
				</svg>
				<h2 class="text-sm lg:text-lg font-bold tracking-wide text-white uppercase">Shop By Category</h2>
			</div>
			<!-- Chevron -->
			<svg class="rfs-ref-category-chevron <?php echo esc_attr( $chevron_display_class ); ?> h-5 w-5 text-white/70 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
			</svg>
		</button>

		<!-- List Container -->
		<div class="rfs-ref-category-list grid transition-[grid-template-rows] duration-500 ease-out <?php echo esc_attr( $grid_state_class ); ?>">
			<div class="overflow-hidden">
				<div class="flex flex-col py-2">
					<?php if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) : ?>
						<?php foreach ( $product_categories as $category ) :
							$category_link     = get_term_link( $category );
							// Ensure ACF plugin is active or use fallback
							$short_description = function_exists('get_field') ? get_field( 'category_nav_short_description', $category ) : '';
							?>
							<a href="<?php echo esc_url( $category_link ); ?>" class="rfs-ref-category-item group px-6 py-3 hover:bg-white/10 cursor-pointer transition-colors duration-200 border-l-4 border-transparent hover:border-[#fbbf24]">
								<h3 class="text-[13px] font-bold uppercase tracking-wider text-white mb-0.5 group-hover:text-[#fbbf24] transition-colors">
									<?php echo esc_html( $category->name ); ?>
								</h3>
								<?php if ( $short_description ) : ?>
									<p class="text-[11px] text-gray-300 font-light leading-tight opacity-80 group-hover:opacity-100">
										<?php echo esc_html( $short_description ); ?>
									</p>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'category_navigation', 'shortcode_category_navigation' );