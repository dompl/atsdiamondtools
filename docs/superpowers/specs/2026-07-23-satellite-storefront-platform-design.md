# Satellite Storefront Platform — Design

**Date:** 2026-07-23
**Status:** Approved pending user spec review
**Supersedes:** the 2026-07-22 single-site "EA Satellite Shop" draft

## What this is

A platform for running **several independent storefronts**, each on its own domain with its own design, all selling the ATS Diamond Tools catalogue. Products, images, stock, shipping, payment and fulfilment come from the **production** ATS WooCommerce site (www.atsdiamondtools.co.uk). Each satellite controls its own product titles, descriptions, meta titles/descriptions, blog articles, static pages, reviews, **selling price**, **which products it lists**, **which coupons work** and **whether it follows ATS sales**.

The first site is "EA". Adding site two, three and four is configuration, not a rebuild.

Customers browse and pay without leaving the satellite domain. ATS is not hidden from them after the sale: the packing slip in the box, the returns process and the card statement all carry ATS branding, by decision. See **Accepted risks**.

## Decisions made

| Decision | Choice |
|---|---|
| Checkout location | Headless on the satellite domain — customer never leaves it; ATS Store API behind the scenes |
| Content storage | Standalone platform admin with its own MySQL database (not in WP admin, not flat files) |
| Stack | Next.js (App Router) + MySQL |
| Hosting | All sites on one server. Developed on the rfsdev staging VPS under a subdomain; deployed to purchased domains later. **Data always pulled from production**, never staging |
| Product data | Shared local mirror + WooCommerce webhooks (instant) + cron (10-min out-of-stock recheck, nightly reconcile) |
| Multi-site model | **One central system, many frontends.** Single codebase, database and admin with a site switcher; each site resolved by domain |
| Pricing | Per-site markup (percentage or fixed), with per-product override, uplift cap and rounding rules. **Configured only in the platform admin** |
| **Sale prices** | **Per-site toggle.** Follow ATS sales (markup applied to both normal and sale price, so the discount percentage matches) or ignore them and always sell at the marked-up normal price |
| **Coupons** | **Per-site allowlist.** No ATS code works on a satellite unless explicitly added to that site's list |
| **Shipping** | Methods and rates come live from ATS. Free-shipping threshold inherited (currently £200 UK). Individual methods can be hidden per site |
| Who charges the card | ATS processes the payment at the marked-up total; margin settled periodically from a platform report |
| Markup visibility | The ATS owner has no login to the platform admin and cannot see or change markup rules. They do see final order totals, since they process the card |
| Product availability | Everything included by default; tick to exclude per site |
| Reviews | Collected from real satellite customers, stored per site, moderated in the platform admin |
| Transactional emails | Sent by the satellite, showing its brand and the price paid. ATS's customer-facing emails are suppressed for satellite orders; ATS fulfilment notifications still fire |
| Customer accounts | **Shared with ATS** — one login across ATS and all satellites. Each satellite shows **only its own orders** |
| Invoices & packing slips | **ATS branding, unchanged.** No per-channel templating |
| Returns | **Handled by ATS directly.** Satellite returns policy points the customer at ATS's process |
| Deliveries | Tracking pulled from ATS's Shipment Tracking plugin and shown on the satellite; ATS's own tracking email suppressed |
| Mother-site code | Lives in the `skylinewp-dev-child` theme `functions/` folder (not a plugin), following the existing theme-feature pattern; developed on staging, tested, shipped to production via the normal flow |

> **Changed from the 2026-07-22 draft:** emails moved from ATS to the satellite, because satellites charge a different price and the customer must receive documents showing the price they actually paid.

> **Confirmed:** "MOTO" meant *mother*. A satellite account lists only that satellite's own orders — never atsdiamondtools.co.uk's, and never another satellite's.

## Architecture

