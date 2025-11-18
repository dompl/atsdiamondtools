<?php
/**
 * Flexible Content Container System
 *
 * Provides a reusable set of ACF fields and an HTML wrapper function
 * to create flexible, full-width container sections with various
 * background, spacing, and parallax effect options.
 *
 * @package    WordPress
 * @subpackage Avolve
 * @since      1.0.0
 *
 * @requires   simpleParallax.js (https://simpleparallax.com/)
 */

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\Checkbox;
use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Range;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\TrueFalse;

if ( !function_exists( 'avolve_round' ) ) {
    function avolve_round(): bool {
        $wrap  = get_field( 'wrap', 'option' );
        $round = get_field( 'round', 'option' );

        if ( $wrap && $round ) {
            return true;
        }
        return false;
    }
}
/**
 * Generates a reusable list of colors for ACF select fields.
 *
 * @param string $prefix The prefix for the Tailwind CSS class (e.g., 'bg', 'text').
 * @return array An array of colors in 'class' => 'Label' format.
 */
if ( !function_exists( 'avolve_colors' ) ) {
    function avolve_colors( string $prefix = '' ): array {
        return [
            $prefix . '-gray'       => 'Gray',
            $prefix . '-light-gray' => 'Light Gray',
            $prefix . '-blue'       => 'Blue',
            $prefix . '-white'      => 'White',
            $prefix . '-orange'     => 'Orange',
            $prefix . '-gray-100'   => 'Gray 100',
            $prefix . '-gray-300'   => 'Gray 300',
            $prefix . '-gray-700'   => 'Gray 700',
            $prefix . '-gray-900'   => 'Gray 900',
            $prefix . '-blue-100'   => 'Blue 100',
            $prefix . '-blue-300'   => 'Blue 300',
            $prefix . '-blue-700'   => 'Blue 700',
            $prefix . '-blue-900'   => 'Blue 900',
            $prefix . '-orange-100' => 'Orange 100',
            $prefix . '-orange-300' => 'Orange 300',
            $prefix . '-orange-700' => 'Orange 700',
            $prefix . '-orange-900' => 'Orange 900',
            $prefix . '-sky-100'    => 'Sky 100',
            $prefix . '-sky-300'    => 'Sky 300',
            $prefix . '-sky'        => 'Sky',
            $prefix . '-sky-700'    => 'Sky 700',
            $prefix . '-sky-900'    => 'Sky 900',
            $prefix . '-indigo-100' => 'Indigo 100',
            $prefix . '-indigo-300' => 'Indigo 300',
            $prefix . '-indigo'     => 'Indigo',
            $prefix . '-indigo-700' => 'Indigo 700',
            $prefix . '-indigo-900' => 'Indigo 900',
            $prefix . '-cyan-100'   => 'Cyan 100',
            $prefix . '-cyan-300'   => 'Cyan 300',
            $prefix . '-cyan'       => 'Cyan',
            $prefix . '-cyan-700'   => 'Cyan 700',
            $prefix . '-cyan-900'   => 'Cyan 900'
        ];
    }
}

