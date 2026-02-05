<?php
/**
 * Simple Content Component
 *
 * A flexible content component with WYSIWYG editor and layout options
 *
 * @package SkylineWP Dev Child
 */

use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\WYSIWYGEditor;

function simple_content_fields() {
    return [
        Tab::make('Content', wp_unique_id())->placement('left'),

        WYSIWYGEditor::make('Content', 'content')
            ->helperText('Add your content here')
            ->tabs('all')
            ->toolbar('full')
            ->required(),

        Tab::make('Layout Settings', wp_unique_id())->placement('left'),

        Select::make('Container Width', 'container_width')
            ->helperText('Choose the width of the content container')
            ->choices([
                'default' => 'Default (Full Width)',
                'medium' => 'Medium (Checkout Width)',
                'narrow' => 'Narrow (Centered)',
            ])
            ->default('default')
            ->format('value')
            ->required(),

        Select::make('Padding Size', 'padding_size')
            ->helperText('Choose the padding around the content')
            ->choices([
                'default' => 'Default',
                'small' => 'Small',
                'large' => 'Large',
            ])
            ->default('default')
            ->format('value')
            ->required(),

        Select::make('Text Size', 'prose_size')
            ->helperText('Choose the text size for the content')
            ->choices([
                'small' => 'Small',
                'default' => 'Default',
                'large' => 'Large',
            ])
            ->default('default')
            ->format('value')
            ->required(),
    ];
}

function component_simple_content_html(string $output, string $layout): string {
    if ($layout !== 'simple_content') {
        return $output;
    }

    // Get field values
    $content = get_sub_field('content');
    $container_width = get_sub_field('container_width') ?: 'default';
    $padding_size = get_sub_field('padding_size') ?: 'default';
    $prose_size = get_sub_field('prose_size') ?: 'default';

    // Define container width classes
    $width_classes = [
        'default' => 'container mx-auto px-4',
        'medium' => 'container mx-auto px-4 max-w-7xl',
        'narrow' => 'container mx-auto px-4 max-w-3xl',
    ];

    // Define padding classes
    $padding_classes = [
        'small' => 'py-6',
        'default' => 'py-12',
        'large' => 'py-20',
    ];

    // Define prose size classes
    $prose_classes = [
        'small' => 'prose-sm',
        'default' => 'prose',
        'large' => 'prose-lg',
    ];

    // Get the appropriate classes
    $container_class = $width_classes[$container_width] ?? $width_classes['default'];
    $padding_class = $padding_classes[$padding_size] ?? $padding_classes['default'];
    $prose_class = $prose_classes[$prose_size] ?? $prose_classes['default'];

    ob_start();
    ?>

    <div class="rfs-ref-simple-content-wrapper <?php echo esc_attr($padding_class); ?> bg-white">
        <div class="<?php echo esc_attr($container_class); ?>">
            <div class="rfs-ref-simple-content-inner prose <?php echo esc_attr($prose_class); ?> max-w-none">
                <?php echo wp_kses_post($content); ?>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_filter('skylinewp_flexible_content_output', 'component_simple_content_html', 10, 2);

// Define the custom layout for flexible content
return Layout::make('Simple Content', 'simple_content')
    ->layout('block')
    ->fields(simple_content_fields());
