# CR: Per-pill auto / prompt delivery on recruitment transition mail

## Goal

Per `(from_status, to_status, short_name)` matrix row, configure whether email is:

- **`prompt`** — open `IndividualSendMailModal` after kanban transition (recruiter confirms Send).
- **`auto`** — send immediately server-side after successful status change.

Settings UX: template **pills** with clickable mode badge toggling `prompt` ↔ `auto` (default `prompt`).

## Implemented changes

### Schema

- Migration: `migrations/Users/m260622_000001_recruitment_transition_mail_delivery_mode.php`
- Column: `u_yf_recruitment_status_transition_mail.delivery_mode` (`prompt` | `auto`, default `prompt`)

### Backend

- `RecruitmentStatusTransitionMail`: `resolveMailActions()`, `sendAutoTemplates()`; removed `getPrompt()`; `saveMatrix()` / `getMatrixForDisplay()` use `templates[{shortName, deliveryMode}]`
- `ChangeCandidateStatusManuallyAjax`: `buildMailActions()` returns optional `mailPrompt` and `autoSend`
- `RelatedListLeftSideEmail::resolvePrimaryEmailField()` for auto-send recipient
- `SaveAjax::saveTransitionMail()` normalizes new payload shape

### Settings UI

- Pill list + add-template dropdown per matrix cell (`TransitionMailContent.tpl`, `TransitionMail.js`, `TransitionMail.css`)
- Mode badge click toggles delivery mode

### Kanban runtime

- `Detail.js`: toast on `autoSend`, then modal for `mailPrompt`

### Languages

- `languages/*/Settings/Recruitment.json` — pill/mode labels, updated help
- `languages/*/ProjektyRekrutacyjne.json` — auto-send toast JS strings

## JSON response (kanban)

```json
{
  "success": true,
  "mailPrompt": { "candidateId": 123, "projectId": 456, "templateIds": [789] },
  "autoSend": { "sent": 1, "failed": 0, "failedShortNames": [] }
}
```

## Testing

```bash
docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
docker compose exec -T app php tests/recruitment_status_transition_mail_smoke.php
```

Smoke test **clears** `u_yf_recruitment_status_transition_mail` — re-save matrix in Settings after run.

## Rollback

1. Revert code commit
2. `ALTER TABLE u_yf_recruitment_status_transition_mail DROP COLUMN delivery_mode;` (if safe)
3. Restore DB backup if auto mails were sent during bad deploy
