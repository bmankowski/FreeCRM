Below is the implementation plan. It treats the architecture doc as the contract and decomposes it into vertical, independently deployable slices. Where it matters, file paths and SQL are concrete to FreeCRM's actual layout (verified above: cron via `vtiger_cron_task`, config in `config/modules/Mail.php`, Settings UI in `src/Modules/Settings/...`).

I'm presenting inline rather than committing to a file per the approval-mode rule; say the word and I'll save it as `documentation/delayed-email-implementation-plan.md`.

---

# 1. Implementation Overview

- **Phases:** 6 deployable phases, all backward-compatible.
- **Critical path:** Phase 0 (schema) → Phase 1 (audit) is *independent* of Phase 2–5 and ships first because it produces value across **all** email paths the day it's enabled. Buffer (2 → 4) is the second track.
- **Deployment strategy:** Six small deploys, each independently revertible. No big-bang. Schema changes precede code; code precedes flag flips; flag flips precede caller migration.
- **First production-safe increment:** Phase 0 (schema only — zero behavior change, can deploy any time).
- **Earliest point where value appears:** End of Phase 1 — every email sent through `s_yf_mail_queue` is auditable, regardless of caller. Buffered emails start delivering value at the end of Phase 4.

| Phase | Title | Code shipped | Behavior change | Flag flip |
|---|---|---|---|---|
| 0 | Schema | DDL | None | — |
| 1 | Delivery audit | `MailerTask` patch, `CleanupMailAuditLogTask` | None until flag flipped | `MAIL_AUDIT_LOG_ENABLED` |
| 2 | Buffer skeleton (dormant) | Enum, resolver, `Buffer` service, `DelayedEmailQueueTask` | None — cron registered but disabled | — |
| 3 | Settings UI | `Settings:DelayedEmails` module | None — read/inspect only | — |
| 4 | First caller wired | Workflow status-change call site | Buffered emails go live | `DELAYED_EMAIL_BUFFER_ENABLED` + cron `status=1` |
| 5 | Hardening | Runbook, monitoring dashboards, language strings | None | — |

---

# 2. Dependency Graph

```
                Phase 0 (schema)
                ┌────┴────┐
                │         │
       Phase 1  │         │  Phase 2
       (audit)  │         │  (buffer skeleton, dormant)
                │         │
                │         ├─────────────→ Phase 3 (Settings UI)
                │         │
                │         └─────────────→ Phase 4 (wire first caller)
                │                              │
                └──────────────────────────────┴──→ Phase 5 (hardening)
```

Blockers:

- Phase 1 cannot ship without Phase 0 (needs `s_yf_mail_sent_log`).
- Phase 2 cannot ship without Phase 0 (needs `s_yf_delayed_email_queue` + `s_yf_mail_queue.source_module`).
- Phase 3 requires Phase 2 (queries the buffer table).
- Phase 4 requires Phase 2 (services exist) and ideally Phase 1 (so the first buffered sends are immediately audit-logged) and Phase 3 (operators must be able to inspect).
- Phase 5 has no blockers within this feature.

Phase 1 and Phase 2 can be done by two engineers in parallel after Phase 0.

---

# 3. Detailed Phase Plan

## Phase 0 — Schema

### Objective
Land all DDL changes with zero application behavior change. Be done with schema risk before any code references the new tables.

### Files / Modules affected

Database (live):
- `s_yf_delayed_email_queue` (new)
- `s_yf_mail_sent_log` (new)
- `s_yf_mail_queue` (alter: 2 columns + index)

Repo:
- `src/Modules/Install/install_schema/scheme.sql` — append the three definitions so fresh installs match production
- `src/Modules/Install/install_schema/data.sql` — append cron-task seed rows (status=0 disabled by default) for `DelayedEmailQueueTask` and `CleanupMailAuditLogTask`

### Tasks
1. Apply the three DDL statements (§4) on the live MariaDB via MCP (per `mcp-database-access.mdc`).
2. Mirror the same DDL into `scheme.sql` for greenfield installs.
3. Append the two `vtiger_cron_task` rows (status=0, frequency=60 for buffer, 86400 for cleanup) to `data.sql` and live DB.
4. Sanity-check: `SHOW CREATE TABLE` matches both repo and live.

### Acceptance Criteria
- `DESCRIBE s_yf_delayed_email_queue;` returns the expected columns + the `uniq_pair_type` unique key + `idx_due` index.
- `DESCRIBE s_yf_mail_sent_log;` returns the expected columns + indexes.
- `DESCRIBE s_yf_mail_queue;` includes `source_module` and `source_id` (both nullable).
- `SELECT name, status FROM vtiger_cron_task WHERE name IN ('LBL_DELAYED_EMAIL_QUEUE','LBL_CLEANUP_MAIL_AUDIT_LOG');` returns 2 rows with `status=0`.
- No new entries in `cache/logs/system.log` after the changes.

### Rollback Strategy
```sql
DROP TABLE s_yf_delayed_email_queue;
DROP TABLE s_yf_mail_sent_log;
ALTER TABLE s_yf_mail_queue DROP KEY idx_source, DROP COLUMN source_id, DROP COLUMN source_module;
DELETE FROM vtiger_cron_task WHERE name IN ('LBL_DELAYED_EMAIL_QUEUE','LBL_CLEANUP_MAIL_AUDIT_LOG');
```
No code references the new tables yet, so rollback is safe at any moment in Phase 0.

