<?php
/**
 * Blog posts index (the Posts page / page_for_posts).
 * Full-width hero (fading top-seller product images) + stacked article rows
 * (square image, generous spacing, grey "Read more" button) + shop / newsletter sidebar.
 *
 * @package SkylineWP Dev Child
 */

get_header();

$blog_page_id = (int) get_option( 'page_for_posts' );
$blog_title   = $blog_page_id ? get_the_title( $blog_page_id ) : __( 'Blog', 'skylinewp-dev-child' );

// Top sellers: hero background images + sidebar products.
$top_ids = get_posts( array(
	'post_type'      => 'product',
	'post_status'    => 'publish',
	'posts_per_page' => 14,
	'meta_key'       => 'total_sales',
	'orderby'        => 'meta_value_num',
	'order'          => 'DESC',
	'fields'         => 'ids',
) );
$hero_imgs = array();
foreach ( $top_ids as $pid ) {
	$tid = get_post_thumbnail_id( $pid );
	if ( $tid ) {
		$u = wpimage( image: $tid, size: array( 1200, 700 ), retina: true, quality: 85 );
		if ( $u ) {
			$hero_imgs[] = $u;
		}
	}
	if ( count( $hero_imgs ) >= 4 ) {
		break;
	}
}
$cta_ids = array_slice( $top_ids, 0, 8 );
?>
<div class="rfs-ref-blog bg-white min-h-screen">

	<!-- Hero banner -->
	<section class="rfs-ref-blog-hero relative bg-ats-dark overflow-hidden">
		<div class="rfs-ref-blog-hero-bg absolute inset-0">
			<?php foreach ( $hero_imgs as $u ) : ?>
				<div class="rfs-ref-blog-hero-slide absolute inset-0">
					<img src="<?php echo esc_url( $u ); ?>" alt="" class="w-full h-full object-cover opacity-40" loading="lazy" aria-hidden="true">
				</div>
			<?php endforeach; ?>
			<div class="absolute inset-0 bg-gradient-to-t from-ats-dark via-ats-dark/70 to-ats-dark/30"></div>
			<div class="rfs-ref-blog-hero-deco absolute inset-0 pointer-events-none opacity-20">
				<div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-primary-600 blur-3xl"></div>
				<div class="absolute -bottom-16 -left-16 w-48 h-48 rounded-full bg-ats-yellow blur-2xl"></div>
			</div>
		</div>
		<div class="rfs-ref-blog-hero-content relative container mx-auto px-4 py-10 md:py-14">
			<div class="max-w-3xl">
				<span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide bg-ats-yellow text-ats-dark mb-5">Guides &amp; Advice</span>
				<h1 class="rfs-ref-blog-hero-title text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4 leading-tight drop-shadow"><?php echo esc_html( $blog_title ); ?></h1>
				<p class="rfs-ref-blog-hero-intro text-gray-200 text-base md:text-lg leading-relaxed max-w-2xl drop-shadow">Practical how-tos and buying advice for professional diamond tooling: cutting, drilling, grinding and polishing.</p>
			</div>
		</div>
	</section>

	<!-- Body: stacked articles + sidebar -->
	<div class="rfs-ref-blog-main container mx-auto px-4 py-12">
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12">

			<!-- Articles, one under the other -->
			<main class="rfs-ref-blog-list-col lg:col-span-8">
				<?php if ( have_posts() ) : ?>
					<div class="rfs-ref-blog-list flex flex-col gap-10">
						<?php while ( have_posts() ) : the_post(); ?>
							<article <?php post_class( 'rfs-ref-blog-row flex flex-row items-start gap-5 sm:gap-6' ); ?>>
								<a href="<?php the_permalink(); ?>" class="rfs-ref-blog-row-media block shrink-0 w-28 sm:w-40 md:w-48" aria-label="<?php the_title_attribute(); ?>">
									<span class="block aspect-square overflow-hidden rounded-lg bg-white border border-gray-200">
										<?php
										// Serve a small, fixed, aspect-preserved image (single URL, no oversized
										// srcset) so the browser never downloads the full original. wpimage()
										// generates the size on demand even when the named size is missing.
										$thumb_id  = has_post_thumbnail() ? get_post_thumbnail_id() : 0;
										$thumb_url = $thumb_id ? wpimage( image: $thumb_id, size: array( 220, 220 ), retina: true, quality: 85 ) : '';
										?>
										<?php if ( $thumb_url ) : ?>
											<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-contain p-3" loading="lazy" decoding="async">
										<?php else : ?>
											<span class="flex w-full h-full items-center justify-center text-gray-300">
												<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
											</span>
										<?php endif; ?>
									</span>
								</a>
								<div class="rfs-ref-blog-row-body flex flex-col flex-1 min-w-0">
									<?php
									$cats = get_the_category();
									if ( ! empty( $cats ) && 'uncategorised' !== $cats[0]->slug ) :
										?>
										<a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>" class="rfs-ref-blog-row-cat inline-flex self-start mb-2 text-[11px] font-semibold uppercase tracking-wide text-primary-700"><?php echo esc_html( $cats[0]->name ); ?></a>
									<?php endif; ?>
									<h2 class="rfs-ref-blog-row-title text-lg md:text-xl font-bold text-ats-dark leading-snug mb-2">
										<a href="<?php the_permalink(); ?>" class="hover:text-primary-700"><?php the_title(); ?></a>
									</h2>
									<p class="rfs-ref-blog-row-excerpt text-sm text-gray-600 leading-relaxed mb-4"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '' ) ); ?></p>
									<div class="rfs-ref-blog-row-foot flex items-center gap-4">
										<a href="<?php the_permalink(); ?>" class="rfs-ref-blog-row-more inline-flex items-center gap-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 transition-colors">Read more
											<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
										</a>
										<time class="rfs-ref-blog-row-date text-xs text-gray-400" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
									</div>
								</div>
							</article>
						<?php endwhile; ?>
					</div>

					<div class="rfs-ref-blog-pagination mt-12">
						<?php
						the_posts_pagination( array(
							'mid_size'  => 1,
							'prev_text' => __( 'Previous', 'skylinewp-dev-child' ),
							'next_text' => __( 'Next', 'skylinewp-dev-child' ),
						) );
						?>
					</div>
				<?php else : ?>
					<p class="text-gray-600"><?php esc_html_e( 'No posts found.', 'skylinewp-dev-child' ); ?></p>
				<?php endif; ?>
			</main>

			<!-- Sidebar -->
			<aside class="rfs-ref-blog-aside lg:col-span-4">
				<div class="lg:sticky lg:top-4 space-y-6">

					<!-- Shop the range: best sellers -->
					<div class="rfs-ref-blog-bestsellers bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
						<div class="rfs-ref-blog-bestsellers-head bg-ats-dark px-5 py-4">
							<h3 class="text-white font-bold text-lg">Shop the range</h3>
							<p class="text-gray-300 text-sm mt-1">Our best-selling diamond tools.</p>
						</div>
						<div class="rfs-ref-blog-bestsellers-body p-3 divide-y divide-gray-100">
							<?php
							foreach ( $cta_ids as $pid ) :
								$p = wc_get_product( $pid );
								if ( ! $p ) {
									continue;
								}
								?>
								<a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" class="rfs-ref-blog-bs-item flex items-center gap-3 group p-2 rounded-lg hover:bg-gray-50 transition-colors">
									<span class="w-12 h-12 shrink-0 rounded border border-gray-200 overflow-hidden bg-gray-50">
										<?php echo $p->get_image( 'thumbnail', array( 'class' => 'w-full h-full object-cover' ) ); ?>
									</span>
									<span class="min-w-0 flex-1">
										<span class="block text-sm font-semibold text-ats-dark leading-snug line-clamp-2 group-hover:text-primary-700"><?php echo esc_html( $p->get_name() ); ?></span>
										<span class="block text-sm font-bold text-primary-700 mt-0.5"><?php echo wp_kses_post( $p->get_price_html() ); ?></span>
									</span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Newsletter sign-up -->
					<div class="rfs-ref-blog-newsletter rounded-lg border border-gray-200 bg-gray-50 p-5">
						<h3 class="text-ats-dark font-bold text-lg mb-1">Join the trade list</h3>
						<p class="text-gray-600 text-sm mb-3">Offers, new products and guides straight to your inbox.</p>
						<form class="rfs-ref-newsletter-form space-y-2" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ats_newsletter_subscribe' ) ); ?>">
							<input type="email" name="newsletter_email" placeholder="<?php esc_attr_e( 'Enter your email', 'skylinewp-dev-child' ); ?>" required class="rfs-ref-newsletter-email w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 p-2.5" />
							<button type="submit" class="rfs-ref-newsletter-submit w-full bg-primary-800 text-white hover:bg-primary-900 font-medium rounded-lg text-sm px-5 py-2.5 transition-colors"><?php esc_html_e( 'Subscribe', 'skylinewp-dev-child' ); ?></button>
							<div class="rfs-ref-newsletter-message text-sm" style="display:none;"></div>
						</form>
					</div>

					<!-- Shop call to action -->
					<div class="rfs-ref-blog-shopcta relative overflow-hidden rounded-lg bg-gradient-to-br from-primary-700 to-primary-900 text-white p-6">
						<div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-ats-yellow opacity-10 blur-2xl pointer-events-none"></div>
						<h3 class="relative font-bold text-xl mb-1">Tools for the job?</h3>
						<p class="relative text-sm text-white/80 mb-4">Browse the full range of professional diamond tools, in UK stock with fast dispatch.</p>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="relative inline-flex items-center gap-2 bg-ats-yellow text-ats-dark font-bold rounded-lg px-5 py-3 hover:bg-yellow-300 transition-colors">
							Shop all products
							<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
						</a>
					</div>

				</div>
			</aside>

		</div>
	</div>
</div>
<?php
get_footer();
