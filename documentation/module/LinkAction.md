# LinkAction module

Signed public link actions (`https://itconnect.pl/la`) with CRM-side queue import.

## Overview

1. CRM signs ECDSA P-256 tokens when rendering `$(custom : LinkActionUrl|action|scope|emailField)$` or `$(custom : LinkActionImageUrl|open|email|emailField)$`.
2. User clicks link on www (`la.php`) or mail client loads tracking image (`img.php`); endpoint verifies signature and appends a JSON Lines row (first hit only per `jti`).
3. CRM cron `LBL_LINK_ACTION_IMPORT` fetches the queue via HTTPS API, re-verifies every line, applies handlers, then acks (truncates remote queue).

Private keys stay on CRM only. www holds public key PEM files.

## Token format

Payload JSON fields: `v`, `kid`, `module`, `record_id`, `action`, `scope`, `email_field`, `eh`, `iat`, `exp`, `jti`.

Wire token:

```
t = <kid>.<base64url(json)>.<base64url(ecdsa_der_sig)>
Click URL = https://www.itconnect.pl/la?t=<urlencoded_t>
Open image URL = https://www.itconnect.pl/la/o/<urlencoded_t>/logo.png
```

Signing input: `kid + "." + payload_b64url` with `OPENSSL_ALGO_SHA256`.

Email hash (`eh`):

```
sha256(module|record_id|email_field|normalize(email)|email_pepper)
```

## Configuration

[`config/modules/LinkAction.php`](../../config/modules/LinkAction.php):

- `active_kid`, key paths under `config/keys/`
- `email_pepper` — must match across CRM instances
- `www_base_url` — production: `https://itconnect.pl/la`
- `queue_api` — `fetch_url`, `ack_url`, `api_key`, `timeout_seconds`, `local_incoming`

Set `queue_api.api_key` to the same value as www `pull_api_key` (prefer `config/config_override.php` on servers; do not commit secrets).

Key generation (CRM server only):

```bash
openssl ecparam -name prime256v1 -genkey -noout -out config/keys/link_action_private_v1.pem
openssl ec -in config/keys/link_action_private_v1.pem -pubout -out config/keys/link_action_public_v1.pem
openssl rand -hex 32   # pull API key for www + CRM
```

Deploy `link_action_public_v1.pem` to Cyberfolks `_link_action/keys/`.

## www deploy (Cyberfolks)

1. In `external/www-la/`: `composer install --no-dev`.
2. Copy package to `public_html/` (`la.php`, `img.php`, `queue-api.php`, `bootstrap.php`, `vendor/`, `src/`, `assets/`, `.htaccess`, `responses/`).
3. Copy `config.production.php` → `_link_action/config.php` and set `pull_api_key`.
4. Ensure `_link_action/` is writable (queue, jti cache, logs).
5. Route `/la`, `/la/o/{token}/logo.png`, and `/la/queue` via `.htaccess` rewrites.
6. Ensure PHP 8.2+ with OpenSSL (ECDSA P-256).

### Queue pull API (`/la/queue`)

| Method | Header | Response |
|--------|--------|----------|
| GET | `X-LinkAction-Pull-Key: <secret>` | **200** + JSONL body, or **204** if empty |
| POST | same | **204** after truncating queue (ack) |

Wrong/missing key → **404**. Pull attempts logged to `_link_action/pull.log`.

Queue line written on click or first image load:

```json
{"ts":"2026-06-03T14:22:01+00:00","t":"<full_token>","fp":"<sha256_hex_of_t>"}
```

### Open tracking image (`/la/o/{token}/logo.png`)

- Served by `img.php`; static PNG from `assets/logo.png` (config: `logo_asset_path`).
- Invalid/expired tokens still return the logo (no queue row).
- `Cache-Control: no-store` — avoids client-side cache skipping the tracking request.
- Semantics: **images were loaded at least once** for this send (Gmail/Apple proxy aware); not proof of reading.

## Settings audit log

**Settings → Logs → Link actions** (`index.php?module=LinkAction&parent=Settings&view=ListView`) — read-only list of `u_yf_link_action_log` rows (clicked_at, module, record, action, scope, email field). Admin only.

## CRM import paths

