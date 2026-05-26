# Field Data Structure Consolidation — MVP

## Goal

Replace ~10 overlapping representations of a CRM field with a single canonical
`FieldDefinition` value object and collapse the persistence chain onto one
class (`Base\Models\Field`).

The current situation (after the `typeofdata` refactor):

| # | Structure | Problem |
|---|-----------|---------|
| `vtlib\FieldBasic` | ~26 public properties, `#[\AllowDynamicProperties]` | Duplicates the DB columns in PHP |
| `vtlib\Field` | extends `FieldBasic`, marked `@deprecated` | Legacy adapter still in heavy use |
| `App\ModuleManagement\Models\Field` | private properties, 28-arg constructor | Exact duplicate of `FieldBasic` in different visibility |
| `App\ModuleManagement\Services\FieldService` | Service-layer wrapper over `vtiger_field` | Pattern-foreign to this codebase (rest of the codebase uses static factories on models) |
| `App\Modules\Base\Models\Field` | extends `vtlib\Field`, magic `get()`/`set()` | 4-level deep inheritance, untyped property bag |
| ~30 module `Field` subclasses | mostly empty since `getValidator()` became data-driven | Dead weight |
| `App\Webservices\WebserviceField` | standalone class, maps the same DB row independently | ~15 duplicated properties, no shared interface |
| `data.sql` + `Base2.php` | same field list encoded twice in install seed | Every schema change requires two edits |

**Architectural principle for this MVP:** this codebase does not use a service
layer — persistence lives on models (Active Record pattern, see `Base\Models\Field::__update()`,
`::save()`, `::getInstance()`). `FieldService` is an inconsistent foreign
pattern and is deleted as part of this refactor.

---

## Status

| Phase | Description | Status |
|-------|-------------|--------|
| Phase 1 — `FieldDefinition` DTO | Create typed value object | **Pending** |
| Phase 2 — Delete empty module Field subclasses | Remove ~30 dead files | **Pending** |
| Phase 3a — `Base\Models\Field` composition | Replace inheritance with `FieldDefinition`; remove magic `get()`/`set()` | **Pending** |
| Phase 3b — Move persistence onto `Base\Models\Field` | Static `create()`/`delete()`; extend `save()` | **Pending** |
| Phase 3c — Delete `FieldService` | Migrate call sites; delete `ServiceLocator::getFieldService()` | **Pending** |
| Phase 3d — Delete legacy adapters | Delete `vtlib\Field`, `vtlib\FieldBasic`, `ModuleManagement\Models\Field` | **Pending** |
| Phase 4 — `WebserviceField` projection | Eliminate parallel property set | **Out of MVP scope** |
| Phase 5 — Seed data consolidation | Single authoritative PHP seed | **Out of MVP scope** |

---

## Functional requirements

### In scope (MVP)

- **Phase 1**: A single `App\Field\FieldDefinition` typed readonly value object that mirrors every column in `vtiger_field`. The DTO becomes the canonical read shape for new code.
- **Phase 2**: All ~30 module-specific `Field` subclasses that have no unique logic are deleted. Module-specific behaviour that remains must be justified and documented.
- **Phase 3a**: `Base\Models\Field` holds a `FieldDefinition` member; magic `get()`/`set()` is removed in favour of typed getters and explicit instance-state accessors. External API of `Base\Models\Field` (`getName()`, `getId()`, `getModule()`, …) is preserved.
- **Phase 3b**: `Base\Models\Field` gains `static create()`, instance `delete()`, and an extended `save()` that covers all writable columns. This replaces every capability of `FieldService`.
- **Phase 3c**: All ~15 `getFieldService()` call sites migrate to `Base\Models\Field` static and instance methods. `FieldService`, `ServiceLocator::getFieldService()`, and the `Services\FieldService` test-only seam are deleted.
- **Phase 3d**: `vtlib\Field`, `vtlib\FieldBasic`, and `App\ModuleManagement\Models\Field` are deleted. The inheritance chain `Base\Models\Field → vtlib\Field → vtlib\FieldBasic` flattens to a standalone `Base\Models\Field`.

### Out of scope (future)

- Reworking `WebserviceField` to be a projection (Phase 4).
- Consolidating `data.sql` and `Base2.php` (Phase 5).
- Deleting `ModuleManagement\Services\BlockService`, `ModuleService`, `PackageService` — they have the same architectural mismatch but are independent refactors. This CR fixes only the Field service.
- Any UI or template changes.

---

## Architecture

```
BEFORE (today)                                  AFTER (this MVP)
─────────────────────────────────────────       ─────────────────────────────────────────
vtiger_field row                                vtiger_field row
    │                                               │
    ├─► vtlib\FieldBasic (public props)             │
    │       │                                       │
    │       └─► vtlib\Field (@deprecated)           │
    │               │                               │
    │               └─► Base\Models\Field           │
    │                       │   (magic get/set,     │
    │                       │    inheritance bag)   │
    │                       │                       │
    │                       └─► ~30 module          │
    │                           Field subclasses    │
    │                                               │
    ├─► ModuleManagement\Models\Field               └─► FieldDefinition::fromRow()
    │       │                                               │
    │       └─► FieldService                                └─► Base\Models\Field (composes
    │             (create/update/delete/                            FieldDefinition; persistence
    │              getInstance/getAllForModule/                     by static + instance methods;
    │              setPicklistValues/setRelatedModules)             no inheritance)
    │                                                               │
    └─► WebserviceField (independent)                               └─► ~5 surviving module
                                                                        Field subclasses
                                                    
                                                    WebserviceField (UNCHANGED in this MVP)
```

Net delta: **five Field-shaped classes collapse to two** (`FieldDefinition` +
`Base\Models\Field`). The persistence chain (`Base\Models\Field → vtlib\Field →
ModuleManagement\Models\Field → FieldService`) collapses to one method-call
boundary.

