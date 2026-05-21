# Plan migracji: `Kandydaci` → `Candidates`

> **Status:** propozycja do akceptacji (Change approval mode — nie wprowadzono jeszcze żadnych zmian w kodzie ani DB).
>
> **Cel:** zmiana nazwy modułu, nazw tabel i kolumn z polskich na angielskie, przy zachowaniu polskich **etykiet** (tłumaczeń wyświetlanych w UI). Klucze tłumaczeń (`vtiger_field.fieldlabel`) pozostają bez zmian — w `languages/pl_pl/Candidates.json` dalej mapują się na "Telefon", "Dostępność", "Kandydaci" itd.

---

## 1. Inwentaryzacja zmian

### 1.1 Baza danych

- **Moduł w `vtiger_tab`**: `tabid = 121`, `name = 'Kandydaci'`, `tablabel = 'Kandydaci'`.
- **Tabele danych**:
  - `u_yf_kandydaci` (PK `kandydaciid`, 21 pól modułu, ~19 791 wierszy),
  - `u_yf_kandydacicf` (custom fields, PK `kandydaciid`, 16 pól, ~16 279 wierszy).
- **Bloki layoutu** (`vtiger_blocks`, tabid 121): 6 sztuk — `LBL_BASIC_INFORMATION`, `Rekrutacja`, `CV`, `PLL_REFERRED_BY_EMPLOYEE_PROGRAMM`, `LBL_CUSTOM_INFORMATION`, `Newsletter`.
- **Pola** (`vtiger_field`): 44 wpisy z `tabid=121`, w tym foreign keys (uitype 10 / 33):
  - `polec_znajomego`, `projekt_na_ktory_ostatnio_wysl`, `cooperation_form`, `zrodlo_aplikacji`.
- **Encje pomocnicze** (`vtiger_entityname`): `tablename='u_yf_kandydaci'`, `entityidfield='kandydaciid'`, `searchcolumn='name,rekrutowany_stanowisko,telefon,telefon_extra,application_id'`.
- **`vtiger_relatedlists`**: 5 wpisów wskazujących na/odwrotnie od `Kandydaci` (Documents, ProjektyRekrutacyjne, Contacts).
- **`vtiger_links`** (DASHBOARDWIDGET, tabid=121): 6 wpisów z `module=Kandydaci` w URL.
- **`vtiger_customview`**: **113 zapisanych widoków** z `entitytype='Kandydaci'` (kolumny tych widoków mogą referować po nazwach kolumn — wymaga aktualizacji `vtiger_cvcolumnlist`, `vtiger_cvstdfilter`, `vtiger_cvadvfilter`).
- **`vtiger_crmentity.setype`**: `Kandydaci` — **21 857 aktywnych rekordów**; dodatkowo 20 073 w tabeli backupowej `vtiger_crmentity__dates_backup_kandydaci_20260506` (zostawiamy bez zmian).
- **Picklisty** (tabele słownikowe, niezależne):
  - `vtiger_zrodlo_aplikacji`, `vtiger_dostepnosc`, `vtiger_status_kandydata`, `vtiger_wymiar_czasu_pracy`.
- **Brak FK w schemacie** na `u_yf_kandydaci(kandydaciid)` (sprawdzone w `KEY_COLUMN_USAGE`) — relacje idą przez `vtiger_crmentity.crmid` i tabelę M:M `u_yf_projekty_rekrutacyjne_relations_members_entity`.
- **Brak raportów** z `Kandydaci` w `vtiger_reportmodules`.
- **Brak workflowów** w `com_vtiger_workflows` dla `module_name='Kandydaci'`.

### 1.2 Kod PHP (PSR-4: `App\Modules\Kandydaci\` → `src/Modules/Kandydaci/`)

- `Kandydaci.php` (CRMEntity), `Models/{Module,Record,Relation,DetailView,ListView,RelatedListLeftSideEmail,RelatedListLeftSideExtras}.php`
- `Actions/{ImportCandidatesManually,ToggleStarCandidateManuallyAjax,TransformCandidateToConsultant,TransformDocumentToCV}.php`
- `Views/{ImportCandidatesModal,RelatedList,TransformCandidateToConsultantModal,TransformDocumentToCVModal}.php`
- `Handlers/{KandydaciHandler,NewCandidateInProject,RefreshDocsNumber}.php`
- `Workflows/{KandydaciWorkflow,HRTaskWorkflow,RunImportCandidatesWorkflow}.php`
- `Crons/ScheduledImport.php`, `Cron/ScheduledImportTask.php`, legacy katalog `cron/` i `scripts/*.php`
- `Widgets/{KandydaciPreview,KandydaciRecruitmentProjects}.php`
- `Files/{MultiAttachment,GetCVImage}.php`

