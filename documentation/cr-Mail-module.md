# Change Request: Mail module — greenfield replacement of OSS mail stack

## Goal

Replace the legacy Roundcube-based **OSSMail / OSSMailScanner / OSSMailView** stack with a single modern **`Mail`** module that:

1. Sends outbound mail through **per-user personal accounts** and **admin-managed shared accounts** (IMAP+SMTP credentials stored encrypted in FreeCRM).
2. Polls configured mailboxes via cron, stores messages, and **auto-binds** them to CRM records.
3. Exposes an **“E-maile” related list** on target modules and a **compose modal** from record detail / email fields.
4. Lets **each user manage their own mailbox credentials** (host, username, password) from **My Preferences**, while admins manage shared accounts in Settings.
5. Keeps the existing **system SMTP path** (`s_yf_mail_smtp` + `App\Email\Mailer`) for template-driven sends — chosen by `sender_type` on email templates, not as an OSS fallback.
6. **Deletes** the OSS mail modules and all references in the same CR — no coexistence, no compat shims.

**Design reference:** `documentation/freecrm-mail-system-architecture.md` (2026-05-21), amended by this CR for credential UX and ownership rules.

---

## Stance

- **No fallbacks.** No `if (Mail) … else OSSMail`. No `class_alias()`. No parallel related lists.
- **No Roundcube.** No `roundcube_*` tables after deploy.
- **No data migration from OSS.** Dev/prod have **0** `roundcube_users` rows and **0** `vtiger_ossmailview` records (verified 2026-06-03). Fresh `u_yf_mail_*` tables; users re-enter mailbox credentials.
- **Dual outbound paths by design** (not legacy fallback): `user_account` → Mail module SMTP; `system_smtp` → existing `App\Email\Mailer`. Template `sender_type` selects the path explicitly.
- OSS mail code is **deleted in the final step** of this CR, not deferred.

---

## Decisions (confirmed with project owner)

| # | Decision |
|---|----------|
| D1 | **Personal + shared** mailboxes in v1 |
| D2 | **Dual outbound path**: user/shared accounts + existing system SMTP (`sender_type` on templates) |
| D3 | Auto-bind modules: **Kandydaci, Leads, Accounts, Contacts, HelpDesk, SSalesProcesses** |
| D4 | Non-admin credential UI: **My Preferences** (`Users` → `PreferenceEdit`), not a separate Settings screen |
| D5 | Admin credential UI: **Settings → Mail Accounts** for all accounts (personal + shared) |

---

## Assumptions

| # | Assumption |
|---|------------|
| A1 | Mail server is own infrastructure (`mail.itconnect.pl` or equivalent); login+password auth only (no OAuth2 in v1). |
| A2 | `App\Security\Encryption` is active (`a_yf_encryption.method = aes-256-cbc` on dev). |
| A3 | Scale: ≤10 mailboxes, ≤500 messages/day, cron poll every 60–120 s. |
| A4 | No webmail UI (folders browser, drafts, threads) — CRM integration only. |
| A5 | `composer require webklex/php-imap:^5.0` is acceptable as the only new dependency. |
| A6 | Each CRM user has **at most one personal mail account** (`UNIQUE (owner_user_id)` where `kind='personal'`). |
| A7 | Shared accounts are **never** editable from My Preferences; only from Settings by admin. |
| A8 | Inbound scan applies to Mail module accounts only; system SMTP mailboxes are send-only unless also registered as a shared Mail account. |

---

## Credential storage and access model (CR amendment)

This section **replaces** §6.8 edit rules and Phase 1 acceptance criteria in `freecrm-mail-system-architecture.md` regarding who may edit credentials.

### Storage

| Item | Rule |
|------|------|
| Column | `u_yf_mail_accounts.password_enc` — `VARBINARY(512)` or `TEXT` |
| Algorithm | `App\Security\Encryption::encrypt()` / `decrypt()` (same as other encrypted CRM secrets) |
| Plaintext | Never persisted in DB, logs, `u_yf_mail_log.context_json`, or HTTP responses |
| CRM login password | Unrelated — `App\Security\PasswordCrypto` (Argon2id) on `vtiger_users` stays separate |

