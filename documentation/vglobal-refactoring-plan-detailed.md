# Szczegółowy plan refaktoryzacji - usuwanie vglobal() z kontrolerów

## Analiza wstępna

### Hierarchia kontrolerów
- Wszystkie kontrolery używające `PopupLayout.tpl`, `MainLayout.tpl`, `Header.tpl` dziedziczą z `BaseViewController` (poprzez `Basic` → `Index` → konkretne widoki)
- `BaseViewController::preProcess()` jest wywoływane przez wszystkie te kontrolery
- `OSSPasswords/Views/ListView` extends `Base\Views\Index`, więc używa hierarchii Base

---

## FAZA 1: Usunięcie `vglobal('listMaxEntriesMassEdit')`

### Kontrolery do modyfikacji:

#### 1. `src/Modules/Base/Views/ListView.php`
**Metoda:** `initializeListViewContents()` (linia 179)
**Powód:** Ta metoda jest wywoływana zarówno dla żądań AJAX (`prepareAjaxListViewData()`) jak i zwykłych (`preProcess()`, `process()`)

**Dodaj na końcu metody (przed zamknięciem):**
```php
$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
```

#### 2. `src/Modules/RecycleBin/Views/ListView.php`
**Metoda:** `preProcess()` (linia 22)
**Uwaga:** `RecycleBin/ListView` dziedziczy z `Base\Views\Index`, nie z `Base\Views\ListView`, więc nie używa `initializeListViewContents()`.
**Dodaj na końcu metody (po ostatnim `$viewer->assign()`, przed zamknięciem metody, około linii 130):**
```php
$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
```

#### 3. `src/Modules/Reports/Views/ListView.php`
**Metoda:** `prepareReportsListView()` (linia 39)
**Uwaga:** `Reports/ListView` dziedziczy z `Base\Views\Index`, nie z `Base\Views\ListView`, więc nie używa `initializeListViewContents()`.
**Dodaj na końcu metody (po ostatnim `$viewer->assign()`, przed zamknięciem metody, około linii 126):**
```php
$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
```

#### 4. `src/Modules/Settings/Base/Views/ListView.php`
**Metoda:** `prepareListViewData()` (linia 65)
**Powód:** `Users/ListView` dziedziczy z `Settings\Base\Views\ListView`, który używa metody `prepareListViewData()` zamiast `initializeListViewContents()`.
**Dodaj na końcu metody (przed zamknięciem, około linii 160):**
```php
$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
```
**Uwaga:** To pokryje wszystkie moduły Settings, w tym `Users/ListView`.

#### 5. `src/Modules/OSSPasswords/Views/ListView.php`
**Uwaga:** Ten kontroler nie nadpisuje `preProcess()` ani `process()`, więc używa `Base\Views\ListView::initializeListViewContents()` - już pokryte w punkcie 1.

---

## FAZA 2: Usunięcie `vglobal('backgroundClosingModal')`

### Kontroler do modyfikacji:

#### `src/Base/Controllers/BaseViewController.php`
**Metoda:** `preProcess()` (linia 29)
**Dodaj przed końcem metody (około linii 65, przed zamknięciem):**
```php
$viewer->assign('BACKGROUND_CLOSING_MODAL', \App\AppConfig::main('backgroundClosingModal'));
```

**Uzasadnienie:** Wszystkie kontrolery dziedziczące z `BaseViewController` wywołują `preProcess()`, więc jedno przypisanie pokrywa wszystkie przypadki.

---

## FAZA 3: Usunięcie `vglobal('systemMode')` i `vglobal('startTime')`

### 3.1. Usunięcie `vglobal('systemMode')` - CZĘŚCIOWO WYKONANE

**Status:** W kontrolerach widoków już wykonane (Login.php, ModuleManager/ListView.php), pozostały 3 wystąpienia w kodzie PHP.

#### Pozostałe pliki do modyfikacji:

##### 1. `src/Modules/Settings/ModuleManager/Views/ModuleImport.php`
**Metoda:** `process()` (linia 28)
**Zmiana:** Zamień `vglobal('systemMode')` na `AppConfig::main('systemMode')`

```php
// PRZED:
$systemMode = vglobal('systemMode');

// PO:
$systemMode = \App\AppConfig::main('systemMode');
```

##### 2. `src/Modules/Users/Models/DetailView.php`
**Metoda:** `getDetailViewLinks()` (linie 40, 52)
**Zmiana:** Zamień `vglobal('systemMode')` na `AppConfig::main('systemMode')` (2 wystąpienia)

```php
// PRZED:
if (vglobal('systemMode') != 'demo') {

// PO:
if (\App\AppConfig::main('systemMode') != 'demo') {
```

**Uwaga:** W tym pliku są 2 wystąpienia - oba należy zmienić.