---

## Phase 1 — Delivery audit (independent value track)

### Objective
Every email leaving `MailerTask` produces exactly one audit row. Roll out behind a flag; flip on once verified.

### Files / Modules affected

Code:
- `src/Modules/Cron/Tasks/MailerTask.php` — wrap post-attempt block in a transaction, insert into `s_#__mail_sent_log`
- `src/Modules/Cron/Tasks/CleanupMailAuditLogTask.php` — new cron task
- `src/Email/Mailer.php` — extend `static $quoteJsonColumn` and accept `source_module`/`source_id` in `addMail()` params (forward-only; existing callers unaffected)

Config:
- `config/modules/Mail.php` — add `MAIL_AUDIT_LOG_ENABLED` (default `false`) and `AUDIT_LOG_RETENTION_DAYS` (default `365`)

Database:
- `vtiger_cron_task`: flip `LBL_CLEANUP_MAIL_AUDIT_LOG` to `status=1` *after* the code is live and flag is on

### Tasks
1. Add the two config keys to `config/modules/Mail.php`.
2. Modify `MailerTask::execute()`:
   - If flag off → existing behavior, no audit row.
   - If flag on → wrap `delete`/`update` and the audit `insert` in `$db->transaction(...)`.
   - On thrown exceptions from `sendByRowQueue`, catch and record as failure with `error`.
3. Create `CleanupMailAuditLogTask` next to `MailerTask`. Delete rows older than `AUDIT_LOG_RETENTION_DAYS`.
4. Extend `Mailer::addMail()` to pass-through `source_module` and `source_id` if present in `$params`. Existing callers must keep working unchanged.

### Acceptance Criteria
- With `MAIL_AUDIT_LOG_ENABLED=false`: zero rows in `s_yf_mail_sent_log` after running `MailerTask`.
- With `MAIL_AUDIT_LOG_ENABLED=true`: every successful send produces a row with `status=1`; every failed send produces a row with `status=2` and a non-null `error`.
- Killing the cron container mid-`MailerTask` leaves `s_yf_mail_queue` and `s_yf_mail_sent_log` in consistent state (verified by injected sleep + SIGTERM in staging).
- `CleanupMailAuditLogTask` deletes rows older than the configured window in a single statement; no other table touched.

### Rollback Strategy
- Flip `MAIL_AUDIT_LOG_ENABLED` to `false`. `MailerTask` reverts to pre-Phase-1 behavior immediately on next cron tick. No data loss; audit rows already written stay until retention expires.
- If the code itself needs reverting: revert the PR; the unused tables are harmless.

---

## Phase 2 — Buffer skeleton (dormant)

### Objective
Ship all buffer code paths in production with zero callers. Verify in staging by manually calling `Buffer::enqueue()` from a CLI or a test endpoint.

### Files / Modules affected

Code:
- `src/Email/Delayed/DelayedEmailType.php` — enum
- `src/Email/Delayed/RelevanceResolver.php` — interface
- `src/Email/Delayed/Resolvers/StatusChangeResolver.php` — first resolver (placeholder ok; real query plugged in Phase 4)
- `src/Email/Delayed/Buffer.php` — service with `enqueue()`, `cancel()`, `sendNow()`
- `src/Email/Delayed/Email.php` — simple value object (recipients/subject/body)
- `src/Modules/Cron/Tasks/DelayedEmailQueueTask.php` — the §6 implementation

Config:
- `config/modules/Mail.php` — add `DELAYED_EMAIL_BUFFER_ENABLED` (default `false`), `DELAYED_EMAIL_DEFAULT_MINUTES` (default `120`)

Database:
- No new schema (Phase 0 already covers it). Cron-task row stays at `status=0`.

### Tasks
1. Create `DelayedEmailType` enum with the single case `STATUS_CHANGE = 'status_change'`. Add `resolver()` method.
2. Create `RelevanceResolver` interface with `hash(int $sourceId, int $destId): string`.
3. Implement `StatusChangeResolver` with a literal `return hash('sha256', 'TODO')` placeholder — wired for real in Phase 4.
4. Implement `Buffer::enqueue()` as the §5 upsert, using `\App\Db\Db::getInstance('admin')` and `\App\Db\Expression('NOW() + INTERVAL :delay MINUTE')`.
5. Implement `Buffer::cancel(int $sourceId, int $destId, ?DelayedEmailType $type)` as the §8 delete.
6. Implement `Buffer::sendNow(int $bufferId)` — `UPDATE s_#__delayed_email_queue SET send_after = NOW() WHERE id = :id`.
7. Implement `DelayedEmailQueueTask`:
   - Outer `SELECT id ... LIMIT 50`.
   - Per-id `db->transaction()` with `SELECT ... FOR UPDATE`.
   - Inside lock: relevance check → either delete (stale) or addMail + delete (promote).
   - All log lines through `App\Log\Log`.
8. Short-circuit `Buffer::enqueue()` when `DELAYED_EMAIL_BUFFER_ENABLED=false`: fall back to immediate `Mailer::addMail()` (so callers introduced in Phase 4 are safe even before the flag is flipped).

