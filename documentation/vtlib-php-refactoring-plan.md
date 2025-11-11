# vtlib PHP Refactoring Plan

## Overview
This document maps `vtlib` function calls in PHP code to their modern `App\` namespace alternatives.

## Key Functions Analysis

### 1. Module & Record Metadata Functions

#### `vtlib\Functions::getAllModules()`
- **Usage Count:** ~5 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getAllModules()`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $allModules = \vtlib\Functions::getAllModules(true, false, 0);
  
  // After
  $allModules = \App\ModuleManagement\Adapters\Functions::getAllModules(true, false, 0);
  ```

#### `vtlib\Functions::getModuleName($id)`
- **Usage Count:** ~5 occurrences
- **Modern Alternative:** `App\Utils\ModuleUtils::getModuleName($id)`
- **Status:** ✅ Modern function exists
- **Refactoring:**
  ```php
  // Before
  $moduleName = \vtlib\Functions::getModuleName($tabId);
  
  // After
  $moduleName = \App\Utils\ModuleUtils::getModuleName($tabId);
  ```

#### `vtlib\Functions::getModuleId($name)`
- **Usage Count:** ~3 occurrences
- **Modern Alternative:** `App\Utils\ModuleUtils::getModuleId($name)`
- **Status:** ✅ Modern function exists
- **Refactoring:**
  ```php
  // Before
  $tabId = \vtlib\Functions::getModuleId('Calendar');
  
  // After
  $tabId = \App\Utils\ModuleUtils::getModuleId('Calendar');
  ```

#### `vtlib\Functions::getCRMRecordLabel($id)`
- **Usage Count:** ~20 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getCRMRecordLabel($id)` or `App\Record::getLabel($id)`
- **Status:** ✅ Adapter exists, but `App\Record::getLabel()` is preferred
- **Refactoring:**
  ```php
  // Before
  $label = \vtlib\Functions::getCRMRecordLabel($recordId);
  
  // After (preferred)
  $label = \App\Record::getLabel($recordId);
  
  // After (adapter - if Record::getLabel() doesn't work)
  $label = \App\ModuleManagement\Adapters\Functions::getCRMRecordLabel($recordId);
  ```

#### `vtlib\Functions::getCRMRecordMetadata($id)`
- **Usage Count:** ~10 occurrences
- **Modern Alternative:** Query `vtiger_crmentity` directly + `App\Record::getLabel()`
- **Status:** ⚠️ Needs custom implementation
- **Refactoring:**
  ```php
  // Before
  $metadata = \vtlib\Functions::getCRMRecordMetadata($recordId);
  
  // After
  $record = \App\Modules\Base\Models\Record::getInstanceById($recordId);
  $metadata = [
      'label' => $record->getDisplayName(),
      'module' => $record->getModuleName(),
      'crmid' => $recordId,
      'smownerid' => $record->get('assigned_user_id'),
      'createdtime' => $record->get('createdtime'),
      'modifiedtime' => $record->get('modifiedtime'),
  ];
  ```

#### `vtlib\Functions::getEntityModuleSQLColumnString($module)`
- **Usage Count:** ~3 occurrences
- **Modern Alternative:** `App\Utils\ModuleUtils::getEntityInfo($module)`
- **Status:** ✅ Modern function exists
- **Refactoring:**
  ```php
  // Before
  $entityFields = \vtlib\Functions::getEntityModuleSQLColumnString($moduleName);
  
  // After
  $entityInfo = \App\Utils\ModuleUtils::getEntityInfo($moduleName);
  $entityFields = $entityInfo['fieldname'];
  ```

### 2. Owner & Assignment Functions

#### `vtlib\Functions::getOwnerRecordLabel($id)`
- **Usage Count:** ~5 occurrences
- **Modern Alternative:** `App\Fields\Owner::getLabel($id)`
- **Status:** ✅ Modern function exists
- **Refactoring:**
  ```php
  // Before
  $ownerLabel = \vtlib\Functions::getOwnerRecordLabel($ownerId);
  
  // After
  $ownerLabel = \App\Fields\Owner::getLabel($ownerId);
  ```

