# Wytyczne do wieloetapowej migracji: Eliminacja pobierania aktualnego użytkownika w modelach

## Zasady ogólne

### Podstawowe reguły

1. **Zawsze przekazuj obiekt User** - `?\App\Modules\Users\Models\Record $currentUser = null`
2. **NIE przekazuj userId** - jeśli potrzebny ID, użyj `$currentUser->getId()`
3. **NIE przekazuj Vtiger_Request** - chyba że metoda potrzebuje innych rzeczy z request (np. inventory data)
4. **Fallback do deprecated** - jeśli `$currentUser === null`, użyj `\App\User\CurrentUser::get()` z `trigger_error()`
5. **Kolejność parametrów** - `$currentUser` zawsze jako ostatni parametr opcjonalny (lub przed ostatnim jeśli jest `$request`)

### Wzorzec refaktoringu

**Przed:**
```php
public function someMethod($param1, $param2)
{
    $userId = \App\Modules\Users\Models\Record::getCurrentUserId();
    // lub
    $userModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
    // ...
}
```

**Po:**
```php
public function someMethod($param1, $param2, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('someMethod() called without user context', E_USER_DEPRECATED);
    }
    // Użyj $currentUser->getId() gdy potrzebny tylko ID
    // Użyj $currentUser->getRole() gdy potrzebne uprawnienia
    // ...
}
```

**W kontrolerze:**
```php
$model->someMethod($param1, $param2, $request->getUser());
```

---

## ETAP 1: Przygotowanie infrastruktury i metody bazowe

### Cel
Przygotowanie infrastruktury i refaktoryzacja najczęściej używanych metod w klasie bazowej `Record`.

### Pliki objęte etapem

1. `src/Modules/Base/Models/Record.php` - metody:
   - `save()`
   - `getEntityDataForSave()`
   - `uploadAndSaveFile()`

2. `src/Modules/Base/Actions/Save.php` - aktualizacja wywołań

### Przykład 1: Metoda `save()` w Record.php

**Przed:**
```php
public function save($request = null)
{
    $db = \App\Database\PearDatabase::getInstance();
    $db->startTransaction();
    if ($this->getModule()->isInventory()) {
        $this->initInventoryData($request);
    }
    // ...
    $this->getModule()->saveRecord($this);
    // ...
}
```

**Po:**
```php
public function save($request = null, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    // Fallback do deprecated jeśli currentUser nie podany
    if ($currentUser === null) {
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('Record::save() called without user context', E_USER_DEPRECATED);
        }
    }
    
    $db = \App\Database\PearDatabase::getInstance();
    $db->startTransaction();
    if ($this->getModule()->isInventory()) {
        $this->initInventoryData($request);
    }
    // ...
    $this->getModule()->saveRecord($this, $currentUser);
    // ...
}
```

**Uwaga:** `$request` pozostaje, ponieważ jest potrzebny dla inventory data i innych rzeczy z request.

### Przykład 2: Metoda `getEntityDataForSave()` w Record.php

**Przed:**
```php
public function getEntityDataForSave()
{
    $row = [];
    $time = date('Y-m-d H:i:s');
    if ($this->isNew()) {
        $row['setype'] = $this->getModuleName();
        $row['smcreatorid'] = \App\Modules\Users\Models\Record::getCurrentUserRealId();
        $row['createdtime'] = $time;
        $row['users'] = ',' . \App\Modules\Users\Models\Record::getCurrentUserId() . ',';
        $this->set('createdtime', $time);
    }
    if ($this->getPreviousValue('modifiedtime')) {
        $time = $this->get('modifiedtime');
    }
    $row['modifiedtime'] = $time;
    $row['modifiedby'] = \App\Modules\Users\Models\Record::getCurrentUserRealId();
    $this->set('modifiedtime', $time);
    $this->set('modifiedby', \App\Modules\Users\Models\Record::getCurrentUserRealId());
    return ['vtiger_crmentity' => $row];
}
```

