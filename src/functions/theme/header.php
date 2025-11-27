<?php
function heading_data( $selected = false, $mobile = false ) {

    $data             = array();
    $main_logo        = get_field( 'ats_header_logo', 'option' );
    $secondary_logo   = get_field( 'ats_header_sublogo', 'option' );
    $navigation_links = get_field( 'ats_top_navigation_links', 'option' );
    $data['phone']    = get_field( 'ats_info_telephone', 'option' );
    $data['email']    = get_field( 'ats_info_email', 'option' );

    if ( $data['email'] ) {
        $data['email_html'] = '<div class="email inline-flex items-center space-x-2 text-ats-text">';
        if ( $mobile == true ) {
            $data['email_html'] .= '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M172.31-180Q142-180 121-201q-21-21-21-51.31v-455.38Q100-738 121-759q21-21 51.31-21h615.38Q818-780 839-759q21 21 21 51.31v455.38Q860-222 839-201q-21 21-51.31 21H172.31ZM480-457.69 160-662.31v410q0 5.39 3.46 8.85t8.85 3.46h615.38q5.39 0 8.85-3.46t3.46-8.85v-410L480-457.69Zm0-62.31 313.85-200h-627.7L480-520ZM160-662.31V-720v467.69q0 5.39 3.46 8.85t8.85 3.46H160v-422.31Z"/></svg>';
            $data['email_html'] .= '<a href="mailto:' . esc_attr( $data['email'] ) . '" class="text-white font-bold">' . esc_html( $data['email'] ) . '</a>';
        } else {
            $data['email_html'] .= '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#373737"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200ZM160-640v-80 480-400Z"/></svg>';
            $data['email_html'] .= '<a href="mailto:' . esc_attr( $data['email'] ) . '" class="text-inherit hidden xl:inline text-[13px]">' . esc_html( $data['email'] ) . '</a>';
        }

        $data['email_html'] .= '</div>';

        $data['email_html_mobile'] = '<a href="mailto:' . esc_attr( $data['email'] ) . '"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#373737"><path d="M184.62-200q-27.62 0-46.12-18.5Q120-237 120-264.62v-430.76q0-27.62 18.5-46.12Q157-760 184.62-760h590.76q27.62 0 46.12 18.5Q840-723 840-695.38v430.76q0 27.62-18.5 46.12Q803-200 775.38-200H184.62ZM480-475.38 160-684.62v420q0 10.77 6.92 17.7 6.93 6.92 17.7 6.92h590.76q10.77 0 17.7-6.92 6.92-6.93 6.92-17.7v-420L480-475.38Zm0-44.62 307.69-200H172.31L480-520ZM160-684.62V-720v455.38q0 10.77 6.92 17.7 6.93 6.92 17.7 6.92H160v-444.62Z"/></svg></a>';
    } else {
        $data['email_html']        = false;
        $data['email_html_mobile'] = false;
    }

    if ( $data['phone'] ) {
        $data['phone_html'] = '<div class="inline-flex items-center space-x-2 text-ats-text">';
        if ( $mobile == true ) {
            $data['phone_html'] .= '<svg style="transform: scaleX(-1);" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M182.39-140q-18.17 0-30.28-12T140-182v-129.69q0-14.54 9.58-26t24.73-15.46l111.07-22.62q14.39-2 27.73 1.92 13.35 3.93 22.74 13.7l89.76 90.54q43-24.31 80.08-52.54t70-61.16q33.39-33.15 62.04-69.57 28.66-36.43 50.96-77.27l-92.38-89.93q-8.77-8-12.16-19.77-3.38-11.77-1-28.15l22.93-117.69q3.15-14.54 14.73-24.42 11.58-9.89 26.73-9.89H778q18 0 30 12.11t12 30.28q0 113.07-52.58 226.99-52.58 113.93-148.15 209.7-95.58 95.76-209.69 148.34Q295.46-140 182.39-140Zm534.07-446.92Q735-629 745.35-671.31q10.34-42.3 13.42-83.3 0-2.31-1.54-3.85t-3.85-1.54h-84.92q-3.08 0-5 1.54t-2.69 4.61l-18 89.39q-.77 2.31-.19 5 .57 2.69 2.5 4.23l71.38 68.31Zm-348 344.46-70.61-71.39q-1.93-1.92-3.66-2.5-1.73-.57-4.04.2l-84 17.69q-3.07.77-4.61 2.69-1.54 1.92-1.54 5v84.15q0 2.31 1.54 3.85t3.85 1.54q37.53-1.85 80.42-12.27 42.88-10.42 82.65-28.96Zm348-344.46Zm-348 344.46Z"/></svg>';
            $data['phone_html'] .= '<a href="tel:+44' . esc_attr( $data['phone'] ) . '" class="text-white font-bold">' . esc_html( $data['phone'] ) . '</a>';
        } else {
            $data['phone_html'] .= '<svg style="transform: scaleX(-1);" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#373737"><path d="M162-120q-18 0-30-12t-12-30v-162q0-13 9-23.5t23-14.5l138-28q14-2 28.5 2.5T342-374l94 94q38-22 72-48.5t65-57.5q33-32 60.5-66.5T681-524l-97-98q-8-8-11-19t-1-27l26-140q2-13 13-22.5t25-9.5h162q18 0 30 12t12 30q0 125-54.5 247T631-329Q531-229 409-174.5T162-120Zm556-480q17-39 26-79t14-81h-88l-18 94 66 66ZM360-244l-66-66-94 20v88q41-3 81-14t79-28Zm358-356ZM360-244Z"/></svg>';
            $data['phone_html'] .= '<a href="tel:+44' . esc_attr( $data['phone'] ) . '" class="text-inherit hidden xl:inline  text-[13px]">' . esc_html( $data['phone'] ) . '</a>';
        }
        $data['phone_html'] .= '</div>';
        $data['phone_html_mobile'] = '<a href="tel:+44' . esc_attr( $data['phone'] ) . '"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#373737"><path d="M202.77-160q-18.33 0-30.55-12Q160-184 160-202v-97.38q0-16.08 10.15-28.5 10.16-12.43 26.47-16.43l84.15-17.23q14.77-2 26.96 1.35 12.19 3.34 21.96 13.88l85.54 87.08q48-26.62 88.15-56.58 40.16-29.96 75-64.81 33.77-34.3 63.58-72.65 29.81-38.35 54.42-83.04l-87.76-81.84q-9.54-8-13.31-20.54-3.77-12.54-1-29.31l19.84-95.38q4.31-16.08 16.47-26.35Q642.77-800 659.08-800H758q18 0 30 12.22t12 30.55q0 101.15-50.65 207-50.66 105.85-141.81 197.38-91.16 91.54-197.39 142.2Q303.92-160 202.77-160Zm512.15-413.85Q735-619 746.69-663.62q11.69-44.61 12.85-85.61 0-4.62-3.08-7.69-3.08-3.08-7.69-3.08h-81.85q-6.15 0-10 3.08-3.84 3.07-5.38 9.23l-18 84.77q-1.54 4.61-.39 10 1.16 5.38 5 8.46l76.77 70.61Zm-338 332.93-75.23-76.77q-3.84-3.85-7.31-5-3.46-1.16-8.07.38l-74 15.39q-6.16 1.54-9.23 5.38-3.08 3.85-3.08 10v80.31q0 4.61 3.08 7.69 3.07 3.08 7.69 3.08 34.08-.69 79.85-10.54 45.76-9.85 86.3-29.92Zm338-332.93Zm-338 332.93Z"/></svg></a>';
    } else {
        $data['phone_html']        = false;
        $data['phone_html_mobile'] = false;
    }

    if ( $main_logo ) {
        // Handle both array (ACF image field) and string/ID formats
        if ( is_array( $main_logo ) ) {
            $alt     = isset( $main_logo['alt'] ) && $main_logo['alt'] ? $main_logo['alt'] : get_bloginfo( 'name' );
            $logo_id = isset( $main_logo['ID'] ) ? $main_logo['ID'] : ( isset( $main_logo['id'] ) ? $main_logo['id'] : 0 );
        } else {
            $alt     = get_bloginfo( 'name' );
            $logo_id = is_numeric( $main_logo ) ? $main_logo : 0;
        }

        if ( $logo_id ) {
            $img_1x                   = wpimage( image: $logo_id, size: 90, retina: false );
            $img_2x                   = wpimage( image: $logo_id, size: 90, retina: true );
            $img_small_1x             = wpimage( image: $logo_id, size: 65, retina: false );
            $img_small_2x             = wpimage( image: $logo_id, size: 65, retina: true );
            $data['logo_image']       = '<a href="' . esc_url( home_url( '/' ) ) . '" class="block"><img src="' . esc_url( $img_1x ) . '" srcset="' . esc_url( $img_1x ) . ' 1x, ' . esc_url( $img_2x ) . ' 2x" alt="' . esc_attr( $alt ) . '" class="rfs-ref-header-logo rounded-sm"></a>';
            $data['logo_image_small'] = '<a href="' . esc_url( home_url( '/' ) ) . '" class="block"><img src="' . esc_url( $img_small_1x ) . '" srcset="' . esc_url( $img_small_1x ) . ' 1x, ' . esc_url( $img_small_2x ) . ' 2x" alt="' . esc_attr( $alt ) . '" class="rfs-ref-header-logo rounded-sm"></a>';
        }
    }

    if ( $secondary_logo ) {
        // Handle both array (ACF image field) and string/ID formats
        if ( is_array( $secondary_logo ) ) {
            $alt        = isset( $secondary_logo['alt'] ) && $secondary_logo['alt'] ? $secondary_logo['alt'] : get_bloginfo( 'name' );
            $sublogo_id = isset( $secondary_logo['ID'] ) ? $secondary_logo['ID'] : ( isset( $secondary_logo['id'] ) ? $secondary_logo['id'] : 0 );
        } else {
            $alt        = get_bloginfo( 'name' );
            $sublogo_id = is_numeric( $secondary_logo ) ? $secondary_logo : 0;
        }

        if ( $sublogo_id ) {
            $img_1x                 = wpimage( image: $sublogo_id, size: 200, retina: false );
            $img_2x                 = wpimage( image: $sublogo_id, size: 200, retina: true );
            $data['secondary_logo'] = '<a href="' . esc_url( home_url( '/' ) ) . '" class="block hidden xl:inline"><img src="' . esc_url( $img_1x ) . '" srcset="' . esc_url( $img_1x ) . ' 1x, ' . esc_url( $img_2x ) . ' 2x" alt="' . esc_attr( $alt ) . '" class="rfs-ref-header-logo rounded-sm"></a>';
        }
    }

    $navigation_html = '<nav class="rfs-ref-header-top-nav">';
    if ( $navigation_links && is_array( $navigation_links ) ) {
        $navigation_html .= '<ul class="flex items-center space-x-4">';
        foreach ( $navigation_links as $nav_item ) {
            $link = $nav_item['ats_link'] ?? null;
            if ( $link && isset( $link['url'], $link['title'] ) ) {
                $target_attr = ( isset( $link['target'] ) && $link['target'] === '_blank' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
                $navigation_html .= '<li><a href="' . esc_url( $link['url'] ) . '" class="text-ats-text text-xs font-medium hover:opacity-100 opacity-70"' . $target_attr . '>' . esc_html( $link['title'] ) . '</a></li>';
            }
        }
        $navigation_html .= '</ul>';
    }
    $navigation_html .= '</nav>';

    $data['navigation'] = $navigation_html;

    if ( $navigation_links && is_array( $navigation_links ) ) {
        $navigation_html_mobile = '<nav>';
        $navigation_html_mobile .= '<ul class="flex flex-col">';
        foreach ( $navigation_links as $nav_item ) {
            $link = $nav_item['ats_link'] ?? null;
            if ( $link && isset( $link['url'], $link['title'] ) ) {
                $target_attr = ( isset( $link['target'] ) && $link['target'] === '_blank' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
                $navigation_html_mobile .= '<li class="border-b border-white border-opacity-10"><a href="' . esc_url( $link['url'] ) . '" class="text-white text-sm font-normal hover:text-ats-yellow block py-3"' . $target_attr . '>' . esc_html( $link['title'] ) . '</a></li>';
            }
        }
        $navigation_html_mobile .= '</ul>';
        $navigation_html_mobile .= '</nav>';
        $data['navigation_mobile'] = $navigation_html_mobile;
    } else {
        $data['navigation_mobile'] = false;
    }

    if ( $selected && array_key_exists( $selected, $data ) ) {
        return $data[$selected];
    } else {
        return $data;
    }
}
