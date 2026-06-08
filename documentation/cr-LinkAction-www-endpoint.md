# Change Request: LinkAction — signed www link actions endpoint (`/la`) + CRM cron import

## Goal

Introduce a **public-facing link-action endpoint** on the Cyberfolks www server (`https://itconnect.pl/la`) that:

1. Accepts **ECDSA P-256-signed tokens** generated exclusively by FreeCRM (private key never leaves CRM).
2. Verifies signature + expiry on www using **public key only** — a www server compromise must **not** allow forging valid action links.
3. Appends accepted events to a **JSON Lines queue file** on www.
4. Lets FreeCRM **pull and import** that queue via cron (CRM is behind VPN; www cannot call CRM).
5. Executes registered link actions for any opted-in module; v1 registers **`unsubscribe`** (scopes: `future_contact`, `all`) for **`Kandydaci`** records.
6. Exposes a reusable **TextParser variable** for email templates: `$(custom : LinkActionUrl|unsubscribe|future_contact|newsletter_email)$`, resolved against the current template record module.

This replaces any ad-hoc “unsubscribe URL in template” approach with a generic, extensible **link-action** contract usable by every module that registers supported actions (unsubscribe today, confirm email or preference changes later) without new endpoints.

**Reference patterns in repo today:**

- File queue import: `RecruitmentApplication\Cron\CvImportTask` + `import/cv/pending/`
- CRM → www push: `ProjektyRekrutacyjne\Scripts\GetProjectsToJSON::sendProjectFileToWWW()` (SSH/SCP)
- TextParser custom URL: `Users\textparsers\UsersLinkToForgotPassword`

---

## Stance

- **Asymmetric crypto only.** No shared HMAC secret on www. Private key lives on CRM only; www holds public key(s) only. Algorithm: **ECDSA P-256** (`prime256v1`) with SHA-256 — available via PHP `openssl` extension on all supported hosts with no additional dependencies.
- **Defense in depth.** CRM **re-verifies** every token signature when importing the queue file — tampered rows are rejected even if www PHP was patched.
- **No parallel import paths.** One queue format, one cron importer, one dedup key (`jti`).
- **No `class_alias()`**, no legacy unsubscribe shim.
- **Generic action contract** from day one; modules opt in through a LinkAction registry/config, and v1 implements only the registered `Kandydaci` `unsubscribe` handler — unregistered modules/actions are rejected on www and skipped in CRM.
- **Preserve production data.** Unsubscribe only clears configured consent flags; never deletes records.

---

## Assumptions

| # | Assumption |
|---|------------|
| A1 | CRM runs on VPN-only server; cron container has outbound SSH/SFTP to `itconnect.pl:22222` (same host as existing `GetProjectsToJSON`). |
| A2 | Cyberfolks serves PHP 8.2+ with OpenSSL extension (`openssl_sign` / `openssl_verify` with `OPENSSL_ALGO_SHA256` and EC keys). **Verified** on `s63.cyber-folks.pl`: PHP 8.2.30, OpenSSL 3.5.1, ECDSA P-256 key generation and sign/verify confirmed working. `sodium` extension is NOT available — do not use it. |
| A3 | www document root: `public_html/autoinstalator/wordpress/` (or sibling path); `la.php` deployed outside direct public use. Public URL is **only** `https://itconnect.pl/la`; web server rewrite routes `/la` to the PHP endpoint. |
| A4 | LinkAction is module-generic; v1 registers **`Kandydaci`** for `unsubscribe`. Field `is_future_contact_allowed` (uitype 56) is the consent flag; `data_maksymalny_kontakt_rodo` is updated on revoke. |
| A5 | Token TTL in emails: **730 days** (2 years) — old mailing links remain valid. |
| A6 | Queue file on www: `../private/la/queue.jsonl` (outside `public_html`); CRM pulls to `import/link-action/incoming/queue.jsonl`. |
| A7 | Email binding uses normalized lowercase email + install pepper (`config/secret_keys.php` or dedicated `email_pepper` in `config/modules/LinkAction.php`). |
| A8 | No new composer dependency required — PHP `openssl` ext sufficient. |

---

## Impact

### Code being modified

