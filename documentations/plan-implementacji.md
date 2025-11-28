# Plan implementacji modułu ImportManager

Dokument opisuje szczegółową kolejność prac potrzebnych do wdrożenia modułu ImportManager zgodnie z założeniami z `documentations/new-import-module.md`. Plan zakłada iteracyjne dostarczanie funkcjonalności – każdy etap kończy się możliwością przetestowania w UI.

---

## Etap 0 – Przygotowanie infrastruktury
1. **Analiza istniejącego importu**  
   - Sprawdzić aktualny moduł legacy (`Import`) i sposób korzystania z `vtiger_import_queue`.
   - Zanotować zależności (np. cron, wpisy w bazie) potrzebne do utrzymania kompatybilności.
2. **Schemat bazy i migracje**  
   - Zaprojektować tabele: `import_batches`, `import_mappings`, `import_logs`.  
   - Przygotować migrację SQL z użyciem `App\Db\Db` (bez PearDatabase).  
   - Zaplanować naming convention dla stagingów `import_stage_{module}_{batchId}`.
3. **Uprawnienia i konfiguracja**  
   - Dodać wpis dla nowego prawa `ImportData` (ACL domyślne).  
   - Utworzyć plik `config/import_duplicates.php` z przykładowymi zestawami identyfikatorów.
   - Przygotować `config/modules/ImportManager.php` z domyślnymi wartościami: `fileLimits.maxUploadSize = 10MB`, `preview.rows = 30`, `staging.chunkSize = 200`, `cleanup.retentionDays = 2`.

### Pliki do utworzenia (Etap 0)
- `migrations/ImportManager/2025_01_01_000001_create_import_tables.php` – migracja tworząca tabele `import_batches`, `import_mappings`, `import_logs` wraz z indeksami.
- `migrations/ImportManager/2025_01_01_000002_setup_import_permissions.php` – dodanie prawa `ImportData` i przypisanie do profili.
- `config/modules/ImportManager.php` – główny plik konfiguracyjny modułu (limity uploadu, preview, staging, queue, cleanup).
- `config/import_duplicates.php` – deklaracja zestawów pól identyfikujących duplikaty dla poszczególnych modułów.

## Etap 1 – Szkielet modułu i UI
1. **Struktura katalogów**  
   - `src/Modules/ImportManager/{Controllers,Actions,Services,Parsers,Jobs}`  
   - `modules/ImportManager/{models,views}` (cienka warstwa vtlib).  
   - Smarty: `layouts/basic/modules/ImportManager/` (np. `WizardStep1.tpl`).  
   - Zasoby: `public/layouts/basic/modules/ImportManager/`.
2. **Kontroler kreatora**  
   - `FreeCRM\Modules\ImportManager\Controllers\WizardController` – trzy kroki (Upload, Mapowanie, Potwierdzenie).  
   - Widoki vtlib delegujące do kontrolera oraz routing przycisków w UI.
3. **Upload i przechowywanie plików**  
   - Akcja `UploadAction` zapisująca pliki w `storage/imports/{batchId}`.  
   - Walidacja typu pliku oraz rozmiaru (CSV, XML, ZIP).  
   - Zapisywanie meta w `import_batches`.
4. **Obsługa ZIP**  
   - `ZipInspector` sprawdzający zawartość archiwum:  
     - pojedynczy CSV → przekazanie do `CsvParser`,  
     - pojedynczy XML → przekazanie do `XmlParser`,  
     - inny przypadek → oznaczenie batcha jako błąd z komunikatem.  
   - UI informuje, który plik z ZIP został użyty.
5. **Podgląd danych**  
   - Integracja z `ParserFactory` (CSV + autodetekcja separatora, XML).  
   - Prezentacja pierwszych N wierszy w UI (krok mapowania).

### Pliki do utworzenia (Etap 1)
- `src/Modules/ImportManager/Controllers/WizardController.php` – kontroler kreatora odpowiedzialny za przepływ trzech kroków i komunikację z usługami uploadu/podglądu.
- `src/Modules/ImportManager/Actions/UploadAction.php` – obsługa przesyłania pliku i inicjalizacji wsadu.
- `src/Modules/ImportManager/Services/ZipInspector.php` – logika detekcji i rozpakowywania plików ZIP.
- `src/Modules/ImportManager/Parsers/ParserFactory.php`, `CsvParser.php`, `XmlParser.php` – podstawowe klasy parsujące wykorzystywane przez podgląd.
- `modules/ImportManager/views/Wizard.php` + `modules/ImportManager/views/partials/WizardHeader.tpl` – cienka warstwa vtlib integrująca UI z layoutem.
- `layouts/basic/modules/ImportManager/WizardStep1.tpl`, `WizardStep2.tpl`, `WizardStep3.tpl` – szablony Smarty dla poszczególnych kroków kreatora.
- `public/layouts/basic/modules/ImportManager/wizard.js` oraz `wizard.css` – interakcje frontowe (walidacja formularzy, obsługa drag&drop, dynamiczne podsumowania).

