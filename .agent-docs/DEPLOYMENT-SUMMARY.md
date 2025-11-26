# WordPress Development Agent - Deployment Summary

## 🎯 What You're Getting

A fully-featured Claude Code agent that:

1. **Knows your theme inside-out** - Scans parent theme on every startup
2. **Never breaks rules** - Works only in child `src/`, never touches parent
3. **Tests everything** - Playwright automation with headless browser
4. **Documents obsessively** - Comprehensive task docs with screenshots
5. **Uses your functions** - Always applies parent theme's wpimage, hooks, filters
6. **Auto-fixes issues** - Finds and fixes errors automatically
7. **Provides preview URLs** - Direct links to test your work
8. **Waits for approval** - Never commits without your explicit confirmation

## 📦 Package Contents

### Core Files

| File | Purpose | Location |
|------|---------|----------|
| `wordpress-theme-agent.md` | Complete agent instructions | Agent brain |
| `wordpress-theme-agent.json` | Agent configuration | Claude Code config |
| `init-wordpress-agent.sh` | Environment setup script | Optional helper |
| `README.md` | Complete documentation | Reference |
| `QUICK-REFERENCE.md` | Usage guide & examples | Your quick guide |
| `AGENT-CHECKLIST.md` | Self-check compliance | Agent reference |
| `CLAUDE-TEMPLATE.md` | Knowledge base template | Auto-generated |
| `SAMPLE-TASK-README.md` | Example output | See what to expect |

### Files the Agent Creates

| File | Purpose | Location |
|------|---------|----------|
| `CLAUDE.md` | Theme knowledge base | Child theme root |
| `.claude/.tasks/[name]/README.md` | Task documentation | Per task |
| `.claude/.tasks/[name]/screenshots/` | Test screenshots | Per task |
| `.claude/.tasks/[name]/DATABASE.md` | DB changes (if any) | Per task |

## 🚀 Quick Deployment

### Option 1: Direct Integration (Recommended)

```bash
# 1. Copy agent instructions to your Claude Code configuration
# 2. Navigate to child theme
cd /path/to/your-child-theme

# 3. Start Claude Code
# Agent will auto-initialize!
```

### Option 2: Manual Setup

```bash
# 1. Copy files to child theme
cd /path/to/your-child-theme
cp /path/to/wordpress-theme-agent.json ./
cp /path/to/QUICK-REFERENCE.md ./

# 2. Run initialization
bash /path/to/init-wordpress-agent.sh

# 3. Start Claude Code with agent
# Agent is ready!
```

## ✅ Pre-Deployment Checklist

Before using the agent, verify:

- [ ] Gulp watch is running in parent theme
- [ ] WP-CLI is installed and accessible
- [ ] WordPress debug mode is enabled
- [ ] You have a development URL for testing
- [ ] Child theme has `src/` folder structure
- [ ] Parent theme path is correct: `/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src`

## 🎬 First Use

### 1. Agent Starts and Initializes

When you first start the agent, it will:
```
📍 Scanning environment...
✓ Found parent theme
✓ Found child theme  
✓ WP-CLI available
✓ Scanning parent theme functions...
✓ Found 47 custom functions
✓ Found wpimage function
✓ Scanning ACF patterns...
✓ Creating CLAUDE.md...
✓ Ready to work!
```

### 2. Give Your First Task

Try something simple:
```
Create a simple "Hello World" component in src/templates/hello-world.php 
that uses Tailwind classes
```

### 3. Agent Works

```
Understanding task...
Planning implementation...
Creating src/templates/hello-world.php...
Complete! What URL should I test with Playwright?
```

### 4. Provide Test URL

```
https://rfsdev.co.uk
```

### 5. Agent Tests

```
Running Playwright tests...
✓ No console errors
✓ Component renders correctly
✓ Mobile responsive verified
✓ Desktop layout verified
✓ Debug.log clean

Preview: https://rfsdev.co.uk/hello-world

Documentation created in .claude/.tasks/hello-world-component/
```

### 6. Review and Approve

Check the work, then:
```
Perfect! Please commit this.
```

Agent will use your git-committer agent.

## 🎯 Key Features Explained

### 1. Auto-Scanning on Startup

**Every time** the agent starts:
- Scans entire parent theme
- Documents all functions (wpimage, etc.)
- Maps hooks and filters
- Identifies ACF patterns
- Updates CLAUDE.md

**Why**: Always has latest knowledge

### 2. Strict Working Directory

Agent **ONLY** works in:
```
your-child-theme/src/
```

**Never**:
- Parent theme (read-only reference)
- Build folder (auto-generated)
- Any other location

**Why**: Safety and consistency

### 3. Parent Function Usage

Agent **ALWAYS** uses parent functions:
- `wpimage()` for images
- Custom hooks and filters
- Utility functions
- ACF patterns

