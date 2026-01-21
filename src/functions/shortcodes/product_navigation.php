<?php
function shortcode_product_navigation() {
	ob_start(); ?>
<div class="text-center">
	<button class="rfs-ref-category-btn w-full flex items-center justify-between p-2 lg:p-4 border-b border-white/10 lg:cursor-default cursor-pointer text-left outline-none focus:bg-white/5 bg-ats-brand relative z-20"  type="button" data-drawer-target="ats-category-drawer" data-drawer-show="ats-category-drawer" aria-controls="ats-category-drawer">
			<div class="flex items-center gap-3">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
				</svg>
				<h2 class="text-sm lg:text-lg font-bold tracking-wide text-white uppercase m-0">Shop By Category</h2>
			</div>
			<!-- Chevron -->
			<svg class="rfs-ref-category-chevron lg:hidden h-5 w-5 text-white/70 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
			</svg>
		</button>
</div>
<!-- drawer component -->
<div id="ats-category-drawer" class="fixed top-0 left-0 z-40 h-screen p-4 overflow-y-auto transition-transform -translate-x-full bg-neutral-primary-soft w-96 border-e border-default" tabindex="-1" aria-labelledby="category-drawer">
	<div class="border-b border-default pb-4 mb-5 flex items-center">
		<h5 id="category-drawer" class="inline-flex items-center text-lg font-medium text-body">
			<svg class="w-5 h-5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
			Drawer heading
		</h5>
		<button type="button" data-drawer-hide="ats-category-drawer" aria-controls="ats-category-drawer" class="text-body bg-transparent hover:text-heading hover:bg-neutral-tertiary rounded-base w-9 h-9 absolute top-2.5 end-2.5 flex items-center justify-center">
			<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
			<span class="sr-only">Close menu</span>
		</button>
	</div>
	<p class="mb-3 text-sm text-body">Upgrade your Figma toolkit with a design system built on top <a href="#" class="font-medium text-heading underline hover:no-underline">Flowbite CSS</a> featuring variants, style guide and auto layout.</p>
	<p class="mb-5 text-sm text-body">Recommended for professional developers and companies building enterprise-level.</p>
	<div class="flex items-center gap-4">
		<button type="button" class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">Pricing & FAQ</button>
		<button type="button" class="inline-flex items-center justify-center text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
			Get access
			<svg class="rtl:rotate-180 w-4 h-4 ms-1.5 -me-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"/></svg>
		</button>
	</div>
</div>

<?php
	return ob_get_clean();
}
add_shortcode( 'product_navigation', 'shortcode_product_navigation' );