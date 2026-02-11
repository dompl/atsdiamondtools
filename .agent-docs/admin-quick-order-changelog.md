# Admin Quick Order Panel - Changelog

## Changes Implemented (2026-02-02)

### 1. **Fixed Display Issues**

#### Container Wrapper
- ✅ Wrapped entire component in `container mx-auto px-4 py-8` for proper page width
- Previously the component was full-width, now it's properly contained

#### Subtotal Display Fix
- ✅ Fixed HTML escaping issue where price was showing as text instead of rendered HTML
- Changed from `textContent` to `innerHTML` in JavaScript: `src/assets/js/components/admin-quick-order.js:409`
- Now properly displays formatted prices like "£10.00" instead of escaped HTML

#### Stock Quantity Display
- ✅ Removed inline stock quantity display from product cards
- Product cards now show only "In Stock" / "Out of Stock" status
- Removed quantity input and +/- buttons from product cards

---

### 2. **Product Display Integration with Quick View Modal**

#### Changed Product Card Behavior
- ✅ Product cards now open the **Product Quick View Modal** when clicked
- Same modal used on shop page for consistency
- Removed inline add-to-cart functionality from product cards

#### What Was Changed:
1. **Product Card HTML** (`src/assets/js/components/admin-quick-order.js:293-312`)
   - Removed quantity inputs and add-to-cart button
   - Added hover overlay with "Quick View" text
   - Made entire card clickable
   - Added cursor-pointer and hover effects

2. **Click Handler** (`src/assets/js/components/admin-quick-order.js:178-188`)
   - Detects product card clicks
   - Opens `ProductQuickView.openQuickView(productId)`
   - Quick View modal already exists in footer (`src/footer.php:2`)

#### User Experience:
- Click any product card → Opens quick view modal
- Modal shows full product details, images, variations, and add-to-cart form
- Matches shop page experience exactly

---

### 3. **Customer Search & Order Assignment**

#### Customer Search Feature
- ✅ Added AJAX-powered customer search field
- Search by **email address** or **last name**
- Real-time search with 300ms debounce

#### Files Changed:
1. **Output Template** (`src/functions/acf/outputs/admin-quick-order.php:66-95`)
   - Added customer search input field
   - Added customer results container
   - Added selected customer display section

2. **JavaScript** (`src/assets/js/components/admin-quick-order.js`)
   - Customer search input handler with debounce
   - `searchCustomers()` function for AJAX calls
   - `renderCustomerResults()` to display results
   - `selectCustomer()` to store selection
   - `clearCustomerSelection()` to reset

3. **AJAX Handlers** (`src/functions/ajax/quick-order.php:237-369`)
   - `ats_handle_search_customers` - Search users by email/last name
   - `ats_search_users_by_last_name` - Custom query to search by meta fields
   - `ats_handle_set_order_customer` - Store customer ID in session
   - `ats_handle_clear_order_customer` - Clear customer from session

#### How It Works:

**Step 1: Search for Customer**
```
Admin types in search box → AJAX searches WordPress users
Results show customer name + email → Admin clicks to select
```

**Step 2: Customer Stored in Session**
```
Selected customer ID stored in: WC()->session->get('ats_order_for_customer_id')
Displayed in green box with name and email
```

**Step 3: Checkout Auto-Population**
```
Filter: woocommerce_checkout_get_value
Populates all billing/shipping fields from customer record
Fields: name, email, phone, address, city, postcode, etc.
```

**Step 4: Order Assignment**
```
Action: woocommerce_checkout_order_created
Order automatically assigned to selected customer ID
Admin can checkout as "guest" but order belongs to customer
Session cleared after order created
```

#### Code References:
- **Checkout field population**: `src/functions/ajax/quick-order.php:338-369`
- **Order assignment hook**: `src/functions/ajax/quick-order.php:371-387`

---

### 4. **Fixed Clear Cart Button**

#### What Was Wrong:
- Button was calling non-existent `woocommerce_clear_cart` action
- Wrong nonce was being used

#### Fix Applied:
- Created proper AJAX handler: `ats_handle_clear_cart` (`src/functions/ajax/quick-order.php:329-336`)
- Uses correct nonce: `cart_nonce` instead of `quick_order_nonce`
- Properly empties cart: `WC()->cart->empty_cart()`
- JavaScript updated to use new action: `src/assets/js/components/admin-quick-order.js:428-440`

---

## Testing Checklist

### Prerequisites:
- [x] Must be logged in as administrator
- [x] Visit: http://atsdiamondtools.rfsdev.co.uk/admin-sale/

### Test Cases:

#### ✅ Layout & Display
- [ ] Page is properly contained (not full-width)
- [ ] Customer search section displays at top
- [ ] Product search and filters work
- [ ] Product cards show "Quick View" overlay on hover

#### ✅ Product Quick View
- [ ] Click product card → Quick view modal opens
- [ ] Modal shows product images, variations, description
- [ ] Add to cart from modal updates cart sidebar
- [ ] Close modal button works

