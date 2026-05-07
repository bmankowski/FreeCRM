# FreeCRM synchronization guide (10.0.0.220 → localhost)

This guide documents a **repeatable** way to make `localhost` contain the same:

- **Candidates** (`Kandydaci`)
- **Recruitment Projects** (`ProjektyRekrutacyjne`)
- **Comments** (`ModComments`) attached to candidates and projects

…from the source CRM at `10.0.0.220`, **with the same IDs**, and with data consistent enough for the UI to work (detail view, relations, CV images, comments timeline).

It is written for the current FreeCRM Docker setup in this repo (`docker-compose.yml`) and for the source server layout observed on `10.0.0.220` (`/var/www/yetiforce`).

---

## What must match (minimum viable sync)

For Candidates + Projects + Comments to behave correctly on localhost, these pieces must be in sync:

- **Entity core**: `vtiger_crmentity` rows for each record ID you sync
  - critical columns: `crmid`, `setype`, `deleted`, `smownerid`, `createdtime`, `modifiedtime`
- **Candidates module data**: `u_yf_kandydaci` (main) + `u_yf_kandydacicf` (custom fields)
- **Projects module data**: `u_yf_projektyrekrutacyjne` (main) + `u_yf_projektyrekrutacyjnecf` (custom fields)
- **Candidate ↔ Project membership relation**:
  - `u_yf_projekty_rekrutacyjne_relations_members_entity` (contains `recruitment_status_rel`, `comment_rel`, etc.)
- **Comments (timeline)**
  - `vtiger_modcomments` + `vtiger_modcommentscf` rows
  - corresponding `vtiger_crmentity` rows for the comment IDs (setype `ModComments`)
  - comments are linked to records via `vtiger_modcomments.related_to` (candidate/project record id)
- **Files (storage)**:
  - CV image served by `file.php?...MultiAttachment...` is controlled by the JSON stored in `u_yf_kandydaci.cv_img_file`
  - the **physical file** must exist under `storage/MultiAttachment/...` on localhost (Docker mounts `./storage` into `/var/www/html/storage`)

---

## Safety rules (read this first)

- This is a **high-impact** operation. Do it on a test instance or during downtime.
- Always create **DB backups** before replacing anything.
- Prefer **replace-from-source** semantics for Kandydaci: it avoids “half migrated” states.
- Keep a rollback point:
  - local DB dump
  - and (optionally) separate backup tables for just the candidate set

---

## Prerequisites

### Localhost (this repo / Docker)

- Services running:

```bash
sudo docker compose ps
```

- You can access the DB container and run MariaDB client commands:

```bash
sudo docker compose exec -T db sh -lc "mariadb -uroot -proot -e 'SELECT 1' freecrm"
```

### Source (10.0.0.220)

- SSH access works:

```bash
ssh 10.0.0.220 'whoami && hostname'
```

- Source DB credentials (as currently configured on the server):
  - host: `localhost`
  - db: `yetiforce2`
  - user: `yetiforceuser`
  - password: `yetiforcepassword`

Verify quickly:

```bash
ssh 10.0.0.220 "mysql -hlocalhost -uyetiforceuser -pyetiforcepassword -e 'SELECT COUNT(*) FROM u_yf_kandydaci' yetiforce2"
```

---

## Recommended strategy: “export datasets” → “rebuild datasets on localhost”

Because IDs must match and many tables reference `crmid`, the most reliable approach is:

1. Export **only** the needed rows (candidates + projects + comments + relations) from source.
2. On localhost:
   - delete/replace the local datasets
   - import the source datasets
3. Rebuild derived/search tables and clear caches if needed.

This avoids subtle drift like:
- `u_yf_kandydaci` row exists but `vtiger_crmentity.setype` is wrong
- `createdtime`/`modifiedtime` differs
- relation rows exist but point to missing projects

---

## Step 0 — Decide scope

Pick one:

- **A. Candidates only (minimal)**:
  - Candidates open correctly and have correct timestamps
  - project widgets and comment timelines may be incomplete
