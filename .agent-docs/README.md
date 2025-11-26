# WordPress Theme Development Agent

A specialized Claude Code agent for WordPress parent-child theme development with automated testing, comprehensive documentation, and strict workflow enforcement.

## Overview

This agent is designed to work with your WordPress theme setup where:
- Parent theme contains all core functions (read-only)
- Child theme is where development happens
- Gulp watch runs continuously for auto-compilation
- SCSS is the source, never compiled CSS
- Extended ACF is used for components
- Tailwind CSS + Flowbite for styling
- Playwright for automated testing

## What Makes This Agent Special

### 🧠 Self-Aware
- Scans parent theme on every startup
- Documents all custom functions, hooks, filters
- Learns ACF patterns and Tailwind configuration
- Creates comprehensive CLAUDE.md knowledge base
- Never hallucinates - verifies everything exists

### 🛡️ Safety First
- NEVER modifies parent theme (read-only)
- Works ONLY in child theme `src/` folder
- Never touches compiled `build/` files
- Respects your specified files/filters exactly
- Won't commit without your 100% confirmation

### 🧪 Testing Expert
- Playwright MCP integration (headless)
- Tests console errors, functionality, responsive design
- Auto-checks debug.log for PHP errors
- Auto-fixes issues and re-tests
- Captures screenshots for documentation

### 📚 Documentation Obsessed
- Creates task folders with comprehensive docs
- Documents all functions used
- Tracks all changes made
- Saves test screenshots
- Maintains organized `.claude/.tasks/` structure

## Files Included

```
wordpress-theme-agent/
├── wordpress-theme-agent.md       # Full agent instructions
├── wordpress-theme-agent.json     # Agent configuration for Claude Code
├── init-wordpress-agent.sh        # Environment initialization script
├── QUICK-REFERENCE.md             # User guide and examples
├── CLAUDE-TEMPLATE.md             # Template for knowledge base
├── AGENT-CHECKLIST.md             # Self-check compliance list
└── README.md                      # This file
```

## Installation

### 1. Prepare Your Environment

Ensure you have:
- Child theme with `src/` folder structure
- Parent theme at: `/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src`
- Gulp watch running in the background
- WP-CLI installed and accessible
- WordPress debug mode enabled

### 2. Deploy Agent Files

```bash
# Navigate to your child theme root
cd /path/to/your/child-theme

# Copy the agent configuration
cp /path/to/wordpress-theme-agent.json ./
# or integrate into your Claude Code configuration

# Optionally, copy reference files for your use
cp /path/to/QUICK-REFERENCE.md ./
cp /path/to/AGENT-CHECKLIST.md ./
```

### 3. Initialize Agent

When you first start Claude Code with this agent, it will automatically:
1. Determine all paths dynamically
2. Scan parent and child theme
3. Create CLAUDE.md knowledge base
4. Set up `.claude/.tasks/` structure
5. Verify environment tools

You can also run the initialization script manually:
```bash
bash init-wordpress-agent.sh
```

## Using the Agent

### Basic Workflow

1. **Start Claude Code** in your child theme folder
2. **Agent auto-initializes** and scans themes
3. **Describe your task**: "Create a custom product filter"
4. **Agent implements** following all rules
5. **Agent asks for test URL**: Provide your dev site URL
6. **Agent tests comprehensively** with Playwright
7. **Agent documents everything** in `.claude/.tasks/`
8. **Agent provides preview URL** for you to review
9. **You confirm if satisfied**: "Task is complete, commit it"
10. **Agent uses git-committer** (your existing agent)

### Example Tasks

#### Simple Task
```
Add a custom copyright message to the footer using the 'wp_footer' hook
```

#### Complex Task
```
Create a testimonials carousel:
- Use the testimonials custom post type
- Display featured image using wpimage
- 3 items per slide on desktop, 1 on mobile
- Auto-rotate every 5 seconds
- Use Flowbite carousel component
- Style to match team section
```

#### Specific Implementation
```
Add AJAX product filtering in src/inc/ajax-handlers.php using the 
'wp_ajax_filter_products' hook. Filter by category and price range.
Update results without page reload.
```

### When Database Changes Needed

Agent will stop and inform you:
```
⚠️ This task requires database changes:
- Register custom taxonomy 'product_badge'
- Command: wp taxonomy register product_badge --post-type=product

Please run this command and confirm when complete.
```

Run the command, then tell agent: "Database updated, continue"

## Agent Capabilities

### ✅ Will Do

- Work exclusively in child theme `src/`
- Use parent theme functions (wpimage, etc.)
- Apply Tailwind CSS classes
- Follow ACF Extended patterns
- Use appropriate hooks and filters
- Test comprehensively with Playwright
- Check debug.log for errors
- Auto-fix discovered issues
- Document everything thoroughly
- Provide specific preview URLs
- Ultra-think before acting

### ❌ Won't Do

- Modify parent theme files
- Work in `build/` folder
- Touch compiled CSS
- Manually enqueue assets
- Run gulp commands
- Hallucinate functions
- Skip testing
- Skip documentation
- Commit without confirmation
- Work outside `src/` folder

## File Structure Created

