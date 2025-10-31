# Next Batch Migration Plan - Iteration 2

## Ready to Execute

All aliases verified to have **ZERO template usage** and can be fully removed!

---

## Batch 2: 11 Aliases to Migrate

### High Priority (Most Impact)

#### 1. Settings_Vtiger_Module_Model → `FreeCRM\Modules\Settings\Vtiger\Models\Module`
- **PHP files:** 81 (40 in src/, 41 in old_modules/)
- **Templates:** 0
- **Command:** `php refactor/migrate-alias-simple.php Settings_Vtiger_Module_Model`

#### 2. VTWorkflowManager → `FreeCRM\Modules\com_vtiger_workflow\VTWorkflowManager`
- **PHP files:** 63 (20 in src/, 43 in old_modules/)
- **Templates:** 0
- **Command:** `php refactor/migrate-alias-simple.php VTWorkflowManager`

#### 3. Settings_Vtiger_Record_Model → `FreeCRM\Modules\Settings\Vtiger\Models\Record`
- **PHP files:** 27 (13 in src/, 14 in old_modules/)
- **Templates:** 0
- **Command:** `php refactor/migrate-alias-simple.php Settings_Vtiger_Record_Model`

#### 4. Vtiger_Paging_Model → `FreeCRM\Modules\Vtiger\Models\Paging`
- **PHP files:** 24 (12 in src/, 12 in old_modules/)
- **Templates:** 0
- **Command:** `php refactor/migrate-alias-simple.php Vtiger_Paging_Model`

#### 5. Vtiger_DetailView_Model → `FreeCRM\Modules\Vtiger\Models\DetailView`
- **PHP files:** 22 (14 in src/, 8 in old_modules/)
- **Templates:** 0
- **Command:** `php refactor/migrate-alias-simple.php Vtiger_DetailView_Model`

#### 6. Vtiger_DependencyPicklist → `FreeCRM\Modules\PickList\DependencyPicklist`
- **PHP files:** 21 (11 in src/, 10 in old_modules/)
- **Templates:** 0
- **Command:** `php refactor/migrate-alias-simple.php Vtiger_DependencyPicklist`

### Medium Priority

#### 7. VTJsonCondition → `FreeCRM\Modules\com_vtiger_workflow\VTJsonCondition`
- **PHP files:** 11 (6 in src/, 5 in old_modules/)
- **Templates:** 0
- **Command:** `php refactor/migrate-alias-simple.php VTJsonCondition`

#### 8. Vtiger_TreeCategoryModal_Model → `FreeCRM\Modules\Vtiger\Models\TreeCategoryModal`
- **PHP files:** 3 (2 in src/, 1 in old_modules/)
- **Templates:** 0
- **Command:** `php refactor/migrate-alias-simple.php Vtiger_TreeCategoryModal_Model`

### Low Priority (Unused - Direct Removal)

#### 9-11. Unused Aliases (Can be deleted immediately)
- **Vtiger_Utility_Model** → 0 PHP files, 0 templates
- **Vtiger_TreeView_Model** → 0 PHP files, 0 templates
- **Vtiger_DashBoard_Model** → 0 PHP files, 0 templates

These can be removed directly from GlobalAliases.php without migration!

---

## Execution Strategy

### Option A: Full Batch (Recommended)
Process all 11 at once, then test and remove:
```bash
cd /home/bmankowski/projects/FreeCRM

# Migrate high priority
php refactor/migrate-alias-simple.php Settings_Vtiger_Module_Model
php refactor/migrate-alias-simple.php VTWorkflowManager
php refactor/migrate-alias-simple.php Settings_Vtiger_Record_Model
php refactor/migrate-alias-simple.php Vtiger_Paging_Model
php refactor/migrate-alias-simple.php Vtiger_DetailView_Model
php refactor/migrate-alias-simple.php Vtiger_DependencyPicklist

# Migrate medium priority
php refactor/migrate-alias-simple.php VTJsonCondition
php refactor/migrate-alias-simple.php Vtiger_TreeCategoryModal_Model

# Remove unused (no migration needed)
sed -i '/Vtiger_Utility_Model/d; /Vtiger_TreeView_Model/d; /Vtiger_DashBoard_Model/d' src/GlobalAliases.php

# Test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L -d "username=admin&password=admin" -X POST "http://localhost/index.php?module=Users&action=Login"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L "http://localhost/index.php?module=Leads&view=Detail&record=112"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L "http://localhost/index.php?module=Leads&view=ListView&mid=48&parent=47"

# If tests pass, remove migrated aliases
sed -i '/Settings_Vtiger_Module_Model/d; /VTWorkflowManager/d; /Settings_Vtiger_Record_Model/d; /Vtiger_Paging_Model/d; /Vtiger_DetailView_Model/d; /Vtiger_DependencyPicklist/d; /VTJsonCondition/d; /Vtiger_TreeCategoryModal_Model/d' src/GlobalAliases.php
```

### Option B: Split into Two Sub-Batches
Process 5-6 at a time with testing in between for extra safety.

---

## Benefits

### Immediate
- **230 files** get proper `use` statements
- **11 aliases** removed from GlobalAliases.php
- **Better IDE support** in Settings and Workflow modules

### Long-term
- Reduces GlobalAliases.php to **394 aliases** (from 405)
- Cleaner dependency management
- Easier codebase navigation

---

## Risk Assessment

**Risk Level: LOW** ✅

Reasons:
- All aliases have zero template usage (verified)
- Same proven migration strategy as batch 1
- Unused aliases can be removed without migration
- Comprehensive testing after each batch

---

## Alternative: Conservative Approach

If you prefer extra safety, start with just the top 3:
1. Settings_Vtiger_Module_Model (81 files)
2. VTWorkflowManager (63 files)
3. Settings_Vtiger_Record_Model (27 files)

Then proceed with the rest after confirmation.

---

Would you like to proceed with **Option A (all 11)** or a more conservative approach?