**Po:**
```php
public function getEntityDataForSave(?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('Record::getEntityDataForSave() called without user context', E_USER_DEPRECATED);
    }
    
    $row = [];
    $time = date('Y-m-d H:i:s');
    if ($this->isNew()) {
        $row['setype'] = $this->getModuleName();
        $row['smcreatorid'] = $currentUser->getRealId(); // Obsługuje user switching
        $row['createdtime'] = $time;
        $row['users'] = ',' . $currentUser->getId() . ',';
        $this->set('createdtime', $time);
    }
    if ($this->getPreviousValue('modifiedtime')) {
        $time = $this->get('modifiedtime');
    }
    $row['modifiedtime'] = $time;
    $row['modifiedby'] = $currentUser->getRealId(); // Obsługuje user switching
    $this->set('modifiedtime', $time);
    $this->set('modifiedby', $currentUser->getRealId());
    return ['vtiger_crmentity' => $row];
}
```

**Uwaga:** Używamy `getRealId()` dla audit trail (obsługuje user switching/impersonation).

### Przykład 3: Metoda `uploadAndSaveFile()` w Record.php

**Przed:**
```php
public function uploadAndSaveFile($fileDetails, $attachmentType = 'Attachment', $moduleName = null, $mode = null, $fileId = null)
{
    $id = $this->getId();
    $module = $moduleName !== null ? $moduleName : $this->getModuleName();
    \App\Log::trace("Entering into uploadAndSaveFile($id,$module,$fileDetails) method.");
    $db = \App\Db::getInstance();
    $userId = \App\Modules\Users\Models\Record::getCurrentUserId();
    $date = date('Y-m-d H:i:s');

    //to get the owner id
    $ownerid = $this->get('assigned_user_id');
    if (!isset($ownerid) || $ownerid === '')
        $ownerid = $userId;
    // ...
}
```

**Po:**
```php
public function uploadAndSaveFile($fileDetails, $attachmentType = 'Attachment', $moduleName = null, $mode = null, $fileId = null, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('Record::uploadAndSaveFile() called without user context', E_USER_DEPRECATED);
    }
    
    $id = $this->getId();
    $module = $moduleName !== null ? $moduleName : $this->getModuleName();
    \App\Log::trace("Entering into uploadAndSaveFile($id,$module,$fileDetails) method.");
    $db = \App\Db::getInstance();
    $userId = $currentUser->getId();
    $date = date('Y-m-d H:i:s');

    //to get the owner id
    $ownerid = $this->get('assigned_user_id');
    if (!isset($ownerid) || $ownerid === '')
        $ownerid = $userId;
    // ...
}
```

### Przykład 4: Aktualizacja kontrolera Save.php

**Przed:**
```php
public function saveRecord(\App\Http\Vtiger_Request $request)
{
    $recordModel = $this->getRecordModelFromRequest($request);
    $recordModel->save($request);
    // ...
}
```

**Po:**
```php
public function saveRecord(\App\Http\Vtiger_Request $request)
{
    $recordModel = $this->getRecordModelFromRequest($request);
    // Przekazujemy obiekt User z request
    $recordModel->save($request, $request->getUser());
    // ...
}
```

### Checklist etapu 1

- [ ] Zrefaktoryzowano `Record::save()` - dodano parametr `$currentUser`
- [ ] Zrefaktoryzowano `Record::getEntityDataForSave()` - dodano parametr `$currentUser`
- [ ] Zrefaktoryzowano `Record::uploadAndSaveFile()` - dodano parametr `$currentUser`
- [ ] Zaktualizowano `Save::saveRecord()` - przekazuje `$request->getUser()`
- [ ] Zaktualizowano wszystkie wywołania `getEntityDataForSave()` w `Record::save()` i innych miejscach
- [ ] Testy przechodzą dla modułów używających `save()`

---

## ETAP 2: Modele modułów - metody zapisu

### Cel
Refaktoryzacja metod `getValuesForSave()` w modelach konkretnych modułów.

### Pliki objęte etapem

1. `src/Modules/ModComments/Models/Record.php` - `getValuesForSave()`
2. `src/Modules/Users/Models/Record.php` - `getValuesForSave()`
3. Wszystkie inne modele z metodą `getValuesForSave()` używającą `getCurrentUserId()`

### Przykład 1: ModComments/Models/Record.php

**Przed:**
```php
public function getValuesForSave()
{
    $forSave = parent::getValuesForSave();
    if (empty($forSave['vtiger_modcomments']['userid'])) {
        $forSave['vtiger_modcomments']['userid'] = \App\Modules\Users\Models\Record::getCurrentUserId();
    }
    return $forSave;
}
```

