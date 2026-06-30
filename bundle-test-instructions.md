# Product Bundles (Pro Kits) — How It Works & How To Test

**Feature:** A custom system for building "Pro Kit" style product bundles — pick existing
products, give the kit a custom image, a fixed price with a "Save £X" figure, optional
price options (e.g. Single/Double row), and a per-product description shown to the
customer.

**Built for:** the "Build Your Pro Kit" newsletter (the three kits below already exist).

**Status:** Built and tested on staging (`http://atsdiamondtools.rfsdev.co.uk`). Live on the
active theme via direct file sync. **Not yet deployed via `gulp dist`** (see *Going Live*).

---

## 1. What it does (in plain terms)

A bundle is just a normal WooCommerce product behind the scenes, so it automatically gets:

- its own product page at `/product/<slug>/`
- normal add-to-cart, checkout, Stripe/payment, order emails and PDF invoices
- a place in the shop / search / category listings

On top of that, the bundle adds:

- a **custom image**
- a list of **included products**, each with its own short description ("What's in the box")
- a **kit price** and a **"Save £X"** badge
- optional **price options** (e.g. *Single row £155 / Double row £175*), each with its own
  price, save figure and SKU
- when added to the basket it stays a **single line item** at the kit price, and the chosen
  option (label + SKU) is recorded on the line so the warehouse can pick it.

You manage everything from a dedicated **Bundles** screen — you never touch the standard
WooCommerce product editor.

---

## 2. Where to find it

**Admin menu:** WP Admin → **Bundles** (left sidebar, near Products).

- **Bundles** (list) — every kit you've made, with image, price, product count, status.
- **Add New Bundle** — the create/edit form.

**Direct URL:** `/wp-admin/admin.php?page=ats-bundles`

---

## 3. How to create a bundle — step by step

1. Go to **Bundles → Add New Bundle**.
2. **Kit name** — e.g. `Tilers Cutting & Drilling Kit`. This becomes the product title.
3. **URL slug** *(optional)* — leave blank to auto-generate, or type one to match a
   newsletter link (e.g. `tilers-cutting-drilling-kit` → `/product/tilers-cutting-drilling-kit/`).
