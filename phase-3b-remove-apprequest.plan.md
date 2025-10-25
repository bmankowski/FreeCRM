<!-- Phase 3B Implementation Plan -->
# Phase 3B: Remove AppRequest - Medium Effort Refactoring

## Status: 📋 READY TO IMPLEMENT

**Estimated Time:** 1-2 hours  
**Complexity:** Medium  
**Risk Level:** Low-Medium

## Overview

Refactor 7 model files to remove `\App\Http\AppRequest` usage by passing data explicitly from controllers. These files use AppRequest for view/action detection and relation parameters - legitimate business data that should be passed through method signatures.

## Strategy

Following the migration document Pattern 1, 3, and 4:
- **Pattern 1**: View/UI Context Checking → Pass view context as parameter
- **Pattern 3**: Action/Mode Detection → Pass boolean flag or action name
- **Pattern 4**: Relation/Return Parameters → Pass parameters explicitly

---

## File 1: Vtiger/Models/Module.php ⭐ HIGH PRIORITY

**Location:** `src/Modules/Vtiger/Models/Module.php` (lines 247-254)  
**Pattern:** Relation/Return Parameters (Pattern 4)  
**Complexity:** ⭐⭐ Medium  
**Impact:** 🔥🔥🔥 High (affects all record saves with relations)

### Current Code

```php
public function saveRecord(\App\Modules\Vtiger\Models\Record $recordModel)
{
    // ... existing code ...
    
    // vtlib customization: Hook provide to enable generic module relation.
    if (\App\Http\AppRequest::get('createmode') === 'link') {              // ❌
        $forModule = \App\Http\AppRequest::get('return_module');           // ❌
        $forCrmid = \App\Http\AppRequest::get('return_id');                // ❌
        if ($forModule && $forCrmid) {
            $focus = \App\CRMEntity::getInstance($forModule);
            \App\Utils\Utils::relateEntities($focus, $forModule, $forCrmid, $moduleName, $recordId);
        }
    }
    
    // ... rest of code ...
}
```

### Refactored Code

```php
/**
 * Function to save a given record model of the current module
 * @param \App\Modules\Vtiger\Models\Record $recordModel
 * @param array $relationParams Optional relation parameters for linking records
 */
public function saveRecord(\App\Modules\Vtiger\Models\Record $recordModel, $relationParams = null)
{
    // ... existing code ...
    
    // vtlib customization: Hook provide to enable generic module relation.
    if ($relationParams && $relationParams['createmode'] === 'link') {    // ✅
        $forModule = $relationParams['return_module'];                     // ✅
        $forCrmid = $relationParams['return_id'];                          // ✅
        if ($forModule && $forCrmid) {
            $focus = \App\CRMEntity::getInstance($forModule);
            \App\Utils\Utils::relateEntities($focus, $forModule, $forCrmid, $moduleName, $recordId);
        }
    }
    
    // ... rest of code ...
}
```

### Implementation Steps

1. ✅ **Update `Module.php`**
   - Add `$relationParams = null` parameter to `saveRecord()`
   - Replace AppRequest calls with `$relationParams` array access
   - Add PHPDoc for new parameter

2. ✅ **Update `Record.php`**
   - Modify `save()` method to extract and pass relation params
   
   ```php
   public function save($request = null)
   {
       $db = \App\Database\PearDatabase::getInstance();
       $db->startTransaction();
       if ($this->getModule()->isInventory()) {
           $this->initInventoryData($request);
       }
       
       // Extract relation parameters if request is available
       $relationParams = null;
       if ($request && $request->get('createmode')) {
           $relationParams = [
               'createmode' => $request->get('createmode'),
               'return_module' => $request->get('return_module'),
               'return_id' => $request->get('return_id')
           ];
       }
       
       $this->getModule()->saveRecord($this, $relationParams);  // ✅ Pass params
       $db->completeTransaction();
       
       // ... rest of code ...
   }
   ```

