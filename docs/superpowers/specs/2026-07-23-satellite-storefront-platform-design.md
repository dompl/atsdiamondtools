# Satellite Storefront Platform — Design

**Date:** 2026-07-23
**Status:** Approved pending user spec review
**Supersedes:** the 2026-07-22 single-site "EA Satellite Shop" draft (scope broadened to a multi-site platform with markup pricing and reviews)

## What this is

A platform for running **several independent storefronts**, each on its own domain with its own design, all selling the ATS Diamond Tools catalogue. Products, images, stock and fulfilment come from the **production** ATS WooCommerce site (www.atsdiamondtools.co.uk). Each satellite controls its own product titles, descriptions, meta titles/descriptions, blog articles, static pages, reviews, **selling price** and **which products it lists**.

The first site is "EA". Adding site two, three and four is configuration, not a rebuild.

Customers never leave the satellite domain. Orders are placed on ATS for fulfilment and payment processing, but the customer sees only the satellite's brand, prices and emails.

## Decisions made

| Decision | Choice |
|---|---|
| Checkout location | Headless on the satellite domain — customer never leaves it; ATS Store API behind the scenes |
| Content storage | Standalone platform admin with its own MySQL database (not in WP admin, not flat files) |
| Stack | Next.js (App Router) + MySQL |
| Hosting | All sites on one server. Developed on the rfsdev staging VPS under a subdomain; deployed to purchased domains later. **Data always pulled from production**, never staging |
| Product data | Shared local mirror + WooCommerce webhooks (instant) + cron (10-min out-of-stock recheck, nightly reconcile) |
| Multi-site model | **One central system, many frontends.** Single codebase, single database, single admin with a site switcher; each site resolved by domain |
| Pricing | Per-site markup (percentage or fixed), with per-product override, uplift cap and rounding rules. **Configured only in the platform admin** |
| Who charges the card | ATS processes the payment at the marked-up total; margin is settled with the ATS owner periodically from a platform report |
| Markup visibility | The ATS owner has no login to the platform admin and cannot see or change markup rules. They do see final order totals, since they process the card |
| Product availability | Everything included by default; tick to exclude per site |
| Reviews | Collected from real satellite customers, stored per site, moderated in the platform admin |
| Transactional emails | **Sent by the satellite**, showing the satellite brand and the price paid. ATS's customer-facing emails are suppressed for satellite orders; ATS admin/fulfilment notifications still fire |
| Mother-site code | Lives in the `skylinewp-dev-child` theme `functions/` folder (not a plugin), following the existing theme-feature pattern; developed on staging, tested, shipped to production via the normal flow |

> **Changed from the 2026-07-22 draft:** emails were originally to be sent by ATS. Because satellites now charge a different price, the customer must receive documents showing the price they actually paid, from the brand they bought from — so the satellite sends them.

## Architecture

```
┌─────────────────────────┐          ┌────────────────────────────────────┐
│  ATS PRODUCTION         │          │  PLATFORM (Next.js + PM2, 1 server)│
│  atsdiamondtools.co.uk  │  webhooks│                                    │
│                         ├─────────►│  MySQL                             │
│  WooCommerce 10.5       │          │   shared: product mirror           │
│  Stripe gateway         │◄─────────┤   per-site: content, pricing,      │
│  Orders + fulfilment    │  Store   │            reviews, orders         │
│                         │  API +   │                                    │
│  theme bridge:          │  wc/v3   │  /admin — site switcher + editors  │
│   · applies markup      ├─────────►│                                    │
│   · tags channel        │  price   │  Domain-routed storefronts:        │
│   · mutes cust. emails  │  rules   │   ea.example      → site 1         │
└─────────────────────────┘          │   site2.example   → site 2         │
                                     └────────────────────────────────────┘
```

The browser never calls ATS directly. Every cart/checkout action goes browser → platform server route → ATS Store API (server-to-server). No CORS surface; API keys never reach the browser. The only client-side third party is Stripe.js.

Each incoming request is resolved to a site by its `Host` header. One codebase, many brands.

## Pricing and markup

