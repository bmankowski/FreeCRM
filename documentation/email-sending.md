# Email Sending in FreeCRM

This document provides a comprehensive overview of all email sending mechanisms implemented in the FreeCRM system.

## Overview

FreeCRM implements a sophisticated email system with multiple sending methods, queue management, template processing, and workflow integration. The system supports various email providers, automated workflows, and both manual and scheduled email sending.

## Core Components

### 1. Mailer Class (`vendor/yetiforce/Mailer.php`)

The central email sending component that wraps PHPMailer functionality:

- **Purpose**: Handles all email sending operations
- **Features**:
  - Multiple SMTP provider support
  - Queue management
  - Template processing
  - Attachment handling
  - Individual vs bulk delivery options
  - Error logging and debugging

#### Key Methods:
- `sendFromTemplate()`: Send emails using predefined templates
- `sendByRowQueue()`: Process emails from the queue
- `addMail()`: Add emails to the sending queue
- `test()`: Test SMTP configuration

### 2. Mail Class (`vendor/yetiforce/Mail.php`)

Manages email templates and SMTP configurations:

- **Purpose**: Template and SMTP server management
- **Features**:
  - SMTP server configuration retrieval
  - Email template management
  - Attachment handling from documents
  - Caching for performance

#### Key Methods:
- `getAll()`: Get all SMTP servers
- `getSmtpById()`: Get specific SMTP configuration
- `getDefaultSmtp()`: Get default SMTP server
- `getTempleteList()`: Get available email templates
- `getTempleteDetail()`: Get template details

### 3. EmailParser Class (`vendor/yetiforce/EmailParser.php`)

Processes email content and variables:

- **Purpose**: Parse email templates with dynamic content
- **Features**:
  - Variable substitution
  - Email opt-out handling
  - Content validation
  - Multi-language support

## Email Sending Methods

### 1. Direct Email Sending

**Location**: `vendor/yetiforce/Mailer.php`

```php
$mailer = new \App\Mailer();
$mailer->loadSmtpByID($smtpId)
       ->subject($subject)
       ->content($content)
       ->to($email, $name)
       ->send();
```

**Use Cases**:
- Immediate email sending
- Programmatic email sending
- Custom email implementations

### 2. Template-Based Sending

**Location**: `vendor/yetiforce/Mailer.php::sendFromTemplate()`

```php
\App\Mailer::sendFromTemplate([
    'template' => $templateId,
    'to' => $recipients,
    'recordModel' => $recordModel,
    'smtp_id' => $smtpId
]);
```

**Use Cases**:
- Automated workflow emails
- Notification emails
- Marketing campaigns

### 3. Queue-Based Sending

**Location**: `vendor/yetiforce/Mailer.php::addMail()`

```php
\App\Mailer::addMail([
    'to' => $recipients,
    'subject' => $subject,
    'content' => $content,
    'smtp_id' => $smtpId,
    'priority' => $priority
]);
```

**Use Cases**:
- Bulk email sending
- Scheduled emails
- High-volume operations

### 4. Workflow Email Tasks

#### VTEmailTemplateTask
**Location**: `modules/com_vtiger_workflow/tasks/VTEmailTemplateTask.php`

- Sends emails using predefined templates
- Supports email opt-out checking
- Integrates with workflow conditions

#### VTSendPdf
**Location**: `modules/com_vtiger_workflow/tasks/VTSendPdf.php`

- Sends emails with PDF attachments
- Generates PDFs from templates
- Combines email and document functionality

#### VTSendNotificationTask
**Location**: `modules/com_vtiger_workflow/tasks/VTSendNotificationTask.php`

- Sends calendar invitations
- Handles iCal attachments
- Manages event notifications

## SMTP Configuration

### Supported Mailer Types

The system supports multiple mailer types:

1. **SMTP** (`smtp`): Standard SMTP protocol
2. **Sendmail** (`sendmail`): Unix sendmail command
3. **Mail** (`mail`): PHP mail() function
4. **Qmail** (`qmail`): Qmail mailer

### SMTP Configuration Fields

- `host`: SMTP server hostname
- `port`: SMTP server port
- `secure`: Security protocol (tls, ssl, etc.)
- `authentication`: Authentication required (boolean)
- `username`: SMTP username
- `password`: SMTP password
- `from_email`: Default sender email
- `from_name`: Default sender name
- `reply_to`: Reply-to address
- `individual_delivery`: Send individual emails vs bulk
- `options`: Additional SMTP options (JSON)

### Configuration Management

**Location**: `modules/Settings/MailSmtp/`