3. ✅ **Test**
   - Create a new record with relation (e.g., HelpDesk from ServiceContracts)
   - Verify relation is created correctly
   - Test without relation params (normal save)

### Files to Modify
- `src/Modules/Vtiger/Models/Module.php`
- `src/Modules/Vtiger/Models/Record.php`

---

## File 2: HelpDesk/Models/Record.php

**Location:** `src/Modules/HelpDesk/Models/Record.php` (lines 82-90)  
**Pattern:** Relation/Return Parameters (Pattern 4)  
**Complexity:** ⭐⭐ Medium  
**Impact:** 🔥 Medium (specific to HelpDesk-ServiceContracts relation)

### Current Code

```php
public function saveToDb()
{
    parent::saveToDb();
    $forModule = \App\Http\AppRequest::get('return_module');         // ❌
    $forCrmid = \App\Http\AppRequest::get('return_id');              // ❌
    if (\App\Http\AppRequest::get('return_action') && $forModule && $forCrmid && $forModule === 'ServiceContracts') {  // ❌
        \App\CRMEntity::getInstance($forModule)->save_related_module($forModule, $forCrmid, \App\Http\AppRequest::get('module'), $this->getId());  // ❌
    }
}
```

### Refactored Code

```php
/**
 * Save record to database
 * @param array $relationParams Optional relation parameters
 */
public function saveToDb($relationParams = null)
{
    parent::saveToDb($relationParams);
    
    if ($relationParams && !empty($relationParams['return_action'])) {   // ✅
        $forModule = $relationParams['return_module'];                   // ✅
        $forCrmid = $relationParams['return_id'];                        // ✅
        $currentModule = $relationParams['current_module'];              // ✅
        
        if ($forModule && $forCrmid && $forModule === 'ServiceContracts') {
            \App\CRMEntity::getInstance($forModule)->save_related_module(
                $forModule, 
                $forCrmid, 
                $currentModule, 
                $this->getId()
            );
        }
    }
}
```

### Implementation Steps

1. ✅ **Update method signature**
   - Add `$relationParams = null` parameter
   - Pass to `parent::saveToDb($relationParams)`

2. ✅ **Update Record.php `saveToDb()`**
   - Modify to accept and pass `$relationParams`
   
   ```php
   public function saveToDb($relationParams = null)
   {
       $entityInstance = $this->getModule()->getEntityInstance();
       // ... existing save logic ...
       
       // Store relation params for child classes
       $this->relationParams = $relationParams;
   }
   ```

3. ✅ **Update `save()` in Record.php**
   - Extract relation params from request
   - Pass to `saveToDb()`
   
   ```php
   // In save() method, after getting $relationParams
   if (!$recordModel->isNew() && !$recordModel->isMandatorySave() && empty($recordModel->getPreviousValue())) {
       \App\Log::info('ERR_NO_DATA');
   } else {
       $recordModel->saveToDb($relationParams);  // ✅ Pass params
   }
   ```

4. ✅ **Test**
   - Create HelpDesk ticket from ServiceContracts detail view
   - Verify relation is created
   - Test normal HelpDesk save (without relation)

### Files to Modify
- `src/Modules/HelpDesk/Models/Record.php`
- `src/Modules/Vtiger/Models/Record.php` (update `saveToDb()`)
- `src/Modules/Vtiger/Models/Module.php` (pass params to `saveToDb()`)

---

## File 3: ServiceContracts/Models/Record.php

**Location:** `src/Modules/ServiceContracts/Models/Record.php` (lines 17-25)  
**Pattern:** Relation/Return Parameters (Pattern 4)  
**Complexity:** ⭐⭐ Medium  
**Impact:** 🔥 Medium (specific to ServiceContracts-HelpDesk relation)

### Current Code

