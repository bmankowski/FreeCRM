# FreeCRM Mail System — Architecture

**Status:** proposed (greenfield replacement of OSSMail / OSSMailScanner / OSSMailView)
**Author:** bmankowski@gmail.com
**Date:** 2026-05-21
**Scope:** new `Mail` module for inbound scanning + outbound from-record sending, replacing the Roundcube-based OSSMail stack.

---

## 1. Background and design constraints

FreeCRM currently embeds Roundcube webmail (`OSSMail`), a scanner (`OSSMailScanner`) and a related-list module (`OSSMailView`). The webmail piece is unused in our workflow; the scanner and per-record mail history are the parts that matter. This design replaces all three with a single new module, scoped to **what we actually use**:

- scanning company mailboxes,
- sending mails from CRM records (Kandydat, Contact, Account, …) authenticated as the user's own mailbox,
- listing the resulting correspondence on each record.

It explicitly does **not** build a webmail client (no folders UI, no threads view, no drafts, no in-app reply chains).

Design decisions taken with the project owner:

| Topic | Decision |
|------|----------|
| Account model | Mixed: per-user personal mailbox **and** shared team mailboxes |
| Auth | Login + password only, against own mail server (no OAuth2) |
| Outbound | Authenticated with user's own SMTP credentials; `From` = user's address |
| Sent copy | IMAP `APPEND` to the user's `Sent` folder (per-account flag) |
| Migration | Fresh start, no migration from `vtiger_ossmailview` |
| Auto-bind modules | Kandydaci, Contacts, Accounts, Leads, SSalesProcesses, HelpDesk |
| Target scale | ≤10 mailboxes, ≤500 messages/day, polling cron every 60–120 s |

---

## 2. Requirements

### 2.1 Functional

1. **Account configuration**
   - Admin can create a mail account with: name, IMAP host/port/SSL/folders, SMTP host/port/secure, username, password.
   - Each account is either owned by one CRM user (personal) or shared with several users (team).
   - "Test connection" button validates IMAP login and SMTP login before saving.
   - A user can mark one of their accessible accounts as the default for outbound mail.
2. **Inbound scanning**
   - Cron job polls each active account at fixed interval.
   - Only new messages (`IMAP UID > last_uid`) are fetched.
   - Each message is parsed (headers, HTML body, text body, attachments) and stored once. Deduplication uses `(account_id, imap_uid)` and `message_id`.
   - Attachments are stored on disk under `storage/Mail/…`; only metadata in DB.
3. **Auto-binding to CRM records**
   - For every incoming message, the binding engine inspects `From`, `To`, `Cc` addresses and links the message to any CRM record (across Kandydaci, Contacts, Accounts, Leads, SSalesProcesses) whose email field matches.
   - HelpDesk binding additionally parses the subject for the pattern `[T#NNN]` (configurable) and links to the matching ticket.
   - All auto-links are tagged `link_type = auto` together with the matched field name.
4. **Manual bind/unbind**
   - On the message detail view, the user can attach or detach the message from any CRM record they have permission to.
5. **Per-record related list**
   - Each of the six target modules exposes an "E-maile" related list showing all linked messages, sorted by date desc, with inbound/outbound badge, subject, counterparty, attachments flag.
6. **Outbound from record**
   - On any module that has the related list, the user clicks "Wyślij e-mail" (or clicks the email field on the record).
   - A compose modal opens, prefilled with To = the record's email field, with template picker (existing `App\Email\Mail::getTempleteList`) and live preview through `App\TextParser`.
   - The modal lists the user's mail accounts in a dropdown; the default is preselected.
   - On send, the mail is sent through the chosen account's SMTP, authenticated as the user, with `From: <account.from_name> <account.username>`.
   - On success: the sent message is `IMAP APPEND`-ed to the account's `Sent` folder (if `append_sent = 1`), stored in `u_yf_mail_messages` with `direction = out`, and auto-linked to the originating CRM record.
7. **Outbound from bulk (existing `IndividualSendMailModal`)**
   - The bulk modal also picks a Mail account (replaces the global `s_yf_mail_smtp` selection for personal users).
   - For admin/system mass mailings the existing SMTP queue stays available as a separate path.
8. **Logs and health**
   - Every scan, send, append, bind operation writes a structured entry to `u_yf_mail_log`.
   - Admin UI shows last scan time and status per account; an account is auto-disabled after N consecutive scan failures (configurable, default 5).

### 2.2 Non-functional

| Concern | Target |
|---------|--------|
| Scale | 10 mailboxes × ~500 msg/day total |
| Latency | New inbound visible in CRM within 2 minutes of arrival |
| Send timing | Synchronous — user clicks Send, sees confirmation within 5 s for typical mail |
| Availability | Single MariaDB + single CRM container; no HA requirement |
| PHP | 8.1+, FreeCRM existing stack |
| Browser | Aligned with rest of CRM (Bootstrap 5) |
| Maintainability | One module, no DSL, no plugin loader, no event bus |
| Observability | DB log + file log; admin UI to read both |
| Security | Encrypted credentials at rest, sanitized HTML on display, no header injection in compose |

### 2.3 Explicit non-goals

