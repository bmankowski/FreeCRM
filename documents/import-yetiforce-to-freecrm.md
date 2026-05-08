# Import Documents (yetiforce → freecrm_new)

This note captures the exact process we used to import the **Documents** module (aka `vtiger_notes`) from the `yetiforce` schema into the FreeCRM schema `freecrm_new`, preserving IDs and keeping attachments consistent with `storage/`.

## Preconditions

- MariaDB is running in Docker (`db` service).
- Source schema exists: `yetiforce`
- Destination schema exists: `freecrm_new`
- `storage/` on disk already contains the referenced attachment files (we copied storage earlier).

## What gets imported

Documents in this codebase map to:

- `vtiger_notes` (document core)
- `vtiger_notescf` (document custom fields)
- `vtiger_crmentity` rows with `setype='Documents'` (entity metadata)
- `vtiger_senotesrel` (relations: document ↔ other records, e.g. Kandydaci)
- `vtiger_attachments` (attachment metadata)
- `vtiger_seattachmentsrel` (relations: document ↔ attachment)

## Quick “are structures compatible?”

We intentionally skipped “column intersection” when copying between FreeCRM schemas because tables were identical.

Run inside the DB container:

```bash
sudo docker compose exec -T db sh -lc 'mariadb -uroot -proot -N -e "
SELECT \"vtiger_notes\",(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_new\" AND table_name=\"vtiger_notes\"),(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_working\" AND table_name=\"vtiger_notes\")
UNION ALL SELECT \"vtiger_notescf\",(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_new\" AND table_name=\"vtiger_notescf\"),(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_working\" AND table_name=\"vtiger_notescf\")
UNION ALL SELECT \"vtiger_senotesrel\",(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_new\" AND table_name=\"vtiger_senotesrel\"),(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_working\" AND table_name=\"vtiger_senotesrel\")
UNION ALL SELECT \"vtiger_attachments\",(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_new\" AND table_name=\"vtiger_attachments\"),(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_working\" AND table_name=\"vtiger_attachments\")
UNION ALL SELECT \"vtiger_seattachmentsrel\",(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_new\" AND table_name=\"vtiger_seattachmentsrel\"),(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_working\" AND table_name=\"vtiger_seattachmentsrel\")
UNION ALL SELECT \"vtiger_crmentity\",(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_new\" AND table_name=\"vtiger_crmentity\"),(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=\"freecrm_working\" AND table_name=\"vtiger_crmentity\");
"'
```

If the counts match for each table, `INSERT ... SELECT * ...` between schemas is safe.

## Step A: Import Documents from `yetiforce` into a “working” schema (example: `freecrm_working`)

This is the most robust pattern for cross-version imports: use column intersection (`information_schema`) when copying from prod-like schema → FreeCRM schema.

We imported **non-deleted** documents by building an ID set from `yetiforce.vtiger_crmentity.deleted=0` joined to `yetiforce.vtiger_notes`.

Implementation used a single SQL session (run against the destination DB, e.g. `freecrm_working`) that:

1) builds temporary tables:
- `tmp_doc_ids` (notes IDs)
- `tmp_doc_attach_ids` (attachment IDs for those notes)
2) deletes existing destination rows for those IDs
3) inserts destination rows using **column intersection** (dynamic `@cols` + prepared statements)

The exact SQL used lives in the Cursor chat history (search for the command containing `tmp_doc_ids` and `tmp_doc_attach_ids` and multiple `PREPARE s FROM @sql` blocks).

## Step B: Copy Documents from `freecrm_working` → `freecrm_new` (same-structure copy)

Once Documents were correct in `freecrm_working`, we copied the data into the app DB `freecrm_new` without column intersection.

Run inside the DB container:

