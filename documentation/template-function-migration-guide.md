# Template Function Migration Guide

## Overview

This guide outlines the strategy for migrating from function calls in Smarty templates to data preparation in controllers. This improves separation of concerns, testability, and maintainability.

## Why Migrate?

### Current Problems
- **Business logic in templates**: Templates call static methods, models, and utility functions directly
- **Hard to test**: Template logic cannot be unit tested
- **Poor separation**: Views contain controller logic
- **Performance**: Functions called multiple times in loops
- **Maintainability**: Logic scattered across templates

### Benefits After Migration
- ✅ **Clean MVC**: Controllers prepare data, templates display it
- ✅ **Testable**: Controller logic can be unit tested
- ✅ **Reusable**: Data preparation logic can be reused
- ✅ **Performance**: Data prepared once, cached if needed
- ✅ **Maintainable**: Logic centralized in controllers

## Common Patterns to Migrate

### Pattern 1: Static Method Calls in Templates

**Before:**
```smarty
{foreach from=\App\Modules\Settings\ConfReport\Models\Module::getConfigurationLibrary() key=key item=item}
    <tr>
        <td>{$key|t:$MODULE}</td>
        <td>{$item.status|t:$MODULE}</td>
    </tr>
{/foreach}
```

**After - Controller:**
```php
public function process(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    $qualifiedModuleName = $request->getModule(false);
    
    // Prepare data in controller
    $viewer->assign('CONFIGURATION_LIBRARY', 
        \App\Modules\Settings\ConfReport\Models\Module::getConfigurationLibrary());
    $viewer->assign('CONFIGURATION_VALUES', 
        \App\Modules\Settings\ConfReport\Models\Module::getConfigurationValue());
    $viewer->assign('SYSTEM_INFO', 
        \App\Modules\Settings\ConfReport\Models\Module::getSystemInfo());
    $viewer->assign('HARDWARE_INFO', 
        \App\Modules\Settings\ConfReport\Models\Module::getHardwareInfo());
    $viewer->assign('PERMISSIONS_FILES', 
        \App\Modules\Settings\ConfReport\Models\Module::getPermissionsFiles());
    
    $viewer->assign('MODULE', $qualifiedModuleName);
    
    if ($request->isAjax()) {
        $viewer->view('IndexContent.tpl', $qualifiedModuleName);
    } else {
        $viewer->view('Index.tpl', $qualifiedModuleName);
    }
}
```

**After - Template:**
```smarty
{foreach from=$CONFIGURATION_LIBRARY key=key item=item}
    <tr>
        <td>{$key|t:$MODULE}</td>
        <td>{$item.status|t:$MODULE}</td>
    </tr>
{/foreach}
```

### Pattern 2: Model Instance Creation

**Before:**
```smarty
{assign var=MODULEINSTANCE value=\App\Modules\Base\Models\Module::getInstance($MODULE_NAME)}
{assign var=FIELD value=\App\Modules\Base\Models\Field::getInstance($item,$MODULEINSTANCE)}
```

**After - Controller:**
```php
$moduleInstance = \App\Modules\Base\Models\Module::getInstance($moduleName);
$viewer->assign('MODULE_INSTANCE', $moduleInstance);

// If processing fields in a loop
$fields = [];
foreach ($items as $item) {
    $fields[] = \App\Modules\Base\Models\Field::getInstance($item, $moduleInstance);
}
$viewer->assign('FIELDS', $fields);
```

### Pattern 3: Permission Checks

**Before:**
```smarty
{if \App\Modules\Users\Models\Privileges::isPermitted('Calendar','CreateView')}
    <button>Create Event</button>
{/if}
```

**After - Controller:**
```php
$viewer->assign('CAN_CREATE_CALENDAR', 
    \App\Modules\Users\Models\Privileges::isPermitted('Calendar','CreateView'));
$viewer->assign('CAN_CREATE_COMMENTS', 
    \App\Modules\Users\Models\Privileges::isPermitted('ModComments','CreateView'));
```

**After - Template:**
```smarty
{if $CAN_CREATE_CALENDAR}
    <button>Create Event</button>
{/if}
```

### Pattern 4: JSON Encoding

**Before:**
```smarty
<input type="hidden" value='{\App\Json::encode($RELATED_SUMMARY['userTime'])}' />
```

**After - Controller:**
```php
$viewer->assign('RELATED_SUMMARY_JSON', 
    \App\Json::encode($relatedSummary['userTime']));
```

**After - Template:**
```smarty
<input type="hidden" value='{$RELATED_SUMMARY_JSON}' />
```

### Pattern 5: Utility Functions

**Before:**
```smarty
{\vtlib\Functions::textLength($RELATED['label'],38)}
{assign var=TOTALTIME value=\vtlib\Functions::decimalTimeFormat($RELATED_SUMMARY['totalTime'])}
```

