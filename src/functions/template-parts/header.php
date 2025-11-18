<?php

/**
 * Main action to output the entire header container.
 * Hooks into 'skyline_after_body'.
 */
add_action( 'skyline_after_body', 'avolve_header_container' );
function avolve_header_container() {
    ?>
    <header class="site-header" role="banner">

        <!-- Mobile Header (Visible on small screens) -->
        <div class="block lg:hidden">
            <?php do_action( 'avolve_mobile_header' ); ?>
        </div>

        <!-- Desktop Header (Hidden on small screens) -->
        <div class="header-main justify-between items-center py-6 class-243598 hidden lg:flex">

            <!-- COLUMN 1: LOGO (on the left) -->
            <div class="flex-shrink-0 mt-auto">
                <?php do_action( 'avolve_header_logo' ); ?>
            </div>

            <!-- COLUMN 2: RIGHT-SIDE CONTENT (Wrapper for both rows) -->
            <div class="flex flex-col items-stretch flex-grow ml-16">

                <!-- ROW 1: Top Bar Content -->
                <div class="header-top flex justify-end items-center pb-5 space-x-4 w-full">
                    <div class="hidden md:flex items-center space-x-4 xl:space-x-6 mr-auto xl:mr-0">
                        <?php do_action( 'avolve_header_certifications' ); ?>
                    </div>
                    <div class="text-sm text-gray-600 hidden md:block pl-2.5">
                        <?php do_action( 'avolve_header_language_selector' ); ?>
                    </div>
                    <div class="block 2xl:hidden">
                        <?php do_action( 'avolve_header_search' ); ?>
                    </div>
                    <div class="block xl:hidden">
                        <?php do_action( 'avolve_header_support_button' ); ?>
                    </div>
                </div>

                <!-- ROW 2: Main Navigation and Utilities -->
                <div class="flex justify-between items-center space-x-6">

                    <!-- Navigation (Aligned Left) -->
                    <div class="hidden md:block">
                        <?php do_action( 'avolve_header_navigation' ); ?>
                    </div>

                    <!-- Utility Wrapper (Aligned Right) -->
                    <div class="hidden md:flex items-center space-x-6">
                        <div class="hidden 2xl:block">
                            <?php do_action( 'avolve_header_search' ); ?>
                        </div>
                        <div class="hidden xl:block">
                            <?php do_action( 'avolve_header_support_button' ); ?>
                        </div>
                    </div>
                </div><!-- End Column 2 -->
            </div>
        </div>
    </header>
    <?php
}

/**
 * Renders the mobile header layout.
 */
add_action( 'avolve_mobile_header', 'avolve_mobile_header_html' );
function avolve_mobile_header_html() {
    $logo = get_field( 'header_logo', 'option' ); ?>
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-20">

            <!-- Left: Mobile Menu Toggle -->
            <div class="w-1/3">
                <button type="button" class="mobile-menu-toggle p-2 rounded-md text-secondary" aria-label="<?php esc_attr_e( 'Open menu', 'avolve' ); ?>" data-drawer-target="drawer-navigation" data-drawer-show="drawer-navigation" aria-controls="drawer-navigation">
                    <svg class="w-[48px] h-[48px] text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-width="1.8" d="M5 7h14M5 12h14M5 17h14" />
                    </svg>
                </button>
            </div>

            <!-- Center: Logo -->
            <div class="w-1/3 flex justify-center">
                <?php if ( $logo && is_array( $logo ) ):
        $logo_alt = !empty( $logo['alt'] ) ? $logo['alt'] : get_bloginfo( 'name' ); ?>
																																																																																																		                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="block">
																																																																																																		                        <img src="<?php echo esc_url( wpimage( image: $logo['id'], size: 120 ) ); ?>"
																																																																																																		                             srcset="<?php echo esc_url( wpimage( image: $logo['id'], size: 120 ) ); ?> 1x, <?php echo esc_url( wpimage( image: $logo['id'], size: 120, retina: true ) ); ?> 2x, <?php echo esc_url( wpimage( image: $logo['id'], size: 120 * 3 ) ); ?> 3x"
																																																																																																		                             alt="<?php echo esc_attr( $logo_alt ); ?>"
																																																																																																		                             class="w-auto">
																																																																																																		                    </a>
																																																																																																		                <?php else: // Fallback if no logo is set ?>
																																																																																																		                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-2xl font-bold text-secondary">Avolve</a>
																																																																																																		                <?php endif; ?>
            </div>

            <!-- Right: Search Toggle -->
            <div class="w-1/3 flex justify-end">
                <?php
$search_on_page = get_field( 'search_results_on_page', 'option' );
    if ( $search_on_page ):
        // Page mode: Link to search page
        ?>
							                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>?s=" class="p-2 rounded-full text-secondary" aria-label="<?php esc_attr_e( 'Search', 'avolve' ); ?>">
							                        <svg class="w-[38px] h-[38px] text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
							                            <path stroke="currentColor" stroke-linecap="round" stroke-width="1.3" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
							                        </svg>
							                    </a>
							                <?php else: ?>
                    <button type="button" class="mobile-search-toggle p-2 rounded-full text-secondary" aria-label="<?php esc_attr_e( 'Open search', 'avolve' ); ?>" data-modal-target="skyline-search-modal" data-modal-toggle="skyline-search-modal">
                        <svg class="w-[38px] h-[38px] text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="1.3" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                    </button>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <?php
}

