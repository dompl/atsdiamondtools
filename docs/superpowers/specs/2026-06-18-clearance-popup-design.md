# Clearance Pop-up — Design Spec

**Date:** 2026-06-18
**Theme:** `skylinewp-dev-child` (source) → builds to active `atsdiamondtools-child` dist
**Status:** Approved (design), pending implementation

---

## 1. Purpose

Announce the new **Clearance** product category with a single, brand-native modal pop-up that
drives visitors to the clearance category page. Must feel native to the site, not like a
third-party plugin overlay.

- Clearance category slug: `clearance` (term ID `3735` — for reference only; **always reference by slug**)
- Default link target: `/product-category/clearance/` (resolved via `get_term_link('clearance','product_cat')`)
- Child theme only — never the parent (`skylinewp-dev-parent`). A parent update must not remove it.

## 2. Approach (chosen: "A")

PHP gates eligibility and renders the markup; a thin JS layer handles timing, frequency-capping,
and open/close; styling is a dedicated SCSS partial. Reuse the theme's existing **Flowbite Modal**
controller (as used by Product Quick View) for backdrop/Esc/focus-trap/scroll-lock.

**Data flow:** PHP decides eligibility → if eligible, renders modal markup + inline `data-*` config →
JS reads config, checks the storage cap, shows the modal after the delay → on close, JS writes the
cap flag and restores focus.

## 3. File map

### Create
| File | Role |
|------|------|
| `src/functions/acf/options/clearance-popup-settings.php` | Registers the **Clearance Pop-up** ACF options sub-page (parent menu: `ats-settings`) + field group. Auto-loaded by the parent theme's ACF directory filter. |
| `src/functions/template-parts/clearance-popup-modal.php` | Eligibility gate (`ats_clearance_popup_should_render()`) + modal markup built from ACF fields. |
| `src/assets/js/components/clearance-popup.js` | Timing, frequency-capping, open/close, focus restore. |
| `src/assets/scss/builds/components/_clearance-popup.scss` | Split-card styling. |

### Edit
| File | Change |
|------|--------|
| `src/footer.php` | Include the new template part (same mechanism as `product-quick-view-modal.php`). |
| `src/assets/js/main.js` | `import './components/clearance-popup.js';` |
| `src/assets/scss/builds/components/_index.scss` | `@use 'clearance-popup';` |

No parent-theme files are touched.

## 4. ACF fields (Clearance Pop-up options sub-page)

Registered with Extended ACF, location `options_page == clearance-popup-settings`; the sub-page is
added under the existing **ATS Settings** menu via `acf_add_options_page()` with `parent_slug` =
`ats-settings`.

