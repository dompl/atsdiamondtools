<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package SkylineWP-Dev-Child
 */

get_header();
?>

<div class="rfs-ref-404-page bg-white min-h-screen">
	<div class="rfs-ref-404-container container mx-auto px-4 py-16">

		<!-- 404 Content -->
		<div class="rfs-ref-404-content max-w-3xl mx-auto text-center">

			<!-- Large 404 Number -->
			<div class="rfs-ref-404-number mb-8">
				<h1 class="text-9xl md:text-[12rem] font-bold text-primary-600 leading-none">404</h1>
			</div>

			<!-- Error Message -->
			<div class="rfs-ref-404-message mb-8">
				<h2 class="text-3xl md:text-4xl font-bold text-ats-dark mb-4">
					<?php esc_html_e( 'Oops! Page Not Found', 'skylinewp-dev-child' ); ?>
				</h2>
				<p class="text-lg text-gray-600 leading-relaxed mb-8">
					<?php esc_html_e( 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'skylinewp-dev-child' ); ?>
				</p>

				<!-- Shop CTA Button -->
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
					   class="inline-flex items-center gap-3 bg-primary-600 text-white hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-600 font-bold rounded-lg text-lg px-8 py-4 transition-colors duration-200">
						<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
						</svg>
						<?php esc_html_e( 'Browse Our Shop', 'skylinewp-dev-child' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<!-- Decorative Icon -->
			<div class="rfs-ref-404-icon mt-12 opacity-20">
				<svg class="w-32 h-32 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg>
			</div>

		</div>

	</div>
</div>

<?php
get_footer();