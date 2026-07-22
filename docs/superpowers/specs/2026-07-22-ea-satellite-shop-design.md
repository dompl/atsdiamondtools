# EA Satellite Shop — Design

**Date:** 2026-07-22
**Status:** Approved pending user spec review

## What this is

A second storefront ("EA") on its own domain, selling the exact same products as ATS Diamond Tools. All e-commerce — products, prices, stock, cart, shipping rates, coupons, payment, orders, refunds, emails — is powered by the existing **production** WooCommerce site (www.atsdiamondtools.co.uk). The EA site differs only in design, product titles/descriptions, meta titles/descriptions, blog articles and static page content.

The mother site is not restructured. Orders placed on EA land in the ATS WooCommerce admin like any other order, tagged as EA-channel. Order confirmation emails and PDF invoices are sent by the mother site with its existing branding — this is intentional and confirmed.

## Decisions made

| Decision | Choice |
|---|---|
| Checkout location | Headless on the EA domain — customer never leaves EA; Store API behind the scenes |
| EA content storage | Standalone EA admin with its own MySQL database (not in WP admin, not flat files) |
| Stack | Next.js (App Router) + MySQL |
| Hosting | Developed on the rfsdev staging VPS under a subdomain; later deployed to a purchased domain on a similar server. **Data always pulled from production**, never staging |
| Product data layer | Local mirror DB + WooCommerce webhooks (instant) + cron (10-min out-of-stock recheck, nightly reconcile) |
| V1 commerce scope | Guest card checkout, coupons, Apple Pay / Google Pay, customer accounts (phased — see Build phases) |
| Transactional emails | Sent by the mother site, ATS-branded, unchanged |
| Mother-site code | Lives in the `skylinewp-dev-child` theme `functions/` folder (not a plugin), following the existing theme-feature pattern; developed on staging, tested, shipped to production via the normal flow |

## Architecture

```
┌─────────────────────────┐         ┌──────────────────────────────┐
│  ATS PRODUCTION         │         │  EA site (Next.js + PM2)     │
│  atsdiamondtools.co.uk  │         │  dev: staging subdomain      │
│                         │ webhooks│  live: purchased domain      │
│  WooCommerce 10.5       ├────────►│                              │
│  Stripe gateway         │         │  MySQL: mirror + overrides   │
│  Orders, stock, emails  │◄────────┤  + blog/pages + admin users  │
│  (unchanged)            │ Store   │                              │
└─────────────────────────┘ API +   │  /admin — EA edit screens    │
                            wc/v3   │  /      — custom storefront  │
                                    └──────────────────────────────┘
```

The browser on the EA site never calls ATS directly. Every cart/checkout action goes browser → EA Next.js server route → ATS Store API (server-to-server). No CORS surface; API keys never reach the browser. The only client-side third party is Stripe.js (talks to Stripe, not ATS).

## EA database schema (MySQL)

- `products` — mirror of the catalogue. `wc_product_id` PK, sku, slug, name, type (simple/variable), status, regular_price, sale_price, stock_status, stock_quantity, manage_stock, weight/dims, categories (JSON), images (JSON, local paths after download), attributes (JSON), raw wc/v3 payload (JSON), synced_at.
- `variations` — per-variation rows for variable products: wc_variation_id PK, parent_id, attributes, prices, stock_status, stock_quantity, image.
- `categories` — mirror: wc_category_id PK, slug, name, parent, description, image.
- `product_overrides` — wc_product_id PK, ea_title, ea_description, ea_short_description, ea_meta_title, ea_meta_description, updated_at. Empty/NULL field = fall back to ATS value at render time.
- `posts` — EA blog: slug, title, body (rich text/HTML), hero image, meta_title, meta_description, status, published_at.
- `pages` — EA static pages (About etc.): slug, title, body, meta fields.
- `admin_users` — email, bcrypt password hash.
- `sync_log` — webhook and cron events with outcome, for the sync dashboard and debugging.

## Product sync

Three mechanisms, all writing to the mirror:

1. **Webhooks (instant).** WooCommerce webhooks configured on production: `product.created`, `product.updated`, `product.deleted`, `product.restored` → `POST /api/webhooks/wc` on the EA app. HMAC signature verified against the webhook secret. Handler upserts/removes the mirror row and downloads any new images. A new product added on ATS appears on EA within seconds, using ATS title/description until EA overrides are written. Stock changes fire `product.updated`, so in-stock↔out-of-stock transitions propagate immediately too.
2. **Out-of-stock cron (every 10 minutes).** Re-fetches every mirrored product with `stock_status != 'instock'` via wc/v3 (read-only API key, batched with `include=`), updating stock fields. This is the explicit requirement backstop for anything webhooks miss.
3. **Nightly reconcile (3am).** Full paginated walk of the production catalogue: upserts everything, syncs categories, flags products that disappeared, verifies image files exist locally.

**Images are downloaded to the EA server** during sync (stored under an EA media directory, served by Next.js). No hotlinking to atsdiamondtools.co.uk — keeps the EA brand separate in page source and makes browsing independent of ATS uptime.

Initial import = the same code path as the nightly reconcile, run once.

## EA admin

Login-protected `/admin` section inside the Next.js app. Sessions via httpOnly cookie; users in `admin_users`.

