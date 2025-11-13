# Plan refaktoryzacji: Odejście od statycznego dostępu do użytkownika w modelach

## Cel

Wprowadzenie właściwej separacji MVC poprzez eliminację bezpośredniego dostępu modeli do aktualnego użytkownika. Modele powinny otrzymywać kontekst użytkownika jako parametr przekazywany przez kontrolery.

## Obecny stan

### Problemy

- Modele używają statycznych metod: `Record::getCurrentUserId()` (82+ miejsc), `CurrentUser::get()` (125+ miejsc)
- Modele bezpośrednio odczytują sesję: `Vtiger_Session::get('authenticated_user_id')`
- Brak separacji MVC - modele mają wiedzę o kontekście sesji
- Trudne testowanie - zależność od globalnego stanu
- Nie można używać modeli w kontekstach bez sesji (CLI, batch jobs)

### Istniejące mechanizmy

- `Vtiger_Request` ma już metody: `setUser()`, `getUser()`, `getUserId()`, `hasUser()`
- Niektóre kontrolery już ustawiają użytkownika w request (`Save::preProcess()`)
- `CurrentUser` jest już oznaczony jako deprecated
- Mechanizm user switching (`baseUserId`) - admin może przełączyć się na innego użytkownika

## Analiza: userId vs pełny obiekt User

### Faktyczne potrzeby modeli

Z analizy kodu wynika, że modele używają użytkownika na różne sposoby:

1. **Podstawowe użycia (większość przypadków):**
   - Zapisywanie `modified_user_id`, `smcreatorid` w bazie danych
   - Cache keys (`Cache::has(__METHOD__ . $userId)`)
   - Query filtering (`where(['smownerid' => $userId])`)
   - Sprawdzanie czy userId jest w array/string

2. **Zaawansowane użycia (mniejszość, ale ważne):**
   - Sprawdzanie uprawnień: `$user->getRole()`, `$user->getGroups()`, `$user->getParentRoles()`
   - Preferencje użytkownika: `$user->get('defaulteventstatus')`, `$user->get('defaultactivitytype')`
   - Role-based picklist: `$user->get('roleid')`
   - Sprawdzanie admin: `$user->isAdminUser()`

### Decyzja architektoniczna: Zawsze przekazywać User object jako `$currentUser`

**Ważna obserwacja:** User object jest już załadowany w `Vtiger_Request` podczas inicjalizacji (`initializeGlobals()`), więc **nie ma uzasadnienia wydajnościowego** dla przekazywania userId zamiast User object. W PHP obiekty są przekazywane przez referencję, więc koszt przekazania int vs object reference jest praktycznie identyczny.

**Zalety przekazywania zawsze User object jako `$currentUser`:**
- ✅ **Elastyczność na przyszłość** - jeśli w przyszłości potrzebne będą dodatkowe weryfikacje/uprawnienia, nie trzeba zmieniać sygnatur metod
- ✅ **Spójność API** - wszystkie metody przyjmują ten sam typ parametru (`?\App\Modules\Users\Models\Record`)
- ✅ **Dostęp do pełnych danych** - zawsze można użyć `$currentUser->getId()` gdy potrzebny tylko ID, ale też `$currentUser->getRole()` gdy potrzebne uprawnienia
- ✅ **User object już jest w pamięci** - nie ma dodatkowego kosztu ładowania
- ✅ **Lepsze testowanie** - jeden typ mock object zamiast mieszanki int i object
- ✅ **Czytelność** - `$currentUser` jest bardziej opisowe niż `$userId`

**Rozwiązanie: Zawsze przekazywać `?\App\Modules\Users\Models\Record $currentUser = null` jako parametr**

**Uzasadnienie:** Skoro User object jest już załadowany w request i nie ma overhead wydajnościowego, lepiej zawsze przekazywać pełny obiekt. To daje elastyczność na przyszłość i zapewnia spójność API. Jeśli metoda potrzebuje tylko userId, można użyć `$currentUser->getId()`, ale jeśli w przyszłości będzie potrzebować uprawnień, nie trzeba zmieniać sygnatury metody.

