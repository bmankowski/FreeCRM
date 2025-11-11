# vtlib Usage in Smarty Templates

## Overview
- Audit scope: searched `/home/bmankowski/projects/FreeCRM/layouts/` for `{vtlib\...` and `\vtlib\...` occurrences in `.tpl` files.
- Goal: map each template dependency on `vtlib` helpers, identify typical use cases, and point toward modern replacements or controller-prepared data.
- Context: ongoing vtlib deprecation in favor of services under `App\` and `ModuleManagement\`; templates should stop invoking vtlib directly.

## Summary of Observed Patterns
- **Module & record metadata lookups** – retrieving module names/IDs, record types, and labels directly from vtlib inside templates.
- **Owner and assignment labels** – templates fallback to `vtlib\Functions::getOwnerRecordLabel()` or `getArrayFromValue()` when field models are incomplete.
- **Currency, limits, and numeric helpers** – vtlib functions supply currency lists, conversion rates, and upload size formatting.
- **Text sanitization and formatting** – vtlib helpers trim content or strip HTML before rendering snippets.
- **Legacy module instantiation** – some widgets still create `vtlib\Module` instances in the view layer to fetch field definitions.

## Use Case Details & Alternatives

### Module & Record Metadata
- **vtlib helpers used**: `vtlib\Functions::getModuleName()`, `vtlib\Functions::getModuleId()`, `\vtlib\Functions::getAllModules()`, `\vtlib\Functions::getCRMRecordType()`, `vtlib\Functions::getCRMRecordMetadata()`.
- **Template touchpoints**: `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/Widgets/IndexContent.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/Users/ColorsContent.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/Menu/types/HomeIcon.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/LangManagement/EditHelpIcon.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Calendar/Reminders.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/RelatedListContents.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Assets/dashboards/ExpiringSoldProductsContents.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/Comment.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Events/InviteRow.tpl`.
- **Modern options**:
  - Controllers can resolve module metadata via `App\Utils\ModuleUtils::getModuleName()` / `App\Utils\ModuleUtils::getModuleId()` and inject results into the view model. (Note: `App\Utils\ModuleUtils::getModuleName()` still proxies to vtlib internally, so long-term plan is to move logic into `ModuleManagement\Services\ModuleService`.)
  - Record-level lookups should use `App\Record::getType($id)`; when templates need additional information provide it via controller assignments (e.g., owner names, URLs) instead of calling vtlib.
  - For lists of modules provide pre-fetched data from `App\Utils\ModuleUtils::getAllEntityModuleInfo()` or the new `ModuleManagement\Models\Module` collection.
  - For `vtlib\Functions::getCRMRecordMetadata()` there is no modern helper yet; expose metadata through an upcoming record service and cache the payload at the controller level until the service exists.

#### Current Alternatives
- `vtlib\Functions::getModuleName()` → `App\Utils\ModuleUtils::getModuleName($tabId)` (uses vtlib internally today; replace with ModuleManagement service once available).
- `vtlib\Functions::getModuleId()` → `App\Utils\ModuleUtils::getModuleId($moduleName)`.
- `\vtlib\Functions::getAllModules()` → `App\Utils\ModuleUtils::getAllEntityModuleInfo()`; future: dedicated `ModuleManagement\Services\ModuleService::getAll()`.
- `vtlib\Functions::getCRMRecordMetadata()` → **Gap**: no PSR service yet; plan to introduce `ModuleManagement\Services\RecordMetadataService::getMetadata($id)`.

### Owner & Assignment Labels
- **vtlib helpers used**: `vtlib\Functions::getOwnerRecordLabel()`, `vtlib\Functions::getArrayFromValue()`.
- **Template touchpoints**: `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/uitypes/Owner.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/uitypes/SharedOwner.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/uitypes/SharedOwnerFieldSearchView.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/UnifiedSearchResults.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/RelatedActivities.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Calendar/ActivityStateModal.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/CustomView/IndexContents.tpl`.
- **Modern options**:
  - Prefer `App\Fields\Owner::getLabel()` and `App\Fields\Owner::getInstance(...)->getAccessibleUsers()` in controllers; pass ready-to-render owner labels. Shared owner arrays can use `App\Json::decode()` or typed value objects instead of `vtlib\Functions::getArrayFromValue()`.
  - For components where field models already exist (e.g., owner uitype), extend the PHP field model to expose fallback labels so templates no longer need raw helper calls.

#### Current Alternatives
- `vtlib\Functions::getOwnerRecordLabel()` → `App\Fields\Owner::getLabel($id)`.
- `vtlib\Functions::getArrayFromValue()` → **Gap**: no drop-in helper; controller should normalize data via `App\Json::decode()` (for JSON sources) or bespoke split logic; consider adding `App\Utils\ArrayHelper::fromDelimitedString()`.

### Currency & Upload Limits
- **vtlib helpers used**: `vtlib\Functions::getAllCurrency()`, `vtlib\Functions::getCurrencySymbolandRate()`.
- **Template touchpoints**: `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/inventoryfields/EditViewCurrency.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/EditViewInventory.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/DetailViewInventoryView.tpl`.
- **Modern options**:
  - Inventory controllers already leverage `App\Modules\Base\Models\InventoryField`; extend that layer with currency lists so templates receive resolved data instead of calling `vtlib`.
  - For currency symbol/rate lookups create a new helper under `App\Fields\Currency` or `ModuleManagement\Services\CurrencyService` that wraps current vtlib logic.

#### Current Alternatives
- `vtlib\Functions::getAllCurrency()` → **Gap**: no modern helper; propose `ModuleManagement\Services\CurrencyService::getAll($onlyActive)`.
- `vtlib\Functions::getCurrencySymbolandRate()` → **Gap**: add `App\Fields\Currency::getSymbolAndRate($currencyId)`.

### Text Sanitization & Formatting
- **vtlib helpers used**: `vtlib\Functions::removeHtmlTags()`, `vtlib\Functions::textLength()`, `vtlib\Functions::decimalTimeFormat()`.
- **Template touchpoints**: `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/widgets/WYSIWYG.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/dashboards/MailsListContents.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/OSSMail/MailActionBarRow.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Import/Import_Step4.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/SummaryWidgetsContent.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/RelatedTreeContent.tpl`, `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/OSSTimeControl/RelatedSummary.tpl`.
- **Modern options**:
  - Prefer `App\Purifier` methods or a new `App\Utils\Html::stripTags()` wrapper that accepts allowed tags and reuses Symfony/HTMLPurifier components.
  - For truncation, consider adding `App\Utils\Text::ellipsis($string, $limit, $appendDots)` so templates can call via assigned helpers or receive pre-truncated text from controllers.
  - Time formatting should move to a PHP-side formatter, e.g., extend `App\Fields\Time::formatDecimal()` and pass the formatted variants to the template.

#### Current Alternatives
- `vtlib\Functions::removeHtmlTags()` → **Gap**: add `App\Utils\Html::stripTags($html, array $allowedTags)`; until then call `App\Purifier::purifyHtml()` in controllers.
- `vtlib\Functions::textLength()` → **Gap**: add `App\Utils\Text::ellipsis($value, $length, $withDots)`.
- `vtlib\Functions::decimalTimeFormat()` → **Gap**: add `App\Fields\Time::formatDecimal($value)` returning detailed/time representations.

### Legacy Module Instantiation in Templates
- **vtlib helper used**: `vtlib\Module::getInstance()` inside `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/widgets/FastEdit.tpl`.
- **Modern options**: Controllers (or widget PHP classes) should construct `App\Modules\Base\Models\Module` / `ModuleManagement\Models\Module` instances and supply field data arrays in the `$WIDGET` payload. Templates should operate on prepared DTOs rather than invoking vtlib directly.

#### Current Alternatives
- `vtlib\Module::getInstance()` → Controller should call `App\Modules\Base\Models\Module::getInstance($moduleName)` (non-vtlib) and pass serialized field metadata to the template; requires widget refactor.
- `vtlib\Language::getAll()` (seen in settings templates) → **Gap**: expose `App\Language::getAll()` or move language list preparation into controller using existing translation infrastructure.

## Detailed Function Alternatives with Usage Examples

### Module & Record Metadata Functions

#### `vtlib\Functions::getModuleName($tabId)`

**Current Alternative:** `App\Utils\ModuleUtils::getModuleName($tabId)`

**Template Usage:**
```smarty
{vtlib\Functions::getModuleName($WIDGET['data']['relatedmodule'])}
```

**Controller Migration:**
```php
// In controller
$moduleId = $widget['data']['relatedmodule'];
$moduleName = \App\Utils\ModuleUtils::getModuleName($moduleId);
$viewer->assign('RELATED_MODULE_NAME', $moduleName);

