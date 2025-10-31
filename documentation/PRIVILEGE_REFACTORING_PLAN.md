# Privilege System Refactoring Plan
## Moving Responsibilities from \App\User to Record and Privilege Classes

**Date:** 2025-10-21  
**Based on:** PRIVILEGE_SYSTEM_ANALYSIS.md  
**Status:** Planning Phase

---

## Current State Analysis

### \App\User (356 lines) - Current Responsibilities

| Responsibility | Lines | Type | Should Move To |
|---------------|-------|------|----------------|
| **Session Management** | | | |
| `getCurrentUserId()` | 24-27 | Static | **Keep** (Session context) |
| `setCurrentUserId()` | 33-36 | Static | **Keep** (Session context) |
| `getCurrentUserRealId()` | 42-54 | Static | **Keep** (Session context) |
| `getCurrentUserModel()` | 60-69 | Static | **Keep** (but return Record model) |
| **User Model Management** | | | |
| `getUserModel($userId)` | 76-88 | Static | → **Record::getInstanceById()** |
| Instance methods (getId, getName, etc.) | 172-280 | Instance | → **Record** (already exists) |
| **Privilege Loading** | | | |
| `getPrivilegesFile($userId)` | 97-129 | Static | → **PrivilegeRepository** (new) |
| `getSharingFile($userId)` | 155-166 | Static | → **PrivilegeRepository** (new) |
| **Cache Management** | | | |
| `clearCache($userId)` | 135-146 | Static | → **Split**: Session cache stays, Privilege cache → **Privileges** |
| Protected static caches | 14-18, 90, 148 | Static | → **Split** across classes |
| **Static User Queries** | | | |
| `isExists($id)` | 286-305 | Static | → **Record::isExists()** (already exists) |
| `getActiveAdminId()` | 311-336 | Static | → **Record::getActiveAdminId()** (already exists) |
| `getUserIdByName($name)` | 343-354 | Static | → **Record::getUserIdByName()** (already exists) |

### \App\Modules\Users\Models\Record (1281 lines) - Current Responsibilities

| Category | Methods | Status |
|----------|---------|--------|
| **Identity & Basic Info** | getId(), getName(), getRealId(), getDetail() | ✅ Good |
| **Authentication** | doLogin(), verifyPassword(), changePassword() | ✅ Good |
| **User Preferences** | setPreference(), savePreferences(), loadPreferences() | ✅ Good |
| **Privilege Access** | getPrivileges(), getRoleDetail(), isAdmin() | ✅ Good |
| **Static Queries** | isExists(), getActiveAdminId(), getUserIdByName() | ⚠️ Duplicates \App\User |
| **Module Operations** | save(), delete(), validate() | ✅ Good |

### \App\Privilege (683 lines) - Current Responsibilities

| Category | Methods | Status |
|----------|---------|--------|
| **Permission Checking** | isPermitted() | ✅ Good (but too complex) |
| **Sharing Permissions** | isPermittedBySharing() | ✅ Good |
| **Updater** | setUpdater(), setAllUpdater() | ✅ Good |

### \App\Modules\Users\Models\Privileges (531 lines) - Current Responsibilities

| Category | Methods | Status |
|----------|---------|--------|
| **Instance Creation** | getInstanceById(), getInstance() | ✅ Good |
| **Permission Queries** | hasModulePermission(), hasGlobalReadPermission() | ✅ Good |
| **Static Helpers** | isPermitted(), getParentRecord() | ✅ Good |

---

## Identified Issues

### Issue #1: Duplicate Static User Queries
**Problem:** Same methods exist in both `\App\User` and `\App\Modules\Users\Models\Record`:
- `isExists($id)`
- `getActiveAdminId()`
- `getUserIdByName($name)`

**Impact:** Confusion about which to use, potential inconsistency

**Solution:** Remove from `\App\User`, keep only in `Record`

### Issue #2: \App\User is a God Object
**Problem:** Mixes 4 distinct concerns:
1. Session management (current user context)
2. User model creation
3. Privilege file loading
4. Static user queries

**Impact:** Violates Single Responsibility Principle, hard to test

**Solution:** Split into focused classes

