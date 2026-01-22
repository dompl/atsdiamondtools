<?php
/**
 * Review Comments Template
 *
 * Custom styled version with Tailwind CSS
 *
 * @package skylinewp-dev-child
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<li <?php comment_class('rfs-ref-review-item bg-white border border-gray-200 rounded-lg p-3 hover:shadow-sm transition-shadow'); ?> id="li-comment-<?php comment_ID(); ?>">

	<div id="comment-<?php comment_ID(); ?>" class="rfs-ref-review-container comment_container flex gap-4">

		<?php
		/**
		 * The woocommerce_review_before hook
		 *
		 * @hooked woocommerce_review_display_gravatar - 10
		 */
		echo '<div class="rfs-ref-review-avatar flex-shrink-0">';
		echo get_avatar( $comment, 60, '', '', array( 'class' => 'rounded-full border-2 m-0 border-gray-200' ) );
		echo '</div>';
		?>

		<div class="rfs-ref-review-content comment-text flex-grow min-w-0">

			<?php
			/**
			 * The woocommerce_review_before_comment_meta hook.
			 *
			 * @hooked woocommerce_review_display_rating - 10
			 */
			do_action( 'woocommerce_review_before_comment_meta', $comment );

			/**
			 * The woocommerce_review_meta hook.
			 *
			 * @hooked woocommerce_review_display_meta - 10
			 */
			do_action( 'woocommerce_review_meta', $comment );

			do_action( 'woocommerce_review_before_comment_text', $comment );

			/**
			 * The woocommerce_review_comment_text hook
			 *
			 * @hooked woocommerce_review_display_comment_text - 10
			 */
			do_action( 'woocommerce_review_comment_text', $comment );

			do_action( 'woocommerce_review_after_comment_text', $comment );
			?>

		</div>
	</div>