**Po:**
```php
public function getValuesForSave(?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('ModComments\Record::getValuesForSave() called without user context', E_USER_DEPRECATED);
    }
    
    $forSave = parent::getValuesForSave($currentUser);
    if (empty($forSave['vtiger_modcomments']['userid'])) {
        $forSave['vtiger_modcomments']['userid'] = $currentUser->getId();
    }
    return $forSave;
}
```

**Uwaga:** Musimy zaktualizować wywołanie `parent::getValuesForSave($currentUser)`.

### Przykład 2: Users/Models/Record.php

**Przed:**
```php
public function getValuesForSave()
{
    $forSave = [
        'vtiger_users' => [
            'date_modified' => date('Y-m-d H:i:s'),
            'reminder_next_time' => date('Y-m-d H:i'),
            'modified_user_id' => \App\Modules\Users\Models\Record::getCurrentUserRealId(),
        ]
    ];
    // ...
}
```

**Po:**
```php
public function getValuesForSave(?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('Users\Record::getValuesForSave() called without user context', E_USER_DEPRECATED);
    }
    
    $forSave = [
        'vtiger_users' => [
            'date_modified' => date('Y-m-d H:i:s'),
            'reminder_next_time' => date('Y-m-d H:i'),
            'modified_user_id' => $currentUser->getRealId(), // Obsługuje user switching
        ]
    ];
    // ...
}
```

### Checklist etapu 2

- [ ] Zrefaktoryzowano wszystkie `getValuesForSave()` w modelach modułów
- [ ] Zaktualizowano wywołania `getValuesForSave($currentUser)` w `Record::save()`
- [ ] Testy przechodzą dla wszystkich modułów

---

## ETAP 3: Metody pomocnicze formatujące daty/czasy

### Cel
Refaktoryzacja metod pomocniczych używających preferencji użytkownika do formatowania.

### Pliki objęte etapem

1. `src/Modules/Base/Helpers/Util.php` - metody:
   - `formatDateIntoStrings()`
   - `formatDateTimeIntoDayString()`

### Przykład 1: formatDateIntoStrings()

**Przed:**
```php
public static function formatDateIntoStrings($date, $time = false)
{
    $currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
    $dateTimeInUserFormat = \App\Modules\Base\UiTypes\Datetime::getDisplayDateTimeValue($date . ' ' . $time);

    list($dateInUserFormat, $timeInUserFormat) = explode(' ', $dateTimeInUserFormat);
    list($hours, $minutes, $seconds) = explode(':', $timeInUserFormat);

    $displayTime = $hours . ':' . $minutes;
    if ($currentUser->get('hour_format') === '12') {
        $displayTime = \App\Modules\Base\UiTypes\Time::getTimeValueInAMorPM($displayTime);
    }
    // ...
}
```

**Po:**
```php
public static function formatDateIntoStrings($date, $time = false, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('Util::formatDateIntoStrings() called without user context', E_USER_DEPRECATED);
    }
    
    $dateTimeInUserFormat = \App\Modules\Base\UiTypes\Datetime::getDisplayDateTimeValue($date . ' ' . $time);

    list($dateInUserFormat, $timeInUserFormat) = explode(' ', $dateTimeInUserFormat);
    list($hours, $minutes, $seconds) = explode(':', $timeInUserFormat);

    $displayTime = $hours . ':' . $minutes;
    if ($currentUser->get('hour_format') === '12') {
        $displayTime = \App\Modules\Base\UiTypes\Time::getTimeValueInAMorPM($displayTime);
    }
    // ...
}
```

### Przykład 2: formatDateTimeIntoDayString()

**Przed:**
```php
public static function formatDateTimeIntoDayString($dateTime, $allday = false)
{
    $currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
    // ...
}
```

**Po:**
```php
public static function formatDateTimeIntoDayString($dateTime, $allday = false, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('Util::formatDateTimeIntoDayString() called without user context', E_USER_DEPRECATED);
    }
    // ...
}
```

### Checklist etapu 3

- [ ] Zrefaktoryzowano wszystkie metody formatujące w `Util.php`
- [ ] Zaktualizowano wszystkie wywołania tych metod (przekazują `$currentUser`)
- [ ] Testy przechodzą

---

## ETAP 4: Metody query i filtrowanie

### Cel
Refaktoryzacja metod wykonujących zapytania z filtrowaniem po użytkowniku.

### Pliki objęte etapem