| Path | Change | Observable? |
|------|--------|---------------|
| `config/modules/LinkAction.php` | **New** — keys paths, www SSH pull config, module/action registry, token TTL, base URL | Internal |
| `config/secret_keys.php` or `config/modules/LinkAction.php` | `email_pepper` (or reuse existing pepper pattern) | Internal |
| `src/Modules/LinkAction/Services/LinkActionToken.php` | **New** — sign (CRM), verify (CRM import), payload encode/decode | Internal |
| `src/Modules/LinkAction/Services/QueuePuller.php` | **New** — SSH/SCP pull queue from www → `import/link-action/incoming/` | Internal |
| `src/Modules/LinkAction/Services/QueueImporter.php` | **New** — parse JSONL, re-verify, dedup, dispatch handlers | Internal |
| `src/Modules/LinkAction/Services/Handlers/HandlerInterface.php` | **New** — common handler contract for module/action implementations | Internal |
| `src/Modules/LinkAction/Services/Handlers/KandydaciUnsubscribeHandler.php` | **New** — first registered `unsubscribe` implementation | Internal |
| `src/Modules/LinkAction/Services/FilePaths.php` | **New** — `incoming/`, `processed/`, `failed/` under `import/link-action/` | Internal |
| `src/Modules/LinkAction/Cron/ImportTask.php` | **New** — cron entry point | Internal |
| `src/TextParser/LinkActionUrl.php` | **New** — reusable `$(custom : LinkActionUrl\|action\|scope\|emailField)$`, resolved against current record module | Email templates |
| `src/TextParser/TextParser.php` | Ensure global custom TextParser variables are discoverable in the template variable picker if current discovery does not list `src/TextParser/*.php` classes | Template editor |
| `src/Email/EmailParser.php` | Add `Kandydaci` → `is_future_contact_allowed`; this is an allow flag: send only when field value is `1` | Mass/workflow mail |
| `migrations/LinkAction/m260604_000001_link_action_schema.php` | **New** — upgrade migration for log table, cron registration, and seeded Template Element | Install/upgrade |
| `src/Modules/Install/install_schema/Base2.php` | Cron row, `u_yf_link_action_log` table, and Template Element seed | Install |
| `src/Modules/Install/install_schema/data.sql` | Same seed data | Fresh install |
| `languages/en_us/LinkAction.json`, `languages/pl_pl/LinkAction.json` | **New** — cron label, admin messages, TextParser label `LBL_LINK_ACTION_URL` | Cron UI / Template editor |
| `languages/en_us/TemplateElements.json`, `languages/pl_pl/TemplateElements.json` | Label for seeded unsubscribe footer Template Element | Template editor |
| `documentation/module/LinkAction.md` | **New** — token spec, deploy runbook | Docs |
| `external/www-la/la.php` | **New** — verify-only endpoint for Cyberfolks deploy | External |
| `external/www-la/config.example.php` | **New** — public key path, rate limits, queue path | External |
| `external/www-la/responses/` | **New** — plain PHP HTML response includes per action (pl/en); no Smarty/Twig/template engine on Cyberfolks | External |
| `tests/unit/LinkAction/LinkActionTokenTest.php` | **New** — sign/verify round-trip, tamper, expiry | Tests |
| `tests/fixtures/link-action/` | **New** — sample tokens + queue lines | Tests |

### Code being deleted

| Path | Reason |
|------|--------|
| *(none in v1)* | Greenfield feature |

Hardcoded credentials in `GetProjectsToJSON.php` / `test.php` are **not** in scope — note as follow-up CR for shared `config/modules/WwwIntegration.php`.

### Database

| Item | Change | Observable? |
|------|--------|---------------|
| `u_yf_link_action_log` | **New table** — processed `jti`, module, record id, action metadata, audit | Internal |
| `vtiger_cron_task` | Insert: `LBL_LINK_ACTION_IMPORT` → `LinkAction\Cron\ImportTask`, frequency 300s | Cron admin |
| Module metadata (`vtiger_tab`) | **No new tab** — utility cron only (like Mail scanner registration) | — |
| `Kandydaci` fields | **No schema change** — uses existing `is_future_contact_allowed`, `data_maksymalny_kontakt_rodo` | — |

### Module metadata