The commercial core of the platform, and the part that must never be wrong.

### Rules you configure (platform admin only)

Per site:

- **Markup type** — percentage or fixed amount
- **Markup value** — e.g. 15% or £12.00
- **Maximum uplift cap** (optional) — e.g. never add more than £50 to any single product, so a percentage doesn't run away on high-value machines
- **Minimum uplift** (optional) — e.g. always add at least £2
- **Rounding rule** — none, nearest pound, or charm pricing (`.99`)

Per product, per site: an override of any of the above, or "no markup".

The admin shows a full catalogue price table — base price, uplift, final price and **effective percentage** for every product — so you can scan pricing across the whole site at a glance and spot outliers.

### How the marked-up price is actually charged

1. The platform proxies Store API calls to ATS with a channel key and HMAC signature identifying the site.
2. The ATS theme bridge recognises the channel and fetches that site's **price ruleset** from the platform API (signed request, cached in a WordPress transient).
3. The bridge applies the ruleset through WooCommerce's price filters (`woocommerce_product_get_price`, `..._get_regular_price`, `..._get_sale_price`, plus the variation equivalents).
4. Everything downstream then computes naturally from the marked-up price: line subtotals, VAT, coupon percentages, free-shipping thresholds, order total, and the ATS invoice.

Filtering at the product-price layer, rather than adding a fee line, is what makes the marked-up price look native everywhere instead of bolted on.

**The bridge only ever applies markup to signed channel requests.** Normal atsdiamondtools.co.uk traffic is untouched.

### Never undercharging

If the ATS bridge cannot reach the platform to fetch a ruleset, it uses the **last cached ruleset** for that channel. If it has never had one, it **refuses the checkout** with a clear message rather than silently selling at ATS base prices. Falling back to base prices would give away the margin on every order, so it is explicitly forbidden.

Prices shown to the customer are always recomputed server-side at cart and checkout, so a markup change mid-session can never result in charging a stale figure.

### Margin reconciliation

Every satellite order is recorded locally with the ATS base total, the charged total and the difference. The admin has a per-site margin report by date range — what was sold, what ATS is owed, what margin is due back to you — exportable as CSV, which is what the periodic settlement with the ATS owner runs from.

## Database schema (MySQL)

**Shared across all sites**

- `products` — mirror of the catalogue. `wc_product_id` PK, sku, slug, name, type, status, regular_price, sale_price, stock_status, stock_quantity, manage_stock, weight/dims, categories (JSON), images (JSON, local paths), attributes (JSON), raw wc/v3 payload (JSON), synced_at
- `variations` — wc_variation_id PK, parent_id, attributes, prices, stock_status, stock_quantity, image
- `categories` — wc_category_id PK, slug, name, parent, description, image
- `sync_log` — webhook and cron events with outcome

**Per site**

- `sites` — id, name, domain, theme, channel_key, hmac_secret, branding config (JSON: logo, palette, fonts), markup_type, markup_value, markup_max_uplift, markup_min_uplift, rounding_rule, coupons_enabled, active
- `site_product_settings` — (site_id, wc_product_id) PK: excluded, markup_override_type, markup_override_value, override_title, override_description, override_short_description, override_meta_title, override_meta_description. An empty override field falls back to the ATS value at render time
- `site_posts` — blog articles: slug, title, body, hero image, meta fields, status, published_at
- `site_pages` — static pages: slug, title, body, meta fields
- `site_reviews` — wc_product_id, author, email, rating, title, body, status (pending/approved/rejected), verified_purchase, created_at
- `site_orders` — wc_order_id, order number, customer, base_total, charged_total, margin, status, placed_at
- `admin_users` + `user_site_access` — accounts and which sites each may manage

The product mirror is synced once and serves every site, so adding a site costs no extra sync load.

## Product sync

Three mechanisms, all writing to the shared mirror:

