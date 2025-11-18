<?php

add_action( 'wp_footer', 'avolve_sidebar_navigation' );

function avolve_sidebar_navigation() {
	?>
	<div id="drawer-navigation" class="fixed top-0 left-0 z-50 w-64 h-screen p-4 overflow-y-auto transition-transform -translate-x-full bg-white flex flex-col" tabindex="-1" aria-labelledby="drawer-navigation-label">
		<div class="flex-shrink-0">
			<?php
			$logo = get_field( 'header_logo', 'option' );
			if ( $logo && is_array( $logo ) ) {
				echo '<img src="' . esc_url( wpimage( image: $logo['id'], size: 30, reversed: true ) ) . '" srcset="' . esc_url( wpimage( image: $logo['id'], size: 30, reversed: true ) ) . ' 1x, ' . esc_url( wpimage( image: $logo['id'], size: 30, retina: true, reversed: true ) ) . ' 2x, ' . esc_url( wpimage( image: $logo['id'], size: 90, reversed: true ) ) . ' 3x" alt="' . esc_attr( $logo['alt'] ) . '">';
			}
			?>
			<button type="button" data-drawer-hide="drawer-navigation" aria-controls="drawer-navigation" class="text-gray bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 absolute top-2.5 end-2.5 inline-flex items-center" >
				<svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
				</svg>
				<span class="sr-only">Close menu</span>
			</button>
		</div>
		<div class="overflow-y-auto mt-4">
			<div>
				<?php do_action( 'avolve_sidebar_nav_content' ); ?>
			</div>
			<div class="pt-5">
				<?php avolve_header_support_button_html( true ) ?>
			</div>
			<div class="pt-5">
				<?php avolve_header_language_selector_html( 'side-drop', true ) ?>
			</div>
			<div class="pt-5"></div>
			<div class="flex justify-center items-center gap-5 pt-5 border-t">
				<?php avolve_header_certifications_html() ?>
			</div>
		</div>
	</div>
	<?php
}

add_action( 'avolve_sidebar_nav_content', 'avolve_sidebar_nav_content_navigation_html' );

function avolve_sidebar_nav_content_navigation_html() {
	?>
	<div id="accordion-flush" data-accordion="collapse" data-active-classes="bg-transparent text-blue text-bold" data-inactive-classes="text-gray">
		<?php
		wp_nav_menu( [
			'theme_location' => 'primary', // Or any other menu location
			'container'      => false, // Let the walker handle the container
			'items_wrap'     => '%3$s', // Don't wrap in a <ul>, the walker does it
			'walker'         => new Flowbite_Accordion_Walker()
		] );
		?>
	</div>
	<?php
}