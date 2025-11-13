# Analiza `vglobal('currentModule')` - Szczegółowa

## Podsumowanie

**Wystąpienia:** 23 wystąpienia w 13 plikach
- **Odczyty:** 19 wystąpień w 13 plikach
- **Ustawiania:** 4 wystąpienia w 3 plikach

---

## Kategorie użycia

### KATEGORIA 1: Metody z dostępem do Request (łatwe do refaktoryzacji)

#### 1.1. Metody z parametrem Request
- **Liczba:** 0 wystąpień
- **Charakterystyka:** Metody które mają `$request` jako parametr mogą użyć `$request->getModule()`

#### 1.2. Metody bez Request, ale w kontekście kontrolera
- **Liczba:** 0 wystąpień
- **Charakterystyka:** Metody w kontrolerach mogą użyć `$request->getModule()`

### KATEGORIA 2: Metody bez dostępu do Request (wymagają innego rozwiązania)

#### 2.1. Metody CRMEntity z opcjonalnym Request
**Pliki:**
- `src/CRMEntity.php`:
  - `getSortOrder($request = null)` (linia 1587) - używa `$currentModule` dla sesji
  - `getOrderBy($request = null)` (linia 1604) - używa `$currentModule` dla sesji
  - `unlinkRelationship()` (linia 415) - używa `$currentModule` jako parametr do innych metod

**Charakterystyka:**
- Metody mogą mieć `$request`, ale nie zawsze
- Używają `$currentModule` dla kluczy sesji lub jako parametr
- **Rozwiązanie:** Dodać opcjonalny parametr `$moduleName` lub użyć `$this->moduleName` jeśli dostępne

#### 2.2. Metody statyczne bez Request
**Pliki:**
- `src/Utils/Utils.php`:
  - `dateDiffAsString($d1, $d2)` (linia 661) - używa `$currentModule` dla tłumaczeń

**Charakterystyka:**
- Metoda statyczna bez dostępu do Request
- Używa `$currentModule` dla tłumaczeń
- **Rozwiązanie:** Dodać opcjonalny parametr `$moduleName` z domyślną wartością

#### 2.3. Metody w klasach dziedziczących z CRMEntity
**Pliki:**
- `src/Modules/ModComments/ModCommentsCore.php`:
  - `getSortOrder($request = null)` (linia 92) - używa `$currentModule` dla sesji
  - `getOrderBy($request = null)` (linia 105) - używa `$currentModule` dla sesji

- `src/Modules/SMSNotifier/SMSNotifierBase.php`:
  - `__construct()` (linia 88) - używa `$currentModule` w konstruktorze
  - `getSortOrder($request = null)` (linia 94) - używa `$currentModule` dla sesji

**Charakterystyka:**
- Klasy dziedziczą z CRMEntity
- Mogą mieć `$this->moduleName` (ustawiane w `CRMEntity::getInstance()`)
- **Rozwiązanie:** Użyć `$this->moduleName` jeśli dostępne, w przeciwnym razie dodać parametr

#### 2.4. Metody w klasach bez Request
**Pliki:**
- `src/Modules/CustomView/CustomView.php`:
  - `getRealValues()` (linia 665) - używa `$currentModule` dla tłumaczeń

- `src/Modules/Reports/VTScheduledReport.php`:
  - `sendEmail()` (linia 124) - używa `$currentModule` dla kontekstu
  - `runScheduledReports()` (linia 368) - używa `$currentModule` z fallback

- `src/Modules/Reports/Models/ScheduleReports.php`:
  - `runScheduledReports()` (linia 318) - używa `$currentModule` z fallback
  - `getEmailContent()` (linia 340) - używa `$currentModule` dla kontekstu

- `src/ModuleManagement/Adapters/LinkData.php`:
  - `__construct()` (linia 30) - używa `$currentModule` w konstruktorze

**Charakterystyka:**
- Metody bez dostępu do Request
- Używają `$currentModule` dla różnych celów
- **Rozwiązanie:** Dodać parametr `$moduleName` lub użyć innego mechanizmu

