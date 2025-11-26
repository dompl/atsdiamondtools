# 🚀 START HERE - WordPress Development Agent

## 👋 Welcome!

You asked for a Claude Code agent that:
- ✅ Knows your entire WordPress theme setup
- ✅ Works only in child theme (never touches parent)
- ✅ Uses your custom functions (like wpimage)
- ✅ Tests everything with Playwright
- ✅ Documents all work comprehensively
- ✅ Auto-fixes errors
- ✅ Waits for your approval before committing

**You got it! Here's how to deploy it in 5 minutes.**

---

## ⚡ 5-Minute Quick Start

### Step 1: Get the Files (30 seconds)

You have 10 files in this package. You only need ONE to start:

```
wordpress-theme-agent.json  ← This is the agent
```

### Step 2: Deploy to Child Theme (30 seconds)

```bash
# Navigate to your child theme
cd /path/to/your-child-theme

# Copy the agent configuration
cp /path/to/wordpress-theme-agent.json ./
```

That's it for files! The agent handles everything else.

### Step 3: Start Claude Code (10 seconds)

```bash
# In your child theme folder
claude-code
# or whatever command starts Claude Code with this agent
```

### Step 4: Watch Magic Happen (2 minutes)

The agent will automatically:
```
📍 Determining paths...
   ✓ Parent theme: /var/.../skylinewp-dev-parent/src
   ✓ Child theme: [current folder]
   ✓ WordPress root: [detected]

🔍 Scanning parent theme...
   ✓ Found 47 custom functions
   ✓ Found wpimage function
   ✓ Scanning ACF patterns...
   ✓ Identifying Tailwind setup...

📝 Creating CLAUDE.md knowledge base...
   ✓ Documented all functions
   ✓ Documented hooks and filters
   ✓ Ready!

🎯 Agent initialized! Ready for tasks.
```

### Step 5: Give First Task (30 seconds)

Try something simple:
```
Create a simple component in src/templates/test.php that displays 
"Hello World" using Tailwind CSS classes
```

### Step 6: Provide Test URL (10 seconds)

Agent will ask:
```
What URL should I test with Playwright?
```

Answer:
```
https://your-dev-site.com
```

Agent remembers this for the session.

### Step 7: Review Results (1 minute)

Agent will:
- Create the file
- Test it with Playwright
- Check debug.log
- Create documentation
- Give you preview URL

Done! 🎉

---

## 📚 What to Read

### Read First (Choose One)

**If you want to jump in immediately:**
→ Read: `DEPLOYMENT-SUMMARY.md` (5 min)

**If you want complete understanding:**
→ Read: `README.md` (15 min)

### Keep Handy

**During daily use:**
→ Keep open: `QUICK-REFERENCE.md`

### Read Once

**To see what agent produces:**
→ Review: `SAMPLE-TASK-README.md`

---

## 🎯 What Files Do What

| File | Purpose | Do You Need It? |
|------|---------|-----------------|
| `wordpress-theme-agent.json` | The agent | ✅ YES - Deploy this |
| `README.md` | Complete docs | ⭐ YES - Read once |
| `QUICK-REFERENCE.md` | Daily guide | ⭐ YES - Keep handy |
| `DEPLOYMENT-SUMMARY.md` | Setup guide | ⭐ YES - Read first |
| `SAMPLE-TASK-README.md` | Example output | 👀 Review once |
| `FILE-MANIFEST.md` | File explanations | 📖 Reference |
| Everything else | Agent internals | ⚙️ Optional |

---

## 🎬 Your First 3 Tasks

### Task 1: Simple (Learn the Flow)
```
Create a simple template in src/templates/test-component.php 
that displays a heading with Tailwind classes
```

**Expected**: ~2 minutes, agent creates file, tests, documents

### Task 2: Use Parent Function (See Function Usage)
```
Create a hero section in src/templates/hero.php that uses 
wpimage to display a featured image
```

**Expected**: ~3 minutes, agent uses wpimage correctly, tests responsive

### Task 3: AJAX Feature (See Full Capability)
```
Create an AJAX search feature in src/inc/ajax-search.php that 
searches posts and returns results without page reload
```

**Expected**: ~10 minutes, agent creates AJAX handler, tests thoroughly, comprehensive docs

---

## 💡 Key Things to Know

### 1. Agent Scans on Every Startup

Every time you start the agent:
- Scans parent theme
- Updates CLAUDE.md
- Always has latest knowledge

**You don't need to do anything!**

### 2. Agent Only Works in src/

The agent will NEVER:
- Modify parent theme
- Touch build/ folder
- Work with compiled CSS
- Enqueue assets manually

**It's hardwired to be safe!**

### 3. Agent Uses Your Functions

The agent ALWAYS uses:
- Your wpimage function
- Your custom hooks
- Your ACF patterns
- Your established conventions

**It learns from your parent theme!**

### 4. Agent Tests Everything

After each task:
- Playwright tests (headless)
- Console error check
- Debug.log check  
- Responsive testing
- Auto-fixes issues

**Quality is guaranteed!**

### 5. Agent Documents Everything

For every task, creates:
```
.claude/.tasks/[task-name]/
├── README.md (what was done)
├── screenshots/ (test results)
└── DATABASE.md (if DB changes)
```