```
┌─────────────────────────┐          ┌────────────────────────────────────┐
│  ATS PRODUCTION         │ product +│  PLATFORM (Next.js + PM2, 1 server)│
│  atsdiamondtools.co.uk  │ order    │                                    │
│                         │ webhooks │  MySQL                             │
│  WooCommerce 10.5       ├─────────►│   shared: product mirror           │
│  Stripe gateway         │          │   per-site: content, pricing,      │
│  Shipping, orders,      │◄─────────┤            reviews, order records  │
│  invoices, returns      │  Store   │                                    │
│                         │  API +   │  /admin — site switcher + editors  │
│  theme bridge:          │  wc/v3 + │                                    │
│   · enforces ruleset    │  auth    │  Domain-routed storefronts:        │
│   · tags channel        │          │   ea.example      → site 1         │
│   · mutes cust. emails  │ channel  │   site2.example   → site 2         │
│   · auth + order history│ ruleset  │                                    │
└─────────────────────────┘◄─────────┴────────────────────────────────────┘
```

The browser never calls ATS directly. Every cart, checkout and account action goes browser → platform server route → ATS (server-to-server). No CORS surface; API keys never reach the browser. The only client-side third party is Stripe.js.

Each incoming request is resolved to a site by its `Host` header. One codebase, many brands.

## The channel ruleset

Everything ATS needs to know about a satellite travels as **one signed document**, fetched by the theme bridge and cached in a WordPress transient. One fetch, one cache, one failure mode — rather than a separate lookup per rule.

A ruleset contains:

- **Markup rules** — the site default and every per-product override
- **Sale policy** — follow ATS sales, or ignore them
- **Coupon allowlist** — the only codes accepted on this channel
- **Excluded products** — product ids this site does not sell
- **Hidden shipping methods** — method instance ids to withhold from this site

The bridge enforces every one of these **server-side**. Hiding a product, a coupon or a shipping method in the satellite's UI is presentation; the ruleset is what makes it real, so a crafted request cannot buy an excluded product, redeem a coupon that isn't allowlisted, or pick a withheld shipping method.

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

The admin shows a full catalogue price table — base price, uplift, final price and **effective percentage** for every product, with on-sale products flagged — so you can scan pricing across the whole site at a glance and spot outliers.

### Sale prices

44 of the 137 products are currently on sale, several at 50% off, so this is a mainstream case rather than an edge one.

Each site chooses its **sale policy**:

- **Follow ATS sales** — markup is applied to the normal price and the sale price alike, so the satellite shows the same percentage off. A £327.71 blade at 50% off becomes about £377 struck through and £188 to pay at 15% markup. Margin holds through the promotion.
- **Ignore ATS sales** — the satellite always sells at the marked-up normal price and displays no sale. The bridge **neutralises the sale price server-side**, not just in the satellite's UI, so the displayed price and the charged price cannot diverge.

Because the policy lives in the ruleset, the same rule governs the storefront and the checkout by construction.

### How the marked-up price is actually charged

1. The platform proxies Store API calls to ATS with a channel key and HMAC signature identifying the site.
2. The ATS theme bridge recognises the channel and loads that site's ruleset (fetched from the platform API, cached in a transient).
3. The bridge applies markup through WooCommerce's price filters (`woocommerce_product_get_price`, `..._get_regular_price`, `..._get_sale_price`, plus the variation equivalents), honouring the sale policy.
4. Everything downstream then computes naturally from the marked-up price: line subtotals, VAT, coupon percentages, free-shipping thresholds, order total, and the ATS invoice and packing slip.

Filtering at the product-price layer, rather than adding a fee line, is what makes the marked-up price look native everywhere instead of bolted on. Because ATS's own documents read the order's stored line items, the ATS-branded invoice shows the satellite price with no extra work.

**The bridge only ever applies a ruleset to signed channel requests.** Normal atsdiamondtools.co.uk traffic is untouched.

### Never undercharging

If the ATS bridge cannot reach the platform to refresh a ruleset, it uses the **last cached ruleset** for that channel. If it has never had one, it **refuses the checkout** with a clear message rather than silently selling at ATS base prices. Falling back to base prices would give away the margin on every order, so it is explicitly forbidden.

Prices shown to the customer are always recomputed server-side at cart and checkout, so a markup or policy change mid-session can never result in charging a stale figure.

### Margin reconciliation

Every satellite order is recorded locally with the ATS base total, the charged total and the difference. Refunds and cancellations sync back from ATS and **reverse the margin proportionally**, so the report never claims margin on money that was given back.

The admin has a per-site margin report by date range — sold, refunded, net owed to ATS, net margin due to you — exportable as CSV, which is what the periodic settlement with the ATS owner runs from.

