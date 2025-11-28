# FreeCRM – nowy moduł ImportManager

**Cel**: Dostarczenie lekkiego modułu importu rekordów, który działa w pełni po stronie FreeCRM i nie wykorzystuje żadnych mechanizmów vtiger Web Services. Moduł ma obsługiwać pliki CSV (z różnymi separatorami i kodowaniami) oraz XML.

## Zakres i ograniczenia
- Obsługiwane formaty wejściowe: CSV, XML.
- Import dotyczy istniejących modułów (np. Kontakty, Szanse) i zapisuje rekordy przez natywne modele FreeCRM (`FreeCRM\Modules\*\Models`), bez wywołań vtiger WS.
- Kreator pokazuje wszystkie moduły standardowe i customowe dostępne w FreeCRM (`\App\Module::getEntityModules()`), filtrując listę według uprawnień użytkownika.
- Pierwsza wersja skupia się na imporcie ręcznym (upload pliku + podgląd + uruchomienie). Dłuższe zadania mogą być oddelegowane do kolejki, ale dopiero kiedy pojawi się realna potrzeba.
- Minimalne UI: kreator w 3 krokach (upload → mapowanie → potwierdzenie).
- Nazwa nowego modułu: **ImportManager** (oddzielny od istniejącego legacy modułu `Import`).
- Warstwa dostępu do danych operuje na `App\Db\Db` (komponent Yii2); nie korzystamy z `PearDatabase`.

## Lokalizacja w repozytorium
- Kod modułu (kontrolery, akcje, serwisy): `src/Modules/ImportManager/`
  - `Controllers/` – nowoczesne kontrolery (`WizardController`, `BatchController`) wywoływane z routera lub cienkich wrapperów vtlib.
  - `Actions/` – klasy wykonujące konkretne operacje (upload, zapis mapowania, start batcha).
  - `Services/`, `Parsers/`, `Jobs/` – logika domenowa.
- Warstwa vtlib: `modules/ImportManager/`
  - `Models/` – minimalne klasy wymagane przez UI (np. listview linki), korzystające z domyślnego ACL FreeCRM (brak niestandardowych reguł).
  - `Views/` – cienkie kontrolery/widoki integrujące się z layoutem i delegujące do klas z `src/`.
- Szablony Smarty dla widoków modułu umieszczamy w `layouts/basic/modules/ImportManager/` (np. `WizardStep1.tpl`, `Preview.tpl`), aby pozostać spójnym z podstawową skórką.
- Zasoby frontowe (JS/CSS) trafiają do `public/layouts/basic/modules/ImportManager/`.
- Pliki tymczasowe: `storage/imports/{batchId}/…`.

## Konfiguracja modułu
- Parametry modułu przechowujemy w `config/modules/ImportManager.php`, tj. w tym samym katalogu, w którym znajdują się konfiguracje pozostałych modułów FreeCRM.
- Proponowana struktura:
  - `fileLimits` – limity uploadu (rozmiar w MB, dozwolone rozszerzenia, timeout parsowania). Wersja startowa: `maxUploadSize = 10` (MB) dla CSV/XML/ZIP, `allowedExtensions = ['csv','xml','zip']`.
  - `preview` – liczba rekordów ładowanych do podglądu/mapowania oraz ustawienia paginacji; domyślnie `rows = 30`.
  - `staging` – wielkość paczki (`chunkSize`), prefiks nazwy tabeli tymczasowej, flagi walidacji. Przyjmujemy `chunkSize = 200`, co jest kompromisem między wydajnością a zużyciem pamięci.
  - `queue` – stała konfiguracja `driver => vtiger_import_queue` oraz progi obciążenia kolejki.
  - `cleanup` – retencja logów i plików (`retentionDays = 2`), harmonogram sprzątania oraz limity jednorazowego purge’a.
- UI (limity uploadu, komunikaty) i backend (cleanup, staging, kolejka) zawsze czytają wartości z tego pliku.

## Najważniejsze komponenty

