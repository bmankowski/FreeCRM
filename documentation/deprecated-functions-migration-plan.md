# Plan Migracji Funkcji Deprecated w FreeCRM

## Analiza Użycia Funkcji Deprecated

### 1. Klasa `vtlib\Deprecated` - Główne Źródło Deprecated Funkcji

Klasa `vtlib\Deprecated` zawiera 15 funkcji deprecated używanych w całym kodzie:

#### Funkcje i ich użycia:

| Funkcja | Liczba użyć | Lokalizacje | Priorytet |
|---------|-------------|-------------|-----------|
| `getSqlForNameInDisplayFormat()` | ~40+ | CustomView, Reports, Accounts, Leads, Documents, HelpDesk, IStorages, OSSEmployees, OSSMailView | **Wysoki** |
| `checkFileAccessForInclusion()` | ~15+ | CRMEntity, Loader, SMSNotifier, PrivilegeFileManager, Link, ShortURL | **Wysoki** |
| `getFullNameFromArray()` | ~5+ | CustomView, Users/Record, Import | **Średni** |
| `getFullNameFromQResult()` | ~3+ | Users/Record, PrivilegeUtil | **Średni** |
| `getModuleTranslationStrings()` | ~3+ | CustomView, Calendar | **Średni** |
| `checkFileAccess()` | ~4+ | ModuleImport, CronRunner | **Średni** |
| `checkFileAccessForDeletion()` | ~4+ | ModuleImport | **Średni** |
| `getBlockId()` | ~4+ | Oss_Tool | **Niski** |
| `createModuleMetaFile()` | ~2+ | InitSchema, ModuleService | **Niski** |
| `return_app_list_strings_language()` | ~2+ | ModuleTypes | **Niski** |
| `isFileAccessible()` | ~1+ | Link | **Niski** |

**Łącznie: ~80+ użyć w kodzie produkcyjnym**

### 2. Metody `Record::getCurrentUserModel()` i `Record::getCurrentUserId()`

**Status:** Oznaczone jako `@deprecated` w `src/Modules/Users/Models/Record.php`

**Użycia:**
- `getCurrentUserModel()`: ~150+ użyć
- `getCurrentUserId()`: ~50+ użyć

**Zastąpienie:** `$request->getUser()` i `$request->getUserId()`

### 3. Klasa `App\User\CurrentUser`

**Status:** Cała klasa oznaczona jako deprecated

**Użycia:** ~125+ użyć (głównie w starszym kodzie)

**Zastąpienie:** `$request->getUser()`

### 4. Adaptery w `ModuleManagement/Adapters/`

**Status:** Oznaczone jako deprecated

- `vtlib\Module` → `App\ModuleManagement\Services\ModuleService`
- `vtlib\Block` → `App\ModuleManagement\Services\BlockService`
- `vtlib\Field` → `App\ModuleManagement\Services\FieldService`
- `vtlib\LanguageExport` → Minimal stub (do usunięcia)

---

## Wielofazowy Plan Migracji

### Faza 0: Przygotowanie i Analiza (Tydzień 1)

**Cel:** Przygotowanie infrastruktury i narzędzi do migracji

#### Zadania:
1. ✅ Utworzenie dokumentacji użycia deprecated funkcji
2. Utworzenie narzędzi do śledzenia użyć deprecated funkcji
3. Skonfigurowanie logowania wywołań deprecated funkcji
4. Utworzenie testów regresyjnych dla obszarów z deprecated funkcjami

#### Deliverables:
- Dokumentacja użycia (ten dokument)
- Skrypt do analizy użyć deprecated funkcji
- System logowania wywołań deprecated funkcji
- Testy regresyjne

---

### Faza 1: Migracja Funkcji Bezpieczeństwa Plików (Tydzień 2-3)

**Cel:** Zastąpienie funkcji bezpieczeństwa plików nowoczesnymi odpowiednikami

**Priorytet:** Wysoki (bezpieczeństwo)