#### 2.5. Wywołania z vglobal jako parametr
**Pliki:**
- `src/Modules/Project/Project.php`:
  - `deleteRelatedFromDB()` (linia 425) - wywołanie z `vglobal('currentModule')` jako parametr

- `src/Modules/ServiceContracts/ServiceContracts.php`:
  - `deleteRelatedFromDB()` (linia 588) - wywołanie z `vglobal('currentModule')` jako parametr

**Charakterystyka:**
- Wywołania metod z `vglobal('currentModule')` jako parametr
- **Rozwiązanie:** Zamienić na bezpośrednie przekazanie modułu lub użycie `$this->moduleName`

### KATEGORIA 3: Ustawiania wartości (wymagają osobnej refaktoryzacji)

#### 3.1. Ustawianie podczas inicjalizacji
**Pliki:**
- `src/EntryPoint/WebUI.php` (linia 626) - ustawianie przed wykonaniem handlera

**Charakterystyka:**
- Ustawiane w EntryPoint przed wykonaniem handlera
- Handler ma dostęp do Request, więc może użyć `$request->getModule()`
- **Rozwiązanie:** Można pozostawić jako ustawianie lub przekazać przez kontekst

#### 3.2. Ustawianie fallback
**Pliki:**
- `src/Modules/Reports/Models/ScheduleReports.php` (linia 321) - ustawianie fallback gdy puste

**Charakterystyka:**
- Ustawiane jako fallback gdy wartość jest pusta
- **Rozwiązanie:** Użyć wartości domyślnej zamiast ustawiania globalnej zmiennej

#### 3.3. Tymczasowe przełączanie
**Pliki:**
- `src/Modules/Base/Actions/RelationAjax.php` (linie 109, 141) - tymczasowe przełączanie podczas operacji relacji
- `src/Modules/Base/Models/TreeCategoryModal.php` (linie 148, 156) - tymczasowe przełączanie podczas operacji

**Charakterystyka:**
- Tymczasowe przełączanie modułu podczas operacji
- **Rozwiązanie:** Przekazywanie modułu jako parametr zamiast globalnej zmiennej

---

## Szczegółowa analiza każdego pliku

### 1. `src/EntryPoint/WebUI.php` (1 ustawianie)

**Linia 626:** `vglobal('currentModule', $module);`
- **Kontekst:** Metoda `executeHandler()` - ustawianie przed wykonaniem handlera
- **Użycie:** Handler ma dostęp do Request, więc może użyć `$request->getModule()`
- **Rozwiązanie:** Można pozostawić jako ustawianie (dla kompatybilności) lub przekazać przez kontekst

### 2. `src/Modules/CustomView/CustomView.php` (1 odczyt)

**Linia 665:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `getRealValues()` - używa dla tłumaczeń
- **Użycie:** `$moduleStrings = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile($currentLanguage, $currentModule);`
- **Rozwiązanie:** Dodać parametr `$moduleName` do metody lub użyć innego mechanizmu

### 3. `src/Modules/Reports/Models/ScheduleReports.php` (2 odczyty, 1 ustawianie)

**Linia 318:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `runScheduledReports()` - używa z fallback
- **Użycie:** `if (empty($currentModule)) { vglobal('currentModule', 'Reports'); }`
- **Rozwiązanie:** Użyć wartości domyślnej 'Reports' zamiast sprawdzania globalnej zmiennej

**Linia 321:** `vglobal('currentModule', 'Reports');`
- **Kontekst:** Ustawianie fallback
- **Rozwiązanie:** Użyć wartości domyślnej zamiast ustawiania globalnej zmiennej

**Linia 340:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `getEmailContent()` - używa dla kontekstu
- **Użycie:** Przekazywane do innych metod
- **Rozwiązanie:** Dodać parametr `$moduleName` do metody

### 4. `src/Modules/Reports/VTScheduledReport.php` (2 odczyty)