/**
 * Renders the Customer Support button from ACF Options.
 *
 * @param bool $full Whether the button should be full-width.
 */
add_action( 'avolve_header_support_button', 'avolve_header_support_button_html' );
function avolve_header_support_button_html( $full = false ) {
    $support_button = get_field( 'header_support_button', 'option' );
    if ( $support_button && !empty( $support_button['url'] ) ): ?>
        <a href="<?php echo esc_url( $support_button['url'] ); ?>"
           target="<?php echo esc_attr( $support_button['target'] ?: '_self' ); ?>"
           class="button-small-blue uppercase <?php echo $full ? 'w-full block' : ''; ?>">
             <?php echo esc_html( $support_button['title'] ); ?>
        </a>
    <?php endif;
}

/**
 * Renders the language selector dropdown.
 *
 * @param string $id   The unique ID for the dropdown toggle.
 * @param bool   $full Whether the button should be full-width.
 */
add_action( 'avolve_header_language_selector', 'avolve_header_language_selector_html' );
function avolve_header_language_selector_html( $id = 'dropdown', $full = false ) {
    $id = wp_unique_id( 'dropdownDefaultButton' );
    ?>
                                                                          <button id="dropdownDefaultButton<?php echo $id ?>" data-dropdown-toggle="<?php echo esc_attr( $id ); ?>" class="border border-gray-300 rounded-full text-sm px-[26px] py-[7px] text-center inline-flex items-center focus:border-blue text-[12px] <?php echo $full ? 'w-full justify-between' : ''; ?>" type="button">
        <span><?php esc_html_e( 'Select Language', 'avolve' ); ?></span> <svg class="w-2.5 h-2.5 ms-3 pt-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/></svg>
    </button>
    <div id="<?php echo esc_attr( $id ); ?>" class="z-10 hidden bg-blue divide-y divide-gray-100 rounded-lg shadow-sm w-44">
        <ul class="py-2 text-sm text-gray-200 w-full" aria-labelledby="dropdownDefaultButton<?php echo $id ?>">
			<?php if ( defined( 'AVOLVE_LANGUAGE' ) && AVOLVE_LANGUAGE === 'nl' ): ?>
				<li><a href="https://avolvesoftware.com/" class="avolve-dropdown-item text-[#f9f9f9]"><?php esc_html_e( 'English', 'avolve' ); ?></a></li>
				<?php else: ?>
					<li><a href="https://nl.avolvesoftware.com/" class="avolve-dropdown-item text-[#f9f9f9]"><?php esc_html_e( 'Netherland', 'avolve' ); ?></a></li>
				<?php endif; ?>
        </ul>
    </div>
    <?php
}

/**
 * Renders the site logo from ACF Options.
 */
add_action( 'avolve_header_logo', 'avolve_header_logo_html' );
function avolve_header_logo_html() {
    $logo = get_field( 'header_logo', 'option' );
    if ( $logo && is_array( $logo ) ) {
        $logo_alt = !empty( $logo['alt'] ) ? $logo['alt'] : get_bloginfo( 'name' );
        echo '<a href="' . esc_url( home_url( '/' ) ) . '" rel="home">';
        echo '<picture>';
        // Source for screens smaller than 1024px
        echo '<source media="(max-width: 1023px)" srcset="' . esc_url( wpimage( image: $logo['id'], size: 50, reversed: true ) ) . ' 1x, ' . esc_url( wpimage( image: $logo['id'], size: 50, reversed: true, retina: true ) ) . ' 2x">';
        // Source for screens 1024px and larger (and the default)
        echo '<source media="(min-width: 1024px)" srcset="' . esc_url( wpimage( image: $logo['id'], size: 60, reversed: true ) ) . ' 1x, ' . esc_url( wpimage( image: $logo['id'], size: 60, reversed: true, retina: true ) ) . ' 2x">';
        // Fallback img element
        echo '<img src="' . esc_url( wpimage( image: $logo['id'], size: 70, reversed: true ) ) . '"
               alt="' . esc_attr( $logo_alt ) . '">';
        echo '</picture>';
        echo '</a>';
    }
}

/**
 * Renders the certification badges from an ACF Repeater field.
 */
