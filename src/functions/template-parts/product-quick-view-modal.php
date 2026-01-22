<?php
/**
 * Product Quick View Modal Template
 *
 * @package SkylineWP Dev Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<!-- Product Quick View Modal -->
<div id="ats-product-quick-view-modal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="rfs-ref-product-quick-view-modal hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="rfs-ref-quick-view-modal-dialog relative p-4 w-full max-w-7xl max-h-full">
        <!-- Modal content -->
        <div class="rfs-ref-quick-view-modal-content relative bg-white rounded-lg shadow">
            <!-- Modal body -->
            <div class="rfs-ref-quick-view-modal-body p-4 md:p-5 space-y-4">
                <!-- Close button inside body -->
                <button type="button" class="rfs-ref-quick-view-close-btn absolute top-4 right-4 z-10 text-gray-400 bg-white hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center shadow-sm" data-modal-hide="ats-product-quick-view-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
                <!-- Loading spinner -->
                <div class="rfs-ref-quick-view-loading flex items-center justify-center py-12">
                    <svg class="animate-spin h-8 w-8 text-accent-yellow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <!-- Product content will be loaded here via AJAX -->
                <div class="rfs-ref-quick-view-product-content"></div>
            </div>
        </div>
    </div>
</div>
