# Clearance Pop-up Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a brand-native, client-editable clearance announcement modal that appears site-wide (with exclusions) and drives clicks to the clearance category.

**Architecture:** PHP gates eligibility and renders markup from ACF options; the theme's existing Flowbite `Modal` controller handles backdrop/Esc/focus-trap/scroll-lock; a thin JS module adds the trigger delay and frequency capping; a dedicated SCSS partial styles the split-card.

**Tech Stack:** WordPress + WooCommerce, Extended ACF (Pro), Flowbite ^3.1.2 modal, Tailwind v3 utilities + SCSS partials, Gulp build, jQuery available (not required here).

## Global Constraints

- Child theme only (`skylinewp-dev-child` → dist `atsdiamondtools-child`). Never edit the parent theme. A parent update must not remove this feature.
- Reference the clearance category by **slug** `clearance`, never the numeric ID (`3735`).
- Escape all output: `esc_html` / `esc_attr` / `esc_url`, `wp_kses_post` for rich description.
- Function prefix `ats_` / option fields prefixed `clearance_popup_`.
- Extended ACF: use `->default()` (not `->defaultValue()`); **do not** call `->default()`/`->stylisedUi()` on `TrueFalse`.
- Do NOT run `gulp dist` (production deploy + version bump + push) without explicit go-ahead. The dev watcher compiles automatically.

**Testing reality:** this theme has no PHP/JS unit-test harness and its JS components are not unit-tested. Verification is therefore `php -l` (syntax), targeted `eslint`, and a manual/Playwright QA pass — not fabricated unit tests. This is a deliberate fit to the codebase.

---

### Task 1: ACF options sub-page + fields

**Files:**
- Create: `src/functions/acf/options/clearance-popup-settings.php`

**Interfaces:**
- Produces: an ACF options sub-page `clearance-popup-settings` (under `ats-settings`) exposing option fields `clearance_popup_enabled` (bool), `clearance_popup_tag`, `clearance_popup_heading`, `clearance_popup_description`, `clearance_popup_button_label`, `clearance_popup_link`, `clearance_popup_image` (attachment ID), `clearance_popup_delay` (int seconds), `clearance_popup_frequency_mode` (`session`|`days`), `clearance_popup_frequency_days` (int). Read later via `get_field('<name>', 'option')`.

- [ ] **Step 1: Create the options file**

```php
<?php
/**
 * Clearance Pop-up Settings
 *
 * Registers a "Clearance Pop-up" options sub-page (under ATS Settings) that
 * lets staff control the site-wide clearance announcement modal: copy, image,
 * link, trigger delay and how often it reappears.
 *
 * @package skylinewp-dev-child
 */

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Number;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Location;

// Register the options sub-page under the existing "ATS Settings" menu.
if ( function_exists( 'acf_add_options_sub_page' ) ) {
	acf_add_options_sub_page(
		[
			'page_title'  => 'Clearance Pop-up',
			'menu_title'  => 'Clearance Pop-up',
			'menu_slug'   => 'clearance-popup-settings',
			'parent_slug' => 'ats-settings',
			'capability'  => 'manage_options',
		]
	);
}

if ( ! function_exists( 'register_ats_clearance_popup_options' ) ) {
	/**
	 * Register the Clearance Pop-up field group.
	 */
	function register_ats_clearance_popup_options() {
		if ( ! function_exists( 'register_extended_field_group' ) ) {
			return;
		}

		register_extended_field_group(
			[
				'title'    => 'Clearance Pop-up',
				'key'      => 'group_clearance_popup',
				'fields'   => [
					TrueFalse::make( 'Enable Pop-up', 'clearance_popup_enabled' )
						->helperText( 'Master switch. When off, the pop-up never appears anywhere on the site.' ),

					Text::make( 'Tag (optional)', 'clearance_popup_tag' )
						->helperText( 'Small label above the heading, e.g. "LIMITED STOCK". Leave blank to hide.' ),

					Text::make( 'Heading', 'clearance_popup_heading' )
						->helperText( 'Main headline.' )
						->default( 'Clearance Sale Now On' ),

					Textarea::make( 'Description', 'clearance_popup_description' )
						->helperText( 'One or two sentences of supporting copy.' )
						->rows( 3 )
						->default( 'Genuine diamond tools at clearance prices — limited stock, while it lasts.' ),

					Text::make( 'Button Label', 'clearance_popup_button_label' )
						->helperText( 'Call-to-action button text.' )
						->default( 'Shop Clearance' ),

					Text::make( 'Button Link', 'clearance_popup_link' )
						->helperText( 'Where the button goes. Leave blank to default to the Clearance category page.' ),

					Image::make( 'Image', 'clearance_popup_image' )
						->helperText( 'Left-hand image (a clearance product photo or sale graphic). Leave blank for a text-only card.' )
						->format( 'id' ),

					Number::make( 'Delay (seconds)', 'clearance_popup_delay' )
						->helperText( 'How long after the page loads before the pop-up appears.' )
						->default( 2 ),

					Select::make( 'Show Frequency', 'clearance_popup_frequency_mode' )
						->helperText( 'How often a visitor sees the pop-up.' )
						->choices(
							[
								'session' => 'Once per browsing session',
								'days'    => 'Once every N days',
							]
						)
						->default( 'session' ),

					Number::make( 'Days Between Shows', 'clearance_popup_frequency_days' )
						->helperText( 'Used only with "Once every N days".' )
						->default( 30 )
						->conditionalLogic(
							[
								ConditionalLogic::where( 'clearance_popup_frequency_mode', '==', 'days' ),
							]
						),
				],
				'location' => [
					Location::where( 'options_page', '==', 'clearance-popup-settings' ),
				],
				'style'    => 'default',
			]
		);
	}
}

add_action( 'init', 'register_ats_clearance_popup_options', 20 );
```