## Etap 2 – Mapowanie i staging
1. **MappingDefinition & FieldMapper**  
   - UI: wybór pól modułu + zestaw identyfikatorów duplikatów.  
   - Panel „Domyślne wartości” (widżety zgodne z typem pola, podobnie jak w legacy Import).  
   - Backend: walidacja kompletności mapowania oraz domyślnych wartości, zapis do `import_mappings`.
2. **StagingWriter / TemporaryTableManager**  
   - Tworzenie płaskiej tabeli `import_stage_{module}_{batchId}` w transakcji.  
   - Płynne generowanie kolumn na podstawie `App\Db\Db::getTableSchema`.  
   - Masowy insert rekordów (chunkowo) z zachowaniem `row_hash`, `retry_token`.
3. **Walidacja stagingu**  
   - `RecordValidator` uruchamia walidatory typu pola (email, telefon, referencje).  
   - Sprawdzenie kluczy unikatowych i obowiązkowych pól.  
   - Aktualizacja statusów w stagingu (`ok`, `failed`) + wpis w `import_logs`.

W implementacji iteracji 2 wszystkie powyższe komponenty już istnieją:
- `FieldMapper` + `MappingDefinition` pilnują mapowania i wartości domyślnych (zapisywanych w `import_mappings`),
- `TemporaryTableManager` generuje tabelę `import_stage_{module}_{batchId}` (jedna kolumna na każde pole + metadane),
- `StagingWriter` wraz z `BatchProcessor::stage()` pobiera plik źródłowy i ładuje rekordy do stagingu, a `RecordValidator` oznacza rekordy błędne,
- akcja `ImportManager/Stage` wywoływana z trzeciego kroku kreatora uruchamia staging i aktualizuje `import_batches`.

### Pliki do utworzenia (Etap 2)
- `src/Modules/ImportManager/Services/MappingDefinition.php` oraz `FieldMapper.php` – reprezentacja map i logika odwzorowania kolumn na pola modułu (z obsługą wartości domyślnych).
- `src/Modules/ImportManager/Actions/SaveMappingAction.php` – endpoint zapisujący mapowanie i konfigurację duplikatów w `import_mappings`.
- `src/Modules/ImportManager/Services/TemporaryTableManager.php` – zarządzanie tworzeniem/usuwaniem tabel stagingowych.
- `src/Modules/ImportManager/Services/StagingWriter.php` – ładowanie danych do stagingu, normalizacja typów.
- `src/Modules/ImportManager/Services/RecordValidator.php` – walidacje stagingu (typy pól, obowiązkowe pola, relacje).
- `layouts/basic/modules/ImportManager/MapFields.tpl` + `public/layouts/basic/modules/ImportManager/mapping.js` – UI mapowania, panel wartości domyślnych i sekcja duplikatów.

## Etap 3 – Import właściwy i obsługa duplikatów
1. **DuplicateResolver & MergeStrategy**  
   - Implementacja strategii: pomijanie, nadpisywanie, łączenie.  
   - Wybór strategii per batch (konfiguracja w kreatorze).  
   - Logowanie decyzji w `import_logs`.
2. **RecordPersister**  
   - Pobieranie rekordów ze stagingu w paczkach (np. 200 ID).  
   - Transakcje przy użyciu `App\Db\Db`; rollback przy błędach.  
   - Integracja z natywnymi modelami (`FreeCRM\Modules\*\Models`).  
3. **Raportowanie**  
   - Podsumowanie batcha (sukcesy, błędy, duplikaty).  
   - Możliwość pobrania JSON/CSV z wynikami.

### Pliki do utworzenia (Etap 3)
- `src/Modules/ImportManager/Services/DuplicateResolver.php` – wybór rekordów do porównania na podstawie konfiguracji `config/import_duplicates.php`.
- `src/Modules/ImportManager/Services/MergeStrategy.php` (oraz ewentualne klasy pochodne) – reguły dla strategii pomijaj/nadpisuj/łącz.
- `src/Modules/ImportManager/Services/RecordPersister.php` – właściwy zapis rekordów w paczkach z użyciem natywnych modeli.
- `src/Modules/ImportManager/Services/ImportLogService.php` – agregacja wyników, zapis logów do DB i pliku JSON.
- `src/Modules/ImportManager/Actions/StartImportAction.php` – uruchomienie procesu importu dla batcha (kolejka/manualne).
- `layouts/basic/modules/ImportManager/Confirm.tpl` + `public/layouts/basic/modules/ImportManager/confirm.js` – podsumowanie kroku 3 i wybór strategii duplikatów.

## Etap 4 – Retry i korekty
1. **Eksport błędów**  
   - Endpoint generujący plik z rekordami `failed` (zawiera `retry_token`).  
   - Weryfikacja uprawnień i filtrowanie po batchu.
2. **Grid korekcyjny**  
   - Widok `Retry.php` z tabelą stagingową (tylko błędne rekordy).  
   - Edycja inline pól + walidacja klient/serwer.  
   - Akcja zapisująca zmiany (`RetryUpdateAction`) → aktualizacja stagingu.  
