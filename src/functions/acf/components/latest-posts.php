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
            ->default( '2' )
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
    $items_count    = get_sub_field( 'items_count' ) ?: '2';
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
    <section class="rfs-ref-latest-posts-section ats-latest-posts-section py-8 lg:py-12 <?php echo esc_attr( $bg_class ); ?>">
        <div class="rfs-ref-latest-posts-container container mx-auto px-4">
            <!-- Section Title -->
            <div class="rfs-ref-latest-posts-header flex items-center w-full gap-5 mb-6">
                <h2 class="rfs-ref-latest-posts-title text-lg xl:text-xl font-bold text-primary-600 whitespace-nowrap tracking-tight">
                    <?php echo esc_html( $title ); ?>
                </h2>
                <div class="rfs-ref-latest-posts-divider flex-grow h-[1px] bg-neutral-300"></div>
            </div>

            <!-- Posts Grid - Horizontal cards with image LEFT, text RIGHT -->
            <div class="rfs-ref-latest-posts-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo min( intval( $items_count ), 3 ); ?> gap-6">
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
                    $thumbnail_url = $thumbnail_id ? wpimage( $thumbnail_id, [600, 400], false, true, true ) : '';
                ?>
                    <article class="rfs-ref-latest-post-card ats-latest-card bg-white border border-neutral-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow flex flex-col">
                        <!-- Image on TOP - Large featured image -->
                        <?php if ( $thumbnail_url ) : ?>
                            <a href="<?php echo esc_url( $post_url ); ?>" class="rfs-ref-latest-post-image-link block">
                                <img
                                    src="<?php echo esc_url( $thumbnail_url ); ?>"
                                    alt="<?php echo esc_attr( $post_title ); ?>"
                                    class="rfs-ref-latest-post-image w-full h-64 object-cover"
                                    loading="lazy"
                                />
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( $post_url ); ?>" class="rfs-ref-latest-post-placeholder block bg-gray-100 flex items-center justify-center h-64">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                            </a>
                        <?php endif; ?>

                        <!-- Content BELOW image -->
                        <div class="rfs-ref-latest-post-content p-6 flex flex-col flex-grow">
                            <div class="rfs-ref-latest-post-meta flex items-center gap-2 text-xs text-gray-400 mb-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="rfs-ref-latest-post-date">
                                    <?php echo esc_html( $post_date ); ?>
                                </span>
                            </div>

                            <h3 class="rfs-ref-latest-post-title text-xl font-bold text-neutral-700 mb-3 line-clamp-2">
                                <a href="<?php echo esc_url( $post_url ); ?>" class="rfs-ref-latest-post-title-link hover:text-black">
                                    <?php echo esc_html( $post_title ); ?>
                                </a>
                            </h3>

                            <?php if ( $post_excerpt ) : ?>
                                <p class="rfs-ref-latest-post-excerpt text-gray-600 text-sm leading-relaxed mb-4 line-clamp-3 flex-grow">
                                    <?php echo esc_html( wp_trim_words( $post_excerpt, 20 ) ); ?>
                                </p>
                            <?php endif; ?>

                            <a
                                href="<?php echo esc_url( $post_url ); ?>"
                                class="rfs-ref-latest-post-cta-btn inline-flex justify-center items-center px-6 py-2 bg-primary-300 hover:bg-primary-400 text-black text-sm font-medium uppercase rounded transition-colors self-start"
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