### 3.2. Usunięcie `vglobal('startTime')`

#### Uwaga:
`startTime` jest już przypisywane jako `SCRIPT_TIME` w `BaseViewController::preProcess()` (linia 56), więc nie trzeba dodawać przypisania w kontrolerze.

---

## Kolejność wykonania

1. **Faza 1** - `listMaxEntriesMassEdit` (najbardziej używane)
2. **Faza 2** - `backgroundClosingModal` (proste, jedno przypisanie)
3. **Faza 3** - `systemMode` i `startTime` (dodatkowe, mniej krytyczne)
4. **Faza 4** - `upload_maxsize` (wartości konfiguracyjne w widokach Edit) - ✅ **WYKONANE**
5. **Faza 5** - `default_timezone`, `site_URL`, `cache_dir`, `tmp_dir` (wartości konfiguracyjne) - ✅ **WYKONANE**
6. **Faza 6** - `default_charset`, `php_max_execution_time`, `davStorageDir` (pozostałe wartości konfiguracyjne) - ✅ **WYKONANE**
7. **Faza 7** - Dokończenie `systemMode` (pozostałe 3 wystąpienia w kodzie PHP) - ✅ **WYKONANE**
8. **Faza 8** - `default_language` (wartość konfiguracyjna, 11 odczytów zmienionych w 6 plikach) - ✅ **WYKONANE**

---

## Weryfikacja po każdej fazie

Po każdej fazie należy:
1. Sprawdzić w przeglądarce, że wartości są dostępne w JavaScript (np. `document.getElementById('listMaxEntriesMassEdit').value`)
2. Sprawdzić logi: `cache/logs/system.log` pod kątem błędów
3. Przetestować podstawową funkcjonalność w przeglądarce (nie tylko CLI)

---

## Notatki techniczne

- `AppConfig::main()` automatycznie sprawdza `$GLOBALS`, więc jest kompatybilne z istniejącym kodem
- Jeśli `AppConfig::main('key')` zwróci `null` (brak klucza), wartość `null` zostanie przypisana do zmiennej Smarty. W Smarty `null` jest wyświetlane jako pusty string, co jest bezpieczne dla hidden inputów.
- Wszystkie kontrolery używające layout templates dziedziczą z `BaseViewController`
- `OSSPasswords/ListView` dziedziczy z `Base\Views\Index`, ale używa `Base\Views\ListView::initializeListViewContents()` poprzez `ListAjax` - już pokryte w punkcie 1
- `Users/ListView` dziedziczy z `Settings\Base\Views\ListView`, który używa `prepareListViewData()` zamiast `initializeListViewContents()` - pokryte w punkcie 4
- `RecycleBin/ListView` i `Reports/ListView` dziedziczą z `Base\Views\Index`, nie z `Base\Views\ListView`, więc wymagają osobnych przypisań
- `startTime` jest już obsługiwane jako `SCRIPT_TIME` w `BaseViewController::preProcess()` (linia 56)
- Funkcja `vglobal()` pozostanie w kodzie PHP dla kompatybilności wstecznej, ale planowane jest całkowite usunięcie w przyszłości

## Zależności między refaktoryzacjami

**Kolejność wykonania:**
1. Faza 1: Refaktoryzacja `listMaxEntriesMassEdit` (w tym planie)
2. Faza 2: Refaktoryzacja `backgroundClosingModal` (w tym planie)
3. Faza 3: Refaktoryzacja `systemMode` i `startTime` (w tym planie)

**Uwaga:** `Users/ListView` jest już częściowo zrefaktoryzowane i dziedziczy z `Settings\Base\Views\ListView`, więc `LIST_MAX_ENTRIES_MASS_EDIT` jest dodawane w `Settings\Base\Views\ListView::prepareListViewData()` (punkt 4).

---

## FAZA 4: Usunięcie `vglobal('upload_maxsize')` - ✅ WYKONANE

### Analiza użycia

**Wystąpienia:** 5 plików
- `src/Modules/Base/Views/QuickCreateAjax.php` (linia 97)
- `src/Modules/Base/Views/Edit.php` (linia 169)
- `src/Modules/Faq/Views/Edit.php` (linia 97)
- `src/Modules/Base/Helpers/Util.php` (linia 243) - metoda `getMaxUploadSize()`
- `src/Modules/Settings/PDF/Actions/Watermark.php` (linia 51)

**Charakterystyka:**
- Wartość konfiguracyjna (łatwa do zastąpienia przez `AppConfig::main()`)
- Używana w widokach Edit do wyświetlania limitu uploadu
- Używana w helperze `Util::getMaxUploadSize()` do konwersji na MB
- Używana w akcji Watermark do walidacji rozmiaru pliku

