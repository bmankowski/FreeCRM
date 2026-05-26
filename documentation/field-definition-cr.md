# Field Data Structure Consolidation — MVP

## Goal

Replace ~10 overlapping representations of a CRM field with a single canonical
`FieldDefinition` value object that every layer hydrates from.

The current situation (after the `typeofdata` refactor):

| # | Structure | Problem |
|---|-----------|---------|
| `vtlib\FieldBasic` | ~26 public properties, wrong namespace | Duplicates the DB columns in PHP |
| `vtlib\Field` | extends FieldBasic, marked `@deprecated` | Legacy adapter still in heavy use |
| `App\ModuleManagement\Models\Field` | private properties, same ~26 fields | Exact duplicate of FieldBasic in different visibility |
| `App\Modules\Base\Models\Field` | extends `vtlib\Field`, magic `get()`/`set()` | 4-level deep inheritance, untyped property bag |
| ~30 module `Field` subclasses | mostly empty since `getValidator()` became data-driven | Dead weight |
| `App\Webservices\WebserviceField` | standalone class, maps same DB row independently | ~15 duplicated properties, no shared interface |
| `data.sql` + `Base2.php` | same field list encoded twice in install seed | Every schema change requires two edits |

---

## Status

| Phase | Description | Status |
|-------|-------------|--------|
| Phase 1 — `FieldDefinition` DTO | Create typed value object | **Pending** |
| Phase 2 — Delete empty module Field subclasses | Remove ~30 dead files | **Pending** |
| Phase 3 — `Base\Models\Field` composition | Replace inheritance with `FieldDefinition` | **Out of MVP scope** |
| Phase 4 — `WebserviceField` projection | Eliminate parallel property set | **Out of MVP scope** |
| Phase 5 — Seed data consolidation | Single authoritative PHP seed | **Out of MVP scope** |

---

## Functional requirements

### In scope (MVP)

- **Phase 1**: A single `App\Field\FieldDefinition` typed readonly value object that mirrors every column in `vtiger_field`. All code that currently reads raw DB rows or copies properties between classes can hydrate a `FieldDefinition` instead.
- **Phase 2**: All ~30 module-specific `Field` subclasses that have no unique logic are deleted. Module-specific behaviour that remains must be justified and documented.
- `ModuleManagement\Models\Field` is **retired in use** (every internal call site migrates to `FieldDefinition`) and annotated `@deprecated`. The class file itself is **not deleted** in this MVP — that is a follow-up after a project-wide grep confirms no external code type-hints it.
- `vtlib\FieldBasic` is marked `@deprecated` pointing to `FieldDefinition`. Its properties are not removed yet (too wide a surface).

### Out of scope (future)

- Changing `Base\Models\Field` to hold `FieldDefinition` by composition (Phase 3 — high-risk, wide touch surface).
- Reworking `WebserviceField` to be a projection (Phase 4).
- Consolidating `data.sql` and `Base2.php` (Phase 5).
- Any UI or template changes.

---

## Architecture

```
vtiger_field (DB)
       │
       ▼
FieldDefinition::fromRow($row)   ← single hydration point for NEW code paths
       │
       └──► App\ModuleManagement\Services\FieldService  (returns FieldDefinition
                                                         instead of Models\Field)

App\Modules\Base\Models\Field   ── UNCHANGED in this MVP. Still extends vtlib\Field
                                   and hydrates via vtlib\FieldBasic properties.
                                   Phase 3 (out of scope) will switch it to consume
                                   FieldDefinition by composition.

App\Webservices\WebserviceField ── UNCHANGED in this MVP (Phase 4, out of scope).
```

The `FieldDefinition` object is **additive** in Phase 1. It is consumed only by `FieldService`; no existing class is modified to accept it. It becomes the required input/output type for new code written after this MVP ships.

---

## Data model

### `App\Field\FieldDefinition`

Mirrors `vtiger_field` columns exactly. PHP 8.1 readonly properties, no setters.

