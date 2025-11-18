<?php
// Check if search results should be displayed on a page instead of popup
$search_on_page = get_field( 'search_results_on_page', 'option' );
$form_class = $search_on_page ? 'search-redirect-to-page' : 'search-ajax-mode';
?>
<div id="skyline-search-modal" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-lg max-h-full mx-auto">
        <!-- The form wrapper needs a 'relative' class for positioning the dropdown -->
        <div class="relative rounded-lg bg-white shadow-lg">
            <form class="w-full <?php echo esc_attr( $form_class ); ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label for="skyline-search-input" class="sr-only"><?php echo _x( 'Search', 'Search modal label', 'avolve' ); ?></label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <input type="search" id="skyline-search-input" name="s" class="block w-full p-4 ps-12 text-sm text-gray border-0 rounded-lg" placeholder="<?php echo _x( 'Search the Website...', 'Search modal placeholder', 'avolve' ); ?>" value="<?php echo get_search_query(); ?>" required autocomplete="<?php echo $search_on_page ? 'on' : 'off'; ?>" />
                </div>
            </form>
            <?php if ( ! $search_on_page ): ?>
            <div id="skyline-search-results" class="absolute top-full w-full bg-white rounded-lg shadow-lg z-50 overflow-hidden hidden mt-1">
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>