# MVC-Compliant TPL Development - Quick Reference

## Quick Violation Checklist

Before committing a TPL file, check that it does NOT contain:

- [ ] `AppConfig::`
- [ ] `_Model::`
- [ ] `::getInstance(`
- [ ] `\App\Privilege::`
- [ ] `\App\Debugger::`
- [ ] `\App\Json::encode(`
- [ ] `Vtiger_Util_Helper::`
- [ ] `array_push(`, `array_key_exists(`, `count(`, `in_array(`
- [ ] `vtlib\Functions::`
- [ ] `\App\Fields::`
- [ ] `_UIType::`

If your template contains any of these, you need to refactor!

---

## Pattern Examples

### ❌ WRONG: Direct Config Access
```smarty
<input type="hidden" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE')}" />
```

### ✅ CORRECT: Pre-processed Config
```php
// Controller
$viewer->assign('CONFIG', [
    'gsAutocomplete' => AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE')
]);
```
```smarty
<!-- Template -->
<input type="hidden" value="{$CONFIG.gsAutocomplete}" />
```

---

### ❌ WRONG: Model Instantiation
```smarty
{assign var="ANNOUNCEMENTS" value=Vtiger_Module_Model::getInstance('Announcements')}
```

### ✅ CORRECT: Controller Provides Instance
```php
// Controller
$announcements = Vtiger_Module_Model::getInstance('Announcements');
$viewer->assign('ANNOUNCEMENTS', $announcements);
```
```smarty
<!-- Template -->
{if $ANNOUNCEMENTS}
    {* Use the model *}
{/if}
```

---

### ❌ WRONG: Permission Check
```smarty
{if \App\Privilege::isPermitted($MODULE, 'ReceivingMailNotifications')}
```

### ✅ CORRECT: Controller Checks Permission
```php
// Controller
$canReceiveNotifications = \App\Privilege::isPermitted($module, 'ReceivingMailNotifications');
$viewer->assign('CAN_RECEIVE_NOTIFICATIONS', $canReceiveNotifications);
```
```smarty
<!-- Template -->
{if $CAN_RECEIVE_NOTIFICATIONS}
```

---

### ❌ WRONG: JSON Encoding
```smarty
<input type="hidden" value='{\App\Json::encode($SELECTED_FIELDS)}' />
```

### ✅ CORRECT: Pre-encoded Data
```php
// Controller
$viewer->assign('SELECTED_FIELDS_JSON', \App\Json::encode($selectedFields));
```
```smarty
<!-- Template -->
<input type="hidden" value='{$SELECTED_FIELDS_JSON}' />
```

---

### ❌ WRONG: Array Operations
```smarty
{assign var=MANDATORY_FIELDS value=array()}
{foreach from=$FIELDS item=FIELD}
    {if $FIELD->isMandatory()}
        {array_push($MANDATORY_FIELDS, $FIELD->getName())}
    {/if}
{/foreach}
```

### ✅ CORRECT: Pre-processed Array
```php
// Controller
$mandatoryFields = [];
foreach ($fields as $field) {
    if ($field->isMandatory()) {
        $mandatoryFields[] = $field->getName();
    }
}
$viewer->assign('MANDATORY_FIELDS', $mandatoryFields);
```
```smarty
<!-- Template -->
{foreach from=$MANDATORY_FIELDS item=FIELD_NAME}
    {$FIELD_NAME}
{/foreach}
```

---

### ❌ WRONG: Utility Helper Calls
```smarty
<div data-content="{Vtiger_Util_Helper::toSafeHTML($CONTENT)}">
```

### ✅ CORRECT: Pre-sanitized Data
```php
// Controller
$viewer->assign('CONTENT_SAFE', Vtiger_Util_Helper::toSafeHTML($content));
```
```smarty
<!-- Template -->
<div data-content="{$CONTENT_SAFE}">
```

---

### ❌ WRONG: Field Formatting
```smarty
<td>{Vtiger_Datetime_UIType::getDisplayDateTimeValue($RECORD->get('created_time'))}</td>
<td>{\App\Fields\Owner::getLabel($RECORD->get('assigned_user_id'))}</td>
```

### ✅ CORRECT: Pre-formatted Values
```php
// Controller or Model
foreach ($records as $record) {
    $record->set('created_time_display', 
        Vtiger_Datetime_UIType::getDisplayDateTimeValue($record->get('created_time')));
    $record->set('assigned_user_display', 
        \App\Fields\Owner::getLabel($record->get('assigned_user_id')));
}
```
```smarty
<!-- Template -->
<td>{$RECORD->get('created_time_display')}</td>
<td>{$RECORD->get('assigned_user_display')}</td>
```

---

## Common Controller Pattern

```php
class Vtiger_MyView_View extends Vtiger_Index_View {
    
    public function process(Vtiger_Request $request) {
        $viewer = $this->getViewer($request);
        $module = $request->getModule();
        
        // 1. Fetch data from models
        $recordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), $module);
        
        // 2. Prepare configuration
        $config = [
            'someConfig' => AppConfig::main('SOME_CONFIG'),
            'otherConfig' => AppConfig::performance('OTHER_CONFIG'),
        ];
        
        // 3. Check permissions
        $permissions = [
            'canEdit' => $recordModel->isEditable(),
            'canDelete' => $recordModel->isDeletable(),
        ];
        
        // 4. Process data
        $displayData = $this->prepareDisplayData($recordModel);
        
        // 5. JSON encode where needed
        $jsonData = \App\Json::encode($someArray);
        
        // 6. Assign everything to viewer
        $viewer->assign('RECORD', $recordModel);
        $viewer->assign('CONFIG', $config);
        $viewer->assign('PERMISSIONS', $permissions);
        $viewer->assign('DISPLAY_DATA', $displayData);
        $viewer->assign('JSON_DATA', $jsonData);
        
        // 7. Render
        $viewer->view('MyView.tpl', $module);
    }
    
    protected function prepareDisplayData($recordModel) {
        // All data processing logic here
        return [
            'formatted_date' => $this->formatDate($recordModel->get('date')),
            'owner_name' => $this->getOwnerName($recordModel->get('assigned_user_id')),
            // ... more processed data
        ];
    }
}
```