| Warstwa | Klasa / komponent | Rola |
| --- | --- | --- |
| Interfejs | `FreeCRM\Modules\ImportManager\Controllers\WizardController` (wywoływany z `modules/ImportManager/views/Wizard.php`) | Obsługa kreatora, autoryzacja, walidacja requestów, koordynacja kroków |
| Serwis główny | `FreeCRM\Modules\ImportManager\Services\BatchProcessor` | Orkiestruje cały proces: wybór parsera, mapowanie, staging, finalny zapis |
| Parsowanie | `ParserFactory`, `CsvParser`, `XmlParser` | Jednolity interfejs `ParserInterface::readChunk()` zwracający kolekcje rekordów |
| CSV | `CsvParser` + `DelimiterDetector` | Wsparcie dla separatorów `, ; \t |` (wskazane ręcznie lub autodetekcja na podstawie pierwszych N wierszy) |
| XML | `XmlParser` | Oczekuje definicji ścieżki do elementów rekordów (np. XPath) |
| Mapowanie | `MappingDefinition`, `FieldMapper` | Łączy kolumny/elementy z polami modułu; wspiera zapis map w DB (`import_mappings` tabela) |
| Walidacja | `RecordValidator` | Odpala Yii2 validators + walidacje modułowe (np. duplikaty) |
| Staging | `TemporaryTableManager`, `StagingWriter` | Tworzy tymczasową tabelę (`import_stage_{module}_{batchId}`) odwzorowującą strukturę modułu (schema pobierany dynamicznie z `App\Db\Db::getTableSchema`) i ładuje tam rekordy |
| Zapis | `RecordPersister` | Wysyła dane przez native modele FreeCRM w transakcji (chunk) po pozytywnej walidacji stagingu |
| Logowanie | `ImportLogService` | Zapisuje sukcesy/błędy do tabeli `import_logs` i do pliku `storage/imports/{batchId}/log.json` |
| Kolejka | `ImportJob` + `ImportJobProcessor` | Rejestruje wsady w `vtiger_import_queue`, a cron legacy `Import` odpala `BatchProcessor`; brak fallbacku do Yii2 DB Queue w tej wersji |

## Przepływ danych
1. **Upload** – plik trafia do `storage/imports/{batchId}`. Użytkownik deklaruje format, separator (opcjonalnie `auto`) oraz moduł docelowy.
2. **Podgląd** – `BatchProcessor` parsuje pierwsze N rekordów i zwraca je do UI w celu ustawienia mapowania.
3. **Mapowanie** – UI zapisuje `MappingDefinition` (kolumna/ścieżka → pole modułu), inkl. opcjonalne reguły (np. domyślne wartości, trim, data format). Pola referencyjne mapujemy wyłącznie po etykiecie rekordu (oryginalnej lub przetłumaczonej przez `vtranslate`); `FieldMapper` wykonuje lookup po labelu i odrzuca mapowanie, jeśli dopasowanie nie jest jednoznaczne.
4. **Załaduj do stagingu** – `StagingWriter` tworzy tymczasową tabelę `import_stage_{module}_{batchId}` (strukturą zbliżoną do tabeli modułu + metadane importu) i ładuje tam wszystkie rekordy, normalizując dane. Może istnieć wiele tabel stagingowych jednocześnie – nazwa zawiera moduł i identyfikator wsadu, więc współbieżne importy nie kolidują.
5. **Walidacja stagingu** – `RecordValidator` odpala walidacje już na poziomie tymczasowej tabeli:
   - Walidatory wynikające z typu pola (np. email, telefon, referencje) uruchamiane są po wczytaniu rekordów do stagingu.
   - Sprawdzane są klucze unikatowe, obowiązkowe pola oraz poprawność relacji (np. istnienie rekordów odniesienia).
6. **Import właściwy** – `BatchProcessor` pobiera paczki ze stagingu (np. 200 ID), mapuje/łączy i zapisuje przy pomocy `RecordPersister`, używając `App\Db\Db` do transakcji.
7. **Raport** – na końcu generowany jest raport (liczba sukcesów, błędów, ostrzeżeń) przechowywany w bazie i możliwy do pobrania jako CSV/JSON.