```php
public function saveToDb()
{
    parent::saveToDb();
    $forModule = \App\Http\AppRequest::get('return_module');         // ❌
    $forCrmid = \App\Http\AppRequest::get('return_id');              // ❌
    if (\App\Http\AppRequest::get('return_action') && $forModule && $forCrmid && $forModule === 'HelpDesk') {  // ❌
        \App\CRMEntity::getInstance($forModule)->save_related_module($forModule, $forCrmid, \App\Http\AppRequest::get('module'), $this->getId());  // ❌
    }
}
```

### Refactored Code

```php
/**
 * Save record to database
 * @param array $relationParams Optional relation parameters
 */
public function saveToDb($relationParams = null)
{
    parent::saveToDb($relationParams);
    
    if ($relationParams && !empty($relationParams['return_action'])) {   // ✅
        $forModule = $relationParams['return_module'];                   // ✅
        $forCrmid = $relationParams['return_id'];                        // ✅
        $currentModule = $relationParams['current_module'];              // ✅
        
        if ($forModule && $forCrmid && $forModule === 'HelpDesk') {
            \App\CRMEntity::getInstance($forModule)->save_related_module(
                $forModule, 
                $forCrmid, 
                $currentModule, 
                $this->getId()
            );
        }
    }
}
```

### Implementation Steps

Same as HelpDesk/Models/Record.php - identical pattern, just different module check.

### Files to Modify
- `src/Modules/ServiceContracts/Models/Record.php`

---

## File 4: Users/Models/Privileges.php

**Location:** `src/Modules/Users/Models/Privileges.php` (line 292)  
**Pattern:** Action/Mode Detection (Pattern 3)  
**Complexity:** ⭐ Easy  
**Impact:** 🔥 Low (optimization for shared owner saves)

### Current Code

```php
public static function setSharedOwner($shownerid, $recordId)
{
    // ... code ...
    
    $saveFull = true;
    $db = \App\Db::getInstance();
    if (\App\Http\AppRequest::get('action') == 'SaveAjax' && \App\Http\AppRequest::has('field') && \App\Http\AppRequest::get('field') != 'shownerid') {  // ❌
        $saveFull = false;
    }
    
    // ... rest of code ...
}
```

### Refactored Code

```php
/**
 * Set shared owner
 * @param mixed $shownerid
 * @param int $recordId
 * @param bool $saveFull Whether to save full permissions (default: true)
 */
public static function setSharedOwner($shownerid, $recordId, $saveFull = true)
{
    // ... code ...
    
    $db = \App\Db::getInstance();
    // $saveFull now passed as parameter  ✅
    
    // ... rest of code ...
}
```

### Implementation Steps

1. ✅ **Update `Privileges.php`**
   - Add `$saveFull = true` parameter
   - Remove AppRequest check
   - Add PHPDoc

2. ✅ **Update caller in `Module.php`**
   
   ```php
   // In saveRecord() method
   $recordId = $recordModel->getId();
   
   // Determine if it's a partial save (SaveAjax for non-shownerid field)
   $saveFull = true;
   if ($relationParams && isset($relationParams['action']) && 
       $relationParams['action'] === 'SaveAjax' && 
       isset($relationParams['field']) && 
       $relationParams['field'] !== 'shownerid') {
       $saveFull = false;
   }
   
   \App\Modules\Users\Models\Privileges::setSharedOwner(
       $recordModel->get('shownerid'), 
       $recordId,
       $saveFull  // ✅ Pass flag
   );
   ```

3. ✅ **Update `Record.php` to pass action/field info**
   
   ```php
   // In save() method when extracting relationParams
   if ($request) {
       $relationParams = [
           'createmode' => $request->get('createmode'),
           'return_module' => $request->get('return_module'),
           'return_id' => $request->get('return_id'),
           'action' => $request->get('action'),        // ✅ Add
           'field' => $request->get('field'),          // ✅ Add
           'current_module' => $request->getModule()   // ✅ Add
       ];
   }
   ```