## Strategia refaktoryzacji

### Faza 1: Analiza i kategoryzacja użyć (1-2 dni)

#### 1.1 Kategoryzacja miejsc użycia

- **Typ A**: Metody modeli wymagające user context (wszystkie będą używać `$currentUser`)
- **Typ B**: Metody pomocnicze/utility używające user
- **Typ C**: Metody w klasach nie-modelowych (CRMEntity, Field, Utils)
- **Typ D**: Metody w kontrolerach/akcjach (mniej krytyczne)

#### 1.2 Utworzenie mapy zależności

- Dokumentacja wszystkich metod modeli używających current user
- Identyfikacja miejsc gdzie user jest przekazywany vs. odczytywany statycznie
- Lista metod wymagających refaktoryzacji priorytetowej

### Faza 2: Wprowadzenie User Context jako parametru (2-3 tygodnie)

#### 2.1 Rozszerzenie interfejsów modeli - zawsze User object jako `$currentUser`

**Plik**: `src/Modules/Base/Models/Record.php`

**Standardowy wzorzec dla wszystkich metod:**

```php
// Przykład dla metody save() - używa $currentUser->getId() dla modified_user_id
public function save($request = null, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    // Jeśli currentUser nie podany, fallback do request lub deprecated static
    if ($currentUser === null) {
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            // Deprecated fallback
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('Model::save() called without user context', E_USER_DEPRECATED);
        }
    }
    // Użyj $currentUser->getRealId() dla audit trail (obsługuje user switching)
    $forSave['vtiger_crmentity']['modified_user_id'] = $currentUser->getRealId();
    // ... reszta logiki
}
```

**Dla metod wymagających uprawnień/preferencji:**

```php
// Przykład dla metody autoAssignRecord() - używa getRole()
public function autoAssignRecord(?\App\Modules\Users\Models\Record $currentUser = null, $request = null)
{
    if ($currentUser === null) {
        // Fallback - można załadować z request lub deprecated static
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('Model::autoAssignRecord() called without user context', E_USER_DEPRECATED);
        }
    }
    // Użyj pełnych możliwości User object
    $roleData = \App\PrivilegeUtil::getRoleDetail($currentUser->getRole());
    // ... reszta logiki
}
```

#### 2.2 Refaktoryzacja metod w Record.php

Metody do zmodyfikowania (wszystkie używają `$currentUser`):

- `save()` - używa `getCurrentUserRealId()` → `?\App\Modules\Users\Models\Record $currentUser = null` (używa `$currentUser->getRealId()` dla audit trail)
- `uploadAndSaveFile()` - używa `getCurrentUserId()` → `?\App\Modules\Users\Models\Record $currentUser = null` (używa `$currentUser->getId()`)
- `getValuesForSave()` - używa `getCurrentUserRealId()` → `?\App\Modules\Users\Models\Record $currentUser = null` (używa `$currentUser->getRealId()` dla audit trail)
- `autoAssignRecord()` - używa `getCurrentUserModel()->getRole()` → `?\App\Modules\Users\Models\Record $currentUser = null` (używa `$currentUser->getRole()`)
- Metody query/access control - wszystkie używają `$currentUser` (większość tylko `getId()`, niektóre pełne metody)

#### 2.3 Refaktoryzacja metod w innych modelach

Priorytetowe moduły (wszystkie używają `$currentUser`):

- `src/Modules/Base/Models/Relation.php` - `addRelation()` używa `getCurrentUserId()` → `?\App\Modules\Users\Models\Record $currentUser = null`
- `src/Modules/Base/Models/Watchdog.php` - metody używające `getCurrentUserId()` → `?\App\Modules\Users\Models\Record $currentUser = null`
- `src/Modules/ModComments/Models/Record.php` - `save()` używa `getCurrentUserId()` → `?\App\Modules\Users\Models\Record $currentUser = null`
- `src/Modules/Base/Models/MiniList.php` - query methods → `?\App\Modules\Users\Models\Record $currentUser = null`
- `src/Modules/Base/Models/MappedFields.php` - `checkUserPermissions()` → `?\App\Modules\Users\Models\Record $currentUser = null`

