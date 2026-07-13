# Module: `RecruitmentApplication`

> **Purpose:** store a single candidate application submitted via the itconnect.pl website (job apply form, leave-CV form, employee referral). One record = one submission; links optionally to `Kandydaci`, `ProjektyRekrutacyjne`, and `Documents`.
>
> **Status (2026-05):** CV JSON import runs via `RecruitmentApplicationImporter` and cron task `LBL_SCHEDULED_CV_IMPORT_APPLICATIONS` (`App\Modules\RecruitmentApplication\Cron\CvImportTask`). Pending files: `import/cv/pending/`; duplicate `application_number` deletes pending files; success moves to `processed/`; errors to `failed/`.

---

## Identity

| Item | Value |
|------|-------|
| Module name | `RecruitmentApplication` |
| Tab ID | `129` |
| PHP entity | `App\Modules\RecruitmentApplication\RecruitmentApplication` |
| Entity file | `src/Modules/RecruitmentApplication/RecruitmentApplication.php` |
| PL label | Aplikacje kandydatów |
| EN label | Candidate Applications |
| Record name field | `application_number` (`FL_APPLICATION_NUMBER`) |
| Translations | `languages/pl_pl/RecruitmentApplication.json`, `languages/en_us/RecruitmentApplication.json` |

---

## Database

| Table | Role |
|-------|------|
| `vtiger_crmentity` | Standard entity row (`setype = RecruitmentApplication`) |
| `vtiger_recruitmentapplication` | Main module table |
| `vtiger_recruitmentapplicationcf` | Custom fields (all CV/application payload) |

### Main table columns

| Column | Label key | UiType | Notes |
|--------|-----------|--------|-------|
| `recruitmentapplicationid` | — | — | PK, FK → `vtiger_crmentity.crmid` |
| `application_number` | `FL_APPLICATION_NUMBER` | 2 (Text) | Business key; maps to CV JSON filename token (e.g. `cv_1738743423` → `1738743423`) |
| `number` | `FL_NUMBER` | 4 (Auto number) | Internal sequence |

Standard CRM fields (`assigned_user_id`, `createdtime`, `modifiedtime`, `created_user_id`) live on `vtiger_crmentity` / system blocks.

### Custom field table

All application data fields are **Layout Editor custom fields** on `vtiger_recruitmentapplicationcf`. Column names are auto-generated `cf_<fieldid>` (FreeCRM convention for “Dodatkowa” table fields). **Internal field names cannot contain underscores** — only `[a-z0-9]`.

---

## Layout blocks

Block order in detail/edit views:

| Seq | Block ID | Label key | PL title | Purpose |
|-----|----------|-----------|----------|---------|
| 1 | 509 | `LBL_BASIC_INFORMATION` | Informacje podstawowe | Candidate contact + link to Kandydaci |
| 2 | 511 | `LBL_APPLICATION_CONTEXT` | Kontekst aplikacji | Job, project, source, form type, submission time |
| 3 | 512 | `LBL_TERMS_AND_MESSAGE` | Warunki i wiadomość | Message, availability, contract, salary, GDPR consent |
| 4 | 513 | `LBL_CV_AND_ATTACHMENTS` | CV i załączniki | CV document reference and file metadata |
| 5 | 514 | `LBL_REFERRAL` | Polecenie znajomego | Employee referral (rare) |
| 6 | 510 | `LBL_CUSTOM_INFORMATION` | Metadane formularza WWW | WordPress/form IDs, referrer URL, raw JSON |

---

## Custom fields reference

