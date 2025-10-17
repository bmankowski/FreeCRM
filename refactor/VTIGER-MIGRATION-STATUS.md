# Vtiger Module Migration Status

## Current State

**Status:** Partially migrated, needs manual fixes

**Date:** 2025-10-09

## What's Complete

✅ **Automated transformation executed:**
- Copied all 250 files from `modules/Vtiger/` to `src/Modules/Vtiger/`
- Renamed directories (models → Models, views → Views, etc.)
- Added namespace declarations to all PHP files
- Updated most class names

✅ **Manual fixes applied:**
- Fixed class names in Views/ directory (35 files)
- Fixed class names in Models/ directory (40+ files)
- Fixed class names in Actions/ directory (30+ files)
- Fixed class names in Dashboards/, UiTypes/, Widgets/, Handlers/

## Remaining Issues

❌ **Syntax errors:** 10+ files
- Issue: Extends clauses need leading backslash for global classes
- Example: `extends vtlib\Field` should be `extends \vtlib\Field`

❌ **Missing namespaces:** ~50 files
- data_access/*.php files (non-class files)
- Some utility/helper files

❌ **Namespace mismatches:** 30 warnings
- Subdirectories not properly reflected in namespace
- helpers/, filters/, files/, etc. need subdirectory in namespace

## Recommended Approach

Given Vtiger's complexity (250 files, base module for all others), **recommend different strategy:**

### Option 1: Fix Systematically (Current Path)
Continue fixing issues with sed:
```bash
# Fix extends clauses for global classes
find src/Modules/Vtiger -name "*.php" -exec sed -i 's/extends vtlib\\/extends \\vtlib\\/g' {} \;
find src/Modules/Vtiger -name "*.php" -exec sed -i 's/extends Vtiger_/extends \\App\\Modules\\Vtiger\\/g' {} \;
# ... more fixes
```

Estimated time: 2-4 hours

### Option 2: Start with Simpler Modules ⭐ RECOMMENDED
Migrate simpler modules first to:
1. Test the full workflow
2. Refine migration scripts
3. Build confidence
4. Then return to Vtiger with better tools

**Suggested order:**
1. **Home** - Very simple (5-10 files)
2. **ModComments** - Simple (10-15 files)
3. **Announcements** - Simple (10-15 files)
4. **Dashboard** - Medium (20-30 files)
5. **Then return to Vtiger** with refined process

### Option 3: Manual Migration from Scratch
For Vtiger only, manually migrate key files:
- Critical models (Record, Module, Field)
- Critical views (List, Detail, Edit)
- Critical actions (Save, Delete)
- Skip less important files initially

## Key Learnings

### What Worked Well
- Automated file copying
- Directory renaming
- Basic namespace addition
- Class name pattern matching for simple cases

### What Needs Improvement in Script
1. **Class name extraction** - Need better parsing of `Module_Component_Type` pattern
2. **Extends clause handling** - Need to detect and fix global class references
3. **Use statements** - Need to generate proper use statements
4. **Subdirectory namespaces** - helpers/, filters/ etc. need special handling
5. **Non-class files** - Skip or handle differently

## Next Steps

**Recommended:** Pause Vtiger, migrate simpler modules first

**Alternative:** Continue with systematic fixes

See `refactor/QUICK-START.md` for migration workflow.

## Commands for Continuation

If continuing with Vtiger fixes:

```bash
# Fix global class extends
cd /home/bmankowski/projects/FreeCRM
find src/Modules/Vtiger -name "*.php" -exec sed -i 's/extends vtlib\\/extends \\vtlib\\/g' {} \;
find src/Modules/Vtiger -name "*.php" -exec sed -i 's/extends Vtiger_\([A-Za-z_]*\)/extends \\/g' {} \;

# Re-validate
php refactor/scripts/validate-module.php Vtiger
```

If starting with simpler module:

```bash
# Start fresh with Home module
php refactor/scripts/copy-module.php Home --dry-run
php refactor/scripts/copy-module.php Home
# ... manual fixes ...
php refactor/scripts/validate-module.php Home
```

