# Szczegółowy plan refaktoryzacji - usuwanie vglobal() z szablonów

## Analiza wstępna

### Hierarchia kontrolerów
- Wszystkie kontrolery używające `PopupLayout.tpl`, `MainLayout.tpl`, `Header.tpl` dziedziczą z `BaseViewController` (poprzez `Basic` → `Index` → konkretne widoki)
- `BaseViewController::preProcess()` jest wywoływane przez wszystkie te kontrolery
- `OSSPasswords/Views/ListView` extends `Base\Views\Index`, więc używa hierarchii Base

### Użycia vglobal() w szablonach
1. **listMaxEntriesMassEdit** - 7 wystąpień w ListView templates
2. **backgroundClosingModal** - 3 wystąpienia w layout templates
3. **systemMode** - 3 wystąpienia (Login.Default.tpl × 2, ModuleManager/ListContent.tpl × 1)
4. **startTime** - 1 wystąpienie (Footer.tpl) - **UWAGA**: już przypisane jako `SCRIPT_TIME` w `BaseViewController::preProcess()`

---

## FAZA 1: Usunięcie `vglobal('listMaxEntriesMassEdit')`

### Szablony do modyfikacji (7 plików):
1. `layouts/basic/modules/Base/ListViewContents.tpl`
2. `layouts/basic/modules/RecycleBin/ListView.tpl`
3. `layouts/basic/modules/Reports/ListView.tpl`
4. `layouts/basic/modules/Users/ListViewContents.tpl`
5. `layouts/basic/modules/Reports/ListViewContents.tpl`
6. `layouts/basic/modules/RecycleBin/ListViewContents.tpl`
7. `layouts/basic/modules/OSSPasswords/ListViewContents.tpl`

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

### Zmiany w szablonach:
W każdym z 7 szablonów zamień:
```smarty
<input type="hidden" id="listMaxEntriesMassEdit" value="{vglobal('listMaxEntriesMassEdit')}" />
```
na:
```smarty
<input type="hidden" id="listMaxEntriesMassEdit" value="{$LIST_MAX_ENTRIES_MASS_EDIT}" />
```

---

## FAZA 2: Usunięcie `vglobal('backgroundClosingModal')`

### Szablony do modyfikacji (3 pliki):
1. `layouts/basic/modules/Base/PopupLayout.tpl`
2. `layouts/basic/modules/Base/MainLayout.tpl`
3. `layouts/basic/modules/Base/Header.tpl`

### Kontroler do modyfikacji:

#### `src/Base/Controllers/BaseViewController.php`
**Metoda:** `preProcess()` (linia 29)
**Dodaj przed końcem metody (około linii 65, przed zamknięciem):**
```php
$viewer->assign('BACKGROUND_CLOSING_MODAL', \App\AppConfig::main('backgroundClosingModal'));
```

**Uzasadnienie:** Wszystkie kontrolery używające tych szablonów dziedziczą z `BaseViewController` i wywołują `preProcess()`, więc jedno przypisanie pokrywa wszystkie przypadki.

### Zmiany w szablonach:
W każdym z 3 szablonów zamień:
```smarty
<input type="hidden" id="backgroundClosingModal" value="{vglobal('backgroundClosingModal')}" />
```
na:
```smarty
<input type="hidden" id="backgroundClosingModal" value="{$BACKGROUND_CLOSING_MODAL}" />
```

---

## FAZA 3: Usunięcie `vglobal('systemMode')` i `vglobal('startTime')`

### 3.1. Usunięcie `vglobal('systemMode')`

#### Szablony do modyfikacji (2 pliki):
1. `layouts/basic/modules/Users/Login.Default.tpl` (2 wystąpienia)
2. `layouts/basic/modules/Settings/ModuleManager/ListContent.tpl` (1 wystąpienie)

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

#### Zmiany w szablonach:

**Login.Default.tpl** (2 wystąpienia):
```smarty
{if vglobal('systemMode') == 'demo'}
```
zamień na:
```smarty
{if $SYSTEM_MODE == 'demo'}
```

**ModuleManager/ListContent.tpl**:
```smarty
{if vglobal('systemMode') != 'demo'}
```
zamień na:
```smarty
{if $SYSTEM_MODE != 'demo'}
```

### 3.2. Usunięcie `vglobal('startTime')`

#### Szablon do modyfikacji:
- `layouts/basic/modules/Base/Footer.tpl` (1 wystąpienie)

#### Uwaga:
`startTime` jest już przypisywane jako `SCRIPT_TIME` w `BaseViewController::preProcess()` (linia 56), więc nie trzeba dodawać przypisania w kontrolerze.

#### Zmiana w szablonie:
**Usuń całkowicie linię 47:**
```smarty
{assign var=SCRIPT_TIME value=round(microtime(true) - vglobal('startTime'), 3)}
```
**Uzasadnienie:** `SCRIPT_TIME` jest już przypisane w `BaseViewController::preProcess()` (linia 56), więc ta linia jest zbędna.

---

## Kolejność wykonania

1. **Faza 1** - `listMaxEntriesMassEdit` (najbardziej używane)
2. **Faza 2** - `backgroundClosingModal` (proste, jedno przypisanie)
3. **Faza 3** - `systemMode` i `startTime` (dodatkowe, mniej krytyczne)

---

## Weryfikacja po każdej fazie

Po każdej fazie należy:
1. Sprawdzić czy wszystkie wystąpienia `vglobal('nazwa')` zostały usunięte z szablonów (użyć grep)
2. Sprawdzić w przeglądarce, że wartości są dostępne w JavaScript (np. `document.getElementById('listMaxEntriesMassEdit').value`)
3. Sprawdzić logi: `cache/logs/system.log` pod kątem błędów
4. Przetestować podstawową funkcjonalność w przeglądarce (nie tylko CLI)

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

