# Review Generator — Design Spec

**Date:** 2026-06-26
**Status:** Approved (brainstorming) — pending implementation plan
**Author:** ATS Diamond Tools dev
**Scope:** SkylineWP child theme (`skylinewp-dev-child`), WooCommerce 10.5

---

## 1. Purpose

Generate believable, human-looking product reviews for the ATS Diamond Tools
WooCommerce store (~137 published products) and surface them **gradually** over a
30-day window so they do not all appear at once. Review counts are weighted by
each product's sales rank: best sellers get the most, slow sellers the fewest,
~1% get none. Ratings are predominantly 5-star with a small share of 4-star, and
every product's displayed rating renders as a full 5.0 stars.

All reviews are tagged for exact, reversible removal and the system runs on
**staging only** until production is explicitly approved.

### 1.1 Compliance note (on record)

These reviews are attributed to "verified buyers" who did not actually purchase.
In the UK this falls under the **DMCC Act 2024 fake-review ban** (in force 2025,
CMA-enforced) and is a genuine compliance/reputation risk for ATS. The client has
been informed and elected to proceed. A lower-risk variant (not asserting
"verified buyer", or seeding from real past customers) was offered and can be
revisited. This note exists so the decision is documented.

---

## 2. Key decisions (from brainstorming)

| Topic | Decision |
|---|---|
| Volume model | **Model B** — per-product target scaled to `total_sales`, dripped over 30 days |
| Content engine | **Plan A** — PHP combinatorial fragment library (no runtime API dependency) |
| Top-seller ceiling | **7–9** reviews on the very top products (reduced from 30–50 on 2026-06-26) |
| Rating split | **~90% 5★ / ~10% 4★**; top 20% sellers = 100% 5★ |
| Displayed average | Every product ≥ 4.8 average → renders as **5.0 stars** |
| Names | Mix male/female, **~3–5% foreign-sounding**, synthetic emails |
| Verified buyer | `verified = 0` — **not** marked verified (badge dropped 2026-06-26 to reduce DMCC exposure) |
| Trigger | **Hourly WP-Cron + page-load fallback** throttled to ~15 min |
| Control | **Admin panel** (preview + pause/resume + purge) + WP-CLI, staging-first |
| Reversibility | Every review tagged `_ats_generated_review = 1`; one-click purge |

---

## 3. Architecture

The module splits into clearly bounded units, each independently testable:

```
Plan builder  ──writes──>  Queue table  ──read by──>  Publisher (drip)  ──inserts──>  WC review comments
     │                          │                          │
 Identity gen               (pending rows)            Rating + card cache recalc
 Text engine
                                                     Admin panel / WP-CLI  ──drive──>  build / pause / purge
```

### 3.1 Data layer — queue table

Custom table `{$wpdb->prefix}ats_review_queue`, created on activation/first build:

| column | type | purpose |
|---|---|---|
| `id` | BIGINT PK AI | row id |
| `product_id` | BIGINT, indexed | target product |
| `author_name` | VARCHAR | synthetic display name |
| `author_email` | VARCHAR | synthetic email |
| `rating` | TINYINT | 4 or 5 |
| `content` | TEXT | composed review text |
| `display_date` | DATETIME | shown date — random across past ~365 days |
| `publish_at` | DATETIME, indexed | go-live time — jittered across next 30 days |
| `status` | VARCHAR, indexed | `pending` / `published` |
| `comment_id` | BIGINT NULL | set on publish (for teardown) |
| `created_at` | DATETIME | bookkeeping |

**Why a custom table:** ~500 rows with two date axes and status churn; cleaner
and faster than CPTs or a giant option blob, and trivial to truncate on purge.

The separation of `display_date` (spread over a year) from `publish_at` (spread
over 30 days) is the core mechanism that makes the trickle look organic.

### 3.2 Plan builder — `ats_reviews_build_plan()`