- No Layout Editor changes.
- No new entity module.

### Cron / background

| Service | Handler | Frequency |
|---------|---------|-----------|
| `LBL_LINK_ACTION_IMPORT` | `App\Modules\LinkAction\Cron\ImportTask` | 300s (5 min) |

Task order: pull remote queue (append or replace) → import new lines → archive file.

### Webservices / REST

- **Not affected.**

### Caches

- Clear Smarty only if template editor labels cached oddly (standard reload sufficient).

### External consumers

| Consumer | Change |
|----------|--------|
| **Cyberfolks www** | Deploy `external/www-la/*`; install public key PEM; create `private/la/` directory writable by PHP; route public `/la` to the PHP endpoint |
| **Email templates** | Update existing `Kandydaci` email template record `1444661` to include seeded `$(dynamic : kandydaci_unsubscribe_footer)$`; raw `$(custom : LinkActionUrl\|unsubscribe\|future_contact\|newsletter_email)$` remains available inside the Template Element |
| **itconnect.pl WordPress** | No WP plugin required; standalone PHP endpoint behind `/la` rewrite |

### Call sites to update

| Contract | Call sites |
|----------|------------|
| `EmailParser::$permissionToSend` | `src/Email/EmailParser.php` — add `Kandydaci` entry; verify workflows and mass mail honor `is_future_contact_allowed = 1` before sending |
| TextParser custom | New reusable parser class plus any loader adjustment needed for global parser discoverability |
| Email template content | Update `Kandydaci` email template record `1444661` to include `$(dynamic : kandydaci_unsubscribe_footer)$` |

---

## Functional requirements

### In scope

| ID | Requirement | Before → After |
|----|-------------|----------------|
| F1 | Token generation | None → CRM signs ECDSA P-256 payload when rendering email template variable for any registered module/action |
| F2 | www endpoint | None → `GET /la?t=<kid>.<payload_b64url>.<sig_b64url>` verifies and appends to queue |
| F3 | CRM import | None → Cron pulls queue, re-verifies, applies handler, logs `jti` |
| F4 | `Kandydaci` `unsubscribe` / `future_contact` | Manual only → Sets `is_future_contact_allowed = 0`, sets `data_maksymalny_kontakt_rodo = today` |
| F5 | `Kandydaci` `unsubscribe` / `all` | — → Same as `future_contact` in v1 (only one consent dimension exists today) |
| F6 | Email binding | — → Token valid only if `eh` matches SHA-256 of normalized target email + pepper for the token's module, record, and email field |
| F7 | Idempotency | — → Same `jti` processed once (DB unique + www-side `jti` file optional) |
| F8 | Expired token | — → www shows generic message; **no queue write** |
| F9 | Invalid signature | — → Generic message; no queue write; rate-limited |
| F10 | Mass mail guard | Kandydaci ignores future-contact consent → `EmailParser` skips recipients with `is_future_contact_allowed = 0` when `emailoptout` checking enabled |
| F12 | Key rotation | — → `kid` in token; CRM + www support multiple public keys; CRM signs with active private `kid` |
| F13 | Template Element | — → Seed active `kandydaci_unsubscribe_footer` Template Element containing the generic signed unsubscribe link and standard footer copy |
| F14 | Confirmation UX | — → Successful unsubscribe click shows a thank-you/completed message; invalid/expired/rejected tokens still show a generic failure page |

### Out of scope (future CR)

| Item | Reason |
|------|--------|
| `unsubscribe` / `newsletter` as separate consent field | No dedicated newsletter-consent checkbox on `Kandydaci` — deferred until field exists |
| `confirm`, `preference` actions | Contract ready; handlers not implemented |
| Additional module registrations | Supported by the contract, but only `Kandydaci` is registered in v1 |
| WP plugin wrapper | Standalone PHP sufficient |
| Real-time webhook (www → CRM) | CRM behind VPN |
| Admin UI for key rotation | Manual PEM deploy in v1 |
| Backfill unsubscribe links for already-sent emails | Cannot retroactively sign |
| Refactor `GetProjectsToJSON` credentials to config | Separate hygiene CR |

### Business rules

