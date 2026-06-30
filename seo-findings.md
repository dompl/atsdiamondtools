# ATS Diamond Tools, SEO Programme — Discovery Findings (Agent A)

Generated 2026-06-29. Source of truth for Agents B–K. Read-only discovery, staging only.

## Summary for orchestrator
- Environment: **STAGING** `http://atsdiamondtools.rfsdev.co.uk`. WP 6.9.4, PHP 8.2.29, WooCommerce 10.5.0. DB prefix `XMTBGX_`. **No media offload plugin** (uploads local on disk) — Agent D is safe to run.
- Active theme `atsdiamondtools-child` (v0.0.96, the built/dist). Edit source in `skylinewp-dev-child`, then `gulp dist` builds + re-activates.
- Published products: **137**. Trashed: 0. Drafts: **3** (the Pro Kits). Pending: 0.
- Nine main categories identified; **all nine have thin/empty descriptions** (0–42 words; target is 150–200). → Agent H rewrites all nine.
- Dead-URL candidates (404 confirmed): the **3 draft Pro Kit** URLs + the **Flex CS60** link. No `_wp_old_slug` rows, no trashed products. → Agent B.
- Footer logo (attachment **4883**) has **empty alt**, renders `alt=""`. → Agent C.
- Image alt audit: **141 image slots across 136 featured + 5 gallery; 134 distinct attachments** (so ~7 duplicate rows in the CSV and some shared attachments). 1 published product has **no featured image** (197673 Pro Polishing Starter Kit). → Agent F.
- Buying guides / posts: **6 posts, all under 1,500 words** (thinnest 112w). Two near-duplicate "Ultimate Guide to Diamond Cutting Blades" posts. → Agent I.
- **Category descriptions DO render** on product-category archives (WC default; no override). → Agent H needs no template hook (confirm visually).
- Product schema: **NO `Product` JSON-LD and NO `aggregateRating` emitted today** (only Yoast WebPage/WebSite/Breadcrumb graph). **592 approved reviews already exist.** → Agent K precondition met; scope is larger than "flip a setting" (must add Product schema in theme).
- Existing SEO meta: **132/137 products already have Yoast title + metadesc**; **categories have 0**; product descriptions essentially un-rewritten (1 product has an rfs-ai-seo backup). → reframes Agent E (focus categories + homepage/shop + 5 kit products) and Agent G (descriptions largely open).
- **In-house `rfs-ai-seo` plugin present and active** — bulk generators for product descriptions, meta (via Yoast), and image alt text. Likely already produced the 132 product metas. Decision needed: use it for E/F/G/H or hand-craft.

## 1. Theme & template paths
- Source theme (edit here): `wp-content/themes/skylinewp-dev-child` (git root, where progress/findings files live).
- Active dist theme (rendered): `wp-content/themes/atsdiamondtools-child` v0.0.96.
- Parent (framework): `wp-content/themes/skylinewp-dev-parent`.
- Footer template part: `src/functions/template-parts/footer-main.php` (build copy: `build/functions/template-parts/footer-main.php`). Footer logo = ACF option `ats_footer_logo` (image array), rendered at lines 47–52 with `alt="<?php echo esc_attr( $footer_logo['alt'] ?? get_bloginfo('name') ); ?>"`. NOTE: `??` does not catch ACF's empty-string alt, so it currently renders `alt=""`. Fix = set alt on attachment 4883 (and/or change `??` to `?:`).
- Post-category archive: `src/archive.php` echoes `category_description()` but only under `is_category()` (post categories) — does NOT apply to WooCommerce `product_cat`.
- No `woocommerce_archive_description` / `term_description` override in child/parent → WC default term-description output is intact on product-category archives.
- Schema output: parent `includes/helpers.php` emits BreadcrumbList + Article + Person only. No Product schema anywhere in theme/parent/plugin. Yoast emits the WebPage/WebSite/Breadcrumb graph. (See §10.)

## 2. Plugins (key)
Yoast `wordpress-seo` 27.9 · WooCommerce 10.5.0 · WP Rocket 3.12.2.1 · WooCommerce Order Status Manager 1.15.6 · Brevo `brevo-campaign-generator` 1.5.47 · WC Stripe 10.3.1 · WC Payments 10.4.0 · WP Mail SMTP 4.9.0 · Shipment Tracking · Table Rate Shipping · PDF Invoices.
Custom:
- **`rfs-ai-seo` 1.0.0** (Red Frog): AI SEO meta generation for WooCommerce products + categories using OpenAI, Yoast integration. Classes: `class-description-generator` (product short+long descriptions; backs up original to `_rfs_ai_original_description`), `class-bulk-generator` (writes `_yoast_wpseo_title`/`_yoast_wpseo_metadesc`), `class-yoast-integration` (writes post meta `_yoast_wpseo_title/metadesc` and term meta `wpseo_title/wpseo_desc`), `class-alt-text-generator` (writes `_wp_attachment_image_alt`), `class-image-generator`, plus admin bulk pages (descriptions, short descriptions, meta, alt text, images). Uses OpenAI + Stripe credits.
- `woocommerce-data-sync` 1.4.5 (custom) — data sync (relevant to staging↔prod).
- `ats-tool-finder` (inactive), `query-monitor` (inactive).

