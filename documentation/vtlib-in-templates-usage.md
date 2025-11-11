# vtlib Usage in Smarty Templates - Quick Reference

## Overview
Templates should stop invoking `vtlib` directly. Use controller-prepared data or modern `App\` / `ModuleManagement\` services instead.

## Quick Reference: vtlib → Modern Alternatives


### Owner & Assignment
- `vtlib\Functions::getOwnerRecordLabel($id)` → `App\Fields\Owner::getLabel($id)` *(supports arrays)*
- `vtlib\Functions::getArrayFromValue($value)` → **Gap** - use `App\Json::decode()` for JSON or `explode(' |##| ', $value)` for delimited strings

### Currency
- `vtlib\Functions::getAllCurrency($onlyActive)` → **Gap** - query `vtiger_currency_info` directly in controller
- `vtlib\Functions::getCurrencySymbolandRate($id)` → **Gap** - query `vtiger_currency_info` directly in controller

### Text Formatting
- `vtlib\Functions::removeHtmlTags($tags, $html)` → **Gap** - use `App\Purifier::purifyHtml()` + manual tag removal in controller
- `vtlib\Functions::textLength($text, $length, $addDots)` → **Gap** - use `mb_substr()` in controller
- `vtlib\Functions::decimalTimeFormat($decTime)` → **Gap** - use `App\ModuleManagement\Adapters\Functions::decimalTimeFormat()` temporarily

### Legacy Module/Language
- `vtlib\Module::getInstance($name)` → `App\Modules\Base\Models\Module::getInstance($name)` *(prepare field data in controller)*
- `vtlib\Language::getAll()` → **Gap** - query `vtiger_language` directly in controller

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

## Known Gaps (Need Implementation)
- `ModuleManagement\Services\CurrencyService::getAll()`
- `App\Fields\Currency::getSymbolAndRate()`
- `App\Utils\Html::stripTags()`
- `App\Utils\Text::ellipsis()`
- `App\Fields\Time::formatDecimal()`
- `App\Language::getAll()`
- `ModuleManagement\Services\RecordMetadataService::getMetadata()`