Twardo zakodowane stringi `"Kandydaci"`/`'Kandydaci'` w kodzie zewnętrznym:

- `src/Modules/Documents/Models/Record.php` (set as CV gdy parent=Kandydaci)
- `src/Modules/ProjektyRekrutacyjne/Relations/GetRelatedMembers.php`
- `src/Modules/ProjektyRekrutacyjne/Views/RelatedList.php`
- `src/Modules/ProjektyRekrutacyjne/Models/Record.php`
- `src/Events/EventHandler.php` (mapa legacy class names → `Kandydaci_NewCandidateInProject_Handler`)
- `src/Modules/Cron/Migration/MigrateKandydaciScheduledImportToClass.php`
- Importowe tabele `import_stage_*` — jednorazowe, można zignorować.

### 1.3 Layouty / public / języki

- `layouts/basic/modules/Kandydaci/**` (10 plików: detail header, summary view, 3 modale, 4 widgety).
- `public/layouts/basic/modules/Kandydaci/resources/TransformDocumentToCVModal.js`.
- `public/modules/ProjektyRekrutacyjne/resources/{Detail,RelatedList}.js` — odwołania do `module=Kandydaci`.
- `public/layouts/basic/skins/style.{css,less}`, `skins/modern/style.css`, `skins/icons/userIcons.css` — selektory `.userIcon-Kandydaci` itp.
- `languages/pl_pl/Kandydaci.json` (etykiety PL), `languages/en_us/Kandydaci.json` (z `"Kandydaci": "Candidates"`).
- `user_privileges/menu_*.php`, `user_privileges/sharing_privileges_*.php` (50+ plików — generowane automatycznie po przeładowaniu uprawnień; bez zmian w kodzie).

### 1.4 Skrypty migracji / dokumentacja

- `scripts/migration/candidates/{remote-export,local-apply}.sh`
- `synchronization-guide.md`, `documentation/plan-implementacji.md`, `documentation/new-import-module.md`, `documentation/freecrm-mail-system-architecture.md`, `documentation/remote-page.html`, `documentation/migrate-to-new-cron.md`
- `src/cache/import/IMPORT_1` (cache importu — do wyczyszczenia).

---

## 2. Kluczowa decyzja architektoniczna

Wymaganie: **nazwa modułu i nazwy pól po angielsku, ALE etykiety (tłumaczenia) zostają**.

To znaczy:

- `vtiger_tab.name`: `Kandydaci` → `Candidates`.
- `vtiger_field.fieldname` i `vtiger_field.columnname`: PL → EN (np. `telefon` → `phone`, `dostepnosc` → `availability`, `polec_znajomego` → `referrer_consultant_id`, `tresc_cv` → `cv_text`, …).
- `vtiger_field.fieldlabel`: **bez zmian** (zostają obecne klucze/etykiety, jak `"Telefon"`, `"Dostępność"`, `"Rekrutowany na stanowisko"`).
- Pliki językowe (`languages/pl_pl/Candidates.json`, `languages/en_us/Candidates.json`) — przeniesione + uzupełnione; **klucze tłumaczeń zachowane** (to one mapują nazwy pól na etykiety w UI).
- Nazwy tabel i kolumn DB — odzwierciedlają nowe nazwy modułu/pól (`u_yf_candidates`, `u_yf_candidatescf`, `candidatesid`).

---

## 3. Mapowanie pól PL → EN

### 3.1 Tabela główna `u_yf_kandydaci` → `u_yf_candidates`

| Obecne                          | Proponowane EN                |
|---                              |---                            |
| `kandydaciid`                   | `candidatesid` (PK)           |
| `name`                          | `name`                        |
| `number`                        | `number`                      |
| `status_kandydata`              | `candidate_status`            |
| `telefon`                       | `phone`                       |
| `telefon_extra`                 | `phone_extra`                 |
| `rekrutowany_stanowisko`        | `recruited_position`          |
| `dostepnosc`                    | `availability`                |
| `wymiar_czasu_pracy`            | `work_time_type`              |
| `cooperation_form`              | `cooperation_form`            |
| `polec_znajomego`               | `referrer_consultant_id`      |
| `application_id`                | `application_id`              |
| `cv_img_file`                   | `cv_img_file`                 |
| `recruitify`                    | `recruitify`                  |
| `application_json_content`      | `application_json_content`    |
| `starred`                       | `starred`                     |
| `is_referred_by_employee`       | `is_referred_by_employee`     |
| `referred_by_employee`          | `referred_by_employee`        |
| `referred_on_position`          | `referred_on_position`        |
| `referred_by_email`             | `referred_by_email`           |
| `is_future_contact_allowed`     | `is_future_contact_allowed`   |
| `test`                          | `test` *(zachowane — D4 (b))* |

