# WordPress Development Agent - Self-Check Checklist

Use this checklist before and during each task to ensure compliance with all rules.

## ✅ Startup Checklist (Every Time Agent Starts)

- [ ] Run `pwd` to determine current path
- [ ] Verify parent theme path: `/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src`
- [ ] Calculate WordPress root (4 levels up from child src/)
- [ ] Scan parent theme for custom functions
- [ ] Scan parent theme for hooks and filters
- [ ] Scan parent theme for ACF patterns
- [ ] Identify Tailwind/Flowbite setup
- [ ] Scan child theme current state
- [ ] Update CLAUDE.md with all findings
- [ ] Verify WP-CLI availability: `wp --version`
- [ ] Confirm debug.log location
- [ ] Verify gulp watch is running (don't start it)
- [ ] Ensure `.claude/.tasks/` structure exists

## ✅ Before Starting Any Task

- [ ] Read and understand the task requirements completely
- [ ] Ultra-think about the implementation approach
- [ ] Identify which parent theme functions apply
- [ ] Identify which hooks/filters to use
- [ ] Verify I'm working in child theme `src/` folder ONLY
- [ ] If user specified files/filters, noted them exactly
- [ ] Plan which files to modify/create
- [ ] Consider ACF patterns if applicable
- [ ] Plan responsive design approach with Tailwind

## ✅ During Implementation

### File Operations
- [ ] Working ONLY in child theme `src/` folder
- [ ] NOT touching parent theme files
- [ ] NOT working in `build/` folder
- [ ] Working with SCSS files, NOT CSS
- [ ] NOT manually enqueueing CSS/JS

### Code Quality
- [ ] Using parent theme's custom functions (like `wpimage`)
- [ ] Applying appropriate hooks and filters from parent
- [ ] Using Tailwind CSS classes
- [ ] Following Extended ACF patterns
- [ ] Writing clean, well-commented code
- [ ] Using Flowbite components where appropriate

### Verification
- [ ] NOT hallucinating - verified all functions/hooks exist
- [ ] Respecting user's specified files/filters exactly
- [ ] Following established patterns from parent theme
- [ ] Code is mobile-responsive (mobile-first approach)

## ✅ Database Changes Protocol

If task requires database changes:
- [ ] STOPPED implementation
- [ ] Informed user of exact changes needed
- [ ] Provided WP-CLI commands or SQL queries
- [ ] WAITING for user confirmation
- [ ] Will document changes in DATABASE.md after confirmation

## ✅ Testing Phase

### Preparation
- [ ] Task is completely finished
- [ ] Asked user: "What URL should I test with Playwright?"
- [ ] Set up Playwright MCP (headless mode)
- [ ] Configured for provided domain

### Test Execution
- [ ] Running console error checks
- [ ] Testing implemented functionality
- [ ] Running visual regression checks
- [ ] Testing responsive design:
  - [ ] Mobile viewport (375x667)
  - [ ] Tablet viewport (768x1024)
  - [ ] Desktop viewport (1920x1080)
- [ ] Capturing screenshots to `.claude/.tasks/[task]/screenshots/`
- [ ] Checking debug.log for PHP errors

### Error Handling
- [ ] If errors found, analyzing them thoroughly
- [ ] Attempting automatic fixes
- [ ] Re-testing after fixes
- [ ] Continuing until all tests pass

### Completion
- [ ] Constructed specific preview URL
- [ ] Prepared to present preview URL to user

## ✅ Documentation Phase

### Task Folder Structure
- [ ] Created `.claude/.tasks/[task-name]/` folder
- [ ] Created README.md with:
  - [ ] Overview
  - [ ] Files modified/created with descriptions
  - [ ] Parent functions used
  - [ ] Hooks/filters applied
  - [ ] Testing results (✅/❌)
  - [ ] Preview URL
  - [ ] Database changes (if any)
  - [ ] Special notes
- [ ] Created CHANGES.md if significant changes
- [ ] Created DATABASE.md if DB changes made
- [ ] Saved Playwright screenshots
- [ ] Organized all files properly

### Content Quality
- [ ] Documentation is comprehensive
- [ ] Code snippets included where helpful
- [ ] Clear explanation of what was done
- [ ] Notes on any special considerations
- [ ] Future improvement suggestions if applicable

## ✅ Task Completion

- [ ] Presented clear summary to user
- [ ] Provided specific preview URL
- [ ] Documented everything in task folder
- [ ] NOT creating git commit yet
- [ ] Waiting for user's 100% confirmation
- [ ] Will use git-committer agent ONLY after user confirms

## ✅ Communication Quality

- [ ] Being direct and concise
- [ ] Asking clarifying questions if needed
- [ ] Explaining what I'm doing during long operations
- [ ] Presenting results clearly
- [ ] Including preview URLs in responses
- [ ] Not being overly verbose

## ❌ Never Do - Critical Rules

- [ ] NEVER work outside child theme `src/` folder
- [ ] NEVER modify parent theme files
- [ ] NEVER touch `build/` folder
- [ ] NEVER enqueue CSS/JS manually
- [ ] NEVER work with compiled CSS files
- [ ] NEVER run gulp commands
- [ ] NEVER hallucinate functions/hooks
- [ ] NEVER commit without user 100% confirmation
- [ ] NEVER skip testing after task completion
- [ ] NEVER skip documentation

## 🔄 Continuous Checks During Task

Every few minutes, verify:
- [ ] Still working in correct directory (child src/)
- [ ] Using parent theme functions, not reinventing
- [ ] Following Tailwind patterns
- [ ] Code is clean and commented
- [ ] Approach aligns with user's specifications

## 📊 Quality Metrics

Before declaring task complete:
- [ ] All tests passing
- [ ] No errors in debug.log
- [ ] Responsive design verified on all viewports
- [ ] Code follows established patterns
- [ ] Documentation is comprehensive
- [ ] Preview URL works correctly
- [ ] Ready for user review

## 🚨 Red Flags - Stop and Reconsider

If any of these occur, STOP and reassess:
- Working outside `src/` folder
- About to modify parent theme
- About to manually enqueue assets
- Inventing functions that might not exist
- About to skip testing
- About to commit without user confirmation
- Creating functionality without checking parent theme first
- Not using parent theme's custom functions

---

## Self-Audit Questions

Before finishing any task, ask:

1. **Did I verify all functions/hooks exist in parent theme?**
2. **Am I working exclusively in child theme src/?**
3. **Have I used appropriate parent theme functions?**
4. **Is my code using Tailwind CSS properly?**
5. **Did I test comprehensively?**
6. **Is documentation complete?**
7. **Have I provided a working preview URL?**
8. **Am I waiting for user confirmation before committing?**

If answer to ANY question is "No", address it before declaring task complete.

---

**This checklist ensures highest quality work and compliance with all project rules.**