**After - Controller:**
```php
$related['label_truncated'] = \vtlib\Functions::textLength($related['label'], 38);
$relatedSummary['totalTime_formatted'] = \vtlib\Functions::decimalTimeFormat($relatedSummary['totalTime']);
$viewer->assign('RELATED', $related);
$viewer->assign('RELATED_SUMMARY', $relatedSummary);
```

### Pattern 6: Configuration Values

**Before:**
```smarty
{assign var="DISCOUNTS_CONFIG" value=\App\Modules\Base\Models\Inventory::getDiscountsConfig()}
{assign var="TAXS_CONFIG" value=\App\Modules\Base\Models\Inventory::getTaxesConfig()}
```

**After - Controller:**
```php
$viewer->assign('DISCOUNTS_CONFIG', 
    \App\Modules\Base\Models\Inventory::getDiscountsConfig());
$viewer->assign('TAXS_CONFIG', 
    \App\Modules\Base\Models\Inventory::getTaxesConfig());
```

### Pattern 7: Conditional Data Loading

**Before:**
```smarty
{if !$MODULE_MODEL}
    {assign var=MODULE_MODEL value=\App\Modules\Base\Models\Module::getInstance($SOURCE_MODULE)}
{/if}
```

**After - Controller:**
```php
// Always prepare in controller, no need for conditional in template
$moduleModel = \App\Modules\Base\Models\Module::getInstance($sourceModule);
$viewer->assign('MODULE_MODEL', $moduleModel);
```

## Migration Process

### Step 1: Identify Function Calls

Scan templates for common patterns:
- `\App\Modules\...::getInstance()`
- `\App\Modules\...::getAll()`
- `\App\Json::encode()`
- `\App\Modules\Users\Models\Privileges::isPermitted()`
- `\vtlib\Functions::`
- `AppConfig::search()`
- `Model::getInstance()`
- Static method calls in `{assign}` or `{foreach}`

### Step 2: Analyze Dependencies

For each function call, identify:
- What data does it need? (request parameters, assigned variables)
- What does it return?
- Is it called multiple times?
- Does it have side effects?

### Step 3: Move to Controller

1. **Find the controller** for the template
2. **Add data preparation** in `process()` or `preProcess()` method
3. **Assign to viewer** using descriptive variable names
4. **Update template** to use assigned variables

### Step 4: Test

1. **Visual test**: Load page in browser, verify it looks correct
2. **Functional test**: Test all interactions
3. **Check logs**: Review `cache/logs/system.log` for errors
4. **Performance**: Verify no performance regressions

### Step 5: Refactor Further (Optional)

- Extract common data preparation to helper methods
- Create view helper classes for reusable logic
- Add computed properties to models

## Migration Checklist

For each template file:

- [ ] Identify all function calls
- [ ] List dependencies for each call
- [ ] Move data preparation to controller
- [ ] Update template to use assigned variables
- [ ] Remove unused `{assign}` statements
- [ ] Test in browser
- [ ] Check system.log for errors
- [ ] Verify AJAX requests still work (if applicable)
- [ ] Update related templates if data is shared

## Best Practices

### 1. Use Descriptive Variable Names

**Good:**
```php
$viewer->assign('CONFIGURATION_LIBRARY', $library);
$viewer->assign('CAN_CREATE_RECORD', $canCreate);
```

**Bad:**
```php
$viewer->assign('DATA', $library);
$viewer->assign('FLAG', $canCreate);
```

### 2. Group Related Assignments

```php
// Configuration
$viewer->assign('DISCOUNTS_CONFIG', $discountsConfig);
$viewer->assign('TAXS_CONFIG', $taxesConfig);
$viewer->assign('BASE_CURRENCY', $baseCurrency);

// Permissions
$viewer->assign('CAN_CREATE', $canCreate);
$viewer->assign('CAN_EDIT', $canEdit);
```

### 3. Prepare Collections Once

**Good:**
```php
$fields = [];
foreach ($items as $item) {
    $fields[] = Field::getInstance($item, $moduleInstance);
}
$viewer->assign('FIELDS', $fields);
```

**Bad:**
```php
// Template calls Field::getInstance() in loop - inefficient
```

### 4. Handle Edge Cases in Controller

```php
$configurationLibrary = [];
try {
    $configurationLibrary = Module::getConfigurationLibrary();
} catch (\Exception $e) {
    \App\Debugger::log('Failed to load configuration library: ' . $e->getMessage());
}
$viewer->assign('CONFIGURATION_LIBRARY', $configurationLibrary);
```

### 5. Document Complex Preparations

