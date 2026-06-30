# ATS Diamond Tools, SEO Programme Progress

Status: STAGING (`http://atsdiamondtools.rfsdev.co.uk`). Live push happens after Dom signs off.

Snapshot baseline taken 2026-06-29: DB exported to `/var/www/vhosts/rfsdev.co.uk/backups/seo-programme-2026-06-29/db-staging-2026-06-29.sql.gz` (69 MB). Uploads (1.9 GB) to be tarred to the same dir immediately before Agent D runs.

| Agent | Task | Status | Note | Updated |
|-------|------|--------|------|---------|
| A | Discovery + safety baseline | Done | seo-findings.md + image-alt CSV written; DB + uploads snapshots taken; no media offload | 2026-06-29 |
| B | 404 redirects + Flex CS60 link | Done | No 301s needed (drafts skipped per Dom; no _wp_old_slug/trash). Removed broken Flex CS60 hyperlink in product 167823, kept text. Verified. | 2026-06-29 |
| C | Footer logo alt + verify redirects | Done | Footer logo alt set on attachment 4883 ("ATS Diamond Tools") + verified in fresh render. No redirects to re-verify (B required none). | 2026-06-29 |
| D | Image-rename WP-CLI command + run | Done (staging) | Full staging run: 215 renamed, 29 shared-skipped, 2 already-named, 0 collisions, 0 errors. New URLs 200, refs updated. url-map in backups for Dom. PRODUCTION run still pending Dom sign-off. | 2026-06-29 |
| E | Meta titles + descriptions | Done | Homepage, shop, 9 categories, 5 kit products: unique Yoast title (<=60) + desc (<=155), verified in rendered source. Categories via wpseo_taxonomy_meta option + indexable refresh. 132/137 products already had meta. | 2026-06-29 |
| F | Product image alt text | Done | Prior pass already gave 117/134 images rich alt; 17 weak ones (empty/filename/generic) updated with hand-crafted alt incl. material/use/size. Applied + verified. | 2026-06-29 |
| G | Product descriptions | Done | Top-25 best-sellers: new short + long descriptions + unique SEO title (<=60)/desc (<=155), all from real data, 0 em dashes, verified in rendered source. Originals backed up to _seoprog_orig_*. Indexables refreshed. | 2026-06-29 |
| H | Category intro copy | Done | All 9 categories: 164-172 word intros, 1 internal product link each, rendering on archives (WC default, no template hook needed). Keyword themes derived in seo-keyword-themes.md. | 2026-06-29 |
| I | Expand buying guides | Done | 5 guides expanded to 1550-1702 words, each with 6-item FAQ + FAQPage JSON-LD, internal links, refreshed Yoast meta + modified date. Duplicate "Ultimate Guide" (50398) consolidated: 301 -> 137158 via mu-plugin + drafted. Verified. | 2026-06-29 |
| J | Review-request email automation | Done (disabled) | mu-plugin ats-review-request.php: fires on Completed/Dispatched, schedules personalised email +12 days via Action Scheduler, once-per-order, review links per product. Forward-only. Dry-tested on a dummy order (correct personalisation + links). DISABLED by default. Dom: `wp ats-review-email test <order> --email=you@x --send` then `wp ats-review-email enable`. | 2026-06-29 |
| K | AggregateRating schema | Done (staging) | mu-plugin ats-product-schema.php emits Product JSON-LD + offers + aggregateRating, guarded to reviewCount>0 (verified: rating shows for review-bearing product, omitted for zero-review). Dom: run Google Rich Results Test + copy mu-plugin to production. | 2026-06-29 |
| N | Blog index page + image performance | Done (staging) | Dedicated blog index (full-width hero, articles listed with thumbnails + summaries, sidebar: best-sellers + newsletter + shop CTA). Single-article pages restyled (compact header, clear prev/next). Fixed image weight: article thumbnails serve a fixed 440px WebP via wpimage() (single URL, no oversized srcset) instead of full-size originals (some 3024x3024). Added cache-bust ?c=mtime for CSS + JS that survives the parent theme's ver-stripping, so builds reach browsers. Theme v0.0.109. | 2026-06-30 |

Status values: `Not started`, `In progress`, `Blocked (reason)`, `Done`, `Deferred`.

