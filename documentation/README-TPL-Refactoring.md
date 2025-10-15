# TPL MVC Refactoring - Documentation Index

Welcome to the FreeCRM TPL Refactoring documentation! This guide will help you understand and fix MVC violations in Smarty template files.

---

## 📚 Documentation Overview

This documentation consists of four main documents, each serving a specific purpose:

### 1. 🎯 [Project Summary](tpl-mvc-refactoring-summary.md)
**Start here!** Executive overview and quick start guide.

**Best for:** Project managers, team leads, getting oriented  
**Contents:**
- What was created and why
- Key findings from analysis
- Quick start instructions
- Benefits and recommendations

**Read this if:** You want to understand the big picture in 5-10 minutes

---

### 2. 📖 [Complete Refactoring Guide](refactoring-tpl-to-be-mvc-compliant.md)
**The comprehensive manual.** Everything you need to know about MVC violations and how to fix them.

**Best for:** Developers doing actual refactoring work  
**Contents:**
- Detailed explanation of 12 violation types
- Before/after examples for each
- Refactoring strategies and patterns
- Automated refactoring methods
- Implementation roadmap
- Testing approaches

**Read this if:** You're actively refactoring templates or want deep understanding

---

### 3. ⚡ [Quick Reference Guide](mvc-tpl-quick-reference.md)
**Cheat sheet for daily work.** Fast answers to common questions.

**Best for:** Developers who need quick answers  
**Contents:**
- Quick violation checklist
- Pattern examples (wrong vs. correct)
- Common controller patterns
- Tool usage commands
- Testing checklist

**Read this if:** You know the concepts and just need a reminder

---

### 4. 🗺️ [Refactoring Roadmap](tpl-refactoring-roadmap.md)
**Auto-generated project plan.** Prioritized list of files to refactor.

**Best for:** Planning work and tracking progress  
**Contents:**
- Violations by module and severity
- High-priority files list
- Effort estimates and timeline
- Recommended phases

**Generate with:** `php scripts/generate_refactoring_roadmap.php`

---

## 🛠️ Tools & Scripts

All refactoring tools are in the `/scripts` directory:

### 1. Violation Analyzer
**File:** `scripts/analyze_tpl_violations.php`  
**Purpose:** Find MVC violations in TPL files

```bash
# Analyze a file
php scripts/analyze_tpl_violations.php path/to/file.tpl

# Analyze a directory
php scripts/analyze_tpl_violations.php layouts/basic/modules/Vtiger/
```

### 2. Auto-Refactoring Tool
**File:** `scripts/refactor_tpl.php`  
**Purpose:** Automatically fix common violations

```bash
# Preview changes
php scripts/refactor_tpl.php path/to/file.tpl --dry-run

# Apply changes
php scripts/refactor_tpl.php path/to/file.tpl
```

### 3. Roadmap Generator
**File:** `scripts/generate_refactoring_roadmap.php`  
**Purpose:** Create prioritized refactoring plan

```bash
php scripts/generate_refactoring_roadmap.php
```

**See:** [scripts/README.md](../scripts/README.md) for complete tool documentation

---

## 🚀 Quick Start Workflows

### For Your First Refactoring

```bash
# 1. Check what's wrong
php scripts/analyze_tpl_violations.php path/to/file.tpl

# 2. See what can be auto-fixed
php scripts/refactor_tpl.php path/to/file.tpl --dry-run

# 3. Apply automatic fixes
php scripts/refactor_tpl.php path/to/file.tpl

# 4. Check the generated controller code and add it to your controller

# 5. Test your changes
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=YourModule&view=YourView"

# 6. Verify all violations are gone
php scripts/analyze_tpl_violations.php path/to/file.tpl
```

### For Planning a Project

```bash
# 1. Generate comprehensive analysis
php scripts/generate_refactoring_roadmap.php

# 2. Review the roadmap
cat documentation/tpl-refactoring-roadmap.md

# 3. Read the summary
cat documentation/tpl-mvc-refactoring-summary.md

# 4. Create tasks from high-priority files
# (Use your project management tool)
```

---

## 📖 Reading Guide by Role

