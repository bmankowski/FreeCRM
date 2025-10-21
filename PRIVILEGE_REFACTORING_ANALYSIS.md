# Privilege Refactoring - Usage Analysis

**Date:** 2025-10-21  
**Strategy:** Move responsibilities to EXISTING classes - no new classes needed

---

## Current Usage Statistics

| Method | Total Calls | Real Code | Action |
|--------|-------------|-----------|--------|
| `getCurrentUserId()` | 76 | ~70 | **KEEP** (session) |
| `getCurrentUserModel()` | 37 | ~30 | **KEEP** (but fix return) |
| `getPrivilegesFile()` | 21 | 7 | **MOVE** to Privileges |
| `getSharingFile()` | 11 | 6 | **INTERNALIZE** in Privilege |
| `getUserModel()` | 24 | 15 | **REPLACE** with Record::getInstanceById |
| `isExists()` | 11 | 3 | **REMOVE** (duplicate) |
| `getActiveAdminId()` | 7 | 2 | **REMOVE** (duplicate) |
| `getUserIdByName()` | 8 | 1 | **REMOVE** (duplicate) |

---

## Key Finding: No New Classes Needed!

### Privilege Loading → Already in Privileges Model
**Current:**
```php
// User.php (wrong place)
public static function getPrivilegesFile($userId) { ... }

// Privileges.php (correct place, already uses it!)
public static function getInstanceById($userId) {
    $valueMap = \App\User::getPrivilegesFile($userId); // ← calls User!
}
```

**Solution:** Move implementation from User.php TO Privileges.php where it's already used.

### Sharing Files → Only Used in Privilege Class
**Current:**
```php
// User.php (wrong place)
public static function getSharingFile($userId) { ... }

// Privilege.php uses it 3 times
// PrivilegeQuery.php uses it 2 times
// CRMEntity.php uses it 1 time (legacy)
```

**Solution:** Move to Privilege.php as protected method, make CRMEntity use Privilege class.

### User Model Creation → Already in Record
**Current:**
```php
// User.php
public static function getUserModel($userId) { ... }

// Record.php ALREADY has this!
public static function getInstanceById($userId, $module) { ... }
```

**Solution:** Replace all `getUserModel()` calls with `Record::getInstanceById()`.

---

## Refactoring Actions (Simplified)

### Action 1: Remove Duplicate Static Methods
**Files to update:** 3 call sites

```bash
# isExists - 3 calls in real code
src/Webservices/VtigerCRMObjectMeta.php:2
api/webservice/Portal/BaseModule/Privileges.php:1

# getActiveAdminId - 2 calls
src/Modules/Users/Users.php:1
src/Modules/Settings/AutomaticAssignment/Models/Record.php:1

# getUserIdByName - 1 call
src/Modules/Import/Actions/Data.php:4
```

**Action:** Replace with `\App\Modules\Users\Models\Record::*` equivalents

**Estimated effort:** 15 minutes (sed + verify)

---

### Action 2: Move Privilege File Loading to Privileges Model
**Current callers (7):**
- `Privilege.php` (1) → Will use Privileges::getPrivilegesFile()
- `Fields/Owner.php` (2) → Will use Privileges::getPrivilegesFile()
- `Modules/Users/Models/Record.php` (1) → Internal, already OK
- `Utils/UserInfoUtil.php` (1) → Legacy, update to use Privileges
- `CRMEntity.php` (1) → Legacy, update to use Privileges
- `Modules/Users/Models/Privileges.php` (1) → THIS IS THE RIGHT PLACE!

**Implementation:**
```php
// In src/Modules/Users/Models/Privileges.php
// Move entire getPrivilegesFile method FROM User.php TO here
protected static $userPrivilegesCache = []; // from User.php

public static function getPrivilegesFile($userId)
{
    // Copy implementation from \App\User::getPrivilegesFile()
    // Exact same code, just different location
}
```

**Files to update:**
1. Copy method from User.php → Privileges.php
2. Update 7 call sites to use `\App\Modules\Users\Models\Privileges::getPrivilegesFile()`
3. Remove from User.php

