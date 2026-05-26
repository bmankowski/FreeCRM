# `vtiger_field.typeofdata` — Current State & Refactoring Plan

## Status

| Change | Status |
|--------|--------|
| Change 1 — `mandatory` column | **Done** (2026-05-26) |
| Change 2 — strip `LE~n` from `typeofdata` | Planned |
| Change 3 — cross-field constraints table | Deferred |
| Change 4 — numeric precision encoding | Deferred |

Deploy **migration before** PHP on each environment: `yii migrate --migrationPath=migrations/Users/`

---

## 1. Current state

### 1.1 Table columns (relevant)

```sql
`readonly` tinyint(1) unsigned NOT NULL,
`mandatory` tinyint(1) unsigned NOT NULL DEFAULT 0,   -- authoritative for required fields
`presence` tinyint(1) unsigned NOT NULL DEFAULT 1,
`typeofdata` varchar(100) DEFAULT NULL,               -- segment 2 (M/O) kept in sync for backward compat
```

Index: `field_mandatory_idx` on `mandatory`.

Fresh installs: [`src/Modules/Install/install_schema/scheme.sql`](../src/Modules/Install/install_schema/scheme.sql).  
Existing DBs: [`migrations/Users/m260526_000001_vtiger_field_mandatory.php`](../migrations/Users/m260526_000001_vtiger_field_mandatory.php).

### 1.2 `typeofdata` format

`typeofdata` is a tilde-separated (`~`) string that still packs several facts into one column:

```
<type_code> ~ <mandatory_flag> [~ <constraint_type> ~ <operator> ~ <ref_field> ~ <ref_label>]
```

Segment 2 (`M` / `O`) remains written on field save alongside `mandatory` until a later cleanup pass.

This still violates **First Normal Form** for type code, constraints, and length/precision encodings — only the mandatory flag has been normalized out.

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

#### Segment 2 — mandatory flag (legacy mirror)

| Code | Meaning   | `mandatory` column |
|------|-----------|-------------------|
| `M`  | Mandatory | `1`               |
| `O`  | Optional  | `0`               |

**Authoritative source:** `vtiger_field.mandatory`.  
**Legacy mirror:** `typeofdata` segment 2 — still updated by `Field::updateTypeofDataFromMandatory()` and persisted in `Field::__update()`.

`Field::isMandatory()` reads the column only:

```php
public function isMandatory(): bool
{
    return (bool) $this->get('mandatory');
}
```

Population at migration time (265 mandatory fields in dev DB):

```sql
UPDATE vtiger_field
SET mandatory = IF(SUBSTRING_INDEX(SUBSTRING_INDEX(typeofdata,'~',2),'~',-1) = 'M', 1, 0);
```

**Anomalous `D~0` rows:** fixed in the same migration (`typeofdata` → `D~O`); no longer present.

#### Segment 3–5 — optional cross-field validation constraint

Present on **17 fields** only. Used to declare that one field's value must be ≥ or > another field's value.

```
D~M~OTH~GE~date_start~Start Date & Time
     ───  ──  ─────────  ────────────────
      │    │    │              └── ref field label (display only)
      │    │    └── ref field name
      │    └── operator: GE (>=), G (>)
      └── constraint type: OTH = "other field"
```

Examples:
| Field | typeofdata | Meaning |
|-------|-----------|---------|
| `due_date` | `D~M~OTH~GE~date_start~Start Date & Time` | end ≥ start |
| `time_end` | `T~M~OTH~GE~time_start~LBL_TIME_START` | end time ≥ start time |
| `support_end_date` | `D~O~OTH~GE~support_start_date~Support Start Date` | end ≥ start |
| `sales_end_date` | `D~O~OTH~GE~sales_start_date~Sales Start Date` | end ≥ start |

#### Segments 3–4 (alternative) — inline length constraint

Present on **29 fields**. Used on varchar custom fields created through the field editor.

```
V~O~LE~100
     ──  ───
      │    └── max length value
      └── operator: LE (≤)
```

**This is a duplicate of `maximumlength`.** Both columns store the same value for all but one field:

| fieldname | typeofdata | maximumlength | Drift? |
|-----------|-----------|---------------|--------|
| `cf_2610` | `V~O~LE~255` | 100 | **YES — drift** |
| all others | `V~O~LE~n` | n | No |

#### Segments 3–4 (alternative) — numeric precision

Present on **3 fields** (`progress`, `discount`, `probability`). Encodes `min_integer_digits~max_decimal_digits`:

```
N~O~2~2   → 2 decimal places
```

#### Special: `DT~M~time_start`

`date_start` fields (uitype 6) use segment 3 to name the **companion time column** that stores the time portion of the datetime:

```
DT~M~time_start
         └── companion time field name
```

---

### 1.4 Consumers of `typeofdata` and `mandatory`