#### 2.4 Aktualizacja metod pomocniczych

**Plik**: `src/Field.php`, `src/Privilege.php`, `src/TextParser.php`

- Dodać opcjonalny parametr `?\App\Modules\Users\Models\Record $currentUser = null`
- Fallback do deprecated static methods z warningiem
- Jeśli potrzebny tylko userId, użyć `$currentUser->getId()`

### Faza 3: Aktualizacja kontrolerów (1-2 tygodnie)

#### 3.1 Rozszerzenie Vtiger_Request o getRealUser()

**Plik**: `src/Http/Vtiger_Request.php`

Dodać metody dla prawdziwego użytkownika (dla user switching):

```php
/** @var \App\Modules\Users\Models\Record|null Real user (handles user switching) */
protected $realUser = null;

/**
 * Set real user (for user switching/impersonation)
 * @param \App\Modules\Users\Models\Record $user
 * @return self
 */
public function setRealUser(\App\Modules\Users\Models\Record $user): self
{
    $this->realUser = $user;
    return $this;
}

/**
 * Get real user (handles user switching)
 * Falls back to getUser() if realUser not set
 * @return \App\Modules\Users\Models\Record|null
 */
public function getRealUser(): ?\App\Modules\Users\Models\Record
{
    return $this->realUser ?? $this->getUser();
}

/**
 * Get real user ID
 * @return int
 */
public function getRealUserId(): int
{
    return $this->getRealUser()->getId();
}
```

#### 3.2 Upewnienie się, że wszystkie kontrolery ustawiają user w request

**Plik**: `src/EntryPoint/WebUI.php`

W metodzie `checkLogin()` lub `executeHandler()`:

```php
// Po autentykacji, zawsze ustaw user w request
$userModel = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
$request->setUser($userModel);

// Jeśli istnieje baseUserId (user switching), ustaw również realUser
if (\App\Http\Vtiger_Session::has('baseUserId') && \App\Http\Vtiger_Session::get('baseUserId')) {
    $realUserId = \App\Http\Vtiger_Session::get('baseUserId');
    $realUserModel = \App\Modules\Users\Models\Record::getInstanceById($realUserId, 'Users');
    $request->setRealUser($realUserModel);
} else {
    // Jeśli nie ma switching, realUser = getUser()
    $request->setRealUser($userModel);
}
```

#### 3.3 Aktualizacja Actions

**Plik**: `src/Modules/Base/Actions/Save.php`

Zmienić wywołania:

```php
// Przed:
$recordModel->save($request);

// Po (dla save() - przekazujemy getRealUser() dla audit trail):
$recordModel->save($request, $request->getRealUser());

// Dla metod wymagających uprawnień (używają getUser() - aktualny kontekst):
$recordModel->autoAssignRecord($request->getUser());

// Dla workflow - zawsze getRealUser():
\App\Modules\Base\Models\WorkflowTrigger::execute($moduleName, $record, $ids, $request->getRealUser());
```

**Zasada:**
- **Audit trail** (`save()`, `modified_user_id`) → `getRealUser()` (prawdziwy użytkownik)
- **Uprawnienia/preferencje** (`autoAssignRecord()`, `checkPermissions()`) → `getUser()` (aktualny kontekst)
- **Workflow** → `getRealUser()` (zawsze prawdziwy użytkownik)

#### 3.4 Aktualizacja Views

Views przekazujące dane do modeli powinny zawsze używać `$request->getUser()`.

#### 3.5 Aktualizacja API/Webservice

**Plik**: `src/Api/Webservice/Core/BaseAction.php`

Upewnić się, że `checkPermission()` ustawia user w request (już jest w linii 77).

### Faza 4: Refaktoryzacja metod statycznych (1 tydzień)

#### 4.1 Metody statyczne w modelach