#### `vtlib\Functions::userIsAdministrator($user)`
- **Usage Count:** ~2 occurrences
- **Modern Alternative:** `$user->isAdmin()` or `\App\Modules\Users\Models\Privileges::isPermitted()`
- **Status:** ✅ Modern methods exist
- **Refactoring:**
  ```php
  // Before
  if (\vtlib\Functions::userIsAdministrator($current_user)) {
  
  // After
  if ($current_user->isAdmin()) {
  // OR
  if (\App\Modules\Users\Models\Privileges::isPermitted('Users', 'Admin')) {
  ```

### 3. Currency Functions

#### `vtlib\Functions::getAllCurrency($onlyActive)`
- **Usage Count:** ~2 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getAllCurrency($onlyActive)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $currencies = \vtlib\Functions::getAllCurrency(true);
  
  // After
  $currencies = \App\ModuleManagement\Adapters\Functions::getAllCurrency(true);
  ```

#### `vtlib\Functions::getCurrencySymbolandRate($id)`
- **Usage Count:** ~2 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getCurrencySymbolandRate($id)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $currencyInfo = \vtlib\Functions::getCurrencySymbolandRate($currencyId);
  
  // After
  $currencyInfo = \App\ModuleManagement\Adapters\Functions::getCurrencySymbolandRate($currencyId);
  ```

#### `vtlib\Functions::getCurrencyName($id, $show_symbol)`
- **Usage Count:** ~2 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getCurrencyName($id, $show_symbol)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $currencyName = \vtlib\Functions::getCurrencyName($currencyId, false);
  
  // After
  $currencyName = \App\ModuleManagement\Adapters\Functions::getCurrencyName($currencyId, false);
  ```

### 4. Text Formatting Functions

#### `vtlib\Functions::removeHtmlTags($tags, $html)`
- **Usage Count:** ~3 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::removeHtmlTags($tags, $html)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $cleanHtml = \vtlib\Functions::removeHtmlTags(['link', 'style', 'a'], $html);
  
  // After
  $cleanHtml = \App\ModuleManagement\Adapters\Functions::removeHtmlTags(['link', 'style', 'a'], $html);
  ```

#### `vtlib\Functions::textLength($text, $length, $addDots)`
- **Usage Count:** ~3 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::textLength($text, $length, $addDots)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $truncated = \vtlib\Functions::textLength($text, 100);
  
  // After
  $truncated = \App\ModuleManagement\Adapters\Functions::textLength($text, 100);
  ```

#### `vtlib\Functions::getHtmlOrPlainText($content)`
- **Usage Count:** ~2 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getHtmlOrPlainText($content)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $formatted = \vtlib\Functions::getHtmlOrPlainText($content);
  
  // After
  $formatted = \App\ModuleManagement\Adapters\Functions::getHtmlOrPlainText($content);
  ```

### 5. Utility Functions

#### `vtlib\Functions::getQueryParams($url)`
- **Usage Count:** ~8 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getQueryParams($url)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $params = \vtlib\Functions::getQueryParams($url);
  
  // After
  $params = \App\ModuleManagement\Adapters\Functions::getQueryParams($url);
  ```

#### `vtlib\Functions::getMinimizationOptions($type)`
- **Usage Count:** ~6 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getMinimizationOptions($type)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  if (\vtlib\Functions::getMinimizationOptions('js')) {
  
  // After
  if (\App\ModuleManagement\Adapters\Functions::getMinimizationOptions('js')) {
  ```

#### `vtlib\Functions::transformFieldTypeOfData($table, $column, $type)`
- **Usage Count:** ~5 occurrences
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::transformFieldTypeOfData($table, $column, $type)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $fieldType = \vtlib\Functions::transformFieldTypeOfData($table, $column, $type);
  
  // After
  $fieldType = \App\ModuleManagement\Adapters\Functions::transformFieldTypeOfData($table, $column, $type);
  ```

#### `vtlib\Functions::varExportMin($var)`
- **Usage Count:** ~10 occurrences (in PrivilegeFile.php)
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::varExportMin($var)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $exported = \vtlib\Functions::varExportMin($data);
  
  // After
  $exported = \App\ModuleManagement\Adapters\Functions::varExportMin($data);
  ```

