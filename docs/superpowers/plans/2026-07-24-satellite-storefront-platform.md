# Satellite Storefront Platform Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a multi-tenant headless storefront platform that resells the ATS Diamond Tools WooCommerce catalogue across several branded domains, each with its own markup pricing, content, coupons and reviews, while ATS handles stock, payment and fulfilment.

**Architecture:** A single Next.js (App Router) application on Node/PM2 serving many domain-routed storefronts from one MySQL database: a shared product mirror synced from ATS production plus per-site content, pricing rules, reviews and order records. A small additive bridge in the ATS `skylinewp-dev-child` theme recognises signed "channel" requests, applies each site's price ruleset at checkout, tags orders, mutes ATS customer emails, and exposes account endpoints. The browser never talks to ATS directly — every cart/checkout/account call is proxied server-to-server through the platform.

**Tech Stack:** Next.js 15 (App Router, Route Handlers), Node 20, MySQL 8, Prisma (schema + migrations + typed client), Stripe.js (publishable key only), WooCommerce Store API + REST API v3, PM2, Vitest (unit/integration), Playwright (E2E). PHP 8 for the ATS theme bridge.

## Global Constraints

- **Data source is ATS production** (`https://www.atsdiamondtools.co.uk`), never staging, for all product, price, stock, shipping and order data.
- **The platform never holds the Stripe secret key.** Charges happen on ATS; the platform uses only the publishable key in the browser.
- **Markup rules live only in the platform.** They must never appear on a public endpoint or in any client payload. The ATS owner has no platform login.
- **Never undercharge:** if the ATS bridge cannot load a channel ruleset, it uses the last cached ruleset; with no cached ruleset it refuses checkout. Falling back to ATS base prices is forbidden.
- **Every per-site restriction is enforced on ATS**, not only hidden in the satellite UI: excluded products, coupon allowlist, free-shipping toggle, hidden shipping methods, sale policy.
- **All money is computed server-side.** Client-supplied totals are never trusted.
- **ATS theme code** lives in `src/functions/woocommerce/` as `satellite-*.php`, one feature per file, auto-loaded by `src/functions.php:52`. Every hook exits immediately unless the request carries a valid channel signature. No existing ATS behaviour changes.
- **Money maths uses integer minor units (pence)** end to end; never float arithmetic on prices.
- **PHP function prefix** `skylinewp_child_satellite_`; escape all output (`esc_html`/`esc_attr`/`esc_url`/`wp_kses_post`).
- Spec of record: `docs/superpowers/specs/2026-07-23-satellite-storefront-platform-design.md`.

---

## File Structure

**Platform app** (new repository `satellite-platform`, developed on the staging VPS):

```
satellite-platform/
├── prisma/
│   └── schema.prisma                     # shared mirror + per-site tables
├── src/
│   ├── lib/
│   │   ├── pricing/
│   │   │   ├── engine.ts                 # markup, cap, min, rounding, sale policy — PURE
│   │   │   ├── money.ts                  # pence helpers, rounding rules
│   │   │   └── ruleset.ts                # build the signed channel ruleset
│   │   ├── ats/
│   │   │   ├── rest.ts                    # wc/v3 read-only client (sync)
│   │   │   ├── store.ts                   # Store API proxy client (cart/checkout)
│   │   │   └── signing.ts                # HMAC sign/verify (both directions)
│   │   ├── sites/resolve.ts              # Host header → site config
│   │   ├── db.ts                          # Prisma singleton
│   │   └── overrides.ts                  # apply per-site text/price overrides to mirror rows
│   ├── sync/
│   │   ├── import-product.ts             # upsert one product (+variations+images)
│   │   ├── reconcile.ts                  # full catalogue walk + 48h order re-pull
│   │   └── oos.ts                         # 10-min out-of-stock recheck
│   ├── app/
│   │   ├── (store)/                       # domain-routed storefront
│   │   │   ├── page.tsx                   # home / product grid
│   │   │   ├── product/[slug]/page.tsx    # product detail + variations
│   │   │   ├── cart/page.tsx
│   │   │   ├── checkout/page.tsx
│   │   │   └── account/…
│   │   ├── admin/                         # platform admin (site switcher)
│   │   └── api/
│   │       ├── webhooks/wc/route.ts       # product + order webhooks
│   │       ├── cart/route.ts              # Store API proxy
│   │       ├── checkout/route.ts
│   │       ├── coupon/route.ts
│   │       ├── account/…/route.ts
│   │       └── ruleset/[channel]/route.ts # signed ruleset fetch (ATS pulls this)
│   └── middleware.ts                      # resolve site by Host, noindex staging
└── tests/ …
```