### 3.2 Tabela CF `u_yf_kandydacicf` → `u_yf_candidatescf`

| Obecne                            | Proponowane EN                  |
|---                                |---                              |
| `kandydaciid`                     | `candidatesid` (PK)             |
| `email_prywatny`                  | `email_private`                 |
| `email_firmowy`                   | `email_business`                |
| `linkedin` / `www` / `github`     | bez zmian                       |
| `ilosc_dokumentow`                | `documents_count_legacy`        |
| `ilosc_dokumentow_kandydata`      | `documents_count`               |
| `komunikator`                     | `messenger`                     |
| `data_maksymalny_kontakt_rodo`    | `gdpr_max_contact_date`         |
| `newsletter_email`                | `newsletter_email`              |
| `newsletter_stanowiska`           | `newsletter_positions`          |
| `zrodlo_aplikacji`                | `application_source`            |
| `tresc_cv`                        | `cv_text`                       |
| `oczekiwania_finansowe_brutto`    | `salary_expectation_gross`      |
| `projekt_na_ktory_ostatnio_wysl`  | `last_sent_to_project_id`       |
| `data_ostatniego_wyslania`        | `last_sent_to_project_date`     |

Picklisty (`vtiger_status_kandydata`, `vtiger_dostepnosc`, `vtiger_wymiar_czasu_pracy`, `vtiger_zrodlo_aplikacji`) — patrz decyzja **D2** w sekcji 6.

---

## 4. Strategia migracji

### Wariant A — „big-bang rename”

Jedna duża transakcja DB + jeden PR z całym kodem. Najprostszy, ale przy pomyłce trzeba przywracać z backupu produkcyjnej tabeli z ~20 tys. rekordów.

### Wariant B — „dwufazowo, bezpiecznie” *(rekomendowany)*

#### Faza 0 — przygotowanie (read-only)

- Pełny backup DB (`mariadb-dump` w `tmp/db-backups/`).
- Backup tabel: `vtiger_tab`, `vtiger_field`, `vtiger_blocks`, `vtiger_entityname`, `vtiger_relatedlists`, `vtiger_links`, `vtiger_customview`, `vtiger_cvcolumnlist`, `vtiger_cvstdfilter`, `vtiger_cvadvfilter` (jeśli istnieją) — do osobnych tabel `*_bak_kandydaci_<data>`.
- Backup `user_privileges/*.php`, `cache/`.
- Snapshot `storage/` (lub potwierdzenie, że ścieżki CV są niezależne od nazwy modułu — sprawdzone: `transformDocumentToCV()` używa `vtlib\Functions::initStorageFileDirectory('MultiImage')` bez nazwy modułu; legacy `transformFileToCV()` ma `"Kandydaci"` w ścieżce, ale fizycznych plików nie ruszamy).
- Sprawdzenie `vtiger_cvcolumnlist` — które kolumny widoków zawierają stare nazwy.

#### Faza 1 — kod i pliki (deploy bez zmiany DB)

1. **Skopiuj** katalog `src/Modules/Kandydaci/` → `src/Modules/Candidates/` (zmiana namespace na `App\Modules\Candidates`).
2. Zmień nazwy klas zawierających `Kandydaci`: `KandydaciHandler` → `CandidatesHandler`, `KandydaciWorkflow` → `CandidatesWorkflow`, klasy widgetów `Kandydaci*` → `Candidates*`.
3. Skopiuj `layouts/basic/modules/Kandydaci/` → `layouts/basic/modules/Candidates/` (i `public/layouts/basic/modules/Kandydaci/` analogicznie).
4. Skopiuj `languages/{pl_pl,en_us}/Kandydaci.json` → `languages/{pl_pl,en_us}/Candidates.json`. **Etykiety bez zmian** w `pl_pl`; w `en_us` uzupełnij brakujące angielskie odpowiedniki.
5. W kodzie kopii zamień `'Kandydaci'` → `'Candidates'` (string literals dla nazwy modułu) i `App\\Modules\\Kandydaci` → `App\\Modules\\Candidates`. Zachowaj jedną mapę legacy w `src/Events/EventHandler.php`:
   `'Kandydaci_NewCandidateInProject_Handler' => 'App\\Modules\\Candidates\\Handlers\\NewCandidateInProject'`.
