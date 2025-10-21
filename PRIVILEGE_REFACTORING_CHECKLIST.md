# Privilege Refactoring - Implementation Checklist

## Quick Reference for Implementation

**Goal:** Move responsibilities from `\App\User` to `Record` and Privilege classes  
**Strategy:** Incremental, safe refactoring following Strangler Fig Pattern  
**Timeline:** 6 weeks

---

## Phase 1: Preparation & Analysis ⏳ Week 1

### Step 1.1: Usage Analysis
- [ ] Run grep to find all `\App\User::` calls
  ```bash
  grep -r "\\\\App\\\\User::" --include="*.php" src/ > analysis/user_usage.txt
  grep -r "App\\User::" --include="*.php" modules/ >> analysis/user_usage.txt
  ```
- [ ] Count usages by method:
  - [ ] `getCurrentUserId()` - ___ calls
  - [ ] `getCurrentUserModel()` - ___ calls
  - [ ] `getPrivilegesFile()` - ___ calls
  - [ ] `getSharingFile()` - ___ calls
  - [ ] `isExists()` - ___ calls
  - [ ] `getActiveAdminId()` - ___ calls
  - [ ] `getUserIdByName()` - ___ calls

### Step 1.2: Test Suite Creation
- [ ] Write test for current user session flow
- [ ] Write test for privilege loading
- [ ] Write test for permission checking
- [ ] Write test for user queries
- [ ] Document expected behaviors
- [ ] Establish baseline (all tests green ✅)

### Step 1.3: Environment Setup
- [ ] Create feature branch: `refactor/privilege-system`
- [ ] Backup current state
- [ ] Verify curl tests work:
  ```bash
  ./test_privilege_system.sh
  ```

**Deliverables:**
- [ ] `analysis/user_usage.txt` created
- [ ] Test suite in place
- [ ] Baseline established

---

## Phase 2: Move Static User Queries ⏳ Week 2

### Step 2.1: Verify Equivalence
- [ ] Test `\App\User::isExists()` vs `Record::isExists()`
  - [ ] Same results for valid user IDs
  - [ ] Same results for invalid user IDs
  - [ ] Same results for edge cases (0, null, negative)
- [ ] Test `getActiveAdminId()` equivalence
- [ ] Test `getUserIdByName()` equivalence

### Step 2.2: Add Deprecation Notices
- [ ] Update `\App\User::isExists()` with deprecation warning
- [ ] Update `\App\User::getActiveAdminId()` with deprecation warning
- [ ] Update `\App\User::getUserIdByName()` with deprecation warning
- [ ] Commit: "Add deprecation notices to duplicate User methods"

### Step 2.3: Replace All Calls
```bash
# Create backup
git add -A && git commit -m "Backup before mass replace"

# Replace calls
find src/ modules/ -name "*.php" -exec sed -i 's/\\App\\User::isExists(/\\App\\Modules\\Users\\Models\\Record::isExists(/g' {} \;
find src/ modules/ -name "*.php" -exec sed -i 's/\\App\\User::getActiveAdminId(/\\App\\Modules\\Users\\Models\\Record::getActiveAdminId(/g' {} \;
find src/ modules/ -name "*.php" -exec sed -i 's/\\App\\User::getUserIdByName(/\\App\\Modules\\Users\\Models\\Record::getUserIdByName(/g' {} \;

# Verify
git diff --stat
```

- [ ] Run automated replacements
- [ ] Review diffs manually
- [ ] Fix any namespace issues
- [ ] Commit: "Replace User static queries with Record methods"

### Step 2.4: Test & Verify
- [ ] Run test suite ✅
- [ ] Run curl tests ✅
  ```bash
  curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L -d "username=admin&password=admin" -X POST "http://localhost/index.php?module=Users&action=Login"
  curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L "http://localhost/index.php?module=Leads&view=Detail&record=112"
  curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L "http://localhost/index.php?module=Leads&view=List"
  curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L "http://localhost/index.php?module=Vtiger&parent=Settings&view=Index"
  ```
- [ ] Check error logs: `cache/logs/system.log`
- [ ] No errors related to User class ✅

### Step 2.5: Remove from User.php
- [ ] Remove `isExists()` method
- [ ] Remove `getActiveAdminId()` method
- [ ] Remove `getUserIdByName()` method
- [ ] Commit: "Remove duplicate methods from User class"
- [ ] Test again ✅