4. ✅ **Test**
   - Save record normally (full save)
   - Use SaveAjax to update single field
   - Use SaveAjax to update shownerid field
   - Verify permissions saved correctly in each case

### Files to Modify
- `src/Modules/Users/Models/Privileges.php`
- `src/Modules/Vtiger/Models/Module.php`
- `src/Modules/Vtiger/Models/Record.php`

---

## File 5: Announcements/Models/Module.php

**Location:** `src/Modules/Announcements/Models/Module.php` (line 18)  
**Pattern:** View/UI Context Checking (Pattern 1)  
**Complexity:** ⭐ Easy  
**Impact:** 🔥 Low (only affects announcement display logic)

### Current Code

```php
public function checkActive()
{
    if (\App\Http\AppRequest::get('view') == 'Login' || !$this->isActive()) {  // ❌
        return false;
    }
    $this->loadAnnouncements();
    return true;
}
```

### Refactored Code

```php
/**
 * Check if announcements should be displayed
 * @param string $view Current view name
 * @return bool
 */
public function checkActive($view = null)
{
    if ($view === 'Login' || !$this->isActive()) {  // ✅
        return false;
    }
    $this->loadAnnouncements();
    return true;
}
```

### Implementation Steps

1. ✅ **Update `Module.php`**
   - Add `$view = null` parameter
   - Replace AppRequest call with parameter check
   - Add PHPDoc

2. ✅ **Find and update all callers**
   
   ```bash
   # Find callers
   grep -r "checkActive()" src/Modules/Announcements/
   ```
   
   Update each caller to pass view:
   ```php
   $announcementModule->checkActive($request->get('view'));  // ✅
   ```

3. ✅ **Test**
   - Display announcements on normal pages
   - Verify announcements hidden on Login page
   - Test without view parameter (should work)

### Files to Modify
- `src/Modules/Announcements/Models/Module.php`
- All files that call `checkActive()` (to be identified)

---

## File 6: OSSMailView/Models/Record.php

**Location:** `src/Modules/OSSMailView/Models/Record.php` (line 34)  
**Pattern:** View/UI Context Checking (Pattern 1)  
**Complexity:** ⭐ Easy  
**Impact:** 🔥 Low (only affects email content display)

### Current Code

```php
public function get($key)
{
    $value = parent::get($key);
    if ($key === 'content' && \App\Http\AppRequest::get('view') == 'Detail') {  // ❌
        return \vtlib\Functions::removeHtmlTags(
            array('link', 'style', 'a', 'img', 'script', 'base'), 
            \vtlib\Functions::getHtmlOrPlainText($value)
        );
    }
    // ... rest of code ...
}
```

### Refactored Code

```php
/**
 * Get field value
 * @param string $key Field name
 * @param bool $isDetailView Whether this is detail view (optional)
 * @return mixed
 */
public function get($key, $isDetailView = null)
{
    $value = parent::get($key);
    
    // If not explicitly set, try to detect from context (backward compatibility)
    if ($isDetailView === null) {
        $isDetailView = \App\Http\AppRequest::get('view') === 'Detail';  // Temporary fallback
    }
    
    if ($key === 'content' && $isDetailView) {  // ✅
        return \vtlib\Functions::removeHtmlTags(
            array('link', 'style', 'a', 'img', 'script', 'base'), 
            \vtlib\Functions::getHtmlOrPlainText($value)
        );
    }
    // ... rest of code ...
}
```

**Note:** This one is trickier because `get()` is called in many places. We'll use a temporary fallback for backward compatibility.

### Implementation Steps

1. ✅ **Update `Record.php`**
   - Add `$isDetailView = null` parameter
   - Add fallback to AppRequest (temporary)
   - Add PHPDoc warning this is transitional

2. ✅ **Find main callers** (optional - can be done later)
   
   ```bash
   # Find where OSSMailView records are used
   grep -r "OSSMailView.*->get(" src/
   ```