- [ ] **Step 2: Syntax check**

Run: `php -l src/functions/acf/options/clearance-popup-settings.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add src/functions/acf/options/clearance-popup-settings.php
git commit -m "feat(clearance-popup): add ACF options sub-page + fields"
```

---

### Task 2: PHP eligibility gate + modal template part (+ footer include)

**Files:**
- Create: `src/functions/template-parts/clearance-popup-modal.php`
- Modify: `src/footer.php`

**Interfaces:**
- Consumes: `get_field('<name>', 'option')` from Task 1; `wpimage()` (parent helper); WooCommerce conditionals.
- Produces: a function `ats_clearance_popup_should_render(): bool` (filterable via `ats_clearance_popup_should_render`); DOM root `#ats-clearance-popup` with `data-delay`, `data-frequency-mode`, `data-frequency-days`, `data-storage-key="ats_clearance_popup_dismissed"`; close trigger `[data-modal-hide]`; panel `.ats-clearance-popup` (+ `.ats-clearance-popup--no-image` when no image).

- [ ] **Step 1: Create the template part**

```php
<?php
/**
 * Clearance Pop-up Modal
 *
 * Site-wide clearance announcement modal. Rendering is gated by
 * ats_clearance_popup_should_render(); behaviour (delay, frequency capping,
 * open/close, focus restore) lives in
 * assets/js/components/clearance-popup.js.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ats_clearance_popup_should_render' ) ) {
	/**
	 * Decide whether the clearance pop-up should render for this request.
	 *
	 * Suppressed when disabled, on the clearance category/products themselves,
	 * and on cart/checkout/account pages.
	 *
	 * @return bool
	 */
	function ats_clearance_popup_should_render() {
		$should = true;

		if ( ! function_exists( 'get_field' ) || ! get_field( 'clearance_popup_enabled', 'option' ) ) {
			$should = false;
		} elseif ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			$should = false;
		} elseif ( function_exists( 'is_product_category' ) && is_product_category( 'clearance' ) ) {
			$should = false;
		} elseif ( function_exists( 'is_product' ) && is_product() && has_term( 'clearance', 'product_cat', get_the_ID() ) ) {
			$should = false;
		}

		/**
		 * Filter whether the clearance pop-up renders for the current request.
		 *
		 * @param bool $should Whether to render.
		 */
		return (bool) apply_filters( 'ats_clearance_popup_should_render', $should );
	}
}

if ( ! ats_clearance_popup_should_render() ) {
	return;
}

// --- Gather settings -----------------------------------------------------
$ats_cp_tag         = (string) get_field( 'clearance_popup_tag', 'option' );
$ats_cp_heading     = (string) get_field( 'clearance_popup_heading', 'option' );
$ats_cp_description = (string) get_field( 'clearance_popup_description', 'option' );
$ats_cp_button      = (string) get_field( 'clearance_popup_button_label', 'option' );
$ats_cp_link        = get_field( 'clearance_popup_link', 'option' );
$ats_cp_image_id    = (int) get_field( 'clearance_popup_image', 'option' );
$ats_cp_delay       = (int) get_field( 'clearance_popup_delay', 'option' );
$ats_cp_freq_mode   = (string) get_field( 'clearance_popup_frequency_mode', 'option' );
$ats_cp_freq_days   = (int) get_field( 'clearance_popup_frequency_days', 'option' );

// Fallbacks.
if ( '' === $ats_cp_heading ) {
	$ats_cp_heading = 'Clearance Sale Now On';
}
if ( '' === $ats_cp_button ) {
	$ats_cp_button = 'Shop Clearance';
}
if ( empty( $ats_cp_link ) ) {
	$ats_cp_term = get_term_by( 'slug', 'clearance', 'product_cat' );
	$ats_cp_link = ( $ats_cp_term && ! is_wp_error( $ats_cp_term ) ) ? get_term_link( $ats_cp_term ) : home_url( '/product-category/clearance/' );
}
if ( is_wp_error( $ats_cp_link ) ) {
	$ats_cp_link = home_url( '/product-category/clearance/' );
}
if ( $ats_cp_delay < 0 ) {
	$ats_cp_delay = 2;
}
if ( '' === $ats_cp_freq_mode ) {
	$ats_cp_freq_mode = 'session';
}
if ( $ats_cp_freq_days < 1 ) {
	$ats_cp_freq_days = 30;
}

// Image (optional).
$ats_cp_image_url = '';
$ats_cp_image_alt = '';
if ( $ats_cp_image_id && function_exists( 'wpimage' ) ) {
	$ats_cp_image_url = (string) wpimage( $ats_cp_image_id, [ 600, 800 ], false, true );
	$ats_cp_image_alt = (string) get_post_meta( $ats_cp_image_id, '_wp_attachment_image_alt', true );
}

$ats_cp_has_image  = '' !== $ats_cp_image_url;
$ats_cp_panel_class = 'ats-clearance-popup' . ( $ats_cp_has_image ? '' : ' ats-clearance-popup--no-image' );
?>
<div
	id="ats-clearance-popup"
	class="ats-clearance-popup-overlay hidden"
	tabindex="-1"
	aria-hidden="true"
	data-delay="<?php echo esc_attr( $ats_cp_delay ); ?>"
	data-frequency-mode="<?php echo esc_attr( $ats_cp_freq_mode ); ?>"
	data-frequency-days="<?php echo esc_attr( $ats_cp_freq_days ); ?>"
	data-storage-key="ats_clearance_popup_dismissed"
>
	<div class="<?php echo esc_attr( $ats_cp_panel_class ); ?>" role="dialog" aria-modal="true" aria-labelledby="ats-clearance-popup-heading">
		<button type="button" class="ats-clearance-popup__close" data-modal-hide="ats-clearance-popup" aria-label="Close">
			<svg class="ats-clearance-popup__close-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14">
				<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
			</svg>
		</button>

		<?php if ( $ats_cp_has_image ) : ?>
			<div class="ats-clearance-popup__media">
				<img src="<?php echo esc_url( $ats_cp_image_url ); ?>" alt="<?php echo esc_attr( $ats_cp_image_alt ); ?>" />
			</div>
		<?php endif; ?>

		<div class="ats-clearance-popup__content">
			<?php if ( '' !== $ats_cp_tag ) : ?>
				<span class="ats-clearance-popup__tag"><?php echo esc_html( $ats_cp_tag ); ?></span>
			<?php endif; ?>

			<h2 id="ats-clearance-popup-heading" class="ats-clearance-popup__heading"><?php echo esc_html( $ats_cp_heading ); ?></h2>

			<?php if ( '' !== $ats_cp_description ) : ?>
				<div class="ats-clearance-popup__description"><?php echo wp_kses_post( wpautop( $ats_cp_description ) ); ?></div>
			<?php endif; ?>

			<a href="<?php echo esc_url( $ats_cp_link ); ?>" class="ats-clearance-popup__cta">
				<?php echo esc_html( $ats_cp_button ); ?>
			</a>
		</div>
	</div>
</div>
```

