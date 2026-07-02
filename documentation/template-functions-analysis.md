# Template Functions Analysis - FreeCRM

## Overview

This document analyzes the types of functions used in FreeCRM Smarty templates. Based on analysis of 124 template files containing function calls (194 total function calls), templates use several categories of functions.

## Function Categories

### 1. **FreeCRM-Specific Helper Functions** (Most Common)

These are custom functions registered specifically for FreeCRM templates:

#### Image/Resource Path Functions
- **`vimage_path()`** - Get image path (most common, ~17 occurrences)
  - Usage: `{vimage_path('drag.png')}`, `{vimage_path('loading.gif')}`
  - Purpose: Resolve image paths with theme support
  - Registered as both function and modifier

- **`vresource_url()`** - Get resource URL with cache busting
  - Usage: `{vresource_url($cssModel->getHref())}`
  - Purpose: Add cache-busting query strings to static resources

- **`vtemplate_path()`** - Get template path
  - Usage: `{vtemplate_path('ListViewAlphabet.tpl',$MODULE_NAME)}`
  - Purpose: Resolve module-specific template paths

#### Global Configuration Functions
- **`vglobal()`** - Get global configuration value
  - Usage: `{vglobal('listMaxEntriesMassEdit')}`, `{vglobal('backgroundClosingModal')}`
  - Purpose: Access global configuration variables
  - Registered as both function and modifier

#### Translation Functions
- **`vtranslate()`** - Legacy translation function (modifier)
- **`t`** - Modern translation modifier (recommended)
  - Usage: `{"LBL_SAVE"|t:$MODULE}`, `{$VARIABLE|t:$MODULE_NAME}`
  - Purpose: Translate language strings

### 2. **PHP Standard Library Functions** (Common)

These are standard PHP functions registered as Smarty plugins:

#### Array Functions
- **`count()`** - Count array elements (~5 occurrences)
  - Usage: `{count($SWITCH_USERS)}`, `{count($LOCKS)}`, `{count($ACTIVITIES)}`
  - Purpose: Count array/collection size

- **`in_array()`** - Check if value exists in array (~15 occurrences)
  - Usage: `{if in_array($OWNER_ID, $USERS)}`, `{if in_array($ITEM, $DUPLICATE_STATUS)}`
  - Purpose: Check membership in arrays

- **`explode()`** - Split string into array (~8 occurrences)
  - Usage: `{explode(' ' ,$DATE_TIME_VALUE_NOW)}`, `{explode(',', $DUPLICATE_STATUS)}`
  - Purpose: Split delimited strings

- **`implode()`** - Join array into string (~10 occurrences)
  - Usage: `{implode(',',$DTIME)}`, `{implode(',', $CURRENT_STATUS)}`
  - Purpose: Join arrays with delimiter

- **`array_flip()`** - Exchange keys and values
- **`array_diff_key()`** - Compare arrays by keys
- **`array_map()`** - Apply function to array elements
- **`array_key_exists()`** - Check if key exists

#### String Functions
- **`strpos()`** / **`stripos()`** - Find string position (~2 occurrences)
  - Usage: `{if stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0}`
  - Purpose: String searching

- **`strlen()`** - Get string length
  - Usage: `{substr:strlen("javascript:")}`
  - Purpose: String manipulation

- **`substr()`** - Extract substring
  - Usage: `{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")}`
  - Purpose: Extract parts of strings

- **`strtoupper()`** - Convert to uppercase
- **`trim()`** - Remove whitespace
- **`sprintf()`** - Format string

#### Type Checking Functions
- **`is_array()`** - Check if variable is array (~3 occurrences)
  - Usage: `{if $PARAMS[$ITEM]|is_array && in_array($ITEMS.id,$PARAMS[$ITEM])}`
  - Purpose: Type validation

- **`method_exists()`** - Check if method exists
- **`get_class()`** - Get class name
- **`file_exists()`** - Check if file exists

#### Conversion Functions
- **`intval()`** - Convert to integer
- **`htmlspecialchars()`** - Escape HTML
- **`html_entity_decode()`** - Decode HTML entities
- **`decode_html()`** - Custom HTML decoder (`\App\Utils\ListViewUtils::decodeHtml`)

#### Other PHP Functions
- **`microtime()`** - Get current time in microseconds
- **`nl2br()`** - Convert newlines to `<br>` (~2 occurrences)
  - Usage: `{nl2br($COMMENT->get('commentcontent'))}`
  - Purpose: Format text with line breaks

### 3. **Static Class Method Calls** (Very Common)

These are static methods from registered classes:

