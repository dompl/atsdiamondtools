<?php
function heading_data( $selected = false ) {

    $data             = array();
    $main_logo        = get_field( 'ats_header_logo', 'option' );
    $secondary_logo   = get_field( 'ats_header_sublogo', 'option' );
    $navigation_links = get_field( 'ats_top_navigation_links', 'option' );
    $data['phone']    = get_field( 'ats_info_telephone', 'option' );
    $data['email']    = get_field( 'ats_info_email', 'option' );

    if ( $data['email'] ) {
        $data['email_html'] = '<div class="email inline-flex items-center space-x-2">
			<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#1C1B1F"><path d="M192.62-232q-24.32 0-40.47-16.16T136-288.66v-383.01Q136-696 152.15-712t40.47-16h574.76q24.32 0 40.47 16.16t16.15 40.5v383.01Q824-264 807.85-248t-40.47 16H192.62ZM480-467.38 168-655.62v367q0 10.77 6.92 17.7 6.93 6.92 17.7 6.92h574.76q10.77 0 17.7-6.92 6.92-6.93 6.92-17.7v-367L480-467.38Zm0-49.62 299.69-179H180.31L480-517ZM168-655.62V-696v407.38q0 10.77 6.92 17.7 6.93 6.92 17.7 6.92H168v-391.62Z"/></svg>
			<a href="mailto:' . esc_attr( $data['email'] ) . '" class="text-inherit">' . esc_html( $data['email'] ) . '</a>
		</div>';
    } else {
        $data['email_html'] = false;
    }

    if ( $data['phone'] ) {
        $data['phone_html'] = '<div class="tel inline-flex items-center space-x-2">
			<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#1C1B1F" style="transform: scaleX(-1);"><path d="M235.77-185q-21.33 2-37.55-12.29Q182-211.57 182-233v-71.38q0-20.08 11.15-34.5 11.16-14.43 30.47-19.43l53.15-12.23q13.77-2 23.46.35 9.69 2.34 18.46 11.88l90.54 89.08q48-24.62 88.15-53.58 40.16-28.96 74.04-62.82 32.73-33.29 61.04-72.14 28.31-38.85 52.92-84.54l-87.76-81.84q-9.54-8-13.81-21.54-4.27-13.54-1.5-27.31l17.84-61.38q6.31-19.08 19.97-30.35Q633.77-776 653.08-776H726q21.43 0 35.71 15.72Q776-744.56 774-723.23q-10 97.15-55.65 189-45.66 91.85-122.81 169.38-77.16 77.54-169.89 124.2Q332.92-194 235.77-185Zm464.15-388.85Q717-615 728.69-655.12q11.69-40.11 12.85-78.11 0-4.62-3.08-7.69-3.08-3.08-7.69-3.08h-83.85q-6.15 0-10 3.08-3.84 3.07-5.38 9.23l-17 64.77q-1.54 4.61-.39 10 1.16 5.38 5 8.46l80.77 74.61Zm-324 320.93-79.23-80.77q-3.84-3.85-7.31-5-3.46-1.16-8.07.38l-55 11.39q-6.16 1.54-9.23 5.38-3.08 3.85-3.08 10v82.31q0 4.61 3.08 7.69 3.07 3.08 7.69 3.08 30.08-2.69 72.35-10.04 42.26-7.35 78.8-24.42Zm324-320.93Zm-324 320.93Z"/></svg>
			<a href="tel:+44' . esc_attr( $data['phone'] ) . '" class="text-inherit">' . esc_html( $data['phone'] ) . '</a>
		</div>';
    } else {
        $data['phone_html'] = false;
    }

    if ( $main_logo ) {
        $alt    = $main_logo['alt'] ? $main_logo['alt'] : get_bloginfo( 'name' );
        $img_1x = wpimage( image: $main_logo['ID'], size: 300, retina: false );
        $img_2x = wpimage( image: $main_logo['ID'], size: 300, retina: true );

        $data['logo_image'] = '<a href="' . esc_url( home_url( '/' ) ) . '" class="block"><img src="' . esc_url( $img_1x ) . '" srcset="' . esc_url( $img_1x ) . ' 1x, ' . esc_url( $img_2x ) . ' 2x" alt="' . esc_attr( $alt ) . '" class="rfs-ref-header-logo rounded-sm"></a>';
    }

    if ( $secondary_logo ) {
        $alt    = $secondary_logo['alt'] ? $secondary_logo['alt'] : get_bloginfo( 'name' );
        $img_1x = wpimage( image: $secondary_logo['ID'], size: 200, retina: false );
        $img_2x = wpimage( image: $secondary_logo['ID'], size: 200, retina: true );

        $data['secondary_logo'] = '<a href="' . esc_url( home_url( '/' ) ) . '" class="block"><img src="' . esc_url( $img_1x ) . '" srcset="' . esc_url( $img_1x ) . ' 1x, ' . esc_url( $img_2x ) . ' 2x" alt="' . esc_attr( $alt ) . '" class="rfs-ref-header-logo rounded-sm"></a>';
    }

    $navigation_html = '<nav class="rfs-ref-header-top-nav">';
    if ( $navigation_links && is_array( $navigation_links ) ) {
        $navigation_html .= '<ul class="flex items-center space-x-6">';
        foreach ( $navigation_links as $nav_item ) {
            $link = $nav_item['ats_link'] ?? null;
            if ( $link && isset( $link['url'], $link['title'] ) ) {
                $target_attr = ( isset( $link['target'] ) && $link['target'] === '_blank' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
                $navigation_html .= '<li><a href="' . esc_url( $link['url'] ) . '" class="rfs-ref-header-nav-link text-neutral-700 text-xs font-normal hover:text-primary-800"' . $target_attr . '>' . esc_html( $link['title'] ) . '</a></li>';
            }
        }
        $navigation_html .= '</ul>';
    }
    $navigation_html .= '</nav>';

    $data['navigation'] = $navigation_html;

    if ( $selected && array_key_exists( $selected, $data ) ) {
        return $data[$selected];
    } else {
        return $data;
    }
}