1. Unsubscribe is **acknowledged as complete on www** after queue write and **effective in CRM** after next successful cron import (≤5 min default).
2. CRM import **never** applies an action without valid ECDSA P-256 signature, even if the queue line exists.
3. If `module` is not registered, `record_id` is not found, `email_field` is not allowed, or `eh` does not match that field on the record → line moved to `failed/`, logged, no record change.
4. Duplicate `jti` → skip silently (idempotent).
5. www **never** stores or reads private key material.
6. Success response on www says thank you and confirms unsubscribe completion from the user's perspective.
7. Error responses on www are **generic** (no leak: expired vs bad sig vs unknown action).

### Token payload schema (v1)

```json
{
  "v": 1,
  "kid": "v1",
  "module": "Kandydaci",
  "record_id": 1404311,
  "action": "unsubscribe",
  "scope": "future_contact",
  "email_field": "newsletter_email",
  "eh": "<sha256_hex>",
  "iat": 1748966400,
  "exp": 1780502400,
  "jti": "<32_hex_chars>"
}
```

**Wire format:**

```
t = <kid>.<base64url(json_payload)>.<base64url(ecdsa_p256_signature_der)>
URL = https://itconnect.pl/la?t=<urlencoded_t>
```

**Signing (CRM only):**

```
openssl_sign( kid . "." . payload_b64url , $sig, $privateKey, OPENSSL_ALGO_SHA256 )
```

**Verification (www + CRM):**

```
openssl_verify( kid . "." . payload_b64url , $sig, $publicKey, OPENSSL_ALGO_SHA256 ) === 1
```

### Queue line schema (www → CRM)

```json
{"ts":"2026-06-03T14:22:01+00:00","t":"<full_token>","fp":"<sha256_hex_of_t>"}
```

www writes **only** after successful verify + expiry check. Minimal metadata — CRM decodes `t`.

---

## Data migration

Upgrade migration file:

```bash
docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
```

Fresh installs receive the same table, cron seed, and Template Element seed through `src/Modules/Install/install_schema/Base2.php` and `src/Modules/Install/install_schema/data.sql`.

### Schema

```sql
CREATE TABLE IF NOT EXISTS `u_yf_link_action_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `jti` varchar(64) NOT NULL,
  `kid` varchar(16) NOT NULL,
  `module` varchar(64) NOT NULL,
  `record_id` int unsigned NOT NULL,
  `action` varchar(32) NOT NULL,
  `scope` varchar(32) NOT NULL,
  `email_field` varchar(64) NOT NULL,
  `eh` char(64) NOT NULL,
  `token_fp` char(64) NOT NULL,
  `processed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_link_action_jti` (`jti`),
  KEY `idx_link_action_target` (`module`, `record_id`),
  KEY `idx_link_action_processed_at` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Cron registration

```sql
INSERT INTO `vtiger_cron_task`
  (`name`, `handler_class`, `handler_params`, `frequency`, `status`, `module`, `sequence`, `description`)
SELECT
  'LBL_LINK_ACTION_IMPORT',
  'App\\Modules\\LinkAction\\Cron\\ImportTask',
  NULL,
  300,
  1,
  'LinkAction',
  28,
  'Pull and import signed link actions from www queue'
WHERE NOT EXISTS (
  SELECT 1 FROM `vtiger_cron_task`
  WHERE `handler_class` = 'App\\Modules\\LinkAction\\Cron\\ImportTask'
);
```

Idempotence rules:

- The table is created with `CREATE TABLE IF NOT EXISTS`.
- The cron row is inserted only when the `handler_class` is absent.
- Existing `Kandydaci` rows are not migrated or rewritten; existing non-conforming rows are handled at import time by failing the queue line without changing the record.

### Key generation (ops, not SQL)

Run once on CRM server (private key **never** copied to www):

```bash
openssl ecparam -name prime256v1 -genkey -noout -out config/keys/link_action_private_v1.pem
openssl ec -in config/keys/link_action_private_v1.pem -pubout -out config/keys/link_action_public_v1.pem
# Deploy link_action_public_v1.pem to Cyberfolks ../private/keys/
```

Add `config/keys/link_action_private_v*.pem` to `.gitignore`; deploy via secure channel.

### Rollback SQL

