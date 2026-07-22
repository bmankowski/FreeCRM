# Change Request: Settings › AiPrompts — katalog domyślnych promptów AI

**Status:** Implemented (dev)  
**Module:** `Settings/AiPrompts`  
**Follow-up:** CR-2 — wywołanie ChatGPT + różdżka w `IndividualSendMailModal` (`mail.improve`)

---

## Goal

Wprowadzić w FreeCRM **Settings module `AiPrompts`**, w którym admin definiuje **systemowe domyślne prompty** dla ustalonych czynności AI (np. poprawianie maila).

CR-1 dostarcza:

- katalog promptów (CRUD w Settings),
- stabilny rejestr akcji w kodzie (`action_key`),
- serwis **resolver** (system default → w przyszłości override użytkownika),
- seed promptu dla `mail.improve`,

bez wywołań OpenAI i bez przycisku w UI compose.

Docelowo (poza tym CR): każdy użytkownik może nadpisać prompt dla siebie; konsumenci (różdżka, inne akcje) wołają wyłącznie `PromptResolver`.

---

## Stance

- **No fallbacks** — brak promptu dla `action_key` → wyjątek / błąd widoczny; nie zwracamy pustego stringa „żeby UI się nie wywalił”.
- **Jeden model danych** od dnia 1: wiersz z `userid IS NULL` = systemowy; `userid` = ID użytkownika = override (CR późniejszy tylko dodaje UI preferencji, nie nową tabelę).
- **`action_key` tylko z rejestru PHP** — admin nie wymyśla dowolnych kluczy; wybiera z listy.
- **Settings, nie entity CRM** — to konfiguracja org, nie rekordy z ownerem/sharingiem.
- Brak `class_alias()`, brak równoległych ścieżek storage (blob `user_preferences` nie jest używany do promptów).

---

## Decisions (confirmed)

| # | Decision |
|---|----------|
| D1 | Scope CR-1 = **katalog + resolver** (bez ChatGPT, bez różdżki). |
| D2 | Klucz API OpenAI (CR-2) → **Settings UI** (wzorzec ApiAddress), w module `AiPrompts` (osobny widok Provider / Config). |
| D3 | Nazwa techniczna: **`AiPrompts`**. |
| D4 | Tabela admin: `s_yf_ai_prompts` (`s_#__ai_prompts` w kodzie). |
| D5 | `userid IS NULL` = system default; `userid` = ID użytkownika = override. Unikalność przez kolumnę generowaną `owner_scope` (MariaDB UNIQUE pozwala na wiele NULL). |
| D6 | Menu Settings: blok **`LBL_INTEGRATION`**. |
| D7 | Pierwsza akcja w rejestrze: **`mail.improve`** (seed + UI wyboru; brak konsumenta UI w CR-1). |

---

## Assumptions

| # | Assumption |
|---|------------|
| A1 | Admin Settings ma pełny dostęp; osobne profile/privileges dla AiPrompts nie są wymagane w CR-1 (jak typowe Settings CRUD). |
| A2 | Treść promptu to plain text / markdown-ish; bez HTML editora (textarea). |
| A3 | Placeholders to proste tokeny `{{name}}`; brak silnika szablonów (Smarty/Twig) w promptach. |
| A4 | Jeden wiersz per `(action_key, owner_scope)` — nie wersjonujemy historii promptów w CR-1. |
| A5 | CR-2 doda Provider config (API key, model) + endpoint Ajax + różdżkę; ten CR tylko przygotowuje kontrakt resolvera. |

---

## Impact

### Observable vs internal

| Change | Observable |
|--------|------------|
| Nowa pozycja Settings › Integration › AI Prompts | Tak — admin UI |
| List / Edit / Detail promptów systemowych | Tak — admin UI |
| Seed `mail.improve` | Tak — widać po migracji |
| `PromptResolver` API | Internal (brak konsumenta UI w CR-1) |
| Provider / OpenAI / różdżka | **Nie** (CR-2) |

### Code being added