---

## Data model

### `App\Field\FieldDefinition`

Mirrors `vtiger_field` columns exactly. PHP 8.1 readonly properties, no setters.
DTO property names and `fromRow()` array keys both mirror the **actual
`vtiger_field` column names** (see `src/Modules/Install/install_schema/scheme.sql:5218`).
This is deliberate — historic PHP code uses `$field->quicksequence` while the
DB column is `quickcreatesequence`; the DTO follows the DB name to eliminate
that translation layer and prevent silent-default bugs of the kind caught in
`table_vtiger_fields_refactor.md` §4 bug #2.

```php
final class FieldDefinition
{
    public function __construct(
        public readonly int     $id,                  // vtiger_field.fieldid
        public readonly int     $tabid,               // vtiger_field.tabid
        public readonly string  $name,                // vtiger_field.fieldname
        public readonly string  $label,               // vtiger_field.fieldlabel
        public readonly string  $table,               // vtiger_field.tablename
        public readonly string  $column,              // vtiger_field.columnname
        public readonly ?string $columntype,          // not stored in vtiger_field; derived
        public readonly int     $uitype,              // vtiger_field.uitype
        public readonly string  $typeofdata,          // single token (V, D, N, …) — post-typeofdata-refactor
        public readonly int     $displaytype,         // vtiger_field.displaytype
        public readonly int     $generatedtype,       // vtiger_field.generatedtype (DB DEFAULT 0)
        public readonly bool    $readonly,            // vtiger_field.readonly (tinyint(1))
        public readonly bool    $mandatory,           // vtiger_field.mandatory (tinyint(1))
        public readonly int     $presence,            // vtiger_field.presence (DB DEFAULT 1)
        public readonly string  $defaultvalue,        // vtiger_field.defaultvalue
        public readonly int     $maximumlength,       // vtiger_field.maximumlength
        public readonly int     $sequence,            // vtiger_field.sequence
        public readonly ?int    $block,               // vtiger_field.block (FK, nullable)
        public readonly int     $masseditable,        // vtiger_field.masseditable
        public readonly int     $quickcreate,         // vtiger_field.quickcreate
        public readonly ?int    $quickcreatesequence, // vtiger_field.quickcreatesequence (was $quicksequence in legacy PHP)
        public readonly string  $info_type,           // vtiger_field.info_type
        public readonly string  $fieldparams,         // vtiger_field.fieldparams
        public readonly string  $helpinfo,            // vtiger_field.helpinfo
        public readonly int     $summaryfield,        // vtiger_field.summaryfield
        public readonly ?string $header_field,        // vtiger_field.header_field
        public readonly int     $maxlengthtext,       // vtiger_field.maxlengthtext
        public readonly int     $maxwidthcolumn,      // vtiger_field.maxwidthcolumn
    ) {}

    public static function fromRow(array $row): self
    {
        // Required keys (PHP errors on undefined index): fieldid, tabid,
        // fieldname, fieldlabel, tablename, columnname, uitype. All other
        // keys fall back to documented defaults below.
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
            generatedtype:       (int) ($row['generatedtype'] ?? 0),    // DB DEFAULT 0
            readonly:            (bool) ($row['readonly'] ?? false),
            mandatory:           (bool) ($row['mandatory'] ?? false),   // DB DEFAULT 0
            presence:            (int) ($row['presence'] ?? 1),         // DB DEFAULT 1
            defaultvalue:        $row['defaultvalue'] ?? '',
            maximumlength:       (int) ($row['maximumlength'] ?? 100),
            sequence:            (int) ($row['sequence'] ?? 0),
            block:               isset($row['block']) ? (int) $row['block'] : null,
            masseditable:        (int) ($row['masseditable'] ?? 1),     // DB DEFAULT 1
            quickcreate:         (int) ($row['quickcreate'] ?? 1),      // DB DEFAULT 1
            quickcreatesequence: isset($row['quickcreatesequence']) ? (int) $row['quickcreatesequence'] : null,
            info_type:           $row['info_type'] ?? 'BAS',
            fieldparams:         $row['fieldparams'] ?? '',
            helpinfo:            $row['helpinfo'] ?? '',
            summaryfield:        (int) ($row['summaryfield'] ?? 0),     // DB DEFAULT 0
            header_field:        $row['header_field'] ?? null,
            maxlengthtext:       (int) ($row['maxlengthtext'] ?? 0),    // DB DEFAULT 0
            maxwidthcolumn:      (int) ($row['maxwidthcolumn'] ?? 0),   // DB DEFAULT 0
        );
    }

    /**
     * Immutable update — returns a new DTO with the given fields replaced.
     * Used by Base\Models\Field::updateMandatory() etc. to mutate
     * persistence-bound state without breaking readonly contract.
     */
    public function with(array $changes): self
    {
        $args = array_merge(get_object_vars($this), $changes);
        return new self(...$args);
    }

    public function toRow(): array
    {
        return [
            'fieldid'             => $this->id,
            'tabid'               => $this->tabid,
            'fieldname'           => $this->name,
            'fieldlabel'          => $this->label,
            'tablename'           => $this->table,
            'columnname'          => $this->column,
            'uitype'              => $this->uitype,
            'typeofdata'          => $this->typeofdata,
            'displaytype'         => $this->displaytype,
            'generatedtype'       => $this->generatedtype,
            'readonly'            => (int) $this->readonly,    // bool → tinyint
            'mandatory'           => (int) $this->mandatory,   // bool → tinyint
            'presence'            => $this->presence,
            'defaultvalue'        => $this->defaultvalue,
            'maximumlength'       => $this->maximumlength,
            'sequence'            => $this->sequence,
            'block'               => $this->block,
            'masseditable'        => $this->masseditable,
            'quickcreate'         => $this->quickcreate,
            'quickcreatesequence' => $this->quickcreatesequence,
            'info_type'           => $this->info_type,
            'fieldparams'         => $this->fieldparams,
            'helpinfo'            => $this->helpinfo,
            'summaryfield'        => $this->summaryfield,
            'header_field'        => $this->header_field,
            'maxlengthtext'       => $this->maxlengthtext,
            'maxwidthcolumn'      => $this->maxwidthcolumn,
        ];
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

`toRow()` performs the `(int)` cast at the persistence boundary, so the DB
layer continues to see `0`/`1`. This is the **only** place the new DTO can
produce incorrect data, so it is enforced centrally rather than at each call
site.

`quickcreatesequence` is typed as `?int` (DB column allows NULL with
`DEFAULT NULL`). Legacy code used `false` for "unset"; the DTO preserves NULL.

`block` is typed as `?int` (DB FK, nullable). It is **only the FK** — Block
instance resolution is a separate concern handled by `BlockService::getById()`
at the point of use, not eagerly inside the DTO or its consumers.

#### Default value reconciliation

Three default-value discrepancies between DB schema and legacy PHP code are
reconciled in favour of the DB:

| Field | DB schema | Legacy PHP default | DTO default | Why DB wins |
|-------|-----------|--------------------|-----------:|-------------|
| `presence` | DEFAULT 1 | 2 (FieldBasic, FieldService, Block.php) | 1 | DB defaults are authoritative; the `2` was a defensive PHP choice that drifted. |
| `readonly` | NOT NULL, no DEFAULT | 1 | false (=0) | NOT NULL means rows always have a value on read; the default is only triggered when `fromRow()` is called on a partial row, in which case `false`/`0` is the safe assumption. |
| `generatedtype` | DEFAULT 0 | 1 (Models\Field, FieldService) | 0 | Same as `presence` — DB default is authoritative. |
| `quickcreatesequence` | DEFAULT NULL | `false` | `null` | Legacy `false` was a PHP idiom; the DB column allows NULL and `?int` expresses that natively. |

Each divergence was logged historically but never reconciled. This MVP picks
the DB-aligned values once and for all. **Reviewers: confirm no production
write path relies on the legacy PHP defaults before merging.**

### `Base\Models\Field` — composition design

```php
namespace App\Modules\Base\Models;