**Why**: Consistency across theme

### 4. Comprehensive Testing

After each task:
- Playwright runs headless
- Tests all viewports
- Checks console errors
- Checks debug.log
- Captures screenshots
- Auto-fixes issues

**Why**: Quality assurance

### 5. Documentation First

For every task:
- Creates `.claude/.tasks/[task-name]/`
- Writes comprehensive README.md
- Lists all changes
- Documents functions used
- Saves test screenshots
- Includes preview URL

**Why**: Knowledge preservation

### 6. Database Safety

If DB changes needed:
- **STOPS** immediately
- Informs you of changes
- Provides exact commands
- **WAITS** for confirmation
- Documents in DATABASE.md

**Why**: Prevent accidental changes

### 7. Git Commit Control

Agent **NEVER** commits until:
- You confirm task is 100% done
- You explicitly say "commit this"
- Uses your existing git-committer

**Why**: You stay in control

## 💡 Usage Tips

### Be Specific

❌ Bad: "Add a filter"
✅ Good: "Add AJAX product filter by category and price"
✅ Better: "Add AJAX filter in src/inc/ajax-handlers.php using wp_ajax_filter_products"

### Provide Context

✅ "Create testimonials carousel using existing testimonial CPT, style like team section"

### Specify Files When Known

✅ "Add this function to src/inc/woocommerce-functions.php"

### Use Parent Functions

✅ "Make sure to use wpimage for the featured image"

## 🐛 Troubleshooting

### Agent seems confused about theme
```
Rescan the parent theme and update CLAUDE.md
```

### Wrong files were modified
Check documentation:
```
Show me what files were modified in .claude/.tasks/[task-name]/
```

### Tests failing repeatedly
Agent auto-fixes, but if stuck:
```
Show me the exact error you're encountering
```

### Can't find CLAUDE.md
Should be in child theme root. If missing:
```
Create CLAUDE.md with current theme knowledge
```

## 📊 Expected Workflow Speed

| Task Complexity | Estimated Time |
|----------------|----------------|
| Simple component | 2-3 minutes |
| AJAX functionality | 5-10 minutes |
| Complex feature | 15-30 minutes |
| Testing phase | 2-5 minutes |
| Documentation | Auto-generated |

**Most time spent**: Implementation and testing  
**Least time spent**: Documentation (automatic)

## 🎓 Learning Curve

### Day 1: Getting Started
- Install agent
- Run first simple task
- See how testing works
- Review documentation

### Day 2-3: Regular Use
- Comfortable with task format
- Understanding documentation structure
- Using parent functions naturally

### Week 1: Expert Level
- Specifying exact files
- Leveraging parent functions
- Minimal review needed
- High confidence in output

## 📈 Expected Benefits

### Immediate
- ✅ Consistent use of parent functions
- ✅ Automated testing
- ✅ Comprehensive documentation
- ✅ Fewer bugs

### Short Term (1-2 weeks)
- ✅ Faster development
- ✅ Better code quality
- ✅ Complete project knowledge
- ✅ Easy onboarding

### Long Term (1+ months)
- ✅ Robust knowledge base
- ✅ Pattern consistency
- ✅ Maintainable codebase
- ✅ Reduced tech debt

## 🔐 Safety Guarantees

1. **Parent theme protected** - Read-only, never modified
2. **Git controlled** - No commits without approval
3. **Tested everything** - Automated testing required
4. **Documented everything** - Complete audit trail
5. **Database safe** - Requires confirmation
6. **Verified functions** - Never hallucinates

## 🎉 You're Ready!

The agent is designed to be:
- **Smart**: Learns your theme
- **Safe**: Never breaks things
- **Fast**: Automated testing
- **Thorough**: Comprehensive docs
- **Reliable**: Consistent output
- **Helpful**: Clear communication

## Next Steps

1. **Deploy** the agent to your child theme
2. **Start** with a simple task
3. **Review** the output and documentation
4. **Approve** when satisfied
5. **Commit** with confidence

## Support

All documentation is in:
- `README.md` - Complete guide
- `QUICK-REFERENCE.md` - Quick examples
- `AGENT-CHECKLIST.md` - Agent behavior
- `SAMPLE-TASK-README.md` - Expected output

## Final Notes

This agent is specifically built for **your** workflow:
- Your parent-child theme setup
- Your gulp process
- Your Extended ACF patterns
- Your Tailwind/Flowbite setup
- Your testing requirements
- Your documentation needs

It's not generic - it's tailored to Red Frog Studio's development process.

---

**You're all set! Start building amazing WordPress sites with your new AI development partner! 🚀**

Questions? Check QUICK-REFERENCE.md or just ask the agent - it's designed to help!