// In template (after migration)
{$RELATED_MODULE_NAME}
```

**Note:** `App\Utils\ModuleUtils::getModuleName()` currently proxies to vtlib internally. Long-term: use `ModuleManagement\Services\ModuleService::getModuleName()` once implemented.

---

#### `vtlib\Functions::getModuleId($moduleName)`

**Current Alternative:** `App\Utils\ModuleUtils::getModuleId($moduleName)`

**Template Usage:**
```smarty
<input type="hidden" name="module" value="{vtlib\Functions::getModuleId('Home')}">
```

**Controller Migration:**
```php
// In controller
$moduleId = \App\Utils\ModuleUtils::getModuleId('Home');
$viewer->assign('HOME_MODULE_ID', $moduleId);

// In template (after migration)
<input type="hidden" name="module" value="{$HOME_MODULE_ID}">
```

---

#### `vtlib\Functions::getAllModules($includeInactive, $onlyActive, $tabId, $restricted, $presence)`

**Current Alternative:** `App\Utils\ModuleUtils::getAllEntityModuleInfo($sort = false)`

**Template Usage:**
```smarty
{foreach item=MODULE_INFO from=\vtlib\Functions::getAllModules(true, false, 0)}
```

**Controller Migration:**
```php
// In controller
$allModules = \App\Utils\ModuleUtils::getAllEntityModuleInfo(true); // true = sorted
$viewer->assign('ALL_MODULES', $allModules);