DTO property names and `fromRow()` array keys both mirror the **actual `vtiger_field` column names** (see `src/Modules/Install/install_schema/scheme.sql:5218`). This is deliberate — historic PHP code uses `$field->quicksequence` while the DB column is `quickcreatesequence`; the DTO follows the DB name to eliminate that translation layer and prevent silent-default bugs of the kind caught in `table_vtiger_fields_refactor.md` §4 bug #2.

```php
final class FieldDefinition
{
    public function __construct(
        public readonly int    $id,                  // vtiger_field.fieldid
        public readonly int    $tabid,               // vtiger_field.tabid
        public readonly string $name,                // vtiger_field.fieldname
        public readonly string $label,               // vtiger_field.fieldlabel
        public readonly string $table,               // vtiger_field.tablename
        public readonly string $column,              // vtiger_field.columnname
        public readonly ?string $columntype,         // not stored in vtiger_field; derived
        public readonly int    $uitype,              // vtiger_field.uitype
        public readonly string $typeofdata,          // vtiger_field.typeofdata (single token: V, D, N, …)
        public readonly int    $displaytype,         // vtiger_field.displaytype
        public readonly int    $generatedtype,       // vtiger_field.generatedtype
        public readonly bool   $readonly,            // vtiger_field.readonly  (tinyint(1))
        public readonly bool   $mandatory,           // vtiger_field.mandatory (tinyint(1))
        public readonly int    $presence,            // vtiger_field.presence  (DB DEFAULT 1)
        public readonly string $defaultvalue,        // vtiger_field.defaultvalue
        public readonly int    $maximumlength,       // vtiger_field.maximumlength
        public readonly int    $masseditable,        // vtiger_field.masseditable
        public readonly int    $quickcreate,         // vtiger_field.quickcreate
        public readonly int    $quickcreatesequence, // vtiger_field.quickcreatesequence (was $quicksequence in legacy PHP)
        public readonly string $info_type,           // vtiger_field.info_type
        public readonly string $fieldparams,         // vtiger_field.fieldparams
        public readonly string $helpinfo,            // vtiger_field.helpinfo
        public readonly int    $summaryfield,        // vtiger_field.summaryfield
    ) {}

    public static function fromRow(array $row): self
    {
        // Required keys (throw if absent): fieldid, tabid, fieldname, fieldlabel,
        // tablename, columnname, uitype. All other keys fall back to DB DEFAULTs.
        return new self(
            id:                  (int) $row['fieldid'],
            tabid:               (int) $row['tabid'],
            name:                $row['fieldname'],
            label:               $row['fieldlabel'],
            table:               $row['tablename'],
            column:              $row['columnname'],
            columntype:          $row['columntype'] ?? null,
            uitype:              (int) $row['uitype'],
            typeofdata:          $row['typeofdata'] ?? 'V',
            displaytype:         (int) ($row['displaytype'] ?? 1),
            generatedtype:       (int) ($row['generatedtype'] ?? 0),  // DB DEFAULT 0
            readonly:            (bool) ($row['readonly'] ?? false),
            mandatory:           (bool) ($row['mandatory'] ?? false),
            presence:            (int) ($row['presence'] ?? 1),       // DB DEFAULT 1
            defaultvalue:        $row['defaultvalue'] ?? '',
            maximumlength:       (int) ($row['maximumlength'] ?? 100),
            masseditable:        (int) ($row['masseditable'] ?? 1),
            quickcreate:         (int) ($row['quickcreate'] ?? 1),
            quickcreatesequence: (int) ($row['quickcreatesequence'] ?? 0),
            info_type:           $row['info_type'] ?? 'BAS',
            fieldparams:         $row['fieldparams'] ?? '',
            helpinfo:            $row['helpinfo'] ?? '',
            summaryfield:        (int) ($row['summaryfield'] ?? 0),
        );
    }
}
```

No DB table changes. No migration needed.