## 3. The nine main product categories (term_id | slug | product count | description word count)
- 15 | cutting | 27 | 12
- 22 | grinding | 30 | 14
- 19 | polishing | 43 | 42
- 18 | drilling | 12 | 11
- 757 | concrete | 35 | 28
- 760 | fitting-kits | 10 | 23
- 20 | profiling | 14 | 31
- 21 | power-tools | 9 | 5
- 23 | distar | 7 | 0 (empty)

Auxiliary (not part of the nine): Bundles (3737, 4 products, 0w), Pro Kits (3736, 0 products, 11w), Clearance (3735, 6 products, 4w), Uncategorized (967, 0). All nine need expansion to 150–200 words (Agent H). Category archive URL form: `/product-category/{slug}/`.

## 4. Product permalink structure
- `woocommerce_permalinks`: product_base `/product`, category_base `product-category`. Site permalink_structure `/%postname%/`.
- Product URL: `https://www.atsdiamondtools.co.uk/product/{slug}/` (staging: `http://atsdiamondtools.rfsdev.co.uk/product/{slug}/`).

## 5. Dead / renamed / trashed / draft products (Agent B input)
- `_wp_old_slug` rows for products: **0**. Trashed products: **0**.
- **Drafts (404 publicly), candidate "three 404s":**
  - 197645 `concrete-cutting-pro-kit` → `/product/concrete-cutting-pro-kit/` = 404
  - 197644 `drilling-pro-kit` → `/product/drilling-pro-kit/` = 404
  - 197643 `polishing-pro-kit` → `/product/polishing-pro-kit/` = 404
  - These are real kit products left in draft. Decision: PUBLISH them, or redirect the URLs to the Pro Kits / Bundles category. Confirm with Dom whether these are the GSC "three 404s" or whether different URLs apply.
- Candidate destinations if redirecting: Pro Kits category (`/product-category/pro-kits/`) or Bundles (`/product-category/bundles/`).

## 6. Flex CS60 link (Agent B input)
- Product **167823** ("Bushboard M Stone Max Top or Howdens Quartz Diamond Blade") contains a link: anchor "Flex CS60 wet saw" → `http://atsdiamondtools.rfsdev.co.uk/product/flex-cs60-wet-stone-saw/` = **404** (no such product in any status).
- Product **167755** ("170mm Laser Welded Turbo Segmented Blade Flex Fitment") mentions "FLEX CS60 WET" in body text (not a CS60 link; it links to a different product, abrasive-bar-250…).
- Closest real product to the missing saw: 167755 (the blade that fits the Flex CS60). Decision: repoint the 167823 link to 167755, point to a category, or remove the link. (ATS does not appear to sell the Flex CS60 saw itself.)

## 7. Blog posts / buying guides (Agent I input) — words | ID | title
- 112 | 137158 | The Ultimate Guide to Diamond Cutting Blades  ← very thin
- 206 | 50398 | The Ultimate Guide to Diamond Cutting Blades: Choosing the Right Blade for Your Project  ← near-duplicate topic of 137158
- 239 | 50399 | 5 Essential Tips for Maintaining Your Diamond Tools
- 767 | 78956 | The definitive guide to M14 profiling tools
- 1154 | 1 | Polishing your concrete worktop or tabletop
- 1261 | 78202 | Wet vs Dry polishing, which is better?
All six are below the 1,500-word target. Flag: 137158 + 50398 are duplicate "Ultimate Guide to Diamond Cutting Blades" content — consolidate into one canonical guide and 301 the other (raise with Dom).

