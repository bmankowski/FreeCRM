# Change Request: RecruitmentApplication — CV JSON cron import

## Goal

Introduce a **cron-based import** that reads website CV/application JSON files from `import/cv/pending/` and persists **one `RecruitmentApplication` record per submission**, with links to `Kandydaci`, `ProjektyRekrutacyjne`, and `Documents`, using the field model defined in `documentation/module/RecruitmentApplication.md`.

Until now, the same JSON pipeline created/updated **`Kandydaci`** only (`src/Modules/Kandydaci/Crons/ScheduledImport.php`). This CR moves orchestration to `RecruitmentApplication` as the system of record for a single submission, while preserving recruitment workflows that still depend on candidates and project relations.

**Reference implementation (actual import today):** `Kandydaci\Crons\ScheduledImport` + `Kandydaci\Cron\ScheduledImportTask` 

---

## Stance

- **No parallel import paths:** one importer, one dedup key (`application_number` on `RecruitmentApplication`), one pending-directory loop.
- **Extract and delete:** parsing/file/orchestration logic moves out of `Kandydaci\Crons\ScheduledImport`; the Kandydaci class is reduced to candidate/project side-effects or deleted once call sites point to the new importer.
- **No `class_alias()`**, no “write to both modules” long term — Kandydaci updates remain only where recruitment still requires them (candidate record, project relation, CV on candidate).
- **Preserve production data** and website behaviour; destructive changes only where justified and documented.

---

## Assumptions

| # | Assumption |
|---|------------|
| A1 | Website still drops JSON (+ CV file) into `import/cv/pending/` on the app server (`/var/www/import/cv/` in Docker/prod). |
| A2 | `application_number` in `vtiger_recruitmentapplication` is the business unique key (filename token per module doc). |
| A3 | Recruitment staff still need **`Kandydaci`** records and **project relations** after import; removing that is out of scope. |
| A4 | Dev DB has **0** `RecruitmentApplication` rows and **~6382** `Kandydaci` rows with `application_id` (prod-like dump); prod cron row for Kandydaci import may exist outside `install_schema/data.sql`. |
| A5 | Both JSON shapes remain in the wild: **JetFormBuilder** (top-level keys) and **legacy MetForm** (`entries` + `file_uploads`) — see module doc § CV JSON sources. |

---

## Impact

### Code being modified

| Path | Change | Observable? |
|------|--------|---------------|
| `src/Modules/RecruitmentApplication/` | New `Crons/`, `Cron/`, optional `Services/` | Internal |
| `src/Modules/Kandydaci/Crons/ScheduledImport.php` | Remove pending-import orchestration; keep only what is still needed for TalentDays PDF import (`importAllCandidatesFromFolder`) or move that too if touched | Internal |
| `src/Modules/Kandydaci/Cron/ScheduledImportTask.php` | Delegate to new importer **or** delete after cron reassigned | Internal |
| `src/Modules/Kandydaci/Actions/ImportCandidatesManually.php` | Call new importer entry point | UI (manual trigger) |
| `src/Modules/Kandydaci/Workflows/RunImportCandidatesWorkflow.php` | Same | Workflow |
| `src/Modules/Kandydaci/scripts/importNewCandidates.php` | Same | CLI/script |
| `src/Modules/Kandydaci/scripts/importNewCandidatesFromFolder.php` | Review; update if it calls JSON import | Script |
| `documentation/module/RecruitmentApplication.md` | Status + import section | Docs |
| `languages/en_us/RecruitmentApplication.json`, `languages/pl_pl/RecruitmentApplication.json` | Cron label `LBL_SCHEDULED_CV_IMPORT` (or similar) | Admin cron UI |
| `languages/en_us/Kandydaci.json`, `languages/pl_pl/Kandydaci.json` | Only if cron name moves / manual action label changes | Admin |

### Code being deleted (same CR — final steps)

| Path | Reason |
|------|--------|
| `ScheduledImport::importNewCandidates()` body (and helpers only used by it) in `Kandydaci/Crons/ScheduledImport.php` | Replaced by RecruitmentApplication importer |
| `ScheduledImport::isApplicationInDatabase()` (Kandydaci `application_id` dedup) | Replaced by `application_number` on `RecruitmentApplication` |
| Duplicate parsers if fully moved: `getApplicationData()`, `getApplicationDataOld()` from Kandydaci class | Live in shared parser |
| `src/Modules/Kandydaci/Cron/ScheduledImportTask.php` | **If** cron module changes to `RecruitmentApplication` and no wrapper needed |