- No webmail UI (folder browser, threading view, drafts).
- No calendar/contacts/identities sync (Roundcube features).
- No OAuth2 / XOAUTH2 in v1 (designed to be addable later, see §11).
- No IMAP IDLE / push delivery.
- No multi-tenant isolation beyond CRM users and shared-account ACL.
- No DKIM/SPF handling at app level (MTA's job).
- No mail filtering / Sieve rules.

---

## 3. System boundaries and responsibilities

```
┌─────────────────────────────┐         ┌──────────────────────────┐
│  IMAP server (own)          │◄────────┤  Mail module: Scanner    │
│  mail.itconnect.pl:993      │  fetch  │  (cron worker)           │
│                             │         └────────┬─────────────────┘
│                             │                  │
│                             │◄────────┐        │ parse + store
│                             │ APPEND  │        ▼
└─────────────────────────────┘         │  ┌──────────────────────┐
┌─────────────────────────────┐         │  │  u_yf_mail_messages  │
│  SMTP server (own)          │         │  │  u_yf_mail_attach…   │
│  mail.itconnect.pl:465      │         │  │  u_yf_mail_record_li…│
└──────▲──────────────────────┘         │  │  u_yf_mail_log       │
       │                                │  └────────┬─────────────┘
       │ send                           │           │
┌──────┴─────────────────────┐          │           │ related-list query
│  Mail module: Sender       │◄─────────┘           │
│  (HTTP action, sync)       │                      │
└────────────▲───────────────┘                      │
             │                                      │
             │ click "Send"                         │
┌────────────┴─────────────────────────┐  ┌─────────▼──────────────┐
│  Compose modal on record (Smarty)    │  │  Related list "E-maile"│
└────────────▲─────────────────────────┘  │  on Kandydat, Contact, │
             │                            │  Account, Lead, SP, HD │
┌────────────┴─────────────────────────┐  └────────────────────────┘
│  Settings: Mail Accounts admin UI    │
└──────────────────────────────────────┘
```

**Owned by Mail module**

- account configuration (CRUD + connection test),
- IMAP polling and parsing,
- outbound send (SMTP) + IMAP APPEND,
- message persistence and attachment storage,
- binding rules engine,
- per-record related list registration on six modules,
- compose modal and message detail view,
- operational log (scan / send / bind),
- cron task registration.

**Reused from existing FreeCRM**

- `App\TextParser` for template variable expansion,
- `App\Email\Mail::getTempleteList` and template detail loading,
- `App\Security\Encryption` for password storage at rest,
- `App\Db\Db` / `App\Db\Query` for DB access,
- `App\Modules\Base\Models\RelationListView` for the related list infrastructure,
- HTMLPurifier (already in `vendor/ezyang/htmlpurifier`) for safe HTML rendering,
- supercronic in the `cron` container for scheduling.

**Out of scope for Mail module**

- the legacy `App\Email\Mailer` / `s_yf_mail_smtp` queue stays available for system-level mails (system notifications, forgot password, etc.); the new Mail module is for **user-driven** mail tied to records.

---

## 4. Components

Single Yii-style module under `src/Modules/Mail/`. Internal organization stays flat — no extra layering beyond what the CRM already uses (Models / Views / Actions / Cron).

```
src/Modules/Mail/
  Mail.php                       module install hook
  Models/
    Account.php                  CRUD + encryption helpers
    Message.php                  insert / fetch / link helpers
    Attachment.php               storage path resolution
    Binding/
      Engine.php                 orchestrates rules
      ByEmail.php                generic email-field match
      HelpDeskSubject.php        [T#NNN] subject parser
  Imap/
    Client.php                   thin wrapper over webklex/php-imap
    Fetcher.php                  one scan pass for one account
    Appender.php                 IMAP APPEND to Sent
  Smtp/
    Sender.php                   PHPMailer wrapper, per-user creds
  Cron/
    Scanner.php                  cron task entry
  Views/
    Compose.php                  modal
    Detail.php                   single message
    RelatedList.php              per-record list
  Actions/
    Send.php                     POST: send mail
    Link.php / Unlink.php        POST: manual bind
    TestConnection.php           POST: validate creds
  layouts/.../Mail/              Smarty templates

src/Modules/Settings/MailAccount/
  Models/Module.php, Models/Record.php
  Views/List.php, Views/Edit.php, Views/Logs.php
  Actions/SaveAjax.php, Actions/DeleteAjax.php, Actions/TestConnection.php
  layouts/.../Settings/MailAccount/
```

We deliberately keep **no plugin loader** for binding rules: the Engine instantiates a fixed list of rule classes. Adding a new module to auto-bind means editing one PHP file. This is the pragmatic trade-off — the existing OSSMailScanner's "load directory of files" pattern was clever but obscures control flow for no real benefit at our scale.

---

## 5. Data flow

### 5.1 Inbound (one scan pass per account)

1. Cron tick → `App\Modules\Mail\Cron\Scanner::run()`.
2. Scanner loads all active accounts whose `next_scan_at <= NOW()`.
3. For each account, acquires an advisory mutex on `(account_id)` (DB row-level `GET_LOCK` or a `mail_scan_in_progress` flag with timestamp). Skips if locked.
4. `Imap\Client::open($account)` connects (TLS, plain login).
5. `Imap\Fetcher::fetchSince($account->last_uid)` queries `UID SEARCH UID <last_uid+1>:*` on the configured Inbox folder.
6. For each new UID, fetched in batches of 20:
   1. parse headers, MIME tree, body parts, attachments via `webklex/php-imap`;
   2. `Message::insertIfNew($accountId, $uid, $parsed)` — wrapped in a DB transaction:
      - INSERT into `u_yf_mail_messages` (skip if `(account_id, imap_uid)` already exists),
      - INSERT attachments rows + write files to `storage/Mail/YYYY/MM/<account_id>/<message_id>_<sanitized_name>`,
      - `Binding\Engine::bind($message)` → INSERTs into `u_yf_mail_record_links`,
      - COMMIT.
   3. Append to `u_yf_mail_log` (level=info, action=scan, msg=summary).
7. Update `account.last_uid = max(seen)`, `account.last_scan_at = NOW()`, `account.last_scan_status = 'ok'`, `next_scan_at = NOW() + interval`.
8. On any IMAP error: increment `consecutive_failures`, log error, set `next_scan_at = NOW() + backoff(failures)`. Account auto-disables when `consecutive_failures >= 5`.

### 5.2 Outbound (user-initiated from record)

1. User on record detail (e.g. Kandydat #1234) clicks email link or "Wyślij e-mail".
2. Browser opens `index.php?module=Mail&view=Compose&sourceModule=Kandydaci&sourceRecord=1234&to=<email>`.
3. `Views\Compose` renders modal with:
   - `From` dropdown: list of accounts user owns + accounts shared with user (where `can_send = 1`),
   - `To`, `Cc`, `Bcc` (Cc/Bcc collapsed by default),
   - Template picker (filtered to the source module),
   - Subject, body editor (CKEditor, same as existing IndividualSendMailModal),
   - Attachments uploader (multipart, stored under `cache/Mail/upload/`),
   - Live preview pane fed by `App\TextParser`.
4. User clicks Send → POST `index.php?module=Mail&action=Send`.
5. `Actions\Send`:
   1. permission check: user must have access to chosen account AND `DetailView` permission on source record,
   2. render subject + body via `TextParser` with `recordId`, `moduleName`, `sourceModule`, `sourceRecord`,
   3. inline CSS via existing `App\Utils\TemplateStyles::inlineEmailCss`,
   4. `Smtp\Sender::send($account, $envelope, $body, $attachments)`:
      - configure PHPMailer with account's host/port/secure/username/decrypted password,
      - set `From`, `To`/`Cc`/`Bcc`, `Subject`, `Reply-To` (= account.username),
      - HTML + AltBody, attachments,
      - call `send()`,
   5. on success, if `account.append_sent = 1`:
      - `Imap\Appender::appendToSent($account, $rfc822)` — builds the RFC822 from PHPMailer's `getSentMIMEMessage()` and IMAP-APPENDs to the configured Sent folder; failure is logged but does **not** fail the user action,
   6. INSERT into `u_yf_mail_messages` (direction=out, imap_uid=null, message_id from generated `Message-Id` header),
   7. INSERT attachment rows + move uploaded files from `cache/Mail/upload/` to `storage/Mail/…`,
   8. INSERT `u_yf_mail_record_links` linking message to source record (link_type=manual, match_field='compose_source'),
   9. log success, return JSON `{success: true, messageId: X}` to UI,
   10. modal closes, related list refreshes via in-page AJAX.
6. On SMTP error: HTTP 200 + `{success: false, error: '…'}`, modal shows error, draft preserved client-side.

### 5.3 Manual bind / unbind

1. From message detail, user picks module + record in a search popup.
2. POST `module=Mail&action=Link` with `messageId`, `crmModule`, `crmRecordId`.
3. Action verifies permissions, INSERT with `link_type=manual`.
4. Unlink: `action=Unlink` with `linkId`, DELETE.

---

## 6. Storage and persistence

All new tables use the FreeCRM `u_yf_*` naming convention.

### 6.1 `u_yf_mail_accounts`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED AI PK | |
| `name` | VARCHAR(120) | display name |
| `owner_user_id` | INT NULL | NULL = shared/no single owner |
| `imap_host` | VARCHAR(190) | |
| `imap_port` | SMALLINT UNSIGNED | default 993 |
| `imap_secure` | ENUM('ssl','tls','none') | default 'ssl' |
| `imap_validate_cert` | TINYINT(1) | default 1 |
| `imap_folder_inbox` | VARCHAR(190) | default 'INBOX' |
| `imap_folder_sent` | VARCHAR(190) | default 'Sent' |
| `smtp_host` | VARCHAR(190) | |
| `smtp_port` | SMALLINT UNSIGNED | default 465 |
| `smtp_secure` | ENUM('ssl','tls','none') | |
| `username` | VARCHAR(190) | full email address |
| `password_enc` | VARBINARY(512) | `App\Security\Encryption::encrypt()` |
| `from_name` | VARCHAR(120) | optional display name in From |
| `append_sent` | TINYINT(1) | default 1 |
| `last_uid` | INT UNSIGNED | last seen UID in Inbox |
| `last_scan_at` | DATETIME NULL | |
| `last_scan_status` | ENUM('ok','error','disabled') | |
| `last_scan_error` | TEXT NULL | last error message, redacted |
| `consecutive_failures` | SMALLINT UNSIGNED | default 0 |
| `next_scan_at` | DATETIME NULL | for back-off |
| `scan_interval_sec` | SMALLINT UNSIGNED | default 120 |
| `active` | TINYINT(1) | default 1 |
| `created_at`, `updated_at` | DATETIME | |

Indexes: `(active, next_scan_at)`, `(owner_user_id)`.

### 6.2 `u_yf_mail_account_users` (ACL for shared accounts)

| Column | Type | Notes |
|--------|------|-------|
| `account_id` | INT UNSIGNED | FK → `u_yf_mail_accounts.id` ON DELETE CASCADE |
| `user_id` | INT | FK → `vtiger_users.id` ON DELETE CASCADE |
| `can_send` | TINYINT(1) | default 1 |
| `is_default` | TINYINT(1) | default 0 — per-user default account |
| PK | `(account_id, user_id)` | |

A personal account is represented by `owner_user_id` set; it is also automatically given a row here so the access-list query is uniform.

### 6.3 `u_yf_mail_messages`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED AI PK | |
| `account_id` | INT UNSIGNED | FK |
| `direction` | ENUM('in','out') | |
| `imap_uid` | INT UNSIGNED NULL | NULL for outbound |
| `message_id` | VARCHAR(255) NULL | RFC 5322 Message-Id, indexed |
| `in_reply_to` | VARCHAR(255) NULL | for future threading |
| `references_hdr` | TEXT NULL | space-separated |
| `date_sent` | DATETIME | |
| `from_email` | VARCHAR(190) | |
| `from_name` | VARCHAR(255) NULL | |
| `to_json` | JSON | `[{"email":"…","name":"…"}, …]` |
| `cc_json` | JSON NULL | |
| `bcc_json` | JSON NULL | only meaningful for direction=out |
| `subject` | VARCHAR(998) | RFC max |
| `body_html` | MEDIUMTEXT NULL | sanitized later at render |
| `body_text` | MEDIUMTEXT NULL | |
| `has_attachments` | TINYINT(1) | |
| `size_bytes` | INT UNSIGNED | |
| `created_at` | DATETIME | |

Indexes: UNIQUE `(account_id, imap_uid)` (where `imap_uid IS NOT NULL`), `(message_id)`, `(date_sent)`, `(from_email)`, `(direction, date_sent)`.

### 6.4 `u_yf_mail_attachments`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED AI PK | |
| `message_id` | INT UNSIGNED | FK ON DELETE CASCADE |
| `filename` | VARCHAR(255) | sanitized for filesystem |
| `original_name` | VARCHAR(255) | as received, used on download |
| `mime_type` | VARCHAR(127) | |
| `size_bytes` | INT UNSIGNED | |
| `content_id` | VARCHAR(255) NULL | for inline images |
| `storage_path` | VARCHAR(500) | relative to `storage/Mail/` |
| `created_at` | DATETIME | |

### 6.5 `u_yf_mail_record_links`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED AI PK | |
| `message_id` | INT UNSIGNED | FK ON DELETE CASCADE |
| `crm_module` | VARCHAR(50) | e.g. 'Kandydaci', 'HelpDesk' |
| `crm_record_id` | INT UNSIGNED | |
| `link_type` | ENUM('auto','manual') | |
| `match_field` | VARCHAR(60) NULL | e.g. 'email1', 'ticket_no_in_subject' |
| `created_at` | DATETIME | |

Unique key `(message_id, crm_module, crm_record_id)`. Index `(crm_module, crm_record_id, message_id)` for related-list queries.

### 6.6 `u_yf_mail_log`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED AI PK | |
| `account_id` | INT UNSIGNED NULL | |
| `user_id` | INT NULL | for outbound actions |
| `level` | ENUM('info','warn','error') | |
| `action` | VARCHAR(30) | scan, send, append, bind, test |
| `message` | VARCHAR(500) | |
| `context_json` | JSON NULL | scrubbed (no passwords, no full bodies) |
| `created_at` | DATETIME | indexed |

Retention: prune `info` rows older than 30 days via cron task; keep `warn`/`error` for 180 days.

### 6.7 Filesystem layout

```
storage/Mail/
  YYYY/MM/<account_id>/<message_id>/<sanitized_name>
cache/Mail/upload/<user_id>/<uuid>/<original_name>   # transient compose uploads
cache/logs/mail.log                                   # tail-friendly text log
```

`message_id` here is the integer PK from `u_yf_mail_messages`, not the RFC Message-Id header. Date-based partitioning keeps any single directory below ~10k entries given the projected scale.

### 6.8 Permissions matrix (DB-level view)

| Subject | Account read | Account send | Message read | Message link | Account CRUD |
|---------|-------------|--------------|--------------|--------------|--------------|
| Admin | all | all | all | all | yes |
| User — owner of account | yes (own) | yes | own messages | yes | only own non-system fields (signature etc.) |
| User — shared on account | yes (shared) | if `can_send=1` | shared messages | yes | no |
| Other user | no | no | no | no | no |

Plus the standard CRM record permission check on the linked record: a user only sees a message on a related list of a record they have `DetailView` on.

---

## 7. APIs and integration points

### 7.1 Internal PHP API

The Mail module exposes one stable service entry point per use case. Other modules consume only these — no direct table access.

```php
namespace App\Modules\Mail\Models;

class Service
{
    // For related-list rendering on any module
    public static function getMessagesForRecord(string $module, int $recordId, int $limit = 50): array;

    // Single message with attachments + links
    public static function getMessage(int $messageId): ?array;

    // Trigger a manual send (used by IndividualSendMailModal too)
    // Returns ['success' => bool, 'messageId' => int|null, 'error' => string|null]
    public static function send(int $accountId, int $userId, array $envelope, array $body, array $attachments = []): array;

    // Account list visible to a user
    public static function getUserAccounts(int $userId): array;

    // Default account for a user (or null)
    public static function getDefaultAccount(int $userId): ?array;
}
```

`Binding\Engine` is internal — not part of the public API.

### 7.2 HTTP endpoints

All under the FreeCRM front controller (`index.php?module=…`).

| Method | URL | Purpose |
|--------|-----|---------|
| GET | `module=Mail&view=Compose&sourceModule=&sourceRecord=&to=` | render compose modal |
| GET | `module=Mail&view=Detail&record=` | render message detail |
| POST | `module=Mail&action=Send` | send mail |
| POST | `module=Mail&action=Link` | manual bind |
| POST | `module=Mail&action=Unlink` | unbind |
| POST | `module=Mail&action=UploadAttachment` | transient upload during compose |
| GET | `module=Mail&action=DownloadAttachment&id=` | serve attachment |
| GET | `module=Settings:MailAccount&view=List` | admin account list |
| GET | `module=Settings:MailAccount&view=Edit&record=` | create/edit account |
| POST | `module=Settings:MailAccount&action=SaveAjax` | save account |
| POST | `module=Settings:MailAccount&action=TestConnection` | test IMAP + SMTP |
| POST | `module=Settings:MailAccount&action=DeleteAjax` | delete account |
| GET | `module=Settings:MailAccount&view=Logs` | read log table |

All POST endpoints require CSRF token (FreeCRM standard) and authenticated session. Permission checks per §6.8.

### 7.3 Cron registration

Inserted into `vtiger_cron_task`:

```
name='LBL_MAIL_SCAN'
handler_class='App\\Modules\\Mail\\Cron\\Scanner'
frequency=60
module='Mail'
status=1
```

Plus a second task for log pruning:

```
name='LBL_MAIL_LOG_PRUNE'
handler_class='App\\Modules\\Mail\\Cron\\LogPrune'
frequency=86400
```

### 7.4 Related list registration

For each of the six target modules, insert into `vtiger_relatedlists` a row pointing to the Mail module's relation function. The function `App\Modules\Mail\Models\Service::getMessagesForRecord` returns the row set; the related-list template is generic (subject/from/date/direction badge).

### 7.5 External

- **IMAP** over TLS to `mail.itconnect.pl:993` (configurable per account).
- **SMTP** over TLS to `mail.itconnect.pl:465` (configurable per account).
- No third-party APIs.

---

## 8. Deployment and operations

### 8.1 Dependencies

Add via Composer (single new top-level dep):

```
composer require webklex/php-imap:^5.0
```

This pulls a maintained IMAP client wrapping `imap_*` extension functions, with MIME parsing, attachment handling, `APPEND` support, and configurable folders. We already use PHPMailer through `App\Email\Mailer` — no new SMTP dependency.

### 8.2 Schema migration

New install schema additions go into `src/Modules/Install/install_schema/` (the same pattern as existing CRM modules). For an existing instance, ship a one-off migration script (`tools/migrate/2026_mail_module.sql` + a tiny PHP runner) that:

1. creates the seven new tables,
2. inserts the `vtiger_tab` entry for the `Mail` module,
3. inserts `vtiger_settings_field` entry under the "Channels" block pointing at `module=Settings:MailAccount&view=List`,
4. registers the two cron tasks,
5. registers six `vtiger_relatedlists` rows.

No data migration — fresh start per decision §1.

### 8.3 OSSMail decommissioning (parallel to migration)

1. Disable modules `OSSMail`, `OSSMailScanner`, `OSSMailView` via ModuleManager (sets `presence=1` in `vtiger_tab`).
2. Remove the three settings links from `vtiger_settings_field`.
3. Replace `App\Modules\Base\UiTypes\Email::getDisplayValue` so email fields open the new Mail compose URL (one-line edit).
4. Replace `App\Modules\Base\Views\IndividualSendMailModal` send action to delegate to `Mail\Service::send` when the user has a personal Mail account; fall back to the existing `s_yf_mail_smtp` queue when not.
5. After two stable weeks: drop tables `vtiger_ossmailview`, `vtiger_ossmail*`, `roundcube_*`, files under `src/Modules/OSSMail*` and `storage/OSSMailView/`. This is a separate cleanup PR, not blocking Phase 1.

### 8.4 Containers

No change to `docker-compose.yml`:

- `app` container runs the new module code — no extra service,
- `cron` container's supercronic already invokes `cron/vtigercron.php` per minute; that runner reads `vtiger_cron_task` and dispatches our `Scanner::run()`. Nothing to add.

### 8.5 Configuration

- Per-account configuration lives in the DB (`u_yf_mail_accounts`), not in `config/`.
- Encryption key reused from `App\Security\Encryption` (already set on the deployment).
- One module-level config in `config/modules/Mail.php` (created on install) for tunables:
  - `default_scan_interval = 120`
  - `max_consecutive_failures = 5`
  - `attachment_max_size_mb = 25`
  - `purge_info_logs_days = 30`
  - `purge_error_logs_days = 180`
  - `compose_upload_ttl_minutes = 60`

### 8.6 Backup

Attachments under `storage/Mail/` are already covered by the existing `storage/` backup policy (see `general-guidelines.mdc`). DB tables follow the existing MariaDB backup.

---

## 9. Scalability, maintainability, observability

### 9.1 Scale headroom

At 10 mailboxes × 500 msgs/day, peak rate is roughly 0.06 msgs/sec. A single scanner pass per account every 120 s handles this with two orders of magnitude of slack. No need for:

- workers / queues,
- IMAP IDLE,
- horizontal sharding,
- table partitioning.

Concrete numbers we will not hit:

- DB rows in `u_yf_mail_messages` after 5 years at projected volume: ~900k. Indexed lookups stay sub-millisecond.
- Attachment storage at 100 KB average × 500/day × 5 years: ~90 GB. Acceptable on a single filesystem with date-based partitioning.

**If scale ever grows 10×:** move scans to a dedicated worker container that runs each account in its own process (still simple PHP CLI, supervised by Docker), and bump cron interval down. The data schema does not need to change.

### 9.2 Maintainability

- One module, ~3 k LOC budget for v1.
- No event bus, no plugin loader. Adding a new module to auto-bind means: write a class implementing `Binding\RuleInterface`, register it in `Binding\Engine::$rules`.
- Templates and PHP follow the same MVC patterns as the rest of FreeCRM — onboarding for the project owner is zero.
- Composer-managed IMAP code, so we are not patching `imap_*` quirks ourselves.

### 9.3 Observability

Three layers, all visible to the admin without leaving the CRM:

1. **Per-account dashboard** in Settings → Mail Accounts: last scan time, status badge, consecutive failures, count of messages last 24 h.
2. **Log table view** in Settings → Mail Accounts → Logs: filterable by level/action/account/date. Default view: last 200 entries.
3. **Per-message detail** shows binding decisions (which rule, which field matched).

Plus a tail-friendly text log at `cache/logs/mail.log` (rotating daily) for cases where the admin UI itself is broken.

No metrics endpoint, no Prometheus. If we ever need that, log table is queryable directly.

---

## 10. Security model

### 10.1 Threats and mitigations

| Threat | Mitigation |
|--------|------------|
| Stolen DB dump exposes credentials | `password_enc` stored as authenticated ciphertext via `App\Security\Encryption`; key in `config/main.php`, outside the DB; passwords never logged. |
| Stored XSS via HTML mail body | On display, body is purified with HTMLPurifier (already used by FreeCRM); inline images served via controller with `Content-Disposition: inline` and strict MIME whitelist. |
| Path traversal via attachment filename | Filenames sanitized on save (`preg_replace('/[^A-Za-z0-9._-]/','_'`)); storage path always anchored under `storage/Mail/`; download controller validates path is inside the configured root. |
| Mail header injection in compose | All user input goes through PHPMailer's setters (no manual header assembly). Newlines stripped from `To`/`Cc`/`Bcc`/`Subject` server-side. |
| User accesses other users' mail | Service queries always join on `u_yf_mail_account_users` for the current user, except for admin. Related-list query additionally checks record permission. |
| Compose abused to spam from CRM | Send rate-limited per user (configurable, default 60/min) via simple counter in `u_yf_mail_log`. Hard cap on attachment size at `attachment_max_size_mb`. |
| IMAP server certificate mismatch | Per-account flag `imap_validate_cert` — defaults to on for own server; admin must explicitly opt out. |
| Replay of compose POST | Standard FreeCRM CSRF token; idempotency key `(user_id, message_id_header)` prevents accidental duplicate sends on retry. |
| Sensitive content in logs | Log writer scrubs `password`, `password_enc`, `body_html`, `body_text` keys from `context_json`. |

### 10.2 Trust boundaries (explicit assumptions)

- Own mail server (`mail.itconnect.pl`) is trusted with full credentials.
- TLS to mail server is mandatory; plain auth is **not** allowed at the application level (no `secure='none'` enabled on production).
- DKIM/SPF/DMARC alignment is handled at MTA level, not in the app.
- All CRM users are trusted to handle records they have access to; we do not encrypt body content per-user.

### 10.3 Permission checks

Every action in `Actions/` calls the same gate as its first line:

```php
\App\Modules\Mail\Models\Acl::assert($user, $action, $resource);
```

Where `Acl` centralises the matrix from §6.8. No permission logic in templates or controllers beyond this.

---

## 11. Implementation phases and execution order

Estimated effort assumes one developer, working day = 4 productive hours.

### Phase 1 — Foundation (5–7 days)

**Goal:** admin can create a mail account in the CRM and validate IMAP/SMTP login. No scanning, no sending yet.

1. `composer require webklex/php-imap`.
2. Create the seven tables (§6) via a one-off SQL migration + corresponding entries in `install_schema/` for fresh installs.
3. Register `Mail` module skeleton (`vtiger_tab` row, namespace `App\Modules\Mail`).
4. Register `Settings:MailAccount` settings module.
5. Implement `Models\Account` (CRUD with encrypted `password_enc`).
6. Implement `Actions\TestConnection`: open IMAP → list folders, open SMTP → noop login.
7. Settings UI: list view, edit form, test-connection button.

**Acceptance:** admin adds an account for `bmankowski@itconnect.pl`, clicks Test, sees green check.

### Phase 2 — Inbound (5–7 days)

**Goal:** mails arriving at configured accounts appear in the CRM, automatically linked to matching records.

1. `Imap\Client` + `Imap\Fetcher`.
2. `Models\Message::insertIfNew` with deduplication.
3. Attachment write to `storage/Mail/…`.
4. `Binding\Engine` + five rules: `ByEmail` (generic), `HelpDeskSubject`.
   - `ByEmail` iterates a configured list of `{module, emailField}` tuples: Kandydaci/`email1`, Contacts/`email`, Accounts/`email1`, Leads/`email`, SSalesProcesses/`assigned_contact_email` — exact list pulled from the modules' field definitions during Phase 2 start.
5. `Cron\Scanner::run()` wired to `vtiger_cron_task`.
6. Register related list `E-maile` on six target modules.
7. Smarty template for the related list (subject, badge, from/to, date, attachments icon, link to message detail).
8. `Views\Detail` — purified HTML rendering, attachment download links, list of bound records with manual unbind.
9. `Actions\Link` / `Actions\Unlink`.

**Acceptance:** send a mail from external account to `bmankowski@itconnect.pl` quoting a candidate's email address; within 2 minutes it appears under the Kandydat's "E-maile" tab with status badge `IN`.

### Phase 3 — Outbound (5–7 days)

**Goal:** user clicks email on a record, fills the modal, the mail leaves through their own SMTP and a copy lands in their Sent folder.

1. `Smtp\Sender` — PHPMailer with per-account creds.
2. `Imap\Appender` — `webklex/php-imap`'s `appendMessage` to the Sent folder; failure logged, doesn't fail send.
3. `Views\Compose` modal (Smarty), based on the existing `IndividualSendMailModal.tpl` but with the account-picker dropdown.
4. `Actions\Send`: validate, render template, send, append, persist outbound message, auto-link to source record.
5. Wire the email UI type (`App\Modules\Base\UiTypes\Email::getDisplayValue`) to open the new compose modal when the user has at least one Mail account; otherwise fall back to `mailto:`.
6. Per-user "default account" toggle in `u_yf_mail_account_users.is_default`.

**Acceptance:** on a Kandydat record, click email → modal opens → write → Send → mail arrives at recipient, copy visible in `bmankowski@itconnect.pl/Sent` via Thunderbird, new row in `u_yf_mail_messages` with `direction=out`, related list refreshes.

### Phase 4 — Polish, operations, decommission OSSMail (3–5 days)

1. Admin log viewer (`Settings:MailAccount&view=Logs`).
2. Per-account health on the account list (last scan, status, failures).
3. Rate-limiting on `Actions\Send`.
4. `Cron\LogPrune` task.
5. Disable OSSMail / OSSMailScanner / OSSMailView modules in ModuleManager.
6. Remove their menu and settings entries.
7. Documentation: short admin guide on adding accounts, and a developer note on adding new Binding rules.

**Acceptance:** OSSMail menu items gone; new Mail module visible in main nav; "Wyślij e-mail" works everywhere it used to; cron log shows no errors for 48 h.

### Phase 5 — Cleanup (1 day, scheduled 2 weeks later)

Drop `roundcube_*` and `vtiger_ossmail*` tables, delete `src/Modules/OSSMail*` directories and `storage/OSSMailView/`. Separate PR, gated on Phase 4 stability.

### Sequencing rules

- Phases 1 and 2 are read-only and can be deployed without disabling OSSMail — they coexist.
- Phase 3 is the first user-visible behaviour change; deploy it only after Phase 2 has been running on production for at least 3 days with `last_scan_status='ok'` consistently.
- Phase 4's step 5 (disable OSSMail) is the cutover. Do not touch it until Phase 3 has handled at least 20 real sends without errors.
- Phase 5 is irreversible cleanup. Do not run before Phase 4 has been stable for 14 days.

---

## 12. Open questions deferred

These are explicit "we will decide later, the architecture does not block them":

- **OAuth2** — adding it later means: extend `u_yf_mail_accounts` with `auth_type ENUM('password','oauth2')`, add a token storage table, swap `Smtp\Sender` and `Imap\Client` to read the token instead of `password_enc`. No schema rewrite needed.
- **Threading view** — `in_reply_to` and `references_hdr` already stored; building a "show conversation" UI later is purely a query layer concern.
- **Filtering rules** — not needed at projected scale; if added, becomes a table `u_yf_mail_rules` evaluated inside `Binding\Engine`.
- **Outbound queue** — current design sends synchronously. If a future requirement is "send 500 templated mails at once", reuse the existing `s_yf_mail_queue` infrastructure but tag rows with `account_id` for per-user sending.

---

## 13. Pre-implementation validation questions

The architecture above makes assumptions that should be confirmed against the actual deployment **before Phase 1 starts**. Each item below either confirms a default or surfaces a value that must be wired into the code.

1. **Email field names per target module.**
   The `ByEmail` rule needs a concrete `{module, fieldName}` map. Assumed defaults are based on common FreeCRM conventions:
   - Kandydaci → `email1`
   - Contacts → `email`
   - Accounts → `email1`
   - Leads → `email`
   - SSalesProcesses → `assigned_contact_email` (placeholder)
   Confirm by querying `vtiger_field` for each `tabid` and `uitype IN (13, 104)` (email types) before Phase 2 starts. The result goes into a single config array in `App\Modules\Mail\Models\Binding\ByEmail`.

2. **`App\Security\Encryption` key initialisation.**
   The design relies on the existing encryption key for `password_enc`. The key must already be configured on the target deployment (`local.itconnect.pl`) and must remain stable — losing it means losing all stored mailbox passwords. Confirm:
   - the key file/value exists in `config/main.php` (or wherever `App\Security\Encryption` reads it),
   - it is included in the backup policy,
   - it is **not** rotated as part of the Mail rollout.

3. **IMAP `Sent` folder name on `mail.itconnect.pl`.**
   The default value for `imap_folder_sent` is `Sent`, but some servers expose it as `Wysłane`, `Sent Items`, or `INBOX.Sent`. Confirm by logging into one real mailbox and listing folders. The TestConnection action should list folders and let the admin pick from a dropdown rather than hard-coding the value.

4. **`IndividualSendMailModal` scope (Phase 3).**
   The architecture currently assumes the bulk-send modal switches to per-user Mail accounts with a fallback to `s_yf_mail_smtp` when the user has no Mail account. The alternative is to leave the bulk modal entirely on the existing `s_yf_mail_smtp` path and only switch the single-record "Wyślij e-mail" flow. Confirm which:
   - **(a)** bulk modal also uses Mail accounts (broader change, consistent UX),
   - **(b)** bulk modal stays on system SMTP (smaller change, two parallel send paths).

These questions do **not** block writing the architecture but must be answered before code is written for the phase that depends on them. Phase 1 and Phase 2 can start with the defaults above; question 4 only matters at Phase 3.

---

## 14. Implementation reference checklist

Concrete file list an engineer can use as a build order for Phase 1+2+3:

```
composer.json                                       +webklex/php-imap
config/modules/Mail.php                              new
src/Modules/Install/install_schema/Mail.php          new (schema)
src/Modules/Install/install_schema/data_mail.sql     new (tab+cron+settings+related)
src/Modules/Mail/Mail.php                            new (install hook)
src/Modules/Mail/Models/Account.php                  new
src/Modules/Mail/Models/Message.php                  new
src/Modules/Mail/Models/Attachment.php               new
src/Modules/Mail/Models/Service.php                  new (public API)
src/Modules/Mail/Models/Acl.php                      new
src/Modules/Mail/Models/Binding/Engine.php           new
src/Modules/Mail/Models/Binding/ByEmail.php          new
src/Modules/Mail/Models/Binding/HelpDeskSubject.php  new
src/Modules/Mail/Imap/Client.php                     new
src/Modules/Mail/Imap/Fetcher.php                    new
src/Modules/Mail/Imap/Appender.php                   new
src/Modules/Mail/Smtp/Sender.php                     new
src/Modules/Mail/Cron/Scanner.php                    new
src/Modules/Mail/Cron/LogPrune.php                   new
src/Modules/Mail/Views/Compose.php                   new
src/Modules/Mail/Views/Detail.php                    new
src/Modules/Mail/Views/RelatedList.php               new
src/Modules/Mail/Actions/Send.php                    new
src/Modules/Mail/Actions/Link.php                    new
src/Modules/Mail/Actions/Unlink.php                  new
src/Modules/Mail/Actions/UploadAttachment.php        new
src/Modules/Mail/Actions/DownloadAttachment.php      new
src/Modules/Settings/MailAccount/Models/Module.php   new
src/Modules/Settings/MailAccount/Models/Record.php   new
src/Modules/Settings/MailAccount/Views/List.php      new
src/Modules/Settings/MailAccount/Views/Edit.php      new
src/Modules/Settings/MailAccount/Views/Logs.php      new
src/Modules/Settings/MailAccount/Actions/SaveAjax.php       new
src/Modules/Settings/MailAccount/Actions/DeleteAjax.php     new
src/Modules/Settings/MailAccount/Actions/TestConnection.php new
layouts/basic/modules/Mail/Compose.tpl               new
layouts/basic/modules/Mail/Detail.tpl                new
layouts/basic/modules/Mail/RelatedList.tpl           new
layouts/basic/modules/Settings/MailAccount/*.tpl     new
public/layouts/basic/modules/Mail/resources/Mail.js  new
languages/pl_pl/Mail.json                            new
languages/en_us/Mail.json                            new
languages/pl_pl/Settings/MailAccount.json            new
languages/en_us/Settings/MailAccount.json            new
src/Modules/Base/UiTypes/Email.php                   modified (compose URL switch)
src/Modules/Base/Views/IndividualSendMailModal.php   modified (account picker)
```

This is the complete surface area. Anything not on this list is either reused from the existing FreeCRM code or out of scope for v1.
