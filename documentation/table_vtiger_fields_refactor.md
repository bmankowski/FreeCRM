# `vtiger_field.typeofdata` — Current State & Refactoring Plan

## Status

| Change | Status |
|--------|--------|
| Change 1 — `mandatory` column | **Done** (2026-05-26) |
| Change 2 — strip `LE~n` from `typeofdata` | **Done** (2026-05-26) |
| Change 3 — remove M/O segment from `typeofdata` | **Done** (2026-05-26) |
| Change 4 — cross-field constraints table + OTH strip | **Done** (2026-05-26) |
| Change 5 — numeric precision encoding (`N~2~2`) | **Done** (2026-05-26) |
| Change 6 — strip `DT~time_start` companion pattern | **Done** (2026-05-26) |
| Fix — self-referential OTH constraints (`vtiger_assets`) | **Done** (2026-05-26) |
| Change 7 — remove legacy `explode('~', typeofdata)` in PHP | **Done** (2026-05-26) |
| Change 8 — strip remaining ~M/~O suffix | **Done** (2026-07-03) |
| Change 9 — add `field_kind` + `storage_type` columns (backfill only) | **Done** (2026-07-03) |

Deploy **migration before** PHP on each environment: `yii migrate --migrationPath=migrations/Users/`

**Change 8 note:** `typeofdata` must be a single type token (`V`, `D`, `N`, …). The `mandatory` column is authoritative. New code must not write `~M`/`~O`; [`FieldDefinition::normalizeTypeofdata()`](src/Field/FieldDefinition.php) strips legacy suffixes at the persistence boundary.

**Change 9 note:** `field_kind` and `storage_type` are populated by migration `m260705_000001`. Runtime still reads `uitype` / `typeofdata` — wiring `Field::getUiTypeName()` and `getStorageType()` is a follow-up CR.

---

## 1. Current state

### 1.1 Table columns (relevant)

```sql
`readonly` tinyint(1) unsigned NOT NULL,
`mandatory` tinyint(1) unsigned NOT NULL DEFAULT 0,   -- authoritative for required fields
`presence` tinyint(1) unsigned NOT NULL DEFAULT 1,
`typeofdata` varchar(100) DEFAULT NULL,               -- single storage type code (V, D, N, …)
```

Index: `field_mandatory_idx` on `mandatory`.

Fresh installs: [`src/Modules/Install/install_schema/scheme.sql`](../src/Modules/Install/install_schema/scheme.sql).  
Existing DBs: [`migrations/Users/m260526_000001_vtiger_field_mandatory.php`](../migrations/Users/m260526_000001_vtiger_field_mandatory.php).

### 1.2 `typeofdata` format

`typeofdata` now contains **only the type-code token** (e.g. `V`, `D`, `N`). All multi-segment encodings have been removed:

| Segment | Description | Status |
|---------|-------------|--------|
| 1 | DB storage type code (`V`, `D`, `N`, `DT`, …) | **Active — only remaining segment** |
| 2 | Mandatory flag (`M`/`O`) | **Removed** (Change 3) — `mandatory` column is authoritative |
| 3–5 | OTH cross-field constraint | **Removed** (Change 4) — `vtiger_field_constraints` table |
| 3–4 | `LE~n` length constraint | **Removed** (Change 2) — `maximumlength` is authoritative |
| 3–4 | `2~2` numeric precision | **Removed** (Change 5) — DB column type is authoritative |
| 3 | `time_start` companion field name | **Removed** (Change 6) — uitype 6 handles this intrinsically |

`typeofdata` is now **fully First Normal Form** — one atomic value per row.

---

### 1.3 Segment breakdown

#### Segment 1 — DB storage type code

| Code | Meaning              | Typical DB column type |
|------|----------------------|------------------------|
| `V`  | Varchar / string     | `varchar`              |
| `C`  | Checkbox / boolean   | `tinyint`              |
| `I`  | Integer              | `int`                  |
| `N`  | Number (fixed dec.)  | `decimal`              |
| `NN` | Double / float       | `double`               |
| `D`  | Date                 | `date`                 |
| `DT` | Datetime             | `datetime`             |
| `T`  | Time                 | `time`                 |
| `E`  | Email                | `varchar`              |
| `P`  | Password             | `varchar`              |
| `M`  | Memo / long text     | `text`                 |

