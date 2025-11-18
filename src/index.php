<?php get_header(); ?>
<main role="main" aria-label="Content">
    <?php if ( have_posts() ): ?>
        <?php while ( have_posts() ): the_post(); ?>
			            <?php the_content(); ?>
			        <?php endwhile; ?>
    <?php get_sidebar()?>
    <?php endif; ?>
</main>
<?php get_footer(); ?>