#### ✅ Customer Search
- [ ] Type email/name → Results appear
- [ ] Click customer → Displays in green box
- [ ] Clear customer button removes selection
- [ ] Search by last name works
- [ ] Search by email works

#### ✅ Checkout Flow
1. [ ] Select a customer from search
2. [ ] Add products to cart via quick view
3. [ ] Click "Proceed to Checkout"
4. [ ] Verify checkout fields are pre-filled with customer data
5. [ ] Complete order
6. [ ] Verify order is assigned to customer (not admin)
7. [ ] Check order shows customer email/name in admin

#### ✅ Guest Checkout
- [ ] Leave customer search empty
- [ ] Add products and checkout
- [ ] Order should be guest order (no customer assigned)
- [ ] Billing info must be entered manually

#### ✅ Clear Cart
- [ ] Add items to cart
- [ ] Click "Clear All" in cart sidebar
- [ ] Confirm dialog appears
- [ ] Cart empties successfully
- [ ] Cart sidebar shows "Cart is empty"

#### ✅ Cart Display
- [ ] Subtotal shows properly formatted price (£10.00)
- [ ] Item count updates correctly
- [ ] Product thumbnails display in cart
- [ ] Product names display correctly

---

## Known Limitations

1. **Administrator Restriction**
   - Component only visible to administrators (admin_only = true by default)
   - Can be changed in ACF field settings if needed

2. **Customer Search Scope**
   - Searches all WordPress users (not just customers)
   - Limited to 10 results
   - Searches: email, display_name, last_name, billing_last_name

3. **Session Dependency**
   - Customer selection stored in WooCommerce session
   - Clearing browser cookies will reset selection
   - Admin must stay logged in throughout process

---

## Files Modified

### PHP Files:
1. `src/functions/acf/components/admin-quick-order.php` - Added filter registration
2. `src/functions/acf/outputs/admin-quick-order.php` - Updated layout, added customer search
3. `src/functions/ajax/quick-order.php` - Added 4 new AJAX handlers + 2 WooCommerce hooks

### JavaScript Files:
1. `src/assets/js/components/admin-quick-order.js` - Complete rewrite of product display and customer search

### No Changes Required:
- `src/assets/js/components/product-quick-view.js` - Already working, no changes needed
- `src/functions/template-parts/product-quick-view-modal.php` - Already included in footer
- `src/footer.php` - Already includes quick view modal

---

## Debug Information

### AJAX Actions Registered:
```php
wp_ajax_ats_quick_order_search        // Product search
wp_ajax_ats_get_cart_contents         // Cart refresh
wp_ajax_ats_search_customers          // Customer search
wp_ajax_ats_set_order_customer        // Store customer in session
wp_ajax_ats_clear_order_customer      // Clear customer from session
wp_ajax_ats_clear_cart                // Empty cart
```

### Session Storage:
```php
WC()->session->get('ats_order_for_customer_id') // Stores selected customer ID
```

### WooCommerce Hooks Used:
```php
woocommerce_checkout_get_value        // Auto-fill checkout fields
woocommerce_checkout_order_created    // Assign order to customer
```

---

## Future Enhancements (Not Implemented)

### Possible Improvements:
1. Add ability to create new customer from quick order panel
2. Show customer's order history when selected
3. Apply customer-specific discounts automatically
4. Add customer notes/preferences display
5. Support for multiple customer roles/permissions
6. Export order list for selected customer

---

## Troubleshooting

### Issue: Quick View Modal Not Opening
**Check:**
- ProductQuickView JavaScript is loaded
- No JavaScript console errors
- Quick view modal exists in DOM (check footer.php)

### Issue: Customer Search Returns No Results
**Check:**
- User is logged in as administrator
- WordPress users exist with matching email/last name
- AJAX nonce is valid (quick_order_nonce)

### Issue: Checkout Fields Not Pre-Filled
**Check:**
- Customer was selected before checkout
- Session is active (WC()->session works)
- Customer has billing/shipping data saved
- Filter `woocommerce_checkout_get_value` is running

### Issue: Order Not Assigned to Customer
**Check:**
- Customer ID in session: `WC()->session->get('ats_order_for_customer_id')`
- Hook `woocommerce_checkout_order_created` is firing
- Customer ID is valid WordPress user
- Order was created successfully

### Issue: Clear Cart Not Working
**Check:**
- Using correct nonce: `cart_nonce` (not quick_order_nonce)
- AJAX action registered: `ats_clear_cart`
- WooCommerce cart is available

---

## Support & Documentation

### Key Resources:
- Parent Theme Architecture: `./.agent-docs/parent-theme-architecture.md`
- Usage Instructions: `./.agent-docs/admin-quick-order-usage.md`
- Project Guidelines: `./CLAUDE.md`

### Contact Points:
- Debug Log: `/wp-content/debug.log`
- Browser Console: Check for JavaScript errors
- Network Tab: Check AJAX requests/responses