## Shipping

Shipping needs almost no new code: rates already come back live from ATS through the same Store API call the cart uses, calculated by ATS's table-rate rules against the customer's real address.

ATS currently offers 1st Class, 2nd Class and Special Delivery next-day in the UK, with free shipping over **£200** (set to ignore coupon discounts), plus separate zones for Europe, Asia, Australia and Rest of World.

- **Methods pass through as configured.** Anything ATS adds, renames or reprices appears on the satellites with no work.
- **Per-site hiding.** A site can withhold individual methods — an international zone it doesn't want to serve, say — enforced through the ruleset rather than only hidden in the UI.
- **The free-shipping threshold is inherited.** Because satellite prices are higher, customers cross £200 with slightly fewer goods, so ATS absorbs marginally more delivery cost. Accepted as the simplest option and always in step with ATS.
- **Existing mother-site shipping logic keeps working**, including the theme's rule that a free-shipping coupon must not zero the premium Special Delivery rates, because the theme loads on channel requests exactly as it does on normal ones.

## Coupons

Each site holds an **allowlist**. A code works only if it is on that site's list; everything else is rejected, whether typed into the satellite's coupon field or injected into a crafted request.

This matters because ATS has 19 live codes, several of them generic (`ats10`, `AUG10`, `MB20`, `JANBLUES`), and the packing slip already points customers at ATS. Without an allowlist, anyone who searches for an ATS discount code could take 10–20% straight off a marked-up price.

Adding a code to a site's list is a deliberate act in the admin, done when you want to run the same promotion. Satellite-only coupon codes, defined on the platform rather than in ATS, are out of scope for v1.

## Customer accounts

**One login across ATS and every satellite**, with per-site order visibility.

- **Where accounts live:** ATS's WordPress customer records. The platform stores no passwords.
- **Login:** satellite form → platform server → signed request to an ATS theme endpoint → `wp_authenticate()` → returns a short-lived token plus customer id, name and email. The platform holds the session in an httpOnly cookie.
- **Registration:** creates a WooCommerce customer on ATS, stamped with the channel it originated from.
- **Password reset:** requested on the satellite; ATS issues the reset key, the satellite emails a link to **its own** reset page, and the new password is posted back to ATS. The customer never lands on atsdiamondtools.co.uk mid-flow.
- **Order history:** the platform requests orders for that customer, and **ATS filters them to the requesting channel before returning anything**. Mother-site orders and other satellites' orders never leave ATS, so they cannot leak through the API, the UI or a crafted request. The channel is taken from the authenticated channel key, never from a client-supplied value.
- **Addresses:** shared across ATS and all satellites, which is a genuine convenience of the shared-account model.
- **Consequence:** a customer logging in at atsdiamondtools.co.uk sees all their orders, satellite ones included, at the prices they paid. Unavoidable with shared accounts, and consistent with ATS being visible elsewhere.

## Deliveries and tracking

ATS already runs the Shipment Tracking plugin, so no new fulfilment tooling is needed.

- Order status and tracking (carrier, tracking number, ship date) sync back from ATS via order webhooks.
- The satellite shows status and tracking on the order-history page and the order confirmation page.
- The satellite emails the customer when the order ships, under its own brand, with the tracking link.
- ATS's own shipment-tracking email is suppressed for channel orders, alongside the other customer-facing emails.

## Invoices, packing slips and returns

**Unchanged from how ATS works today.** No per-channel PDF templating is built.

- The packing slip in the box and the PDF invoice carry ATS's logo, address and VAT footer. Both show the **satellite price**, because the order's stored line items are already marked up.
- Returns are handled by ATS end to end. Each satellite has a returns policy page pointing the customer at ATS's process.
- Refunds are processed by ATS in Stripe. The refund syncs back to the platform, updates the order record and reverses the margin in the reconciliation report.

## Database schema (MySQL)

**Shared across all sites**

- `products` — mirror of the catalogue. `wc_product_id` PK, sku, slug, name, type, status, regular_price, sale_price, sale_dates, stock_status, stock_quantity, manage_stock, weight/dims, categories (JSON), images (JSON, local paths), attributes (JSON), raw wc/v3 payload (JSON), synced_at
- `variations` — wc_variation_id PK, parent_id, attributes, regular_price, sale_price, stock_status, stock_quantity, image
- `categories` — wc_category_id PK, slug, name, parent, description, image
- `sync_log` — webhook and cron events with outcome

