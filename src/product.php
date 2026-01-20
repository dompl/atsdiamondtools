<?php get_header(); ?>

<!-- Main Content Area -->
<?php if ( have_posts() ): ?>

	<?php while ( have_posts() ): the_post(); ?>
		<section class="container mx-auto relative av-padding-small">
				<?php the_content()?>
		</section>
	<?php endwhile; ?>
<?php endif; ?>
<?php get_template_part( 'aside' ); ?>
<?php get_footer(); ?>