**Deliverables:**
- [ ] All calls use `Record` methods
- [ ] Methods removed from `\App\User`
- [ ] Tests green ✅
- [ ] Curl tests pass ✅

---

## Phase 3: Create PrivilegeRepository ⏳ Week 3

### Step 3.1: Create Repository Class
- [ ] Create `src/Privilege/` directory
- [ ] Create `src/Privilege/PrivilegeRepository.php`
- [ ] Copy `getPrivilegesFile()` implementation
- [ ] Copy `getSharingFile()` implementation
- [ ] Add `clearCache()` method
- [ ] Add proper namespace and PHPDoc
- [ ] Commit: "Create PrivilegeRepository class"

### Step 3.2: Update composer.json
```json
"autoload": {
    "psr-4": {
        "App\\Privilege\\": "src/Privilege/",
        ...
    }
}
```
- [ ] Update composer.json
- [ ] Run `composer dump-autoload`
- [ ] Verify class loads: `php -r "new \App\Privilege\PrivilegeRepository();"`

### Step 3.3: Add Delegation in User.php
```php
public static function getPrivilegesFile($userId)
{
    return \App\Privilege\PrivilegeRepository::getPrivilegesFile($userId);
}

public static function getSharingFile($userId)
{
    return \App\Privilege\PrivilegeRepository::getSharingFile($userId);
}
```
- [ ] Update methods to delegate
- [ ] Add deprecation comments
- [ ] Commit: "User delegates privilege loading to PrivilegeRepository"
- [ ] Test ✅

### Step 3.4: Replace Direct Calls
```bash
find src/ modules/ -name "*.php" -exec sed -i 's/\\App\\User::getPrivilegesFile(/\\App\\Privilege\\PrivilegeRepository::getPrivilegesFile(/g' {} \;
find src/ modules/ -name "*.php" -exec sed -i 's/\\App\\User::getSharingFile(/\\App\\Privilege\\PrivilegeRepository::getSharingFile(/g' {} \;
```

- [ ] Run automated replacements
- [ ] Review diffs
- [ ] Fix any issues
- [ ] Test ✅
- [ ] Commit: "Replace User privilege methods with PrivilegeRepository"

### Step 3.5: Update Privilege Classes
- [ ] Update `\App\Privilege::isPermitted()` to use PrivilegeRepository
- [ ] Update `\App\Privilege::isPermittedBySharing()` to use PrivilegeRepository
- [ ] Update `\App\Privilege::isReadPermittedBySharing()` to use PrivilegeRepository
- [ ] Update `\App\Privilege::isReadWritePermittedBySharing()` to use PrivilegeRepository
- [ ] Update `\App\Modules\Users\Models\Privileges::getInstanceById()` to use PrivilegeRepository
- [ ] Commit: "Update Privilege classes to use PrivilegeRepository"
- [ ] Test ✅

### Step 3.6: Remove Delegation from User.php
- [ ] Remove `getPrivilegesFile()` method from User
- [ ] Remove `getSharingFile()` method from User
- [ ] Remove privilege cache variables
- [ ] Update `clearCache()` to only clear user model cache
- [ ] Commit: "Remove privilege loading from User class"
- [ ] Test ✅

**Deliverables:**
- [ ] PrivilegeRepository class created
- [ ] All privilege loading uses PrivilegeRepository
- [ ] User class no longer handles privilege files
- [ ] Tests green ✅

---

## Phase 4: Simplify User to Session Only ⏳ Week 4

### Step 4.1: Analyze getUserModel() Usage
- [ ] Find all calls to `\App\User::getUserModel()`
  ```bash
  grep -r "User::getUserModel" --include="*.php" src/ modules/ > analysis/getUserModel_usage.txt
  ```
- [ ] Count: ___ usages found
- [ ] Categorize usage patterns

### Step 4.2: Replace getUserModel() Calls
```bash
# Pattern 1: Direct replacement
find src/ modules/ -name "*.php" -exec sed -i 's/\\App\\User::getUserModel(/\\App\\Modules\\Users\\Models\\Record::getInstanceById(/g' {} \;

# Pattern 2: getCurrentUserModel stays the same (returns Record)
# No change needed - already returns proper model
```
- [ ] Run replacements
- [ ] Review each change manually
- [ ] Test ✅
- [ ] Commit: "Replace getUserModel with Record::getInstanceById"

