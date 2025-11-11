# Template Function Migration Progress

## Completed Modules

### ✅ ConfReport Module
- **Status**: Complete
- **Files Migrated**: 
  - `src/Modules/Settings/ConfReport/Views/Index.php` - Added `prepareConfReportData()` method
  - `layouts/basic/modules/Settings/ConfReport/IndexContent.tpl` - Removed 5 function calls
- **Function Calls Removed**: 5
  - `getConfigurationLibrary()`
  - `getConfigurationValue()`
  - `getSystemInfo()`
  - `getHardwareInfo()`
  - `getPermissionsFiles()`

### ✅ Leads Module
- **Status**: Complete
- **Files Migrated**:
  - `src/Modules/Leads/Views/Detail.php` - Moved `toSafeHTML()` call to controller
  - `layouts/basic/modules/Leads/DetailViewHeaderTitle.tpl` - Removed function call
- **Function Calls Removed**: 1
  - `Util::toSafeHTML()`

### ✅ Contacts Module
- **Status**: Complete
- **Files Migrated**:
  - `src/Modules/Base/Views/RelatedList.php` - Added `prepareRelatedListLeftSideData()` method
  - `layouts/basic/modules/Contacts/RelatedListLeftSide.tpl` - Removed function calls
- **Function Calls Removed**: 3
  - `AppConfig::main('isActiveSendingMails')`
  - `Privileges::isPermitted('OSSMail')`
  - `AppConfig::module('ModTracker', 'UNREVIEWED_COUNT')`
- **Note**: Also benefits all other modules using RelatedListLeftSide

### ✅ Accounts Module
- **Status**: Complete
- **Files Migrated**:
  - `src/Modules/Accounts/Views/Detail.php` - Created, added `COUNT_IN_HIERARCHY` preparation
  - `src/Modules/Accounts/Dashboards/NewAccounts.php` - Added `prepareNewAccountsData()` method
  - `src/Modules/Accounts/Dashboards/NeglectedAccounts.php` - Added `prepareNeglectedAccountsData()` method
  - `src/Modules/Accounts/Views/AccountHierarchy.php` - Added `prepareAccountHierarchyData()` method
  - `src/Modules/Base/Views/RelatedList.php` - Added `AUTO_REFRESH_LIST_ON_CHANGE` preparation
  - Multiple template files updated
- **Function Calls Removed**: 8
  - `AppConfig::module('Accounts', 'COUNT_IN_HIERARCHY')`
  - `AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')`
  - Multiple `Privileges::isPermitted()` calls
  - `Util::formatDateDiffInStrings()`

### ✅ Documents Module
- **Status**: Complete
- **Files Migrated**:
  - `src/Modules/Documents/Views/ListView.php` - Created, added `prepareDocumentsListViewData()` method
  - `src/Modules/Documents/Views/MoveDocuments.php` - Added JSON encoding preparation
  - `src/Modules/Base/Views/RelatedList.php` - Added Documents and Calendar-specific data preparation
  - Multiple template files updated
- **Function Calls Removed**: 8
  - `Record::getFileIconByFileType()` (per record)
  - `AppConfig::module('ModTracker', 'SHOW_TIMELINE_IN_LISTVIEW')`
  - `AppConfig::module('ModTracker', 'UNREVIEWED_COUNT')`
  - `Json::encode()` calls
  - `Calendar\Models\Module::getComponentActivityStateLabel()`

## Base Module Improvements

### ✅ Base RelatedList Controller
- Added `prepareRelatedListLeftSideData()` method
- Handles OSSMail URLs, ModTracker config, Documents image classes, Calendar activity labels
- Benefits all modules using RelatedListLeftSide template

### ✅ Base ListView Controller
- Added `AUTO_REFRESH_LIST_ON_CHANGE` preparation
- Benefits all modules using ListViewContents template

## Migration Statistics

- **Total Modules Completed**: 5 (ConfReport, Leads, Contacts, Accounts, Documents)
- **Total Function Calls Removed**: ~25+
- **Base Improvements**: 2 (benefits all modules)
- **Remaining Function Calls**: ~959 (across all modules)

## Remaining Work

### High Priority Modules (Core CRM)
- Base module templates (many shared templates)
- Settings module (many templates)
- Calendar/Events modules
- Products/Services modules

### Medium Priority Modules
- OSS modules (OSSMail, OSSTimeControl, etc.)
- Inventory modules
- Sales modules
- Financial modules

### Low Priority Modules
- Custom modules
- Rarely used modules

## Migration Patterns Established

### Pattern 1: Simple Config Checks
**Before**: `{if AppConfig::module($MODULE, 'KEY')}`
**After**: Controller prepares `$viewer->assign('CONFIG_KEY', AppConfig::module($module, 'KEY'))`

### Pattern 2: Permission Checks
**Before**: `{if \App\Modules\Users\Models\Privileges::isPermitted($MODULE, 'Action', $id)}`
**After**: Controller prepares permissions array: `$viewer->assign('PERMISSIONS', $permissions)`

### Pattern 3: Per-Record Data in Loops
**Before**: `{foreach from=$RECORDS item=record}{assign var=DATA value=Model::getData($record)}{/foreach}`
**After**: Controller prepares data array: `$data[$recordId] = Model::getData($record)`

### Pattern 4: JSON Encoding
**Before**: `value={\App\Json::encode($DATA)}`
**After**: Controller prepares: `$viewer->assign('DATA_JSON', \App\Json::encode($data))`

### Pattern 5: Utility Functions
**Before**: `{\vtlib\Functions::textLength($text, 38)}`
**After**: Controller prepares: `$viewer->assign('TEXT_TRUNCATED', \vtlib\Functions::textLength($text, 38))`

## Next Steps

1. Continue migrating modules one by one
2. Focus on Base module templates (high impact)
3. Migrate Settings module templates
4. Continue with remaining modules systematically

## Notes

- Base module improvements benefit all modules automatically
- Some templates are shared across modules (RelatedListLeftSide, ListViewContents)
- Per-record data preparation requires loops in controllers
- Global config/permissions should be prepared once, not per record