| Path | Role |
|------|------|
| `migrations/Users/mYYMMDD_000001_ai_prompts.php` | Tabela, settings field, seed |
| `src/Modules/Settings/AiPrompts/Models/Module.php` | Settings module model (`baseTable = s_#__ai_prompts`) |
| `src/Modules/Settings/AiPrompts/Models/Record.php` | CRUD rekordu promptu |
| `src/Modules/Settings/AiPrompts/Views/ListView.php` | Lista |
| `src/Modules/Settings/AiPrompts/Views/Edit.php` | Tworzenie / edycja |
| `src/Modules/Settings/AiPrompts/Views/Detail.php` | Podgląd |
| `src/Modules/Settings/AiPrompts/Actions/SaveAjax.php` | Zapis |
| `src/Modules/Settings/AiPrompts/Actions/DeleteAjax.php` | Usuwanie |
| `layouts/basic/modules/Settings/AiPrompts/*.tpl` | List / Edit / Detail |
| `public/layouts/basic/modules/Settings/AiPrompts/resources/*.js` (+ minify) | Form validation, action_key select |
| `src/Ai/Prompt/ActionRegistry.php` | Stałe `action_key` + metadane (label, placeholders) |
| `src/Ai/Prompt/PromptResolver.php` | `resolve(actionKey, userId): string` + `applyPlaceholders()` |
| `src/Ai/Prompt/PromptNotFoundException.php` | Brak promptu / nieaktywny |
| `languages/en_us/Settings/AiPrompts.json` | EN |
| `languages/pl_pl/Settings/AiPrompts.json` | PL |
| `languages/*/Settings/Vtiger.json` | Ewentualny klucz pozycji menu jeśli nie w module json |
| `tests/ai_prompts_resolver_smoke.php` | Smoke: seed + resolve + unique constraint |

### Code being modified

| Path | Change |
|------|--------|
| (opcjonalnie) `.cursor/rules/` — krótka notatka o AI prompts | Tylko jeśli po implementacji warto utrwalić kontrakt |

### Code being deleted

**Brak** — greenfield; nic nie zastępujemy.

### DB

**Add** `s_yf_ai_prompts` (patrz Data migration).

**Add** wiersz `vtiger_settings_field` pod `LBL_INTEGRATION`.

**No drops.**

### Module metadata

- Settings field only — **bez** `vtiger_tab` (to nie jest entity module).
- Po migracji: regeneracja uprawnień / menu nie jest krytyczna dla Settings field (cache requestowy), ale warto przeładować Settings.

### Language

- `languages/en_us/Settings/AiPrompts.json`
- `languages/pl_pl/Settings/AiPrompts.json`
- Klucze menu / help w sync EN↔PL.

### Cron / Webservices / Caches

- Cron: brak.
- Webservices: brak.
- Smarty: `rm -f cache/templates_c/*.php` po deployu UI.

### External consumers

- Brak w CR-1. CR-2 będzie pierwszym konsumentem (`IndividualSendMailModal`).

---

## Functional requirements

### Before → after

| Before | After |
|--------|-------|
| Brak AI / promptów w CRM | Admin zarządza promptami w Settings › AiPrompts |
| — | Kod może wywołać `PromptResolver::resolve('mail.improve', $userId)` i dostać treść |
| — | Override użytkownika (`userid IS NOT NULL`) jest wspierany w schemacie, ale **bez UI** w CR-1 |

### Business rules

1. `action_key` musi należeć do `ActionRegistry::all()`.
2. Dla `userid IS NULL` (system): co najwyżej jeden wiersz na `action_key` (UNIQUE na `owner_scope`).
3. Nieaktywny prompt (`active = 0`) **nie** jest zwracany przez resolver (traktowany jak brak → wyjątek), chyba że jawnie wywołano tryb admin preview (tylko Edit/Detail).
4. Usunięcie systemowego promptu dla akcji, która jest w rejestrze, jest dozwolone, ale resolver rzuci wyjątek do czasu ponownego utworzenia — **bez** cichego fallbacku do hardcodowanego tekstu w PHP.
5. CR-1 UI pokazuje / edytuje **tylko** `userid IS NULL`. Wiersze użytkowników (gdy pojawią się później) nie występują na liście admina.

### Validation

- `action_key` — required, z rejestru.
- `name` — required, max 255.
- `prompt_body` — required, non-empty after trim.
- `active` — 0/1.
- Duplikat systemowego `(action_key, userid IS NULL)` → błąd walidacji przy save (UNIQUE `owner_scope`).

### Placeholders (kontrakt dla CR-2)

Zdefiniowane per akcja w `ActionRegistry`. Dla `mail.improve`:

| Token | Znaczenie |
|-------|-----------|
| `{{body}}` | Treść maila (HTML lub plain z contenteditable) |
| `{{subject}}` | Temat |

`PromptResolver::applyPlaceholders(string $template, array $vars): string` — brakujące klucze w `$vars` → wyjątek (nie puste podstawienie).

### In scope

- Tabela + migracja + seed `mail.improve`
- Settings CRUD (system prompts only)
- `ActionRegistry` + `PromptResolver`
- i18n EN/PL
- Smoke test

### Out of scope / future CR