### Step 4.3: Update getCurrentUserModel() Return Type
```php
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
    
    return static::$currentUserCache = \App\Modules\Users\Models\Record::getInstanceById(
        static::$currentUserId,
        'Users'
    );
}
```
- [ ] Update method implementation
- [ ] Update PHPDoc
- [ ] Test ✅
- [ ] Commit: "getCurrentUserModel returns Record instance"

### Step 4.4: Remove Instance Methods from User
- [ ] Remove `getId()` method
- [ ] Remove `getName()` method
- [ ] Remove `getDetail()` method
- [ ] Remove `getProfiles()` method
- [ ] Remove `getGroups()` method
- [ ] Remove `getRole()` method
- [ ] Remove `getRoleInstance()` method
- [ ] Remove `getParentRoles()` method
- [ ] Remove `getParentRolesSeq()` method
- [ ] Remove `isAdmin()` method
- [ ] Remove `get()` method
- [ ] Remove `isActive()` method
- [ ] Remove `$privileges` property
- [ ] Commit: "Remove instance methods from User class"

### Step 4.5: Simplify Cache Management
```php
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
```
- [ ] Update clearCache() to only handle session
- [ ] Remove privilege cache handling
- [ ] Test ✅
- [ ] Commit: "Simplify User cache to session only"

### Step 4.6: Final User.php Review
- [ ] Class should be ~100 lines
- [ ] Only session methods remain
- [ ] Clean PHPDoc
- [ ] No references to privileges
- [ ] Commit: "Final User.php cleanup"

**Deliverables:**
- [ ] User.php reduced to ~100 lines
- [ ] Only session management in User
- [ ] All user data via Record
- [ ] Tests green ✅

---

## Phase 5: Update Privilege Classes ⏳ Week 5

### Step 5.1: Update Privileges Model
- [ ] Review `\App\Modules\Users\Models\Privileges::getInstanceById()`
- [ ] Verify uses PrivilegeRepository (should already from Phase 3)
- [ ] Clean up any remaining User references
- [ ] Test ✅

### Step 5.2: Update Privilege Permission Checker
- [ ] Review `\App\Privilege::isPermitted()`
- [ ] Verify uses PrivilegeRepository (should already from Phase 3)
- [ ] Clean up any remaining User references
- [ ] Test ✅

### Step 5.3: Add Clear Separation Comments
```php
// In Privilege.php
/**
 * Check permission for a Module/Action/Record
 * 
 * Flow:
 * 1. Get privilege data from PrivilegeRepository
 * 2. Check various permission levels
 * 3. Return boolean result
 * 
 * @param string $moduleName Module name
 * @param string|null $actionName Action to perform
 * @param int|bool $record Record ID
 * @param int|bool $userId User ID (defaults to current user from \App\User)
 * @return bool
 */
```
- [ ] Add architectural comments
- [ ] Document data flow
- [ ] Clarify responsibilities
- [ ] Commit: "Document Privilege class architecture"

### Step 5.4: Verify No User.php Dependencies
```bash
# Should find NO results (except getCurrentUserId for session):
grep -r "\\\\App\\\\User::getPrivilegesFile" --include="*.php" src/
grep -r "\\\\App\\\\User::getSharingFile" --include="*.php" src/
grep -r "\\\\App\\\\User::getUserModel" --include="*.php" src/
```
- [ ] No getPrivilegesFile calls to User ✅
- [ ] No getSharingFile calls to User ✅
- [ ] No getUserModel calls to User ✅
- [ ] Only getCurrentUserId/getCurrentUserModel calls remain ✅

**Deliverables:**
- [ ] All privilege classes use PrivilegeRepository
- [ ] No unintended User dependencies
- [ ] Clear documentation
- [ ] Tests green ✅

---

## Phase 6: Documentation & Cleanup ⏳ Week 6

### Step 6.1: Update Architecture Documentation
- [ ] Update `docs/User-Classes-Architecture.md`
- [ ] Update class responsibility matrix
- [ ] Add PrivilegeRepository to diagrams
- [ ] Document new patterns
- [ ] Commit: "Update architecture documentation"

### Step 6.2: Create Migration Guide
- [ ] Document breaking changes (if any)
- [ ] Provide before/after examples
- [ ] List all moved methods
- [ ] Add troubleshooting section
- [ ] Commit: "Add privilege refactoring migration guide"

### Step 6.3: Add Type Hints
```php
public static function getCurrentUserId(): ?int
public static function setCurrentUserId(int $userId): void
public static function getCurrentUserRealId(): int
public static function getCurrentUserModel(): ?\App\Modules\Users\Models\Record
public static function clearCache(int|bool $userId = false): void
```
- [ ] Add return type hints to User methods
- [ ] Add parameter type hints
- [ ] Update PHPDoc
- [ ] Test with PHP 7.4+ ✅
- [ ] Commit: "Add type hints to User class"