- **Products** — table of mirrored products (name, SKU, price, stock, override status: none / partial / full). Edit screen: the five override fields, with the ATS originals shown alongside for reference. Price/stock/images displayed read-only.
- **Blog** — CRUD for EA articles with a rich-text editor and meta fields.
- **Pages** — edit About Us and other static pages.
- **Sync dashboard** — last webhook received, last cron runs and outcomes, out-of-stock count, recent `sync_log` entries, and a "Sync everything now" button (runs the reconcile on demand).

## Cart & checkout (headless, guest, v1)

- EA issues its own visitor session cookie; the EA server stores the mapping session → Woo **Cart-Token** (returned by the Store API, identifies a server-side cart on ATS).
- Proxied Store API operations: add/update/remove items, apply/remove coupon, set customer address → returns live shipping rates from ATS table-rate rules, select shipping rate.
- Checkout: customer enters billing/shipping on EA → card details go into **Stripe Elements** on the EA page using ATS's Stripe **publishable** key → resulting PaymentMethod id submitted with `POST /wc/store/v1/checkout` (payment_method `stripe`) via the EA server → Stripe gateway charges server-side on ATS; 3-D Secure handled in the browser via the returned client_secret when required → order lands in ATS admin as `processing`; EA shows its own confirmation page.
- Coupons (e.g. free24) apply through the Store API, so all mother-site logic — including the free-shipping premium-Special-Delivery exclusion in the theme — holds automatically, because the theme loads on Store API requests.
- Apple Pay / Google Pay (phase 4): Stripe Express Checkout Element on the EA domain, feeding the same checkout call. Requires the final EA domain registered in the ATS Stripe dashboard (can only be completed after the domain is purchased).
- Customer accounts (phase 4): login + order history on EA, backed by theme-hosted endpoints on the mother site (credential check issuing a short-lived token; order-history lookup proxied server-side). Detailed design deferred to that phase.

## Mother-site changes (all in `skylinewp-dev-child` theme)

Kept deliberately tiny, in `functions/` following the existing pattern (cf. `free-shipping-exclude-premium.php`):

1. **EA order tagging** — when a Store API checkout request carries the secret `X-EA-Channel` header (added by the EA server proxy), set order meta `_sales_channel = ea` and show a small "EA" indicator column/badge in the orders list. Reporting/filtering by channel comes free.
2. **(Phase 4) Account endpoints** — REST routes for EA login and order history.

Non-code production setup (WP admin, no deploy needed): create a read-only wc/v3 REST API key for the sync; create the four product webhooks pointing at the EA endpoint with a shared secret; (phase 4) register the EA domain with Stripe.

## Security

- wc/v3 key is read-only and lives only in the EA server env, never the browser.
- Webhook receiver verifies the WooCommerce HMAC signature; rejects otherwise.
- All Store API traffic is server-to-server; the Cart-Token store is server-side only.
- Stripe secret key is never involved on EA (charges happen on ATS; EA only uses the publishable key).
- `X-EA-Channel` is a shared secret so third parties can't tag orders as EA.
- Admin: bcrypt passwords, httpOnly session cookies, rate-limited login.
- Totals are never trusted from the client: the Store API computes all prices, shipping and discounts on ATS.

## Failure behaviour

- **ATS unreachable:** browsing, search and product pages work fully from the mirror; cart/checkout actions show a friendly "please try again shortly" message.
- **Missed webhook:** the 10-minute stock cron and nightly reconcile self-heal the mirror.
- **Stock race at purchase:** the Store API is the final authority — it rejects the checkout and EA surfaces the message. The mirror never overrides live stock at checkout time.
- **Image download failure:** sync logs it and falls back to re-attempting on the next reconcile; the product still renders (placeholder if no image yet).

## Build phases

1. **Foundation** — EA app skeleton on the staging subdomain, MySQL schema, initial full import from production, webhook receiver + crons, EA admin (products/overrides + sync dashboard).
2. **Commerce** — cart, guest card checkout via Stripe, coupons; EA order tagging added to the theme. Checkout is exercised end-to-end against ATS **staging** (Stripe test mode) first; the theme change ships to production via the normal staging→prod flow; then one small real production order, refunded, to verify live.
3. **Content & launch** — blog, static pages, SEO (meta from overrides, sitemap, canonicals, product JSON-LD), design polish, deploy to the purchased domain.
4. **Extras** — Apple Pay / Google Pay, customer accounts.

## Testing approach

- Sync: unit tests for webhook signature verification and upsert logic; a replayable fixture of wc/v3 product payloads (simple + variable + out-of-stock).
- Checkout: full flow against ATS staging in Stripe test mode (cards incl. 3DS challenge card), then a single live smoke order refunded immediately.
- Admin: auth + override fallback rendering tests.
- Ops: kill-ATS simulation (point EA at an unreachable host) to confirm graceful degradation.

## Out of scope

- Any change to ATS pricing, stock handling, shipping rules, payment configuration or email branding.
- Red Frog rewards, back-in-stock notifications and other ATS loyalty features on EA.
- Multi-currency / multi-language.
- Reviews on EA (ATS review content stays on ATS; EA product pages launch without reviews).