// In template (after migration)
{foreach item=MODULE_INFO from=$ALL_MODULES}
```

**Note:** `getAllEntityModuleInfo()` returns different structure. Filter active modules in controller:
```php
$allModules = \App\Utils\ModuleUtils::getAllEntityModuleInfo(true);
$activeModules = array_filter($allModules, function($module) {
    return isset($module['presence']) && $module['presence'] == 0;
});
$viewer->assign('ACTIVE_MODULES', $activeModules);
```

**Future:** `ModuleManagement\Services\ModuleService::getAll($filters)` will provide better API.

---

#### `vtlib\Functions::getCRMRecordMetadata($crmid)`

**Current Alternative:** **Gap - No modern replacement yet**

**Template Usage:**
```smarty
{assign var=INVITIE_RECORD value=vtlib\Functions::getCRMRecordMetadata($INVITIE['crmid'])}
```

**Temporary Workaround:**
```php
// In controller - manually fetch metadata
$inviteeIds = array_column($invitees, 'crmid');
$metadata = [];
foreach ($inviteeIds as $id) {
    $metadata[$id] = [
        'setype' => \App\Record::getType($id),
        'smownerid' => // fetch from vtiger_crmentity if needed
    ];
}
$viewer->assign('INVITEE_METADATA', $metadata);

// In template
{assign var=INVITIE_RECORD value=$INVITIE_METADATA[$INVITIE.crmid]}
```

**Future:** `ModuleManagement\Services\RecordMetadataService::getMetadata($id)` should be created.

---

### Owner & Assignment Functions

#### `vtlib\Functions::getOwnerRecordLabel($ownerId)`

**Current Alternative:** `App\Fields\Owner::getLabel($ownerId)`

**Template Usage:**
```smarty
{vtlib\Functions::getOwnerRecordLabel($FIELD_VALUE)}
```

**Controller Migration:**
```php
// In controller
$ownerIds = array_unique(array_filter([$record->get('smownerid'), $record->get('smcreatorid')]));
$ownerLabels = \App\Fields\Owner::getLabel($ownerIds); // Supports array input
$viewer->assign('OWNER_LABELS', $ownerLabels);
$viewer->assign('ASSIGNED_OWNER_LABEL', $ownerLabels[$record->get('smownerid')] ?? '');

