# CR Review — Field Data Structure Consolidation MVP

**Document under review:** `documentation/field-definition-mvp.md`  
**Reviewer:** Staff Engineer / Tech Lead  
**Date:** 2026-05-26

---

## Summary verdict

**NOT READY**

Three blocking issues must be resolved before implementation: the architecture diagram
contradicts the implementation plan, the actual API surface of `FieldService` is mis-stated,
and a key name mismatch in `fromRow()` will produce a silent data bug at runtime.

---

## Blocking issues

### ❌ [Architecture] `setDefinition()` is a phantom method

The architecture section shows:

```
App\Modules\Base\Models\Field  (still extends vtlib\Field for now;
    accepts FieldDefinition via setDefinition() for new code paths)
```

`setDefinition()` does not exist in `Base\Models\Field`, is not in the implementation plan,
and is not described anywhere in the document. The architecture diagram promises an integration
point that will never be built in this MVP. As written, `FieldDefinition` is a completely
isolated new class with no actual connection to `Base\Models\Field` — the diagram is wrong.

**Resolution:** Remove `setDefinition()` from the architecture diagram. Be explicit that
`FieldDefinition` is **not consumed by `Base\Models\Field`** in this MVP. The arrow from
`FieldDefinition` to `Base\Models\Field` in the "AFTER" diagram must be removed.

---

### ❌ [Implementation plan] Wrong method name; `FieldService` API mis-stated

Step 1.2 says:

> `FieldService::getField()` and `FieldService::getFields()` return `FieldDefinition`

The actual method is `FieldService::getInstance()`, not `getField()`. There is no `getField()`
or `getFields()` on `FieldService`. This tells us the implementation plan was written without
verifying it against the actual code.

Additionally, `FieldService::create()` accepts `Models\Field $field` as its parameter type.
If `Models\Field` is "retired," what does `create()` accept? This is not addressed anywhere.
`Block.php` (line 286) and `FieldService::getInstance()` (line 273) both instantiate `Models\Field`
with the full 27-argument constructor — these call sites are not listed in the "Files" column
of the implementation table.

**Resolution:**
1. Replace "getField()" with "getInstance()" throughout.
2. Add `FieldService::create()` parameter type to the retirement plan (or explicitly defer it).
3. Add `src/ModuleManagement/Adapters/Block.php` to the files table for task 1.2/1.3.

---

### ❌ [Data model] Key name mismatch in `fromRow()` will cause a silent data bug

`FieldDefinition::fromRow()` reads `$row['quicksequence']`. But in
`FieldService::getInstance()` (the only current hydration point), the DB row key is
`$data['quickcreatesequence']` (line 297 of `FieldService.php`). The column in `vtiger_field`
is also `quickcreatesequence`, not `quicksequence`.

If `fromRow()` is called with a raw DB row, `quicksequence` will always be missing and the
default `0` will be silently used. This is the same class of bug that caused the
`getFieldConstraints()` `'fieldid'` vs `'id'` failure documented in
`table_vtiger_fields_refactor.md` §4, bug #2.

**Resolution:** Change `fromRow()` to use `$row['quickcreatesequence'] ?? 0` (or add a
cross-reference test that verifies every key in `fromRow()` exists in a real DB row).

---

## Conditions (non-blocking but must be resolved during implementation)

### ⚠️ [Architecture] The "AFTER" diagram shows `FieldDefinition` hydrating `Base\Models\Field`

The diagram implies `FieldDefinition::fromRow()` is "the single entry point" for all consumers
including `Base\Models\Field`. But `Base\Models\Field` is explicitly unchanged for this MVP —
it still hydrates via `vtlib\FieldBasic` properties set through `vtlib\Field`. The diagram
overstates the MVP's scope and will mislead the implementor.

**Resolution:** Redraw the "AFTER" diagram to show `Base\Models\Field` unchanged,
with `FieldDefinition` used only by `FieldService` path.

---

### ⚠️ [Data model] Type change for `readonly` — `int` → `bool` — is not acknowledged

`vtlib\FieldBasic` stores `$readonly` as `int` (default `1`). `FieldDefinition` stores it
as `bool`. Code that consumes `FieldDefinition` and then passes `$def->readonly` back into a
context expecting an `int` (e.g. a DB INSERT) will pass `true` instead of `1`. In strict_types
contexts this will error; elsewhere it will silently coerce. The same applies to `mandatory`.
Both exist in the DB as `tinyint(1)` and are compared with `=== 1` in some places.

**Resolution:** Document the type change explicitly and enumerate whether downstream write
paths need casting.

---

### ⚠️ [Data model] `columntype` is `null` in the primary call site

