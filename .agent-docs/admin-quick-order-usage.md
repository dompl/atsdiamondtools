# Admin Quick Order Panel - Usage Guide

## Overview

The **Admin Quick Order Panel** is a powerful ACF Flexible Content component that allows website administrators to quickly search for products and create orders without browsing the website. Perfect for phone orders, quick order entry, and wholesale/trade customers.

## Features

- ✅ **Live Product Search** - Real-time search with AJAX
- ✅ **Advanced Filters** - Filter by category, brand, and stock status
- ✅ **Quick Add to Cart** - Add products with quantity in one click
- ✅ **Running Cart Display** - See cart building up in real-time
- ✅ **Customer Email Field** - Optional field to associate order with customer
- ✅ **Responsive Design** - Works on desktop and mobile
- ✅ **Admin Restriction** - Can be restricted to administrators only

## Setup Instructions

### 1. Create a New Page

1. Go to **Pages > Add New** in WordPress admin
2. Give it a name like "Quick Order Panel" or "Admin Order Tool"
3. Set page status to **Draft** or **Private** (recommended for security)

### 2. Add the Component

1. In the page editor, look for **Flexible Content** field
2. Click **Add Row**
3. Select **Admin Quick Order Panel**
4. Configure the settings:
   - **Section Heading**: Default is "Quick Order Panel"
   - **Instructions**: Help text for users
   - **Show Customer Email Field**: Enable/disable customer email input
   - **Show Product Filters**: Enable/disable category/brand filters
   - **Products Per Row**: Choose 2, 3, or 4 products per row
   - **Admin Only**: Restrict to administrators (recommended: ON)

### 3. Publish and Use

1. Save the page
2. Visit the page URL
3. Start searching for products!

## How to Use

### Searching for Products

1. **Type in the search box** - Search by product name, SKU, or description
2. Results appear instantly as you type
3. **Use filters** (if enabled):
   - Filter by Category
   - Filter by Brand
   - Filter by Stock Status

### Adding Products to Cart

1. Find the product you want
2. Adjust quantity using **+** / **-** buttons or type directly
3. Click **Add to Cart**
4. Product is instantly added - see it in the cart sidebar

### Managing the Cart

- **View Cart**: Click to see full cart page
- **Proceed to Checkout**: Complete the order
- **Clear All**: Remove all items from cart

### Customer Email (Optional)

If enabled, you can enter a customer's email address at the top of the panel. This helps associate the order with a specific customer for record-keeping.

## Advanced Tips

### For Phone Orders

1. Have the customer on the phone
2. Enter their email in the Customer Email field
3. Search and add products as they request
4. Review cart totals with customer
5. Proceed to checkout and complete order

### Bulk Ordering

1. Search for first product
2. Set quantity
3. Add to cart
4. Repeat for all products
5. Review cart
6. Checkout when complete

### Finding Products Quickly

- **Search by SKU**: If you know the SKU, just type it
- **Use filters**: Narrow down by category first
- **Sort results**: Products are sorted alphabetically by default

## Technical Details

### Files Created

- **ACF Component**: `src/functions/acf/components/admin-quick-order.php`
- **Output Template**: `src/functions/acf/outputs/admin-quick-order.php`
- **JavaScript**: `src/assets/js/components/admin-quick-order.js`
- **AJAX Handlers**: `src/functions/ajax/quick-order.php`

### AJAX Actions

- `ats_quick_order_search`: Product search with filters
- `ats_get_cart_contents`: Get current cart for display

### Security

- Nonce verification on all AJAX requests
- Admin-only restriction option
- Page can be set to Private or Draft

## Troubleshooting

### Products Not Appearing

- Check that products are published
- Verify stock status filter isn't excluding products
- Try clearing search and filters

### Search Not Working

- Hard refresh the page (Ctrl+F5 / Cmd+Shift+R)
- Check browser console for JavaScript errors
- Verify nonce is being generated (check page source)

### Add to Cart Not Working

- Ensure WooCommerce is active
- Check product stock status
- Verify AJAX URL is correct in themeData

## Future Enhancements

Possible additions for future versions:

- Barcode scanner support
- Product variations support
- Quick order history
- CSV import for bulk orders
- Print order summary
- Custom pricing for wholesale

---

**Created**: January 2026
**Version**: 1.0
**Requires**: ACF Extended, WooCommerce
