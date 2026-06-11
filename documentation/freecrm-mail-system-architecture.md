# FreeCRM Mail System — Architecture

**Status:** proposed (greenfield replacement of OSSMail / OSSMailScanner / OSSMailView)
**Author:** bmankowski@gmail.com
**Date:** 2026-05-21
**Scope:** new `Mail` module for inbound scanning + outbound from-record sending, replacing the Roundcube-based OSSMail stack.

---

## 1. Background and design constraints

FreeCRM ships with Roundcube webmail (`OSSMail`), a scanner (`OSSMailScanner`) and a related-list module (`OSSMailView`), but **the project owner confirms none of these are in active use** on the target deployment. We therefore design a clean greenfield module without backward-compatibility code, fallback paths, or coexistence requirements — the OSSMail stack is treated as dead weight to be removed later (cleanup only, not blocking).

This design covers **what we actually use**:

- scanning company mailboxes,
- sending mails from CRM records (Kandydat, Contact, Account, …) authenticated as the user's own mailbox,
- listing the resulting correspondence on each record.

It explicitly does **not** build a webmail client (no folders UI, no threads view, no drafts, no in-app reply chains).

Design decisions taken with the project owner:

| Topic | Decision |
|------|----------|
| Account model | Mixed: per-user personal mailbox **and** shared team mailboxes |
| Auth | Login + password only, against own mail server (no OAuth2) |
| Outbound paths | **Two coexisting paths**: (i) Mail module → user's personal/shared IMAP+SMTP account; (ii) existing `s_yf_mail_smtp` for role-based system sends (`rekrutacja@`, `marketing@`) — chosen per template |
| Sender selection | Template-driven: each email template carries `sender_type ∈ {user_account, system_smtp, any}` |
| Per-user editability | A non-admin user can only edit their own personal account record (default flag); admin manages all accounts including shared ones |
| Sent copy | IMAP `APPEND` to the account's "Sent" folder (per-account flag, folder discovered at TestConnection time — not hard-coded) |
| Migration | Greenfield, no fallbacks to OSSMail/Roundcube; the legacy modules can be left untouched until a later cleanup |
| Auto-bind modules | Kandydaci, Contacts, Accounts, Leads, SSalesProcesses, HelpDesk |
| Visibility of messages on records | **Hybrid**: messages from shared mailboxes are visible to any user with `DetailView` on the bound record; messages from personal mailboxes only to the account owner |
| Auto-bind outbound | Source record (the one user composed from) always linked; additionally any other CRM record matched by To/Cc/Bcc email |
| Reply-To for shared mailbox sends | Configurable per shared account (3 modes: same as From, user's personal email, custom address) |
| Backfill on account add | None by default (`last_uid` = current MAX UID at install); an optional "Import history from date" admin action is deferred to a later phase |
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
6. **Outbound from record (template-driven sender selection)**
   - On any module that has the related list, the user clicks "Wyślij e-mail" (or clicks the email field on the record).
   - A compose modal opens, prefilled with To = the record's email field. The template picker shows templates available for the source module via existing `App\Email\Mail::getTempleteList`.
   - **Sender choice depends on the picked template's `sender_type`:**
     - `user_account` — modal shows a dropdown of the current user's accessible **Mail accounts** (personal + shared they're on). On send, mail goes through that account's SMTP, authenticated as the user, with `From: <account.from_name> <account.username>`.
     - `system_smtp` — modal shows the fixed system SMTP server defined on the template (`u_yf_emailtemplates.smtp_id` → `s_yf_mail_smtp`). No picker, just "Wysyłka z: rekrutacja@itconnect.pl" label. `From` is taken from the SMTP record's `from_email`/`from_name`.
     - `any` — modal shows both options stacked: user's accounts at top, then available system SMTP servers, with the user's default Mail account preselected.
   - Live preview through `App\TextParser` works identically regardless of sender choice.
   - **Behavior when user has no Mail account configured and template requires `user_account`**: modal opens but Send is disabled; an info banner explains the situation and shows a "Skonfiguruj swoje konto pocztowe" button linking to `Settings:MailAccount&view=Edit` (prefilled with the current user's id).
   - On send via user account: outbound persisted in `u_yf_mail_messages` (direction=out, `account_id` set); IMAP `APPEND` to the account's Sent folder if enabled; **auto-bound to the source record (manual link_type=auto, match_field='compose_source')** plus any additional CRM records whose email field matches To/Cc/Bcc (link_type=auto, match_field=actual field name).
   - On send via `system_smtp`: outbound persisted in `u_yf_mail_messages` (direction=out, `account_id` = NULL, `smtp_id` set, see §6.3 schema change); no IMAP APPEND (no per-user IMAP context); same auto-binding logic.
7. **Bulk outbound from list / mass mailing (`IndividualSendMailModal`)**
   - The same template-driven branching applies. The bulk modal calls into the same Send service per recipient.
   - Templates intended for mass marketing (`marketing@`) typically carry `sender_type = system_smtp` and use the existing `s_yf_mail_queue` to queue items; templates intended for personal touch-ups (`user_account`) send synchronously through the user's SMTP.
   - The bulk modal never silently switches paths — if a template requires `user_account` and the user has no configured account, bulk send is blocked with the same banner as the single-record flow.
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
       INBOUND                                  OUTBOUND
                                  ┌─────────── path A (user) ──────────┐
┌─────────────────────────┐       │   ┌──────────────────────────┐     │
│  IMAP server (own)      │◄──────┼───┤ Mail module: Smtp\Sender │     │
│  mail.itconnect.pl:993  │APPEND │   │ (user's SMTP creds)      │     │
│                         │       │   └────────────▲─────────────┘     │
│                         │◄──────┤                │                   │
│                         │ fetch │                │                   │
└─────────────────────────┘       │     ┌──────────┴──────────┐        │
            ▲                     │     │  Mail Actions\Send  │        │
            │                     │     │  (sync HTTP)        │        │
┌───────────┴────────────────┐    │     └──────────▲──────────┘        │
│  Mail Cron\Scanner         │    │                │ if template       │
│  (cron tick / 60-120 s)    │    │                │ sender_type =     │
└────────────┬───────────────┘    │                │ user_account      │
             │ parse + store      │                │ or any+user pick  │
             ▼                    │     ┌──────────┴──────────┐        │
   ┌──────────────────────┐       │     │ Compose modal       │        │
   │ u_yf_mail_messages   │       │     │ on record           │        │
   │ u_yf_mail_attach…    │       │     └──────────▲──────────┘        │
   │ u_yf_mail_record_li… │       │                │                   │
   │ u_yf_mail_log        │       │                │ if template       │
   └──────────┬───────────┘       │                │ sender_type =     │
              │                   │                │ system_smtp       │
              │ related-list      │                ▼                   │
              │ query             │     ┌─────────────────────┐        │
              ▼                   │     │ App\Email\Mailer    │        │
   ┌─────────────────────────┐    │     │ (existing)          │        │
   │ Related list "E-maile"  │    │     └──────────▲──────────┘        │
   │ on Kandydat, Contact,   │    │                │                   │
   │ Account, Lead, SP, HD   │    │     ┌──────────┴──────────┐        │
   └─────────────────────────┘    │     │ s_yf_mail_smtp      │        │
                                  │     │ (rekrutacja@, etc.) │        │
   ┌─────────────────────────┐    │     └──────────▲──────────┘        │
   │ Settings: Mail Accounts │    │                │                   │
   │ admin UI                │    │                ▼                   │
   └─────────────────────────┘    └────────── SMTP server (own) ───────┘
                                              mail.itconnect.pl:465
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

**Shared with the existing system SMTP path (`s_yf_mail_smtp`)**

- The existing `App\Email\Mailer` + `s_yf_mail_smtp` infrastructure is **not deprecated**. It stays as the *system/role-based* outbound path for templates marked `sender_type = system_smtp` (e.g. `rekrutacja@`, `marketing@`, system notifications, forgot-password, queued mass mailings).
- The new Mail module's `Send` action delegates to `App\Email\Mailer` when the chosen template/account combination resolves to `system_smtp`. There is no duplication of SMTP code: the new module brings its own `Smtp\Sender` only for the per-user authenticated path.

**Out of scope for Mail module**

- Inbound scanning of `s_yf_mail_smtp`-bound mailboxes — system SMTP is send-only by definition. If a `rekrutacja@` mailbox needs to be scanned as well, it must be configured **also** as a shared Mail account.
- Migration or coexistence wrappers around OSSMail/OSSMailScanner/OSSMailView — they are not running on the target deployment.

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
3. `Views\Compose` loads:
   - the source record's accessible email fields (for the To prefill),
   - the list of templates for the source module,
   - for each template, its `sender_type`,
   - the current user's accessible Mail accounts (personal + shared with `can_send=1`),
   - available `s_yf_mail_smtp` servers (admin-managed).
4. Modal renders:
   - `To`, `Cc`, `Bcc` (Cc/Bcc collapsed by default),
   - Template picker (filtered to the source module),
   - Subject, body editor (CKEditor),
   - Attachments uploader (multipart, stored under `cache/Mail/upload/`),
   - Live preview pane fed by `App\TextParser`,
   - **Sender area whose contents depend on the picked template**:
     - on `user_account`: dropdown of the user's Mail accounts (default preselected), label "Wysyłka z: " above; if user has no accounts → banner + "Skonfiguruj teraz" button, Send disabled,
     - on `system_smtp`: read-only label "Wysyłka z: `<smtp.from_name> <smtp.from_email>`" pulled from `s_yf_mail_smtp` row referenced by the template; no picker,
     - on `any`: combined dropdown grouped into "Moje konta" / "Konta systemowe", default = user's default Mail account if any, otherwise the template's `smtp_id`.
5. User clicks Send → POST `index.php?module=Mail&action=Send` with `{templateId, senderRef}` where `senderRef` is `account:<id>` or `smtp:<id>`.
6. `Actions\Send` decides the outbound path:

   **Path A — user_account (Mail module's own Smtp\Sender)**
   1. permission check: user has access to `account` AND `DetailView` on source record,
   2. render subject + body via `TextParser`,
   3. inline CSS via `App\Utils\TemplateStyles::inlineEmailCss`,
   5. `Smtp\Sender::send($account, $envelope, $body, $attachments)`:
      - configure PHPMailer with the account's host/port/secure/username/decrypted password,
      - set `From: <account.from_name> <account.username>`,
      - set `Reply-To` according to `account.reply_to_mode` (see §6.1),
      - set `To`/`Cc`/`Bcc`, `Subject`, HTML + AltBody, attachments,
      - call `send()`,
   6. on success, if `account.append_sent = 1`:
      - `Imap\Appender::appendToSent($account, $rfc822)` — builds RFC822 from PHPMailer's `getSentMIMEMessage()` and APPENDs to `account.imap_folder_sent`; failure is logged but does **not** fail the user action,
   7. INSERT into `u_yf_mail_messages` (direction=out, `account_id` set, `smtp_id` NULL, `imap_uid` NULL, `message_id` from generated header),
   8. INSERT attachment rows + move uploaded files from `cache/Mail/upload/` to `storage/Mail/…`,
   9. INSERT `u_yf_mail_record_links` for the source record (link_type=auto, match_field='compose_source') AND for any other CRM record whose configured email field matches To/Cc/Bcc (link_type=auto, match_field=actual field name),
   10. log success, return JSON `{success: true, messageId: X}`.

   **Path B — system_smtp (existing App\Email\Mailer)**
   1. permission check: user has `DetailView` on source record,
   2. render template (same as Path A),
   3. delegate to `App\Email\Mailer::sendFromTemplate([...])` with the template's `smtp_id` forced; for queueable mass sends use `App\Email\Mailer::addMail([...])` instead,
   4. on success, persist a row in `u_yf_mail_messages` with direction=out, `account_id` NULL, `smtp_id` set, `from_email` = the SMTP's `from_email`,
   5. write attachments and bind links same as Path A.

7. On error from either path: HTTP 200 + `{success: false, error: '…'}`, modal shows error, draft preserved client-side. Failure to APPEND to Sent (Path A only) is silent for the user but logged.

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
| `kind` | ENUM('personal','shared') | drives visibility model (§6.8) |
| `owner_user_id` | INT NULL | required when `kind='personal'`, NULL for shared |
| `imap_host` | VARCHAR(190) | |
| `imap_port` | SMALLINT UNSIGNED | default 993 |
| `imap_secure` | ENUM('ssl','tls','none') | default 'ssl' |
| `imap_validate_cert` | TINYINT(1) | default 1 |
| `imap_folder_inbox` | VARCHAR(190) | default 'INBOX' |
| `imap_folder_sent` | VARCHAR(190) | **discovered at TestConnection time**, not hard-coded; admin picks from a dropdown listing the server's actual folders (`Sent`, `Wysłane`, `Elementy wysłane`, …) |
| `smtp_host` | VARCHAR(190) | |
| `smtp_port` | SMALLINT UNSIGNED | default 465 |
| `smtp_secure` | ENUM('ssl','tls','none') | |
| `username` | VARCHAR(190) | full email address |
| `password_enc` | VARBINARY(512) | `App\Security\Encryption::encrypt()` |
| `from_name` | VARCHAR(120) | optional display name in From |
| `reply_to_mode` | ENUM('same_as_from','user_personal','custom') | default `same_as_from`; `user_personal` only meaningful for shared accounts (resolves to sending user's `vtiger_users.email1` at send time) |
| `reply_to_address` | VARCHAR(190) NULL | used when `reply_to_mode='custom'` |
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

Indexes: `(active, next_scan_at)`, `(owner_user_id)`, `(kind)`.

Edit rules (enforced in `Settings:MailAccount` controller):

- non-admin user can edit only `reply_to_mode`/`reply_to_address` (if `kind='personal'` and they are `owner_user_id`), `from_name`, and their own row in `u_yf_mail_account_users` (`is_default`),
- non-admin user **cannot** edit host/port/credentials/`active`/`kind`/`owner_user_id`,
- admin edits everything.

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
| `account_id` | INT UNSIGNED NULL | FK → `u_yf_mail_accounts`; NULL when message was sent through `s_yf_mail_smtp` (system path) |
| `smtp_id` | INT UNSIGNED NULL | FK → `s_yf_mail_smtp`; NULL when message was sent or received through a Mail account |
| `sender_user_id` | INT NULL | for outbound: which CRM user pressed Send; for inbound: NULL |
| `direction` | ENUM('in','out') | |
| `imap_uid` | INT UNSIGNED NULL | NULL for outbound or for system-SMTP sends |
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

CHECK constraint (or app-level): exactly one of `account_id` / `smtp_id` is non-NULL.

Indexes: UNIQUE `(account_id, imap_uid)` (where `imap_uid IS NOT NULL`), `(message_id)`, `(date_sent)`, `(from_email)`, `(direction, date_sent)`, `(sender_user_id, direction)`.

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

Two orthogonal checks. **Both must pass** for a user to see a message on a record's related list.

**(1) Account access** — which accounts a user is "in":

| Subject | Account read | Account send | Account CRUD |
|---------|--------------|--------------|--------------|
| Admin | all | all | yes |
| User — owner of `kind='personal'` account | yes (own) | yes | non-system fields only (reply_to, from_name, is_default) |
| User — listed in `u_yf_mail_account_users` for `kind='shared'` account | yes (shared) | if `can_send=1` | no |
| Other user | no | no | no |

**(2) Message visibility on a related list** (the "hybrid" rule):

| Message source | Visible to … |
|----------------|--------------|
| Inbound from `kind='shared'` account | any user with `DetailView` on the linked record |
| Inbound from `kind='personal'` account | only the account's `owner_user_id` (and admins) |
| Outbound, `account_id` set, `kind='shared'` | any user with `DetailView` on the linked record |
| Outbound, `account_id` set, `kind='personal'` | only `sender_user_id` (i.e. the user who sent it) and admins |
| Outbound, `smtp_id` set (system SMTP path) | any user with `DetailView` on the linked record |

Concretely: a user's related-list query is

```sql
SELECT m.* FROM u_yf_mail_messages m
JOIN u_yf_mail_record_links l ON l.message_id = m.id
LEFT JOIN u_yf_mail_accounts a ON a.id = m.account_id
WHERE l.crm_module = :module AND l.crm_record_id = :recordId
  AND (
        :isAdmin = 1
     OR a.kind IS NULL                          -- system_smtp path, always visible if record-permitted
     OR a.kind = 'shared'                       -- shared mailbox, always visible if record-permitted
     OR (a.kind = 'personal' AND a.owner_user_id = :userId)
     OR (m.direction = 'out' AND m.sender_user_id = :userId)
      )
ORDER BY m.date_sent DESC;
```

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

### 8.3 OSSMail status

OSSMail / OSSMailScanner / OSSMailView are not in active use on the target deployment, so there is **no decommissioning workflow** to coordinate with the Mail module rollout. The legacy code and tables can be left in place during the rollout; a one-shot cleanup PR can be scheduled at any later date to:

1. Disable modules `OSSMail`, `OSSMailScanner`, `OSSMailView` in `vtiger_tab` (`presence=1`).
2. Remove their entries from `vtiger_settings_field`.
3. Drop tables `vtiger_ossmail*` and `roundcube_*`.
4. Delete `src/Modules/OSSMail*` and `storage/OSSMailView/`.

This is purely housekeeping and is **not** a dependency of any Mail module phase.

### 8.4 Integration with the existing email UI type

`App\Modules\Base\UiTypes\Email::getDisplayValue` currently builds a `<a class="sendMailBtn">` whose URL points at `OSSMail&view=compose`. As part of Phase 3, this is changed to point at `Mail&view=Compose` with the same query parameters (`crmModule`, `crmRecord`, `to`). One-line change, no `internal_mailer` legacy preference check — that flag is ignored in the new flow.

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

**Goal:** admin can create a mail account in the CRM and validate IMAP/SMTP login; user can edit their own account Reply-To. No scanning, no sending yet.

1. `composer require webklex/php-imap`.
2. Create the seven tables (§6) via a one-off SQL migration + corresponding entries in `install_schema/` for fresh installs. Includes `kind`, `reply_to_mode`, `reply_to_address` columns and the dual-FK schema on `u_yf_mail_messages`.
3. Add `sender_type` columns to `u_yf_emailtemplates`. Backfill: all existing templates get `sender_type='system_smtp'` (preserves current behavior).
4. Register `Mail` module skeleton (`vtiger_tab` row, namespace `App\Modules\Mail`).
5. Register `Settings:MailAccount` settings module with role-based edit gating (admin vs personal owner).
6. Implement `Models\Account` (CRUD with encrypted `password_enc`).
7. Implement `Actions\TestConnection`: open IMAP, list mailbox folders (returned to UI as a dropdown for `imap_folder_sent` selection), open SMTP, perform noop login.
8. Settings UI: list view (admin sees all, user sees own + accessible shared), edit form, test-connection button, folder-pick dropdown populated from TestConnection response.

**Acceptance:** admin adds a personal account for `bmankowski@itconnect.pl` and a shared account `rekrutacja@itconnect.pl`, clicks Test, sees green check + correctly listed "Wysłane"/"Sent"/"Elementy wysłane" folder name; logs in as a non-admin user, sees only own personal account, can change `is_default` but not host/credentials.

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

### Phase 3 — Outbound, both paths (5–7 days)

**Goal:** user clicks email on a record, fills the modal, and the mail leaves through the appropriate path based on the template — either user's SMTP (with APPEND to their Sent) or `s_yf_mail_smtp` (no APPEND).

1. `Smtp\Sender` — PHPMailer with per-account creds Reply-To resolution from `account.reply_to_mode`.
2. `Imap\Appender` — `webklex/php-imap`'s `appendMessage` to the per-account `imap_folder_sent`; failure logged, doesn't fail send.
3. Adapter for the `system_smtp` path: thin wrapper around existing `App\Email\Mailer` that also writes to `u_yf_mail_messages` and runs the binding engine on outbound.
4. `Views\Compose` modal (Smarty): template picker, dynamic sender area (account dropdown / system label / combined), live preview, attachment upload, Send disabled when sender resolution fails.
5. `Actions\Send`: branch by `sender_type` (Path A or Path B per §5.2), persist outbound message, run binding (source record always, plus auto-match on To/Cc/Bcc).
6. Wire `App\Modules\Base\UiTypes\Email::getDisplayValue` to open the new compose modal (drop the `internal_mailer` legacy check).
7. Per-user "default account" toggle in `u_yf_mail_account_users.is_default`.
8. `IndividualSendMailModal` switch: replace its send action with `Mail\Actions\Send` per recipient; the template's `sender_type` dictates the path. Mass sends with `sender_type=system_smtp` still go to `s_yf_mail_queue`.

**Acceptance (Path A):** on a Kandydat record, click email, pick a template with `sender_type='user_account'`, write, Send → mail arrives at recipient, copy visible in `bmankowski@itconnect.pl/<chosen_folder>` via Thunderbird, new row in `u_yf_mail_messages` with `direction='out'`, `account_id` set, related list refreshes, binding shows source record (auto/compose_source) plus matched recipients.

**Acceptance (Path B):** pick a template with `sender_type='system_smtp'` and `smtp_id` pointing at `rekrutacja@`, send, no APPEND occurs, row written with `account_id=NULL`, `smtp_id` set, `from_email='rekrutacja@itconnect.pl'`, related list still refreshes and shows the message under the Kandydat.

### Phase 4 — Polish and operations (3–5 days)

1. Admin log viewer (`Settings:MailAccount&view=Logs`) with filters by level/action/account/date.
2. Per-account health on the account list (last scan, status badge, failures, last 24 h message count).
3. Rate-limiting on `Actions\Send` (default 60/min per user, configurable).
4. `Cron\LogPrune` task — keep `info` 30 d, `warn`/`error` 180 d.
5. Documentation: short admin guide on adding accounts, developer note on adding new Binding rules, end-user guide on Reply-To.

**Acceptance:** new Mail module visible in main nav and settings; "Wyślij e-mail" works on all six target modules; cron log shows no errors for 48 h; admin can read both paths' logs in one view.

### Sequencing rules

- Phases 1 and 2 are read-only and can be deployed independently.
- Phase 3 is the first user-visible behaviour change; deploy it only after Phase 2 has been running on production for at least 3 days with `last_scan_status='ok'` consistently.
- The OSSMail decommissioning described in §8.3 is a **separate housekeeping PR** with no dependency on these phases — schedule it whenever convenient.

---

## 12. Open questions deferred

These are explicit "we will decide later, the architecture does not block them":

- **OAuth2** — adding it later means: extend `u_yf_mail_accounts` with `auth_type ENUM('password','oauth2')`, add a token storage table, swap `Smtp\Sender` and `Imap\Client` to read the token instead of `password_enc`. No schema rewrite needed.
- **Threading view** — `in_reply_to` and `references_hdr` already stored; building a "show conversation" UI later is purely a query layer concern.
- **Filtering rules** — not needed at projected scale; if added, becomes a table `u_yf_mail_rules` evaluated inside `Binding\Engine`.
- **Outbound queue** — current design sends synchronously. If a future requirement is "send 500 templated mails at once", reuse the existing `s_yf_mail_queue` infrastructure but tag rows with `account_id` for per-user sending.

---

## 13. Pre-implementation validation questions

Status legend: ✅ answered · ⚠️ default chosen, confirm before the relevant phase · 🔍 to be checked against actual deployment data, no decision needed.

### Answered

1. ✅ **Account model** — mixed (personal + shared).
2. ✅ **Auth** — login/password only, no OAuth2.
3. ✅ **Outbound** — two paths: Mail module (user/shared with per-user SMTP) + existing `s_yf_mail_smtp` for role-based system sends.
4. ✅ **Per-user editability** — user edits only own personal account (Reply-To, default); admin edits everything.
5. ✅ **IMAP Sent folder** — not hard-coded; discovered by TestConnection at account creation, admin picks from dropdown.
6. ✅ **`IndividualSendMailModal`** — template-driven; `sender_type` on each template chooses path.
7. ✅ **OSSMail fallbacks** — none; greenfield install.

### Default chosen — confirm before the relevant phase

8. ⚠️ **Visibility of messages on records** (§6.8) — default: **hybrid** (shared mailbox messages visible to anyone with record DetailView; personal mailbox messages only to account owner). Confirm before Phase 2 starts coding the related-list query.

9. ⚠️ **Outbound auto-binding scope** — default: **source record always + auto-match on To/Cc/Bcc** across all six target modules. Confirm before Phase 3.

10. ⚠️ **Reply-To for shared mailbox sends** — default: **per-account config** with three modes (`same_as_from`, `user_personal`, `custom`); admin sets per shared account. Default mode for new shared accounts: `same_as_from`. Confirm before Phase 3.

11. ⚠️ **Behavior when user has no Mail account and template needs `user_account`** — default: **block Send, show banner with "Skonfiguruj teraz" link** to `Settings:MailAccount&view=Edit`. Confirm before Phase 3.

12. ⚠️ **Sender selection encoding in templates** — default: **new column `sender_type` on `u_yf_emailtemplates`** with values `user_account`/`system_smtp`/`any`. Confirm before Phase 1 schema work.

13. ⚠️ **Backfill on account creation** — default: **none** (`last_uid` = current MAX UID at account add); an optional "Import from date" action can be added later if needed. Confirm before Phase 2.


15. ⚠️ **HelpDesk subject pattern** — default: `[T#NNN]`. Confirm by inspecting one real HelpDesk ticket or the `ticket_no` field format before Phase 2.

### To check against deployment, no decision needed

16. 🔍 **Email field names per target module.** Concrete `{module, fieldName}` map for `ByEmail` rule. Tentative:
   - Kandydaci → `email1`
   - Contacts → `email`
   - Accounts → `email1`
   - Leads → `email`
   - SSalesProcesses → `assigned_contact_email` (placeholder — verify)
   Query `vtiger_field` for each `tabid` and `uitype IN (13, 104)` at the start of Phase 2. The result becomes a config array in `App\Modules\Mail\Models\Binding\ByEmail`.

17. 🔍 **`App\Security\Encryption` key.** Verify it is configured on `test.itconnect.pl`, included in backups, and stable across the rollout (do not rotate during Phase 1).

These questions do **not** block writing the architecture. Phase 1 and 2 can start with the defaults above; #11 only matters at Phase 3.

---

## 14. Implementation reference checklist

Concrete file list an engineer can use as a build order for Phase 1+2+3:

```
composer.json                                       +webklex/php-imap
config/modules/Mail.php                              new
src/Modules/Install/install_schema/Mail.php          new (schema: 7 new tables)
src/Modules/Install/install_schema/data_mail.sql     new (tab+cron+settings+related)
tools/migrate/2026_mail_module.sql                   new (alter u_yf_emailtemplates: add sender_type)
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
src/Modules/Mail/Smtp/Sender.php                     new (Path A: user SMTP)
src/Modules/Mail/Smtp/SystemSender.php               new (Path B: adapter around App\Email\Mailer)
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

---

## Delayed emails (buffer and audit)

Implemented MVP for **delayed, cancellable workflow emails** and a generic **delivery audit log**. Full design: [mvp-email-buffer.md](../mvp-email-buffer.md). Operator runbook: [runbooks/delayed-email-runbook.md](runbooks/delayed-email-runbook.md).

| Component | Table / class |
|-----------|----------------|
| Buffer | `s_yf_delayed_email_queue`, `App\Email\Delayed\Buffer` |
| Promote cron | `DelayedEmailQueueTask` (60 s) |
| Mail queue extension | `s_yf_mail_queue.source_module`, `source_id` |
| Delivery audit | `s_yf_mail_sent_log`, hook in `MailerTask` |
| Audit cleanup | `CleanupMailAuditLogTask` |
| Admin UI | `Settings:DelayedEmails` |

First production caller: relation workflow email tasks (`VTEmailTask` / `VTEmailTemplateTask` on `ON_RELATION_MODIFY`) for recruitment status changes. Feature flags in `config/modules/Mail.php` default to **off** until activation.