**Per site**

- `sites` — id, name, domain, theme, channel_key, hmac_secret, branding config (JSON), markup_type, markup_value, markup_max_uplift, markup_min_uplift, rounding_rule, follow_ats_sales, hidden_shipping_methods (JSON), active
- `site_product_settings` — (site_id, wc_product_id) PK: excluded, markup_override_type, markup_override_value, override_title, override_description, override_short_description, override_meta_title, override_meta_description. An empty override field falls back to the ATS value at render time
- `site_coupons` — (site_id, coupon_code) PK: the allowlist, plus added_at and a note
- `site_posts` — blog articles: slug, title, body, hero image, meta fields, status, published_at
- `site_pages` — static pages: slug, title, body, meta fields
- `site_reviews` — wc_product_id, author, email, rating, title, body, status (pending/approved/rejected), verified_purchase, created_at
- `site_orders` — wc_order_id, order number, wc_customer_id, email, status, base_total, charged_total, margin, refunded_total, shipping_method, shipping_total, tracking_carrier, tracking_number, shipped_at, placed_at, updated_at
- `admin_users` + `user_site_access` — platform staff accounts and which sites each may manage

No customer passwords or customer records are stored on the platform — accounts live on ATS.

## Product and order sync

Writing to the shared mirror and to per-site order records:

1. **Product webhooks (instant).** `product.created`, `product.updated`, `product.deleted`, `product.restored` → `POST /api/webhooks/wc`. HMAC verified. Upserts the mirror row and downloads new images. A new product on ATS appears on every satellite within seconds (unless excluded), using ATS text until overrides are written. Stock changes and sale-price changes both fire `product.updated`, so a sale starting or ending propagates immediately.
2. **Order webhooks.** `order.updated` → updates the matching `site_orders` row with status, tracking and refunded amount. This is what keeps account pages, shipping notifications and the margin report truthful after the sale.
3. **Out-of-stock cron (every 10 minutes).** Re-fetches every mirrored product with `stock_status != 'instock'` via wc/v3 (read-only key, batched with `include=`). The explicit backstop for anything webhooks miss.
4. **Nightly reconcile (3am).** Full paginated walk of the production catalogue: upserts everything, syncs categories, flags disappeared products, verifies local image files exist. Also re-pulls any order updated in the last 48 hours, so a missed order webhook cannot leave a stale status or an unrecorded refund.

Scheduled sales are handled by storing the sale start and end dates in the mirror and evaluating them at render time, so a sale that expires overnight does not need a webhook to disappear.

**Images are downloaded to the platform server** and served from the satellite domain. No hotlinking to atsdiamondtools.co.uk — keeps each brand separate in page source and makes browsing independent of ATS uptime.

Initial import is the same code path as the nightly reconcile, run once.

## Platform admin

Login-protected `/admin`, with a **site switcher** in the header. Everything below is scoped to the selected site. Sessions via httpOnly cookie; users in `admin_users`, permissioned per site.

- **Sites** — create/edit a site: domain, theme, branding, markup defaults, sale policy, hidden shipping methods, active flag. Creating a site is the whole of "set up another one".
- **Products** — table of the catalogue with per-site columns: included/excluded toggle, override status, base price, uplift, final price, effective %, on-sale flag. Edit screen holds the five text override fields (with ATS originals shown alongside) plus the per-product markup override. Stock and images are read-only, always from ATS.
- **Pricing** — markup defaults, rounding and sale policy, with a live preview across a sample of the catalogue (including on-sale products) before saving.
- **Coupons** — the site's allowlist, choosing from the codes that exist on ATS.
- **Reviews** — moderation queue: approve, reject, edit, with verified-purchase flagged.
- **Blog / Pages** — CRUD with a rich-text editor and meta fields.
- **Orders & margin** — satellite orders with status, tracking, base/charged/refunded/margin, and the reconciliation report with CSV export.
- **Sync dashboard** — last product and order webhook, last cron runs, out-of-stock count, ruleset version currently cached by ATS, recent `sync_log`, and a "Sync everything now" button.