`FieldService::getInstance()` passes `null` for `columntype` (line 280: "not in getFieldInfo").
`FieldDefinition` accepts `string $columntype` and defaults to `''` in `fromRow()`. These are
consistent — but `fromRow()` will silently swallow a missing `columntype` while the type
declaration promises a `string`. If `fromRow()` is ever called from a query that does not
SELECT `columntype`, the default `''` will be returned with no error, making the missing-key
situation invisible.

**Resolution:** Document which `vtiger_field` columns are required vs. optional in `fromRow()`.
Consider throwing on missing required keys rather than silently defaulting.

---

### ⚠️ [Impact analysis] `presence` default discrepancy between `FieldBasic` and `FieldDefinition`

`vtlib\FieldBasic` defaults `$presence` to `2`. `FieldDefinition::fromRow()` defaults
`$presence` to `1` (matching the DB column `DEFAULT 1`). Code that currently creates a
`FieldBasic` and relies on the `2` default will behave differently if migrated to
`FieldDefinition`. The CR does not acknowledge this discrepancy.

**Resolution:** Verify which value is correct (DB default `1` vs code default `2`) and
document the intended behavior.

---

### ⚠️ [Testing] No testing plan

There is no smoke test, regression list, or post-deletion verification. Minimum expected:

- Smoke: load a module EditView and DetailView for one standard module after Phase 2 deletions.
- Regression: confirm that modules whose Field subclasses were deleted do not trigger a
  `Class not found` fatal (check `include` paths and factory methods that instantiate by name).
- Verification: run a `grep -r 'Modules/Calendar/Models/Field'` (and equivalent for each deleted
  class) to confirm no remaining `use` or `new` references after Phase 2.

---

### ⚠️ [Rollback] No rollback plan

For Phase 1 (additive new file), rollback is trivially `git revert`. This should be stated.
For Phase 2 (deleting ~25 files), rollback is also `git revert`, but the document should
confirm there is no external persistent state (no DB rows, no cache keys, no config) that
records which Field subclass to instantiate at runtime.

**Resolution:** Add a one-liner rollback note per phase.

---

### ⚠️ [Edge cases] Module Field class instantiation by string — not audited

Several factory methods instantiate Field subclasses by constructing a class name from the module
name (e.g. `"App\\Modules\\{$module}\\Models\\Field"`). If that class no longer exists after
Phase 2 and the factory falls back gracefully to `Base\Models\Field`, deletion is safe. If it
throws `Class not found`, deletion breaks the module silently.

The CR says "The base `App\Modules\Base\Models\Field` is instantiated directly for those modules"
but does not verify that the dynamic instantiation code has a graceful fallback.

**Resolution:** Confirm (by reading the factory/loader code) that the fallback exists before
any file is deleted. Add this as a pre-condition gate for Phase 2.

---

## Strengths

- Scope is tightly bounded. Deferring `Base\Models\Field` composition and `WebserviceField` to
  later phases is the right call.
- "No DB migration required" is stated explicitly — good.
- The Phase 2 deletion algorithm (audit each subclass, keep any that has real logic) is clear.
- Using PHP 8.1 `readonly` properties is the right design choice for an immutable DTO.
- Identifying the prior `fieldid` vs `id` key-mapping bug class as a risk is good institutional
  memory — but then the CR replicates the same bug class with `quicksequence` vs
  `quickcreatesequence`.

---

## Recommended next action

Fix the three blocking issues in the document before touching any code:

1. Remove `setDefinition()` from the architecture diagram; redraw "AFTER" to show
   `FieldDefinition` used only by `FieldService`, not by `Base\Models\Field`.
2. Replace "getField()" with "getInstance()" and add `Block.php` + `FieldService::create()`
   parameter type to the retirement scope.
3. Change `fromRow()` key `quicksequence` → `quickcreatesequence`; add a cross-reference
   comment listing the exact DB column name next to each key.

---

## Assumptions made during review

- `FieldService` was read directly from `src/ModuleManagement/Services/FieldService.php` to
  verify method names and return types. The document did not cite the actual method signatures.
- `Block.php` line 286 was identified as the only external call site constructing
  `ModuleManagement\Models\Field` directly; other usages go through `FieldService::getInstance()`.
- Dynamic Field class instantiation by module name (e.g. `App\Modules\{$module}\Models\Field`)
  is assumed to occur somewhere in the factory layer but was not fully traced — this is the
  basis for the ⚠️ edge case note above.
- PHP version is ≥ 8.1 (required for `readonly` properties). This matches the existing
  `declare(strict_types=1)` in current files.

---

## Change log

| Date | Author | Change |
|------|--------|--------|
| 2026-05-26 | bmankowski | Initial CR review |