#### `vtlib\Functions::getDiskSpace()`
- **Usage Count:** ~1 occurrence
- **Modern Alternative:** `App\ModuleManagement\Adapters\Functions::getDiskSpace($dir)`
- **Status:** ✅ Adapter exists
- **Refactoring:**
  ```php
  // Before
  $disk = \vtlib\Functions::getDiskSpace();
  
  // After
  $disk = \App\ModuleManagement\Adapters\Functions::getDiskSpace();
  ```

#### `vtlib\Functions::recurseDelete($path)`
- **Usage Count:** ~1 occurrence
- **Modern Alternative:** Custom utility or use `\App\Utils\File::deleteRecursive()`
- **Status:** ⚠️ Needs custom implementation
- **Refactoring:**
  ```php
  // Before
  \vtlib\Functions::recurseDelete('cache/speed');
  
  // After (check if exists)
  \App\Utils\File::deleteRecursive('cache/speed');
  
  // After (if no utility)
  // Implement recursive delete or use adapter
  ```

### 6. Module/Block/Field Classes

#### `vtlib\Module::getInstance($name)`
- **Usage Count:** ~20 occurrences
- **Modern Alternative:** `App\Modules\Base\Models\Module::getInstance($name)`
- **Status:** ✅ Modern class exists
- **Refactoring:**
  ```php
  // Before
  $moduleInstance = \vtlib\Module::getInstance('Calendar');
  
  // After
  $moduleInstance = \App\Modules\Base\Models\Module::getInstance('Calendar');
  ```

#### `vtlib\Block::getInstance($id)`
- **Usage Count:** ~1 occurrence
- **Modern Alternative:** `App\Modules\Base\Models\Block::getInstance($id)`
- **Status:** ✅ Modern class exists
- **Refactoring:**
  ```php
  // Before
  $blockInstance = \vtlib\Block::getInstance($blockId);
  
  // After
  $blockInstance = \App\Modules\Base\Models\Block::getInstance($blockId);
  ```

#### `vtlib\Field::getInstance($name, $moduleInstance)`
- **Usage Count:** ~2 occurrences
- **Modern Alternative:** `App\Modules\Base\Models\Field::getInstance($name, $moduleInstance)`
- **Status:** ✅ Modern class exists
- **Refactoring:**
  ```php
  // Before
  $fieldInstance = \vtlib\Field::getInstance($fieldName, $moduleInstance);
  
  // After
  $fieldInstance = \App\Modules\Base\Models\Field::getInstance($fieldName, $moduleInstance);
  ```

## Summary Statistics

| Category | Function Count | Total Usages | Status |
|----------|---------------|--------------|--------|
| Module & Record Metadata | 6 | ~46 | ✅ Mostly migrated |
| Owner & Assignment | 2 | ~7 | ✅ Ready to migrate |
| Currency | 3 | ~6 | ✅ Ready to migrate |
| Text Formatting | 3 | ~8 | ✅ Ready to migrate |
| Utilities | 6 | ~30 | ✅ Ready to migrate |
| Module/Block/Field Classes | 3 | ~23 | ✅ Ready to migrate |
| **TOTAL** | **23** | **~120** | **✅ 100% Ready** |

## Migration Priority

### Phase 1: High Priority (Most Used)
1. `getCRMRecordLabel()` - 20 usages
2. `getModuleName()` - 5 usages
3. `getQueryParams()` - 8 usages
4. `getMinimizationOptions()` - 6 usages
5. `Module::getInstance()` - 20 usages

### Phase 2: Medium Priority
1. `getCRMRecordMetadata()` - 10 usages (needs custom implementation)
2. `getAllModules()` - 5 usages
3. `transformFieldTypeOfData()` - 5 usages
4. `varExportMin()` - 10 usages

