# ats-social-media-tracking — Build Instructions & Design

**Date:** 2026-06-30
**Status:** Design approved (architecture + CAPI scope). `wp-config` migration step pending final confirmation (see §4).
**Author:** Claude (brainstorming → spec)

---

## 1. Purpose & decisions

A small standalone plugin that gives the ATS site a **Marketing → Social Media** admin
settings area, becomes the **single source of truth** for all tracking IDs/tokens/UTM
defaults, and fills the one gap the theme doesn't already cover (**Google Ads
conversion**).

**Why a control panel + gap-filler (not a self-contained re-implementation):**
The theme **already fires GA4 and the Meta Pixel live** — both IDs are real values in
`wp-config.php` and the full purchase funnel already fires for both:

| Tag | Already in theme | File |
|-----|------------------|------|
| Meta Pixel base + PageView | ✅ | `skylinewp-dev-child/src/header.php` (lines ~21–36) |
| Meta `Purchase` (+ ViewContent, InitiateCheckout, client AddToCart) | ✅ | `skylinewp-dev-child/src/functions/woocommerce/meta-pixel.php` |
| GA4 `gtag.js` loader | ✅ | `skylinewp-dev-child/src/header.php` (lines ~12–18) |
| GA4 `purchase` (+ view_item, view_item_list, view_cart, begin_checkout, search, login, sign_up) | ✅ | `skylinewp-dev-child/src/functions/woocommerce/ga4-tracking.php` |
| Staff/internal-traffic exclusion | ✅ | `ats_ga4_is_excluded_user()` in `ga4-tracking.php` |
| **Google Ads conversion** | ❌ missing | — |
| **Admin UI for IDs** (currently `wp-config` constants) | ❌ missing | — |
| **CAPI token storage / Instagram ID / UTM defaults** | ❌ missing | — |

If the plugin *also* emitted GA4/Meta base + Purchase tags, **every purchase would be
counted twice**. So the plugin **never re-emits GA4/Meta tags** — it drives the existing
theme code instead.

**Locked decisions:**
- **Architecture:** Control panel + gap-filler. No double-firing.
- **CAPI scope:** Store + mask the token now. Server-side CAPI send is a later phase.
- **Menu:** New top-level `ats-marketing` ("Marketing"), per the task default.

---

## 2. Where the plugin lives

```
/var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/plugins/ats-social-media-tracking/
```

Conventions (match existing in-house plugins): function prefix `ats_smt_`, class prefix
`ATS_SMT_`, constant prefix `ATS_SMT_`, option key `ats_social_media_settings`, text
domain `ats-social-media-tracking`.

### File tree (what to add, and where)

```
ats-social-media-tracking/
├── ats-social-media-tracking.php          # Main file: plugin header, constants, requires, activation hook, bootstrap
├── uninstall.php                          # Deletes the option on plugin delete only
├── includes/
│   ├── helpers.php                        # ats_smt_get_settings(), defaults, regexes, mask helper, consent + exclusion gate
│   ├── class-ats-smt-settings.php         # Settings API: register_setting, sections, fields, sanitise, inline errors
│   ├── class-ats-smt-admin-menu.php       # Top-level "Marketing" + "Social Media" submenu, enqueue admin.css
│   └── class-ats-smt-tracking.php         # Front end: constant bridge, Google Ads conversion tag, consent gate
├── admin/
│   ├── css/
│   │   └── admin.css                      # Help-box + masked-field styling (admin screen only)
│   └── views/
│       └── settings-page.php              # Renders the verbatim help block (read-only) + the settings form
└── readme.txt                             # Standard WP plugin readme (short)
```

---

## 3. File-by-file specification

### 3.1 `ats-social-media-tracking.php` (main)
- Standard plugin header (Name: "ATS Social Media Tracking", Author: Red Frog Studio,
  Requires PHP 7.4+, text domain).
