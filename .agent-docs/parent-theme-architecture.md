# SkylineWP Parent Theme Architecture Documentation

**Generated:** 2025-11-26
**Parent Theme Path:** `/var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-parent/src`
**Total PHP Files:** 812 files
**Version:** 1.3

---

## Table of Contents

1. [Overview](#overview)
2. [Core Functions](#core-functions)
3. [Image Handling - wpimage()](#image-handling---wpimage)
4. [Helper Functions](#helper-functions)
5. [ACF Extended Integration](#acf-extended-integration)
6. [Custom ACF Fields](#custom-acf-fields)
7. [Flexible Content System](#flexible-content-system)
8. [Available Hooks & Filters](#available-hooks--filters)
9. [Asset Management](#asset-management)
10. [Theme Cleanup & Optimization](#theme-cleanup--optimization)
11. [Image Sizes](#image-sizes)
12. [UIKit Integration](#uikit-integration)
13. [Build System](#build-system)
14. [File Organization](#file-organization)

---

## Overview

SkylineWP is a parent theme built with:
- **ACF Extended** (Extended\ACF namespace) for field group registration
- **Tailwind CSS** for utility-first styling
- **Gulp** for build automation
- **WebP conversion** via rosell-dk/webp-convert
- **Custom image handling** with wpimage() function
- **Modular architecture** with deferred ACF loading to prevent textdomain issues

### Key Architecture Decisions

1. **ACF-dependent files are loaded on `init` action** (priority 1) to prevent early textdomain loading
2. **Child theme detection** - Parent assets only load when no child theme is active
3. **Environment-aware caching** - Transient caching disabled in WP_DEBUG mode
4. **Gutenberg disabled** for pages (but not posts)
5. **Comments completely disabled** across the site
6. **Default WooCommerce assets dequeued** - must manually include required assets

---

## Core Functions

### Main Setup Class

**File:** `/src/functions.php`

```php
class SkylineWPParentThemeSetup
```

**Purpose:** Orchestrates theme initialization, Composer autoloading, and ACF dependency management.

**Key Methods:**

- `load_autoloader()` - Loads Composer dependencies on `init` action (priority 0)
- `include_functions_directory()` - Recursively loads all PHP files in `/functions/` except:
  - Files in `/components/` directory
  - Files in `/template-parts/` directory
  - ACF-dependent files (loaded separately)
- `include_acf_dependent_files()` - Loads ACF-dependent files on `init` action (priority 1)
- `is_acf_dependent_file()` - Detects files that use ACF functions to defer loading
- `ensure_acf_is_active()` - Forces ACF Pro plugin activation
- `prevent_acf_deactivation()` - Removes deactivate link for ACF
- `hide_acf_from_plugins_list()` - Hides ACF from plugins admin page

**ACF Protection:**
The theme actively prevents ACF Pro from being deactivated or hidden, treating it as a required dependency.

---

## Image Handling - wpimage()

**File:** `/src/functions/plugins/wpimage/wpimage.php`

### wpimage() Function

**The primary image function for the entire theme.** All image rendering MUST use this function.

```php
function wpimage(
    $image,                    // int (attachment ID) or string (URL)
    $size,                     // int or array [width, height]
    $reversed = false,         // bool - swap width/height
    $retina = false,           // bool - generate @2x version
    $webp = true,              // bool - convert to WebP
    $preserve_aspect_ratio = true,  // bool
    $quality = 85              // int - JPEG/WebP quality
)
```

**Full Signature with Named Arguments:**
```php
wpimage(
    image: 123,                // Attachment ID or URL
    size: [1200, 800],         // Target dimensions
    reversed: false,           // If true, swaps dimensions in array
    retina: true,              // Generates @2x version
    webp: true,                // Converts to WebP format
    preserve_aspect_ratio: true, // Maintains aspect ratio
    quality: 85                // Image quality (1-100)
)
```

**What It Does:**

1. **Accepts attachment ID or URL** as input
2. **Resizes and crops images** to exact dimensions
3. **Generates retina (@2x) versions** when `$retina = true`
4. **Converts to WebP automatically** (returns WebP URL if successful)
5. **Preserves PNG transparency perfectly** using Imagick
6. **Handles upscaling** via `allow_image_upscaling()` filter
7. **Cleans up generated files** when attachment is deleted
8. **Caches results** - returns existing files if already generated

**Special PNG Handling:**
- Uses **Imagick** exclusively for PNG files to preserve transparency
- Forces `png32` format for alpha channel support
- Sets lossless WebP conversion for PNG sources

**Return Value:**
- Returns the URL to the processed image (WebP if conversion succeeded, otherwise original format)
- Falls back to original URL on error

**Example Usage:**

```php
// Basic usage with attachment ID
$img_url = wpimage(image: 123, size: 800);

// Retina image with custom dimensions
$img_url = wpimage(
    image: 123,
    size: [1200, 600],
    retina: true,
    quality: 90
);

// From URL (not recommended, use attachment ID when possible)
$img_url = wpimage(
    image: 'https://example.com/image.jpg',
    size: 640,
    webp: true
);
```

**Important Notes:**
- Generated images are stored in WordPress uploads directory
- Filename format: `{original}-{width}x{height}[@2x].{ext}`
- WebP versions: `{original}-{width}x{height}[@2x].webp`
- Always returns WebP URL when `$webp = true` and conversion succeeds
- **ALWAYS use this function instead of:**
  - `wp_get_attachment_image()`
  - `wp_get_attachment_image_src()`
  - `get_the_post_thumbnail()`
  - Standard `<img>` tags for uploaded media

### wpimage_upload() Function

```php
function wpimage_upload( $image_url )
```

**Purpose:** Downloads an external image URL to WordPress Media Library without attaching to a post.

**Features:**
- Checks if image already uploaded (via `external_image_url` meta key)
- Returns existing attachment ID if found
- Downloads and sideloads image if new
- Generates all thumbnail sizes automatically
- Stores original URL in post meta

**Return:** Attachment ID (int) or WP_Error on failure

**Example:**
```php
$attachment_id = wpimage_upload('https://external-site.com/image.jpg');
if (!is_wp_error($attachment_id)) {
    // Use wpimage() to display it
    $img = wpimage(image: $attachment_id, size: 800, retina: true);
}
```

### skylinewp_get_image_size_by_url() Function

```php
function skylinewp_get_image_size_by_url( $url )
```

**Purpose:** Gets image dimensions (width/height) from URL without loading the full image.

**Return:** Array with 'width' and 'height' keys, or null if unavailable

**Example:**
```php
$dimensions = skylinewp_get_image_size_by_url('https://example.com/image.jpg');
// Returns: ['width' => 1200, 'height' => 800]
```

### allow_image_upscaling() Function

**Purpose:** Allows WordPress to upscale images when cropping (hooked to `image_resize_dimensions` filter)

**When It's Used:** Automatically applied during `wpimage()` resize operations

---

## Helper Functions

### wrap_lines_with_element()

**File:** `/src/functions/helpers/wrap_lines_with_div.php`

```php
function wrap_lines_with_element(
    $content,           // string - Content to wrap
    $class = '',        // string - CSS class for wrapper
    $tag = 'div',       // string - HTML tag to use
    $last_item_class = '' // string - Special class for last item
)
```

**Purpose:** Splits content by `<br>` tags and wraps each line in specified HTML element.

**Example:**
```php
$text = "Line 1<br>Line 2<br>Line 3";
echo wrap_lines_with_element($text, 'text-line', 'span', 'last-line');
// Output:
// <span class="text-line">Line 1</span>
// <span class="text-line">Line 2</span>
// <span class="last-line">Line 3</span>
```

### convertToHtmlTag()

**File:** `/src/functions/helpers/convertToHtmlTag.php`

```php
function convertToHtmlTag(
    $input,                  // string - Format: "title|tag|class1,class2"
    $defaultTag = 'h2',      // string - Default tag if not specified
    $defaultClass = '',      // string - Default class if not specified
    $additionalAttributes = [] // array - Extra attributes ['style' => 'color:red;']
)
```

**Purpose:** Converts pipe-separated string into HTML element with classes and attributes.

**Input Format:**
- `"Title|h1"` → `<h1>Title</h1>`
- `"Title|h2|primary,bold"` → `<h2 class="primary bold">Title</h2>`
- `"Title|div|container"` → `<div class="container">Title</div>`

**Example:**
```php
echo convertToHtmlTag(
    "Welcome to Our Site|h1|hero-title,text-4xl",
    'h2',
    '',
    ['id' => 'main-heading']
);
// Output: <h1 class="hero-title text-4xl" id="main-heading">Welcome to Our Site</h1>
```

**Use Case:** Perfect for ACF fields where editors specify both content and markup.

---

## ACF Extended Integration

**The theme uses ACF Extended exclusively - NEVER use native ACF functions.**

### Required Namespace Imports

```php
use Extended\ACF\Fields\FlexibleContent;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\Accordion;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Fields\Checkbox;
use Extended\ACF\Location;
use Extended\ACF\ConditionalLogic;
```

### Field Group Registration

**Use `register_extended_field_group()` instead of `acf_add_local_field_group()`**

```php
register_extended_field_group([
    'title' => 'My Field Group',
    'fields' => [
        Text::make('Title', 'title')
            ->required(),
        Image::make('Background Image', 'bg_image')
            ->returnFormat('id'),
    ],
    'location' => [
        Location::where('post_type', '==', 'page')
    ]
]);
```

### ACF Extended Documentation

**ALWAYS use the Context7 MCP to check Extended ACF documentation before implementing:**

```bash
# Check Extended ACF docs via MCP
mcp__context7__resolve-library-id("extended-acf")
mcp__context7__get-library-docs("/vinkla/extended-acf", "code")
```

**Official Repository:** https://github.com/vinkla/extended-acf

---

## Custom ACF Fields

### UniqueID Field

**File:** `/src/functions/acf/custom-fields/UniqueID.php`

**Namespace:** `SkylineWP\ACF\Fields\UniqueID`

**Purpose:** Generates non-editable, unique 13-character IDs (format: `63340dddb9413`)

**Usage in Field Group:**
```php
use SkylineWP\ACF\Fields\UniqueID;

UniqueID::make('Component ID', 'component_id')
```

**Features:**
- Auto-generates on field load if empty
- Persists on save
- Displays as read-only text in admin
- Uses PHP `uniqid()` function
- 13 hexadecimal characters

**Hooks:**
- `acf/load_value/type=unique_id` - Generates value on load
- `acf/update_value/type=unique_id` - Persists value on save
- `acf/render_field/type=unique_id` - Custom render as plain text + hidden input

### Hidden Field

**File:** `/src/functions/acf/custom-fields/hidden.php`

**Purpose:** Field that's hidden in admin but still saved to database

### Popup Text Field

**File:** `/src/functions/acf/custom-fields/popup-text.php`

**Purpose:** Text field with popup/modal editing interface

---

## Flexible Content System

**File:** `/src/functions/acf/flex.php`

**Class:** `SkylineWPFlexibleContent`

### How It Works

The theme uses ACF Flexible Content for modular page building. Components are auto-loaded from:

1. `/functions/acf/shared/*.php` (child theme)
2. `/functions/acf/components/*.php` (child theme)
3. Additional directories via `skylinewp_additional_acf_directories` filter

**Each component file must return a `Layout` instance:**

```php
<?php
// File: /functions/acf/components/hero-section.php

use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Image;

return Layout::make('Hero Section', 'hero_section')
    ->fields([
        Text::make('Heading', 'heading'),
        Text::make('Subheading', 'subheading'),
        Image::make('Background', 'background')
            ->returnFormat('id'),
    ]);
```

### Component Output Templates

**Location:** Create a filter for `skylinewp_flexible_content_output`

```php
add_filter('skylinewp_flexible_content_output', function($output, $layout_name) {
    if ($layout_name === 'hero_section') {
        ob_start();
        include get_stylesheet_directory() . '/template-parts/hero-section.php';
        return ob_get_clean();
    }
    return $output;
}, 10, 2);
```

### Caching System

**Transient Caching (Production):**
- Set `define('ACF_USE_TRANSIENT', true);` in wp-config.php
- Layout files cached for 24 hours
- Component output cached for 12 hours per post
- Auto-clears on post save

**Cache Clearing:**
- Manual: Visit `yoursite.com/wp-admin/?clear_acf_layout_cache=true`
- Automatic: Disabled when `WP_DEBUG` is true
- Per-post: Cleared on `acf/save_post` hook (priority 20)

### Key Methods

```php
SkylineWPFlexibleContent::get_content_blocks_output()
```
Returns buffered HTML of all flexible components for current post.

```php
SkylineWPFlexibleContent::append_content_blocks_to_content($content)
```
Hooked to `the_content` filter - appends flexible components to post content.

### Important Filters

**`skylinewp_flexible_content_location`**
Modify where flexible content appears:
```php
add_filter('skylinewp_flexible_content_location', function($location) {
    return [
        Location::where('post_type', '==', 'page'),
        Location::where('post_type', '==', 'product'),
    ];
});
```

**`skylinewp_flexible_content_post_types`**
Control which post types get components appended:
```php
add_filter('skylinewp_flexible_content_post_types', function($types) {
    return ['page', 'post', 'product'];
});
```

**`skylinewp_additional_acf_directories`**
Add custom component directories:
```php
add_filter('skylinewp_additional_acf_directories', function($dirs) {
    $dirs[] = get_stylesheet_directory() . '/acf-blocks';
    return $dirs;
});
```

---

## Available Hooks & Filters

### Asset Management Filters

**`use_skylinewp_localize`** (bool, default: true)
Enable/disable wp_localize_script for bundle.js
```php
add_filter('use_skylinewp_localize', '__return_false');
```

**`skylinewp_localize`** (array)
Modify JavaScript localization data
```php
add_filter('skylinewp_localize', function($data) {
    $data['custom_value'] = 'something';
    return $data;
});
```

**`use_skylinewp_admin_localize`** (bool, default: true)
Enable/disable admin.js localization

**`skylinewp_admin_localize`** (array)
Modify admin JavaScript localization data

### Image Size Filters

**`skylinewp_custom_image_sizes`** (array)
Add custom image sizes
```php
add_filter('skylinewp_custom_image_sizes', function($sizes) {
    $sizes[] = [
        'name' => 'product-thumbnail',
        'width' => 400,
        'height' => 400,
        'crop' => true,
    ];
    return $sizes;
});
```

**`skylinewp_remove_image_sizes`** (array)
Remove specific custom image sizes
```php
add_filter('skylinewp_remove_image_sizes', function($sizes) {
    return ['old-size-name'];
});
```

**`skylinewp_remove_default_image_sizes`** (array)
Remove default WordPress image sizes
```php
add_filter('skylinewp_remove_default_image_sizes', function($sizes) {
    // Default removes: medium, 1536x1536, 2048x2048, medium_large, large
    return $sizes;
});
```

### Excerpt Filter

**`skylinewp_excerpt_support_types`** (array)
Add excerpt support to post types
```php
add_filter('skylinewp_excerpt_support_types', function($types) {
    $types[] = 'product';
    return $types;
});
```

### Navigation Filters

**`uikit_nav_add_before_link`** (string, WP_Post $item)
Add content before navigation links

### ACF Flexible Content Filters

**`skylinewp_additional_acf_directories`** (array)
See [Flexible Content System](#flexible-content-system)

**`skylinewp_flexible_content_location`** (array)
See [Flexible Content System](#flexible-content-system)

**`skylinewp_flexible_content_output`** (string, string $layout_name)
See [Flexible Content System](#flexible-content-system)

**`skylinewp_flexible_content_post_types`** (array)
See [Flexible Content System](#flexible-content-system)

### Search Filters

**`skyline_search_post_types`** (array)
Post types included in search
```php
add_filter('skyline_search_post_types', function($types) {
    return ['post', 'page', 'product'];
});
```

**`skyline_search_result_limit`** (int, default: -1)
Maximum search results to return

**`skyline_search_result_snippet`** (string, int $post_id)
Modify search result snippet text

### Email Filters

**`skylinewp_smtp_settings`** (array)
Configure SMTP settings
```php
add_filter('skylinewp_smtp_settings', function($settings) {
    return [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'user@example.com',
        'password' => 'password',
        'encryption' => 'tls',
    ];
});
```

**`skylinewp_email_params`** (array)
Modify email parameters

### Actions

**`skyline_shortcode_helper`**
Hook for adding shortcode documentation/help

---

## Asset Management

**File:** `/src/functions/setup/enqueue.php`

**Class:** `SkylineWPThemeAssets`

### Frontend Assets

**Only loads parent assets when NO child theme is active:**

```php
if (get_template() === get_stylesheet()) {
    // Parent theme assets
}
```

**Loaded Assets (Parent Theme Only):**

1. **style.css** - Main stylesheet (handle: `theme-style`)
2. **build.css** - Development only, when `WP_ENV === 'development'` (handle: `build-css`)
3. **bundle.js** - Main JavaScript bundle (handle: `bundle`)
   - Location: `/assets/js/bundle.js`
   - Loaded in footer
   - Depends on jQuery
   - Version: File modification time

**JavaScript Localization:**
```javascript
// Available as window.skylinewp object
{
    ajax_url: '/wp-admin/admin-ajax.php',
    nonce: 'skyline_nonce',
    search_nonce: 'skyline_search_nonce'
}
```

### Admin Assets

**Loaded on ALL admin pages:**

1. **WordPress Media Library** (wp_enqueue_media)
2. **admin.js** - Custom admin JavaScript (handle: `admin-js`)
   - Location: `/assets/js/admin.js`
   - Depends on jQuery
   - Version: File modification time

**Admin JavaScript Localization:**
```javascript
// Available as window.skylinewp object
{
    ajax_url: '/wp-admin/admin-ajax.php',
    nonce: 'skyline_admin_nonce'
}
```

### Child Theme Asset Strategy

**In child theme, you must enqueue ALL assets manually:**

```php
// Child theme functions.php example
function skylinewp_child_enqueue_assets() {
    // Enqueue child theme styles
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        [], // No dependencies on parent
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // Enqueue child theme scripts if needed
    wp_enqueue_script(
        'child-bundle',
        get_stylesheet_directory_uri() . '/assets/js/bundle.js',
        ['jquery'],
        filemtime(get_stylesheet_directory() . '/assets/js/bundle.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'skylinewp_child_enqueue_assets');
```

---

## Theme Cleanup & Optimization

**File:** `/src/functions/setup/cleanup.php`

### Removed WordPress Features

**Emoji Support:**
- `print_emoji_detection_script` removed
- `print_emoji_styles` removed
- Both frontend and admin

**WordPress Head Tags:**
- RSD link
- WordPress generator tag
- RSS feed links
- Index rel link
- Windows Live Writer manifest
- Post relational links
- WordPress shortlink
- REST API discovery links
- oEmbed discovery links
- XML-RPC disabled

**Gutenberg:**
- Block editor disabled for all post types
- Block library CSS removed from frontend
- WooCommerce block styles removed
- Classic editor removed for pages (but kept for posts)

**Comments:**
- Comment support removed from all post types
- Comments closed on frontend
- Existing comments hidden
- Comments admin page removed and redirected
- Comments dashboard widget removed

**Other:**
- Post tags disabled
- Query strings removed from static resources (for better caching)
- Thumbnail width/height attributes removed from HTML
- Content width set to 1024px globally

### Implications for Child Theme

**You cannot rely on:**
- Default Gutenberg blocks or styling
- WordPress comment system
- oEmbed auto-embeds
- Default RSS feeds
- XML-RPC functionality

**You must manually include:**
- Any block editor features if needed
- Custom comment systems
- oEmbed handling if required

---

## Image Sizes

**File:** `/src/functions/setup/thumbnail.php`

### Default Image Sizes

**Added by Parent:**
- `mini` - 80x80 cropped

**Removed by Default:**
- `medium` (WordPress default)
- `1536x1536` (WordPress default)
- `2048x2048` (WordPress default)
- `medium_large` (WordPress default)
- `large` (WordPress default)

### Adding Custom Image Sizes (Child Theme)

```php
// In child theme functions.php
add_filter('skylinewp_custom_image_sizes', function($sizes) {
    $sizes[] = [
        'name' => 'product-card',
        'width' => 400,
        'height' => 400,
        'crop' => true,
    ];

    $sizes[] = [
        'name' => 'hero-banner',
        'width' => 1920,
        'height' => 600,
        'crop' => true,
    ];

    return $sizes;
});
```

### Removing Image Sizes (Child Theme)

```php
// Remove custom sizes
add_filter('skylinewp_remove_image_sizes', function($sizes) {
    return ['old-thumbnail', 'deprecated-size'];
});

// Restore default WordPress sizes
add_filter('skylinewp_remove_default_image_sizes', function($sizes) {
    // Return empty array to keep all default sizes
    return [];

    // Or selectively remove
    return ['medium', '1536x1536']; // Removes only these two
});
```

### Site Icon Support

The theme includes custom favicon handling:
- 32x32 for browsers
- 192x192 for Android
- 180x180 Apple Touch Icon

Configure via **Customizer → Site Identity → Site Icon**

---

## UIKit Integration

**File:** `/src/functions/uikit/uk_background_image.php`

### uk_background_image() Function

**Purpose:** Generates UIkit-compatible background image attributes with srcset for responsive images.

```php
function uk_background_image(
    int $image_id,                                              // Attachment ID
    array $sizes_map = [640 => 640, 960 => 960, 1200 => 1200], // [breakpoint => size]
    bool $webp = true,                                          // Use WebP
    bool $retina = false,                                       // Add 2x variants
    string $class = '',                                         // CSS classes
    ?string $sizes_attr = null,                                 // Custom sizes attribute
    bool $echo = false,                                         // Echo or return
    int $quality = 85                                           // Image quality
)
```

**Returns:** String of HTML attributes for UIkit lazy-loaded backgrounds

**Example Usage:**

```php
// Basic usage
<div <?php echo uk_background_image(
    image_id: 123,
    sizes_map: [640 => 640, 1024 => 1024, 1920 => 1920]
); ?>>
    Content here
</div>

// With retina and custom class
<div <?php echo uk_background_image(
    image_id: 123,
    sizes_map: [640 => 640, 960 => 960],
    retina: true,
    class: 'uk-background-cover uk-height-large',
    webp: true
); ?>>
    Content here
</div>
```

**Generated Output:**
```html
<div
    class="uk-background-cover uk-height-large"
    data-src="image-640.webp"
    data-srcset="image-640.webp 640w, image-960.webp 960w, image-1920.webp 1920w"
    sizes="(min-width: 1920px) 1920px, (min-width: 960px) 960px, (min-width: 640px) 640px, 100vw"
    uk-img
>
```

**Features:**
- Automatically generates srcset with width descriptors
- Optionally includes 2x retina variants
- Uses wpimage() internally for image processing
- Supports WebP conversion
- Auto-generates responsive sizes attribute
- UIkit lazy-loading ready

**When to Use:**
- Hero sections with background images
- Banner components
- Cards with image backgrounds
- Any scenario where you need UIkit's lazy-loaded backgrounds

---

## Build System

### Gulp Configuration

**Files:**
- `package.json` - Dependencies
- `gulpfile.js` - Main gulp file
- `gulpconfig.js` - Configuration
- `tailwind.config.js` - Tailwind settings

### Key Technologies

**CSS:**
- Tailwind CSS 3.x
- PostCSS with Autoprefixer
- SCSS support
- Nano CSS minification

**JavaScript:**
- Rollup bundler
- Babel transpilation
- CommonJS modules
- Multi-entry support

**Development:**
- BrowserSync for live reload
- Watch tasks for automatic compilation
- File change detection

### Tailwind Content Scanning

**File:** `tailwind.config.js`

```javascript
module.exports = {
    content: [
        "./src/**/*.php",
        "./src/assets/js/**/*.js",
        "./src/assets/scss/**/*.scss"
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
```

**Key Points:**
- Tailwind automatically scans PHP files for utility classes
- Scans both parent `/src/` and child theme directories
- JavaScript and SCSS files also scanned
- **DO NOT manually add static PHP classes to safelist**
- Only add dynamically-generated JavaScript classes to safelist

### Flowbite Integration

**Admin only** - Flowbite CSS/JS loaded in WordPress admin for custom ACF fields:

**File:** `/src/functions/acf/custom-fields/enqueue-admin-assets.php`

```php
wp_enqueue_style(
    'tailwindcss-admin',
    'https://cdn.jsdelivr.net/npm/tailwindcss@3.4.17/dist/tailwind.min.css'
);

wp_enqueue_style(
    'flowbite-admin',
    'https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css'
);

wp_enqueue_script(
    'flowbite-admin',
    'https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js'
);
```

**Not loaded on frontend** - Include manually if needed for child theme.

### NPM Scripts

```bash
npm start        # Runs gulp watch (default task)
npm run build    # Runs gulp build (one-time compilation)
npm run dist     # Runs gulp dist (production build)
```

**IMPORTANT:** The gulp watch process runs in background - **NEVER run `gulp build` manually** unless explicitly needed.

### Build Process Flow

1. **Watch task** monitors changes to:
   - `/src/assets/scss/**/*.scss`
   - `/src/assets/js/**/*.js`
   - `/src/**/*.php`

2. **On change:**
   - SCSS compiled to CSS
   - Tailwind processes utility classes
   - JavaScript bundled with Rollup
   - Assets minified for production
   - BrowserSync reloads browser

3. **Output locations:**
   - CSS: `/assets/css/` (in build directory)
   - JS: `/assets/js/bundle.js`
   - Compiled PHP: `/build/` directory

---

## File Organization

### Directory Structure

```
skylinewp-dev-parent/
├── src/                          # SOURCE OF TRUTH - All work happens here
│   ├── functions.php             # Main theme setup class
│   ├── sidebar.php               # Sidebar registration
│   ├── 404.php                   # Error template
│   │
│   ├── Classes/                  # PHP classes
│   │   └── SkylineWPhelpers.php  # Helper class (currently empty)
│   │
│   ├── functions/                # Modular functionality
│   │   │
│   │   ├── acf/                  # ACF Extended implementation
│   │   │   ├── flex.php          # Flexible content system
│   │   │   ├── navigation.php    # ACF-powered navigation walkers
│   │   │   │
│   │   │   ├── components/       # Component definitions (child theme)
│   │   │   ├── shared/           # Shared component definitions (child theme)
│   │   │   │
│   │   │   ├── custom-fields/    # Custom ACF field types
│   │   │   │   ├── UniqueID.php  # Unique ID field
│   │   │   │   ├── hidden.php    # Hidden field type
│   │   │   │   ├── popup-text.php # Popup text field
│   │   │   │   ├── example-usage.php # Usage examples
│   │   │   │   └── enqueue-admin-assets.php # Admin Tailwind/Flowbite
│   │   │   │
│   │   │   └── options/          # ACF options pages
│   │   │       ├── super-admin-page.php  # Super admin options
│   │   │       ├── options.php   # General options
│   │   │       └── footer.php    # Footer options
│   │   │
│   │   ├── helpers/              # Helper functions
│   │   │   ├── wrap_lines_with_div.php
│   │   │   └── convertToHtmlTag.php
│   │   │
│   │   ├── plugins/              # Plugin-like functionality
│   │   │   │
│   │   │   ├── wpimage/          # Image processing
│   │   │   │   └── wpimage.php   # Main wpimage() function
│   │   │   │
│   │   │   ├── acf/              # ACF extensions
│   │   │   │   ├── component-cloner.php
│   │   │   │   └── acf-search-indexer/  # Search functionality
│   │   │   │       ├── class-skylinewp-search-manager.php
│   │   │   │       └── template-parts/
│   │   │   │
│   │   │   ├── admin/            # Admin enhancements
│   │   │   │   ├── update-and-view.php
│   │   │   │   ├── super-admin-cap.php
│   │   │   │   ├── image-replace-with-new.php
│   │   │   │   ├── drag-drop-order.php
│   │   │   │   ├── page-filter-advanced.php
│   │   │   │   └── markerio.php
│   │   │   │
│   │   │   ├── phpmailer/        # Email configuration
│   │   │   │   └── phpmailer.php
│   │   │   │
│   │   │   ├── theme-translations/ # Translation system
│   │   │   │   └── admin/
│   │   │   │       └── views/
│   │   │   │
│   │   │   ├── ui-kit/           # UIKit utilities
│   │   │   │   └── uk_replace.php
│   │   │   │
│   │   │   └── wp_cli/           # WP-CLI commands
│   │   │
│   │   ├── setup/                # Theme setup
│   │   │   ├── thumbnail.php     # Image size registration
│   │   │   ├── enqueue.php       # Asset enqueuing
│   │   │   ├── excerpt.php       # Excerpt support
│   │   │   ├── theme-activate.php # Theme activation
│   │   │   ├── cleanup.php       # WordPress cleanup
│   │   │   └── disallow-edits.php # File editing disable
│   │   │
│   │   ├── shortcodes/           # Shortcode definitions
│   │   │   └── options-page.php
│   │   │
│   │   ├── uikit/                # UIKit helpers
│   │   │   └── uk_background_image.php
│   │   │
│   │   └── woocommerce/          # WooCommerce integration
│   │
│   ├── assets/                   # Source assets
│   │   ├── fonts/
│   │   ├── icons/
│   │   ├── images/
│   │   │   └── theme/
│   │   ├── js/
│   │   │   ├── admin/            # Admin JavaScript
│   │   │   │   └── plugins/
│   │   │   ├── components/       # JS components
│   │   │   └── plugins/          # JS plugins
│   │   └── scss/                 # Sass stylesheets
│   │       ├── abstracts/
│   │       ├── builds/
│   │       │   └── components/
│   │       ├── styles/
│   │       └── tinymces/
│   │
│   └── vendor/                   # Composer dependencies
│       ├── rosell-dk/webp-convert # WebP conversion
│       ├── scssphp/scssphp      # SCSS compiler
│       ├── league/commonmark     # Markdown parser
│       └── phpmailer/phpmailer   # Email handling
│
├── build/                        # NEVER TOUCH - Auto-generated by Gulp
│   ├── *.php                     # Compiled templates
│   ├── assets/
│   └── style.css
│
├── package.json                  # NPM dependencies
├── gulpfile.js                   # Gulp tasks
├── gulpconfig.js                 # Gulp configuration
├── tailwind.config.js            # Tailwind configuration
└── composer.json                 # Composer dependencies
```

### Important Paths

**Parent Theme Root:**
```php
get_template_directory()          // /path/to/skylinewp-dev-parent/src
get_template_directory_uri()      // https://site.com/wp-content/themes/skylinewp-dev-parent/src
```

**Child Theme Root:**
```php
get_stylesheet_directory()        // /path/to/skylinewp-dev-child/src
get_stylesheet_directory_uri()    // https://site.com/wp-content/themes/skylinewp-dev-child/src
```

**Uploads Directory:**
```php
wp_upload_dir()['basedir']        // /path/to/uploads
wp_upload_dir()['baseurl']        // https://site.com/wp-content/uploads
```

### File Loading Order

1. **Parent `functions.php`** loads immediately
2. **Non-ACF functions** from `/functions/` loaded immediately
3. **Composer autoloader** loaded on `init` action (priority 0)
4. **ACF-dependent files** loaded on `init` action (priority 1)
5. **Child theme `functions.php`** loads after parent

### Which Files Load When

**Immediate Loading (Before `init`):**
- All files in `/functions/` except:
  - Files in `/components/` folder
  - Files in `/template-parts/` folder
  - Files containing ACF function calls
  - Files in `/acf/` directory
  - `/theme-translations/` files
  - `super-admin-page.php`
  - `super-admin-cap.php`

**Deferred Loading (On `init` priority 1):**
- All `/functions/acf/` files
- Any file using `get_field()`, `acf_add_options_page()`, or Extended ACF classes
- Theme translations system files
- Super admin ACF options pages

---

## Summary & Best Practices

### Critical Rules for Child Theme Development

1. **ALWAYS use `wpimage()` for all image display** - Never use native WordPress image functions
2. **ALWAYS use ACF Extended** - Never use native ACF functions like `acf_add_local_field_group()`
3. **ALWAYS check Context7 MCP** before implementing ACF fields
4. **NEVER edit files in `/build/` directory** - They're auto-generated
5. **NEVER run `gulp build`** - The watch task handles compilation
6. **ALWAYS add reference classes** (`rfs-ref-*`) to all HTML elements
7. **ALWAYS check `debug.log`** after making changes
8. **NEVER assume WooCommerce assets are loaded** - Explicitly enqueue what you need
9. **ALWAYS work in `/src/` directory** for all theme files
10. **ALWAYS use Tailwind colors** from config - Never hardcode colors

### Parent Theme Capabilities

**✅ Provides:**
- Image processing with WebP conversion and retina support
- ACF Flexible Content system with caching
- Custom ACF field types (UniqueID, Hidden, Popup Text)
- WordPress cleanup and optimization
- Asset management with child theme detection
- UIKit background image helper
- Modular architecture with hook points

**❌ Does NOT Provide:**
- Default WooCommerce styles/scripts (dequeued)
- Gutenberg block support (disabled)
- Comment system (removed)
- Default WordPress image sizes (removed)
- Frontend Flowbite/UIKit libraries (admin only)

### Getting Help

**For ACF Extended:**
1. Use Context7 MCP: `mcp__context7__get-library-docs("/vinkla/extended-acf")`
2. Check official docs: https://github.com/vinkla/extended-acf

**For wpimage():**
- Check parent theme: `/functions/plugins/wpimage/wpimage.php`
- Always pass attachment ID when possible (not URL)
- Use named arguments for clarity

**For Hooks:**
- Search parent theme for `apply_filters()` and `do_action()`
- Check this documentation for available filters
- Test hooks with low priority to ensure they run

---

**End of Documentation**

*This document is comprehensive but the theme is modular - not all features may be active. Check specific implementation files for exact behavior in your environment.*