### Write path

1. **Create personal account (My Preferences):** user submits IMAP/SMTP settings + password → `Models\Account::savePersonalForUser($userId, $data)` encrypts password → INSERT `kind='personal'`, `owner_user_id=$userId` → INSERT matching row in `u_yf_mail_account_users` with `can_send=1`.
2. **Update password:** empty password field in form = keep existing `password_enc`; non-empty = re-encrypt and UPDATE.
3. **Create/update shared account (Settings):** admin only; same encryption path.
4. **Use path (send/scan):** decrypt in memory only inside `Imap\Client`, `Smtp\Sender`, `Actions\TestConnection`; zero out variable after use where practical.

### Display rules

- Forms always show password as **`**********`** when a credential exists.
- API/Ajax responses never include `password_enc` or decrypted password.
- ModTracker: mail account entity is **excluded** from change history for `password_enc` (same pattern as OSSPasswords secure handler — mask or skip field).

### Permission matrix (revised)

| Subject | Personal account (`kind='personal'`, owner = self) | Shared account |
|---------|-----------------------------------------------------|----------------|
| **Owner user (My Preferences)** | CRUD on: `imap_*`, `smtp_*`, `username`, `password_enc`, `from_name`, `signature_html`, `reply_to_mode`, `reply_to_address`, `append_sent`, `imap_folder_sent` (after TestConnection). Cannot set `active=0` (admin can disable). | Read-only visibility in compose dropdown if listed in `u_yf_mail_account_users`; **no credential access** |
| **Admin (Settings:MailAccount)** | Full CRUD on all fields including `active`, disable/enable scan | Full CRUD + assign users in `u_yf_mail_account_users` |
| **Other users** | No access | Send-only if `can_send=1` in ACL table; no credentials |

### My Preferences UI (D4)

Add a **“Mailbox”** panel to `Users/PreferenceEdit` (and `Settings/Users/Edit` when admin edits another user’s personal mailbox):

- Rendered via `{include file='modules/Mail/PreferenceMailbox.tpl'}` included from `layouts/basic/modules/Users/PreferenceEdit.tpl` (or a small PHP hook in `PreferenceEdit::process()` assigning `$MAIL_ACCOUNT` from `Account::getPersonalForUser($recordId)`).
- Fields: account display name, IMAP host/port/secure, SMTP host/port/secure, username, password (optional on edit), from name, signature (HTML), append-to-sent checkbox.
- Buttons: **Test connection** (Ajax → `Mail&action=TestConnection`), **Save** (Ajax → `Mail&action=SavePersonalAccount`).
- First-time UX: empty state with short explanation + link text “Configure your mailbox to send and receive mail in CRM”.
- **Activation gate:** account remains `active=0` until TestConnection succeeds at least once; scanner skips inactive accounts.

Admin **Settings → Mail Accounts** remains the place to manage shared mailboxes and to view/disable any user’s personal account operationally.

---

## Impact

### Code being created

| Path | Purpose | Observable? |
|------|---------|-------------|
| `src/Modules/Mail/` | Core module: scan, send, bind, related list, compose, detail | Yes |
| `src/Modules/Settings/MailAccount/` | Admin account list/edit/logs | Yes (admin) |
| `layouts/basic/modules/Mail/` | Compose, detail, related list templates | Yes |
| `layouts/basic/modules/Settings/MailAccount/` | Admin UI | Yes (admin) |
| `layouts/basic/modules/Mail/PreferenceMailbox.tpl` | My Preferences mailbox block | Yes |
| `public/layouts/basic/modules/Mail/resources/` | Compose, related list JS | Yes |
| `config/modules/Mail.php` | Tunables (scan interval, rate limits) | Internal |
| `tools/migrate/2026_mail_module.sql` | One-off migration for existing installs | Deploy |
| `src/Modules/Install/install_schema/` | New tables + cron rows for fresh installs | Internal |

### Code being modified

