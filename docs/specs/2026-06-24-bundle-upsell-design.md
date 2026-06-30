# Mini-cart "Upgrade to the kit" upsell — design

**Date:** 2026-06-24
**Status:** Approved (brainstorm), building
**Author:** Claude + info@redfrogstudio.co.uk

## Problem / goal

When a customer adds a single product that is a component of one of our bundles ("Pro
Kits"), we want to nudge them — at the highest-intent moment — to buy the kit instead and
save money. Raise average order value by converting single-item buyers into kit buyers.

A *pre-add* notice already exists on the product page (`ats_bundle_render_in_bundle_notice()`
— "Bundle deal … buy together and save"). This feature adds the *post-add* moment.

## Decisions (from brainstorm)

1. **Mechanic: swap / upgrade.** The primary button removes the single item and adds the
   kit in its place (no double-paying). One click.
2. **Placement: the slide-out mini-cart.** The banner renders under the relevant line item
   whenever the cart *contains* a kit-eligible item — so it covers every add path
   (product page, quick-view, listing) with one implementation, no per-event hooks.
3. **Multiple kits: show the single biggest-saving kit.** Keeps the banner clean.

## UX flow

1. Customer adds a kit-eligible item → slide-out mini-cart opens (existing behaviour).
2. Under that line item: a green-accented banner — kit name, "save £X", **[Upgrade to the
   kit →]**, a **"see what's in it ▾"** toggle (expands the kit contents), and a dismiss "×".
3. Click **Upgrade** → the single line is removed, 1× kit added, drawer refreshes showing the
   kit. Banner is gone (trigger item no longer present).
4. Click **×** / ignore → banner hides and does not reappear for that kit for the rest of the
   browser session.

## Banner content

Compact by default: kit name + "save £X" + Upgrade button + dismiss. The kit contents
(item names/thumbnails from `ats_bundle_get_items()`) are rendered collapsed and revealed by
the "see what's in it" toggle — keeps the mini-cart from growing too tall.

## Edge cases & defaults

- **Quantity:** if the single item line has qty > 1, Upgrade replaces that whole line with
  **1× kit** (not N kits).
- **Bundle has options** (`ats_bundle_has_options`): a silent swap can't choose an option, so
  for option-bundles the button becomes **"Choose & add the kit →"** and links to the bundle
  page instead of swapping. (Current ATS kits have no options, but handle it safely.)
- **Kit already in cart:** if the best-saving kit is already in the cart, don't show the
  banner for that item.
- **Bundle items themselves:** never show the banner on a bundle line (`ats_is_bundle()` guard).
- **Dismiss memory:** per-kit, `sessionStorage` (mirrors the exit-intent popup pattern).
- **Pre-add product-page notice stays** — complementary (before vs after the add).

## Technical design

Reuse the existing bundle data layer and mini-cart plumbing; add one new self-contained file
plus one small hook into the mini-cart item renderer.

### New file: `functions/woocommerce/bundle-cart-upsell.php` (auto-loaded, inline CSS/JS)

- `ats_bundle_best_kit_for_product( $product_id )` — wraps `ats_bundle_get_bundles_for_product()`
  + `ats_bundle_max_save()`; returns the highest-saving published bundle id (and its saving),
  skipping kits already in the cart. Null if none.
- `ats_bundle_render_minicart_upsell( $product_id, $cart_key )` — returns the banner HTML
  (or '') for a given cart line. Guards: skip if the line product is itself a bundle, skip if
  no eligible kit. Adds `data-bundle-id`, `data-cart-key`, and either upgrade or `data-bundle-url`
  (option-bundles).
- AJAX `ats_bundle_upgrade_to_kit` (`wp_ajax_` + `nopriv_`):
  - nonce check `ats_mini_cart_nonce` (the existing mini-cart nonce).
  - inputs: `cart_key`, `bundle_id`.
  - **validate**: cart line exists; `bundle_id` is a real published bundle; the line's product
    id is genuinely a component of that bundle (`ats_bundle_get_bundles_for_product()` contains
    it) — prevents arbitrary swaps; bundle has no options.
  - `WC()->cart->remove_cart_item( $cart_key )` → `WC()->cart->add_to_cart( $bundle_id, 1 )`
    → `calculate_totals()`.
  - return `ats_get_updated_cart_data()` (items_html + subtotal/total/tax + count) — identical
    shape to the existing remove/update endpoints.
- Inline CSS: `.ats-kit-upsell` banner (green accent, matches the £-save badge styling, no
  shadow, lightly rounded).
- Inline JS (jQuery, delegated on `document`):
  - `.ats-kit-upsell__upgrade` click → POST `ats_bundle_upgrade_to_kit` with
    `window.themeData.ajax_url` + `window.themeData.mini_cart_nonce`; on success set
    `.js-mini-cart-items` innerHTML = items_html and update `.js-mini-cart-subtotal/-total/-tax`
    + `.js-mini-cart-count` (same nodes the drawer updates).
  - `.ats-kit-upsell__dismiss` → `sessionStorage` + hide.
  - `.ats-kit-upsell__toggle` → reveal contents.
  - on any mini-cart render, hide banners whose kit is in `sessionStorage`.

### Edit: `functions/shortcodes/add_to_cart/ajax-handler.php`

Inside `ats_get_cart_items_html()`'s item loop, after each item `<div>`, add one guarded call:
```php
if ( function_exists( 'ats_bundle_render_minicart_upsell' ) ) {
    echo ats_bundle_render_minicart_upsell( $item['product_id'], $item['key'] ); // phpcs:ignore -- escaped in fn
}
```
This single function feeds the drawer on add, open, qty-change and remove, so the banner stays
in sync everywhere.

## Out of scope (possible future)

- **Cart-aware bundle detection** — when the cart already holds 2+ items that all belong to
  the same kit, prompt "you're 1 item away from the kit". Higher effort; revisit later.
- Cart-page (full page) cross-sell variant.

## Test plan (staging, Playwright)

1. Add a kit component → drawer opens → banner shows correct kit + saving.
2. "see what's in it" expands the kit contents.
3. Upgrade → single line removed, 1× kit present, totals/count correct, banner gone.
4. Qty 3 of component → Upgrade → 1× kit (not 3).
5. Dismiss → gone; reopen drawer → still gone (session); new session → returns.
6. Non-kit product → no banner. Bundle line → no banner. Kit already in cart → no banner.
7. No console errors; bundle product pages and normal cart flows unaffected.

## Deploy

Staging-first (build + active theme), verify, then rsync the two files to production active
theme + flush caches (object cache + Rocket). Same method as the sale-badge / popup files.
No DB push, no `gulp dist`.
