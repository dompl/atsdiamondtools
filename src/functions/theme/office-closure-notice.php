<?php
/**
 * Temporary Office Closure Notice
 *
 * Site-wide announcement strip for the August 2026 stock-take closure.
 *
 * Self-expiring by design: everything in this file is gated on a fixed
 * date window in UK local time. Once the window passes, nothing renders,
 * the clearance bar comes back on its own, and no manual step is needed.
 *
 * There is deliberately no admin screen. To reuse the notice for another
 * closure, edit the window and message in ats_closure_notice_config().
 *
 * Cache safety: the site runs WP Rocket, so a page generated during the
 * window keeps the markup after expiry until it is regenerated. Two
 * belts-and-braces measures cover that:
 *   1. a one-shot cron event purges the Rocket cache at expiry;
 *   2. a tiny inline script removes the strip client-side if a stale
 *      cached page is served before the purge lands.
 *
 * Renders on `wp_body_open`, i.e. the first element inside <body>, so it
 * pushes the page down rather than overlaying the header.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notice window and copy.
 *
 * Window is inclusive of `start` and exclusive of `end`, both read in the
 * `timezone` given, independent of the WordPress timezone setting.
 *
 * @return array{timezone:string,start:string,end:string,message:string}
 */
function ats_closure_notice_config() {
	return [
		'timezone' => 'Europe/London',
		'start'    => '2026-08-13 00:00:00',
		'end'      => '2026-08-18 00:01:00',
		'message'  => 'Our office closes at midday on <strong>Friday 14th August</strong> for stock take and reopens <strong>Monday afternoon</strong>. Orders placed after midday on Friday will be dispatched on Monday.',
	];
}

/**
 * Resolve the end of the notice window as a UTC timestamp.
 *
 * @return int Unix timestamp, or 0 if the configured window is unparseable.
 */
function ats_closure_notice_end_timestamp() {
	static $timestamp = null;

	if ( null !== $timestamp ) {
		return $timestamp;
	}

	$config = ats_closure_notice_config();

	try {
		$zone      = new DateTimeZone( $config['timezone'] );
		$timestamp = ( new DateTimeImmutable( $config['end'], $zone ) )->getTimestamp();
	} catch ( Exception $e ) {
		$timestamp = 0;
	}

	return $timestamp;
}

/**
 * Whether the notice window is currently open.
 *
 * @return bool
 */
function ats_closure_notice_is_active() {
	static $active = null;

	if ( null !== $active ) {
		return $active;
	}

	$config = ats_closure_notice_config();

	try {
		$zone  = new DateTimeZone( $config['timezone'] );
		$now   = new DateTimeImmutable( 'now', $zone );
		$start = new DateTimeImmutable( $config['start'], $zone );
		$end   = new DateTimeImmutable( $config['end'], $zone );

		$active = ( $now >= $start && $now < $end );
	} catch ( Exception $e ) {
		// A bad window must never leave a stale notice on the site.
		$active = false;
	}

	return $active;
}

/**
 * Suppress the clearance top bar while the closure notice is showing.
 *
 * Only one full-width strip should sit above the header. The clearance bar
 * returns automatically once the window closes.
 *
 * @param bool $should_render Whether the clearance bar would render.
 * @return bool
 */
function ats_closure_notice_replace_clearance_bar( $should_render ) {
	return ats_closure_notice_is_active() ? false : $should_render;
}
add_filter( 'ats_clearance_bar_should_render', 'ats_closure_notice_replace_clearance_bar' );

/**
 * Queue a single cache purge for the moment the notice expires.
 *
 * Scheduled a minute past the window so the regenerated pages are built
 * with the notice already inactive.
 */
function ats_closure_notice_schedule_expiry_purge() {
	if ( wp_next_scheduled( 'ats_closure_notice_expired' ) ) {
		return;
	}

	$end = ats_closure_notice_end_timestamp();

	if ( ! $end ) {
		return;
	}

	wp_schedule_single_event( $end + MINUTE_IN_SECONDS, 'ats_closure_notice_expired' );
}

/**
 * Flush the page cache so the notice stops being served from WP Rocket.
 */
function ats_closure_notice_purge_cache() {
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
}
add_action( 'ats_closure_notice_expired', 'ats_closure_notice_purge_cache' );

/**
 * Render the notice strip.
 */
function ats_closure_notice_render() {
	if ( ! ats_closure_notice_is_active() ) {
		return;
	}

	ats_closure_notice_schedule_expiry_purge();

	$config  = ats_closure_notice_config();
	$message = wp_kses( $config['message'], [ 'strong' => [], 'br' => [] ] );
	$end_ms  = ats_closure_notice_end_timestamp() * 1000;
	?>
<style id="ats-closure-notice-css">
.ats-closure-notice{position:relative;z-index:30;color:#fff;background:linear-gradient(90deg,#594652 0%,#57434e 100%)}
.ats-closure-notice__inner{display:flex;align-items:center;justify-content:center;gap:12px;max-width:1440px;margin:0 auto;padding:11px 24px;font-size:14px;font-weight:600;line-height:1.45;text-align:center;letter-spacing:.01em}
.ats-closure-notice__icon{flex-shrink:0;width:19px;height:19px;color:#ffd902}
.ats-closure-notice__text strong{font-weight:800;color:#ffd902}
@media (max-width:768px){.ats-closure-notice__inner{gap:9px;padding:10px 16px;font-size:13px}}
@media (max-width:480px){.ats-closure-notice__icon{display:none}}
</style>
<div id="ats-closure-notice" class="ats-closure-notice" role="status">
	<div class="ats-closure-notice__inner">
		<svg class="ats-closure-notice__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 24 24">
			<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
		</svg>
		<span class="ats-closure-notice__text"><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped via wp_kses above. ?></span>
	</div>
</div>
<script id="ats-closure-notice-js">
/* Safety net: drop the strip if a page cached during the window is served after it. */
(function(){try{if(Date.now()>=<?php echo (int) $end_ms; ?>){var n=document.getElementById('ats-closure-notice'),s=document.getElementById('ats-closure-notice-css');if(n&&n.parentNode){n.parentNode.removeChild(n);}if(s&&s.parentNode){s.parentNode.removeChild(s);}}}catch(e){}})();
</script>
	<?php
}
add_action( 'wp_body_open', 'ats_closure_notice_render' );