1. `src/Modules/Base/Models/MiniList.php` - metody:
   - `getRecords()`
   - `getGetTotalCountURL()`
   - `getListViewURL()`

2. `src/Modules/Base/Models/DashBoard.php` - metody używające current user

### Przykład 1: MiniList::getRecords()

**Przed:**
```php
public function getRecords($user)
{
    $this->initListViewController();
    if (!$user) {
        $user = \App\Modules\Users\Models\Record::getCurrentUserId();
    } else if ($user === 'all') {
        $user = '';
    }
    if (!$this->listviewRecords) {
        if (!empty($user)) {
            $this->queryGenerator->addNativeCondition(['vtiger_crmentity.smownerid' => $user]);
        }
        // ...
    }
    return $this->listviewRecords;
}
```

**Po:**
```php
public function getRecords($user = null, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('MiniList::getRecords() called without user context', E_USER_DEPRECATED);
    }
    
    $this->initListViewController();
    if (!$user) {
        $user = $currentUser->getId();
    } else if ($user === 'all') {
        $user = '';
    }
    if (!$this->listviewRecords) {
        if (!empty($user)) {
            $this->queryGenerator->addNativeCondition(['vtiger_crmentity.smownerid' => $user]);
        }
        // ...
    }
    return $this->listviewRecords;
}
```

**Uwaga:** Parametr `$user` może być ID lub 'all', więc pozostaje. Dodajemy `$currentUser` jako osobny parametr.

### Przykład 2: MiniList::getGetTotalCountURL()

**Przed:**
```php
public function getGetTotalCountURL($user = false)
{
    $url = 'index.php?module=' . $this->getTargetModule() . '&action=Pagination&mode=getTotalCount&viewname=' . $this->widgetModel->get('filterid');
    if (!$user) {
        $user = \App\Modules\Users\Models\Record::getCurrentUserId();
    }
    return $user === 'all' ? $url : $url .= '&search_params=[[["assigned_user_id","e","' . $user . '"]]]';
}
```

**Po:**
```php
public function getGetTotalCountURL($user = false, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('MiniList::getGetTotalCountURL() called without user context', E_USER_DEPRECATED);
    }
    
    $url = 'index.php?module=' . $this->getTargetModule() . '&action=Pagination&mode=getTotalCount&viewname=' . $this->widgetModel->get('filterid');
    if (!$user) {
        $user = $currentUser->getId();
    }
    return $user === 'all' ? $url : $url .= '&search_params=[[["assigned_user_id","e","' . $user . '"]]]';
}
```

### Checklist etapu 4

- [ ] Zrefaktoryzowano wszystkie metody query w `MiniList.php`
- [ ] Zrefaktoryzowano metody w `DashBoard.php`
- [ ] Zaktualizowano wszystkie wywołania tych metod
- [ ] Testy przechodzą

---

## ETAP 5: Sprawdzanie własności i uprawnień

### Cel
Refaktoryzacja metod sprawdzających własność rekordów/widoków.

### Pliki objęte etapem

1. `src/Modules/CustomView/Models/Record.php` - metody:
   - `isDefault()`
   - `isMine()`

2. `src/Modules/Base/Models/Record.php` - metody sprawdzające uprawnienia

### Przykład 1: CustomView::isDefault()

**Przed:**
```php
public function isDefault()
{
    \App\Log::trace('Entering ' . __METHOD__ . ' method ...');
    if ($this->isDefault === false) {
        $currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
        $cvId = $this->getId();
        if (!$cvId) {
            $this->isDefault = false;
            return false;
        }
        $this->isDefault = (new \App\Db\Query())->from('vtiger_user_module_preferences')
            ->where(['userid' => 'Users:' . $currentUser->getId(), 'tabid' => $this->getModule()->getId(), 'default_cvid' => $cvId])
            ->exists();
    }
    \App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
    return $this->isDefault;
}
```

**Po:**
```php
public function isDefault(?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('CustomView::isDefault() called without user context', E_USER_DEPRECATED);
    }
    
    \App\Log::trace('Entering ' . __METHOD__ . ' method ...');
    if ($this->isDefault === false) {
        $cvId = $this->getId();
        if (!$cvId) {
            $this->isDefault = false;
            return false;
        }
        $this->isDefault = (new \App\Db\Query())->from('vtiger_user_module_preferences')
            ->where(['userid' => 'Users:' . $currentUser->getId(), 'tabid' => $this->getModule()->getId(), 'default_cvid' => $cvId])
            ->exists();
    }
    \App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
    return $this->isDefault;
}
```

