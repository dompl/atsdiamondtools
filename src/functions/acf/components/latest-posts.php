<?php
/**
 * ACF Flex Field: Latest from ATS
 *
 * Displays latest blog posts in horizontal card layout with image on LEFT, text on RIGHT
 *
 * @package ATS Diamond Tools
 */

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\PostObject;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\TrueFalse;

function latest_posts_fields() {
    return [
        Tab::make( 'Content', wp_unique_id() )->placement( 'left' ),

        Text::make( 'Title', 'title' )
            ->helperText( 'Enter the section title (e.g., "Latest from ATS")' )
            ->default( 'Latest From ATS' )
            ->required(),

        Select::make( 'Content Source', 'content_source' )
            ->helperText( 'Choose whether to display posts, pages, or manually selected content' )
            ->choices( [
                'posts'    => 'Latest Blog Posts',
                'pages'    => 'Selected Pages',
                'selected' => 'Manually Selected Posts/Pages'
            ] )
            ->default( 'posts' )
            ->format( 'value' )
            ->required(),

        PostObject::make( 'Selected Content', 'selected_content' )
            ->helperText( 'Select specific posts or pages to display' )
            ->postTypes( ['post', 'page'] )
            ->format( 'object' )
            ->multiple()
            ->conditionalLogic( [
                ConditionalLogic::where( 'content_source', '==', 'selected' )
            ] ),

        PostObject::make( 'Selected Pages', 'selected_pages' )
            ->helperText( 'Select specific pages to display' )
            ->postTypes( ['page'] )
            ->format( 'object' )
            ->multiple()
            ->conditionalLogic( [
                ConditionalLogic::where( 'content_source', '==', 'pages' )
            ] ),

        Tab::make( 'Settings', wp_unique_id() )->placement( 'left' ),

        Select::make( 'Number of Items', 'items_count' )
            ->helperText( 'How many items to display' )
            ->choices( [
                '2' => '2 Items',
                '3' => '3 Items',
                '4' => '4 Items'
            ] )
            ->default( '3' )
            ->format( 'value' )
            ->required(),

        Text::make( 'Button Text', 'button_text' )
            ->helperText( 'Custom button text' )
            ->default( 'Read More' ),

        Select::make( 'Background Color', 'bg_color' )
            ->helperText( 'Choose the section background color' )
            ->choices( [
                'white'      => 'White',
                'gray-50'    => 'Light Gray',
                'gray-100'   => 'Gray',
                'primary-50' => 'Light Primary'
            ] )
            ->default( 'white' )
            ->format( 'value' ),
    ];
}

function component_latest_posts_html( string $output, string $layout ): string {
    if ( $layout !== 'latest_posts' ) {
        return $output;
    }

    $title          = get_sub_field( 'title' ) ?: 'Latest From ATS';
    $content_source = get_sub_field( 'content_source' ) ?: 'posts';
    $selected       = get_sub_field( 'selected_content' );
    $selected_pages = get_sub_field( 'selected_pages' );
    $items_count    = get_sub_field( 'items_count' ) ?: '3';
    $button_text    = get_sub_field( 'button_text' ) ?: 'Read More';
    $bg_color       = get_sub_field( 'bg_color' ) ?: 'white';

    // Get posts/pages
    $items = [];

    if ( $content_source === 'selected' && !empty( $selected ) ) {
        $items = array_slice( $selected, 0, intval( $items_count ) );
    } elseif ( $content_source === 'pages' && !empty( $selected_pages ) ) {
        $items = array_slice( $selected_pages, 0, intval( $items_count ) );
    } else {
        // Get latest posts
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => intval( $items_count ),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];
        $items = get_posts( $args );
    }

    if ( empty( $items ) ) {
        return '';
    }

    $bg_class = 'bg-' . $bg_color;

    ob_start();
    ?>
    <section class="ats-latest-posts-section py-8 lg:py-12 <?php echo esc_attr( $bg_class ); ?>">
        <div class="container mx-auto px-4">
            <!-- Section Title -->
            <div class="flex items-center w-full gap-5 mb-6">
                <h2 class="text-lg xl:text-xl font-bold text-primary-600 whitespace-nowrap tracking-tight">
                    <?php echo esc_html( $title ); ?>
                </h2>
                <div class="flex-grow h-[1px] bg-neutral-300"></div>
            </div>

            <!-- Posts Grid - Horizontal cards with image LEFT, text RIGHT -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo min( intval( $items_count ), 3 ); ?> gap-6">
                <?php foreach ( $items as $item ) :
                    $post_obj = is_object( $item ) ? $item : get_post( $item );
                    if ( !$post_obj ) continue;

                    $post_id       = $post_obj->ID;
                    $post_title    = get_the_title( $post_id );
                    $post_url      = get_permalink( $post_id );
                    $post_date     = get_the_date( 'j M Y', $post_id );
                    // Use raw excerpt to avoid infinite loop with the_content filter
                    $post_excerpt  = !empty( $post_obj->post_excerpt ) ? $post_obj->post_excerpt : wp_trim_words( strip_shortcodes( $post_obj->post_content ), 55 );
                    $thumbnail_id  = get_post_thumbnail_id( $post_id );
                    $thumbnail_url = $thumbnail_id ? wpimage( $thumbnail_id, [200, 200], false, true, true ) : '';
                ?>
                    <article class="ats-latest-card bg-white border border-neutral-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow" style="display: flex; flex-direction: row;">
                        <!-- Image on LEFT -->
                        <?php if ( $thumbnail_url ) : ?>
                            <a href="<?php echo esc_url( $post_url ); ?>" style="flex-shrink: 0; width: 140px; min-width: 140px;">
                                <img
                                    src="<?php echo esc_url( $thumbnail_url ); ?>"
                                    alt="<?php echo esc_attr( $post_title ); ?>"
                                    style="width: 140px; height: 100%; object-fit: cover;"
                                    loading="lazy"
                                />
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( $post_url ); ?>" style="flex-shrink: 0; width: 140px; min-width: 140px; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                            </a>
                        <?php endif; ?>

                        <!-- Content on RIGHT -->
                        <div style="display: flex; flex-direction: column; justify-content: space-between; padding: 1rem; padding-left: 1rem; flex-grow: 1; min-width: 0;">
                            <div>
                                <span class="text-xs text-gray-400 mb-1 block">
                                    <?php echo esc_html( $post_date ); ?>
                                </span>

                                <h3 class="text-base font-bold text-primary-700 mb-2 line-clamp-2">
                                    <a href="<?php echo esc_url( $post_url ); ?>" class="hover:text-primary-800">
                                        <?php echo esc_html( $post_title ); ?>
                                    </a>
                                </h3>

                                <?php if ( $post_excerpt ) : ?>
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                        <?php echo esc_html( wp_trim_words( $post_excerpt, 15 ) ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <a
                                href="<?php echo esc_url( $post_url ); ?>"
                                class="ats-btn ats-btn-sm ats-btn-yellow self-start"
                            >
                                <?php echo esc_html( $button_text ); ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_filter( 'skylinewp_flexible_content_output', 'component_latest_posts_html', 10, 2 );

// Define the custom layout for flexible content
return Layout::make( 'Latest from ATS', 'latest_posts' )
    ->layout( 'block' )
    ->fields( latest_posts_fields() );
