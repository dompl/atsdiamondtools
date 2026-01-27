<?php
/**
 * Newsletter Widget for Sidebar
 *
 * Reusable newsletter subscription widget
 * Uses existing newsletter Ajax handler and JS component
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render newsletter widget HTML
 *
 * @return string Newsletter widget HTML.
 */
function ats_render_newsletter_widget() {
	// Get newsletter nonce.
	$newsletter_nonce = wp_create_nonce( 'ats_newsletter_subscribe' );

	ob_start();
	?>
	<div class="rfs-ref-newsletter-widget bg-ats-gray p-6 rounded-lg border border-primary-300">
		<h3 class="rfs-ref-newsletter-widget-title text-lg font-bold text-ats-dark mb-3">
			<?php esc_html_e( 'Stay Updated', 'skylinewp-dev-child' ); ?>
		</h3>

		<p class="rfs-ref-newsletter-widget-description text-sm text-gray-600 mb-4 leading-relaxed">
			<?php esc_html_e( 'Subscribe to get special offers, free giveaways, and updates.', 'skylinewp-dev-child' ); ?>
		</p>

		<form class="rfs-ref-newsletter-form" data-nonce="<?php echo esc_attr( $newsletter_nonce ); ?>">
			<div class="rfs-ref-newsletter-form-group space-y-3">
				<input type="email"
				       name="newsletter_email"
				       placeholder="<?php esc_attr_e( 'Enter your email', 'skylinewp-dev-child' ); ?>"
				       required
				       class="rfs-ref-newsletter-email w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary-600 focus:border-primary-600 block p-3" />

				<button type="submit"
				        class="rfs-ref-newsletter-submit w-full bg-primary-600 text-white hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-600 font-bold rounded-lg text-sm px-5 py-3 text-center transition-colors duration-200">
					<?php esc_html_e( 'Subscribe', 'skylinewp-dev-child' ); ?>
				</button>
			</div>

			<div class="rfs-ref-newsletter-message" style="display: none;"></div>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