---

## Automated Tools

### Check for Violations
```bash
# Single file
php scripts/analyze_tpl_violations.php layouts/basic/modules/Vtiger/Header.tpl

# Entire directory
php scripts/analyze_tpl_violations.php layouts/basic/modules/Vtiger/

# Generate JSON report
php scripts/analyze_tpl_violations.php layouts/basic/modules/ json
```

### Refactor File
```bash
# Dry run (no changes)
php scripts/refactor_tpl.php layouts/basic/modules/Vtiger/Header.tpl --dry-run

# Actually refactor
php scripts/refactor_tpl.php layouts/basic/modules/Vtiger/Header.tpl
```

### Pre-commit Hook
Add to `.git/hooks/pre-commit`:
```bash
#!/bin/bash
# Check TPL files for MVC violations

CHANGED_TPL_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.tpl$')

if [ -n "$CHANGED_TPL_FILES" ]; then
    echo "Checking TPL files for MVC violations..."
    
    for file in $CHANGED_TPL_FILES; do
        php scripts/analyze_tpl_violations.php "$file" > /tmp/tpl_check.txt 2>&1
        
        if [ $? -ne 0 ]; then
            echo "❌ MVC violations found in: $file"
            cat /tmp/tpl_check.txt
            echo ""
            echo "Run: php scripts/refactor_tpl.php $file --dry-run"
            echo "to see suggested fixes."
            exit 1
        fi
    done
    
    echo "✓ All TPL files are MVC-compliant"
fi
```

---

## What Templates SHOULD Do

### Display Data
```smarty
<h1>{$TITLE}</h1>
<p>{$DESCRIPTION}</p>
```

### Simple Loops
```smarty
{foreach from=$ITEMS item=ITEM}
    <li>{$ITEM.name}</li>
{/foreach}
```

### Simple Conditionals
```smarty
{if $IS_ADMIN}
    <button>Admin Action</button>
{/if}
```

### Include Other Templates
```smarty
{include file='SubTemplate.tpl'|@vtemplate_path:$MODULE}
```

### Apply Translations
```smarty
{"LBL_SAVE"|t:$MODULE}
```

### Access Pre-processed Variables
```smarty
{$USER_MODEL->get('name')}
{$RECORD->getDisplayValue('fieldname')}
```

---

## What Templates SHOULD NOT Do

### ❌ Business Logic
```smarty
{* NO! *}
{if $USER_MODEL->isAdmin() || $USER_MODEL->hasPermission('edit')}
```

### ❌ Data Processing
```smarty
{* NO! *}
{assign var=TOTAL value=$PRICE * $QUANTITY}
```

### ❌ Database Queries
```smarty
{* NO! *}
{assign var=RECORDS value=Model::getRecords()}
```

### ❌ Configuration Access
```smarty
{* NO! *}
{if AppConfig::main('FEATURE_ENABLED')}
```

### ❌ Complex Calculations
```smarty
{* NO! *}
{math equation="(x + y) * z" x=$A y=$B z=$C assign="result"}
```

---

## Testing Your Changes

### 1. Run Violation Check
```bash
php scripts/analyze_tpl_violations.php path/to/your/file.tpl
```

### 2. Verify Functionality
```bash
# Test with curl (from repo rules)
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=YourModule&view=YourView"
```

### 3. Check Browser
- Open the page in browser
- Verify all functionality works
- Check browser console for errors
- Test all interactive features

### 4. Code Review Checklist
- [ ] No direct model calls
- [ ] No config access
- [ ] No permission checks
- [ ] Controller has all preparation logic
- [ ] Template only displays data
- [ ] Tests pass
- [ ] Documentation updated

---

## Common Refactoring Mistakes

### Mistake 1: Moving Logic to Template Assignment
```smarty
{* Still wrong! Logic in template *}
{assign var=IS_ALLOWED value=$USER->isAdmin() || $USER->hasPermission('edit')}
```
**Fix:** Move to controller!

### Mistake 2: Partial Refactoring
```php
// Controller
$viewer->assign('USER_NAME', $user->getName());
```
```smarty
{* Template still accessing model method *}
<div>{$USER_NAME} ({$USER_MODEL->getEmail()})</div>
```
**Fix:** Pass email from controller too!

### Mistake 3: Creating God Controllers
```php
// Don't put everything in one giant method!
public function process() {
    // 500 lines of code...
}
```
**Fix:** Break into smaller helper methods!

---

## Need Help?

1. **Read full documentation:**
   `documentation/refactoring-tpl-to-be-mvc-compliant.md`

2. **Run analyzer:**
   `php scripts/analyze_tpl_violations.php your-file.tpl`

3. **Check examples:**
   Look at recently refactored files in git history

4. **Ask team:**
   If unsure, ask in code review!

---

**Remember:** When in doubt, if something feels like business logic, it probably doesn't belong in the template!