**Full audit trail!**

### 6. Agent Waits for Approval

The agent will NEVER commit until you say:
```
Perfect! Please commit this.
```

**You stay in control!**

---

## 🚨 Common First-Time Questions

### Q: Do I need to configure anything?

**A:** No! Agent auto-detects everything:
- Parent theme path (hardcoded)
- Child theme path (current folder)
- WordPress root (calculated)
- Custom functions (scanned)
- ACF patterns (discovered)

### Q: What if gulp isn't running?

**A:** Agent checks but doesn't start it. You mentioned it's always running - if not, start it manually before using agent.

### Q: Will agent modify my parent theme?

**A:** NEVER. It's read-only. Agent is hardwired to only work in child src/.

### Q: What if I specify the wrong file?

**A:** Agent uses exactly what you specify. Be specific:
```
Add this to src/inc/my-functions.php
```

### Q: How do I test without providing URL every time?

**A:** Agent asks once per session, then remembers.

### Q: Can I disable testing?

**A:** Testing only happens after complete tasks, not every save. It's fast (2-5 min).

---

## ⚡ Power User Tips

### Specify Exact Files
```
✅ Add this function to src/inc/ajax-handlers.php using wp_ajax_custom_search
```

### Reference Parent Functions
```
✅ Use the wpimage function from parent theme
```

### Provide Context
```
✅ Create testimonials carousel, style it like the existing team section
```

### Check Documentation
```
Show me what was documented in .claude/.tasks/last-task/
```

---

## 🎯 Expected Workflow

```
You: [Describe task]
       ↓
Agent: [Plans implementation]
       ↓
Agent: [Asks clarifying questions if needed]
       ↓
Agent: [Implements in child src/]
       ↓
Agent: "What URL should I test?"
       ↓
You: [Provide URL]
       ↓
Agent: [Tests comprehensively]
       ↓
Agent: [Auto-fixes any issues]
       ↓
Agent: [Creates documentation]
       ↓
Agent: "Preview: https://yoursite.com/page"
       ↓
You: [Review work]
       ↓
You: "Perfect! Commit it."
       ↓
Agent: [Uses git-committer]
       ↓
Done! ✅
```

---

## 📊 Time Expectations

| Task Type | Time | Includes |
|-----------|------|----------|
| Simple component | 2-3 min | Code + test + docs |
| AJAX feature | 5-10 min | Handler + test + docs |
| Complex feature | 15-30 min | Full impl + test + docs |
| Bug fix | 3-5 min | Fix + verify + docs |

**Most time**: Implementation and testing  
**Fastest**: Documentation (automatic!)

---

## ✅ Pre-Flight Checklist

Before first use, verify:

- [ ] Child theme has `src/` folder
- [ ] Gulp watch is running (check with `ps aux | grep gulp`)
- [ ] WP-CLI available (check with `wp --version`)
- [ ] WordPress debug enabled
- [ ] You have a dev URL for testing
- [ ] Parent theme at correct path

All good? **Deploy and start!**

---

## 🆘 Need Help?

### Agent Not Working?
1. Check you're in child theme folder
2. Verify `wordpress-theme-agent.json` is present
3. Restart Claude Code

### Agent Seems Confused?
```
Rescan the parent theme and update CLAUDE.md
```

### Want to See What Agent Knows?
```
Show me the contents of CLAUDE.md
```

### Tests Failing?
Agent auto-fixes! But if stuck:
```
Show me the exact error
```

---

## 🎉 You're Ready!

### Right Now:
1. Copy `wordpress-theme-agent.json` to child theme
2. Start Claude Code
3. Give first task
4. Watch magic happen

### Within 5 Minutes:
- Agent initialized
- First task complete
- Documentation created
- You're productive!

### Within 1 Hour:
- Comfortable with workflow
- Understand documentation
- Leveraging parent functions
- Moving fast!

---

## 📁 Files at a Glance

```
wordpress-dev-agent/
├── START-HERE.md              ← You are here!
├── wordpress-theme-agent.json ← Deploy this
├── README.md                  ← Complete reference
├── QUICK-REFERENCE.md         ← Daily guide
├── DEPLOYMENT-SUMMARY.md      ← Setup details
├── SAMPLE-TASK-README.md      ← Example output
├── FILE-MANIFEST.md           ← All files explained
├── AGENT-CHECKLIST.md         ← Agent internals
├── CLAUDE-TEMPLATE.md         ← Agent internals
└── init-wordpress-agent.sh    ← Optional helper
```

**Focus on**: The top 5 files  
**Deploy**: Just the .json  
**Read**: README or DEPLOYMENT-SUMMARY

---

## 🚀 Final Words

This agent was built specifically for YOUR workflow:
- Your parent-child theme setup
- Your gulp configuration
- Your Extended ACF patterns
- Your Tailwind/Flowbite integration
- Your testing requirements
- Your documentation needs

It's not generic - it's **tailored to Red Frog Studio**.

**Stop reading. Start building.** 

The agent handles the complexity. You focus on features.

---

**Deploy now → Build better WordPress sites faster! 🎯**

Questions? Everything is documented in the other files.  
Still stuck? The agent itself can help - just ask it!