### I'm a Developer
1. Start with [Quick Reference](mvc-tpl-quick-reference.md) - 10 min
2. Work through an example file
3. Refer to [Complete Guide](refactoring-tpl-to-be-mvc-compliant.md) as needed
4. Keep Quick Reference open while coding

### I'm a Team Lead
1. Read [Project Summary](tpl-mvc-refactoring-summary.md) - 10 min
2. Generate and review roadmap
3. Skim [Complete Guide](refactoring-tpl-to-be-mvc-compliant.md) - 30 min
4. Plan sprints based on roadmap

### I'm New to the Project
1. Read [Project Summary](tpl-mvc-refactoring-summary.md)
2. Read "MVC Pattern Principles" in [Complete Guide](refactoring-tpl-to-be-mvc-compliant.md)
3. Review violation examples in [Quick Reference](mvc-tpl-quick-reference.md)
4. Practice with analyzer tool on sample files

### I'm Doing Code Review
1. Bookmark [Quick Reference](mvc-tpl-quick-reference.md)
2. Use violation checklist from Quick Reference
3. Run analyzer on changed files
4. Refer to examples when giving feedback

---

## 🎓 Common MVC Violations at a Glance

| Violation | Severity | Example |
|-----------|----------|---------|
| Model calls | ⚠️ High | `Model::getInstance()` |
| Permissions | ⚠️ High | `\App\Privilege::isPermitted()` |
| Config access | ⚠️ Medium | `AppConfig::search()` |
| JSON encoding | ⚠️ Medium | `\App\Json::encode()` |
| Array operations | ⚠️ Medium | `array_push()`, `count()` |
| Utility helpers | ⚠️ Medium | `Vtiger_Util_Helper::` |
| Field formatting | ⚠️ Medium | `_UIType::getDisplay*()` |
| Debugger | ℹ️ Low | `\App\Debugger::` |

---

## ✅ MVC Compliance Checklist

Before committing a TPL file, ensure it does NOT contain:

- [ ] `AppConfig::`
- [ ] `_Model::`
- [ ] `::getInstance(`
- [ ] `\App\Privilege::`
- [ ] `\App\Debugger::`
- [ ] `\App\Json::encode(`
- [ ] `Vtiger_Util_Helper::`
- [ ] `array_push(`, `count(`, `in_array(`
- [ ] `vtlib\Functions::`
- [ ] `\App\Fields::`
- [ ] `_UIType::`

**All violations should be fixed in the controller/model!**

---

## 🔧 Setup Pre-Commit Hook

Automatically check for violations before committing:

```bash
# Create pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
CHANGED_TPL_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.tpl$')

if [ -n "$CHANGED_TPL_FILES" ]; then
    echo "Checking TPL files for MVC violations..."
    for file in $CHANGED_TPL_FILES; do
        if [ -f "$file" ]; then
            php scripts/analyze_tpl_violations.php "$file" > /dev/null 2>&1
            if [ $? -ne 0 ]; then
                echo "❌ MVC violations in: $file"
                php scripts/analyze_tpl_violations.php "$file"
                exit 1
            fi
        fi
    done
    echo "✓ All TPL files are MVC-compliant"
fi
EOF

# Make executable
chmod +x .git/hooks/pre-commit
```

---

## 📊 Example Output

### Analyzer Output
```
================================================================================
MVC Violations Analysis Report
================================================================================

SUMMARY
--------------------------------------------------------------------------------
Total Violations: 14
Files Affected: 1

By Severity:
  HIGH      : 2
  MEDIUM    : 10
  LOW       : 2

TYPE: APPCONFIG [MEDIUM]
Count: 6
--------------------------------------------------------------------------------
  layouts/basic/modules/Vtiger/Header.tpl:59
  > <input type="hidden" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE')}" />
```

### Refactoring Output
```
================================================================================
TPL Refactoring Tool
================================================================================

✓ Changes detected!

Replacements made:
  1. AppConfig::search() → $CONFIG.gsAutocomplete (1 occurrence)

REQUIRED CONTROLLER CODE
--------------------------------------------------------------------------------
$config = [
    'gsAutocomplete' => AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE'),
];
$viewer->assign('CONFIG', $config);
```

