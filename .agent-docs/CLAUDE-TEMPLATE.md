# WordPress Theme Knowledge Base

**Last Updated**: [Auto-generated on each scan]  
**Parent Theme**: skylinewp-dev-parent  
**Child Theme**: [Auto-detected]

---

## Theme Architecture Overview

### File Structure
```
Parent Theme: /var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/
├── src/                    # Source files (READ ONLY)
├── build/                  # Compiled output (NEVER TOUCH)
├── gulpfile.js            # Build configuration
├── tailwind.config.js     # Tailwind configuration
└── package.json           # Dependencies

Child Theme: [Current location]
├── CLAUDE.md              # This file
├── .claude/               # Documentation
│   └── .tasks/           # Task-specific docs
└── src/                   # Working directory
```

### Build Process
- **Source**: Work in `src/` folder only
- **Compilation**: Automatic via Gulp watch (always running)
- **Output**: `build/` folder (auto-generated, never modify)
- **SCSS → CSS**: Compiled automatically on save
- **JS**: [Document bundling process if applicable]

---

## Custom Functions Reference

### Image Functions

#### `wpimage()`
**Location**: [Auto-detected]  
**Purpose**: Standard function for displaying images  
**Usage**:
```php
// Signature: [Document exact signature]
wpimage($attachment_id, $size, $attr);

// Example:
wpimage(get_post_thumbnail_id(), 'large', ['class' => 'w-full h-auto']);
```

**When to use**: ALWAYS use this instead of `wp_get_attachment_image()`

### [Other Custom Functions - Auto-documented]

[Agent will populate this section with all discovered functions]

---

## Hooks & Filters

### Custom Actions

#### `action_name`
**Location**: [File path]  
**Purpose**: [Description]  
**Parameters**: [List parameters]  
**Usage**:
```php
add_action('action_name', 'callback_function', 10, 1);
```

### Custom Filters

#### `filter_name`
**Location**: [File path]  
**Purpose**: [Description]  
**Parameters**: [List parameters]  
**Return**: [Expected return type]  
**Usage**:
```php
add_filter('filter_name', 'callback_function', 10, 1);
```

[Agent will populate with all discovered hooks and filters]

---

## ACF Extended Implementation

### Setup
- **Package**: https://github.com/vinkla/extended-acf
- **Method**: Code-based (not admin/JSON)
- **Location**: [Auto-detected ACF definition files]

### Component Patterns

[Agent will document discovered patterns]

Example structure:
```php
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Image;
use Extended\ACF\Location;

register_extended_field_group([
    'title' => 'Component Name',
    'fields' => [
        Text::make('Heading'),
        Image::make('Background Image'),
    ],
    'location' => [
        Location::if('post_type', 'page'),
    ],
]);
```

### Existing Components

[Agent will list all discovered ACF components with their fields]

---

## Styling Framework

### Tailwind CSS

**Configuration**: [Path to tailwind.config.js]

**Custom Theme Colors**:
[Auto-document from config]

**Custom Spacing**:
[Auto-document from config]

**Custom Breakpoints**:
```
sm: [value]
md: [value]
lg: [value]
xl: [value]
2xl: [value]
```

**Commonly Used Utilities**:
[Agent will document frequently used patterns from existing code]

### Flowbite Integration

**Installation**: [Auto-detected: npm/CDN/custom]  
**Version**: [Auto-detected]

**Commonly Used Components**:
[Agent will document Flowbite components found in theme]

---

## File Organization

### Include Files Structure
```
src/
├── inc/
│   ├── [List discovered files]
│   └── [Document purpose of each]
├── templates/
│   └── [List template files]
└── assets/
    └── scss/
        └── [List SCSS structure]
```

### Naming Conventions
[Agent will identify patterns like: component-name.php, feature-functions.php, etc.]

---

## WordPress Customizations

### Custom Post Types
[Auto-document registered CPTs]

### Custom Taxonomies
[Auto-document registered taxonomies]

### Custom Template Hierarchy
[Document custom template files and their usage]

---

## WooCommerce Integration
(If applicable)

### Custom Hooks Used
[Document WooCommerce hooks utilized]

### Product Modifications
[Document custom product fields, meta, display modifications]

---

## Performance Considerations

### Caching Strategy
[Document transients, object cache usage]

### Query Optimization
[Document common query patterns]

---

## Development Standards

### Code Style
- PHP: [Identified standards - WordPress Coding Standards]
- SCSS: [Identified patterns]
- JS: [If applicable]

### Comments & Documentation
[Example of documentation style found in parent theme]

### Function Naming
[Identify prefix patterns, e.g., `theme_prefix_function_name`]

---

## Common Patterns

### Displaying Images
```php
// ALWAYS use wpimage()
wpimage(get_post_thumbnail_id(), 'full', [
    'class' => 'rounded-lg shadow-lg'
]);
```

### Creating Components with ACF
[Document standard pattern from parent theme]

### AJAX Handlers
[If patterns exist, document structure]

### Responsive Implementations
[Document mobile-first patterns, breakpoint usage]

---

## Testing Reference

### Debug Log Location
`/wp-content/debug.log`

### Common Debug Functions
```php
error_log(print_r($variable, true)); // Log variables
error_log('Checkpoint reached'); // Simple logging
```

### Playwright Test Viewports
- Mobile: 375x667
- Tablet: 768x1024
- Desktop: 1920x1080

---

## Database Schema Extensions
(If applicable)

### Custom Tables
[Document custom tables if any]

### Custom Meta Keys
[Document commonly used meta keys]

---

## Dependencies

### npm Packages
[Auto-list from package.json]

### PHP Libraries
[If Composer is used, auto-list dependencies]

---

## Known Issues & Workarounds

[Document any quirks, known issues, or workarounds discovered in parent theme]

---

## Quick Reference

### Most Used Functions
1. `wpimage()` - Image display
2. [Other frequently used functions]

### Most Used Hooks
1. [Most common hook]
2. [Second most common]

### File Modification Frequency
[Track which files are commonly modified in child theme]

---

## Notes

- Parent theme is READ-ONLY - never modify
- Always work in child theme `src/` folder
- Gulp watch runs continuously - changes auto-compile
- Test after each completed task
- Document everything in `.claude/.tasks/`

---

**This file is automatically updated on each agent startup to reflect the current theme state.**
