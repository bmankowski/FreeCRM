# Template Refactoring Priority Plan - MVC Separation

## Overview

Based on analysis of templates with MVC violations (function calls, static methods, business logic), this document prioritizes which templates should be refactored next to move data preparation from templates to controllers.

## Priority Criteria

1. **Frequency of Use**: Templates used across many modules (Base module templates)
2. **Complexity**: Number and complexity of violations
3. **Impact**: How many modules/pages are affected
4. **Partial Refactoring**: Templates already partially refactored (easier to complete)

## High Priority Templates (Refactor First)

### 1. **`Base/EditViewInventory.tpl`** ⭐⭐⭐⭐⭐

**Status**: Partially refactored (has `prepareInventoryData()` method)

**Violations Found**:
- `\App\Modules\Base\Models\InventoryField::getInstance($MODULE)` - Model instantiation
- `\App\Modules\Base\Models\Inventory::getDiscountsConfig()` - Static method call
- `\App\Modules\Base\Models\Inventory::getTaxesConfig()` - Static method call
- `\App\Modules\Base\Helpers\Util::getBaseCurrency()` - Static method call
- `\vtlib\Functions::getCurrencySymbolandRate($CURRENCY)` - Static method call
- `CurrencyField::convertToUserFormat($SUM, null, true)` - Static formatting method
- Multiple `count()` calls on arrays
- Complex conditional logic for currency handling

**Impact**: Used by all inventory-enabled modules (Invoices, Quotes, Purchase Orders, etc.)

**Controller**: `src/Modules/Base/Views/Edit.php` (already has `prepareInventoryData()` method)

**Refactoring Needed**:
- Complete the `prepareInventoryData()` method to assign ALL variables
- Remove all `{assign}` statements with function calls from template
- Move currency symbol/rate logic to controller
- Pre-calculate field counts in controller

**Estimated Effort**: Medium (2-3 hours) - foundation already exists

---

### 2. **`Base/DetailViewInventoryView.tpl`** ⭐⭐⭐⭐⭐

**Status**: Not refactored

**Violations Found**:
- `\App\Modules\Base\Models\InventoryField::getInstance($MODULE_NAME)` - Model instantiation
- `\App\Modules\Base\Helpers\Util::getBaseCurrency()` - Static method call
- `\vtlib\Functions::getCurrencySymbolandRate($CURRENCY)` - Static method call
- `CurrencyField::convertToUserFormat($SUM, null, true)` - Static formatting method
- `\App\Record::getType($INVENTORY_ROW['name'])` - Static method call in loop
- Multiple `count()` calls

**Impact**: Used by all inventory-enabled modules for detail view

**Controller**: `src/Modules/Base/Views/Detail.php`

**Refactoring Needed**:
- Create `prepareDetailInventoryData()` method in Detail controller
- Move all data preparation to controller
- Pre-process inventory rows with record types
- Pre-calculate summaries and currency formatting

**Estimated Effort**: Medium (2-3 hours)

---

### 3. **`Base/widgets/FastEdit.tpl`** ⭐⭐⭐⭐

**Status**: Not refactored

**Violations Found**:
- `\App\Modules\Base\Models\Module::getInstance($MODULE_NAME)` - Model instantiation
- `\App\Modules\Base\Models\Field::getInstance($item,$MODULEINSTANCE)` - Model instantiation in loop
- `\App\Json::encode($FIELD_MODEL->getFieldInfo())` - JSON encoding
- `\App\Json::encode($SPECIAL_VALIDATOR)` - JSON encoding
- `\App\Modules\Base\Helpers\Util::toSafeHTML()` - Utility function

**Impact**: Used by FastEdit widget across all modules

**Controller**: `src/Modules/Base/Widgets/FastEdit.php`

**Refactoring Needed**:
- Create `prepareFastEditData()` method in widget controller
- Pre-process all fields before template rendering
- Pre-encode JSON data
- Prepare field models array instead of instantiating in loop

**Estimated Effort**: Medium (2-3 hours)

---

### 4. **`Base/AdvanceFilterExpressions.tpl`** ⭐⭐⭐⭐

**Status**: Not refactored

**Violations Found**:
- `\App\Modules\Base\Models\Module::getInstance($SOURCE_MODULE)` - Conditional model instantiation
- `\App\Modules\Base\AdvancedFilter::getDateFilter($QUALIFIED_MODULE)` - Static method call
- `\App\Modules\Base\AdvancedFilter::getAdvancedFilterOptions()` - Static method call
- `\App\Modules\Base\AdvancedFilter::getAdvancedFilterOpsByFieldType()` - Static method call
- `\App\Modules\Base\AdvancedFilter::getExpressions()` - Static method call
- `\App\Modules\Base\AdvancedFilter::getMetaVariables()` - Static method call

**Impact**: Used by all modules for advanced filtering

**Controller**: Multiple (CustomView, Reports, ListView)

**Refactoring Needed**:
- Create `prepareAdvancedFilterData()` helper method
- Move all filter data preparation to controllers
- Remove conditional `{if !$VAR}` checks - always prepare in controller

**Estimated Effort**: Medium-High (3-4 hours) - used in multiple places

---

## Medium Priority Templates

### 5. **`Base/uitypes/SharedOwner.tpl`** ⭐⭐⭐

**Violations**:
- `\App\Fields\Owner::getInstance($MODULE)->getAccessibleUsers()` - Model instantiation + method call
- `\App\Fields\Owner::getInstance($MODULE)->getAccessibleGroups()` - Model instantiation + method call