#### AppConfig Class (Most Common Static Calls)
- **`AppConfig::module()`** - Get module configuration (~8 occurrences)
  - Usage: `{AppConfig::module('Users','IS_VISIBLE_USER_INFO_FOOTER')}`
  - Purpose: Access module-specific config

- **`AppConfig::search()`** - Get search configuration (~4 occurrences)
  - Usage: `{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE')}`
  - Purpose: Search-related settings

- **`AppConfig::performance()`** - Get performance configuration (~6 occurrences)
  - Usage: `{AppConfig::performance('SEARCH_OWNERS_BY_AJAX')}`
  - Purpose: Performance-related settings

- **`AppConfig::main()`** - Get main configuration
  - Usage: `{AppConfig::main('site_URL')}`
  - Purpose: Core application settings

- **`AppConfig::security()`** - Get security configuration
  - Usage: `{AppConfig::security('FIELDS_REFERENCES_DEPENDENT')}`
  - Purpose: Security-related settings

- **`AppConfig::sounds()`** - Get sounds configuration
  - Usage: `{AppConfig::sounds()}`

#### Field Formatting Classes
- **`DateTimeField::convertToUserFormat()`** (~6 occurrences)
  - Usage: `{DateTimeField::convertToUserFormat($WORKFLOW_MODEL_OBJ->nexttrigger_time)}`
  - Purpose: Format dates/times for user display

- **`CurrencyField::convertToUserFormat()`** (~3 occurrences)
  - Usage: `{CurrencyField::convertToUserFormat($SUM, null, true)}`
  - Purpose: Format currency values

#### Utility Classes
- **`\App\Json::encode()`** - JSON encoding
  - Usage: `{\App\Json::encode($LANGUAGE_STRINGS)}`
  - Purpose: Convert data to JSON

- **`\App\Modules\Base\Helpers\Util::toSafeHTML()`** - HTML escaping
  - Usage: `{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode(AppConfig::sounds()))}`
  - Purpose: Safe HTML output

- **`\App\Modules\Base\Helpers\Util::formatDateTimeIntoDayString()`** - Date formatting
  - Usage: `{\App\Modules\Base\Helpers\Util::formatDateTimeIntoDayString($COMMENT->getCommentedTime())}`
  - Purpose: Format dates for display

- **`\App\Modules\Base\Helpers\Util::formatDateDiffInStrings()`** - Relative date formatting
  - Usage: `{\App\Modules\Base\Helpers\Util::formatDateDiffInStrings($COMMENT->getCommentedTime())}`
  - Purpose: Show relative time ("2 hours ago")

- **`\App\Record::getLabel()`** - Get record label
  - Usage: `{\App\Record::getLabel($RELATED_TO)}`
  - Purpose: Get human-readable record name

#### Module Model Classes
- **`Settings_PDF_Module_Model::getCreateRecordUrl()`** - Get create URL
- **`Settings_PDF_Module_Model::getImportViewUrl()`** - Get import URL
- **`Settings_PDF_Module_Model::getDefaultUrl()`** - Get default URL
- **`OSSMail_Module_Model::getExternalUrlForWidget()`** - Get external URL

#### Debug Classes
- **`\App\Debugger::isDebugBar()`** - Check if debug bar enabled
- **`\App\Debugger::getDebugBar()->getJavascriptRenderer()->renderHead()`** - Render debug bar

### 4. **Object Method Calls** (Very Common)

These are instance methods called on template variables:

#### Common Patterns
- **`$MODEL->get()`** - Get property value
- **`$MODEL->getId()`** - Get record ID
- **`$MODEL->getName()`** - Get name
- **`$MODEL->getUrl()`** - Get URL
- **`$MODEL->isPermitted()`** - Check permissions
- **`$MODEL->getListViewDisplayValue()`** - Get display value

#### Specific Examples Found
- `$USER_MODEL->get('leftpanelhide')`
- `$USER_MODEL->getHeadLocks()`
- `$USER_MODEL->getBodyLocks()`
- `$PAGING_MODEL->getRecordStartRange()`
- `$PAGING_MODEL->isNextPageExists()`
- `$LISTVIEW_ENTRY->getDetailViewUrl()`
- `$COMMENT->getImageWebUrl()`
- `$COMMENT->getCommentedByModel()`
- `$WIDGET->get('active')`
- `$WIDGET->getPositionRow()`
- `$WIDGET->getWidth()`

## Function Registration

All functions are registered in `src/Runtime/CRM_Viewer.php` in the `registerSmartyPlugins()` method:

