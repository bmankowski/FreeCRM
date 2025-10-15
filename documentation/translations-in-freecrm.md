# Translation Mechanisms in FreeCRM

## Overview
FreeCRM uses multiple translation systems for different contexts: Smarty templates (server-side), JavaScript (client-side), and specialized menu translations.

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

## 3. Menu-Specific Translations

### Function
```php
Vtiger_Menu_Model::vtranslateMenu($key, $module)
```

### Purpose
Provides two-tier translation system for menu items with override capability.

### Implementation
```php
public static function vtranslateMenu($key, $module)
{
    // 1. Check Menu.php first
    $moduleStrings = Vtiger_Language_Handler::getModuleStringsFromFile($language, 'Menu');
    if (isset($moduleStrings['languageStrings'][$key])) {
        return stripslashes($moduleStrings['languageStrings'][$key]);
    }
    
    // 2. Fallback to regular translation
    return Vtiger_Language_Handler::getTranslatedString($key, $module);
}
```

### Advantages
- **Centralized**: All menu labels in one file (`Menu.php`)
- **Override capability**: Customize menu labels without changing module translations
- **Consistency**: Same label across all menu appearances

---

## 4. Translation Fallback Chain

### For vtranslateMenu()
```
1. languages/{lang}/Menu.php
   ↓ (not found)
2. languages/{lang}/{Module}.php
   ↓ (not found)
3. languages/{lang}/{BaseModule}.php (for submodules like Settings.Users)
   ↓ (not found)
4. languages/{lang}/Vtiger.php (common strings)
   ↓ (not found)
5. Repeat 2-4 in default language (usually en_us)
   ↓ (not found)
6. Return the key itself (untranslated)
```

### For vtranslate()
Same as above, but **skips step 1** (Menu.php).

---

## 5. File Structure

```
languages/
├── en_us/
│   ├── Vtiger.php           # Common strings (global fallback)
│   ├── Menu.php             # Menu-specific translations
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

### Vtiger.php (Global Safety Net)
Contains common UI strings used across all modules:
```php
$languageStrings = [
    'LBL_SAVE' => 'Save',
    'LBL_CANCEL' => 'Cancel',
    'LBL_EDIT' => 'Edit',
    'LBL_DELETE' => 'Delete',
    // ... hundreds more
];
```

### Menu.php (Menu Overrides)
```php
$languageStrings = [
    'MEN_VIRTUAL_DESK' => 'Virtual desk',
    'MEN_COMPANIES_CONTACTS' => 'Companies and Contacts',
    'LBL_QUICK_CREATE_MODULE' => 'Quick create',
];
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
function vtranslate($key, $moduleName = 'Vtiger')
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
- Use `vtranslateMenu()` for menu items
- Store common strings in `Vtiger.php`
- Store menu labels in `Menu.php`

### DON'T
- Use nested braces: `{vtranslate({$VAR}, $MODULE)}`
- Mix Smarty and JavaScript translation methods
- Duplicate common strings across module files
- Use Smarty syntax in external `.js` files

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

| Aspect | vtranslate() | vtranslateMenu() | app.vtranslate() |
|--------|--------------|------------------|------------------|
| **Context** | Smarty templates | Menu items | JavaScript |
| **Execution** | Server-side | Server-side | Client-side |
| **Override file** | None | Menu.php | N/A |
| **Fallback** | Module → Vtiger.php | Menu.php → Module → Vtiger.php | Pre-loaded strings |
| **Modern syntax** | `\|t` modifier | N/A (PHP function) | N/A (JS API) |