6. Brak `class_alias` (zgodnie z regułą projektu). Zamiast tego **przepisz wszystkie zewnętrzne odwołania** w fazie 1, zanim usuniesz stary katalog:
   - `src/Modules/Documents/Models/Record.php`
   - `src/Modules/ProjektyRekrutacyjne/Relations/GetRelatedMembers.php`, `Views/RelatedList.php`, `Models/Record.php`
   - `src/Events/EventHandler.php`
   - `src/Modules/Cron/Migration/MigrateKandydaciScheduledImportToClass.php`
7. Zmień nazwy w skryptach: `scripts/migration/candidates/*` (ścieżka już jest `candidates/`, treść skryptów do aktualizacji w Fazie 4).
8. **Stary katalog `src/Modules/Kandydaci/` na razie zostaje** — żeby moduł działał z aktualnym `vtiger_tab.name='Kandydaci'`. Czyścimy go dopiero po Fazie 2.
9. W nowych plikach modułu zaktualizuj odwołania do tabel i kolumn:
   - `u_yf_kandydaci` → `u_yf_candidates`, `u_yf_kandydacicf` → `u_yf_candidatescf`, `kandydaciid` → `candidatesid`,
   - wszystkie pary z sekcji 3.

> ⚠️ W tej fazie kod NEW + DB OLD = niespójność. Dlatego nowy moduł na razie *nie jest aktywny* — Fazy 1 i 2 wykonujemy w jednym oknie technicznym.

#### Faza 2 — migracja DB (jedna transakcja techniczna, app w maintenance)

W jednym skrypcie `migrations/Mxxx_RenameKandydaciToCandidates.php` (klasa Yii) lub `.sql`:

```sql
START TRANSACTION;

-- 2.1 Rename tabel i PK kolumny
ALTER TABLE u_yf_kandydaci   RENAME TO u_yf_candidates;
ALTER TABLE u_yf_candidates  CHANGE kandydaciid candidatesid INT(11) NOT NULL;
ALTER TABLE u_yf_kandydacicf RENAME TO u_yf_candidatescf;
ALTER TABLE u_yf_candidatescf CHANGE kandydaciid candidatesid INT(11) NOT NULL;

-- 2.2 Rename kolumn (po jednej, wg mapowania z sekcji 3)
ALTER TABLE u_yf_candidates  CHANGE telefon phone VARCHAR(30) DEFAULT '';
ALTER TABLE u_yf_candidates  CHANGE telefon_extra phone_extra VARCHAR(100) DEFAULT NULL;
-- ... (wszystkie pozostałe wg mapowania)

-- 2.3 vtiger_tab: zmiana nazwy modułu
UPDATE vtiger_tab SET name='Candidates' WHERE tabid=121;
-- tablabel pozostawiamy / lub ustawiamy 'Candidates' i etykietę w languages/pl_pl/Candidates.json — patrz D1

-- 2.4 vtiger_field: nazwa tabeli + nazwy kolumn
UPDATE vtiger_field SET tablename='u_yf_candidates'   WHERE tablename='u_yf_kandydaci';
UPDATE vtiger_field SET tablename='u_yf_candidatescf' WHERE tablename='u_yf_kandydacicf';
UPDATE vtiger_field SET columnname='phone', fieldname='phone' WHERE tabid=121 AND fieldname='telefon';
-- ... powtórzyć dla każdego renamowanego pola; fieldlabel ZOSTAWIAMY bez zmian

-- 2.5 vtiger_entityname
UPDATE vtiger_entityname
  SET modulename='Candidates', tablename='u_yf_candidates',
      entityidfield='candidatesid', entityidcolumn='candidatesid',
      searchcolumn='name,recruited_position,phone,phone_extra,application_id'
  WHERE modulename='Kandydaci';

-- 2.6 vtiger_customview + vtiger_links
UPDATE vtiger_customview SET entitytype='Candidates' WHERE entitytype='Kandydaci';
UPDATE vtiger_links SET linkurl=REPLACE(linkurl,'module=Kandydaci','module=Candidates') WHERE tabid=121;
-- vtiger_relatedlists nie ma kolumny module/relmodule po nazwie — używa tabid/related_tabid, nic do zmiany.

-- 2.7 vtiger_crmentity.setype
UPDATE vtiger_crmentity SET setype='Candidates' WHERE setype='Kandydaci';

-- 2.8 vtiger_blocks: zostawić (klucze już są jako PLL_*/LBL_*)

-- 2.9 Picklisty - patrz decyzja D2:
--   (D2.a) zostawiamy nazwy tabel jak są (rekomendowane na start)
--   (D2.b) renamujemy tabele słownikowe (np. vtiger_status_kandydata -> vtiger_candidate_status)

-- 2.10 Reference fields: vtiger_field.fieldparams dla uitype 10 - sprawdzić, czy pole 'polec_znajomego'
--      miało twarde wskazanie modułu (u nas jest puste, OK).

-- 2.11 vtiger_cvcolumnlist / vtiger_cvstdfilter / vtiger_cvadvfilter - update kolumn referujących stare nazwy
--      (113 zapisanych widoków). Konkretne UPDATE'y per renamed column.

COMMIT;
```