### Pliki do modyfikacji:

#### 1. `src/Modules/Base/Helpers/Util.php`
**Metoda:** `getMaxUploadSize()` (linia 241)
**Zmiana:** Zastąp `vglobal('upload_maxsize')` przez `AppConfig::main('upload_maxsize')`

```php
// PRZED:
$upload_maxsize = (int) vglobal('upload_maxsize');

// PO:
$upload_maxsize = (int) \App\AppConfig::main('upload_maxsize');
```

**Uzasadnienie:** Ta metoda jest używana przez kontrolery widoków, więc refaktoryzacja tutaj automatycznie poprawi wszystkie miejsca, które używają `getMaxUploadSize()`.

#### 2. Kontrolery widoków Edit (3 pliki)

##### 2.1. `src/Modules/Base/Views/Edit.php`
**Metoda:** `process()` (około linia 169)
**Zmiana:** Zastąp `vglobal('upload_maxsize')` przez `AppConfig::main('upload_maxsize')`

```php
// PRZED:
$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));

// PO:
$viewer->assign('MAX_UPLOAD_LIMIT', \App\AppConfig::main('upload_maxsize'));
```

**Uwaga:** W tym samym pliku jest już `MAX_UPLOAD_LIMIT_MB` używające `Util::getMaxUploadSize()`, które po refaktoryzacji punktu 1 będzie działać poprawnie.

##### 2.2. `src/Modules/Base/Views/QuickCreateAjax.php`
**Metoda:** `process()` (około linia 97)
**Zmiana:** Zastąp `vglobal('upload_maxsize')` przez `AppConfig::main('upload_maxsize')`

```php
// PRZED:
$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));

// PO:
$viewer->assign('MAX_UPLOAD_LIMIT', \App\AppConfig::main('upload_maxsize'));
```

##### 2.3. `src/Modules/Faq/Views/Edit.php`
**Metoda:** `process()` (około linia 97)
**Zmiana:** Zastąp `vglobal('upload_maxsize')` przez `AppConfig::main('upload_maxsize')`

```php
// PRZED:
$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));

// PO:
$viewer->assign('MAX_UPLOAD_LIMIT', \App\AppConfig::main('upload_maxsize'));
```

#### 3. `src/Modules/Settings/PDF/Actions/Watermark.php`
**Metoda:** `process()` (około linia 51)
**Zmiana:** Zastąp `vglobal('upload_maxsize')` przez `AppConfig::main('upload_maxsize')`

```php
// PRZED:
if ($uploadOk && $_FILES['watermark']['size'][0] > vglobal('upload_maxsize')) {

// PO:
if ($uploadOk && $_FILES['watermark']['size'][0] > \App\AppConfig::main('upload_maxsize')) {
```

### Wykonane zmiany:

1. ✅ **`Util::getMaxUploadSize()`** - zamieniono `vglobal('upload_maxsize')` na `AppConfig::main('upload_maxsize')`
2. ✅ **`Base/Views/Edit.php`** - zamieniono w metodzie `assignEditViewData()` (linia 169)
3. ✅ **`Base/Views/QuickCreateAjax.php`** - zamieniono w metodzie `process()` (linia 97)
4. ✅ **`Faq/Views/Edit.php`** - zamieniono w metodzie `process()` (linia 97)
5. ✅ **`Settings/PDF/Actions/Watermark.php`** - zamieniono w metodzie `process()` (linia 51)

### Korzyści:

- **Prostota:** Wartość konfiguracyjna, łatwa do zastąpienia
- **Wpływ:** Używana w kluczowych widokach (Edit, QuickCreate)
- **Spójność:** Ujednolici sposób dostępu do konfiguracji w całym projekcie
- **Niskie ryzyko:** Nie wpływa na logikę biznesową, tylko sposób dostępu do konfiguracji

### Weryfikacja:

✅ **Wszystkie wystąpienia zostały zmienione** - grep nie znajduje już `vglobal('upload_maxsize')` w kodzie źródłowym
✅ **Brak błędów lintera** - wszystkie pliki przeszły weryfikację

**Do przetestowania:**
1. Czy formularze Edit wyświetlają poprawny limit uploadu
2. Czy walidacja rozmiaru plików działa poprawnie (Watermark)
3. Czy `MAX_UPLOAD_LIMIT_MB` jest poprawnie obliczane w widokach

---

## FAZA 5: Usunięcie wartości konfiguracyjnych - `default_timezone`, `site_URL`, `cache_dir`, `tmp_dir` - ✅ WYKONANE

### Analiza użycia

**Wystąpienia:** 17 wystąpień w 12 plikach