| Path | Change | Observable? |
|------|--------|-------------|
| `src/Modules/Base/UiTypes/Email.php` | Open `Mail&view=Compose` instead of OSSMail compose URL; drop `internal_mailer` + OSSMail permission gate | Yes |
| `src/Modules/Kandydaci/Models/RelatedListLeftSideEmail.php` | Gate on Mail module + user account availability instead of `getDefaultSmtp()` only | Yes |
| `src/Modules/Contacts/Models/RelatedListLeftSideMail.php` | Same | Yes |
| `src/Modules/Base/Views/RelatedList.php` | Register Mail related list handler (if generic hook needed) | Yes |
| `src/Modules/Base/Views/Detail.php` | Remove OSSMailView widget hooks if present | Yes |
| `src/Modules/Base/Widgets/EmailList.php`, `HistoryRelation.php` | Point to Mail or remove OSSMailView dependency | Yes |
| `src/Modules/Base/Dashboards/MailsList.php` | Repoint or remove | Yes |
| `src/Modules/Notification/Actions/Notification.php` | Replace OSSMail permission check with Mail | Internal |
| `src/Runtime/CRM_Viewer.php` | Remove OSSMail Smarty class registrations | Internal |
| `src/Modules/Users/Views/PreferenceEdit.php` | Assign mailbox model; include PreferenceMailbox block | Yes |
| `layouts/basic/modules/Users/PreferenceEdit.tpl` | Include mailbox panel | Yes |
| `public/layouts/basic/modules/Users/resources/PreferenceEdit.js` | TestConnection + SavePersonalAccount handlers | Yes |
| `u_yf_emailtemplates` | Add `sender_type`, `skip_account_signature` columns | Admin templates |
| `composer.json` | Add `webklex/php-imap` | Deploy |
| `user_privileges/moduleHierarchy.php` | Remove OSSMailView relation; add Mail relation config if needed | Internal |
| `src/Modules/Install/install_schema/data.sql` | Mail module tab, cron, related lists, settings field | Fresh install |

### Code being deleted (mandatory final steps)

**Modules (entire directories):**

- `src/Modules/OSSMail/` (+ embedded `roundcube/`)
- `src/Modules/OSSMailScanner/` (+ `scanneractions/`)
- `src/Modules/OSSMailView/`
- `src/Modules/Settings/OSSMail/`
- `src/Modules/Settings/OSSMailScanner/`
- `src/Modules/Settings/OSSMailView/`

**Layouts / public assets:**

- `layouts/basic/modules/OSSMail/`, `OSSMailView/`, `Settings/OSSMail*/`
- `public/layouts/basic/modules/OSSMail/`, `OSSMailView/`, `Settings/OSSMailScanner/`

**Config:**

- `config/modules/OSSMail.php`

**Languages (delete module files):**

- `languages/{en_us,pl_pl}/OSSMail.json`, `OSSMailScanner.json`, `OSSMailView.json`
- `languages/{en_us,pl_pl}/Settings/OSSMail.json`, `Settings/OSSMailScanner.json`, `Settings/OSSMailView.json`

**Install schema references:** remove OSS mail table definitions from `Base2.php`, `Base3.php`, `Base4.php`, `data.sql` as applicable.

**Cron handlers:** remove `OSSMailScanner` cron task rows and handler classes.

### Database

| Item | Change | Observable? |
|------|--------|-------------|
| **New tables** | `u_yf_mail_accounts`, `u_yf_mail_account_users`, `u_yf_mail_messages`, `u_yf_mail_attachments`, `u_yf_mail_record_links`, `u_yf_mail_log` | Internal |
| `u_yf_emailtemplates` | ADD `sender_type ENUM('user_account','system_smtp','any') DEFAULT 'system_smtp'`, ADD `skip_account_signature TINYINT(1) DEFAULT 0` | Template admin |
| `vtiger_tab` | INSERT `Mail` module; SET `presence=1` on OSSMail, OSSMailScanner, OSSMailView | Module manager |
| `vtiger_cron_task` | INSERT Mail scanner + log prune; DELETE OSSMailScanner cron rows | Cron admin |
| `vtiger_relatedlists` | INSERT Mail related list on 6 modules; DELETE OSSMailView related lists | UI |
| `vtiger_settings_field` | INSERT Mail Accounts; DELETE OSS Mail / Scanner / View settings links | Settings menu |
| **Drop (after code deploy)** | `roundcube_*`, `vtiger_ossmail*`, `vtiger_ossmailview*` tables | Internal |