Po commicie:

- usuń `cache/` (cały) i `user_privileges/` (system odbuduje przy następnym requeście),
- restart kontenerów lub minimum `opcache_reset()`,
- usuń stary `src/Modules/Kandydaci/`, `layouts/basic/modules/Kandydaci/`, stare pliki językowe, stary `public/.../Kandydaci/`.

#### Faza 3 — weryfikacja

1. `tail -f cache/logs/system.log` podczas otwierania:
   - listy `index.php?module=Candidates&view=List`,
   - kilku konkretnych rekordów (powinny zachować `crmid`),
   - powiązań z `ProjektyRekrutacyjne` i `Documents`.
2. Test CV (`file.php?module=Candidates&action=MultiAttachment&...`).
3. Test 113 widoków `vtiger_customview` (kolumny w `vtiger_cvcolumnlist` z nowymi nazwami).
4. Test cron-ów (`ScheduledImport`, workflowy).
5. Test importu (`ImportCandidatesModal`).

#### Faza 4 — sprzątanie

- Aktualizacja `synchronization-guide.md`, `scripts/migration/candidates/*.sh`, `.cursor/rules/mcp-database-access.mdc` (przykład z `cv_img_file`).
- `tmp/migration-*` jeśli pojawi się nowy rollback.

---

## 5. Ryzyka i mitygacje

| Ryzyko | Mitygacja |
|---|---|
| Twarde stringi `'Kandydaci'` w 50+ miejscach kodu / templates | Faza 1 — pełen sweep z grep'em po dosłownych literałach |
| `vtiger_customview_cvcolumnlist` (113 widoków) może zawierać `u_yf_kandydaci:telefon:Telefon:telefon:V` | Update razem z 2.4 — dedykowane `UPDATE`y per renamed column |
| Workflowy zapisane w `com_vtiger_workflows` z polskimi `fieldname` w filtrach | Sprawdzić tabelę przed migracją; obecnie brak workflowów dla modułu, ale `com_vtiger_workflowtasks` może mieć inną treść |
| Tabele backupowe `vtiger_crmentity__*_backup_kandydaci_*` | Zostawiamy bez zmian (nie wpływają na działanie) |
| Pliki JSON w `cv_img_file` z `path` jak `storage/MultiAttachment/Kandydaci/cv_img_file/...` | Sprawdzone: nowy `transformDocumentToCV()` używa generycznego storage path; stare ścieżki z legacy `transformFileToCV()` zostają — fizycznych plików nie ruszamy |
| Duży `UPDATE vtiger_crmentity SET setype=... WHERE setype='Kandydaci'` (21 857 wierszy) | Wykonalne w jednej transakcji; mała tabela kolumnowo |
| `user_privileges/*.php` cache | Skasowanie i rebuild przy starcie |
| Skrypty cron z hardcoded ścieżkami `/var/www/import/cv/...` | Bez zmian — to są dane spoza repo |

---

## 6. Decyzje

### D1. `vtiger_tab.tablabel` — ✅ ZATWIERDZONE (a)

`tablabel='Candidates'` w DB, ale `languages/pl_pl/Candidates.json` mapuje `"Candidates": "Kandydaci"` → w PL UI dalej widoczne "Kandydaci".

### D2. Tabele picklist — ✅ ZATWIERDZONE (b)