### Issue #3: File-Based Privilege Loading in Wrong Class
**Problem:** `getPrivilegesFile()` and `getSharingFile()` are in `\App\User` but should be in privilege system

**Impact:** Tight coupling, hard to replace file-based cache

**Solution:** Move to new `PrivilegeRepository` class

### Issue #4: Mixed Static and Instance Methods
**Problem:** `\App\User` has both static session methods AND instance user data methods

**Impact:** Unclear API, confusing usage patterns

**Solution:** Separate session context (static) from user data (instance via Record)

---

## Target Architecture

### Phase 1: Separate Concerns (Non-Breaking)

```
┌─────────────────────────────────────────────────────────────────┐
│                        NEW ARCHITECTURE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────┐         ┌─────────────────────┐       │
│  │   \App\User         │         │  \App\Modules\Users\ │       │
│  │  (Session Context)  │────────▶│  Models\Record      │       │
│  │   ~150 lines        │         │  (User Data & Ops)  │       │
│  ├─────────────────────┤         │   ~1300 lines       │       │
│  │ getCurrentUserId()  │         ├─────────────────────┤       │
│  │ setCurrentUserId()  │         │ Static Queries:     │       │
│  │ getCurrentUserReal  │         │  - isExists()       │       │
│  │ getCurrentUserModel │         │  - getActiveAdminId │       │
│  │ clearCache()        │         │  - getUserIdByName  │       │
│  └─────────────────────┘         │ Instance Methods:   │       │
│           │                      │  - doLogin()        │       │
│           │                      │  - getPrivileges()  │       │
│           ▼                      │  - isAdmin()        │       │
│  ┌─────────────────────┐         │  - save(), delete() │       │
│  │  PrivilegeRepository│         └─────────────────────┘       │
│  │  (Privilege Loading)│                   │                   │
│  │   ~150 lines        │                   │                   │
│  ├─────────────────────┤                   ▼                   │
│  │ getPrivilegesFile() │         ┌─────────────────────┐       │
│  │ getSharingFile()    │         │ \App\Modules\Users\  │       │
│  │ savePrivileges()    │────────▶│ Models\Privileges   │       │
│  │ clearPrivilegeCache │         │ (Privilege Model)   │       │
│  └─────────────────────┘         │   ~550 lines        │       │
│           │                      ├─────────────────────┤       │
│           │                      │ hasModulePermission │       │
│           ▼                      │ hasGlobalPermission │       │
│  ┌─────────────────────┐         │ isPermitted()       │       │
│  │   \App\Privilege    │◀────────│ getParentRecord()   │       │
│  │ (Permission Checks) │         └─────────────────────┘       │
│  │   ~700 lines        │                                       │
│  ├─────────────────────┤                                       │
│  │ isPermitted()       │                                       │
│  │ isPermittedBySharing│                                       │
│  │ setUpdater()        │                                       │
│  └─────────────────────┘                                       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Refactoring Plan

### Phase 1: Preparation & Analysis (Week 1)
**Goal:** Understand usage patterns, no code changes

#### Step 1.1: Find All Usages
```bash
# Find all calls to \App\User methods
grep -r "\\\\App\\\\User::" --include="*.php" . > user_usage.txt
grep -r "App\\User::" --include="*.php" . >> user_usage.txt
grep -r "User::getCurrentUserId()" --include="*.php" . >> user_usage.txt
```

#### Step 1.2: Categorize Usages
Create inventory of:
- How many calls to `getCurrentUserId()`
- How many calls to `getCurrentUserModel()`
- How many calls to `getPrivilegesFile()`
- How many calls to duplicate methods (`isExists`, etc.)

#### Step 1.3: Create Test Suite
- Write integration tests for current behavior
- Document expected outputs
- Establish baseline for refactoring

**Deliverables:**
- [ ] Usage analysis document
- [ ] Test suite covering current behavior
- [ ] List of affected files

---

### Phase 2: Move Static User Queries (Week 2)
**Goal:** Consolidate duplicate methods, remove from \App\User

#### Step 2.1: Verify Record Methods Work Identically
```php
// Test that these produce same results:
\App\User::isExists($id) === \App\Modules\Users\Models\Record::isExists($id)
\App\User::getActiveAdminId() === \App\Modules\Users\Models\Record::getActiveAdminId()
\App\User::getUserIdByName($name) === \App\Modules\Users\Models\Record::getUserIdByName($name)
```

#### Step 2.2: Add Deprecation Notices
```php
// In \App\User
/**
 * @deprecated Use \App\Modules\Users\Models\Record::isExists() instead
 */