- Create/edit SMTP configurations
- Test SMTP connections
- Set default SMTP server
- Manage multiple SMTP accounts

## Email Queue System

### Queue Statuses

- `0`: Pending Acceptance (requires approval)
- `1`: Waiting to be Sent (ready for processing)
- `2`: Error During Sending (failed)

### Queue Processing

**Location**: `cron/Mailer.php`

The cron job processes the email queue:

```php
$dataReader = (new \App\Db\Query())->from('s_#__mail_queue')
    ->where(['status' => 1])
    ->orderBy(['priority' => SORT_DESC, 'date' => SORT_ASC])
    ->limit(AppConfig::performance('CRON_MAX_NUMBERS_SENDING_MAILS'))
    ->createCommand($db)->query();
```

### Queue Features

- Priority-based processing
- Batch processing limits
- Error handling and retry logic
- Automatic cleanup of sent emails
- Manual queue management

## Email Templates

### Template System

**Location**: `vendor/yetiforce/Mail.php`

- Template storage in database
- Dynamic content processing
- Multi-language support
- Attachment support

### Template Variables

The system supports various template variables:

- `$(record : field_name)$`: Record field values
- `$(organization : name)$`: Organization information
- `$(employee : field_name)$`: Employee information
- `$(general : CurrentDate)$`: System information
- `$(translate : key)$`: Translation keys

### Template Processing

**Location**: `vendor/yetiforce/TextParser.php`

- Variable substitution
- Content parsing
- Multi-language support
- Custom function support

## Email Integration Modules

### 1. OSSMail Module

**Location**: `modules/OSSMail/`

- Email client integration
- IMAP/POP3 support
- Email composition
- Address book integration

### 2. OSSMailView Module

**Location**: `modules/OSSMailView/`

- Email viewing and management
- Email categorization
- Related record binding

### 3. OSSMailScanner Module

**Location**: `modules/OSSMailScanner/`

- Automated email processing
- Email-to-record binding
- Scanner actions and rules

## Email Sending Triggers

### 1. Workflow Triggers

- Record creation/modification
- Scheduled workflows
- Manual triggers
- Related record changes

### 2. System Triggers

- User notifications
- System alerts
- Error notifications
- Status changes

### 3. Manual Triggers

- User-initiated sending
- Bulk operations
- Mass email campaigns

## Configuration Options

### Mail Module Configuration

**Location**: `config/modules/Mail.php`

```php
$CONFIG = [
    'MAILTO_LIMIT' => 2030,  // URL character limit
    'RC_COMPOSE_ADDRESS_MODULES' => ['Accounts', 'Contacts', ...],
    'HELPDESK_NEXT_WAIT_FOR_RESPONSE_STATUS' => 'Answered',
    'HELPDESK_OPENTICKET_STATUS' => 'Open',
    'MAILER_REQUIRED_ACCEPTATION_BEFORE_SENDING' => false,
];
```

### Performance Configuration

- `CRON_MAX_NUMBERS_SENDING_MAILS`: Maximum emails per cron run
- `MAILER_DEBUG`: Enable debug logging
- Queue processing limits

## Error Handling and Logging

### Error Types

- SMTP connection errors
- Authentication failures
- Template processing errors
- Queue processing errors

### Logging

- Debug logging for SMTP operations
- Error logging for failed sends
- Trace logging for successful operations
- Queue status tracking

## Security Features

### Email Opt-out Handling

**Location**: `vendor/yetiforce/EmailParser.php`

- Respects user opt-out preferences
- Module-specific opt-out fields
- Automatic filtering of opted-out users

### Authentication

- SMTP authentication support
- Secure connection options (TLS/SSL)
- Password encryption
- User permission checks

## Best Practices

### 1. SMTP Configuration

- Use dedicated SMTP servers for production
- Configure proper authentication
- Set appropriate timeouts
- Monitor SMTP server health

### 2. Queue Management

- Monitor queue size and processing
- Set appropriate batch limits
- Handle failed emails appropriately
- Regular queue cleanup

### 3. Template Design

- Use clear, professional templates
- Test templates thoroughly
- Handle missing data gracefully
- Optimize for different email clients

### 4. Performance

- Use caching for templates and SMTP configs
- Batch similar operations
- Monitor email sending rates
- Optimize database queries

## Troubleshooting

### Common Issues

1. **SMTP Connection Failures**
   - Check server credentials
   - Verify network connectivity
   - Check firewall settings

2. **Template Processing Errors**
   - Validate template syntax
   - Check variable availability
   - Test with sample data