**Not deleted in this CR (follow-up):**

- `Kandydaci.application_id` column and UI — still useful for legacy screens until backfill + UI migration complete.
- `importAllCandidatesFromFolder()` (TalentDays PDF path) — separate flow; leave unless refactor naturally absorbs it.

### Database

| Item | Change | Observable? |
|------|--------|---------------|
| `vtiger_recruitmentapplication.application_number` | Add **UNIQUE** index (non-deleted rows enforced in app + optional partial index) | Data integrity |
| `vtiger_cron_task` | Insert row: `module = RecruitmentApplication`, `handler_class = App\Modules\RecruitmentApplication\Cron\CvImportTask` | Cron admin |
| `vtiger_cron_task` | Remove or disable Kandydaci JSON import row (prod) | Cron admin |
| Module/custom fields | **No new fields** — Layout Editor fields already exist (tabid 129) | — |

### Module metadata

- No Layout Editor changes required (fields documented in `RecruitmentApplication.md`).
- Related lists still empty — optional follow-up CR.

### Cron / background

- New task: ~**5 min** frequency (match current practical rate; Kandydaci import is not in default `data.sql` but runs on prod).
- Existing: `scripts/dev/sync-cv-from-prod.sh` → still fills `pending/` (dev only).
- Runner: `cron/vtigercron.php` via `App\Modules\Cron\Runner\CronRunner` + `handler_class` (see `documentation/migrate-to-new-cron.md`).

### Webservices / REST

- None expected (internal batch).

### Caches

- Clear `cache/templates_c/*.php` only if new Smarty/admin UI for manual import on RecruitmentApplication (optional).

### External consumers

- **itconnect.pl** — no change; still writes JSON to `import/cv/pending/`.
- Error mail recipients today: hardcoded in `ScheduledImport::sendErrorMail` — preserve behaviour, update subject/body to mention RecruitmentApplication.

### Call sites to update (complete list)

1. `src/Modules/Kandydaci/Cron/ScheduledImportTask.php`
2. `src/Modules/Kandydaci/Actions/ImportCandidatesManually.php`
3. `src/Modules/Kandydaci/Workflows/RunImportCandidatesWorkflow.php`
4. `src/Modules/Kandydaci/scripts/importNewCandidates.php`
5. Any grep hit for `ScheduledImport::importNewCandidates` or `isApplicationInDatabase`

---

## Functional requirements

### In scope

| ID | Requirement | Before → After |
|----|-------------|----------------|
| F1 | Cron imports `import/cv/pending/*.json` | Creates **Kandydaci** only → Creates **`RecruitmentApplication`** first, then links candidate/project/CV |
| F2 | Dedup | By `Kandydaci.application_id` → By `RecruitmentApplication.application_number` |
| F3 | JSON formats | JetForm only in practice → **Auto-detect** MetForm vs JetForm (`entries` key) |
| F4 | Field mapping | Ad hoc on Kandydaci → All fields per `RecruitmentApplication.md` (use `cf_*` / field names via Record API) |
| F5 | Candidate match/create | Same rules: name+phone, then name+email (`getCandidate` logic) |
| F6 | Project link | `bindCandidateToProject` when `project_id` present and project active | Unchanged behaviour; `FL_PROJECT` set on application |
| F7 | CV file | Document + `transformDocumentToCV` on candidate + `FL_CV_DOCUMENT` on application | |
| F8 | File lifecycle | delete on duplicate; `failed/` on error; `processed/` after success (today: mixed delete/rename) | Align with module doc directories |
| F9 | GDPR consent | Normalize `tak` / `Yes` / `true` / `1` → checkbox | Same |
| F10 | `FL_APPLICATION_JSON_CONTENT` | Stored on Kandydaci → Stored on **RecruitmentApplication** (and optionally trimmed on Kandydaci later) |
| F11 | Manual import action | Kandydaci list → Still reachable; implementation calls shared importer |
| F12 | One-time **backfill** | N/A → Idempotent script: existing `Kandydaci` + `application_id` (+ JSON in `application_json_content` if present) → `RecruitmentApplication` |

