<?php
/**
 * ATS Diamond Tools - AJAX Search Shortcode
 *
 * @package ATS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the search shortcode
 */
add_shortcode( 'ats_search', 'ats_render_search_shortcode' );

/**
 * Render the search shortcode
 *
 * @return string HTML output
 */
function ats_render_search_shortcode() {
	// Get product categories
	$categories = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
	) );

	ob_start();
	?>
	<div class="ats-search-container rfs-ref-search-container relative" data-ats-search>
		<!-- Desktop Search Bar -->
		<div class="rfs-ref-search-desktop hidden md:flex items-center border border-neutral-200 rounded-[3px] bg-white h-8 w-[510px]" style="border-width: 1.5px;">
			<!-- Category Dropdown -->
			<div class="relative">
				<button
					id="ats-search-category-btn"
					data-dropdown-toggle="ats-search-category-dropdown"
					class="flex items-center justify-between px-3 h-full min-w-[140px] text-neutral-700 text-xs font-bold font-['Inter'] focus:outline-none"
					type="button"
				>
					<span id="ats-selected-category-text"><?php esc_html_e( 'All Categories', 'ats' ); ?></span>
					<svg class="w-3 h-3 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
					</svg>
				</button>
				<input type="hidden" id="ats-selected-category" value="">

				<!-- Dropdown Menu -->
				<div
					id="ats-search-category-dropdown"
					class="z-50 hidden bg-white border border-neutral-200 rounded shadow-lg w-44"
				>
					<ul class="p-2 text-sm text-neutral-700 font-medium" aria-labelledby="ats-search-category-btn">
						<li>
							<a
								href="#"
								class="inline-flex items-center w-full p-2 hover:bg-neutral-100 hover:text-neutral-900 rounded"
								data-category-id=""
								data-category-name="<?php esc_attr_e( 'All Categories', 'ats' ); ?>"
							>
								<?php esc_html_e( 'All Categories', 'ats' ); ?>
							</a>
						</li>
						<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
							<?php foreach ( $categories as $category ) : ?>
								<li>
									<a
										href="#"
										class="inline-flex items-center w-full p-2 hover:bg-neutral-100 hover:text-neutral-900 rounded"
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
					id="ats-search-input"
					class="w-full border-0 focus:ring-0 text-neutral-500 text-xs font-light font-['Inter'] placeholder:text-neutral-500 bg-transparent"
					placeholder="<?php esc_attr_e( 'Search over 1000 products', 'ats' ); ?>"
					autocomplete="off"
				>
			</div>

			<!-- Search Icon -->
			<button
				type="button"
				id="ats-search-submit"
				class="px-3 h-full flex items-center justify-center text-neutral-700 hover:text-neutral-900"
				aria-label="<?php esc_attr_e( 'Search', 'ats' ); ?>"
			>
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<circle cx="11" cy="11" r="8"/>
					<path d="m21 21-4.3-4.3"/>
				</svg>
			</button>
		</div>

		<!-- Mobile Search Icon -->
		<button
			type="button"
			id="ats-mobile-search-trigger"
			class="md:hidden flex items-center justify-center p-2 text-neutral-700 hover:text-neutral-900"
			aria-label="<?php esc_attr_e( 'Open search', 'ats' ); ?>"
		>
			<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<circle cx="11" cy="11" r="8"/>
				<path d="m21 21-4.3-4.3"/>
			</svg>
		</button>

		<!-- Search Results Container -->
		<div
			id="ats-search-results"
			class="rfs-ref-search-results absolute left-0 top-full mt-2 bg-white border border-neutral-200 rounded shadow-lg hidden z-50 max-h-[400px] overflow-y-auto w-full md:w-[510px]"
		>
			<div id="ats-search-results-inner">
				<!-- Results will be injected here -->
			</div>
			<div id="ats-search-loading" class="hidden p-4 text-center">
				<div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-neutral-700"></div>
				<p class="mt-2 text-sm text-neutral-500"><?php esc_html_e( 'Searching...', 'ats' ); ?></p>
			</div>
			<div id="ats-search-no-results" class="hidden p-4 text-center text-neutral-500">
				<?php esc_html_e( 'No products found', 'ats' ); ?>
			</div>
			<div id="ats-search-sentinel" class="h-1"></div>
		</div>

		<!-- Mobile Search Modal -->
		<div
			id="ats-mobile-search-modal"
			class="rfs-ref-search-mobile-modal fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden"
		>
			<div class="bg-white w-full max-w-lg mx-auto mt-10 rounded-lg shadow-xl">
				<!-- Modal Header -->
				<div class="flex items-center justify-between p-4 border-b border-neutral-200">
					<h3 class="text-lg font-semibold text-neutral-900"><?php esc_html_e( 'Search Products', 'ats' ); ?></h3>
					<button
						type="button"
						id="ats-mobile-search-close"
						class="text-neutral-500 hover:text-neutral-700"
						aria-label="<?php esc_attr_e( 'Close', 'ats' ); ?>"
					>
						<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
						</svg>
					</button>
				</div>

				<!-- Modal Body -->
				<div class="p-4">
					<!-- Category Dropdown (Mobile) -->
					<div class="mb-4">
						<label class="block text-xs font-bold text-neutral-700 mb-2"><?php esc_html_e( 'Category', 'ats' ); ?></label>
						<select
							id="ats-mobile-category-select"
							class="w-full border border-neutral-200 rounded px-3 py-2 text-sm"
						>
							<option value=""><?php esc_html_e( 'All Categories', 'ats' ); ?></option>
							<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->term_id ); ?>">
										<?php echo esc_html( $category->name ); ?>
									</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</div>

					<!-- Search Input (Mobile) -->
					<div class="relative">
						<input
							type="text"
							id="ats-mobile-search-input"
							class="w-full border border-neutral-200 rounded px-3 py-2 pr-10 text-sm"
							placeholder="<?php esc_attr_e( 'Search over 1000 products', 'ats' ); ?>"
							autocomplete="off"
						>
						<svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<circle cx="11" cy="11" r="8"/>
							<path d="m21 21-4.3-4.3"/>
						</svg>
					</div>
				</div>

				<!-- Modal Results -->
				<div
					id="ats-mobile-search-results"
					class="border-t border-neutral-200 max-h-[50vh] overflow-y-auto"
				>
					<div id="ats-mobile-search-results-inner" class="p-4">
						<!-- Results will be injected here -->
					</div>
					<div id="ats-mobile-search-loading" class="hidden p-4 text-center">
						<div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-neutral-700"></div>
						<p class="mt-2 text-sm text-neutral-500"><?php esc_html_e( 'Searching...', 'ats' ); ?></p>
					</div>
					<div id="ats-mobile-search-no-results" class="hidden p-4 text-center text-neutral-500">
						<?php esc_html_e( 'No products found', 'ats' ); ?>
					</div>
					<div id="ats-mobile-search-sentinel" class="h-1"></div>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
