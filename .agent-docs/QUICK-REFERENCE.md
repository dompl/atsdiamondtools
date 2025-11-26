# WordPress Development Agent - Quick Reference

## Setup Instructions

1. **Copy agent to child theme folder**:
   ```bash
   cd /path/to/child-theme
   # Copy wordpress-theme-agent.json to this folder
   # Or configure Claude Code to use this agent
   ```

2. **First run - Agent will automatically**:
   - Scan parent and child theme
   - Document all functions, hooks, filters
   - Create CLAUDE.md with full knowledge base
   - Set up .claude/.tasks/ folder structure

3. **Verify gulp watch is running**:
   ```bash
   ps aux | grep gulp
   ```

## Using the Agent

### Starting a New Task

Simply describe what you need:
```
Create a custom product filter for WooCommerce products that filters by color and size
```

The agent will:
1. Understand the task
2. Ask clarifying questions if needed
3. Plan the implementation
4. Execute the work
5. Ask for testing URL
6. Run comprehensive tests
7. Provide preview URL
8. Create documentation

### Specifying Files/Filters

If you want work done in specific files:
```
Add a new product badge using the 'woocommerce_before_shop_loop_item_title' hook in src/inc/woocommerce-customizations.php
```

Agent will use EXACTLY those specifications.

### Database Changes

If task requires DB changes, agent will:
1. Stop and inform you
2. Provide WP-CLI commands
3. Wait for your confirmation
4. Continue after confirmation

Example:
```
⚠️ This task requires database changes:
- Create custom product_badge taxonomy
- Command: wp taxonomy register product_badge --post-type=product

Please run this command and confirm when complete.
```

### Testing

Agent tests automatically after task completion:
- Console errors
- Functionality verification
- Responsive design (mobile/tablet/desktop)
- Debug.log check
- Auto-fixes any issues found

You'll be asked once:
```
What URL should I test with Playwright?
```
Provide: `https://yoursite.com`

### Git Commits

Agent will NOT commit until you confirm:
```
Task looks perfect! Please commit this.
```

Then it uses your existing git-committer agent.

## Common Commands

### View Current Theme Knowledge
```
Show me what functions you know about from the parent theme
```

### Refresh Knowledge Base
```
Please rescan the parent theme and update CLAUDE.md
```

### Check for Errors
```
Check the debug.log for any errors from the last hour
```

### Test Specific Feature
```
Test the product filter I just worked on at https://example.com/shop
```

### Document Existing Feature
```
Document the existing testimonials component in .claude/.tasks/testimonials-component/
```

## File Structure

```
child-theme/
├── CLAUDE.md                          # Main knowledge base
├── .claude/
│   └── .tasks/
│       ├── product-filter/
│       │   ├── README.md
│       │   ├── CHANGES.md
│       │   ├── DATABASE.md
│       │   └── screenshots/
│       │       ├── desktop.png
│       │       ├── mobile.png
│       │       └── tablet.png
│       └── custom-badge/
│           └── README.md
└── src/                               # Your working directory
    ├── inc/
    ├── templates/
    └── assets/
```

## Key Reminders

### What Agent WILL Do
✅ Work in src/ folder only
✅ Use parent theme functions (wpimage, etc.)
✅ Apply Tailwind CSS classes
✅ Follow ACF Extended patterns
✅ Test comprehensively
✅ Document everything
✅ Auto-fix errors
✅ Provide preview URLs

### What Agent WON'T Do
❌ Modify parent theme
❌ Work in build/ folder
❌ Touch compiled CSS files
❌ Enqueue assets manually
❌ Commit without confirmation
❌ Hallucinate functions
❌ Skip testing
❌ Work outside src/

## Examples

### Simple Task
```
You: Add a custom header to single product pages

Agent:
- Plans implementation
- Creates src/templates/single-product-header.php
- Uses wpimage() for product images
- Applies Tailwind classes
- Tests at provided URL
- Documents in .claude/.tasks/product-header/
- Provides preview URL
```

### Complex Task with DB Changes
```
You: Create a custom reviews system with ratings

Agent:
- Plans custom post type + meta
- Asks you to run WP-CLI commands
- Waits for confirmation
- Implements functionality
- Tests thoroughly
- Documents everything including DB changes
- Provides preview URL
```

### Bug Fix
```
You: Fix the mobile menu not closing

Agent:
- Investigates issue
- Checks debug.log
- Fixes the bug
- Tests on mobile viewport
- Verifies fix works
- Documents the fix
- Provides preview URL
```

## Troubleshooting

### Agent seems confused about parent theme
```
Please rescan the parent theme and update your knowledge base
```

### Tests failing
Agent will automatically:
1. Identify the issue
2. Attempt fix
3. Re-test
4. Repeat until passing

### Need to see what changed
```
Show me exactly what files were modified in the last task
```

Check: `.claude/.tasks/[task-name]/README.md`

### Preview URL not working
Agent constructs specific URLs. If wrong:
```
The preview should be https://example.com/products, not /shop
```

## Tips for Best Results

1. **Be specific about requirements**:
   - Good: "Add AJAX filter for products by color, update on change, show loading state"
   - Okay: "Add product filter"

2. **Specify files when you know them**:
   ```
   Add this to src/inc/ajax-handlers.php using the wp_ajax_filter_products hook
   ```

3. **Provide context for complex tasks**:
   ```
   Create testimonials carousel using the existing testimonial post type, 
   should match the style of the team members section
   ```

4. **Confirm testing URL once**:
   Agent remembers for the session

5. **Review documentation**:
   Check `.claude/.tasks/` folders for comprehensive details

## Getting Help

Agent is designed to:
- Ask questions when unclear
- Explain what it's doing
- Show testing results
- Provide clear documentation
- Be transparent about limitations

Just communicate naturally and the agent will guide the conversation!
