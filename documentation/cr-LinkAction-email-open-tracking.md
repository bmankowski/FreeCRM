# Change Request: LinkAction email open tracking (first open per send)

## Goal

Record whether a specific Kandydaci email send had its tracking logo loaded at least once, using the existing LinkAction signed-token pipeline. Audit log only — no CRM field updates.

## Stance

Extended LinkAction with a new `open` action and www image endpoint. No parallel tracking system, no compat shims.

## Impact summary

### Modified

- `config/modules/LinkAction.php` — `open` action registry
- `src/Modules/LinkAction/Services/LinkActionToken.php` — `buildImageUrl()`
- `src/TextParser/TextParser.php` — variable picker label for `LinkActionImageUrl`
- `external/www-la/` — `.htaccess`, `config.production.php`, `src/Registry.php`
- Language files: `LinkAction.json`, `Settings/LinkAction.json`, `TemplateElements.json` (en_us + pl_pl)
- `documentation/module/LinkAction.md`, `.cursor/rules/link-action.mdc`

### Added

- `src/TextParser/LinkActionImageUrl.php`
- `src/Modules/LinkAction/Services/Handlers/KandydaciOpenHandler.php`
- `external/www-la/img.php`, `src/ImageResponse.php`, `assets/logo.png`
- `migrations/LinkAction/m260608_000001_link_action_open_tracking.php`
- `tests/phpunit/LinkAction/KandydaciOpenHandlerTest.php`

### Deleted

None.

### DB

No schema change. Opens stored in `u_yf_link_action_log` with `action='open'`, `scope='email'`.

## Functional requirements

- `$(custom : LinkActionImageUrl|open|email|emailField)$` → `https://www.itconnect.pl/la/o/{token}/logo.png`
- First valid image load queues one JSONL row; JTI replay prevents duplicates
- Invalid/expired token → logo PNG still served, no queue row
- Import validates email hash; handler does not mutate records
- Template element `kandydaci_open_tracking_logo` seeded (not auto-appended to template 1444661)

## Data migration

No schema migration. Idempotent template element seed via `m260608_000001_link_action_open_tracking.php`.

## Testing

```bash
docker compose exec -T app php vendor/bin/phpunit -c tests/phpunit.xml tests/phpunit/LinkAction/
docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
```

Manual www smoke after Cyberfolks deploy:

1. Generate open URL from Kandydaci template preview
2. `curl -sS -D- -o /tmp/logo.png "https://www.itconnect.pl/la/o/{token}/logo.png"`
3. Repeat curl — queue unchanged (replay)
4. Run `LBL_LINK_ACTION_IMPORT` cron
5. Verify `u_yf_link_action_log` row with `action='open'`

## Rollback

Revert code; remove `img.php`, rewrite rule, `assets/logo.png`, `open` from www config. Optional: `DELETE FROM u_yf_link_action_log WHERE action='open'`.

## Risks

| Risk | Severity |
|------|----------|
| Gmail/Apple proxy ≠ human read | Med |
| Large campaign rate limits | Med — separate `img_rate_limit` (500/min) |
| Partial deploy (CRM before www) | Low — deploy www first |