The type code is **not fully derivable from `uitype`**. The same uitype can appear with different type codes:

```
uitype 1  → V, N, NN, I  (text vs. number custom fields)
uitype 56 → C, I, V      (boolean stored in different column types)
uitype 15 → V (normal), but also T, D (anomalous legacy data)
```

#### Segment 2 — **removed**

The `M`/`O` mandatory flag has been stripped from `typeofdata` entirely (migration `m260526_000004`).  
`vtiger_field.mandatory` is the sole authority. Segment 2 no longer exists in any row.

`typeofdata` now contains only the `<type_code>` token (no `~` segments).

#### Segment 3–5 — OTH cross-field constraint *(removed — Change 4)*

Was present on 17 fields. Format: `D~M~OTH~GE~date_start~Start Date & Time`.  
All constraints migrated to `vtiger_field_constraints`. `typeofdata` rows stripped to type code only.

#### Segments 3–4 — `LE~n` length constraint *(removed — Change 2)*

Was present on 29 varchar fields. Format: `V~O~LE~100`.  
`maximumlength` column is authoritative. One drifted row (`cf_2610`) corrected.

#### Segments 3–4 — numeric precision *(removed — Change 5)*

Was present on 3 fields (`progress`, `discount`, `probability`). Format: `N~O~2~2`.  
Never read at runtime; DB column type is authoritative.

#### Segment 3 — `time_start` companion field name *(removed — Change 6)*

Was present on 2 `date_start` fields (uitype 6). Format: `DT~M~time_start`.  
uitype 6 handles the companion relationship intrinsically; segment was unused.

---

### 1.4 Consumers of `typeofdata` and `mandatory`

| Consumer | Reads | How |
|----------|-------|-----|
| `Field::isMandatory()` | `mandatory` column | `(bool) $this->get('mandatory')` |
| `Field::getFieldInfo()` | via `isMandatory()` | `fieldInfo['mandatory']` for UI/JS |
| `Field::updateMandatory()` | `mandatory` column only | no longer touches `typeofdata` |
| `Field::__update()` | persists `mandatory` + `typeofdata` | typeofdata no longer encodes M/O |
| `WebserviceField` constructor | `mandatory` column | `(bool)($row['mandatory'] ?? false)` |
| `CRMEntity::initRequiredFields()` | `mandatory` column | `mandatory = 1` |
| `Field::getFieldType()` | type code | `typeofdata` directly |
| `Field::getCustomViewColumnName()` | type code | then `transformFieldTypeOfData()` |
| `Field::getReportFilterColumnName()` | type code | then `transformFieldTypeOfData()` |
| `WebserviceField::getFieldTypeFromTypeOfData()` | type code | REST API type fallback |
| Module management (`FieldService`) | both | INSERT/UPDATE `mandatory`; `typeofdata` unchanged |
| `ModuleManagement/Adapters/Filter.php` | type code | filter criteria type |

Templates and JS are unchanged — they call `isMandatory()` / `fieldInfo.mandatory`.

---

## 2. Problems summary

| Problem | Severity | Status |
|---------|----------|--------|
| 1NF violation (type, constraints, LE, precision in one string) | Medium | **Fully resolved** — `typeofdata` is now a single type-code token for every row |
| No dedicated `mandatory` column | High | **Resolved** |
| `LE~n` duplicates `maximumlength` | Medium | **Resolved** |
| Anomalous `D~0` (digit zero) | Low | **Resolved** in migration |
| Cross-field constraints in string | Low | **Resolved** (Change 4) |
| Numeric precision encoding (`N~2~2`) unused | Low | **Resolved** |
| Self-referential OTH constraints (`vtiger_assets`) | Low | **Resolved** |

---

## 3. Suggested changes

### Change 1 — Add dedicated `mandatory` column ✅ Done

**Migration:** `migrations/Users/m260526_000001_vtiger_field_mandatory.php`

