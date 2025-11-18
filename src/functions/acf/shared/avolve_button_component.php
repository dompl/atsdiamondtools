<?php
/**
 * Reusable function for a contact block
 *
 * @return array
 */
use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Link;
use Extended\ACF\Fields\Number;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\TrueFalse;

function component_shared_component_button_fields( $prefix = '', $center = true ) {
    $fields = [];

    if ( $center === true ) {
        $fields[] = TrueFalse::make( __( 'Centre buttons', 'avolve' ), $prefix . 'centre_buttons' )
            ->default( false )
            ->stylized(
                on: __( 'Yes', 'avolve' ),
                off: __( 'No', 'avolve' )
            )
            ->conditionalLogic( [
                ConditionalLogic::where( $prefix . 'buttons', '!=empty' )
            ] );

        $fields[] = Select::make( 'Buttons top spacing', $prefix . 'buttons_space' )
            ->helperText( 'Select the box shadow color.' )
            ->nullable()
            ->stylized()
            ->choices( [
                'av-margin-xsmall'  => 'X Small',
                'av-margin-small'   => 'Small',
                'av-margin-default' => 'Default',
                'av-margin-large'   => 'Large',
                'av-margin-xlarge'  => 'Extra Large'
            ] )
            ->default( 'none' )
            ->format( 'value' )
            ->conditionalLogic( [
                ConditionalLogic::where( $prefix . 'buttons', '!=empty' )
            ] );
    }

    $fields[] = Repeater::make( __( 'Buttons', 'avolve' ), $prefix . 'buttons' )
        ->layout( 'block' )
        ->button( __( 'Add Button', 'avolve' ) )
        ->fields( [
            Group::make( __( 'Button', 'avolve' ), 'button_group' )
                ->layout( 'table' )
                ->fields( [
                    Link::make( __( 'Link', 'avolve' ), 'link' )
                        ->required(),

                    Image::make( __( 'Button Image', 'avolve' ), 'image' )
                        ->helperText( __( 'Instead of button text you can add an image', 'avolve' ) )
                        ->library( 'all' )
                        ->format( 'array' )
                        ->previewSize( 'thumbnail' ),

                    Number::make( __( 'Image Width', 'avolve' ), 'width' )
                        ->helperText( __( 'Add button image width', 'avolve' ) )
                        ->default( 300 )
                        ->min( 100 )
                        ->max( 1200 )
                        ->required( true )
                        ->conditionalLogic( [
                            // show only if an image exists
                            ConditionalLogic::where( 'image', '!=empty' )
                        ] ),

                    ButtonGroup::make( __( 'Colour', 'avolve' ), 'colour' )
                        ->choices( [
                            'orange' => __( 'Orange', 'avolve' ),
                            'blue'   => __( 'Blue', 'avolve' ),
                            'white'  => __( 'White', 'avolve' ),
                            'black'  => __( 'Black', 'avolve' )
                        ] )
                        ->default( 'orange' )
                        ->required()
                        ->conditionalLogic( [
                            // show only if no image
                            ConditionalLogic::where( 'image', '==empty' )
                        ] ),

                    ButtonGroup::make( __( 'Size', 'avolve' ), 'size' )
                        ->choices( [
                            'small'  => __( 'Small', 'avolve' ),
                            'large'  => __( 'Large', 'avvolve' ),
                            'xlarge' => __( 'X Large', 'avolve' )
                        ] )
                        ->default( 'large' )
                        ->required()
                        ->conditionalLogic( [
                            // show only if no image
                            ConditionalLogic::where( 'image', '==empty' )
                        ] )
                ] )
        ] );

    return $fields;
}

function avolve_buttons( $buttons = [], $container_classes = '', $button_classes = '', $size = 'large', $prefix = '' ): string {

    if ( empty( $buttons ) ) {
        $buttons = get_sub_field( $prefix . 'buttons' );
    }
    if ( empty( $buttons ) ) {
        return '';
    }

    $center = get_sub_field( $prefix . 'centre_buttons' );
    $space  = get_sub_field( $prefix . 'buttons_space' );

    $container_classes .= $center ? ' justify-center' : '';
    $container_classes .= $space != 'none' && !empty( $space ) ? ' ' . $space . ' pb-0' : '';

    ob_start(); ?>

	<div class="flex items-center flex-wrap gap-4 <?php echo $container_classes ?>">
		<?php foreach ( $buttons as $button_row ):
        $button_group       = $button_row['button_group'];
        $button_link        = $button_group['link'];
        $button_image       = $button_group['image'];
        $button_image_width = $button_group['width'] ?? 300;
        $button_color       = $button_group['colour'] ?? 'orange';
        $button_size        = $button_group['size'] ?? $size;
        if ( $button_link ): ?>
																									  <?php if ( !empty( $button_image ) ): ?>
																										<a href="<?php echo esc_url( $button_link['url'] ); ?>"
																								target="<?php echo esc_attr( $button_link['target'] ?: '_self' ); ?>">
																								<img src="<?php echo wpimage( image: $button_image['id'], size: $button_image_width, retina: false ) ?>" srcset="<?php echo wpimage( image: $button_image['id'], size: $button_image_width, retina: false ) ?> 1x, <?php echo wpimage( image: $button_image['id'], size: $button_image_width, retina: true ) ?> 2x" loading="lazy">
																							</a>
																						<?php else: ?>
																		<a href="<?php echo esc_url( $button_link['url'] ); ?>"
																			target="<?php echo esc_attr( $button_link['target'] ?: '_self' ); ?>"
																			class="button-<?php echo $button_size ?>-<?php echo esc_attr( $button_color ); ?>">
																			<?php echo esc_html( $button_link['title'] ); ?>
																		</a>

														<?php endif?>

			<?php endif;
    endforeach; ?>
	</div>

	<?php
return ob_get_clean();

}