> Aktualny stan: `RecordPersister` obsługuje strategie `skip`, `overwrite`, `merge`, wykrywa duplikaty przez `DuplicateResolver` i zapisuje logi do `import_logs` oraz `storage/imports/{batchId}/log.json`. Kreator pozwala uruchomić import od razu lub przekazać go do `vtiger_import_queue` (typ zadania `import`).

## UI/UX kreatora i widoków
- **Krok 1 – Plik i moduł**: drag&drop + fallback upload, odczyt limitów z `fileLimits`, wybór modułu z listy dostępnych standardowych i customowych modułów. Sekcja parametrów pozwala wymusić separator/znak cytowania i informuje czy plik ZIP zostanie rozpakowany. Po poprawnym uploadzie aktywuje się przycisk „Podgląd” ładowania pierwszych N rekordów (`preview.rows`).
- **Krok 2 – Mapowanie**: tabela (kolumny wejściowe → pola modułu) z filtrami po typie. Panel boczny „Domyślne wartości” renderuje widgety zgodnie z typem pola. Sekcja „Identyfikacja duplikatów” prezentuje zestawy z `config/import_duplicates.php` i pozwala użytkownikowi przełączać, które zestawy będą aktywne w tym wsadzie oraz wskazać strategię (pomijaj/nadpisuj/łącz).
- **Krok 3 – Potwierdzenie**: podsumowanie pliku, modułu, aktywnych zestawów duplikatów, strategii i liczby rekordów. Wyświetlamy ostrzeżenia (np. niemapowane kolumny) i komunikaty o blokadach. Start importu przekierowuje do widoku postępu.
- **Lista wsadów**: tabela `import_batches` z filtrami, statusem, modułem, liczbą rekordów, strategią duplikatów i datami. Akcje: podejrzyj raport, eksport błędów, ponów błędne.
- **Szczegóły wsadu**: progress bar obejmujący upload → staging → walidację → import, live statystyki sukcesów/błędów, link do logu (`storage/imports/{batchId}/log.json`), panel retry.
- **Retry grid**: tabela rekordów `failed` umożliwia inline edycję pól (z walidacją typu) i oznaczanie `retry_token`. UI oraz API blokują edycję, gdy wsad ma status `running`; ponowne próby stają się dostępne po zakończeniu batcha.

## Obsługa CSV
- `CsvParser` korzysta z `SplFileObject` + ustawionego separatora.
- `DelimiterDetector` analizuje pierwsze 5 wierszy i wybiera separator z największą spójnością (liczba pól).
- Obsługiwane kodowania: automatyczna detekcja (mb_detect_encoding) i konwersja do UTF-8.
- Moduł pozwala użytkownikowi wymusić separator oraz znak cytowania, aby uniknąć błędnej autodetekcji.

## Obsługa XML
- Wymagane podanie XPath (np. `/Records/Record`) wskazującego pojedynczy rekord.
- Każde pole w mapowaniu posiada XPath względem rekordu (np. `Contact/Email`).
- Parser działa strumieniowo (`XMLReader`) – nie wczytujemy całego pliku do pamięci.

## Obsługa ZIP
- `ZipInspector` analizuje zawartość archiwum po uploadzie:
  - Jeśli ZIP zawiera pojedynczy plik CSV → wypakowanie do `storage/imports/{batchId}` i przekazanie do `CsvParser`.
  - Jeśli ZIP zawiera pojedynczy plik XML → przekazanie do `XmlParser`.
  - Jeśli ZIP zawiera wiele plików lub rozszerzenia nieobsługiwane, batch otrzymuje status błędu z opisem (brak importu częściowego).
- UI komunikatu informuje użytkownika, które pliki zostały użyte; w planach rozwoju można rozszerzyć obsługę o inne formaty.

## Domyślne wartości pól
- Formularz mapowania zawiera panel „Domyślne wartości” inspirowany legacy importem:
  - Dla każdego pola modułu renderujemy widget zgodny z typem (`picklist`, `owner`, `date`, itp.).
  - Użytkownik może wskazać wartość domyślną stosowaną, gdy w danych wejściowych kolumna jest pusta.