add_action( 'avolve_header_certifications', 'avolve_header_certifications_html' );
function avolve_header_certifications_html() {
    if ( have_rows( 'header_certifications', 'option' ) ) {
        while ( have_rows( 'header_certifications', 'option' ) ): the_row();
            $badge_image = get_sub_field( 'badge_image' );
            $badge_link  = get_sub_field( 'badge_link' );

            if ( $badge_image ) {
                $size       = 60;
                $image_alt  = !empty( $badge_image['alt'] ) ? $badge_image['alt'] : esc_attr__( 'Certification Badge', 'avolve' );
                $image_html = '<img src="' . esc_url( wpimage( $badge_image['id'], $size ) ) . '" srcset="' . esc_url( wpimage( $badge_image['id'], $size, reversed: true ) ) . ' 1x, ' . esc_url( wpimage( image: $badge_image['id'], size: $size, retina: true, reversed: true ) ) . ' 2x, ' . esc_url( wpimage( $badge_image['id'], $size * 3, reversed: true ) ) . ' 3x" alt="' . esc_attr( $image_alt ) . '" class="hover:scale-[1.5] transition-all transform-gpu  w-auto h-[40px]">';

                if ( $badge_link && !empty( $badge_link['url'] ) ) {
                    echo '<a href="' . esc_url( $badge_link['url'] ) . '" target="' . esc_attr( $badge_link['target'] ?: '_self' ) . '">' . $image_html . '</a>';
                } else {
                    echo $image_html;
                }
            }
        endwhile;
    }
}

/**
 * Renders the primary site navigation menu.
 */
add_action( 'avolve_header_navigation', 'avolve_header_navigation_html' );
function avolve_header_navigation_html() {
    ?>
    <nav>
        <div class="flex md:flex-row flex-wrap items-center justify-between">
            <div class="hidden w-full md:block md:w-auto" id="navbar-dropdown">
                <?php
wp_nav_menu( [
        'theme_location' => 'primary',
        'depth'          => 2,
        'container'      => false,
        'menu_class'     => 'flex flex-col font-normal p-0 mt-0 gray md:flex-row md:mt-0 md:text-lg md:border-0 md:bg-white space-x-5  md:space-x-9 md:rtl:space-x-reverse',
        'fallback_cb'    => '__return_false',
        'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
        'walker'         => new Flowbite_Nav_Walker_Hover()
    ] ); ?>
            </div>
        </div>
    </nav>
    <?php
}

/**
 * Renders the header search form and mobile search toggle.
 */
add_action( 'avolve_header_search', 'avolve_header_search_html' );
function avolve_header_search_html() {
    // Check if search results should be displayed on page
    $search_on_page = get_field( 'search_results_on_page', 'option' );

    if ( $search_on_page ) {
        // Page mode: Show actual search form
        ?>
        <div class="block xl:hidden">
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <button type="submit" class="block w-full md:w-auto text-white bg-blue hover:bg-bg-blue-600 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm px-2 py-2 text-center">
                    <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                </button>
            </form>
        </div>
        <div class="hidden xl:block">
            <form class="flex items-center max-w-sm" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label for="header-search-input" class="sr-only"><?php esc_html_e( 'Search', 'avolve' ); ?></label>
                <div class="relative w-full">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-2 pointer-events-none">
                        <svg class="w-4 h-4 text-blue" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                    </div>
                    <input type="search" id="header-search-input" name="s" class="bg-white border border-blue text-blue text-sm rounded-full focus:ring-blue focus:border-blue block w-full ps-8 p-2 pr-4 h-8" placeholder="<?php esc_attr_e( 'Search...', 'avolve' ); ?>" value="<?php echo get_search_query(); ?>" required />
                </div>
            </form>
        </div>
        <?php
} else {
        // Modal mode: Show modal trigger buttons
        ?>
        <div class="block xl:hidden">
            <button data-modal-target="skyline-search-modal" data-modal-toggle="skyline-search-modal" class="block w-full md:w-auto text-white bg-blue hover:bg-bg-blue-600 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm px-2 py-2 text-center" type="button">
                <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                </svg>
            </button>
        </div>
        <div class="hidden xl:block">
            <button type="button" class="bg-white relative border border-blue h-8 w-[189px] text-blue text-sm rounded-full focus:ring-blue focus:border-blue block ps-8 p-2 pr-4" data-modal-target="skyline-search-modal" data-modal-toggle="skyline-search-modal">
                <div class="absolute inset-y-0 start-0 flex items-center ps-2 pointer-events-none">
                    <svg class="w-4 h-4 text-blue" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                </div>
            </button>
        </div>
        <?php
}
}

/**
 * Renders the search modal HTML in the footer.
 */

add_action( 'wp_footer', 'avolve_search_modal' );
function avolve_search_modal() {
    get_template_part( 'template-parts/search-modal' );
}