### Module metadata

- New **`Mail`** utility module (`isentitytype=0`) — messages are not CRMEntity records.
- New **Settings:MailAccount** pseudo-module.
- Related list label: **“E-maile”** / **“Emails”** on Kandydaci, Leads, Accounts, Contacts, HelpDesk, SSalesProcesses.
- Profiles: grant Mail actions to roles that send mail; Settings:MailAccount admin-only.

### Language files (new)

- `languages/en_us/Mail.json`, `languages/pl_pl/Mail.json`
- `languages/en_us/Settings/MailAccount.json`, `languages/pl_pl/Settings/MailAccount.json`
- Add strings to `languages/*/Users.json` for My Preferences mailbox block

### Cron / background

| Task | Handler | Frequency |
|------|---------|-----------|
| `LBL_MAIL_SCAN` | `App\Modules\Mail\Cron\Scanner` | 60 s |
| `LBL_MAIL_LOG_PRUNE` | `App\Modules\Mail\Cron\LogPrune` | 86400 s |

Remove: OSSMailScanner cron tasks (`MailScannerActionTask`, `MailScannerBindTask`, `MailScannerVerificationTask`, legacy `OSSMailScanner/Actions/cron.php` if registered).

### Webservices / REST

- No new public REST endpoints in v1.
- Internal only: `Mail\Models\Service` for related lists and compose.

### Caches

- Clear `cache/templates_c/*.php` after Smarty changes.
- No module cache invalidation beyond standard deploy.

### External consumers

- IMAP/SMTP to customer mail server — new integration (replacing Roundcube’s connection).
- No change to website / import pipelines.

### Known call sites to update (grep verification)

Run before merge:

```bash
rg -l 'OSSMail|OSSMailScanner|OSSMailView' --glob '!documentation/**' --glob '!documentation/cr-*'
```

Must return **zero** hits outside git history after step 6. High-priority files today:

- `src/Modules/Base/UiTypes/Email.php`
- `src/Runtime/CRM_Viewer.php`
- `src/Modules/Base/Views/RelatedList.php`
- `src/Modules/Kandydaci/Models/RelatedListLeftSideEmail.php`
- `src/Modules/Contacts/Models/RelatedListLeftSideMail.php`
- `src/Modules/Notification/Actions/Notification.php`
- `src/Modules/Calendar/Views/Reminders.php`, `ActivityStateModal.php`
- `src/Modules/Documents/Models/DetailView.php`, `Relation.php`
- `src/Modules/ModTracker/Models/Record.php`
- `src/Modules/Workflow/Tasks/VTAddressBookTask.php`
- `layouts/basic/modules/Base/HeaderMailIcon.tpl`
- `layouts/basic/modules/Base/widgets/EmailList.tpl`
- `public/layouts/basic/modules/Base/resources/Vtiger.js` (compose modal wiring)

---

## Functional requirements

### In scope

| ID | Requirement | Before → After |
|----|-------------|----------------|
| F1 | Personal mailbox setup | None / Roundcube admin → User configures IMAP+SMTP+password in **My Preferences**; encrypted at rest |
| F2 | Shared mailbox setup | N/A → Admin creates in **Settings → Mail Accounts**, assigns users with `can_send` |
| F3 | Inbound scan | OSSMailScanner cron (unused) → Mail cron polls active accounts, stores in `u_yf_mail_messages` |
| F4 | Auto-bind | OSS scanner plugins → `Binding\Engine` + fixed rules for 6 modules |
| F5 | Kandydaci bind fields | — | Match on **all email fields**: `email_prywatny`, `email_firmowy`, `newsletter_email`, `referred_by_email` |
| F6 | HelpDesk bind | — | Email match + subject pattern `[T#NNN]` (configurable) |
| F7 | Related list | OSSMailView tab → Mail “E-maile” tab with visibility rules (§6.8 architecture doc) |
| F8 | Compose from record | OSSMail / mailto / old modal → `Mail&view=Compose` with template picker |
| F9 | Outbound path A | — | Template `sender_type=user_account` → user's chosen Mail account SMTP + optional IMAP APPEND Sent |
| F10 | Outbound path B | Existing system SMTP | Template `sender_type=system_smtp` → `App\Email\Mailer` + row in `u_yf_mail_messages` with `smtp_id` set |
| F11 | Outbound path C | — | Template `sender_type=any` → user picks account or system SMTP in modal |
| F12 | No mail account | — | If template requires `user_account` and user has none: Send disabled + banner linking to My Preferences |
| F13 | Test connection | — | Validates IMAP login + SMTP login; returns folder list for Sent folder picker |
| F14 | OSS removal | 3 legacy modules present → Deleted; no code references remain |