3. **Queue Processing Issues**
   - Check cron job configuration
   - Monitor queue status
   - Review error logs

4. **Email Delivery Problems**
   - Check recipient addresses
   - Verify SMTP server reputation
   - Review spam filters

### Debug Tools

- SMTP test functionality
- Template preview
- Queue status monitoring
- Error log analysis

## Conclusion

The FreeCRM email system provides a comprehensive solution for all email sending needs, from simple notifications to complex automated workflows. The modular design allows for easy customization and extension while maintaining reliability and performance.

Key strengths:
- Multiple sending methods
- Robust queue management
- Template system with variables
- Workflow integration
- Comprehensive error handling
- Security features

The system is designed to handle both low-volume and high-volume email operations while maintaining deliverability and user experience standards.

---

# Stage 1 plan — native compose without Roundcube

Goal: when a user clicks the envelope on a Lead (or any other detail view), open a **native CRM modal** with a compose form instead of redirecting to Roundcube. Keep Roundcube available as a fallback for full‑mailbox use (folders, drafts, mass reading).

## Why this is feasible cheaply

All heavy lifting already exists in FreeCRM. Stage 1 is mostly a **thin UI layer** over the existing `App\Email\Mailer`, plus a new persistence hook into `OSSMailView` so the sent message becomes a CRM record like any IMAP‑scanned one.

| Need | Already implemented in | Reuse as‑is? |
|------|------------------------|--------------|
| Queue a mail | `App\Email\Mailer::addMail()` (`src/Email/Mailer.php`) | yes |
| Send from template + parsed variables | `App\Email\Mailer::sendFromTemplate()` (`src/Email/Mailer.php`) | yes |
| List SMTP servers / pick "from" | `App\Email\Mail::getAll()`, `getDefaultSmtp()` (`src/Email/Mail.php`) | yes |
| List email templates per module | `App\Email\Mail::getTempleteList($moduleName)` | yes |
| Variable substitution (record fields) | `App\TextParser\TextParser::getInstanceByModel()` | yes |
| Resolve "to" e‑mail from a record | `OSSMailView\Models\Record::findEmail($id, $module)` | yes |
| Build subject `[RECORDNO] Name` | `OSSMail\Models\Module::getComposeParam()` (`src/Modules/OSSMail/Models/Module.php`) | yes |
| Persist a mail in CRM (Sent, with attachments, relations) | `OSSMailView` module, `scanneractions/CreatedEmail` | yes (call from action) |
| Actually transmit the queued mail | `cron/Mailer.php` (`MailerTask` cron) | yes |

What's missing is **only the compose modal itself**, the action that ingests its POST, and the swap of the existing `sendMailBtn` URL/handler.

## Files that change (minimal set)

### New files

| File | Purpose |
|------|---------|
| `src/Modules/OSSMail/Views/ComposeModal.php` | Modal view (`module=OSSMail&view=ComposeModal`); prefills the form using existing `Module::getComposeParam($request)` plus template/SMTP lists |
| `src/Modules/OSSMail/Actions/SaveCompose.php` | POST endpoint (`module=OSSMail&action=SaveCompose`); validates, calls `Mailer::addMail()` or `Mailer::sendFromTemplate()`, then persists to `vtiger_ossmailview` with `verify=1` |
| `layouts/basic/modules/OSSMail/ComposeModal.tpl` | Markup: From (SMTP select), To/Cc/Bcc, Subject, Template select, WYSIWYG body, attachments, hidden `crmModule`/`crmRecord` |
| `public/layouts/basic/modules/OSSMail/resources/ComposeModal.js` | Modal init, attachment upload, submit via `AppConnector`, success toast |
| `config/modules/Mail.php` — add key `USE_NATIVE_COMPOSE` | Feature flag; falls back to Roundcube `view=compose` when `false` |

### Files to modify (small, surgical edits)

| File | Change |
|------|--------|
| `src/Modules/OSSMail/Models/Module.php` | Add `getNativeComposeUrl(...)` (parallel to `getComposeUrl`) emitting `view=ComposeModal`. Keep `getComposeParam` untouched — `ComposeModal` view consumes it directly. |
| `src/Modules/Base/UiTypes/Email.php` | In `getDisplayValue()`, when `USE_NATIVE_COMPOSE` is on, emit `data-url` pointing at `getNativeComposeUrl()` and `data-popup="0"`. No other logic changes — `sendMailBtn` JS handles modal vs popup uniformly. |
| `layouts/basic/modules/Base/widgets/EmailList.tpl` | Same swap of URL builder for the envelope button on EmailList widget. |
| `src/Modules/OSSMailView/Models/DetailView.php` | Reply/ReplyAll/Forward links (lines 25–48) point to native modal with `type=reply|replyAll|forward`; modal pre‑fills quoted body using existing `getExternalUrlForWidget()` body composition extracted into a helper. |
| `public/layouts/basic/modules/Base/resources/Vtiger.js` (`sendMailWindow`) | When URL points at `view=ComposeModal`, open it as Bootstrap modal via `app.showModalWindow(...)` instead of `window.open(...)`. One `if` branch, ~6 lines. |