// In template (after migration)
{$ASSIGNED_OWNER_LABEL}
```

**Batch Processing Example:**
```php
// For multiple records
$allOwnerIds = [];
foreach ($records as $record) {
    $allOwnerIds[] = $record->get('smownerid');
}
$allOwnerIds = array_unique(array_filter($allOwnerIds));
$allOwnerLabels = \App\Fields\Owner::getLabel($allOwnerIds);
$viewer->assign('ALL_OWNER_LABELS', $allOwnerLabels);
```

---

#### `vtlib\Functions::getArrayFromValue($value)`

**Current Alternative:** **Gap - No direct replacement**

**Template Usage:**
```smarty
{assign var=FIELD_VALUE value=vtlib\Functions::getArrayFromValue($FIELD_VALUE)}
```

**Migration Options:**

**Option 1: If value is JSON string:**
```php
// In controller
$fieldValue = $fieldModel->get('fieldvalue');
if (is_string($fieldValue) && !empty($fieldValue)) {
    $fieldArray = \App\Json::decode($fieldValue);
} else {
    $fieldArray = is_array($fieldValue) ? $fieldValue : [];
}
$viewer->assign('FIELD_VALUE_ARRAY', $fieldArray);

// In template
{assign var=FIELD_VALUE value=$FIELD_VALUE_ARRAY}
```

**Option 2: If value is comma-separated string:**
```php
// In controller
$fieldValue = $fieldModel->get('fieldvalue');
$fieldArray = !empty($fieldValue) ? explode(' |##| ', $fieldValue) : [];
$viewer->assign('FIELD_VALUE_ARRAY', $fieldArray);
```

**Option 3: Create helper (recommended):**
```php
// Create App\Utils\ArrayHelper::fromDelimitedString($value, $delimiter = ' |##| ')
namespace App\Utils;

class ArrayHelper
{
    public static function fromDelimitedString($value, $delimiter = ' |##| ')
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && strpos($value, $delimiter) !== false) {
            return explode($delimiter, $value);
        }
        return [$value];
    }
}

// Usage in controller
$fieldArray = \App\Utils\ArrayHelper::fromDelimitedString($fieldValue);
```

---

### Currency & Upload Limit Functions

#### `vtlib\Functions::getAllCurrency($onlyActive = false)`

**Current Alternative:** **Gap - No modern replacement yet**

**Template Usage:**
```smarty
{assign var=CURRENCIES value=vtlib\Functions::getAllCurrency(true)}
```

**Temporary Workaround:**
```php
// In controller - query directly
$query = (new \App\Db\Query())
    ->from('vtiger_currency_info')
    ->where(['deleted' => 0]);
if ($onlyActive) {
    $query->andWhere(['currency_status' => 'Active']);
}
$currencies = $query->all();
$viewer->assign('CURRENCIES', $currencies);

// In template
{assign var=CURRENCIES value=$CURRENCIES}
```

**Future:** Create `ModuleManagement\Services\CurrencyService::getAll($onlyActive)`:
```php
namespace App\ModuleManagement\Services;

class CurrencyService
{
    public static function getAll($onlyActive = false)
    {
        $query = (new \App\Db\Query())
            ->from('vtiger_currency_info')
            ->where(['deleted' => 0]);
        if ($onlyActive) {
            $query->andWhere(['currency_status' => 'Active']);
        }
        return $query->all();
    }
}
```

---

#### `vtlib\Functions::getCurrencySymbolandRate($currencyId)`

**Current Alternative:** **Gap - No modern replacement yet**

**Template Usage:**
```smarty
{assign var="CURRENCY_SYMBOLAND" value=vtlib\Functions::getCurrencySymbolandRate($CURRENCY)}
```

**Temporary Workaround:**
```php
// In controller
$currencyData = (new \App\Db\Query())
    ->select(['currency_symbol', 'conversion_rate', 'currency_code'])
    ->from('vtiger_currency_info')
    ->where(['id' => $currencyId, 'deleted' => 0])
    ->one();