### Out of scope (future CR)

| Item | Reason |
|------|--------|
| OAuth2 / XOAUTH2 | Explicit non-goal; schema allows future `auth_type` column |
| Webmail UI | Not needed |
| Historical import / backfill from IMAP | `last_uid` starts at current MAX UID; optional “import from date” later |
| OAuth notification on shared mailbox | — |
| Replacing `s_yf_mail_queue` mass-mail infrastructure | Still used for `system_smtp` bulk; Mail module delegates to it |

### Business rules

1. Scanner processes only accounts with `active=1` and `last_scan_status != 'disabled'`.
2. Auto-disable account after **5** consecutive scan failures (configurable).
3. Deduplicate inbound by `(account_id, imap_uid)` and `message_id`.
4. Personal inbound/outbound visibility: only owner (+ admin) per architecture §6.8.
5. Shared inbound/outbound visibility: any user with DetailView on linked record.
6. User cannot delete a personal account if it is the only send path and pending outbound — soft rule: allow delete with confirmation.
7. TLS required for production (`imap_secure` / `smtp_secure` cannot be `none` unless admin override flag on account).

### Validation

- Username must be valid email format.
- Ports: 1–65535.
- Password min length 1 on create; optional on update.
- TestConnection required before first activation of personal account.

---

## Data migration

### Schema creation (idempotent migration script)

File: `tools/migrate/2026_mail_module.sql`

```sql
-- 1. u_yf_mail_accounts
CREATE TABLE IF NOT EXISTS u_yf_mail_accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  kind ENUM('personal','shared') NOT NULL,
  owner_user_id INT NULL,
  imap_host VARCHAR(190) NOT NULL,
  imap_port SMALLINT UNSIGNED NOT NULL DEFAULT 993,
  imap_secure ENUM('ssl','tls','none') NOT NULL DEFAULT 'ssl',
  imap_validate_cert TINYINT(1) NOT NULL DEFAULT 1,
  imap_folder_inbox VARCHAR(190) NOT NULL DEFAULT 'INBOX',
  imap_folder_sent VARCHAR(190) NULL,
  smtp_host VARCHAR(190) NOT NULL,
  smtp_port SMALLINT UNSIGNED NOT NULL DEFAULT 465,
  smtp_secure ENUM('ssl','tls','none') NOT NULL DEFAULT 'ssl',
  username VARCHAR(190) NOT NULL,
  password_enc TEXT NULL,
  from_name VARCHAR(120) NULL,
  reply_to_mode ENUM('same_as_from','user_personal','custom') NOT NULL DEFAULT 'same_as_from',
  reply_to_address VARCHAR(190) NULL,
  signature_html MEDIUMTEXT NULL,
  append_sent TINYINT(1) NOT NULL DEFAULT 1,
  last_uid INT UNSIGNED NOT NULL DEFAULT 0,
  last_scan_at DATETIME NULL,
  last_scan_status ENUM('ok','error','disabled') NULL,
  last_scan_error TEXT NULL,
  consecutive_failures SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  next_scan_at DATETIME NULL,
  scan_interval_sec SMALLINT UNSIGNED NOT NULL DEFAULT 120,
  active TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_active_scan (active, next_scan_at),
  KEY idx_owner (owner_user_id),
  KEY idx_kind (kind),
  UNIQUE KEY uk_owner_user_id (owner_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note: shared accounts keep owner_user_id NULL (multiple allowed); personal accounts set owner_user_id → at most one personal account per CRM user.

-- 2. u_yf_mail_account_users
CREATE TABLE IF NOT EXISTS u_yf_mail_account_users (
  account_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  can_send TINYINT(1) NOT NULL DEFAULT 1,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (account_id, user_id),
  CONSTRAINT fk_mail_acct_users_account FOREIGN KEY (account_id) REFERENCES u_yf_mail_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3–6. u_yf_mail_messages, u_yf_mail_attachments, u_yf_mail_record_links, u_yf_mail_log
-- (full DDL per architecture doc §6.3–6.6 — include in migration file)

-- 7. Email templates
ALTER TABLE u_yf_emailtemplates
  ADD COLUMN IF NOT EXISTS sender_type ENUM('user_account','system_smtp','any') NOT NULL DEFAULT 'system_smtp',
  ADD COLUMN IF NOT EXISTS skip_account_signature TINYINT(1) NOT NULL DEFAULT 0;

-- 8. Module + cron + settings + related lists: use PHP runner tools/migrate/2026_mail_module.php
--    (insert vtiger_tab, vtiger_cron_task, vtiger_settings_field, vtiger_relatedlists — idempotent)
```