- **B. Candidates + projects + relations + comments (recommended for your setup)**:
  - Candidate detail widget “projects with status” works
  - project kanban/statistics stay consistent
  - comments/timeline on candidates and projects work

This guide assumes **B**.

---

## Step 1 — Full local DB backup (rollback)

From localhost:

```bash
mkdir -p tmp/db-backups
sudo docker compose exec -T db sh -lc "mariadb-dump -uroot -proot --single-transaction --routines --triggers freecrm" > tmp/db-backups/freecrm-$(date +%F_%H%M%S).sql
```

Optional: compress it.

---

## Step 2 — Export from source (10.0.0.220)

### 2.1 Export candidate IDs (reference set, NOT deleted)

```bash
ssh 10.0.0.220 "mysql -N -B -hlocalhost -uyetiforceuser -pyetiforcepassword yetiforce2 -e \\
  \"SELECT crmid FROM vtiger_crmentity WHERE setype='Kandydaci' AND deleted=0;\" \\
  > /tmp/kandydaci_ids.tsv"
```

### 2.2 Export required tables (filtered to candidate set)

Create a dump that includes only rows for candidate IDs.
We use `--where` for each table.

```bash
ssh 10.0.0.220 "
set -e
IDS='/tmp/kandydaci_ids.tsv'

# helper: produce a comma-separated list for WHERE IN (...)
IDLIST=\$(paste -sd, \"\$IDS\")

mysqldump -hlocalhost -uyetiforceuser -pyetiforcepassword yetiforce2 \\
  --single-transaction --skip-lock-tables --no-tablespaces \\
  vtiger_crmentity --where=\"crmid IN (\$IDLIST)\" \\
  u_yf_kandydaci --where=\"kandydaciid IN (\$IDLIST)\" \\
  u_yf_kandydacicf --where=\"kandydaciid IN (\$IDLIST)\" \\
  > /tmp/kandydaci_core.sql
"
```

### 2.3 Export all projects (IDs must match)

Because you want **all projects** on localhost (not just projects referenced by existing local candidates), export the entire `ProjektyRekrutacyjne` dataset from the source.

```bash
ssh 10.0.0.220 "
set -e
mysql -N -B -hlocalhost -uyetiforceuser -pyetiforcepassword yetiforce2 -e \\
  \"SELECT crmid FROM vtiger_crmentity WHERE setype='ProjektyRekrutacyjne' AND deleted=0;\" \\
  > /tmp/projekty_ids.tsv

PIDLIST=\$(paste -sd, /tmp/projekty_ids.tsv)
mysqldump -hlocalhost -uyetiforceuser -pyetiforcepassword yetiforce2 \\
  --single-transaction --skip-lock-tables --no-tablespaces \\
  vtiger_crmentity --where=\"crmid IN (\$PIDLIST)\" \\
  u_yf_projektyrekrutacyjne --where=\"projektyrekrutacyjneid IN (\$PIDLIST)\" \\
  u_yf_projektyrekrutacyjnecf --where=\"projektyrekrutacyjneid IN (\$PIDLIST)\" \\
  > /tmp/projekty_core.sql
"
```

### 2.4 Export candidate ↔ project relations

Export relations for those candidates (and any projects they reference):

```bash
ssh 10.0.0.220 "
set -e
IDS='/tmp/kandydaci_ids.tsv'
IDLIST=\$(paste -sd, \"\$IDS\")

mysqldump -hlocalhost -uyetiforceuser -pyetiforcepassword yetiforce2 \\
  --single-transaction --skip-lock-tables --no-tablespaces \\
  u_yf_projekty_rekrutacyjne_relations_members_entity \\
  --where=\"relcrmid IN (\$IDLIST) OR crmid IN (\$IDLIST)\" \\
  > /tmp/kandydaci_relations.sql

# no additional project-id export needed here if you already sync all projects (2.3)
"
```

### 2.5 Export comments (ModComments) for candidates and projects

