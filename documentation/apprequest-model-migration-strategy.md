# AppRequest Model Migration Strategy - Analysis & Recommendation

**Date:** October 25, 2025  
**Status:** Strategic Decision Document

## Executive Summary

After analyzing 16 model files using AppRequest, I've identified 5 distinct usage patterns. This document provides a **recommended migration strategy for each pattern** with concrete examples.

---

## 📊 Usage Pattern Analysis

### Pattern 1: View/UI Context Checking (3 files)

**What it does:** Models check which view/mode is active to alter behavior

**Examples:**
```php
// Announcements/Models/Module.php
if (\App\Http\AppRequest::get('view') == 'Login') {
    return false;
}

// OSSMailView/Models/Record.php
if ($key === 'content' && \App\Http\AppRequest::get('view') == 'Detail') {
    return \vtlib\Functions::removeHtmlTags(...);
}

// Vtiger/Models/Field.php
if (in_array(\App\Http\AppRequest::get('module'), ['CustomView', 'Workflows'])) {
    // Different behavior
}
```

**Problem:** Models shouldn't know about UI concerns (views/modes)

**Recommendation:** ✅ **Option B - Pass data explicitly**

**Why:** This is a code smell - models should be view-agnostic. Extract the decision in the controller.

---

### Pattern 2: Form Field Access During Save (7 files - MOST COMMON)

**What it does:** Models read form fields directly during `saveToDb()` or related methods

**Examples:**
```php
// Products/Models/Record.php - 11 usages!
public function insertPriceInformation() {
    $requestPrice = \App\Http\AppRequest::get('unit_price');
    $actualPrice = \App\Http\AppRequest::get($curValue);
    $baseCurrency = \App\Http\AppRequest::get('base_currency');
    // ... uses these values
}

// Events/Models/Record.php
$timeStart = \App\Http\AppRequest::get('time_start');
$timeEnd = \App\Http\AppRequest::get('time_end');
$dateStart = \App\Http\AppRequest::get('date_start');

// Contacts/Models/Record.php
$module = \App\Http\AppRequest::get('module');
$hiddenFile = \App\Http\AppRequest::get($fileindex . '_hidden');
```

**Problem:** Tight coupling to HTTP layer, hard to test, can't reuse for API/batch operations

**Recommendation:** ⚠️ **Option C - RequestContext Bridge (Temporary)**

**Why:** 
- These methods are called deep in save chains (parent::saveToDb() → child::saveToDb())
- Changing signatures would break inheritance chains
- Many dynamic field names (e.g., 'curname' . $curid)
- Too risky to refactor in Phase 1
- **Long-term:** Should be refactored to use data from `$this->data` instead of reading request

---

### Pattern 3: Action/Mode Detection (2 files)

**What it does:** Models check what action/mode is being performed to alter behavior

**Examples:**
```php
// Products/Models/Record.php
if (\App\Http\AppRequest::get('ajxaction') != 'DETAILVIEW' && 
    \App\Http\AppRequest::get('action') != 'MassSave') {
    $this->insertPriceInformation();
}

// Users/Models/Privileges.php
if (\App\Http\AppRequest::get('action') == 'SaveAjax' && 
    \App\Http\AppRequest::get('field') != 'shownerid') {
    // Special handling
}
```

**Problem:** Models shouldn't know about controller actions

**Recommendation:** ✅ **Option B - Pass boolean flag explicitly**

**Why:** Controller should decide and pass a flag like `$includePrice = true`

---

### Pattern 4: Relation/Return Parameters (3 files)

**What it does:** Models read return_module/return_id/createmode to handle relationships

**Examples:**
```php
// HelpDesk/Models/Record.php
public function saveToDb() {
    parent::saveToDb();
    $forModule = \App\Http\AppRequest::get('return_module');
    $forCrmid = \App\Http\AppRequest::get('return_id');
    if (\App\Http\AppRequest::get('return_action') && $forModule && $forCrmid) {
        \App\CRMEntity::getInstance($forModule)->save_related_module(...);
    }
}

// Vtiger/Models/Module.php
if (\App\Http\AppRequest::get('createmode') === 'link') {
    $forModule = \App\Http\AppRequest::get('return_module');
    $forCrmid = \App\Http\AppRequest::get('return_id');
    \App\Utils\Utils::relateEntities(...);
}
```

**Problem:** Mixing business logic with HTTP parameters

**Recommendation:** ✅ **Option B - Pass parameters explicitly**