#### Type changes vs. legacy classes

Two type narrowings vs. `vtlib\FieldBasic` and `ModuleManagement\Models\Field`:

| Field | Legacy type | DTO type | Why |
|-------|-------------|----------|-----|
| `readonly`  | `int` (tinyint(1)) | `bool` | Semantically a flag. |
| `mandatory` | `int` (tinyint(1)) | `bool` | Semantically a flag. |

**Implication for write paths:** any code that takes a `FieldDefinition` and writes back into `vtiger_field` (e.g. `FieldService::create()`/`update()`) MUST cast `(int) $def->readonly` and `(int) $def->mandatory` before passing them to the DB layer. This is the only place the new DTO can produce incorrect data, so it must be enforced explicitly in any code that wires `FieldDefinition` into a write path. See §Edge cases.

#### `presence` default discrepancy

`vtiger_field.presence` has `DEFAULT 1` (scheme.sql:5229). However, three PHP call sites currently default a **missing key** to `2`:

- `vtlib\FieldBasic::$presence = 2` (line 82)
- `FieldService::getInstance()` — `$data['presence'] ?? 2` (line 292)
- `Block.php::addField()` — `(int) $get($fieldInstance, 'presence', 2)` (line 305)

The DTO picks **`1`** in `fromRow()` because `fromRow()` hydrates from a DB row; if `presence` is missing the value the DB itself would have returned is `1`. The PHP-side `?? 2` convention is preserved in legacy code paths and is **not** propagated into the DTO. This divergence is logged under §Risks and should be reconciled in a follow-up (decide once whether the canonical PHP default for an unknown field is `1` or `2`).

### `ModuleManagement\Models\Field` → retired

`FieldDefinition` provides everything `ModuleManagement\Models\Field` did, with proper types. The following call sites must be migrated as part of this MVP (audit performed against the current code):

| File / method | Current usage | Target |
|---------------|---------------|--------|
| `src/ModuleManagement/Services/FieldService.php` `getInstance()` (line 251) | Returns `?Models\Field` via 27-arg constructor (line 273) | Return `?FieldDefinition` |
| `src/ModuleManagement/Services/FieldService.php` `getAllForModule()` (line 444) | Returns `Models\Field[]` via 27-arg constructor (line 466) | Return `FieldDefinition[]` |
| `src/ModuleManagement/Services/FieldService.php` `create()` (line 46) | Accepts `Models\Field $field` | Accepts `FieldDefinition $field` |
| `src/ModuleManagement/Services/FieldService.php` `update()` (line 175) | Accepts `Models\Field $field` | Accepts `FieldDefinition $field` |
| `src/ModuleManagement/Adapters/Block.php` `addField()` (line 286) | Instantiates `\App\ModuleManagement\Models\Field` directly (27-arg constructor) | Instantiate `FieldDefinition` via named arguments |
| `src/ModuleManagement/Services/FieldService.php` `createVtlibFieldInstance()` (line 563) | Reads `$field->getQuicksequence()` to set `$vtlibField->quicksequence` | Read `$def->quickcreatesequence`, still assign to `$vtlibField->quicksequence` (vtlib property name is part of the legacy public surface and not changing in this MVP) |

After these are migrated, `src/ModuleManagement/Models/Field.php` itself is annotated `@deprecated` (the class file is **not deleted** in this MVP — out of caution for any extension code that type-hints it; full deletion is a follow-up).

---

## Processing logic

### Phase 1 — Add `FieldDefinition`

1. Create `src/Field/FieldDefinition.php`.
2. Update `FieldService::getInstance()` and `FieldService::getAllForModule()` to return `?FieldDefinition` and `FieldDefinition[]` respectively.
3. Update `FieldService::create()` and `FieldService::update()` parameter type from `Models\Field` to `FieldDefinition`. Update the only external direct constructor call (`src/ModuleManagement/Adapters/Block.php` line 286) accordingly.
4. Mark `src/ModuleManagement/Models/Field.php` `@deprecated` with a pointer to `FieldDefinition` (do not delete in this MVP).
5. Mark `src/ModuleManagement/Adapters/FieldBasic.php` (`vtlib\FieldBasic`) `@deprecated`.
6. All new code written from this point forwards accepts/returns `FieldDefinition`.