public static function isExists($id)
{
    \App\Log::warning('DEPRECATED: \App\User::isExists() - Use \App\Modules\Users\Models\Record::isExists()');
    return \App\Modules\Users\Models\Record::isExists($id);
}
```

#### Step 2.3: Update All Calls (Automated)
```bash
# Use sed to replace calls
find . -name "*.php" -exec sed -i 's/\\App\\User::isExists(/\\App\\Modules\\Users\\Models\\Record::isExists(/g' {} \;
find . -name "*.php" -exec sed -i 's/\\App\\User::getActiveAdminId(/\\App\\Modules\\Users\\Models\\Record::getActiveAdminId(/g' {} \;
find . -name "*.php" -exec sed -i 's/\\App\\User::getUserIdByName(/\\App\\Modules\\Users\\Models\\Record::getUserIdByName(/g' {} \;
```

#### Step 2.4: Remove from \App\User
After all calls updated, remove the methods

**Deliverables:**
- [ ] All calls updated to use Record methods
- [ ] Methods removed from \App\User
- [ ] Tests pass
- [ ] Web application tested with curl commands

---

### Phase 3: Create PrivilegeRepository (Week 3)
**Goal:** Extract privilege file loading into dedicated class

#### Step 3.1: Create New Class
```php
<?php
namespace App\Privilege;

/**
 * Repository for loading and caching user privileges
 */
class PrivilegeRepository
{
    protected static $userPrivilegesCache = [];
    protected static $userSharingCache = [];
    
    /**
     * Get base privileges from file by id
     * @param int $userId
     * @return array|null
     */
    public static function getPrivilegesFile($userId)
    {
        if (isset(self::$userPrivilegesCache[$userId])) {
            return self::$userPrivilegesCache[$userId];
        }
        
        if (!file_exists("user_privileges/user_privileges_{$userId}.php")) {
            return null;
        }
        
        $privileges = require("user_privileges/user_privileges_{$userId}.php");
        
        $valueMap = [];
        $valueMap['id'] = $userId;
        $valueMap['is_admin'] = (bool) $is_admin;
        $valueMap['user_info'] = $user_info;
        $valueMap['_privileges'] = $privileges;
        
        if (!$is_admin) {
            $valueMap['roleid'] = $current_user_roles;
            $valueMap['parent_role_seq'] = $current_user_parent_role_seq;
            $valueMap['profiles'] = $current_user_profiles;
            $valueMap['profile_global_permission'] = $profileGlobalPermission;
            $valueMap['profile_tabs_permission'] = $profileTabsPermission;
            $valueMap['profile_action_permission'] = $profileActionPermission;
            $valueMap['groups'] = $current_user_groups;
            $valueMap['subordinate_roles'] = $subordinate_roles;
            $valueMap['parent_roles'] = $parent_roles;
            $valueMap['subordinate_roles_users'] = $subordinate_roles_users;
            
            $sharingPrivileges = self::getSharingFile($userId);
            $valueMap['defaultOrgSharingPermission'] = $sharingPrivileges['defOrgShare'];
            $valueMap['related_module_share'] = $sharingPrivileges['relatedModuleShare'];
        }
        
        self::$userPrivilegesCache[$userId] = $valueMap;
        return $valueMap;
    }
    
    /**
     * Get sharing privileges from file by id
     * @param int $userId
     * @return array|null
     */
    public static function getSharingFile($userId)
    {
        if (isset(self::$userSharingCache[$userId])) {
            return self::$userSharingCache[$userId];
        }
        
        if (!file_exists("user_privileges/sharing_privileges_{$userId}.php")) {
            return null;
        }
        
        $sharingPrivileges = require("user_privileges/sharing_privileges_{$userId}.php");
        self::$userSharingCache[$userId] = $sharingPrivileges;
        return $sharingPrivileges;
    }
    
