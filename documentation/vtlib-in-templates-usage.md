# vtlib Usage in Smarty Templates - Quick Reference

## Overview
Templates should stop invoking `vtlib` directly. Use controller-prepared data or modern `App\` / `ModuleManagement\` services instead.

## Quick Reference: vtlib → Modern Alternatives

### Module & Record Metadata
- `vtlib\Functions::getModuleName($tabId)` → `App\Utils\ModuleUtils::getModuleName($tabId)`
- `vtlib\Functions::getModuleId($moduleName)` → `App\Utils\ModuleUtils::getModuleId($moduleName)`
- `vtlib\Functions::getAllModules(...)` → `App\ModuleManagement\Adapters\Functions::getAllModules(...)`
- `vtlib\Functions::getCRMRecordType($id)` → `App\Record::getType($id)`
- `vtlib\Functions::getCRMRecordMetadata($id)` → Query `vtiger_crmentity` + `App\Record::getLabel()` in controller

### Owner & Assignment
- `vtlib\Functions::getOwnerRecordLabel($id)` → `App\Fields\Owner::getLabel($id)` *(supports arrays)*
- `vtlib\Functions::getArrayFromValue($value)` → Use `App\Json::decode()` for JSON or `explode(',', $value)` for delimited strings

### Currency
- `vtlib\Functions::getAllCurrency($onlyActive)` → `App\ModuleManagement\Adapters\Functions::getAllCurrency($onlyActive)`
- `vtlib\Functions::getCurrencySymbolandRate($id)` → `App\ModuleManagement\Adapters\Functions::getCurrencySymbolandRate($id)`

### Text Formatting
- `vtlib\Functions::removeHtmlTags($tags, $html)` → `App\ModuleManagement\Adapters\Functions::removeHtmlTags($tags, $html)`
- `vtlib\Functions::textLength($text, $length, $addDots)` → `App\ModuleManagement\Adapters\Functions::textLength($text, $length, $addDots)`
- `vtlib\Functions::decimalTimeFormat($decTime)` → `App\ModuleManagement\Adapters\Functions::decimalTimeFormat($decTime)`

### Legacy Module/Language
- `vtlib\Module::getInstance($name)` → `App\Modules\Base\Models\Module::getInstance($name)`
- `vtlib\Language::getAll()` → Query `vtiger_language` directly in controller and assign to viewer

## Migration Pattern

**Before (in template):**
```smarty
{vtlib\Functions::getModuleName($MODULE_ID)}
```

**After (controller):**
```php
$moduleName = \App\Utils\ModuleUtils::getModuleName($moduleId);
$viewer->assign('MODULE_NAME', $moduleName);
```

**After (template):**
```smarty
{$MODULE_NAME}
```

## Priority Areas
1. Base templates (`uitypes/`, `widgets/`) - cascades across modules
2. Shared components (`Base/RelatedListContents.tpl`, `Base/Comment.tpl`)
3. Settings templates (less critical but should be migrated)

## Status
All `vtlib` function calls have been migrated from Smarty templates. Templates now use:
- Modern `App\` namespace classes
- `App\ModuleManagement\Adapters\Functions` adapter functions
- Controller-prepared data assigned to viewer

## Future Improvements
While templates are migrated, consider creating dedicated service classes for:
- `ModuleManagement\Services\CurrencyService::getAll()`
- `App\Fields\Currency::getSymbolAndRate()`
- `App\Utils\Html::stripTags()`
- `App\Utils\Text::ellipsis()`
- `App\Fields\Time::formatDecimal()`
- `App\Language::getAll()`
- `ModuleManagement\Services\RecordMetadataService::getMetadata()`
