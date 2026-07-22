# Change Request: AI mail improve — OpenAI provider + różdżka w IndividualSendMailModal

**Status:** Implemented (dev)  
**Depends on:** [cr-ai-prompts.md](cr-ai-prompts.md) (CR-1 — katalog + `PromptResolver`)  
**Scope:** Provider Settings + ChatGPT call + wand on compose modal (z cofaniem)

---

## Goal

Po naciśnięciu **różdżki** w `IndividualSendMailModal` CRM:

1. bierze temat + HTML treści maila,
2. ładuje prompt `mail.improve` przez `PromptResolver` (z override użytkownika gdy kiedyś powstanie),
3. wywołuje **OpenAI Chat Completions**,
4. podmienia treść w contenteditable,
5. pozwala **cofnąć** jedną poprawkę (przywrócenie poprzedniego HTML).

Admin konfiguruje **API key** (zamaskowany) i **model** w Settings › AiPrompts › Provider.

---

## Stance

- **No fallbacks** — brak klucza / brak promptu / błąd API → komunikat użytkownikowi; nie zostawiamy starej treści „po cichu” jako sukcesu.
- Jedyny klient OpenAI w CRM: `App\Ai\OpenAi\Client` (lub równoważna nazwa). Brak równoległych curlów w Actions.
- Konsumenci UI wołają wyłącznie Ajax action → serwis; nie czytają tabeli promptów / providera ad hoc.
- Klucz API **nigdy** w logach (`system.log` maskuje body/content; klucz też nie trafia do logów).
- `class_alias()` zabronione.

---

## Decisions (confirmed)

