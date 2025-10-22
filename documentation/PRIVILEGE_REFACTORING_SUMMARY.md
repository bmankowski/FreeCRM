# Privilege System Refactoring - Visual Summary

## Before: Current Architecture (Mixed Responsibilities)

```
┌────────────────────────────────────────────────────────────────────────┐
│                         \App\User (God Object)                          │
│                              356 lines                                  │
├────────────────────────────────────────────────────────────────────────┤
│  SESSION MANAGEMENT                                                     │
│  • getCurrentUserId()      ◄─── Keep (Core responsibility)             │
│  • setCurrentUserId()                                                   │
│  • getCurrentUserRealId()                                               │
│  • getCurrentUserModel()                                                │
├────────────────────────────────────────────────────────────────────────┤
│  PRIVILEGE FILE LOADING                                                 │
│  • getPrivilegesFile()     ◄─── Move to PrivilegeRepository            │
│  • getSharingFile()        ◄─── Move to PrivilegeRepository            │
├────────────────────────────────────────────────────────────────────────┤
│  USER MODEL CREATION                                                    │
│  • getUserModel()          ◄─── Delegate to Record::getInstanceById()  │
│  • Instance methods        ◄─── Remove (use Record directly)           │
├────────────────────────────────────────────────────────────────────────┤
│  STATIC USER QUERIES (Duplicated!)                                     │
│  • isExists()              ◄─── Remove (already in Record)             │
│  • getActiveAdminId()      ◄─── Remove (already in Record)             │
│  • getUserIdByName()       ◄─── Remove (already in Record)             │
├────────────────────────────────────────────────────────────────────────┤
│  CACHE MANAGEMENT                                                       │
│  • clearCache()            ◄─── Split: session cache vs privilege cache│
│  • Static cache vars       ◄─── Split across appropriate classes       │
└────────────────────────────────────────────────────────────────────────┘
           │ Tight Coupling
           ├──────────────────────┬─────────────────────┐
           ▼                      ▼                     ▼
    ┌────────────┐         ┌─────────────┐      ┌───────────┐
    │  Record    │         │ Privileges  │      │ Privilege │
    │ 1281 lines │         │  531 lines  │      │ 683 lines │
    └────────────┘         └─────────────┘      └───────────┘
    Has duplicate          Uses User class      Uses User class
    methods!               for privilege load   for privilege load
```

## After: Target Architecture (Separated Concerns)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        CLEAN SEPARATION OF CONCERNS                      │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────┐
│        \App\User                     │  ◄─── SESSION CONTEXT ONLY
│     (Session Context)                │
│       ~100 lines                     │
├──────────────────────────────────────┤
│  RESPONSIBILITY: Current user session│
│                                      │
│  • getCurrentUserId()                │
│  • setCurrentUserId()                │
│  • getCurrentUserRealId()            │
│  • getCurrentUserModel() ────┐       │
│  • clearCache()              │       │
└──────────────────────────────│───────┘
                               │
                               │ Returns Record model
                               ▼
┌──────────────────────────────────────────────────────┐
│  \App\Modules\Users\Models\Record                    │  ◄─── USER DATA & OPERATIONS
│  (User Data Model)                                   │
│   ~1300 lines                                        │
├──────────────────────────────────────────────────────┤
│  RESPONSIBILITY: User data and operations            │
│                                                      │
│  STATIC QUERIES (single source of truth):           │
│  • getInstanceById()                                 │
│  • isExists()                                        │
│  • getActiveAdminId()                                │
│  • getUserIdByName()                                 │
│  • getAll()                                          │
│                                                      │
│  INSTANCE METHODS:                                   │
│  • getId(), getName(), getDetail()                   │
│  • doLogin(), verifyPassword()                       │
│  • getPrivileges() ──────┐                           │
│  • save(), delete()      │                           │
│  • setPreference()       │                           │
└──────────────────────────│───────────────────────────┘
                           │
                           │ Returns Privileges model
                           ▼
┌──────────────────────────────────────────────────────┐
│  \App\Modules\Users\Models\Privileges                │  ◄─── PRIVILEGE MODEL
│  (Privilege Model)                                   │
│   ~550 lines                                         │
├──────────────────────────────────────────────────────┤
│  RESPONSIBILITY: Privilege data model                │
│                                                      │
│  • getInstanceById() ────────┐                       │
│  • hasModulePermission()     │                       │
│  • hasGlobalReadPermission() │                       │
│  • hasGlobalWritePermission()│                       │
│  • getRoleDetail()           │                       │
│  • isPermitted() (delegates) │                       │
└──────────────────────────────│───────────────────────┘
                               │
                               │ Uses PrivilegeRepository
                               ▼