### Data migration from OSS

**None.** Users re-enter credentials in My Preferences. No `roundcube_users` → `u_yf_mail_accounts` copy.

### OSS table drops (deploy step 6 — after code live)

```sql
-- Disable legacy modules
UPDATE vtiger_tab SET presence = 1 WHERE name IN ('OSSMail','OSSMailScanner','OSSMailView');

-- Drop legacy tables (order respects FKs if any)
DROP TABLE IF EXISTS vtiger_ossmailview_relation;
DROP TABLE IF EXISTS vtiger_ossmailviewcf;
DROP TABLE IF EXISTS vtiger_ossmailview;
DROP TABLE IF EXISTS vtiger_ossmailscanner_folders_uid;
DROP TABLE IF EXISTS vtiger_ossmailscanner_config;
DROP TABLE IF EXISTS vtiger_ossmailscanner_actions;
DROP TABLE IF EXISTS vtiger_ossmails_logs;
DROP TABLE IF EXISTS roundcube_users_autologin;
DROP TABLE IF EXISTS roundcube_users;
-- Additional roundcube_* / vtiger_ossmail* tables: enumerate from install_schema before DROP
```

### Rollback SQL

- **Before OSS drop:** revert code + `DROP TABLE u_yf_mail_*` + remove `vtiger_tab` Mail row + restore OSS modules from git.
- **After OSS drop:** **restore from backup** — forward-only destructive step.

---

## Implementation plan

Each phase is committable; system stays runnable. Estimated **18–26 dev-days** total (AI-assisted implementation may compress calendar time; QA bandwidth unchanged).

### Phase 1 — Foundation + credentials (5–7 days)

1. `composer require webklex/php-imap:^5.0`
2. Add migration SQL + PHP runner; mirror in `install_schema/`
3. `src/Modules/Mail/Mail.php` — `vtlib_handler` (postinstall: settings field, cron rows)
4. `src/Modules/Mail/Models/Account.php` — CRUD, encrypt/decrypt, `getPersonalForUser`, `savePersonalForUser`, ACL checks
5. `src/Modules/Mail/Models/Acl.php` — central permission gate
6. `src/Modules/Mail/Actions/TestConnection.php`
7. `src/Modules/Mail/Actions/SavePersonalAccount.php` — My Preferences save endpoint
8. `src/Modules/Settings/MailAccount/` — admin List, Edit, SaveAjax, TestConnection, DeleteAjax (shared + override personal)
9. `layouts/basic/modules/Mail/PreferenceMailbox.tpl` + Users PreferenceEdit integration
10. `public/layouts/basic/modules/Users/resources/PreferenceEdit.js` — ajax handlers, minify
11. Languages: `Mail.json`, `Settings/MailAccount.json`, `Users.json` strings (en_us + pl_pl)
12. `config/modules/Mail.php`

**Acceptance:** User opens My Preferences → configures personal mailbox → Test Connection green → Save → row in `u_yf_mail_accounts` with non-null `password_enc`, `active=1`. Admin creates shared account in Settings, assigns users.