Zostawiamy nazwy picklist po polsku (`vtiger_status_kandydata`, `vtiger_dostepnosc`, `vtiger_wymiar_czasu_pracy`, `vtiger_zrodlo_aplikacji`). W `u_yf_candidates` nazwy pól są po angielsku (`candidate_status`, `availability`, `work_time_type`, `application_source`), ale w `vtiger_field` wskazują na stare polskie tabele słownikowe. Refactor picklist może odbyć się w osobnym kroku w przyszłości.

### D3. Mapowanie legacy w `Events/EventHandler.php` — ✅ ZATWIERDZONE (b)

Czyste cięcie. W `src/Events/EventHandler.php` usuwamy wpis `'Kandydaci_NewCandidateInProject_Handler' => ...`. Bezpieczne, ponieważ:

- W `vtiger_eventhandlers` nie ma żadnego rekordu z `handler_class LIKE '%Kandyda%'` (sprawdzone).
- Tabela `s_yf_handler_updater` jest pusta (sprawdzone).
- W kodzie repo legacy string `Kandydaci_NewCandidateInProject_Handler` występuje wyłącznie w samym `Events/EventHandler.php`.

W nowym module rejestracja handlera odbędzie się przez pełnokwalifikowany namespace `App\\Modules\\Candidates\\Handlers\\NewCandidateInProject` (bez mapy).

### D4. Pole `test` — ✅ ZATWIERDZONE (b)

Zachować pole `test` (varchar(100), uitype 1, label 'Test') bez zmian — przydaje się jako pole tymczasowe/diagnostyczne.

### D5. Sposób wykonania migracji DB — ✅ ZATWIERDZONE (a)

Klasa migracji Yii w katalogu `migrations/` uruchamiana przez `./yii migrate`. Konwencja spójna z istniejącymi migracjami w repo. Plik: `migrations/Mxxx_RenameKandydaciToCandidates.php`.

---

## 7. Artefakty do dostarczenia

Po akceptacji planu — każdy do osobnego review:

1. **`migrations/M00xx_RenameKandydaciToCandidates.php`** — pełna migracja DB w jednej transakcji, z `echo` per krok.
2. **`src/Modules/Candidates/`** — pełna kopia z renamingiem (nowy namespace, nowe fieldnames, nowe nazwy tabel).
3. **Patch dla zewnętrznych referencji** (Documents/ProjektyRekrutacyjne/Events/Settings/public skins).
4. **`languages/pl_pl/Candidates.json`** + **`languages/en_us/Candidates.json`** — etykiety PL zachowane, klucze dla nowych nazw pól.
5. **`documentation/migrate-kandydaci-to-candidates.md`** *(ten dokument)* — z runbookiem (kroki + rollback).
6. **Skrypt smoke-test**: `bin/check_candidates_migration.php` (liczba pól, liczba rekordów, kilka kluczowych SELECT-ów na nowych nazwach kolumn).

---

## 8. Rollback

W razie problemów:

1. Zatrzymaj aplikację (maintenance mode).
2. `mariadb < tmp/db-backups/freecrm-<data>.sql` — przywróć całą bazę z backupu z Fazy 0.
3. `git revert` lub `git checkout` poprzedniego commita z kodem.
4. Usuń `cache/` i `user_privileges/`.
5. Restart kontenerów / `opcache_reset()`.
6. Sprawdź `cache/logs/system.log`.

Backupy granularnie:

- Tabele DB: `*_bak_kandydaci_<data>` (np. `vtiger_field_bak_kandydaci_20260521`) → `INSERT ... SELECT` z powrotem.
- `cv_img_file` — fizyczne pliki w `storage/MultiAttachment/` zostają nieruszone.

---

## 9. Status decyzji

| ID | Status | Wartość |
|---|---|---|
| **D1** | ✅ | `tablabel='Candidates'` w DB; w PL UI dalej "Kandydaci" przez `languages/pl_pl/Candidates.json` |
| **D2** | ✅ | Tabele picklist po polsku zostają; tylko nazwy pól w `u_yf_candidates` po angielsku |
| **D3** | ✅ | Czyste cięcie — usuwamy wpis `Kandydaci_NewCandidateInProject_Handler` z `Events/EventHandler.php` (DB jest pusta) |
| **D4** | ✅ | Pole `test` zostaje (przydaje się jako tymczasowe/diagnostyczne) |
| **D5** | ✅ | Migracja jako klasa Yii w `migrations/Mxxx_RenameKandydaciToCandidates.php` |

Wszystkie decyzje zatwierdzone — plan gotowy do wdrożenia.
