# CR Review: RecruitmentApplication — CV JSON cron import

**Reviewer role:** Staff Engineer + Tech Lead + Code Reviewer
**Stance applied:** FreeCRM modernization — no fallbacks, no compat shims, no parallel code paths.
**Source CR:** `documentation/cr-RecruitmentApplication-import.md`

---

## Summary verdict

**READY WITH CONDITIONS** — the design is sound and aligned with the modernization stance, but four concrete gaps must be closed before the first commit lands (table-creation migration, soft-delete vs UNIQUE collision, broken `vtiger_field` metadata for tabid 129, and the `importAllCandidatesFromFolder` helper-extraction fallout). None of these require redesign; all are tightening.

---

## Section-by-section checklist

| # | Section | Status | Notes |
|---|---------|--------|-------|
| 1 | Goal & scope clarity | ✅ | Clear "what changes", explicit in-/out-of-scope, owner-of-record stated. |
| 2 | Impact | ⚠️ | Call sites complete in app code; hidden coupling on `importAllCandidatesFromFolder` (TalentDays PDF flow) not addressed. |
| 3 | Functional requirements | ✅ | Before→after table is concrete; validation + business rules stated. |
| 4 | Data migration | ❌ | (a) Schema diff assumes table exists — false for fresh installs (`vtiger_recruitmentapplication` is **not** in `install_schema/data.sql`); (b) UNIQUE on `application_number` collides with soft-deleted rows; (c) MariaDB has no partial indexes — CR hand-waves "optional partial index". |
| 5 | Implementation plan | ⚠️ | Step order is OK, but Step 4 (DB migration) lands the new cron after Step 3 ships the new task code; if old Kandydaci cron is still in prod DB at that moment, you get parallel imports between Step 4 and the manual cron-disable. UNIQUE index mitigates DB duplicates but not file-side races. |
| 6 | Testing plan | ⚠️ | Smoke + grep good. Missing: explicit pre-backfill duplicate-detection query and pre-migration "are existing rows compliant with UNIQUE?" check. |
| 7 | Rollback plan | ⚠️ | Code + index rollback fine. "Acceptable to lose hours of new applications" is stated — confirm with product. No restore procedure for `import/cv/processed/` files when the move was the only side effect. |
| 8 | Edge cases | ⚠️ | Soft-deleted reapplication (delete record → resubmit same `application_number`) not covered. Race between two cron workers reading the same `pending/` file not covered (`flock` is mentioned but cron runner lock is per-task, not per-file). |
| 9 | Modernization completeness | ⚠️ | Step 6 allows `ScheduledImportTask` to remain as "a one-line deprecated wrapper that calls new importer (wrapper deleted in same step if grep-clean)" — this is the **exact "kept around for now" pattern** the stance rejects. Tighten language: wrapper is deleted, full stop. |
| 10 | Risks | ✅ | Severity table is honest and matches observed reality. |

---

## Blocking issues

1. **[Data migration] Table creation missing for fresh installs.**
   `vtiger_recruitmentapplication` and `vtiger_recruitmentapplicationcf` exist only on dev/prod because they were created via Layout Editor. They are **not** in `src/Modules/Install/install_schema/data.sql` or `Base2.php`. The proposed `ALTER TABLE … ADD UNIQUE INDEX` will fail on any fresh install. Either:
   - add a `CREATE TABLE IF NOT EXISTS` migration shipped in the same CR, **and** seed the `vtiger_tab` / `vtiger_field` / `vtiger_blocks` rows, **or**
   - explicitly scope this CR to "dev + prod only, fresh installs require separate module-install CR" and label it.

2. **[Data migration] UNIQUE index vs soft-delete collision.**
   `application_number` lives on the main table, not on `vtiger_crmentity`. Soft-deleting a `RecruitmentApplication` row leaves `application_number` populated. A reapplication with the same number then fails the UNIQUE insert. MariaDB does not support partial indexes; the CR's "optional partial index" is not implementable. Decision must be made and documented:
   - clear `application_number` on soft delete (trigger or app hook), **or**
   - enforce uniqueness only in app code (drop the DB UNIQUE), **or**
   - hard-delete on cleanup.