**ATS theme bridge** (in this repo, `src/functions/woocommerce/`):

```
satellite-channel.php        satellite-orders.php
satellite-ruleset.php        satellite-emails.php
satellite-pricing.php        satellite-accounts.php
satellite-restrictions.php
```

---

## Phase 1 — Foundation (mirror, sync, admin)

### Task 1: Prisma schema + database

**Files:**
- Create: `prisma/schema.prisma`, `src/lib/db.ts`
- Test: `tests/db/schema.test.ts`

**Interfaces:**
- Produces: Prisma models `Product`, `Variation`, `Category`, `SyncLog`, `Site`, `SiteProductSetting`, `SiteCoupon`, `SitePost`, `SitePage`, `SiteReview`, `SiteOrder`, `AdminUser`, `UserSiteAccess`. All prices stored as `Int` pence.

- [ ] **Step 1:** Write the schema per the spec's "Database schema" section. `Site` carries `markupType`, `markupValue`, `markupMaxUplift`, `markupMinUplift`, `roundingRule`, `followAtsSales`, `freeShippingEnabled`, `hiddenShippingMethods Json`, `channelKey`, `hmacSecret`, `branding Json`.
- [ ] **Step 2:** `npx prisma migrate dev --name init` → expect tables created.
- [ ] **Step 3:** Test that a `Site` round-trips through Prisma. Run: `vitest run tests/db` → PASS.
- [ ] **Step 4:** Commit `feat(db): initial schema and Prisma client`.

### Task 2: Money helpers (pence, rounding)

**Files:** Create `src/lib/pricing/money.ts`; Test `tests/pricing/money.test.ts`

**Interfaces:**
- Produces: `toPence(decimalString): number`, `fromPence(pence): string`, `applyRounding(pence, rule: 'none'|'nearest_pound'|'charm'): number`.

- [ ] **Step 1: failing test** — `toPence('29.99') === 2999`; `toPence('155') === 15500`; `applyRounding(2760,'charm') === 2799` (round up to next `.99`); `applyRounding(2740,'nearest_pound') === 2700`; `applyRounding(2999,'none') === 2999`.
- [ ] **Step 2:** run → FAIL.
- [ ] **Step 3:** implement with integer maths only (parse via string split on `.`, never `parseFloat*100`).
- [ ] **Step 4:** run → PASS. **Step 5:** commit `feat(pricing): money + rounding helpers`.

### Task 3: The pricing engine (highest-risk — most coverage)

**Files:** Create `src/lib/pricing/engine.ts`; Test `tests/pricing/engine.test.ts`

**Interfaces:**
- Consumes: `money.ts`.
- Produces:
  ```ts
  type MarkupRule = { type: 'percent'|'fixed'; value: number;   // percent basis-points or fixed pence
    maxUplift?: number; minUplift?: number; rounding: 'none'|'nearest_pound'|'charm' }
  type PricedResult = { basePence: number; chargedPence: number; upliftPence: number; effectivePct: number; onSale: boolean }
  function priceOne(input: { regularPence: number; salePence: number|null;
    followSales: boolean; rule: MarkupRule }): PricedResult
  ```

- [ ] **Step 1: failing tests** (table-driven — one case per row):

```ts
// 15% on £24.00 base, charm rounding → £27.99
priceOne({regularPence:2400, salePence:null, followSales:true,
  rule:{type:'percent', value:1500, rounding:'charm'}})
  // → { basePence:2400, chargedPence:2799, upliftPence:399, effectivePct:16.6, onSale:false }

// cap: 15% on £1850 base capped at £50 uplift, charm → £1899.99
priceOne({regularPence:185000, salePence:null, followSales:true,
  rule:{type:'percent', value:1500, maxUplift:5000, rounding:'charm'}})
  // → chargedPence:189999, upliftPence:5000

// fixed £12 uplift, no rounding
priceOne({regularPence:2800, salePence:null, followSales:true,
  rule:{type:'fixed', value:1200, rounding:'none'}}) // → chargedPence:4000

// minUplift: 5% on £2 base but min £2 uplift
priceOne({regularPence:200, salePence:null, followSales:true,
  rule:{type:'percent', value:500, minUplift:200, rounding:'none'}}) // → chargedPence:400

// FOLLOW SALE: £327.71 reg, £163.85 sale, 15%, charm → sale charged
priceOne({regularPence:32771, salePence:16385, followSales:true,
  rule:{type:'percent', value:1500, rounding:'charm'}})
  // → onSale:true, chargedPence:18899 (approx; assert exact once computed)

// IGNORE SALE: same product, followSales:false → charged off REGULAR, onSale:false
priceOne({regularPence:32771, salePence:16385, followSales:false,
  rule:{type:'percent', value:1500, rounding:'charm'}})
  // → onSale:false, basePence:32771
```