function component_shared_component_container_fields( array $exclude = [] ): array {

    $fields = [];

    // Main tab
    $fields[] = Tab::make( 'Container', wp_unique_id() )
        ->placement( 'left' );

    // Background settings
    if ( !in_array( 'background', $exclude, true ) ) {
        $fields[] = ButtonGroup::make( 'Background Type', 'background_type' )
            ->helperText( 'Select a background type for this section.' )
            ->choices( [
                'none'  => 'None',
                'color' => 'Colour',
                'image' => 'Image'
            ] )
            ->layout( 'horizontal' );
        $fields[] = TrueFalse::make( 'Use Particles', 'particles' )
            ->helperText( 'Select whether to display moving particles in the container' )
            ->default( false )
            ->stylized( on: 'Yes', off: 'No' );
        $fields[] = Select::make( 'Background Colour', 'background_color' )
            ->helperText( 'Select a background colour.' )
            ->choices( avolve_colors( 'bg' ) )
            ->nullable()
            ->stylized()
            ->conditionalLogic( [
                ConditionalLogic::where( 'background_type', '==', 'color' )
            ] );

        $fields[] = Image::make( 'Background Image', 'background_image' )
            ->helperText( 'Upload a background image for the section. Allowed image format: jpeg' )
            ->format( 'array' )
            ->acceptedFileTypes( ['jpg', 'jpeg'] )
            ->conditionalLogic( [
                ConditionalLogic::where( 'background_type', '==', 'image' )
            ] );

        $fields[] = TrueFalse::make( 'Enable Parallax Effect', 'parallax_enabled' )
            ->helperText( 'Add a subtle parallax scroll effect to the background image.' )
            ->stylized( 'On', 'Off' )
            ->conditionalLogic( [
                ConditionalLogic::where( 'background_type', '==', 'image' ),
                ConditionalLogic::where( 'background_image', '!=empty' )
            ] );

        // Grouped Parallax Settings
        $fields[] = Group::make( 'Parallax Settings', 'parallax_settings' )
            ->helperText( 'Adjust orientation, scale, and delay for the parallax effect.' )
            ->layout( 'table' )
            ->conditionalLogic( [
                ConditionalLogic::where( 'parallax_enabled', '==', 1 )
            ] )
            ->fields( [
                ButtonGroup::make( 'Orientation', 'parallax_orientation' )
                    ->helperText( 'Direction of the parallax motion.' )
                    ->choices( ['up' => 'Up', 'down' => 'Down', 'left' => 'Left', 'right' => 'Right'] )
                    ->default( 'down' ),

                Range::make( 'Scale', 'parallax_scale' )
                    ->helperText( 'Zoom factor for the image (e.g. 1.6).' )
                    ->min( 1 )->max( 3 )->step( 0.1 )->default( 1.6 ),

                Range::make( 'Delay', 'parallax_delay' )
                    ->helperText( 'Transition delay in seconds (e.g. 0.4).' )
                    ->min( 0 )->max( 1 )->step( 0.1 )->default( 0.4 )
            ] );
    }

    // Text colour
    if ( !in_array( 'text', $exclude, true ) ) {
        $fields[] = Select::make( 'Text Colour', 'text_color' )
            ->helperText( 'Select a text colour for the content inside this container.' )
            ->choices( avolve_colors( 'text' ) )
            ->nullable()
            ->stylized();
    }

    // Vertical padding
    if ( !in_array( 'padding', $exclude, true ) ) {
        $fields[] = Select::make( 'Vertical Padding', 'vertical_padding' )
            ->helperText( 'Set the spacing above and below the content.' )
            ->nullable()
            ->stylized()
            ->choices( [
                'av-margin-small'   => 'Small',
                'av-margin-default' => 'Default',
                'av-margin-large'   => 'Large',
                'av-margin-xlarge'  => 'Extra Large'
            ] );

        $fields[] = Checkbox::make( 'Remove vertical padding', 'padding_remove' )
            ->helperText( 'You can remove padding from top or bottom' )
            ->choices( ['top' => 'Remove top padding', 'bottom' => 'Remove bottom padding'] )
            ->default( '' )
            ->format( 'value' )
            ->layout( 'horizontal' )
            ->conditionalLogic( [
                ConditionalLogic::where( 'vertical_padding', '!=', '' )
            ] );

    }

    // Horizontal padding
    if ( !in_array( 'margin', $exclude, true ) ) {
        $fields[] = Select::make( 'Horizontal Padding', 'horizontal_padding' )
            ->helperText( 'Set the side padding for the inner content. This is responsive.' )
            ->choices( [
                'av-padding-small'   => 'Small',
                'av-padding-default' => 'Default',
                'av-padding-large'   => 'Large',
                'av-padding-xlarge'  => 'Extra Large'
            ] )
            ->nullable()
            ->stylized();
    }

    if ( !in_array( 'round', $exclude, true ) && avolve_round() ) {
        $fields[] = Checkbox::make( 'Round', 'round' )
            ->helperText( 'Round container edges' )
            ->choices( [
                'rounded-t-lg' => 'Round top edges',
                'rounded-b-lg' => 'Round bottom edges'
            ] )
            ->layout( 'horizontal' );

        $fields[] = Checkbox::make( 'Separate section', 'separate' )
            ->helperText( 'Separate container by adding space above or below it.' )
            ->choices( [
                'top'    => 'Separate from top',
                'bottom' => 'Separate from bottom'
            ] )
            ->layout( 'horizontal' );
    }

    if ( !in_array( 'buttons', $exclude ) ) {
        $fields = array_merge( $fields, component_shared_component_button_fields( prefix: 'container_' ) );
    }

    $fields[] = Text::make( 'Container ID', 'id' )
        ->helperText( 'Add unique for the page container ID' );

    return $fields;
}

