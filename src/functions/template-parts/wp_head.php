<?php

/**
 * Checks if the current request is for the 'nl' subdomain.
 *
 * @return bool True if the host starts with 'nl.', false otherwise.
 */
function is_nl_subdomain(): bool
{
	// HTTP_HOST includes port if present, so strip that off.
	$host = isset($_SERVER['HTTP_HOST'])
		? strtolower(explode(':', $_SERVER['HTTP_HOST'], 2)[0])
		: '';

	// Return true if it starts with 'nl.'
	return str_starts_with($host, 'nl.');
}

add_action('wp_head', 'avolve_scripts');

/**
 * Outputs tracking and marketing scripts in the <head> tag.
 *
 * Skips output if WP_DEBUG is enabled.
 */
function avolve_scripts()
{
	if (defined('WP_DEBUG') && WP_DEBUG) {
		return;
	}

	// Determine the correct Google Analytics ID based on the subdomain.
	$google_analytics_id = is_nl_subdomain() ? 'G-TR8G243G23' : 'G-V61CWBYJFH';
?>
	<!-- Termly -->
	<script type="text/javascript" src="https://app.termly.io/resource-blocker/e0bb0ca7-a07f-45cf-a2ec-073c55e2a11a?autoBlock=on"></script>
	<!-- 6sense -->
	<script id="6senseWebTag" src="https://j.6sc.co/j/aefa9dc0-8bd7-4646-ac9b-5423ec00fd4c.js"></script>
	<!-- Propensity -->
	<script src="https://cdn.propensity.com/propensity/propensity_analytics.js" crossorigin="anonymous"></script>
	<script type="text/javascript">
		propensity("propensity-003915");
	</script>
	<!-- Fastbase -->
	<script>
		(function() {
			var e, i = ["https://www.fastbase.com/fscript.js", "JhB8yux3la", "script"],
				a = document,
				s = a.createElement(i[2]);
			s.async = !0, s.id = i[1], s.src = i[0], (e = a.getElementsByTagName(i[2])[0]).parentNode.insertBefore(s, e)
		})();
	</script>
	<!-- HubSpot -->
	<script type="text/javascript" id="hs-script-loader" async defer src="//js.hs-scripts.com/23531946.js"></script>
	<!-- Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($google_analytics_id); ?>"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag() { dataLayer.push(arguments); }
		gtag('js', new Date());
		gtag('config', '<?php echo esc_js($google_analytics_id); ?>');
	</script>
<?php
}