1. **Webhooks (instant).** Production webhooks for `product.created`, `product.updated`, `product.deleted`, `product.restored` → `POST /api/webhooks/wc`. HMAC verified. Upserts the mirror row and downloads new images. A new product on ATS appears on every satellite within seconds (unless excluded), using ATS text until overrides are written. Stock changes fire `product.updated`, so stock transitions propagate immediately too.
2. **Out-of-stock cron (every 10 minutes).** Re-fetches every mirrored product with `stock_status != 'instock'` via wc/v3 (read-only key, batched with `include=`). The explicit backstop for anything webhooks miss.
3. **Nightly reconcile (3am).** Full paginated walk of the production catalogue: upserts everything, syncs categories, flags disappeared products, verifies local image files exist.

**Images are downloaded to the platform server** and served from the satellite domain. No hotlinking to atsdiamondtools.co.uk — keeps each brand separate in page source and makes browsing independent of ATS uptime.

Initial import is the same code path as the nightly reconcile, run once.

## Platform admin

Login-protected `/admin`, with a **site switcher** in the header. Everything below is scoped to the selected site. Sessions via httpOnly cookie; users in `admin_users`, permissioned per site.

- **Sites** — create/edit a site: domain, theme, branding, markup defaults, coupon toggle, active flag. Creating a site is the whole of "set up another one".
- **Products** — table of the catalogue with per-site columns: included/excluded toggle, override status (none/partial/full), base price, uplift, final price, effective %. Edit screen holds the five text override fields (with ATS originals shown alongside) plus the per-product markup override. Stock and images are read-only, always from ATS.
- **Pricing** — the site's markup defaults and rounding, with a live preview across a sample of the catalogue before saving.
- **Reviews** — moderation queue: approve, reject, edit, with verified-purchase flagged.
- **Blog / Pages** — CRUD with a rich-text editor and meta fields.
- **Orders & margin** — satellite orders with base/charged/margin, and the reconciliation report with CSV export.
- **Sync dashboard** — last webhook, last cron runs, out-of-stock count, recent `sync_log`, and a "Sync everything now" button.

## Cart & checkout

- The platform issues its own visitor session cookie per site; the server stores the mapping session → Woo **Cart-Token**.
- Proxied Store API operations: add/update/remove items, apply/remove coupon, set address → live shipping rates from ATS table-rate rules, select rate.
- **Excluded products are validated server-side** before any add-to-cart is proxied, so a crafted request cannot buy a product the site doesn't list.
- Checkout: customer enters billing/shipping → card details go into **Stripe Elements** using ATS's Stripe publishable key → PaymentMethod id submitted with `POST /wc/store/v1/checkout` via the platform server → charged server-side on ATS at the marked-up total; 3-D Secure handled in the browser via the returned client_secret → order lands in ATS admin as `processing`, tagged with its channel → the satellite records the order locally and shows its own confirmation page and sends its own emails.
- **Coupons** are per-site toggleable. When enabled, ATS coupons apply through the Store API and all mother-site logic holds — including the free-shipping premium-Special-Delivery exclusion in the theme — but note percentage coupons discount the marked-up price and free-shipping thresholds are reached sooner. Satellite-only coupon codes are out of scope for v1.
- Apple Pay / Google Pay (final phase): Stripe Express Checkout Element, feeding the same checkout call. Requires each satellite domain registered in the ATS Stripe dashboard, so it can only be completed once domains are purchased.
- Customer accounts (final phase): login and order history on the satellite, backed by theme-hosted endpoints on ATS. Detailed design deferred to that phase.

## Reviews

Real customers only. A review form appears on satellite product pages; submissions land as `pending` in `site_reviews` and appear publicly only after approval in the admin. Where the reviewer's email matches an order placed on that site, the review is flagged **verified purchase**. Aggregate rating per product per site drives the star display and product JSON-LD.

Reviews are per site — ATS's own reviews never appear on satellites, and satellite reviews never flow back to ATS.

## Mother-site changes (all in the child theme)

Kept deliberately small, in `functions/` following the existing pattern (cf. `free-shipping-exclude-premium.php`):