| Item | Why deferred |
|------|----------------|
| OpenAI / ChatGPT HTTP call | D1 — osobny CR-2 |
| Różdżka w IndividualMail | Konsument UI; zależy od CR-2 |
| Settings › Provider (API key, model) | D2 potwierdzone; implementacja w CR-2 |
| UI „mój prompt” (My Preferences) | Schema gotowa; UI później |
| Historia wywołań / token usage | Niepotrzebne do katalogu |
| Wybór providera (OpenAI vs inny) | CR-2+ |
| Kolejne `action_key` poza `mail.improve` | Dodawane do rejestru gdy pojawi się konsument |

---

## Data migration

### Schema

```sql
CREATE TABLE s_yf_ai_prompts (
  id INT(11) NOT NULL AUTO_INCREMENT,
  action_key VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  prompt_body MEDIUMTEXT NOT NULL,
  userid INT(11) NULL DEFAULT NULL COMMENT 'NULL = system default',
  -- MariaDB UNIQUE allows multiple NULLs; IFNULL makes one system row per action_key
  owner_scope INT(11) AS (IFNULL(userid, 0)) STORED,
  active TINYINT(1) NOT NULL DEFAULT 1,
  createdtime DATETIME NOT NULL,
  modifiedtime DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ai_prompts_action_owner (action_key, owner_scope),
  KEY idx_ai_prompts_userid (userid),
  KEY idx_ai_prompts_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Resolver: najpierw wiersz z `userid = :currentUser`, potem `userid IS NULL`.

Kod PHP używa aliasu `s_#__ai_prompts` (jak MailSmtp → `s_yf_mail_smtp`).

### Seed (idempotent)

Jeden wiersz systemowy:

- `action_key` = `mail.improve`
- `name` = `Improve email`
- `userid` = `NULL`
- `active` = `1`
- `prompt_body` ≈:

```text
You are an assistant that improves business emails.
Rewrite the email to be clearer, more professional, and concise.
Keep the original language of the message.
Do not invent facts. Preserve meaning and any requested call to action.

Subject: {{subject}}

Email body:
{{body}}
```

Insert tylko jeśli brak wiersza `action_key = mail.improve AND userid IS NULL`.

### Settings menu (idempotent)

```php
\App\Modules\Settings\Base\Models\Module::addSettingsField('LBL_INTEGRATION', [
    'name' => 'LBL_AI_PROMPTS',
    'iconpath' => 'adminIcon-integration', // lub istniejący glyphicon / fa — zweryfikować w adminIcons.css
    'description' => 'LBL_AI_PROMPTS_DESCRIPTION',
    'linkto' => 'index.php?module=AiPrompts&parent=Settings&view=ListView',
]);
```

### Rollback SQL

```sql
DELETE FROM vtiger_settings_field WHERE name = 'LBL_AI_PROMPTS';
DROP TABLE IF EXISTS s_yf_ai_prompts;
```

Additive only → rollback = drop table + delete settings field. **Restore from backup** nie jest wymagany, o ile nikt nie polegał jeszcze na promptach w produkcji.

### Non-conforming rows

Greenfield — brak istniejących wierszy.

---

## Implementation plan

Każdy krok committowalny osobno; system pozostaje runnable.

### 1. Schema + seed + settings field

- `migrations/Users/mYYMMDD_000001_ai_prompts.php`
- Idempotent `safeUp` / `safeDown`

### 2. Domain: ActionRegistry + PromptResolver