- `MappingDefinition` przechowuje zmapowane wartości domyślne i przekazuje je do `FieldMapper` → `StagingWriter`. Dzięki temu domyślne wartości trafiają już do stagingu i przechodzą pełną walidację.

## Model danych
- `import_batches` – metadane wsadów (użytkownik, moduł docelowy, status, ścieżka pliku, rozmiar).
- `import_mappings` – zserializowane definicje mapowań powiązane z wsadem.
- `import_logs` – szczegółowy log (rekord, status, komunikat). Do szybkiego podglądu wystarczy tekst + JSON payload.
- `import_stage_{module}_{batchId}` – tymczasowa tabela tworzona na czas importu; struktura kolumn pobierana dynamicznie z docelowej tabeli przez `App\Db\Db::getTableSchema`, ale tworzymy ją jako płaską tabelę bez dodatkowych indeksów/constraintów (walidacja odbywa się w aplikacji). Zawiera pola pomocnicze (`row_hash`, `validation_status`, `error_payload`, `retry_token`). Może istnieć wiele tabel stagingowych równolegle – każda jest zarządzana przez `TemporaryTableManager` i usuwana po zakończeniu wsadu.
- Tabele są proste, brak skomplikowanych relacji – klucz obcy `batch_id`.

## Import bez częściowych zapisów
- `TemporaryTableManager` zawsze pracuje w transakcjach `App\Db\Db::getInstance()`, dzięki czemu utworzenie tabeli stagingowej i masowy insert są atomowe.
- Finalny zapis do rekordów modułu odbywa się paczkami, ale każda paczka to osobna transakcja – w przypadku błędu transakcja jest wycofywana, a wiersze w stagingu otrzymują status `failed`, więc główna tabela pozostaje nienaruszona.
- Raport końcowy jest generowany na podstawie stagingu, dzięki czemu użytkownik może poprawić błędy i ponownie uruchomić import bez duplikowania danych w tabeli docelowej.
- UI udostępnia dwa narzędzia naprawcze:
  1. **Eksport błędów** – CSV/JSON zawierający tylko wiersze niepoprawne.
  2. **Grid korekcyjny** – widok w `modules/ImportManager/views/Retry.php` renderuje tabelę z rekordami `failed`, umożliwiając edycję pojedynczych pól inline (z walidacją typu). Po zapisaniu zmian `RetryManager` aktualizuje staging i oznacza rekord do ponownego przetworzenia.
- Dodatkowo dostępna jest akcja „uruchom ponownie tylko niepoprawne wiersze” – po poprawkach (czy to z gridu, czy z pliku korekcyjnego) `RetryManager` ponownie uruchamia import dla rekordów oznaczonych `retry_token`.
- API oraz UI odmawiają edycji mapowania, stagingu i retry, jeżeli `import_batches.status = running`, dzięki czemu nie ma ryzyka kolizji z aktualnie przetwarzanym wsadem.
- Dla dużych wsadów staging (i później import właściwy) mogą zostać zlecone do kolejki (`vtiger_import_queue`). Kreator informuje użytkownika, że zadanie zostało dodane do tła, a worker `cron/ImportManager/Import.php` uruchomi je automatycznie.

> Aktualnie (Etap 4) widok Retry wraz z eksportem i zapisem poprawek jest dostępny z poziomu kreatora („Przygotuj dane” → „Popraw błędne rekordy”). Każda edycja oznacza rekord `retry_token`, więc kolejne etapy importu będą mogły przetwarzać jedynie poprawione pozycje.