### Out of scope (separate CR)

- Removing `Kandydaci.application_id` or changing how recruiters search candidates.
- Related lists on `RecruitmentApplication` / `ProjektyRekrutacyjne`.
- Re-importing entire `import/cv/backup/` (~7.8k files) in production automatically (backfill script yes; mass re-run from backup is ops-triggered).
- Fixing `getApplicationData()` mapping bugs (e.g. `sourceId` from `__form_id` vs `sourceId` / `cv-source-id`) beyond what module doc specifies — **in scope only where doc defines correct mapping**.

### Validation rules

- `application_number` required, non-empty, unique among non-deleted records.
- `FL_PROJECT` reference must exist in CRM when `project_id` is non-empty (invalid id: log + leave empty or skip project bind — match current “no throw” style for missing project).
- Email fields: use existing sanitization from current importer.
- Phone: `FL_PHONE_RAW` always; `FL_PHONE` only when libphonenumber accepts normalized value.

### Business rules

- One JSON file → at most one `RecruitmentApplication`.
- Duplicate `application_number`: delete pending JSON + attachment (current behaviour).
- Re-application to same project: existing `hasCandidateAppliedForProject` skip logic remains for **candidate↔project** relation; application row may still be created or skipped — **decision: skip creating duplicate application if `application_number` already exists; if candidate reapplies with new application number, create new RecruitmentApplication** (matches one submission = one record).

---

## Data migration

### Schema diff

```sql
-- Idempotent: add unique business key (run once per environment)
ALTER TABLE vtiger_recruitmentapplication
  ADD UNIQUE INDEX uq_recruitmentapplication_application_number (application_number);
```

If duplicate `application_number` values exist after failed tests, clean before applying index.

### Cron registration (idempotent DML)

```sql
-- Pick next free id or use NULL auto if column allows
INSERT INTO vtiger_cron_task (name, handler_class, handler_params, frequency, status, module, sequence, description)
SELECT 'LBL_SCHEDULED_CV_IMPORT',
       'App\\Modules\\RecruitmentApplication\\Cron\\CvImportTask',
       NULL,
       300,
       1,
       'RecruitmentApplication',
       COALESCE(MAX(sequence), 0) + 1,
       'Import CV applications from import/cv/pending'
FROM vtiger_cron_task
WHERE NOT EXISTS (
  SELECT 1 FROM vtiger_cron_task
  WHERE handler_class = 'App\\Modules\\RecruitmentApplication\\Cron\\CvImportTask'
);
```

Prod: disable/delete Kandydaci import cron row pointing at `App\Modules\Kandydaci\Cron\ScheduledImportTask`.

### Backfill script (idempotent)

CLI: `docker compose exec -T app php src/Modules/RecruitmentApplication/Scripts/BackfillFromKandydaci.php`

Logic per row:

1. `SELECT` Kandydaci with non-empty `application_id`, not deleted.
2. Skip if `application_number` already exists on `RecruitmentApplication`.
3. Create record from Kandydaci columns + `application_json_content` where available.
4. Set `FL_KANDYDACI` to source candidate id.
5. Log counts: created / skipped / errors.

Optional second pass: scan `import/cv/backup/*.json` for application numbers not in DB (ops-controlled, `--limit` / `--since` flags).

### Rollback strategy (data)

- **Schema:** `ALTER TABLE vtiger_recruitmentapplication DROP INDEX uq_recruitmentapplication_application_number;`
- **Imported rows:** `DELETE` RecruitmentApplication created after deploy marker, or restore DB backup.
- **Backfill:** same — restore backup if mass backfill went wrong.

### Non-conforming rows

- Kandydaci without `application_id`: not backfilled automatically; remain candidate-only.
- JSON without name: fail file → `failed/` + error mail (current behaviour).

---

## Implementation plan

### Step 1 — Shared CV application layer

Create (namespace example):

