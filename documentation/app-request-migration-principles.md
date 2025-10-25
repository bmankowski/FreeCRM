# AppRequest Class Migration Principles

**Document Version:** 1.1  
**Date:** October 25, 2025  
**Status:** Analysis & Planning Phase - Requirements Confirmed

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Current Architecture Analysis](#current-architecture-analysis)
3. [Problems & Antipatterns](#problems--antipatterns)
4. [Migration Strategy](#migration-strategy)
5. [Implementation Plan](#implementation-plan)
6. [Testing Strategy](#testing-strategy)
7. [Risks & Mitigations](#risks--mitigations)
8. [Questions for Review](#questions-for-review)

---

## Executive Summary

The `AppRequest` class (`src/Http/AppRequest.php`) is a **static wrapper/facade** around `Vtiger_Request` that provides global access to request data throughout the application. This document analyzes the current architecture and proposes a migration strategy to remove this antipattern.

### Key Findings

- **Usage Count:** 145+ method calls across 62+ files
- **Entry Points:** 5 main entry points (index.php, file.php, webservice.php, cron/vtigercron.php, shorturl.php)
- **Pattern:** Singleton pattern with lazy initialization
- **Problem:** Global state antipattern, violates dependency injection principles

### Recommended Approach

**Gradual Migration** - Replace static calls with dependency injection, starting from entry points and working through the call stack.

---

## Current Architecture Analysis

### 1. Class Structure

```php
namespace App\Http;

class AppRequest
{
    private static $request = null;  // Singleton instance
    
    // Methods:
    // - init()         → Creates/returns singleton Vtiger_Request
    // - get()          → Delegates to Vtiger_Request::get()
    // - has()          → Delegates to Vtiger_Request::has()
    // - set()          → Delegates to Vtiger_Request::set()
    // - isEmpty()      → Delegates to Vtiger_Request::isEmpty()
    // - getForSql()    → Delegates to Vtiger_Request::getForSql()
    // - isAjax()       → Checks $_SERVER directly (duplicated from Vtiger_Request)
}
```

### 2. Relationship Diagram

```
Entry Points                 Static Facade              Real Request Object
============                 =============              ===================

index.php         ------>   AppRequest::init()  ----->  Vtiger_Request
file.php                         ↓                           ↓
webservice.php                   └── static $request --------┘
cron/vtigercron.php
shorturl.php

Controllers/Models   ---->  AppRequest::get()
                            AppRequest::has()
                            AppRequest::set()
                            AppRequest::isEmpty()
                            AppRequest::getForSql()
                            AppRequest::isAjax()
```

### 3. Current Request Flow

#### Main WebUI Flow (index.php)
```
1. index.php
   └── AppRequest::init() → creates Vtiger_Request($_REQUEST)
       ├── WebUI::process($request) → receives Vtiger_Request instance
       │   ├── WebUI::initializeGlobals($request)
       │   │   └── $request->setUser($userModel)  [GOOD: instance-based]
       │   ├── WebUI::handleRequest($request)
       │   │   └── $handler->process($request)
       │   │       └── Vtiger_Controller::process($request)  [GOOD: instance-based]
       │   └── $response->emit()
       └── PROBLEM: Deep in call stack, code uses AppRequest::get() instead of $request
```

#### File Handler Flow (file.php)
```
file.php
└── AppRequest::init()
    └── File::process($request)  [receives instance]
        └── $handler->$method($request)  [passes instance]
```

#### Webservice Flow (webservice.php)
```
webservice.php
└── AppRequest::get('operation')  [uses static directly]
    └── AppRequest::get('format')
        └── AppRequest::has('PHPSESSID')
```

#### Cron Flow (cron/vtigercron.php)
```
cron/vtigercron.php
└── AppRequest::get('service')  [uses static directly, no request object created]
```

### 4. Usage Patterns Identified

Based on code analysis, here are the usage patterns:

#### Pattern A: Entry Point Usage (GOOD foundation)
```php
// index.php, file.php
$request = \App\Http\AppRequest::init();
$webUI->process($request);
```
✅ **Already creates instance** - can be easily migrated

#### Pattern B: Static Access in Legacy Code
```php
// Various controllers/models
$module = \App\Http\AppRequest::get('module');
if (\App\Http\AppRequest::has('record')) {
    $record = \App\Http\AppRequest::get('record');
}
```
❌ **Bypasses dependency injection** - needs migration

#### Pattern C: Webservice Direct Static Usage
```php
// webservice.php
$operation = AppRequest::get('operation');
$format = AppRequest::get('format', 'json');
```
❌ **No request object created** - needs refactoring

#### Pattern D: Mixed Usage (INCONSISTENT)
```php
// Some methods receive $request but still use AppRequest::
public function process(\App\Http\Vtiger_Request $request) {
    $module = $request->getModule();  // ✅ Good
    $action = \App\Http\AppRequest::get('action');  // ❌ Bad
}
```
❌ **Inconsistent** - should use $request parameter

### 5. Files With Heavy Usage

Based on grep results, these files have the most usage:

| File | Usage Count | Pattern | Priority |
|------|-------------|---------|----------|
| `webservice.php` | 5 calls | Static only (C) | HIGH |
| `src/Modules/Users/Users.php` | 7 calls | Mixed (D) | HIGH |
| `src/Modules/Documents/Documents.php` | 8 calls | Mixed (D) | HIGH |
| `src/CustomView.php` | 8 calls | Mixed (D) | HIGH |
| `src/CRMEntity.php` | 4 calls | Mixed (D) | MEDIUM |
| `src/Modules/Vtiger/Models/*` | Multiple | Mixed (D) | MEDIUM |
| `cron/vtigercron.php` | 1 call | Static only (C) | MEDIUM |
| Various other models | 1-2 calls | Mixed (D) | LOW |

---

## Problems & Antipatterns

### 1. **Global State Antipattern**

```php
// Anywhere in the codebase, deeply nested:
$value = \App\Http\AppRequest::get('some_key');
```

**Problems:**
- Hidden dependency - not visible in method signature
- Makes testing difficult (can't mock/inject)
- Creates tight coupling to global state
- Violates Dependency Inversion Principle

### 2. **Singleton Pattern Issues**

```php
private static $request = null;
```

**Problems:**
- Single shared state across entire application lifecycle
- Difficult to reset between tests
- Can't have multiple request contexts
- Memory leaks in long-running processes

### 3. **Lazy Initialization Complexity**

```php
public static function get($key, $defvalue = '') {
    if (!self::$request) {
        static::init();  // Lazy initialization on every call
    }
    return self::$request->get($key, $defvalue);
}
```

**Problems:**
- Unnecessary checks on every call
- Creates $_REQUEST at unpredictable times
- Makes request lifecycle unclear

### 4. **Code Duplication**

```php
// AppRequest::isAjax() duplicates Vtiger_Request::isAjax()
public static function isAjax() {
    if (!empty($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] === true) {
        return true;
    }
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
}
```

### 5. **Bypasses Existing Proper Architecture**

The architecture **already has** proper request passing:
```php
// WebUI.php (GOOD)
public function process(\App\Http\Vtiger_Request $request) { ... }

// Controllers (GOOD)
abstract public function process(\App\Http\Vtiger_Request $request);
```

But code still uses `AppRequest::get()` inside these methods! 😞

---

## Migration Strategy

### Philosophy

**"Progressive Enhancement"** - Migrate from the outside-in, starting at entry points and working through the call stack.

### Goals

1. ✅ Eliminate global state
2. ✅ Use dependency injection throughout
3. ✅ Maintain backward compatibility during migration
4. ✅ Zero downtime
5. ✅ Improve testability

### Non-Goals

- ❌ Complete rewrite of request handling
- ❌ Changing Vtiger_Request class behavior
- ❌ Modifying request data structure

---

## Implementation Plan

### Phase 0: Preparation (Pre-Migration)

**Goal:** Set up infrastructure for safe migration

#### Step 0.1: Create Deprecation Notices
```php
// Add to AppRequest methods
trigger_error(
    'AppRequest::get() is deprecated. Use $request->get() instead.',
    E_USER_DEPRECATED
);
```

#### Step 0.2: Add Tracking/Logging
```php
// Track usage in development
if (\App\AppConfig::debug('TRACK_APPREQUEST_USAGE')) {
    \App\Log::trace('AppRequest::get() called from ' . debug_backtrace()[1]['file']);
}
```

#### Step 0.3: Create Migration Helper
```php
namespace App\Http;

/**
 * TEMPORARY helper during migration
 * Provides backward-compatible access while we migrate away from AppRequest
 */
class RequestContext
{
    private static $currentRequest = null;
    
    public static function setCurrent(Vtiger_Request $request): void {
        self::$currentRequest = $request;
    }
    
    public static function getCurrent(): ?Vtiger_Request {
        return self::$currentRequest;
    }
}
```

#### Step 0.4: Document All Usage
- Run: `grep -r "AppRequest::" --include="*.php" > apprequest-usage.txt`
- Categorize by pattern (A, B, C, D)
- Prioritize by impact

---

### UPDATED TIMELINE: 4-Week Migration Plan

Based on confirmed requirements:
- ✅ No third-party module dependencies
- ✅ No backward compatibility needed (can remove immediately)
- ✅ Webservice.php not heavily used
- ✅ No code freeze periods
- ⏱️ **Target: 4 weeks total**

---

### Phase 1: Entry Points (Week 1)

**Goal:** Ensure all entry points properly initialize and pass request objects

#### Step 1.1: Update Entry Points

**Current:**
```php
// index.php
$request = \App\Http\AppRequest::init();
$webUI->process($request);
```

**After:**
```php
// index.php
$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
\App\Http\RequestContext::setCurrent($request);  // Temporary bridge
$webUI->process($request);
```

**Files to Update:**
- ✅ `index.php`
- ✅ `file.php`
- ✅ `webservice.php` ⚠️ (needs special handling)
- ✅ `cron/vtigercron.php` ⚠️ (CLI context)
- ✅ `shorturl.php`

#### Step 1.2: Update WebUI Entry Point

**Current:**
```php
// src/Main/File.php
$webUI->process(\App\Http\AppRequest::init());
```

**After:**
```php
// src/Main/File.php  
$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
\App\Http\RequestContext::setCurrent($request);
$webUI->process($request);
```

#### Step 1.3: Update AppRequest to Use Context (Temporary Bridge)

```php
class AppRequest
{
    public static function init() {
        trigger_error('AppRequest::init() is deprecated', E_USER_DEPRECATED);
        
        $request = \App\Http\RequestContext::getCurrent();
        if (!$request) {
            // Fallback during migration
            $request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
            \App\Http\RequestContext::setCurrent($request);
        }
        return $request;
    }
    
    public static function get($key, $defvalue = '') {
        trigger_error('AppRequest::get() is deprecated', E_USER_DEPRECATED);
        
        $request = \App\Http\RequestContext::getCurrent();
        return $request ? $request->get($key, $defvalue) : $defvalue;
    }
    // ... similar for other methods
}
```

✅ **Benefit:** Existing code still works while we migrate!

---

### Phase 2: Controllers & Actions (Week 2)

**Goal:** Migrate all controllers to use injected $request parameter

**ACCELERATED:** Using sed scripts for bulk migration where safe

#### Step 2.1: Update Base Controller Pattern

Controllers already receive `$request` - just need to use it!

**Before:**
```php
class MyController extends Vtiger_Controller
{
    public function process(\App\Http\Vtiger_Request $request) {
        $module = \App\Http\AppRequest::get('module');  // ❌
        $record = \App\Http\AppRequest::get('record');  // ❌
    }
}
```

**After:**
```php
class MyController extends Vtiger_Controller
{
    public function process(\App\Http\Vtiger_Request $request) {
        $module = $request->get('module');  // ✅
        $record = $request->get('record');  // ✅
    }
}
```

#### Step 2.2: Migration Approach

**Option A: Manual (Recommended for critical files)**
- Review each file
- Replace `\App\Http\AppRequest::` with `$request->`
- Test thoroughly

**Option B: Automated (sed script for bulk)**
```bash
# Find all methods that receive $request parameter
find src/Modules -name "*.php" -type f -exec sed -i \
  's/\\App\\Http\\AppRequest::get(\([^)]*\))/$request->get(\1)/g' {} \;
```

⚠️ **Warning:** Only safe if method signature includes `$request` parameter!

#### Step 2.3: Priority Order

1. **HIGH Priority** (Week 2)
   - `src/Modules/Users/` (authentication critical)
   - `src/Modules/Vtiger/` (core functionality)
   - All `Actions/` classes

2. **MEDIUM Priority** (Week 3)
   - `src/Modules/*/Models/` classes
   - `src/Modules/*/Views/` classes

3. **LOW Priority** (Week 4)
   - Helper functions
   - Utility classes
   - Legacy modules

---

### Phase 3: Models & Business Logic (Week 3)

**Goal:** Remove request dependencies from models

**PENDING:** Awaiting decision on signature change approach (see Questions #2)

#### Challenge: Models Don't Currently Receive Request

Many models use `AppRequest::get()` but don't receive `$request` in constructor/methods.

**Current:**
```php
class Vtiger_Record_Model
{
    public function save() {
        $currentUser = \App\Http\AppRequest::get('current_user');  // ❌
    }
}
```

#### Solution A: Pass Request as Parameter

**After:**
```php
class Vtiger_Record_Model
{
    public function save(\App\Http\Vtiger_Request $request) {
        $currentUser = $request->get('current_user');  // ✅
    }
}
```

**Problem:** Breaks existing API - all callers must be updated!

#### Solution B: Use Request from Context (Temporary)

**After:**
```php
class Vtiger_Record_Model
{
    public function save() {
        $request = \App\Http\RequestContext::getCurrent();
        if ($request) {
            $currentUser = $request->get('current_user');  // ✅
        }
    }
}
```

**Benefit:** No API breaking changes, gradual migration

#### Solution C: Don't Pass Request to Models (Preferred!)

**Philosophy:** Models shouldn't access request directly!

**Before:**
```php
public function save() {
    $userId = \App\Http\AppRequest::get('user_id');  // ❌ Request in model
}
```

**After:**
```php
// In Controller
public function process(\App\Http\Vtiger_Request $request) {
    $userId = $request->get('user_id');
    $model->save($userId);  // ✅ Pass data explicitly
}

// In Model
public function save($userId) {
    // Use parameter, not request
}
```

✅ **Benefit:** Better separation of concerns!

---

### Phase 4: Special Cases & Cleanup (Week 4)

**COMBINED:** Since webservice.php is not heavily used, handle quickly

#### Case 1: Webservice.php (No WebUI Framework)

**Current:**
```php
$operation = AppRequest::get('operation');
$format = AppRequest::get('format', 'json');
```

**After:**
```php
$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
$operation = $request->get('operation');
$format = $request->get('format', 'json');
```

#### Case 2: Cron (CLI Context)

**Challenge:** No $_REQUEST in CLI

**Current:**
```php
$serviceName = \App\Http\AppRequest::has('service') 
    ? \App\Http\AppRequest::get('service') 
    : null;
```

**After:**
```php
// Parse CLI arguments into request-like structure
$cliParams = getopt('', ['service::']);
$requestData = [
    'service' => $cliParams['service'] ?? null,
];
$request = new \App\Http\Vtiger_Request($requestData, $requestData);
$serviceName = $request->get('service');
```

#### Case 3: LinkData.php (vtlib Legacy)

**Current:**
```php
public function getInputParameter($name) {
    return \App\Http\AppRequest::get($name);
}
```

**After - Option 1:** Pass request to constructor
```php
protected $request;

public function __construct(\App\Http\Vtiger_Request $request) {
    $this->request = $request;
}

public function getInputParameter($name) {
    return $this->request->get($name);
}
```

**After - Option 2:** Use RequestContext temporarily
```php
public function getInputParameter($name) {
    $request = \App\Http\RequestContext::getCurrent();
    return $request ? $request->get($name) : '';
}
```

---

### Phase 5: Final Validation (Integrated Throughout)

**NOTE:** Testing handled separately, focus on migration correctness

#### Step 5.1: Automated Tests

**Unit Tests:**
```php
public function testControllerUsesInjectedRequest() {
    $request = new Vtiger_Request(['module' => 'Leads']);
    $controller = new MyController();
    $controller->process($request);
    // Assert no AppRequest calls were made
}
```

**Integration Tests:**
```bash
# Use existing curl tests
curl -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=Leads&view=List"
```

#### Step 5.2: Deprecation Warning Check

```bash
# Enable deprecation warnings
# Check logs for AppRequest usage
grep "AppRequest.*deprecated" cache/logs/system.log
```

#### Step 5.3: Static Analysis

```bash
# Check for remaining AppRequest usage
phpstan analyse src/ --level 5 | grep "AppRequest"
```

---

### Phase 6: Complete Removal (End of Week 4)

**ACCELERATED:** No backward compatibility needed

#### Step 6.1: Remove RequestContext Bridge

```php
// Delete src/Http/RequestContext.php immediately after migration
```

#### Step 6.2: Remove AppRequest Class

```php
// Delete src/Http/AppRequest.php immediately
// No deprecation period needed
```

#### Step 6.3: Update Composer Autoloader

```bash
composer dump-autoload
```

#### Step 6.4: Final Verification

```bash
# Ensure no references remain
grep -r "AppRequest" src/ modules/ vtlib/
# Should return: 0 results
```

---

## Testing Strategy

### Test Levels

1. **Unit Tests**
   - Test individual methods with mocked requests
   - Verify request data flows correctly

2. **Integration Tests**
   - Use existing curl test suite
   - Verify web interface still works

3. **Regression Tests**
   - Test all major user workflows
   - Login, CRUD operations, reports, etc.

### Testing Checklist

- [ ] Login/authentication works
- [ ] List views load correctly
- [ ] Detail views display records
- [ ] Create new records
- [ ] Edit existing records
- [ ] Delete records
- [ ] Settings pages accessible
- [ ] Reports generate correctly
- [ ] File uploads work
- [ ] Webservices respond
- [ ] Cron jobs execute
- [ ] AJAX requests succeed

### Rollback Plan

If issues discovered:
1. Keep AppRequest class available
2. Keep RequestContext bridge
3. Revert individual files as needed
4. Git revert to last known good state

---

## Risks & Mitigations

### Risk 1: Breaking Existing Functionality

**Probability:** MEDIUM  
**Impact:** HIGH

**Mitigation:**
- ✅ Gradual migration (phases)
- ✅ Keep AppRequest during transition
- ✅ Extensive testing at each phase
- ✅ Feature flags for new behavior

### Risk 2: Third-Party Module Compatibility

**Probability:** HIGH  
**Impact:** MEDIUM

**Mitigation:**
- ✅ Keep RequestContext bridge longer for plugins
- ✅ Document migration guide for module developers
- ✅ Provide compatibility shim

### Risk 3: Performance Regression

**Probability:** LOW  
**Impact:** LOW

**Mitigation:**
- ✅ Benchmark before/after
- ✅ No additional overhead (removing static calls is faster!)

### Risk 4: Incomplete Migration

**Probability:** MEDIUM  
**Impact:** MEDIUM

**Mitigation:**
- ✅ Automated detection (grep, PHPStan)
- ✅ Deprecation warnings
- ✅ Code review checklist

---

## Questions for Review

Before proceeding with migration, please answer:

### ✅ 1. **Third-Party Modules**
   - **A:** NO third-party dependencies
   - **Impact:** Can proceed with full migration

### ⏳ 2. **Model Method Signatures** [NEEDS CLARIFICATION]
   - **Q:** When models use AppRequest, should we:
     - A) Add $request parameter to method signatures?
     - B) Pass data explicitly (remove request from models)?
     - C) Use RequestContext bridge (temporary)?
   - **Impact:** Affects Phase 3 migration strategy
   - **STATUS:** **PENDING - Please clarify your preference (A, B, or C)**

### ✅ 3. **Timeline Constraints**
   - **A:** 4 weeks total
   - **Impact:** Accelerated schedule, use automated scripts where possible

### ✅ 4. **Testing Resources**
   - **A:** Handled separately
   - **Impact:** Focus on migration correctness, not test coverage

### ✅ 5. **Backward Compatibility**
   - **A:** No need for compatibility layer
   - **Impact:** Can remove AppRequest immediately after migration

### ✅ 6. **Webservice Architecture**
   - **A:** Not heavily used
   - **Impact:** Simple refactoring sufficient

### ✅ 7. **Performance Requirements**
   - **A:** No specific requirements
   - **Impact:** Prioritize clean code over micro-optimizations

### ✅ 8. **Code Freeze Periods**
   - **A:** None
   - **Impact:** Can proceed continuously

---

## Appendix A: Code Examples

### Example 1: Controller Migration

**Before:**
```php
namespace App\Modules\Leads\Actions;

class SaveAjax extends \App\Runtime\Vtiger_Action_Controller
{
    public function process(\App\Http\Vtiger_Request $request) {
        $recordId = \App\Http\AppRequest::get('record');
        $module = \App\Http\AppRequest::get('module');
        
        if (\App\Http\AppRequest::has('field_name')) {
            $fieldName = \App\Http\AppRequest::get('field_name');
        }
    }
}
```

**After:**
```php
namespace App\Modules\Leads\Actions;

class SaveAjax extends \App\Runtime\Vtiger_Action_Controller
{
    public function process(\App\Http\Vtiger_Request $request) {
        $recordId = $request->get('record');
        $module = $request->get('module');
        
        if ($request->has('field_name')) {
            $fieldName = $request->get('field_name');
        }
    }
}
```

### Example 2: Model with Request Context

**Before:**
```php
class Vtiger_Record_Model
{
    public function isEditable() {
        $recordId = \App\Http\AppRequest::get('record');
        // ... permission check
    }
}
```

**After (Preferred - no request in model):**
```php
class Vtiger_Record_Model
{
    public function isEditable($recordId) {
        // ... permission check using parameter
    }
}

// In Controller
$recordId = $request->get('record');
$isEditable = $model->isEditable($recordId);
```

**After (Acceptable - using context during migration):**
```php
class Vtiger_Record_Model
{
    public function isEditable() {
        $request = \App\Http\RequestContext::getCurrent();
        $recordId = $request ? $request->get('record') : null;
        // ... permission check
    }
}
```

---

## Appendix B: Automated Migration Scripts

### Script 1: Find All AppRequest Usage

```bash
#!/bin/bash
# find-apprequest-usage.sh

echo "=== AppRequest Usage Report ==="
echo "Generated: $(date)"
echo ""

echo "Files using AppRequest:"
grep -r "AppRequest::" --include="*.php" src/ modules/ vtlib/ | \
  cut -d: -f1 | sort | uniq -c | sort -rn

echo ""
echo "Method usage breakdown:"
grep -roh "AppRequest::\w\+" --include="*.php" src/ modules/ vtlib/ | \
  sort | uniq -c | sort -rn
```

### Script 2: Migrate Controller File

```bash
#!/bin/bash
# migrate-controller.sh <filename>

FILE=$1

if [[ ! -f "$FILE" ]]; then
    echo "File not found: $FILE"
    exit 1
fi

# Backup
cp "$FILE" "$FILE.bak"

# Replace AppRequest::get with $request->get
sed -i 's/\\App\\Http\\AppRequest::get(/\$request->get(/g' "$FILE"
sed -i 's/\\App\\Http\\AppRequest::has(/\$request->has(/g' "$FILE"
sed -i 's/\\App\\Http\\AppRequest::set(/\$request->set(/g' "$FILE"
sed -i 's/\\App\\Http\\AppRequest::isEmpty(/\$request->isEmpty(/g' "$FILE"
sed -i 's/\\App\\Http\\AppRequest::getForSql(/\$request->getForSql(/g' "$FILE"
sed -i 's/\\App\\Http\\AppRequest::isAjax(/\$request->isAjax(/g' "$FILE"

echo "Migrated: $FILE"
echo "Backup: $FILE.bak"
echo "Please review changes and test!"
```

---

## Appendix C: Migration Checklist

### Pre-Migration
- [ ] Document all AppRequest usage
- [ ] Identify third-party dependencies
- [ ] Set up staging environment
- [ ] Create rollback plan
- [ ] Get stakeholder approval

### Phase 1: Entry Points
- [ ] Update index.php
- [ ] Update file.php
- [ ] Update webservice.php
- [ ] Update cron/vtigercron.php
- [ ] Update shorturl.php
- [ ] Create RequestContext bridge
- [ ] Add deprecation warnings
- [ ] Test all entry points

### Phase 2: Controllers
- [ ] Migrate all Action classes
- [ ] Migrate all View classes
- [ ] Update base controller if needed
- [ ] Test each controller after migration
- [ ] Check for regressions

### Phase 3: Models
- [ ] Identify models using AppRequest
- [ ] Refactor to remove request dependency
- [ ] Pass data explicitly from controllers
- [ ] Update tests

### Phase 4: Special Cases
- [ ] Handle webservice.php
- [ ] Handle cron system
- [ ] Handle vtlib LinkData
- [ ] Handle legacy modules
- [ ] Update any custom code

### Phase 5: Testing
- [ ] Run unit tests
- [ ] Run integration tests
- [ ] Manual testing of all features
- [ ] Performance benchmarking
- [ ] Security review

### Phase 6: Cleanup
- [ ] Remove RequestContext
- [ ] Remove AppRequest class
- [ ] Update documentation
- [ ] Final testing
- [ ] Deploy to production

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-10-25 | System Analyst | Initial analysis and migration plan |
| 1.1 | 2025-10-25 | System Analyst | Updated based on stakeholder answers: 4-week timeline, no compatibility needed |

---

**Next Steps:** 
1. ⏳ **PENDING:** Clarify Question #2 about model method signature approach (A, B, or C)
2. ✅ Once answered, proceed with Phase 1 implementation
3. ✅ Target completion: 4 weeks from start date