- `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- Define `ATS_SMT_VERSION`, `ATS_SMT_FILE`, `ATS_SMT_DIR`, `ATS_SMT_URL`, and the
  option-name constant `ATS_SMT_OPTION` = `'ats_social_media_settings'`.
- `require_once` the four `includes/` files.
- **Activation hook** → `ats_smt_activate()` (in helpers): seed the option from the
  existing `wp-config` constants if the option is empty (migration — see §4) and ensure
  UTM defaults are set.
- Bootstrap: instantiate `ATS_SMT_Settings`, `ATS_SMT_Admin_Menu` (admin only), and
  `ATS_SMT_Tracking`.
- The **constant bridge** is registered on `plugins_loaded` (priority 1) from
  `ATS_SMT_Tracking` — see §3.5.

### 3.2 `includes/helpers.php`
- `ats_smt_get_settings()` — returns the option array merged over defaults.
- `ats_smt_defaults()` — defaults incl. `utm_source_facebook=facebook`,
  `utm_source_instagram=instagram`, `utm_medium=paid_social`, `dont_output_tags=0`.
- **Validation regexes** (shared by sanitiser + inline errors):
  - `meta_pixel_id` → `/^\d{15,16}$/`
  - `meta_dataset_id` → `/^\d+$/` (optional)
  - `instagram_business_id` → `/^\d+$/` (optional)
  - `ga4_measurement_id` → `/^G-[A-Z0-9]+$/`
  - `google_ads_conversion_id` → `/^AW-\d+$/`
- `ats_smt_mask_secret( $value )` — returns `••••••••1234` (last 4 only).
- `ats_smt_consent_granted()` — `apply_filters( 'ats_social_media_tracking_consent_granted', true )`.
- `ats_smt_is_excluded()` — `function_exists('ats_ga4_is_excluded_user') && ats_ga4_is_excluded_user()`
  (reuses the theme's staff exclusion when present; safe if absent).
- `ats_smt_activate()` — migration/seed routine (see §4).

### 3.3 `includes/class-ats-smt-settings.php`
- `admin_init` → `register_setting( 'ats_smt_group', ATS_SMT_OPTION, [ sanitize_callback ] )`.
- Three sections: **A — Meta / Facebook & Instagram**, **B — Google**,
  **C — Default UTM tagging**, plus a **Tag output** section for the
  "Don't output tags (IDs managed elsewhere)" checkbox.
- One `add_settings_field` per field. CAPI token field renders as `type="password"`,
  value attribute left **empty**, placeholder shows `ats_smt_mask_secret()` when a token
  is stored; submitting an empty token field **keeps the stored value** (never wipes it).
- **Sanitise callback:**
  - Trim every field; `sanitize_text_field` baseline.
  - Numeric fields: strip non-digits, then regex-check; on mismatch keep old value +
    `add_settings_error('ats_smt', 'meta_pixel_id', 'Meta Pixel ID must be 15–16 digits.', 'error')`.
  - `ga4_measurement_id`: uppercase, regex-check `G-…`; inline error on mismatch.
  - `google_ads_conversion_id`: uppercase, regex-check `AW-…`; inline error on mismatch.
  - `google_ads_conversion_label`: `sanitize_text_field`.
  - UTM fields: lowercase, allow `[a-z0-9_-]`.
  - `meta_capi_token`: if submitted empty → keep stored; else `sanitize_text_field`.
  - `dont_output_tags`: cast to `0/1`.
  - Valid saves rely on the Settings API's standard **"Settings saved"** notice;
    field errors surface via `settings_errors()`.

### 3.4 `includes/class-ats-smt-admin-menu.php`
- `add_menu_page( 'Marketing', 'Marketing', 'manage_options', 'ats-marketing', cb, 'dashicons-megaphone' )`.
- `add_submenu_page( 'ats-marketing', 'Social Media', 'Social Media', 'manage_options', 'ats-social-media', cb )`.
- Rename the auto first submenu item from "Marketing" to "Overview" (optional) or point
  the top level straight at a short overview that links to Social Media.
- Both page callbacks `require` `admin/views/settings-page.php` (the Social Media page);
  capability re-checked inside the callback.
- `admin_enqueue_scripts` → enqueue `admin/css/admin.css` only on the `ats-social-media`
  screen.

### 3.5 `includes/class-ats-smt-tracking.php` (front end)
- **Constant bridge** — on `plugins_loaded` (pri 1):
  ```php
  $s = ats_smt_get_settings();
  if ( ! defined( 'ATS_GA4_MEASUREMENT_ID' ) && ! empty( $s['ga4_measurement_id'] ) ) {
      define( 'ATS_GA4_MEASUREMENT_ID', $s['ga4_measurement_id'] );
  }
  if ( ! defined( 'ATS_META_PIXEL_ID' ) && ! empty( $s['meta_pixel_id'] ) ) {
      define( 'ATS_META_PIXEL_ID', $s['meta_pixel_id'] );
  }
  ```
  The existing theme GA4 + Meta code reads those constants → now driven by the UI.
  (`header.php` runs far later than `plugins_loaded`, so timing is safe.)
- **Google Ads conversion (the gap):** gate everything on
  `ats_smt_consent_granted() && ! ats_smt_is_excluded() && empty( $s['dont_output_tags'] )`
  and a non-empty `google_ads_conversion_id`.
  - `wp_head`: if `gtag` isn't already loaded by GA4 (i.e. `ATS_GA4_MEASUREMENT_ID`
    unset), print the `gtag.js` loader; then `gtag('config', 'AW-…')`. Guard with a JS
    `window.gtag` check to avoid a double loader.
  - **Order received** (`is_wc_endpoint_url('order-received')`): fire
    `gtag('event','conversion',{ send_to:'AW-…/LABEL', value, currency, transaction_id })`.
    Refresh-dedup via order meta `_ats_smt_google_ads_tracked` (mirrors the theme's
    `_ats_ga4_tracked` pattern).
- **No GA4/Meta tag output here** — that stays the theme's job (avoids double-fire).
- CAPI token / dataset ID / Instagram ID are **stored only**, not output (reserved for
  the later server-side CAPI phase; eventID dedup is already wired in the theme via
  `order_{id}`).

### 3.6 `admin/views/settings-page.php`
- Capability check.
- Render the **verbatim "Client setup instructions" block** (from the task) as read-only
  help text in a styled box **above** the form (`wp_kses_post` / static HTML).
- `<form method="post" action="options.php">` → `settings_fields('ats_smt_group')`,
  `do_settings_sections('ats-social-media')`, `submit_button()`.
- `settings_errors()` near the top for the "Settings saved" + inline validation messages.

### 3.7 `uninstall.php`
- `if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }`
- `delete_option( 'ats_social_media_settings' )`. (Runs on **delete** only, not
  deactivation.)

### 3.8 `admin/css/admin.css`
- Light styling for the help box (callout panel) and the masked secret field. Admin
  screen only. No front-end CSS.

### 3.9 `readme.txt`
- Short standard WP readme (name, description, the architecture note, changelog 1.0.0).

---

## 4. The one sensitive change — `wp-config.php` (CONFIRM BEFORE DOING)

For the admin UI to be **authoritative**, the plugin must be able to define
`ATS_GA4_MEASUREMENT_ID` / `ATS_META_PIXEL_ID`. PHP can't redefine a constant, and
`wp-config.php` currently defines both — so while those lines exist, `wp-config` wins and
the UI can only mirror them.

**Recommended migration (staging first):**
1. On plugin activation, `ats_smt_activate()` copies the current constant values into
   `ats_social_media_settings` **if the option is empty** → nothing is lost.
2. Remove these two lines from
   `/var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-config.php`:
   ```php
   define( 'ATS_GA4_MEASUREMENT_ID', '…' );   // line ~104
   define( 'ATS_META_PIXEL_ID', '…' );        // line ~109
   ```
3. The plugin's `plugins_loaded` bridge now defines them from the option → theme keeps
   working, UI is authoritative.
4. **Production:** repeat steps 1–2 on prod **only with explicit go-ahead** (`wp-config`
   is not in version control / not synced by the theme deploy). Document, don't auto-apply.

**Alternative (no `wp-config` edit):** leave both `define()` lines in place. The plugin's
bridge then no-ops for GA4/Meta (constants already defined), so the UI shows those two
IDs **read-only / for reference**, and the UI fully controls only Google Ads + CAPI +
UTM. Zero risk, but GA4/Meta IDs still change via `wp-config`, not the UI.

> **Decision needed:** migration (recommended, UI authoritative) **or** no-edit
> (GA4/Meta IDs stay `wp-config`-managed, shown read-only).

---

## 5. Fields (single option `ats_social_media_settings`)

| Key | Group | Type | Validation | Output use |
|-----|-------|------|-----------|------------|
| `meta_pixel_id` | A | text | `^\d{15,16}$` | Bridges to theme Meta Pixel |
| `meta_capi_token` | A | password (masked) | non-empty; kept if blank-submitted | Stored only (future CAPI) |
| `meta_dataset_id` | A | text (optional) | `^\d+$` | Stored only |
| `instagram_business_id` | A | text (optional) | `^\d+$` | Stored only |
| `ga4_measurement_id` | B | text | `^G-[A-Z0-9]+$` | Bridges to theme GA4 |
| `google_ads_conversion_id` | B | text | `^AW-\d+$` | Google Ads tag (plugin) |
| `google_ads_conversion_label` | B | text | `sanitize_text_field` | Google Ads tag (plugin) |
| `utm_source_facebook` | C | text | `[a-z0-9_-]`, default `facebook` | Reference |
| `utm_source_instagram` | C | text | `[a-z0-9_-]`, default `instagram` | Reference |
| `utm_medium` | C | text | `[a-z0-9_-]`, default `paid_social` | Reference |
| `dont_output_tags` | Tag output | checkbox | `0/1` | Suppresses plugin's Google Ads output |

---

## 6. Acceptance criteria (from task)

- [ ] "Marketing" menu + "Social Media" submenu visible to admins only.
- [ ] Saving persists to `ats_social_media_settings` + standard "Settings saved" notice.
- [ ] Invalid IDs show **inline** errors (regex per §5); valid ones save.
- [ ] CAPI token rendered as password, only last-4 shown, never re-echoed, not wiped on
      blank re-save.
- [ ] Google Ads ID + label set → conversion fires on order-received (value/currency/
      transaction_id), once per order.
- [ ] **GA4 + Meta still fire exactly once** (no double-count) — verified in page source.
- [ ] Tags gated behind `ats_social_media_tracking_consent_granted` + the
      "Don't output tags" checkbox.
- [ ] No PHP notices/warnings; nonce + capability checks on save (Settings API handles
      nonce); `uninstall.php` removes the option on delete.

---

## 7. Verification plan

1. `php -l` on every `.php` file (zero syntax errors).
2. Activate on staging; confirm option seeded from constants (§4 step 1).
3. wp-cli round-trip: save valid + invalid values, assert sanitised result + inline error.
4. `curl` an order-received URL (or `wp eval`): assert exactly one GA4 `purchase`, one
   Meta `Purchase`, and one Google Ads `conversion` in the output.
5. Toggle "Don't output tags" → assert the Google Ads tag disappears, GA4/Meta unchanged.
6. Confirm CAPI token never appears in page source or in the field value attribute.

**Deploy:** staging only in this build. Production rollout (including the optional
`wp-config` edit) is a separate, explicitly-approved step — no prod changes without
go-ahead.
```