    /**
     * Clear privilege cache
     * @param int|bool $userId
     */
    public static function clearCache($userId = false)
    {
        if ($userId) {
            unset(self::$userPrivilegesCache[$userId], self::$userSharingCache[$userId]);
        } else {
            self::$userPrivilegesCache = [];
            self::$userSharingCache = [];
        }
    }
}
```

#### Step 3.2: Update \App\User to Delegate
```php
// In \App\User
public static function getPrivilegesFile($userId)
{
    return \App\Privilege\PrivilegeRepository::getPrivilegesFile($userId);
}

public static function getSharingFile($userId)
{
    return \App\Privilege\PrivilegeRepository::getSharingFile($userId);
}
```

#### Step 3.3: Update All Direct Calls
```bash
# Find and replace calls
find . -name "*.php" -exec sed -i 's/\\App\\User::getPrivilegesFile(/\\App\\Privilege\\PrivilegeRepository::getPrivilegesFile(/g' {} \;
find . -name "*.php" -exec sed -i 's/\\App\\User::getSharingFile(/\\App\\Privilege\\PrivilegeRepository::getSharingFile(/g' {} \;
```

#### Step 3.4: Remove Delegation, Keep Only Session Methods in \App\User
After all external calls updated, remove delegation and move logic entirely to PrivilegeRepository

**Deliverables:**
- [ ] PrivilegeRepository class created
- [ ] All privilege loading moved to PrivilegeRepository
- [ ] Tests pass
- [ ] \App\User reduced to session management only

---

### Phase 4: Simplify \App\User to Session Context Only (Week 4)
**Goal:** Make \App\User a pure session context manager

#### Step 4.1: Final \App\User Structure
```php
<?php
namespace App;

/**
 * User Session Context Manager
 * Manages the current user session and context
 */
class User
{
    protected static $currentUserId;
    protected static $currentUserRealId = false;
    protected static $currentUserCache = false;
    
    /**
     * Get current user Id
     * @return int|null
     */
    public static function getCurrentUserId()
    {
        return static::$currentUserId;
    }
    
    /**
     * Set current user Id
     * @param int $userId
     */
    public static function setCurrentUserId($userId)
    {
        static::$currentUserId = $userId;
        static::$currentUserCache = false; // Invalidate cache
    }
    
    /**
     * Get real current user Id (handles user switching)
     * @return int
     */
    public static function getCurrentUserRealId()
    {
        if (static::$currentUserRealId) {
            return static::$currentUserRealId;
        }
        
        if (\App\Http\Vtiger_Session::has('baseUserId') && \App\Http\Vtiger_Session::get('baseUserId')) {
            $id = \App\Http\Vtiger_Session::get('baseUserId');
        } else {
            $id = static::getCurrentUserId();
        }
        
        static::$currentUserRealId = $id;
        return $id;
    }
    
    /**
     * Get current user model (returns full Record model)
     * @return \App\Modules\Users\Models\Record
     */
    public static function getCurrentUserModel()
    {
        if (static::$currentUserCache) {
            return static::$currentUserCache;
        }
        
        if (!static::$currentUserId) {
            static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
        }
        
        return static::$currentUserCache = \App\Modules\Users\Models\Record::getInstanceById(static::$currentUserId, 'Users');
    }
    
    /**
     * Clear session cache
     * @param int|bool $userId
     */
    public static function clearCache($userId = false)
    {
        if ($userId && static::$currentUserId === $userId) {
            static::$currentUserCache = false;
        } elseif (!$userId) {
            static::$currentUserCache = false;
            static::$currentUserRealId = false;
        }
    }
}
```

#### Step 4.2: Update getUserModel() Calls
```php
// OLD: Returns \App\User instance with limited data
$userModel = \App\User::getUserModel($userId);