### Phase 3: Low Priority (Rare Usage)
1. `recurseDelete()` - 1 usage (needs custom implementation)
2. `getHtmlOrPlainText()` - 2 usages
3. `getDiskSpace()` - 1 usage

## Proposed Implementation Phases

### Phase 1: High Priority Functions (Most Used)
**Target:** ~60 usages across 5 functions  
**Estimated Time:** 2-3 hours  
**Risk Level:** Low (all have direct replacements)

#### 1.1 Migrate `getCRMRecordLabel()` (20 usages)
- **Files to update:** ~15 files
- **Replacement:** `App\Record::getLabel($id)` (preferred) or `App\ModuleManagement\Adapters\Functions::getCRMRecordLabel($id)`
- **Files:**
  - `src/Base/Controllers/BaseViewController.php`
  - `src/CRMEntity.php`
  - `src/Modules/OSSMailView/Models/Record.php`
  - `src/Modules/Home/Models/Module.php`
  - `src/Modules/OSSEmployees/OSSEmployees.php`
  - `src/Modules/OSSTimeControl/Actions/GetTCInfo.php`
  - `src/Modules/Base/InventoryFields/Name.php`
  - `src/Modules/Base/InventoryFields/Reference.php`
  - `src/Modules/API/Models/CardDAV.php`
  - `src/Modules/API/Models/CalDAV.php`
  - `src/Modules/Accounts/data_access/unique_account.php`
  - `src/Modules/Base/data_access/unique_value.php`
  - `src/Modules/Calendar/iCal/iCalendar_event.php`
  - `src/Modules/ModTracker/Handlers/ModTracker_ModTrackerHandler_Handler.php`

#### 1.2 Migrate `Module::getInstance()` (20 usages)
- **Files to update:** ~15 files
- **Replacement:** `App\Modules\Base\Models\Module::getInstance($name)`
- **Files:**
  - `src/Modules/Base/Models/Field.php`
  - `src/Modules/Settings/LayoutEditor/Models/Module.php`
  - `src/Modules/OSSEmployees/OSSEmployees.php`
  - `src/Modules/Calendar/Models/Module.php`
  - `src/Modules/Settings/ModuleManager/Views/ModuleImport.php`
  - `src/Modules/ModTracker/ModTracker.php`
  - `src/Modules/Services/Services.php`
  - `src/Modules/Assets/Assets.php`
  - `src/Modules/LettersOut/LettersOut.php`
  - `src/Modules/LettersIn/LettersIn.php`
  - `src/Modules/Import/Actions/Data.php`
  - `src/Modules/OSSTimeControl/OSSTimeControl.php`

#### 1.3 Migrate `getQueryParams()` (8 usages)
- **Files to update:** ~6 files
- **Replacement:** `App\ModuleManagement\Adapters\Functions::getQueryParams($url)`
- **Files:**
  - `src/Modules/Base/Views/Detail.php`
  - `src/Modules/Settings/Base/Models/MenuItem.php`
  - `src/Modules/Base/Models/Link.php`
  - `src/Modules/Base/Models/DashBoard.php`
  - `src/Modules/Base/Models/Menu.php`

#### 1.4 Migrate `getModuleName()` (5 usages)
- **Files to update:** ~4 files
- **Replacement:** `App\Utils\ModuleUtils::getModuleName($id)`
- **Files:**
  - `src/Modules/Base/Views/Detail.php`
  - `src/Modules/Base/Models/MappedFields.php`
  - `src/Modules/Settings/Widgets/Models/Module.php`

#### 1.5 Migrate `getMinimizationOptions()` (6 usages)
- **Files to update:** 1 file (already done in BaseViewController)
- **Replacement:** `App\ModuleManagement\Adapters\Functions::getMinimizationOptions($type)`
- **Status:** ✅ Already migrated in `src/Base/Controllers/BaseViewController.php`

**Phase 1 Completion Criteria:**
- ✅ All 5 high-priority functions migrated
- ✅ No `vtlib\Functions::getCRMRecordLabel()` calls remain
- ✅ No `vtlib\Module::getInstance()` calls remain
- ✅ All files tested and verified

