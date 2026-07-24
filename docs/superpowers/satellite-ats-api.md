# Satellite Bridge — ATS-side API reference

The ATS-side of the satellite platform, implemented in the theme at
`src/functions/woocommerce/satellite-*.php`. Auto-loaded by the parent's ACF
loader; **inert on normal traffic** — every hook no-ops unless a request carries
a valid channel signature. Nothing here stores markup: it is fetched from the
platform as a signed ruleset and cached transiently, so it never appears in WP
admin.

## Files

| File | Responsibility |
|---|---|
| `satellite-pricing-engine.php` | Pure integer-pence markup engine (mirror of the TS reference) |
| `satellite-channel.php` | HMAC channel recognition — the security gate |
| `satellite-ruleset.php` | Fetch/cache the signed ruleset; never-undercharge fallback |
| `satellite-pricing.php` | Apply markup via WooCommerce price filters; refuse checkout with no ruleset |
| `satellite-restrictions.php` | Exclusions, coupon allowlist, free-shipping toggle, hidden methods |
| `satellite-orders.php` | Tag channel orders; Channel column in the orders list |
| `satellite-emails.php` | Mute ATS customer order emails for channel orders |
| `satellite-accounts.php` | Login/register/reset + channel-filtered order history |

## Configuration (ATS)

Set in `wp-config.php` (kept out of the admin UI):

```php
// Channel registry: key => { secret, label }
define( 'SATELLITE_CHANNELS', json_encode( array(
    'ea' => array( 'secret' => 'CHANGE-ME-LONG-RANDOM', 'label' => 'Edge Abrasives' ),
) ) );

// Base URL of the platform's ruleset API
define( 'SATELLITE_PLATFORM_URL', 'https://platform.example/api/' );
```

Both are also overridable via the `skylinewp_child_satellite_channels` and
`skylinewp_child_satellite_platform_url` filters. Ruleset cache TTL is filterable
via `skylinewp_child_satellite_ruleset_ttl` (default 5 min).

Also required (WP admin, no code): a read-only wc/v3 REST key for the catalogue
sync, and product/order webhooks to the platform.

## Request signing (both directions)

Every platform→ATS request (and ATS→platform ruleset fetch) carries:

```
X-EA-Channel    <channel key>
X-EA-Timestamp  <unix seconds>
X-EA-Sign       hex HMAC-SHA256( "<key>\n<timestamp>\n<body>", secret )
```

Verified within a ±300s window with `hash_equals`. Account requests additionally
send `X-EA-Auth: <login token>`.

## Ruleset contract (platform serves, ATS consumes)

`GET {SATELLITE_PLATFORM_URL}ruleset/{channel}` → signed JSON:

```json
{
  "version": 42,
  "markup":  { "type": "percent", "value": 1500, "maxUplift": 5000,
               "minUplift": 0, "rounding": "charm" },
  "followSales": true,
  "productMarkup": { "9000": { "type": "fixed", "value": 1000, "rounding": "none" },
                     "5001": "none" },
  "excluded":      [ 12345, 999 ],
  "coupons":       [ "ats10", "free24" ],
  "freeShipping":  true,
  "hiddenMethods": [ "table_rate:8" ]
}
```

- `markup.type` — `percent` (basis points; 1500 = 15%) or `fixed` (pence).
- `rounding` — `none` | `nearest_pound` | `charm` (nearest `.99`).
- `productMarkup[id]` — a replacement rule, or the string `"none"` for no markup.
- **Never-undercharge:** if ATS cannot obtain a ruleset it uses the last-known-good;
  with none ever cached it refuses checkout. It never falls back to base prices.

## Account routes (`satellite/v1`, all channel-signed)

| Method | Route | Body / header | Returns |
|---|---|---|---|
| POST | `/login` | `{ login, password }` | `{ token, customer }` |
| POST | `/register` | `{ email, password, first_name?, last_name? }` | `{ token, customer }` |
| POST | `/reset-request` | `{ email }` | `{ exists, key, login }` |
| POST | `/reset` | `{ login, key, password }` | `{ ok }` |
| GET | `/orders` | header `X-EA-Auth: <token>` | `{ orders: [...] }` |

`/orders` returns **only the requesting channel's orders**, filtered on ATS from
the signed channel key — the platform cannot widen it with a parameter.

## Verification status

- **Pure logic** (engine, ruleset decisions, HMAC, marked-price, restrictions,
  email decision, order isolation): 66 standalone checks, plus the 27 TypeScript
  reference tests. All green.
- **Live hook wiring** (`docs/superpowers/integration/`): verified against the
  running WooCommerce via `wp eval-file` — a signed channel request marks a real
  £155 product up to £177.99 through `get_price`, an excluded product is
  non-purchasable and unmarked, the coupon allowlist bites, and a channel-off
  request leaves everything untouched. 10/10 channel-on, 3/3 channel-off.

Still to verify live (needs creating a real order/customer, so deferred to the
first platform build): the Store API checkout-refusal path when no ruleset is
available, order tagging + email suppression on a real order, and the REST
account routes — including the REST body/`php://input` interplay used by channel
verification on those routes.