┌──────────────────────────────────────────────────────┐
│  \App\Privilege\PrivilegeRepository                  │  ◄─── PRIVILEGE LOADING
│  (Privilege File Loader)                             │      (NEW CLASS)
│   ~150 lines                                         │
├──────────────────────────────────────────────────────┤
│  RESPONSIBILITY: Load/save privilege files           │
│                                                      │
│  • getPrivilegesFile($userId)                        │
│  • getSharingFile($userId)                           │
│  • clearCache($userId)                               │
│  • savePrivileges($userId, $data) [future]           │
│                                                      │
│  FUTURE: Can be replaced with RedisRepository!      │
└──────────────────────────────────────────────────────┘
                               │
                               │ Provides data to
                               ▼
┌──────────────────────────────────────────────────────┐
│  \App\Privilege                                      │  ◄─── PERMISSION CHECKING
│  (Permission Checker)                                │
│   ~700 lines                                         │
├──────────────────────────────────────────────────────┤
│  RESPONSIBILITY: Check permissions                   │
│                                                      │
│  • isPermitted($module, $action, $record, $user)     │
│  • isPermittedBySharing(...)                         │
│  • isReadPermittedBySharing(...)                     │
│  • isReadWritePermittedBySharing(...)                │
│  • setUpdater(), setAllUpdater()                     │
│                                                      │
│  Uses: PrivilegeRepository                           │
└──────────────────────────────────────────────────────┘
```

## Key Changes Summary

### ❌ Problems Solved

| Problem | Solution |
|---------|----------|
| **God Object** | Split \App\User into 3 focused classes |
| **Duplicate Methods** | Remove duplicates, single source in Record |
| **Tight Coupling** | Repository pattern for privilege loading |
| **Mixed Concerns** | Clear separation: session, data, privileges |
| **Hard to Test** | Each class has single responsibility |
| **Hard to Replace Cache** | Repository can be swapped (file → Redis) |

### ✅ Benefits

| Benefit | Impact |
|---------|--------|
| **Single Responsibility** | Each class does one thing well |
| **Easier Testing** | Mock PrivilegeRepository in tests |
| **Better Performance** | Can optimize each layer independently |
| **Future-Proof** | Repository pattern enables Redis migration |
| **Clearer API** | Obvious which class to use for what |
| **Less Code Duplication** | One implementation per feature |

## Responsibility Matrix

| Responsibility | Before | After | Change |
|----------------|--------|-------|--------|
| **Session Management** | \App\User | \App\User | ✅ Keep |
| **Get Current User Model** | \App\User | \App\User → Record | ✅ Delegate |
| **User Data Access** | \App\User + Record | Record only | ✅ Consolidate |
| **Static User Queries** | Both classes | Record only | ✅ Remove duplicate |
| **Privilege File Loading** | \App\User | PrivilegeRepository | ✅ Extract |
| **Privilege Model** | Privileges | Privileges | ✅ Keep |
| **Permission Checking** | Privilege | Privilege | ✅ Keep |
| **Cache Management** | \App\User | Split appropriately | ✅ Separate |

## Usage Pattern Changes

### Getting Current User

**Before:**
```php
// Mixed patterns - confusing!
$userId = \App\User::getCurrentUserId();
$userModel = \App\User::getCurrentUserModel(); // Returns \App\User instance
$userName = $userModel->getName();
$isAdmin = $userModel->isAdmin();
```

**After:**
```php
// Clear, consistent pattern
$userId = \App\User::getCurrentUserId();
$userModel = \App\User::getCurrentUserModel(); // Returns \App\Modules\Users\Models\Record
$userName = $userModel->getName();
$isAdmin = $userModel->isAdmin();
```

### Getting User By ID

**Before:**
```php
// Two ways to do the same thing!
$userModel = \App\User::getUserModel($userId);  // Returns \App\User
// OR
$userModel = \App\Modules\Users\Models\Record::getInstanceById($userId); // Returns Record
```

**After:**
```php
// Single, clear way
$userModel = \App\Modules\Users\Models\Record::getInstanceById($userId);
```

### Checking if User Exists

**Before:**
```php
// Which one to use?!
$exists = \App\User::isExists($userId);
// OR
$exists = \App\Modules\Users\Models\Record::isExists($userId);
```

**After:**
```php
// Only one way
$exists = \App\Modules\Users\Models\Record::isExists($userId);
```

### Loading Privileges

**Before:**
```php
// Privilege loading in wrong class
$privileges = \App\User::getPrivilegesFile($userId);
$sharing = \App\User::getSharingFile($userId);
```

**After:**
```php
// Privilege loading in dedicated repository
$privileges = \App\Privilege\PrivilegeRepository::getPrivilegesFile($userId);
$sharing = \App\Privilege\PrivilegeRepository::getSharingFile($userId);

