# Refactoring TPL Templates to be MVC-Compliant

## Table of Contents
1. [Overview](#overview)
2. [MVC Pattern Principles](#mvc-pattern-principles)
3. [Common MVC Violations in TPL Files](#common-mvc-violations-in-tpl-files)
4. [Refactoring Strategies](#refactoring-strategies)
5. [Automated Refactoring Methods](#automated-refactoring-methods)
6. [Implementation Roadmap](#implementation-roadmap)

---

## Overview

This document analyzes MVC (Model-View-Controller) violations found in Smarty template (TPL) files in the FreeCRM project and provides strategies for automated refactoring to achieve better separation of concerns.

### What is MVC Compliance in Templates?

In MVC architecture, **Views (templates) should only be responsible for presentation logic**:
- ✅ Displaying data passed from the controller
- ✅ Simple formatting (e.g., applying CSS classes)
- ✅ Iterating over collections
- ✅ Basic conditional rendering based on provided data

Views should **NOT**:
- ❌ Make direct calls to models
- ❌ Perform business logic calculations
- ❌ Access configuration directly
- ❌ Check permissions
- ❌ Instantiate objects
- ❌ Transform or process data

---

## MVC Pattern Principles

### Proper Data Flow
```
User Request → Controller → Model → Controller → View → User
```

### Responsibilities

#### Controller
- Handle user input
- Coordinate model and view
- Fetch data from models
- Prepare data for views
- Handle business logic decisions

#### Model
- Represent business entities
- Handle data access
- Perform calculations
- Validate data
- Enforce business rules

#### View
- Receive prepared data from controller
- Display data using HTML/CSS
- No direct model access
- No business logic
- Minimal presentation logic only

---

## Common MVC Violations in TPL Files

### 1. Direct Model Static Method Calls

#### ❌ Violation Example
```smarty
{* File: install/tpl/Step3.tpl, Line 49 *}
{foreach from=Settings_ConfReport_Module_Model::getConfigurationLibrary() key=key item=item}
```

```smarty
{* File: layouts/basic/modules/Vtiger/widgets/Basic.tpl, Line 64 *}
{assign var=RELATED_MODULE_MODEL value=Vtiger_Module_Model::getInstance($WIDGET['data']['relatedmodule'])}
```

```smarty
{* File: layouts/basic/modules/Vtiger/Header.tpl, Line 72 *}
{assign var="ANNOUNCEMENTS" value=Vtiger_Module_Model::getInstance('Announcements')}
```

#### Why It's a Violation
- Views directly access model layer
- Business logic leaks into presentation
- Violates single responsibility principle
- Makes templates hard to test
- Creates tight coupling

#### ✅ Correct Approach
**Controller prepares data:**
```php
// In controller
$viewer->assign('CONFIGURATION_LIBRARY', Settings_ConfReport_Module_Model::getConfigurationLibrary());
$viewer->assign('RELATED_MODULE_MODEL', Vtiger_Module_Model::getInstance($relatedModule));
$viewer->assign('ANNOUNCEMENTS', Vtiger_Module_Model::getInstance('Announcements'));
```

**Template only displays:**
```smarty
{foreach from=$CONFIGURATION_LIBRARY key=key item=item}
```

---

### 2. Configuration Access

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/Vtiger/Header.tpl, Lines 59-61 *}
<input type="hidden" id="gsAutocomplete" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE')}" />
<input type="hidden" id="gsMinLength" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH')}" />
<input type="hidden" id="gsAmountResponse" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_LIMIT')}" />
```

```smarty
{* File: layouts/basic/modules/Vtiger/ListViewContents.tpl, Line 20 *}
<input type="hidden" id="autoRefreshListOnChange" value="{AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')}" />
```

#### Why It's a Violation
- Direct access to configuration layer
- Templates shouldn't know about app structure
- Hard to mock in tests
- Configuration logic belongs in controller

#### ✅ Correct Approach
**Controller:**
```php
$viewer->assign('CONFIG', [
    'gsAutocomplete' => AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE'),
    'gsMinLength' => AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH'),
    'gsAmountResponse' => AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_LIMIT'),
    'autoRefreshListOnChange' => AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')
]);
```

**Template:**
```smarty
<input type="hidden" id="gsAutocomplete" value="{$CONFIG.gsAutocomplete}" />
<input type="hidden" id="gsMinLength" value="{$CONFIG.gsMinLength}" />
```

---

### 3. Permission Checks

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/Notification/NotificationConfig.tpl, Lines 5, 59 *}
{assign var="IS_PERMITTED" value=\App\Privilege::isPermitted($MODULE, 'ReceivingMailNotifications')}

{if $CRON_ACTIVE && \App\Privilege::isPermitted($MODULE, 'ReceivingMailNotifications')}
```

```smarty
{* File: layouts/basic/modules/Vtiger/menu/CustomFilter.tpl, Line 4 *}
{if (isset($ACTIVE_MODULES[$MENU.mod]) && $ACTIVE_MODULES[$MENU.mod]) AND ($PRIVILEGESMODEL->isAdminUser() || $PRIVILEGESMODEL->hasGlobalReadPermission() || $PRIVILEGESMODEL->hasModulePermission($MENU['tabid']) ) }
```

#### Why It's a Violation
- Authorization logic in view
- Security-critical code in presentation layer
- Controller should determine what to show
- Hard to audit security

#### ✅ Correct Approach
**Controller:**
```php
$viewer->assign('IS_PERMITTED_MAIL_NOTIFICATIONS', \App\Privilege::isPermitted($module, 'ReceivingMailNotifications'));
$viewer->assign('SHOW_MENU_ITEM', $this->shouldShowMenuItem($menu, $privilegesModel));
```

**Template:**
```smarty
{if $CRON_ACTIVE && $IS_PERMITTED_MAIL_NOTIFICATIONS}
```

---

### 4. Debugging and Development Utilities

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/Vtiger/Header.tpl, Lines 41-43 *}
{if \App\Debugger::isDebugBar()}
    {\App\Debugger::getDebugBar()->getJavascriptRenderer()->renderHead()}
{/if}
```

#### Why It's a Violation
- Dev tools directly accessed in view
- Environment-specific logic in template
- Controller should handle conditional rendering

#### ✅ Correct Approach
**Controller:**
```php
$debugBarHtml = '';
if (\App\Debugger::isDebugBar()) {
    $debugBarHtml = \App\Debugger::getDebugBar()->getJavascriptRenderer()->renderHead();
}
$viewer->assign('DEBUG_BAR_HTML', $debugBarHtml);
```

**Template:**
```smarty
{if $DEBUG_BAR_HTML}
    {$DEBUG_BAR_HTML}
{/if}
```

---

### 5. Utility and Helper Function Calls

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/CustomView/EditView.tpl, Line 23 *}
<input type="hidden" name="date_filters" data-value='{Vtiger_Util_Helper::toSafeHTML(\App\Json::encode($DATE_FILTERS))}' />
```

```smarty
{* File: layouts/basic/modules/Notification/NotificationConfig.tpl, Line 73 *}
<a href="#" class="infoPopover pull-left" data-content="{Vtiger_Util_Helper::toSafeHTML($POPOVER_CONTENT)}">&nbsp;
```

```smarty
{* File: layouts/basic/modules/Vtiger/widgets/Basic.tpl, Line 4 *}
<div class="widgetContainer_{$key}" data-url="{Vtiger_Util_Helper::toSafeHTML($WIDGET['url'])}" data-name="{$WIDGET['label']}" data-type="{$WIDGET['type']}">
```

#### Why It's a Violation
- Data transformation in view
- Business logic for sanitization belongs in controller
- Repeated transformation calls inefficient

#### ✅ Correct Approach
**Controller:**
```php
$viewer->assign('DATE_FILTERS_JSON', Vtiger_Util_Helper::toSafeHTML(\App\Json::encode($dateFilters)));
$viewer->assign('POPOVER_CONTENT_SAFE', Vtiger_Util_Helper::toSafeHTML($popoverContent));
$viewer->assign('WIDGET_URL_SAFE', Vtiger_Util_Helper::toSafeHTML($widget['url']));
```

**Template:**
```smarty
<input type="hidden" name="date_filters" data-value='{$DATE_FILTERS_JSON}' />
```

---

### 6. JSON Encoding in Templates

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/Vtiger/Header.tpl, Lines 46, 65 *}
<div id="js_strings" class="hide noprint">{\App\Json::encode($LANGUAGE_STRINGS)}</div>
<input type="hidden" id="sounds" value="{Vtiger_Util_Helper::toSafeHTML(\App\Json::encode(AppConfig::sounds()))}"/>
```

```smarty
{* File: layouts/basic/modules/CustomView/EditView.tpl, Lines 116-117 *}
<input type="hidden" name="columnslist" value='{\App\Json::encode($SELECTED_FIELDS)}' />
<input id="mandatoryFieldsList" type="hidden" value='{\App\Json::encode($MANDATORY_FIELDS)}' />
```

```smarty
{* File: layouts/basic/modules/Vtiger/widgets/Basic.tpl, Lines 33, 41, 69 *}
{if $RESTRICTIONS_FIELD}data-rf='{\App\Json::encode($RESTRICTIONS_FIELD)}'{/if}
{if $AUTOCOMPLETE_FIELD} data-acf='{\App\Json::encode($AUTOCOMPLETE_FIELD)}'{/if}
{assign var="FIELD_INFO" value=\App\Json::encode($FIELD_MODEL->getFieldInfo())}
```

#### Why It's a Violation
- Data serialization is business logic
- Performance overhead in template
- Not a presentation concern
- Should be prepared in controller

#### ✅ Correct Approach
**Controller:**
```php
$viewer->assign('LANGUAGE_STRINGS_JSON', \App\Json::encode($languageStrings));
$viewer->assign('SOUNDS_JSON', Vtiger_Util_Helper::toSafeHTML(\App\Json::encode(AppConfig::sounds())));
$viewer->assign('SELECTED_FIELDS_JSON', \App\Json::encode($selectedFields));
$viewer->assign('RESTRICTIONS_FIELD_JSON', \App\Json::encode($restrictionsField));
```

**Template:**
```smarty
<div id="js_strings" class="hide noprint">{$LANGUAGE_STRINGS_JSON}</div>
<input type="hidden" id="sounds" value="{$SOUNDS_JSON}"/>
<input type="hidden" name="columnslist" value='{$SELECTED_FIELDS_JSON}' />
```

---

### 7. Business Logic Operations

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/CustomView/EditView.tpl, Lines 78, 85, 102 *}
{assign var=MANDATORY_FIELDS value=array()}
{if $FIELD_MODEL->isMandatory()}
    {array_push($MANDATORY_FIELDS, $FIELD_MODEL->getCustomViewColumnName())}
{/if}
```

```smarty
{* File: layouts/basic/modules/OSSPasswords/RelatedListContents.tpl, Line 72 *}
{if array_key_exists('password',$RELATED_HEADERS)}
```

```smarty
{* File: layouts/basic/modules/Vtiger/ListViewContents.tpl, Line 94 *}
{assign var="LISTVIEW_HEADER_COUNT" value=count($LISTVIEW_HEADERS)}
```

#### Why It's a Violation
- Array manipulation is business logic
- Building data structures in view
- Controller should prepare complete data
- Harder to debug and test

#### ✅ Correct Approach
**Controller:**
```php
$mandatoryFields = [];
foreach ($recordStructure as $blockFields) {
    foreach ($blockFields as $fieldModel) {
        if ($fieldModel->isMandatory()) {
            $mandatoryFields[] = $fieldModel->getCustomViewColumnName();
        }
    }
}
$viewer->assign('MANDATORY_FIELDS', $mandatoryFields);
$viewer->assign('HAS_PASSWORD_FIELD', array_key_exists('password', $relatedHeaders));
$viewer->assign('LISTVIEW_HEADER_COUNT', count($listviewHeaders));
```

**Template:**
```smarty
{* MANDATORY_FIELDS is already prepared *}
<input id="mandatoryFieldsList" type="hidden" value='{$MANDATORY_FIELDS}' />

{if $HAS_PASSWORD_FIELD}
    {* ... *}
{/if}
```

---

### 8. Field Value Transformations

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/OSSPasswords/RelatedListContents.tpl, Lines 86, 100-101 *}
{if $RELATED_HEADERNAME eq 'password'}
    {str_repeat('*', 10)}
{/if}

<td class="medium">{Vtiger_Datetime_UIType::getDisplayDateTimeValue($RELATED_RECORD->get('rel_created_time'))}</td>
<td class="medium">{\App\Fields\Owner::getLabel($RELATED_RECORD->get('rel_created_user'))}</td>
```

#### Why It's a Violation
- Data transformation belongs in model/controller
- Field formatting should be pre-processed
- View shouldn't know about field types

#### ✅ Correct Approach
**Model or Controller:**
```php
// In model or controller
foreach ($relatedRecords as $record) {
    if ($record->has('password')) {
        $record->set('password_display', str_repeat('*', 10));
    }
    if ($record->has('rel_created_time')) {
        $record->set('rel_created_time_display', 
            Vtiger_Datetime_UIType::getDisplayDateTimeValue($record->get('rel_created_time')));
    }
    if ($record->has('rel_created_user')) {
        $record->set('rel_created_user_display', 
            \App\Fields\Owner::getLabel($record->get('rel_created_user')));
    }
}
```

**Template:**
```smarty
<td class="medium">{$RELATED_RECORD->get('password_display')}</td>
<td class="medium">{$RELATED_RECORD->get('rel_created_time_display')}</td>
<td class="medium">{$RELATED_RECORD->get('rel_created_user_display')}</td>
```

---

### 9. Model Instantiation and Method Chaining

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/Vtiger/widgets/Basic.tpl, Lines 28-30 *}
{assign var=VRM value=Vtiger_Record_Model::getInstanceById($RECORD->getId(), $MODULE_NAME)}
{assign var=VRMM value=Vtiger_RelationListView_Model::getInstance($VRM, $WIDGET['data']['relatedmodule'])}
{assign var=RELATIONMODEL value=$VRMM->getRelationModel()}
```

```smarty
{* File: layouts/basic/modules/Vtiger/menu/CustomFilter.tpl, Line 5 *}
{assign var=ICON value=Vtiger_Menu_Model::getMenuIcon($MENU, $MENU['name']|t:$MENU_MODULE)}
```

#### Why It's a Violation
- Object instantiation in view
- Complex model interactions
- Violates dependency injection
- Makes testing impossible

#### ✅ Correct Approach
**Controller:**
```php
$recordModel = Vtiger_Record_Model::getInstanceById($record->getId(), $moduleName);
$relationListViewModel = Vtiger_RelationListView_Model::getInstance($recordModel, $widget['data']['relatedmodule']);
$relationModel = $relationListViewModel->getRelationModel();

$viewer->assign('RELATION_MODEL', $relationModel);
$viewer->assign('MENU_ICON', Vtiger_Menu_Model::getMenuIcon($menu, $menuName));
```

**Template:**
```smarty
{* Use pre-instantiated objects *}
{assign var=RESTRICTIONS_FIELD value=$RELATION_MODEL->getRestrictionsPopupField($VRM)}
```

---

### 10. Complex Conditional Logic with Model Access

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/Vtiger/menu/CustomFilter.tpl, Line 4 *}
{if (isset($ACTIVE_MODULES[$MENU.mod]) && $ACTIVE_MODULES[$MENU.mod]) AND 
    ($PRIVILEGESMODEL->isAdminUser() || 
     $PRIVILEGESMODEL->hasGlobalReadPermission() || 
     $PRIVILEGESMODEL->hasModulePermission($MENU['tabid']) ) }
```

#### Why It's a Violation
- Complex business rules in template
- Multiple model method calls
- Hard to understand and maintain
- Business logic scattered across views

#### ✅ Correct Approach
**Controller or Model:**
```php
// Create a helper method
protected function canShowMenuItem($menu, $privilegesModel, $activeModules)
{
    if (!isset($activeModules[$menu['mod']]) || !$activeModules[$menu['mod']]) {
        return false;
    }
    
    return $privilegesModel->isAdminUser() 
        || $privilegesModel->hasGlobalReadPermission() 
        || $privilegesModel->hasModulePermission($menu['tabid']);
}

// In controller
foreach ($menuItems as &$menu) {
    $menu['canShow'] = $this->canShowMenuItem($menu, $privilegesModel, $activeModules);
}
```

**Template:**
```smarty
{if $MENU.canShow}
    <li class="menuCustomFilter">
        {* ... *}
    </li>
{/if}
```

---

### 11. Function Namespace Calls

#### ❌ Violation Example
```smarty
{* File: layouts/basic/modules/Vtiger/widgets/Basic.tpl, Line 12 *}
{vtlib\Functions::getModuleName($WIDGET['data']['relatedmodule'])|t:vtlib\Functions::getModuleName($WIDGET['data']['relatedmodule'])}
```

#### Why It's a Violation
- Direct static method calls to utility functions
- Same transformation done twice
- Not a view responsibility

#### ✅ Correct Approach
**Controller:**
```php
$widget['moduleName'] = \vtlib\Functions::getModuleName($widget['data']['relatedmodule']);
```

**Template:**
```smarty
{$WIDGET['moduleName']|t:$WIDGET['moduleName']}
```

---

## Refactoring Strategies

### Strategy 1: Move Data Preparation to Controllers

**Pattern:**
```php
// Before: Template does work
// {foreach from=Model::getData() item=item}

// After: Controller prepares data
class MyController extends Vtiger_Basic_Controller {
    public function process(Vtiger_Request $request) {
        $data = Model::getData();
        $viewer = $this->getViewer($request);
        $viewer->assign('DATA', $data);
        $viewer->view('MyView.tpl', $module);
    }
}
```

### Strategy 2: Create View Helper Methods

When the same transformation appears in multiple templates:

```php
// Create a helper class
class ViewDataHelper {
    public static function prepareConfigForView() {
        return [
            'gsAutocomplete' => AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE'),
            'gsMinLength' => AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH'),
            // ... more config
        ];
    }
    
    public static function prepareWidgetData($widget, $record, $module) {
        $recordModel = Vtiger_Record_Model::getInstanceById($record->getId(), $module);
        $relationListViewModel = Vtiger_RelationListView_Model::getInstance(
            $recordModel, 
            $widget['data']['relatedmodule']
        );
        
        return [
            'relationModel' => $relationListViewModel->getRelationModel(),
            'restrictionsField' => $relationListViewModel->getRelationModel()->getRestrictionsPopupField($recordModel),
            // ... more widget data
        ];
    }
}
```

### Strategy 3: Extend Models with Display Methods

Add display-ready properties to models:

```php
// In model
class Vtiger_Record_Model extends Vtiger_Base_Model {
    public function getDisplayData() {
        $data = $this->getData();
        
        // Add display-formatted versions
        if ($this->has('rel_created_time')) {
            $data['rel_created_time_display'] = 
                Vtiger_Datetime_UIType::getDisplayDateTimeValue($this->get('rel_created_time'));
        }
        
        if ($this->has('rel_created_user')) {
            $data['rel_created_user_display'] = 
                \App\Fields\Owner::getLabel($this->get('rel_created_user'));
        }
        
        return $data;
    }
}
```

### Strategy 4: Pre-process Collections

Instead of processing in loops within templates:

```php
// Controller
$records = $model->getRecords();
$processedRecords = [];

foreach ($records as $record) {
    $processedRecords[] = [
        'id' => $record->getId(),
        'name' => $record->getName(),
        'displayUrl' => $record->getDetailViewUrl(),
        'isViewable' => $record->isViewable(),
        'formattedDate' => $this->formatDate($record->get('date')),
        // All transformations done here
    ];
}

$viewer->assign('RECORDS', $processedRecords);
```

### Strategy 5: Use Computed Properties

Add computed properties to models:

```php
class CustomView_Model extends Vtiger_Base_Model {
    public function getJsonEncodedSelectedFields() {
        return \App\Json::encode($this->getSelectedFields());
    }
    
    public function getSafeDescription() {
        return Vtiger_Util_Helper::toSafeHTML($this->get('description'));
    }
    
    public function getMandatoryFieldsList() {
        $mandatoryFields = [];
        foreach ($this->getRecordStructure() as $blockFields) {
            foreach ($blockFields as $fieldModel) {
                if ($fieldModel->isMandatory()) {
                    $mandatoryFields[] = $fieldModel->getCustomViewColumnName();
                }
            }
        }
        return $mandatoryFields;
    }
}
```

---

## Automated Refactoring Methods

### Method 1: Static Code Analysis Script

Create a script to detect violations:

```php
<?php
/**
 * Scan TPL files for MVC violations
 * Usage: php scripts/analyze_tpl_violations.php
 */

class TplMvcAnalyzer {
    private $violations = [];
    
    // Patterns to detect
    private $patterns = [
        'model_call' => '/[A-Z][a-zA-Z_]*_Model::[a-zA-Z]+\(/i',
        'appconfig' => '/AppConfig::[a-zA-Z]+\(/i',
        'privilege' => '/\\\\App\\\\Privilege::/i',
        'json_encode' => '/\\\\App\\\\Json::encode\(/i',
        'debugger' => '/\\\\App\\\\Debugger::/i',
        'util_helper' => '/Vtiger_Util_Helper::/i',
        'array_operations' => '/(array_push|array_key_exists|count|in_array)\(/i',
        'vtlib_functions' => '/vtlib\\\\Functions::/i',
        'field_classes' => '/\\\\App\\\\Fields::/i',
        'uitype_calls' => '/_UIType::/i',
    ];
    
    public function analyzeTplFile($filePath) {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNum => $line) {
            foreach ($this->patterns as $type => $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $this->violations[] = [
                        'file' => $filePath,
                        'line' => $lineNum + 1,
                        'type' => $type,
                        'code' => trim($line),
                        'match' => $matches[0]
                    ];
                }
            }
        }
    }
    
    public function analyzeDirectory($dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'tpl') {
                $this->analyzeTplFile($file->getPathname());
            }
        }
    }
    
    public function generateReport() {
        $report = "MVC Violations Analysis Report\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= str_repeat('=', 80) . "\n\n";
        
        // Group by type
        $byType = [];
        foreach ($this->violations as $violation) {
            $byType[$violation['type']][] = $violation;
        }
        
        foreach ($byType as $type => $violations) {
            $report .= "Violation Type: " . strtoupper($type) . " (Count: " . count($violations) . ")\n";
            $report .= str_repeat('-', 80) . "\n";
            
            foreach (array_slice($violations, 0, 5) as $violation) {
                $report .= sprintf(
                    "File: %s:%d\nCode: %s\n\n",
                    str_replace(ROOT_DIRECTORY . '/', '', $violation['file']),
                    $violation['line'],
                    $violation['code']
                );
            }
            
            if (count($violations) > 5) {
                $report .= sprintf("... and %d more violations of this type\n", count($violations) - 5);
            }
            
            $report .= "\n";
        }
        
        return $report;
    }
    
    public function getViolationsByFile() {
        $byFile = [];
        foreach ($this->violations as $violation) {
            $file = str_replace(ROOT_DIRECTORY . '/', '', $violation['file']);
            if (!isset($byFile[$file])) {
                $byFile[$file] = [];
            }
            $byFile[$file][] = $violation;
        }
        return $byFile;
    }
}