### Registration Methods:
1. **As Functions**: `{function_name($param)}`
2. **As Modifiers**: `{$variable|function_name}`
3. **As Classes**: Static methods via `ClassName::method()`

### Registration Location:
```143:243:src/Runtime/CRM_Viewer.php
private function registerSmartyPlugins()
{
	// Register custom functions for Smarty 4.5 compatibility
	// Functions in global namespace (from TemplateHelpers.php) can be called directly in templates
	try {
		// Register plugins - these are in global namespace from TemplateHelpers.php
		$this->registerPlugin('modifier', 'vtranslate', '\App\Runtime\Vtiger_Language_Handler::translate');
		$this->registerPlugin('function', 'vimage_path', 'vimage_path');
		$this->registerPlugin('modifier', 'vimage_path', 'vimage_path'); // Also as modifier
		$this->registerPlugin('function', 'vtemplate_path', 'vtemplate_path');
		$this->registerPlugin('modifier', 'vtemplate_path', 'vtemplate_path'); // Also as modifier
		$this->registerPlugin('function', 'vresource_url', 'vresource_url');
		$this->registerPlugin('modifier', 'vresource_url', 'vresource_url'); // Also as modifier
		$this->registerPlugin('function', 'vglobal', 'vglobal');
		$this->registerPlugin('modifier', 'vglobal', 'vglobal'); // Also register as modifier for compatibility

		// Register  modifier 't'
		$this->registerPlugin('modifier', 't', '\App\Runtime\Vtiger_Language_Handler::translate');
		
	// Register static classes for template use
	$this->registerClass('AppConfig', '\App\AppConfig');
	$this->registerClass('\App\Modules\Base\Util', '\App\Modules\\Base\\Helpers\\Util');
	// ... more classes registered ...
```

## Statistics

- **Total template files with functions**: 124 files
- **Total function calls**: 194+ occurrences
- **Most common function**: `vimage_path()` (~17 occurrences)
- **Most common PHP function**: `in_array()` (~15 occurrences)
- **Most common static class**: `AppConfig` (~20+ occurrences)

## Usage Patterns

### Pattern 1: Function Call
```smarty
{vimage_path('drag.png')}
{count($SWITCH_USERS)}
{vglobal('listMaxEntriesMassEdit')}
```

### Pattern 2: Modifier Usage
```smarty
{$VARIABLE|t:$MODULE}
{$VARIABLE|vimage_path}
{$VARIABLE|count}
```

### Pattern 3: Static Method Call
```smarty
{AppConfig::module('Users','IS_VISIBLE_USER_INFO_FOOTER')}
{DateTimeField::convertToUserFormat($DATE)}
{\App\Json::encode($DATA)}
```

### Pattern 4: Conditional with Functions
```smarty
{if in_array($OWNER_ID, $USERS)} selected {/if}
{if AppConfig::performance('SEARCH_OWNERS_BY_AJAX')}
{if $PARAMS[$ITEM]|is_array && in_array($ITEMS.id,$PARAMS[$ITEM])}
```

### Pattern 5: Complex Expressions
```smarty
{implode(',',$DTIME)}
{explode(' ' ,$DATE_TIME_VALUE_NOW)}
{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")}
```

## Recommendations

### 1. **Function Usage Guidelines**
- Prefer modifiers (`{$var|function}`) over function calls (`{function($var)}`) for consistency
- Use `t` modifier instead of `vtranslate()` function
- Use `AppConfig` static methods for configuration access
- Use helper classes for formatting (DateTimeField, CurrencyField)

### 2. **MVC Compliance**
- Most function usage is appropriate for templates
- Business logic should remain in controllers/models
- Functions used are primarily for:
  - Formatting/display
  - Configuration access
  - Path resolution
  - Type checking

### 3. **Potential Refactoring Opportunities**
- Consider moving complex formatting logic to view helpers
- Some `in_array()` checks could be moved to controllers
- String manipulation (`substr`, `strlen`) could be simplified with helper methods

## Files Analyzed

Analysis based on 124 template files containing function calls, including:
- Base module templates
- Settings module templates
- Dashboard templates
- ListView templates
- DetailView templates
- Widget templates
- Uitype templates

## Conclusion

Templates in FreeCRM use a mix of:
1. **Custom helper functions** for FreeCRM-specific operations (paths, resources, config)
2. **Standard PHP functions** for common operations (arrays, strings, type checking)
3. **Static class methods** for formatting and utility operations
4. **Object methods** for accessing model data

The function usage is generally appropriate for template rendering, with most functions serving display/formatting purposes rather than business logic.