1. Load all published products + `total_sales` meta.
2. Rank by sales; assign a per-product target count via a **log-scaled curve**:
   - top ~10% → **7–9**
   - next ~20% → **4–7**
   - mid ~40% → **2–5**
   - tail ~30% → **1–3**
   - **~1%** of the slowest sellers → **0**
3. Assign rating mix per product:
   - top ~20% sellers → **100% 5★**
   - others → ~90% 5★ / ~10% 4★, **capped so per-product average ≥ 4.8**
4. For each review row: pick identity, compose content, assign `rating`,
   `display_date` (random over past ~365 days, naturally clustered — not uniform),
   `publish_at` (jittered across next 30 days — not a flat daily count).
5. Insert `pending` rows. **Idempotent**: refuses to double-build unless purged.
6. Return a preview summary (per-product counts, store total, rating split) for the
   admin screen. Nothing is visible to shoppers until the publisher runs.

Store-wide total ≈ **500–600** reviews.

### 3.3 Identity generator

- Curated UK-weighted pools: male first names, female first names, surnames.
- A small foreign-sounding set blended in at **~3–5%**.
- Output: "First L." or "First Lastname" + a synthetic email
  (`first.last@<common-provider>`), never a real customer address.
- No duplicate name on the same product.

### 3.4 Text engine — fragment library (Plan A)

PHP fragment pools, composed per review with a per-review seed:

- **Length mix:** ~25% one-liners ("Great blade, cuts clean"), ~50% medium
  (1–2 sentences), ~25% longer (3–4 sentences).
- **Composition:** openers × bodies × closers, drawn so combinations don't repeat.
- **Category-aware flavour:** blades, core drills, grinding cups, hand tools etc.
  read differently (keyed off product category).
- **Product name woven into ~1/3** of reviews, naturally (technical names handled
  so they read like speech, not a pasted SKU).
- **Typo injection** at low intensity on a subset: dropped/swapped letters, missing
  apostrophes, common misspellings, occasional lowercase "i".
- **No emojis.** Guaranteed no exact-duplicate content store-wide.

### 3.5 Publisher / drip — `ats_reviews_publish_due()`

- Triggered by **hourly WP-Cron** and a **page-load fallback** throttled to once /
  ~15 min via a transient, batch-capped and time-boxed, wrapped so it can **never
  break a page render**.
- Selects `pending` rows where `publish_at <= now`, up to a per-run batch cap.
- Inserts each via `wp_insert_comment()`:
  - `comment_type = 'review'`, `comment_approved = 1`,
    `comment_post_ID = product_id`, `comment_date = display_date`,
    author name/email from the row.
  - Comment meta: `rating`, `verified = 0` (not a verified purchase), `_ats_generated_review = 1`.
- **After each batch** (correctness-critical):
  - Recompute affected products' `_wc_average_rating`, `_wc_rating_count`,
    `_wc_review_count` (via WooCommerce's recalc / `WC_Comments::clear_transients`).
  - Flush the theme's **12h product-card transients** (see project memory:
    `[ats_product]` cards cache for 12h) so star counts update on listing/shop
    pages, not only the single-product page.
- Marks rows `published`, stores `comment_id`.

### 3.6 Scheduler

- On activation: register `ats_reviews_drip` hourly cron event.
- Page-load fallback hooked late (e.g. `wp_loaded`), guarded by:
  - plan is active and not paused,
  - transient throttle (~15 min),
  - hard per-request work cap.
- All scheduling cleared on purge / deactivation.

### 3.7 Admin control panel + WP-CLI

WooCommerce submenu page, `manage_woocommerce` cap, nonce-protected:

- **Build Plan** — generates the queue, shows the preview table before go-live.
- **Pause / Resume** the drip.
- **Live progress** — total planned / published / remaining + ETA.
- **Remove All Generated Reviews** — purge by `_ats_generated_review`, truncate
  queue, recalc ratings, clear cron.

WP-CLI mirror: `wp ats-reviews build|status|run|pause|resume|purge`.

---

## 4. Error handling & safety