```sql
DROP TABLE IF EXISTS `u_yf_link_action_log`;
DELETE FROM `vtiger_cron_task`
 WHERE `handler_class` = 'App\\Modules\\LinkAction\\Cron\\ImportTask';
```

Existing consent values changed by import: **restore from backup** if rollback needed (cannot infer previous consent from log alone unless ModComment added later).

---

## Implementation plan

### Phase 0 — Crypto + config (CRM)

1. `config/modules/LinkAction.php` — paths, active `kid`, token TTL, www SSH host/path, `base_url`, module/action registry
2. `config/keys/.gitignore` entry
3. `src/Modules/LinkAction/Services/LinkActionToken.php`
   - `sign(array $payload): string`
   - `verify(string $token): ?array` (returns payload or null)
   - `buildPayload(string $moduleName, int $recordId, string $emailField, string $email, string $action, string $scope): array`
   - `emailHash(string $email): string`
4. `tests/unit/LinkAction/LinkActionTokenTest.php`

### Phase 1 — www endpoint (Cyberfolks deploy package)

5. `external/www-la/la.php` — verify-only, rate limit, append queue, render view
6. `external/www-la/config.example.php` — copy to `../private/la/config.php` on server
7. `external/www-la/responses/unsubscribe_ok.php` — plain PHP thank-you/completed response include (+ generic error response); no `.tpl` files on Cyberfolks
8. `external/www-la/.htaccess` — optional rate limit hint
9. Manual deploy runbook in `documentation/module/LinkAction.md`

### Phase 2 — CRM import pipeline

10. `src/Modules/LinkAction/Services/FilePaths.php`
11. `src/Modules/LinkAction/Services/QueuePuller.php` — SSH/SCP (mirror `GetProjectsToJSON` connection params from config, not hardcoded)
12. `src/Modules/LinkAction/Services/Handlers/HandlerInterface.php`
13. `src/Modules/LinkAction/Services/Handlers/KandydaciUnsubscribeHandler.php`
14. `src/Modules/LinkAction/Services/QueueImporter.php`
15. `src/Modules/LinkAction/Cron/ImportTask.php`
16. `migrations/LinkAction/m260604_000001_link_action_schema.php` + `Base2.php` / `data.sql` seeds
17. `mkdir -p import/link-action/{incoming,processed,failed}` in repo `.gitkeep`

### Phase 3 — Email integration

18. `src/TextParser/LinkActionUrl.php`
    - Params: `action`, `scope`, email field name (default comes from module registry)
    - Module: current parser record module; no module name in template variable unless a future use case requires cross-record links
    - Output: full `https://itconnect.pl/la?t=...` URL (or `<a href>` variant — match template needs)
19. `src/TextParser/TextParser.php`
    - Ensure reusable parser appears in the template variable picker as `$(custom : LinkActionUrl|unsubscribe|future_contact|newsletter_email)$` for registered modules
20. Seed two `u_yf_templateelements` records, one for `pl_pl` and one for `en_us`:
    - `code`: `kandydaci_unsubscribe_footer`
    - `module_name`: `Kandydaci`
    - `language`: `pl_pl` / `en_us`
    - `type`: `PLL_VARIABLE_ALIAS`
    - `status`: `1`
    - `content`: short footer copy containing `$(custom : LinkActionUrl|unsubscribe|future_contact|newsletter_email)$`
21. Update `Kandydaci` email template record `1444661` to include `$(dynamic : kandydaci_unsubscribe_footer)$`
22. `src/Email/EmailParser.php` — add Kandydaci consent check
23. Language files (`en_us` + `pl_pl`)

### Phase 4 — Docs + verification

24. `documentation/module/LinkAction.md` — token spec, deploy, rotate keys, troubleshoot
25. Grep verification (see Testing)
26. Update `.cursor/rules/` if useful (optional: `link-action.mdc` — defer unless team wants it)

---

## Testing

