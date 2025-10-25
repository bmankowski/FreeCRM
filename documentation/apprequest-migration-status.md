# AppRequest Migration Status

**Last Updated:** October 25, 2025

## ✅ Phase 1: Controllers - COMPLETE

**Status:** All controllers migrated  
**Files Changed:** 2  
**Controllers Clean:** 162/162 (100%)

### Changes Made

1. ✅ `src/Modules/Products/Actions/SaveAjax.php` - Changed `AppRequest::set()` to `$request->set()`
2. ✅ `src/Modules/Users/Actions/ForgotPassword.php` - Changed `AppRequest::init()` to direct instantiation

---

## 📊 Remaining AppRequest Usage

### Category Breakdown

| Category | Count | Priority | Notes |
|----------|-------|----------|-------|
| **Entry Points** | 4 files | HIGH | Core bootstrap files |
| **Models** | 16 files | MEDIUM | Business logic layer |
| **Legacy Modules** | 11 files | MEDIUM | Old-style module files |
| **Other** | ~40 files | LOW | Helpers, utils, etc. |

---

## 🎯 Next Steps (Recommended Order)

### Step 2: Entry Points (Easy - 15 min)

Migrate the 4 entry point files:
- `index.php` ✅ (already uses it correctly, just remove facade call)
- `file.php`
- `shorturl.php`
- `webservice.php`

**Impact:** High - sets foundation for all requests  
**Risk:** Low - simple changes  
**Effort:** 15 minutes

---

### Step 3: Legacy Module Files (Medium - 1-2 hours)

Files in `src/Modules/*/` (not Models/Actions/Views):
- `Cron/Bootstrap.php`
- `Install/Install.php`
- `Workflow/WorkflowComponents.php`
- `Calendar/Activity.php`
- `Calendar/CalendarCommon.php`
- `Documents/Documents.php`
- `ModComments/ModCommentsCore.php`
- `Project/Project.php`
- `Reports/Reports.php`
- `SMSNotifier/SMSNotifierBase.php`
- `Users/Users.php`

**Impact:** Medium - used in specific workflows  
**Risk:** Medium - need to trace request flow  
**Effort:** 1-2 hours

---

### Step 4: Models (Medium - 2-3 hours)

16 model files use AppRequest (need to decide on approach):
- `Modules/Announcements/Models/Module.php`
- `Modules/HelpDesk/Models/Record.php`
- `Modules/OSSMailView/Models/Record.php`
- `Modules/Products/Models/Record.php` (11 usages!)
- `Modules/Vtiger/Models/Field.php`
- `Modules/Vtiger/Models/Menu.php`
- `Modules/Vtiger/Models/Module.php`
- `Modules/Vtiger/Models/Record.php`
- `Modules/Vtiger/Models/Relation.php`
- And 7 more...

**Impact:** High - core business logic  
**Risk:** Medium-High - need decision on architecture (pass $request vs. pass data)  
**Effort:** 2-3 hours

---

### Step 5: Other Files (Low priority)

Remaining files in `src/` and `vtlib/`:
- Various helpers
- Utilities
- Legacy code

**Impact:** Low - edge cases  
**Risk:** Low  
**Effort:** 1-2 hours

---

## 📈 Migration Progress

```
Total Files with AppRequest: ~71
Migrated: 2 (Controllers)
Remaining: ~69

Progress: ████░░░░░░░░░░░░░░░░ 3%
```

---

## 🤔 Decision Needed: Model Strategy

Before proceeding with models, we need to decide:

**Option A:** Pass $request to model methods  
- Pro: Quick migration
- Con: Couples models to HTTP layer

**Option B:** Extract data in controllers, pass to models  
- Pro: Clean architecture
- Con: More work, signature changes

**Option C:** Use RequestContext bridge temporarily  
- Pro: No breaking changes
- Con: Still some coupling

**Recommendation:** Start with Option B for new/simple models, use Option C for complex ones.

---

## Next Action Items

1. [ ] Decide on model migration strategy (A, B, or C)
2. [ ] Migrate entry points (Step 2)
3. [ ] Migrate legacy module files (Step 3)
4. [ ] Migrate models (Step 4)
5. [ ] Test all changes
6. [ ] Remove AppRequest class
7. [ ] Update documentation

---

## Testing Strategy

After each migration step:

```bash
# Test login
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=Users&action=Login"

# Test product operations
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Products&view=List"

# Test various modules
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  "http://localhost/index.php?module=Leads&view=List&mid=48&parent=47"
```