- [ ] **Step 2: Include it from the footer**

Modify `src/footer.php` — add the template part after the quick-view modal, before `wp_footer()`:

```php
<?php get_template_part( 'functions/template-parts/footer-main' ); ?>
<?php get_template_part( 'functions/template-parts/product-quick-view-modal' ); ?>
<?php get_template_part( 'functions/template-parts/clearance-popup-modal' ); ?>
<?php wp_footer(); ?>
</body>
</html>
```

- [ ] **Step 3: Syntax check**

Run: `php -l src/functions/template-parts/clearance-popup-modal.php && php -l src/footer.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Commit**

```bash
git add src/functions/template-parts/clearance-popup-modal.php src/footer.php
git commit -m "feat(clearance-popup): add gated modal template part + footer include"
```

---

### Task 3: SCSS split-card styling

**Files:**
- Create: `src/assets/scss/builds/components/_clearance-popup.scss`
- Modify: `src/assets/scss/builds/components/_index.scss`

**Interfaces:**
- Consumes: markup classes from Task 2 (`.ats-clearance-popup-overlay`, `.ats-clearance-popup`, `__close`, `__media`, `__content`, `__tag`, `__heading`, `__description`, `__cta`, `--no-image`).
- Note: Flowbite toggles display via `.hidden`/`.flex` utilities + adds `items-center justify-center` on show (placement: center), so the partial does not set `display` on the overlay.

- [ ] **Step 1: Create the partial**

```scss
// Clearance announcement pop-up (split-card modal).
// Markup:    functions/template-parts/clearance-popup-modal.php
// Behaviour: assets/js/components/clearance-popup.js

