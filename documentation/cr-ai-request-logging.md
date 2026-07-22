# Change Request: AI request logging (`cache/logs/ai.log`)

**Status:** Implemented (dev)  
**Depends on:** [cr-ai-prompts-mail-improve.md](cr-ai-prompts-mail-improve.md) (CR-2 — OpenAI client + mail improve)  
**Scope:** Full request/response logging for every OpenAI call; config toggle; no UI

---

## Goal

When CRM calls OpenAI, developers can inspect **exactly what was sent and what came back**, without digging through masked `system.log` POST dumps.

After this CR:

1. Every OpenAI HTTP exchange is written to **`cache/logs/ai.log`** (when enabled).
2. Chat Completions entries include full `messages[]` (system + user) and full assistant content (or failure details).
3. Short triage lines stay in `system.log`; payloads live only in `ai.log`.
4. API key / Authorization header **never** appear in any log.

Triggered by production timeouts (`errno=28` / 45 s) on ~192 KB mail bodies where `system.log` only showed `LBL_AI_HTTP_FAILED`.

---

## Stance

- **No fallbacks** — logging failures must not swallow OpenAI errors; if the write fails, log a one-line `system.log` error and continue (the AI call result is authoritative).
- **Single choke point** — only `App\Ai\OpenAi\Client` writes AI exchange logs. No duplicate logging in Actions / Services.
- **No parallel clients** — no second curl path that bypasses the logger.
- API key never in logs (same rule as CR-2).
- `class_alias()` forbidden.
- Do **not** dump full AI payloads into `system.log` (masked mail bodies + `LOG_LEVELS` = error/warning make that the wrong place).

---

## Decisions (confirmed)

| # | Decision |
|---|----------|
| D1 | Dedicated file **`cache/logs/ai.log`** (same pattern as `http-requests.log` / `sql.log`), not `system.log`. |
| D2 | Toggle: **`LOG_AI_REQUESTS`** in `config/debug.php` (default **`true`** on current dev; document that prod should set `false` unless debugging). |
| D3 | Hook only in **`App\Ai\OpenAi\Client`** — covers `chatCompletions` and `listChatModels`. |
| D4 | Callers pass **`action`** (+ optional `userId`) so blocks are labeled (`mail.improve`, `provider.list_models`, …). |
| D5 | Log copy of message/content may **replace `data:` URIs** with `[data-uri omitted N chars]` — wire payload to OpenAI unchanged. |
| D6 | One exchange = two appends with the same `id`: **request before HTTP**, result after (so `ai.log` exists while OpenAI is still running). |
| D7 | **No DB audit table** in this CR (defer; mail has `s_yf_mail_sent_log` as future parallel if needed). |
| D8 | **No Settings UI** for browsing logs. |

---

## Assumptions

| # | Assumption |
|---|------------|
| A1 | CR-2 is live: `Client`, `ImproveMailService`, Provider Ajax exist. |
| A2 | `cache/logs/` is writable by PHP-FPM (`www-data`) — same as other log files. |
| A3 | Dev wants full prompt bodies for debugging oversized / slow improves; prod can disable via config. |
| A4 | Raising curl timeout / dedicated `LBL_AI_TIMEOUT` are **out of scope** (follow-up). |
| A5 | `listChatModels` is logged as a compact exchange (endpoint, duration, status, model count) — not the full model id list unless useful; prefer count + first few ids max to avoid noise. |

---

## Impact

### Observable vs internal

| Change | Observable |
|--------|------------|
| New file `cache/logs/ai.log` when AI is used | Dev/ops only |
| `LOG_AI_REQUESTS` in `config/debug.php` | Config |
| User-facing compose / Provider UI | **No** change |
| OpenAI wire format / prompts | **No** change |

### Code being added

| Path | Role |
|------|------|
| `src/Ai/OpenAi/RequestContext.php` | Small value object: `action`, `?userId`, optional extras |
| `src/Ai/OpenAi/AiRequestLogger.php` | Append structured blocks to `cache/logs/ai.log`; redact `data:` URIs; never see API key |
| `tests/ai_request_logging_smoke.php` | Logger redaction + flag-off writes nothing (no real OpenAI) |

### Code being modified

| Path | Change |
|------|--------|
| `config/debug.php` | Add `LOG_AI_REQUESTS => true` (+ comment) |
| `src/Ai/OpenAi/Client.php` | Accept `?RequestContext`; wrap `requestJson` / chat path with logger; map errno 28 → `status=timeout` in log |
| `src/Ai/Mail/ImproveMailService.php` | Pass `RequestContext(action: mail.improve, userId: …)` into `chatCompletions` |
| `src/Modules/Settings/AiPrompts/Actions/SaveProviderAjax.php` | Pass `RequestContext(action: provider.list_models, userId: …)` into `listChatModels` |
| `.cursor/rules/error-checking.mdc` | Document `cache/logs/ai.log` |

### Code being deleted

**None.**

### Call sites of `Client` (must all pass context)

| Caller | Method | `action` |
|--------|--------|----------|
| `ImproveMailService::improve` | `chatCompletions` | `mail.improve` |
| `SaveProviderAjax` (model list) | `listChatModels` | `provider.list_models` |