### Phase 2 — Inbound (5–7 days)

1. `src/Modules/Mail/Imap/Client.php`, `Fetcher.php`
2. `src/Modules/Mail/Models/Message.php`, `Attachment.php`
3. `src/Modules/Mail/Models/Binding/Engine.php`, `ByEmail.php`, `HelpDeskSubject.php`
4. Configure bind targets:

   | Module | Email fields |
   |--------|--------------|
   | Kandydaci | `email_prywatny`, `email_firmowy`, `newsletter_email`, `referred_by_email` |
   | Contacts | `email`, `secondaryemail` (verify via `vtiger_field`) |
   | Accounts | `email1`, `email2` |
   | Leads | `email`, `secondaryemail` |
   | HelpDesk | contact email fields + subject rule |
   | SSalesProcesses | verify field names via DB |

5. `src/Modules/Mail/Cron/Scanner.php` + `vtiger_cron_task` registration
6. `src/Modules/Mail/Views/Detail.php`, `Actions/Link.php`, `Unlink.php`, `DownloadAttachment.php`
7. Related list registration + `Views/RelatedList.php` + Smarty template
8. Register related list on 6 modules in DB

**Acceptance:** External email to user's inbox → within 2 min appears on matching Kandydat/Leads/Account related list.

### Phase 3 — Outbound dual path (5–7 days)

1. `src/Modules/Mail/Smtp/Sender.php`, `Imap/Appender.php`
2. `src/Modules/Mail/Views/Compose.php`, `Actions/Send.php`, `UploadAttachment.php`
3. System SMTP adapter writing to `u_yf_mail_messages`
4. Update `src/Modules/Base/UiTypes/Email.php`
5. Update `RelatedListLeftSideEmail.php`, `IndividualSendMailModal` / `Vtiger.js` compose flow
6. Template admin: expose `sender_type` on email template edit UI
7. Default account toggle (`u_yf_mail_account_users.is_default`)

**Acceptance:** Compose from Kandydat with `user_account` template sends via personal SMTP; with `system_smtp` sends via rekrutacja@ path; related list updates.

### Phase 4 — Operations polish (3–5 days)

1. `Settings/MailAccount/Views/Logs.php`, health badges on account list
2. `Cron/LogPrune.php`
3. Send rate limit (60/min/user)
4. Update `documentation/email-sending.md` to reference Mail module

### Phase 5 — OSS deletion (2–3 days) **required**

1. Remove all OSS mail code paths listed in Impact
2. Run OSS table DROP migration
3. `UPDATE vtiger_tab SET presence = 1` for OSS modules (or uninstall via ModuleManager)
4. Remove OSS entries from `install_schema/`, menus, `moduleHierarchy.php`
5. Grep verification: zero OSS mail references
6. Remove Roundcube library dependency from ModuleManager if no other consumer

**Acceptance:** `rg OSSMail` returns no production code; CRM boots; cron runs Mail scanner only.

---

## Testing

### Manual smoke tests

| # | Test |
|---|------|
| T1 | My Preferences: create personal account, test, save, reload — password masked |
| T2 | My Preferences: update password only — old password replaced |
| T3 | My Preferences: empty password on save — old password retained |
| T4 | Admin: create shared account, assign User A `can_send=1` |
| T5 | User A sends from Kandydat via personal account — received externally |
| T6 | User A sends via `system_smtp` template — From shows system address |
| T7 | Inbound mail from candidate email — binds to Kandydaci |
| T8 | Related list visibility: personal mail not visible to other users |
| T9 | Shared mailbox inbound visible to all with record access |
| T10 | Manual link/unlink on message detail |
| T11 | Account auto-disable after 5 failed scans (simulate bad password) |
| T12 | User without mailbox + `user_account` template — Send blocked, banner shown |

### Regression areas

- Existing template-based bulk send (`IndividualSendMailModal`) with `system_smtp` templates
- Workflow emails, forgot-password, notification emails (must still use Mailer)
- Kandydaci related list email action
- User My Preferences save (non-mail fields unchanged)

### Automated tests (recommended)