1. **Channel recognition** — validate the channel key and HMAC on incoming Store API requests; tag resulting orders with `_sales_channel` and show the channel in the orders list.
2. **Markup application** — fetch and cache the channel's price ruleset from the platform API; apply it through the WooCommerce price filters for channel requests only; refuse checkout if no ruleset is available.
3. **Email suppression** — disable customer-facing WooCommerce emails for channel orders (admin/fulfilment notifications still fire), since the satellite sends its own.
4. **(Final phase) Account endpoints** — REST routes for satellite login and order history.

Non-code production setup, done in WP admin with no deploy: create a read-only wc/v3 REST API key; create the four product webhooks with a shared secret; register satellite domains with Stripe when they exist.

## Security

- wc/v3 key is read-only and lives only in the platform server env, never the browser.
- Webhook receiver verifies the WooCommerce HMAC signature.
- All Store API traffic is server-to-server; Cart-Tokens are stored server-side only.
- Stripe secret key is never involved on the platform; charges happen on ATS, the platform uses only the publishable key.
- Channel key + HMAC in both directions: only signed requests get markup applied, and only ATS can fetch price rulesets.
- Markup rules are never exposed on any public endpoint or in any client payload.
- Admin: bcrypt passwords, httpOnly session cookies, rate-limited login, per-site permissions.
- Totals are never trusted from the client; ATS computes all prices, shipping and discounts.
- Review submissions are rate-limited and spam-filtered, and never publish without moderation.

## Failure behaviour

- **ATS unreachable:** browsing, search and product pages work fully from the mirror; cart/checkout actions show a friendly "please try again shortly" message.
- **Platform unreachable from ATS:** the bridge uses the last cached price ruleset. With no cached ruleset it refuses the checkout rather than charging base prices.
- **Missed webhook:** the 10-minute stock cron and nightly reconcile self-heal the mirror.
- **Stock race at purchase:** the Store API is the final authority — it rejects the checkout and the satellite surfaces the message. The mirror never overrides live stock at checkout time.
- **Image download failure:** logged and retried on the next reconcile; the product still renders.
- **Satellite email failure:** the order still completes; failed sends are queued and retried, and flagged in the admin.

## Build phases

1. **Foundation** — platform skeleton with domain routing, MySQL schema, initial full import from production, webhooks + crons, admin with site switcher, product overrides and exclusions. First site live on the staging subdomain.
2. **Pricing engine** — markup rules, per-product overrides, caps and rounding, the catalogue price table, and the ATS theme bridge that applies rulesets. Verified with test orders before any real money moves.
3. **Commerce** — cart, guest card checkout, coupons, order records, satellite-branded emails, margin reconciliation report. Exercised end-to-end against ATS staging in Stripe test mode, then one small real production order, refunded.
4. **Content & reviews** — blog, static pages, review submission and moderation, SEO (meta from overrides, sitemaps, canonicals, product and review JSON-LD), design polish.
5. **Launch & extras** — deploy to the purchased domain, then Apple Pay / Google Pay and customer accounts. Second site stood up to prove the multi-site path.

## Testing approach

- **Sync:** unit tests for webhook signature verification and upsert logic; replayable fixtures of wc/v3 payloads (simple, variable, out-of-stock).
- **Pricing:** the highest-risk area, so it gets the most coverage — a table-driven suite over percentage/fixed, caps, minimums and rounding, including VAT and coupon interaction; an explicit test that a missing ruleset refuses checkout rather than charging base price; and reconciliation figures asserted against known orders.
- **Exclusions:** a crafted add-to-cart for an excluded product must be rejected server-side.
- **Checkout:** full flow against ATS staging in Stripe test mode (including the 3DS challenge card), then a live smoke order refunded immediately.
- **Admin:** auth, per-site permissions, and override fallback rendering.
- **Multi-site:** two sites configured with different markups and exclusions, asserting no bleed between them.
- **Ops:** kill-ATS and kill-platform simulations to confirm both graceful degradation paths.

## Out of scope

- Any change to ATS stock handling, shipping rules, payment configuration or fulfilment.
- Red Frog rewards, back-in-stock notifications and other ATS loyalty features on satellites.
- Multi-currency and multi-language.
- Satellite-specific coupon codes (v1 uses ATS coupons or none).
- Auto-generated reviews — satellite reviews are from real customers only.