**Przykład**: `src/Modules/Base/Models/Module.php`

```php
// Przed:
public static function getInstance($mixed)
{
    // używa getCurrentUserId() wewnętrznie jeśli potrzebne
}

// Po:
public static function getInstance($mixed, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('Module::getInstance() called without user context', E_USER_DEPRECATED);
    }
    // użyj $currentUser->getId() jeśli potrzebne
}
```

#### 4.2 Metody statyczne w klasach pomocniczych

**Przykład**: `src/Privilege.php`

```php
// Przed:
public static function isPermitted($moduleName, $actionName = null, $record = false, $userId = false)
{
    if (!$userId) {
        $userId = \App\Modules\Users\Models\Record::getCurrentUserId();
    }
    // ...
}

// Po:
public static function isPermitted($moduleName, $actionName = null, $record = false, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('Privilege::isPermitted() called without user context', E_USER_DEPRECATED);
    }
    $userId = $currentUser->getId();
    // ...
}
```

### Faza 5: Refaktoryzacja klas nie-modelowych (1 tydzień)

#### 5.1 CRMEntity i klasy legacy

**Plik**: `src/CRMEntity.php`

Dla metod używających `CurrentUser::get()`, dodać opcjonalny parametr `$userId` lub `$user` z fallbackiem.

#### 5.2 Field classes

**Plik**: `src/Field.php`, `src/Fields/CurrencyField.php`

Metody cache powinny przyjmować `?\App\Modules\Users\Models\Record $currentUser = null` jako parametr zamiast używać `getCurrentUserId()`. Jeśli potrzebny tylko userId, użyć `$currentUser->getId()`.

#### 5.3 Utility classes

**Plik**: `src/Utils/InventoryUtils.php`, `src/TextParser.php`

Przekazywanie `?\App\Modules\Users\Models\Record $currentUser = null` przez parametry zamiast statycznego dostępu.

### Faza 6: Refaktoryzacja Workflow i Automation (1 tydzień)

#### 6.1 Workflow - jak powinno działać

**Obecny stan:**
- Workflow używa `VTWorkflowUtils` z mechanizmem user stack
- Workflow actions mogą być wykonywane w kontekście różnych użytkowników
- `WorkflowTrigger::execute()` przyjmuje `$userId` jako parametr

**Refaktoryzacja:**

**Plik**: `src/Modules/Base/Models/WorkflowTrigger.php`

```php
// Przed:
public static function execute($moduleName, $record, $ids, $userId)
{
    $recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
    if ($userId) {
        $recordModel->executeUser = $userId;
    }
    // ...
}

// Po:
public static function execute($moduleName, $record, $ids, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    $recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
    
    if ($currentUser === null) {
        // Fallback - spróbuj załadować z request lub użyj deprecated
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('WorkflowTrigger::execute() called without user context', E_USER_DEPRECATED);
    }
    
    // Przekaż currentUser do workflow tasks
    $recordModel->executeUser = $currentUser;
    // ...
}
```

**Plik**: `src/Modules/Base/Actions/Workflow.php`

```php
public function execute(\App\Http\Vtiger_Request $request)
{
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $ids = $request->get('ids');
    
    // Workflow zawsze działa w kontekście prawdziwego użytkownika (dla audit trail)
    $currentUser = $request->getRealUser();
    
    \App\Modules\Base\Models\WorkflowTrigger::execute($moduleName, $record, $ids, $currentUser);
    // ...
}
```

**Workflow tasks** powinny otrzymywać `$currentUser` jako parametr:

```php
// Przykład: src/Modules/Workflow/Tasks/VTCreateEntityTask.php
public function doTask($entity, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('VTCreateEntityTask::doTask() called without user context', E_USER_DEPRECATED);
    }
    // użyj $currentUser dla operacji
}
```

#### 6.2 Cron Jobs - jak powinno działać

**Obecny stan:**
- CronRunner ustawia admin user przez `setCurrentUserId()`
- Cron tasks są wykonywane jako admin user
- Nie ma request object w kontekście cron

