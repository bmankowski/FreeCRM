# CR Review — Field Data Structure Consolidation (MVP)

Reviewing: `documentation/field-definition-cr.md` (revision dated 2026-05-26).

Role: Staff Engineer + Tech Lead + Code Reviewer. Verification depth: every cited
file/line/method was cross-checked against the current codebase.

---

## Summary verdict

**NOT READY.**

The DTO does not actually mirror `vtiger_field` as the CR claims — five columns
that are consumed today by `FieldService::create()`, `FieldService::update()`,
`FieldService::createVtlibFieldInstance()`, and `Block.php::addField()` are
missing from `FieldDefinition`. Without resolving this, Phase 1 cannot replace
`ModuleManagement\Models\Field` on the read path or the write path without data
loss.

---

## Section-by-section results

| § | Topic | Verdict |
|---|-------|---------|
| 1 | Goal & scope clarity | ✅ |
| 2 | Impact analysis | ❌ (incomplete call-site list) |
| 3 | Functional requirements | ⚠️ (read-boundary bool/int behaviour unstated) |
| 4 | Data migration | ✅ (correctly states "no DB changes") |
| 5 | Architecture impact | ❌ (DTO is missing 5 columns; cache untouched in plan but not analysed) |
| 6 | Implementation plan | ❌ (Block.php `addField` migration unspecified for `block` arg; external call sites not enumerated) |
| 7 | Testing plan | ⚠️ (round-trip test asserts properties but DTO won't have the columns to round-trip) |
| 8 | Rollback plan | ✅ |
| 9 | Edge cases | ⚠️ (read-side bool/int impact missing; cache invariant missing) |
| 10 | Risk assessment | ⚠️ (incomplete — see below) |

---

## Citation verification

Every concrete file/line claim in the CR was checked. Results:

| CR claim | Verdict | Notes |
|----------|---------|-------|
| `vtlib\FieldBasic` at `src/ModuleManagement/Adapters/FieldBasic.php` with `public $presence = 2` on line 82 | ✅ | Confirmed line 82 |
| `FieldBasic` has `#[\AllowDynamicProperties]` and 26 public props | ⚠️ minor | Confirmed; class also has dynamic-properties attribute (worth noting for the deprecation step — magic dynamic prop access keeps it implicitly hot) |
| `ModuleManagement\Models\Field` "27-arg constructor" at `FieldService.php:273` and `:466` | ⚠️ inaccurate | The constructor takes **28 positional parameters** (id, name, tabid, label, table, column, columntype, helpinfo, summaryfield, header_field, maxlengthtext, maxwidthcolumn, masseditable, uitype, typeofdata, displaytype, generatedtype, readonly, presence, defaultvalue, maximumlength, sequence, quickcreate, quicksequence, info_type, block, fieldparams, mandatory). Citation lines 273 and 466 are correct |
| `FieldService::create()` at line 46, takes `Models\Field $field` | ✅ | Confirmed |
| `FieldService::update()` at line 175, takes `Models\Field $field` | ✅ | Confirmed |
| `FieldService::getInstance()` at line 251, returns `?Models\Field` | ✅ | Confirmed |
| `FieldService::getInstance()` `$data['presence'] ?? 2` at line 292 | ✅ | Confirmed |
| `FieldService::getAllForModule()` at line 444 returns `Models\Field[]` | ✅ | Confirmed |
| `FieldService::createVtlibFieldInstance()` at line 563, reads `getQuicksequence()` and writes `$vtlibField->quicksequence` | ✅ | Confirmed (write at line 595) |
| `Block.php::addField()` at line 286 instantiates `\App\ModuleManagement\Models\Field` | ✅ | Confirmed; this is the only `new \App\ModuleManagement\Models\Field(...)` outside `FieldService` itself (grep confirms) |
| `Block.php` line 305 has `(int) $get($fieldInstance, 'presence', 2)` | ✅ | Confirmed |
| `Core/Loader::getComponentClassName()` graceful fallback at lines 158–164 | ✅ | Confirmed; capitalized-name fallback also exists at 167–175 (CR doesn't mention this but it strengthens the safety claim) |
| `Base\Models\Field` lines 792–793 use `Loader::getComponentClassName('Model', 'Field', $moduleName)` | ✅ | Confirmed; also note line 791: `$moduleName = $moduleName ?? 'Base';` — already routes deleted-module fields to `Base` |
| `scheme.sql:5218` is the `vtiger_field` CREATE TABLE, presence DEFAULT 1 on 5229 | ✅ | Confirmed |

**Summary:** all line-number citations are correct except the recurring "27-arg
constructor" — it is 28. Cosmetic but the CR repeats this number in three places,
so any reader sizing the refactor by argument count will be off by one.

---

## Blocking issues (❌)

### B1. [Data model] DTO is missing 5 columns from `vtiger_field`

The CR says the DTO "Mirrors `vtiger_field` columns exactly" (§Data model) and
that "`FieldDefinition` provides everything `ModuleManagement\Models\Field` did"
(§Data model). Both statements are false against the schema in
`scheme.sql:5218–5247` and the current `Models\Field`.

Columns present in `vtiger_field` and consumed by today's code but **absent from
the DTO**:

| Column | Schema (scheme.sql) | Consumed by | Impact if absent |
|--------|---------------------|-------------|------------------|
| `sequence` | line 5232, `smallint(5) unsigned NOT NULL` | `FieldService::create()` line 58 (`$field->getSequence()`), `FieldService::update()` line 193, `createVtlibFieldInstance()` line 593 | **Cannot create or update a field via the new DTO** — sequence is read before INSERT and written on UPDATE. Phase 1 step 1.3 is impossible without it. |
| `block` | line 5233, `int(19) DEFAULT NULL` | `FieldService::getInstance()` lines 260–271 (resolves a `Models\Block` via `BlockService`), `createVtlibFieldInstance()` line 600 | `getInstance()`→`FieldDefinition` would silently drop the resolved block, breaking `createVtlibFieldInstance()` and any downstream code that does `$field->getBlock()`. |
| `header_field` | line 5243, `varchar(15) DEFAULT NULL` | `Models\Field` ctor arg 10; `createVtlibFieldInstance()` line 580 | `vtlib\Field->header_field` would become `null` for all routes that go through the DTO. |
| `maxlengthtext` | line 5244, `smallint(3) unsigned DEFAULT '0'` | `Models\Field` ctor arg 11; `createVtlibFieldInstance()` line 581 | Same as above |
| `maxwidthcolumn` | line 5245, `smallint(3) unsigned DEFAULT '0'` | `Models\Field` ctor arg 12; `createVtlibFieldInstance()` line 582 | Same as above |

**Resolution required:** add all 5 properties to `FieldDefinition` (including
`fromRow()` mappings with documented defaults), or explicitly justify each
omission with a code-path argument showing it is never read after the migration.
The current CR has neither.

`block` deserves a specific design decision: today `FieldService::getInstance()`
returns a `Models\Field` carrying a hydrated `Models\Block` instance. If
`FieldDefinition` is to remain a pure DB-mirror, it should hold the raw
`?int $block` foreign key and block-instance resolution should move into a
separate service step. The CR must pick one and document it.

### B2. [Implementation plan] `Block.php::addField()` migration is under-specified for the `block` argument

Step 1.3 says "update the only external direct constructor call (`src/ModuleManagement/Adapters/Block.php` line 286) accordingly." But line 312 of that call site passes `$blockId` as the `block` argument of `Models\Field`. The CR's `FieldDefinition` has no `block` property at all (see B1), so it is not clear what `$blockId` becomes in the named-argument call.

Pick one of these and write it into the plan:

- `FieldDefinition` gains `?int $block` and `Block.php::addField()` passes `block: $blockId`.
- `FieldDefinition` does **not** carry block at all; `FieldService::create()` takes `$blockId` separately (it already does, line 46) and `Block.php` simply does not pass block info through the DTO.

Either is defensible; the choice must be in the CR before the implementer
starts.

### B3. [Impact analysis] External `FieldService` consumers not enumerated

The CR §Data model audit table lists 6 call sites (all inside `FieldService` and
`Block.php`). A workspace grep for `getFieldService()` returns consumers the CR
does not list:

```
src/Modules/Settings/LayoutEditor/Models/Field.php:29
src/ModuleManagement/Adapters/Field.php:56, 67, 79, 92, 147, 197, 253
src/ModuleManagement/Adapters/Block.php:199, 236
src/ModuleManagement/Services/PackageService.php:958, 2477
src/ModuleManagement/Services/ModuleService.php:504
src/ModuleManagement/Services/BlockService.php:221
```

Each of these will break when `getInstance()`/`getAllForModule()` switch return
type. Specifically, `Adapters/Block.php:236` does
`getFieldService()->getInstance($fieldId)` and the returned object's API
methods (`getName()`, `getLabel()` etc.) become invalid because
`FieldDefinition` has typed readonly **properties** (`$def->name`), not
getters.

This is a wider sweep than "Block.php::addField + FieldService internals." Phase
1 step 1.3 needs an explicit call-site migration list, or the implementer will
discover them one at a time during merge.

The same grep should be in §Testing as a regression gate.

---

## Conditions (⚠️ — must be resolved before or during implementation)

### C1. [Functional requirements] Read-boundary `bool` vs `int` impact unaddressed

§Edge cases addresses only the **write** boundary (`(int) $def->readonly` cast
before INSERT/UPDATE). The **read** side is not discussed:

- Today `Models\Field::getReadonly()` returns `int` (0 or 1). Code that does
  `if ($field->getReadonly() === 1)` returns true.
- After migration, `$def->readonly` returns `bool`. The same `=== 1` check
  silently becomes false.

`FieldService::update()` line 188 already passes the raw value:
`'readonly' => $field->getReadonly()`. Under the DTO this becomes
`'readonly' => $def->readonly`, i.e. PHP sends `true`/`false`. The CR catches
this (§Edge cases), but does not catch:

- Smarty/template code reading `$FIELD->readonly` via `Base\Models\Field`'s
  magic getter — `Base\Models\Field` is unchanged in this MVP, so this *should*
  not regress, but the CR should state the invariant: "this MVP does not change
  the type seen by template code, because `Base\Models\Field` is unchanged."
- Webservice JSON output via `WebserviceField` (out of scope) — confirm it does
  not go through `FieldService::getInstance()`. (Grep shows
  `Webservices/WebserviceField.php` does not depend on `FieldService`, so this
  is OK; document the conclusion.)

Add a §Edge cases row: "Read-boundary type change for `readonly`/`mandatory` —
contained to direct DTO consumers (`FieldService` internals + new code).
`Base\Models\Field` and `WebserviceField` still expose `int` because they are
unchanged in this MVP."

### C2. [Architecture] DTO default values diverge from current PHP defaults — only `presence` is documented

The CR documents the `presence` 1-vs-2 divergence well. But the same kind of
divergence applies to three more fields and is not flagged:

| Field | DB schema | Current PHP default (`Models\Field` / `FieldService::getInstance`) | CR DTO `fromRow` default | Acknowledged in CR? |
|-------|-----------|--------------------------------------------------------------------|--------------------------|---------------------|
| `presence` | DEFAULT 1 | 2 | 1 | ✅ |
| `readonly` | NOT NULL, no DEFAULT | 1 | `false` (0) | ❌ |
| `generatedtype` | DEFAULT 0 | 1 | 0 | ❌ |
| `quickcreatesequence` | DEFAULT NULL | `false` | 0 | ❌ |

`readonly` going from `1` to `false` is the most surprising — fields without an
explicit `readonly` flag in the data array would flip from "readonly=1" to
"readonly=0/false" after migration. Worth a §Risks row.

`quickcreatesequence`: the DB allows NULL, current PHP uses `false`, the DTO
proposes `0`. `FieldService::update()` line 197 does
`$field->getQuicksequence() ? (int) $field->getQuicksequence() : null` — i.e. it
deliberately writes NULL when the value is falsy. With the DTO returning `0`,
this becomes `0 ? (int) 0 : null` → null, which is the same outcome — OK by
coincidence. But callers that test for "is sequence set" via `if ($val !== null)`
or `if ($val !== false)` will behave differently. Either type the DTO field as
`?int` and preserve NULL, or call this out explicitly.

Add these to the existing "presence default discrepancy" subsection so the
reconciliation can happen in one follow-up rather than four.

### C3. [Architecture] `'ModuleFields'` cache not analysed

`Base\Models\Field` populates a `ModuleFields` cache at line 1041 of
`src/Modules/Base/Models/Field.php` (`Cache::save('ModuleFields', $moduleModel->id, $fieldModelList);`)
and reads it at line 1023. `Block.php::addField()` line 322, the layout-editor
controller, and four migrations all invalidate this cache.

Because Phase 1 leaves `Base\Models\Field` untouched, the cache contents do not
change. Phase 2 deletes empty module Field subclasses — the loader fallback then
returns `Base\Models\Field` for those modules, so a cache populated before Phase
2 might contain `App\Modules\X\Models\Field` instances and a cache populated
after contains `App\Modules\Base\Models\Field`. If those classes are
serialization-compatible (same shape), the cache is fine; if not, a stale cache
after deploy would deserialize into the old class name and fatal.

Required actions:

- Confirm `Cache::save('ModuleFields', ...)` is in-memory or short-TTL (file
  cache could survive a deploy). If it is persistent, add a cache-bust step
  between deploys.
- State the conclusion in §Edge cases.

### C4. [Implementation plan] Phase 2 grep regex is slightly under-specified

The pre-condition gate grep
`rg "new \\?App\\Modules\\\w+\\Models\\Field"` catches `new \App\Modules\X\Models\Field`
constructions. It misses:

- `\App\Modules\X\Models\Field::someStatic()` — static calls or constants.
- String references in config/DI: `'App\\Modules\\X\\Models\\Field'`,
  `"App\\Modules\\X\\Models\\Field"`, or in PHP attributes.
- `use App\Modules\X\Models\Field;` with subsequent `new Field(...)` or
  `Field::CONST`.

Recommend extending the regression-grep block in §Testing (Phase 2) to:

```
rg "App\\\\Modules\\\\{X}\\\\Models\\\\Field"
```

(no leading `new `) — the broader pattern catches every reference. The CR's
existing §Testing already lists three patterns, so this is a tightening rather
than a redesign.

### C5. [Architecture] Project namespace direction

`.cursor/rules/project-identity.mdc` states that `FreeCRM\` is the primary
PSR-4 namespace, but the existing code (and this CR) uses `App\*`. The new
file `src/Field/FieldDefinition.php` is the only opportunity in this MVP to
seed a class under the canonical namespace.

Either:
1. Keep `App\Field\FieldDefinition` (consistent with neighbours, inconsistent
   with the rule), or
2. Use `FreeCRM\Field\FieldDefinition` (consistent with the rule, inconsistent
   with neighbours).

The CR is silent. Pick one before implementation.

### C6. [Testing] Round-trip test must include both data shapes

§Testing Phase 1 says round-trip a `vtiger_field` row through `fromRow()`. Good.
But `FieldService::getInstance()` currently hydrates from
`\App\Fields\Field::getFieldInfo()` (line 254), not from `SELECT * FROM
vtiger_field`. The two producers may have different key sets (e.g.
`getFieldInfo()` may add `block`, omit `quickcreatesequence` aliasing, etc.).

The round-trip test should be run against **both** producers, or
`fromRow()`'s contract should be tightened to "accept any superset of these
keys; ignore unknown keys" and tested accordingly.

### C7. [Risks] Phase 2 subclass audit needs a concrete inventory

The CR estimates "~30 module Field subclasses" with "~25 to delete." Before
deletion, an inventory table (module name → contains custom logic? → action)
should be added to the CR or as a companion file. Otherwise "audit each" is a
TBD in the plan.

### C8. [Risks] Capitalized-name fallback at `Loader.php:167–175`

The CR's Phase 2 pre-condition gate references only the lowercase fallback
(`src/Core/Loader.php:158–164`). The Loader also has a capitalized-name
fallback at 167–175 which strengthens the safety argument. Worth a one-line
addition to the gate so future readers see the full picture.

---

## Strengths

- §Testing has actionable, codepath-aware tests (round-trip, smoke, regression
  grep). The decision to drive the round-trip test by `SELECT * FROM vtiger_field
  WHERE fieldid = <known>` is exactly right for catching the
  `quicksequence`/`quickcreatesequence` class of bug.
- §Edge cases for the write-side `(int)` cast on `readonly`/`mandatory` is
  correctly flagged as STRICT_TRANS_TABLES-sensitive — a real failure mode.
- Phase 2 pre-condition gate (the `Loader` fallback chain analysis) is the kind
  of due diligence that distinguishes a deletion-safe refactor from a
  hopeful one.
- The DTO scope is correctly minimised: `Base\Models\Field` and `WebserviceField`
  are deliberately untouched, and the AFTER diagram reflects this honestly.
- §Tradeoffs explains *why* each unintuitive decision was made (final class,
  readonly props, keeping `FieldBasic` alive).
- Use of column-name-as-property-name with cross-references to `scheme.sql:5218`
  is a deliberate stance with documented rationale.

---

## Risk table (extended)

| Risk | Severity | Mitigation in CR | Gap |
|------|----------|------------------|-----|
| Missing 5 DTO columns silently drops data on read+write paths | **High** | ❌ Not mentioned | Add columns or document each omission. **Blocking — see B1.** |
| `block` resolution lost when `getInstance()` returns DTO | **High** | ❌ Not mentioned | Decide architecture: DTO carries `?int $block`, or block resolved outside the DTO. **Blocking — see B1/B2.** |
| External `getFieldService()` consumers (8 files outside the audit list) break on return-type change | **High** | ⚠️ "IDE will surface them" | Enumerate them; gate merge on grep coverage. **Blocking — see B3.** |
| `readonly` default 1 → false flips behaviour for missing-key rows | Medium | ❌ Not mentioned | Document; reconcile with `presence` discussion. **See C2.** |
| `generatedtype` default 1 → 0 | Medium | ❌ Not mentioned | Same as above. **See C2.** |
| `quickcreatesequence` NULL/false/0 collapse | Low | ❌ Not mentioned | Type as `?int` to preserve NULL. **See C2.** |
| `'ModuleFields'` cache containing pre-Phase-2 class names survives deploy | Medium | ❌ Not mentioned | Add cache-bust step or verify cache is in-memory only. **See C3.** |
| Phase 2 grep misses static or string references | Low | ⚠️ Partial | Broaden the regex. **See C4.** |
| Read-side `bool` vs `int` consumers (template, JSON, comparison-by-strict-equality) | Low (contained in this MVP) | ⚠️ Implied | Document the containment invariant. **See C1.** |
| Capitalized-name fallback in Loader not cited in the gate | Trivial | — | One-line clarification. **See C8.** |
| Namespace direction `App\*` vs `FreeCRM\*` for new file | Trivial | ❌ Not mentioned | Decide once. **See C5.** |
| `bool` → `tinyint(1)` write under STRICT_TRANS_TABLES | Medium | ✅ Documented + `(int)` cast required | Acceptable as-is |
| `presence` 1 (DB) vs 2 (PHP) divergence | Low | ✅ Documented | Acceptable as-is |
| `FieldBasic` external consumers | Low | ✅ `@deprecated` only | Acceptable as-is |
| Concurrent writes during migration | N/A | — | No DB migration; not applicable |

---

## Recommended next action

**Resolve B1 first.** Expand `FieldDefinition` to include `sequence`,
`block` (or explicitly excise it from the DTO with a written architectural
reason), `header_field`, `maxlengthtext`, `maxwidthcolumn`. Update the
`fromRow()` mapping, the §Data model property list, the §Implementation
plan steps 1.3 and 1.4, and the §Testing round-trip assertion. Re-run the
review after that revision — the remaining issues (B2, B3, C1–C8) become
mechanical to address once the data shape is settled.

---

## Assumptions made during review

1. The "typeofdata refactor" referenced in the CR's introduction is already
   shipped and `vtiger_field.typeofdata` is a single token (`V`, `D`, `N`, …)
   rather than the historical `V~M` form. The schema view I inspected has
   `typeofdata varchar(100) DEFAULT NULL` which is consistent with either
   convention; I trust the CR's stated pre-condition.
2. `src/Modules/Settings/LayoutEditor/Models/Field.php` is **not** in scope for
   Phase 2 deletion — Settings sub-modules are not "module Field subclasses"
   in the conventional sense. The CR's "~30 subclasses under
   `src/Modules/*/Models/Field.php`" wording is ambiguous on this point;
   I assumed Settings is excluded.
3. The workspace rule that names `FreeCRM\` as the primary namespace is
   aspirational and the codebase has not yet migrated. The CR's use of
   `App\*` therefore matches the codebase reality.
4. `\App\Fields\Field::getFieldInfo()` returns the full `vtiger_field` row
   (possibly enriched). I did not read its implementation; if it strips
   columns, the round-trip test in §Testing Phase 1 has additional coverage
   gaps. Worth a quick check during implementation.
5. The `mandatory` getter in `Models\Field` returns `int` (line 217 returns
   `$this->mandatory` which defaults to `?? 0` in the ctor at line 195) — i.e.
   never `null` in practice. The CR's `bool` proposal is therefore a clean
   `int → bool` swap, not a `?int → bool` swap.

---

## Output

Verdict: **NOT READY** — three blocking issues, eight conditions. The DTO
shape must be corrected (B1) before any of the implementation steps in Phase
1 can be executed safely. Once corrected, the remaining work is concrete
and tractable.
