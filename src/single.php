<?php get_header(); ?>

<!-- Main Content Area -->
<?php if ( have_posts() ): ?>

	<?php while ( have_posts() ): the_post(); ?>
		<?php
		$title = esc_attr( get_the_title() );
		if ( in_category( 1 ) ) {
			$subtitle = get_field( 'blog_banner_title', 'option' ) ? get_field( 'blog_banner_title', 'option' ) : esc_attr__( 'Avolve News', 'avolve' ) . '|orange';
		} elseif ( in_category( 14 ) ) {
			$subtitle = get_field( 'events_banner_title', 'option' ) ? get_field( 'events_banner_title', 'option' ) : esc_attr__( 'Avolve Events', 'avolve' ) . '|orange';
		} elseif ( in_category( 15 ) ) {
			$subtitle = get_field( 'news_banner_title', 'option' ) ? get_field( 'news_banner_title', 'option' ) : esc_attr__( 'Avolve News', 'avolve' ) . '|orange';
		}
		$banner_background = get_field( 'blog_banner_image', 'option' ) ? get_field( 'blog_banner_image', 'option' ) : 4547;

		$shortcode = <<<SC
[hero_slide
font="text-xl lg:text-2xl xl:text-3xl 2xl:text-4xl lg:leading-8 xl:leading-8 2xl:leading-10"
size="small"
title="{$title}"
subtitle="{$subtitle}"
background_image_id="{$banner_background}"
]
SC;

		echo do_shortcode( $shortcode );
		?>
		<section class="container mx-auto relative av-padding-small">
			<div class="prose-sm xl:prose w-full max-w-full xl:max-w-full av-margin-default ">
				<?php the_content()?>
				<div class="mt-10 pt-10 border-t border-gray-100">
					<a href="<?php echo esc_url( get_the_permalink( 261 ) ) ?>" class="button-small-orange no-underline uppercase">
						<?php if ( defined( 'AVOLVE_LANGUAGE' ) && AVOLVE_LANGUAGE === 'nl' ): ?>
							<span class="no-underline uppercase" ><?php echo _x( 'Back to Blogs', 'Single post page button text', 'avolve' ) ?></span>
						<?php else: ?>
							<span class="no-underline uppercase" ><?php echo _x( 'Back to News', 'Single post page button text', 'avolve' ) ?></span>
						<?php endif?>
					</a>
				</div>
			</div>
		</section>
	<?php endwhile; ?>
<?php endif; ?>
<?php get_template_part( 'aside' ); ?>
<?php get_footer(); ?>