#### Funkcje do migracji:
- `checkFileAccessForInclusion()` → `App\Security\FileAccessValidator::validateForInclusion()`
- `checkFileAccess()` → `App\Security\FileAccessValidator::validate()`
- `checkFileAccessForDeletion()` → `App\Security\FileAccessValidator::validateForDeletion()`
- `isFileAccessible()` → `App\Security\FileAccessValidator::isAccessible()`

#### Plan działania:
1. Utworzenie klasy `App\Security\FileAccessValidator`
2. Migracja użyć w kolejności:
   - `src/Loader.php` (1 użycie)
   - `src/CRMEntity.php` (2 użycia)
   - `src/Modules/SMSNotifier/SMSNotifierBase.php` (2 użycia)
   - `src/Modules/Users/Services/PrivilegeFileManager.php` (4 użycia)
   - `src/ModuleManagement/Adapters/Link.php` (2 użycia)
   - `src/Modules/Base/Helpers/ShortURL.php` (1 użycie)
   - `src/Modules/Settings/ModuleManager/Views/ModuleImport.php` (6 użyć)
   - `src/Modules/Cron/Runner/CronRunner.php` (1 użycie)
3. Dodanie deprecation warnings do starych funkcji
4. Testy bezpieczeństwa

#### Deliverables:
- Nowa klasa `FileAccessValidator`
- Wszystkie użycia zmigrowane
- Testy bezpieczeństwa
- Dokumentacja nowego API

**Szacowany czas:** 2 tygodnie

---

### Faza 2: Migracja Funkcji Formatowania Nazw (Tydzień 4-6)

**Cel:** Zastąpienie funkcji formatowania nazw nowoczesnymi odpowiednikami

**Priorytet:** Wysoki (używane w wielu miejscach)

#### Funkcje do migracji:
- `getSqlForNameInDisplayFormat()` → `App\Utils\NameFormatter::getSqlForDisplayFormat()`
- `getFullNameFromArray()` → `App\Utils\NameFormatter::getFullNameFromArray()`
- `getFullNameFromQResult()` → `App\Utils\NameFormatter::getFullNameFromQueryResult()`

#### Plan działania:
1. Utworzenie klasy `App\Utils\NameFormatter`
2. Migracja użyć w kolejności (najpierw najczęściej używane):
   - `src/Modules/Reports/ReportRun.php` (~15 użyć)
   - `src/Modules/CustomView/CustomView.php` (~6 użyć)
   - `src/Modules/Accounts/Accounts.php` (3 użycia)
   - `src/Modules/Leads/Leads.php` (1 użycie)
   - `src/Modules/Documents/Documents.php` (1 użycie)
   - `src/Modules/HelpDesk/HelpDesk.php` (1 użycie)
   - `src/Modules/IStorages/IStorages.php` (2 użycia)
   - `src/Modules/OSSEmployees/OSSEmployees.php` (2 użycia)
   - `src/Modules/OSSMailView/Models/Record.php` (1 użycie)
   - `src/Modules/OSSMailView/Views/sview.php` (1 użycie)
   - `src/Modules/OSSMailView/Views/preview.php` (1 użycie)
   - `src/Modules/Reports/Models/ListView.php` (1 użycie)
   - `src/Modules/Users/Models/Record.php` (2 użycia)
   - `src/Modules/Import/Actions/Data.php` (1 użycie)
   - `src/PrivilegeUtil.php` (1 użycie)
3. Testy formatowania dla różnych modułów
4. Weryfikacja poprawności SQL queries

#### Deliverables:
- Nowa klasa `NameFormatter`
- Wszystkie użycia zmigrowane
- Testy formatowania
- Dokumentacja nowego API

**Szacowany czas:** 3 tygodnie

---

### Faza 3: Migracja Funkcji Tłumaczeń (Tydzień 7-8)

**Cel:** Zastąpienie funkcji tłumaczeń nowoczesnymi odpowiednikami

**Priorytet:** Średni

#### Funkcje do migracji:
- `getModuleTranslationStrings()` → `App\Runtime\Vtiger_Language_Handler::getModuleStrings()` (już istnieje)
- `return_app_list_strings_language()` → `App\Runtime\Vtiger_Language_Handler::getAppListStrings()`

