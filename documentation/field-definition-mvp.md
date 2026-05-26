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
- `ModuleManagement\Models\Field` is retired — `FieldDefinition` replaces it with proper PHP 8 types and no 27-argument constructor.
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
FieldDefinition::fromRow($row)   ← single hydration point
       │
       ├──► App\Modules\Base\Models\Field  (still extends vtlib\Field for now;
       │         accepts FieldDefinition via setDefinition() for new code paths)
       │
       ├──► App\Webservices\WebserviceField  (unchanged for MVP)
       │
       └──► App\ModuleManagement\Services\FieldService  (replaces Models\Field)
```

The `FieldDefinition` object is **additive** in Phase 1 — no existing class is broken. It becomes the required input type for new code written after this MVP ships.

---

## Data model

### `App\Field\FieldDefinition`

Mirrors `vtiger_field` columns exactly. PHP 8.1 readonly properties, no setters.

```php
final class FieldDefinition
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $tabid,
        public readonly string $name,
        public readonly string $label,
        public readonly string $table,
        public readonly string $column,
        public readonly string $columntype,
        public readonly int    $uitype,
        public readonly string $typeofdata,    // single type-code token: V, D, N, …
        public readonly int    $displaytype,
        public readonly int    $generatedtype,
        public readonly bool   $readonly,
        public readonly bool   $mandatory,
        public readonly int    $presence,
        public readonly string $defaultvalue,
        public readonly int    $maximumlength,
        public readonly int    $masseditable,
        public readonly int    $quickcreate,
        public readonly int    $quicksequence,
        public readonly string $info_type,
        public readonly string $fieldparams,
        public readonly string $helpinfo,
        public readonly int    $summaryfield,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id:            (int) $row['fieldid'],
            tabid:         (int) $row['tabid'],
            name:          $row['fieldname'],
            label:         $row['fieldlabel'],
            table:         $row['tablename'],
            column:        $row['columnname'],
            columntype:    $row['columntype'] ?? '',
            uitype:        (int) $row['uitype'],
            typeofdata:    $row['typeofdata'] ?? 'V',
            displaytype:   (int) ($row['displaytype'] ?? 1),
            generatedtype: (int) ($row['generatedtype'] ?? 1),
            readonly:      (bool) ($row['readonly'] ?? false),
            mandatory:     (bool) ($row['mandatory'] ?? false),
            presence:      (int) ($row['presence'] ?? 1),
            defaultvalue:  $row['defaultvalue'] ?? '',
            maximumlength: (int) ($row['maximumlength'] ?? 100),
            masseditable:  (int) ($row['masseditable'] ?? 1),
            quickcreate:   (int) ($row['quickcreate'] ?? 1),
            quicksequence: (int) ($row['quicksequence'] ?? 0),
            info_type:     $row['info_type'] ?? 'BAS',
            fieldparams:   $row['fieldparams'] ?? '',
            helpinfo:      $row['helpinfo'] ?? '',
            summaryfield:  (int) ($row['summaryfield'] ?? 0),
        );
    }
}
```

No DB table changes. No migration needed.

### `ModuleManagement\Models\Field` → retired

`FieldDefinition` provides everything `ModuleManagement\Models\Field` did, with proper types. `FieldService` methods that return `ModuleManagement\Models\Field` are updated to return `FieldDefinition`.

---

## Processing logic

### Phase 1 — Add `FieldDefinition`

1. Create `src/Field/FieldDefinition.php`.
2. Update `FieldService::getField()` and `FieldService::getFields()` to return `FieldDefinition` (or `FieldDefinition[]`).
3. Mark `ModuleManagement\Models\Field` `@deprecated` with a pointer to `FieldDefinition`.
4. Mark `vtlib\FieldBasic` `@deprecated`.
5. All new code written from this point forwards accepts/returns `FieldDefinition`.

### Phase 2 — Delete empty module Field subclasses

Audit each of the ~30 subclasses under `src/Modules/*/Models/Field.php`:

```
for each module Field subclass:
    if only method(s) are getValidator() with identical logic to base class → DELETE
    if only method is getValidator() with return type annotation only → DELETE
    if unique logic exists → document it, keep the file, move logic toward FieldDefinition/uitype
```

After deletion, remove any `use`/`require` references to the deleted classes. The base `App\Modules\Base\Models\Field` is instantiated directly for those modules.

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
| `fromRow()` called with incomplete row (e.g. partial select) | Missing keys fall to defaults; document which keys are required |

---

## Deliverables

### Implementation phases

| # | Task | Risk | Files |
|---|------|------|-------|
| 1.1 | Create `src/Field/FieldDefinition.php` | None (additive) | New file |
| 1.2 | `FieldService::getField()` returns `FieldDefinition` | Low | `src/ModuleManagement/Services/FieldService.php` |
| 1.3 | Deprecate `ModuleManagement\Models\Field` | None | `src/ModuleManagement/Models/Field.php` |
| 1.4 | Deprecate `vtlib\FieldBasic` | None | `src/ModuleManagement/Adapters/FieldBasic.php` |
| 2.1 | Audit all ~30 module Field subclasses | None | `src/Modules/*/Models/Field.php` |
| 2.2 | Delete empty subclasses (confirmed no logic) | Low | ~25 files |
| 2.3 | Document surviving subclasses | None | This file |

### Architecture diagram

```
BEFORE                                      AFTER (MVP)
──────────────────────────────────────      ──────────────────────────────────────
vtiger_field row (array)                    vtiger_field row (array)
    │                                           │
    ├─► vtlib\FieldBasic (public props)         ▼
    │       │                               FieldDefinition::fromRow()  ← single entry
    │       └─► vtlib\Field (@deprecated)       │
    │               │                           ├─► FieldService (returns FieldDefinition)
    │               └─► Base\Models\Field       │
    │                       │                   └─► Base\Models\Field (unchanged for now)
    │                       └─► ~30 module          (no more ~25 empty subclasses)
    │                           Field subclasses
    │
    ├─► ModuleManagement\Models\Field        ModuleManagement\Models\Field (@deprecated)
    │   (27-arg constructor duplicate)
    │
    └─► WebserviceField (independent)        WebserviceField (unchanged for MVP)
```

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
| `FieldService` callers expect `ModuleManagement\Models\Field` return type | Add `@deprecated` tag and IDE inspection rule; do not break the return type without a sweep |
| `fromRow()` default values diverge from DB `DEFAULT` | Cross-check each default against `scheme.sql` before merging |

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