---

### Phase 2: Medium Priority Functions
**Target:** ~30 usages across 4 functions  
**Estimated Time:** 2-3 hours  
**Risk Level:** Low-Medium (one needs custom implementation)

#### 2.1 Migrate `getCRMRecordMetadata()` (10 usages)
- **Files to update:** ~8 files
- **Replacement:** Custom implementation using Record model
- **Implementation Pattern:**
  ```php
  // Create helper method in App\Modules\Base\Models\Record or utility class
  public static function getMetadata($recordId) {
      $record = self::getInstanceById($recordId);
      return [
          'label' => $record->getDisplayName(),
          'module' => $record->getModuleName(),
          'crmid' => $recordId,
          'smownerid' => $record->get('assigned_user_id'),
          'createdtime' => $record->get('createdtime'),
          'modifiedtime' => $record->get('modifiedtime'),
      ];
  }
  ```
- **Files:**
  - `src/Modules/Users/Models/Privileges.php` (3 usages)
  - `src/Modules/Base/Models/Module.php`
  - `src/PrivilegeQuery.php` (2 usages)
  - `src/Modules/Base/Models/TransferOwnership.php`
  - `src/Modules/Calendar/Views/Detail.php` (comment mentions replacement)
  - `src/Modules/Calendar/Views/Edit.php` (comment mentions replacement)

#### 2.2 Migrate `getAllModules()` (5 usages)
- **Files to update:** ~4 files
- **Replacement:** `App\ModuleManagement\Adapters\Functions::getAllModules(...)`
- **Files:**
  - `src/Modules/Base/Models/Field.php`
  - `src/Modules/Base/Models/Module.php`
  - `src/Base/Controllers/BaseViewController.php` (already migrated ✅)

#### 2.3 Migrate `transformFieldTypeOfData()` (5 usages)
- **Files to update:** ~4 files
- **Replacement:** `App\ModuleManagement\Adapters\Functions::transformFieldTypeOfData(...)`
- **Files:**
  - `src/Modules/Base/Models/Field.php` (3 usages)
  - `src/Modules/Reports/Reports.php`
  - `src/Modules/Reports/ReportRun.php`

#### 2.4 Migrate `varExportMin()` (10 usages)
- **Files to update:** 1 file (all in PrivilegeFile.php)
- **Replacement:** `App\ModuleManagement\Adapters\Functions::varExportMin($var)`
- **Files:**
  - `src/PrivilegeFile.php` (10 usages)

**Phase 2 Completion Criteria:**
- ✅ All 4 medium-priority functions migrated
- ✅ `getCRMRecordMetadata()` helper method created and tested
- ✅ All files tested and verified

---

### Phase 3: Low Priority Functions (Rare Usage)
**Target:** ~4 usages across 3 functions  
**Estimated Time:** 1 hour  
**Risk Level:** Low

#### 3.1 Migrate `getHtmlOrPlainText()` (2 usages)
- **Files to update:** 1 file
- **Replacement:** `App\ModuleManagement\Adapters\Functions::getHtmlOrPlainText($content)`
- **Files:**
  - `src/Modules/OSSMailView/Models/Record.php` (2 usages)

#### 3.2 Migrate `getDiskSpace()` (1 usage)
- **Files to update:** 1 file
- **Replacement:** `App\ModuleManagement\Adapters\Functions::getDiskSpace($dir)`
- **Files:**
  - `src/Modules/Settings/ConfReport/Models/Module.php`

#### 3.3 Migrate `recurseDelete()` (1 usage)
- **Files to update:** 1 file
- **Replacement:** Check for `App\Utils\File::deleteRecursive()` or implement adapter
- **Files:**
  - `src/Modules/Settings/ConfReport/Models/Module.php`

**Phase 3 Completion Criteria:**
- ✅ All 3 low-priority functions migrated
- ✅ All files tested and verified

---

