# Change Request: Rebuild Documents storage

## Goal

Replace the four-table document/attachment model with a **single source of truth**: the
document entity row owns its file descriptor directly.

Today a single uploaded document spans:

- `vtiger_crmentity` (the Documents record) +
- `vtiger_notes` (`filename`, `filetype`, `filesize`, `filelocationtype`, `filestatus`, …) +
- `vtiger_notescf` (empty — zero custom fields) +
- `vtiger_seattachmentsrel` (`crmid` → `attachmentsid`) +
- `vtiger_attachments` (`name`, `type`, `path` — **duplicates** the notes metadata) +
- a **second** `vtiger_crmentity` row (`setype = 'Documents Attachment'`) minted only to
  produce an `attachmentsid`.

The physical path is **reconstructed** as `path + attachmentsid + '_' + name` in 13+ call
sites. After this CR, a document is `vtiger_crmentity` + one `vtiger_notes` row carrying an
explicit `storage_path`, plus `vtiger_senotesrel` for record links. Nothing else.

One migration, no fallbacks, no parallel code paths (`general-guidelines.mdc` modernization
stance).

## Decisions (confirmed)

| # | Decision | Choice |
|---|----------|--------|
| D1 | Column naming | **Clean rename** — `location_type`, `storage_path`, `original_name`, `external_url`, `mime_type`, `size_bytes`, `download_count`, `active` |
| D2 | Record images / non-Documents attachments | **Migrate + drop** — move the 13 non-Documents rows to a new clean table, then DROP `vtiger_attachments` + `vtiger_seattachmentsrel` fully |
| D3 | 68,956 orphan `vtiger_notes` (no `crmentity`, no blob) | **Delete** them + their 12,980 ghost `senotesrel` + matching `notescf` rows |
| D4 | `u_yf_mail_attachments`, `u_yf_attachments`/MultiImage | **Out of scope** — already single-table, clean; not touched |

## Live data snapshot (via `freecrm-mysql` MCP)

| Metric | Value |
|--------|-------|
| `vtiger_notes` total | 100,006 |
| …with `crmentity` (real docs) | 31,050 (30,995 active / 55 deleted) |
| …orphan (no `crmentity`, 0 blobs) | **68,956** (12,980 carry `senotesrel`) |
| `vtiger_attachments` | 30,532 |
| `vtiger_seattachmentsrel` | 30,528 (Documents 30,515; HelpDesk 5; ModComments 4; DocumentTemplates 3; Candidates 1) |
| Docs with >1 attachment | **0** (strict 1:1 in practice) |
| `filelocationtype` distribution (active) | `I`=30,936, `E`=3, NULL=56 |
| Rows using `notecontent` | 2,573 (**keep**) |
| Rows using `fileversion` / `ossdc_status` | 1 / 0 (**drop**) |
| Distinct `folderid` | 3 |
| Active internal docs with no attachment row | 456 |
| `seattachmentsrel` → missing attachment / attachment → no rel | 5 / 9 |

---

## Target schema

### `vtiger_notes` (the document entity — kept, renamed columns)

