<?php get_header(); ?>

<!-- Main Content Area -->
<?php if ( have_posts() ): ?>

<?php while ( have_posts() ): the_post();
    $banner_image = get_field( 'banner_image' ); ?>
							<?php if ( $banner_image ): ?>
									<img src="<?php echo esc_url( wpimage( image: $banner_image, size: 1900 ) ) ?>" srcset="<?php echo esc_url( wpimage( image: $banner_image, size: 1900 ) ) ?> 1x, <?php echo esc_url( wpimage( image: $banner_image, size: 1900, retina: true ) ) ?> 2x" class="w-full">
								<?php endif; ?>
						<?php the_content()?>
						<div class="container">
						<div class="pt-10 mb-20 border-t border-gray-100">
						<a href="<?php echo esc_url( get_the_permalink( 245 ) ) ?>" class="button-small-orange no-underline uppercase"><span class="no-underline uppercase" ><?php echo _x( 'Back to Success Stories', 'Case study single page button text', 'avolve' ) ?></span></a>
					</div>
					</div>
						<?php endwhile; ?>
<?php endif; ?>
<?php get_template_part( 'aside' ); ?>
<?php get_footer(); ?>