### Step 6.4: Remove Deprecated Code
- [ ] Verify no deprecation warnings in logs
- [ ] Remove any temporary delegation methods
- [ ] Remove unused imports
- [ ] Clean up comments
- [ ] Commit: "Remove deprecated code"

### Step 6.5: Final Code Review
- [ ] Review User.php (~100 lines) ✅
- [ ] Review PrivilegeRepository.php (~150 lines) ✅
- [ ] Review Record.php (~1300 lines) ✅
- [ ] Review Privilege.php (~700 lines) ✅
- [ ] Review Privileges.php (~550 lines) ✅
- [ ] No code duplication ✅
- [ ] Clean separation of concerns ✅

### Step 6.6: Comprehensive Testing
- [ ] Run full test suite ✅
- [ ] Run curl tests ✅
  ```bash
  ./test_privilege_system.sh
  ```
- [ ] Manual testing:
  - [ ] Login as admin
  - [ ] Login as regular user
  - [ ] View leads list
  - [ ] View lead detail
  - [ ] Edit lead
  - [ ] Access settings
  - [ ] Check permissions denied
- [ ] Check logs for errors ✅
- [ ] Performance testing (no regression) ✅

### Step 6.7: Prepare Release
- [ ] Merge feature branch to main
- [ ] Tag release: `v1.0.0-privilege-refactor`
- [ ] Update CHANGELOG.md
- [ ] Prepare release notes

**Deliverables:**
- [ ] Documentation complete
- [ ] Type hints added
- [ ] Code cleaned up
- [ ] All tests passing ✅
- [ ] Production ready ✅

---

## Verification Checklist (Run After Each Phase)

### Quick Smoke Tests
```bash
# 1. Login test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" -X POST \
  "http://localhost/index.php?module=Users&action=Login" | grep -q "success"

# 2. List view test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Leads&view=List" | grep -q "Leads"

# 3. Detail view test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Leads&view=Detail&record=112" | grep -q "Lead"

# 4. Settings test
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Vtiger&parent=Settings&view=Index" | grep -q "Settings"
```

### Error Log Check
```bash
tail -n 50 cache/logs/system.log | grep -i "error\|warning\|User\|Privilege"
```

### Code Quality
```bash
# No syntax errors
find src/ -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"

# No duplicate class names
find src/ -name "*.php" -exec grep -H "^class " {} \; | awk '{print $2}' | sort | uniq -d
```

---

## Rollback Procedures

### If Phase 2 Fails
```bash
git reset --hard HEAD~1  # Reset to before mass replacement
```

### If Phase 3 Fails
```bash
rm src/Privilege/PrivilegeRepository.php
git checkout -- src/User.php
composer dump-autoload
```

### If Phase 4 Fails
```bash
git checkout -- src/User.php
composer dump-autoload
```

### If Phase 5 Fails
```bash
git checkout -- src/Privilege.php
git checkout -- src/Modules/Users/Models/Privileges.php
```

### Full Rollback
```bash
git reset --hard <commit-before-refactoring>
composer dump-autoload
```

---

## Success Metrics

- [ ] User.php: ~100 lines (from 356)
- [ ] PrivilegeRepository.php: ~150 lines (new)
- [ ] Record.php: ~1300 lines (consolidated)
- [ ] No duplicate methods
- [ ] All tests passing
- [ ] No performance regression (<5% acceptable)
- [ ] Clear separation of concerns
- [ ] Ready for future improvements (Redis, DI, etc.)

---

## Quick Command Reference

```bash
# Find usages
grep -r "\\App\\User::" --include="*.php" src/ modules/

# Mass replace
find src/ modules/ -name "*.php" -exec sed -i 's/OLD/NEW/g' {} \;

# Run tests
php vendor/bin/phpunit

# Check logs
tail -f cache/logs/system.log

# Dump autoload
composer dump-autoload

# Syntax check
php -l src/User.php

# Git status
git status
git diff --stat
git log --oneline -n 10
```

---

**Remember:** 
- ✅ Test after each step
- ✅ Commit frequently
- ✅ Run curl tests after each phase
- ✅ Check error logs
- ✅ Don't skip steps
- ✅ Keep backups

**On errors:** Don't stop - continue! Fix and move forward per repo rules.

