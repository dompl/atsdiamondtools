<?php
// Custom search logic that matches the AJAX search behavior
$is_search    = is_search();
$search_query = get_search_query();

// Ensure search query is never null to avoid deprecated warnings
if ( is_null( $search_query ) ) {
    $search_query = '';
}

// If this is a search, override the default query to match AJAX search behavior
if ( $is_search && !empty( $search_query ) ) {
    // Get post types to search (same as AJAX)
    $post_types = apply_filters( 'skyline_search_post_types', ['post', 'page', 'case_studies'] );

    // Query 1: Search in post title and content
    $title_content_ids = get_posts( [
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        's'              => $search_query
    ] );

    // Query 2: Search in indexable_content meta field
    $indexable_content_ids = get_posts( [
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            ['key' => 'indexable_content', 'value' => $search_query, 'compare' => 'LIKE']
        ]
    ] );

    // Get pinned search pages from ACF options
    $pinned_pages_ids = [];
    if ( function_exists( 'have_rows' ) && have_rows( 'pinned_search_pages', 'option' ) ) {
        while ( have_rows( 'pinned_search_pages', 'option' ) ) {
            the_row();
            $pinned_page_id = get_sub_field( 'pinned_page' );
            if ( $pinned_page_id ) {
                $pinned_pages_ids[] = $pinned_page_id;
            }
        }
    }

    // Merge and deduplicate results
    $all_matching_ids = array_unique( array_merge( $title_content_ids, $indexable_content_ids ) );

    // Remove pinned pages from regular results to avoid duplicates
    $all_matching_ids = array_diff( $all_matching_ids, $pinned_pages_ids );

    // Prepend pinned pages to the top of results (maintaining their order)
    $final_results_ids = array_merge( $pinned_pages_ids, $all_matching_ids );

    // Override the main query with our custom results
    if ( !empty( $final_results_ids ) ) {
        global $wp_query;
        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

        $wp_query = new WP_Query( [
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'posts_per_page' => get_option( 'posts_per_page', 10 ),
            'paged'          => $paged,
            'post__in'       => $final_results_ids,
            'orderby'        => 'post__in',
            's'              => $search_query  // Preserve search query for wp_title()
        ] );
    } else {
        // No results found - create empty query
        global $wp_query;
        $wp_query = new WP_Query( [
            'post__in' => [0],
            's'        => $search_query  // Preserve search query for wp_title()
        ] );
    }
}

get_header();

// Debug output
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $is_search ) {
    global $wp_query;
    error_log( 'Search Debug - Query: ' . $search_query );
    error_log( 'Search Debug - Found Posts: ' . $wp_query->found_posts );
    error_log( 'Search Debug - Post Count: ' . $wp_query->post_count );
}
?>

<!-- Search Results Page -->
<section class="container mx-auto relative av-padding-small">
    <div class="w-full max-w-3xl mx-auto av-margin-default">

        <?php
// Debug output visible in HTML comments
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    global $wp_query;
    echo '<!-- Search Debug: is_search=' . ( $is_search ? 'true' : 'false' ) . ', query=' . esc_html( $search_query ) . ', found_posts=' . $wp_query->found_posts . ' -->';
}
?>

        <?php if ( have_posts() ): ?>
            <header class="mb-10">
                <h1 class="text-3xl md:text-4xl font-bold text-blue mb-4">
                    <?php
printf(
    _x( 'Search Results for: %s', 'Search results page title', 'avolve' ),
    '<span class="text-orange">' . esc_html( $search_query ) . '</span>'
);
?>
                </h1>
                <p class="text-lg text-gray">
                    <?php
global $wp_query;
printf(
    _nx(
        'Found %s result',
        'Found %s results',
        $wp_query->found_posts,
        'Search results count',
        'avolve'
    ),
    '<strong>' . number_format_i18n( $wp_query->found_posts ) . '</strong>'
);
?>
                </p>
            </header>

            <div class="space-y-8" id="search-results-container">
                <?php while ( have_posts() ): the_post(); ?>
				                    <article class="border-b border-gray-100 pb-8 last:border-b-0">
				                        <h2 class="text-lg font-bold mb-3">
				                            <a href="<?php the_permalink(); ?>" class="text-blue hover:text-orange transition-colors no-underline">
				                                <?php the_title(); ?>
				                            </a>
				                        </h2>

				                        <?php
    // Use the same snippet logic as AJAX search
    $snippet = function_exists( 'get_field' ) ? get_field( 'search_results_text', get_the_ID() ) : '';
    if ( empty( $snippet ) ) {
        $snippet = has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 100, '...' );
    }
    if ( !empty( $snippet ) ):
    ?>
				                            <div class="text-sm text-gray-700 mb-4">
				                                <?php echo wp_kses_post( $snippet ); ?>
				                            </div>
				                        <?php endif; ?>

                        <a href="<?php the_permalink(); ?>" class="button-small-blue no-underline uppercase">
                            <?php echo _x( 'Read More', 'Search results read more link', 'avolve' ); ?>
                        </a>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php if ( $wp_query->max_num_pages > 1 ): ?>
                <div id="search-load-more" class="text-center mt-10">
                    <button id="load-more-search" class="button-small-orange uppercase" data-page="1" data-max-pages="<?php echo $wp_query->max_num_pages; ?>" data-query="<?php echo esc_attr( $search_query ); ?>">
                        <?php echo _x( 'Load More Results', 'Search load more button', 'avolve' ); ?>
                    </button>
                </div>
                <div id="search-loading" class="text-center mt-10 hidden">
                    <span class="text-gray-500"><?php echo _x( 'Loading...', 'Search loading text', 'avolve' ); ?></span>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-20">
                <h1 class="text-3xl md:text-4xl font-bold text-blue mb-4">
                    <?php echo _x( 'No Results Found', 'No search results title', 'avolve' ); ?>
                </h1>
                <p class="text-lg text-gray mb-8">
                    <?php
printf(
    _x( 'Sorry, no results were found for "%s". Please try searching with different keywords.', 'No search results message', 'avolve' ),
    '<strong>' . esc_html( $search_query ) . '</strong>'
);
?>
                </p>

                <!-- Search form -->
                <div class="max-w-md mx-auto">
                    <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex gap-2">
                        <input type="search" name="s" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg text-sm" placeholder="<?php echo _x( 'Try another search...', 'Search form placeholder', 'avolve' ); ?>" value="<?php echo esc_attr( $search_query ); ?>" required />
                        <button type="submit" class="button-small-orange no-underline uppercase">
                            <?php echo _x( 'Search', 'Search button', 'avolve' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php get_template_part( 'aside' ); ?>
<?php get_footer(); ?>