### Files that stay 100% unchanged

- `src/Email/Mailer.php`, `src/Email/Mail.php` — full reuse.
- `cron/Mailer.php` (`MailerTask`) — picks up `s_#__mail_queue` exactly like today.
- `src/Modules/OSSMailScanner/scanneractions/*` — still binds the sent copy thanks to `verify=1`.
- Roundcube: `src/Modules/OSSMail/Views/compose.php` and `index.php` stay (fallback + full mailbox).

## Data flow

```
[Lead detail / envelope click]
        │ data-url = index.php?module=OSSMail&view=ComposeModal&crmModule=Leads&crmRecord=123&type=new
        ▼
[ComposeModal::process]
   ├─ getComposeParam($request)            ── from src/Modules/OSSMail/Models/Module.php
   │    → to, subject "[LEAD123] Acme", from (SEND_IDENTITY per role)
   ├─ Mail::getAll()                        ── SMTP list
   ├─ Mail::getTempleteList($moduleName)    ── templates for module
   └─ render ComposeModal.tpl in #globalModal
        ▼
[user fills form / picks template / attaches files]
        ▼
[POST → SaveCompose::process]
   ├─ if template chosen:
   │     Mailer::sendFromTemplate([...])    ── parser + queue insert
   │ else:
   │     Mailer::addMail([
   │       'smtp_id'  => ...,
   │       'from'     => {email,name},
   │       'to/cc/bcc'=> [...],
   │       'subject'  => '[LEAD123] ...',
   │       'content'  => $body,
   │       'attachments' => [path => name, 'ids' => [docIds]],
   │     ])
   │
   └─ persist a copy to vtiger_ossmailview (Sent, type=0, verify=1)
       so MailScannerBindTask wires it back to Lead 123
        ▼
[cron MailerTask] → PHPMailer → SMTP server
```

## Subject prefix MUST be preserved

`OSSMailScanner` uses the `[RECORDNUMBER]` prefix in the subject to wire incoming replies back to records (`BindHelpDesk`, `BindSSalesProcesses`, `BindCampaigns`, `BindProject`). `getComposeParam()` already builds it:

```84:89:src/Modules/OSSMail/Models/Module.php
			$recordNumber = $recordModel->getRecordNumber();
			if (!empty($recordNumber)) {
				$return['recordNumber'] = $recordNumber;
				$subject = "[$recordNumber] $subject";
			}
```

Native compose must call the same builder and not let the user delete the prefix accidentally — render it as a read‑only badge before the editable subject input, or re‑inject it server‑side on submit if missing.

## Persisting the Sent copy (key trick)

The cheapest way to keep parity with current Roundcube + scanner behaviour is to write the sent message into `vtiger_ossmailview` exactly the way `scanneractions/CreatedEmail` does for incoming mail, then set `verify = 1`. The existing **`MailScannerBindTask`** cron then auto‑wires the OSSMailView row to the source record on the next tick:

```23:30:src/Modules/OSSMailScanner/Cron/MailScannerBindTask.php
		$result = $db->query("SELECT vtiger_ossmailview.*,roundcube_users.actions FROM vtiger_ossmailview INNER JOIN roundcube_users ON roundcube_users.user_id = vtiger_ossmailview.rc_user WHERE vtiger_ossmailview.verify = 1");
		while ($row = $db->getRow($result)) {
			$scanerModel->bindMail($row);
```

For an immediate (non‑deferred) link, `SaveCompose` can additionally call `OSSMailView_Relation_Model::addRelation($mailId, $crmrecord, $date)` right after insert. Both approaches reuse code, no new binding logic.

Fields to populate on insert (`vtiger_ossmailview`):