$ats-cp-yellow: #ffd902;
$ats-cp-heading: #57434e;
$ats-cp-body: #444;
$ats-cp-border: #e5e5e5;

.ats-clearance-popup-overlay {
	position: fixed;
	inset: 0;
	z-index: 50;
	align-items: center;
	justify-content: center;
	padding: 1rem;
	// `display` is controlled by Flowbite via .hidden / .flex utilities.
}

.ats-clearance-popup {
	position: relative;
	display: flex;
	width: 100%;
	max-width: 720px;
	max-height: 90vh;
	overflow: hidden;
	background: #fff;
	border: 1px solid $ats-cp-border;
	border-radius: 4px;
	box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);

	&__close {
		position: absolute;
		top: 10px;
		right: 10px;
		z-index: 2;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		color: #1a1a1a;
		background: rgba(255, 255, 255, 0.85);
		border: 0;
		border-radius: 9999px;
		cursor: pointer;
		transition: background-color 0.15s ease, color 0.15s ease;

		&:hover {
			background: #fff;
			color: #000;
		}

		&:focus-visible {
			outline: 2px solid $ats-cp-heading;
			outline-offset: 2px;
		}
	}

	&__close-icon {
		width: 14px;
		height: 14px;
	}

	&__media {
		flex: 0 0 45%;

		img {
			display: block;
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
	}

	&__content {
		display: flex;
		flex: 1 1 55%;
		flex-direction: column;
		padding: 32px;
		overflow-y: auto;
	}

	&--no-image &__content {
		flex-basis: 100%;
		text-align: center;
		align-items: center;
	}

	&__tag {
		display: inline-block;
		margin-bottom: 12px;
		font-size: 12px;
		font-weight: 700;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		color: $ats-cp-heading;
	}

	&__heading {
		margin: 0 0 12px;
		font-size: 28px;
		line-height: 1.15;
		font-weight: 700;
		color: $ats-cp-heading;
	}

	&__description {
		margin: 0 0 20px;
		font-size: 16px;
		line-height: 1.55;
		color: $ats-cp-body;

		p:last-child {
			margin-bottom: 0;
		}
	}

	&__cta {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		align-self: flex-start;
		padding: 12px 28px;
		font-size: 16px;
		font-weight: 700;
		color: #000;
		text-decoration: none;
		background: $ats-cp-yellow;
		border-radius: 4px;
		transition: filter 0.15s ease;

		&:hover {
			color: #000;
			filter: brightness(0.92);
		}

		&:focus-visible {
			outline: 2px solid #000;
			outline-offset: 2px;
		}
	}

	&--no-image &__cta {
		align-self: center;
	}
}