- `src/Recruitment/CvImport/CvApplicationDto.php` — normalized array/DTO after parse
- `src/Recruitment/CvImport/CvJsonParser.php` — `parseFile(path): CvApplicationDto` (JetForm + MetForm)
- `src/Recruitment/CvImport/ApplicationNumberResolver.php` — filename → `application_number`
- `src/Recruitment/CvImport/CvFilePaths.php` — `pending|processed|failed|backup` under `{ROOT}/import/cv/` (replace hardcoded `/var/www/import/cv/pending/`)

Move from `Kandydaci\Crons\ScheduledImport` with minimal behaviour change:

- `try_to_get_correct_phonenumber`, `formatFullName`, `switchFirstAndLastName`
- `getSourceName`, phone validation
- File ops: `deleteFiles`, `moveFilesToProcessed`, `moveFilesToFailed`, `saveAndDeleteFile`, `prepareRelationsString`

### Step 2 — RecruitmentApplication persistence

- `src/Modules/RecruitmentApplication/Services/RecruitmentApplicationImporter.php`
  - `importPending(): void` — glob pending JSON loop
  - `isApplicationImported(string $applicationNumber): bool`
  - `createFromDto(CvApplicationDto $dto): Record` — map to fields per module doc (use field **names** `FL_*` in `set()`, not raw `cf_303291` in business code if Record API resolves them)
  - `linkCandidate`, `attachCvDocument`, `bindProject` — delegate to extracted candidate helpers (initially move `getCandidate`, `addCVToCandidate`, `bindCandidateToProject`, `addCommentToCandidate` into `src/Recruitment/CvImport/CandidateApplicationSideEffects.php`)

### Step 3 — Cron task

- `src/Modules/RecruitmentApplication/Cron/CvImportTask.php` extends `AbstractCronTask`
  - Set current user to `automat` (same as scripts)
  - Call `RecruitmentApplicationImporter::importPending()`

### Step 4 — DB migration

- SQL migration file under `src/Modules/RecruitmentApplication/sql/` + run on deploy
- Cron insert script or migration class

### Step 5 — Repoint call sites

Update list in Impact § Call sites to call `RecruitmentApplicationImporter::importPending()` (or thin facade).

### Step 6 — Delete legacy import code

- Remove `importNewCandidates` and Kandydaci-only dedup from `ScheduledImport.php`
- Remove `ScheduledImportTask` **or** make it a one-line deprecated wrapper that calls new importer (wrapper deleted in same step if grep-clean)
- Grep verification: no `getApplicationData` / `isApplicationInDatabase` left in Kandydaci

### Step 7 — Backfill + docs

- `BackfillFromKandydaci.php` + README section in module doc
- Update `RecruitmentApplication.md` status

### Step 8 — Translations

- `LBL_SCHEDULED_CV_IMPORT` in `en_us` + `pl_pl` RecruitmentApplication.json

---

## Testing

### Manual smoke (dev)

1. Place sample JetForm JSON + CV in `import/cv/pending/` (copy from module doc examples).
2. Run: `docker compose exec -T app php cron/vtigercron.php service=LBL_SCHEDULED_CV_IMPORT`
3. Verify: new `RecruitmentApplication` with correct `application_number`, custom fields populated, `FL_KANDYDACI` set, `FL_CV_DOCUMENT` set, files moved to `processed/`.
4. Re-run with same file — duplicate skipped, files removed from pending.
5. Place legacy MetForm `cv_*.json` — parses and imports.
6. Broken JSON — lands in `failed/`, error mail sent, entry in `cache/logs/system.log` / `cron.log`.
7. Kandydaci manual import button / workflow still triggers import.

### Regression

- Candidate still linked to **ProjektyRekrutacyjne** when project active.
- ModComment still created for message/availability/salary (or move comment to application — **if moved, document in release notes**; default: keep on candidate for this CR).
- `transformDocumentToCV` still runs on candidate.

### Data integrity (after backfill)

```sql
SELECT COUNT(*) FROM vtiger_recruitmentapplication ra
JOIN vtiger_crmentity e ON e.crmid = ra.recruitmentapplicationid AND e.deleted = 0;

SELECT COUNT(*) FROM u_yf_kandydaci k
JOIN vtiger_crmentity e ON e.crmid = k.kandydaciid AND e.deleted = 0
WHERE k.application_id IS NOT NULL AND k.application_id != '';

-- Expect backfill count <= kandydaci with application_id
```