### Przykład 2: CustomView::isMine()

**Przed:**
```php
public function isMine()
{
    $userModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
    return ($this->get('status') == \App\CustomView::CV_STATUS_DEFAULT || $this->get('userid') == $userModel->getId());
}
```

**Po:**
```php
public function isMine(?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('CustomView::isMine() called without user context', E_USER_DEPRECATED);
    }
    
    return ($this->get('status') == \App\CustomView::CV_STATUS_DEFAULT || $this->get('userid') == $currentUser->getId());
}
```

### Checklist etapu 5

- [ ] Zrefaktoryzowano wszystkie metody sprawdzające własność
- [ ] Zaktualizowano wszystkie wywołania tych metod
- [ ] Testy przechodzą

---

## ETAP 6: Metody statyczne w klasach pomocniczych

### Cel
Refaktoryzacja metod statycznych w klasach pomocniczych używających current user.

### Pliki objęte etapem

1. `src/Modules/Base/Models/Module.php` - metody statyczne używające current user
2. `src/Modules/Users/Models/Privileges.php` - metody statyczne
3. Inne klasy pomocnicze

### Przykład 1: Module::getInstance() (jeśli używa current user)

**Przed:**
```php
public static function getInstance($mixed)
{
    // Jeśli gdzieś używa getCurrentUserId() wewnętrznie
    $userId = \App\Modules\Users\Models\Record::getCurrentUserId();
    // ...
}
```

**Po:**
```php
public static function getInstance($mixed, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('Module::getInstance() called without user context', E_USER_DEPRECATED);
    }
    
    // Użyj $currentUser->getId() jeśli potrzebne
    $userId = $currentUser->getId();
    // ...
}
```

### Checklist etapu 6

- [ ] Zrefaktoryzowano wszystkie metody statyczne używające current user
- [ ] Zaktualizowano wszystkie wywołania tych metod
- [ ] Testy przechodzą

---

## ETAP 7: Klasy legacy (CRMEntity, Field, Utils)

### Cel
Refaktoryzacja klas legacy używających current user.

### Pliki objęte etapem

1. `src/CRMEntity.php` - metody używające `CurrentUser::get()`
2. `src/Field.php` - metody cache używające current user
3. Inne klasy legacy

### Przykład: CRMEntity (jeśli używa current user)

**Przed:**
```php
public function someMethod()
{
    $currentUser = \App\User\CurrentUser::get();
    // ...
}
```

**Po:**
```php
public function someMethod(?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('CRMEntity::someMethod() called without user context', E_USER_DEPRECATED);
    }
    // ...
}
```

### Checklist etapu 7

- [ ] Zrefaktoryzowano klasy legacy
- [ ] Zaktualizowano wywołania
- [ ] Testy przechodzą

---

## ETAP 8: Workflow i Automation

### Cel
Refaktoryzacja workflow i automation używających current user.

### Pliki objęte etapem

1. `src/Modules/Base/Models/WorkflowTrigger.php` - `execute()`
2. `src/Modules/Base/Actions/Workflow.php` - przekazywanie user do workflow
3. Workflow tasks używające current user

### Przykład 1: WorkflowTrigger::execute()

**Przed:**
```php
public static function execute($moduleName, $record, $ids, $userId)
{
    $recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
    if ($userId) {
        $recordModel->executeUser = $userId;
    }
    // ...
}
```

**Po:**
```php
public static function execute($moduleName, $record, $ids, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('WorkflowTrigger::execute() called without user context', E_USER_DEPRECATED);
    }
    
    $recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
    $recordModel->executeUser = $currentUser;
    // ...
}
```

### Przykład 2: Workflow Action

**Przed:**
```php
public function execute(\App\Http\Vtiger_Request $request)
{
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $ids = $request->get('ids');
    
    \App\Modules\Base\Models\WorkflowTrigger::execute($moduleName, $record, $ids, $request->getUserId());
    // ...
}
```

**Po:**
```php
public function execute(\App\Http\Vtiger_Request $request)
{
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $ids = $request->get('ids');
    
    // Workflow zawsze działa w kontekście prawdziwego użytkownika (dla audit trail)
    \App\Modules\Base\Models\WorkflowTrigger::execute($moduleName, $record, $ids, $request->getUser());
    // ...
}
```