3. **RetryManager**  
   - Opcja „Uruchom ponownie tylko niepoprawne wiersze”.  
   - Aktualizacja statusów w stagingu i ponowne odpalenie `BatchProcessor` dla wybranych rekordów.

### Pliki do utworzenia (Etap 4)
- `src/Modules/ImportManager/Actions/ExportErrorsAction.php` – generowanie CSV/JSON z rekordami `failed`.
- `src/Modules/ImportManager/Services/RetryManager.php` – logika oznaczania rekordów do ponownego przetworzenia oraz komunikacja z `BatchProcessor`.
- `src/Modules/ImportManager/Actions/RetryUpdateAction.php` – zapis zmian z gridu korekcyjnego do stagingu przy blokadzie wsadów `running`.
- `modules/ImportManager/views/Retry.php` + `layouts/basic/modules/ImportManager/RetryGrid.tpl` – widok listy błędnych rekordów i formularz edycji.
- `public/layouts/basic/modules/ImportManager/retry.js` – obsługa UI (inline edit, walidacje, eksport błędów, restart).

## Etap 5 – Integracja z kolejką i CRON
1. **ImportJob & ImportJobProcessor**  
   - Job DB korzystający z `vtiger_import_queue`.  
   - Mechanizm kolejkowania plików > N rekordów (konfiguracja progu).  
2. **Cron / Worker**  
   - Aktualizacja istniejącego crona (legacy `Import`) obsługującego `vtiger_import_queue`.  
   - Monitorowanie postępu batcha (status w `import_batches`).

### Pliki do utworzenia (Etap 5)
- `src/Modules/ImportManager/Jobs/ImportJob.php` – enkapsulacja danych wsadu zapisywanych do `vtiger_import_queue`.
- `src/Modules/ImportManager/Jobs/ImportJobProcessor.php` – wywołanie `BatchProcessor` na podstawie wpisu kolejki.
- `cron/modules/ImportManager/Import.php` (lub aktualizacja istniejącego) – worker crona, który pobiera zadania z `vtiger_import_queue` i odpala proces importu.
- `src/Modules/ImportManager/Services/QueueDispatcher.php` – logika decydująca o wstawieniu wsadu do kolejki (wg progów z konfiguracji).

## Etap 6 – Testy i logowanie
1. **Testy jednostkowe / integracyjne**  
   - Parsery (CSV/XML), walidatory, DuplicateResolver.  
   - Scenariusze staging → import dla kilku modułów (np. Contacts, Leads).
2. **E2E (Playwright/Cypress)**  
   - Przejście kreatora, zapis mapowania, import i retry.  
   - Przypadki dla różnych separatorów CSV oraz XML.
3. **Monitoring/logi**  
   - Upewnić się, że `ImportLogService` zapisuje dane zarówno w bazie, jak i w `storage/imports/{batchId}/log.json`.  
   - Sprawdzić `cache/logs/system.log` po każdej dużej zmianie.

### Pliki do utworzenia (Etap 6)
- `tests/PhpUnit/ImportManager/Parsers/CsvParserTest.php`, `XmlParserTest.php` – testy jednostkowe parserów i autodetekcji.
- `tests/PhpUnit/ImportManager/Services/DuplicateResolverTest.php`, `RecordValidatorTest.php` – walidacja logiki biznesowej.
- `tests/Integration/ImportManager/ImportFlowTest.php` – scenariusz staging → import dla przykładowego modułu.
- `tests/e2e/tests/Modules/ImportManager/wizard.spec.ts` – przejście kreatora + import.
- `tests/e2e/tests/Modules/ImportManager/retry.spec.ts` – scenariusz błędów i ponownego importu.

## Etap 7 – Dokumentacja i rollout
1. **Dokumentacja użytkownika**  
   - Krótkie HOWTO w `/documentation/` (opis kreatora, retry, strategie duplikatów).  
   - Aktualizacja README modułu (jeśli istnieje).  
2. **Migracja danych**  
   - Jeżeli legacy Import ma otwarte batch-e, przygotować skrypt migracyjny (opcjonalnie).  
3. **Plan wdrożenia**  
   - Kolejność migracji DB → deploy kodu → uruchomienie crona / queue.  
   - Checklisty rollbacku (np. usunięcie staging tables).

### Pliki do utworzenia (Etap 7)
- `documentations/importmanager-user-guide.md` – instrukcja użytkownika opisująca kreator, retry oraz strategie duplikatów.
- `modules/ImportManager/README.md` – skrót architektury modułu i kroki wdrożenia (dla devopsów).
- `deploy/checklists/importmanager-rollout.md` – plan wdrożeniowy z kolejnością migracji, restartów cronów i rollbackiem stagingu.

---

Realizacja etapów w tej kolejności pozwala szybko uzyskać działający import (po Etapie 3), a następnie stopniowo dodawać narzędzia korekcyjne, kolejkę i testy automatyczne.