| Consumer | Reads | How |
|----------|-------|-----|
| `Field::isMandatory()` | `mandatory` column | `(bool) $this->get('mandatory')` |
| `Field::getFieldInfo()` | via `isMandatory()` | `fieldInfo['mandatory']` for UI/JS |
| `Field::updateTypeofDataFromMandatory()` | writes both | sets `typeofdata` segment 2 **and** `mandatory` |
| `Field::__update()` | writes both | persists `typeofdata` + `mandatory` |
| `WebserviceField` constructor | `mandatory` column | prefers `row['mandatory']`; falls back to `typeofdata[1]` |
| `CRMEntity::initRequiredFields()` | `mandatory` column | `mandatory = 1` (replaces `typeofdata LIKE '%M%'`) |
| `Field::getFieldType()` | segment 1 | `explode('~', ...)[0]` |
| `Field::getCustomViewColumnName()` | segment 1 | then `transformFieldTypeOfData()` |
| `Field::getReportFilterColumnName()` | segment 1 | then `transformFieldTypeOfData()` |
| `WebserviceField::getFieldTypeFromTypeOfData()` | segment 1 | REST API type fallback |
| Module management (`FieldService`) | both | INSERT/UPDATE `mandatory`; `typeofdata` unchanged |
| `ModuleManagement/Adapters/Filter.php` | segment 1 | filter criteria type |

Templates and JS are unchanged — they call `isMandatory()` / `fieldInfo.mandatory`.

---

## 2. Problems summary

| Problem | Severity | Status |
|---------|----------|--------|
| 1NF violation (type, constraints, LE, precision in one string) | Medium | Open |
| No dedicated `mandatory` column | High | **Resolved** |
| `LE~n` duplicates `maximumlength` | Medium | Open (Change 2) |
| Anomalous `D~0` (digit zero) | Low | **Resolved** in migration |
| Cross-field constraints in string | Low | Deferred (Change 3) |

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
| `src/ModuleManagement/Models/Field.php` | `$mandatory`, `getMandatory()`, `mandatoryFromTypeofdata()` |
| `src/ModuleManagement/Adapters/FieldBasic.php` | `public $mandatory = 0` |
| `src/ModuleManagement/Adapters/Field.php` | maps `mandatory` on adapter instances |
| `src/Modules/Install/install_schema/scheme.sql` | column + index for new installs |

---

### Change 2 — Remove `LE~n` from `typeofdata` ⚠️ Recommended next

**Rationale:** `maximumlength` is the authoritative source. `LE~n` inside `typeofdata` is pure redundancy and has already drifted on one field.

**Migration steps:**

1. Verify and repair the one drifted field:
   ```sql
   -- cf_2610: typeofdata says LE~255 but maximumlength=100
   -- Decide which is authoritative, then fix. Likely maximumlength is correct.
   UPDATE vtiger_field SET typeofdata = 'V~O' WHERE fieldname = 'cf_2610' AND typeofdata = 'V~O~LE~255';
   ```

2. Strip `LE~n` from all other matching fields:
   ```sql
   UPDATE vtiger_field
   SET typeofdata = SUBSTRING_INDEX(typeofdata, '~LE~', 1)
   WHERE typeofdata LIKE '%~LE~%';
   ```

3. Remove any code that generates or reads `LE~n` from `typeofdata` in the field editor / module management.

---

### Change 3 — Cross-field constraints table 🔄 Deferred

**Rationale:** Only 17 fields use this. Works correctly today. A proper implementation would require a new `vtiger_field_constraints` table with its own read/write/export path. High effort, low return at this stage.

**Future schema sketch:**
```sql
CREATE TABLE vtiger_field_constraints (
  constraintid  int NOT NULL AUTO_INCREMENT,
  fieldid       int NOT NULL,
  constraint_type VARCHAR(10) NOT NULL,  -- 'OTH'
  operator      VARCHAR(5) NOT NULL,      -- 'GE', 'G'
  ref_fieldname VARCHAR(50) NOT NULL,
  ref_label     VARCHAR(100) DEFAULT '',
  PRIMARY KEY (constraintid),
  KEY (fieldid)
);
```

---

### Change 4 — Numeric precision encoding 🔄 Deferred

**Rationale:** Only 3 fields. Functional. Could eventually move to `fieldparams` JSON (already used for other per-field config) or a dedicated `decimal_places` column.

---

## 4. Implementation order

| # | Change | Status |
|---|--------|--------|
| 1 | Fix `D~0` anomaly | **Done** (in `m260526_000001` migration) |
| 2 | Add `mandatory` column + populate | **Done** |
| 3 | Update PHP consumers of mandatory flag | **Done** |
| 4 | Strip `LE~n` from `typeofdata` | Planned |
| 5 | Cross-field constraints table | Deferred |

---

## 5. Open questions

- [x] Should `typeofdata` segment 2 continue to be written during field save after `mandatory` column is added? **Yes** — dual-write until a dedicated cleanup pass.
- [ ] Is any external integration (REST API, package import/export) reading `typeofdata` raw and expecting the `M`/`O` flag to be present? (Unverified; dual-write mitigates risk.)
- [ ] For `cf_2610`: is `maximumlength=100` or `LE~255` the correct value?
- [ ] The `DT~M~time_start` companion field pattern — is this the only case or are there other `~field_name` appended variants?
- [ ] When to stop writing `typeofdata` segment 2 and rely on `mandatory` only?

---

## 6. Change log

| Date | Author | Change |
|------|--------|--------|
| 2026-05-26 | bmankowski | Initial draft |
| 2026-05-26 | bmankowski | Change 1 implemented: `mandatory` column, migration, PHP consumers, install schema; doc updated |