- [ ] **Step 2:** run → FAIL.
- [ ] **Step 3:** implement: choose base (sale if `followSales && salePence`), compute uplift, clamp to `[minUplift, maxUplift]`, add, round, derive `effectivePct = round((uplift/base)*1000)/10`. Never mutate input.
- [ ] **Step 4:** run → PASS (fix any expected values to the computed exacts, keeping cap/min/sale invariants).
- [ ] **Step 5:** commit `feat(pricing): markup engine with caps, rounding, sale policy`.

### Task 4: ATS REST client + product importer

**Files:** Create `src/lib/ats/rest.ts`, `src/sync/import-product.ts`; Test `tests/sync/import.test.ts` (fixtures of wc/v3 payloads: simple, variable, on-sale, out-of-stock).

**Interfaces:**
- Produces: `importProduct(wcProduct): Promise<void>` — upserts `Product` (+`Variation` rows, +downloads new images to disk), stores raw payload and `salePence`/`saleDates`.

- [ ] **Step 1:** failing test — feeding the variable-product fixture creates 1 `Product` + N `Variation` rows with pence prices.
- [ ] **Step 2:** FAIL. **Step 3:** implement upsert + image download (skip re-download if file exists). **Step 4:** PASS. **Step 5:** commit `feat(sync): product importer`.

### Task 5: Webhooks, OOS cron, nightly reconcile

**Files:** Create `src/app/api/webhooks/wc/route.ts`, `src/sync/oos.ts`, `src/sync/reconcile.ts`, `src/lib/ats/signing.ts`; Test `tests/sync/webhook.test.ts`.

**Interfaces:**
- Consumes: `importProduct`, `verifyWebhook(sig, body, secret)`.
- Produces: route handling `product.*` and `order.updated`; `runOos()`; `runReconcile()`.

- [ ] **Step 1:** failing test — a POST with a valid `x-wc-webhook-signature` imports; an invalid signature returns 401 and imports nothing.
- [ ] **Step 2:** FAIL. **Step 3:** implement HMAC verify + dispatch. **Step 4:** PASS.
- [ ] **Step 5:** PM2 ecosystem cron entries: `oos` every 10 min, `reconcile` at 03:00. Commit `feat(sync): webhooks + crons`.

### Task 6: Site resolution + admin skeleton

**Files:** Create `src/middleware.ts`, `src/lib/sites/resolve.ts`, `src/app/admin/**`; Test `tests/sites/resolve.test.ts`.

- [ ] **Step 1:** failing test — `resolveSite('ea.staging.rfsdev.co.uk')` returns the EA site row; unknown host → 404 config.
- [ ] **Step 2:** FAIL. **Step 3:** implement Host lookup + `noindex` header on staging domains. **Step 4:** PASS.
- [ ] **Step 5:** admin: login (bcrypt, httpOnly cookie), site switcher, Products table (exclude toggle + text overrides), Sync dashboard. Commit `feat(admin): site switcher, product overrides, sync dashboard`.

## Phase 2 — Pricing & rules engine (ATS bridge)

### Task 7: Ruleset builder + signed fetch endpoint

**Files:** Create `src/lib/pricing/ruleset.ts`, `src/app/api/ruleset/[channel]/route.ts`; Test `tests/pricing/ruleset.test.ts`.

**Interfaces:**
- Produces: `buildRuleset(siteId)` → `{ version, markup, perProduct[], salePolicy, couponAllowlist[], excluded[], freeShipping, hiddenMethods[] }`; endpoint returns it only to a correctly-HMAC-signed request from ATS.

- [ ] **Step 1:** failing test — unsigned request → 401; signed request → ruleset JSON with a monotonically increasing `version`.
- [ ] **Step 2:** FAIL. **Step 3:** implement. **Step 4:** PASS. **Step 5:** commit.

### Task 8: ATS theme bridge — channel + ruleset + pricing

**Files (this repo):** Create `src/functions/woocommerce/satellite-channel.php`, `satellite-ruleset.php`, `satellite-pricing.php`.