| Label key | DB column | UiType | Block | Import / meaning |
|-----------|-----------|--------|-------|------------------|
| `FL_CANDIDATE_NAME` | `cf_303291` | Text | Basic | `name` / `cv-imie-nazwisko` |
| `FL_EMAIL` | `cf_303283` | Email | Basic | `email` / `cv-email` |
| `FL_PHONE` | `cf_303285` | Phone | Basic | Normalized phone after libphonenumber |
| `FL_PHONE_RAW` | `cf_303293` | Text | Basic | `phone_number` / `cv-numer-telefonu` (raw from form) |
| `FL_KANDYDACI` | `cf_303289` | Reference (10) | Basic | Link to matched/created `Kandydaci` record |
| `FL_JOB_TITLE` | `cf_303295` | Text | Context | `job_title` / `nazwa_stanowiska` / `cv-nazwa-stanowiska` |
| `FL_PROJECT` | `cf_303297` | Reference (10) → `ProjektyRekrutacyjne` | Context | `project_id` / `id_projektu` / `cv-id-projektu` (CRM id) |
| `FL_SOURCE_ID` | `cf_303299` | Integer | Context | `sourceId` / `cv-source-id` → `vtiger_zrodlo_aplikacji.zrodlo_aplikacjiid` |
| `FL_FORM_TYPE` | `cf_303339` | Text | Context | `formtype`: `apply` \| `leavecv` |
| `FL_SUBMITTED_AT` | `cf_303325` | Text | Context | `created_at` (WWW timestamp string) |
| `FL_MESSAGE` | `cf_303287` | TextArea | Terms | `message` / `cv-wiadomosc` |
| `FL_AVAILABLE_FROM` | `cf_303311` | Text | Terms | `available_from` / `cv-od-kiedy` |
| `FL_PREFERRED_CONTRACT_TYPE` | `cf_303313` | Text | Terms | `preferred_contract_type` / `cv-preferowana` (free text, not picklist) |
| `FL_EXPECTED_SALARY` | `cf_303315` | Text | Terms | `expected_salary` / `cv-oczekiwania` |
| `FL_FUTURE_RECRUITMENT_CONSENT` | `cf_303317` | Checkbox | Terms | `future_recruitment_consent` / `cv-zgoda` |
| `FL_CV_DOCUMENT` | `cf_303337` | Reference (10) → `Documents` | CV | Linked CV document after import |
| `FL_CV_ORIGINAL_FILENAME` | `cf_303319` | Text | CV | `cv_original_filename` / MetForm upload name |
| `FL_CV_SAVED_FILENAME` | `cf_303321` | Text | CV | `cv_saved_filename` on server |
| `FL_CV_ATTACHMENT_URL` | `cf_303323` | URL | CV | `attachment_cv` / `file_uploads.cv-zalacz-cv[0].url` |
| `FL_IS_REFERRED_BY_EMPLOYEE` | `cf_303329` | Checkbox | Referral | Set when `cv-imie-nazwisko-polecajaca` present |
| `FL_REFERRED_BY_EMPLOYEE` | `cf_303331` | Text | Referral | `cv-imie-nazwisko-polecajaca` |
| `FL_REFERRED_BY_EMAIL` | `cf_303333` | Email | Referral | `cv-email-polecajaca` |
| `FL_REFERRED_ON_POSITION` | `cf_303335` | Text | Referral | `cv-nazwa-stanowiska` (referral context) |
| `FL_FORM_LANGUAGE` | `cf_303341` | Text | Metadata | `cv-jezyk-formularza` (`pl`, `en`) |
| `FL_POST_ID` | `cf_303305` | Integer | Metadata | WordPress `post_id` (JetForm only) |
| `FL_FORM_ID` | `cf_303307` | Integer | Metadata | `__form_id` (WordPress form id) |
| `FL_REFERRER_URL` | `cf_303309` | URL | Metadata | `__refer` |
| `FL_APPLICATION_JSON_CONTENT` | `cf_303327` | TextArea | Metadata | Full source JSON (audit / re-import) |

---

## CV JSON sources

Incoming files live under `import/cv/` (`pending/`, `backup/`, `processed/`, `failed/`). Two formats exist in backup (~7.8k files):

### Legacy MetForm (`entries` + `file_uploads`)

Example: `import/cv/backup/cv_1738743423.json`

```json
{
  "entries": {
    "cv-imie-nazwisko": "Cieślak",
    "cv-email": "krzysztofcieslak@me.com",
    "cv-numer-telefonu": "+48 662 409 438",
    "cv-wiadomosc": "...",
    "cv-od-kiedy": "1 tydzień",
    "cv-preferowana": "B2B",
    "cv-oczekiwania": "15000",
    "cv-nazwa-stanowiska": "PMO",
    "cv-id-projektu": "1404311",
    "cv-source-id": "14",
    "cv-zgoda": "tak",
    "cv-jezyk-formularza": "pl"
  },
  "file_uploads": {
    "cv-zalacz-cv": [{ "url": "...", "name": "entry-file-1738743423.pdf" }]
  },
  "created_at": "2025-02-05 08:17:03"
}
```

Application number = numeric suffix of filename (`1738743423`).

### JetFormBuilder (normalized top-level keys)

Example: `import/cv/pending/apply_*.json`

Key fields: `name`, `email`, `phone_number`, `message`, `available_from`, `preferred_contract_type`, `expected_salary`, `job_title`, `project_id`, `post_id`, `sourceId`, `future_recruitment_consent`, `formtype`, `__form_id`, `__refer`, `cv_saved_filename`, `cv_original_filename`, `attachment_cv`.

Application number = segment after first `_` in filename (see `ScheduledImport::importNewCandidates()`).

### Form types

| `formtype` | Meaning | Typical fields populated |
|------------|---------|--------------------------|
| `apply` | Application to a specific job offer | `project_id`, `job_title`, `post_id`, `future_recruitment_consent` |
| `leavecv` | General “leave your CV” page | Often empty `project_id` / `job_title` |

---

## Relationship to existing import

Current production import: `src/Modules/Kandydaci/Crons/ScheduledImport.php`

- Reads `import/cv/pending/*.json`
- Creates/updates **`Kandydaci`** records (not `RecruitmentApplication`)
- Dedup key: `application_id` on Kandydaci (= application number from filename)
- Maps the same JSON keys as listed above into Kandydaci fields + ModComments + Documents

**Planned flow for this module:** one `RecruitmentApplication` per JSON file, then link `FL_KANDYDACI` and `FL_PROJECT`, attach CV to `FL_CV_DOCUMENT`. Reuse parsing logic from `getApplicationData()` / `getApplicationDataOld()` in `ScheduledImport.php`.