We sync all comments that point to either a candidate id or project id via `vtiger_modcomments.related_to`.

```bash
ssh 10.0.0.220 "
set -e
IDS='/tmp/kandydaci_ids.tsv'
PIDS='/tmp/projekty_ids.tsv'
IDLIST=\$(paste -sd, \"\$IDS\")
PIDLIST=\$(paste -sd, \"\$PIDS\")

# comment ids referencing candidates or projects
mysql -N -B -hlocalhost -uyetiforceuser -pyetiforcepassword yetiforce2 -e \\
  \"SELECT modcommentsid FROM vtiger_modcomments WHERE related_to IN (\$IDLIST,\$PIDLIST);\" \\
  > /tmp/modcomments_ids.tsv

CMIDLIST=\$(paste -sd, /tmp/modcomments_ids.tsv)
mysqldump -hlocalhost -uyetiforceuser -pyetiforcepassword yetiforce2 \\
  --single-transaction --skip-lock-tables --no-tablespaces \\
  vtiger_crmentity --where=\"crmid IN (\$CMIDLIST)\" \\
  vtiger_modcomments --where=\"modcommentsid IN (\$CMIDLIST)\" \\
  vtiger_modcommentscf --where=\"modcommentsid IN (\$CMIDLIST)\" \\
  > /tmp/modcomments_core.sql
"
```

### 2.6 Copy dumps to localhost

```bash
mkdir -p tmp/sync
scp 10.0.0.220:/tmp/kandydaci_core.sql tmp/sync/
scp 10.0.0.220:/tmp/projekty_core.sql tmp/sync/
scp 10.0.0.220:/tmp/kandydaci_relations.sql tmp/sync/
scp 10.0.0.220:/tmp/modcomments_core.sql tmp/sync/
```

```bash
ssh 10.0.0.220 "
set -e
PIDS='/tmp/projekty_ids.tsv'
PIDLIST=\$(paste -sd, \"\$PIDS\")

mysqldump -hlocalhost -uyetiforceuser -pyetiforcepassword yetiforce2 \\
  --single-transaction --skip-lock-tables --no-tablespaces \\
  vtiger_crmentity --where=\"crmid IN (\$PIDLIST)\" \\
  u_yf_projektyrekrutacyjne --where=\"projektyrekrutacyjneid IN (\$PIDLIST)\" \\
  u_yf_projektyrekrutacyjnecf --where=\"projektyrekrutacyjneid IN (\$PIDLIST)\" \\
  > /tmp/projekty_core.sql
"
```

---

---

## Step 3 — Sync `storage/` (files)

If you want CV images and other attachments to render, sync storage too.

**Source storage root** on 10.0.0.220 (observed):
- `/var/www/yetiforce/storage/`

Local storage root in Docker:
- `./storage/` (mounted into containers as `/var/www/html/storage`)

Recommended:

```bash
rsync -a --delete -e ssh 10.0.0.220:/var/www/yetiforce/storage/ ./storage/
```

If you can’t use `--delete`, drop it and accept leftovers.

---

## Step 4 — Apply on localhost (DB)

### 4.1 Load dumps into the DB container

```bash
cid=$(sudo docker compose ps -q db)
sudo docker cp tmp/sync/kandydaci_core.sql "$cid:/tmp/kandydaci_core.sql"
sudo docker cp tmp/sync/projekty_core.sql "$cid:/tmp/projekty_core.sql"
sudo docker cp tmp/sync/kandydaci_relations.sql "$cid:/tmp/kandydaci_relations.sql"
sudo docker cp tmp/sync/modcomments_core.sql "$cid:/tmp/modcomments_core.sql"
```

### 4.2 Replace local Kandydaci (FULL replace) + ProjektyRekrutacyjne + relations + comments (recommended)

Because IDs must match and you want “same IDs”, the clean approach is:
- delete the local rows that will be replaced
- import source rows

**Important**: order matters because of FKs.