use App\Field\FieldDefinition;

class Field
{
    /** Immutable DB-mirror — replaced via clone-and-replace, never mutated in place. */
    protected FieldDefinition $definition;

    /** Runtime state — was previously magic-`set()` onto dynamic properties. */
    protected ?Module $module = null;
    protected ?\App\Modules\Base\UiTypes\BaseUiType $uitypeModel = null;
    protected ?string $fieldDataType = null;
    protected mixed $fieldvalue = null;
    protected ?bool $isReadOnly = null;
    protected ?bool $isEditableReadOnly = null;
    protected ?array $fieldInfo = null;
    protected ?WebserviceField $webserviceField = null;
    protected mixed $workflow_columnname = null;
    protected mixed $reportcolumn = null;
    protected mixed $reportlabel = null;
    protected mixed $reportcolumninfo = null;
    protected mixed $fromOutsideList = null;
    protected ?\App\Modules\Base\Models\Block $blockInstance = null;  // lazily resolved from $definition->block

    public function __construct(FieldDefinition $definition)
    {
        $this->definition = $definition;
    }

    public function getDefinition(): FieldDefinition { return $this->definition; }

    public static function fromRow(array $row): self
    {
        return new static(FieldDefinition::fromRow($row));
    }

    /** Typed accessors delegate to $definition. Preserves existing public API. */
    public function getId(): int                       { return $this->definition->id; }
    public function getName(): string                  { return $this->definition->name; }
    public function getFieldName(): string             { return $this->definition->name; }
    public function getLabel(): string                 { return $this->definition->label; }
    public function getFieldLabel(): string            { return $this->definition->label; }
    public function getTableName(): string             { return $this->definition->table; }
    public function getColumnName(): string            { return $this->definition->column; }
    public function getUIType(): int                   { return $this->definition->uitype; }
    public function getTabid(): int                    { return $this->definition->tabid; }
    public function getModuleId(): int                 { return $this->definition->tabid; }
    public function getDisplayType(): int              { return $this->definition->displaytype; }
    public function isMandatory(): bool                { return $this->definition->mandatory; }
    public function isCustomField(): bool              { return $this->definition->generatedtype === 2; }
    public function hasDefaultValue(): bool            { return $this->definition->defaultvalue !== ''; }
    public function isMassEditable(): bool             { return $this->definition->masseditable === 1; }
    public function isHeaderField(): bool              { return !empty($this->definition->header_field); }
    public function isActiveField(): bool              { return in_array($this->definition->presence, [0, 2], true); }
    public function getDefaultFieldValue(): string     { return $this->definition->defaultvalue; }
    // ... and so on for every getter that today reads a DB-mirror property
}
```

**No magic `get($name)` or `set($name, $value)`.** Every property access is
explicit and typed. The `protected` visibility on the runtime-state fields
allows the ~5 surviving module subclasses to extend behaviour without
re-introducing the magic bag.

**Module subclasses (`src/Modules/X/Models/Field.php`)** that survive Phase 2
must call `parent::__construct($definition)` and may only add new
behaviour, not new DB-mirror properties.

### Persistence — moves from `FieldService` onto `Base\Models\Field`

`FieldService` is deleted in Phase 3c. Its capabilities relocate as follows:

| `FieldService` method | Replacement on `Base\Models\Field` |
|-----------------------|-------------------------------------|
| `getInstance($idOrName, ?Module)` | `static getInstance($value, $module = false): ?self` (already exists, extended to use `FieldDefinition::fromRow()`) |
| `getAllForModule(int $moduleId)` | `static getAllForModule(Module $module): array` (already exists, rewired to use `FieldDefinition::fromRow()`) |
| `getAllForBlock(Block, Module)` | `static getAllForBlock($blockInstance, $moduleInstance): array` (new — used by old `vtlib\Field::getAllForBlock`) |
| `create(int $moduleId, int $blockId, FieldDefinition $def): int` | `static create(int $moduleId, int $blockId, FieldDefinition $def): self` (new — encapsulates the transactional INSERT + `ALTER TABLE` + `initProfile` + cache-bust) |
| `update(int $fieldId, FieldDefinition $def): void` | extended `save(): self` (already exists; today only updates a subset, will be expanded to cover every writable column) |
| `delete(int $fieldId): void` | `delete(): void` (instance method, new) |
| `setPicklistValues(int $fieldId, array $values)` | already covered by `\App\Fields\Picklist::setPickListValues()` — call site moves directly to it |
| `setRelatedModules(int $fieldId, array $modules)` | `setRelatedModules(array $modules): void` (instance method, new) |
| `unsetRelatedModules(int $fieldId, array $modules)` | `unsetRelatedModules(array $modules): void` (instance method, new) |
| `setSequence(int $blockId, array $sequence)` | static helper or moves into `Block::setFieldSequence()` |
| `deleteForModule(Module)` | already exists on `Base\Models\Field` as `static deleteForModule` (today via inheritance from `vtlib\Field`) |
| `createVtlibFieldInstance(int $fieldId): \vtlib\Field` | **deleted entirely** — `vtlib\Field` disappears in Phase 3d |

The transactional `create()` is the only piece with non-trivial coordination
(INSERT into `vtiger_field` + `ALTER TABLE` to add the column + `initProfile()`,
all inside a transaction). It lives on `Base\Models\Field` as a static factory
rather than in a dedicated persistence helper, to match the existing pattern
where `save()` and `__update()` are already on the model.

If `Base\Models\Field` grows uncomfortably large, the transactional helpers
can be extracted into a `trait` (`Base\Models\FieldPersistenceTrait`) that the
class uses — same call surface, just a file split. This is left to the
implementer's judgement during Phase 3b.

---

## Processing logic

### Phase 1 — Add `FieldDefinition`

1. Create `src/Field/FieldDefinition.php` with the DTO, `fromRow()`, `toRow()`, and `with()`.
2. No call sites are migrated yet. The DTO is additive and unreferenced. Zero functional change.
3. Add `tests/Unit/Field/FieldDefinitionTest.php`: round-trip a real `vtiger_field` row, verify every column maps correctly, verify `toRow()` produces a write-compatible array.

### Phase 2 — Delete empty module Field subclasses

**Pre-condition gate** (verified at execution time, not just at CR-review time):

> The dynamic Field-class resolver at `src/Modules/Base/Models/Field.php:792`
> calls `\App\Core\Loader::getComponentClassName('Model', 'Field', $moduleName)`,
> which has a graceful fallback chain (`src/Core/Loader.php:158–164` for the
> lowercase fallback, `:167–175` for the capitalized fallback). If
> `App\Modules\{ModuleName}\Models\Field` does not exist, both fallbacks
> resolve to `App\Modules\Base\Models\Field`. Therefore deleting an empty
> subclass cannot produce a "class not found" fatal at this call site.
>
> **Action before any file is deleted:** re-run
> `rg "App\\\\Modules\\\\\\w+\\\\Models\\\\Field"` (no leading `new` — the
> broader pattern catches static method calls, string class names in config,
> `use` statements with subsequent constant access, and PHP attributes).
> If any hard-coded FQCN reference is found outside the Loader, treat it as
> a blocker for Phase 2.

**Audit (concrete inventory)**: before deletion, produce a table of every
`src/Modules/*/Models/Field.php` with one of:

| Outcome | Criterion |
|---------|-----------|
| Delete | File contains only a class declaration extending `Base\Models\Field` with no body, or with only `getValidator(): array { return []; }` |
| Delete | File contains only `getValidator()` that returns the same value as `Base\Models\Field::getValidator()` for the same field name |
| Keep | File overrides any method with behaviour absent from the base class |
| Keep | File defines any property, constant, or method other than `getValidator()` |

The inventory table is committed alongside the Phase 2 PR for future review.

After deletion, remove any `use`/`require` references to the deleted classes.
The Loader's `Base\Models\Field` fallback handles instantiation.

### Phase 3a — Compose `Base\Models\Field` over `FieldDefinition`

1. Add `protected FieldDefinition $definition;` field to `Base\Models\Field`.
2. Add a constructor that accepts a `FieldDefinition`.
3. Add `static fromRow(array $row): self`.
4. Replace `initialize($valuemap)` (lines 244–281) with `static fromRow()`. Internal call sites in `getInstanceFromFieldId()` (line 1345) and any other `->initialize($x)` switch to `Field::fromRow($x)`.
5. **Remove `public get($propertyName)` and `public set($name, $value)`** (lines 60–78). Every internal call site of `$this->get('foo')` and `$this->set('foo', X)` migrates to either:
   - A typed delegating getter on `Base\Models\Field` (for DB-mirror properties), or
   - Direct typed access to a runtime-state property (`$this->uitypeModel = ...`).
6. Migrate all direct `$this->propname` accesses to either `$this->definition->propname` (for DB-mirror) or stay as direct access (for runtime state). Examples that need this migration:
   - `getDefaultFieldValue()` line 1207: `$this->defaultvalue` → `$this->definition->defaultvalue`
   - `isCustomField()` line 1293: `$this->generatedtype` → `$this->definition->generatedtype`
   - `hasDefaultValue()` line 1298: `$this->defaultvalue` → `$this->definition->defaultvalue`
   - `isMassEditable()` line 1309: `$this->masseditable` → `$this->definition->masseditable`
   - `isHeaderField()` line 1314: `$this->header_field` → `$this->definition->header_field`
7. `updateMandatory($mandatoryValue)` (line 1281) — currently `set('mandatory', ...)`. Rewrite as: `$this->definition = $this->definition->with(['mandatory' => $mandatoryValue === 'M']); return $this;`
8. Remove the `extends \vtlib\Field` declaration. `Base\Models\Field` becomes a standalone class. All inherited static methods (`getInstance`, `getAllForModule`, `getAllForBlock`, `deleteForModule`) are re-implemented to use `FieldDefinition::fromRow()` directly against the DB, replacing the `parent::getInstance()` call at line 1059.

This phase has no external API changes — every public method on
`Base\Models\Field` keeps its signature.

### Phase 3b — Persistence methods on `Base\Models\Field`

1. Add `static create(int $moduleId, int $blockId, FieldDefinition $def): self` containing the transactional INSERT + `ALTER TABLE` + `initProfile()` + cache invalidation logic that lives in `FieldService::create()` today. Returns a hydrated `Field` instance.
2. Add instance `delete(): void` containing the cascade-delete logic from `FieldService::delete()` (delete from `vtiger_field`, delete from `vtiger_fieldmodulerel` if UIType 10, cache invalidation).
3. Extend the existing `save()` and `__update()` (lines 1230–1278) to write every editable column from `$this->definition` via `$this->definition->toRow()` (not just the layout-editor subset). Confirm cache invalidation covers `'ModuleFields'`, `'field-{moduleId}'`, `'FieldModel'`, and `\App\Fields\Field::clearFieldsPermissionsCacheForTab()`.
4. Add `setRelatedModules(array $modules): void`, `unsetRelatedModules(array $modules): void`.

### Phase 3c — Delete `FieldService`; migrate consumers

Every consumer of `\App\ModuleManagement\ServiceLocator::getFieldService()`
migrates to a `Base\Models\Field` static method or instance call. Concrete
migration table (from workspace grep, must be re-run before merge to catch any
new consumers introduced after this CR was written):

| File:line | Today | Target |
|-----------|-------|--------|
| `src/Modules/Settings/LayoutEditor/Models/Field.php:29` | `getFieldService()` for layout-editor field CRUD | Call `Base\Models\Field::create()`, `$field->save()`, `$field->delete()` directly |
| `src/ModuleManagement/Adapters/Field.php:56,67,79,92,147,197,253` | Wrapper class delegating to FieldService | **File deleted entirely** in Phase 3d (this is `vtlib\Field`) |
| `src/ModuleManagement/Adapters/Block.php:199,236,286,317` | Field CRUD inside the Block adapter (vtlib\Block) | Migrate to `Base\Models\Field::create()` / `::getInstance()` |
| `src/ModuleManagement/Services/PackageService.php:958,2477` | Field creation during module-package install | Call `Base\Models\Field::create()` directly |
| `src/ModuleManagement/Services/ModuleService.php:504` | Field operations during module create | Call `Base\Models\Field::create()` directly |
| `src/ModuleManagement/Services/BlockService.php:221` | Field collection for a block | Call `Base\Models\Field::getAllForBlock()` |

After all migrations are done:

1. Delete `src/ModuleManagement/Services/FieldService.php`.
2. Delete the `getFieldService()` method and the `'field'` slot from `src/ModuleManagement/ServiceLocator.php:49–55`.
3. Re-run the grep `rg "getFieldService\("` — must return zero hits.
4. Re-run the grep `rg "Services\\\\FieldService"` — must return zero hits.

The other Services (`BlockService`, `ModuleService`, `PackageService`) are
**not** deleted in this MVP. They are architecturally inconsistent too, but
that is a separate refactor with its own blast radius.

### Phase 3d — Delete legacy adapters

1. Delete `src/ModuleManagement/Adapters/Field.php` (`vtlib\Field`).
2. Delete `src/ModuleManagement/Adapters/FieldBasic.php` (`vtlib\FieldBasic`).
3. Delete `src/ModuleManagement/Models/Field.php` (`ModuleManagement\Models\Field`).
4. Re-run the grep `rg "vtlib\\\\Field[Basic]*\b"` — every remaining hit should be a docblock reference or a comment; any code reference is a blocker.
5. Re-run the grep `rg "ModuleManagement\\\\Models\\\\Field\b"` — must return zero hits.
6. Confirm `installer/migrations/*` and `cron/*` do not import these classes.

---

## Reliability

- `FieldDefinition` is immutable — no concurrency concern. `with()` returns a new instance.
- `fromRow()` uses null-coalescing defaults that match the DB `DEFAULT` values; no data loss on read.
- `toRow()` applies the `(bool → int)` casts centrally; no STRICT_TRANS_TABLES surprise at write boundary.
- Phase 2 deletion has no runtime effect because the deleted subclasses add no behaviour (verified by audit table).
- Phase 3 changes are internal restructuring; external API of `Base\Models\Field` is preserved verbatim. Existing callers of `$field->getName()`, `$field->getId()`, `$field->save()` continue to work.

---

## Edge cases

| Case | Handling |
|------|----------|
| Module that still overrides `getValidator()` with real logic | Keep the subclass; document in the Phase 2 audit table; subclass must call `parent::__construct($definition)`. |
| `fromRow()` called with an incomplete row (e.g. partial SELECT) | Required keys (`fieldid`, `tabid`, `fieldname`, `fieldlabel`, `tablename`, `columnname`, `uitype`) trigger PHP "undefined array key" → coerce error. All other keys fall back to documented defaults. |
| `bool` → `tinyint(1)` at write boundary | `toRow()` performs the `(int)` cast for `readonly` and `mandatory` before they reach the persistence layer. Without this cast, PHP sends `true`/`false`, which MySQL coerces silently in non-strict mode but errors under `STRICT_TRANS_TABLES`. The cast is centralised in `toRow()`. |
| Read-side `bool` vs legacy `int` consumers | `Base\Models\Field::isMandatory()` already returns `bool` (line 595). `isReadOnly()` returns `bool` (line 494). External callers of these methods are already using the bool API. Template/Smarty code that reads `$field->mandatory` directly: with magic getter gone, that read returns `null` (property no longer exists on the class). Such call sites must migrate to `$field->isMandatory()`. The Phase 3a step explicitly removes `get()`/`set()`, which forces this migration to surface immediately. |
| Dynamic Field-class loading after Phase 2 deletion | Verified safe via `Core/Loader::getComponentClassName()` fallback chain (lowercase + capitalized). See Phase 2 pre-condition gate. |
| `'ModuleFields'` and `'field-{id}'` cache contains pre-Phase-3 objects | Phase 3a changes the in-memory shape of `Base\Models\Field` (composition vs. inheritance with dynamic properties). Persisted-cache deploys must invalidate these keys. Deploy procedure: clear cache (file cache, APCu, opcache) immediately after deploying Phase 3a. Documented in Phase 3a deployment step. |
| `vtlib\Block` instances passed in via `$field->block` | Today `Base\Models\Field` holds a hydrated `vtlib\Block` or `Base\Models\Block` instance under `block`. In the new design, the FK (`?int`) lives in `$definition->block`, and the hydrated `Block` instance lives separately in `$this->blockInstance` (lazily loaded via `Block::getInstance($this->definition->block)`). The `getBlockId()` / `getBlockName()` methods preserve their public API (lines 199–242 of today). |
| External package code type-hints `vtlib\Field` or `vtlib\FieldBasic` | Phase 3d gate grep catches this before deletion. If any package code hits, treat as blocker; either provide a temporary `class_alias` shim (against project rules — see `general-guidelines.mdc`, no `class_alias`) or migrate the package code. The clean path: migrate. |
| Migration scripts (`migrations/Users/*`) | They call `\App\Cache\Cache::delete('ModuleFields', self::TABID)` only — no direct `vtlib\Field` or `FieldService` usage (verified by grep). Safe across all phases. |

---

## Deliverables

### Implementation phases

| # | Task | Risk | Files |
|---|------|------|-------|
| 1.1 | Create `src/Field/FieldDefinition.php` (DTO + `fromRow()` + `toRow()` + `with()`) | None (additive) | New file |
| 1.2 | Unit test for `FieldDefinition::fromRow()` round-trip + `toRow()` write-compat | None | New test file |
| 2.0 | Re-verify Phase 2 pre-condition gate (broad grep for any hard-coded FQCN to module Field) | None | grep only |
| 2.1 | Generate Phase 2 audit inventory table | None | Inline in this doc or companion file |
| 2.2 | Delete confirmed-empty subclasses | Low | ~25 files in `src/Modules/*/Models/Field.php` |
| 3a.1 | Add `FieldDefinition $definition` field and constructor to `Base\Models\Field` | Medium | `src/Modules/Base/Models/Field.php` |
| 3a.2 | Add `static fromRow()`; rewrite `initialize()` callers | Medium | Same + `getInstanceFromFieldId()` |
| 3a.3 | Remove magic `get()`/`set()` (lines 60–78); migrate all `$this->get('X')` and `$this->set('X', Y)` to typed access | **High** (wide internal touch) | `Base\Models\Field` |
| 3a.4 | Migrate direct property reads (`$this->defaultvalue`, `$this->generatedtype`, etc.) to `$this->definition->X` | Medium | `Base\Models\Field` |
| 3a.5 | Rewrite `updateMandatory()` to use `$this->definition->with(['mandatory' => ...])` | Low | `Base\Models\Field` |
| 3a.6 | Remove `extends \vtlib\Field`; re-implement inherited statics (`getInstance`, `getAllForModule`, `getAllForBlock`, `deleteForModule`) directly | High | `Base\Models\Field` |
| 3a.7 | Deploy hook: bust `'ModuleFields'`, `'field-*'`, `'FieldModel'` caches on deploy | Low | Deploy script / migration |
| 3b.1 | Implement `static create(int $moduleId, int $blockId, FieldDefinition $def): self` (transactional INSERT + ALTER TABLE + initProfile + cache bust) | **High** (write path with DDL) | `Base\Models\Field` |
| 3b.2 | Implement instance `delete(): void` (cascade delete + cache bust) | Medium | `Base\Models\Field` |
| 3b.3 | Extend `save()` / `__update()` to cover every editable column via `$this->definition->toRow()` | Medium | `Base\Models\Field` |
| 3b.4 | Implement `setRelatedModules()` / `unsetRelatedModules()` instance methods (UIType 10) | Low | `Base\Models\Field` |
| 3c.1 | Migrate `Settings/LayoutEditor/Models/Field.php:29` to use `Base\Models\Field::create()` and `save()` directly | Medium | 1 file |
| 3c.2 | Migrate `ModuleManagement/Adapters/Block.php:199,236,286,317` to use `Base\Models\Field` directly | Medium | 1 file |
| 3c.3 | Migrate `PackageService.php:958,2477` to `Base\Models\Field::create()` | Medium | 1 file |
| 3c.4 | Migrate `ModuleService.php:504`, `BlockService.php:221` to `Base\Models\Field` static methods | Low | 2 files |
| 3c.5 | Delete `src/ModuleManagement/Services/FieldService.php` | Low (after migrations) | -1 file |
| 3c.6 | Remove `getFieldService()` slot from `ServiceLocator.php:49–55` | None | 1 file |
| 3c.7 | Gate grep: `rg "getFieldService\("` must return 0 hits | None | grep only |
| 3d.1 | Delete `src/ModuleManagement/Adapters/Field.php` | Low | -1 file |
| 3d.2 | Delete `src/ModuleManagement/Adapters/FieldBasic.php` | Low | -1 file |
| 3d.3 | Delete `src/ModuleManagement/Models/Field.php` | Low | -1 file |
| 3d.4 | Gate greps: `rg "vtlib\\\\Field"`, `rg "ModuleManagement\\\\Models\\\\Field"` must return 0 hits | None | grep only |

Net file delta after MVP: **+1 file (`FieldDefinition.php`), -27 files (~25 empty subclasses + 3 legacy adapters)**.

### Architecture diagram

(See §Architecture above for BEFORE/AFTER.)

---

## Tradeoffs

| Decision | Rationale |
|----------|-----------|
| Composition over inheritance for `Base\Models\Field` | Inheritance chain `Base\Models\Field → vtlib\Field → vtlib\FieldBasic` exists only for property bag — no Liskov-substitutable polymorphism. Composing a DTO removes 28 untyped properties and gives one source of truth. |
| Delete `FieldService` instead of keeping it | The codebase doesn't use a service layer anywhere else (`Base\Models\Field` already has `save()`, `__update()`, `static getInstance()`). Keeping `FieldService` perpetuates pattern inconsistency for no gain (no DI, no testing seam in active use). |
| Persistence onto `Base\Models\Field` even though it's already a 1400-line class | Matches existing Active Record pattern. Trait-extraction is left available if the file grows uncomfortable in Phase 3b. |
| `FieldDefinition` as `final class` | Fields don't need polymorphism at the data level; behaviour belongs on `Base\Models\Field` and uitype classes. |
| `FieldDefinition` in `App\Field` namespace (not `FreeCRM\Field`) | Workspace rule `project-identity.mdc` aspires to `FreeCRM\*` as the primary namespace, but the entire surrounding codebase uses `App\*`. Consistency with neighbours wins; a project-wide namespace migration is a separate concern. |
| PHP 8.1 `readonly` properties over getters | Eliminates 28 getter methods; readonly is enforced by the runtime. `with()` provides clone-and-replace semantics for the rare cases where the DTO needs to be updated. |
| Keep `WebserviceField` unchanged | Separate consumer with its own test surface; Phase 4 has a different risk profile. Deferring keeps this MVP focused even though Phase 4 is the obvious follow-up. |
| Block instance resolution stays separate from `FieldDefinition` | DTO holds only the FK (`?int $block`); `Block::getInstance($fk)` resolves the Block instance lazily on `Base\Models\Field` when needed. Eager resolution inside the DTO would require a `BlockService` dependency in the DTO — wrong layering. |

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Phase 3a removal of magic `get()`/`set()` breaks Smarty templates and undiscovered callers | **High** | Phase 3a includes a workspace-wide grep for `\$\w+->get\('` and `\$\w+->set\('` on Field-typed variables; full smoke pass on EditView, DetailView, ListView, Layout Editor before merge. |
| `Base\Models\Field` becomes too large (already 1400 lines + persistence code) | Medium | Extract persistence into `Base\Models\FieldPersistenceTrait` if file growth exceeds ~200 LOC. Trait is invisible to callers. |
| Module package install (`PackageService.php`) creates fields via `FieldService::create()` — migration breaks module install | **High** | Phase 3c migration of `PackageService` is paired with a real module-install smoke test (install one fresh module from a zip, verify all fields land in `vtiger_field` with correct columns). |
| Cron jobs or migrations reference deleted classes | Medium | Phase 3d gate grep covers `migrations/`, `cron/`, `tools/`, `installer/`. |
| `'ModuleFields'` / `'field-*'` / `'FieldModel'` cache contains pre-Phase-3a object shape across deploy | Medium | Deploy procedure clears all field caches as the first step of Phase 3a deploy. |
| `bool` → `tinyint(1)` write under STRICT_TRANS_TABLES | Medium | Centralised in `FieldDefinition::toRow()`. Every write path goes through it. |
| Default-value reconciliation (presence 1, generatedtype 0, readonly false) breaks an unknown read path | Medium | Audit before merge: grep for code that defaults missing values to the legacy PHP defaults (e.g., `?? 2` for presence). Each hit either accepts the new DB-aligned default or is explicitly migrated. |
| Phase 2 grep misses static or string references | Low | Broadened regex (`rg "App\\\\Modules\\\\X\\\\Models\\\\Field"` — no leading `new`) catches static calls, string class names, attributes. |
| `WebserviceField` is touched indirectly through `Base\Models\Field::getWebserviceFieldObject()` | Low | Verified: that method (line 311) constructs a `WebserviceField::fromArray()` from `$this->get('uitype')`, etc. Migrate the `$this->get(...)` calls inside that method to typed accessors. `WebserviceField` itself is unchanged. |
| Surviving Settings/LayoutEditor subclasses with non-trivial logic break | Low | Phase 2 audit table explicitly preserves subclasses with logic. Settings sub-modules (under `src/Modules/Settings/*/Models/Field.php`) are **explicitly excluded** from the Phase 2 deletion grep. |

---

## Testing plan

### Phase 1 (additive DTO)

- **Unit**: `FieldDefinition::fromRow()` round-trip. Feed a real `vtiger_field` row (`SELECT * FROM vtiger_field WHERE fieldid = <known>`) into `fromRow()`; assert every property matches the row column. This is the test that would have caught the `quicksequence`/`quickcreatesequence` bug.
- **Unit**: every key in `fromRow()` matches an actual column name in `scheme.sql:5218`. This is the cross-reference test.
- **Unit**: `toRow()` produces a `(int)`-cast write-compatible array for `readonly` and `mandatory`.
- **Unit**: `with()` returns a new instance with the changed field and all others preserved.

### Phase 2 (delete empty subclasses)

Per deleted subclass `X`:

- **Regression greps** (must return zero hits AFTER deletion):
  - `rg "use App\\\\Modules\\\\{X}\\\\Models\\\\Field"`
  - `rg "App\\\\Modules\\\\{X}\\\\Models\\\\Field"` (broad — catches static + string)
- **Smoke**: load EditView + DetailView for module `X`; confirm the Loader fallback returns `Base\Models\Field` without error.
- **Log check**: tail `cache/logs/system.log` during smoke; any `Class not found` or `Handler not found` is a regression.

### Phase 3a (composition + magic-method removal)

- **Smoke**: full EditView + DetailView + ListView + Quick Create + Mass Edit for one standard module (Accounts) — covers most reads of `Base\Models\Field` properties.
- **Smoke**: Settings → Layout Editor → edit a field's mandatory flag, save, reload — exercises `updateMandatory()` + `save()` with the new `with()`-based clone-and-replace.
- **Smoke**: Smarty templates rendering field metadata — verify no PHP warnings in `cache/logs/system.log` about removed `get()`/`set()`.
- **Smoke**: cache clear, then full smoke pass — verifies cache repopulates with the new object shape.

### Phase 3b (persistence on Base\Models\Field)

- **Smoke**: Layout Editor → create a new field (string, picklist, reference, currency — at least one of each major type). Verify INSERT into `vtiger_field` + ALTER TABLE adds the column. Compare the resulting `vtiger_field` row against the old `FieldService::create()` output for the same input.
- **Smoke**: Layout Editor → delete a field. Verify cascade delete to `vtiger_fieldmodulerel` for UIType 10.
- **Smoke**: edit each writable column via Layout Editor → save → verify DB row reflects the change.
- **Regression**: install a module package via Settings → Modules Manager → confirm all fields land correctly (exercises `PackageService` migration path).

### Phase 3c (delete FieldService)

- **Greps** (all must return zero hits): `rg "getFieldService\("`, `rg "Services\\\\FieldService"`, `rg "use App\\\\ModuleManagement\\\\Services\\\\FieldService"`.
- **Smoke**: same suite as 3b — verifies migrated call sites work.

### Phase 3d (delete legacy adapters)

- **Greps**: `rg "vtlib\\\\FieldBasic"`, `rg "vtlib\\\\Field\b"`, `rg "ModuleManagement\\\\Models\\\\Field"` — all must return zero hits.
- **Smoke**: full smoke (EditView, DetailView, Layout Editor create+edit+delete) one more time.
- **External**: confirm cron jobs (`cron/vtigercron.php`) and any background workers run cleanly for one cycle.

---

## Rollback

| Phase | Code rollback | External state | Acceptable data loss |
|-------|---------------|----------------|---------------------|
| Phase 1 | `git revert <commit>` | None — DTO is unreferenced | None |
| Phase 2 | `git revert <commit>` | None — deleted subclasses were unreferenced (verified by gate grep) | None |
| Phase 3a | `git revert <commit>` + manual cache clear (`rm -f cache/templates_c/*.php`, clear APCu) | Cache shape mismatch with pre-3a code; cache clear resolves it | None |
| Phase 3b | `git revert <commit>` | None — new methods are additive; `save()`/`__update()` extension is backward-compatible | None — UPDATEs continue to work (just write fewer columns under the old code) |
| Phase 3c | `git revert <commit>` of the delete; restore `FieldService.php` and `ServiceLocator` entry | All migrated call sites now reference the missing service — revert must include all 3c.1–3c.4 migration commits, or partial state will break | None — DB writes pre- and post-3c are functionally identical |
| Phase 3d | `git revert <commit>` of the deletes; restore `vtlib/*` and `ModuleManagement/Models/Field.php` | If external packages added new code against `vtlib\Field` between phase 3d and rollback, those references will break; require workspace grep at rollback time | None |

All phases are independently rollback-safe via `git revert` of the relevant
commits. Cache invalidation is the only non-code state change; it is a single
command (`Cache::deleteAll()` or `rm -rf cache/*/*`).

---

## Future improvements (out of MVP)

- **Phase 4** — `WebserviceField` becomes a projection: accepts `FieldDefinition`, no duplicated properties. Enables a single canonical type for webservice consumers.
- **Phase 5** — Install seed data (`data.sql` + `Base2.php`) consolidated into a single PHP seed class that emits `FieldDefinition` instances.
- **Service-layer cleanup** — apply the same "delete the Service, push onto the model" treatment to `BlockService`, `ModuleService`, `PackageService` (separate refactors; each has its own blast radius).
- **`Base\Models\Field` trait split** — if the class grows beyond ~1600 lines after Phase 3b, extract `FieldPersistenceTrait`, `FieldDisplayTrait`, etc.

---

## Change log

| Date | Author | Change |
|------|--------|--------|
| 2026-05-26 | bmankowski | Initial MVP document |
| 2026-05-26 | bmankowski | Revision per `field-definition-cr-review.md`: removed phantom `setDefinition()`; redrew AFTER diagram to keep `Base\Models\Field` unchanged; corrected `FieldService` method names; added `Block.php::addField()` and `createVtlibFieldInstance()` to retirement scope; renamed `quicksequence` → `quickcreatesequence`; documented `readonly`/`mandatory` int→bool type change; documented presence default divergence; added Phase 2 pre-condition gate; added testing and rollback sections. |
| 2026-05-26 | bmankowski + review | **Major revision (option c1)**: scope expanded to include Phase 3 (composition + delete `vtlib\Field` / `vtlib\FieldBasic` / `ModuleManagement\Models\Field`). `FieldService` deleted per architectural principle (no service layer in this codebase). Phase 3 split into 3a/3b/3c/3d. DTO expanded with 5 missing columns (`sequence`, `block`, `header_field`, `maxlengthtext`, `maxwidthcolumn`) + `with()` + `toRow()`. Default-value discrepancies for `readonly`, `generatedtype`, `quickcreatesequence` reconciled DB-side. Cache invariant under deploy documented. Complete `getFieldService()` consumer migration table added. Constructor arg count corrected to 28. |