**Linia 124:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `sendEmail()` - używa dla kontekstu
- **Użycie:** Przekazywane do innych metod
- **Rozwiązanie:** Dodać parametr `$moduleName` do metody

**Linia 368:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `runScheduledReports()` - używa z fallback
- **Użycie:** `if (empty($currentModule)) $currentModule = 'Reports';`
- **Rozwiązanie:** Użyć wartości domyślnej 'Reports'

### 5. `src/Utils/Utils.php` (1 odczyt)

**Linia 661:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda statyczna `dateDiffAsString($d1, $d2)` - używa dla tłumaczeń
- **Użycie:** `\App\Runtime\Vtiger_Language_Handler::translate('LBL_YEARS', $currentModule)`
- **Rozwiązanie:** Dodać opcjonalny parametr `$moduleName = null` z domyślną wartością lub użyć 'Vtiger'

### 6. `src/CRMEntity.php` (3 odczyty)

**Linia 415:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `unlinkRelationship($id, $returnModule, $returnId, $relatedName = false)`
- **Użycie:** Przekazywane jako parametr do innych metod (`deleteRelatedM2M`, `deleteRelatedDependent`, `deleteRelatedFromDB`)
- **Rozwiązanie:** Użyć `$this->moduleName` jeśli dostępne, w przeciwnym razie dodać parametr

**Linia 1587:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `getSortOrder($request = null)` - używa dla klucza sesji
- **Użycie:** `$_SESSION[$currentModule . '_Sort_Order']`
- **Rozwiązanie:** Użyć `$this->moduleName` jeśli dostępne, w przeciwnym razie dodać parametr

**Linia 1604:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `getOrderBy($request = null)` - używa dla klucza sesji
- **Użycie:** `$_SESSION[$currentModule . '_Sort_Order']`
- **Rozwiązanie:** Użyć `$this->moduleName` jeśli dostępne, w przeciwnym razie dodać parametr

### 7. `src/Modules/ModComments/ModCommentsCore.php` (2 odczyty)

**Linia 92:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `getSortOrder($request = null)` - używa dla klucza sesji
- **Użycie:** `$_SESSION[$currentModule . '_Sort_Order']`
- **Rozwiązanie:** Użyć `$this->moduleName` jeśli dostępne

**Linia 105:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `getOrderBy($request = null)` - używa dla klucza sesji
- **Użycie:** `$_SESSION[$currentModule . '_Sort_Order']`
- **Rozwiązanie:** Użyć `$this->moduleName` jeśli dostępne

### 8. `src/Modules/SMSNotifier/SMSNotifierBase.php` (2 odczyty)

**Linia 88:** `$this->column_fields = \App\Utils\Utils::getColumnFields(vglobal('currentModule'));`
- **Kontekst:** Konstruktor `__construct()` - używa w konstruktorze
- **Użycie:** Przekazywane do `Utils::getColumnFields()`
- **Rozwiązanie:** Użyć `$this->moduleName` jeśli dostępne (ustawiane w `CRMEntity::getInstance()`)

**Linia 94:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `getSortOrder($request = null)` - używa dla klucza sesji
- **Użycie:** `$_SESSION[$currentModule . '_Sort_Order']`
- **Rozwiązanie:** Użyć `$this->moduleName` jeśli dostępne

### 9. `src/ModuleManagement/Adapters/LinkData.php` (1 odczyt)

**Linia 30:** `$this->module = vglobal('currentModule');`
- **Kontekst:** Konstruktor `__construct($user, $input = [])` - używa w konstruktorze
- **Użycie:** Przypisanie do właściwości klasy
- **Rozwiązanie:** Dodać parametr `$moduleName` do konstruktora

### 10. `src/Modules/Project/Project.php` (1 odczyt jako parametr)

**Linia 425:** `parent::deleteRelatedFromDB(vglobal('currentModule'), $id, $return_module, $return_id);`
- **Kontekst:** Metoda `deleteRelatedFromDB()` - wywołanie z `vglobal('currentModule')` jako parametr
- **Użycie:** Przekazywane jako pierwszy parametr do metody rodzica
- **Rozwiązanie:** Użyć `$this->moduleName` zamiast `vglobal('currentModule')`