| # | Decision |
|---|----------|
| D1 | Scope = Provider + różdżka + OpenAI (**bez** UI „mój prompt”). |
| D2 | Model domyślny: **`gpt-5-nano`** (najtańszy chat model na [cenniku OpenAI](https://developers.openai.com/api/docs/pricing) stan 2026-07; admin może zmienić w Provider). |
| D3 | Po kliknięciu: **od razu podmiana** treści + loader; **jednopoziomowe cofanie** (przywróć HTML sprzed poprawki). |
| D4 | Do modelu idzie **HTML** z contenteditable (bez strip). |
| D5 | API key: tylko admin; w UI **zamaskowany** po zapisie (jak hasło). |
| D6 | Endpoint: OpenAI **Chat Completions** (`https://api.openai.com/v1/chat/completions`). |
| D7 | Undo tylko po stronie klienta (session w modalu); brak historii w DB. |

---

## Assumptions

| # | Assumption |
|---|------------|
| A1 | CR-1 wdrożony (tabela + seed `mail.improve` + resolver). |
| A2 | Jedna poprawka naraz; kolejne kliknięcie różdżki nadpisuje bufor undo (ostatnia poprzednia treść). |
| A3 | Odpowiedź modelu traktujemy jako HTML fragment do wstawienia w `.js-mail-content` (bez opakowania w pełny dokument). Prompt seed powinien to wymuszać; CR-2 może doprecyzować instruction w seedzie / system message. |
| A4 | Timeout HTTP ~60 s; dłuższe → błąd widoczny. |
| A5 | Brak streamingu w CR-2 (jedna odpowiedź JSON). |
| A6 | Temat maila **nie** jest zmieniany przez AI (tylko body); `{{subject}}` jest kontekstem w prompcie. |

---

## Impact

### Observable vs internal

| Change | Observable |
|--------|------------|
| Settings › AiPrompts › Provider (API key, model) | Tak — admin |
| Różdżka + Undo w IndividualSendMailModal | Tak — compose UX |
| Wywołanie OpenAI | External API |
| PromptResolver / tabela promptów | Bez zmian kontraktu (reuse) |

### Code being added

| Path | Role |
|------|------|
| `migrations/Users/mYYMMDD_000001_ai_provider.php` | Tabela `s_yf_ai_provider` + seed model + settings field / submenu |
| `src/Ai/OpenAi/Client.php` | HTTP Chat Completions |
| `src/Ai/OpenAi/OpenAiException.php` | Błędy API / config |
| `src/Ai/Mail/ImproveMailService.php` | resolve prompt → placeholders → OpenAI → HTML |
| `src/Modules/Settings/AiPrompts/Views/Provider.php` | Settings UI |
| `src/Modules/Settings/AiPrompts/Actions/SaveProviderAjax.php` | Zapis key/model |
| `src/Modules/Settings/AiPrompts/Models/ProviderConfig.php` | Read/write config (mask key) |
| `layouts/basic/modules/Settings/AiPrompts/Provider.tpl` | Formularz |
| `public/.../Settings/AiPrompts/resources/Provider.js` (+ minify) | Save Ajax |
| `src/Modules/Base/Actions/AiImproveMail.php` (lub `Mail` mode) | Ajax: subject + body → improved HTML |
| Zmiany w `IndividualSendMailModal.tpl` | Przycisk różdżki + undo przy edytorze |
| Zmiany w `Vtiger.js` (+ minify) | Click → Ajax → replace; undo buffer |
| `languages/*/Settings/AiPrompts.json` | Provider + błędy |
| `languages/*/Vtiger.json` (js) | LBL/JS dla różdżki / undo / błędów |
| `tests/ai_improve_mail_smoke.php` | Resolver + mock/config validation (bez real OpenAI w CI) |

### Code being modified

| Path | Change |
|------|--------|
| `layouts/basic/modules/Base/IndividualSendMailModal.tpl` | Toolbar przy body: improve + undo |
| `public/layouts/basic/modules/Base/resources/Vtiger.js` | `registerSendMailModalView` — AI handlers |
| `public/layouts/basic/modules/Base/resources/Vtiger.min.js` | minify |
| Opcjonalnie seed prompt w migracji CR-2 | Dopisek: „Return only the improved HTML body, no markdown fences” |

### Code being deleted

**Brak.**

### DB

```sql
CREATE TABLE s_yf_ai_provider (
  id INT(11) NOT NULL AUTO_INCREMENT,
  provider VARCHAR(32) NOT NULL DEFAULT 'openai',
  api_key VARCHAR(512) NULL DEFAULT NULL,
  model VARCHAR(64) NOT NULL DEFAULT 'gpt-5-nano',
  modifiedtime DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ai_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Seed: jeden wiersz `provider=openai`, `api_key=NULL`, `model=gpt-5-nano`.

Settings: drugi link pod Integration / AiPrompts — `view=Provider`  
(albo zakładka na liście AiPrompts — preferencja: osobny settings field `LBL_AI_PROVIDER` obok `LBL_AI_PROMPTS`).

### Language / cron / webservice

- EN + PL sync.
- Cron: brak.
- Webservice: brak.
- Smarty cache wipe po deployu tpl.

---

## Functional requirements

### Before → after

| Before | After |
|--------|-------|
| Compose bez AI | Różdżka poprawia treść przez OpenAI |
| Brak miejsca na klucz API | Admin ustawia key + model w Provider |
| — | Undo przywraca HTML sprzed ostatniej poprawki |

### Business rules

1. Różdżka widoczna tylko gdy sekcja preview jest widoczna (jest treść do edycji).
2. Pusty body → błąd walidacji (nie wołaj API).
3. Brak `api_key` → błąd: skonfiguruj Provider (bez wywołania HTTP).
4. Brak / nieaktywny prompt `mail.improve` → `PromptNotFoundException` → komunikat.
5. Sukces: `.js-mail-content` = odpowiedź; sync do `.js-mail-content-input`.
6. Undo: przywraca poprzedni HTML; disable gdy brak bufora; po undo czyść bufor (jedno cofnięcie).
7. Drugie improve przed undo: bufor = treść sprzed **tego** improve (nie stos).
8. Detail Provider: klucz pokazany jako `••••` + opcjonalne „zmień klucz”; puste pole przy zapisie = **nie nadpisuj** istniejącego klucza.

### Validation

- `api_key`: przy pierwszym zapisie required; przy update — optional (keep).
- `model`: required, non-empty string (admin free-text lub select z krótką listą + custom).
- Ajax improve: `subject` string, `body` HTML string non-empty after trim/strip tags check? → non-empty HTML (nawet `<br>` OK jeśli użytkownik coś wpisał; całkowicie pusty string → reject).

### In scope

- Provider CRUD (single row openai)
- OpenAI client + ImproveMailService
- Ajax + różdżka + undo
- i18n, smoke (bez live key w teście)

### Out of scope

| Item | Why |
|------|-----|
| UI per-user prompt override | D1 |
| Streaming | Złożoność UI |
| Zmiana tematu przez AI | A6 |
| Multi-provider (Anthropic…) | Później |
| Usage / token accounting DB | Później |
| Historia poprawień serwerowa | D7 |

---

## UX (compose)

Przy `.js-mail-content` (nad lub pod edytorem, spójnie z attachment toolbar):

- Przycisk **Popraw AI** (glyphicon-magic / różdżka) — `js-ai-improve-mail`
- Przycisk **Cofnij** — `js-ai-improve-undo`, disabled dopóki brak bufora
- Podczas requestu: progress na modal / disable różdżki; po błędzie toast/alert z komunikatem

Flow JS:

1. Zapisz `previousHtml = contentEditor.html()`
2. POST Ajax (`module=Base&action=AiImproveMail` lub mode na istniejącym Mail action)
3. On success: `contentEditor.html(result.html)`; sync hidden input; `undoHtml = previousHtml`; enable Undo
4. On undo: restore `undoHtml`; clear buffer; disable Undo

---

## OpenAI request shape

```json
{
  "model": "gpt-5-nano",
  "messages": [
    {
      "role": "system",
      "content": "You improve business email HTML. Reply with HTML body only — no markdown code fences, no commentary."
    },
    {
      "role": "user",
      "content": "<resolved prompt with {{subject}} / {{body}} substituted>"
    }
  ]
}
```

Bez `temperature` — nowsze modele (m.in. `gpt-5-nano`) akceptują tylko domyślną wartość API.

Auth: `Authorization: Bearer {api_key}`.

Parse: `choices[0].message.content`; strip opcjonalnych ```html fences jeśli model je doda mimo instrukcji.

---

## Data migration

- Additive table + settings field + seed model.
- Rollback: `DROP TABLE s_yf_ai_provider`; delete settings field; revert code.
- Klucz API w DB plaintext (jak hasła SMTP w projekcie) — akceptowalne w CR-2; szyfrowanie = follow-up jeśli pojawi się standard.

---

## Implementation plan

1. Migracja `s_yf_ai_provider` + settings field `LBL_AI_PROVIDER`
2. `ProviderConfig` + `OpenAi\Client` + `ImproveMailService`
3. Settings view Provider + SaveProviderAjax + JS/tpl/i18n
4. Ajax `AiImproveMail` (permission: zalogowany user z dostępem do compose — nie tylko admin)
5. Modal tpl + `Vtiger.js` (wand + undo) + minify
6. Opcjonalna aktualizacja seed prompt / system instruction
7. Smoke test (config missing, empty body, placeholder path z mock client jeśli da się wstrzyknąć)

---

## Testing

### Manual

1. Bez API key → różdżka → czytelny błąd.
2. Ustaw key + `gpt-5-nano` → improve na przykładowym HTML → treść się zmienia.
3. Undo → wraca poprzednia treść; Undo disabled.
4. Improve → Improve ponownie → Undo wraca do wersji sprzed **ostatniego** improve.
5. Wyślij mail po improve — treść poprawiona idzie w send (sync hidden input).
6. Provider: zapisz key → odśwież → pole zamaskowane; zapisz tylko model bez key → key zostaje.

### Regression

- IndividualSendMailModal preview/send/attachments bez AI — bez regresji.
- Settings AiPrompts list/edit — bez regresji.

### Automated

```bash
docker compose exec -T app php tests/ai_improve_mail_smoke.php
```

- Brak key → service throws
- applyPlaceholders path nadal OK
- (opcjonalnie) Client z mock HTTP nie w CR-2 jeśli brak infrastruktury mock — wtedy tylko unit-like config checks

### Logs

- `cache/logs/system.log` — brak raw API key; brak pełnego mail body jeśli już maskowane.

---

## Rollback plan

1. Revert code.
2. `DROP TABLE s_yf_ai_provider;` + delete `LBL_AI_PROVIDER`.
3. Brak destrukcji danych biznesowych.
4. Koszt: ewentualne wywołania OpenAI już opłacone — nieodwracalne.

---

## Edge cases

| Case | Handling |
|------|----------|
| Model usunięty z OpenAI | Błąd API → komunikat; admin zmienia model |
| Timeout / 429 | Komunikat; treść bez zmian |
| Odpowiedź z ```html | Strip fences |
| User nie-admin klika różdżkę | Dozwolone (używa org key) |
| Modal zamknięty w trakcie requestu | Ignore late response (sequence counter jak preview) |
| Concurrent double-click | Disable button na czas requestu |

---

## Decision rationale

| Topic | Choice | Why |
|-------|--------|-----|
| Model | `gpt-5-nano` | Najtańszy chat na oficjalnym cenniku 2026-07; wystarczy do rewrite maila; admin może podnieść |
| Chat Completions | vs Responses API | Prostszy, powszechny, wystarczający |
| Undo client-side | vs DB | Zero schematu; wystarcza UX „oj, cofnij” |
| HTML in/out | vs plain | Compose jest HTML; unikamy round-trip strip |

**Uwaga model:** `gpt-5.4-nano` jest nowszy w linii nano, ale droższy (~4× input). Domyślnie nano najtańszy; lista sugerowanych w Provider: `gpt-5-nano`, `gpt-5.4-nano`, `gpt-4o-mini`.

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Model ID `gpt-5-nano` niedostępny na danym koncie API | Med | Komunikat błędu API; admin zmienia model |
| Wyciek API key w logach / JS | High | Nigdy nie zwracaj raw key w Ajax/Detail; mask; nie loguj headers |
| HTML injection z odpowiedzi modelu | Med | Wstawianie do contenteditable jak dziś preview template — ten sam trust boundary co szablony maili |
| Koszty OpenAI przy spam klikaniu | Low | Disable during request; brak batch |
| Undo mylone z historią przeglądarki | Low | Osobny przycisk Cofnij w modalu |

---

## CR-3 preview (nie implementować w CR-2)

→ **[cr-ai-request-logging.md](cr-ai-request-logging.md)** — pełne logowanie request/response OpenAI do `cache/logs/ai.log`.

---

## Deliverables checklist

- [x] Impact
- [x] Schema
- [x] Implementation steps
- [x] Testing
- [x] Rollback
- [x] Rationale
- [x] Risks
- [x] Decisions D1–D7