### Checklist etapu 8

- [ ] Zrefaktoryzowano `WorkflowTrigger::execute()`
- [ ] Zrefaktoryzowano workflow actions
- [ ] Zrefaktoryzowano workflow tasks
- [ ] Testy przechodzą

---

## ETAP 9: Cron Jobs i CLI

### Cel
Refaktoryzacja cron jobs i CLI scripts używających current user.

### Pliki objęte etapem

1. `src/Modules/Cron/Runner/CronRunner.php` - przekazywanie admin user do cron tasks
2. Cron task handlers używające modeli

### Przykład: CronRunner

**Przed:**
```php
public function runTask(\vtlib\Cron $cronTask): void
{
    try {
        // Ustaw admin user przez static method
        $adminId = \App\Modules\Users\Models\Record::getActiveAdminId();
        \App\Modules\Users\Models\Record::setCurrentUserId($adminId);
        
        // Wykonaj cron task
        require_once $cronTask->getHandlerFile();
        // ...
    }
}
```

**Po:**
```php
public function runTask(\vtlib\Cron $cronTask): void
{
    try {
        // Załaduj admin user jako obiekt
        $adminId = \App\Modules\Users\Models\Record::getActiveAdminId();
        $adminUser = \App\Modules\Users\Models\Record::getInstanceById($adminId, 'Users');
        
        // Dla backward compatibility, ustaw również static (deprecated)
        \App\Modules\Users\Models\Record::setCurrentUserId($adminId);
        
        // Przekaż adminUser do cron task handler
        // Cron tasks powinny przyjmować $currentUser jako parametr
        require_once $cronTask->getHandlerFile();
        // ...
    }
}
```

### Przykład: Cron Task Handler

**Przed:**
```php
function cronTaskHandler()
{
    $recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, 'Module');
    $recordModel->save(null); // Używa static getCurrentUserId()
    // ...
}
```

**Po:**
```php
function cronTaskHandler(?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        // Fallback dla backward compatibility
        $currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
        trigger_error('Cron task called without user context', E_USER_DEPRECATED);
    }
    
    $recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, 'Module');
    $recordModel->save(null, $currentUser);
    // ...
}
```

### Checklist etapu 9

- [ ] Zrefaktoryzowano CronRunner
- [ ] Zrefaktoryzowano cron task handlers
- [ ] Testy przechodzą dla cron jobs

---

## ETAP 10: Finalne czyszczenie i deprecation

### Cel
Oznaczenie wszystkich deprecated methods i przygotowanie do usunięcia w v2.0.

### Pliki objęte etapem

1. `src/Modules/Users/Models/Record.php` - oznaczenie jako deprecated:
   - `getCurrentUserId()`
   - `getCurrentUserModel()`
   - `getCurrentUserRealId()` (może zostać jako helper)

2. `src/User/CurrentUser.php` - już oznaczone jako deprecated

### Przykład: Oznaczenie jako deprecated

**W Record.php:**
```php
/**
 * Get current user Id
 * @deprecated Use $request->getUser()->getId() or pass $currentUser as parameter to model methods
 * Will be removed in version 2.0
 * @return int
 */
public static function getCurrentUserId()
{
    // Legacy implementation - get from session
    if (!static::$currentUserId) {
        static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
    }
    return static::$currentUserId;
}

/**
 * Static Function to get the instance of the User Record model for the current user
 * @deprecated Use $request->getUser() or pass $currentUser as parameter to model methods
 * Will be removed in version 2.0
 * @return \App\Modules\Users\Models\Record instance
 */
public static function getCurrentUserModel()
{
    // Legacy implementation - get from session
    if (static::$currentUserCache) {
        return static::$currentUserCache;
    }
    if (!static::$currentUserId) {
        static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
    }
    return static::$currentUserCache = self::getInstanceById(
        static::$currentUserId,
        'Users'
    );
}
```

### Checklist etapu 10

- [ ] Oznaczono wszystkie deprecated methods
- [ ] Zaktualizowano dokumentację
- [ ] Przygotowano migration guide dla developerów
- [ ] Testy przechodzą

---

## Wzorce i najlepsze praktyki