// Stack on small screens; keep the CTA visible without page scroll.
@media (max-width: 600px) {
	.ats-clearance-popup {
		flex-direction: column;
		max-height: 88vh;

		&__media {
			flex: 0 0 auto;
			height: 140px;
		}

		&__content {
			padding: 24px;
		}

		&__heading {
			font-size: 22px;
		}
	}
}

@media (prefers-reduced-motion: reduce) {
	.ats-clearance-popup {
		transition: none;
	}
}
```

- [ ] **Step 2: Register the partial**

Append to `src/assets/scss/builds/components/_index.scss`:

```scss
@use "clearance-popup";
```

- [ ] **Step 3: Commit**

```bash
git add src/assets/scss/builds/components/_clearance-popup.scss src/assets/scss/builds/components/_index.scss
git commit -m "feat(clearance-popup): add split-card SCSS partial"
```

---

### Task 4: JS behaviour (delay + capping + focus restore)

**Files:**
- Create: `src/assets/js/components/clearance-popup.js`
- Modify: `src/assets/js/main.js`

**Interfaces:**
- Consumes: DOM root `#ats-clearance-popup` + `data-*` from Task 2; Flowbite `Modal`.
- Produces: a self-initialising IIFE (no export); reads `data-delay`/`data-frequency-mode`/`data-frequency-days`/`data-storage-key`, shows the modal after the delay unless capped, writes the cap flag + restores focus on hide.

- [ ] **Step 1: Create the component**

```js
/**
 * Clearance Pop-up
 *
 * Shows the site-wide clearance announcement modal after a configurable delay,
 * capped by sessionStorage (once per session) or localStorage (once every N
 * days). Markup + eligibility live in
 * functions/template-parts/clearance-popup-modal.php.
 *
 * @package skylinewp-dev-child
 */

import { Modal } from 'flowbite';

(function () {
	'use strict';

	const ROOT_ID = 'ats-clearance-popup';

	/**
	 * Whether the pop-up was already shown within the capping window.
	 */
	function isCapped(key, mode, days) {
		try {
			if (mode === 'days') {
				const stored = parseInt(window.localStorage.getItem(key), 10);
				if (!stored) return false;
				const windowMs = Math.max(1, days) * 86400000;
				return Date.now() - stored < windowMs;
			}
			return window.sessionStorage.getItem(key) === '1';
		} catch (e) {
			// Storage unavailable (private mode etc.) — fail open.
			return false;
		}
	}

	/**
	 * Persist the "seen" flag according to the capping mode.
	 */
	function markSeen(key, mode) {
		try {
			if (mode === 'days') {
				window.localStorage.setItem(key, String(Date.now()));
			} else {
				window.sessionStorage.setItem(key, '1');
			}
		} catch (e) {
			// Ignore storage failures.
		}
	}

	function init() {
		try {
			const root = document.getElementById(ROOT_ID);
			if (!root) return;

			const key = root.dataset.storageKey || 'ats_clearance_popup_dismissed';
			const mode = root.dataset.frequencyMode || 'session';
			const days = parseInt(root.dataset.frequencyDays, 10) || 30;
			const delayMs = (parseInt(root.dataset.delay, 10) || 0) * 1000;

			if (isCapped(key, mode, days)) return;

			let lastFocused = null;

			const modal = new Modal(root, {
				placement: 'center',
				backdrop: 'dynamic',
				backdropClasses: 'bg-black/60 fixed inset-0 z-40',
				closable: true,
				onHide: function () {
					markSeen(key, mode);
					if (lastFocused && typeof lastFocused.focus === 'function') {
						lastFocused.focus();
					}
				},
			});

			// Close button (Flowbite doesn't auto-bind for programmatic instances).
			const closeBtn = root.querySelector('[data-modal-hide]');
			if (closeBtn) {
				closeBtn.addEventListener('click', function (e) {
					e.preventDefault();
					modal.hide();
				});
			}

			// Close when the overlay (not the panel) is clicked.
			root.addEventListener('click', function (e) {
				if (e.target === root) {
					modal.hide();
				}
			});

			window.setTimeout(function () {
				lastFocused = document.activeElement;
				modal.show();
			}, delayMs);
		} catch (e) {
			// Never break the page over an announcement modal.
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
```