3. ✅ **Test**
   - View email in Detail view (content should be sanitized)
   - View email in List view (content should be as-is)
   - Test email display in various contexts

### Files to Modify
- `src/Modules/OSSMailView/Models/Record.php`

**Decision Point:** Should we keep the AppRequest fallback or update all callers now?
- **Recommendation:** Keep fallback for now, mark as TODO for future cleanup

---

## File 7: Vtiger/Models/Field.php

**Location:** `src/Modules/Vtiger/Models/Field.php` (line 771)  
**Pattern:** View/UI Context Checking (Pattern 1)  
**Complexity:** ⭐⭐ Medium  
**Impact:** 🔥🔥 Medium (affects owner field rendering)

### Current Code

```php
case 'owner':
case 'userCreator':
case 'sharedOwner':
    if (!\App\AppConfig::performance('SEARCH_OWNERS_BY_AJAX') || 
        in_array(\App\Http\AppRequest::get('module'), ['CustomView', 'Workflows', 'PDF', 'MappedFields', 'DataAccess', 'Reports']) ||  // ❌
        \App\Http\AppRequest::get('mode') === 'showAdvancedSearch') {  // ❌
        
        $userList = \App\Fields\Owner::getInstance($this->getModuleName(), $currentUser)->getAccessibleUsers('', $fieldDataType);
        $groupList = \App\Fields\Owner::getInstance($this->getModuleName(), $currentUser)->getAccessibleGroups('', $fieldDataType);
        // ... more code ...
    }
    break;
```

### Refactored Code

```php
/**
 * Get picklist values for the field
 * @param array $context Optional context information (module, mode, etc.)
 * @return array
 */
public function getPicklistValues($context = [])
{
    // ... existing code ...
    
    case 'owner':
    case 'userCreator':
    case 'sharedOwner':
        $module = $context['module'] ?? null;
        $mode = $context['mode'] ?? null;
        
        if (!\App\AppConfig::performance('SEARCH_OWNERS_BY_AJAX') || 
            in_array($module, ['CustomView', 'Workflows', 'PDF', 'MappedFields', 'DataAccess', 'Reports']) ||  // ✅
            $mode === 'showAdvancedSearch') {  // ✅
            
            $userList = \App\Fields\Owner::getInstance($this->getModuleName(), $currentUser)->getAccessibleUsers('', $fieldDataType);
            $groupList = \App\Fields\Owner::getInstance($this->getModuleName(), $currentUser)->getAccessibleGroups('', $fieldDataType);
            // ... more code ...
        }
        break;
    
    // ... rest of code ...
}
```

**Note:** This requires checking what method contains this code and updating its signature.

### Implementation Steps

1. ✅ **Identify the method containing this code**
   
   ```bash
   grep -B 20 "SEARCH_OWNERS_BY_AJAX" src/Modules/Vtiger/Models/Field.php
   ```

2. ✅ **Update method signature**
   - Add `$context = []` parameter
   - Extract module and mode from context
   - Add PHPDoc

3. ✅ **Find and update callers**
   - Pass context array with module and mode
   
   ```php
   $field->getPicklistValues([
       'module' => $request->getModule(),
       'mode' => $request->get('mode')
   ]);
   ```

4. ✅ **Add backward compatibility fallback**
   
   ```php
   // If context not provided, fallback to AppRequest (temporary)
   if (empty($context)) {
       $context = [
           'module' => \App\Http\AppRequest::get('module'),
           'mode' => \App\Http\AppRequest::get('mode')
       ];
   }
   ```

5. ✅ **Test**
   - Test owner field in various contexts
   - Test in CustomView, Workflows, Reports
   - Test advanced search
   - Verify performance (AJAX vs. full list)

### Files to Modify
- `src/Modules/Vtiger/Models/Field.php`
- Files that call the method containing this logic (to be identified)

---

## Implementation Order

Recommended order based on dependencies and impact:

1. **Vtiger/Models/Module.php** (File 1) - Foundation for others
2. **Users/Models/Privileges.php** (File 4) - Used by Module.php
3. **HelpDesk/Models/Record.php** (File 2) - Depends on Record.php changes
4. **ServiceContracts/Models/Record.php** (File 3) - Same pattern as HelpDesk
5. **Announcements/Models/Module.php** (File 5) - Independent, easy win
6. **OSSMailView/Models/Record.php** (File 6) - Independent, easy win
7. **Vtiger/Models/Field.php** (File 7) - More complex, save for last

---

## Testing Strategy

### Test Cases

1. **Relation Handling**
   ```bash
   # Create HelpDesk from ServiceContracts (tests File 1, 2)
   # Create ServiceContracts from HelpDesk (tests File 1, 3)
   # Create any record with createmode=link
   ```

2. **SaveAjax Optimization**
   ```bash
   # Update single field via SaveAjax (tests File 4)
   # Update shownerid field via SaveAjax
   # Full save (normal action)
   ```

3. **View Context**
   ```bash
   # View announcements on normal page (tests File 5)
   # View announcements on login page (should be hidden)
   # View email in detail view (tests File 6)
   # View email in list view
   ```

4. **Owner Field**
   ```bash
   # Edit owner field in normal module (tests File 7)
   # Edit owner in CustomView, Workflows, Reports
   # Test advanced search with owner field
   ```

### Regression Tests

Run existing curl tests to ensure nothing broke:

```bash
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L -d "username=admin&password=admin" -X POST "http://localhost/index.php?module=Users&action=Login"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L "http://localhost/index.php?module=Leads&view=Detail&record=112"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L "http://localhost/index.php?module=Leads&view=List&mid=48&parent=47"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L "http://localhost/index.php?module=Vtiger&parent=Settings&view=Index"
```

---

## Files Summary

| File | Lines | Pattern | Complexity | Impact | Priority |
|------|-------|---------|------------|--------|----------|
| Vtiger/Models/Module.php | 247-254 | Pattern 4 | ⭐⭐ | 🔥🔥🔥 | 1 |
| HelpDesk/Models/Record.php | 82-90 | Pattern 4 | ⭐⭐ | 🔥 | 3 |
| ServiceContracts/Models/Record.php | 17-25 | Pattern 4 | ⭐⭐ | 🔥 | 4 |
| Users/Models/Privileges.php | 292 | Pattern 3 | ⭐ | 🔥 | 2 |
| Announcements/Models/Module.php | 18 | Pattern 1 | ⭐ | 🔥 | 5 |
| OSSMailView/Models/Record.php | 34 | Pattern 1 | ⭐ | 🔥 | 6 |
| Vtiger/Models/Field.php | 771 | Pattern 1 | ⭐⭐ | 🔥🔥 | 7 |

---

## Expected Benefits

After completing Phase 3B:

- ✅ **7 more files** cleaned of AppRequest dependencies
- ✅ **Cleaner architecture** - Business logic separated from HTTP layer
- ✅ **Better testability** - Can test with mock data instead of HTTP requests
- ✅ **API-ready** - These methods can be called from REST API, CLI, background jobs
- ✅ **Maintainability** - Clear method signatures showing what data is needed

---

## Next Steps After Phase 3B

After completing Phase 3B, we can:

1. **Review Phase 3C** - Decide if we want to tackle complex save methods or use RequestContext bridge
2. **Create comprehensive tests** - Add unit tests for all refactored methods
3. **Documentation** - Update API documentation with new method signatures
4. **Phase 4** - Start removing AppRequest from Views and other components

---

## Notes

- All refactorings maintain backward compatibility through optional parameters
- Some methods include temporary AppRequest fallbacks for gradual migration
- Test thoroughly after each file to catch regressions early
- Can be implemented incrementally (one file per session if needed)
- Consider using sed for bulk updates to save() method callers if needed

