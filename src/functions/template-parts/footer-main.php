<?php
/**
 * Footer Main Template
 *
 * @package SkylineWP Dev Child
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Get ACF fields with 'option' parameter
$footer_logo            = get_field( 'ats_footer_logo', 'option' );
$footer_description     = get_field( 'ats_footer_description', 'option' );
$footer_telephone       = get_field( 'ats_footer_telephone', 'option' );
$footer_email           = get_field( 'ats_footer_email', 'option' );
$links_title_1          = get_field( 'ats_footer_links_title_1', 'option' );
$useful_links           = get_field( 'ats_footer_useful_links', 'option' );
$links_title_2          = get_field( 'ats_footer_links_title_2', 'option' );
$category_links         = get_field( 'ats_footer_category_links', 'option' );
$newsletter_title       = get_field( 'ats_footer_newsletter_title', 'option' );
$newsletter_description = get_field( 'ats_footer_newsletter_description', 'option' );
$newsletter_button      = get_field( 'ats_footer_newsletter_button', 'option' );
$newsletter_disclaimer  = get_field( 'ats_footer_newsletter_disclaimer', 'option' );
$copyright_text         = get_field( 'ats_footer_copyright', 'option' );
$company_reg            = get_field( 'ats_footer_company_reg', 'option' );
$vat_number             = get_field( 'ats_footer_vat_number', 'option' );

// Process copyright text - replace %year% variable with current year
$current_year   = date( 'Y' );
$copyright_text = str_replace( '%year%', $current_year, $copyright_text );

// Process newsletter disclaimer - replace %privacy_policy% variable with link
$privacy_policy_url    = get_privacy_policy_url();
$privacy_policy_link   = '<a href="' . esc_url( $privacy_policy_url ) . '" class="text-primary-800 hover:underline" target="_blank">' . esc_html__( 'Privacy Policy', 'skylinewp-dev-child' ) . '</a>';
$newsletter_disclaimer = str_replace( '%privacy_policy%', $privacy_policy_link, $newsletter_disclaimer );
?>

<footer class="rfs-ref-footer bg-white">
    <!-- Main Footer Section -->
    <div class="rfs-ref-footer-main pt-16 pb-8">
        <div class="rfs-ref-footer-container container px-4">
            <div class="rfs-ref-footer-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8">
                <!-- Column 1: Company Info -->
                <div class="rfs-ref-footer-col-info lg:col-span-4 flex flex-col justify-between">
                    <div class="rfs-ref-footer-top-content space-y-4">
                        <?php if ( $footer_logo && isset( $footer_logo['url'] ) ): ?>
                            <div class="rfs-ref-footer-logo mb-4">
                                <img src="<?php echo esc_url( $footer_logo['url'] ); ?>"
                                     alt="<?php echo esc_attr( $footer_logo['alt'] ?? get_bloginfo( 'name' ) ); ?>"
                                     class="h-auto max-w-full" />
                            </div>
                        <?php endif; ?>

                        <?php if ( $footer_description ): ?>
                            <p class="rfs-ref-footer-description text-gray-600 text-sm leading-relaxed">
                                <?php echo esc_html( $footer_description ); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="rfs-ref-footer-contact-info space-y-4 mt-4">
                        <?php if ( $footer_telephone ): ?>
                            <div class="rfs-ref-footer-phone flex items-center space-x-2">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $footer_telephone ) ); ?>"
                                   class="text-gray-600 hover:text-primary-800 text-base font-bold">
                                    <?php echo esc_html( $footer_telephone ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ( $footer_email ): ?>
                            <div class="rfs-ref-footer-email flex items-center space-x-2">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <a href="mailto:<?php echo esc_attr( $footer_email ); ?>"
                                   class="text-gray-600 hover:text-primary-800 text-base font-bold">
                                    <?php echo esc_html( $footer_email ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 2: Useful Links -->
                <div class="rfs-ref-footer-col-links lg:col-span-2">
                    <?php if ( $links_title_1 ): ?>
                        <h3 class="rfs-ref-footer-links-title text-sm font-bold text-gray-900 uppercase mb-6">
                            <?php echo esc_html( $links_title_1 ); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ( $useful_links && is_array( $useful_links ) ): ?>
                        <ul class="rfs-ref-footer-links-list space-y-3">
                            <?php foreach ( $useful_links as $link_item ): ?>
                                <?php
$link = $link_item['ats_footer_link'] ?? null;
if ( $link && isset( $link['url'], $link['title'] ) ):
?>
                                    <li>
                                        <a href="<?php echo esc_url( $link['url'] ); ?>"
                                           class="text-gray-600 hover:text-primary-800 text-sm"
                                           <?php echo( isset( $link['target'] ) && $link['target'] === '_blank' ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                            <?php echo esc_html( $link['title'] ); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Column 3: Product Categories -->
                <div class="rfs-ref-footer-col-categories lg:col-span-2">
                    <?php if ( $links_title_2 ): ?>
                        <h3 class="rfs-ref-footer-categories-title text-sm font-bold text-gray-900 uppercase mb-6">
                            <?php echo esc_html( $links_title_2 ); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ( $category_links && is_array( $category_links ) ): ?>
                        <div class="rfs-ref-footer-categories-grid grid grid-cols-2 gap-x-4 gap-y-3">
                            <?php foreach ( $category_links as $link_item ): ?>
                                <?php
$link = $link_item['ats_footer_category_link'] ?? null;
if ( $link && isset( $link['url'], $link['title'] ) ):
?>
                                    <a href="<?php echo esc_url( $link['url'] ); ?>"
                                       class="text-gray-600 hover:text-primary-800 text-sm"
                                       <?php echo( isset( $link['target'] ) && $link['target'] === '_blank' ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                        <?php echo esc_html( $link['title'] ); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Column 4: Newsletter -->
                <div class="rfs-ref-footer-col-newsletter lg:col-span-4">
                    <?php if ( $newsletter_title ): ?>
                        <h3 class="rfs-ref-footer-newsletter-title text-sm font-bold text-gray-900 uppercase mb-4">
                            <?php echo esc_html( $newsletter_title ); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ( $newsletter_description ): ?>
                        <p class="rfs-ref-footer-newsletter-description text-gray-600 text-sm mb-4">
                            <?php echo esc_html( $newsletter_description ); ?>
                        </p>
                    <?php endif; ?>

                    <form class="rfs-ref-newsletter-form space-y-3" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ats_newsletter_subscribe' ) ); ?>">
                        <div class="rfs-ref-footer-form-group flex flex-col sm:flex-row gap-2">
                            <input type="email"
                                   name="newsletter_email"
                                   placeholder="<?php esc_attr_e( 'Enter your email', 'skylinewp-dev-child' ); ?>"
                                   required
                                   class="rfs-ref-newsletter-email flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5" />

                            <button type="submit"
                                    class="rfs-ref-newsletter-submit bg-primary-800 text-white hover:bg-primary-900 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center whitespace-nowrap">
                                <?php echo esc_html( $newsletter_button ?: __( 'SUBSCRIBE', 'skylinewp-dev-child' ) ); ?>
                            </button>
                        </div>

                        <div class="rfs-ref-newsletter-message" style="display: none;"></div>
                    </form>

                    <?php if ( $newsletter_disclaimer ): ?>
                        <p class="rfs-ref-footer-disclaimer text-gray-500 text-xs mt-3 leading-relaxed">
                            <?php echo wp_kses_post( $newsletter_disclaimer ); ?>
                        </p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="rfs-ref-footer-bottom pb-4 ">
        <div class="rfs-ref-footer-bottom-container max-w-screen-2xl mx-auto px-4">
			<div class="border-t border-primary-300 pt-8">
            <div class="rfs-ref-footer-bottom-content flex flex-col md:flex-row md:items-center md:justify-between space-y-2 md:space-y-0">
                <?php if ( $copyright_text ): ?>
                    <p class="rfs-ref-footer-copyright text-gray-600 text-xs">
                        <?php echo esc_html( $copyright_text ); ?>
                    </p>
                <?php endif; ?>

                <div class="rfs-ref-footer-legal flex flex-col sm:flex-row sm:space-x-4 space-y-1 sm:space-y-0">
                    <?php if ( $company_reg ): ?>
                        <span class="rfs-ref-footer-company-reg text-gray-600 text-xs">
                            <?php echo esc_html( $company_reg ); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ( $vat_number ): ?>
                        <span class="rfs-ref-footer-vat text-gray-600 text-xs">
                            <?php echo esc_html( $vat_number ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>
    </div>
</footer>