// OR use the model (preferred)
$userModel = \App\Modules\Users\Models\Record::getInstanceById($userId);
$privilegesModel = $userModel->getPrivileges();
```

### Checking Permissions

**Before:**
```php
// Works, but uses wrong class for privilege loading
$canView = \App\Privilege::isPermitted('Leads', 'DetailView', 112);
// Internally calls \App\User::getPrivilegesFile()
```

**After:**
```php
// Same API, but cleaner internals
$canView = \App\Privilege::isPermitted('Leads', 'DetailView', 112);
// Internally calls \App\Privilege\PrivilegeRepository::getPrivilegesFile()
```

## File Size Changes

```
Before:
  \App\User.php                            356 lines ━━━━━━━━━━━━━━━━━━━━ (God Object)
  \App\Modules\Users\Models\Record.php    1281 lines ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  (No PrivilegeRepository)                  0 lines

After:
  \App\User.php                            ~100 lines ━━━━━ (Session only)
  \App\Modules\Users\Models\Record.php    ~1300 lines ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  \App\Privilege\PrivilegeRepository.php  ~150 lines ━━━━━━━━ (New)

Total before: 1637 lines
Total after:  1550 lines (5% reduction + better organization)
```

## Migration Safety

### Backward Compatibility During Transition

```php
// Phase 3: Both work during transition
\App\User::getPrivilegesFile($userId);                        // OLD (delegates)
\App\Privilege\PrivilegeRepository::getPrivilegesFile($userId); // NEW (actual impl)

// Phase 4+: Only new way works
\App\Privilege\PrivilegeRepository::getPrivilegesFile($userId); // Only this
```

### Testing at Each Phase

```bash
# Must pass after each phase:
✓ Login test
✓ Permission check test  
✓ List view test
✓ Detail view test
✓ Settings access test
```

## Future Roadmap (After This Refactoring)

Once this refactoring is complete, the door is open for:

### 1. Replace File-Based Cache → Redis
```php
// Current (after refactoring)
class PrivilegeRepository {
    public static function getPrivilegesFile($userId) {
        // Load from file
    }
}

// Future
class RedisPrivilegeRepository implements PrivilegeRepositoryInterface {
    public static function getPrivilegesFile($userId) {
        // Load from Redis
    }
}
```

### 2. Introduce Dependency Injection
```php
// Current (after refactoring - still static)
$privileges = PrivilegeRepository::getPrivilegesFile($userId);

// Future (with DI container)
$privilegeService = $container->get(PrivilegeService::class);
$privileges = $privilegeService->getUserPrivileges($userId);
```

### 3. Value Objects for Type Safety
```php
// Current
$privileges = PrivilegeRepository::getPrivilegesFile($userId);
$isAdmin = $privileges['is_admin']; // Array access

// Future
$privileges = $privilegeService->getUserPrivileges($userId);
$isAdmin = $privileges->isAdmin(); // Type-safe method
```

### 4. Permission Result Object
```php
// Current
$allowed = Privilege::isPermitted('Leads', 'DetailView', 112);
if (!$allowed) {
    // Why was it denied? Hard to tell
}

// Future
$result = $privilegeService->isPermitted('Leads', 'DetailView', 112);
if (!$result->isAllowed()) {
    echo "Denied because: " . $result->getReason();
    Log::audit($result->toArray());
}
```

## Conclusion

This refactoring:
- ✅ **Maintains** all existing functionality
- ✅ **Improves** code organization
- ✅ **Removes** duplication
- ✅ **Enables** future improvements
- ✅ **Low risk** - incremental changes
- ✅ **High value** - better maintainability

**Next Step:** Begin Phase 1 (Preparation & Analysis)