## Polityka duplikatów
- Detekcja: `DuplicateResolver` korzysta z konfiguracji per moduł (`config/import_duplicates.php`) jedynie dla zestawów opcjonalnych. Wymagane zestawy wynikają bezpośrednio z pól oznaczonych jako obowiązkowe w `vtiger_field`, więc nie trzeba utrzymywać ich ręcznie. Podczas mapowania UI wymusza przypięcie kolumn do każdego wymaganego zestawu, a użytkownik może dodatkowo włączać/wyłączać zestawy opcjonalne zapisane w konfiguracji.
- Panel „Identyfikacja duplikatów” w kroku mapowania pozwala przełączać zestawy z konfiguracji (o ile moduł ma kilka wariantów) oraz zapisuje wybór w `import_mappings`, aby raport jasno wskazywał, które reguły obowiązywały dla konkretnego wsadu.
- Strategie zachowania (wybierane oddzielnie przy każdym imporcie, domyślnie „pomijaj”):
  1. **Pomijaj** – rekord zostaje oznaczony jako `skipped_duplicate`, log zawiera ID rekordu powielonego.
  2. **Nadpisuj** – `RecordPersister` ładuje istniejący rekord i aktualizuje tylko pola z mapowania (pozostałe pozostają bez zmian).
  3. **Łącz** – `MergeStrategy` spina dane z importu i istniejącego rekordu wg reguł:
     - Pola tekstowe: wybór zgodnie z priorytetem (import > istniejące) z możliwością „zachowaj jeśli puste” per pole.
     - Listy wielokrotne (picklist multi, telefony, e-maile): unia wartości z deduplikacją prostą (case-insensitive).
     - Relacje (np. właściciel, firma nadrzędna): brak automatycznego scalania, chyba że mapa wskaże identyfikator – w przeciwnym razie pozostaje oryginał.
- Łączenie rekordów następuje zawsze po polach zdefiniowanych w konfiguracji `identityFields`. Dodatkowo można wskazać `mergeKeys` (np. `['Contacts' => ['email','mobile']]`) – jeżeli oba rekordy mają w tych polach tożsame wartości, unia nie duplikuje wpisów.
- Każde rozstrzygnięcie zapisywane jest w `import_logs` z informacją o strategii oraz ID rekordu źródłowego i docelowego, aby łatwo prześledzić historię scalania.

## Rozszerzalność bez nadmiernej złożoności
- Każdy parser implementuje ten sam interface, więc dodanie np. JSON sprowadza się do nowej klasy i wpisu w `ParserFactory`.
- Hooki/eventy:
  - `BatchProcessor::EVENT_BEFORE_STAGE_WRITE`
  - `BatchProcessor::EVENT_AFTER_STAGE_WRITE`
  - `BatchProcessor::EVENT_BEFORE_RECORD_PERSIST`
  - `BatchProcessor::EVENT_AFTER_RECORD_PERSIST`
  - Pozwala dołożyć własne walidacje lub enrichment bez modyfikacji core.
- Reguły mapowania można trzymać jako prosty JSON – brak potrzeby osobnego DSL.

## Bezpieczeństwo i audyt
- Dostęp do modułu chroniony przez uprawnienia (np. nowe prawo `ImportData`).
- Pliki przechowywane poza `public/`, usuwane przez cron po liczbie dni zdefiniowanej w `config/modules/ImportManager.php` (`cleanup.retentionDays`). Ta sama wartość steruje retencją logów i tabel stagingowych.
- Każdy batch zapisuje `created_by`, `started_at`, `finished_at`, co umożliwia audyt.

## Minimalny plan wdrożenia
1. Utworzenie tabel `import_batches`, `import_mappings`, `import_logs`.
2. Implementacja `src/Modules/ImportManager/Controllers/WizardController` + cienkich wrapperów vtlib (`modules/ImportManager/views`) odpowiadających za upload i kreator.
3. `BatchProcessor` + `StagingWriter` + `CsvParser` + podstawowy `RecordPersister`.
4. Raport końcowy + podgląd błędów.
5. Dodanie `XmlParser` i konfiguracji XPath.
6. Integracja z kolejką legacy (`vtiger_import_queue` + cron `Import`) dla wsadów uruchamianych w tle.

Taka architektura pozostaje prosta, ale pozwala skalować funkcje importu bez polegania na vtiger Web Services i bez nadmiernego rozbudowywania infrastruktury.