**Estimated effort:** 30 minutes

---

### Action 3: Internalize Sharing Files in Privilege Class
**Current callers (6):**
- `Privilege.php` (3) → All in sharing methods, make internal
- `PrivilegeQuery.php` (2) → Can access via Privilege methods
- `CRMEntity.php` (1) → Legacy, update to use Privilege

**Implementation:**
```php
// In src/Privilege.php
protected static $userSharingCache = []; // from User.php

protected static function getSharingFile($userId)
{
    // Copy implementation from \App\User::getSharingFile()
    // Make it protected, only for internal use
}
```

**Files to update:**
1. Copy method from User.php → Privilege.php (as protected)
2. Update CRMEntity.php and PrivilegeQuery.php to use Privilege class methods
3. Remove from User.php

**Estimated effort:** 20 minutes

---

### Action 4: Replace getUserModel() with Record::getInstanceById()
**Current callers (15):**

```php
// OLD pattern:
$userModel = \App\User::getUserModel($userId);
$email = $userModel->getDetail('email1');

// NEW pattern:
$userModel = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
$email = $userModel->get('email1');
```

**Files to update:**
- `src/Fields/Owner.php` (4 calls)
- `src/QueryGenerator.php` (1 call)
- `src/CustomView.php` (1 call)
- `src/Modules/Users/Actions/Login.php` (1 call)
- `src/Modules/com_vtiger_workflow/tasks/VTSendNotificationTask.php` (1 call)
- `src/Modules/Settings/AutomaticAssignment/Models/Record.php` (1 call)
- `src/Modules/Notification/Actions/Notification.php` (1 call)
- `src/Modules/Notification/cron/Notifications.php` (1 call)
- `src/Fields/Email.php` (1 call)
- `src/Modules/Reports/Models/ScheduleReports.php` (2 calls)
- `src/Modules/Reports/VTScheduledReport.php` (1 call)

**Automated replacement:**
```bash
find src/ -name "*.php" -exec sed -i \
  's/\\App\\User::getUserModel(/\\App\\Modules\\Users\\Models\\Record::getInstanceById(/g' {} \;

# Then add second parameter 'Users' where needed (manual review)
```

**Estimated effort:** 45 minutes (automated + manual review for second param)

---

### Action 5: Fix getCurrentUserModel() Return
**Issue:** Currently returns `\App\User` instance, should return `\App\Modules\Users\Models\Record`

```php
// In User.php - BEFORE
public static function getUserModel($userId)
{
    // Returns \App\User instance
}

public static function getCurrentUserModel()
{
    return static::getUserModel(static::$currentUserId);
}

// In User.php - AFTER
public static function getCurrentUserModel()
{
    if (static::$currentUserCache) {
        return static::$currentUserCache;
    }
    
    if (!static::$currentUserId) {
        static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
    }
    
    return static::$currentUserCache = \App\Modules\Users\Models\Record::getInstanceById(
        static::$currentUserId,
        'Users'
    );
}
```

**Impact:** 30+ call sites, but most should work without changes (Record has same/better interface)

**Estimated effort:** 20 minutes + testing

---

### Action 6: Simplify User.php to Session Only
**Remove from User.php:**
- `getUserModel()` method (replaced by getCurrentUserModel)
- `getPrivilegesFile()` (moved to Privileges)
- `getSharingFile()` (moved to Privilege)
- `isExists()` (duplicate)
- `getActiveAdminId()` (duplicate)
- `getUserIdByName()` (duplicate)
- Instance properties and methods (no longer needed)
- Privilege cache variables (moved to respective classes)

**Keep in User.php:**
- `getCurrentUserId()`
- `setCurrentUserId()`
- `getCurrentUserRealId()`
- `getCurrentUserModel()` (fixed to return Record)
- `clearCache()` (session cache only)

**Result:** User.php reduced from 356 lines → ~100 lines

**Estimated effort:** 15 minutes (deletion + cleanup)

---

## Implementation Order

### Phase 1: Remove Duplicates (Low Risk)
1. **Action 1** - Replace duplicate static method calls
2. Remove duplicate methods from User.php
3. **Test with curl**