- [ ] **Step 2: Import it in main.js**

Add to the "Active Components" block of `src/assets/js/main.js` (after the `product-quick-view.js` import):

```js
import './components/clearance-popup.js';
```

- [ ] **Step 3: Lint the new component**

Run: `npx eslint src/assets/js/components/clearance-popup.js`
Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add src/assets/js/components/clearance-popup.js src/assets/js/main.js
git commit -m "feat(clearance-popup): add delay + frequency-cap behaviour"
```

---

### Task 5: Build verification + QA

**Files:** none (verification only)

- [ ] **Step 1: Confirm wiring**

- `_index.scss` contains `@use "clearance-popup";`
- `main.js` contains `import './components/clearance-popup.js';`
- `footer.php` contains the `clearance-popup-modal` `get_template_part`.

- [ ] **Step 2: Let the watcher compile / one-off build**

The dev Gulp watcher compiles automatically. If a manual compile is needed and approved: `npm run build` (gulp build). **Do NOT run `npm run dist`.**

- [ ] **Step 3: Manual QA checklist (staging)**

- Enable the pop-up in ATS Settings → Clearance Pop-up; set an image, copy, delay 2s, frequency = session.
- Home page: pop-up appears ~2s after load, centered, split-card.
- Excluded — verify NO pop-up on: clearance category page, a single clearance product, cart, checkout, account/login.
- Close via ×, backdrop click, and Esc — each prevents re-show for the rest of the session.
- Switch frequency to "every N days"; confirm it stays hidden within the window across sessions.
- Mobile (<600px): stacks, image becomes a banner, CTA visible without scrolling, no horizontal scroll.
- Keyboard: focus trapped while open; focus restored to the previously-focused element on close.
- Toggle off → pop-up absent everywhere.
- Clear the image field → clean single-column content card.

- [ ] **Step 4: Optional Playwright pass**

Drive 1440 / 768 / 375 px against staging and screenshot the open modal + an excluded page (checkout) to confirm absence.

---

## Self-Review

**1. Spec coverage:**
- Layout/split-card → Task 2 markup + Task 3 SCSS ✓
- Colours/CTA yellow → Task 3 ✓
- Trigger delay → Task 1 field + Task 4 ✓
- Frequency capping (session/days) → Task 1 + Task 4 ✓
- Exclusions (clearance cat/products, cart, checkout, account) → Task 2 `ats_clearance_popup_should_render()` ✓
- Dismissal (×/backdrop/Esc) → Task 4 + Flowbite ✓
- Mobile stacking + CTA visible → Task 3 media query ✓
- Client-editable (toggle/copy/image/link/delay/frequency) → Task 1 ✓
- Accessibility (dialog role, labelled, Esc, focus trap+restore, contrast, alt) → Task 2 markup + Task 4 focus restore + Flowbite ✓
- Slug not ID → Task 2 ✓
- Child theme only → all tasks ✓

**2. Placeholder scan:** none — every step contains final code/commands.

**3. Type consistency:** option field names in Task 1 match `get_field()` reads in Task 2; DOM id/data-attrs/classes in Task 2 match Task 3 selectors and Task 4 `dataset` reads; `ats_clearance_popup_should_render` named consistently.
