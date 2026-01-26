<?php
/**
 * "Order received" message.
 *
 * Styled with Tailwind CSS
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package skylinewp-dev-child
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="rfs-ref-order-received-message bg-white border border-green-200 rounded-lg p-8 text-center">
	<div class="rfs-ref-success-icon mb-4">
		<svg class="w-16 h-16 mx-auto text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
			<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
		</svg>
	</div>
	<p class="rfs-ref-order-received-text woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received text-lg font-semibold text-green-800 mb-0">
		<?php
		/**
		 * Filter the message shown after a checkout is complete.
		 *
		 * @since 2.2.0
		 *
		 * @param string         $message The message.
		 * @param WC_Order|false $order   The order created during checkout, or false if order data is not available.
		 */
		$message = apply_filters(
			'woocommerce_thankyou_order_received_text',
			esc_html( __( 'Thank you. Your order has been received.', 'woocommerce' ) ),
			$order
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $message;
		?>
	</p>
</div>