- `src/Ai/Prompt/ActionRegistry.php`
- `src/Ai/Prompt/PromptResolver.php`
- `src/Ai/Prompt/PromptNotFoundException.php`
- Autoload PSR-4: namespace `FreeCRM\Ai\Prompt\` lub `App\Ai\Prompt\` — **dopasować do konwencji `src/`** przy implementacji (sprawdzić `composer.json` / istniejące top-level pod `src/`).

### 3. Settings module models + actions

- Wzorzec: `Settings/MailSmtp` (List/Edit/Detail/SaveAjax/DeleteAjax)
- `Record::getInstanceById`, save z walidacją `action_key` ∈ registry
- ListView filtr: `userid IS NULL` only

### 4. Views + templates + JS

- `layouts/basic/modules/Settings/AiPrompts/`
- Edit: select `action_key` z rejestru, textarea `prompt_body`, checkbox `active`, read-only lista placeholders dla wybranej akcji
- `npm run minify-js -- public/layouts/basic/modules/Settings/AiPrompts/resources/Edit.js` (i inne nowe JS)

### 5. Languages

- `en_us` + `pl_pl` Settings/AiPrompts.json — pełny sync kluczy

### 6. Smoke test

- `tests/ai_prompts_resolver_smoke.php`:
  - resolve `mail.improve` → non-empty
  - resolve unknown key → exception
  - inactive → exception
  - applyPlaceholders z brakującym `body` → exception
  - applyPlaceholders happy path

### 7. Legacy deletion

**N/A** — greenfield.

---

## Testing

### Manual smoke

1. Migracja: `docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0`
2. Settings › Integration › AI Prompts → lista z seedem `mail.improve`
3. Edit seedu → zapisz → Detail pokazuje nową treść
4. Utwórz drugi prompt z tym samym `action_key` → błąd walidacji
5. Dezaktywuj prompt → smoke test / ad-hoc PHP: `PromptResolver` rzuca
6. Usuń i utwórz ponownie ten sam `action_key` → OK

### Regression

- Settings › MailSmtp, ApiAddress, Recruitment — menu Integration nadal działa
- IndividualSendMailModal — **bez zmian** w CR-1 (brak regresji UI)

### Automated

- `docker compose exec -T app php tests/ai_prompts_resolver_smoke.php`

### Logs

- `cache/logs/system.log` — brak `[error]` przy CRUD Settings

### Templates

- `rm -f cache/templates_c/*.php` po deployu UI

### Grep verification (po CR-2, nie tu)

- Konsumenci AI wołają tylko `PromptResolver`, nie czytają tabeli ad hoc.

---

## Rollback plan

1. Revert commit(s) z kodem Settings/AiPrompts + `src/Ai/Prompt/*`
2. `DROP TABLE s_yf_ai_prompts;` + delete `vtiger_settings_field` (`LBL_AI_PROMPTS`)
3. Downtime: brak (additive)
4. Data loss: tylko treści promptów wprowadzone po migracji (akceptowalne w CR-1)

---

## Edge cases

| Case | Handling |
|------|----------|
| Duplikat systemowego `action_key` | UNIQUE + walidacja UI/PHP |
| `userid IS NOT NULL` w CR-1 | Schema pozwala; ListView admina ich nie pokazuje; brak UI create dla user override |
| Usunięty seed / pusty katalog | Resolver → `PromptNotFoundException` |
| Przerywana migracja | Idempotent: CREATE IF NOT EXISTS / INSERT IF NOT EXISTS / settings field exists check |
| Nowy `action_key` w kodzie bez wiersza DB | Resolver fail — admin musi dodać prompt (świadomie) |
| Placeholders w seedzie vs registry | Seed musi używać wyłącznie tokenów zarejestrowanych dla akcji |

---

## Decision rationale & tradeoffs

| Approach | Pros | Cons | Verdict |
|----------|------|------|---------|
| Settings CRUD (`s_yf_*`) | Spójne z MailSmtp; admin-only; bez crmentity | Nie ma sharingu CRM | **Wybrane** |
| Entity module (`u_yf_*` + crmentity) | Sharing, owner | Overkill dla config; mylące dla user override | Odrzucone |
| PHP config file | Proste | Brak UI admina; trudniejszy per-user | Odrzucone |
| `user_preferences` blob | Szybki override | Brak admin katalogu; nieprzejrzyste | Odrzucone |
| `userid NULL` vs `0` | NULL = system default (czytelniejsze) | UNIQUE wymaga `owner_scope` AS IFNULL | **`userid NULL` + `owner_scope`** |

Resolver w CR-1 bez konsumenta UI jest świadomy: unikamy łączenia konfiguracji z OpenAI/HTTP w jednym dużym CR; CR-2 podpina się pod gotowy kontrakt.

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Settings module wiring (ListView base) różni się od MailSmtp w detalach | Med | Trzymać się 1:1 MailSmtp; smoke UI zaraz po migracji |
| Namespace `src/Ai/` vs istniejący PSR-4 | Low | Sprawdzić `composer.json` przed pierwszym plikiem |
| Ikona Settings nieistniejąca → pusty glyph | Low | Wybrać istniejący `adminIcon-*` / `glyphicon-*` |
| Admin usunie jedyny prompt `mail.improve` przed CR-2 | Low | Akceptowalne; CR-2 pokaże błąd zamiast cichego fail |
| Przyszły wyciek API key w logach (CR-2) | Med (CR-2) | Nie logować raw key; maskować w Detail |

---

## CR-2 preview (nie implementować tutaj)

→ **[cr-ai-prompts-mail-improve.md](cr-ai-prompts-mail-improve.md)** (Provider + OpenAI + różdżka + undo).

---

## Deliverables checklist

- [x] Impact summary (+ empty delete list)
- [x] Schema / migration plan
- [x] Implementation steps
- [x] Testing checklist
- [x] Rollback plan
- [x] Rationale & tradeoffs
- [x] Risks
- [x] Decisions D1–D7 recorded

---

## Open items for implementation kickoff

1. ~~Namespace~~ — użyty `App\Ai\` (PSR-4 `App\` → `src/`).
2. ~~Ikona~~ — `adminIcon-integration`.
3. Seed prompt EN — OK.