// Usage
$analyzer = new TplMvcAnalyzer();
$analyzer->analyzeDirectory(ROOT_DIRECTORY . '/layouts/basic/modules');
$analyzer->analyzeDirectory(ROOT_DIRECTORY . '/install/tpl');

echo $analyzer->generateReport();

// Export JSON for further processing
file_put_contents(
    ROOT_DIRECTORY . '/cache/tpl_violations.json',
    json_encode($analyzer->getViolationsByFile(), JSON_PRETTY_PRINT)
);
```

### Method 2: Automated Refactoring Script

Create a script that performs safe, automated refactoring:

```php
<?php
/**
 * Automated TPL Refactoring
 * Usage: php scripts/refactor_tpl.php <file_path>
 */

class TplRefactor {
    private $replacements = [];
    
    public function __construct() {
        $this->setupReplacements();
    }
    
    private function setupReplacements() {
        // Simple replacements that can be done automatically
        $this->replacements = [
            // AppConfig calls
            '/AppConfig::search\([\'"]GLOBAL_SEARCH_AUTOCOMPLETE[\'"]\)/' => 
                '$CONFIG.gsAutocomplete',
            '/AppConfig::search\([\'"]GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH[\'"]\)/' => 
                '$CONFIG.gsMinLength',
            '/AppConfig::search\([\'"]GLOBAL_SEARCH_AUTOCOMPLETE_LIMIT[\'"]\)/' => 
                '$CONFIG.gsAmountResponse',
            '/AppConfig::performance\([\'"]AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE[\'"]\)/' => 
                '$CONFIG.autoRefreshListOnChange',
            '/AppConfig::performance\([\'"]INTERVAL_FOR_NOTIFICATION_NUMBER_CHECK[\'"]\)/' => 
                '$CONFIG.intervalForNotificationNumberCheck',
            '/AppConfig::security\([\'"]FIELDS_REFERENCES_DEPENDENT[\'"]\)/' => 
                '$CONFIG.fieldsReferencesDependent',
            '/AppConfig::sounds\(\)/' => 
                '$CONFIG.sounds',
                
            // Debugger calls
            '/\\\\App\\\\Debugger::isDebugBar\(\)/' => 
                '$DEBUG_BAR_ENABLED',
            '/\\\\App\\\\Debugger::getDebugBar\(\)->getJavascriptRenderer\(\)->renderHead\(\)/' => 
                '$DEBUG_BAR_HTML',
        ];
    }
    