#### Plan działania:
1. Sprawdzenie czy nowe funkcje już istnieją w `Vtiger_Language_Handler`
2. Jeśli nie, utworzenie nowych metod
3. Migracja użyć:
   - `src/Modules/CustomView/CustomView.php` (1 użycie)
   - `src/Modules/Calendar/CalendarCommon.php` (1 użycie)
   - `src/Webservices/ModuleTypes.php` (2 użycia)
4. Testy tłumaczeń

#### Deliverables:
- Zaktualizowane metody w `Vtiger_Language_Handler`
- Wszystkie użycia zmigrowane
- Testy tłumaczeń

**Szacowany czas:** 2 tygodnie

---

### Faza 4: Migracja Funkcji Zarządzania Modułami (Tydzień 9-10)

**Cel:** Zastąpienie funkcji zarządzania modułami nowoczesnymi odpowiednikami

**Priorytet:** Niski (używane rzadko)

#### Funkcje do migracji:
- `createModuleMetaFile()` → `App\ModuleManagement\Services\ModuleService::createMetaFile()`
- `getBlockId()` → `App\ModuleManagement\Services\BlockService::getIdByLabel()`
- `getSettingsBlockId()` → `App\ModuleManagement\Services\BlockService::getSettingsIdByLabel()`

#### Plan działania:
1. Dodanie metod do odpowiednich serwisów
2. Migracja użyć:
   - `src/Modules/Install/Models/InitSchema.php` (1 użycie)
   - `src/ModuleManagement/Services/ModuleService.php` (1 użycie)
   - `src/Modules/Settings/DataAccess/Helpers/Oss_Tool.php` (4 użycia)
3. Testy zarządzania modułami

#### Deliverables:
- Nowe metody w serwisach
- Wszystkie użycia zmigrowane
- Testy zarządzania modułami

**Szacowany czas:** 2 tygodnie

---

### Faza 5: Migracja CurrentUser - Część 1 (Tydzień 11-14)

**Cel:** Migracja użyć `Record::getCurrentUserModel()` i `Record::getCurrentUserId()`

**Priorytet:** Wysoki (używane bardzo często)

**Uwaga:** Ta faza jest częściowo opisana w `documentation/getting-rid-of-current-user-from-controllers.md`

#### Plan działania:
1. **Tydzień 11:** Migracja kontrolerów i akcji
   - Przekazywanie `$request->getUser()` do metod modeli
   - Migracja ~30 kontrolerów/akcji

2. **Tydzień 12:** Migracja modeli - część 1
   - `src/Modules/Base/Models/Record.php`
   - `src/Modules/Users/Models/Record.php`
   - `src/Modules/CustomView/Models/Record.php`
   - Migracja ~50 użyć

3. **Tydzień 13:** Migracja modeli - część 2
   - `src/Modules/Reports/Models/Record.php`
   - `src/Modules/Base/Models/Module.php`
   - `src/Modules/Base/Models/DashBoard.php`
   - Migracja ~40 użyć

4. **Tydzień 14:** Migracja pozostałych miejsc
   - Helpery, utilities, services
   - Migracja ~30 użyć

#### Deliverables:
- Wszystkie użycia `getCurrentUserModel()` zmigrowane
- Wszystkie użycia `getCurrentUserId()` zmigrowane
- Metody oznaczone jako deprecated z trigger_error()
- Testy regresyjne

**Szacowany czas:** 4 tygodnie

---

### Faza 6: Migracja CurrentUser - Część 2 (Tydzień 15-16)

**Cel:** Migracja użyć `App\User\CurrentUser`

**Priorytet:** Średni

#### Plan działania:
1. Identyfikacja wszystkich użyć `CurrentUser::get()` i `CurrentUser::getId()`
2. Migracja do `$request->getUser()` i `$request->getUserId()`
3. Dla miejsc bez dostępu do request, użycie fallback do session
4. Testy