#### `default_timezone` (6 wystąpień):
- `src/Modules/Base/Helpers/Util.php` (linia 360) - metoda `getActiveAdminCurrentDateTime()`
- `src/Modules/Reports/Models/ScheduleReports.php` (linia 294) - metoda `getScheduledReports()`
- `src/Modules/WSAPP/Utils.php` (linia 89) - `date_default_timezone_set()`
- `src/Modules/Workflow/WorkFlowScheduler.php` (linia 196) - metoda `getNextTriggerTime()`
- `src/Modules/Workflow/Workflow.php` (linia 166) - metoda `getNextTriggerTime()`
- `src/Modules/Users/Models/EditRecordStructure.php` (linia 65) - domyślna wartość pola

#### `site_URL` (4 wystąpienia):
- `src/Modules/Reports/Models/ScheduleReports.php` (linia 339) - generowanie URL w emailach
- `src/Modules/Users/Actions/ForgotPassword.php` (linie 81, 87) - redirect po wysłaniu emaila
- `src/Modules/Users/Handlers/Users_ForgotPassword_Handler.php` (linia 19) - generowanie URL

#### `cache_dir` (4 wystąpienia):
- `src/Modules/PaymentsIn/Views/step1.php` (linia 62) - zapisywanie plików
- `src/Modules/PaymentsIn/Models/Record.php` (linia 20) - zapisywanie plików
- `src/Modules/PaymentsOut/Views/step1.php` (linia 60) - zapisywanie plików
- `src/Modules/PaymentsOut/Models/Record.php` (linia 20) - zapisywanie plików

#### `tmp_dir` (3 wystąpienia):
- `src/Modules/Reports/Models/Record.php` (linie 764, 791) - eksport raportów
- `src/Modules/Base/Actions/QuickExport.php` (linia 108) - eksport danych

**Charakterystyka:**
- Wszystkie są wartościami konfiguracyjnymi (łatwe do zastąpienia przez `AppConfig::main()`)
- Używane w różnych kontekstach: workflow, raporty, eksport, zapisywanie plików, generowanie URL-i
- Nie wymagają specjalnej logiki, tylko zamiany źródła wartości

### Pliki do modyfikacji:

#### Grupa 1: `default_timezone` (6 plików)

##### 1.1. `src/Modules/Base/Helpers/Util.php`
**Metoda:** `getActiveAdminCurrentDateTime()` (linia 358)
**Zmiana:** Zamień `vglobal('default_timezone')` na `AppConfig::main('default_timezone')`

##### 1.2. `src/Modules/Reports/Models/ScheduleReports.php`
**Metoda:** `getScheduledReports()` (linia 292)
**Zmiana:** Zamień `vglobal('default_timezone')` na `AppConfig::main('default_timezone')`

##### 1.3. `src/Modules/WSAPP/Utils.php`
**Linia:** 89
**Zmiana:** Zamień `vglobal('default_timezone')` na `AppConfig::main('default_timezone')`

##### 1.4. `src/Modules/Workflow/WorkFlowScheduler.php`
**Metoda:** `getNextTriggerTime()` (około linia 196)
**Zmiana:** Zamień `vglobal('default_timezone')` na `AppConfig::main('default_timezone')`

##### 1.5. `src/Modules/Workflow/Workflow.php`
**Metoda:** `getNextTriggerTime()` (około linia 164)
**Zmiana:** Zamień `vglobal('default_timezone')` na `AppConfig::main('default_timezone')`

##### 1.6. `src/Modules/Users/Models/EditRecordStructure.php`
**Linia:** 65
**Zmiana:** Zamień `vglobal('default_timezone')` na `AppConfig::main('default_timezone')`

#### Grupa 2: `site_URL` (3 pliki)

##### 2.1. `src/Modules/Reports/Models/ScheduleReports.php`
**Linia:** 339
**Zmiana:** Zamień `vglobal('site_URL')` na `AppConfig::main('site_URL')`

##### 2.2. `src/Modules/Users/Actions/ForgotPassword.php`
**Linie:** 81, 87
**Zmiana:** Zamień `vglobal('site_URL')` na `AppConfig::main('site_URL')` (2 wystąpienia)

##### 2.3. `src/Modules/Users/Handlers/Users_ForgotPassword_Handler.php`
**Linia:** 19
**Zmiana:** Zamień `vglobal('site_URL')` na `AppConfig::main('site_URL')`

#### Grupa 3: `cache_dir` (4 pliki)

##### 3.1. `src/Modules/PaymentsIn/Views/step1.php`
**Metoda:** `saveFile()` (linia 60)
**Zmiana:** Zamień `vglobal('cache_dir')` na `AppConfig::main('cache_dir')`

##### 3.2. `src/Modules/PaymentsIn/Models/Record.php`
**Linia:** 20
**Zmiana:** Zamień `vglobal('cache_dir')` na `AppConfig::main('cache_dir')`