### Automated

- Unit tests for `CvJsonParser` (two fixtures: MetForm + JetForm) and `ApplicationNumberResolver` (filename variants).
- Optional integration test: import one fixture in test DB transaction.

### Grep verification (no leftovers)

```bash
rg 'importNewCandidates|isApplicationInDatabase|getApplicationData\(' src/
rg 'Kandydaci\\\\Cron\\\\ScheduledImportTask' 
```

### Logs

- `cache/logs/cron.log`, `cache/logs/system.log`

---

## Rollback plan

| Layer | Action |
|-------|--------|
| Code | Revert deploy commit |
| Cron | Re-enable Kandydaci cron row; disable RecruitmentApplication task |
| DB index | Drop unique index if blocking |
| Data | Restore DB backup if backfill or bad import batch; acceptable to lose hours of new applications if restored to night backup |

Brief maintenance window acceptable to run backfill on prod.

---

## Decision rationale and tradeoffs

| Decision | Why | Alternative rejected |
|----------|-----|------------------------|
| `RecruitmentApplication` owns cron | Matches module purpose (one submission = one record) | Keep Kandydaci cron and duplicate writes — violates single path |
| Shared `CvImport` namespace | Parser used by backfill + pending import | Copy-paste in RecruitmentApplication only — duplicates 800+ lines |
| Keep Kandydaci side-effects | Recruiters use candidates/projects today | Import only application module — breaks kanban/project flows |
| UNIQUE on `application_number` | DB-enforced dedup | Rely on query only — race under parallel cron |
| Auto-detect JSON format | Backup has ~7.8k MetForm files | JetForm only — breaks legacy reprocessing |
| Backfill in same CR | Dev/prod already have thousands of `application_id` on Kandydaci | Empty module forever until follow-up |

**Clarification:** User mentioned “ProjektyRekrutacyjne” — that module exports `projects.json` for the website (`ProjektyRekrutacyjne/Scripts/GetProjectsToJSON.php`). **CV import is implemented under Kandydaci**; this CR follows that implementation.

---

## Edge cases

| Case | Handling |
|------|----------|
| Filename without `_` (referral) | Whole basename = `application_number` (unchanged) |
| Missing CV file on disk | Log; import application row; skip document/CV transform |
| Invalid `project_id` | Log; skip project bind; still create application |
| Candidate exists, new application number | New RecruitmentApplication; candidate updated (RODO dates etc.) |
| Same candidate, same project, new application number | `hasCandidateAppliedForProject` may skip relation — document in comment; still store application |
| `sourceId` JetForm vs MetForm | Map per module doc: `sourceId` / `cv-source-id`; do not use `__form_id` as source id |
| Concurrent cron | `flock` on cron runner already; unique index prevents duplicate rows |
| Partial migration | Backfill safe to re-run (skip existing `application_number`) |

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Prod cron still on Kandydaci after deploy → double import | **High** | Deploy checklist: disable old cron row; dedup index |
| Backfill mismatch with live Kandydaci data | **Med** | Dry-run mode; counts; spot-check 20 records |
| MetForm path still broken in current `getApplicationData` | **Med** | Explicit parser tests + backup sample |
| Hardcoded `/var/www/` paths | **Low** | `CvFilePaths` uses `ROOT_DIRECTORY` |
| Comment duplication (candidate + application) | **Low** | Keep candidate-only in v1; note in release |

---

## Deliverables checklist

- [ ] `documentation/cr-RecruitmentApplication-import.md` (this file)
- [ ] Shared parser + importer code
- [ ] Cron task + `vtiger_cron_task` migration
- [ ] UNIQUE index migration
- [ ] Call site updates + legacy deletion
- [ ] Backfill script
- [ ] Module doc + `en_us`/`pl_pl` strings
- [ ] Tests for parser/resolver

---

## Questions

None blocking — assumptions A1–A5 documented above. Confirm with product owner:

1. Should **ModComments** stay on `Kandydaci` only, or also on `RecruitmentApplication`?
Only Kandydaci
2. On duplicate `application_number`, is **delete pending files** still correct (vs move to `processed/`)?
Yes.
