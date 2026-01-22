<?php
/**
 * The template to display the reviewers meta data (name, verified owner, review date)
 *
 * Custom styled version with Tailwind CSS
 *
 * @package skylinewp-dev-child
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $comment;
$verified = wc_review_is_from_verified_owner( $comment->comment_ID );

if ( '0' === $comment->comment_approved ) { ?>

	<div class="rfs-ref-review-meta meta bg-yellow-50 border border-yellow-200 rounded-lg p-5 mb-3">
		<em class="woocommerce-review__awaiting-approval text-sm text-yellow-800 flex items-center gap-2">
			<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#92400E">
				<path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
			</svg>
			<?php esc_html_e( 'Your review is awaiting approval', 'woocommerce' ); ?>
		</em>
	</div>

<?php } else { ?>

	<div class="rfs-ref-review-meta meta flex flex-wrap items-center gap-2 mb-0 text-sm text-ats-text prose prose-sm">
		<strong class="rfs-ref-review-author woocommerce-review__author text-ats-dark font-semibold"><?php comment_author(); ?></strong>
		<?php
		if ( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && $verified ) {
			echo '<span class="rfs-ref-review-verified woocommerce-review__verified verified inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">
				<svg xmlns="http://www.w3.org/2000/svg" height="12px" viewBox="0 -960 960 960" width="12px" fill="currentColor">
					<path d="m344-60-76-128-144-32 14-148-98-112 98-112-14-148 144-32 76-128 136 58 136-58 76 128 144 32-14 148 98 112-98 112 14 148-144 32-76 128-136-58-136 58Zm34-102 102-44 104 44 56-96 110-26-10-112 74-84-74-86 10-112-110-24-58-96-102 44-104-44-56 96-110 24 10 112-74 86 74 84-10 114 110 24 58 96Zm102-318Zm-42 142 226-226-56-58-170 170-86-84-56 56 142 142Z"/>
				</svg>
				' . esc_attr__( 'Verified owner', 'woocommerce' ) . '
			</span>';
		}
		?>
		<span class="rfs-ref-review-dash woocommerce-review__dash text-gray-400">•</span>
		<time class="rfs-ref-review-date woocommerce-review__published-date" datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>">
			<?php echo esc_html( get_comment_date( wc_date_format() ) ); ?>
		</time>
	</div>

	<?php
}