### Acceptance Criteria
- Calling `Buffer::enqueue()` twice for the same `(source, dest, type)` results in exactly one row (verified by `SELECT COUNT(*)`).
- Calling `DelayedEmailQueueTask::execute()` manually with one due, relevant row promotes it: row disappears from `s_yf_delayed_email_queue`, appears in `s_yf_mail_queue` with `source_module='DelayedBuffer'`.
- With the relevance resolver returning a different hash than what was stored, the row is deleted without being promoted.
- Two CLI invocations of `DelayedEmailQueueTask::execute()` started concurrently in staging promote each due row at most once.
- Cron task is registered but disabled (`status=0`); manual invocation works.

### Rollback Strategy
- Cron task remains `status=0`. If anything goes wrong, no buffer cron ever runs.
- Revert the PR if code itself is broken. Tables remain (Phase 0).

---

## Phase 3 — Settings UI (`Settings:DelayedEmails`)

### Objective
Operators can list pending buffered emails and act on them (Cancel, Send now). Useful even before the buffer has live callers — lets QA inspect staging.

### Files / Modules affected

Code:
- `src/Modules/Settings/DelayedEmails/Views/ListView.php`
- `src/Modules/Settings/DelayedEmails/Models/ListView.php`
- `src/Modules/Settings/DelayedEmails/Models/Record.php`
- `src/Modules/Settings/DelayedEmails/Actions/Cancel.php`
- `src/Modules/Settings/DelayedEmails/Actions/SendNow.php`

Templates:
- `layouts/basic/modules/Settings/DelayedEmails/ListView.tpl`
- `layouts/basic/modules/Settings/DelayedEmails/ListViewContent.tpl`

Language strings:
- `languages/en_us/Settings/DelayedEmails.json`
- `languages/pl_pl/Settings/DelayedEmails.json`

Menu registration:
- Add `Settings:DelayedEmails` to the settings menu seed (`s_yf_settings_field` or wherever the Settings menu is registered — verify against existing entries like `Settings:CronTasks`).

### Tasks
1. Pattern-match against `Settings:CronTasks` for module skeleton (controllers, views, templates, settings registration).
2. Wire `ListView` to query `s_yf_delayed_email_queue` joined with the relevant record-name resolution (`source_id` → record label, `dest_id` → record label) — keep this best-effort; show raw IDs if module is unknown.
3. Wire `Cancel` action to call `Buffer::cancel()` by `id`.
4. Wire `SendNow` action to `Buffer::sendNow()`.
5. Follow the MainLayout rule (`/.cursor/rules/main-layout-views.mdc`): `ListView.tpl` must `{extends file="MainLayout.tpl"|@vtemplate_path}`.
6. Per-row recipient column: decode `recipients_json`, show first `to` address.

### Acceptance Criteria
- `Settings → Opóźnione e-maile` (or English equivalent) appears in the settings menu.
- The list view renders the contents of `s_yf_delayed_email_queue` sorted by `send_after ASC`.
- Cancel removes a row; the row disappears from the list on refresh.
- Send now updates `send_after = NOW()`; within ≤ 60 s the row is promoted by the cron (verifiable once cron is enabled in Phase 4; until then, manual `DelayedEmailQueueTask::execute()` from CLI).
- Page renders with the left side menu (MainLayout). No new errors in `cache/logs/system.log`.

### Rollback Strategy
- Revert the PR. The Settings module entry vanishes; no other system is affected.

---

## Phase 4 — Wire the first caller (status-change emails)

### Objective
The first real production traffic flows through the buffer. The feature is now visible to recruiters.

### Files / Modules affected

Code:
- `src/Email/Delayed/Resolvers/StatusChangeResolver.php` — implement the real state hash for the recruitment status-change use case (read the candidate-on-project status from the appropriate `u_yf_...` table).
- Whatever workflow / event handler currently calls `Mailer::addMail()` (or `Mailer::sendFromTemplate()`) for status-change emails — replace with `Buffer::enqueue()`.

Database:
- `UPDATE vtiger_cron_task SET status=1 WHERE name='LBL_DELAYED_EMAIL_QUEUE';`

Config:
- Flip `DELAYED_EMAIL_BUFFER_ENABLED=true` after rollout window starts.

### Tasks
1. Locate the existing status-change email call site (workflow task handler). Audit it for any retry / repeat semantics.
2. Replace direct `addMail` with `Buffer::enqueue($projectId, $candidateId, DelayedEmailType::STATUS_CHANGE, $email)`.
3. Implement `StatusChangeResolver::hash()` to read the actual current status field for the (project, candidate) pair. Confirm with stakeholders what counts as the "state" — likely just the status column.
4. Flip the cron task to `status=1`.
5. Flip `DELAYED_EMAIL_BUFFER_ENABLED=true`.
6. Watch `cache/logs/system.log` and the audit table for the first 2-hour cycle.