| Column | Value |
|--------|-------|
| `type` | `0` (Sent) |
| `ossmailview_sendtype` | `'Sent'` |
| `from_email` | resolved SMTP from |
| `to_email`, `cc_email`, `bcc_email` | submitted addresses |
| `subject`, `content` | submitted, after `TemplateStyles::inlineEmailCss` |
| `uid` | generated `Message-ID` (also passed to PHPMailer via `MessageID`) |
| `mbox` | `'Sent'` (synthetic; non‑IMAP) |
| `rc_user` | user's `roundcube_users.user_id` if exists, else `0` |
| `attachments_exist` | `1` if any |
| `verify` | `1` → MailScannerBind will resolve relations |

## Template flow stays compatible

If the user picks a template in the modal, just delegate to the existing `Mailer::sendFromTemplate()`. It already does variable substitution against the record model, attaches template attachments, and inserts into `s_#__mail_queue`:

```86:121:src/Email/Mailer.php
	public static function sendFromTemplate($params)
	{
		// ... parses template with TextParser::getInstanceByModel($recordModel) ...
		static::addMail(array_intersect_key($params, array_flip(static::$quoteColumn)));
		return true;
	}
```

Native compose only needs to forward `template`, `moduleName`, `recordId`, plus user overrides (`subject`, `content` if edited). No new code path.

## Feature flag and rollout

Add to `config/modules/Mail.php`:

```php
$CONFIG['USE_NATIVE_COMPOSE'] = true; // false → fall back to Roundcube compose
```

Rollout order (safe — each step shippable independently):

1. Land `ComposeModal` view/action/template + `SaveCompose` writing to queue and `vtiger_ossmailview`. **Flag off** by default.
2. Switch UI helpers (`UiTypes/Email`, `EmailList.tpl`, `OSSMailView/DetailView`) to call `getNativeComposeUrl()` when flag is on; otherwise keep `getComposeUrl()`. No behaviour change with the flag off.
3. Internal dogfood: turn flag on for admins; verify queue, `vtiger_ossmailview` row, subject prefix, scanner bind.
4. Flip default to `true`. Roundcube `view=compose` remains reachable from "Moja skrzynka" for power users.

## What this does NOT solve in Stage 1

- **Full mailbox UI** (folders, drafts, search across IMAP). Still Roundcube. That's Stage 3.
- **Live IMAP thread view per record**. `OSSMailView` widget shows scanned mail; the new sent copy will appear there too once `MailScannerBindTask` runs. A "Reply in thread" UX that re‑opens the modal with quoted body already works via the existing `OSSMailView/DetailView` reply links — they just point to the new modal instead of Roundcube.
- **OAuth2 / XOAUTH2 for IMAP & SMTP** (Gmail / O365 enforcement). Independent track; addresses `PHPMailer` OAuth in `App\Email\Mailer::setSmtp()` and replaces `imap_open` in `OSSMailScanner` with `webklex/php-imap` or similar. Should be planned next regardless of compose UI.

## Risks & mitigations

| Risk | Mitigation |
|------|-----------|
| Users delete `[RECORDNO]` prefix in subject → broken thread binding | Render prefix as read‑only chip; re‑inject server‑side if missing |
| Attachments uploaded but mail never sent (queue paused) → orphan files | Reuse existing `Mailer::sendByRowQueue()` cleanup of `cache/` paths (already deletes attachments after send); for failed sends, add nightly cleanup of orphaned `cache/upload/` files older than N days |
| Templates expect record context that's missing in free compose | Always pass `crmModule`/`crmRecord` to `sendFromTemplate`; reject template selection when context is empty (UI hint) |
| Concurrency: `SaveCompose` enqueues but cron `MailerTask` is paused → user thinks mail is sent | Show queue status badge in success toast: "Queued — will be sent within X minutes" (read `MAILER_REQUIRED_ACCEPTATION_BEFORE_SENDING`) |
| Permission bypass | `SaveCompose::checkPermission` mirrors `SendMailModal::checkPermission` (module perm + record perm via `Privilege::isPermitted($sourceModule, 'DetailView', $sourceRecord)`) |
| Roundcube identity vs SMTP from name | Use `s_#__mail_smtp.from_email/from_name` (already in `Mailer::setSmtp()`); per‑user override picked via `SEND_IDENTITY[roleid]` in module config — same as today |

## Effort estimate

- `ComposeModal` view + tpl + JS: ~1 day.
- `SaveCompose` action incl. OSSMailView persistence: ~1 day.
- Wiring in `UiTypes/Email`, `EmailList.tpl`, `OSSMailView/DetailView`, `Vtiger.js`: ~0.5 day.
- Template + attachment integration, feature flag, end‑to‑end tests via Roundcube fallback: ~1 day.

Total: **~3–4 dev‑days** to ship Stage 1 behind a flag, before any work on Stage 2 (native thread view) or removing Roundcube.