**Refaktoryzacja:**

**Plik**: `src/Modules/Cron/Runner/CronRunner.php`

```php
public function runTask(\vtlib\Cron $cronTask): void
{
    try {
        // Załaduj admin user jako currentUser
        $adminId = \App\Modules\Users\Models\Record::getActiveAdminId();
        $adminUser = \App\Modules\Users\Models\Record::getInstanceById($adminId, 'Users');
        
        // Przekaż adminUser do cron task handler
        // Cron tasks powinny przyjmować $currentUser jako parametr
        // Jeśli cron task używa modeli, przekaż $adminUser
        
        // Dla backward compatibility, ustaw również static (deprecated)
        \App\Modules\Users\Models\Record::setCurrentUserId($adminId);
        
        // Wykonaj cron task
        require_once $cronTask->getHandlerFile();
        // ...
    }
}
```

**Cron task handlers** powinny przyjmować `$currentUser`:

```php
// Przykład cron task handler
function cronTaskHandler(?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        // Fallback dla backward compatibility
        $currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
        trigger_error('Cron task called without user context', E_USER_DEPRECATED);
    }
    
    // Użyj $currentUser dla operacji
    $recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, 'Module');
    $recordModel->save(null, $currentUser);
}
```

**Uwaga:** Cron tasks mogą pozostać z deprecated fallbackiem dłużej, ponieważ są wykonywane w specjalnym kontekście bez request object.

### Faza 7: Deprecation i cleanup (1 tydzień)

#### 7.1 Oznaczenie metod jako deprecated

**Plik**: `src/Modules/Users/Models/Record.php`

```php
/**
 * @deprecated Use $request->getUser() or pass $currentUser as parameter to model methods
 * Will be removed in version 2.0
 */
public static function getCurrentUserId() { ... }

/**
 * @deprecated Use $request->getUser() or pass $currentUser as parameter to model methods  
 * Will be removed in version 2.0
 */
public static function getCurrentUserModel() { ... }
```

#### 5.2 Logowanie użyć deprecated methods

Rozszerzyć `CurrentUser::logDeprecation()` aby logować wszystkie wywołania deprecated methods.

#### 5.3 Aktualizacja dokumentacji

- Dokumentacja nowego wzorca przekazywania user context
- Przykłady użycia w kontrolerach i modelach
- Migration guide dla developerów

### Faza 6: Usunięcie deprecated code (future - v2.0)

#### 6.1 Usunięcie static methods

- `Record::getCurrentUserId()`
- `Record::getCurrentUserModel()`
- `Record::setCurrentUserId()` (zostanie tylko dla specjalnych przypadków jak cron)
- `CurrentUser::get()` i `CurrentUser::getId()`

#### 6.2 Finalne czyszczenie

- Usunięcie wszystkich fallbacków do deprecated methods
- Aktualizacja testów

## Wzorce implementacji

### Wzorzec 1: Metoda modelu używająca tylko userId (ale przyjmuje User object)

```php
public function save($request = null, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    // 1. Pobierz currentUser z parametru, request, lub deprecated fallback
    if ($currentUser === null) {
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('Model::save() called without user context', E_USER_DEPRECATED);
        }
    }
    
    // 2. Użyj $currentUser->getId() gdy potrzebny tylko ID
    $forSave['vtiger_crmentity']['modified_user_id'] = $currentUser->getId();
    // ... reszta logiki
}
```

### Wzorzec 2: Metoda modelu używająca uprawnień/preferencji

```php
public function autoAssignRecord(?\App\Modules\Users\Models\Record $currentUser = null, $request = null)
{
    // 1. Pobierz currentUser z parametru, request, lub deprecated fallback
    if ($currentUser === null) {
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('Model::autoAssignRecord() called without user context', E_USER_DEPRECATED);
        }
    }
    
    // 2. Użyj pełnych możliwości User object
    $roleData = \App\PrivilegeUtil::getRoleDetail($currentUser->getRole());
    // Jeśli w przyszłości potrzebne będą dodatkowe weryfikacje, można użyć:
    // $currentUser->getGroups(), $currentUser->isAdminUser(), etc.
    // ... reszta logiki
}
```