1. `ADD COLUMN mandatory tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER readonly`
2. Backfill from `typeofdata` segment 2
3. `UPDATE … SET typeofdata = 'D~O' WHERE typeofdata = 'D~0'`
4. `CREATE INDEX field_mandatory_idx (mandatory)`

**Dual-write policy:** Layout Editor and field saves still update `typeofdata` segment 2 (`M`/`O`) and the `mandatory` column together. External tools that read raw `typeofdata` keep working.

**Files updated:**

| File | Change |
|------|--------|
| `src/Modules/Base/Models/Field.php` | `isMandatory()`, `__update()`, `updateTypeofDataFromMandatory()` |
| `src/Webservices/WebserviceField.php` | constructor prefers `mandatory` column |
| `src/Core/CRMEntity.php` | `initRequiredFields()` uses `mandatory = 1` |
| `src/ModuleManagement/Services/FieldService.php` | INSERT/UPDATE + loaders |
| `src/ModuleManagement/Models/Field.php` | `$mandatory`, `getMandatory()` |
| `src/ModuleManagement/Adapters/FieldBasic.php` | `public $mandatory = 0` |
| `src/ModuleManagement/Adapters/Field.php` | maps `mandatory` on adapter instances |
| `src/Modules/Install/install_schema/scheme.sql` | column + index for new installs |

---

### Change 2 — Remove `LE~n` from `typeofdata` ✅ Done

**Migration:** `migrations/Users/m260526_000002_typeofdata_strip_le.php`

1. Fixed drifted `cf_2610` (`V~O~LE~255` → `V~O`; `maximumlength=100` is authoritative).
2. Stripped `LE~n` from all 29 affected rows via `SUBSTRING_INDEX`.
3. `LayoutEditor/Models/Module.php` — `getTypeDetailsForAddField()` for `Text` type no longer embeds `LE~n`; `maximumlength` is the only length store.
4. Seed data (`data.sql`, `Base2.php`) updated for the 5 affected fields.

---

### Change 4 — Cross-field constraints table ✅ Done

**Migrations:** `m260526_000007_vtiger_field_constraints.php`

All OTH cross-field comparison constraints have been extracted from `typeofdata` into a dedicated `vtiger_field_constraints` table. `typeofdata` is now a single type-code token for every field.

**Schema:**
```sql
CREATE TABLE vtiger_field_constraints (
    id            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    fieldid       INT(10) UNSIGNED NOT NULL,
    operator      ENUM('GE','G','LE','L') NOT NULL COMMENT 'GE>=  G>  LE<=  L<',
    ref_fieldname VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_fieldid (fieldid)
);
```

**26 rows** covering:
- 16 direct GE/G constraints (migrated from `typeofdata` OTH segments)
- 6 LE inverse constraints (migrated from the hardcoded `getValidator()` switch)
- 1 additional LE (`end_period <= duedate`) from `getValidator()`
- 3 bug-fix rows previously missing from both sources (`dateinservice`, `time_start` in osstimecontrol and reservations)

**`Field::getValidator()` rewritten** — replaced the hardcoded switch with a table-driven lookup. Loads the full `vtiger_field_constraints` table once per request into a static cache, then serves all fields from memory. Single-field validators (`lessThanToday`, `PositiveNumber`, `WholeNumber`, `ReferenceField`) remain as a static in-memory map.

**Files updated:**
| File | Change |
|------|--------|
| `migrations/Users/m260526_000007_vtiger_field_constraints.php` | CREATE TABLE + 26 INSERTs + UPDATE typeofdata strip |
| `src/Modules/Base/Models/Field.php` | `getValidator()` rewritten; `getFieldConstraints()` helper added |
| `src/Modules/Calendar/Models/Field.php` | removed now-redundant `getValidator()` override (due_date case covered by table) |
| `src/Modules/Reservations/Models/Field.php` | added `: array` return type to `getValidator()` |
| `src/Modules/OSSTimeControl/Models/Field.php` | added `: array` return type to `getValidator()` |
| `src/Modules/SSalesProcesses/Models/Field.php` | added `: array` return type to `getValidator()` |
| `src/Modules/Install/install_schema/scheme.sql` | `CREATE TABLE vtiger_field_constraints` added |
| `src/Modules/Install/install_schema/data.sql` | 17 typeofdata rows stripped + 26 constraint rows seeded |
| `src/Modules/Install/install_schema/Base2.php` | same + `data2()` method for batch insert |

