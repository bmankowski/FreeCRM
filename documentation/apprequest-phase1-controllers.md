# AppRequest Migration - Phase 1: Controllers

**Goal:** Migrate all controllers (Actions/Views) to use `$request` parameter instead of `AppRequest::*` static calls.

## Status

- ✅ **Controllers using AppRequest:** Only 2 files!
- ✅ **Estimated time:** 15 minutes
- ✅ **Risk level:** Very Low (simple find/replace)

## Files to Migrate

### 1. src/Modules/Products/Actions/SaveAjax.php

**Current:**
```php
public function process(\App\Http\Vtiger_Request $request)
{
    \App\Http\AppRequest::set('ajxaction', 'DETAILVIEW');
    parent::process($request);
}
```

**After:**
```php
public function process(\App\Http\Vtiger_Request $request)
{
    $request->set('ajxaction', 'DETAILVIEW');
    parent::process($request);
}
```

**Change:** Replace `\App\Http\AppRequest::set` → `$request->set`

---

### 2. src/Modules/Users/Actions/ForgotPassword.php

**Current (line 107):**
```php
\App\Modules\Users\Actions\ForgotPassword::run(\App\Http\AppRequest::init());
```

**After:**
```php
$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
\App\Modules\Users\Actions\ForgotPassword::run($request);
```

**Change:** Replace `\App\Http\AppRequest::init()` with direct instantiation

---

## Migration Commands

### Automated Approach (Recommended)

```bash
# 1. Fix SaveAjax.php
sed -i 's/\\App\\Http\\AppRequest::set/$request->set/g' \
  src/Modules/Products/Actions/SaveAjax.php

# 2. Fix ForgotPassword.php
sed -i 's/\\App\\Http\\AppRequest::init()/$request = new \\App\\Http\\Vtiger_Request($_REQUEST, $_REQUEST); $request/g' \
  src/Modules/Users/Actions/ForgotPassword.php
```

### Manual Approach

Just edit the 2 files and make the changes shown above.

---

## Verification

After changes, verify no controllers use AppRequest:

```bash
grep -r "AppRequest::" src/Modules/*/Actions/*.php src/Modules/*/Views/*.php
# Should return: no results
```

---

## Testing

Test the affected functionality:

```bash
# Test product save
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=Products&action=SaveAjax&record=123"

# Test forgot password (if enabled)
curl -s -L "http://localhost/index.php?module=Users&view=Login&fpError=0"
```

---

## Next Steps After Phase 1

Once controllers are clean:
1. ✅ Controllers now use dependency injection properly
2. ⏭️ Move to Phase 2: Update Models
3. ⏭️ Move to Phase 3: Update legacy files
4. ⏭️ Remove AppRequest class

---

## Notes

- Controllers already receive `$request` - this is just cleanup!
- No breaking changes to APIs
- Very safe migration
- Sets good example for rest of codebase