### Automated

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/LinkAction/LinkActionTokenTest.php
```

Cases:

- Sign → verify round-trip
- Tampered payload → verify fails
- Tampered signature → verify fails
- Expired `exp` → verify fails (CRM); www rejects
- Wrong `module`, `record_id`, `email_field`, or `eh` → importer moves to failed
- Duplicate `jti` → second import skipped

### Manual smoke (CRM dev)

1. Generate token via CLI script or temporary test endpoint for module + record ID + email field
2. Open `https://dev.itconnect.pl/la?t=...` (or local mock of `external/www-la/la.php`)
3. Confirm queue line appended on www
4. Run: `docker compose exec -T app php cron/vtigercron.php service=LBL_LINK_ACTION_IMPORT`
5. Verify `Kandydaci.is_future_contact_allowed = 0` and row in `u_yf_link_action_log`

### Migration/data integrity checks

Run the migration:

```bash
docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
```

Verify via `freecrm-mysql` MCP `execute_query`:

```sql
SELECT COUNT(*) AS table_exists
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'u_yf_link_action_log';

SELECT name, handler_class, frequency, status
FROM vtiger_cron_task
WHERE handler_class = 'App\\Modules\\LinkAction\\Cron\\ImportTask';

SHOW INDEX FROM u_yf_link_action_log WHERE Key_name = 'uk_link_action_jti';

SELECT code, module_name, language, type, status
FROM u_yf_templateelements
WHERE code = 'kandydaci_unsubscribe_footer';

SELECT emailtemplatesid, module, content
FROM u_yf_emailtemplates
WHERE emailtemplatesid = 1444661
  AND content LIKE '%$(dynamic : kandydaci_unsubscribe_footer)$%';
```

### Manual smoke (email template)

1. Add `$(dynamic : kandydaci_unsubscribe_footer)$` to test template
2. Send to test record via mass mail / compose
3. Click link → confirm end-to-end

### Regression areas

| Area | Check |
|------|-------|
| CV import cron | `LBL_SCHEDULED_CV_IMPORT` still runs |
| Mass mail to Kandydaci | Recipients with `is_future_contact_allowed = 0` skipped |
| RecruitmentApplication import | Unchanged |
| Projects JSON export | Unchanged |

### Logs

- `cache/logs/system.log` — import errors, verify failures
- `cache/logs/cron.log` — cron task completion
- www: `../private/la/reject.log` (failed verify, rate limit)

### Grep verification

```bash
rg 'ImportTask|LinkActionToken|LinkAction' src/ config/
rg 'LinkActionUrl|kandydaci_unsubscribe_footer|is_future_contact_allowed|permissionToSend' src/Email/EmailParser.php src/TextParser/ src/Modules/LinkAction/ migrations/
# No private key paths in external/www-la/
rg 'PRIVATE|private.*pem' external/www-la/
```

---

## Rollback plan

### Code

1. Revert deploy commit / redeploy previous tag.
2. Disable the `/la` route on Cyberfolks (remove endpoint file or return 503).
3. Disable cron: `UPDATE vtiger_cron_task SET status = 0 WHERE name = 'LBL_LINK_ACTION_IMPORT'`

### Data

Use rollback SQL above to drop `u_yf_link_action_log` and remove the cron row. Consent flag changes applied before rollback cannot be reversed from the log — restore from backup if needed. Acceptable operational impact: unsubscribe links may return an error while `/la` is disabled; no record deletions occur, but consent changes already imported are destructive unless restored from backup.

---

## Edge cases

| Case | Handling |
|------|----------|
| Target record deleted before import | Import fails line → `failed/`; no crmentity update |
| Email changed in CRM after mail sent | Old link still valid for old `eh` — import matches by `module` + `record_id` + `email_field` + `eh`; if email no longer matches that field → failed |
| Multiple email fields | Token bound to specific field used at send time (`newsletter_email` vs `email_prywatny`) |
| User clicks unsubscribe twice | www idempotent (same `jti` — optional www `jti.cache`; CRM dedups via DB) |
| Partial queue file during pull | Pull to temp file, atomic rename; importer reads complete file only |
| Clock skew www vs CRM | Allow 60s skew on `iat`; `exp` compared in UTC |
| Key rotation mid-flight | Old tokens with old `kid` verify until `exp`; both public keys on www |
| CRM cron offline for days | Queue accumulates on www; import catches up; tokens may expire before click — acceptable |
| Attacker floods www with garbage | Rate limit; no CRM impact until pull; invalid lines fail verify at import |
| Attacker compromises www, patches PHP to write fake lines | CRM re-verify rejects all forgeries without private key |
| Attacker compromises www, replaces queue file entirely | CRM verify rejects invalid tokens; ops alert on zero valid / high failure rate |
| `is_future_contact_allowed` already 0 | Handler no-op but log row still written (audit) |