```bash
sudo docker compose exec -T db mariadb -uroot -proot freecrm_new <<'SQL'
SET FOREIGN_KEY_CHECKS=0;

DROP TEMPORARY TABLE IF EXISTS tmp_doc_ids;
CREATE TEMPORARY TABLE tmp_doc_ids (id INT PRIMARY KEY) ENGINE=MEMORY;
INSERT INTO tmp_doc_ids(id)
  SELECT n.notesid
  FROM freecrm_working.vtiger_notes n
  JOIN freecrm_working.vtiger_crmentity e ON e.crmid=n.notesid
  WHERE e.deleted=0;

DROP TEMPORARY TABLE IF EXISTS tmp_doc_attach_ids;
CREATE TEMPORARY TABLE tmp_doc_attach_ids (id INT PRIMARY KEY) ENGINE=MEMORY;
INSERT INTO tmp_doc_attach_ids(id)
  SELECT DISTINCT r.attachmentsid
  FROM freecrm_working.vtiger_seattachmentsrel r
  JOIN tmp_doc_ids d ON d.id=r.crmid;

-- Cleanup in freecrm_new (only the docs we are about to copy)
DELETE r FROM vtiger_senotesrel r JOIN tmp_doc_ids d ON d.id=r.notesid;
DELETE r FROM vtiger_seattachmentsrel r JOIN tmp_doc_ids d ON d.id=r.crmid;
DELETE c FROM vtiger_crmentity c JOIN tmp_doc_ids d ON d.id=c.crmid;
DELETE cf FROM vtiger_notescf cf JOIN tmp_doc_ids d ON d.id=cf.notesid;
DELETE n FROM vtiger_notes n JOIN tmp_doc_ids d ON d.id=n.notesid;

-- attachments referenced by those docs
DELETE ar FROM vtiger_seattachmentsrel ar JOIN tmp_doc_attach_ids a ON a.id=ar.attachmentsid;
DELETE a FROM vtiger_attachments a JOIN tmp_doc_attach_ids t ON t.id=a.attachmentsid;

-- Copy data
INSERT INTO vtiger_notes SELECT * FROM freecrm_working.vtiger_notes WHERE notesid IN (SELECT id FROM tmp_doc_ids);
INSERT INTO vtiger_notescf SELECT * FROM freecrm_working.vtiger_notescf WHERE notesid IN (SELECT id FROM tmp_doc_ids);
INSERT INTO vtiger_senotesrel SELECT * FROM freecrm_working.vtiger_senotesrel WHERE notesid IN (SELECT id FROM tmp_doc_ids);
INSERT INTO vtiger_crmentity SELECT * FROM freecrm_working.vtiger_crmentity WHERE crmid IN (SELECT id FROM tmp_doc_ids);

INSERT INTO vtiger_attachments SELECT * FROM freecrm_working.vtiger_attachments WHERE attachmentsid IN (SELECT id FROM tmp_doc_attach_ids);
INSERT INTO vtiger_seattachmentsrel SELECT * FROM freecrm_working.vtiger_seattachmentsrel WHERE crmid IN (SELECT id FROM tmp_doc_ids);

SET FOREIGN_KEY_CHECKS=1;
SQL
```

## Verification checklist

### Row counts

```bash
sudo docker compose exec -T db sh -lc 'mariadb -uroot -proot -N -e "
SELECT COUNT(*) FROM freecrm_new.vtiger_notes;
SELECT COUNT(*) FROM freecrm_new.vtiger_attachments;
SELECT COUNT(*) FROM freecrm_new.vtiger_crmentity WHERE setype=\"Documents\";
"'
```

### Candidate has the expected document

Example from debugging:

- Candidate `1441792` should have `notesid=1441794`.

```bash
sudo docker compose exec -T db sh -lc 'mariadb -uroot -proot -N -e "
SELECT COUNT(*) FROM freecrm_new.vtiger_senotesrel WHERE crmid=1441792;
SELECT notesid FROM freecrm_new.vtiger_senotesrel WHERE crmid=1441792;
SELECT n.notesid,n.title,n.filename,e.deleted
FROM freecrm_new.vtiger_notes n
JOIN freecrm_new.vtiger_crmentity e ON e.crmid=n.notesid
WHERE n.notesid=1441794;
"'
```

### Attachment file exists on disk

Pick an attachment row:

```bash
sudo docker compose exec -T db sh -lc 'mariadb -uroot -proot -N -e "
SELECT r.attachmentsid,a.name,a.path
FROM freecrm_new.vtiger_seattachmentsrel r
JOIN freecrm_new.vtiger_attachments a ON a.attachmentsid=r.attachmentsid
WHERE r.crmid=1441794
LIMIT 5;
"'
```

Then verify in the app container:

```bash
sudo docker compose exec -T app sh -lc 'test -f "storage/Documents/2026/May/week1/<ATTACHMENTSID>" && echo OK || echo MISSING'
```

## Notes / gotchas

- If Documents exist but **don’t show on a record**, the most common cause is missing rows in `vtiger_senotesrel` for that record, not missing `vtiger_notes`.
- If the UI shows a document but download fails, check:
  - `vtiger_seattachmentsrel` links doc → attachment id
  - `vtiger_attachments.path` matches your `storage/` layout
  - the numeric file under `storage/.../<attachmentsid>` exists