**Bugs found during testing:**

1. **Fatal error on Calendar EditView** — `Calendar\Models\Field::getValidator()` declared without `: array` return type, incompatible with the base class signature. Fixed by removing the override entirely (the table-driven base class now produces the identical result for `due_date`).

2. **Silent constraint lookup returning nothing** — `getFieldConstraints()` was called with `$this->get('fieldid')`, but the field model's `initialize()` maps the DB column `fieldid` to the key `'id'`. The lookup always received `0` and returned no constraints. Fixed to `$this->get('id')`.

3. **Latent fatal errors** — Three other Field subclasses (`Reservations`, `OSSTimeControl`, `SSalesProcesses`) had the same missing `: array` return type. Fixed preemptively.

### Change 6 — Strip `DT~time_start` companion pattern ✅ Done

**Migration:** `m260526_000006_typeofdata_strip_dt_companion.php`

The two `date_start` fields in `vtiger_activity` carried `DT~time_start` — a companion time-field name appended to the type code. No PHP code reads this segment (uitype 6 handles the companion relationship intrinsically). Stripped to `DT`.

---

### Change 5 — Numeric precision encoding ✅ Done

**Migration:** `migrations/Users/m260526_000005_typeofdata_strip_numeric_precision.php`

3 fields (`progress`, `discount`, `probability`) carried `N~2~2` in `typeofdata`. The `2~2` segments (min_integer_digits, max_decimal_digits) were written by the LayoutEditor `Percent` branch but **never read** by any PHP runtime code. The DB column type (`decimal(5,2)`) already encodes precision authoritatively.

1. Stripped `~2~2` from all 3 rows — `typeofdata` is now `N`.
2. `LayoutEditor/Models/Module.php` `Percent` branch updated to emit `N` only.
3. Seed data (`data.sql`, `Base2.php`) updated for all 3 fields.

Also bundled in the same migration: fix of self-referential OTH constraints (see section below).

---

### Fix — Self-referential OTH constraints (`vtiger_assets`) ✅ Done

Pre-existing data bug: both `datesold` and `dateinservice` in `vtiger_assets` had OTH constraints that referenced themselves (e.g. `D~OTH~GE~datesold~Date Sold` — "date sold ≥ date sold"), which is a no-op.

Corrected values:

| Field | Before | After |
|-------|--------|-------|
| `datesold` | `D~OTH~GE~datesold~Date Sold` | `D~OTH~GE~dateinservice~Date in Service` |
| `dateinservice` | `D~OTH~GE~dateinservice~Date in Service` | `D` |

Logic: an asset is sold *after* it enters service (`datesold ≥ dateinservice`). `dateinservice` itself has no cross-field lower bound.

Bundled in `migrations/Users/m260526_000005_typeofdata_strip_numeric_precision.php`.

---

### Change 7 — Remove legacy `explode('~', typeofdata)` in PHP ✅ Done

No migration — DB already has single-token `typeofdata` only (0 rows with `~`).

1. Replaced all `explode('~', …)[0]` reads with the `typeofdata` string directly.
2. Removed deprecated `ModuleManagement\Models\Field::mandatoryFromTypeofdata()`.
3. Simplified report column-total numeric check (`in_array` on `N`/`I`/`NN`; dropped dead `~2~2` branch).
4. Fixed `ReportRun.php` bug: `$typeofdata[0]` on a string used only the first character (`DT` → `D`).

**Files updated:**

| File | Change |
|------|--------|
| `src/Modules/Base/Models/Field.php` | `getFieldType()`, `getCustomViewColumnName()`, `getReportFilterColumnName()` |
| `src/Webservices/WebserviceField.php` | constructor; comment `V~O` → `V` |
| `src/ModuleManagement/Adapters/Filter.php` | `getColumnValue()` |
| `src/ModuleManagement/Services/FilterService.php` | `getColumnValue()` |
| `src/ModuleManagement/Models/Field.php` | `$mandatory ?? 0`; removed `mandatoryFromTypeofdata()` |
| `src/Modules/Settings/LayoutEditor/Models/Field.php` | field delete cleanup |
| `src/Modules/Reports/Reports.php` | advance filter + column totals |
| `src/Modules/Reports/ReportRun.php` | standard date filter + `transformFieldTypeOfData()` arg |

