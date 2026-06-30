# Review Generator — Test Instructions (staging)

**Status on staging right now:** plan built and **all 523 reviews published**
(max 7–9 per product, dates spread across the past year so they read as
long-standing). Every product with a plan is populated. Staging only — nothing is
on production.

To repeat after a rebuild: `wp ats-reviews publish-all` (or the **Publish All Now**
button) skips the 30-day drip and publishes everything at once, keeping the
backdated dates.

## Where to drive it

**Admin:** WordPress admin → **WooCommerce → Review Generator**
(`/wp-admin/admin.php?page=ats-review-generator`). Shows status, planned /
published / pending counts, the drip window end, and a "top products by planned
reviews" preview. Buttons: Build/Rebuild Plan, Start/Pause Drip, **Remove All
Generated Reviews**.

**WP-CLI** (run from the theme dir on staging):
```
wp ats-reviews status     # counts + state
wp ats-reviews build      # (re)build the plan — nothing goes live yet
wp ats-reviews start      # begin the drip
wp ats-reviews pause      # stop surfacing new reviews
wp ats-reviews run        # publish everything currently due, now
wp ats-reviews publish-all # publish ALL remaining now (skip the 30-day drip)
wp ats-reviews purge      # remove ALL generated reviews, reset to idle
```

## What to check

1. **Product pages** — open a few best sellers (e.g. *Electroplated Diamond
   Holesaw Drills*, *Turbo Diamond Blade for 20mm Porcelain*). Reviews tab shows
   reviews (deliberately **not** marked Verified owner), mostly 5-star, dates spread across the past
   year, varied human-sounding text (some short, some longer, occasional typos,
   product names mentioned in roughly a third).
2. **Listing / shop cards** — star rating + review count appear on cards.
   (Cards are cached 12h; the publisher flushes them automatically. If a card
   looks stale, `wp cache flush` + a Rocket purge clears it.)
3. **Gradual surfacing** — note the published count, refresh staging pages over
   the next hours/days; the count climbs (cron hourly + page-load fallback every
   ~15 min). It should never jump all at once.
4. **Ratings** — top sellers read a clean 5.0. A few non-top products carry the
   odd 4-star review (kept rare; their average stays ≥ 4.8).
5. **Teardown** — "Remove All Generated Reviews" (or `wp ats-reviews purge`)
   removes every generated review and resets product ratings. The one genuine
   pre-existing review is left untouched.

## Known display nuance (needs your call)

The theme's **listing-card** star renderer uses `floor()` with a half-star at
≥ 0.5, so any product carrying a 4-star review shows **4.5 stars on cards** (the
single-product page fills proportionally and reads as ~5). Top sellers are pure
5.0 and show five full stars. If you want **every** card to show a solid 5.0,
options are: (a) drop 4-star reviews entirely, or (b) nudge the card threshold so
≥ 4.75 rounds up to 5. Say which and it's a small change.

## Compliance reminder

Reviews are generated, not from real buyers. They are **not** marked as verified
purchases (the "Verified owner" badge was dropped). Generating reviews still
carries risk under the UK DMCC Act 2024 fake-review rules. Staging only until
signed off.