```
child-theme/
├── CLAUDE.md                      # Knowledge base (auto-updated)
├── wordpress-theme-agent.json     # Agent config
├── QUICK-REFERENCE.md             # Your reference guide
├── .claude/
│   └── .tasks/
│       ├── product-filter/
│       │   ├── README.md          # Comprehensive task doc
│       │   ├── CHANGES.md         # Detailed changelog
│       │   ├── DATABASE.md        # DB changes if any
│       │   └── screenshots/
│       │       ├── desktop.png
│       │       ├── mobile.png
│       │       └── tablet.png
│       └── testimonials-carousel/
│           ├── README.md
│           └── screenshots/
└── src/                           # Your working directory
    ├── inc/
    ├── templates/
    └── assets/
```

## Testing Details

### What Agent Tests

1. **Console Errors**: JavaScript errors and warnings
2. **Functionality**: Feature works as specified
3. **Visual Regression**: Layout and styling correct
4. **Responsive Design**:
   - Mobile: 375x667
   - Tablet: 768x1024
   - Desktop: 1920x1080
5. **PHP Errors**: Checks debug.log
6. **Performance**: Basic performance checks

### Test Process

1. Agent asks for URL once per session
2. Sets up Playwright MCP (headless)
3. Runs comprehensive test suite
4. Captures screenshots
5. If errors found: auto-fix and re-test
6. Provides results in task documentation

## Documentation Structure

### CLAUDE.md (Knowledge Base)

Updated on every agent startup:
- Complete theme architecture
- All parent theme functions
- All hooks and filters
- ACF patterns
- Tailwind/Flowbite setup
- File organization
- Common patterns

### Task Documentation

For each task in `.claude/.tasks/[task-name]/`:

**README.md**:
- Task overview
- Files modified/created
- Parent functions used
- Hooks/filters applied
- Testing results
- Preview URL
- Special notes

**CHANGES.md** (if significant):
- Detailed changelog
- Before/after comparisons

**DATABASE.md** (if applicable):
- All DB changes
- Commands used
- Reasons

**screenshots/**:
- Visual test results
- All viewports

## Troubleshooting

### Agent seems confused
```
Please rescan the parent theme and update CLAUDE.md
```

### Tests keep failing
Agent auto-fixes issues, but if stuck:
```
Show me the test errors you're seeing
```

### Wrong files modified
Review `.claude/.tasks/[task-name]/README.md` to see what was changed

### Need to undo changes
Git history is your friend - agent documents everything but doesn't commit without permission

### Preview URL wrong
Tell agent the correct URL structure:
```
Preview should be https://example.com/products, not /shop
```

## Advanced Usage

### Refresh Knowledge Base
```
Scan parent theme and update CLAUDE.md with latest changes
```

### Document Existing Code
```
Document the existing header component in .claude/.tasks/header-component/
```

### Review Past Work
```
Show me all tasks completed this month
```

### Check Theme Health
```
Scan debug.log for errors from the past 24 hours
```

## Best Practices

### 1. Be Specific
Good: "Add AJAX filter for products by category, update on select change"  
Better: "Add AJAX filter in src/inc/ajax-handlers.php using wp_ajax_filter_products"

### 2. Provide Context
"Create testimonials carousel using existing testimonial CPT, match team section style"

### 3. Review Documentation
Check `.claude/.tasks/` folders for comprehensive details

### 4. Test Thoroughly
Agent tests automatically, but manually verify critical functionality

### 5. Confirm Before Commit
Always review changes before confirming task is 100% done

## Integration with Existing Workflow

### Works With:
- Your existing gulp setup (doesn't interfere)
- Your git-committer agent (used after confirmation)
- Your development server
- Your WordPress setup
- Your existing theme structure

### Enhances:
- Code quality (uses parent functions consistently)
- Testing coverage (automated Playwright tests)
- Documentation (comprehensive task docs)
- Workflow speed (auto-fixes issues)
- Knowledge preservation (CLAUDE.md)

## Support & Feedback

### Common Questions

**Q: Will agent modify my parent theme?**  
A: Never. Parent theme is read-only reference.

**Q: Can I work in multiple child themes?**  
A: Yes, agent determines paths dynamically per project.

**Q: What if I need to add new functions to parent?**  
A: Add them manually, agent will discover on next startup.

**Q: Can I disable testing for quick tasks?**  
A: Testing only runs after complete tasks, not every save.

**Q: Will agent commit automatically?**  
A: Never. Waits for your explicit 100% confirmation.

## Updates & Maintenance

The agent:
- Scans theme on every startup (always current)
- Updates CLAUDE.md automatically
- Adapts to your theme changes
- Learns new functions you add
- Follows your patterns

No maintenance needed - agent stays synchronized with your codebase.

## Quick Start Checklist

- [ ] Agent files copied to child theme
- [ ] Gulp watch is running
- [ ] WP-CLI is available
- [ ] Debug mode enabled in WordPress
- [ ] Start Claude Code in child theme folder
- [ ] Agent auto-initializes and scans
- [ ] CLAUDE.md created
- [ ] `.claude/.tasks/` structure created
- [ ] Ready to receive tasks!

---

## Get Started

```bash
cd /path/to/child-theme
# Start Claude Code with this agent configuration
# Agent will handle everything else!
```

**Your WordPress development just got supercharged! 🚀**

---

For detailed usage instructions, see **QUICK-REFERENCE.md**  
For agent behavior details, see **wordpress-theme-agent.md**  
For self-check compliance, see **AGENT-CHECKLIST.md**