---

## Decision rationale & tradeoffs

### Why ECDSA P-256 asymmetric (not HMAC)?

**Requirement:** www compromise must not enable token generation. HMAC requires shared secret on www → fails requirement. ECDSA P-256 gives short tokens (~96-char base64url signature), fast verify, and works natively via PHP `openssl_sign` / `openssl_verify` with `OPENSSL_ALGO_SHA256` on every supported host — including Cyberfolks PHP 8.2 (verified).

**Ed25519 considered and rejected:** Ed25519 is not supported by PHP's OpenSSL extension on Cyberfolks (confirmed: `openssl_pkey_new` with `ed25519` fails; `sodium` extension unavailable). ECDSA P-256 provides equivalent 128-bit security with full PHP OpenSSL support.

**RSA-4096 considered and rejected:** 342-char base64url signature makes email URLs unwieldy. ECDSA P-256 signature is 96 chars.

### Why signed plaintext payload (not encrypted)?

PHP must read `exp`, `module`, and `action` without private key. Encryption would require symmetric key on www → back to shared-secret problem. Payload fields (`module`, `record_id`, `email_field`, `eh`) are not secret; security is in **unforgeability**, not confidentiality.

### Why file queue (not DB on www)?

Matches existing CV/projects integration pattern; no MySQL access needed on www; CRM pulls from www over outbound HTTPS (CRM is VPN-only; www cannot call CRM).

**Alternative:** www POST directly to CRM — impossible without exposing CRM.

### Why HTTP pull API (not SSH/SCP)?

SSH pull on Cyberfolks proved brittle (login format, `sshpass` vs cron `www-data`, OpenSSH vs PHP ssh2). An authenticated HTTPS GET/POST on `https://itconnect.pl/la/queue` is simpler to operate and test. Queue file remains on disk; only the transport changed.

**Ack after import:** remote queue is truncated only after CRM successfully imports (safer than truncate-on-download).

### Why CRM re-verify?

www file is not trusted storage. Re-verify closes the “patched PHP writes garbage” attack.

### Why `jti` dedup table (not file-only)?

Survives queue file rotation; queryable audit; idempotent across partial imports.

### Why utility module `LinkAction` (not `RecruitmentApplication`)?

Link actions are generic (unsubscribe today, confirm/preference tomorrow); RecruitmentApplication is CV-specific. Keeps cron ownership clear.

---

## Risks

| Risk | Severity | Mitigation in CR | Gap |
|------|----------|------------------|-----|
| Private key leaked from CRM | **Low** | CRM is VPN-only (no network exposure); PEM outside web root; restrictive file permissions; `.gitignore` entry for key files; key rotation runbook | None |
| SSH credentials for queue pull leaked | **Med** | *(superseded)* | Replaced by HTTP pull API key — see addendum below |
| Delayed unsubscribe (cron lag) | **Low** | 5-min cron; confirmation page sets expectation | None |
| Email template not updated | **Low** | Document variable; update marketing templates in same deploy | Need list of production templates during implementation |
| ECDSA P-256 unavailable on www PHP | **Low** | Verified working on Cyberfolks s63 PHP 8.2.30 / OpenSSL 3.5.1 | None |
| False failed imports (email normalization mismatch) | **Med** | Single `normalizeEmail()` shared between sign and verify | Include Unicode/IDN email cases in unit tests if present in data |
| GDPR: unsubscribe not instant in CRM | **Med** | www thank-you page confirms completion; CRM applies within minutes; document processing timing in privacy policy if required | None |

---

## Resolved implementation decisions

1. Update existing `Kandydaci` email template record `1444661` in the same deploy.
2. Seed separate `pl_pl` and `en_us` Template Element records selected by template language.

---

## Deliverables checklist

