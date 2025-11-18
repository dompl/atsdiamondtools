<?php

use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\WYSIWYGEditor;

function simple_content_fields() {

    $toolbar_options = [
        'aligncenter',
        //   'alignleft',
        'alignright',
        //   'blockquote',
        'bold',
        'bullist',
        //   'charmap',
        'forecolor',
        //   'formatselect',
        //   'fullscreen',
        //   'hr',
        //   'indent',
        'italic',
        'link',
        'numlist',
        //   'outdent',
        'pastetext'
        //   'redo',
        //   'removeformat',
        //   'spellchecker',
        //   'strikethrough',
        //   'underline',
        //   'undo'
    ];

    return [
        Tab::make( 'Content', wp_unique_id() )->placement( 'left' ),
        Select::make( 'Text Size', 'size' )
            ->helperText( 'Select global text size' )
            ->choices( ['prose-sm' => 'Small', 'prose' => 'Default', 'prose-lg' => 'Large', 'prose-xl' => 'X Large'] )
            ->default( 'prose' )
            ->format( 'value' )
            ->nullable( true )
            ->stylized( true )
            ->lazyLoad( true ),
        WYSIWYGEditor::make( 'Content', 'content' )
            ->tabs( 'all' ) // all, text, visual (default)
            ->toolbar( $toolbar_options )
            ->disableMediaUpload()
            ->lazyLoad()
    ];
}

function component_simple_content_html( string $output, string $layout ): string {

    if ( $layout !== 'content_simple' ) {
        return $output;
    }
    $simple_content    = get_sub_field( 'content' );
    $container_buttons = get_sub_field( 'container_buttons' );
    $prose             = get_sub_field( 'size' ) ?: 'prose xl:prose-lg';
    $output .= avolve_render_heading_section();
    $output .= apply_filters( 'skyline_before_flex_element', '', $layout );
    if ( !$simple_content ) {
        return avolve_container( $output );
    }
    $output .= apply_filters( 'skyline_after_flex_element', '', $layout );
    if ( $container_buttons ) {
        $output .= '<div class="' . $prose . ' max-w-none text-inherit mb-[2rem]">' . $simple_content . '</div>';

    } else {
        $output .= '<div class="' . $prose . ' max-w-none text-inherit">' . $simple_content . '</div>';
    }
    $output .= apply_filters( 'skyline_after_flex_element', '', $layout );

    return avolve_container( $output );
}
add_filter( 'skylinewp_flexible_content_output', 'component_simple_content_html', 10, 2 );

// Define the custom layout for flexible content
return Layout::make( 'Simple Content', 'content_simple' )
    ->layout( 'block' ) // Choose block layout style
    ->fields( array_merge( component_shared_component_heading_fields(), simple_content_fields(), component_shared_component_container_fields( exclude: [] ) ) );