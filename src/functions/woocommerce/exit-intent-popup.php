<?php
/**
 * Exit-intent popup — free UK postage over £100.
 *
 * Shows a modal when a desktop visitor moves to leave the page (cursor exits the
 * top of the viewport), once per browser session. Promotes free UK postage over
 * £100 with the checkout code. Self-contained: markup + inline CSS/JS in the
 * footer; not shown in admin, cart or checkout.
 *
 * @package skylinewp-dev-child
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ATS_EXIT_POPUP_CODE' ) ) {
	define( 'ATS_EXIT_POPUP_CODE', 'free24' );
}

/**
 * Render the exit-intent popup in the footer.
 *
 * @return void
 */
function ats_exit_intent_popup_render() {
	if ( is_admin() ) {
		return;
	}
	// Don't interrupt the checkout flow.
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return;
	}

	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$code = ATS_EXIT_POPUP_CODE;
	?>
	<div class="ats-exit-popup-overlay" data-exit-popup hidden>
		<div class="ats-exit-popup" role="dialog" aria-modal="true" aria-labelledby="ats-exit-popup-title">
			<button type="button" class="ats-exit-popup__close" data-exit-close aria-label="<?php esc_attr_e( 'Close', 'woocommerce' ); ?>">&times;</button>
			<span class="ats-exit-popup__tag"><?php esc_html_e( "Wait — don't leave empty-handed", 'woocommerce' ); ?></span>
			<h2 id="ats-exit-popup-title" class="ats-exit-popup__title"><?php esc_html_e( 'Free UK postage over £100', 'woocommerce' ); ?></h2>
			<p class="ats-exit-popup__text"><?php esc_html_e( 'Before you go — stock up and save on delivery. Spend £100 or more and enter this code at the basket for free UK postage:', 'woocommerce' ); ?></p>
			<div class="ats-exit-popup__codebox">
				<span class="ats-exit-popup__code" data-exit-code><?php echo esc_html( $code ); ?></span>
				<button type="button" class="ats-exit-popup__copy" data-exit-copy><?php esc_html_e( 'Copy', 'woocommerce' ); ?></button>
			</div>
			<a class="ats-exit-popup__cta" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Shop now', 'woocommerce' ); ?></a>
			<p class="ats-exit-popup__small"><?php esc_html_e( 'UK mainland orders over £100. Apply the code in your basket before checkout.', 'woocommerce' ); ?></p>
		</div>
	</div>
	<style id="ats-exit-popup-css"><?php echo ats_exit_intent_popup_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS. ?></style>
	<script id="ats-exit-popup-js"><?php echo ats_exit_intent_popup_js(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static JS. ?></script>
	<?php
}
add_action( 'wp_footer', 'ats_exit_intent_popup_render' );

/**
 * Popup CSS.
 *
 * @return string
 */
function ats_exit_intent_popup_css() {
	return <<<'CSS'
.ats-exit-popup-overlay{position:fixed;inset:0;z-index:99998;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(40,28,42,.6);opacity:0;visibility:hidden;transition:opacity .25s ease}
.ats-exit-popup-overlay[hidden]{display:none}
.ats-exit-popup-overlay.is-open{opacity:1;visibility:visible}
.ats-exit-popup{position:relative;background:#fff;border-radius:10px;max-width:440px;width:100%;padding:38px 30px 28px;text-align:center;box-shadow:0 24px 60px -12px rgba(0,0,0,.45);transform:translateY(12px);transition:transform .25s ease}
.ats-exit-popup-overlay.is-open .ats-exit-popup{transform:none}
.ats-exit-popup__close{position:absolute;top:8px;right:12px;border:none;background:none;font-size:30px;line-height:1;color:#9a9a9a;cursor:pointer;padding:4px}
.ats-exit-popup__close:hover{color:#373737}
.ats-exit-popup__tag{display:inline-block;background:#FFD902;color:#373737;font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;padding:5px 12px;border-radius:4px}
.ats-exit-popup__title{font-size:1.55rem;font-weight:800;color:#594652;margin:14px 0 8px;line-height:1.2}
.ats-exit-popup__text{font-size:.95rem;color:#5b5b5b;line-height:1.5;margin:0 0 18px}
.ats-exit-popup__codebox{display:flex;align-items:stretch;justify-content:center;max-width:300px;margin:0 auto 18px;border:2px dashed #594652;border-radius:6px;overflow:hidden}
.ats-exit-popup__code{flex:1;display:flex;align-items:center;justify-content:center;font-size:1.15rem;font-weight:800;letter-spacing:.05em;color:#373737;padding:11px 12px}
.ats-exit-popup__copy{border:none;background:#594652;color:#fff;font-weight:700;font-size:.85rem;padding:0 18px;cursor:pointer;white-space:nowrap}
.ats-exit-popup__copy:hover{background:#4a3944}
.ats-exit-popup__cta{display:inline-block;background:#FFD902;color:#373737;font-weight:800;text-transform:uppercase;letter-spacing:.04em;font-size:.9rem;padding:12px 30px;border-radius:6px;text-decoration:none}
.ats-exit-popup__cta:hover{background:#e6c402}
.ats-exit-popup__small{font-size:.72rem;color:#9a9a9a;margin:16px 0 0;line-height:1.4}
CSS;
}

/**
 * Popup JS — exit-intent trigger, once per session, copy code, close handlers.
 *
 * @return string
 */
function ats_exit_intent_popup_js() {
	return <<<'JS'
(function(){
	var KEY='ats_exit_popup_seen';
	function init(){
		var overlay=document.querySelector('[data-exit-popup]');
		if(!overlay){return;}
		var armed=false, done=false;
		// Arm after a short delay so it can't fire the instant the page loads.
		setTimeout(function(){ armed=true; }, 3000);
		function open(){
			if(done||!armed){return;}
			try{ if(sessionStorage.getItem(KEY)){return;} }catch(e){}
			done=true;
			try{ sessionStorage.setItem(KEY,'1'); }catch(e){}
			overlay.removeAttribute('hidden');
			requestAnimationFrame(function(){ overlay.classList.add('is-open'); });
			document.body.style.overflow='hidden';
		}
		function close(){
			overlay.classList.remove('is-open');
			document.body.style.overflow='';
			setTimeout(function(){ overlay.setAttribute('hidden',''); }, 250);
		}
		// Exit intent: cursor leaves through the top of the viewport.
		document.addEventListener('mouseout', function(e){
			if(e.clientY<=0 && !e.relatedTarget && !e.toElement){ open(); }
		});
		overlay.addEventListener('click', function(e){
			if(e.target===overlay || (e.target.closest && e.target.closest('[data-exit-close]'))){ close(); }
		});
		document.addEventListener('keydown', function(e){ if(e.key==='Escape' && overlay.classList.contains('is-open')){ close(); } });
		var copyBtn=overlay.querySelector('[data-exit-copy]');
		if(copyBtn){
			copyBtn.addEventListener('click', function(){
				var el=overlay.querySelector('[data-exit-code]');
				var code=el?el.textContent.trim():'';
				var ok=function(){ var t=copyBtn.getAttribute('data-label')||copyBtn.textContent; copyBtn.setAttribute('data-label',t); copyBtn.textContent='Copied!'; setTimeout(function(){ copyBtn.textContent=t; }, 1600); };
				if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(code).then(ok).catch(ok); } else { ok(); }
			});
		}
	}
	if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
JS;
}