    public function refactorFile($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Apply replacements
        foreach ($this->replacements as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Check if changes were made
        if ($content === $originalContent) {
            return false;
        }
        
        // Create backup
        $backupPath = $filePath . '.backup';
        copy($filePath, $backupPath);
        
        // Write refactored content
        file_put_contents($filePath, $content);
        
        return true;
    }
    
    public function generateControllerCode($tplFile) {
        // Analyze what the template needs and generate controller code
        $content = file_get_contents($tplFile);
        $code = "// Add to controller:\n\n";
        
        // Detect AppConfig calls
        if (preg_match_all('/AppConfig::([a-z]+)\([\'"]([A-Z_]+)[\'"]\)/i', $content, $matches)) {
            $code .= "\$config = [];\n";
            foreach ($matches[0] as $i => $match) {
                $method = $matches[1][$i];
                $param = $matches[2][$i];
                $key = lcfirst(str_replace('_', '', ucwords(strtolower($param), '_')));
                $code .= "\$config['$key'] = AppConfig::$method('$param');\n";
            }
            $code .= "\$viewer->assign('CONFIG', \$config);\n\n";
        }
        
        // Detect Model::getInstance calls
        if (preg_match_all('/([A-Z][a-zA-Z_]*)_Model::getInstance\(([^)]+)\)/i', $content, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $modelClass = $matches[1][$i];
                $params = $matches[2][$i];
                $varName = strtoupper(preg_replace('/Model$/', '', $modelClass));
                $code .= "\${$varName} = {$modelClass}_Model::getInstance($params);\n";
                $code .= "\$viewer->assign('$varName', \${$varName});\n\n";
            }
        }
        
        return $code;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: php refactor_tpl.php <file_path>\n";
        exit(1);
    }
    
    $refactor = new TplRefactor();
    $filePath = $argv[1];
    
    try {
        if ($refactor->refactorFile($filePath)) {
            echo "✓ Refactored: $filePath\n";
            echo "  Backup created: $filePath.backup\n\n";
            echo "Generated controller code:\n";
            echo $refactor->generateControllerCode($filePath);
        } else {
            echo "✗ No changes needed: $filePath\n";
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
```

### Method 3: Controller Generator

Generate controller code to prepare data for views:

```php
<?php
/**
 * Generate controller methods for TPL files
 * Usage: php scripts/generate_controller_methods.php
 */

class ControllerMethodGenerator {
    
    public function generateForTpl($tplFile, $moduleName) {
        $content = file_get_contents($tplFile);
        
        $methodCode = "/**\n";
        $methodCode .= " * Prepare data for " . basename($tplFile) . "\n";
        $methodCode .= " * Generated by automated refactoring tool\n";
        $methodCode .= " */\n";
        $methodCode .= "protected function prepareViewData(Vtiger_Request \$request) {\n";
        $methodCode .= "    \$viewer = \$this->getViewer(\$request);\n\n";
        
        // Config assignments
        $configCalls = $this->extractConfigCalls($content);
        if (!empty($configCalls)) {
            $methodCode .= "    // Configuration\n";
            $methodCode .= "    \$config = [\n";
            foreach ($configCalls as $key => $call) {
                $methodCode .= "        '$key' => $call,\n";
            }
            $methodCode .= "    ];\n";
            $methodCode .= "    \$viewer->assign('CONFIG', \$config);\n\n";
        }
        
        // Model instances
        $modelInstances = $this->extractModelInstances($content);
        if (!empty($modelInstances)) {
            $methodCode .= "    // Models\n";
            foreach ($modelInstances as $var => $call) {
                $methodCode .= "    \$$var = $call;\n";
                $methodCode .= "    \$viewer->assign('$var', \$$var);\n";
            }
            $methodCode .= "\n";
        }
        
        // Permission checks
        $permissionChecks = $this->extractPermissionChecks($content);
        if (!empty($permissionChecks)) {
            $methodCode .= "    // Permissions\n";
            foreach ($permissionChecks as $var => $call) {
                $methodCode .= "    \$$var = $call;\n";
                $methodCode .= "    \$viewer->assign('$var', \$$var);\n";
            }
            $methodCode .= "\n";
        }
        
        // JSON encodings
        $jsonCalls = $this->extractJsonEncodings($content);
        if (!empty($jsonCalls)) {
            $methodCode .= "    // JSON Data\n";
            foreach ($jsonCalls as $var => $data) {
                $methodCode .= "    \$viewer->assign('$var', \\App\\Json::encode(\$$data));\n";
            }
            $methodCode .= "\n";
        }
        
        $methodCode .= "    return \$viewer;\n";
        $methodCode .= "}\n";
        
        return $methodCode;
    }
    
    private function extractConfigCalls($content) {
        $configs = [];
        if (preg_match_all('/AppConfig::([a-z]+)\([\'"]([A-Z_]+)[\'"]\)/i', $content, $matches)) {
            foreach ($matches[0] as $i => $fullMatch) {
                $method = $matches[1][$i];
                $param = $matches[2][$i];
                $key = lcfirst(str_replace('_', '', ucwords(strtolower($param), '_')));
                $configs[$key] = "AppConfig::$method('$param')";
            }
        }
        return $configs;
    }
    
    private function extractModelInstances($content) {
        $models = [];
        if (preg_match_all('/([A-Z][a-zA-Z_]*)_Model::getInstance\(([^)]+)\)/i', $content, $matches)) {
            foreach ($matches[0] as $i => $fullMatch) {
                $modelClass = $matches[1][$i];
                $params = $matches[2][$i];
                $varName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelClass)) . '_instance';
                $models[$varName] = "{$modelClass}_Model::getInstance($params)";
            }
        }
        return $models;
    }
    
    private function extractPermissionChecks($content) {
        $permissions = [];
        if (preg_match_all('/\\\\App\\\\Privilege::isPermitted\(([^)]+)\)/i', $content, $matches)) {
            foreach ($matches[0] as $i => $fullMatch) {
                $params = $matches[1][$i];
                $varName = 'isPermitted' . $i;
                $permissions[$varName] = "\\App\\Privilege::isPermitted($params)";
            }
        }
        return $permissions;
    }
    
    private function extractJsonEncodings($content) {
        $jsonCalls = [];
        if (preg_match_all('/\\\\App\\\\Json::encode\(\$([A-Z_]+)\)/i', $content, $matches)) {
            foreach ($matches[1] as $i => $varName) {
                $jsonCalls[$varName . '_JSON'] = $varName;
            }
        }
        return $jsonCalls;
    }
}

// Usage
$generator = new ControllerMethodGenerator();
$tplFile = ROOT_DIRECTORY . '/layouts/basic/modules/Vtiger/Header.tpl';
$moduleName = 'Vtiger';

echo $generator->generateForTpl($tplFile, $moduleName);
```

### Method 4: Batch Refactoring with Git Integration

Script that refactors multiple files and creates commits:

```bash
#!/bin/bash
# Batch refactor TPL files with automatic commits

TPL_DIR="layouts/basic/modules"
BACKUP_DIR=".refactoring_backups"
REPORT_FILE="refactoring_report.md"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Initialize report
echo "# TPL Refactoring Report" > "$REPORT_FILE"
echo "Generated: $(date)" >> "$REPORT_FILE"
echo "" >> "$REPORT_FILE"

# Find all TPL files
find "$TPL_DIR" -name "*.tpl" | while read -r tpl_file; do
    echo "Processing: $tpl_file"
    
    # Run analysis
    php scripts/analyze_tpl_violations.php "$tpl_file" > temp_analysis.txt
    
    if [ -s temp_analysis.txt ]; then
        echo "  Found violations, refactoring..."
        
        # Create backup
        cp "$tpl_file" "$BACKUP_DIR/$(basename $tpl_file).backup"
        
        # Refactor
        php scripts/refactor_tpl.php "$tpl_file"
        
        # Generate controller code
        php scripts/generate_controller_methods.php "$tpl_file" > temp_controller.php
        
        # Add to report
        echo "## $tpl_file" >> "$REPORT_FILE"
        echo "" >> "$REPORT_FILE"
        echo "### Violations Found:" >> "$REPORT_FILE"
        cat temp_analysis.txt >> "$REPORT_FILE"
        echo "" >> "$REPORT_FILE"
        echo "### Controller Code Required:" >> "$REPORT_FILE"
        echo '```php' >> "$REPORT_FILE"
        cat temp_controller.php >> "$REPORT_FILE"
        echo '```' >> "$REPORT_FILE"
        echo "" >> "$REPORT_FILE"
        
        # Commit changes
        git add "$tpl_file"
        git commit -m "refactor: Make $(basename $tpl_file) MVC-compliant

- Remove model calls from template
- Move business logic to controller
- See refactoring_report.md for details"
        
        echo "  ✓ Refactored and committed"
    else
        echo "  ✓ No violations found"
    fi
    
    rm -f temp_analysis.txt temp_controller.php
done

echo ""
echo "Refactoring complete! Check $REPORT_FILE for details."
```

### Method 5: IDE/Editor Plugin Configuration

For real-time detection, configure your editor:

**PHPStorm/IntelliJ IDEA Custom Inspections:**

```xml
<!-- .idea/inspectionProfiles/MVC_Violations.xml -->
<component name="InspectionProjectProfileManager">
  <profile version="1.0">
    <option name="myName" value="MVC Violations" />
    <inspection_tool class="RegExpSingleChar" enabled="true" level="ERROR">
      <option name="pattern" value="AppConfig::|_Model::|\\App\\Privilege::|\\App\\Debugger::|\\App\\Json::encode" />
      <option name="message" value="MVC Violation: Model/Config/Utility call in template" />
    </inspection_tool>
  </profile>
</component>
```

**VS Code settings.json:**

```json
{
  "files.associations": {
    "*.tpl": "smarty"
  },
  "editor.quickSuggestions": {
    "other": true,
    "comments": false,
    "strings": false
  },
  "todo-tree.highlights.customHighlight": {
    "MVC_VIOLATION": {
      "icon": "alert",
      "iconColour": "red",
      "foreground": "red",
      "type": "text-and-comment"
    }
  },
  "todo-tree.regex.regex": "(AppConfig::|_Model::|\\\\App\\\\Privilege::|\\\\App\\\\Debugger::)",
  "editor.rulers": [
    {
      "column": 80,
      "color": "#ff0000"
    }
  ]
}
```

---

## Implementation Roadmap

### Phase 1: Analysis (Week 1)
1. Run violation detection script on all TPL files
2. Generate comprehensive report
3. Categorize violations by type and severity
4. Identify most common patterns
5. Prioritize files for refactoring

### Phase 2: Tool Development (Week 2)
1. Complete automated refactoring scripts
2. Create controller code generator
3. Set up testing framework
4. Configure IDE/editor warnings
5. Create refactoring documentation

### Phase 3: Safe Refactoring (Weeks 3-6)
1. Start with simplest violations (config calls)
2. Refactor one module at a time
3. Test each refactoring thoroughly
4. Update controllers with generated code
5. Verify no functionality breaks

### Phase 4: Complex Refactoring (Weeks 7-10)
1. Tackle model instantiation violations
2. Refactor business logic operations
3. Update helper methods
4. Consolidate common patterns
5. Create reusable view helpers

### Phase 5: Verification (Week 11)
1. Run full test suite
2. Manual QA testing
3. Performance benchmarking
4. Code review
5. Documentation updates

### Phase 6: Deployment (Week 12)
1. Merge refactored code
2. Monitor for regressions
3. Update coding standards
4. Train team on new patterns
5. Set up CI/CD checks

---

## Testing Refactored Templates

### Unit Testing Controllers

```php
<?php
class Vtiger_HeaderController_Test extends PHPUnit\Framework\TestCase {
    
    public function testPrepareConfigData() {
        $controller = new Vtiger_Index_View();
        $request = new Vtiger_Request(['module' => 'Vtiger']);
        
        $viewer = $controller->prepareViewData($request);
        $config = $viewer->get_template_vars('CONFIG');
        
        $this->assertArrayHasKey('gsAutocomplete', $config);
        $this->assertArrayHasKey('gsMinLength', $config);
        $this->assertArrayHasKey('gsAmountResponse', $config);
    }
    
    public function testNoModelCallsInTemplate() {
        $tplContent = file_get_contents('layouts/basic/modules/Vtiger/Header.tpl');
        
        // Should not contain direct model calls
        $this->assertStringNotContainsString('AppConfig::', $tplContent);
        $this->assertStringNotContainsString('_Model::', $tplContent);
        $this->assertStringNotContainsString('\\App\\Privilege::', $tplContent);
    }
}
```

### Functional Testing

```php
<?php
class MvcComplianceTest extends \Codeception\Test\Unit {
    
    public function testHeaderRenders() {
        $I = $this->tester;
        $I->amOnPage('/');
        $I->see('FreeCRM');
        $I->seeElement('#configuration');
        $I->seeElement('input#gsAutocomplete');
    }
    
    public function testConfigValuesPresent() {
        $I = $this->tester;
        $I->amOnPage('/index.php?module=Vtiger&view=Index');
        $I->seeInSource('<input type="hidden" id="gsAutocomplete"');
        $I->seeInSource('<input type="hidden" id="gsMinLength"');
    }
}
```

---

## Best Practices Going Forward

### 1. Controller Checklist
Before creating a template, ensure controller:
- [ ] Fetches all required data from models
- [ ] Performs all business logic
- [ ] Checks all permissions
- [ ] Formats data for display
- [ ] Encodes all JSON data
- [ ] Prepares all configuration values
- [ ] Assigns all data to viewer

### 2. Template Checklist
Templates should only:
- [ ] Display pre-processed data
- [ ] Use simple loops and conditionals
- [ ] Apply CSS classes and styling
- [ ] Include other templates
- [ ] Use translation helpers
- [ ] Access pre-assigned variables

### 3. Code Review Checklist
- [ ] No direct model calls
- [ ] No AppConfig:: calls
- [ ] No \\App\\ namespace calls
- [ ] No business logic
- [ ] No array manipulations
- [ ] No JSON encoding
- [ ] All data pre-processed
- [ ] Clear variable names

### 4. Continuous Monitoring
- Set up pre-commit hooks to detect violations
- Add CI/CD pipeline checks
- Regular code review sessions
- Update documentation as patterns evolve

---

## Conclusion

Refactoring TPL files to be MVC-compliant is essential for:
- **Maintainability**: Easier to understand and modify
- **Testability**: Controllers can be unit tested
- **Reusability**: Logic can be shared across views
- **Performance**: Data processing done once
- **Security**: Centralized permission checking
- **Scalability**: Cleaner architecture supports growth

The automated tools and strategies outlined in this document provide a systematic approach to achieving MVC compliance across the entire codebase.

---

## Additional Resources

- [Smarty Template Documentation](https://www.smarty.net/docs/en/)
- [MVC Pattern Best Practices](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller)
- [PHP The Right Way - MVC](https://phptherightway.com/#design_patterns)
- [Separation of Concerns Principle](https://en.wikipedia.org/wiki/Separation_of_concerns)

---

**Document Version:** 1.0  
**Last Updated:** 2025-10-15  
**Author:** FreeCRM Development Team

