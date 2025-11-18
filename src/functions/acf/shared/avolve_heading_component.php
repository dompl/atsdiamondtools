<?php
use Extended\ACF\Fields\Checkbox;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Textarea;

/**
 * Reusable function for a contact block
 *
 * @return array
 */
function component_shared_component_heading_fields( $exclude = [], $prefix = '' ) {
    $fields = [];
    if ( !in_array( 'tab', $exclude ) ) {
        $fields[] = Tab::make( 'General', wp_unique_id() )->placement( 'left' );
    }
    if ( !in_array( 'heading', $exclude ) ) {
        $fields[] = Textarea::make( 'Heading', $prefix . 'heading' )
            ->helperText( 'Enter the main heading for this section (e.g., "Trusted by creators worldwide").' )
            ->rows( 2 );
    }
    if ( !in_array( 'subheading', $exclude ) ) {
        $fields[] = Textarea::make( 'Subheading', $prefix . 'subheading' )->rows( 2 );
    }
    if ( !in_array( 'settings', $exclude ) ) {
        $fields[] = Checkbox::make( 'Additional settings', $prefix . 'settings' )
            ->helperText( 'Heading additional settings' )
            ->choices( [
                'center'           => 'Center Text',
                'narrow'           => 'Narrow heading',
                'bold_title'       => 'Bold title',
                'bold_description' => 'Bold description',
                'reduce_margin'    => 'Reduce bottom margin',
                'remove_margin'    => 'Remove bottom margin'
            ] )
            ->format( 'value' ) // array, label, value (default)
            ->layout( 'horizontal' ); // vertical, horizontal;
    }
    if ( !in_array( 'tag', $exclude ) ) {
        $fields[] = Select::make( 'Heading Tag', $prefix . 'tag' )
            ->helperText( 'Heading additional settings' )
            ->choices( [
                'h1'  => 'H1',
                'h2'  => 'H2',
                'h3'  => 'H3',
                'h4'  => 'H4',
                'p'   => 'p',
                'div' => 'div'
            ] )
            ->default( 'div' ) // array, label, value (default)
            ->format( 'value' ); // array, label, value (default)
    }
    if ( !in_array( 'buttons', $exclude ) ) {
        $fields = array_merge( $fields, component_shared_component_button_fields( $prefix ) );
    }
    return $fields;
}

/**
 * Renders a heading section with optional heading and subheading.
 *
 * @param string $heading    The main heading string (may contain <br>).
 * @param string $subheading The subheading string (may contain <br>).
 * @param array  $args       Optional. Override default classes or wrapper.
 *                           {
 *                             string $container_classes  Container <div> classes.
 *                             string $heading_wrapper    Element for heading (e.g. 'h2', 'div').
 *                             string $heading_classes    Classes for the heading wrapper.
 *                             string $subheading_classes Classes for the <p> subheading.
 *                           }
 */
function avolve_render_heading_section( $args = [], $prefix = '', $container_classes = '' ) {

    $heading                  = get_sub_field( $prefix . 'heading' );
    $subheading               = get_sub_field( $prefix . 'subheading' );
    $settings                 = get_sub_field( $prefix . 'settings' );
    $buttons                  = get_sub_field( $prefix . 'buttons' );
    $prose                    = get_sub_field( $prefix . 'prose' ) ?: 'prose';
    $tag                      = get_sub_field( $prefix . 'tag' ) ?: 'div';
    $button_container_classes = '';

    // If neither heading nor subheading nor buttons, bail early
    if ( !$heading && !$subheading && empty( $buttons ) ) {
        return '';
    }

    // Default settings
    $defaults = array(
        'container_classes'  => '',
        'heading_wrapper'    => $tag,
        'heading_classes'    => 'heading-default',
        'subheading_classes' => $prose . ' text-[1rem] max-w-full text-inherit'
    );
    $args = wp_parse_args( $args, $defaults );

    if ( !in_array( 'remove_margin', (array) $settings ) ) {
        if ( !empty( $settings ) && in_array( 'reduce_margin', $settings ) ) {
            $args['container_classes'] .= '  mb-8 lg:mb-8';
        } else {
            $args['container_classes'] .= '  mb-8 lg:mb-16';
        }
    }
    $args['container_classes'] .= $container_classes;

    if ( !empty( $settings ) ) {

        if ( in_array( 'center', $settings ) ) {
            $args['container_classes'] .= ' mx-auto text-center';
            $button_container_classes = ' justify-center';
        }
        if ( in_array( 'narrow', $settings ) ) {
            $args['container_classes'] .= ' ml-0 mr-0 md:mx-8 lg:mx-16 xl:mx-32 2xl:mx-44 destroy';
        }

        if ( in_array( 'bold_title', $settings ) ) {
            $args['heading_classes'] .= ' font-bold';
        }
        if ( in_array( 'bold_description', $settings ) ) {
            $args['subheading_classes'] .= ' font-bold';
        }

    }

    if ( $heading || $subheading ) {
        $button_container_classes .= ' text-lg';
    } else {

    }

    $output = '';

    // Start container
    $output .= '<div class="' . esc_attr( $args['container_classes'] ) . '">';

    // Heading
    if ( $heading ) {
        $output .= sprintf(
            '<%1$s class="%2$s">%3$s</%1$s>',
            esc_html( $args['heading_wrapper'] ),
            esc_attr( $args['heading_classes'] ),
            avolve_text( wp_kses( $heading, array( 'br' => array(), 'strong' => array(), 'b' => array() ) ) )
        );
    }

    // Subheading
    if ( $subheading ) {
        // add top margin if heading exists
        $mt = $heading ? 'mt-6 ' : '';
        $output .= sprintf(
            '<p class="%1$s%2$s">%3$s</p>',
            esc_attr( $mt ),
            esc_attr( $args['subheading_classes'] ),
            avolve_text( wp_kses( $subheading, array( 'br' => array() ) ) )
        );
    }
    // Close container

    $output .= avolve_buttons( container_classes: $button_container_classes );
    $output .= '</div>';

    // Check if output contains only an empty div
    if ( preg_match( '/^<div[^>]*>\s*<\/div>$/', $output ) ) {
        return '';
    }

    return $output;
}