## Cart & checkout

- The platform issues its own visitor session cookie per site; the server stores the mapping session → Woo **Cart-Token**.
- Proxied Store API operations: add/update/remove items, apply/remove coupon, set address → live shipping rates from ATS table-rate rules, select rate.
- **Excluded products, non-allowlisted coupons and hidden shipping methods are rejected by the ruleset on ATS**, as well as being absent from the satellite UI.
- Checkout: customer enters billing/shipping → card details go into **Stripe Elements** using ATS's Stripe publishable key → PaymentMethod id submitted with `POST /wc/store/v1/checkout` via the platform server → charged server-side on ATS at the marked-up total; 3-D Secure handled in the browser via the returned client_secret → order lands in ATS admin as `processing`, tagged with its channel → the platform records the order locally, shows its own confirmation page and sends its own emails.
- Logged-in customers check out with their saved ATS address prefilled; guests check out without an account and can register afterwards with the same email to see the order in their history.
- The cart shows progress toward the inherited free-shipping threshold, calculated from the satellite's own prices so the figure always matches what ATS will decide.
- Apple Pay / Google Pay (final phase): Stripe Express Checkout Element, feeding the same checkout call. Requires each satellite domain registered in the ATS Stripe dashboard, so it can only be completed once domains are purchased.

## Reviews

Real customers only. A review form appears on satellite product pages; submissions land as `pending` in `site_reviews` and appear publicly only after approval in the admin. Where the reviewer's email matches an order placed on that site, the review is flagged **verified purchase**. Aggregate rating per product per site drives the star display and product JSON-LD.

Reviews are per site — ATS's own reviews never appear on satellites, and satellite reviews never flow back to ATS.

## What goes on the mother site

**Code on ATS is required.** A satellite cannot charge a different price, block a coupon or hide another site's orders on its own — ATS calculates every total and owns every order, so ATS has to do those things. The bridge is deliberately small and additive: no existing file is rewritten, nothing about normal atsdiamondtools.co.uk behaviour changes, and every hook exits immediately unless the request carries a valid channel signature.

### Files

New files in `src/functions/woocommerce/`, which the theme auto-loads (`src/functions.php:52`), following the same one-feature-per-file convention as `bundle-*.php` and `customer-insights-*.php`:

| File | Responsibility |
|---|---|
| `satellite-channel.php` | Validate the channel key and HMAC on incoming requests; identify which satellite is calling. Everything else no-ops without this |
| `satellite-ruleset.php` | Fetch the channel ruleset from the platform, cache it in a transient, refuse checkout if none is available |
| `satellite-pricing.php` | Apply markup and sale policy through the WooCommerce price filters |
| `satellite-restrictions.php` | Reject excluded products and non-allowlisted coupons; withhold hidden shipping methods |
| `satellite-orders.php` | Tag orders with `_sales_channel`; add the channel column and filter to the admin orders list |
| `satellite-emails.php` | Suppress customer-facing emails, including shipment tracking, for channel orders. Admin and fulfilment notifications still fire |
| `satellite-accounts.php` | Login, registration, password reset and order-history endpoints, with order history filtered to the requesting channel |

Developed on staging, tested there, then shipped to production through the normal deploy flow.

### Configuration (WP admin, no deploy)

- A **read-only wc/v3 REST API key** for the catalogue sync.
- **Webhooks** for `product.created`, `product.updated`, `product.deleted`, `product.restored` and `order.updated`, pointing at the platform with a shared secret.
- **Satellite domains registered with Stripe**, once purchased, for Apple Pay and Google Pay.

### What does not change on ATS

Its own storefront, prices, checkout, shipping rules, emails, invoices, packing slips, returns process and admin all behave exactly as they do today. The bridge is invisible to normal traffic.

## Settings available on each satellite

Everything you can control from the platform admin, and everything you cannot.

### Platform-wide (set once, shared by every site)

| Setting | Notes |
|---|---|
| ATS production URL | The mother site every satellite reads from |
| wc/v3 key and secret | Read-only, used for catalogue and order sync |
| Webhook shared secret | Verifies incoming product and order webhooks |
| Stripe publishable key | ATS's key, used by every satellite's card form |
| Outgoing mail (SMTP) | One relay serving all sites |
| Sync timings | Out-of-stock interval (default 10 minutes) and nightly reconcile hour |
| Admin users and site access | Who can log in, and which sites each may manage |