```sql
-- Final shape after migration
CREATE TABLE `vtiger_notes` (
  `notesid`        int(19)      NOT NULL,                 -- = crmid
  `note_no`        varchar(100) NOT NULL,                 -- record sequence (uitype 4)
  `title`          varchar(255) NOT NULL,
  `folderid`       varchar(255) DEFAULT NULL,             -- Tree (uitype 302)
  `notecontent`    mediumtext   DEFAULT NULL,             -- kept (2,573 rows)
  `location_type`  enum('internal','external') NOT NULL DEFAULT 'internal',
  `storage_path`   varchar(500) DEFAULT NULL,             -- relative path under ROOT_DIRECTORY (internal only)
  `original_name`  varchar(255) DEFAULT NULL,             -- download filename (internal)
  `external_url`   varchar(2048) DEFAULT NULL,            -- URL (external only)
  `mime_type`      varchar(127) DEFAULT NULL,
  `size_bytes`     int(19) unsigned NOT NULL DEFAULT 0,
  `download_count` int(19) unsigned NOT NULL DEFAULT 0,
  `active`         tinyint(1)   NOT NULL DEFAULT 1,       -- file present & usable
  PRIMARY KEY (`notesid`),
  KEY `notes_title_idx` (`title`),
  CONSTRAINT `fk_1_vtiger_notes` FOREIGN KEY (`notesid`)
    REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Column mapping (old → new):

| Old | New | Notes |
|-----|-----|-------|
| `filelocationtype` `'I'`/`'E'` | `location_type` `internal`/`external` | NULL → `internal` |
| *(reconstructed path)* | `storage_path` | `CONCAT(a.path, a.attachmentsid, '_', a.name)` |
| `filename` (internal) | `original_name` | download name |
| `filename` (external) | `external_url` | the URL |
| `filetype` | `mime_type` | |
| `filesize` | `size_bytes` | |
| `filedownloadcount` | `download_count` | |
| `filestatus` | `active` | missing-file rows → `0` |
| `fileversion`, `ossdc_status` | *(dropped)* | dead |

### `s_yf_record_files` (new — non-Documents file attachments)

Replaces the `vtiger_attachments` + `vtiger_seattachmentsrel` usage for records that attach a
file **without** being a Documents entity (HelpDesk screenshots, ModComments attachments,
DocumentTemplates files, record images for Contacts/Products/Users — currently 0 rows but code
paths exist).

```sql
CREATE TABLE `s_yf_record_files` (
  `id`            int(10) unsigned NOT NULL AUTO_INCREMENT,
  `crm_record_id` int(19)      NOT NULL,                  -- owning record crmid
  `role`          varchar(32)  NOT NULL DEFAULT 'attachment', -- 'attachment' | 'image'
  `storage_path`  varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type`     varchar(127) NOT NULL DEFAULT 'application/octet-stream',
  `size_bytes`    int(10) unsigned NOT NULL DEFAULT 0,
  `created_at`    datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`crm_record_id`, `role`),
  CONSTRAINT `fk_record_files_crmid` FOREIGN KEY (`crm_record_id`)
    REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Single-image-per-record semantics: query by `(crm_record_id, role='image')`; replace on
re-upload. Path is explicit (`storage_path`) — no `{id}_{name}` reconstruction.

### Dropped tables

```sql
DROP TABLE `vtiger_seattachmentsrel`;
DROP TABLE `vtiger_attachments`;
DROP TABLE `vtiger_notescf`;
-- vtiger_attachmentsfolder: no CREATE in scheme.sql; only WS-entity seed rows removed
```

`vtiger_senotesrel` is **kept** — it is the document↔record relation, the actual value.

---

## Impact

For every touchpoint: **(O)** = observable (UI/API/data shape), **(I)** = internal only.

### Code modified — Documents core

| File | Change | |
|------|--------|--|
| `src/Modules/Documents/Models/Record.php` | `getFileDetails` removed; `downloadFile`/`checkFileIntegrity`/`getDownloadFileURL` use `storage_path`; `saveToDb` writes file + sets `storage_path`/`original_name`/`mime_type`/`size_bytes`/`location_type`; `updateFileStatus`→`active`; `updateDownloadCount`→`download_count` | O |
| `src/Modules/Base/Models/Record.php` | `uploadAndSaveFile()` (1149–1247): Documents branch writes the file and updates `vtiger_notes` directly — **no** second `crmentity`, **no** `vtiger_attachments`/`seattachmentsrel` insert. Non-Documents branch writes `s_yf_record_files` | O |
| `src/Modules/Documents/Documents.php` | drop `vtiger_notescf` from `tab_name`/`tab_name_index`/`customFieldTable`; rename list/search/relation field column refs (`filename`→`original_name`, etc.); export query | O |
| `src/Modules/Documents/Actions/DownloadFile.php` | path from `storage_path`; `download_count` | O |
| `src/Modules/Documents/Actions/CheckFileIntegrity.php` | verify `storage_path`; set `active` | O |
| `src/Modules/Documents/Actions/MassAdd.php` | create via new upload path | O |
| `src/Modules/Documents/Actions/MoveDocuments.php` | unaffected by storage (folderid) — verify only | I |
| `src/Modules/Documents/Models/{Module,ListView,DetailView,Relation,Field,EditRecordStructure}.php` | rename column refs; popup/folder queries; filesize display via `size_bytes` | O |
| `src/Modules/Base/UiTypes/DocumentsFileUpload.php` | uitype 28 → `original_name`; download link from `storage_path` (drop `seattachmentsrel` lookup) | O |
| `src/QueryField/DocumentsFileUploadField.php` | column rename | I |

### Code modified — consumers of the file/path

| File | Change | |
|------|--------|--|
| `src/Email/Mail.php` (`getAttachmentsFromDocument`, ~240–251) | join `vtiger_notes` only; path from `storage_path` | O |
| `src/Modules/EmailTemplates/Models/TemplateAttachment.php` | `listForTemplate`/`physicalFileSize`/`documentHasPhysicalFile` use `storage_path`, `original_name`, `mime_type`, `size_bytes`, `active`; drop `vtiger_attachments`/`seattachmentsrel` joins | O |
| `src/Modules/Candidates/Actions/ExportCvZip.php` | doc path from `storage_path` | O |
| `src/Modules/Candidates/Models/Record.php` (CV lookup + `transformDocumentToCV` copy) | source path from `storage_path` | O |
| `src/Modules/Candidates/Handlers/RefreshDocsNumber.php` | count via `senotesrel` (drop `seattachmentsrel` join) | I |
| `src/Modules/RecruitmentApplication/Services/CvImport/DocumentHelper.php` | create document with `storage_path`/`original_name` | O |
| `src/Webservices/Utils.php` (604–626, lead convert) | copy `senotesrel` only | I |
| `src/Core/CRMEntity.php` (`getOldFileName` 171–182, retrieve skip-list) | drop attachment references | I |
| `src/Modules/RecycleBin/Models/Module.php` (`deleteFiles` 199–230) | unlink `storage_path`; delete from `vtiger_notes`; delete `s_yf_record_files` by `crm_record_id` | O |
| `scripts/cleanup-orphan-documents.php` | rewrite around `storage_path`/`senotesrel`/`u_yf_documents_emailtemplates` | I |
| `src/Utils/ExportUtils.php` (104–109), `src/Modules/Reports/ReportRun.php` (307–308) | column renames in skip/format lists | O |
| `src/QueryField/QueryGenerator.php` (521–522) | auto-include `location_type` instead of `filelocationtype` | I |
| `src/Modules/Settings/Workflows/Views/EditTask.php` (83) | restricted-field column renames | O |

### Code modified — record images / non-Documents attachments (D2)

| File | Change | |
|------|--------|--|
| `src/Modules/Contacts/Models/Record.php`, `src/Modules/Products/Models/Record.php` | image read/write via `s_yf_record_files` (`role='image'`) | O |
| `src/Modules/HelpDesk/Models/Record.php`, `src/Modules/HelpDesk/Files/Image.php` | screenshot/image via `s_yf_record_files` | O |
| `src/Modules/Users/Users.php` (412–476), `src/Modules/Users/Models/Record.php` (655, 798) | user photo via `s_yf_record_files` | O |
| `src/Modules/Base/UiTypes/Image.php` + `layouts/.../uitypes/Image*.tpl`, `ImageDetailView.tpl` | serve from `s_yf_record_files` | O |
| `src/Modules/Vendors/Vendors.php` (97) | image join | O |

### Code deleted

| File / item | Reason |
|-------------|--------|
| `src/Modules/Base/UiTypes/DocumentsFolder.php` + `layouts/.../uitypes/DocumentsFolder.tpl` | dead — queries non-existent `vtiger_attachmentsfolder`; folders use Tree uitype 302 |
| `vtiger_attachmentsfolder` WS-entity seed (`data.sql` 27875/27885/27897, `Base4.php` 9832/9846/9860) | vestigial |
| `vtiger_notes.fileversion` / `ossdc_status` field metadata; `ossdc_status` branch in `Settings/DataAccess/Helpers/DataAccess_Conditions.php:189` | dead columns |
| `vtiger_attachments` / `vtiger_seattachmentsrel` / `vtiger_notescf` in `scheme.sql`, `Base2/3/4.php`, `data.sql`, FKs, the 50+ `getAttachments` relatedlist seeds | tables dropped |

### Metadata / non-code

- `vtiger_field` rows for Documents: update `columnname` (`filename`→`original_name`,
  `filelocationtype`→`location_type`, `filetype`→`mime_type`, `filesize`→`size_bytes`,
  `filestatus`→`active`, `filedownloadcount`→`download_count`); remove `fileversion`/`ossdc_status`. **(O)**
- `vtiger_relatedlists` `getAttachments` entries regenerated without satellite tables. **(O)**
- `bin/regenerate_user_privileges.php` after metadata changes; clear `cache/templates_c/*.php`. **(I)**
- Language files: no string changes expected (field labels unchanged); verify
  `languages/en_us/Documents.json` + `languages/pl_pl/Documents.json` stay in sync. **(I)**
- Cron: `AttachmentsTask` (`u_yf_attachments` purge) **unaffected** (D4). `cleanup-orphan-documents.php` rewritten. **(I)**

---

## Functional requirements

Before → after, business rules that must hold:

1. **Internal document upload** — file stored under `storage/{Y}/{F}/week{N}/`; `vtiger_notes`
   row has `location_type='internal'`, non-null `storage_path`, `original_name`, `mime_type`,
   `size_bytes`, `active=1`. Download streams the file and increments `download_count`.
2. **External document** — `location_type='external'`, `external_url` set, `storage_path` NULL;
   open link in new tab. No file on disk.
3. **1:1 invariant** — a document has at most one file (enforced implicitly: descriptor is on
   the row). The old multi-attachment capability for Documents is removed (never used: 0 rows).
4. **Record links preserved** — `senotesrel` continues to relate a document to many records;
   related lists, lead conversion, merge transfer, recycle-bin cascade all keep working.
5. **Email template attachments** — link/unlink/list/send unchanged in behavior; backed by
   `u_yf_documents_emailtemplates` + `storage_path`.
6. **Missing-file documents** — a visible document whose file is absent on disk has `active=0`
   (same effect as today's `filestatus=0`); UI shows it as non-downloadable.
7. **Integrity check** action re-verifies `storage_path` on disk and toggles `active`.

In scope: Documents module, email-template doc attachments, Candidates CV, CV import,
non-Documents record attachments/images.
Out of scope (D4): mail attachments (`u_yf_mail_attachments`), MultiImage
(`u_yf_attachments`) — already clean, separate subsystems.

---

## Data migration (idempotent, one-shot)

Order matters; safe to re-run (guards on NULL / existence).

```sql
-- STEP 1  Additive columns on vtiger_notes (alongside old, temporarily)
ALTER TABLE vtiger_notes
  ADD COLUMN location_type  ENUM('internal','external') NOT NULL DEFAULT 'internal',
  ADD COLUMN storage_path   VARCHAR(500)  DEFAULT NULL,
  ADD COLUMN original_name  VARCHAR(255)  DEFAULT NULL,
  ADD COLUMN external_url   VARCHAR(2048) DEFAULT NULL,
  ADD COLUMN mime_type      VARCHAR(127)  DEFAULT NULL,
  ADD COLUMN size_bytes     INT(19) UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN download_count INT(19) UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN active         TINYINT(1)    NOT NULL DEFAULT 1;

-- STEP 2  Backfill scalar metadata from old columns
UPDATE vtiger_notes
SET location_type  = CASE WHEN filelocationtype = 'E' THEN 'external' ELSE 'internal' END,
    mime_type      = NULLIF(filetype, ''),
    size_bytes     = COALESCE(filesize, 0),
    download_count = COALESCE(filedownloadcount, 0),
    active         = COALESCE(filestatus, 1),
    external_url   = CASE WHEN filelocationtype = 'E' THEN filename ELSE NULL END,
    original_name  = CASE WHEN filelocationtype = 'E' THEN NULL ELSE filename END;

-- STEP 3  Backfill storage_path for internal docs from the soon-dead tables
UPDATE vtiger_notes n
JOIN vtiger_seattachmentsrel s ON s.crmid = n.notesid
JOIN vtiger_attachments a      ON a.attachmentsid = s.attachmentsid
SET n.storage_path = CONCAT(a.path, a.attachmentsid, '_', a.name)
WHERE n.location_type = 'internal'
  AND (n.storage_path IS NULL OR n.storage_path = '');

-- STEP 4  Migrate the 13 non-Documents attachments to s_yf_record_files
INSERT INTO s_yf_record_files (crm_record_id, role, storage_path, original_name, mime_type, size_bytes)
SELECT s.crmid,
       CASE WHEN c.setype LIKE '%Image%' THEN 'image' ELSE 'attachment' END,
       CONCAT(a.path, a.attachmentsid, '_', a.name),
       a.name,
       COALESCE(a.type, 'application/octet-stream'),
       0
FROM vtiger_seattachmentsrel s
JOIN vtiger_attachments a   ON a.attachmentsid = s.attachmentsid
JOIN vtiger_crmentity   c   ON c.crmid = s.crmid
WHERE c.setype <> 'Documents';
```

**STEP 5 — PHP migration step (`migrations/Users/m2606XX_documents_storage_rebuild.php`):**

1. For each internal `vtiger_notes` row, `realpath`-verify `storage_path`
   (`ROOT_DIRECTORY . DIRECTORY_SEPARATOR . storage_path`, applying `decodeHtml` to the name as
   today). Missing file (incl. the 456 no-attachment rows) → `active = 0`, `storage_path = NULL`.
2. Same disk-verify + `size_bytes = filesize()` backfill for `s_yf_record_files`.
3. **Purge orphans (D3)** — delete `vtiger_notes` (and `vtiger_notescf`, `vtiger_senotesrel`)
   where the `notesid` has no `vtiger_crmentity` row. ~68,956 notes + 12,980 senotesrel.
4. Delete stranded `vtiger_crmentity` rows with `setype LIKE '% Attachment'` /
   `setype LIKE '% Image'` (the throwaway attachment entities) once unreferenced.

```sql
-- STEP 6  Drop old columns + dead columns
ALTER TABLE vtiger_notes
  DROP COLUMN filename,
  DROP COLUMN filetype,
  DROP COLUMN filesize,
  DROP COLUMN filelocationtype,
  DROP COLUMN filestatus,
  DROP COLUMN filedownloadcount,
  DROP COLUMN fileversion,
  DROP COLUMN ossdc_status;

-- STEP 7  Drop the satellite tables (after STEP 4 moved the 13 rows)
DROP TABLE vtiger_seattachmentsrel;
DROP TABLE vtiger_attachments;
DROP TABLE vtiger_notescf;
```

### Non-conforming rows — explicit handling

| Case | Count | Action |
|------|-------|--------|
| Orphan notes (no crmentity, no blob) | 68,956 | **Delete** + senotesrel/notescf (D3) |
| Active internal, no attachment row | 456 | Keep, `active=0`, `storage_path=NULL` |
| `filelocationtype` NULL | 56 | → `location_type='internal'`, disk-verify → likely `active=0` |
| External (`E`) | 3 | `external_url=filename`, `storage_path=NULL` |
| rel → missing attachment / attachment → no rel | 5 / 9 | dropped with the tables (after STEP 4 skips broken joins) |

---

## Implementation plan (ordered, each step committable)

1. **Schema add** — STEP 1 ALTER; create `s_yf_record_files`. System still runs on old code.
2. **Backfill** — STEPS 2–4 SQL + STEP 5 PHP (disk verify; orphan purge gated behind the
   migration so re-runs are safe).
3. **Backend rewrite** — `Documents\Models\Record`, `Base\Models\Record::uploadAndSaveFile`,
   `DocumentsFileUpload` UiType (read/write new columns + `storage_path`).
4. **Update every consumer** — Mail, TemplateAttachment, Candidates CV + CvImport, Webservices
   lead-convert, RecycleBin, CRMEntity, cleanup script, ExportUtils, ReportRun, QueryGenerator,
   Workflows EditTask.
5. **Record images / non-Documents attachments** — Contacts/Products/HelpDesk/Users/Vendors +
   `Image` UiType + templates → `s_yf_record_files`.
6. **Metadata** — `vtiger_field.columnname` updates, regenerate `getAttachments` relatedlists,
   remove `fileversion`/`ossdc_status`/`vtiger_attachmentsfolder` WS seed; update
   `scheme.sql`/`data.sql`/`Base*.php`.
7. **Drop legacy** — STEP 6 + STEP 7; delete `DocumentsFolder` UiType/tpl; remove every
   dropped-table reference.
8. **Privileges + cache** — `bin/regenerate_user_privileges.php`; clear `cache/templates_c/*.php`.
9. **Grep gate** — zero references to `vtiger_attachments`, `vtiger_seattachmentsrel`,
   `vtiger_notescf`, `vtiger_attachmentsfolder`, `filelocationtype`, `filedownloadcount`,
   `fileversion`, `ossdc_status`, `getFileDetails`, `DocumentsFolder` remain.

Step 7 (legacy deletion) is part of this CR, not a follow-up.

---

## Testing

**Smoke (happy path + edges)**
- Upload internal document → file under `storage/`, download streams, `download_count` +1.
- Create external-URL document → opens URL; no disk file.
- Create document with no file → `active=0`, shown non-downloadable; run CheckFileIntegrity.
- Link document to Account / Candidate / Project; related list renders; unlink; recycle-bin
  permanent delete removes the file from disk and the row.
- Email template: link + unlink + list; compose from template → file auto-attaches
  (`assertAllFilesPresent`); send.
- Candidates: "Set as CV" + Export CV ZIP; CV-import cron creates a document.
- HelpDesk report-issue screenshot upload + view (via `s_yf_record_files`).
- Lead → Account conversion carries linked documents.
- Documents in a Report / list export.

**Regression areas** — Documents ListView (folder sidebar, mass-move, mass-add), DetailView
(download/integrity links, relations tab), ModTracker timeline download link.

**Data integrity checks (post-migration)**
```sql
SELECT COUNT(*) FROM vtiger_notes;                                  -- ~31,050
SELECT COUNT(*) FROM vtiger_notes n LEFT JOIN vtiger_crmentity c
  ON c.crmid=n.notesid WHERE c.crmid IS NULL;                       -- 0
SELECT COUNT(*) FROM vtiger_notes
  WHERE location_type='internal' AND active=1 AND storage_path IS NULL; -- 0
SELECT COUNT(*) FROM information_schema.tables
  WHERE table_schema='freecrm'
    AND table_name IN ('vtiger_attachments','vtiger_seattachmentsrel','vtiger_notescf'); -- 0
```

**Logs** — `cache/logs/system.log`, `cron.log` (see `error-checking.mdc`).

---

## Rollback

Destructive (DROP tables + 8 dropped columns + ~69k row delete). Primary rollback =
**restore from the backup taken immediately before the migration**; accepted loss = anything
written since that backup (brief maintenance window). Steps 1–2 alone (additive) are reversible
by dropping the new columns and `s_yf_record_files`. Take `mysqldump` of `vtiger_notes`,
`vtiger_notescf`, `vtiger_attachments`, `vtiger_seattachmentsrel`, `vtiger_senotesrel`,
`vtiger_crmentity` (Documents + attachment setypes) before STEP 5.

---

## Edge cases

- **Re-run safety** — orphan purge and column drops guarded (`IF EXISTS` / existence checks);
  backfill `WHERE storage_path IS NULL` so reapplying STEP 3 is a no-op.
- **`decodeHtml` names** — STEP 3 builds the path from raw `a.name`; STEP 5 applies the same
  `ListViewUtils::decodeHtml` the old `downloadFile` used, so files with HTML-encoded names
  resolve identically; failures flag `active=0` instead of 500-ing.
- **Stranded crmentity** — attachment-setype rows deleted only after `seattachmentsrel` no
  longer references them (STEP 4 already copied the 13 live ones).
- **Missed callers** — the grep gate (step 9) over the dropped identifiers is the proof-of-
  completeness; the consumer map in this CR is the checklist.
- **DocumentTemplates (3 rows)** — these are template *files* on tickets/templates; they land in
  `s_yf_record_files` as `attachment`; confirm DocumentTemplates rendering reads the new table.

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Dropping shared `vtiger_attachments`/`seattachmentsrel` while a record-image path still queries them | High | Mandatory step 5 + grep gate before step 7 |
| Deleting 68,956 orphan notes | Med | Pre-backup; rows are invisible + file-less; D3 confirmed |
| A missed consumer reintroduces a dead-table query post-deploy | Med | Step 9 grep gate; consumer map |
| `storage_path` backfill mis-resolves an encoded name | Low | Disk-verify flags `active=0` rather than breaking download |
| Field-metadata `columnname` drift vs code | Low | `regenerate_user_privileges` + integrity SQL |

---

## Decision rationale & tradeoffs

- **Keep `vtiger_notes` as a CRM entity** rather than extracting Documents into a standalone
  table: Documents' value is being a first-class record (relations via `senotesrel`, sharing,
  related lists on ~50 modules, recycle bin, reports, lead conversion). De-entitying explodes
  scope for no storage benefit. The duplication lived *between* `notes` and `attachments` — so
  dropping `attachments` removes it.
- **`storage_path` (explicit) over reconstruction**: mirrors the already-clean
  `u_yf_mail_attachments` model; kills the `path + id + '_' + name` pattern in 13 sites.
- **`s_yf_record_files` (new, tiny) over keeping the legacy tables for 13 rows**: keeping them
  would preserve a whole parallel path — forbidden by the modernization stance. The new table is
  clean and future-proof for record-scoped files.
- **Clean column rename (D1)**: chosen despite field-metadata churn, consistent with the broader
  CRM rebuild; names now state intent (`location_type`, `size_bytes`, `active`).