#### Deliverables:
- Wszystkie użycia `CurrentUser` zmigrowane
- Klasa `CurrentUser` może pozostać jako facade z deprecation warning
- Testy

**Szacowany czas:** 2 tygodnie

---

### Faza 7: Migracja Adapterów ModuleManagement (Tydzień 17-18)

**Cel:** Migracja użyć adapterów do nowych serwisów

**Priorytet:** Średni

#### Plan działania:
1. Identyfikacja wszystkich użyć adapterów:
   - `vtlib\Module`
   - `vtlib\Block`
   - `vtlib\Field`
   - `vtlib\LanguageExport`

2. Migracja do odpowiednich serwisów:
   - `App\ModuleManagement\Services\ModuleService`
   - `App\ModuleManagement\Services\BlockService`
   - `App\ModuleManagement\Services\FieldService`

3. Usunięcie `LanguageExport` (stub)

#### Deliverables:
- Wszystkie użycia adapterów zmigrowane
- Adaptery pozostają jako facades z deprecation warnings
- Testy

**Szacowany czas:** 2 tygodnie

---

### Faza 8: Oznaczenie Deprecated i Dokumentacja (Tydzień 19-20)

**Cel:** Dodanie deprecation warnings i aktualizacja dokumentacji

#### Zadania:
1. Dodanie `trigger_error(E_USER_DEPRECATED)` do wszystkich deprecated funkcji
2. Aktualizacja PHPDoc z `@deprecated` i informacją o zastąpieniu
3. Utworzenie migracji guide dla deweloperów
4. Aktualizacja dokumentacji API
5. Komunikacja do społeczności (jeśli dotyczy)

#### Deliverables:
- Wszystkie deprecated funkcje z trigger_error()
- Zaktualizowana dokumentacja
- Migration guide
- Komunikacja do społeczności

**Szacowany czas:** 2 tygodnie

---

### Faza 9: Monitoring i Optymalizacja (Tydzień 21-24)

**Cel:** Monitorowanie użyć deprecated funkcji i optymalizacja

#### Zadania:
1. Monitoring logów deprecated warnings przez 1 miesiąc
2. Identyfikacja pozostałych użyć (jeśli są)
3. Optymalizacja nowych funkcji na podstawie użycia
4. Aktualizacja testów
5. Dokumentacja best practices

#### Deliverables:
- Raport z monitoringu
- Optymalizacje
- Zaktualizowane testy
- Best practices guide

**Szacowany czas:** 4 tygodnie (monitoring)

---

### Faza 10: Usunięcie Deprecated Code (v2.0 - Future)

**Cel:** Usunięcie wszystkich deprecated funkcji

**Timeline:** 6-12 miesięcy po Fazie 9

#### Zadania:
1. Usunięcie klasy `vtlib\Deprecated`
2. Usunięcie metod `Record::getCurrentUserModel()` i `Record::getCurrentUserId()`
3. Usunięcie klasy `App\User\CurrentUser`
4. Usunięcie adapterów `ModuleManagement/Adapters/`
5. Finalne testy
6. Release notes dla v2.0

#### Deliverables:
- Wszystkie deprecated funkcje usunięte
- Czysta architektura
- v2.0 release

**Szacowany czas:** 2-4 tygodnie (po okresie stabilizacji)

---

## Priorytetyzacja

### Wysoki Priorytet (Bezpieczeństwo i Częstość Użycia):
1. ✅ Faza 1: Funkcje bezpieczeństwa plików
2. ✅ Faza 2: Funkcje formatowania nazw
3. ✅ Faza 5: CurrentUser - część 1

### Średni Priorytet:
4. ✅ Faza 3: Funkcje tłumaczeń
5. ✅ Faza 6: CurrentUser - część 2
6. ✅ Faza 7: Adaptery ModuleManagement

### Niski Priorytet:
7. ✅ Faza 4: Funkcje zarządzania modułami

### Maintenance:
8. ✅ Faza 8: Dokumentacja
9. ✅ Faza 9: Monitoring
10. ✅ Faza 10: Usunięcie (v2.0)

---