- [ ] `documentation/cr-LinkAction-www-endpoint.md` (this document)
- [ ] `documentation/module/LinkAction.md` (implementation-time spec)
- [ ] CRM: `LinkActionToken`, importer, cron, migration, TextParser, EmailParser
- [ ] `external/www-la/` deploy package
- [ ] Unit tests + manual test protocol
- [ ] Key generation + Cyberfolks deploy (ops)
- [ ] Marketing email template record `1444661` updated with unsubscribe footer dynamic element

---

## Appendix A — `config/modules/LinkAction.php` (sketch)

```php
<?php
return [
    'active_kid' => 'v1',
    'private_key_path' => ROOT_DIRECTORY . '/config/keys/link_action_private_v1.pem',
    'public_keys' => [
        'v1' => ROOT_DIRECTORY . '/config/keys/link_action_public_v1.pem',
    ],
    'token_ttl_seconds' => 63072000, // 730 days
    'email_pepper' => '…', // or read from secret_keys
    'www_base_url' => 'https://itconnect.pl/la',
    'modules' => [
        'Kandydaci' => [
            'default_email_field' => 'newsletter_email',
            'email_fields' => ['newsletter_email', 'email_prywatny'],
            'actions' => [
                'unsubscribe' => [
                    'handler' => \App\Modules\LinkAction\Services\Handlers\KandydaciUnsubscribeHandler::class,
                    'scopes' => ['future_contact', 'all'],
                ],
            ],
        ],
    ],
    'queue_api' => [
        'fetch_url' => 'https://itconnect.pl/la/queue',
        'ack_url' => 'https://itconnect.pl/la/queue',
        'api_key' => '…', // same as www pull_api_key
        'timeout_seconds' => 30,
        'local_incoming' => ROOT_DIRECTORY . '/import/link-action/incoming/queue.jsonl',
    ],
];
```

## Appendix B — TextParser usage (template)

```
$(dynamic : kandydaci_unsubscribe_footer)$
```

Seeded Template Element wraps the signed URL variable in a styled anchor (parser returns URL only):

```html
<a href="$(custom : LinkActionUrl|unsubscribe|future_contact|email_prywatny)$">Wypisuje się</a>
```

Template authors should use the dynamic element by default so footer copy, link text, and future wording changes stay centralized.

## Appendix C — UnsubscribeHandler field effects

| scope | `is_future_contact_allowed` | `data_maksymalny_kontakt_rodo` |
|-------|----------------------------|--------------------------------|
| `future_contact` | `0` | today (UTC date) |
| `all` | `0` | today (UTC date) |

---

## Addendum — HTTP queue pull API (2026-06)

Replaces SSH/SCP pull described in the original CR. Click flow (`la.php`) unchanged.

### www

| File | Role |
|------|------|
| `external/www-la/common.php` | Shared config + helpers |
| `external/www-la/queue-api.php` | `GET`/`POST /la/queue` |
| `_link_action/config.php` | `pull_api_key`, `pull_rate_limit`, `pull_log_path` |

- Auth: header `X-LinkAction-Pull-Key` (constant-time compare); wrong key → **404**
- `GET`: locked read of `queue.jsonl`; **200** (JSONL) or **204** (empty)
- `POST`: locked truncate (ack); **204**
- `.htaccess`: `RewriteRule ^la/queue$ queue-api.php [L]`

### CRM

| Change | Detail |
|--------|--------|
| Config | `queue_pull` → `queue_api` (`fetch_url`, `ack_url`, `api_key`, `timeout_seconds`) |
| `QueuePuller` | `fetch()` + `ack()` via `QueueHttpClient` (curl) |
| `ImportTask` | `fetch()` → `importIncoming()` → `ack()` |
| Removed | All `scp`/`ssh`/`sshpass`/`exec` pull code |

### Deploy

1. `openssl rand -hex 32` → set `pull_api_key` (www) and `queue_api.api_key` (CRM, prefer `config_override.php`)
2. Upload `common.php`, `queue-api.php`, updated `la.php`, `.htaccess`
3. Rotate/remove obsolete SSH password used for queue pull
4. Smoke: click `/la`, `curl` GET queue, run cron, verify log + empty queue after ack

### Risk: pull API key leak

Treat like SSH credentials: HTTPS only, rotate on compromise, monitor `_link_action/pull.log`.