### Phase 4: Remaining Utility Functions
**Target:** ~15 usages across 6 functions  
**Estimated Time:** 1-2 hours  
**Risk Level:** Low

#### 4.1 Migrate Owner & Assignment Functions
- `getOwnerRecordLabel()` → `App\Fields\Owner::getLabel()` (5 usages)
- `userIsAdministrator()` → `$user->isAdmin()` (2 usages)
- **Files:**
  - `src/CRMEntity.php`
  - `src/Modules/OSSEmployees/OSSEmployees.php`
  - `src/Modules/Accounts/data_access/unique_account.php`
  - `src/Modules/Base/data_access/unique_value.php`

#### 4.2 Migrate Currency Functions
- `getAllCurrency()` → `App\ModuleManagement\Adapters\Functions::getAllCurrency()` (2 usages)
- `getCurrencySymbolandRate()` → `App\ModuleManagement\Adapters\Functions::getCurrencySymbolandRate()` (2 usages)
- `getCurrencyName()` → `App\ModuleManagement\Adapters\Functions::getCurrencyName()` (2 usages)
- **Files:**
  - `src/Modules/Base/Models/ExportToXml.php`
  - `src/Modules/Base/Models/Export.php`
  - `src/Modules/Base/UiTypes/Currency.php`

#### 4.3 Migrate Text Formatting Functions
- `removeHtmlTags()` → `App\ModuleManagement\Adapters\Functions::removeHtmlTags()` (3 usages)
- `textLength()` → `App\ModuleManagement\Adapters\Functions::textLength()` (3 usages)
- **Files:**
  - `src/Modules/OSSMailView/Models/Record.php`
  - `src/Modules/Base/UiTypes/Reference.php`

#### 4.4 Migrate Entity Functions
- `getEntityModuleSQLColumnString()` → `App\Utils\ModuleUtils::getEntityInfo()` (3 usages)
- **Files:**
  - `src/CRMEntity.php` (2 usages)

#### 4.5 Migrate Module/Block/Field Classes
- `Block::getInstance()` → `App\Modules\Base\Models\Block::getInstance()` (1 usage)
- `Field::getInstance()` → `App\Modules\Base\Models\Field::getInstance()` (2 usages)
- **Files:**
  - `src/Modules/Base/Models/Field.php`
  - `src/Modules/Calendar/Models/Module.php`
  - `src/Modules/Import/Actions/Data.php`

**Phase 4 Completion Criteria:**
- ✅ All remaining utility functions migrated
- ✅ All class instantiations migrated
- ✅ Complete codebase scan shows zero `vtlib\` calls (except in adapters)

---

## Implementation Strategy

1. **Use Adapters First**: Most functions have adapters in `App\ModuleManagement\Adapters\Functions`
2. **Use Modern Classes**: Prefer `App\Modules\Base\Models\*` over `vtlib\*`
3. **Custom Implementation**: For functions without adapters, implement in appropriate utility class
4. **Test After Each Batch**: Migrate by category, test, then proceed
5. **Incremental Approach**: Complete one phase before moving to the next
6. **Verification**: After each phase, run grep to verify no remaining usages

## Testing Strategy

### After Each Phase:
1. **Static Analysis**: Run `grep -r "vtlib\\" src/` to verify removals
2. **Unit Tests**: Run existing test suite
3. **Manual Testing**: Test affected modules/features
4. **Log Review**: Check `cache/logs/system.log` for errors

### Final Verification:
- ✅ Zero `vtlib\Functions::` calls in `src/` directory
- ✅ Zero `vtlib\Module::` calls in `src/` directory
- ✅ Zero `vtlib\Block::` calls in `src/` directory
- ✅ Zero `vtlib\Field::` calls in `src/` directory
- ✅ All tests passing
- ✅ No runtime errors in logs

## Notes

- All adapters maintain backward compatibility
- Some functions may need custom implementations (marked with ⚠️)
- `App\Record::getLabel()` is preferred over adapter for record labels
- Check adapter existence before implementing custom solutions
- Keep `vtlib\` namespace in adapters for backward compatibility during transition