$viewer->assign('CURRENCY_SYMBOLAND', $currencyData ?: ['symbol' => '', 'rate' => 1]);

// In template
{assign var="CURRENCY_SYMBOLAND" value=$CURRENCY_SYMBOLAND}
```

**Future:** Create `App\Fields\Currency::getSymbolAndRate($currencyId)`:
```php
namespace App\Fields;

class Currency
{
    public static function getSymbolAndRate($currencyId)
    {
        $data = (new \App\Db\Query())
            ->select(['currency_symbol', 'conversion_rate', 'currency_code'])
            ->from('vtiger_currency_info')
            ->where(['id' => $currencyId, 'deleted' => 0])
            ->one();
        return $data ?: ['symbol' => '', 'rate' => 1, 'code' => ''];
    }
}
```

---

### Text Sanitization & Formatting Functions

#### `vtlib\Functions::removeHtmlTags($tags, $html)`

**Current Alternative:** **Gap - No direct replacement**

**Template Usage:**
```smarty
{vtlib\Functions::removeHtmlTags(array('link', 'style', 'a', 'img', 'script', 'base'), $item->get('body'))}
```

**Temporary Workaround:**
```php
// In controller - use App\Purifier
$cleanBody = \App\Purifier::purifyHtml($item->get('body'));
// Then manually remove specific tags if needed
$cleanBody = preg_replace('/<(link|style|script|base)[^>]*>.*?<\/\1>/is', '', $cleanBody);
$viewer->assign('CLEAN_BODY', $cleanBody);

// In template
{$CLEAN_BODY}
```

**Future:** Create `App\Utils\Html::stripTags($html, array $tagsToRemove)`:
```php
namespace App\Utils;

class Html
{
    public static function stripTags($html, array $tagsToRemove)
    {
        // First purify
        $html = \App\Purifier::purifyHtml($html);
        
        // Remove specific tags and their content
        foreach ($tagsToRemove as $tag) {
            $html = preg_replace('/<' . preg_quote($tag, '/') . '[^>]*>.*?<\/' . preg_quote($tag, '/') . '>/is', '', $html);
            $html = preg_replace('/<' . preg_quote($tag, '/') . '[^>]*\/?>/i', '', $html);
        }
        
        return $html;
    }
}
```

---

#### `vtlib\Functions::textLength($text, $length = false, $addDots = true)`

**Current Alternative:** **Gap - No modern replacement yet**

**Template Usage:**
```smarty
{vtlib\Functions::textLength($RELATED_RECORD->get('rel_comment'), AppConfig::relation('COMMENT_MAX_LENGTH'))}
```

**Temporary Workaround:**
```php
// In controller
$comment = $relatedRecord->get('rel_comment');
$maxLength = \App\AppConfig::relation('COMMENT_MAX_LENGTH');
$truncated = mb_strlen($comment) > $maxLength 
    ? mb_substr($comment, 0, $maxLength) . '...' 
    : $comment;
$viewer->assign('TRUNCATED_COMMENT', $truncated);

// In template
{$TRUNCATED_COMMENT}
```

**Future:** Create `App\Utils\Text::ellipsis($value, $length, $withDots = true)`:
```php
namespace App\Utils;

class Text
{
    public static function ellipsis($value, $length = false, $withDots = true)
    {
        if ($length === false || mb_strlen($value) <= $length) {
            return $value;
        }
        
        $truncated = mb_substr($value, 0, $length);
        return $withDots ? $truncated . '...' : $truncated;
    }
}

// Usage in controller
$truncated = \App\Utils\Text::ellipsis($comment, $maxLength);
```

---

#### `vtlib\Functions::decimalTimeFormat($decTime)`

**Current Alternative:** **Gap - No modern replacement yet**

**Template Usage:**
```smarty
{assign var=TOTALTIME value=vtlib\Functions::decimalTimeFormat($RELATED_SUMMARY['totalTime'])}
```

**Temporary Workaround:**
```php
// In controller - use vtlib adapter temporarily
$totalTime = \App\ModuleManagement\Adapters\Functions::decimalTimeFormat($relatedSummary['totalTime']);
$viewer->assign('TOTAL_TIME', $totalTime);