---

## Related modules

| Module | Field | Direction |
|--------|-------|-----------|
| `Kandydaci` | `FL_KANDYDACI` (`cf_303289`) | Application → candidate |
| `ProjektyRekrutacyjne` | `FL_PROJECT` (`cf_303297`) | Application → recruitment project |
| `Documents` | `FL_CV_DOCUMENT` (`cf_303337`) | Application → CV file |

Related lists for `RecruitmentApplication` are not configured yet (`vtiger_relatedlists` empty for tabid 129). Creating reference fields via Layout Editor may add dependent-list entries on target modules.

---

## Configuration URLs

| Action | URL |
|--------|-----|
| Layout Editor | `/index.php?module=LayoutEditor&parent=Settings&view=Index&block=2&fieldid=33&sourceModule=RecruitmentApplication` |
| Module list | `/index.php?module=RecruitmentApplication&view=ListView` |

---

## Implementation notes

1. **Custom field column names** — use `cf_*` column names (or field ids from `vtiger_field`) in import code, not semantic names like `candidate_name`.
2. **`FL_SOURCE_ID`** — integer id into `vtiger_zrodlo_aplikacji`; resolve display name via `CandidateApplicationSideEffects::getSourceName()`.
3. **`FL_FORM_TYPE` / `FL_FORM_LANGUAGE`** — stored as Text (picklist creation failed in Layout Editor for cf fields); values are free-form strings.
4. **`FL_SUBMITTED_AT`** — Text, not DateTime, to preserve full WWW timestamp strings.
5. **GDPR consent** — checkbox; source values include `tak`, empty, `Yes`; normalize on import like Kandydaci (`agreeToContact`).
6. **Phone** — store raw in `FL_PHONE_RAW`, normalized in `FL_PHONE` when libphonenumber accepts the number.
7. **Duplicate prevention** — use `application_number` (main table) as unique business key per submission.

---

## CV import (cron)

| Item | Value |
|------|-------|
| Cron service | `LBL_SCHEDULED_CV_IMPORT_APPLICATIONS` |
| Handler | `App\Modules\RecruitmentApplication\Cron\CvImportTask` |
| Importer | `importApplicationsFromPending()` + `importCandidatesFromApplications()` |
| Manual trigger | Kandydaci list → import candidates (same importer) |

```bash
docker compose exec -T app php cron/vtigercron.php service=LBL_SCHEDULED_CV_IMPORT_APPLICATIONS
```

**Backfill** (idempotent, from existing `Kandydaci.application_id`):

```bash
docker compose exec -T app php src/Modules/RecruitmentApplication/scripts/backfillFromKandydaci.php --dry-run
docker compose exec -T app php src/Modules/RecruitmentApplication/scripts/backfillFromKandydaci.php
```

**SQL migrations** (fresh install / UNIQUE index / field dedup):

```bash
docker compose exec -T app php -r "chdir('/var/www/html'); require 'vendor/autoload.php'; \App\Modules\Cron\Bootstrap::init(); \App\Modules\RecruitmentApplication\Migration\RunSqlMigrations::execute();"
```

Rollback note: files moved to `import/cv/processed/` are authoritative; re-import from there if code is reverted.

---

## Files

```
src/Modules/RecruitmentApplication/
  RecruitmentApplication.php
  Cron/CvImportTask.php
  Services/RecruitmentApplicationImporter.php
  Services/CvImport/                    # parser, DTO, side-effects
  scripts/backfillFromKandydaci.php
  sql/001_bootstrap_module.sql
languages/pl_pl/RecruitmentApplication.json
languages/en_us/RecruitmentApplication.json
import/cv/{pending,processed,failed,backup}/
documentation/module/RecruitmentApplication.md
```

---

## Example: mapping one backup record

Source: `cv_1738743423.json`

| RecruitmentApplication field | Value |
|----------------------------|-------|
| `application_number` | `1738743423` |
| `FL_CANDIDATE_NAME` | Cieślak |
| `FL_EMAIL` | krzysztofcieslak@me.com |
| `FL_PHONE_RAW` | +48 662 409 438 |
| `FL_JOB_TITLE` | PMO |
| `FL_PROJECT` | 1404311 |
| `FL_SOURCE_ID` | 14 |
| `FL_MESSAGE` | Krzysztof Cieślak |
| `FL_AVAILABLE_FROM` | 1 tydzień |
| `FL_PREFERRED_CONTRACT_TYPE` | B2B |
| `FL_EXPECTED_SALARY` | 15000 |
| `FL_FUTURE_RECRUITMENT_CONSENT` | yes (from `tak`) |
| `FL_FORM_LANGUAGE` | pl |
| `FL_SUBMITTED_AT` | 2025-02-05 08:17:03 |
| `FL_CV_ORIGINAL_FILENAME` | entry-file-1738743423.pdf |
| `FL_CV_ATTACHMENT_URL` | (from `file_uploads`) |
| `FL_APPLICATION_JSON_CONTENT` | (full file) |