```
import/link-action/incoming/queue.jsonl
import/link-action/processed/
import/link-action/failed/
```

Cron handler: `App\Modules\LinkAction\Cron\ImportTask` (300s).

Import order: `QueuePuller::fetch()` → `QueueImporter::importIncoming()` → `QueuePuller::ack()`.

Manual run:

```bash
docker compose exec -T cron gosu www-data php cron/vtigercron.php service=LBL_LINK_ACTION_IMPORT
```

Migration:

```bash
docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
```

## Email templates

`$(custom : LinkActionUrl|action|scope|emailField)$` resolves to the **signed URL string** (HTML-escaped for attribute context). Wrap it in your own markup, e.g. `<a href="$(custom : LinkActionUrl|unsubscribe|future_contact|email_prywatny)$">Wypisuje się</a>`. The third parameter must be the same email field used when sending.

`$(custom : LinkActionImageUrl|open|email|emailField)$` resolves to a **signed image URL** for `<img src="…">`. Each send gets a unique token (`jti`); first load records an open in the audit log.

Seeded Template Elements:

- `kandydaci_unsubscribe_footer` (`pl_pl` / `en_us`) — footer copy + `<a href="…LinkActionUrl…">` with label
- `kandydaci_open_tracking_logo` (`pl_pl` / `en_us`) — logo `<img>` with `LinkActionImageUrl`
- Template `1444661` updated to append `$(dynamic : kandydaci_unsubscribe_footer)$`

Mass mail guard: `EmailParser` skips Kandydaci recipients with `is_future_contact_allowed = 0`.

## v1 handlers

| Module | Action | Scopes | Effect |
|--------|--------|--------|--------|
| Kandydaci | unsubscribe | future_contact, all | `is_future_contact_allowed = 0`, `data_maksymalny_kontakt_rodo = today` |
| Kandydaci | open | email | Audit log only (`u_yf_link_action_log`) |

Outbound mail lifecycle: every send creates a row in `u_yf_mail_messages` with `send_status = prepared`, embeds that row’s `id` as **`mid`** in signed LinkAction tokens (`LinkActionImageUrl`, `LinkActionUrl`), then sends and sets `send_status = sent` (or `failed`). Settings log joins `u_yf_link_action_log.mail_message_id` → message subject so you can tell which of several sends to the same person was opened. Template preview omits LinkAction tokens until send (`mailMessageId` unset).

## Key rotation

1. Generate new key pair with new `kid` (e.g. `v2`).
2. Add public key to www config and CRM `public_keys`.
3. Set CRM `active_kid` to `v2` for new tokens.
4. Keep old public key on www until all old tokens expire (730 days default).

## Troubleshooting

| Symptom | Check |
|---------|-------|
| Link shows error page | www `reject.log`, token expiry, public key path |
| Thank-you but CRM unchanged | cron status, `queue_api` URL/key, `cache/logs/system.log` |
| Queue not clearing on www | ack failed after import; check `pull.log`, re-run cron (jti dedup is safe) |
| Import failures | `import/link-action/failed/`, email hash mismatch vs record field |
| Duplicate clicks/opens | idempotent via `jti` in `u_yf_link_action_log` and www `jti.cache` |
| Image broken in mail | deploy `assets/logo.png`, check rewrite for `/la/o/…/logo.png` |
| Open logged but user did not read | expected with Gmail/Apple image proxy — see open tracking semantics |

## Manual smoke test

1. Ensure keys exist in `config/keys/` and matching `pull_api_key` / `queue_api.api_key`.
2. Generate URL from a Kandydaci record email template preview.
3. Open `https://itconnect.pl/la?t=...`.
4. Verify queue: `curl -sS -H "X-LinkAction-Pull-Key: $KEY" https://itconnect.pl/la/queue`
5. Run cron import command above.
6. Verify consent fields, `u_yf_link_action_log` row, empty remote queue after ack.

## Automated tests

```bash
docker compose exec -T app php vendor/bin/phpunit -c tests/phpunit.xml tests/phpunit/LinkAction/
```

Tests: `LinkActionTokenTest.php`, `KandydaciOpenHandlerTest.php`, `QueuePullerTest.php` (mock HTTP client).
