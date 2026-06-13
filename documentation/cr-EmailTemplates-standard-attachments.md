# Change Request: Email template standard attachments

## Goal

Administrators manage **standard attachments** on the EmailTemplates **Edit** screen (upload or link Documents). When users compose mail from that template, files are attached automatically.

## Implementation summary

- **Storage:** existing `u_yf_documents_emailtemplates` (`crmid` = Document, `relcrmid` = EmailTemplate) via `getManyToMany` relation 525.
- **Canonical API:** `App\Modules\EmailTemplates\Models\TemplateAttachment` — list, link, unlink, limits, cache bust.
- **Ajax:** `EmailTemplates&action=TemplateAttachment` modes `list`, `link`, `unlink`.
- **Edit UI:** panel in `layouts/basic/modules/EmailTemplates/EditViewBlocks.tpl` + `partials/TemplateAttachments.tpl` + `TemplateAttachments.js`.
- **Send path:** unchanged — `Mail::getTemplete()` → `Outbound::sendFromTemplate()` → `Attachment::resolveForSend()`; compose preview via `ComposeAttachment::getTemplateAttachmentMeta()`.
- **Migration:** `m260613_000001_emailtemplates_standard_attachments` — relation 525 actions `ADD,SELECT`.

## Files added

| Path |
|------|
| `src/Modules/EmailTemplates/Models/TemplateAttachment.php` |
| `src/Modules/EmailTemplates/Actions/TemplateAttachment.php` |
| `layouts/basic/modules/EmailTemplates/EditViewBlocks.tpl` |
| `layouts/basic/modules/EmailTemplates/partials/TemplateAttachments.tpl` |
| `public/layouts/basic/modules/EmailTemplates/resources/TemplateAttachments.js` (+ `.min.js`) |
| `public/layouts/basic/modules/EmailTemplates/resources/TemplateAttachments.css` (+ `.min.css`) |
| `migrations/Users/m260613_000001_emailtemplates_standard_attachments.php` |

## Files modified

| Path | Change |
|------|--------|
| `src/Email/Mail.php` | `getAttachmentsFromTemplete()` delegates to `TemplateAttachment` |
| `src/Modules/Mail/Models/ComposeAttachment.php` | `getTemplateAttachmentMeta()` delegates to `TemplateAttachment` |
| `src/Modules/Documents/Actions/MassAdd.php` | EmailTemplates return uses `TemplateAttachment::link()` |
| `src/Modules/EmailTemplates/Views/Edit.php` | limits assign + JS/CSS registration |
| `public/layouts/basic/modules/EmailTemplates/resources/Edit.js` (+ `.min.js`) | init attachment panel |
| `public/layouts/basic/modules/Base/resources/Vtiger.js` (+ `.min.js`) | `massAddDocuments(url, options)` for Edit linking |
| `languages/en_us/EmailTemplates.json`, `languages/pl_pl/EmailTemplates.json` | panel labels |
| `.cursor/rules/mail-compose.mdc` | admin configuration note |

## Testing checklist

- [ ] Edit template → upload PDF → chip appears
- [ ] Select existing Document → linked
- [ ] Unlink → junction row removed, Document remains
- [ ] New template → panel disabled until saved
- [ ] Compose from record → locked template chips + files on send
- [ ] Detail → Documents tab → Add works

## Rollback

Revert code commit; run migration `safeDown` (restores relation actions to `SELECT` only). Junction data preserved.