##### 3.3. `src/Modules/PaymentsOut/Views/step1.php`
**Metoda:** `saveFile()` (linia 60)
**Zmiana:** Zamień `vglobal('cache_dir')` na `AppConfig::main('cache_dir')`

##### 3.4. `src/Modules/PaymentsOut/Models/Record.php`
**Linia:** 20
**Zmiana:** Zamień `vglobal('cache_dir')` na `AppConfig::main('cache_dir')`

#### Grupa 4: `tmp_dir` (2 pliki)

##### 4.1. `src/Modules/Reports/Models/Record.php`
**Linie:** 764, 791
**Zmiana:** Zamień `vglobal('tmp_dir')` na `AppConfig::main('tmp_dir')` (2 wystąpienia)

##### 4.2. `src/Modules/Base/Actions/QuickExport.php`
**Linia:** 108
**Zmiana:** Zamień `vglobal('tmp_dir')` na `AppConfig::main('tmp_dir')`

### Wykonane zmiany:

1. ✅ **Grupa 1** - `default_timezone` (6 plików) - wszystkie zmienione
   - `Util.php`, `ScheduleReports.php`, `WSAPP/Utils.php`, `WorkFlowScheduler.php`, `Workflow.php`, `EditRecordStructure.php`
2. ✅ **Grupa 2** - `site_URL` (3 pliki) - wszystkie zmienione
   - `ScheduleReports.php`, `ForgotPassword.php` (2 wystąpienia), `Users_ForgotPassword_Handler.php`
3. ✅ **Grupa 3** - `cache_dir` (4 pliki) - wszystkie zmienione
   - `PaymentsIn/Views/step1.php`, `PaymentsIn/Models/Record.php`, `PaymentsOut/Views/step1.php`, `PaymentsOut/Models/Record.php`
4. ✅ **Grupa 4** - `tmp_dir` (2 pliki, 3 wystąpienia) - wszystkie zmienione
   - `Reports/Models/Record.php` (2 wystąpienia), `QuickExport.php`

### Korzyści:

- **Prostota:** Wszystkie wartości konfiguracyjne, łatwe do zastąpienia
- **Spójność:** Ujednolici sposób dostępu do konfiguracji
- **Niskie ryzyko:** Nie wpływa na logikę biznesową, tylko sposób dostępu do konfiguracji
- **Wpływ:** Pokrywa różne obszary aplikacji (workflow, raporty, eksport, płatności)

### Weryfikacja:

✅ **Wszystkie wystąpienia zostały zmienione** - grep nie znajduje już `vglobal('default_timezone')`, `vglobal('site_URL')`, `vglobal('cache_dir')`, `vglobal('tmp_dir')` w kodzie źródłowym
✅ **Brak błędów lintera** - wszystkie pliki przeszły weryfikację

**Do przetestowania:**
1. Czy workflow działa poprawnie z nową strefą czasową
2. Czy raporty zaplanowane są generowane poprawnie
3. Czy eksport danych działa poprawnie
4. Czy zapisywanie plików w modułach Payments działa poprawnie
5. Czy generowanie URL-i w emailach działa poprawnie

---

## FAZA 6: Usunięcie pozostałych wartości konfiguracyjnych - `default_charset`, `php_max_execution_time`, `davStorageDir` - ✅ WYKONANE

### Analiza użycia

**Wystąpienia:** 9 wystąpień w 9 plikach

#### `default_charset` (3 wystąpienia):
- `src/Modules/CustomView/Models/Record.php` (linia 644) - kodowanie dla bazy danych
- `src/Modules/OSSMail/Models/Module.php` (linia 232) - konfiguracja HTMLPurifier
- `src/Modules/Documents/Models/Record.php` (linia 66) - dekodowanie nazw plików

#### `php_max_execution_time` (4 wystąpienia):
- `src/Modules/Settings/Profiles/Models/Record.php` (linia 839) - metoda `recalculate()`
- `src/Modules/Settings/GlobalPermission/Models/Record.php` (linia 68) - metoda `recalculate()`
- `src/Modules/Settings/SharingAccess/Models/Module.php` (linia 166) - metoda `recalculateSharingRules()`
- `src/Modules/Settings/Groups/Models/Record.php` (linia 214) - metoda `recalculate()`

#### `davStorageDir` (2 wystąpienia):
- `src/Modules/Settings/Dav/Models/Module.php` (linie 104, 118) - ścieżka do katalogu DAV storage

**Charakterystyka:**
- Wszystkie są wartościami konfiguracyjnymi (łatwe do zastąpienia przez `AppConfig::main()`)
- Używane w różnych kontekstach: kodowanie, limity wykonania, ścieżki katalogów
- Nie wymagają specjalnej logiki, tylko zamiany źródła wartości

