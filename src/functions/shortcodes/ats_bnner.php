	<?php
function ats_banner_html() {
    ob_start();
    ?>
		<div class=" flex items-center justify-center p-4 lg:p-8">
		  <!-- Banner Container -->
		  <div class="w-full container mx-auto flex flex-col-reverse lg:flex-row gap-4 lg:gap-5 p-4 lg:p-0">

			<!-- Sidebar (Navigation) -->
			<div class="w-full lg:w-[320px] flex-shrink-0 bg-[#594652] text-white rounded-lg overflow-hidden shadow-xl flex flex-col relative z-20 h-fit">

			  <!-- Toggle Button -->
			  <button id="category-btn" class="w-full flex items-center justify-between p-5 border-b border-white/10 lg:cursor-default cursor-pointer text-left outline-none focus:bg-white/5 bg-[#594652] relative z-20">
				<div class="flex items-center gap-3">
				  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
				  </svg>
				  <h2 class="text-lg font-bold tracking-wide text-white uppercase">Shop By Category</h2>
				</div>
				<!-- Chevron -->
				<svg id="category-chevron" xmlns="http://www.w3.org/2000/svg" class="lg:hidden h-5 w-5 text-white/70 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
				</svg>
			  </button>

			  <!-- List Container -->
			  <div id="category-list" class="grid transition-[grid-template-rows] duration-500 ease-out grid-rows-0 lg:grid-rows-1">
				<div class="overflow-hidden">
				  <div class="flex flex-col py-2" id="category-items">
					<!-- Items injected via JS -->
				  </div>
				</div>
			  </div>
			</div>

			<!-- Carousel -->
			<div class="w-full lg:flex-1 relative h-[500px] lg:h-auto rounded-lg overflow-hidden shadow-xl bg-gray-900 group">
				<div id="carousel-slides" class="absolute inset-0 w-full h-full">
					<!-- Slides injected via JS -->
				</div>

				<!-- Controls -->
				<button id="prev-btn" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 p-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 lg:opacity-0">
					<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
					</svg>
				</button>
				<button id="next-btn" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 p-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 lg:opacity-0">
					<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
					</svg>
				</button>

				<!-- Dots -->
				<div id="carousel-dots" class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-2">
					<!-- Dots injected via JS -->
				</div>
			</div>

		  </div>
		</div>
		<?php
return ob_get_clean();
}
?>