**Interfaces:**
- `skylinewp_child_satellite_channel()` → array|null (validates key+HMAC on the request, identifies the site).
- `skylinewp_child_satellite_ruleset()` → array|WP_Error (fetches from platform, caches in transient `sat_ruleset_{channel}`, returns last-cached on failure, `WP_Error` if never cached).
- pricing hooks on `woocommerce_product_get_price` etc. apply the ruleset **only** when a channel is present.

- [ ] **Step 1:** guard test (PHP, via WP test harness or a scripted `wp eval`): with no channel header, `get_price()` of a product is unchanged.
- [ ] **Step 2:** with a valid channel + a stubbed transient ruleset (15%, charm), `get_price()` returns the marked-up figure; a checkout with no obtainable ruleset is blocked with a notice.
- [ ] **Step 3:** implement, mirroring the pure engine's algorithm in PHP (integer pence). **Step 4:** verify both. **Step 5:** commit `feat(satellite): channel recognition, ruleset cache, markup pricing`.

### Task 9: ATS bridge — restrictions (exclusions, coupons, shipping, free shipping)

**Files:** Create `src/functions/woocommerce/satellite-restrictions.php`.

- [ ] **Step 1:** tests — add-to-cart of an excluded product id is rejected; a coupon not on the allowlist is rejected with a clear notice; when `freeShipping=false`, `free_shipping` methods are removed from the packages; a `hiddenMethods` id is removed.
- [ ] **Step 2:** FAIL. **Step 3:** implement via `woocommerce_add_to_cart_validation`, `woocommerce_coupon_is_valid`, `woocommerce_package_rates`. **Step 4:** PASS. **Step 5:** commit.

### Task 10: Catalogue price table (admin) + per-product overrides

**Files:** `src/app/admin/pricing/**`, `src/app/admin/products/**`, `src/lib/overrides.ts`; Test `tests/admin/pricing-preview.test.ts`.

- [ ] **Step 1:** failing test — the preview endpoint returns base/uplift/final/effectivePct for a sample, matching `priceOne`.
- [ ] **Step 2:** FAIL. **Step 3:** implement the table (with on-sale flag), the markup form with live preview, per-product markup override + text overrides, coupon allowlist editor, free-shipping checkbox, hidden-methods multiselect. **Step 4:** PASS. **Step 5:** commit.

## Phase 3 — Commerce (cart, checkout, orders, emails)

### Task 11: Store API proxy (cart)

**Files:** `src/lib/ats/store.ts`, `src/app/api/cart/route.ts`, `src/app/api/coupon/route.ts`; Test `tests/cart/proxy.test.ts`.

**Interfaces:** session cookie → server-held Woo `Cart-Token`; add/update/remove, apply/remove coupon, set address → shipping rates, select rate. Every outbound request carries the channel key + HMAC (`x-ea-channel`, `x-ea-sign`).

- [ ] **Step 1:** failing test (mocked Store API) — add item returns cart with marked-up totals; excluded product is refused server-side even if requested directly.
- [ ] **Step 2:** FAIL. **Step 3:** implement. **Step 4:** PASS. **Step 5:** commit.

### Task 12: Checkout + Stripe

**Files:** `src/app/(store)/checkout/**`, `src/app/api/checkout/route.ts`; Test `tests/checkout/flow.test.ts`.

- [ ] **Step 1:** failing test — posting a valid PaymentMethod id drives `POST /wc/store/v1/checkout`, returns an order; a 3DS `requires_action` response surfaces the client secret to the browser.
- [ ] **Step 2:** FAIL. **Step 3:** implement Stripe Elements (publishable key), submit via proxy, handle 3DS, record `SiteOrder` (base/charged/margin). **Step 4:** PASS. **Step 5:** commit.

### Task 13: ATS bridge — order tagging + email suppression

**Files:** Create `src/functions/woocommerce/satellite-orders.php`, `satellite-emails.php`.

- [ ] **Step 1:** tests — a channel checkout sets `_sales_channel` meta and shows a channel column; WooCommerce customer emails (incl. shipment tracking) do not send for channel orders, but admin/fulfilment ones do.
- [ ] **Step 2:** FAIL. **Step 3:** implement via `woocommerce_checkout_order_processed` + `woocommerce_email_enabled_*` filters gated on channel. **Step 4:** PASS. **Step 5:** commit.

### Task 14: Satellite-branded emails + margin report

**Files:** `src/lib/email/**`, `src/app/admin/orders/**`; Test `tests/orders/margin.test.ts`.