### Acceptance Criteria
- A status change from Interview → Rejected creates exactly one row in `s_yf_delayed_email_queue`.
- A subsequent Rejected → Interview within 5 minutes either:
  - Overwrites the row with the corresponding "interview" email (if that workflow path emits one), or
  - Causes a hash mismatch at promote time (if the workflow doesn't re-emit), discarding the rejected email silently.
- 2 hours after a *settled* Interview → Rejected, exactly one audit row appears in `s_yf_mail_sent_log` with `source_module='DelayedBuffer'`.
- No regression in non-buffered email paths (verified by spot-checking `s_yf_mail_sent_log` for non-`DelayedBuffer` source rows).

### Rollback Strategy

Per severity:

1. **Disable behavior, keep data:** set `DELAYED_EMAIL_BUFFER_ENABLED=false`. `Buffer::enqueue()` short-circuits back to immediate `Mailer::addMail()` (Phase 2 fallback). Cron drains residual pending rows over up to ~2 h; or run the cron manually to flush.
2. **Disable cron too:** `UPDATE vtiger_cron_task SET status=0 WHERE name='LBL_DELAYED_EMAIL_QUEUE';`. Pending rows freeze; operators can `cancel()` or `sendNow()` via Settings UI.
3. **Revert caller PR:** workflow goes back to direct `addMail()`. Any pending rows can be cleaned with `Buffer::cancel()` admin-side or `DELETE FROM s_yf_delayed_email_queue WHERE type='status_change';` if needed.

---

## Phase 5 — Hardening

### Objective
Documentation, dashboards, and the residual polish.

### Files / Modules affected
- `documentation/freecrm-mail-system-architecture.md` — add a "Delayed emails" subsection that links to `mvp-email-buffer.md`.
- `documentation/runbooks/delayed-email-runbook.md` (new) — operator playbook: how to inspect pending, how to cancel, what each metric means.
- Language strings for any remaining UI labels.

### Tasks
1. Write the runbook (one page max).
2. Add the observability SQL from §9 to a small "Diagnostics" tab in `Settings:DelayedEmails` (optional; defer if time-pressed).
3. Sweep for TODOs and dead branches.

### Acceptance Criteria
- Runbook exists and is linked from the architecture doc.
- On-call can find "what's pending right now" and "what was sent in the last 24 h" without engineering help.

### Rollback Strategy
- Docs are docs; revert if wrong. No production impact.

---

# 4. Database Migration Plan

FreeCRM has no migration framework — schema is applied directly to the live MariaDB and mirrored in `scheme.sql`/`data.sql` for fresh installs. All changes below are **additive and online** (no `ALTER` on hot data paths). They can run during business hours.

### Migration 0.1 — `s_yf_delayed_email_queue`

```sql
CREATE TABLE s_yf_delayed_email_queue (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_id           INT UNSIGNED NOT NULL,
  dest_id             INT UNSIGNED NOT NULL,
  type                VARCHAR(64)  NOT NULL,
  recipients_json     JSON         NOT NULL,
  subject             VARCHAR(998) NOT NULL,
  body                MEDIUMTEXT   NOT NULL,
  expected_state_hash CHAR(64)     DEFAULT NULL,
  send_after          TIMESTAMP    NOT NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_pair_type (source_id, dest_id, type),
  KEY idx_due (send_after)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- **Safe live?** Yes — new table.
- **Compatibility period needed?** No.
- **Rollback:** `DROP TABLE s_yf_delayed_email_queue;`

### Migration 0.2 — `s_yf_mail_sent_log`

```sql
CREATE TABLE s_yf_mail_sent_log (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mail_queue_id   INT UNSIGNED NOT NULL,
  smtp_id         INT UNSIGNED NOT NULL,
  owner           INT UNSIGNED DEFAULT NULL,
  recipients_json JSON         NOT NULL,
  subject         VARCHAR(998) NOT NULL,
  body_sha256     CHAR(64)     NOT NULL,
  body_excerpt    VARCHAR(500) DEFAULT NULL,
  status          TINYINT      NOT NULL,
  error           TEXT         DEFAULT NULL,
  attempted_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  source_module   VARCHAR(64)  DEFAULT NULL,
  source_id       INT UNSIGNED DEFAULT NULL,
  KEY idx_attempted_at (attempted_at),
  KEY idx_status       (status, attempted_at),
  KEY idx_source       (source_module, source_id),
  KEY idx_smtp         (smtp_id, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- **Safe live?** Yes — new table.
- **Compatibility period needed?** No.
- **Rollback:** `DROP TABLE s_yf_mail_sent_log;`

### Migration 0.3 — Extend `s_yf_mail_queue`

```sql
ALTER TABLE s_yf_mail_queue
  ADD COLUMN source_module VARCHAR(64) DEFAULT NULL,
  ADD COLUMN source_id     INT UNSIGNED DEFAULT NULL,
  ADD KEY idx_source (source_module, source_id);
```

- **Safe live?** Yes — adding nullable columns + non-unique index. InnoDB online DDL handles this without blocking writes on MariaDB 10.5+ (`ALGORITHM=INPLACE, LOCK=NONE` implicit for nullable adds).
- **Compatibility period needed?** No. Existing rows get `NULL` in the new columns; existing `Mailer::addMail()` callers continue to work without passing the new fields.
- **Rollback:**
  ```sql
  ALTER TABLE s_yf_mail_queue DROP KEY idx_source, DROP COLUMN source_id, DROP COLUMN source_module;
  ```

### Migration 0.4 — Seed cron tasks (disabled)

```sql
INSERT INTO vtiger_cron_task
  (name, handler_class, frequency, status, module, sequence, description)
VALUES
  ('LBL_DELAYED_EMAIL_QUEUE',
   'App\\Modules\\Cron\\Tasks\\DelayedEmailQueueTask',
   60, 0, 'Vtiger', 25,
   'Promotes buffered emails into the mail queue when delay has elapsed.'),
  ('LBL_CLEANUP_MAIL_AUDIT_LOG',
   'App\\Modules\\Cron\\Tasks\\CleanupMailAuditLogTask',
   86400, 0, 'Vtiger', 26,
   'Deletes audit rows older than Mail.AUDIT_LOG_RETENTION_DAYS.');
```

- **Safe live?** Yes. Rows are inserted disabled; `vtigercron.php` ignores them.
- **Rollback:** `DELETE FROM vtiger_cron_task WHERE name IN ('LBL_DELAYED_EMAIL_QUEUE','LBL_CLEANUP_MAIL_AUDIT_LOG');`

### Order

```
0.1 (create buffer)  ─┐
0.2 (create audit)   ─┼── apply in any order; all independent
0.3 (alter queue)    ─┤
0.4 (seed cron rows) ─┘   (must come after the cron classes exist if cron is started immediately — but they're status=0 so safe earlier too)
```

---

# 5. Code-Level Work Breakdown

Sizes: XS ≤ 1 h, S ≤ 0.5 d, M ≤ 1 d, L ≤ 2 d.

## Buffer

| Task | Files | Size |
|---|---|---|
| `DelayedEmailType` enum + `resolver()` | `src/Email/Delayed/DelayedEmailType.php` | XS |
| `RelevanceResolver` interface | `src/Email/Delayed/RelevanceResolver.php` | XS |
| `StatusChangeResolver` placeholder | `src/Email/Delayed/Resolvers/StatusChangeResolver.php` | XS |
| `Email` VO (`recipients`, `subject`, `body`) | `src/Email/Delayed/Email.php` | XS |
| `Buffer::enqueue()` (upsert + hash + SQL `NOW() + INTERVAL`) | `src/Email/Delayed/Buffer.php` | S |
| `Buffer::cancel(int $source, int $dest, ?DelayedEmailType)` | same | XS |
| `Buffer::sendNow(int $bufferId)` | same | XS |
| Flag short-circuit fallback to `Mailer::addMail()` | same | S |
| Config keys (`DELAYED_EMAIL_BUFFER_ENABLED`, `DELAYED_EMAIL_DEFAULT_MINUTES`) | `config/modules/Mail.php` | XS |
| Real `StatusChangeResolver::hash()` query | `src/Email/Delayed/Resolvers/StatusChangeResolver.php` | S |

## Cron

| Task | Files | Size |
|---|---|---|
| `DelayedEmailQueueTask::execute()` (outer scan) | `src/Modules/Cron/Tasks/DelayedEmailQueueTask.php` | S |
| `promoteOne(int $id)` with `db->transaction()` + `FOR UPDATE` | same | S |
| `isStillRelevant(array $row)` | same | XS |
| Promote: `Mailer::addMail` with `source_module='DelayedBuffer'` | same | XS |
| `CleanupMailAuditLogTask::execute()` | `src/Modules/Cron/Tasks/CleanupMailAuditLogTask.php` | XS |
| Seed both rows in `vtiger_cron_task` (status=0) | `data.sql` + live DB | XS |

## Mail Pipeline

| Task | Files | Size |
|---|---|---|
| Accept `source_module` / `source_id` in `Mailer::addMail()` params | `src/Email/Mailer.php` | XS |
| Wrap post-attempt block of `MailerTask::execute()` in transaction | `src/Modules/Cron/Tasks/MailerTask.php` | S |
| Audit insert (hash, excerpt, recipients_json) | same | S |
| Catch `Throwable` around `sendByRowQueue` and record `error` | same | XS |
| Flag gate `MAIL_AUDIT_LOG_ENABLED` | `config/modules/Mail.php` + `MailerTask` | XS |
| Config key `AUDIT_LOG_RETENTION_DAYS` | `config/modules/Mail.php` | XS |

## Settings UI

| Task | Files | Size |
|---|---|---|
| Skeleton mirror of `Settings:CronTasks` | `src/Modules/Settings/DelayedEmails/...` | M |
| `ListView` model + view (sorted by `send_after`) | as above | M |
| `Cancel` action | `Actions/Cancel.php` | S |
| `SendNow` action | `Actions/SendNow.php` | S |
| Templates with MainLayout wrapper | `layouts/basic/modules/Settings/DelayedEmails/*.tpl` | S |
| Language files (en_us, pl_pl) | `languages/.../Settings/DelayedEmails.json` | XS |
| Settings menu registration | seed table | XS |

## Cleanup

| Task | Files | Size |
|---|---|---|
| `CleanupMailAuditLogTask` body (single DELETE with interval) | `src/Modules/Cron/Tasks/CleanupMailAuditLogTask.php` | XS |
| Runbook & retention doc | `documentation/runbooks/delayed-email-runbook.md` | S |

---

# 6. Feature Flag Strategy

Two product flags + two config knobs, all in `config/modules/Mail.php`.

| Flag | Default | What "off" means | What "on" means | Rollback |
|---|---|---|---|---|
| `MAIL_AUDIT_LOG_ENABLED` | `false` | `MailerTask` does **not** write to `s_yf_mail_sent_log`. Existing behavior, zero risk. | Each send/fail writes one audit row in the same transaction as the queue mutation. | Flip back to `false`; next cron pass reverts to old behavior. Existing audit rows remain. |
| `DELAYED_EMAIL_BUFFER_ENABLED` | `false` | `Buffer::enqueue()` short-circuits to `Mailer::addMail()` immediately. Buffer table is never written. | Buffer behavior active; emails delayed and promoted by cron. | Flip back to `false`. Callers immediately fall back to direct send. Drain residual pending rows by leaving cron task `status=1` for ≤ 2 h, or cancel them via Settings UI. |
| `DELAYED_EMAIL_DEFAULT_MINUTES` | `120` | n/a | Sets the default delay used when `enqueue()` is called without an explicit override. | Change value; in effect immediately for new enqueues. |
| `AUDIT_LOG_RETENTION_DAYS` | `365` | n/a | Sets cleanup window for `s_yf_mail_sent_log`. | Change value; next cleanup-cron pass uses new value. |

**How code ships dormant:** the cron task row is seeded with `status=0`. Flags default `false`. Caller migration in Phase 4 references the buffer, but because the flag is off, the buffer's `enqueue()` falls straight through to `addMail()`. Three independent switches must all be flipped (config flag, cron `status=1`) for buffered behavior to occur.

**Rollout sequence:**
1. Deploy Phase 0 schema. No flags involved.
2. Deploy Phase 1 code. Flag `MAIL_AUDIT_LOG_ENABLED` off in prod; flip on in staging; observe; flip on in prod.
3. Deploy Phase 2 code. Cron remains `status=0`. Flag off. No-op in prod.
4. Deploy Phase 3 (UI). Cosmetic — no flag needed.
5. Deploy Phase 4 caller change. Still no-op because flag is off.
6. **Activation:** in one operation:
   - `UPDATE vtiger_cron_task SET status=1 WHERE name='LBL_DELAYED_EMAIL_QUEUE';`
   - `UPDATE vtiger_cron_task SET status=1 WHERE name='LBL_CLEANUP_MAIL_AUDIT_LOG';`
   - Flip `DELAYED_EMAIL_BUFFER_ENABLED=true`.

---

# 7. Testing Plan

## Unit

| Scenario | Setup | Execution | Expected |
|---|---|---|---|
| `Buffer::enqueue()` inserts when absent | empty buffer | call enqueue with (1, 2, STATUS_CHANGE) | 1 row, `send_after ≈ NOW() + 120m`, `expected_state_hash` populated |
| `Buffer::enqueue()` overwrites when present | existing row for (1, 2, STATUS_CHANGE) with subject "A" | call enqueue with subject "B" | still 1 row, subject is "B", `send_after` refreshed |
| `Buffer::cancel(source, dest)` removes all types | two rows for same pair, different types | call `cancel(1, 2)` | 0 rows for the pair |
| `Buffer::cancel(source, dest, type)` removes only that type | two rows for same pair, different types | call `cancel(1, 2, STATUS_CHANGE)` | 1 row remains (the other type) |
| `isStillRelevant()` returns true when hashes match | stub resolver returns the same hash that's stored | call from `promoteOne` | promotes |
| `isStillRelevant()` returns false when hashes diverge | stub resolver returns different hash | call from `promoteOne` | discards, no row in `s_yf_mail_queue` |
| Flag off short-circuits to `addMail()` | `DELAYED_EMAIL_BUFFER_ENABLED=false` | `Buffer::enqueue()` | row appears in `s_yf_mail_queue`, none in `s_yf_delayed_email_queue` |

## Integration

| Scenario | Setup | Execution | Expected |
|---|---|---|---|
| Enqueue → cron → mail queue → MailerTask → audit | one due row, relevance OK | run `DelayedEmailQueueTask::execute()` then `MailerTask::execute()` (audit flag on) | buffer empty; queue empty; 1 audit row with `source_module='DelayedBuffer'`, `status=1` |
| Overdue processing after downtime | row with `send_after = NOW() - 1h` | run cron | promotes immediately (if still relevant) |
| Hash mismatch path | enqueue with one state; mutate underlying data | run cron | buffer row deleted; no queue row; no audit row |
| Caller short-circuit | flag off | enqueue from caller | direct addMail; no buffer row; eventual audit row with `source_module=NULL` |

## Cron

| Scenario | Setup | Execution | Expected |
|---|---|---|---|
| Two concurrent workers on the same row | two CLI invocations of `DelayedEmailQueueTask::execute()` against staging with one due row | parallel via `&` | exactly one promotion observed; loser silently moves on |
| Restart mid-promote | enqueue one row, due; inject `sleep` between FOR UPDATE and DELETE; SIGTERM the cron process | recover, run cron again | buffer row intact, will be re-tried; no queue row written during the killed run |
| Cleanup cron respects retention | seed audit rows with `attempted_at = NOW() - 400d` and `attempted_at = NOW() - 100d` | run `CleanupMailAuditLogTask` with retention=365 | only the 400d row is deleted |

## Manual QA (staging, web UI)

| Scenario | Setup | Execution | Expected |
|---|---|---|---|
| Rapid drag: Interview → Rejected → Interview | candidate on a recruitment project | drag both ways within 1 min | only one row in buffer (last writer wins); after 2 h either correct email goes out or hash mismatch discards |
| Admin Cancel | one pending row | open `Settings → Opóźnione e-maile`, click Cancel | row gone; no audit row appears later |
| Admin Send now | one pending row | click Send now | within ≤ 60 s, row promoted; audit row appears with `source_module='DelayedBuffer'` |
| Stop the cron container | row pending, due | `docker compose stop cron`; observe | row remains in buffer; restart cron → row promoted on next pass |

## Production verification (Phase 4 activation window)

| Check | Frequency in first 24 h |
|---|---|
| `SELECT COUNT(*) FROM s_yf_delayed_email_queue` is bounded (< ~100 typical) | every 15 min |
| `SELECT COUNT(*) FROM s_yf_mail_sent_log WHERE attempted_at > NOW()-INTERVAL 1 HOUR` is non-zero and consistent with traffic | hourly |
| `cache/logs/system.log` has no new error patterns | watched live |
| `SELECT COUNT(*) FROM s_yf_mail_queue WHERE source_module='DelayedBuffer' AND status=2` is zero (no terminal failures from buffer path) | hourly |
| Sample audit row → spot-check recipient + subject against the originating record | 3 samples in the first hour |

---

# 8. Deployment Plan

### Deploy 1 — Schema (`Phase 0`)

1. Apply migrations 0.1, 0.2, 0.3, 0.4 against live DB.
2. Commit `scheme.sql` and `data.sql` changes.
3. **Smoke:** `DESCRIBE` all three tables; check `vtiger_cron_task` for the two seeded rows.
4. **Validation queries:**
   ```sql
   SELECT COUNT(*) FROM s_yf_delayed_email_queue; -- 0
   SELECT COUNT(*) FROM s_yf_mail_sent_log;        -- 0
   SHOW INDEX FROM s_yf_delayed_email_queue WHERE Key_name = 'uniq_pair_type';
   SELECT name, status FROM vtiger_cron_task
    WHERE name IN ('LBL_DELAYED_EMAIL_QUEUE','LBL_CLEANUP_MAIL_AUDIT_LOG');
   ```
5. **Rollback trigger:** any error from the above queries.

### Deploy 2 — Delivery audit, dormant (`Phase 1` code)

1. Ship `MailerTask` patch, `Mailer::addMail()` extension, `CleanupMailAuditLogTask` class, two config keys.
2. `MAIL_AUDIT_LOG_ENABLED` stays `false`.
3. **Smoke:** force a manual send (e.g., template test) → confirm `s_yf_mail_sent_log` is still empty (flag off).
4. **Rollback:** revert PR.

### Deploy 3 — Enable audit

1. Flip `MAIL_AUDIT_LOG_ENABLED=true` in `config/modules/Mail.php`.
2. `UPDATE vtiger_cron_task SET status=1 WHERE name='LBL_CLEANUP_MAIL_AUDIT_LOG';` (so retention starts cycling).
3. **Smoke:**
   ```sql
   SELECT COUNT(*) FROM s_yf_mail_sent_log WHERE attempted_at > NOW() - INTERVAL 5 MINUTE;
   ```
   should be non-zero after a few cron passes.
4. **Validation:** sample a row and verify `body_sha256` matches `SHA2(s_yf_mail_queue.content, 256)` for a still-resident queue row, if any.
5. **Rollback trigger:** crashes/exceptions in `MailerTask`, audit row count not matching expected send rate. Flip flag off.

### Deploy 4 — Buffer skeleton, dormant (`Phase 2` code)

1. Ship enum, resolver, `Buffer`, `DelayedEmailQueueTask`.
2. Cron stays `status=0`. `DELAYED_EMAIL_BUFFER_ENABLED` stays `false`.
3. **Smoke:** manually call `DelayedEmailQueueTask::execute()` via CLI on staging with a seeded row.
4. **Rollback:** revert PR; tables remain.

### Deploy 5 — Settings UI (`Phase 3`)

1. Ship the `Settings:DelayedEmails` module.
2. **Smoke:** open `index.php?module=DelayedEmails&parent=Settings&view=ListView`; renders with MainLayout, lists `s_yf_delayed_email_queue` (empty unless test rows seeded).
3. **Rollback:** revert PR.

### Deploy 6 — First caller wired, dormant (`Phase 4` code)

1. Ship the workflow call-site change. Implement `StatusChangeResolver::hash()`.
2. `DELAYED_EMAIL_BUFFER_ENABLED` stays `false`. Caller goes through `Buffer::enqueue()`, which short-circuits to `addMail()`.
3. **Smoke:** trigger a status change in staging with flag off → email goes out immediately as before; `s_yf_delayed_email_queue` stays empty.
4. **Rollback:** revert PR.

### Deploy 7 — Activate

1. `UPDATE vtiger_cron_task SET status=1 WHERE name='LBL_DELAYED_EMAIL_QUEUE';`
2. Flip `DELAYED_EMAIL_BUFFER_ENABLED=true`.
3. **Smoke (within 5 min):**
   ```sql
   SELECT COUNT(*) FROM s_yf_delayed_email_queue;
   ```
   should be > 0 after the first status-change event.
4. **Validation (within ~2 h):**
   ```sql
   SELECT COUNT(*) FROM s_yf_mail_sent_log
    WHERE source_module='DelayedBuffer' AND attempted_at > NOW() - INTERVAL 30 MINUTE;
   ```
5. **Rollback trigger (any of):**
   - Pending count grows unboundedly (> 500 for a small deployment) without corresponding sends.
   - New errors in `cache/logs/system.log` referencing `DelayedEmailQueueTask` or `Buffer`.
   - Recruiters report rejection emails going out despite manual correction.
6. **Rollback action:** flip `DELAYED_EMAIL_BUFFER_ENABLED=false`; optionally disable cron; if data harmful, `DELETE FROM s_yf_delayed_email_queue;`.

### Deploy 8 — Hardening (`Phase 5`)

Docs only. No production gates.

---

# 9. Observability

### Metrics (read by humans via SQL; no metrics infra introduced)

| Metric | SQL |
|---|---|
| Pending buffer size | `SELECT COUNT(*) FROM s_yf_delayed_email_queue;` |
| Pending by type | `SELECT type, COUNT(*) FROM s_yf_delayed_email_queue GROUP BY type;` |
| Oldest pending (queue lag indicator) | `SELECT MIN(send_after) FROM s_yf_delayed_email_queue WHERE send_after <= NOW();` |
| Overdue rows (cron not keeping up) | `SELECT COUNT(*) FROM s_yf_delayed_email_queue WHERE send_after < NOW() - INTERVAL 5 MINUTE;` |
| Sends in last hour | `SELECT COUNT(*) FROM s_yf_mail_sent_log WHERE attempted_at > NOW() - INTERVAL 1 HOUR;` |
| Sends from buffer in last hour | `SELECT COUNT(*) FROM s_yf_mail_sent_log WHERE source_module='DelayedBuffer' AND attempted_at > NOW() - INTERVAL 1 HOUR;` |
| Failures in last 24 h | `SELECT COUNT(*) FROM s_yf_mail_sent_log WHERE status=2 AND attempted_at > NOW() - INTERVAL 1 DAY;` |
| Failure samples | `SELECT id, smtp_id, subject, error, attempted_at FROM s_yf_mail_sent_log WHERE status=2 ORDER BY attempted_at DESC LIMIT 20;` |
| Suppression rate (rough) | `(enqueues seen in workflow logs) - (rows in s_yf_mail_sent_log WHERE source_module='DelayedBuffer')` over a fixed window |
| Audit-log table size | `SELECT COUNT(*) FROM s_yf_mail_sent_log; SELECT MIN(attempted_at), MAX(attempted_at) FROM s_yf_mail_sent_log;` |

### Logs

`App\Log\Log::trace()` in `DelayedEmailQueueTask` for:

- start/end of each `execute()` with count promoted/skipped/stale-discarded
- per-row decision (`promoted id=...`, `stale id=... hash mismatch`)

`App\Log\Log::warn()` in `MailerTask` when `sendByRowQueue` throws (path that produces a `status=2` audit row).

### Admin verification (Settings UI)

- `Settings → Cron Tasks`: `LBL_DELAYED_EMAIL_QUEUE` has recent `laststart`/`lastend` (< 90 s ago) and `status=1`.
- `Settings → Opóźnione e-maile`: list is non-empty during business hours; entries scroll off after ~2 h.

### Alerting (lightweight; cron-based check)

Optional follow-up cron: warn on `system.log` if pending > N or oldest > 5 min overdue. Skip for MVP.

---

# 10. Definition of Done

### Database
- [ ] `s_yf_delayed_email_queue` exists with `uniq_pair_type` unique key and `idx_due` index.
- [ ] `s_yf_mail_sent_log` exists with the four documented indexes.
- [ ] `s_yf_mail_queue` has `source_module`, `source_id`, `idx_source`.
- [ ] `vtiger_cron_task` contains `LBL_DELAYED_EMAIL_QUEUE` (freq=60) and `LBL_CLEANUP_MAIL_AUDIT_LOG` (freq=86400), both `status=1` after activation.
- [ ] `scheme.sql` and `data.sql` mirror the live schema.

### Code
- [ ] `DelayedEmailType` enum + at least one case (`STATUS_CHANGE`) + `resolver()`.
- [ ] `RelevanceResolver` interface + `StatusChangeResolver` with a real hash query.
- [ ] `Buffer::{enqueue, cancel, sendNow}` implemented and flag-gated.
- [ ] `DelayedEmailQueueTask::execute()` runs the §6 transactional flow.
- [ ] `MailerTask::execute()` writes an audit row in the same transaction as the queue mutation, flag-gated.
- [ ] `Mailer::addMail()` accepts and persists `source_module`/`source_id`.
- [ ] `CleanupMailAuditLogTask` deletes rows older than `AUDIT_LOG_RETENTION_DAYS` in one statement.
- [ ] Config keys `MAIL_AUDIT_LOG_ENABLED`, `DELAYED_EMAIL_BUFFER_ENABLED`, `DELAYED_EMAIL_DEFAULT_MINUTES`, `AUDIT_LOG_RETENTION_DAYS` present.

### Cron
- [ ] Both new cron rows enabled and ticking (verified in `Settings → Cron Tasks`).
- [ ] `MailerTask` continues running unchanged for non-buffer emails.

### UI
- [ ] `Settings → Opóźnione e-maile` lists rows, sorted by `send_after`.
- [ ] Cancel removes a row and the list refreshes.
- [ ] Send now updates `send_after` to `NOW()` and the row clears within one cron cycle.
- [ ] Page renders with MainLayout (left menu visible).
- [ ] Polish + English language files complete.

### Monitoring
- [ ] All §9 SQL queries return expected values in staging.
- [ ] `cache/logs/system.log` contains the documented trace lines for `DelayedEmailQueueTask`.

### Documentation
- [ ] `documentation/freecrm-mail-system-architecture.md` links to `mvp-email-buffer.md`.
- [ ] `documentation/runbooks/delayed-email-runbook.md` exists: lists §9 queries, rollback steps, and the meaning of every config flag.

### Operations
- [ ] Rollback for every phase rehearsed against staging at least once.
- [ ] Activation runbook (Deploy 7) reviewed by an operator who didn't write the code.
- [ ] Production verification queries are in the runbook and a recent timestamped sample run is attached to the rollout ticket.