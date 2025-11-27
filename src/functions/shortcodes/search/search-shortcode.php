<?php
/**
 * ATS Diamond Tools - AJAX Search Shortcode
 *
 * @package ATS
 * @since 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the search shortcode
 */
add_shortcode( 'ats_search', 'ats_render_search_shortcode' );

/**
 * Render the search shortcode
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output
 */
function ats_render_search_shortcode( $atts = array() ) {
    // Parse attributes
    $atts = shortcode_atts( array(
        'context' => 'desktop' // 'desktop' or 'mobile'
    ), $atts, 'ats_search' );

    $context = sanitize_key( $atts['context'] );
    $prefix  = 'ats-search-' . $context;

    // Get product categories
    $categories = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0
    ) );

    ob_start();
    ?>
	<div class="ats-search-container rfs-ref-search-container relative" data-ats-search data-search-context="<?php echo esc_attr( $context ); ?>">
		<!-- Search Bar -->
		<div class="rfs-ref-search-bar flex items-center border border-neutral-500 rounded-[3px] bg-white h-8 w-full lg:w-[510px]">
			<!-- Category Dropdown -->
			<div class="relative">
				<button
					id="<?php echo esc_attr( $prefix ); ?>-category-btn"
					data-dropdown-toggle="<?php echo esc_attr( $prefix ); ?>-category-dropdown"
					class="js-search-category-btn flex items-center justify-between px-3 h-full min-w-[140px] text-ats-text text-xs font-bold focus:outline-none"
					type="button"
				>
					<span class="js-selected-category-text"><?php esc_html_e( 'All Categories', 'ats' ); ?></span>
					<svg class="w-3 h-3 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
					</svg>
				</button>
				<input type="hidden" class="js-selected-category" value="">

				<!-- Dropdown Menu -->
				<div
					id="<?php echo esc_attr( $prefix ); ?>-category-dropdown"
					class="js-search-category-dropdown absolute top-full left-0 z-50 hidden bg-white border border-neutral-200 rounded shadow-lg w-44"
				>
					<ul class="p-2 text-sm text-ats-text font-medium">
						<li>
							<a
								href="#"
								class="inline-flex items-center w-full p-2 hover:bg-ats-gray hover:text-ats-dark rounded"
								data-category-id=""
								data-category-name="<?php esc_attr_e( 'All Categories', 'ats' ); ?>"
							>
								<?php esc_html_e( 'All Categories', 'ats' ); ?>
							</a>
						</li>
						<?php if ( !empty( $categories ) && !is_wp_error( $categories ) ): ?>
							<?php foreach ( $categories as $category ): ?>
								<li>
									<a
										href="#"
										class="inline-flex items-center w-full p-2 hover:bg-ats-gray hover:text-ats-dark rounded"
										data-category-id="<?php echo esc_attr( $category->term_id ); ?>"
										data-category-name="<?php echo esc_attr( $category->name ); ?>"
									>
										<?php echo esc_html( $category->name ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</div>
			</div>

			<!-- Divider -->
			<div class="w-px h-full bg-neutral-200" style="width: 1.5px;"></div>

			<!-- Search Input -->
			<div class="flex-1 flex items-center px-3">
				<input
					type="text"
					class="js-search-input w-full border-0 focus:ring-0 text-ats-text text-xs font-light font-['Inter'] placeholder:text-ats-text placeholder:opacity-70 bg-transparent"
					placeholder="<?php esc_attr_e( 'Search over 1000 products', 'ats' ); ?>"
					autocomplete="off"
				>
			</div>

			<!-- Search Icon -->
			<button
				type="button"
				class="js-search-submit px-3 h-full flex items-center justify-center text-ats-text hover:text-ats-dark"
				aria-label="<?php esc_attr_e( 'Search', 'ats' ); ?>"
			>
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<circle cx="11" cy="11" r="8"/>
					<path d="m21 21-4.3-4.3"/>
				</svg>
			</button>
		</div>

		<!-- Search Results Container -->
		<div
			class="js-search-results rfs-ref-search-results absolute left-0 top-full mt-2 bg-white border border-ats-gray rounded shadow-lg hidden z-50 max-h-[400px] overflow-y-auto w-full lg:w-[510px]"
		>
			<div class="js-search-results-inner">
				<!-- Results will be injected here -->
			</div>
			<div class="js-search-loading hidden p-4 text-center">
				<div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-ats-text"></div>
				<p class="mt-2 text-sm text-ats-text"><?php esc_html_e( 'Searching...', 'ats' ); ?></p>
			</div>
			<div class="js-search-no-results hidden p-4 text-center text-ats-text">
				<?php esc_html_e( 'No products found', 'ats' ); ?>
			</div>
			<div class="js-search-sentinel h-1"></div>
		</div>
	</div>
	<?php
return ob_get_clean();
}