3. **[Impact / Step 1] `importAllCandidatesFromFolder` collateral damage.**
   `ScheduledImport::importAllCandidatesFromFolder` (TalentDays PDF) calls `saveAndDeleteFile`, `prepareRelationsString`, `vecho`, `getCandidateIdByNameAndEmail`, `getSourceName`, `isApplicationInDatabase`. Step 1 moves these helpers out of `ScheduledImport`. The CR says TalentDays is "left unless refactor naturally absorbs it" — but the helper move forces a decision now. Add an explicit step: either (a) update TalentDays to call the new shared layer, (b) keep the named helpers as facades on the old class until TalentDays is migrated in a follow-up CR (acceptable on stance grounds only if time-boxed), or (c) delete TalentDays.

4. **[Modernization] Step 6 leaves room for a wrapper.**
   The "one-line deprecated wrapper" caveat violates the no-fallbacks stance. Rewrite as: "`ScheduledImportTask` is deleted; all call sites in §Impact updated to instantiate `App\Modules\RecruitmentApplication\Cron\CvImportTask` (or call the importer service directly). Grep verification confirms no remaining references."

---

## Conditions (non-blocking)

- **[Impact] DB cleanup precondition.** `vtiger_field` for `tabid=129` currently contains **9 duplicate copies** of every standard field (`application_number`, `number`, `assigned_user_id`, `createdtime`, `modifiedtime`, `created_user_id` — fieldids 303229, 303235, 303241, 303247, 303253, 303259, 303265, 303271, 303277). This pre-existing metadata corruption will cause unpredictable Layout Editor / Record API behaviour. Either fix as Step 0 of this CR or open a separate cleanup CR and reference it as a hard dependency.

- **[Impact] Cron registration pattern.** The CR proposes raw SQL for the cron insert. The codebase convention is a PHP migration class (`src/Modules/Cron/Migration/*.php`, e.g. `MigrateKandydaciScheduledImportToClass`). Use the same pattern: a `MigrateCvImportToRecruitmentApplication` class that inserts the new row **and** deletes the Kandydaci row in one transaction. This also closes the "prod-only manual disable" gap.