Any future caller must supply `RequestContext` — signature requires it (or a default `action=unknown` that is loud in logs; prefer **required** `RequestContext` so missing labels fail at compile/review time).

### DB / module metadata / language / cron / webservice

**None.**

### Caches

None (no Smarty).

### External

OpenAI traffic unchanged; only local disk append.

---

## Functional requirements

### Before → after

| Before | After |
|--------|-------|
| Timeout / API failure → `system.log` line with errno / `LBL_AI_*` only | Same + full exchange in `ai.log` (when enabled) |
| Cannot see resolved prompt / body size that hit OpenAI | `ai.log` shows messages, byte sizes, duration, status |
| `$_POST.body` in `system.log` masked as `*** (N chars)` | Unchanged; payload detail is in `ai.log` |

### In scope

- Flag + file logger + Client integration + caller context
- Redact `data:` URIs in **logged** text only
- Redact any accidental `sk-…` substrings in logged API error text (reuse / share Client sanitize)
- Document log file in `error-checking.mdc`

### Out of scope / future

| Item | Why deferred |
|------|----------------|
| Raise `TIMEOUT_SECONDS` / align with JS 90 s | Separate reliability fix |
| User-facing `LBL_AI_TIMEOUT` vs `LBL_AI_HTTP_FAILED` | UX; not required for logging |
| DB audit / retention cron | Ops product; file is enough for now |
| Streaming / Responses API | Not used |
| Admin UI to view `ai.log` | Ops use `tail` / editor |
| Logging non-OpenAI providers | No other provider yet |

### Business rules

- When `LOG_AI_REQUESTS === false`, **zero** writes to `ai.log` (and no temp files).
- Logged content must not include API key or `Authorization` header values.
- Failure to append `ai.log` must not change the OpenAI success/failure outcome returned to the user.

---

## Data migration

**No data migration required.**

---

## Log record format

Multi-line block (UTF-8), append-only:

```
=== ai.request id=<uuid> ts=2026-07-22T12:18:28+02:00 ===
action=mail.improve user=1 model=gpt-4.1-mini endpoint=chat.completions
request_bytes=<n>
messages:
  [system] <text>
  [user] <text with data: URIs redacted>
---
status=ok|timeout|http_4xx|http_5xx|transport|invalid_json duration_ms=<n> http=<code> errno=<n>
response_bytes=<n>
usage: prompt_tokens=<n> completion_tokens=<n> total_tokens=<n>   # omit section if absent
content:
  <assistant text or empty on failure>
error: <sanitized short message if any>
=== ai.end id=<uuid> ===
```

For `listChatModels`:

```
=== ai.request id=… ===
action=provider.list_models user=… model=- endpoint=models.list
…
status=ok duration_ms=… http=200
models_count=<n>
=== ai.end id=… ===
```

**Status mapping**

| Condition | `status` |
|-----------|----------|
| HTTP 2xx + usable body | `ok` |
| curl errno 28 | `timeout` |
| other curl errno | `transport` |
| non-JSON body | `invalid_json` |
| HTTP 4xx / 5xx | `http_4xx` / `http_5xx` |

---

## Implementation plan

Ordered, each step leaves the app runnable.

### 1. Config flag

- `config/debug.php` — add `LOG_AI_REQUESTS` with comment pointing at `cache/logs/ai.log`.

### 2. Logger + context types

- Add `RequestContext.php` (`action: string`, `userId: ?int`).
- Add `AiRequestLogger.php`:
  - `isEnabled(): bool` via `AppConfig::debug('LOG_AI_REQUESTS')`
  - `begin(RequestContext, model, endpoint): string` → request id (or single `writeExchange(...)` after the call — prefer **one write after** so duration/status are complete; still generate id up front for correlation if needed)
  - Prefer **single append after** `curl_exec` with all fields (simpler, atomic enough with `FILE_APPEND|LOCK_EX`)
  - `redactForLog(string): string` — `data:` URIs + `sk-…` patterns
  - Path: `ROOT_DIRECTORY . '/cache/logs/ai.log'`
  - Pattern reference: `WebUI::logHttpRequest()` (`file_put_contents` + `LOCK_EX`)

### 3. Client integration

- Change signatures:

```php
public function chatCompletions(
  string $apiKey,
  string $model,
  array $messages,
  RequestContext $context
): string

public function listChatModels(string $apiKey, RequestContext $context): array
```

- After each HTTP attempt (success or fail), call logger with request messages / response content / timing / errno / HTTP code / usage if present.
- Keep existing `Log::error` triage lines; enrich timeout case in **ai.log** with `status=timeout` (optional: include `timeout` word in the existing error line — do not remove errno).

### 4. Update every call site

- `ImproveMailService::improve` — `new RequestContext(ActionRegistry::MAIL_IMPROVE, $userId)` (or literal `'mail.improve'` if registry constant is the source of truth — use the registry constant).
- `SaveProviderAjax` — `new RequestContext('provider.list_models', $currentUserId)`.

### 5. Docs / rules