```bash
sudo docker compose exec -T db sh -lc "
set -e
mariadb -uroot -proot freecrm -e '
  SET FOREIGN_KEY_CHECKS=0;

  -- comments first (they reference candidates/projects)
  DELETE FROM vtiger_modcommentscf WHERE modcommentsid IN (SELECT modcommentsid FROM vtiger_modcomments WHERE related_to IN (SELECT kandydaciid FROM u_yf_kandydaci));
  DELETE FROM vtiger_modcomments WHERE related_to IN (SELECT kandydaciid FROM u_yf_kandydaci);
  DELETE FROM vtiger_crmentity WHERE setype=\"ModComments\" AND crmid NOT IN (SELECT modcommentsid FROM vtiger_modcomments);

  -- relations first (candidate ↔ project)
  DELETE FROM u_yf_projekty_rekrutacyjne_relations_members_entity
    WHERE relcrmid IN (SELECT kandydaciid FROM u_yf_kandydaci)
       OR crmid IN (SELECT kandydaciid FROM u_yf_kandydaci);

  -- candidates: FULL replace (local candidates are removed unconditionally)
  DELETE FROM u_yf_kandydacicf;
  DELETE FROM u_yf_kandydaci;

  -- NOTE: vtiger_crmentity also contains other modules; only remove candidate rows
  DELETE FROM vtiger_crmentity WHERE setype=\"Kandydaci\";

  -- projects (optional: if you want full sync, replace projects too)
  DELETE FROM u_yf_projektyrekrutacyjnecf WHERE projektyrekrutacyjneid IN (SELECT projektyrekrutacyjneid FROM u_yf_projektyrekrutacyjne);
  DELETE FROM u_yf_projektyrekrutacyjne;
  DELETE FROM vtiger_crmentity WHERE setype=\"ProjektyRekrutacyjne\";

  SET FOREIGN_KEY_CHECKS=1;
';
"
```

Now import:

```bash
sudo docker compose exec -T db sh -lc "
set -e
mariadb -uroot -proot freecrm < /tmp/projekty_core.sql
mariadb -uroot -proot freecrm < /tmp/kandydaci_core.sql
mariadb -uroot -proot freecrm < /tmp/kandydaci_relations.sql
mariadb -uroot -proot freecrm < /tmp/modcomments_core.sql
"
```

---

## Step 5 — Post-sync verification

### 5.1 Counts

```bash
sudo docker compose exec -T db sh -lc "mariadb -N -B -uroot -proot freecrm -e '
  SELECT
    (SELECT COUNT(*) FROM u_yf_kandydaci) AS kandydaci,
    (SELECT COUNT(*) FROM u_yf_kandydacicf) AS kandydaci_cf,
    (SELECT COUNT(*) FROM vtiger_crmentity WHERE setype=\"Kandydaci\") AS crmentity_kandydaci,
    (SELECT COUNT(*) FROM u_yf_projektyrekrutacyjne) AS projekty,
    (SELECT COUNT(*) FROM vtiger_crmentity WHERE setype=\"ProjektyRekrutacyjne\") AS crmentity_projekty,
    (SELECT COUNT(*) FROM vtiger_modcomments) AS modcomments,
    (SELECT COUNT(*) FROM u_yf_projekty_rekrutacyjne_relations_members_entity) AS rel_members;
'"
```

### 5.2 Spot checks in UI

- Candidate detail should open:
  - `http://localhost/index.php?module=Kandydaci&view=Detail&record=1896`
- Confirm timestamps and status widget render without “belongs to module …” errors.

### 5.3 Logs

Check:
- `cache/logs/system.log`

---

## Notes / gotchas

- **Huge IN lists**: if `paste -sd,` makes a too-long query or mysqldump `--where` hits limits, switch to an approach using a temporary table on source and join-based exports.
- **Permissions/ownership** for `storage/`: ensure files are readable by the web container user.
- **Derived tables / caches**: if you see stale labels or missing relations, clear `cache/` and restart containers.

