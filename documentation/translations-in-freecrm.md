# Translation Mechanisms in FreeCRM

## Overview
FreeCRM uses multiple translation systems for different contexts: Smarty templates (server-side) and JavaScript (client-side).

---

## 1. Smarty Template Translations (Server-Side)

### Legacy Syntax (being phased out)
```smarty
{vtranslate('LBL_SAVE', $MODULE)}
{vtranslate('LBL_SAVE')}
{vtranslate($VARIABLE, $MODULE)}
```

### Modern Syntax (recommended)
```smarty
{"LBL_SAVE"|t:$MODULE}
{"LBL_SAVE"|t}
{$VARIABLE|t:$MODULE}
```

### Complex Expressions
```smarty
{* With modifiers *}
{"LBL_CV_GROUP_"|cat:strtoupper($GROUP_LABEL)|t}

{* With variables *}
{$FIELD_MODEL->get('label')|t:$MODULE_NAME}
```

### Invalid Syntax (found in codebase, should be fixed)
```smarty
{vtranslate({$VARIABLE}, $MODULE)}  ❌ Nested braces invalid
```
Should be: `{vtranslate($VARIABLE, $MODULE)}` or `{$VARIABLE|t:$MODULE}`

---

## 2. JavaScript Translations (Client-Side)

### API Call
```javascript
app.vtranslate('JS_JANUARY')
app.vtranslate('JS_LOADING')
```

### Why Used in TPL Files
- **Consistency**: Same API for inline scripts and external `.js` files
- **Dynamic content**: AJAX-loaded content, charts, calendars
- **Reusability**: External `.js` files can't use Smarty syntax
- **Safety**: Handles escaping automatically

### Translation Loading
Translations are pre-loaded into JavaScript via hidden div:
```smarty
<div id="js_strings" class="hide">
    {"JS_JANUARY"|t}
    {"JS_FEBRUARY"|t}
</div>
```

---

## 3. Menu Translations

Menu items use the standard `vtranslate()` function, just like any other part of the application. All menu translation strings are stored in `Vtiger.php` along with other common UI strings.

### Common Menu Translation Keys
```php
// In languages/en_us/Vtiger.php
$languageStrings = [
    'LBL_QUICK_CREATE_MODULE' => 'Quick create',
    'MEN_VIRTUAL_DESK' => 'Virtual desk',
    'MEN_COMPANIES_CONTACTS' => 'Companies and Contacts',
    'MEN_SALES' => 'Sales',
    'MEN_PROJECTS' => 'Realization',
    'MEN_MARKETING' => 'Marketing',
    // ... etc
];
```

### Note
`Menu.php` files still exist in language directories for compatibility purposes but contain no translations (empty arrays). All menu translations have been consolidated into `Vtiger.php`.

---

## 4. Translation Fallback Chain

### For \App\Runtime\Vtiger_Language_Handler::translate()
```
1. languages/{lang}/{Module}.php
   ↓ (not found)
2. languages/{lang}/{BaseModule}.php (for submodules like Settings.Users)
   ↓ (not found)
3. languages/{lang}/Vtiger.php (common strings)
   ↓ (not found)
4. Repeat 1-3 in default language (usually en_us)
   ↓ (not found)
5. Return the key itself (untranslated)
```

---

## 5. File Structure

```
languages/
├── en_us/
│   ├── Vtiger.php           # Common strings (global fallback) + menu translations
│   ├── Menu.php             # Empty (kept for compatibility)
│   ├── Accounts.php         # Module-specific
│   ├── Contacts.php
│   └── Settings/
│       ├── Vtiger.php       # Base Settings module
│       ├── Users.php        # Settings submodule
│       └── Companies.php
└── pl_pl/
    └── (same structure)
```

---

## 6. Common Translation Files

### Vtiger.php (Global Fallback)
Contains common UI strings used across all modules, including menu translations:
```php
$languageStrings = [
    // Common UI strings
    'LBL_SAVE' => 'Save',
    'LBL_CANCEL' => 'Cancel',
    'LBL_EDIT' => 'Edit',
    'LBL_DELETE' => 'Delete',
    
    // Menu translations
    'LBL_QUICK_CREATE_MODULE' => 'Quick create',
    'MEN_VIRTUAL_DESK' => 'Virtual desk',
    'MEN_COMPANIES_CONTACTS' => 'Companies and Contacts',
    'MEN_SALES' => 'Sales',
    // ... hundreds more
];
```

### Menu.php (Legacy Compatibility)
```php
// All menu translations have been moved to Vtiger.php
// This file is kept for compatibility with the translation system
$languageStrings = [];
```

---

## 7. sprintf Support

### PHP/Smarty
```php
vtranslate('LBL_RECORDS_FOUND', $MODULE, $count)
// In language file: 'LBL_RECORDS_FOUND' => 'Found %s records'
```

### Implementation
```php
function \App\Runtime\Vtiger_Language_Handler::translate($key, $moduleName = 'Vtiger')
{
    $formattedString = getTranslatedString($key, $moduleName);
    $args = func_get_args();
    array_shift($args); // Remove $key
    array_shift($args); // Remove $moduleName
    if ($args !== []) {
        return call_user_func_array('vsprintf', [$formattedString, $args]);
    }
    return $formattedString;
}
```

---

## 8. Best Practices

### DO
- Use `|t` modifier in new Smarty templates
- Use `app.vtranslate()` for JavaScript
- Use standard `vtranslate()` for all PHP contexts (including menus)
- Store common strings and menu labels in `Vtiger.php`
- Keep module-specific translations in their respective module files

### DON'T
- Use nested braces: `{vtranslate({$VAR}, $MODULE)}`
- Mix Smarty and JavaScript translation methods
- Duplicate common strings across module files
- Use Smarty syntax in external `.js` files
- Add translations to `Menu.php` (it's deprecated)

---

## 9. Refactoring Notes

### Automated Conversion Patterns
The `refactor/simple_vtranslate_to_t.py` script handles these conversions:

| Pattern | Old | New |
|---------|-----|-----|
| Simple string | `{vtranslate('KEY')}` | `{"KEY"\|t}` |
| String + module | `{vtranslate('KEY', $MODULE)}` | `{"KEY"\|t:$MODULE}` |
| Variable | `{vtranslate($VAR)}` | `{$VAR\|t}` |
| Variable + module | `{vtranslate($VAR, $MODULE)}` | `{$VAR\|t:$MODULE}` |
| Complex expression | `{vtranslate('KEY'\|cat:func($VAR))}` | `{"KEY"\|cat:func($VAR)\|t}` |

### Skipped Cases
- Nested braces (invalid syntax)
- JavaScript `app.vtranslate()` calls
- Three or more parameters (custom sprintf)

---

## 10. Key Differences Summary

| Aspect | \App\Runtime\Vtiger_Language_Handler::translate() | \App\Runtime\Vtiger_Language_Handler::translate() | app.vtranslate() |
|--------|--------------|------------------|------------------|
| **Context** | Smarty templates | Menu items | JavaScript |
| **Execution** | Server-side | Server-side | Client-side |
| **Override file** | None | Menu.php | N/A |
| **Fallback** | Module → Vtiger.php | Menu.php → Module → Vtiger.php | Pre-loaded strings |
| **Modern syntax** | `\|t` modifier | N/A (PHP function) | N/A (JS API) |