**Why:** These are legitimate business parameters, just need to be passed in method signature

---

### Pattern 5: Request Initialization (2 files)

**What it does:** Uses `AppRequest::init()` to get request object

**Examples:**
```php
// Vtiger/Models/Record.php
if (isset($this->inventoryRawData)) {
    $request = $this->inventoryRawData;
} else {
    $request = \App\Http\AppRequest::init();  // Fallback
}

// Vtiger/Models/Menu.php
$request = \App\Http\AppRequest::init();
$module = $request->get('module');
```

**Problem:** Models depend on global singleton

**Recommendation:** ✅ **Option B - Pass request to constructor/method**

**Why:** Clean dependency injection, easy to fix

---

## 🎯 Recommended Migration Strategy

### PHASE 3A: Easy Wins (15 minutes)

**Files:** 2 files using Pattern 5 (Request Initialization)

1. ✅ **Vtiger/Models/Menu.php** - Pass $request to `getBreadcrumbs()`
2. ✅ **Vtiger/Models/Record.php** - Already has fallback for `inventoryRawData`, enhance it

**Approach:** Add $request parameter to methods

---

### PHASE 3B: Medium Effort (1-2 hours)

**Files:** 6 files using Patterns 1, 3, 4 (View/Action Detection, Relations)

1. ✅ **Announcements/Models/Module.php** - Pass view context as parameter
2. ✅ **OSSMailView/Models/Record.php** - Pass $isDetailView flag
3. ✅ **Vtiger/Models/Field.php** - Pass module/mode as parameters
4. ✅ **Users/Models/Privileges.php** - Pass action/field as parameters
5. ✅ **HelpDesk/Models/Record.php** - Pass relation params to saveToDb()
6. ✅ **ServiceContracts/Models/Record.php** - Pass relation params to saveToDb()
7. ✅ **Vtiger/Models/Module.php** - Pass createmode/return params to saveRecord()

**Approach:** Refactor to pass data explicitly from controllers

---

### PHASE 3C: Complex - Use Bridge (Defer to later)

**Files:** 7 files using Pattern 2 (Form Field Access in Save Methods)

1. ⚠️ **Products/Models/Record.php** - 11 usages in deeply nested save logic
2. ⚠️ **Events/Models/Record.php** - Time/date handling in save
3. ⚠️ **Calendar/Models/Record.php** - Time field manipulation
4. ⚠️ **Contacts/Models/Record.php** - File upload in save
5. ⚠️ **Documents/Models/Record.php** - File upload in save
6. ⚠️ **Vtiger/Models/Record.php** - Multiple usages in save/upload
7. ⚠️ **Calendar/Models/Field.php** - Search param handling

**Approach:** Use RequestContext bridge temporarily, refactor later

**Long-term fix:** Models should get data from their own properties (`$this->data`), not from request

---

## 🔧 Implementation Details

### Example 1: Easy Win - Menu.php

**BEFORE:**
```php
public static function getBreadcrumbs($pageTitle = false)
{
    $breadcrumbs = [];
    $request = \App\Http\AppRequest::init();  // ❌
    $module = $request->get('module');
    // ...
}
```

**AFTER:**
```php
public static function getBreadcrumbs(\App\Http\Vtiger_Request $request, $pageTitle = false)
{
    $breadcrumbs = [];
    $module = $request->get('module');  // ✅
    // ...
}
```

**Caller Update:**
```php
// In controller
$breadcrumbs = Vtiger_Menu_Model::getBreadcrumbs($request);
```

---

### Example 2: Medium Effort - HelpDesk/Models/Record.php

**BEFORE:**
```php
public function saveToDb()
{
    parent::saveToDb();
    $forModule = \App\Http\AppRequest::get('return_module');  // ❌
    $forCrmid = \App\Http\AppRequest::get('return_id');  // ❌
    if (\App\Http\AppRequest::get('return_action') && $forModule && $forCrmid) {
        // ...
    }
}
```

**AFTER:**
```php
public function saveToDb($relationParams = null)
{
    parent::saveToDb();
    if ($relationParams && !empty($relationParams['return_action'])) {
        $forModule = $relationParams['return_module'];
        $forCrmid = $relationParams['return_id'];
        // ...
    }
}
```

**Caller Update:**
```php
// In controller
$relationParams = [
    'return_module' => $request->get('return_module'),
    'return_id' => $request->get('return_id'),
    'return_action' => $request->get('return_action'),
];
$recordModel->saveToDb($relationParams);
```