---

## 4. Implementation order

| # | Change | Status |
|---|--------|--------|
| 1 | Fix `D~0` anomaly | **Done** |
| 2 | Add `mandatory` column + populate | **Done** |
| 3 | Update PHP consumers of mandatory flag | **Done** |
| 4 | Strip `LE~n` from `typeofdata` | **Done** |
| 5 | Strip `N~2~2` precision encoding + fix OTH self-ref | **Done** |
| 6 | Strip `DT~time_start` companion pattern | **Done** |
| 7 | Cross-field constraints table + strip OTH + rewrite `getValidator()` | **Done** |
| 8 | Remove legacy `explode('~', typeofdata)` in PHP | **Done** |

---

## 5. Open questions

- [x] Should `typeofdata` segment 2 continue to be written during field save after `mandatory` column is added? **Resolved** — segment 2 (M/O) has been fully removed (Change 3). `mandatory` column is the sole authority.
- [x] Is any external integration (REST API, package import/export) reading `typeofdata` raw and expecting the `M`/`O` flag to be present? **Moot** — segment 2 is gone (Change 3 done). If an integration breaks, it should be updated to read the `mandatory` column.
- [x] For `cf_2610`: is `maximumlength=100` or `LE~255` the correct value? **`maximumlength=100` is authoritative.** The `LE~255` was drift; resolved in Change 2. DB confirmed: `typeofdata=V`, `maximumlength=100`.
- [x] The `DT~M~time_start` companion field pattern — is this the only case or are there other `~field_name` appended variants? **Confirmed only case.** DB shows exactly 2 rows (`date_start` / `vtiger_activity`), both now `DT~time_start` after M/O strip. No other `~field_name` appended variants exist.
- [x] When to stop writing `typeofdata` segment 2 and rely on `mandatory` only? **Done** — Change 3 removed all M/O segments.

---

## 6. Change log

| Date | Author | Change |
|------|--------|--------|
| 2026-05-26 | bmankowski | Initial draft |
| 2026-05-26 | bmankowski | Change 1 implemented: `mandatory` column, migration, PHP consumers, install schema; doc updated |
| 2026-05-26 | bmankowski | Change 2 implemented: `LE~n` stripped from all 29 rows, generator fixed, seed data updated |
| 2026-05-26 | bmankowski | Change 3 implemented: M/O removed from typeofdata in DB and all PHP/seed files; `updateMandatory()` replaces dual-write method |
| 2026-05-26 | bmankowski | Change 5 implemented: `N~2~2` stripped from 3 fields (progress, discount, probability); LayoutEditor Percent branch simplified; seed data updated |
| 2026-05-26 | bmankowski | Fix: self-referential OTH constraints on vtiger_assets corrected (datesold → references dateinservice; dateinservice → plain D); all open questions closed |
| 2026-05-26 | bmankowski | Change 6: DT~time_start companion stripped (2 rows); migration 006; seed data updated |
| 2026-05-26 | bmankowski | Change 4+7: vtiger_field_constraints table created (26 rows, ENUM GE/G/LE/L); all OTH segments stripped from typeofdata; Field::getValidator() rewritten as data-driven lookup with request-level cache; 3 previously missing validators added (dateinservice, time_start×2); typeofdata is now fully 1NF |
| 2026-05-26 | bmankowski | Testing bug fixes: (1) Calendar\Models\Field::getValidator() removed (missing return type + now redundant); (2) getFieldConstraints() fixed to use $this->get('id') not 'fieldid' (model key mapping); (3) Reservations/OSSTimeControl/SSalesProcesses Field::getValidator() return types added |
| 2026-05-26 | bmankowski | Change 7: removed all `explode('~', typeofdata)` in PHP; `mandatoryFromTypeofdata()` deleted; report column-total check simplified; fixed ReportRun `$typeofdata[0]` string-index bug (`DT` fields treated as `D`) |
