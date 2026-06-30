# Review Generator — Complete Operator Runbook

> **What this file is.** A single self-contained guide to the automated product
> review system on ATS Diamond Tools. If you are an AI assistant (Claude) or a
> developer picking this up cold: read this top to bottom and you can operate,
> tune, deploy, verify, and tear it down without any other context. Every path,
> command, config value, and gotcha needed is in here.
>
> **Status:** LIVE on production (https://www.atsdiamondtools.co.uk) since
> 2026-06-27. Also runs on staging (http://atsdiamondtools.rfsdev.co.uk).
>
> **Who does each step** (markers used throughout this doc):
> - 🟣 **Claude Code** can do this for you — editing files, running CLI/SQL,
>   deploying, verifying. Paste the relevant section to Claude and it runs it.
> - 🟢 **You** (human) do this — browser actions, visual checks, and
>   business/compliance decisions Claude shouldn't make for you.
>
> Sections without a dot are reference/context for both to read.

---

## 1. What it does (plain English)

It generates believable, human-looking WooCommerce product reviews and inserts
them as real review comments. The number of reviews per product is weighted by
how well the product sells (best sellers get the most). Reviews look organic:
varied names/nicknames, spelling mistakes, sloppy capitalisation, dates spread
over the past 2.5 years, mostly 5-star with a few 4/3/2-star on weaker products.

It can either **drip** reviews live gradually over 30 days, or **publish them all
at once** (with dates still backdated so they read as long-standing). Production
used publish-all.

Every generated review is tagged so the whole set can be removed in one click.

### Compliance (read once) — 🟢 your call

These reviews are NOT from real buyers. In the UK this falls under the **DMCC Act
2024 fake-review ban** (CMA-enforced, fines up to 10% of global turnover). To
reduce exposure the reviews are deliberately **not** marked "Verified owner". The
client was informed at every step and chose to proceed. This is a business/legal
decision, not a technical one — do not silently change it.

---

## 2. Where the code lives & how it loads

This is a WordPress + WooCommerce child theme. **Critical build topology:**

- **You edit source** in: `skylinewp-dev-child/src/functions/woocommerce/`
- **The ACTIVE theme on every server is the compiled dist** `atsdiamondtools-child`
  (a sibling theme dir). `skylinewp-dev-child` is NOT active.
- These files are **pure PHP (no compilation)**, so to make a change live you
  copy the file from `src/functions/woocommerce/` into the active dist theme's
  `functions/woocommerce/`. Do **not** run `gulp dist` for this — `gulp dist`
  auto-commits a version bump and **pushes to git master**, which we don't want
  for a hotfix-style change.
- All files in `functions/woocommerce/` are **auto-included by the parent theme**
  during the `acf/init` hook. Therefore inside these files: never wrap code in
  `add_action('acf/init', ...)` (it won't fire — acf/init is already running),
  and never call a function from another review-generator file at top level
  (load order is alphabetical; only register hooks at top level).

### File map (8 files)

| File | Responsibility |
|---|---|
| `review-generator-core.php` | Config (`ats_reviews_config()`), queue-table install, run-state option, rating recount + cache flush, purge, cron scheduling. |
| `review-generator-names.php` | Reviewer identity: name pools (male/female/foreign), nicknames, username handles, and the many display-name formats. |
| `review-generator-text.php` | The review text engine: fragment pools, category flavour, sentence composition, spelling-mistake injection, sloppy-capitalisation. |
| `review-generator-plan.php` | Builds the plan: per-product counts (sales-weighted), rating distribution, backdated dates → writes queue rows. |
| `review-generator-publisher.php` | Publishes due reviews (atomic-claim, concurrency-safe), `publish-all`, the hourly cron + page-load fallback. |
| `review-generator-admin.php` | Admin UI: WooCommerce → Review Generator (build/start/pause/publish-all/purge + preview). |
| `review-generator-cli.php` | WP-CLI commands (`wp ats-reviews ...`). |
| `enable-reviews.php` (pre-existing, **modified**) | Added `ats_product_reviews_newest_first()` — forces product reviews newest-first on the initial page render to match the AJAX paginator. |

---

## 3. Data model

- **Queue table** `{$wpdb->prefix}ats_review_queue` (created on first build):
  columns `id, product_id, author_name, author_email, rating, content,
  display_date, publish_at, status, comment_id, created_at`.
  `status` is `pending` / `published` / a transient `claim:<unixtime>:<rand>`
  token during publishing. **`status` MUST be `VARCHAR(64)`** — see gotchas.
- **Options:** `ats_reviews_state` (`status` = idle/paused/active/completed,
  plus built_at, total_planned, window_start, window_end); `ats_reviews_db_version`.
- **Comment meta on each generated review:** `rating` (2–5), `verified` = `0`
  (deliberately not verified), `_ats_generated_review` = `1` (the removal tag).
- **Reviews are standard WooCommerce review comments** (`comment_type = 'review'`,
  approved). The 1–2 genuine pre-existing reviews are never touched (no tag).

---

## 4. Full configuration reference

All tunables live in one function: `ats_reviews_config()` in
`review-generator-core.php`. Current values:

| Key | Value | Meaning |
|---|---|---|
| `drip_days` | 30 | Window the drip spreads publishing over. |
| `backdate_days` | 912 | Oldest displayed review date (~2.5 years ago). |
| `recent_min_days` | 2 | Newest displayed review date (~2 days ago). |
| `cron_batch` | 60 | Max reviews published per hourly cron tick. |
| `pageload_batch` | 12 | Max reviews published per page-load fallback. |
| `pageload_throttle` | 15 min | Min gap between page-load fallback runs. |
| `tiers` | see below | Per-product review-count ranges by sales rank. |
| `zero_pct` | 0.01 | ~1% of slowest sellers get zero reviews. |
| `top_five_star_pct` | 0.20 | Top 20% of sellers are 100% 5-star. |
| `four_star_ratio` | 0.10 | ~10% of (non-top) reviews are 4-star. |
| `min_avg` | 4.8 | 4-stars capped so each product's average stays ≥ this. |
| `low_tier_p` | 0.60 | Products beyond this sales percentile can get 3/2-star. |
| `three_star_pct` | 0.35 | Chance a low-tier product gets a 3-star. |
| `two_star_pct` | 0.15 | Chance a low-tier product gets a 2-star. |
| `typo_pct` | 0.35 | Share of reviews with spelling mistakes. |
| `caps_ignore_pct` | 0.30 | Share of reviews with sloppy capitalisation. |
| `male_pct` | 0.80 | Share of (non-foreign) names that are male. |
| `name_mention_pct` | 0.33 | Share of reviews that mention the product by name. |
| `foreign_pct` | 0.04 | Share of foreign-sounding names. |

**Tiers** (sales-rank percentile 0 = top seller; counts get ±30% jitter, clamped
to a hard 1–9):
- top ~10% → 6–9 reviews
- next ~20% → 4–8
- mid ~40% → 2–6
- tail ~30% → 1–4

**To change behaviour, edit `ats_reviews_config()`, redeploy that one file, then
rebuild** (section 7). Examples:
- "More reviews per product" → raise tier `min`/`max` (hard cap is enforced at 9
  in `ats_reviews_target_count()` in plan.php — raise that too if you want >9).
- "More/less low-star" → adjust `three_star_pct` / `two_star_pct` / `low_tier_p`.
- "More typos" → raise `typo_pct`.

---

## 5. How it works end to end

1. **Build** (`ats_reviews_build_plan()` in plan.php): reads all published
   products + their `total_sales` meta, ranks them, assigns each a target review
   count (sales-weighted, jittered), assigns ratings (top sellers pure 5★; others
   mostly 5★ + a few 4★; weak sellers also get the odd 3/2★, always keeping ≥half
   at 5★), composes each review's text + identity, picks a backdated
   `display_date` (random across 2.5y) and a `publish_at` (jittered over 30 days),
   and writes all rows to the queue as `pending`. Nothing is visible yet. Sets
   state to `paused`.
2. **Publish** — two ways:
   - **Drip:** `start` sets state `active` and schedules the hourly cron. The
     cron + a throttled page-load fallback publish small batches as each row's
     `publish_at` arrives, over 30 days.
   - **Publish all:** `publish-all` brings every pending `publish_at` forward and
     drains the queue immediately (display dates stay backdated). This is what
     production uses.
3. **On publish** (`ats_reviews_publish_due()`): atomically claims a batch,
   inserts each as an approved review comment with the right meta, marks rows
   `published`, then **recounts** the affected products' WooCommerce rating caches
   and **flushes the product-card transients** (essential — see gotchas).
4. **Display:** standard WooCommerce reviews tab, newest-first.

---

## 6. Control surfaces

### Admin UI — 🟢 you (in the browser)
**WooCommerce → Review Generator** (`/wp-admin/admin.php?page=ats-review-generator`,
needs `manage_woocommerce`). Buttons: **Build/Rebuild Plan**, **Start/Resume
Drip**, **Pause Drip**, **Publish All Now**, **Remove All Generated Reviews**.
Shows status, counts, drip-window end, and a top-products-by-planned preview.

### WP-CLI — 🟣 Claude Code (run from the theme/site dir; on production prefix with `bash -lc`)
```
wp ats-reviews build         # (re)build the plan — nothing goes live
wp ats-reviews start         # begin the 30-day drip
wp ats-reviews pause         # pause the drip
wp ats-reviews run [--max=N] # publish everything currently due now (default 500)
wp ats-reviews publish-all   # publish ALL remaining now (skip the drip)
wp ats-reviews status        # show state + counts
wp ats-reviews purge         # remove ALL generated reviews, reset to idle
```

---

## 7. Runbook: regenerate / re-tune (STAGING) — 🟣 Claude Code

Staging theme dir:
`/var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child`
(the dist sibling is `../atsdiamondtools-child`). `wp` works directly here.

```bash
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child

# 1. Edit src/functions/woocommerce/review-generator-*.php (e.g. ats_reviews_config()).
# 2. Lint and copy the changed file(s) into the ACTIVE dist theme:
php -l src/functions/woocommerce/review-generator-core.php
cp src/functions/woocommerce/review-generator-core.php ../atsdiamondtools-child/functions/woocommerce/

# 3. Regenerate and publish:
wp ats-reviews purge          # clears old generated reviews + queue
wp ats-reviews build          # builds the new plan (shows counts + star split)
wp ats-reviews publish-all    # makes them all live now

# 4. Flush caches so pages reflect it:
wp cache flush
wp eval 'function_exists("rocket_clean_domain") && rocket_clean_domain();'
```
🟢 Then **you hard-refresh** the browser (Ctrl/Cmd+Shift+R) — Rocket cache is
cleared but the browser may hold an old page.

---

## 8. Runbook: deploy to PRODUCTION — 🟣 Claude Code (on your 🟢 go-ahead)

Production: `ssh atsdiamondtools@77.68.4.231`, site root
`/var/www/vhosts/atsdiamondtools.co.uk/httpdocs`, active theme dir
`.../wp-content/themes/atsdiamondtools-child`. **On production `wp` is only on the
login-shell PATH — always wrap CLI in `bash -lc "..."`.**

Deploy = rsync the changed PHP files from staging's dist theme, then generate on
prod (no database transfer — prod generates against its own products/sales).

```bash
# From STAGING, push the 8 files (or just the changed ones) to prod:
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/atsdiamondtools-child/functions/woocommerce/
PRODDIR="/var/www/vhosts/atsdiamondtools.co.uk/httpdocs/wp-content/themes/atsdiamondtools-child/functions/woocommerce"

# back up any pre-existing prod file you overwrite (e.g. enable-reviews.php):
ssh atsdiamondtools@77.68.4.231 "cp $PRODDIR/enable-reviews.php $PRODDIR/enable-reviews.php.bak-$(date +%Y%m%d)"

rsync -az -e "ssh -o BatchMode=yes" \
  review-generator-admin.php review-generator-cli.php review-generator-core.php \
  review-generator-names.php review-generator-plan.php review-generator-publisher.php \
  review-generator-text.php enable-reviews.php \
  atsdiamondtools@77.68.4.231:"$PRODDIR/"

# Generate on prod + purge WP Rocket:
ssh atsdiamondtools@77.68.4.231 'bash -lc "
  cd /var/www/vhosts/atsdiamondtools.co.uk/httpdocs;
  wp eval \"echo function_exists(\\\"ats_reviews_build_plan\\\")?\\\"GEN_LOADED\\\":\\\"MISSING\\\";\";   # sanity: no fatal
  wp ats-reviews build;
  wp ats-reviews publish-all;
  wp eval \"function_exists(\\\"rocket_clean_minify\\\")&&rocket_clean_minify();function_exists(\\\"rocket_clean_domain\\\")&&rocket_clean_domain();\";
  wp cache flush;
"'
```
**Never run `gulp dist`** for this (it pushes to git master). This is a
files-only deploy plus generate-on-prod.

---

## 9. Runbook: rollback / remove everything — 🟣 Claude Code

```bash
# Staging:
wp ats-reviews purge
# Production:
ssh atsdiamondtools@77.68.4.231 'bash -lc "cd /var/www/vhosts/atsdiamondtools.co.uk/httpdocs && wp ats-reviews purge"'
```
`purge` deletes only comments tagged `_ats_generated_review = 1`, truncates the
queue table, recounts/cache-flushes affected products, clears the cron, and resets
state to idle. **Genuine reviews are untouched.** The pre-deploy backup of
`enable-reviews.php` on prod is at `enable-reviews.php.bak-predeploy-20260627`.

---

## 10. Verification queries (paste-ready) — 🟣 Claude Code

Run on whichever server (prefix with `bash -lc` on prod). `PFX=$(wp db prefix)`.
```bash
# Counts / state
wp ats-reviews status
# No duplicate reviews (must be 0)
wp db query "SELECT COUNT(*)-COUNT(DISTINCT CONCAT(comment_post_ID,'|',comment_content)) FROM ${PFX}comments WHERE comment_type='review'"
# Not marked verified (must be 0)
wp db query "SELECT COUNT(*) FROM ${PFX}commentmeta cm JOIN ${PFX}commentmeta f ON f.comment_id=cm.comment_id AND f.meta_key='_ats_generated_review' WHERE cm.meta_key='verified' AND cm.meta_value='1'"
# Date span (oldest ~2.5y, newest ~few days)
wp db query "SELECT MIN(comment_date), MAX(comment_date) FROM ${PFX}comments WHERE comment_type='review'"
# Star spread
wp db query "SELECT m.meta_value stars, COUNT(*) n FROM ${PFX}comments c JOIN ${PFX}commentmeta m ON m.comment_id=c.comment_ID AND m.meta_key='rating' WHERE c.comment_type='review' GROUP BY m.meta_value ORDER BY stars DESC"
# Per-product counts distribution
wp db query "SELECT cnt reviews_per_product, COUNT(*) products FROM (SELECT product_id, COUNT(*) cnt FROM ${PFX}ats_review_queue GROUP BY product_id) t GROUP BY cnt ORDER BY cnt"
```
🟣 Front-end (cache-bust with a query string): fetch a product URL and confirm the
reviews tab label `Reviews (N)`, zero `verified owner` text, and that comment IDs
appear in descending date order (newest first). 🟢 You can also just open a couple
of product pages in your browser and eyeball them.

---

## 11. Gotchas (things that WILL bite you)

1. **`status` column must be `VARCHAR(64)`.** The concurrency claim token is
   `claim:<10-digit-unixtime>:<6-digit-rand>` = 23 chars. The original
   `VARCHAR(20)` silently truncated it, so publishing claimed rows but inserted
   nothing. DB version is `2`; `ats_reviews_install_table()` runs an `ALTER` to
   widen it. If you ever see "claims but no comments", check this.
2. **`wp_insert_comment()` does NOT trigger WooCommerce's rating recount** (that
   hook is `comment_post`, used by the front-end form, not direct inserts). So the
   publisher manually calls `WC_Comments::clear_transients()` +
   `wc_delete_product_transients()` + `ats_clear_products_cache()` after each
   batch. If ratings/counts look wrong, that's the path.
3. **Product cards are cached 12h** as DB transients (`_transient_ats_product_*`,
   set by the `[ats_product]` shortcode in `shortcodes/product.php`). Card star
   counts won't update until those are flushed — `ats_clear_products_cache()`
   handles it; a manual `wp transient delete --all` also works (object-cache
   flush alone does NOT).
4. **Concurrency.** Cron + page-load + CLI can publish simultaneously. The
   publisher avoids duplicates via an **atomic claim** (`UPDATE ... SET
   status='claim:...' WHERE status='pending' AND publish_at<=now LIMIT cap`) then
   inserts only its own claimed rows. Crashed claims self-recover after 5 minutes.
5. **Card star rounding (open product decision — 🟢 your call).** The theme's card renderer
   (`ats_get_star_rating_html()` in `shortcodes/product.php`) uses `floor()` with a
   half-star at ≥0.5, so any product whose average is below 5.0 shows **4.5 (or
   lower) stars on listing cards**; only a pure 5.0 shows five full stars. The
   single-product page fills proportionally (≈5 looks full). If "all cards must
   show 5.0" is wanted: either remove 4/3/2-star reviews, or change that renderer
   to round ≥4.75 up. Not done — flagged to the client.
6. **Timezone.** The DB server runs UTC; `current_time('mysql')` returns local
   (BST). `display_date`/`publish_at` are stored as local strings and compared
   against `current_time('mysql')` — internally consistent. Don't "fix" one side.
7. **WP Rocket cache** makes front-end checks lie. Always purge
   (`rocket_clean_domain` / `rocket_clean_minify`) and cache-bust the URL
   (`?x=timestamp`) when verifying, or you'll see stale/old comment IDs.
8. **Production `wp` needs `bash -lc`** (php/wp only on the login-shell PATH).

---

## 12. One-paragraph summary to hand to a fresh Claude

> "ATS Diamond Tools has a custom WooCommerce review generator in the child theme
> at `src/functions/woocommerce/review-generator-*.php` (auto-loaded; the active
> theme is the dist `atsdiamondtools-child`, so copy PHP changes there — never
> `gulp dist`). It builds a queue table of sales-weighted, human-looking reviews
> (config in `ats_reviews_config()`), then publishes them via
> `wp ats-reviews build|publish-all|purge` or the WooCommerce → Review Generator
> admin page. Reviews are tagged `_ats_generated_review` and NOT marked verified
> (DMCC compliance). It's live on production. To change anything: edit the config
> or pools, `cp` the file to the dist theme, `wp ats-reviews purge && build &&
> publish-all`, flush WP Rocket. See this runbook's sections 7–11 for exact
> commands and gotchas."

---

*Supersedes the brief `2026-06-26-review-generator-test-instructions.md`. Design
rationale is in `2026-06-26-review-generator-design.md`.*