## 8. Product image alt-text audit (Agent F input)
- Full per-image rows in `seo-findings-image-alt.csv` (columns: attachment_id, product_id, product_title, filename, current_alt, status).
- Coverage: 136/137 products have a featured image; 5 gallery images total; **141 image slots, 134 distinct attachments** → ~7 duplicate CSV rows AND some attachments shared across products (relevant to Agent D's shared-attachment handling).
- CAVEAT: the CSV `status` classification was produced by the (interrupted) discovery subagent and looks lenient — several generic/mismatched alts are marked `ok`. **Re-validate classification at Agent F time** before bulk-writing. Dedup rows first.
- 197673 "Pro Polishing Starter Kit" (published) has **no featured image** — flag to Dom.

## 9. Category description rendering on archive
- Polishing (term 19) description text appears in the rendered `/product-category/polishing/` HTML (1 match). No `term-description` wrapper class found, so confirm visually it is in the archive body (not only a meta tag). WC default behaviour with no override = description renders on page 1 of the archive. Agent H likely needs NO template change.

## 10. Product schema / aggregateRating (Agent K input)
- Reviews enabled: `woocommerce_enable_reviews=yes`, `enable_review_rating=yes`, `review_rating_required=yes`.
- Approved reviews: **592** (the generated-review drip). Data precondition for K is already met.
- Rendered product page (electroplated-diamond-holesaws): 2 JSON-LD blocks, both the Yoast graph (WebPage, WebSite, BreadcrumbList, ImageObject, SearchAction). **No `Product` type and no `aggregateRating`.** Likely the custom single-product template skips the WooCommerce hooks that normally emit `WC_Structured_Data` Product schema.
- Therefore Agent K scope: ADD Product schema (incl. `aggregateRating`, guarded `reviewCount>0`) in the theme schema output. Bigger than a settings flip.

## 11. Existing SEO meta state
- Published products with `_yoast_wpseo_title`: **132/137**; with `_yoast_wpseo_metadesc`: **132/137**. The 5 missing (all new kit products): 197669 Tilers Cutting & Drilling Kit, 197670 Concrete Grinding Kit, 197671 Electroplated Holesaw Package, 197574 Sintered Metal Bonded Grinding Pads, 197673 Pro Polishing Starter Kit.
- Categories with Yoast term title: **0**.
- Products with rfs-ai-seo description backup (`_rfs_ai_original_description`): **1** → product long/short descriptions are essentially un-rewritten.
- Implication: Agent E's product-meta work is mostly done (likely by rfs-ai-seo); real E gaps = homepage, shop, 9 categories, 5 kit products. Agent G (descriptions) is wide open. (Quality of the existing 132 metas not yet audited.)

## 12. Best-seller candidate "priority products" (top 25 by total_sales) — for Dom to confirm (Agent E/G)
sales | ID | title
- 24208 | 167648 | Electroplated Diamond Holesaw Drills
- 14039 | 167343 | Premium Wet Diamond Polishing Pads
- 11723 | 167274 | Super Premium Dry Diamond Polishing Pads
- 9794 | 167247 | Diamond Hand Polishing Pads
- 8912 | 167027 | Turbo Diamond Blade for 20mm thick Porcelain
- 6165 | 167810 | Flush Cut Diamond Vanity Blade
- 4534 | 167417 | 5 Step Flexible White Quartz Diamond Polishing pads
- 3993 | 167012 | Diamond Blades to fit 165mm x 20mm Plunge Saws
- 3959 | 167826 | Turbo X Diamond Porcelain Blade
- 3663 | 167321 | Copper Bonded Diamond Polishing Pads
- 3649 | 167380 | SAIT Velour Silicon Carbide Discs 125mm
- 3139 | 167680 | Vacuum Brazed Dry Diamond Drill M14 (50mm working length)
- 2745 | 167819 | Diamond Jigsaw Blades
- 2664 | 167829 | Bushboard M Stone Max Top Howdens Quartz Router and Installation Tools
- 2446 | 167795 | Porcelain Continuous Rim Diamond Blade
- 2359 | 167159 | Drainer Groove Diamond Hand Polishing pads
- 2149 | 167216 | Rubber Velcro Backing Pad M14
- 2149 | 167807 | Porcelain & Quartz Thin Turbo Flange Diamond Blade
- 2093 | 167508 | Quartz Rigid Sponge Diamond Polishing Pads
- 1947 | 167823 | Bushboard M Stone Max Top or Howdens Quartz Diamond Blade
- 1805 | 167256 | Triangle Polishing pads Wet/Dry
- 1373 | 167783 | Super Premium Segmented Laser Welded Blade
- 1257 | 167334 | 100mm Economy Wet Diamond Polishing Pads
- 1156 | 167088 | Typhoon Diamond Resin Concrete Dry Polishing Puck
- 1155 | 167103 | Typhoon Diamond Copper Concrete Dry Polishing Puck

## Open decisions for Dom (carried to seo-progress.md)
1. E/F/G/H: use the in-house `rfs-ai-seo` bulk generators, or hand-craft per the brief's writing rules, or hybrid (plugin drafts → human review/cleanup)?
2. Six SEO keyword themes + theme→category mapping (Agents E, H).
3. Priority products: confirm the top-25 above, or supply a list.
4. The 3 draft Pro Kits: publish, or redirect (and confirm they are the GSC "three 404s").
5. Flex CS60 broken link: repoint to blade 167755, point to a category, or remove.
6. Duplicate "Ultimate Guide to Diamond Cutting Blades" posts (137158 + 50398): consolidate + 301?

## Safety baseline
- DB snapshot: `/var/www/vhosts/rfsdev.co.uk/backups/seo-programme-2026-06-29/db-staging-2026-06-29.sql.gz` (69 MB).
- Uploads snapshot: `/var/www/vhosts/rfsdev.co.uk/backups/seo-programme-2026-06-29/uploads-2026-06-29.tar` (1.9 GB).