### Phase 2 — Delete empty module Field subclasses

**Pre-condition gate (verified during CR review, must remain true at execution time):**
> The dynamic Field-class resolver at `src/Modules/Base/Models/Field.php:792-793` calls
> `\App\Core\Loader::getComponentClassName('Model', 'Field', $moduleName)`, which already
> implements a graceful fallback chain (`src/Core/Loader.php:158-164`): if
> `App\Modules\{ModuleName}\Models\Field` does not exist, it falls back to
> `App\Modules\Base\Models\Field`. Therefore deleting an empty module Field subclass cannot
> produce a `Class not found` fatal at this call site.
>
> **Action before any file is deleted:** re-run `rg "new \\\\?App\\\\Modules\\\\\\w+\\\\Models\\\\Field"`
> to confirm no other code path constructs a module Field class by hard-coded FQCN (as opposed
> to via the loader). If such a call site is found, treat it as a blocker for Phase 2.

Audit each of the ~30 subclasses under `src/Modules/*/Models/Field.php`:

```
for each module Field subclass:
    if only method(s) are getValidator() with identical logic to base class → DELETE
    if only method is getValidator() with return type annotation only → DELETE
    if unique logic exists → document it, keep the file, move logic toward FieldDefinition/uitype
```

After deletion, remove any `use`/`require` references to the deleted classes. The loader's `Base\Models\Field` fallback handles instantiation for those modules.

---

## Reliability

- `FieldDefinition` is immutable — no concurrency concern.
- `fromRow()` uses null-coalescing defaults that match existing DB `DEFAULT` values; no data loss possible.
- Deleting empty subclasses has no runtime effect because they added no behaviour.

---

## Edge cases

| Case | Handling |
|------|----------|
| Module that still overrides `getValidator()` with real logic | Keep the subclass; mark it explicitly as intentional |
| Code that type-hints `ModuleManagement\Models\Field` | IDE will flag it; update call sites in a follow-up sweep |
| `vtlib\FieldBasic` accessed by external packages | `@deprecated` tag; not removed until Phase 3 |
| `fromRow()` called with incomplete row (e.g. partial select) | Required keys (`fieldid`, `tabid`, `fieldname`, `fieldlabel`, `tablename`, `columnname`, `uitype`) throw on `array_key_exists` failure via PHP's "Undefined array key" notice → coerce error; all others fall back to documented DB DEFAULTs. |
| `bool` → `tinyint(1)` at write boundary | `FieldService::create()`/`update()` must cast `(int) $def->readonly` and `(int) $def->mandatory` before passing them into `Db::createCommand()->insert()/update()`. Without the cast, PHP will send `true`/`false`, which MySQL coerces to `1`/`0` silently in non-strict mode but errors under `STRICT_TRANS_TABLES`. Treat the cast as mandatory. |
| Dynamic Field-class loading after Phase 2 deletion | Verified safe via `Core/Loader::getComponentClassName()` fallback chain (`src/Core/Loader.php:158-164`). See Phase 2 pre-condition gate. |

---

## Deliverables

### Implementation phases