- [ ] **Step 1:** failing test — margin report over a date range sums `chargedTotal - baseTotal`; a refund reverses margin proportionally.
- [ ] **Step 2:** FAIL. **Step 3:** implement order-confirmation + shipped emails (site sender identity), orders table, CSV export. **Step 4:** PASS. **Step 5:** commit.

## Phase 4 — Accounts, content & reviews

### Task 15: ATS bridge — account endpoints (channel-filtered)

**Files:** Create `src/functions/woocommerce/satellite-accounts.php`.

- [ ] **Step 1:** the load-bearing isolation test — a customer with orders on ATS, site 1 and site 2, queried with site 1's channel key, returns only site 1's orders; the channel is derived from the signed key, never a request parameter.
- [ ] **Step 2:** FAIL. **Step 3:** implement login/register/reset/order-history REST routes, filtering order history by `_sales_channel`. **Step 4:** PASS. **Step 5:** commit.

### Task 16: Platform account UI + tracking

**Files:** `src/app/(store)/account/**`, `src/app/api/account/**`; Test `tests/account/history.test.ts`.

- [ ] **Step 1:** failing test — order history renders only the site's orders with status + tracking pulled from the order webhook records.
- [ ] **Step 2:** FAIL. **Step 3:** implement login/register/reset flows (reset link stays on the satellite domain), history + tracking display. **Step 4:** PASS. **Step 5:** commit.

### Task 17: Reviews (collect + moderate)

**Files:** `src/app/(store)/product/[slug]/reviews/**`, `src/app/admin/reviews/**`, `src/app/api/reviews/route.ts`; Test `tests/reviews/moderation.test.ts`.

- [ ] **Step 1:** failing tests — a submitted review is `pending` and not public; approving it publishes it; a matching order email flags `verified_purchase`; aggregate rating drives JSON-LD.
- [ ] **Step 2:** FAIL. **Step 3:** implement form (rate-limited), moderation queue, aggregate. **Step 4:** PASS. **Step 5:** commit.

### Task 18: SEO (meta, sitemaps, canonicals, structured data)

**Files:** `src/app/(store)/**` metadata, `src/app/sitemap.ts`, `src/lib/seo/**`; Test `tests/seo/meta.test.ts`.

- [ ] **Step 1:** failing test — a product page emits the site's override meta title/description (falling back to ATS), a canonical on the satellite domain, and Product + AggregateRating JSON-LD.
- [ ] **Step 2:** FAIL. **Step 3:** implement. **Step 4:** PASS. **Step 5:** commit.

## Phase 5 — Launch & extras

### Task 19: Second site + multi-site isolation tests

**Files:** `tests/e2e/multisite.spec.ts` (Playwright).

- [ ] **Step 1:** failing E2E — two sites with different markups, sale policies, coupon lists and exclusions show no bleed; each checkout charges its own price.
- [ ] **Step 2:** FAIL. **Step 3:** stand up site 2 by config only. **Step 4:** PASS. **Step 5:** commit.

### Task 20: Deploy to purchased domain + Apple/Google Pay

**Files:** PM2 + nginx vhost config, Stripe domain registration notes.

- [ ] **Step 1:** deploy the app on the purchased domain via PM2/nginx.
- [ ] **Step 2:** register the domain in the ATS Stripe dashboard; add the Express Checkout Element.
- [ ] **Step 3:** live smoke order, refunded, asserting margin reverses. Commit `chore(launch): production domain + express checkout`.

## Testing strategy (recap)

- **Pricing** gets the deepest coverage (Task 3): percent/fixed × cap × min × rounding × both sale policies, plus VAT/coupon interaction, the missing-ruleset-refuses-checkout invariant, and refund-aware reconciliation.
- **Ruleset enforcement** (Tasks 8–9, 11, 15) is security-critical: crafted requests must fail on ATS, not merely be absent from the UI.
- **Account isolation** (Task 15) is the load-bearing shared-account test.
- **Ops:** kill-ATS (browse from mirror, checkout degrades gracefully) and kill-platform (ATS uses cached ruleset, refuses if none).

## Self-review notes

- Every spec section maps to a task: mirror/sync (1,4,5), pricing+sale policy (2,3,7,8,10), restrictions/coupons/free-shipping (9,10,11), accounts (15,16), deliveries (13,16), reviews (17), SEO (18), multi-site (19), launch/express pay (20). Invoices/packing slips/returns need no task — unchanged on ATS by decision.
- Prices are integer pence throughout; the PHP bridge mirrors the TS engine's algorithm exactly and both are covered by the same table of cases.
