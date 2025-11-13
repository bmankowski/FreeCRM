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

### 3.1. Usunięcie `vglobal('systemMode')`

#### Kontrolery do modyfikacji:

##### 1. `src/Modules/Users/Views/Login.php`
**Metoda:** `process()` (linia 29)
**Uwaga:** Login nie używa `BaseViewController`, ma własną hierarchię
**Dodaj przed `$viewer->view()` (około linii 53):**
```php
$viewer->assign('SYSTEM_MODE', \App\AppConfig::main('systemMode'));
```

##### 2. `src/Modules/Settings/ModuleManager/Views/ListView.php`
**Metoda:** `process()` (linia 18)
**Dodaj przed `$viewer->view()` (około linii 30):**
```php
$viewer->assign('SYSTEM_MODE', \App\AppConfig::main('systemMode'));
```

### 3.2. Usunięcie `vglobal('startTime')`

#### Uwaga:
`startTime` jest już przypisywane jako `SCRIPT_TIME` w `BaseViewController::preProcess()` (linia 56), więc nie trzeba dodawać przypisania w kontrolerze.

---

## Kolejność wykonania

1. **Faza 1** - `listMaxEntriesMassEdit` (najbardziej używane)
2. **Faza 2** - `backgroundClosingModal` (proste, jedno przypisanie)
3. **Faza 3** - `systemMode` i `startTime` (dodatkowe, mniej krytyczne)
4. **Faza 4** - `upload_maxsize` (wartości konfiguracyjne w widokach Edit) - ✅ **WYKONANE**

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

## Inne proponowane fazy (do rozważenia w przyszłości):

### FAZA 5: Wartości konfiguracyjne - `default_timezone`, `site_URL`, `cache_dir`, `tmp_dir`
- **Wystąpienia:** ~15-20 plików
- **Charakterystyka:** Wartości konfiguracyjne, łatwe do zastąpienia przez `AppConfig::main()`
- **Priorytet:** Średni (używane w różnych kontekstach)

### FAZA 6: Wartości runtime - `currentModule`
- **Wystąpienia:** ~30+ plików
- **Charakterystyka:** Wartość runtime, ustawiana w `EntryPoint/WebUI.php`
- **Alternatywa:** Użycie `$request->getModule()` lub przekazywanie przez kontekst
- **Priorytet:** Wysoki (najczęściej używane), ale wymaga większej refaktoryzacji

### FAZA 7: Wartości runtime - `current_language` / `default_language`
- **Wystąpienia:** ~20+ plików
- **Charakterystyka:** Wartości runtime/konfiguracyjne
- **Alternatywa:** Użycie `Vtiger_Language_Handler::getLanguage()` i `AppConfig::main('default_language')`
- **Priorytet:** Wysoki (często używane)

### FAZA 8: Wartości runtime - `mod_strings` / `app_strings`
- **Wystąpienia:** ~10+ plików
- **Charakterystyka:** Wartości runtime, ustawiane w `EntryPoint/WebUI.php`
- **Alternatywa:** Użycie `Vtiger_Language_Handler::getModuleStringsFromFile()`
- **Priorytet:** Średni (używane głównie w starszym kodzie)