| # | Task | Risk | Files |
|---|------|------|-------|
| 1.1 | Create `src/Field/FieldDefinition.php` (DTO + `fromRow()`) | None (additive) | New file |
| 1.2 | `FieldService::getInstance()` returns `?FieldDefinition`; `FieldService::getAllForModule()` returns `FieldDefinition[]` | Low | `src/ModuleManagement/Services/FieldService.php` |
| 1.3 | `FieldService::create()` / `update()` accept `FieldDefinition`; update direct constructor call site in `Block.php::addField()` | Medium (touches a write path) | `src/ModuleManagement/Services/FieldService.php`, `src/ModuleManagement/Adapters/Block.php` |
| 1.4 | `FieldService::createVtlibFieldInstance()` reads `$def->quickcreatesequence` (still writes `$vtlibField->quicksequence`) | None | `src/ModuleManagement/Services/FieldService.php` |
| 1.5 | Deprecate `ModuleManagement\Models\Field` (do not delete) | None | `src/ModuleManagement/Models/Field.php` |
| 1.6 | Deprecate `vtlib\FieldBasic` | None | `src/ModuleManagement/Adapters/FieldBasic.php` |
| 2.0 | Re-verify Phase 2 pre-condition gate (no hard-coded `new App\Modules\{X}\Models\Field` outside the loader) | None | grep only |
| 2.1 | Audit all ~30 module Field subclasses | None | `src/Modules/*/Models/Field.php` |
| 2.2 | Delete empty subclasses (confirmed no logic) | Low | ~25 files |
| 2.3 | Document surviving subclasses | None | This file |

### Architecture diagram

```
BEFORE                                          AFTER (MVP)
──────────────────────────────────────────      ──────────────────────────────────────────
vtiger_field row (array)                        vtiger_field row (array)
    │                                               │
    ├─► vtlib\FieldBasic (public props)             ├─► vtlib\FieldBasic (@deprecated, kept)
    │       │                                       │       │
    │       └─► vtlib\Field (@deprecated)           │       └─► vtlib\Field (@deprecated)
    │               │                               │               │
    │               └─► Base\Models\Field           │               └─► Base\Models\Field
    │                       │                       │                       │  (UNCHANGED in this MVP;
    │                       └─► ~30 module          │                       │   Phase 3 will swap in
    │                           Field subclasses    │                       │   FieldDefinition by
    │                                               │                       │   composition)
    │                                               │                       └─► surviving module Field
    │                                               │                           subclasses (~5 files)
    │                                               │                           — empty ~25 deleted
    │                                               │
    ├─► ModuleManagement\Models\Field               └─► FieldDefinition::fromRow()
    │   (27-arg ctor duplicate)                             │
    │                                                       └─► FieldService
    │                                                            (getInstance, getAllForModule,
    │                                                             create, update all use
    │                                                             FieldDefinition)
    │
    └─► WebserviceField (independent)               WebserviceField (UNCHANGED in this MVP)
                                                    ModuleManagement\Models\Field (@deprecated, kept)
```

`FieldDefinition` has exactly one consumer in this MVP: `FieldService`. `Base\Models\Field` and `WebserviceField` are untouched.

---

## Tradeoffs

| Decision | Rationale |
|----------|-----------|
| Keep `vtlib\FieldBasic` alive (just deprecated) | Removing it breaks ~10 files in `Base\Models\Field` inheritance chain — out of MVP scope |
| Don't touch `WebserviceField` yet | Separate consumer with its own test surface; deferring keeps the MVP focused |
| `FieldDefinition` as final class | Fields don't need polymorphism at the data level; behaviour belongs in services and uitype classes |
| PHP 8.1 `readonly` properties over getters | Eliminates 26 getter methods; readonly is enforced by the runtime |

---

## Risks

| Risk | Mitigation |
|------|------------|
| A "empty" subclass actually monkey-patches a parent method invisibly | Full grep for all method bodies before deletion; keep any file that overrides even one method |
| `FieldService` callers expect `ModuleManagement\Models\Field` return type | Return type changes in step 1.2/1.3 are breaking; sweep all call sites in the same PR (IDE will surface them). Smoke test before merge. |
| `fromRow()` default values diverge from DB `DEFAULT` | Each default is cross-referenced against `scheme.sql:5218` in the data-model section. `presence` default is intentionally `1` (DB) and intentionally diverges from the `2` used by `FieldBasic`/`FieldService`/`Block.php`; the divergence is documented and must be reconciled in a follow-up. |
| `bool` → `tinyint(1)` write-path bug under strict SQL mode | See §Edge cases. Required `(int)` casts at `FieldService::create()`/`update()` insert/update arrays. |
| Hard-coded `new App\Modules\{X}\Models\Field` outside the loader breaks after Phase 2 | Phase 2 pre-condition gate (re-run grep before deletion). The Loader fallback already protects the known dynamic call site at `Base\Models\Field:792-793`. |

