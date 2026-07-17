# Customer Insights — Design Spec

**Date:** 2026-07-17
**Status:** Approved approach — pending final spec review
**Site:** ATS Diamond Tools (staging first; production deploy requires explicit permission)

## Goal

An admin page where the client can browse customers ranked by how much they buy, filter them into useful segments (top buyers, dormant, coupon users, etc.), click any customer for a popup with their full purchase history, and push any filtered segment to a Brevo list for email campaigns.

Serves two purposes equally: **monitoring** (who are the best customers) and **marketing** (build a segment, email it via Brevo).

## Data sources (verified on staging DB)

WooCommerce's analytics lookup tables — indexed, kept in sync by WooCommerce itself:

| Table | Contents | Rows |
|---|---|---|
| `{prefix}wc_customer_lookup` | One row per customer incl. guests (name, email, user_id, country, city) | 16,677 |
| `{prefix}wc_order_stats` | One row per order (date, items sold, totals, status, customer_id) | 27,816 |
| `{prefix}wc_order_product_lookup` | One row per order line item (product_id, qty, revenue) | — |
| `{prefix}wc_order_coupon_lookup` | One row per coupon applied to an order (coupon_id, discount) | — |

- Rankings count orders with status `wc-completed` and `wc-processing` only.
- Fully refunded (`wc-refunded`), cancelled, failed and trashed orders are excluded from rankings. Partial refunds are ignored in v1 (orders count at original totals). Refunded/cancelled orders still appear greyed out in the popup order list.
- Guests are included — `wc_customer_lookup` keys them by billing email.
- HPOS is OFF on this site; the lookup tables work regardless.

## Page & files

New page at **WooCommerce → Customer Insights**, capability `manage_woocommerce`.

Three files in `src/functions/woocommerce/`, following the Review Generator admin pattern:

| File | Responsibility |
|---|---|
| `customer-insights-admin.php` | Menu registration, page markup, filter form, results table, modal shell, page-scoped inline JS |
| `customer-insights-data.php` | All SQL against the lookup tables, segment logic, transient cache |
| `customer-insights-ajax.php` | Nonce-protected `admin-ajax` endpoints: customer popup, send-to-Brevo |

Function prefix `ats_customer_insights_` / `ats_ci_`. Server-rendered PHP; the modal and sort interactions use a small inline script (admin pages do not go through the gulp bundle). All output escaped (`esc_html` / `esc_attr` / `esc_url`).

## Filters & controls

Submitted as GET parameters so filtered views are bookmarkable.

- **Date range** — presets: 30 days / 90 days / 12 months / all time, plus custom from–to date pickers. All ranking stats are computed within this range.
- **Rows per page** — 25 / 50 / 100, with pagination.
- **Minimums** — min products (units), min orders, min total spend. Any combination.
- **Dormant** — "no order in the last N days" (default 180, editable). Measured against today, independent of the selected date range.
- **At-risk** — toggle: customers with 3+ orders whose days-since-last-order exceeds 1.5× their own average gap between orders.
- **Coupons** — "used any coupon" or a specific code chosen from a dropdown of codes actually present in `wc_order_coupon_lookup`.
- **Bought product / category** — product search-select or product-category dropdown; matches customers with at least one qualifying order line in range.
- **Buyer type** — all / repeat / one-time; and all / registered / guest.

## Results table

Columns: rank, customer (name + email; email alone if no name; "guest" badge where `user_id` is NULL), segment badge, orders, units bought, total spend, average order value, last order date, days since last order, coupon-orders count.

Default sort: **units bought, descending**. Also sortable: total spend, orders, last order date.

### Segment badge

Deterministic rules evaluated top-down, first match wins (computed over all-time data, not the filtered range):

1. **Dormant** — last order more than 180 days ago
2. **At-risk** — 3+ orders and days-since-last-order > 1.5× average gap between their orders
3. **One-time** — exactly 1 counted order
4. **Champion** — 5+ orders
5. **Loyal** — 2+ orders

Thresholds (180 days, 1.5×, 5 orders) overridable via an `ats_customer_insights_segment_thresholds` filter so tuning needs no redesign.

## Customer popup

Clicking a row opens a modal loaded via AJAX:

- **Header** — name, email, location (city/country), registered or guest, segment badge
- **Headline stats** — lifetime spend, total orders, total units, AOV, first order date, last order date, average days between orders, and a **"due to reorder"** flag when days-since-last ≥ their average gap (3+ orders only)
- **Top 5 products** — most-bought by quantity
- **Coupons used** — code, times used, total discount taken
- **Order list** — scrollable: date, order number linked to the admin order-edit screen, item summary, order total, status, coupon used. Refunded/cancelled orders shown greyed out.

## Send to Brevo

A **"Send to Brevo"** button beside the filters, operating on the entire filtered result set (all pages, not just the visible one):

1. Modal asks: create a new list (name pre-filled from the active filters and date, e.g. *"Insights: Dormant 180d + coupon — 17 Jul 2026"*) or pick an existing Brevo list from a dropdown.
2. Contacts are bulk-imported via Brevo's `POST /v3/contacts/import` (email, FIRSTNAME, LASTNAME attributes; `updateExistingContacts` true) using the existing `BREVO_API` constant already used by the checkout newsletter subscription.
3. Button reports back: "N contacts sent to list X".

Implementation note: check the in-house `brevo-campaign-generator` plugin for a reusable Brevo API client before writing a new wrapper (`wp_remote_post` fallback if the plugin's client isn't cleanly reusable). New lists are created in a "Customer Insights" Brevo folder (created on first use).

**Consent note (client's call — open question):** the import includes past customers who ticked the newsletter opt-out at checkout. Mitigation: Brevo retains unsubscribe/blacklist state, so importing an unsubscribed contact does not re-enable emails to them. If the client wants checkout opt-outs excluded at source, that is a follow-up depending on whether the opt-out is recorded locally.

## Data layer & performance

- Main ranking query: `wc_customer_lookup` JOIN `wc_order_stats` (counted statuses only), aggregated per customer over the date range; `HAVING` clauses implement the minimums; product/category/coupon filters as `EXISTS` subqueries on the respective lookup tables.
- Segment/cadence figures (average gap, days since last) come from an all-time aggregate per customer, computed in the same pass where possible.
- Ranking results cached in a 15-minute transient keyed `ats_ci_<md5 of filter set>` (same DB-transient approach as the product-card cache).
- If lookup tables ever look stale, WooCommerce → Status → Tools has a regenerate action (noted in code comments).

## Out of scope (v1)

- CSV export (replaced by Send to Brevo)
- Charts/graphs
- Phone numbers (not in lookup tables)
- RFM scoring beyond the segment badge
- Composing/sending campaigns (done inside Brevo)
- Production deploy — staging only until explicitly approved