// NEW: Use Record::getInstanceById() directly
$userModel = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
```

#### Step 4.3: Remove Instance Methods from \App\User
All instance methods (getId, getName, getDetail, etc.) should be accessed via Record model

**Deliverables:**
- [ ] \App\User reduced to ~100 lines
- [ ] Only session management remains in \App\User
- [ ] All user data access via Record model
- [ ] Tests pass

---

### Phase 5: Update Privilege Classes to Use Record (Week 5)
**Goal:** Privilege classes should use Record model instead of arrays

#### Step 5.1: Update \App\Modules\Users\Models\Privileges::getInstanceById()
```php
public static function getInstanceById($userId, $module = null)
{
    if (empty($userId)) {
        return null;
    }
    
    if (isset(self::$userPrivilegesModelCache[$userId])) {
        return self::$userPrivilegesModelCache[$userId];
    }
    
    // Use PrivilegeRepository instead of \App\User
    $valueMap = \App\Privilege\PrivilegeRepository::getPrivilegesFile($userId);
    
    if ($valueMap === null) {
        \App\Log::error("User privileges file not found for user: $userId");
        return null;
    }
    
    if (is_array($valueMap['user_info'])) {
        $valueMap = array_merge($valueMap, $valueMap['user_info']);
    }
    
    $instance = self::getInstance($valueMap);
    self::$userPrivilegesModelCache[$userId] = $instance;
    return $instance;
}
```

#### Step 5.2: Update \App\Privilege Methods
```php
// In \App\Privilege::isPermitted()
// OLD:
$userPrivileges = \App\User::getPrivilegesFile($userId);

// NEW:
$userPrivileges = \App\Privilege\PrivilegeRepository::getPrivilegesFile($userId);
```

#### Step 5.3: Update Sharing Permission Methods
```php
// In isPermittedBySharing(), isReadPermittedBySharing(), etc.
// OLD:
$sharingPrivileges = \App\User::getSharingFile($userId);

// NEW:
$sharingPrivileges = \App\Privilege\PrivilegeRepository::getSharingFile($userId);
```

**Deliverables:**
- [ ] All Privilege classes use PrivilegeRepository
- [ ] No direct calls to \App\User::getPrivilegesFile()
- [ ] No direct calls to \App\User::getSharingFile()
- [ ] Tests pass

---

### Phase 6: Documentation & Cleanup (Week 6)
**Goal:** Document new architecture, remove old code

#### Step 6.1: Update Documentation
- Update architecture diagrams
- Document new class responsibilities
- Create migration guide for developers

#### Step 6.2: Remove Deprecated Code
- Remove deprecated methods from \App\User
- Remove unused cache variables
- Clean up comments

#### Step 6.3: Add PHPDoc and Type Hints
```php
/**
 * Get current user ID from session
 * @return int|null Current authenticated user ID
 */
public static function getCurrentUserId(): ?int
{
    return static::$currentUserId;
}
```

#### Step 6.4: Final Testing
- Run full test suite
- Test with curl commands (per repo rules)
- Verify no regressions

**Deliverables:**
- [ ] Updated documentation
- [ ] Deprecated code removed
- [ ] Type hints added
- [ ] All tests passing
- [ ] Production-ready

---

## Testing Strategy

### Integration Tests
```php
class UserPrivilegeIntegrationTest extends TestCase
{
    public function testCurrentUserFlow()
    {
        // Set current user
        \App\User::setCurrentUserId(1);
        
        // Get user model
        $userModel = \App\User::getCurrentUserModel();
        $this->assertInstanceOf(\App\Modules\Users\Models\Record::class, $userModel);
        $this->assertEquals(1, $userModel->getId());
        
        // Get privileges
        $privileges = $userModel->getPrivileges();
        $this->assertInstanceOf(\App\Modules\Users\Models\Privileges::class, $privileges);
        
        // Check permission
        $canView = $privileges->hasModulePermission('Leads');
        $this->assertIsBool($canView);
    }
    
    public function testPrivilegeRepositoryLoading()
    {
        $privileges = \App\Privilege\PrivilegeRepository::getPrivilegesFile(1);
        $this->assertIsArray($privileges);
        $this->assertArrayHasKey('is_admin', $privileges);
        $this->assertArrayHasKey('profiles', $privileges);
    }
    