---

## Testing plan

### Phase 1 (additive DTO + `FieldService` return-type swap)

- **Unit**: `FieldDefinition::fromRow()` round-trip — feed a real `vtiger_field` row (selected by `SELECT * FROM vtiger_field WHERE fieldid = <a known id>`) into `fromRow()` and assert every property matches the row column.
- **Unit**: confirm every `fromRow()` array key matches an actual column name from `scheme.sql:5218`. This is the cross-reference test that would have caught the `quicksequence` vs `quickcreatesequence` bug.
- **Smoke**: load EditView and DetailView for one standard module (e.g. Accounts) — exercises `FieldService::getInstance()` and `getAllForModule()` via the existing Base\Models\Field hydration path.
- **Smoke**: create one field via Settings → Layout Editor — exercises `FieldService::create()` (the write path that holds the `(int)` cast risk for `readonly`/`mandatory`).

### Phase 2 (delete empty subclasses)

- **Regression grep** (must return zero hits AFTER deletion of each subclass `X`):
  - `rg "use App\\\\Modules\\\\{X}\\\\Models\\\\Field"`
  - `rg "new\s+\\\\?App\\\\Modules\\\\{X}\\\\Models\\\\Field"`
  - `rg "App\\\\Modules\\\\{X}\\\\Models\\\\Field::"`
- **Smoke**: same EditView + DetailView load for one module whose subclass was deleted, to confirm the loader fallback returns `Base\Models\Field` without error.
- **Log check**: tail `cache/logs/system.log` during the smoke flow; any `Handler not found` from `Core/Loader` indicates a missed hard-coded reference.

---

## Rollback

| Phase | Rollback | External state |
|-------|----------|----------------|
| Phase 1 | `git revert <commit>` | None. No DB changes, no cache keys, no config files reference `FieldDefinition`. |
| Phase 2 | `git revert <commit>` | None. The deleted subclasses are not referenced in any DB row, cache key, or config (verified — instantiation is by FQCN resolution through `Core/Loader`, which is recomputed at runtime). No data restoration needed. |

---

## Future improvements (out of MVP)

- **Phase 3** — `Base\Models\Field` holds a `FieldDefinition` by composition; magic `get()`/`set()` removed; `vtlib\FieldBasic` and `vtlib\Field` deleted.
- **Phase 4** — `WebserviceField` becomes a projection: accepts `FieldDefinition`, no duplicated properties.
- **Phase 5** — Install seed data (`data.sql` + `Base2.php`) consolidated into a single PHP seed class.

---

## Change log

| Date | Author | Change |
|------|--------|--------|
| 2026-05-26 | bmankowski | Initial MVP document |
| 2026-05-26 | bmankowski | Revision per `field-definition-cr-review.md`: removed phantom `setDefinition()`; redrew AFTER diagram to keep `Base\Models\Field` unchanged; corrected `FieldService` method names to `getInstance()`/`getAllForModule()`/`create()`/`update()`; added `Block.php::addField()` (line 286) and `createVtlibFieldInstance()` to retirement scope; renamed `quicksequence` → `quickcreatesequence` (DB column name) with column-name cross-reference comments; documented `readonly`/`mandatory` `int` → `bool` type change with required `(int)` cast at write boundary; documented `presence` `1` (DB) vs `2` (PHP) default divergence; added Phase 2 pre-condition gate verifying the `Core/Loader` graceful fallback; added testing and rollback sections. |