- `tests/Unit/Mail/AccountEncryptionTest.php` — round-trip encrypt/decrypt, empty password update
- `tests/Unit/Mail/BindingByEmailTest.php` — Kandydaci multi-field match
- `tests/Unit/Mail/AclTest.php` — personal vs shared permission matrix

### Logs

- `cache/logs/system.log` — PHP errors
- `cache/logs/cron.log` — scanner runs
- `cache/logs/mail.log` — structured mail ops
- `u_yf_mail_log` — admin viewer

### Integrity checks post-deploy

```sql
SELECT COUNT(*) FROM u_yf_mail_accounts WHERE kind='personal' AND password_enc IS NULL AND active=1;
-- expect 0

SELECT COUNT(*) FROM roundcube_users;
-- expect 0 after phase 5
```

### Grep gate (must pass before merge)

```bash
rg 'OSSMail|OSSMailScanner|OSSMailView|roundcube_users' src/ layouts/ public/ languages/ config/ --glob '!documentation/**'
```

---

## Rollback plan

| Stage | Code | Data |
|-------|------|------|
| After Phase 1–4, before Phase 5 | Revert git deploy | `DROP TABLE u_yf_mail_*`; OSS code still in repo if not deleted yet |
| After Phase 5 (OSS dropped) | Revert git **insufficient** | **Restore MariaDB + storage backup from before deploy**; redeploy previous tag |

Acceptable rollback data loss after Phase 5: all mail stored in `u_yf_mail_*` since deploy. Acceptable downtime: maintenance window (~30 min).

---

## Decision rationale & tradeoffs

| Alternative | Why rejected |
|-------------|--------------|
| **Modernize OSS modules** | Roundcube coupling, admin-centric creds, 3 modules, plugin loader, fights “no fallbacks” rule |
| **Keep OSS during transition** | Parallel related lists, dual compose URLs, grep debt — explicitly forbidden |
| **Credentials only in Settings** | Owner rejected; users must self-serve from My Preferences |
| **User accounts only (no system SMTP)** | Owner chose dual path — marketing/rekrutacja templates keep working without per-user creds |
| **OSSPasswords-style AES in module config** | Use centralized `App\Security\Encryption` instead — one key management story |

**Why My Preferences + Settings split:** credentials are user-owned data; operational control (shared mailboxes, disable misbehaving accounts) stays admin-only in Settings.

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Users must re-enter all mailbox passwords | Med | Clear comms; My Preferences UX; Test Connection feedback |
| Missed OSS reference causes runtime fatal | High | Phase 5 grep gate; CI ripgrep job |
| IMAP library edge cases (MIME, encoding) | Med | `webklex/php-imap`; store both HTML and text; HTMLPurifier on display |
| Encryption inactive on some env | Med | Install check in `Mail.php` postinstall; block save if `!Encryption::isActive()` |
| Dual outbound path confusion | Low | Template `sender_type` explicit; UI labels show which path |
| Large OSS deletion PR | Med | Phase 5 as dedicated commit; deploy after Phases 1–4 validated |
| Kandydaci 4 email fields → duplicate binds | Low | Unique link key `(message_id, crm_module, crm_record_id)` — one link per record |

---

## Binding configuration reference

```php
// src/Modules/Mail/Models/Binding/Engine.php — static registry (no plugin loader)
private static array $emailRules = [
    ['module' => 'Kandydaci', 'fields' => ['email_prywatny', 'email_firmowy', 'newsletter_email', 'referred_by_email']],
    ['module' => 'Leads', 'fields' => ['email', 'secondaryemail']],
    ['module' => 'Accounts', 'fields' => ['email1', 'email2']],
    ['module' => 'Contacts', 'fields' => ['email', 'secondaryemail']],
    // SSalesProcesses, HelpDesk: confirm fields at Phase 2 start via MCP describe
];
```

---

## Open questions (none blocking — resolved)

All four design questions were answered 2026-06-03 (see **Decisions** table).

---

## Related documents

- `documentation/freecrm-mail-system-architecture.md` — detailed flows, API list, security (amend credential edit rules per this CR)
- `documentation/email-sending.md` — update after Phase 4
- `.cursor/rules/cr-create.mdc` — template used for this CR