// In template
{assign var=TOTALTIME value=$TOTAL_TIME}
```

**Future:** Create `App\Fields\Time::formatDecimal($value)`:
```php
namespace App\Fields;

class Time
{
    public static function formatDecimal($decTime)
    {
        $hours = floor($decTime);
        $minutes = round(($decTime - $hours) * 60);
        
        return [
            'short' => sprintf('%02d:%02d', $hours, $minutes),
            'long' => sprintf('%d hours %d minutes', $hours, $minutes),
            'hours' => $hours,
            'minutes' => $minutes,
            'decimal' => $decTime
        ];
    }
}

// Usage in controller
$timeFormatted = \App\Fields\Time::formatDecimal($totalTime);
$viewer->assign('TOTAL_TIME', $timeFormatted);
```

---

### Legacy Module & Language Functions

#### `vtlib\Module::getInstance($moduleName)`

**Current Alternative:** `App\Modules\Base\Models\Module::getInstance($moduleName)`

**Template Usage:**
```smarty
{assign var=MODULEINSTANCE value=vtlib\Module::getInstance($MODULE_NAME)}
```

**Controller Migration:**
```php
// In controller - prepare module instance and fields
$moduleInstance = \App\Modules\Base\Models\Module::getInstance($moduleName);
$fastEditFields = [];
foreach ($widget['data']['FastEdit'] as $fieldName) {
    $fieldModel = $moduleInstance->getField($fieldName);
    $fastEditFields[] = [
        'name' => $fieldModel->getName(),
        'label' => $fieldModel->get('label'),
        'fieldvalue' => $fieldModel->get('fieldvalue'),
        'picklistValues' => $fieldModel->getPicklistValues(),
        // ... other field data
    ];
}
$viewer->assign('FAST_EDIT_FIELDS', $fastEditFields);

// In template - iterate over prepared data
{foreach item=field from=$FAST_EDIT_FIELDS}
    {$field.label}
{/foreach}
```

**Note:** This requires widget refactoring to prepare field data in PHP rather than template.

---

#### `vtlib\Language::getAll()`

**Current Alternative:** **Gap - No modern replacement yet**

**Template Usage:**
```smarty
{foreach from=vtlib\Language::getAll() key=CODE item=NAME}
```

**Temporary Workaround:**
```php
// In controller - query languages directly
$languages = (new \App\Db\Query())
    ->select(['prefix', 'name', 'label'])
    ->from('vtiger_language')
    ->where(['active' => 1])
    ->orderBy('name')
    ->all();
$viewer->assign('LANGUAGES', $languages);

// In template
{foreach from=$LANGUAGES item=LANG}
    {$LANG.prefix} - {$LANG.name}
{/foreach}
```

**Future:** Create `App\Language::getAll()`:
```php
namespace App;