---

### Example 3: Complex - Use Bridge - Products/Models/Record.php

**BEFORE:**
```php
public function insertPriceInformation()
{
    $requestPrice = \App\Http\AppRequest::get('unit_price');  // ❌
    $actualPrice = \App\Http\AppRequest::get($curValue);  // ❌
    // ... 11 more AppRequest calls
}
```

**TEMPORARY BRIDGE (Phase 3C):**
```php
public function insertPriceInformation()
{
    $request = \App\Http\RequestContext::getCurrent();  // ⚠️ Temporary
    if (!$request) {
        // Handle case where no request (API, batch, etc.)
        return;
    }
    $requestPrice = $request->get('unit_price');
    $actualPrice = $request->get($curValue);
    // ...
}
```

**LONG-TERM FIX (Future refactoring):**
```php
// Controller extracts ALL price data and sets on model
$recordModel->setPriceData([
    'unit_price' => $request->get('unit_price'),
    'currencies' => $this->extractCurrencyData($request),
]);

// Model uses its own data
public function insertPriceInformation()
{
    $priceData = $this->getPriceData();
    $requestPrice = $priceData['unit_price'];
    // No request access at all!
}
```

---

## 📋 Migration Checklist

### Phase 3A: Easy Wins (Do Now)
- [ ] Vtiger/Models/Menu.php - Add $request parameter to getBreadcrumbs()
- [ ] Vtiger/Models/Record.php - Enhance inventory data handling
- [ ] Find all callers and update
- [ ] Test breadcrumbs and inventory operations

### Phase 3B: Medium Effort (Next Session)
- [ ] Announcements/Models/Module.php - Pass view context
- [ ] OSSMailView/Models/Record.php - Pass isDetailView flag
- [ ] Vtiger/Models/Field.php - Pass module/mode params
- [ ] Users/Models/Privileges.php - Pass action/field params
- [ ] HelpDesk/Models/Record.php - Pass relation params
- [ ] ServiceContracts/Models/Record.php - Pass relation params
- [ ] Vtiger/Models/Module.php - Pass createmode params
- [ ] Test all affected functionality

### Phase 3C: Complex (Defer)
- [ ] Create RequestContext bridge if needed
- [ ] Products/Models/Record.php - Use bridge temporarily
- [ ] Events/Models/Record.php - Use bridge temporarily
- [ ] Calendar/Models/Record.php - Use bridge temporarily
- [ ] Contacts/Models/Record.php - Use bridge temporarily
- [ ] Documents/Models/Record.php - Use bridge temporarily
- [ ] Vtiger/Models/Record.php - Use bridge temporarily
- [ ] Calendar/Models/Field.php - Use bridge temporarily
- [ ] Schedule future refactoring to remove bridge

---

## ⚖️ Final Recommendation

**Recommended Approach:**

1. **Start with Phase 3A** (Easy Wins) - Low risk, immediate value
2. **Then do Phase 3B** (Medium Effort) - Clean architecture improvements
3. **Defer Phase 3C** (Complex) - Use bridge, schedule proper refactor later

**DO NOT** attempt to refactor Products/Models/Record.php in this sprint - it's too complex and risky.

**Total Effort Estimate:**
- Phase 3A: 15-30 minutes
- Phase 3B: 1-2 hours
- Phase 3C: 5 minutes (just use bridge)
- **Total: 2-3 hours** to migrate all models (vs. weeks if we try to do it "perfectly")

---

## 🎭 Trade-offs

| Approach | Pros | Cons | When to Use |
|----------|------|------|-------------|
| **Option A: Pass $request** | Simple migration | Couples model to HTTP | Never (worst of both) |
| **Option B: Pass data** | Clean architecture | More work, API changes | Simple cases (Patterns 1,3,4,5) |
| **Option C: RequestContext** | No breaking changes | Still has coupling | Complex cases (Pattern 2) |

**Decision:** Use **B for simple**, **C for complex**

---

## 📝 Next Actions

**DECISION REQUIRED:** Approve this strategy?

If yes, I will proceed with:
1. ✅ Phase 3A migration (Easy Wins)
2. ✅ Phase 3B migration (Medium Effort)
3. ✅ Create RequestContext bridge for Phase 3C

**Estimated completion:** 2-3 hours of work

---

## 📚 Related Documents

- `documentation/app-request-migration-principles.md` - Overall migration plan
- `documentation/apprequest-migration-status.md` - Current status