| Field name | Type | Notes |
|------------|------|-------|
| `clearance_popup_enabled` | True/False | Master on/off. **Plain TrueFalse** — do NOT chain `->defaultValue()` / `->stylisedUi()` (unsupported by this theme's Extended ACF). |
| `clearance_popup_tag` | Text | Optional small tag above heading, e.g. "LIMITED STOCK". |
| `clearance_popup_heading` | Text | Default "Clearance Sale Now On". |
| `clearance_popup_description` | Textarea | 1–2 sentences; output via `wp_kses_post`. |
| `clearance_popup_button_label` | Text | Default "Shop Clearance". |
| `clearance_popup_link` | URL | Empty → PHP falls back to `get_term_link('clearance','product_cat')`. |
| `clearance_popup_image` | Image (return array) | Left image. Empty → single-column content-only card. |
| `clearance_popup_delay` | Number | Seconds before showing; default 2. |
| `clearance_popup_frequency_mode` | Select | `session` (Once per session) / `days` (Every N days). Default `session`. |
| `clearance_popup_frequency_days` | Number | Default 30; conditional — shown only when mode = `days`. |

## 5. PHP — eligibility & render

`ats_clearance_popup_should_render(): bool` returns **false** (renders nothing) when any of:
- `! function_exists('get_field')` or `get_field('clearance_popup_enabled','option')` is falsy;
- `is_cart() || is_checkout() || is_account_page()`;
- `is_product_category('clearance')`;
- `is_product()` **and** the queried product `has_term('clearance','product_cat')`.

Result is filterable: `apply_filters('ats_clearance_popup_should_render', $should, ...)`.

When eligible, `clearance-popup-modal.php` outputs:
- Root `#ats-clearance-popup` carrying `data-delay` (seconds), `data-frequency-mode`,
  `data-frequency-days`, `data-storage-key` (`ats_clearance_popup_dismissed`).
- Image via `wpimage($img['ID'], [600, 800], retina: true)` with `object-fit: cover`; alt from the
  attachment's alt text. If no image → add `--no-image` modifier and omit the image column.
- Link resolved from `clearance_popup_link`, falling back to the clearance term link.
- All output escaped: `esc_html` / `esc_attr` / `esc_url`, `wp_kses_post` for the description.
- Category referenced by **slug** `clearance` throughout.

Included from `footer.php` using the same mechanism as `product-quick-view-modal.php`.

## 6. JS behaviour (`clearance-popup.js`)

- Init via the `document.readyState` guard pattern (not only `DOMContentLoaded`), wrapped in
  try/catch. If `#ats-clearance-popup` is absent (excluded page / disabled) → return early.
- Read config from `data-*`. **Cap check:**
  - `session` mode → `sessionStorage.getItem(storageKey)` present ⇒ suppress.
  - `days` mode → `localStorage` timestamp; suppress if `now - stored < days × 86400000`.
- If allowed: `setTimeout(show, delaySeconds × 1000)`.
- **Modal = Flowbite `Modal`** (imported as in `product-quick-view.js`), configured
  `backdrop: 'dynamic'` + `closable: true` so **backdrop-click AND Esc both close** (differs from
  quick-view's `static` backdrop — the brief wants backdrop-close).
- Capture `document.activeElement` before showing; on hide → write the cap flag (sessionStorage key,
  or localStorage timestamp) **and restore focus**. Flowbite provides focus-trap + body-scroll-lock.

## 7. SCSS (`_clearance-popup.scss`)

- Panel: `max-width: 720px; max-height: 90vh; border-radius: 4px;` soft drop shadow;
  `display: flex; overflow: hidden`. Backdrop scrim `rgba(0,0,0,0.6)`.
- Columns ~45/55 (image/content). Image column full-height `object-fit: cover`.
- Content padding 28–32px; gaps heading→desc 12px, desc→button 20px.
- Colours: heading `#57434E` (primary-700); body `#1A1A1A`/`#444`; modal bg `#FFFFFF`;
  borders `#E5E5E5`; optional urgency accent `#367A33`.
- **CTA (bespoke** — no yellow `dpn-btn` variant exists): bg `#FFD902`, text `#000`, bold,
  4px radius, subtle hover-darken. Yellow used sparingly so it reads as the single action.
- Optional tag: small, uppercase, muted.
- **< 600px:** `flex-direction: column`; image becomes a ~140px top banner (`object-fit: cover`);
  content area `overflow-y: auto` so the **CTA stays visible without page scroll**; no horizontal
  scroll. `--no-image` collapses to single column.
- `@media (prefers-reduced-motion: reduce)`: disable entrance transition.
- Font: inherit Inter (do not import).

## 8. Accessibility

- `role="dialog"`, `aria-modal="true"`, `aria-labelledby` → heading id; `aria-describedby` → description id.
- Close `×` is a real `<button type="button" aria-label="Close">`.
- Esc + focus-trap via Flowbite; focus-restore handled in our JS.
- Contrast passes AA: black on `#FFD902`; `#444`/`#57434E` on white.
- Image alt from the attachment (empty alt if decorative).
- Fully keyboard- and mobile-operable.

## 9. Build, deploy & testing

- After edits, the source must be compiled into the active `atsdiamondtools-child` dist via the
  Gulp/npm build. The dev watcher runs automatically; **`gulp dist` (production deploy) is NOT run
  without explicit go-ahead** (re-activates the dist + pushes version bump to master).
- **QA checklist:**
  - Appears ~2s after load on home / other category / blog pages.
  - **Absent** on: clearance category page, single clearance products, cart, checkout, account/login.
  - Closes via ×, backdrop click, and Esc — each writes the cap flag.
  - Does not re-show within the chosen window (session or N days).
  - Mobile (<600px): stacks; CTA visible without scrolling; no horizontal scroll.
  - Keyboard: focus trapped while open, restored on close.
  - Toggle off → hidden everywhere.
  - Empty image → clean single-column card.
- Optional: Playwright pass at 1440 / 768 / 375 px against staging once built.

## 10. Out of scope

- Persistent top announcement bar (brief notes its dismissal state must be documented separately if added later).
- GA4 / Meta Pixel click tracking on the CTA (can be added later via the existing tracking modules).