**Estimated: 30 minutes**

### Phase 2: Move Privilege Loading (Medium Risk)
1. **Action 2** - Move getPrivilegesFile to Privileges model
2. Update all 7 call sites
3. **Test with curl**

**Estimated: 45 minutes**

### Phase 3: Internalize Sharing (Medium Risk)
1. **Action 3** - Move getSharingFile to Privilege class
2. Update CRMEntity and PrivilegeQuery
3. **Test with curl**

**Estimated: 30 minutes**

### Phase 4: Replace getUserModel (Medium Risk)
1. **Action 4** - Replace all getUserModel calls
2. **Action 5** - Fix getCurrentUserModel return type
3. **Test with curl**

**Estimated: 60 minutes**

### Phase 5: Cleanup (Low Risk)
1. **Action 6** - Remove all moved code from User.php
2. Simplify to session management only
3. Update cache management
4. **Final testing**

**Estimated: 30 minutes**

---

## Total Estimated Time: 3-4 hours

---

## Files to Modify Summary

### Core Changes (6 files)
- `src/User.php` - Remove methods, simplify to session only
- `src/Privilege.php` - Add getSharingFile as protected method
- `src/Modules/Users/Models/Privileges.php` - Add getPrivilegesFile method
- `src/Modules/Users/Models/Record.php` - Already has methods, no changes needed

### Update Call Sites (15 files)
- `src/Privilege.php` - Use Privileges::getPrivilegesFile()
- `src/Fields/Owner.php` - Use Privileges + Record
- `src/Utils/UserInfoUtil.php` - Use Privileges
- `src/CRMEntity.php` - Use Privilege methods
- `src/PrivilegeQuery.php` - Use Privilege methods
- `src/QueryGenerator.php` - Use Record::getInstanceById
- `src/CustomView.php` - Use Record::getInstanceById
- `src/Fields/Email.php` - Use Record::getInstanceById
- `src/Modules/Users/Actions/Login.php` - Use Record::getInstanceById
- `src/Modules/Settings/AutomaticAssignment/Models/Record.php` - Use Record
- `src/Modules/Notification/` (2 files) - Use Record
- `src/Modules/Reports/` (2 files) - Use Record
- `src/Modules/com_vtiger_workflow/tasks/VTSendNotificationTask.php` - Use Record
- `src/Webservices/VtigerCRMObjectMeta.php` - Use Record::isExists
- `api/webservice/Portal/BaseModule/Privileges.php` - Use Record::isExists

**Total: 19 files to modify**

---

## Testing Strategy

### After Each Phase
```bash
# Login test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" -X POST \
  "http://localhost/index.php?module=Users&action=Login"

# Permission test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Leads&view=Detail&record=112"

# List test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Leads&view=List&mid=48&parent=47"

# Settings test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Vtiger&parent=Settings&view=Index"

# Check logs
tail -n 20 cache/logs/system.log
```

---

## Success Criteria

- [ ] User.php: ~100 lines (from 356) - Session management only
- [ ] No duplicate methods across classes
- [ ] Privilege loading in Privileges model (where it belongs)
- [ ] Sharing loading in Privilege class (where it's used)
- [ ] All curl tests pass
- [ ] No errors in system log
- [ ] Clear separation of concerns

---

## Risk Assessment

**Low Risk:**
- Removing duplicate methods (already have alternatives)
- Moving privilege file loading (pure code organization)

**Medium Risk:**
- Changing getUserModel calls (need to verify method compatibility)
- Changing getCurrentUserModel return type (might affect some callers)

**Mitigation:**
- Test after each phase
- Keep git commits small
- Can rollback any phase independently

---

## Conclusion

**Simple refactoring - no new classes needed!**

1. Move privilege loading to Privileges model (already used there)
2. Move sharing files to Privilege class (only used there)
3. Remove duplicate methods (already exist in Record)
4. Replace getUserModel with Record::getInstanceById
5. Simplify User.php to session management

**Result:** Clean separation, no new abstractions, use existing architecture.