```php
/**
 * Prepare configuration report data
 * Loads library status, configuration values, system info, and file permissions
 */
protected function prepareConfReportData(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    
    // Load all configuration data
    $viewer->assign('CONFIGURATION_LIBRARY', 
        \App\Modules\Settings\ConfReport\Models\Module::getConfigurationLibrary());
    // ... more assignments
}
```

## Example: Complete Migration

### Before: ConfReport/IndexContent.tpl

```smarty
{foreach from=\App\Modules\Settings\ConfReport\Models\Module::getConfigurationLibrary() key=key item=item}
    <tr>
        <td>{$key|t:$MODULE}</td>
        <td>{$item.status|t:$MODULE}</td>
    </tr>
{/foreach}
```

### After: ConfReport/Views/Index.php

```php
public function process(\App\Http\Vtiger_Request $request)
{
    \App\Cache\Cache::clear();
    $viewer = $this->getViewer($request);
    $qualifiedModuleName = $request->getModule(false);
    
    // Prepare all data in controller
    $this->prepareConfReportData($viewer, $qualifiedModuleName);
    
    $viewer->assign('CCURL', 'index.php?module=OSSMail&view=CheckConfig');
    $viewer->assign('MODULE', $qualifiedModuleName);
    
    if ($request->isAjax()) {
        $viewer->view('IndexContent.tpl', $qualifiedModuleName);
    } else {
        $viewer->view('Index.tpl', $qualifiedModuleName);
    }
}

protected function prepareConfReportData($viewer, $module)
{
    $viewer->assign('CONFIGURATION_LIBRARY', 
        \App\Modules\Settings\ConfReport\Models\Module::getConfigurationLibrary());
    $viewer->assign('CONFIGURATION_VALUES', 
        \App\Modules\Settings\ConfReport\Models\Module::getConfigurationValue());
    $viewer->assign('SYSTEM_INFO', 
        \App\Modules\Settings\ConfReport\Models\Module::getSystemInfo());
    $viewer->assign('HARDWARE_INFO', 
        \App\Modules\Settings\ConfReport\Models\Module::getHardwareInfo());
    $viewer->assign('PERMISSIONS_FILES', 
        \App\Modules\Settings\ConfReport\Models\Module::getPermissionsFiles());
}
```

### After: ConfReport/IndexContent.tpl

```smarty
{foreach from=$CONFIGURATION_LIBRARY key=key item=item}
    <tr>
        <td>{$key|t:$MODULE}</td>
        <td>{$item.status|t:$MODULE}</td>
    </tr>
{/foreach}
```

## Tools and Scripts

### Finding Function Calls

Use grep to find patterns:
```bash
grep -r "::getInstance\|::getAll\|::isPermitted\|AppConfig::\|Json::encode" layouts/basic/modules/
```

### Analyzing Templates

See `documentation/refactoring-tpl-to-be-mvc-compliant.md` for automated analysis scripts.

## Migration Priority

1. **High Priority**: Frequently used templates (Base module, ListView, DetailView)
2. **Medium Priority**: Settings modules, commonly accessed views
3. **Low Priority**: Rarely used templates, admin-only views

## Common Pitfalls

### 1. Forgetting AJAX Requests

If a template is used for AJAX, ensure data is prepared in both full page and AJAX flows:
```php
if ($request->isAjax()) {
    $this->prepareAjaxData($viewer);
    $viewer->view('Content.tpl', $module);
} else {
    $this->prepareFullPageData($viewer);
    $viewer->view('Index.tpl', $module);
}
```

### 2. Breaking Conditional Logic

**Before:**
```smarty
{if !$MODULE_MODEL}
    {assign var=MODULE_MODEL value=Module::getInstance($MODULE)}
{/if}
```

**After:**
```php
// Always prepare, don't rely on template conditionals
$moduleModel = Module::getInstance($module);
$viewer->assign('MODULE_MODEL', $moduleModel);
```

### 3. Performance in Loops

If data preparation is expensive and used in loops, prepare once:
```php
// Prepare once
$allFields = [];
foreach ($items as $item) {
    $allFields[$item['id']] = Field::getInstance($item, $moduleInstance);
}
$viewer->assign('ALL_FIELDS', $allFields);

// Template uses pre-prepared data
{foreach from=$items item=item}
    {assign var=FIELD value=$ALL_FIELDS[$item.id]}
{/foreach}
```

## Next Steps

1. Start with one module (e.g., ConfReport)
2. Migrate all templates in that module
3. Test thoroughly
4. Move to next module
5. Document any patterns specific to your codebase

## Related Documentation

- `refactoring-tpl-to-be-mvc-compliant.md` - Detailed refactoring strategies
- `template-blocks-developer-guide.md` - Template structure guide
- Architecture rules in `.cursor/rules/architecture.mdc`

