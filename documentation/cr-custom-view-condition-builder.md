# Change Request: Custom View ConditionBuilder (port z YetiForce)

## Goal

Zastąpić legacy edytor filtrów list (dwie sztywne sekcje „Wszystkie warunki” / „Dowolny warunek” + ukryte `column_condition`) **ConditionBuilderem** z YetiForce: przełącznik **AND | OR**, **Dodaj warunek**, **Dodaj grupę** (zagnieżdżone podgrupy), zapis w drzewie warunków i wykonanie zapytania zgodne z tym drzewem.

Referencja implementacji: gałąź `developer` repozytorium [YetiForceCompany/YetiForceCRM](https://github.com/YetiForceCompany/YetiForceCRM) (archiwum 2025, ostatnia wersja open source ~6.5).

---

## Stance

- **Jeden model warunków** dla CustomView — drzewo `{ condition, rules[] }`. Bez równoległego odczytu `vtiger_cvadvfilter` po migracji.
- **Usunąć** w tej samej zmianie: `transformToNewAdvancedFilter()`, bucket `conditionsAnd`/`conditionsOr` dla CustomView w `QueryGenerator::parseAdvFilter()`, użycie `AdvanceFilter.tpl` w edytorze CustomView.
- **Nie** portować w tym CR: `advanced_conditions` (filtry po rekordach powiązanych), `u_yf_cv_duplicates`, pełnego pakietu `App\Conditions\QueryFields\*` — osobne CR-y.
- **Reports / Workflows / DocumentTemplates** nadal używają `AdvanceFilter.tpl` — poza zakresem; nie blokują migracji CustomView (osobne tabele: `vtiger_relcriteria` vs `vtiger_cvadvfilter`).

---

## Część A — Jak to działa w YetiForce (referencja)

### A.1 Ewolucja modelu

| Era | UI | Storage | Query |
|-----|-----|---------|-------|
| Stary vtiger / FreeCRM (teraz) | `AdvanceFilter.tpl`: 2 sekcje ALL/ANY | `vtiger_cvadvfilter` + `vtiger_cvadvfilter_grouping`, `condition_expression` | `QueryGenerator`: grupa 1 → AND, grupa 2+ → OR; **ignoruje** `column_condition` |
| YetiForce 6.x+ | `ConditionBuilder*.tpl` | `u_yf_cv_condition_group` + `u_yf_cv_condition` | `CustomView::getConditions()` + `QueryGenerator::parseConditions()` rekurencyjnie |

W YetiForce `getAdvFilterByCvid()` **nie występuje** w `app/CustomView.php` — CustomView w całości przeszedł na nowy model.

### A.2 UI — komponent ConditionBuilder

**Pliki szablonów** (`layouts/basic/modules/Vtiger/`):

| Plik | Rola |
|------|------|
| `ConditionBuilder.tpl` | Korzeń: szablon ukryty + instancja główna z `$ADVANCE_CRITERIA` |
| `ConditionBuilderGroup.tpl` | Jedna grupa: toggle AND/OR, przyciski, lista dzieci |
| `ConditionBuilderRow.tpl` | Wiersz: pole, operator, widget wartości |
| `ConditionBuilder/*.tpl` | Widgety wartości per typ (Date, Picklist, Owner, Tree, …) |

**Nagłówek grupy** (`ConditionBuilderGroup.tpl`):

- Przełącznik **AND | OR** (Bootstrap `btn-group`, klasa aktywna na `.js-condition-switch-value`).
- **Dodaj warunek** (`.js-condition-add`) — AJAX ładuje nowy wiersz.
- **Dodaj grupę** (`.js-group-add`) — klonuje ukryty szablon podgrupy.
- **Usuń grupę** (`.js-group-delete`) — tylko gdy `ROOT_ITEM=false` (korzeń bez kosza).

**Rekurencja w szablonie:** w pętli `rules` — jeśli element ma klucz `condition`, `{include ConditionBuilderGroup.tpl}`; w przeciwnym razie `ConditionBuilderRow.tpl`.

**JS** (`public_html/.../ConditionBuilder.js`, klasa `Vtiger_ConditionBuilder_Js`):

- `registerAddCondition()` — POST do `view=ConditionBuilder` (domyślnie `row`), append do `.js-condition-builder-conditions-container`.
- `registerAddGroup()` — klon `.js-condition-builder-group-template`.
- `readCondition(container)` / `getConditions()` — buduje JSON drzewa z DOM.
- Zmiana pola/operatora — AJAX przeładowuje wiersz (nowy widget wartości).

**Integracja CustomView** (`CustomView/EditView.tpl`):

```smarty
{include file='ConditionBuilder.tpl' MODULE_NAME=$SOURCE_MODULE}
```

**JS zapisu** (`CustomView.js`):

```javascript
$('#advfilterlist').val(JSON.stringify(this.advanceFilterInstance.getConditions()));
```

Edytor filtra otwierany w **modalu** (`app.showModalWindow`).

### A.3 Format JSON (kontrakt UI ↔ backend)

```json
{
  "condition": "AND",
  "rules": [
    {
      "fieldname": "status:Candidates",
      "operator": "e",
      "value": "Aktywny"
    },
    {
      "condition": "OR",
      "rules": [
        { "fieldname": "cv_text:Candidates", "operator": "c", "value": "bank" },
        { "fieldname": "cv_text:Candidates", "operator": "c", "value": "SCRUM" }
      ]
    }
  ]
}
```

**`fieldname`:**

- Pole modułu bazowego: `{fieldName}:{moduleName}` — np. `status:Candidates`.
- Pole modułu powiązanego (reference): `{fieldName}:{relatedModule}:{sourceFieldName}` — np. `accountname:Accounts:account_id`.

**Operatory:** kody z `App\Condition::STANDARD_OPERATORS` (`e`, `n`, `c`, `om`, `last120days`, …).

### A.4 Schemat bazy (YetiForce)

```sql
CREATE TABLE `u_yf_cv_condition_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cvid` int(10) DEFAULT NULL,
  `condition` varchar(3) DEFAULT NULL,      -- 'AND' | 'OR'
  `parent_id` int(10) DEFAULT NULL,         -- 0 = korzeń logiczny; FK do parent group.id
  `index` tinyint(5) DEFAULT NULL,          -- kolejność w rodzicu
  PRIMARY KEY (`id`),
  KEY `u_yf_cv_condition_group_cvid_idx` (`cvid`),
  CONSTRAINT ... FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE
);

CREATE TABLE `u_yf_cv_condition` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned DEFAULT NULL,
  `field_name` varchar(50) DEFAULT NULL,
  `module_name` varchar(25) DEFAULT NULL,
  `source_field_name` varchar(50) DEFAULT NULL,
  `operator` varchar(20) DEFAULT NULL,
  `value` text DEFAULT NULL,
  `index` tinyint(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT ... FOREIGN KEY (`group_id`) REFERENCES `u_yf_cv_condition_group` (`id`) ON DELETE CASCADE
);
```

Opcjonalnie (YetiForce): kolumna `vtiger_customview.advanced_conditions` (JSON) — filtry po relacjach; **poza zakresem tego CR**.

### A.5 Zapis — `CustomView/models/Record.php`

Przy create/update:

1. `DELETE FROM u_yf_cv_condition_group WHERE cvid = ?` (CASCADE czyści `u_yf_cv_condition`).
2. `addGroup($advfilterlist, parentId=0, index=0)` rekurencyjnie:
   - INSERT grupy → `$parentId = lastInsertId`
   - Dla każdego elementu `rules`:
     - ma `condition` → `addGroup($rule, $parentId, $index)`
     - else → `addCondition($rule, $parentId, $index)` z normalizacją wartości przez UiType (`getDbConditionBuilderValue`).

Stare tabele `vtiger_cvadvfilter*` **nie są** zapisywane w nowym flow.

### A.6 Odczyt — `App\CustomView::getConditions($cvid)`

1. JOIN `u_yf_cv_condition_group` LEFT JOIN `u_yf_cv_condition`, ORDER BY `parent_id`, `index`.
2. Budowa drzewa przez referencje PHP (`&$referenceGroup`, `&$referenceParent`) — algorytm sortuje w `sortConditions()`.
3. Cache: `CustomView_GetConditions`.

**EditAjax** assign: `$customViewModel->getConditions()` → `$ADVANCE_CRITERIA` (nie `transformToNewAdvancedFilter()`).

### A.7 Wykonanie zapytania — `App\QueryGenerator`

**Inicjalizacja filtra listy** (`initForCustomViewById`):

```php
$this->conditions = CustomView::getConditions($viewId);
```

**WHERE** (`loadWhere`):

```php
$this->query->andWhere($this->parseConditions($this->conditions));
```

**Parser rekurencyjny:**

```php
private function parseConditions(?array $conditions): array {
    $where = [$conditions['condition']];  // 'AND' | 'OR'
    foreach ($conditions['rules'] as $rule) {
        if (isset($rule['condition'])) {
            $where[] = $this->parseConditions($rule);
        } else {
            $where[] = $this->getCondition($fieldName, $value, $operator);
            // lub getRelatedCondition() dla pól reference
        }
    }
    return $where;
}
```

Wynik to tablica Yii2 `['AND', cond1, ['OR', cond2, cond3], …]` — bez bucketów `conditionsAnd`/`conditionsOr` dla CustomView.

**Uwaga:** YetiForce nadal używa bucketów dla innych źródeł (inline list search); CustomView idzie przez `$this->conditions`.

### A.8 Widok AJAX wiersza — `modules/Vtiger/views/ConditionBuilder.php`

- `row` — render `ConditionBuilderRow.tpl` (zmiana pola/operatora).
- `builder` — render całego `ConditionBuilder.tpl` (np. advanced conditions modal).

Parametry: `sourceModuleName`, opcjonalnie `fieldname`, `operator`, `relatedModuleSkip`.

### A.9 Warstwa `App\Condition`

Centralna klasa (`app/Condition.php`):

- Stałe operatorów, dat, operatorów bez wartości.
- `getConditionsFromRequest()` — normalizacja wartości z POST.
- `checkConditions()` — walidacja rekordu (workflow, data access) przez `App\Conditions\RecordFields\*`.

Dla **CustomView list query** wystarczy podzbiór: stałe operatorów + normalizacja zapisu. Pełny port `RecordFields` / `QueryFields` — osobny CR (Workflows skorzysta później).

### A.10 Stan FreeCRM vs YetiForce (luka)

| Element | FreeCRM | YetiForce 6.5 |
|---------|---------|---------------|
| Tabele `u_yf_cv_condition*` | brak | tak |
| UI ConditionBuilder | brak | tak |
| `getConditions()` | brak | tak |
| `parseConditions()` w QueryGenerator | brak | tak |
| Wiersze w `vtiger_cvadvfilter` | 17 | legacy / puste dla nowych filtrów |
| Filtry z warunkami | ~15 / 166 | — |

---

## Impact

### Code being modified

| Path | Change |
|------|--------|
| `migrations/Users/m260709_000001_cv_condition_builder_schema.php` | CREATE `u_yf_cv_condition_group`, `u_yf_cv_condition` |
| `migrations/Users/m260709_000002_migrate_cvadvfilter_to_condition_tree.php` | migracja danych z `vtiger_cvadvfilter*` |
| `src/View/CustomView.php` | `getConditions($id)`, usuń / zdeprecjonuj `getAdvFilterByCvid` dla list |
| `src/Modules/CustomView/Models/Record.php` | `getConditions()`, `addGroup()`, `addCondition()`, nowy `setConditionsForFilter()`; usuń stary zapis do `vtiger_cvadvfilter` |
| `src/Modules/CustomView/Actions/Save.php` | odbiór drzewa JSON z POST |
| `src/Modules/CustomView/Views/EditAjax.php` | `$ADVANCE_CRITERIA = getConditions()` |
| `src/QueryField/QueryGenerator.php` | `$conditions`, `parseConditions()`, `loadWhere()` dla CustomView |
| `src/Condition/Condition.php` (nowy) | stałe operatorów, `getConditionsFromRequest()` — wycinek z YF `App\Condition` |
| `src/Modules/Base/Views/ConditionBuilder.php` (nowy) | AJAX row/builder |
| `layouts/basic/modules/Base/ConditionBuilder*.tpl` | port szablonów (namespace FreeCRM) |
| `layouts/basic/modules/Base/ConditionBuilder/*.tpl` | widgety wartości — fazowo: Picklist, Date, DateTime, Owner, String (reszta w follow-up) |
| `layouts/basic/modules/CustomView/EditView.tpl` | zamiana `AdvanceFilter.tpl` → `ConditionBuilder.tpl` |
| `public/layouts/basic/modules/Base/resources/ConditionBuilder.js` | port + minify |
| `public/layouts/basic/modules/CustomView/resources/CustomView.js` | `Vtiger_ConditionBuilder_Js`, zapis `getConditions()` |
| `src/Modules/Base/Models/Field.php` | `getRecordOperators()` / `getQueryOperators()` / `getOperatorTemplateName()` jeśli brak |
| `src/ModuleManagement/Services/FilterService.php` | zapis warunków → nowe tabele |
| `src/Modules/Settings/LayoutEditor/Models/Field.php` | przy rename/delete pola: też `u_yf_cv_condition` |
| `languages/en_us/Vtiger.json`, `languages/pl_pl/Vtiger.json` | `LBL_ADD_CONDITION_GROUP`, ewent. brakujące operatory |

### Code being deleted (w tym CR)

| Path / symbol | Powód |
|---------------|--------|
| `Record::transformToNewAdvancedFilter()` | zastąpione przez `getConditions()` |
| `CustomView::getAdvFilterByCvid()` (użycie w list QueryGenerator) | zastąpione przez `getConditions()` |
| `QueryGenerator::parseAdvFilter()` wywołanie z `initForCustomViewById` | zastąpione `parseConditions($this->conditions)` |
| `{include AdvanceFilter.tpl}` w `CustomView/EditView.tpl` | zastąpione ConditionBuilder |
| Zapis/odczyt `vtiger_cvadvfilter*` w `CustomView/Models/Record.php` | zastąpione `u_yf_cv_condition*` |

**Nie usuwać jeszcze:** tabel `vtiger_cvadvfilter` / `vtiger_cvadvfilter_grouping` (DROP w osobnym kroku po weryfikacji migracji i grep = 0 referencji).

### Database

**Dodane:**

- `u_yf_cv_condition_group` (jak YF)
- `u_yf_cv_condition` (jak YF)

**Modyfikowane dane:**

- ~17 wierszy `vtiger_cvadvfilter` → drzewo w nowych tabelach
- ~44 wiersze `vtiger_cvadvfilter_grouping` → mapowanie 2 grup YF (ALL/ANY) na drzewo:
  - grupa 1 (`and`) → korzeń AND z wierszami
  - grupa 2 z wierszami → podgrupa OR pod korzeniem (lub osobna gałąź OR — patrz migracja)

**Observable:** UI edytora filtra, wyniki list po zapisie filtra z warunkami złożonymi.

**Internal-only:** struktura POST `advfilterlist`, cache keys.

### Out of scope (future CR)

- `advanced_conditions` + `CustomView/AdvCondBody.tpl` (filtry po relacjach)
- `u_yf_cv_duplicates` (wykrywanie duplikatów w filtrze)
- Port ConditionBuilder do Reports (`vtiger_relcriteria`)
- Port do Workflows / DocumentTemplates (nadal `AdvanceFilter.tpl`)
- Pełny `App\Conditions\QueryFields\*` (80+ klas YF)
- Modal zamiast inline w `contentsDiv` (opcjonalny UX follow-up)

---

## Functional requirements

| ID | Before → After |
|----|----------------|
| F1 | Edytor filtra: 2 sekcje bez toggle → **AND \| OR** + **Dodaj warunek** + **Dodaj grupę** na każdym poziomie |
| F2 | `(A OR B) AND C` możliwe przez podgrupę OR wewnątrz grupy AND |
| F3 | Zapis filtra → drzewo w `u_yf_cv_condition*`; lista rekordów respektuje to samo drzewo |
| F4 | Istniejące filtry (~15 z warunkami) po migracji dają **identyczny** wynik listy jak przed CR (regresja na cvid 453, 545, …) |
| F5 | Filtr bez warunków → brak dodatkowego WHERE (puste `rules` = brak ograniczeń) |
| F6 | Operator `om` (zalogowany użytkownik) — wartość pusta w DB, działanie jak dziś |
| F7 | Pola reference w warunku (opcjonalnie faza 1.5): `{field}:{Module}:{sourceField}` jak YF |

### Business rules

- Korzeń drzewa: jedna grupa (`parent_id = 0` w YF to brak rodzica w INSERT — używamy `0` lub NULL spójnie).
- Kolejność `rules` = kolumna `index` (0-based).
- Pusty wiersz (pole `none` / operator `none`) — pomijany przy zapisie (`getConditions(skipEmpty=true)`).
- Usunięcie filtra (`cvid`) → CASCADE na grupy i warunki.

---

## Data migration

### Schema (migration 000001)

Identyczne DDL jak YetiForce (`scheme.sql` linie 2077–2104), prefiks `u_yf_` zgodny z FreeCRM.

### Data migration (migration 000002) — idempotentna

Algorytm dla każdego `cvid` z wierszami w `vtiger_cvadvfilter`:

1. Jeśli już istnieją wiersze w `u_yf_cv_condition_group` dla `cvid` → **skip** (idempotencja).
2. Wczytaj grupy z `vtiger_cvadvfilter_grouping` ORDER BY `groupid`.
3. Dla każdej grupy YF ze `columns` z `getAdvFilterByCvid` (legacy read — **tylko w migracji**):
   - **Grupa 1** (`group_condition=and`): utwórz korzeń `{ condition: 'AND', rules: [...] }` — wiersze po kolei `columnindex`; między wierszami: `column_condition` z DB (domyślnie `and`).
   - **Grupa 2+** z niepustymi wierszami: jeśli wiersze istnieją, dodaj jako **podgrupę OR** (lub AND jeśli `group_condition=and`) pod korzeniem — odwzorowanie dotychczasowego `(ALL...) OR (ANY...)`.
4. Rekurencyjny `addGroup()` zapisuje do nowych tabel.
5. **Nie** kasuj starych wierszy w tej migracji (backup); osobny skrypt cleanup po QA.

**Mapowanie operatorów/kolumn:** bez zmian — te same kody `comparator`, te same `columnname` rozbite na `field_name` / moduł z explode `:`.

### Rollback data

- Usuń wiersze `u_yf_cv_condition_group` WHERE cvid IN (zmigrowane).
- Stare `vtiger_cvadvfilter*` pozostają nietknięte do rollbacku kodu.

### Weryfikacja po migracji

```sql
SELECT cv.cvid, cv.viewname,
  (SELECT COUNT(*) FROM vtiger_cvadvfilter f WHERE f.cvid = cv.cvid) AS old_rows,
  (SELECT COUNT(*) FROM u_yf_cv_condition c
     JOIN u_yf_cv_condition_group g ON c.group_id = g.id WHERE g.cvid = cv.cvid) AS new_rows
FROM vtiger_customview cv
WHERE EXISTS (SELECT 1 FROM vtiger_cvadvfilter f WHERE f.cvid = cv.cvid);
```

---

## Implementation plan

### Krok 1 — Schema

- `migrations/Users/m260709_000001_cv_condition_builder_schema.php`
- `src/Modules/Install/install_schema/` — dopisać tabele dla fresh install

### Krok 2 — Backend core

- `src/Condition/Condition.php` — stałe + `getConditionsFromRequest()`
- `src/View/CustomView.php` — `getConditions($id)` (port algorytmu z YF)
- `src/Modules/CustomView/Models/Record.php` — `getConditions()`, `addGroup()`, `addCondition()`, nowy `setConditionsForFilter()`; usunąć stary insert do `vtiger_cvadvfilter`

### Krok 3 — QueryGenerator

- Właściwość `$conditions`
- `initForCustomViewById`: `$this->conditions = CustomView::getConditions($viewId)`
- `parseConditions()` + integracja w `loadWhere()`
- Usunąć wywołanie legacy `parseAdvFilter()` dla ścieżki CustomView

### Krok 4 — UI templates (minimal viable widgets)

Port plików:

- `ConditionBuilder.tpl`, `ConditionBuilderGroup.tpl`, `ConditionBuilderRow.tpl`
- Widgety: `ConditionBuilder/Base.tpl`, `Picklist.tpl`, `Date.tpl`, `Datetime.tpl` (DateTimeRange), `Owner.tpl`

Dostosować namespace: `\App\Modules\Base\...`, `\App\Layout::getTemplatePath` → odpowiednik FreeCRM (`vtemplate_path`).

### Krok 5 — AJAX view + JS

- `src/Modules/Base/Views/ConditionBuilder.php`
- `public/.../ConditionBuilder.js` — port klasy ES6 → dostosować do `AppConnector` FreeCRM
- `CustomView.js` — instancja builder, submit JSON

### Krok 6 — EditView + Save

- `EditView.tpl` — ConditionBuilder zamiast AdvanceFilter
- `EditAjax.php` — `$ADVANCE_CRITERIA = $customViewModel->getConditions()`
- `Save.php` — decode `advfilterlist` jako drzewo (bez `transformToNewAdvancedFilter`)

### Krok 7 — Migracja danych

- `m260709_000002_migrate_cvadvfilter_to_condition_tree.php`
- Skrypt weryfikacji: `tests/customview_conditions_migration_smoke.php`

### Krok 8 — Call sites cleanup

- `FilterService.php`, `LayoutEditor/Models/Field.php` — nowe tabele
- Grep: `getAdvFilterByCvid`, `transformToNewAdvancedFilter`, `vtiger_cvadvfilter` w `CustomView` — zero wyników

### Krok 9 — Języki + minify

- `LBL_ADD_CONDITION_GROUP` → PL: „Dodaj grupę warunków” (już w pl_pl jako `LBL_ADD_GROUP` — ujednolicić klucz)
- `npm run minify-js -- public/layouts/basic/modules/Base/resources/ConditionBuilder.js`
- `npm run minify-js -- public/layouts/basic/modules/CustomView/resources/CustomView.js`

### Krok 10 — Legacy delete

- Usunąć martwy kod (lista w sekcji Impact)
- **Opcjonalnie** po QA: migration DROP `vtiger_cvadvfilter`, `vtiger_cvadvfilter_grouping` + cleanup install_schema

---

## Testing

### Automatyczne

```bash
docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
docker compose exec -T app php tests/customview_conditions_migration_smoke.php
docker compose exec -T app php tests/customview_sort_smoke.php   # regresja sortu filtrów
```

Smoke migration: dla cvid 453, 545 porównaj COUNT listy przed/po (z tym samym użytkownikiem).

### Manualne

1. **Kandydaci → Lista → Utwórz filtr** — toggle AND/OR widoczny; dodaj podgrupę; zapisz.
2. **Filtr złożony** — `(CV zawiera X OR CV zawiera Y) AND Status = …` — poprawna liczba rekordów.
3. **Edycja istniejącego filtra** „Technical Product Owner” (cvid 453) — warunki wczytane, lista bez regresji.
4. **Operator daty** `last120days` na `createdtime`.
5. **Operator `om`** na assignee.
6. **Widget MiniList / ChartFilter** z `viewname=` — lista respektuje warunki.

### Logi

`tail -f cache/logs/system.log` — brak `[QueryGenerator]`, błędów `parseConditions`, PHP 8.2 warnings.

### Po zmianach .tpl

`rm -f cache/templates_c/*.php`

### Grep verification

```bash
rg "transformToNewAdvancedFilter|getAdvFilterByCvid" src/Modules/CustomView src/QueryField/QueryGenerator.php
rg "vtiger_cvadvfilter" src/Modules/CustomView
```

Oczekiwane: brak w CustomView (poza migracją / install_schema).

---

## Rollback plan

1. **Kod:** revert commit(ów) CR.
2. **Dane:** `DELETE FROM u_yf_cv_condition_group WHERE cvid IN (...)` — CASCADE warunki; stare `vtiger_cvadvfilter*` nadal intact jeśli migracja 000002 nie usuwała starych danych.
3. **Jeśli DROP legacy wykonany:** restore DB z backupu — jedyna opcja.
4. **Downtime:** krótkie okno na migrate (~ sekundy przy 17 wierszach warunków).

---

## Edge cases

| Case | Rozwiązanie |
|------|-------------|
| Filtr ze starą grupą 2 pustą | Migracja pomija puste grupy |
| Mieszane `and`/`or` w `condition_expression` w jednej grupie | Migracja parsuje tokeny `0 and 1 or 2` → podgrupy OR/AND (jak YF legacy transform) |
| Pole usunięte z modułu | Wiersz w DB zostaje; UI pokazuje pusty select; zapis filtra wymusza poprawkę |
| Rename pola (LayoutEditor) | Aktualizacja `u_yf_cv_condition.field_name` + migracje jak dziś dla `vtiger_cvadvfilter` |
| Calendar + pola Events | `RecordStructure` Events w ConditionBuilderRow — jak dziś w AdvanceFilter |
| Inventory modules — block ITEM_DETAILS | Wykluczyć z listy pól (jak EditAjax) |
| Głębokie zagnieżdżenie (>5 poziomów) | Dozwolone; brak limitu produktowego (YF też nie limituje) |

---

## Decision rationale & tradeoffs

**Dlaczego port YF ConditionBuilder, a nie „opcja B” (selectory między wierszami)?**

- YF ma gotowy, przetestowany model drzewa + UI dokładnie jak oczekiwany mockup.
- Opcja B nadal nie daje prawdziwych podgrup bez `parent_id`.
- Koszt portu UI+DB jest wyższy niż B, ale niższy niż wymyślanie własnego formatu.

**Dlaczego nie pełny port `App\Conditions\*` od razu?**

- 80+ klas QueryFields/RecordFields w YF; FreeCRM ma własny `src/QueryField/` — wystarczy `parseConditions()` mapujący na istniejące QueryField.
- RecordFields potrzebne dopiero dla runtime workflow validation.

**Alternatywa odrzucona:** JSON drzewa w kolumnie `vtiger_customview.conditions` — gorsze indeksowanie, brak FK, trudniejsze rename pól.

---

## Risks

| Ryzyko | Severity | Mitigacja |
|--------|----------|-----------|
| Regresja wyników list po migracji ALL/ANY → drzewo | **High** | Smoke na cvid 453, 545; porównanie COUNT przed/po |
| Niekompletne widgety typów pól | **Med** | Faza 1: 90% pól (V, N, D, DT, picklist, owner); reszta w iteracji |
| FilterService / ModuleManagement nadal pisze stare tabele | **Med** | Krok 8 obowiązkowy |
| Reports/Workflows nadal AdvanceFilter — rozjazd UX | **Low** | Documented out of scope |
| Port JS (ES6 class vs jQuery) | **Low** | YetiForce używa tego samego stacku |

---

## Assumptions

- Źródło referencyjne: [YetiForceCRM @ developer](https://github.com/YetiForceCompany/YetiForceCRM/tree/developer), commity ConditionBuilder od ~2022 (6.4+).
- Prod FreeCRM: 166 filtrów, 15 z warunkami zaawansowanymi — migracja mała.
- Edytor filtra pozostaje w `contentsDiv` (inline) w pierwszej wersji; modal jak YF — opcjonalny follow-up UX.
- Uprawnienie `CreateCustomFilter` bez zmian.

---

## Questions (resolved for implementation)

| Pytanie | Decyzja na implementację |
|---------|--------------------------|
| Modal vs inline editor? | **Inline** (zachować obecny UX FreeCRM); modal opcjonalnie później |
| Pola related w warunku w fazie 1? | **Tak**, jeśli port `ConditionBuilderRow` z YF — niski koszt |
| DROP starych tabel? | **Osobny krok** po 2 tygodniach QA na dev/test |
| Advanced conditions (relacje)? | **Out of scope** |

---

## Deliverables checklist

- [ ] Impact summary (ten dokument)
- [ ] Migracje schema + data
- [ ] Backend: getConditions, addGroup, parseConditions
- [ ] UI: ConditionBuilder tpl + js
- [ ] Cleanup legacy CustomView advfilter code
- [ ] Testy smoke + manual QA
- [ ] Aktualizacja `.cursor/rules/customview-filters.mdc` po merge