### Wzorzec 1: Metoda wymagająca tylko userId

```php
public function someMethod($param1, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('someMethod() called without user context', E_USER_DEPRECATED);
    }
    
    // Użyj getId() gdy potrzebny tylko ID
    $userId = $currentUser->getId();
    // ...
}
```

### Wzorzec 2: Metoda wymagająca uprawnień/preferencji

```php
public function someMethod($param1, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        $currentUser = \App\User\CurrentUser::get();
        trigger_error('someMethod() called without user context', E_USER_DEPRECATED);
    }
    
    // Użyj pełnych możliwości User object
    $role = $currentUser->getRole();
    $isAdmin = $currentUser->isAdminUser();
    $dateFormat = $currentUser->get('date_format');
    // ...
}
```

### Wzorzec 3: Metoda z request (dla inventory data)

```php
public function save($request = null, ?\App\Modules\Users\Models\Record $currentUser = null)
{
    if ($currentUser === null) {
        if ($request && $request->hasUser()) {
            $currentUser = $request->getUser();
        } else {
            $currentUser = \App\User\CurrentUser::get();
            trigger_error('save() called without user context', E_USER_DEPRECATED);
        }
    }
    
    // $request jest potrzebny dla inventory data
    if ($this->getModule()->isInventory()) {
        $this->initInventoryData($request);
    }
    // ...
}
```

### Wzorzec 4: Kontroler przekazujący User

```php
public function process(\App\Http\Vtiger_Request $request)
{
    $recordModel = $this->getRecordModelFromRequest($request);
    // Zawsze przekazujemy User object z request
    $recordModel->save($request, $request->getUser());
    // ...
}
```

---

## Checklist ogólny migracji

### Przed rozpoczęciem każdego etapu

- [ ] Przeanalizowano wszystkie pliki objęte etapem
- [ ] Zidentyfikowano wszystkie wywołania metod do refaktoryzacji
- [ ] Przygotowano plan testów

### Podczas refaktoryzacji

- [ ] Dodano parametr `?\App\Modules\Users\Models\Record $currentUser = null`
- [ ] Dodano fallback do deprecated z `trigger_error()`
- [ ] Zaktualizowano wszystkie wywołania metody
- [ ] Użyto `$currentUser->getId()` gdy potrzebny tylko ID
- [ ] Użyto `$currentUser->getRealId()` dla audit trail (user switching)
- [ ] Użyto pełnych metod User object gdy potrzebne uprawnienia/preferencje

### Po refaktoryzacji

- [ ] Testy jednostkowe przechodzą
- [ ] Testy integracyjne przechodzą
- [ ] Sprawdzono logi deprecated warnings
- [ ] Zaktualizowano dokumentację

---

## Metryki sukcesu

1. **Redukcja użyć static methods**: Z 220+ do <10 (tylko specjalne przypadki jak cron)
2. **Pokrycie testami**: Wszystkie nowe metody z `$currentUser` parameter mają testy
3. **Backward compatibility**: Aplikacja działa bez błędów podczas migracji
4. **Code quality**: Modele nie mają zależności od sesji/globalnego stanu
5. **API consistency**: Wszystkie metody używają spójnego parametru `$currentUser`

---

## Ryzyka i migracja

### Ryzyka

- Breaking changes w custom modules/extensions
- Trudność refaktoryzacji legacy code (CRMEntity)
- Performance overhead jest praktycznie nieistotny - User object jest już załadowany w request

### Strategia migracji

- **Incremental**: Refaktoryzacja etap po etap
- **Backward compatible**: Deprecated fallbacks przez cały okres migracji
- **Documentation**: Jasne przykłady i migration guide
- **Testing**: Testy na każdym etapie refaktoryzacji
- **Consistent API**: Wszystkie metody używają `$currentUser` - spójne podejście

---

## Podsumowanie

Ten plan migracji zapewnia:

1. **Spójne API** - wszystkie metody używają tego samego typu parametru (`$currentUser`)
2. **Elastyczność** - łatwo dodać dodatkowe weryfikacje bez zmiany sygnatur
3. **Lepsze testowanie** - jeden typ mock object
4. **Właściwą separację MVC** - modele nie mają zależności od globalnego stanu
5. **Brak overhead wydajnościowego** - User object już jest załadowany w request
6. **Backward compatibility** - deprecated fallbacks przez cały okres migracji

