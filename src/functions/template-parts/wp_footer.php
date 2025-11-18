<?php

/**
 * Main function to display the footer.
 * It now uses a transient to cache the footer HTML.
 */
add_action( 'wp_footer', 'avolve_footer' );

function avolve_footer() {
// Try to get the footer from cache first.
    $cached_footer = get_transient( 'avolve_footer_html' );
    if ( false !== $cached_footer ) {
        echo $cached_footer;
        return;
    }

// If not in cache, start output buffering to capture the HTML.
    ob_start();

// Check if ACF's get_field function exists.
    if ( !function_exists( 'get_field' ) ) {
        ob_end_clean(); // Clean the buffer and stop if ACF is not active.
        return;
    }

// Get all the fields from ACF options page.
    $footer_logo   = get_field( 'footer_logo', 'option' );
    $nav_columns   = get_field( 'footer_navigation_columns', 'option' );
    $social_links  = get_field( 'footer_social_links', 'option' );
    $email_address = get_field( 'footer_email', 'option' );
    $phone_numbers = get_field( 'footer_phone_numbers', 'option' );
    ?>

<?php echo avolve_round() ? '<div class="mt-[1rem]"></div>' : ''; ?>
<footer class="bg-blue text-white p-8 md:p-16 md:pb-8 <?php echo avolve_round() ? 'rounded-lg mb-6 mt-5' : ''; ?> overflow-hidden">
<div class="container">
<?php if ( $footer_logo && !empty( $footer_logo['url'] ) ): ?>
<div class="lg:hidden text-center mb-4">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Home">
<img src="<?php echo wpimage( $footer_logo['id'], 100, false ) ?>" srcset="<?php echo wpimage( $footer_logo['id'], 100, false ) ?> 1x, <?php echo wpimage( $footer_logo['id'], 100, true ) ?> 2x" <?php echo esc_attr( $footer_logo['alt'] ); ?> class="h-30 inline-block">
</a>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:gap-8">

<?php if ( $nav_columns ): ?>
<div class="lg:col-span-8">

<!-- DESKTOP NAVIGATION GRID -->
<div class="hidden lg:grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
<?php foreach ( $nav_columns as $column ): ?>
<?php if ( !empty( $column['column_title'] ) ): ?>
<div>
<h2 class="font-bold text-dm uppercase tracking-wider mb-4"><?php echo esc_html( $column['column_title'] ); ?></h2>
<?php if ( !empty( $column['column_links'] ) ): ?>
<ul class="space-y-2 text-sm">
<?php foreach ( $column['column_links'] as $link_item ):
        $link = $link_item['link_item'];
        if ( $link && !empty( $link['url'] ) && !empty( $link['title'] ) ): ?>
																						<li><a href="<?php echo esc_url( $link['url'] ); ?>" target="<?php echo esc_attr( $link['target'] ? $link['target'] : '_self' ); ?>" class="hover:underline"><?php echo esc_html( $link['title'] ); ?></a></li>
																					<?php endif;
    endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php endif; ?>
<?php endforeach; ?>
</div>

<div data-accordion="collapse" class="lg:hidden">
<?php $accordion_index = 1;
    foreach ( $nav_columns as $column ):
        if ( !empty( $column['column_title'] ) ): ?>
															<h2 id="accordion-heading-mob-<?php echo $accordion_index; ?>">
																	<button type="button" class="flex items-center justify-between w-full p-3 font-medium text-left rtl:text-right border-b border-gray-200/10" data-accordion-target="#accordion-body-mob-<?php echo $accordion_index; ?>" aria-expanded="false" aria-controls="accordion-body-mob-<?php echo $accordion_index; ?>">
																		<span><?php echo esc_html( $column['column_title'] ); ?></span>
																		<svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/></svg>
																	</button>
															</h2>
															<div id="accordion-body-mob-<?php echo $accordion_index; ?>" class="hidden" aria-labelledby="accordion-heading-mob-<?php echo $accordion_index; ?>">
																	<?php if ( !empty( $column['column_links'] ) ): ?>
																		<ul class="py-3 px-3 space-y-3 border-b border-gray-700">
																			<?php foreach ( $column['column_links'] as $link_item ):
            $link = $link_item['link_item'];
            if ( $link && !empty( $link['url'] ) && !empty( $link['title'] ) ): ?>
																																																														<li><a href="<?php echo esc_url( $link['url'] ); ?>" target="<?php echo esc_attr( $link['target'] ? $link['target'] : '_self' ); ?>" class="hover:underline"><?php echo esc_html( $link['title'] ); ?></a></li>
																																																													<?php endif;
    endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php $accordion_index++;
    endif;
    endforeach; ?>
</div>
</div>
<?php endif; ?>

<div class="lg:col-span-4 lg:border-l lg:border-gray-200/10 lg:pl-12">
<div class="flex flex-col space-y-8 items-center text-center lg:items-start lg:text-left">
<?php if ( $footer_logo && !empty( $footer_logo['url'] ) ): ?>
<div class="hidden lg:block w-full text-center">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Home" class="inline-block">
<img src="<?php echo wpimage( $footer_logo['id'], 150, false ) ?>" srcset="<?php echo wpimage( $footer_logo['id'], 150, false ) ?> 1x, <?php echo wpimage( $footer_logo['id'], 150, true ) ?> 2x" alt="<?php echo esc_attr( $footer_logo['alt'] ); ?>" class="block m-a">
</a>
</div>
<?php endif; ?>
<?php if ( $social_links ): ?>
<div>
<h3 class="font-semibold mb-5">Follow Us</h3>
<div class="flex justify-center lg:justify-start space-x-4 items-center">
<?php foreach ( $social_links as $social ):
        $icon = $social['social_icon_image'];
        if ( !empty( $social['social_url'] ) && !empty( $icon ) ): ?>
																		<a href="<?php echo esc_url( $social['social_url'] ); ?>" aria-label="<?php echo esc_attr( $social['social_aria_label'] ); ?>" target="_blank" rel="noopener noreferrer">
																			<?php $icon_size = 80; ?>
																			<img src="<?php echo esc_url( wpimage( $icon['id'], $icon_size, false ) ) ?>" srcset="<?php echo esc_url( wpimage( $icon['id'], $icon_size, false ) ) ?> 1x, <?php echo esc_url( wpimage( $icon['id'], $icon_size, true ) ) ?> 2x" <?php echo esc_attr( $footer_logo['alt'] ); ?> class="h-<?php echo $icon_size / 10; ?> w-<?php echo $icon_size / 10; ?> object-contain">
																		</a>
																	<?php endif;
    endforeach; ?>
</div>
</div>
<?php endif; ?>

<?php if ( $email_address ): ?>
<div>
<h3 class="font-semibold mb-3">Email us at:</h3>
<div class="flex items-center space-x-3">
<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
<a href="mailto:<?php echo esc_attr( $email_address ); ?>" class="hover:underline"><?php echo esc_html( $email_address ); ?></a>
</div>
</div>
<?php endif; ?>

<?php if ( $phone_numbers ): ?>
<?php foreach ( $phone_numbers as $phone ):
        if ( !empty( $phone['phone_label'] ) && !empty( $phone['phone_number'] ) ): ?>
															<div>
																	<h3 class="font-semibold mb-3"><?php echo esc_html( $phone['phone_label'] ); ?></h3>
																	<div class="flex items-center space-x-3">
																		<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
																		<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone['phone_number'] ) ); ?>" class="hover:underline"><?php echo esc_html( $phone['phone_number'] ); ?></a>
																	</div>
															</div>
														<?php endif;
    endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>
<div class="pt-8 mt-8 text-[#9ea2aa] border-t border-gray-200/10 text-center text-xs lg:text-left">Copyright <?php echo date( 'Y' ) ?> | Avolve Software © | Registered address: 21001 N Tatum Blvd, STE 1630-503, Phoenix, AZ 85050, USA</div>
</div>
</footer>
<?php
// Get the captured HTML from the buffer.
    $footer_html = ob_get_clean();

// Save the generated HTML into a transient with no expiration.
// The '0' means it will not expire until the cache is cleared or it's manually deleted.
    set_transient( 'avolve_footer_html', $footer_html, 0 );

// Display the newly generated footer.
    echo $footer_html;
}

/**
 * Deletes the footer transient when the ACF options page is saved.
 * This ensures the footer cache is cleared and will be regenerated on the next page load.
 */
add_action( 'acf/save_post', 'avolve_delete_footer_transient_on_save', 20 );

function avolve_delete_footer_transient_on_save() {
    skyline_delete_acf_options_transient( transient: 'avolve_footer_html', page: 'footer-settings' );
    skyline_delete_acf_options_transient( transient: 'avolve_footer_html', page: 'general-settings' );
}