---

## 🆘 Troubleshooting

### "No violations found but code still looks wrong"
→ The script detects common patterns. Some violations may need manual review.  
→ Check the [Complete Guide](refactoring-tpl-to-be-mvc-compliant.md) for all violation types.

### "Auto-refactoring broke my page"
→ Restore from the backup file created (`.backup_*`)  
→ Review and add the controller code manually  
→ Test incrementally

### "I don't understand why this is a violation"
→ Read the specific section in [Complete Guide](refactoring-tpl-to-be-mvc-compliant.md)  
→ Look at before/after examples in [Quick Reference](mvc-tpl-quick-reference.md)  
→ Check MVC principles in the guide

---

## 📞 Getting Help

1. **Quick answers:** Check [Quick Reference](mvc-tpl-quick-reference.md)
2. **Detailed explanation:** See [Complete Guide](refactoring-tpl-to-be-mvc-compliant.md)
3. **Tool usage:** Read [scripts/README.md](../scripts/README.md)
4. **Project planning:** Review [Summary](tpl-mvc-refactoring-summary.md)

---

## 📈 Tracking Progress

### Generate periodic reports
```bash
# Save progress snapshots
php scripts/analyze_tpl_violations.php layouts/basic/modules/ json \
  > progress_$(date +%Y%m%d).json

# Compare violations over time
diff progress_20250101.json progress_20250115.json
```

### Metrics to track
- Total violations (should decrease)
- Files with violations (should decrease)
- Violations by severity (high priority first)
- Module compliance (% clean files per module)

---

## 🎯 Success Criteria

Your refactoring is successful when:

1. ✅ Zero violations reported by analyzer
2. ✅ All functionality works correctly
3. ✅ Tests pass (manual and automated)
4. ✅ Controller contains all business logic
5. ✅ Template only displays pre-processed data
6. ✅ Code review approved

---

## 📝 Contributing

Found a new violation pattern? Want to improve the tools?

1. Add detection pattern to `scripts/analyze_tpl_violations.php`
2. Add refactoring logic to `scripts/refactor_tpl.php`
3. Document in [Complete Guide](refactoring-tpl-to-be-mvc-compliant.md)
4. Add example to [Quick Reference](mvc-tpl-quick-reference.md)
5. Update this index if needed

---

## 🗂️ File Structure

```
FreeCRM/
├── documentation/
│   ├── README-TPL-Refactoring.md          ← YOU ARE HERE
│   ├── tpl-mvc-refactoring-summary.md     ← Start here
│   ├── refactoring-tpl-to-be-mvc-compliant.md  ← Complete guide
│   ├── mvc-tpl-quick-reference.md         ← Quick reference
│   └── tpl-refactoring-roadmap.md         ← Generated roadmap
│
├── scripts/
│   ├── README.md                          ← Scripts documentation
│   ├── analyze_tpl_violations.php         ← Analyzer tool
│   ├── refactor_tpl.php                   ← Refactoring tool
│   └── generate_refactoring_roadmap.php   ← Roadmap generator
│
└── cache/
    ├── tpl_violations_report.txt          ← Generated reports
    └── tpl_violations.json                ← Machine-readable data
```

---

## ⏭️ Next Steps

**New to this?**
1. Read [Project Summary](tpl-mvc-refactoring-summary.md) (10 min)
2. Run analyzer on a sample file
3. Try auto-refactoring with `--dry-run`

**Ready to refactor?**
1. Generate roadmap: `php scripts/generate_refactoring_roadmap.php`
2. Pick a high-priority file
3. Follow the Quick Start workflow above

**Planning a project?**
1. Read [Summary](tpl-mvc-refactoring-summary.md)
2. Review generated roadmap
3. Plan sprints based on effort estimates

---

**Good luck with your refactoring! 🚀**

Remember: When in doubt, if it feels like business logic, it doesn't belong in the template!

---

**Documentation Version:** 1.0  
**Last Updated:** 2025-10-15  
**Maintained By:** FreeCRM Development Team