- `.cursor/rules/error-checking.mdc` — new subsection for `cache/logs/ai.log`.
- Cross-link from CR-2 doc footer → this CR as CR-3.

### 6. Smoke test

- `tests/ai_request_logging_smoke.php`:
  - redaction replaces a sample `data:image/png;base64,…`
  - with flag forced off (if test can override config) or by testing logger method that accepts enabled flag — prefer pure unit of `redactForLog` + format builder without disk if config is hard to override; otherwise write to temp path injected for test.
- No live OpenAI in CI.

### 7. Legacy deletion

**N/A** — additive only.

---

## Testing

### Manual smoke

1. `LOG_AI_REQUESTS=true` — compose wand on a short mail → `ai.log` has `action=mail.improve`, both messages, `status=ok`, content HTML.
2. Same with a body containing a small inline `data:image/...;base64,...` → OpenAI still receives the image; **log** shows `[data-uri omitted N chars]`.
3. Provider → refresh model list → `ai.log` has `provider.list_models`.
4. Set `LOG_AI_REQUESTS=false` — trigger improve → **no** new `ai.log` lines; improve still works.
5. Force timeout / disconnect (optional) → `status=timeout` or `transport`; user still sees existing error message; `system.log` still has short error.

### Regression

- IndividualSendMailModal improve / undo / send — behavior unchanged.
- Settings › AiPrompts › Provider save / model list — unchanged.
- `system.log` still masks `$_POST.body`; still must not contain raw API key.

### Automated

```bash
docker compose exec -T app php tests/ai_request_logging_smoke.php
```

### Logs to watch

- `cache/logs/ai.log` — primary
- `cache/logs/system.log` — no key leak; no full prompt dump; existing short AI errors remain

### Grep verification

```bash
rg -n 'chatCompletions\(|listChatModels\(' src --glob '*.php'
```

Every call must pass `RequestContext`.

---

## Rollback plan

1. Revert the commit / redeploy previous code.
2. Delete or ignore `cache/logs/ai.log` (no DB).
3. Remove `LOG_AI_REQUESTS` from `config/debug.php` if desired (harmless if left).
4. Downtime: none. Data loss: none (log file only).

---

## Edge cases

| Case | Handling |
|------|----------|
| `ai.log` not writable | One `Log::error`; AI result unchanged |
| Huge body (~200 KB+) | Log full text after `data:` redact; disk growth accepted while flag on |
| Concurrent improves | `LOCK_EX` on append; blocks may interleave at OS level — UUID demarcation required |
| Empty assistant content | Log `status` from HTTP layer; existing `LBL_AI_EMPTY_RESPONSE` path unchanged |
| Exception before HTTP | No `ai.log` exchange (nothing sent); optional one-liner not required |
| Missing `RequestContext` | Not allowed — signature enforces |

---

## Decision rationale & tradeoffs

| Topic | Choice | Why |
|-------|--------|-----|
| Dedicated file vs `system.log` | `ai.log` | Payloads are large; `LOG_LEVELS` drops info; mail bodies already masked in FileTarget |
| File vs DB audit | File | Fast to ship; matches http/sql debug logs; DB retention is a product decision |
| Yii Log target vs direct write | Direct `file_put_contents` | Same as `http-requests.log`; avoids fighting level filters |
| Redact `data:` only in log | Yes | Keeps logs readable; does not change model input (product still sends full HTML) |
| Required `RequestContext` | Yes | Forces labeled actions; no silent `unknown` pile-up |
| Default flag `true` | Dev-friendly | Prod must set `false` consciously — document in config comment |

**Rejected alternatives**

| Alternative | Why rejected |
|-------------|--------------|
| Log only in `ImproveMailService` | Misses Provider model list and future AI actions |
| Always-on without flag | Prod disk / privacy risk |
| Full dump into `system.log` | Noise + conflicts with masking |
| DB table in same CR | Scope creep; no UI consumer yet |

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| PII / mail content on disk in `ai.log` | Med | Flag off on prod; file under `cache/logs/` (not public web root); same trust as other logs |
| Disk growth from large HTML | Med | Redact `data:`; flag; ops truncate/rotate manually (no rotation code required in CR — note in docs) |
| Logger bug breaks improve | Low | try/catch around log write only; never catch OpenAI exceptions there |
| Accidental API key in error body from OpenAI | Low | Existing sanitize + logger redact `sk-…` |
| Call site forgets context | Low | Required parameter; smoke grep |

---

## Deliverables checklist

- [x] Impact summary (+ empty delete list)
- [x] No DB migration
- [x] Implementation steps (ordered, with paths)
- [x] Testing checklist
- [x] Rollback plan
- [x] Rationale & tradeoffs
- [x] Risks
- [x] Decisions D1–D8 recorded
- [x] Implemented (dev)

---

## Follow-ups (not this CR)

1. Align OpenAI curl timeout with CR-2 A4 / JS 90 s; map timeout to dedicated user label.
2. Optional size guard / clearer error when body exceeds a soft limit.
3. Optional `s_yf_ai_request_log` audit + retention cron (mirror mail audit).