- **[Impact] Namespace inconsistency.** `src/Recruitment/CvImport/` is non-PSR-4 with current `composer.json` autoload (`App\` → `src/`). The shared layer should live under `App\Modules\RecruitmentApplication\Services\CvImport\` (or a new `App\Modules\Cron\Services\` if you want a true cross-module shared layer). Same point for the proposed `src/Modules/RecruitmentApplication/Scripts/BackfillFromKandydaci.php` — existing scripts use lowercase `scripts/` (see `Kandydaci/scripts/importNewCandidates.php`).

- **[Functional] `assigned_user_id` is mandatory** on `RecruitmentApplication` (declared in entity `$mandatory_fields`). The CR never says which user owns imported rows. Make it explicit: `automat`, same as Kandydaci. Same for `created_user_id`.

- **[Functional / F12] Backfill duplicate check.** Verified in DB: `application_id` is already unique across 6382 Kandydaci rows. State this in the CR so the backfill can assume one-to-one mapping; add a pre-flight `SELECT application_id, COUNT(*) GROUP BY application_id HAVING COUNT(*) > 1` to refuse running if that ever changes.

- **[Functional / F8] File lifecycle on duplicate.** Current behaviour is `unlink` (delete). Module doc and CR step 5/Q2 hint at moving to `processed/` instead. Pick one in this CR — do not ship "decision deferred".

- **[Testing] Grep verification incomplete.** Add to the grep checklist:
  - `rg 'addCVToCandidate|addCommentToCandidate|hasCandidateAppliedForProject|isProjectActive' src/Modules/Kandydaci/` — these helpers either move with the importer or stay; explicit before/after counts make the diff reviewable.
  - `rg 'application_id' src/Modules/Kandydaci/` — confirm the old dedup column is no longer read by import code (still allowed in display code per A4).

- **[Testing] Missing pre-migration integrity check.** Before applying the UNIQUE index, the CR should run `SELECT application_number, COUNT(*) FROM vtiger_recruitmentapplication GROUP BY application_number HAVING COUNT(*) > 1` and refuse the migration on hits. Dev count is 0 today, so the migration succeeds, but the runbook step makes it safe forever.

- **[Edge cases] Concurrent cron.** Two `vtigercron.php` invocations on the same task are blocked by the per-task lock, but two **different** tasks importing the same directory are not. UNIQUE index closes the DB race; explicit `flock` on the importer (not just on the cron task) closes the file-rename race. Cheap and worth doing.

- **[Functional / parser fidelity] Known bug in legacy MetForm parser.** `ScheduledImport::getApplicationDataOld()` lines 425–432 contain inverted name-empty logic (`if (!empty(...)) throw` followed by a re-fetch and re-validation of the same field) — this is dead/broken code. The CR's "behaviour preserved" promise will carry this forward. Either explicitly fix this case under F3 or call it out as a follow-up. Don't replicate the bug silently.

- **[Functional / parser fidelity] JetForm field-name drift.** `getApplicationData()` reads `$data["name"]` but the file-header example JSON in the source code shows `"full_name"`. The module doc accepts both. The CR should specify which key takes precedence and document the fallback order in the new `CvJsonParser`.

- **[Rollback] Files-side rollback unspecified.** After a bad import, JSON files have been moved to `processed/`. A code revert does not undo the move. State: either "files in `processed/` are authoritative; rollback re-imports from `processed/` on demand" or "rollback restores `pending/` from `backup/`".

---

## Strengths

- Stance is correctly internalized: one importer, one dedup key, deletion is in the same CR.
- Impact tables are unusually thorough — code-modified, code-deleted, not-deleted-with-reason, call-sites enumerated.
- Functional `Before → After` table is the cleanest part of the document.
- Backfill is idempotent by design and acknowledges the `~7.8k` backup volume explicitly.
- Decision/tradeoff table makes the "why" reviewable in 30 seconds.
- The CR correctly anticipates the production-only Kandydaci cron row (verified: no such row in dev DB; deploy checklist mention is essential).

---

## Recommended next action

Open the CR for a "tightening pass" addressing the four blockers in order:

1. Add a "Step 0 — Schema bootstrap" that ships `CREATE TABLE IF NOT EXISTS` for `vtiger_recruitmentapplication` / `vtiger_recruitmentapplicationcf` and seeds `vtiger_tab` + `vtiger_field` + `vtiger_blocks` for tabid 129. Cite the dev-DB field-meta corruption (9 duplicate `application_number` rows) as the trigger for a one-time cleanup at the same point.
2. Replace the "optional partial index" language with a concrete soft-delete policy (clear `application_number` on delete, or drop the DB UNIQUE and enforce in app).
3. Decide the `importAllCandidatesFromFolder` fate in §Impact (migrate / facade-with-deadline / delete) so Step 1's helper extraction is unambiguous.
4. Tighten Step 6 to "delete `ScheduledImportTask`" with no wrapper escape clause; add the `MigrateCvImportToRecruitmentApplication` PHP migration that inserts the new cron row and deletes the old one atomically.

After those four are addressed, this CR is implementable as a single coherent change.

---

## Assumptions made during review

- `App\Modules\Cron\Tasks\AbstractCronTask` and `CronTaskInterface` are the canonical bases for new cron tasks (verified — both exist; other modules already use this pattern, e.g. `App\Modules\Import\Cron\ScheduledImportTask`).
- Production `vtiger_cron_task` contains a `module='Kandydaci'` row pointing at `App\Modules\Kandydaci\Cron\ScheduledImportTask` (CR claims this; dev DB does not have it; treated as a prod-only fact per CR's A4).
- `vtiger_recruitmentapplicationcf.cf_*` column ids in the module doc are stable across environments (verified on dev; assumed identical on prod — should be confirmed by the implementer before backfill).
- The `automat` user exists in prod (it does in dev; reused by every other importer in `Kandydaci`).
- The website (`itconnect.pl`) and `scripts/dev/sync-cv-from-prod.sh` will not be touched as part of this CR.

---

**Output file:** `documentation/cr-RecruitmentApplication-import-review.md`
