<?php
/**
 * Display single product reviews (comments)
 *
 * Custom styled version with Tailwind CSS
 *
 * @package skylinewp-dev-child
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	return;
}

?>
<div id="reviews" class="rfs-ref-reviews woocommerce-Reviews">
	<div id="comments" class="rfs-ref-reviews-comments">
		<h2 class="rfs-ref-reviews-title text-2xl font-bold text-ats-dark mb-6">
			<?php
			$count = $product->get_review_count();
			if ( $count && wc_review_ratings_enabled() ) {
				/* translators: 1: reviews count 2: product name */
				$reviews_title = sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $count, 'woocommerce' ) ), esc_html( $count ), '<span class="text-ats-brand">' . get_the_title() . '</span>' );
				echo apply_filters( 'woocommerce_reviews_title', $reviews_title, $count, $product ); // WPCS: XSS ok.
			} else {
				esc_html_e( 'Reviews', 'woocommerce' );
			}
			?>
		</h2>

		<?php if ( have_comments() ) : ?>
            <div class="space-y-6 mb-8" id="reviews-list-container">
    			<ol class="rfs-ref-reviews-list commentlist space-y-6" id="reviews-list">
    				<?php
					// Set 5 comments per page
					$comments_per_page = 5;
					$paged = get_query_var('cpage') ? get_query_var('cpage') : 1;

					wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array(
						'callback' => 'woocommerce_comments',
						'per_page' => $comments_per_page,
					) ) );
					?>
    			</ol>
            </div>

			<?php
			$total_comments = get_comments_number();
			$total_pages = ceil( $total_comments / 5 );

			if ( $total_pages > 1 ) :
				?>
				<nav class="rfs-ref-reviews-pagination woocommerce-pagination flex items-center justify-center gap-2 my-8" id="reviews-pagination" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
					<?php
					$current_page = max( 1, get_query_var('cpage') );

					// Previous button
					if ( $current_page > 1 ) :
						?>
						<button class="ats-reviews-prev page-numbers inline-flex items-center justify-center w-8 h-8 rounded border border-gray-200 hover:bg-gray-50 text-ats-text" data-page="<?php echo esc_attr( $current_page - 1 ); ?>">
							<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
						</button>
					<?php endif; ?>

					<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
						<button class="ats-reviews-page page-numbers inline-flex items-center justify-center w-8 h-8 rounded border border-gray-200 hover:bg-gray-50 text-ats-text <?php echo $i === $current_page ? 'current bg-ats-yellow text-ats-dark border-ats-yellow font-semibold' : ''; ?>" data-page="<?php echo esc_attr( $i ); ?>">
							<?php echo $i; ?>
						</button>
					<?php endfor; ?>

					<?php
					// Next button
					if ( $current_page < $total_pages ) :
						?>
						<button class="ats-reviews-next page-numbers inline-flex items-center justify-center w-8 h-8 rounded border border-gray-200 hover:bg-gray-50 text-ats-text" data-page="<?php echo esc_attr( $current_page + 1 ); ?>">
							<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
						</button>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		<?php else : ?>
			<div class="rfs-ref-no-reviews woocommerce-noreviews bg-ats-gray border border-gray-200 rounded-lg p-8 text-center">
				<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#9CA3AF" class="mx-auto mb-4 opacity-50">
					<path d="m363-390 117-71 117 71-31-133 104-90-137-11-53-126-53 126-137 11 104 90-31 133ZM80-80v-720q0-33 23.5-56.5T160-880h640q33 0 56.5 23.5T880-800v480q0 33-23.5 56.5T800-240H240L80-80Zm126-240h594v-480H160v525l46-45Zm-46 0v-480 480Z"/>
				</svg>
				<p class="text-ats-text text-lg"><?php esc_html_e( 'There are no reviews yet.', 'woocommerce' ); ?></p>
				<p class="text-ats-text text-sm mt-2"><?php esc_html_e( 'Be the first to review this product!', 'woocommerce' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>
		<div id="review_form_wrapper" class="rfs-ref-review-form-wrapper mt-6">
			<div id="review_form" class="rfs-ref-review-form bg-white border border-gray-200 rounded-lg p-8">
				<?php
				$commenter    = wp_get_current_commenter();
				$comment_form = array(
					/* translators: %s is product title */
					'title_reply'         => have_comments() ? esc_html__( 'Add a review', 'woocommerce' ) : sprintf( esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'woocommerce' ), get_the_title() ),
					/* translators: %s is product title */
					'title_reply_to'      => esc_html__( 'Leave a Reply to %s', 'woocommerce' ),
					'title_reply_before'  => '<h3 id="reply-title" class="rfs-ref-review-form-title comment-reply-title text-xl font-bold text-ats-dark mb-6 mt-0" role="heading" aria-level="3">',
					'title_reply_after'   => '</h3>',
					'comment_notes_after' => '',
					'label_submit'        => esc_html__( 'Submit Review', 'woocommerce' ),
					'logged_in_as'        => '',
					'comment_field'       => '',
					'class_container'     => 'rfs-ref-review-form-container comment-respond',
					'class_form'          => 'rfs-ref-review-form-element comment-form',
					'class_submit'        => 'rfs-ref-review-submit-btn submit ats-btn ats-btn-md ats-btn-yellow w-full sm:w-auto',
					'submit_button'       => '<button type="submit" name="%1$s" id="%2$s" class="%3$s">%4$s</button>',
				);

				$name_email_required = (bool) get_option( 'require_name_email', 1 );

				// Build name and email fields in one row
				$comment_form['fields'] = array();

				// Name and Email fields in one row
				$name_email_html = '<div class="rfs-ref-review-name-email-row grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">';

				// Name field
				$name_email_html .= '<div class="rfs-ref-review-field-author">';
				$name_email_html .= '<label for="author" class="block text-sm font-medium text-ats-dark mb-2">' . esc_html__( 'Name', 'woocommerce' );
				if ( $name_email_required ) {
					$name_email_html .= '&nbsp;<span class="required text-red-500">*</span>';
				}
				$name_email_html .= '</label>';
				$name_email_html .= '<input id="author" name="author" type="text" autocomplete="name" value="' . esc_attr( $commenter['comment_author'] ) . '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow transition-colors" ' . ( $name_email_required ? 'required' : '' ) . ' />';
				$name_email_html .= '<p class="text-red-500 text-sm mt-1 hidden" id="author-error">' . esc_html__( 'Please enter your name', 'woocommerce' ) . '</p>';
				$name_email_html .= '</div>';

				// Email field
				$name_email_html .= '<div class="rfs-ref-review-field-email">';
				$name_email_html .= '<label for="email" class="block text-sm font-medium text-ats-dark mb-2">' . esc_html__( 'Email', 'woocommerce' );
				if ( $name_email_required ) {
					$name_email_html .= '&nbsp;<span class="required text-red-500">*</span>';
				}
				$name_email_html .= '</label>';
				$name_email_html .= '<input id="email" name="email" type="email" autocomplete="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow transition-colors" ' . ( $name_email_required ? 'required' : '' ) . ' />';
				$name_email_html .= '<p class="text-red-500 text-sm mt-1 hidden" id="email-error">' . esc_html__( 'Please enter a valid email', 'woocommerce' ) . '</p>';
				$name_email_html .= '</div>';

				$name_email_html .= '</div>';

				$comment_form['fields']['name_email_row'] = $name_email_html;

				$account_page_url = wc_get_page_permalink( 'myaccount' );
				if ( $account_page_url ) {
					/* translators: %s opening and closing link tags respectively */
					$comment_form['must_log_in'] = '<p class="must-log-in bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-sm text-blue-800">' . sprintf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'woocommerce' ), '<a href="' . esc_url( $account_page_url ) . '" class="font-medium underline hover:text-blue-900">', '</a>' ) . '</p>';
				}

				if ( wc_review_ratings_enabled() ) {
					$comment_form['comment_field'] = '<div class="rfs-ref-review-rating-field comment-form-rating mb-6">
						<label for="rating" id="comment-form-rating-label" class="block text-sm font-medium text-ats-dark mb-2">' . esc_html__( 'Your rating', 'woocommerce' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required text-red-500">*</span>' : '' ) . '</label>
						<div class="flex items-center gap-1 mb-1">
							<button type="button" class="ats-star-rating-btn text-lg transition-colors duration-200 text-gray-300 hover:text-ats-yellow focus:outline-none" data-rating="1" aria-label="' . esc_attr__( '1 star', 'woocommerce' ) . '">★</button>
							<button type="button" class="ats-star-rating-btn text-lg transition-colors duration-200 text-gray-300 hover:text-ats-yellow focus:outline-none" data-rating="2" aria-label="' . esc_attr__( '2 stars', 'woocommerce' ) . '">★</button>
							<button type="button" class="ats-star-rating-btn text-lg transition-colors duration-200 text-gray-300 hover:text-ats-yellow focus:outline-none" data-rating="3" aria-label="' . esc_attr__( '3 stars', 'woocommerce' ) . '">★</button>
							<button type="button" class="ats-star-rating-btn text-lg transition-colors duration-200 text-gray-300 hover:text-ats-yellow focus:outline-none" data-rating="4" aria-label="' . esc_attr__( '4 stars', 'woocommerce' ) . '">★</button>
							<button type="button" class="ats-star-rating-btn text-lg transition-colors duration-200 text-gray-300 hover:text-ats-yellow focus:outline-none" data-rating="5" aria-label="' . esc_attr__( '5 stars', 'woocommerce' ) . '">★</button>
						</div>
						<input type="hidden" name="rating" id="rating" value="" required>
						<p class="text-red-500 text-sm mt-1 hidden" id="rating-error">' . esc_html__( 'Please select a rating', 'woocommerce' ) . '</p>
					</div>';
				}

				$comment_form['comment_field'] .= '<p class="rfs-ref-review-comment-field comment-form-comment mb-6">
					<label for="comment" class="block text-sm font-medium text-ats-dark mb-2">' . esc_html__( 'Your review', 'woocommerce' ) . '&nbsp;<span class="required text-red-500">*</span></label>
					<textarea id="comment" name="comment" cols="45" rows="8" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ats-yellow focus:border-ats-yellow transition-colors resize-vertical" placeholder="' . esc_attr__( 'Share your thoughts about this product...', 'woocommerce' ) . '"></textarea>
					<p class="text-red-500 text-sm mt-1 hidden" id="comment-error">' . esc_html__( 'Please enter your review', 'woocommerce' ) . '</p>
				</p>';

				comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
				?>
			</div>
		</div>
	<?php else : ?>
		<div class="rfs-ref-review-verification-required woocommerce-verification-required bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mt-8">
			<svg xmlns="http://www.w3.org/2000/svg" height="32px" viewBox="0 -960 960 960" width="32px" fill="#F59E0B" class="mx-auto mb-3">
				<path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
			</svg>
			<p class="text-ats-text font-medium"><?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'woocommerce' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="clear"></div>
</div>