### Pliki do modyfikacji:

#### Grupa 1: `default_charset` (3 pliki)

##### 1.1. `src/Modules/CustomView/Models/Record.php`
**Linia:** 644
**Zmiana:** Zamień `vglobal('default_charset')` na `AppConfig::main('default_charset')`

##### 1.2. `src/Modules/OSSMail/Models/Module.php`
**Linia:** 232
**Zmiana:** Zamień `vglobal('default_charset')` na `AppConfig::main('default_charset')`

##### 1.3. `src/Modules/Documents/Models/Record.php`
**Linia:** 66
**Zmiana:** Zamień `vglobal('default_charset')` na `AppConfig::main('default_charset')`

#### Grupa 2: `php_max_execution_time` (4 pliki)

##### 2.1. `src/Modules/Settings/Profiles/Models/Record.php`
**Metoda:** `recalculate()` (linia 837)
**Zmiana:** Zamień `vglobal('php_max_execution_time')` na `AppConfig::main('php_max_execution_time')`

##### 2.2. `src/Modules/Settings/GlobalPermission/Models/Record.php`
**Metoda:** `recalculate()` (linia 66)
**Zmiana:** Zamień `vglobal('php_max_execution_time')` na `AppConfig::main('php_max_execution_time')`

##### 2.3. `src/Modules/Settings/SharingAccess/Models/Module.php`
**Metoda:** `recalculateSharingRules()` (linia 164)
**Zmiana:** Zamień `vglobal('php_max_execution_time')` na `AppConfig::main('php_max_execution_time')`

##### 2.4. `src/Modules/Settings/Groups/Models/Record.php`
**Metoda:** `recalculate()` (linia 212)
**Zmiana:** Zamień `vglobal('php_max_execution_time')` na `AppConfig::main('php_max_execution_time')`

#### Grupa 3: `davStorageDir` (1 plik, 2 wystąpienia)

##### 3.1. `src/Modules/Settings/Dav/Models/Module.php`
**Linie:** 104, 118
**Zmiana:** Zamień `vglobal('davStorageDir')` na `AppConfig::main('davStorageDir')` (2 wystąpienia)

### Wykonane zmiany:

1. ✅ **Grupa 1** - `default_charset` (3 pliki) - wszystkie zmienione
   - `CustomView/Models/Record.php`, `OSSMail/Models/Module.php`, `Documents/Models/Record.php`
2. ✅ **Grupa 2** - `php_max_execution_time` (4 pliki) - wszystkie zmienione
   - `Settings/Profiles/Models/Record.php`, `Settings/GlobalPermission/Models/Record.php`, `Settings/SharingAccess/Models/Module.php`, `Settings/Groups/Models/Record.php`
3. ✅ **Grupa 3** - `davStorageDir` (1 plik, 2 wystąpienia) - wszystkie zmienione
   - `Settings/Dav/Models/Module.php` (2 wystąpienia)

### Korzyści:

- **Prostota:** Wszystkie wartości konfiguracyjne, łatwe do zastąpienia
- **Spójność:** Dokończy refaktoryzację wszystkich wartości konfiguracyjnych
- **Niskie ryzyko:** Nie wpływa na logikę biznesową, tylko sposób dostępu do konfiguracji
- **Kompletność:** Po tej fazie wszystkie wartości konfiguracyjne będą używać `AppConfig::main()`

### Weryfikacja:

✅ **Wszystkie wystąpienia zostały zmienione** - grep nie znajduje już `vglobal('default_charset')`, `vglobal('php_max_execution_time')`, `vglobal('davStorageDir')` w kodzie źródłowym
✅ **Brak błędów lintera** - wszystkie pliki przeszły weryfikację
✅ **Kompletność wartości konfiguracyjnych** - wszystkie wartości konfiguracyjne używają teraz `AppConfig::main()`

**Do przetestowania:**
1. Czy kodowanie znaków działa poprawnie (CustomView, Documents)
2. Czy HTMLPurifier działa poprawnie z nowym kodowaniem (OSSMail)
3. Czy metody recalculate w Settings działają poprawnie z limitem czasu
4. Czy operacje DAV storage działają poprawnie z nową ścieżką

---

## FAZA 7: Dokończenie `vglobal('systemMode')` - ✅ WYKONANE

### Analiza użycia

**Wystąpienia:** 3 wystąpienia w 2 plikach (pozostałe po częściowej implementacji FAZY 3)

#### Pozostałe wystąpienia:
- `src/Modules/Settings/ModuleManager/Views/ModuleImport.php` (linia 30) - walidacja trybu demo
- `src/Modules/Users/Models/DetailView.php` (linie 40, 52) - warunki wyświetlania linków (2 wystąpienia)