function avolve_container( string $content ): string {
    // --- Data fetching ---
    $background_type  = get_sub_field( 'background_type' ) ?: 'none';
    $background_color = get_sub_field( 'background_color' ) ?: 'bg-transparent';
    $background_image = get_sub_field( 'background_image' );
    $parallax_enabled = get_sub_field( 'parallax_enabled' );
    $use_particles    = get_sub_field( 'particles' );
    $round            = get_sub_field( 'round' ) ?: [];
    $separate         = get_sub_field( 'separate' ) ?: [];
    $id               = get_sub_field( 'id' );

    // Grouped parallax settings
    $parallax_settings = get_sub_field( 'parallax_settings' ) ?: [];
    $orientation       = $parallax_settings['parallax_orientation'] ?? 'down';
    $scale             = $parallax_settings['parallax_scale'] ?? '1.6';
    $delay             = $parallax_settings['parallax_delay'] ?? '0.4';

    // Other settings
    $text_color         = get_sub_field( 'text_color' );
    $vertical_padding   = get_sub_field( 'vertical_padding' ) ?: '';
    $padding_remove     = get_sub_field( 'padding_remove' ) ?: [];
    $horizontal_padding = get_sub_field( 'horizontal_padding' ) ?: '';

    // --- Prepare classes ---
    $section_classes = ['w-full', 'overflow-hidden', 'relative', 'isolation-isolate', $text_color, $vertical_padding];

    if ( in_array( 'top', $padding_remove ) ) {
        $section_classes[] = 'pt-0';
    }
    if ( in_array( 'bottom', $padding_remove ) ) {
        $section_classes[] = 'pb-0';
    }

    $background_html = '';
    $wrapper_classes = ['container', 'mx-auto', 'relative', $horizontal_padding];

    if ( 'color' === $background_type ) {
        $section_classes[] = $background_color;
    }

    if ( 'image' === $background_type && !empty( $background_image['url'] ) ) {
        $bg_classes = ['absolute', 'inset-0', 'w-full', 'h-full', 'z-0', 'overflow-hidden'];
        $data_attr  = '';

        if ( $parallax_enabled ) {
            $bg_classes[] = 'simple-parallax-image';
            $data_attr    = sprintf(
                ' data-orientation="%s" data-scale="%s" data-delay="%s"',
                esc_attr( $orientation ),
                esc_attr( $scale ),
                esc_attr( $delay )
            );
        }

        $background_html = sprintf(
            '<div class="%s" style="background-image:url(%s);background-size:cover;background-position:center;"%s></div>',
            implode( ' ', $bg_classes ),
            esc_url( $background_image['url'] ),
            $data_attr
        );
    }

    $content .= avolve_buttons( prefix: 'container_' );

    if ( avolve_round() ) {

        if ( get_row_layout() == 'sub_navigation_bar' ) {
            $section_classes[] = 'rounded-b-lg';
        }
        if ( !empty( $round ) ) {
            foreach ( $round as $rounded_class ) {
                $section_classes[] = $rounded_class;
            }
        }
    }

    // --- Render HTML ---
    $html = '';
    $html .= in_array( 'top', $separate ) ? '<div class="h-[1rem]"></div>' : '';
    $html .= sprintf( '<section class="%s relative"%s>', esc_attr( implode( ' ', $section_classes ) ), $id ? ' id="' . $id . '"' : '' );
    $html .= $background_html;
    $html .= sprintf( '<div class="%s">%s</div>%s', esc_attr( implode( ' ', $wrapper_classes ) ), $content, $use_particles ? '<div class="particles-js"></div>' : '' );
    $html .= '</section>';
    $html .= in_array( 'bottom', $separate ) ? '<div class="h-[1rem]"></div>' : '';

    return $html;
}
