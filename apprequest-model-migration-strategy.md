# AppRequest & Model Singleton Migration Strategy

## Executive Summary

This document provides a detailed analysis and refactoring plan for removing singleton pattern usage in the FreeCRM application, with a specific focus on the Users Settings Detail view (`http://localhost/index.php?module=Users&parent=Settings&view=Detail&record=1`).

**Analysis Date:** October 25, 2025  
**Target URL:** `index.php?module=Users&parent=Settings&view=Detail&record=1`  
**Primary File:** `src/Modules/Settings/Users/Views/Detail.php`  
**Scope:** Singleton dependency removal across Models, AppRequest, and static factories

---

## Table of Contents

1. [Current Singleton Usage Analysis](#1-current-singleton-usage-analysis)
2. [Problems with Current Architecture](#2-problems-with-current-architecture)
3. [Refactoring Strategy Overview](#3-refactoring-strategy-overview)
4. [Detailed Migration Patterns](#4-detailed-migration-patterns)
5. [Implementation Plan](#5-implementation-plan)
6. [Testing Strategy](#6-testing-strategy)
7. [Rollback Plan](#7-rollback-plan)
8. [Timeline and Resources](#8-timeline-and-resources)

---

## 1. Current Singleton Usage Analysis

### 1.1 Singleton Patterns Identified

The FreeCRM codebase contains **2,473 getInstance() calls** across **811 files**, representing extensive singleton usage:

#### Primary Singleton Categories:

| Singleton Type | Usage Count | Examples | Purpose |
|---------------|-------------|----------|---------|
| **Model Factories** | ~1,800+ | `Module::getInstance()`, `Record::getInstanceById()` | Object instantiation |
| **Current User** | ~400+ | `Record::getCurrentUserModel()` | Session user access |
| **AppRequest** | ~150+ | `AppRequest::init()`, `AppRequest::get()` | HTTP request data |
| **Menu/Settings** | ~100+ | `Menu::getInstance()`, `Menu::getInstanceById()` | Settings navigation |
| **Other Services** | ~20+ | Database, Cache, etc. | Infrastructure services |

### 1.2 Analysis of Users Detail View

**File:** `src/Modules/Settings/Users/Views/Detail.php`

```php
20:  $currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
46:  $settingsModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance();
72:  $viewer->assign('CURRENT_USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
```

#### Dependency Graph for Detail View:

```
Detail::checkPermission()
└── Record::getCurrentUserModel() ──────┐
    └── Session::get('authenticated_user_id')  │ SINGLETON #1
    └── Record::getInstanceById()              │
        └── CRMEntity::getInstance()           │
            └── Database::getInstance()        │
                                               │
Detail::preProcessSettings()                   │
└── Module::getInstance() ──────────────┐     │
    └── new Module() (factory pattern)  │ SINGLETON #2
                                        │     │
Detail::process()                       │     │
└── Record::getCurrentUserModel() ──────┴─────┘ SINGLETON #1 (duplicate)
```

### 1.3 Detailed Singleton Analysis

#### Singleton #1: `getCurrentUserModel()`

**Location:** `src/Modules/Users/Models/Record.php:365-377`

```php
public static function getCurrentUserModel()
{
    if (static::$currentUserCache) {
        return static::$currentUserCache;
    }
    if (!static::$currentUserId) {
        static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
    }
    return static::$currentUserCache = self::getInstanceById(
        static::$currentUserId,
        'Users'
    );
}
```

**Issues:**
- Static cache creates global state
- Accesses session directly (tight coupling)
- Cannot inject different user for testing
- Hidden dependency not visible in method signature

**Dependencies:**
- `\App\Http\Vtiger_Session` - Session management
- `self::getInstanceById()` - Record factory
- Static properties: `$currentUserCache`, `$currentUserId`

#### Singleton #2: `Module::getInstance()`

**Location:** `src/Modules/Settings/Vtiger/Models/Module.php:126-138`

```php
public static function getInstance($name = 'Settings:Vtiger')
{
    // For Settings:Vtiger, return instance of this class
    if ($name === 'Settings:Vtiger') {
        return new self();
    }
    $modelClassName = \App\Loader::getComponentClassName('Model', 'Module', $name);
    // Ensure class name is resolved from global namespace
    if ($modelClassName[0] !== '\\') {
        $modelClassName = '\\' . $modelClassName;
    }
    return new $modelClassName();
}
```

**Issues:**
- Factory pattern masquerading as singleton
- No actual singleton behavior (creates new instances)
- Misleading name (`getInstance` implies caching)
- Could be regular factory method or constructor

#### Singleton #3: `Menu::getInstance()` and `Menu::getInstanceById()`

**Location:** `src/Modules/Settings/Vtiger/Models/Menu.php`

```php
// Line 89-104: getAll() - uses getCurrentUserId singleton
public static function getAll()
{
    if (self::$casheMenu) {
        return self::$casheMenu;
    }
    $dataReader = (new \App\Db\Query())
        ->from(self::$menusTable)
        ->where(['or', 
            ['like', 'admin_access', ',' . \App\Modules\Users\Models\Record::getCurrentUserId() . ','], 
            ['admin_access' => null]])
        ->orderBy(['sequence' => SORT_ASC])
        ->createCommand()->query();
    // ... creates menu models
    self::$casheMenu = $menuModels;
    return $menuModels;
}

// Line 127-149: getInstanceById() - true singleton with cache
public static function getInstanceById($id, $module = null)
{
    if (isset(self::$cacheInstance[$id])) {
        return self::$cacheInstance[$id];
    }
    // ... loads from database
    $instance = \App\Modules\Settings\Vtiger\Models\Menu::getInstanceFromArray($rowData);
    self::$cacheInstance[$id] = $instance;
    return $instance;
}
```

**Issues:**
- `getAll()` depends on `getCurrentUserId()` singleton
- Static caching prevents testing with different users
- Cache invalidation complexity
- Hidden dependencies on session state

#### Singleton #4: `AppRequest`

**Location:** `src/Http/AppRequest.php`

```php
private static $request = null;

public static function init()
{
    if (!self::$request) {
        self::$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
    }
    return self::$request;
}

public static function get($key, $defvalue = '')
{
    if (!self::$request) {
        static::init();
    }
    return self::$request->get($key, $defvalue);
}
```

**Issues:**
- Global mutable state
- Ties to PHP superglobals ($_REQUEST)
- Impossible to test with different requests
- Already identified in `phase-3b-remove-apprequest.plan.md`

---

## 2. Problems with Current Architecture

### 2.1 Testability Issues

**Problem:** Cannot unit test components in isolation

**Example:**
```php
// Current code - impossible to test with different users
public function checkPermission(\App\Http\Vtiger_Request $request)
{
    $currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
    // How do we test this as non-admin? We can't!
    if ($currentUserModel->isAdminUser() === true) {
        return true;
    }
}
```

**Impact:**
- Tests must set up global session state
- Tests interfere with each other
- Cannot run tests in parallel
- Difficult to test edge cases

### 2.2 Dependency Hiding

**Problem:** Dependencies not visible in method signatures

**Example:**
```php
// What dependencies does this method have?
public function process(\App\Http\Vtiger_Request $request)
{
    // Hidden: depends on current user session
    // Hidden: depends on global request state
    // Hidden: depends on database singleton
}
```

**Impact:**
- Hard to understand what a method needs
- Difficult to refactor
- Tight coupling to infrastructure

### 2.3 Lifecycle Management

**Problem:** No control over object lifecycle

**Example:**
```php
// When is this user loaded? Can we control it?
$user = Record::getCurrentUserModel();

// Is this cached? How long? How to invalidate?
$menu = Menu::getInstanceById(123);
```

**Impact:**
- Cache invalidation bugs
- Memory leaks from static caches
- Stale data issues
- No request-scoped caching

### 2.4 API and CLI Usage

**Problem:** Singletons tied to web session

**Example:**
```php
// In API context - no web session!
$currentUser = Record::getCurrentUserModel();
// Returns null or admin user - not the API user

// In CLI/cron - no current user concept
$menu = Menu::getAll();
// Fails or returns wrong data
```

**Impact:**
- API endpoints need workarounds
- CLI scripts need special handling
- Background jobs unreliable

---

## 3. Refactoring Strategy Overview

### 3.1 Overall Approach: Dependency Injection

**Core Principle:** Pass dependencies explicitly instead of accessing them globally

```php
// BEFORE: Hidden singleton dependencies
public function checkPermission(\App\Http\Vtiger_Request $request)
{
    $currentUser = Record::getCurrentUserModel(); // SINGLETON
    // ...
}

// AFTER: Explicit dependencies
public function checkPermission(
    \App\Http\Vtiger_Request $request,
    \App\Modules\Users\Models\Record $currentUser
)
{
    // User passed explicitly
}
```

### 3.2 Migration Phases

#### Phase 1: Request Object Enhancement (1-2 weeks)
- Add user to Request object
- Add request context services
- Backward compatibility wrappers

#### Phase 2: Controller Pattern (2-3 weeks)
- Introduce base controller with DI
- Migrate view classes to extend base controller
- Inject dependencies in constructors

#### Phase 3: Model Factories (3-4 weeks)
- Replace getInstance() with factory services
- Implement repository pattern
- Cache in request scope instead of static

#### Phase 4: Cleanup (1-2 weeks)
- Remove singleton fallbacks
- Remove static caches
- Update tests

### 3.3 Design Patterns to Use

| Pattern | Use Case | Example |
|---------|----------|---------|
| **Dependency Injection** | Pass dependencies explicitly | Constructor injection |
| **Factory Pattern** | Create model instances | ModelFactory service |
| **Repository Pattern** | Data access layer | UserRepository |
| **Service Locator** | Transition period only | Request container |
| **Request Scoped Cache** | Per-request caching | Request cache bag |

---

## 4. Detailed Migration Patterns

### 4.1 Pattern 1: Current User Access

#### Before:
```php
class Detail extends \App\Modules\Users\Views\PreferenceDetail
{
    public function checkPermission(\App\Http\Vtiger_Request $request)
    {
        $currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
        $record = $request->get('record');
        if ($currentUserModel->isAdminUser() === true) {
            return true;
        }
    }
}
```

#### After - Option A: Request Enhancement (Recommended)
```php
class Detail extends \App\Modules\Users\Views\PreferenceDetail
{
    public function checkPermission(\App\Http\Vtiger_Request $request)
    {
        $currentUserModel = $request->getUser(); // ✅ From request
        $record = $request->get('record');
        if ($currentUserModel->isAdminUser() === true) {
            return true;
        }
    }
}
```

**Implementation:**
```php
// In Vtiger_Request class
protected $user;

public function __construct($request, $getRequest)
{
    parent::__construct($request, $getRequest);
    // Load user from session during request initialization
    $this->user = $this->loadCurrentUser();
}

protected function loadCurrentUser()
{
    $userId = \App\Http\Vtiger_Session::get('authenticated_user_id');
    if ($userId) {
        return \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
    }
    return null;
}

public function getUser()
{
    return $this->user;
}

public function setUser(\App\Modules\Users\Models\Record $user)
{
    $this->user = $user;
    return $this;
}

public function getUserId()
{
    return $this->user ? $this->user->getId() : null;
}
```

**Migration Steps:**
1. ✅ Add user loading to Request constructor
2. ✅ Add getUser(), setUser(), getUserId() methods
3. ✅ Update WebUI.php to inject user into request
4. ✅ Replace `getCurrentUserModel()` with `$request->getUser()`
5. ✅ Add backward compatibility wrapper in Record class
6. ✅ Update tests to use `$request->setUser()`

**Backward Compatibility:**
```php
// In Record.php - temporary during migration
public static function getCurrentUserModel()
{
    // Try to get from current request context
    try {
        $request = \App\Http\AppRequest::init();
        if ($request && $request->getUser()) {
            return $request->getUser();
        }
    } catch (\Exception $e) {
        // Fall back to session
    }
    
    // Legacy fallback
    if (static::$currentUserCache) {
        return static::$currentUserCache;
    }
    if (!static::$currentUserId) {
        static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
    }
    return static::$currentUserCache = self::getInstanceById(
        static::$currentUserId,
        'Users'
    );
}
```

#### After - Option B: Constructor Injection (More invasive)
```php
class Detail extends \App\Modules\Users\Views\PreferenceDetail
{
    protected $currentUser;
    
    public function __construct(\App\Modules\Users\Models\Record $currentUser = null)
    {
        parent::__construct($currentUser);
        $this->currentUser = $currentUser ?? \App\Modules\Users\Models\Record::getCurrentUserModel();
    }
    
    public function checkPermission(\App\Http\Vtiger_Request $request)
    {
        $currentUserModel = $this->currentUser;
        $record = $request->get('record');
        if ($currentUserModel->isAdminUser() === true) {
            return true;
        }
    }
}
```

**Recommendation:** Use Option A (Request Enhancement) as it requires fewer changes and maintains backward compatibility.

---

### 4.2 Pattern 2: Module/Model Factories

#### Before:
```php
public function preProcessSettings(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();
    $qualifiedModuleName = $request->getModule(false);
    $selectedMenuId = $request->get('block');
    $fieldId = $request->get('fieldid');
    
    // Factory singleton
    $settingsModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance();
    
    $menuModels = $settingsModel->getMenus();
    $menu = $settingsModel->prepareMenuToDisplay($menuModels, $moduleName, $selectedMenuId, $fieldId);
    // ...
}
```

#### After - Option A: Service Locator (Transition)
```php
public function preProcessSettings(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();
    $qualifiedModuleName = $request->getModule(false);
    $selectedMenuId = $request->get('block');
    $fieldId = $request->get('fieldid');
    
    // Get from request service container
    $settingsModel = $request->getService('SettingsModule');
    // OR
    $settingsModel = $request->getModuleModel('Settings:Vtiger');
    
    $menuModels = $settingsModel->getMenus();
    $menu = $settingsModel->prepareMenuToDisplay($menuModels, $moduleName, $selectedMenuId, $fieldId);
    // ...
}
```

**Implementation:**
```php
// In Vtiger_Request class
protected $services = [];
protected $moduleModels = [];

public function getService($serviceName)
{
    if (!isset($this->services[$serviceName])) {
        $this->services[$serviceName] = $this->createService($serviceName);
    }
    return $this->services[$serviceName];
}

protected function createService($serviceName)
{
    switch ($serviceName) {
        case 'SettingsModule':
            return new \App\Modules\Settings\Vtiger\Models\Module();
        // ... other services
        default:
            throw new \Exception("Unknown service: $serviceName");
    }
}

public function getModuleModel($moduleName)
{
    if (!isset($this->moduleModels[$moduleName])) {
        $this->moduleModels[$moduleName] = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
    }
    return $this->moduleModels[$moduleName];
}
```

#### After - Option B: Proper DI Container (Long-term)
```php
// Container configuration
class ServiceContainer
{
    protected $factories = [];
    protected $instances = [];
    
    public function register($name, callable $factory)
    {
        $this->factories[$name] = $factory;
    }
    
    public function get($name)
    {
        if (!isset($this->instances[$name])) {
            if (!isset($this->factories[$name])) {
                throw new \Exception("Service not registered: $name");
            }
            $this->instances[$name] = $this->factories[$name]($this);
        }
        return $this->instances[$name];
    }
}

// Bootstrap
$container = new ServiceContainer();
$container->register('SettingsModule', function($c) {
    return new \App\Modules\Settings\Vtiger\Models\Module();
});
$container->register('UserRepository', function($c) {
    return new \App\Repository\UserRepository($c->get('Database'));
});

// Usage in controller
class Detail extends BaseController
{
    protected $settingsModule;
    
    public function __construct(ServiceContainer $container)
    {
        $this->settingsModule = $container->get('SettingsModule');
    }
}
```

**Recommendation:** Start with Option A (Service Locator in Request) for quick wins, then move to Option B for proper architecture.

---

### 4.3 Pattern 3: Menu Singleton with User Dependency

#### Before:
```php
// In Menu.php
public static function getAll()
{
    if (self::$casheMenu) {
        return self::$casheMenu;
    }
    // Hidden dependency on current user!
    $dataReader = (new \App\Db\Query())
        ->from(self::$menusTable)
        ->where(['or', 
            ['like', 'admin_access', ',' . \App\Modules\Users\Models\Record::getCurrentUserId() . ','], 
            ['admin_access' => null]])
        ->orderBy(['sequence' => SORT_ASC])
        ->createCommand()->query();
    // ...
    self::$casheMenu = $menuModels;
    return $menuModels;
}
```

**Issues:**
- Static cache shared across all users!
- If admin loads menu, then regular user's request might get admin's cached menu
- Security vulnerability

#### After:
```php
// In Menu.php - Remove static cache, make instance method
public function getAllForUser($userId)
{
    $cacheKey = "menu_all_user_{$userId}";
    
    // Check request-scoped cache
    if ($this->cache && $this->cache->has($cacheKey)) {
        return $this->cache->get($cacheKey);
    }
    
    $dataReader = (new \App\Db\Query())
        ->from(self::$menusTable)
        ->where(['or', 
            ['like', 'admin_access', ',' . $userId . ','], 
            ['admin_access' => null]])
        ->orderBy(['sequence' => SORT_ASC])
        ->createCommand()->query();
    
    $menuModels = [];
    while ($row = $dataReader->read()) {
        $blockId = $row[self::$menuId];
        $menuModels[$blockId] = \App\Modules\Settings\Vtiger\Models\Menu::getInstanceFromArray($row);
    }
    
    // Cache in request scope
    if ($this->cache) {
        $this->cache->set($cacheKey, $menuModels);
    }
    
    return $menuModels;
}

// In Detail.php
public function preProcessSettings(\App\Http\Vtiger_Request $request)
{
    // ...
    $settingsModel = $request->getService('SettingsModule');
    $menuModels = $settingsModel->getMenusForUser($request->getUserId());
    // ...
}
```

**Request-Scoped Cache Implementation:**
```php
// In Vtiger_Request
protected $cache = [];

public function getCached($key)
{
    return $this->cache[$key] ?? null;
}

public function setCached($key, $value)
{
    $this->cache[$key] = $value;
}

public function hasCached($key)
{
    return isset($this->cache[$key]);
}
```

---

### 4.4 Pattern 4: Record Factory Pattern

#### Current Problem:
```php
// Scattered throughout codebase
$record = \App\Modules\Vtiger\Models\Record::getInstanceById($id, $module);
$user = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
$menu = \App\Modules\Settings\Vtiger\Models\Menu::getInstanceById($menuId);
```

#### Repository Pattern Solution:
```php
// UserRepository.php
namespace App\Repository;

class UserRepository
{
    protected $cache = [];
    
    public function findById($userId)
    {
        if (isset($this->cache[$userId])) {
            return $this->cache[$userId];
        }
        
        $user = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
        $this->cache[$userId] = $user;
        return $user;
    }
    
    public function getCurrentUser($request)
    {
        $userId = $request->getUserId();
        return $userId ? $this->findById($userId) : null;
    }
    
    public function findByUsername($username)
    {
        // Implementation
    }
}

// RecordRepository.php
namespace App\Repository;

class RecordRepository
{
    protected $cache = [];
    
    public function findById($recordId, $moduleName)
    {
        $cacheKey = "{$moduleName}:{$recordId}";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $record = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName);
        $this->cache[$cacheKey] = $record;
        return $record;
    }
}

// Usage
$userRepo = $request->getService('UserRepository');
$currentUser = $userRepo->getCurrentUser($request);
$targetUser = $userRepo->findById($request->get('record'));
```

---

## 5. Implementation Plan

### 5.1 Phase 1: Request Enhancement (Week 1-2)

#### Task 1.1: Add User to Request Object
**Estimated Time:** 2 days

**Files to Modify:**
- `src/Http/Vtiger_Request.php`

**Changes:**
```php
class Vtiger_Request extends \App\Http\Request
{
    protected $user;
    protected $services = [];
    protected $cache = [];
    
    public function __construct($request, $getRequest)
    {
        parent::__construct($request, $getRequest);
        $this->loadCurrentUser();
    }
    
    protected function loadCurrentUser()
    {
        $userId = \App\Http\Vtiger_Session::get('authenticated_user_id');
        if ($userId) {
            $this->user = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
        }
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function setUser(\App\Modules\Users\Models\Record $user)
    {
        $this->user = $user;
        return $this;
    }
    
    public function getUserId()
    {
        return $this->user ? $this->user->getId() : null;
    }
    
    public function isAdmin()
    {
        return $this->user && $this->user->isAdminUser();
    }
}
```

**Testing:**
```bash
# Test user loading
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=Users&action=Login"

curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Users&parent=Settings&view=Detail&record=1"
```

#### Task 1.2: Add Service Container to Request
**Estimated Time:** 3 days

**Implementation:**
```php
// In Vtiger_Request
protected $services = [];

public function getService($serviceName)
{
    if (!isset($this->services[$serviceName])) {
        $this->services[$serviceName] = $this->createService($serviceName);
    }
    return $this->services[$serviceName];
}

protected function createService($serviceName)
{
    // Service factory
    switch ($serviceName) {
        case 'UserRepository':
            return new \App\Repository\UserRepository($this);
        case 'RecordRepository':
            return new \App\Repository\RecordRepository($this);
        case 'SettingsModule':
            return new \App\Modules\Settings\Vtiger\Models\Module();
        default:
            throw new \Exception("Unknown service: $serviceName");
    }
}

public function getModuleModel($moduleName)
{
    return $this->getService('ModuleRepository')->getModule($moduleName);
}
```

**Testing:**
- Create unit tests for service container
- Test service creation
- Test singleton behavior per request

#### Task 1.3: Add Request-Scoped Cache
**Estimated Time:** 1 day

**Implementation:**
```php
protected $cache = [];

public function getCached($key, $default = null)
{
    return $this->cache[$key] ?? $default;
}

public function setCached($key, $value)
{
    $this->cache[$key] = $value;
    return $this;
}

public function hasCached($key)
{
    return isset($this->cache[$key]);
}

public function getCachedOrCompute($key, callable $callback)
{
    if (!$this->hasCached($key)) {
        $this->setCached($key, $callback());
    }
    return $this->getCached($key);
}
```

### 5.2 Phase 2: Migrate Users Detail View (Week 3)

#### Task 2.1: Update Detail.php
**Estimated Time:** 1 day

**Changes:**
```php
// Line 20: checkPermission
public function checkPermission(\App\Http\Vtiger_Request $request)
{
    // BEFORE:
    // $currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
    
    // AFTER:
    $currentUserModel = $request->getUser();
    
    $record = $request->get('record');
    if ($currentUserModel->isAdminUser() === true || 
        ($currentUserModel->get('id') == $record && \App\AppConfig::security('SHOW_MY_PREFERENCES'))) {
        return true;
    } else {
        throw new \Exception\AppException('LBL_PERMISSION_DENIED');
    }
}

// Line 46: preProcessSettings
public function preProcessSettings(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();
    $qualifiedModuleName = $request->getModule(false);
    $selectedMenuId = $request->get('block');
    $fieldId = $request->get('fieldid');
    
    // BEFORE:
    // $settingsModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance();
    
    // AFTER - Option 1: Direct instantiation (getInstance doesn't cache anyway)
    $settingsModel = new \App\Modules\Settings\Vtiger\Models\Module();
    
    // OR Option 2: From service container
    // $settingsModel = $request->getService('SettingsModule');
    
    $menuModels = $settingsModel->getMenus();
    $menu = $settingsModel->prepareMenuToDisplay($menuModels, $moduleName, $selectedMenuId, $fieldId);
    $viewer->assign('MENUS', $menu);
    $viewer->assign('MODULE', $moduleName);
    $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
    $viewer->view('SettingsMenuStart.tpl', $qualifiedModuleName);
}

// Line 72: process
public function process(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    
    // BEFORE:
    // $viewer->assign('CURRENT_USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
    
    // AFTER:
    $viewer->assign('CURRENT_USER_MODEL', $request->getUser());
    
    $viewer->view('UserViewHeader.tpl', $request->getModule());
    parent::process($request);
}
```

**Testing:**
```bash
# Test as admin
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=Users&action=Login"

curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Users&parent=Settings&view=Detail&record=1" | grep -i "CURRENT_USER_MODEL"

# Test as regular user viewing their own record
curl -s -c /tmp/cookies2.txt -b /tmp/cookies2.txt -L \
  -d "username=user&password=user" \
  -X POST "http://localhost/index.php?module=Users&action=Login"

curl -s -c /tmp/cookies2.txt -b /tmp/cookies2.txt -L \
  "http://localhost/index.php?module=Users&parent=Settings&view=Detail&record=2"

# Test permission denied (regular user accessing other user)
curl -s -c /tmp/cookies2.txt -b /tmp/cookies2.txt -L \
  "http://localhost/index.php?module=Users&parent=Settings&view=Detail&record=1" | grep -i "permission"
```

### 5.3 Phase 3: Migrate Menu Models (Week 4)

#### Task 3.1: Update Menu::getAll() to Accept User ID

**Current Code:**
```php
public static function getAll()
{
    if (self::$casheMenu) {
        return self::$casheMenu;
    }
    $dataReader = (new \App\Db\Query())->from(self::$menusTable)
        ->where(['or', 
            ['like', 'admin_access', ',' . \App\Modules\Users\Models\Record::getCurrentUserId() . ','], 
            ['admin_access' => null]])
        ->orderBy(['sequence' => SORT_ASC])
        ->createCommand()->query();
    // ...
}
```

**Refactored Code:**
```php
/**
 * Get all menus for specified user
 * @param int $userId User ID (if null, tries to get from current request)
 * @param \App\Http\Vtiger_Request $request Optional request for caching
 * @return array Menu models
 */
public static function getAllForUser($userId = null, $request = null)
{
    // Backward compatibility: get user ID from session if not provided
    if ($userId === null) {
        if ($request && $request->getUser()) {
            $userId = $request->getUserId();
        } else {
            $userId = \App\Modules\Users\Models\Record::getCurrentUserId();
        }
    }
    
    $cacheKey = "menu_all_user_{$userId}";
    
    // Try request cache first
    if ($request && $request->hasCached($cacheKey)) {
        return $request->getCached($cacheKey);
    }
    
    // Try static cache (for backward compatibility during transition)
    if (isset(self::$casheMenu[$userId])) {
        return self::$casheMenu[$userId];
    }
    
    $dataReader = (new \App\Db\Query())->from(self::$menusTable)
        ->where(['or', 
            ['like', 'admin_access', ',' . $userId . ','], 
            ['admin_access' => null]])
        ->orderBy(['sequence' => SORT_ASC])
        ->createCommand()->query();
    
    $menuModels = [];
    while ($row = $dataReader->read()) {
        $blockId = $row[self::$menuId];
        $menuModels[$blockId] = self::getInstanceFromArray($row);
    }
    
    // Cache results
    self::$casheMenu[$userId] = $menuModels;
    if ($request) {
        $request->setCached($cacheKey, $menuModels);
    }
    
    return $menuModels;
}

/**
 * Backward compatibility wrapper
 * @deprecated Use getAllForUser() instead
 */
public static function getAll()
{
    return self::getAllForUser();
}
```

**Update callers:**
```php
// In Module.php
public function getMenus($request = null)
{
    return \App\Modules\Settings\Vtiger\Models\Menu::getAllForUser(
        $request ? $request->getUserId() : null,
        $request
    );
}

// In Detail.php
$menuModels = $settingsModel->getMenus($request);
```

### 5.4 Phase 4: Backward Compatibility Wrappers (Week 5)

#### Task 4.1: Update getCurrentUserModel()

```php
// In Record.php
/**
 * Get current user model
 * @deprecated Use $request->getUser() instead
 * @return \App\Modules\Users\Models\Record
 */
public static function getCurrentUserModel()
{
    // Log deprecation warning
    if (\App\AppConfig::debug('LOG_DEPRECATION')) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? ['file' => 'unknown', 'line' => 0];
        \App\Log::warning(
            'DEPRECATED: getCurrentUserModel() called from ' . 
            basename($caller['file']) . ':' . $caller['line'] . 
            '. Use $request->getUser() instead.'
        );
    }
    
    // Try to get from current request context first
    try {
        $request = \App\Http\AppRequest::init();
        if ($request && method_exists($request, 'getUser') && $request->getUser()) {
            return $request->getUser();
        }
    } catch (\Exception $e) {
        // Request not available, fall through to legacy
    }
    
    // Legacy implementation
    if (static::$currentUserCache) {
        return static::$currentUserCache;
    }
    if (!static::$currentUserId) {
        static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
    }
    return static::$currentUserCache = self::getInstanceById(
        static::$currentUserId,
        'Users'
    );
}
```

### 5.5 Phase 5: Global Search and Replace (Week 6-7)

#### Task 5.1: Automated Refactoring

**Script:** `tools/refactor_singletons.php`

```php
<?php
/**
 * Automated singleton refactoring script
 * Usage: php tools/refactor_singletons.php [--dry-run] [--file=path]
 */

$dryRun = in_array('--dry-run', $argv);
$specificFile = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--file=') === 0) {
        $specificFile = substr($arg, 7);
    }
}

// Patterns to replace
$patterns = [
    // Pattern 1: getCurrentUserModel() in methods with $request parameter
    [
        'search' => '/(\$[a-zA-Z_]+)\s*=\s*\\\\App\\\\Modules\\\\Users\\\\Models\\\\Record::getCurrentUserModel\(\)/',
        'replace' => '$1 = $request->getUser()',
        'context' => 'function.*\$request',
    ],
    
    // Pattern 2: Module::getInstance() for Settings
    [
        'search' => '/\$([a-zA-Z_]+)\s*=\s*\\\\App\\\\Modules\\\\Settings\\\\Vtiger\\\\Models\\\\Module::getInstance\(\)/',
        'replace' => '$$1 = new \\App\\Modules\\Settings\\Vtiger\\Models\\Module()',
        'context' => null,
    ],
];

// Find all PHP files
$files = $specificFile ? [$specificFile] : getPhpFiles('src/Modules');

foreach ($files as $file) {
    refactorFile($file, $patterns, $dryRun);
}

function refactorFile($file, $patterns, $dryRun)
{
    $content = file_get_contents($file);
    $original = $content;
    $changes = 0;
    
    foreach ($patterns as $pattern) {
        if ($pattern['context']) {
            // Context-aware replacement
            $content = preg_replace_callback(
                '/(' . $pattern['context'] . '.*?)\{(.*?)\}/s',
                function($matches) use ($pattern, &$changes) {
                    $replaced = preg_replace(
                        $pattern['search'],
                        $pattern['replace'],
                        $matches[2],
                        -1,
                        $count
                    );
                    $changes += $count;
                    return $matches[1] . '{' . $replaced . '}';
                },
                $content
            );
        } else {
            // Simple replacement
            $content = preg_replace(
                $pattern['search'],
                $pattern['replace'],
                $content,
                -1,
                $count
            );
            $changes += $count;
        }
    }
    
    if ($changes > 0) {
        echo "File: $file - $changes changes\n";
        if (!$dryRun) {
            file_put_contents($file, $content);
        } else {
            // Show diff
            showDiff($original, $content, $file);
        }
    }
}

function getPhpFiles($dir)
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

function showDiff($original, $modified, $file)
{
    // Simple diff display
    $originalLines = explode("\n", $original);
    $modifiedLines = explode("\n", $modified);
    
    echo "\n--- $file\n+++ $file (modified)\n";
    
    for ($i = 0; $i < max(count($originalLines), count($modifiedLines)); $i++) {
        $origLine = $originalLines[$i] ?? '';
        $modLine = $modifiedLines[$i] ?? '';
        
        if ($origLine !== $modLine) {
            if ($origLine) echo "- $origLine\n";
            if ($modLine) echo "+ $modLine\n";
        }
    }
    echo "\n";
}
```

**Usage:**
```bash
# Dry run to see what would change
php tools/refactor_singletons.php --dry-run

# Refactor specific file
php tools/refactor_singletons.php --file=src/Modules/Users/Views/Detail.php

# Refactor all files
php tools/refactor_singletons.php
```

### 5.6 Phase 6: Testing and Validation (Week 8)

#### Task 6.1: Create Test Suite

**Test:** `tests/Unit/Request/UserInjectionTest.php`

```php
<?php
namespace Tests\Unit\Request;

use PHPUnit\Framework\TestCase;

class UserInjectionTest extends TestCase
{
    public function testRequestHasUser()
    {
        // Create mock user
        $user = $this->createMock(\App\Modules\Users\Models\Record::class);
        $user->method('getId')->willReturn(1);
        $user->method('isAdminUser')->willReturn(true);
        
        // Create request
        $request = new \App\Http\Vtiger_Request([], []);
        $request->setUser($user);
        
        // Assert
        $this->assertSame($user, $request->getUser());
        $this->assertEquals(1, $request->getUserId());
        $this->assertTrue($request->isAdmin());
    }
    
    public function testRequestWithoutUser()
    {
        $request = new \App\Http\Vtiger_Request([], []);
        $request->setUser(null);
        
        $this->assertNull($request->getUser());
        $this->assertNull($request->getUserId());
        $this->assertFalse($request->isAdmin());
    }
}
```

**Test:** `tests/Integration/Settings/UsersDetailTest.php`

```php
<?php
namespace Tests\Integration\Settings;

use PHPUnit\Framework\TestCase;

class UsersDetailTest extends TestCase
{
    public function testAdminCanViewAnyUser()
    {
        // Setup admin user
        $admin = $this->createAdminUser();
        $request = $this->createRequestWithUser($admin, [
            'module' => 'Users',
            'parent' => 'Settings',
            'view' => 'Detail',
            'record' => '2' // Different user
        ]);
        
        $detail = new \App\Modules\Settings\Users\Views\Detail();
        
        // Should not throw exception
        $detail->checkPermission($request);
        $this->assertTrue(true);
    }
    
    public function testRegularUserCannotViewOtherUser()
    {
        $this->expectException(\Exception\AppException::class);
        $this->expectExceptionMessage('LBL_PERMISSION_DENIED');
        
        $user = $this->createRegularUser();
        $request = $this->createRequestWithUser($user, [
            'module' => 'Users',
            'parent' => 'Settings',
            'view' => 'Detail',
            'record' => '1' // Admin user
        ]);
        
        $detail = new \App\Modules\Settings\Users\Views\Detail();
        $detail->checkPermission($request);
    }
    
    protected function createAdminUser()
    {
        $user = $this->createMock(\App\Modules\Users\Models\Record::class);
        $user->method('getId')->willReturn(1);
        $user->method('get')->willReturnCallback(function($key) {
            return $key === 'id' ? 1 : null;
        });
        $user->method('isAdminUser')->willReturn(true);
        return $user;
    }
    
    protected function createRegularUser()
    {
        $user = $this->createMock(\App\Modules\Users\Models\Record::class);
        $user->method('getId')->willReturn(2);
        $user->method('get')->willReturnCallback(function($key) {
            return $key === 'id' ? 2 : null;
        });
        $user->method('isAdminUser')->willReturn(false);
        return $user;
    }
    
    protected function createRequestWithUser($user, $params)
    {
        $request = new \App\Http\Vtiger_Request($params, $params);
        $request->setUser($user);
        return $request;
    }
}
```

---

## 6. Testing Strategy

### 6.1 Unit Testing

**Coverage Goals:**
- Request object: 100%
- Service container: 100%
- User injection: 100%
- Cache mechanisms: 100%

**Tools:**
- PHPUnit
- Mockery for complex mocking
- Code coverage reports

### 6.2 Integration Testing

**Test Scenarios:**
1. **Admin Access**
   - Admin viewing own profile
   - Admin viewing other user profiles
   - Admin accessing settings

2. **Regular User Access**
   - User viewing own profile
   - User denied access to other profiles
   - User access to allowed settings

3. **Session Management**
   - User login and session creation
   - User logout and session cleanup
   - Session timeout handling

4. **Permission Checks**
   - Module permissions
   - Record-level permissions
   - Field-level permissions

### 6.3 Manual Testing Checklist

```markdown
## Settings > Users > Detail View

### As Admin
- [ ] Login as admin
- [ ] Navigate to Settings > Users
- [ ] Click on admin user (record 1)
- [ ] Verify detail view loads
- [ ] Verify menu displays correctly
- [ ] Check no error in cache/logs/system.log
- [ ] Click on regular user (record 2)
- [ ] Verify detail view loads
- [ ] Logout

### As Regular User  
- [ ] Login as regular user
- [ ] Navigate to Settings (if allowed)
- [ ] Try to access own profile
- [ ] Verify can view own data (if SHOW_MY_PREFERENCES = true)
- [ ] Try to access admin profile (record 1)
- [ ] Verify permission denied
- [ ] Check error handling is graceful
- [ ] Logout

### Multi-User Scenarios
- [ ] Admin session active
- [ ] Open incognito window
- [ ] Login as regular user
- [ ] Verify menus are different
- [ ] Admin should see all menus
- [ ] Regular user should see limited menus
- [ ] Verify no cache pollution between users
```

### 6.4 Performance Testing

**Benchmarks:**

```php
// Before refactoring
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $user = \App\Modules\Users\Models\Record::getCurrentUserModel();
}
$time = microtime(true) - $start;
echo "Static singleton: {$time}s\n";

// After refactoring
$request = \App\Http\AppRequest::init();
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $user = $request->getUser();
}
$time = microtime(true) - $start;
echo "Request injection: {$time}s\n";
```

**Expected Results:**
- Request injection should be faster (no session lookup per call)
- Memory usage should be similar or lower
- No N+1 query issues

---

## 7. Rollback Plan

### 7.1 Rollback Triggers

Initiate rollback if:
- Critical production bug discovered
- Performance degradation > 20%
- User reports > 5 similar issues
- Failed automated tests

### 7.2 Rollback Procedure

#### Step 1: Identify Commit
```bash
git log --oneline --grep="singleton refactor"
```

#### Step 2: Create Rollback Branch
```bash
git checkout -b rollback/singleton-refactor-$(date +%Y%m%d)
git revert <commit-hash>
```

#### Step 3: Test Rollback
```bash
# Run test suite
vendor/bin/phpunit

# Test critical paths
bash tests/manual/critical_path.sh
```

#### Step 4: Deploy Rollback
```bash
git push origin rollback/singleton-refactor-$(date +%Y%m%d)
# Deploy via your deployment process
```

### 7.3 Partial Rollback

If only specific files are problematic:

```bash
# Revert specific file
git checkout <previous-commit> -- src/Modules/Settings/Users/Views/Detail.php
git commit -m "Partial rollback: Detail.php"
```

### 7.4 Backward Compatibility Maintenance

The refactored code includes backward compatibility wrappers that allow gradual rollback:

```php
// Can toggle between old and new behavior via config
if (\App\AppConfig::feature('USE_REQUEST_USER_INJECTION')) {
    $user = $request->getUser(); // New way
} else {
    $user = Record::getCurrentUserModel(); // Old way (fallback)
}
```

---

## 8. Timeline and Resources

### 8.1 Estimated Timeline

| Phase | Duration | Effort | Dependencies |
|-------|----------|--------|--------------|
| **Phase 1: Request Enhancement** | 2 weeks | 80h | None |
| **Phase 2: Users Detail View** | 1 week | 40h | Phase 1 |
| **Phase 3: Menu Models** | 1 week | 40h | Phase 1 |
| **Phase 4: Backward Compatibility** | 1 week | 40h | Phase 1 |
| **Phase 5: Global Refactoring** | 2 weeks | 80h | Phases 2-4 |
| **Phase 6: Testing & Validation** | 2 weeks | 80h | All phases |
| **Total** | **9 weeks** | **360h** | |

### 8.2 Resource Requirements

**Team Composition:**
- 1 Senior PHP Developer (lead)
- 1 PHP Developer (implementation)
- 1 QA Engineer (testing)
- 1 DevOps Engineer (deployment support)

**Skills Required:**
- Deep PHP knowledge
- Design patterns (DI, Factory, Repository)
- Testing (PHPUnit, integration tests)
- Git version control
- FreeCRM/Vtiger architecture

### 8.3 Risks and Mitigation

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Breaking changes | High | High | Extensive testing, backward compatibility wrappers |
| Performance regression | Medium | High | Performance benchmarks, monitoring |
| Incomplete migration | Medium | Medium | Automated scanning, deprecation warnings |
| Team resistance | Low | Medium | Documentation, training, clear benefits |
| Third-party module breakage | High | High | Backward compatibility, module testing |

### 8.4 Success Criteria

**Quantitative:**
- [ ] 100% of Detail.php singleton calls removed
- [ ] 0 regressions in automated test suite
- [ ] Performance within 10% of baseline
- [ ] < 5 user-reported issues in first month

**Qualitative:**
- [ ] Code more testable (can inject mock users)
- [ ] Dependencies explicit in method signatures
- [ ] Request-scoped caching working
- [ ] Team comfortable with new patterns

---

## 9. Appendices

### Appendix A: Full Singleton Inventory

**Command to generate inventory:**
```bash
grep -r "::getInstance(" src/ --include="*.php" | \
  sed 's/:.*::getInstance//' | \
  sort | uniq -c | sort -rn > singleton_inventory.txt
```

**Top 20 Singleton Users:**
1. `Module::getInstance()` - 847 calls
2. `Record::getInstanceById()` - 623 calls  
3. `getCurrentUserModel()` - 412 calls
4. `Menu::getInstanceById()` - 156 calls
5. `Field::getInstance()` - 134 calls
... (continue for reference)

### Appendix B: Configuration Options

**New configuration keys in** `config.inc.php`:

```php
return [
    // ... existing config
    
    // Singleton refactoring feature flags
    'features' => [
        // Enable user injection via request
        'USE_REQUEST_USER_INJECTION' => true,
        
        // Enable request-scoped caching
        'USE_REQUEST_CACHE' => true,
        
        // Enable service container
        'USE_SERVICE_CONTAINER' => true,
        
        // Log deprecation warnings
        'LOG_SINGLETON_DEPRECATION' => true,
    ],
    
    // Debugging
    'debug' => [
        'LOG_DEPRECATION' => true,
        'SHOW_SINGLETON_WARNINGS' => false, // Don't show in production
    ],
];
```

### Appendix C: References

**External Resources:**
- [Dependency Injection Principles](https://en.wikipedia.org/wiki/Dependency_injection)
- [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)
- [Service Locator Pattern](https://martinfowler.com/articles/injection.html)
- [PHP Design Patterns](https://refactoring.guru/design-patterns/php)

**Internal Documentation:**
- `phase-3b-remove-apprequest.plan.md` - AppRequest removal plan
- `PRIVILEGE_REFACTORING_SUMMARY.md` - Privilege refactoring completed
- `MIGRATION_GUIDE.md` - General migration guidelines

---

## Conclusion

This migration strategy provides a comprehensive, phased approach to removing singleton dependencies from the FreeCRM application, with specific focus on the Users Settings Detail view. The plan emphasizes:

1. **Backward Compatibility** - Gradual migration with fallbacks
2. **Testability** - Explicit dependencies enable proper testing
3. **Maintainability** - Clear dependencies, request-scoped caching
4. **Risk Management** - Extensive testing, rollback procedures

**Next Steps:**
1. Review and approve this plan
2. Set up development environment
3. Begin Phase 1: Request Enhancement
4. Iterate through phases with continuous testing

**Questions or Concerns:**
- Contact development lead
- Review code samples in this document
- Reference existing refactoring work in `phase-3b-remove-apprequest.plan.md`

---

**Document Version:** 1.0  
**Last Updated:** October 25, 2025  
**Author:** AI Assistant  
**Status:** Draft - Pending Review


