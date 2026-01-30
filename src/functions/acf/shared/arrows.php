<?php

function ats_slider_arrows() {

    $previous_inner = '<span class="inline-flex items-center justify-center w-6 h-6 lg:w-10 lg:h-10 rounded-full bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white group-focus:outline-none">';
    $previous_inner .= '<svg class="w-3 h-3 lg:w-4 lg:h-4 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/></svg>';
    $previous_inner .= '<span class="sr-only">Previous</span>';
    $previous_inner .= '</span>';

    $previous = '';
    $previous .= '<button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-prev>';
    $previous .= $previous_inner;
    $previous .= '</button>';

    $next_inner = '<span class="inline-flex items-center justify-center w-6 h-6 lg:w-10 lg:h-10 rounded-full bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white group-focus:outline-none">';
    $next_inner .= '<svg class="w-3 h-3 lg:w-4 lg:h-4 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>';
    $next_inner .= '<span class="sr-only">Next</span>';
    $next_inner .= '</span>';

    $next = '';
    $next .= '<button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none splide__arrow splide__arrow--next" data-carousel-next>';
    $next .= $next_inner;
    $next .= '</button>';

    $icons = [
        'previous' => '<svg class="w-6 h-6 text-gray" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"> <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/> </svg>',
        'next'     => '<svg class="w-6 h-6 text-gray" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"> <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/> </svg>'
    ];

    return ['previous' => $previous, 'next' => $next, 'icons' => $icons];

}

add_filter( 'skyline_child_localizes', function ( $localizes ) {
    $localizes['arrows'] = ats_slider_arrows();
    return $localizes;
} );