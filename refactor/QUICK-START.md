# Quick Start: Module Migration

## Prerequisites Complete ✅

Phase 1 foundation is complete:
- ✅ New `FreeCRM\Loader` class created
- ✅ Composer configured for PSR-4 modules
- ✅ WebUI updated to use new loader
- ✅ Migration scripts ready

## Migrate Your First Module (Recommended: Vtiger)

### Step 1: Preview Migration (Dry Run)

```bash
cd /home/bmankowski/projects/FreeCRM
php refactor/scripts/copy-module.php Vtiger --dry-run
```

This shows what would happen without making changes.

### Step 2: Execute Migration

```bash
php refactor/scripts/copy-module.php Vtiger
```

This creates `src/Modules/Vtiger/` with:
- Transformed directory structure (Models, Views, Actions)
- Namespace declarations added
- Class names updated
- Basic transformations applied

### Step 3: Manual Review & Fixes

The automated script does ~70-80% of the work. You'll need to manually fix:

1. **Use statements** - Add missing imports
2. **Parent classes** - Update extends clauses
3. **Type hints** - Fix any incorrect type references
4. **Complex references** - Review dynamic class construction

**Common fixes:**
```php
// Add use statements at top
use FreeCRM\Modules\Vtiger\Models\Record as VtigerRecord;
use FreeCRM\Modules\Vtiger\Models\Module;
use FreeCRM\Runtime\Vtiger_Base_Model;

// Update extends
class Record extends VtigerRecord {  // was: Vtiger_Record_Model
    // ...
}

// Update method calls
$model = Module::getInstance($name);  // was: Vtiger_Module_Model
```

### Step 4: Validate

```bash
php refactor/scripts/validate-module.php Vtiger
```

This checks:
- ✓ All files have namespaces
- ✓ Class names match file names
- ✓ No syntax errors
- ✓ PSR-4 compliance

Fix any errors reported.

### Step 5: Test Loading

```bash
php refactor/scripts/test-module.php Vtiger
```

This verifies components can be loaded by the new Loader.

### Step 6: Web Testing

Since the application now uses `FreeCRM\Loader`, test through the web interface:

```bash
# Start dev server if needed
php -S localhost:8000

# Test with curl
curl -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost:8000/index.php?module=Users&action=Login"
```

Test the migrated module's views/actions through the browser.

### Step 7: Commit

```bash
git add src/Modules/Vtiger
git add src/Loader.php
git add composer.json
git add src/EntryPoint/WebUI.php
git commit -m "Migrate Vtiger base module to PSR-4

- Created FreeCRM\Loader for PSR-4 module resolution
- Updated composer.json with FreeCRM\Modules mapping
- Migrated Vtiger base module to src/Modules/Vtiger/
- Updated WebUI to use new loader
"
```

## After First Module

Once Vtiger is successfully migrated and tested, continue with:

1. **Home** - Simple module, good practice
2. **Dashboard** - Simple module
3. **Users** - Critical, test thoroughly
4. **Leads/Accounts/Contacts** - Core business modules

## Troubleshooting

### Module doesn't load

Check `cache/logs/phpError.log` for errors:
```bash
tail -f cache/logs/phpError.log
```

### Class not found

1. Verify namespace in file matches PSR-4 structure
2. Run `composer dump-autoload`
3. Check class name matches file name exactly

### Parent class not found

Add use statement for parent class:
```php
use FreeCRM\Modules\Vtiger\Models\Record as VtigerRecord;

class MyRecord extends VtigerRecord {
    // ...
}
```

### Dynamic class construction fails

Update to use Loader:
```php
// Old:
$class = $module . '_Record_Model';

// New:
$class = \FreeCRM\Loader::getComponentClassName('Model', 'Record', $module);
```

## Tips

1. **Start simple** - Migrate small/simple modules first to learn the process
2. **Test frequently** - Validate after each module
3. **Commit often** - One commit per module
4. **Use dry-run** - Always preview before executing
5. **Keep notes** - Document module-specific issues for others

## Need Help?

Check these files:
- `refactor/MIGRATION-PROGRESS.md` - Overall progress tracking
- `psr-4-module-migration.plan.md` - Complete plan
- `refactor/scripts/*.php` - Script source code

## Next Module

After Vtiger works, run:
```bash
php refactor/scripts/copy-module.php Home --dry-run
```

Happy migrating! 🚀