4. **Description** — the kit's intro/marketing copy (rich text).
5. **Included products** — click **+ Add product**, then search and pick a product. Repeat
   for each item in the kit. For each product, type a short **description** ("what this part
   does in the kit") — it shows in the "What's in the box" grid.
   - As you add products, **Components total** updates automatically (the sum of their live
     prices). This is only used to suggest the saving.
6. **Pricing** — two modes:
   - **Single price (default):** enter **Kit price (£)** and **Save (£)**. When the kit
     costs less than the components, a *Suggested: £X* link appears next to Save — click it
     to fill the figure, or type your own.
   - **Price options:** tick **"This kit has price options"**. A repeater appears — add a
     row per option with **Label** (e.g. `With Single Row cup`), **Price**, **Save** and
     **SKU**. Each option gets its own suggested-save link too. The first option is the
     default shown on the page.
7. **Sidebar → Publish:** set **Status** to *Published* (or *Draft*), optionally a **Base
   SKU**, then click **Create bundle**.
8. **Sidebar → Custom image:** click **Select image**, choose/upload the main image,
   **Use this image**. (You can do this before saving.)
9. **Sidebar → Gallery images:** click **Add gallery images** to pick one or more extra
   photos. These show as a gallery with thumbnails on the product page (the main image is
   shown first). Click the red × on a thumbnail to remove it.
10. After saving you'll see *"Bundle saved"* and a **View bundle on site →** link. The kit is
    automatically added to the **Bundles** product category (so it shows in the shop sidebar
    and at `/product-category/bundles/`).

> **Editing later:** Bundles → click the kit name (or *Edit*). Change anything and **Update
> bundle**. The product-card cache is flushed automatically on save.

---

## 4. What the customer sees (frontend)

On `/product/<slug>/`:

- The **custom image** as the main product image, plus a **thumbnail gallery** if you added
  gallery images.
- The **kit price** with a yellow **"Save £X"** badge next to it.
- If the kit has options, a **"Choose your option"** radio selector. Picking an option
  **live-updates** the price and the Save badge (no reload).
- The normal **Add to Basket** button + quantity.
- A **"What's in the box"** section: a card per included product showing its live image,
  title (links to that product), live price, and your description.
- In shop/category/search listings the kit appears like any product with a green **"Save £X"**
  badge over the image (option kits show the *best* saving); option kits show **"From £155"**.

**Variable products inside a bundle:** the kit itself is always a fixed-price *simple* product,
so a variable component never changes the kit price or checkout. In "What's in the box" a
variable component shows **"From £[lowest price]"**, and its lowest price is what the admin
savings-suggestion uses.

---

## 5. Cart & checkout behaviour

- Adding a kit puts **one line** in the basket at the kit price.
- If an option was chosen, the line shows e.g. **"Option: With Double Row cup"** and uses
  that option's price.
- Choosing a different option and adding again creates a **separate** line (as expected).
- At checkout the chosen **option label + SKU** are written onto the order line item, so
  they appear on the order screen, the customer email and the PDF invoice/packing slip.

---

## 6. Bundles already in the system

Created and ready to test (all published):

| Kit | URL | Price | Save | Type |
|-----|-----|-------|------|------|
| Tilers Cutting & Drilling Kit | `/product/tilers-cutting-drilling-kit/` | £95 | £19.98 | single |
| Concrete Grinding Kit | `/product/concrete-grinding-kit/` | £155 / £175 | £28.63 / £28.95 | **options** (Single/Double row) |
| Electroplated Holesaw Package | `/product/electroplated-holesaw-package/` | £35 | £12.08 | single |
| Pro Polishing Starter Kit *(demo I created via the form)* | `/product/pro-polishing-starter-kit/` | £29.99 | £4.92 | single |

> Prices/saves match the newsletter's stated figures. The component products are real,
> representative ATS products — swap them for the exact newsletter line-ups any time by
> editing each kit.

---

## 7. Test checklist — what to test & how

Work through these. Expected result in *italics*.

### A. Backend / admin
1. **List loads:** Bundles menu → *table shows the 4 kits with image, price, count, status.*
2. **Create single-price kit:** Add New → name, add 2–3 products with descriptions, set a
   price below the components total → *a "Suggested" save link appears; click it → Save
   fills.* Publish → *"Bundle saved"; it appears in the list and on the front end.*
3. **Create options kit:** tick "This kit has price options", add two options with different
   prices/SKUs → *Save → front end shows the radio selector.*
4. **Product search:** in the product picker, type 2+ characters → *matching products appear
   (bundles themselves are excluded).* 
5. **Custom image:** Select image → choose one → save → *image shows as the product image on
   the front end and as the thumbnail in the list.*
6. **Edit + re-save:** change a price → Update → *front end reflects the new price.*
7. **Draft:** set a kit to Draft → *it is not visible to logged-out visitors.*

### B. Frontend — single-price kit (e.g. Tilers)
8. Visit the page → *kit price + "Save £19.98" badge; no option selector; "What's in the box"
   lists each product with image, title link, price and description.*
9. Click a "What's in the box" product title → *opens that product's own page.*

### C. Frontend — options kit (Concrete Grinding Kit)
10. *Default shows "With Single Row cup" selected, £155.00, Save £28.63.*
11. Click **With Double Row cup** → *price changes to £175.00 and badge to Save £28.95
    instantly.*

### D. Basket & checkout (the important one)
12. On the Concrete Grinding Kit, select **Double Row**, **Add to Basket**, open the basket →
    *one line: "Concrete Grinding Kit", "Option: With Double Row cup", £175.00.*
13. Empty the basket, add the **Single Row** option → *line shows £155.00 / "With Single Row
    cup".*
14. Add a single-price kit (Tilers) → *one line at £95.00, no option text.*
15. *(Optional, needs a test order)* Complete checkout with a test payment → open the order in
    WP Admin → *the line item shows the Option label and Kit SKU; same on the PDF invoice.*

### E. Regression (make sure normal products still work)
16. Open any **non-bundle** product (e.g. a single blade) → *price shows normally, no Save
    badge, no "What's in the box", add-to-cart works as before.*

### How to test
- **Manually** in a browser at `http://atsdiamondtools.rfsdev.co.uk` (front end is public; for
  the admin use your own administrator login).
- Use a **private/incognito window** to check the logged-out customer view.
- If a kit looks stale after an edit, it's the product-card cache — it's flushed on save, but
  you can force it from the admin bar **"Clear product cache"** button or
  `wp transient delete --all`.

---

## 8. Automated checks already run (for reference)

- PHP lint clean on all new files; WordPress loads them with no fatal errors.
- Data model verified via WP-CLI (items, options, prices, save figures read back correctly).
- Playwright on staging:
  - admin list renders the kits;
  - the Add New form saves a new kit end-to-end (product search AJAX returned 20 results,
    components total + save suggestion calculated, product + meta created on save);
  - options kit live-updates price/save (£155→£175);
  - **add-to-cart records the chosen option** — basket shows one line at £175 /
    "With Double Row cup";
  - single-price kit renders price, save badge and the 2-item "What's in the box" grid.

---

## 9. Notes & v1 limitations

- A kit is its **own SKU/stock** — adding it does **not** deduct stock from the individual
  component products (you manage the kit's own stock). This was the chosen behaviour.
- Components in "What's in the box" are **descriptive** (image, title, price, your text) — the
  customer isn't buying them individually from the kit page.
- The component products shown are representative; edit each kit to set the exact newsletter
  line-up if needed.
- Per-option independent stock and "explode the kit into separate cart lines" are
  intentionally **out of scope** for v1.

---

## 10. Files (for the developer)

All under the child theme (`skylinewp-dev-child/src/`, auto-loaded from `functions/woocommerce/`):

| File | Responsibility |
|------|----------------|
| `functions/woocommerce/bundle-core.php` | Data model, meta keys, read helpers |
| `functions/woocommerce/bundle-admin.php` | Bundles admin menu, list, form, save handler, product-search AJAX, admin CSS/JS |
| `functions/woocommerce/bundle-cart.php` | Cart price, cart/checkout option display, order-line meta |
| `functions/woocommerce/bundle-frontend.php` | Single-product price/save block, option selector, "What's in the box", listing "From" price, inline CSS/JS, add-to-cart AJAX option passthrough |
| `functions/shortcodes/product.php` | *(1 small change)* added `ats_product_price_html` filter so bundles can inject the price/save block |

CSS & JS for the feature are enqueued inline (no SCSS/rollup build needed) and only load on
bundle pages / the Bundles admin screen.

---

## 11. Going live (deployment)

Changes are currently synced straight into the active `atsdiamondtools-child` theme for
testing. The canonical deploy is **`gulp dist`** (it minifies, bumps the version, commits +
pushes to `master` and re-activates the theme). Per project rules I have **not** run it — say
the word and I'll deploy. (Also: not on a Friday afternoon. 🙂)
