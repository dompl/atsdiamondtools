<?php
/**
 * The template to display the reviewers star rating in reviews
 *
 * Custom styled version with Tailwind CSS
 *
 * @package skylinewp-dev-child
 * @version 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $comment;
$rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );

if ( $rating && wc_review_ratings_enabled() ) {
	echo '<div class="rfs-ref-review-rating flex items-center gap-1 mb-2">';
	echo ats_get_star_rating_html( $rating, 0, 'text-ats-yellow text-base' );
	echo '</div>';
}