### Wzorzec 3: Kontroler przekazujący User object

```php
public function process(\App\Http\Vtiger_Request $request)
{
    $recordModel = $this->getRecordModelFromRequest($request);
    // Zawsze przekazujemy User object
    $recordModel->save($request, $request->getUser());
    // ...
}
```

### Wzorzec 4: Query method z currentUser

```php
public function getRelatedRecords(?\App\Modules\Users\Models\Record $currentUser = null, $request = null)
{
    if ($currentUser === null) {
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('getRelatedRecords() called without user', E_USER_DEPRECATED);
        }
    }
    
    // Użyj getId() gdy potrzebny tylko ID
    $query = (new \App\Db\Query())
        ->where(['smownerid' => $currentUser->getId()]);
    // ... reszta logiki
}
```

## Priorytetyzacja

### Wysoki priorytet

1. Metody `save()` w modelach - najczęściej używane → **`$currentUser`** (używają `getId()`)
2. Metody query/access control - wpływają na bezpieczeństwo → **`$currentUser`** (większość używa `getId()`, niektóre pełne metody)
3. Metody w `Record.php` - bazowa klasa używana przez wszystkie moduły → **`$currentUser`**

### Średni priorytet

4. Metody w klasach pomocniczych (Field, Utils) → **`$currentUser`** (większość używa `getId()`)
5. Metody w CRMEntity (legacy code) → **`$currentUser`**

### Niski priorytet

6. Metody w kontrolerach (mniej krytyczne dla MVC separation)
7. CLI/cron scripts (specjalne przypadki - mogą używać deprecated methods)

## Testowanie

### Testy jednostkowe

- Modele powinny być testowalne bez sesji
- Mock User object przekazywany jako parametr `$currentUser`
- Testy deprecated fallbacków

### Testy integracyjne

- Weryfikacja, że kontrolery ustawiają user w request
- Weryfikacja, że modele otrzymują user context (`$currentUser`)
- Testy backward compatibility

## Metryki sukcesu

1. **Redukcja użyć static methods**: Z 200+ do <10 (tylko specjalne przypadki)
2. **Pokrycie testami**: Wszystkie nowe metody z `$currentUser` parameter mają testy
3. **Backward compatibility**: Aplikacja działa bez błędów podczas migracji
4. **Code quality**: Modele nie mają zależności od sesji/globalnego stanu
5. **API consistency**: Wszystkie metody używają spójnego parametru `$currentUser`
6. **Future-proof**: Elastyczność na przyszłość - łatwo dodać dodatkowe weryfikacje bez zmiany sygnatur

## Ryzyka i migracja

### Ryzyka

- Breaking changes w custom modules/extensions
- Trudność refaktoryzacji legacy code (CRMEntity)
- **Uwaga:** Performance overhead jest praktycznie nieistotny - User object jest już załadowany w request, a w PHP obiekty są przekazywane przez referencję (koszt identyczny jak int)

### Strategia migracji

- **Incremental**: Refaktoryzacja moduł po moduł
- **Backward compatible**: Deprecated fallbacks przez cały okres migracji
- **Documentation**: Jasne przykłady i migration guide
- **Testing**: Testy na każdym etapie refaktoryzacji
- **Consistent API**: Wszystkie metody używają `$currentUser` - spójne podejście

## Pliki kluczowe do modyfikacji

### Modele

- `src/Modules/Base/Models/Record.php` - bazowa klasa modelu
- `src/Modules/Base/Models/Relation.php` - relacje
- `src/Modules/Base/Models/Watchdog.php` - watchdog
- `src/Modules/ModComments/Models/Record.php` - komentarze
- `src/Modules/Users/Models/Record.php` - user model (deprecated methods)
- `src/Modules/Base/Models/MappedFields.php` - mapped fields (wymaga User object)

### Kontrolery

