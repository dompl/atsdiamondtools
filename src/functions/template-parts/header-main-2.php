<div class="container rfs-ref-header-wrapper py-4">
	<!-- Mobile Header (visible on screens < lg) -->
	<div class="rfs-ref-header-mobile lg:hidden">
		<!-- Top row: hamburger, logo, cart -->
		<div class="rfs-ref-mobile-top-row flex items-center justify-between gap-4">
			<!-- Mobile Menu Toggle -->
			<button type="button"
					id="ats-mobile-menu-toggle"
					class="rfs-ref-mobile-menu-toggle js-mobile-menu-toggle p-2 text-ats-text hover:text-ats-dark"
					aria-label="<?php esc_attr_e( 'Toggle menu', 'skylinewp-dev-child' ); ?>"
					aria-expanded="false">
				<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
					<path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z"/>
				</svg>
			</button>

			<!-- Logo (centered) -->
			<div class="rfs-ref-mobile-logo flex-1 flex justify-center">
				<?php echo heading_data( 'logo_image' ); ?>
			</div>

			<!-- Cart -->
			<div class="rfs-ref-mobile-cart">
				<?php echo do_shortcode( '[ats_add_to_cart]' ); ?>
			</div>
		</div>

		<!-- Search row (mobile) -->
		<div class="rfs-ref-mobile-search-row mt-3">
			<?php echo do_shortcode( '[ats_search context="mobile"]' ); ?>
		</div>

	</div>

	<!-- Mobile Menu Modal (full-screen overlay) -->
	<div id="ats-mobile-menu"
		 class="rfs-ref-mobile-menu js-mobile-menu hidden fixed inset-0 z-50 lg:hidden">
		<!-- Backdrop -->
		<div class="rfs-ref-mobile-menu-backdrop js-mobile-menu-backdrop absolute inset-0 bg-black bg-opacity-50"></div>

		<!-- Menu Panel (slide from left) -->
		<div class="rfs-ref-mobile-menu-panel js-mobile-menu-panel absolute top-0 left-0 h-full w-[85%] max-w-sm bg-white shadow-xl overflow-y-auto">
			<!-- Header with close button -->
			<div class="rfs-ref-mobile-menu-header flex items-center justify-between p-4 border-b border-ats-gray">
				<div class="rfs-ref-mobile-menu-logo">
					<?php echo heading_data( 'logo_image' ); ?>
				</div>
				<button type="button"
						class="rfs-ref-mobile-menu-close js-mobile-menu-close p-2 text-ats-text hover:text-ats-dark"
						aria-label="<?php esc_attr_e( 'Close menu', 'skylinewp-dev-child' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
						<path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/>
					</svg>
				</button>
			</div>

			<!-- Navigation Links -->
			<nav class="rfs-ref-mobile-nav p-4">
				<?php echo heading_data( 'navigation' ); ?>
			</nav>

			<!-- Contact Info -->
			<div class="rfs-ref-mobile-contacts space-y-3 p-4 border-t border-ats-gray">
				<?php if ( heading_data( 'phone_html' ) ): ?>
					<div class="rfs-ref-mobile-phone">
						<?php echo heading_data( 'phone_html' ); ?>
					</div>
				<?php endif; ?>
				<?php if ( heading_data( 'email_html' ) ): ?>
					<div class="rfs-ref-mobile-email">
						<?php echo heading_data( 'email_html' ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Login/Account -->
			<div class="rfs-ref-mobile-login p-4 border-t border-ats-gray">
				<div class="inline-flex items-center space-x-2 text-ats-text">
					<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M250.85-269.62q54-35.69 109.73-53.03Q416.31-340 480-340q63.69 0 119.42 17.35 55.73 17.34 109.73 53.03 39.62-41 61.23-94.84Q792-418.31 792-480q0-129.67-91.23-220.84-91.23-91.16-221-91.16Q350-792 259-700.84 168-609.67 168-480q0 61.69 21.62 115.54 21.61 53.84 61.23 94.84ZM480.02-428q-51.56 0-87.79-36.21Q356-500.42 356-551.98q0-51.56 36.21-87.79Q428.42-676 479.98-676q51.56 0 87.79 36.21Q604-603.58 604-552.02q0 51.56-36.21 87.79Q531.58-428 480.02-428Zm.26 292q-71.59 0-134.28-26.54t-109.81-73.65q-47.11-47.12-73.65-109.77Q136-408.61 136-480.46t26.54-134.04q26.54-62.19 73.65-109.31 47.12-47.11 109.77-73.65Q408.61-824 480.46-824t134.04 26.54q62.19 26.54 109.31 73.65 47.11 47.12 73.65 109.53 26.54 62.42 26.54 134 0 71.59-26.54 134.28t-73.65 109.81q-47.12 47.11-109.53 73.65-62.42 26.54-134 26.54Zm-.28-32q54.31 0 108.85-20.35 54.53-20.34 96.53-56.96-43-29.31-95.23-46Q537.92-308 480-308q-57.92 0-111.04 15.81-53.11 15.81-94.34 46.88 42 36.62 96.53 56.96Q425.69-168 480-168Zm0-292q37.69 0 64.85-27.15Q572-514.31 572-552t-27.15-64.85Q517.69-644 480-644t-64.85 27.15Q388-589.69 388-552t27.15 64.85Q442.31-460 480-460Zm0-92Zm0 309Z"/></svg>
					<?php echo do_shortcode( '[ats_email_login]' ); ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Desktop Header (visible on screens >= lg) -->
	<div class="rfs-ref-header-desktop hidden lg:block">
		<!-- Top Bar: Navigation, Phone, Email, Login -->
		<div class="rfs-ref-desktop-top-bar flex items-center justify-between pb-2 border-b border-ats-gray">
			<!-- Left: Navigation -->
			<div class="rfs-ref-desktop-nav">
				<?php echo heading_data( 'navigation' ); ?>
			</div>

			<!-- Right: Contacts & Login -->
			<div class="rfs-ref-desktop-contacts-login flex items-center gap-6 text-[13px] font-bold text-ats-text">
				<?php echo heading_data( 'phone_html' ); ?>
				<?php echo heading_data( 'email_html' ); ?>
				<div class="rfs-ref-desktop-login inline-flex items-center space-x-2">
					<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M250.85-269.62q54-35.69 109.73-53.03Q416.31-340 480-340q63.69 0 119.42 17.35 55.73 17.34 109.73 53.03 39.62-41 61.23-94.84Q792-418.31 792-480q0-129.67-91.23-220.84-91.23-91.16-221-91.16Q350-792 259-700.84 168-609.67 168-480q0 61.69 21.62 115.54 21.61 53.84 61.23 94.84ZM480.02-428q-51.56 0-87.79-36.21Q356-500.42 356-551.98q0-51.56 36.21-87.79Q428.42-676 479.98-676q51.56 0 87.79 36.21Q604-603.58 604-552.02q0 51.56-36.21 87.79Q531.58-428 480.02-428Zm.26 292q-71.59 0-134.28-26.54t-109.81-73.65q-47.11-47.12-73.65-109.77Q136-408.61 136-480.46t26.54-134.04q26.54-62.19 73.65-109.31 47.12-47.11 109.77-73.65Q408.61-824 480.46-824t134.04 26.54q62.19 26.54 109.31 73.65 47.11 47.12 73.65 109.53 26.54 62.42 26.54 134 0 71.59-26.54 134.28t-73.65 109.81q-47.12 47.11-109.53 73.65-62.42 26.54-134 26.54Zm-.28-32q54.31 0 108.85-20.35 54.53-20.34 96.53-56.96-43-29.31-95.23-46Q537.92-308 480-308q-57.92 0-111.04 15.81-53.11 15.81-94.34 46.88 42 36.62 96.53 56.96Q425.69-168 480-168Zm0-292q37.69 0 64.85-27.15Q572-514.31 572-552t-27.15-64.85Q517.69-644 480-644t-64.85 27.15Q388-589.69 388-552t27.15 64.85Q442.31-460 480-460Zm0-92Zm0 309Z"/></svg>
					<?php echo do_shortcode( '[ats_email_login]' ); ?>
				</div>
			</div>
		</div>

		<!-- Main Bar: Logo | Search | Cart -->
		<div class="rfs-ref-desktop-main-bar flex items-center gap-6 pt-3">
			<!-- Left: Logo + Secondary Logo (as one column) -->
			<div class="rfs-ref-desktop-logo flex items-center gap-3 flex-shrink-0">
				<?php echo heading_data( 'logo_image' ); ?>
				<?php
				$secondary_logo = heading_data( 'secondary_logo' );
				if ( $secondary_logo ): ?>
					<?php echo $secondary_logo; ?>
				<?php endif; ?>
			</div>

			<!-- Center: Search (takes remaining space) -->
			<div class="rfs-ref-desktop-search flex-1">
				<?php echo do_shortcode( '[ats_search context="desktop"]' ); ?>
			</div>

			<!-- Right: Cart (fixed width column) -->
			<div class="rfs-ref-desktop-cart flex-shrink-0">
				<?php echo do_shortcode( '[ats_add_to_cart]' ); ?>
			</div>
		</div>
	</div>
</div>