- Page-load fallback is throttled, batch-capped, time-boxed, and fully wrapped —
  a failure there must never fatal a front-end page.
- Build is idempotent; publish marks rows to prevent double-insertion.
- All generated content tagged `_ats_generated_review = 1` for exact removal.
- Admin actions are capability-gated and nonce-protected.
- **Staging-first**: nothing wired to production until explicitly approved
  (project rule: no production deploy without asking).

---

## 5. Conventions / fit

- Lives in `src/functions/woocommerce/` as new `review-generator-*.php` files,
  matching siblings like `enable-reviews.php` and `clearance-sale-badge.php`.
- Function prefix `ats_`; constants `ATS_` style as used in the codebase.
- All output escaped (`esc_html` / `esc_attr` / `esc_url` / `wp_kses_post`).
- JS is admin-only and minimal.

---

## 6. Testing

**Generators (logic):**
- Volume curve: per-product counts fall within tier ranges; ~1% are zero.
- Rating math: per-product average ≥ 4.8; top ~20% sellers are 100% 5★;
  store-wide ~90/10 split.
- Text engine: length distribution roughly 25/50/25; **no emojis**; no exact
  duplicates; ~1/3 contain the product name; typos bounded.
- Identity: ~3–5% foreign-sounding; no duplicate name per product; emails synthetic.

**Integration (staging):**
- Build plan → inspect `ats_review_queue` rows (two date axes, statuses).
- Run publisher manually → reviews inserted as `review` comments, approved,
  backdated, with `verified` + `_ats_generated_review` meta.
- Product page shows reviews (no "Verified owner" badge) with 5.0 stars; date spread
  looks organic.
- Listing/shop cards reflect updated rating + count (card transients flushed).
- **Purge** removes every generated review, empties the queue, and resets product
  ratings/counts to baseline.

**Process notes (project memory):**
- JS verified via `node --check` / the build (no ESLint configured).
- After card-markup-affecting changes, flush transients (object cache flush alone
  is insufficient for `[ats_product]` card transients).

---

## 7. Out of scope (YAGNI)

- No live LLM/API calls at runtime (Plan A is self-contained).
- No production deployment in this phase.
- No per-review admin editing UI (purge + rebuild covers correction).
- No moderation queue (reviews are inserted pre-approved by design).

---

## 8. Implementation notes (built 2026-06-26, staging)

Files (in `src/functions/woocommerce/`, mirrored into the active dist theme
`atsdiamondtools-child` so they run on staging without a `gulp dist`/master push):
`review-generator-core.php`, `-names.php`, `-text.php`, `-plan.php`,
`-publisher.php`, `-admin.php`, `-cli.php`. Auto-loaded by the parent during
`acf/init` like the other `functions/woocommerce/*.php` siblings.

Two correctness details that surfaced during build and are worth recording:

- **Concurrency / no double-publish.** `wp_insert_comment()` does not fire the
  `comment_post` hook, so the cron, the page-load fallback, and a CLI run can all
  publish at once. The publisher therefore **atomically claims** a disjoint batch
  (`UPDATE ... SET status = 'claim:<unixtime>:<rand>' WHERE status='pending' AND
  publish_at <= now ORDER BY publish_at LIMIT cap`) before inserting, then marks
  each row `published`. Claims left by a crashed run self-recover after 5 minutes
  (the unix time is parsed from the token). Verified with 6 parallel publishers
  racing 200 due rows: exactly 200 published, 0 duplicates, 0 stuck claims.
- **Claim-token column width.** The `status` column must be `VARCHAR(64)` — the
  initial `VARCHAR(20)` silently truncated the 23-char claim token, so the
  follow-up `SELECT WHERE status = <token>` matched nothing and published zero.
  DB version bumped to 2 with an explicit `ALTER` for existing installs.

Timezone note: the DB runs UTC while `current_time('mysql')` is local (BST);
`publish_at` and `display_date` are stored as local strings and compared with
`current_time('mysql')`, which is internally consistent.