## Notes / blockers / follow-ups
- 2026-06-29 (A): Confirmed STAGING (`atsdiamondtools.rfsdev.co.uk`). Production is the separate IONOS box (`www.atsdiamondtools.co.uk`), not touched.
- 2026-06-29 (A): No media offload plugin (no S3/WP Offload/Stateless/CDN-storage). Uploads local on disk. Agent D can run as specified.
- 2026-06-29 (A): DB snapshot stored outside web root. Uploads tar deferred to just before Agent D (1.9 GB).
- 2026-06-29 (A): Custom plugin `rfs-ai-seo` (active) and `woocommerce-data-sync` present, both potentially relevant to SEO/schema work, flagged for Agent A inventory.
- 2026-06-29 (A): Discovery complete — see seo-findings.md. Highlights: 137 products; nine categories all have thin/empty descriptions; footer logo (att 4883) alt is empty; 3 draft Pro Kits + Flex CS60 link all 404; 6 blog posts all <1500 words (2 are duplicate "Ultimate Guide" posts); NO Product schema/aggregateRating today but 592 reviews exist; 132/137 products already have Yoast meta (categories have none); in-house rfs-ai-seo plugin can bulk-generate descriptions/meta/alt.
- DECISIONS for Dom (blocking various agents):
  1. E/F/G/H approach: rfs-ai-seo plugin vs hand-craft vs hybrid.
  2. Six keyword themes + theme→category mapping (E, H).
  3. Priority products: confirm top-25 best-sellers (in seo-findings.md §12) or supply list.
  4. 3 draft Pro Kits: publish or redirect (and confirm they are the GSC "three 404s").
  5. Flex CS60 broken link destination.
  6. Duplicate "Ultimate Guide to Diamond Cutting Blades" posts: consolidate + 301?
- 2026-06-29 (ops): WP Rocket WP-CLI command is NOT registered. Purge front-end cache by clearing `wp-content/cache/wp-rocket/` or via WP Rocket admin.
- 2026-06-29 (D): rename-product-images.php dry-run outputs in `/var/www/vhosts/rfsdev.co.uk/backups/seo-programme-2026-06-29/`: rename-dryrun.csv (per-attachment), rename-dryrun-url-map.csv (1588 old→new), rename-dryrun-redirects.conf. Hand the url-map to Dom before any live run.

## FINAL HANDOFF (all agents complete on STAGING, 2026-06-29)

All non-deferred rows are Done. Everything is on staging (`atsdiamondtools.rfsdev.co.uk`); production is untouched.

### Production rollout kit (durable): `/var/www/vhosts/rfsdev.co.uk/backups/seo-programme-2026-06-29/`
- Snapshots: `db-staging-2026-06-29.sql.gz`, `uploads-2026-06-29.tar`
- Data: `alt-proposed.csv` (F), `e-meta.json` (E), `category-copy.json` (H), `g-descriptions.json` (G), `i-guides.json` (I) + `.md` mirrors
- `apply-scripts/`: apply-alt, apply-cat-copy, apply-meta, apply-descriptions, apply-guides, fix-cs60-link, rename-product-images.php
- `mu-plugins/`: ats-seo-redirects.php (B + I 301), ats-product-schema.php (K), ats-review-request.php (J)
- Image rename maps: `rename-full-url-map.csv` (old→new for Dom to eyeball)

### How each change reaches PRODUCTION (staging DB does NOT auto-sync; wordmove DB push is broken)
1. Copy the 3 files from `mu-plugins/` into production `wp-content/mu-plugins/`.
2. Image rename on prod: snapshot prod DB+uploads, then `wp --require=rename-product-images.php rfs rename-product-images` (dry-run), a `--product=` subset `--no-dry-run`, then full `--no-dry-run`. Leave `--set-alt` off.
3. Re-run the apply scripts on prod against the same data files: `wp eval-file apply-alt.php alt-proposed.csv apply`; `wp eval-file apply-cat-copy.php category-copy.json apply`; `wp eval-file apply-meta.php e-meta.json apply`; `wp eval-file apply-descriptions.php g-descriptions.json apply`; `wp --user=1 eval-file apply-guides.php i-guides.json apply`. Then delete the touched Yoast indexable rows (or `wp yoast index`) so meta surfaces. fix-cs60-link.php apply for the CS60 anchor; set post 50398 to draft.
4. Flush: `wp transient delete --all` + clear WP Rocket cache + regenerate Yoast sitemaps (incl. images).

### Outstanding for Dom (decisions / go-live)
- J review emails: live send-test then `wp ats-review-email enable` when happy with copy/timing.
- K schema: run Google Rich Results Test on a few product URLs.
- D: review `rename-full-url-map.csv` before the production rename.
- Note: K mu-plugin emits Product schema; if Yoast WooCommerce SEO is ever enabled, check for duplicate Product schema.
