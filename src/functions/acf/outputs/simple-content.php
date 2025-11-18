<?php
/**
 * Render Content Section within Flexible Content
 *
 * This function outputs a UIKit-based content section for flexible content,
 * with a prefix, main title, description, and an optional gap position.
 *
 * @param string$output The existing output.
 * @param string$layout The layout type (e.g., 'content_section').
 * @return string Modified output for the content section layout.
 */
function explore_render_simple_section( $output, $layout ) {
    if ( $layout === 'content_simple' ) {

        $content = get_sub_field( 'content' );

        // Start building the output
        ob_start();?>

<div class="uk-container">
    <?php echo $content ?>
</div>

<?php
// Return the buffered output
        $output = ob_get_clean();
    }
    return $output;
}
add_filter( "explore_flexible_content_output", "explore_render_simple_section", 10, 2 );