class Language
{
    public static function getAll($onlyActive = true)
    {
        $query = (new \App\Db\Query())
            ->select(['prefix', 'name', 'label'])
            ->from('vtiger_language');
        if ($onlyActive) {
            $query->where(['active' => 1]);
        }
        return $query->orderBy('name')->all();
    }
}
```

---

## Recommendations & Next Steps
- Prioritize removing `vtlib` calls from shared base templates (`uitypes`, `widgets`) because they cascade across modules.
- For each use case above, add new helper methods under `App\` or expand existing service classes, then update controllers to inject ready-to-render data.
- Create regression tests (UI/E2E or snapshot comparisons) for templates once vtlib usage is removed to ensure formatting stays consistent.
- Track remaining gaps (currency services, metadata providers, text utilities) in the vtlib refactoring plan and schedule their implementation under `ModuleManagement\Services`.

## Occurrence Matrix

| Template (absolute path) | vtlib call(s) | Purpose |
| --- | --- | --- |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/inventoryfields/EditViewCurrency.tpl` | `vtlib\Functions::getAllCurrency(true)` | Populate currency select list |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/dashboards/MailsListContents.tpl` | `vtlib\Functions::removeHtmlTags()` | Strip HTML from mail previews |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/Widgets/IndexContent.tpl` | `vtlib\Functions::getModuleName()` | Display related module label |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/Workflows/Tasks/VTCreateTodoTask.tpl` | `vtlib\Functions::getArrayFromValue()` | Parse multi-value workflow field |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/Users/ColorsContent.tpl` | `vtlib\Functions::getModuleName()` | Resolve module label in color grid |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/Menu/types/HomeIcon.tpl` | `vtlib\Functions::getModuleId()` | Hidden input with module id |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/PDF/Step2.tpl` | `vtlib\Language::getAll()` | Load language list |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/LangManagement/EditHelpIcon.tpl` | `\vtlib\Functions::getAllModules()` | Iterate modules for help icons |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Settings/CustomView/IndexContents.tpl` | `vtlib\Functions::getOwnerRecordLabel()` | Show creator label |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/OSSMail/MailActionBarRow.tpl` | `vtlib\Functions::textLength()` | Truncate link labels |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/OSSTimeControl/RelatedSummary.tpl` | `vtlib\Functions::decimalTimeFormat()` | Render total time summary |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Import/Import_Step4.tpl` | `\vtlib\Functions::textLength()` | Truncate imported values |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Events/DetailViewBlockView.tpl` | `vtlib\Functions::getCRMRecordMetadata()` | Resolve invitee metadata |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Events/InviteRow.tpl` | `vtlib\Functions::getCRMRecordMetadata()` | Resolve invitee metadata |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Calendar/Reminders.tpl` | `\vtlib\Functions::getCRMRecordType()` | Determine related module |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Calendar/ActivityStateModal.tpl` | `\vtlib\Functions::getCRMRecordType()`, `vtlib\Functions::getOwnerRecordLabel()` | Populate activity metadata |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/widgets/WYSIWYG.tpl` | `vtlib\Functions::removeHtmlTags()` | Sanitize widget content |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/widgets/FastEdit.tpl` | `vtlib\Module::getInstance()` | Fetch module field definitions |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/uitypes/SharedOwnerFieldSearchView.tpl` | `vtlib\Functions::getOwnerRecordLabel()` | Show shared owner labels |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/widgets/Basic.tpl` | `vtlib\Functions::getModuleName()` | Display widget title |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/uitypes/SharedOwner.tpl` | `vtlib\Functions::getArrayFromValue()`, `vtlib\Functions::getOwnerRecordLabel()` | Expand shared owner selections |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/uitypes/Owner.tpl` | `vtlib\Functions::getOwnerRecordLabel()` | Fallback owner label |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/UnifiedSearchResults.tpl` | `vtlib\Functions::getOwnerRecordLabel()` | Show owner in search results |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/RelatedListContents.tpl` | `vtlib\Functions::getCRMRecordType()` | Detect inventory row module |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/RelatedActivities.tpl` | `vtlib\Functions::getOwnerRecordLabel()` | Render owner labels in tooltip |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/SummaryWidgetsContent.tpl` | `vtlib\Functions::textLength()` | Truncate relation comment |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/RelatedTreeContent.tpl` | `vtlib\Functions::textLength()` | Truncate relation comment |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/EditViewInventoryItem.tpl` | `vtlib\Functions::getCRMRecordType()` | Determine referenced module |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/EditViewInventory.tpl` | `vtlib\Functions::getCurrencySymbolandRate()` | Display currency symbol/rate |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/DetailViewInventoryView.tpl` | `vtlib\Functions::getCurrencySymbolandRate()`, `vtlib\Functions::getCRMRecordType()` | Render inventory totals |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Assets/dashboards/ExpiringSoldProductsContents.tpl` | `vtlib\Functions::getCRMRecordType()` | Link to parent records |
| `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/Comment.tpl` | `vtlib\Functions::getCRMRecordType()` | Show comment relation |