**Charakterystyka:**
- Wartość konfiguracyjna (łatwa do zastąpienia przez `AppConfig::main()`)
- Używana do sprawdzania trybu demo systemu
- Kontrolery widoków już zrefaktoryzowane (Login.php, ModuleManager/ListView.php)
- Pozostały tylko przypadki w kodzie PHP (nie w kontrolerach widoków)

### Wykonane zmiany:

1. ✅ **`src/Modules/Settings/ModuleManager/Views/ModuleImport.php`**
   - Metoda: `process()` (linia 30)
   - Zamieniono `vglobal('systemMode')` na `AppConfig::main('systemMode')`

2. ✅ **`src/Modules/Users/Models/DetailView.php`**
   - Metoda: `getDetailViewLinks()` (linie 40, 52)
   - Zamieniono `vglobal('systemMode')` na `AppConfig::main('systemMode')` (2 wystąpienia)

### Korzyści:

- **Prostota:** Wartość konfiguracyjna, łatwa do zastąpienia
- **Kompletność:** Dokończy refaktoryzację `systemMode` rozpoczętą w FAZIE 3
- **Niskie ryzyko:** Nie wpływa na logikę biznesową, tylko sposób dostępu do konfiguracji
- **Mała liczba plików:** Tylko 2 pliki do modyfikacji

### Weryfikacja:

✅ **Wszystkie wystąpienia zostały zmienione** - grep nie znajduje już `vglobal('systemMode')` w kodzie źródłowym
✅ **Brak błędów lintera** - wszystkie pliki przeszły weryfikację
✅ **Kompletność refaktoryzacji systemMode** - wszystkie wystąpienia `systemMode` używają teraz `AppConfig::main()`

**Do przetestowania:**
1. Czy import modułów działa poprawnie (walidacja trybu demo)
2. Czy linki w widoku szczegółów użytkownika są poprawnie wyświetlane/ukrywane w trybie demo

---

## FAZA 8: Usunięcie `vglobal('default_language')` - ✅ WYKONANE

### Analiza użycia

**Wystąpienia:** 19 wystąpień w 7 plikach

#### Wystąpienia:
- `src/Runtime/Vtiger_Language_Handler.php` (linie 49, 215, 239) - 3 wystąpienia w handlerze języków
- `src/ModuleManagement/Services/PackageService.php` (linie 135, 152) - 2 wystąpienia w instalacji pakietów
- `src/Webservices/Utils.php` (linie 889, 896) - 2 wystąpienia w webservices
- `src/Modules/Install/Views/Index.php` (linia 80) - ustawianie podczas instalacji
- `src/Modules/OpenStreetMap/Actions/GetRoute.php` (linia 48) - domyślny język dla routingu
- `src/Modules/Base/Actions/PDF.php` (linie 111, 121, 144, 166, 176, 189, 209) - 7 wystąpień w generowaniu PDF
- `src/Modules/Base/Pdfs/mPDF.php` (linie 340, 341, 350) - 3 wystąpienia w mPDF

**Charakterystyka:**
- Wartość konfiguracyjna (łatwa do zastąpienia przez `AppConfig::main()`)
- Używana głównie jako fallback, gdy nie ma języka użytkownika
- Kluczowe użycie w `Vtiger_Language_Handler` - refaktoryzacja tego będzie miała duży wpływ
- Używana w generowaniu PDF do przełączania języków

### Wykonane zmiany:

#### Grupa 1: Vtiger_Language_Handler (3 wystąpienia) - ✅ WYKONANE

1. ✅ **`src/Runtime/Vtiger_Language_Handler.php`**
   - Metody: `getTranslatedString()` (linia 49), `getLanguage()` (linia 215), `export()` (linia 239)
   - Zamieniono wszystkie odczyty `vglobal('default_language')` na `AppConfig::main('default_language')`

#### Grupa 2: Inne pliki (6 plików, 11 odczytów) - ✅ WYKONANE

2. ✅ **`src/ModuleManagement/Services/PackageService.php`**
   - Linie: 135, 152
   - Zamieniono `vglobal('default_language')` na `AppConfig::main('default_language')` (2 wystąpienia)

3. ✅ **`src/Webservices/Utils.php`**
   - Linie: 889, 896
   - Zamieniono `vglobal('default_language')` na `AppConfig::main('default_language')` (2 wystąpienia)

4. ✅ **`src/Modules/OpenStreetMap/Actions/GetRoute.php`**
   - Linia: 48
   - Zamieniono `vglobal('default_language')` na `AppConfig::main('default_language')`