**Impact**: Used by SharedOwner field type across modules

**Estimated Effort**: Low-Medium (1-2 hours)

---

### 6. **`Base/InventoryTaxesType1.tpl`** ⭐⭐⭐

**Violations**:
- `\App\Modules\Base\Models\Record::getInstanceById($RECORD)` - Model instantiation
- `\App\Modules\Base\UiTypes\Taxes::getValues()` - Static method call
- `CurrencyField::convertToUserFormat()` - Formatting in loop

**Impact**: Used in inventory modules for tax selection

**Estimated Effort**: Low-Medium (1-2 hours)

---

### 7. **`Base/Comment.tpl`** ⭐⭐⭐

**Violations**:
- `\App\Modules\Users\Models\Privileges::isPermitted()` - Permission checks (multiple)
- `\App\Modules\Base\Models\Module::getInstance('ModComments')` - Model instantiation
- `\App\Modules\Base\Helpers\Util::formatDateTimeIntoDayString()` - Formatting
- `\App\Modules\Base\Helpers\Util::formatDateDiffInStrings()` - Formatting
- `\App\Record::getLabel()` - Static method call

**Impact**: Used across all modules for comments

**Estimated Effort**: Medium (2 hours)

---

### 8. **`Base/BodyHeaderMobile.tpl`** ⭐⭐

**Violations**:
- `\App\Modules\Base\Models\Module::getQuickCreateModules(true)` - Static method call in foreach

**Impact**: Used in mobile header navigation

**Estimated Effort**: Low (1 hour)

---

### 9. **`OSSMail/MailActionBarRow.tpl`** ⭐⭐

**Violations**:
- Multiple `\App\Modules\Users\Models\Privileges::isPermitted()` calls

**Impact**: Used in OSSMail module

**Estimated Effort**: Low (1 hour)

---

## Low Priority Templates

### 10. **`Calendar/Reminders.tpl`** ⭐⭐
- `\App\Modules\Settings\Calendar\Models\Module::getCalendarConfig('colors')` - Static method call
- `App\Privilege::isPermitted()` - Permission check

### 11. **`Users/Login.Default.tpl`** ⭐⭐
- `Vtiger_Language_Handler::getAllLanguages()` - Static method call
- `\App\Runtime\Yeti_Layout::getAllLayouts()` - Static method call

### 12. **`Settings/PDF/Step2.tpl`** ⭐
- `Settings_PDF_Module_Model::getPageFormats()` - Static method call

---

## Recommended Refactoring Order

### Phase 1: Core Inventory Templates (Week 1)
1. ✅ **Complete `EditViewInventory.tpl`** - Finish partial refactoring
2. ✅ **Refactor `DetailViewInventoryView.tpl`** - High impact, similar to EditView

**Why First**: 
- Used by many modules (Invoices, Quotes, Purchase Orders)
- Already partially refactored (easier to complete)
- High business value (inventory is critical feature)

### Phase 2: Widgets and Filters (Week 2)
3. ✅ **Refactor `FastEdit.tpl`** - Widget used across modules
4. ✅ **Refactor `AdvanceFilterExpressions.tpl`** - Used in filtering

**Why Second**:
- Frequently used features
- Moderate complexity
- Good learning from Phase 1

### Phase 3: Field Types and Components (Week 3)
5. ✅ **Refactor `SharedOwner.tpl`** - Field type template
6. ✅ **Refactor `Comment.tpl`** - Common component
7. ✅ **Refactor `InventoryTaxesType1.tpl`** - Inventory component

**Why Third**:
- Reusable components
- Lower complexity than inventory
- Good for establishing patterns

### Phase 4: UI Components (Week 4)
8. ✅ **Refactor remaining templates** - Lower priority, polish

---

## Refactoring Template

For each template, follow this process:

### Step 1: Analyze Template
```bash
# Find all function calls
grep -n "::\|getInstance\|Json::encode" layouts/basic/modules/Base/EditViewInventory.tpl
```

### Step 2: Identify Controller
- Find which controller renders the template
- Check if helper methods already exist
- Identify all entry points (AJAX, full page, etc.)

### Step 3: Create/Update Controller Method
```php
protected function prepareInventoryData($viewer, $moduleName, $recordModel)
{
    // Move all data preparation here
    $inventoryField = \App\Modules\Base\Models\InventoryField::getInstance($moduleName);
    $viewer->assign('INVENTORY_FIELD', $inventoryField);
    // ... more assignments
}
```

### Step 4: Update Template
- Remove all `{assign}` statements with function calls
- Use pre-assigned variables
- Keep only display logic

### Step 5: Test
- Test in browser (visual verification)
- Test AJAX requests (if applicable)
- Check `cache/logs/system.log` for errors
- Test with different modules (if Base template)

---

## Success Metrics

After refactoring each template:
- ✅ Zero function calls in template (except display helpers)
- ✅ All data prepared in controller
- ✅ Template only contains display logic
- ✅ No errors in system.log
- ✅ Visual appearance unchanged
- ✅ Functionality works correctly

---

## Next Steps

**Immediate Action**: Start with `EditViewInventory.tpl` completion

1. Review existing `prepareInventoryData()` method
2. Identify missing assignments
3. Complete the method
4. Update template to use pre-assigned variables
5. Test with multiple inventory modules
6. Document any edge cases found

---

## Related Documentation

- `template-function-migration-guide.md` - Detailed migration guide
- `template-functions-analysis.md` - Analysis of function usage
- `refactoring-tpl-to-be-mvc-compliant.md` - MVC compliance guide