### 11. `src/Modules/ServiceContracts/ServiceContracts.php` (1 odczyt jako parametr)

**Linia 588:** `parent::deleteRelatedFromDB(vglobal('currentModule'), $id, $returnModule, $returnId);`
- **Kontekst:** Metoda `deleteRelatedFromDB()` - wywołanie z `vglobal('currentModule')` jako parametr
- **Użycie:** Przekazywane jako pierwszy parametr do metody rodzica
- **Rozwiązanie:** Użyć `$this->moduleName` zamiast `vglobal('currentModule')`

### 12. `src/Modules/Base/Actions/RelationAjax.php` (2 ustawiania)

**Linia 109:** `vglobal('currentModule', $relatedModule);`
- **Kontekst:** Metoda `deleteRelation()` - tymczasowe przełączanie podczas operacji relacji
- **Użycie:** Ustawiane przed operacją, nie przywracane
- **Rozwiązanie:** Przekazywanie modułu jako parametr zamiast globalnej zmiennej

**Linia 141:** `vglobal('currentModule', $sourceModule);`
- **Kontekst:** Metoda `updateRelation()` - tymczasowe przełączanie podczas operacji relacji
- **Użycie:** Ustawiane przed operacją, nie przywracane
- **Rozwiązanie:** Przekazywanie modułu jako parametr zamiast globalnej zmiennej

### 13. `src/Modules/Base/Models/TreeCategoryModal.php` (1 odczyt, 2 ustawiania)

**Linia 147:** `$currentModule = vglobal('currentModule');`
- **Kontekst:** Metoda `getSelectedRecords()` - zapisanie oryginalnej wartości
- **Użycie:** Zapisywane przed przełączeniem, przywracane po operacji

**Linia 148:** `vglobal('currentModule', $this->get('srcModule'));`
- **Kontekst:** Tymczasowe przełączanie podczas operacji
- **Użycie:** Ustawiane przed operacją

**Linia 156:** `vglobal('currentModule', $currentModule);`
- **Kontekst:** Przywracanie oryginalnej wartości
- **Użycie:** Przywracane po operacji
- **Rozwiązanie:** Przekazywanie modułu jako parametr zamiast globalnej zmiennej

---

## Proponowane rozwiązania

### Rozwiązanie 1: Użycie `$this->moduleName` (dla klas dziedziczących z CRMEntity)

**Zastosowanie:**
- `CRMEntity::unlinkRelationship()` - użyć `$this->moduleName`
- `CRMEntity::getSortOrder()` - użyć `$this->moduleName`
- `CRMEntity::getOrderBy()` - użyć `$this->moduleName`
- `ModComments::getSortOrder()` - użyć `$this->moduleName`
- `ModComments::getOrderBy()` - użyć `$this->moduleName`
- `SMSNotifierBase::__construct()` - użyć `$this->moduleName` (ale trzeba sprawdzić czy jest dostępne w konstruktorze)
- `SMSNotifierBase::getSortOrder()` - użyć `$this->moduleName`
- `Project::deleteRelatedFromDB()` - użyć `$this->moduleName`
- `ServiceContracts::deleteRelatedFromDB()` - użyć `$this->moduleName`

**Uwaga:** `$this->moduleName` jest ustawiane w `CRMEntity::getInstance()`, więc powinno być dostępne w większości przypadków.

### Rozwiązanie 2: Dodanie parametru `$moduleName` (dla metod bez dostępu do Request)

**Zastosowanie:**
- `Utils::dateDiffAsString()` - dodać opcjonalny parametr `$moduleName = 'Vtiger'`
- `CustomView::getRealValues()` - dodać parametr `$moduleName` lub użyć innego mechanizmu
- `VTScheduledReport::sendEmail()` - dodać parametr `$moduleName`
- `ScheduleReports::getEmailContent()` - dodać parametr `$moduleName`
- `LinkData::__construct()` - dodać parametr `$moduleName`