    public function testPermissionChecking()
    {
        $permitted = \App\Privilege::isPermitted('Leads', 'DetailView', 112, 1);
        $this->assertIsBool($permitted);
    }
}
```

### Curl Tests (Per Repo Rules)
```bash
# After each phase, run these tests:
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L -d "username=admin&password=admin" -X POST "http://localhost/index.php?module=Users&action=Login"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L -d "username=admin&password=admin" -X POST "http://localhost/index.php?module=Leads&view=Detail&record=112"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L -d "username=admin&password=admin" -X POST "http://localhost/index.php?module=Leads&view=ListView&mid=48&parent=47"
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L -d "username=admin&password=admin" -X POST "http://localhost/index.php?module=Vtiger&parent=Settings&view=Index"
```

---

## Risk Assessment

### High Risk Areas
1. **Session Management Changes**
   - Risk: Breaking authentication flow
   - Mitigation: Extensive testing, phased rollout

2. **Privilege Loading Changes**
   - Risk: Incorrect permissions, security holes
   - Mitigation: No logic changes in Phase 3, only extraction

3. **Cache Invalidation**
   - Risk: Stale data, permission errors
   - Mitigation: Clear all caches between phases

### Low Risk Areas
1. **Moving static user queries** (Phase 2)
   - Already have identical implementations
   - Pure code organization

2. **Documentation updates** (Phase 6)
   - No functional changes

---

## Rollback Plan

Each phase has a rollback strategy:

### Phase 2 Rollback
```bash
# Revert sed changes
git checkout -- .
```

### Phase 3 Rollback
```bash
# Keep \App\User methods as primary, remove PrivilegeRepository
rm src/Privilege/PrivilegeRepository.php
git checkout -- src/User.php
```

### Phase 4+ Rollback
```bash
# Full git revert
git revert <commit-hash>
```

---

## Success Criteria

- [ ] \App\User reduced to ~100 lines (session management only)
- [ ] All privilege loading in PrivilegeRepository
- [ ] All user data operations in Record model
- [ ] No duplicate static methods
- [ ] All tests passing
- [ ] Curl tests working
- [ ] No performance regression
- [ ] Documentation updated

---

## Future Enhancements (Post-Refactoring)

After completing this refactoring, consider:

1. **Replace File-Based Cache** (PRIVILEGE_SYSTEM_ANALYSIS.md Recommendation #2)
   - Implement Redis/Database cache
   - Current: `PrivilegeRepository` uses files
   - Future: `PrivilegeRepository` uses Redis

2. **Introduce Service Layer** (PRIVILEGE_SYSTEM_ANALYSIS.md Recommendation #1)
   - Create `PrivilegeService` with DI
   - Replace static methods with injectable service

3. **Chain of Responsibility** (PRIVILEGE_SYSTEM_ANALYSIS.md Recommendation #3)
   - Break down `Privilege::isPermitted()` into checkers
   - AdminChecker, ModulePermissionChecker, etc.

4. **Value Objects** (PRIVILEGE_SYSTEM_ANALYSIS.md Recommendation #4)
   - Replace arrays with `UserPrivileges` value object
   - Type-safe privilege data

5. **Permission Result Object** (PRIVILEGE_SYSTEM_ANALYSIS.md Recommendation #5)
   - Return `PermissionResult` instead of boolean
   - Include reason, metadata, audit trail

---

## Timeline Summary

| Phase | Duration | Effort | Risk |
|-------|----------|--------|------|
| Phase 1: Preparation | 1 week | Medium | Low |
| Phase 2: Move Static Queries | 1 week | Low | Low |
| Phase 3: PrivilegeRepository | 1 week | Medium | Medium |
| Phase 4: Simplify User | 1 week | High | High |
| Phase 5: Update Privilege Classes | 1 week | Medium | Medium |
| Phase 6: Documentation | 1 week | Low | Low |
| **Total** | **6 weeks** | **Medium** | **Medium** |

---

## Conclusion

This refactoring plan follows the **Strangler Fig Pattern** recommended in PRIVILEGE_SYSTEM_ANALYSIS.md:
- ✅ Incremental changes, not big-bang rewrite
- ✅ Both old and new systems coexist during transition
- ✅ Each phase is independently testable
- ✅ Clear rollback strategy at each phase
- ✅ Low risk, high value

After completion:
- **\App\User**: Pure session context (~100 lines)
- **\App\Modules\Users\Models\Record**: Complete user data model (~1300 lines)
- **\App\Privilege\PrivilegeRepository**: Privilege loading (~150 lines)
- **\App\Privilege**: Permission checking (~700 lines)
- **\App\Modules\Users\Models\Privileges**: Privilege model (~550 lines)

**Clear separation of concerns, easier to maintain, easier to test.**