- `src/EntryPoint/WebUI.php` - główny entry point
- `src/Modules/Base/Actions/Save.php` - akcja zapisu
- `src/Api/Webservice/Core/BaseAction.php` - API actions

### Klasy pomocnicze

- `src/CRMEntity.php` - legacy entity
- `src/Field.php` - pola
- `src/Privilege.php` - uprawnienia
- `src/Utils/InventoryUtils.php` - utility

### Request/User handling

- `src/Http/Vtiger_Request.php` - request object (już ma user methods)
- `src/User/CurrentUser.php` - deprecated facade

## Checklist refaktoryzacji metody

Przed refaktoryzacją każdej metody, odpowiedz na pytania:

- [ ] Czy metoda używa `getCurrentUserId()` lub `getCurrentUserModel()`? → Przekaż `?\App\Modules\Users\Models\Record $currentUser = null`
- [ ] Czy metoda ma dostęp do `$request`? → Użyj `$request->getUser()` w kontrolerze
- [ ] Czy dodałem fallback do deprecated methods z `trigger_error()`?
- [ ] Czy zaktualizowałem wszystkie wywołania tej metody w kontrolerach?
- [ ] Czy dodałem testy jednostkowe dla nowej sygnatury?
- [ ] Czy użyłem `$currentUser->getId()` gdy potrzebny tylko ID?

## Przykłady refaktoryzacji

### Przykład 1: Metoda save() - używa tylko getId(), ale przyjmuje User object

**Przed:**
```php
public function save($request = null)
{
    $forSave['vtiger_crmentity']['modified_user_id'] = \App\Modules\Users\Models\Record::getCurrentUserRealId();
    // ...
}
```

**Po:**
```php
public function save($request = null, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('Model::save() called without user context', E_USER_DEPRECATED);
        }
    }
    // Użyj getId() gdy potrzebny tylko ID, ale mamy pełny obiekt na przyszłość
    $forSave['vtiger_crmentity']['modified_user_id'] = $currentUser->getId();
    // ...
}
```

**W kontrolerze:**
```php
$recordModel->save($request, $request->getUser());
```

### Przykład 2: Metoda autoAssignRecord() - używa uprawnień

**Przed:**
```php
public function autoAssignRecord()
{
    $userModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
    $roleData = \App\PrivilegeUtil::getRoleDetail($userModel->getRole());
    // ...
}
```

**Po:**
```php
public function autoAssignRecord(?\App\Modules\Users\Models\Record $currentUser = null, $request = null)
{
    if ($currentUser === null) {
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('Model::autoAssignRecord() called without user context', E_USER_DEPRECATED);
        }
    }
    // Użyj pełnych możliwości User object
    $roleData = \App\PrivilegeUtil::getRoleDetail($currentUser->getRole());
    // W przyszłości można łatwo dodać dodatkowe weryfikacje:
    // if (!$currentUser->isAdminUser()) { ... }
    // $groups = $currentUser->getGroups();
    // ...
}
```

**W kontrolerze:**
```php
$recordModel->autoAssignRecord($request->getUser());
```

## Podsumowanie

Refaktoryzacja ma na celu wprowadzenie właściwej separacji MVC poprzez:

1. **Eliminację statycznego dostępu** do current user w modelach
2. **Zawsze przekazywanie User object** jako `$currentUser` - spójne API dla wszystkich metod
3. **Elastyczność na przyszłość** - jeśli potrzebne będą dodatkowe weryfikacje, nie trzeba zmieniać sygnatur
4. **Backward compatibility** przez deprecated fallbacks
5. **Incremental migration** moduł po moduł

To podejście zapewnia:
- **Spójne API** - wszystkie metody używają tego samego typu parametru
- **Elastyczność** - łatwo dodać dodatkowe weryfikacje bez zmiany sygnatur
- **Lepsze testowanie** - jeden typ mock object
- **Właściwą separację MVC** - modele nie mają zależności od globalnego stanu
- **Brak overhead wydajnościowego** - User object już jest załadowany w request