### Per site — identity and design

- Site name and domain
- Theme (which component set the storefront renders with)
- Logo and favicon
- Colour palette and fonts
- Contact details: address, phone, email, opening hours
- Social links
- Active or offline (offline shows a holding page)
- Search-engine visibility — keep satellites `noindex` while they live on the staging subdomain

### Per site — commerce

- Markup type: percentage or fixed
- Markup value
- Maximum uplift cap
- Minimum uplift
- Rounding rule: none, nearest pound, or `.99`
- Sale policy: follow ATS sales or ignore them
- Coupon allowlist
- Hidden shipping methods
- Channel key and HMAC secret — generated on site creation, rotatable

### Per site — content and SEO

- Homepage sections
- Meta title template or suffix
- Default meta description
- Analytics and tracking IDs (GA4, Meta Pixel, Google Ads)
- Returns policy, terms and privacy page content

Sitemaps, canonicals and product structured data are generated automatically and need no setting.

### Per site — email

- Sender name and from address
- Reply-to address
- Email logo and footer text
- Which notifications send: order confirmation, order shipped

### Per product, per site

- Included or excluded from this site
- Product title override
- Description override
- Short description override
- Meta title override
- Meta description override
- Markup override: type, value, or no markup

Any override left empty falls back to the ATS value, so a new product is sellable the moment it syncs.

### Per content item

- **Blog posts** — title, slug, body, hero image, meta title, meta description, status, publish date
- **Pages** — title, slug, body, meta title, meta description
- **Reviews** — approve, reject or edit; author, rating, body, verified-purchase flag

### Fixed — always from ATS, not settable on a satellite

- Product images, categories, attributes and variations
- Base prices before markup
- Stock levels and stock status
- Shipping zones, methods, rates and the free-shipping threshold
- Coupon definitions and their discount amounts — satellites only allow or deny existing codes
- VAT rates
- Order fulfilment, status and tracking
- Invoice and packing slip design
- The returns process

## Security

- wc/v3 key is read-only and lives only in the platform server env, never the browser.
- Webhook receivers verify the WooCommerce HMAC signature.
- All Store API and account traffic is server-to-server; Cart-Tokens and sessions are stored server-side only.
- Stripe secret key is never involved on the platform; charges happen on ATS, the platform uses only the publishable key.
- Channel key + HMAC in both directions: only signed requests get a ruleset applied, and only ATS can fetch rulesets.
- **Every per-site restriction is enforced on ATS, not only in the satellite UI** — excluded products, coupon allowlist, hidden shipping methods and sale policy all live in the ruleset.
- Markup rules are never exposed on any public endpoint or in any client payload.
- **Order history is channel-filtered on ATS**, derived from the authenticated channel key rather than any client-supplied value, so one site's orders can never be read from another.
- Account endpoints are rate-limited and return uniform errors so they cannot be used to enumerate which email addresses exist.
- Platform admin: bcrypt passwords, httpOnly session cookies, rate-limited login, per-site permissions.
- Totals are never trusted from the client; ATS computes all prices, shipping and discounts.
- Review submissions are rate-limited and spam-filtered, and never publish without moderation.

## Failure behaviour

- **ATS unreachable:** browsing, search and product pages work fully from the mirror; cart, checkout and account actions show a friendly "please try again shortly" message.
- **Platform unreachable from ATS:** the bridge uses the last cached ruleset. With no cached ruleset it refuses the checkout rather than charging base prices or ignoring restrictions.
- **Missed product webhook:** the 10-minute stock cron and nightly reconcile self-heal the mirror.
- **Missed order webhook:** the nightly 48-hour order re-pull corrects status, tracking and refunds.
- **Stock race at purchase:** the Store API is the final authority — it rejects the checkout and the satellite surfaces the message. The mirror never overrides live stock at checkout time.
- **Image download failure:** logged and retried on the next reconcile; the product still renders.
- **Satellite email failure:** the order still completes; failed sends are queued and retried, and flagged in the admin.

## Accepted risks

Recorded deliberately, having been raised and accepted:

- **Customers can discover ATS and buy direct.** The packing slip carries ATS's logo, website and VAT footer, and returns go to ATS. A customer who looks up atsdiamondtools.co.uk will find the same products at base prices and can see the markup. Accepted in exchange for zero PDF and returns work. The coupon allowlist limits the damage a leaked code can do.
- **The card statement shows ATS.** Because ATS's Stripe takes the payment, the customer's bank statement carries ATS's business name. A per-charge suffix can add the satellite name, but the prefix cannot be removed without a separate Stripe account.
- **Merchant of record and VAT.** ATS is the VAT-registered entity taking the money and issuing the invoice. The satellite is effectively reselling, and the arrangement should be confirmed with an accountant and written into the agreement with ATS before launch. This is a commercial action, not a build task.
- **Shared accounts expose satellite orders on ATS.** A customer logging in at atsdiamondtools.co.uk sees their satellite orders at satellite prices.
- **Free shipping costs ATS slightly more.** Marked-up prices reach the £200 threshold with fewer goods.

## Build phases

1. **Foundation** — platform skeleton with domain routing, MySQL schema, initial full import from production, product webhooks + crons, admin with site switcher, product overrides and exclusions. First site live on the staging subdomain.
2. **Pricing and rules engine** — markup rules with caps and rounding, sale policy, coupon allowlist, hidden shipping methods, the catalogue price table, and the ATS theme bridge that fetches and enforces channel rulesets. Verified with test orders before any real money moves.
3. **Commerce** — cart, guest card checkout, live shipping rates, order records, order webhooks, satellite-branded emails, margin reconciliation report. Exercised end-to-end against ATS staging in Stripe test mode, then one small real production order, refunded.
4. **Accounts, content & reviews** — shared ATS login with channel-filtered order history, tracking display and shipping emails, blog, static pages, review submission and moderation, SEO (meta from overrides, sitemaps, canonicals, product and review JSON-LD), design polish.
5. **Launch & extras** — deploy to the purchased domain, then Apple Pay / Google Pay. Second site stood up to prove the multi-site path.

## Testing approach

- **Sync:** unit tests for webhook signature verification and upsert logic; replayable fixtures of wc/v3 product and order payloads (simple, variable, on-sale, scheduled sale, out-of-stock, partially refunded).
- **Pricing:** the highest-risk area, so it gets the most coverage — a table-driven suite over percentage/fixed, caps, minimums and rounding, crossed with both sale policies, including VAT and coupon interaction; an explicit test that a missing ruleset refuses checkout rather than charging base price; and reconciliation figures asserted against known orders including refunds.
- **Ruleset enforcement:** the security-critical suite. Crafted requests must fail on ATS, not merely be absent from the UI — an excluded product cannot be added to the cart, a non-allowlisted coupon cannot be redeemed, a hidden shipping method cannot be selected, and a site set to ignore sales cannot be charged the sale price.
- **Account isolation:** a customer with orders on ATS, site 1 and site 2 must see only site 1's orders when logged into site 1, verified at the ATS endpoint rather than in the UI.
- **Shipping:** rates for each zone returned correctly, free-shipping threshold crossing at the marked-up total, and the premium Special Delivery exclusion still holding when a free-shipping coupon is applied.
- **Checkout:** full flow against ATS staging in Stripe test mode (including the 3DS challenge card), then a live smoke order refunded immediately, asserting the refund reverses the recorded margin.
- **Admin:** auth, per-site permissions, and override fallback rendering.
- **Multi-site:** two sites with different markups, sale policies, coupon lists and exclusions, asserting no bleed between them.
- **Ops:** kill-ATS and kill-platform simulations to confirm both graceful degradation paths.

## Out of scope

- Any change to ATS stock handling, shipping rules, payment configuration, fulfilment, PDF templates or returns process.
- Red Frog rewards, back-in-stock notifications and other ATS loyalty features on satellites.
- Multi-currency and multi-language.
- Satellite-only coupon codes defined on the platform (v1 allowlists existing ATS codes).
- Per-site free-shipping thresholds — ATS's figure is inherited.
- Satellite-branded invoices and packing slips.
- A returns request flow in the customer account — returns go to ATS directly.
- Auto-generated reviews — satellite reviews are from real customers only.