### Rozwiązanie 3: Wartości domyślne (dla fallback)

**Zastosowanie:**
- `ScheduleReports::runScheduledReports()` - użyć wartości domyślnej 'Reports' zamiast sprawdzania globalnej zmiennej
- `VTScheduledReport::runScheduledReports()` - użyć wartości domyślnej 'Reports'

### Rozwiązanie 4: Przekazywanie jako parametr (dla tymczasowych przełączeń)

**Zastosowanie:**
- `RelationAjax::deleteRelation()` - przekazywanie modułu jako parametr zamiast globalnej zmiennej
- `RelationAjax::updateRelation()` - przekazywanie modułu jako parametr zamiast globalnej zmiennej
- `TreeCategoryModal::getSelectedRecords()` - przekazywanie modułu jako parametr zamiast globalnej zmiennej

### Rozwiązanie 5: Pozostawienie ustawiania w EntryPoint (dla kompatybilności)

**Zastosowanie:**
- `EntryPoint/WebUI.php` - można pozostawić jako ustawianie dla kompatybilności z kodem, który jeszcze używa `vglobal('currentModule')`

---

## Plan refaktoryzacji

### FAZA 12A: Proste przypadki - użycie `$this->moduleName`

**Pliki:**
1. `CRMEntity::unlinkRelationship()` - zamienić na `$this->moduleName`
2. `CRMEntity::getSortOrder()` - zamienić na `$this->moduleName`
3. `CRMEntity::getOrderBy()` - zamienić na `$this->moduleName`
4. `ModComments::getSortOrder()` - zamienić na `$this->moduleName`
5. `ModComments::getOrderBy()` - zamienić na `$this->moduleName`
6. `SMSNotifierBase::getSortOrder()` - zamienić na `$this->moduleName`
7. `Project::deleteRelatedFromDB()` - zamienić na `$this->moduleName`
8. `ServiceContracts::deleteRelatedFromDB()` - zamienić na `$this->moduleName`

**Uwaga:** Dla `SMSNotifierBase::__construct()` trzeba sprawdzić czy `$this->moduleName` jest dostępne w konstruktorze (ustawiane w `CRMEntity::getInstance()`).

### FAZA 12B: Dodanie parametrów - metody bez Request

**Pliki:**
1. `Utils::dateDiffAsString()` - dodać opcjonalny parametr `$moduleName = 'Vtiger'`
2. `CustomView::getRealValues()` - dodać parametr `$moduleName` lub użyć innego mechanizmu
3. `VTScheduledReport::sendEmail()` - dodać parametr `$moduleName`
4. `ScheduleReports::getEmailContent()` - dodać parametr `$moduleName`
5. `LinkData::__construct()` - dodać parametr `$moduleName`

### FAZA 12C: Wartości domyślne - fallback

**Pliki:**
1. `ScheduleReports::runScheduledReports()` - użyć wartości domyślnej 'Reports'
2. `VTScheduledReport::runScheduledReports()` - użyć wartości domyślnej 'Reports'

### FAZA 12D: Tymczasowe przełączanie - przekazywanie jako parametr

**Pliki:**
1. `RelationAjax::deleteRelation()` - przekazywanie modułu jako parametr
2. `RelationAjax::updateRelation()` - przekazywanie modułu jako parametr
3. `TreeCategoryModal::getSelectedRecords()` - przekazywanie modułu jako parametr

### FAZA 12E: EntryPoint - pozostawienie lub refaktoryzacja

**Pliki:**
1. `EntryPoint/WebUI.php` - można pozostawić jako ustawianie dla kompatybilności lub przekazać przez kontekst

---

## Weryfikacja

Po każdej fazie sprawdzić:
1. Czy metody działają poprawnie z nowymi parametrami
2. Czy sesje działają poprawnie z nowymi kluczami
3. Czy tłumaczenia działają poprawnie z nowymi modułami
4. Czy operacje relacji działają poprawnie z przekazywanymi parametrami