5. ✅ **`src/Modules/Base/Actions/PDF.php`**
   - Linie: 111, 176
   - Zamieniono odczyty `vglobal('default_language')` na `AppConfig::main('default_language')` (2 wystąpienia)
   - Ustawiania wartości pozostawione jako `vglobal()` (tymczasowe przełączanie języka podczas generowania PDF)

6. ✅ **`src/Modules/Base/Pdfs/mPDF.php`**
   - Linia: 340
   - Zamieniono odczyt `vglobal('default_language')` na `AppConfig::main('default_language')`
   - Ustawiania wartości pozostawione jako `vglobal()` (tymczasowe przełączanie języka podczas generowania PDF)

#### Uwaga specjalna:

- **`src/Modules/Install/Views/Index.php`** (linia 80) - ustawianie podczas instalacji pozostawione jako `vglobal()` (to jest ustawianie konfiguracji podczas instalacji)
- **`src/Modules/Base/Actions/PDF.php`** i **`src/Modules/Base/Pdfs/mPDF.php`** - ustawiania wartości pozostawione jako `vglobal()` (tymczasowe przełączanie języka podczas generowania PDF, wymaga lokalnej zmiennej)

### Kolejność wykonania:

1. **Grupa 1** - `Vtiger_Language_Handler.php` (3 wystąpienia) - najważniejsze, używane wszędzie
2. **Grupa 2** - Pozostałe pliki (6 plików, 16 wystąpień)

### Korzyści:

- **Prostota:** Wartość konfiguracyjna, łatwa do zastąpienia
- **Wpływ:** Refaktoryzacja `Vtiger_Language_Handler` będzie miała duży wpływ na cały system
- **Spójność:** Ujednolici sposób dostępu do domyślnego języka
- **Niskie ryzyko:** Nie wpływa na logikę biznesową, tylko sposób dostępu do konfiguracji

### Weryfikacja:

✅ **Wszystkie odczyty zostały zmienione** - grep nie znajduje już `vglobal('default_language')` jako odczyt w kodzie źródłowym
✅ **Brak błędów lintera** - wszystkie pliki przeszły weryfikację
✅ **Ustawiania wartości pozostawione** - tymczasowe przełączanie języka w PDF i ustawianie podczas instalacji pozostawione jako `vglobal()` (wymaga osobnej refaktoryzacji)

**Do przetestowania:**
1. Czy tłumaczenia działają poprawnie (fallback do domyślnego języka)
2. Czy generowanie PDF działa poprawnie z przełączaniem języków
3. Czy webservices zwracają poprawny język
4. Czy instalacja pakietów działa poprawnie

---

## Inne proponowane fazy (do rozważenia w przyszłości):

### FAZA 9: Wartości runtime - `current_language`
- **Wystąpienia:** ~23 wystąpienia w 13 plikach
- **Charakterystyka:** Wartość runtime, ustawiana w `EntryPoint/WebUI.php`
- **Alternatywa:** Użycie `$request->getModule()` lub przekazywanie przez kontekst
- **Priorytet:** Wysoki (często używane), ale wymaga większej refaktoryzacji (kontekst Request)

### FAZA 9: Wartości runtime - `current_language`
- **Wystąpienia:** ~6 wystąpień w 5 plikach
- **Charakterystyka:** Wartość runtime, ustawiana w `EntryPoint/WebUI.php`
- **Alternatywa:** Użycie `Vtiger_Language_Handler::getLanguage()`
- **Priorytet:** Wysoki (często używane), ale wymaga większej refaktoryzacji

### FAZA 10: Wartości runtime - `mod_strings` / `app_strings`
- **Wystąpienia:** ~4 wystąpienia (głównie ustawianie w EntryPoint, ale też użycie w Reports.php, CustomView.php)
- **Charakterystyka:** Wartości runtime, ustawiane w `EntryPoint/WebUI.php`
- **Alternatywa:** Użycie `Vtiger_Language_Handler::getModuleStringsFromFile()`
- **Priorytet:** Średni (używane głównie w starszym kodzie)

### FAZA 11: Wartości runtime - `currentModule`
- **Wystąpienia:** ~23 wystąpienia w 13 plikach
- **Charakterystyka:** Wartość runtime, ustawiana w `EntryPoint/WebUI.php`
- **Alternatywa:** Użycie `$request->getModule()` lub przekazywanie przez kontekst
- **Priorytet:** Wysoki (często używane), ale wymaga większej refaktoryzacji (kontekst Request)

### FAZA 12: Pozostałe wartości runtime
- **Wystąpienia:** ~11 wystąpień różnych wartości
- **Charakterystyka:** Różne wartości runtime (workflowIdsAlreadyDone, showsAdditionalLabels, isPermittedLog, popupAjax, translated_language)
- **Priorytet:** Niski (specjalne przypadki, wymagają indywidualnej analizy)