## Metryki Sukcesu

### Przed Migracją:
- ~80+ użyć `vtlib\Deprecated`
- ~200+ użyć `getCurrentUserModel()`/`getCurrentUserId()`
- ~125+ użyć `CurrentUser::get()`

### Po Fazie 9:
- 0 użyć deprecated funkcji w nowym kodzie
- <5 użyć deprecated funkcji w legacy kodzie (z planem migracji)
- Wszystkie nowe funkcje używają nowoczesnych API

### Po Fazie 10 (v2.0):
- 0 deprecated funkcji w kodzie
- Czysta architektura

---

## Ryzyka i Mitigacja

### Ryzyko 1: Breaking Changes dla Modułów Zewnętrznych
**Mitigacja:**
- Zachowanie backward compatibility przez facades
- Okres deprecation 6-12 miesięcy
- Dokumentacja migracji dla deweloperów

### Ryzyko 2: Błędy w Migracji Funkcji Formatowania
**Mitigacja:**
- Szczegółowe testy dla każdego modułu
- Weryfikacja SQL queries
- Code review dla każdej migracji

### Ryzyko 3: Problemy z Bezpieczeństwem Plików
**Mitigacja:**
- Szczegółowe testy bezpieczeństwa
- Code review przez ekspertów bezpieczeństwa
- Testy penetracyjne

### Ryzyko 4: Wydajność Nowych Funkcji
**Mitigacja:**
- Benchmarki przed i po migracji
- Monitoring wydajności
- Optymalizacja na podstawie wyników

---

## Narzędzia i Wsparcie

### Narzędzia do Migracji:
1. **Skrypt analizy użyć:**
   ```bash
   php scripts/analyze-deprecated-usage.php
   ```

2. **Rector rules** (jeśli dostępne):
   - Automatyczna migracja prostych przypadków
   - Refaktoryzacja do nowych API

3. **Testy regresyjne:**
   - Automatyczne testy dla każdej fazy
   - Testy integracyjne

### Dokumentacja:
- Migration guide dla każdej fazy
- API documentation dla nowych funkcji
- Best practices guide

---

## Timeline Podsumowanie

| Faza | Czas | Status |
|------|------|--------|
| Faza 0: Przygotowanie | 1 tydzień | ⏳ Do rozpoczęcia |
| Faza 1: Bezpieczeństwo plików | 2 tygodnie | ⏳ Do rozpoczęcia |
| Faza 2: Formatowanie nazw | 3 tygodnie | ⏳ Do rozpoczęcia |
| Faza 3: Tłumaczenia | 2 tygodnie | ⏳ Do rozpoczęcia |
| Faza 4: Zarządzanie modułami | 2 tygodnie | ⏳ Do rozpoczęcia |
| Faza 5: CurrentUser część 1 | 4 tygodnie | ⏳ Do rozpoczęcia |
| Faza 6: CurrentUser część 2 | 2 tygodnie | ⏳ Do rozpoczęcia |
| Faza 7: Adaptery | 2 tygodnie | ⏳ Do rozpoczęcia |
| Faza 8: Dokumentacja | 2 tygodnie | ⏳ Do rozpoczęcia |
| Faza 9: Monitoring | 4 tygodnie | ⏳ Do rozpoczęcia |
| Faza 10: Usunięcie (v2.0) | 2-4 tygodnie | 🔮 Future |

**Całkowity czas:** ~24 tygodnie (6 miesięcy) + monitoring + v2.0

---

## Następne Kroki

1. ✅ Utworzenie tego dokumentu
2. Review planu przez zespół
3. Rozpoczęcie Fazy 0: Przygotowanie
4. Utworzenie narzędzi do analizy i migracji
5. Rozpoczęcie Fazy 1: Migracja funkcji bezpieczeństwa

---

## Notatki

- Plan może być modyfikowany w trakcie implementacji
- Każda faza powinna być testowana przed przejściem do następnej
- Backward compatibility musi być zachowana przez cały okres migracji
- Komunikacja z społecznością jest ważna dla modułów zewnętrznych




