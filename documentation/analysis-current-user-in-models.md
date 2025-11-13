# Analiza użycia funkcji odwołujących się do obecnego użytkownika w modelach

## Statystyki

- **Łączna liczba wystąpień**: 220+ w 117 plikach
- **Główne funkcje używane**:
  - `Record::getCurrentUserId()` - ~82+ miejsc
  - `Record::getCurrentUserModel()` - ~100+ miejsc  
  - `Record::getCurrentUserRealId()` - ~20+ miejsc
  - `CurrentUser::get()` (deprecated) - ~125+ miejsc

## Kategorie użycia

### 1. Zapisywanie metadanych rekordów

**Cel**: Automatyczne ustawianie pól `smcreatorid`, `modified_user_id`, `userid` podczas zapisu

**Przykłady**:

```245:245:src/Modules/Users/Models/Record.php
				'modified_user_id' => \App\Modules\Users\Models\Record::getCurrentUserRealId(),
```

```478:489:src/Modules/Base/Models/Record.php
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
```

```80:82:src/Modules/ModComments/Models/Record.php
		if (empty($forSave['vtiger_modcomments']['userid'])) {
			$forSave['vtiger_modcomments']['userid'] = \App\Modules\Users\Models\Record::getCurrentUserId();
		}
```

**Obserwacje**:
- Używa się `getCurrentUserRealId()` dla `smcreatorid` i `modifiedby` (obsługa impersonacji)
- Używa się `getCurrentUserId()` dla prostych pól `userid`
- Występuje w metodach `getValuesForSave()` i `getEntityDataForSave()`

---

### 2. Formatowanie dat i czasu zgodnie z preferencjami użytkownika

**Cel**: Formatowanie dat/czasu zgodnie z preferencjami użytkownika (`date_format`, `hour_format`)

**Przykłady**:

```143:156:src/Modules/Base/Helpers/Util.php
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$dateTimeInUserFormat = \App\Modules\Base\UiTypes\Datetime::getDisplayDateTimeValue($date . ' ' . $time);

		list($dateInUserFormat, $timeInUserFormat) = explode(' ', $dateTimeInUserFormat);
		list($hours, $minutes, $seconds) = explode(':', $timeInUserFormat);

		$displayTime = $hours . ':' . $minutes;
		if ($currentUser->get('hour_format') === '12') {
			$displayTime = \App\Modules\Base\UiTypes\Time::getTimeValueInAMorPM($displayTime);
		}

		$today = \App\Modules\Base\UiTypes\Date::getDisplayDateValue(date('Y-m-d H:i:s'));
		$tomorrow = \App\Modules\Base\UiTypes\Date::getDisplayDateValue(date('Y-m-d H:i:s', strtotime('tomorrow')));
		$userDate = \App\Fields\DateTimeField::__convertToUserFormat($date, $currentUser->get('date_format'));
```

```201:208:src/Modules/Base/Helpers/Util.php
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$dateTimeInUserFormat = explode(' ', \App\Modules\Base\UiTypes\Datetime::getDisplayDateTimeValue($dateTime));

		if (count($dateTimeInUserFormat) == 3) {
			list($dateInUserFormat, $timeInUserFormat, $meridiem) = $dateTimeInUserFormat;
		} else {
			list($dateInUserFormat, $timeInUserFormat) = $dateTimeInUserFormat;
			$meridiem = '';
```

**Obserwacje**:
- Wymaga pełnego obiektu użytkownika (`getCurrentUserModel()`)
- Używa preferencji: `date_format`, `hour_format`, `time_zone`
- Występuje w funkcjach pomocniczych formatujących daty/czasy

---

### 3. Filtrowanie zapytań po użytkowniku

**Cel**: Filtrowanie rekordów przypisanych do obecnego użytkownika

**Przykłady**:

```111:122:src/Modules/Base/Models/MiniList.php
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
```

```144:150:src/Modules/Base/Models/MiniList.php
	public function getGetTotalCountURL($user = false)
	{
		$url = 'index.php?module=' . $this->getTargetModule() . '&action=Pagination&mode=getTotalCount&viewname=' . $this->widgetModel->get('filterid');
		if (!$user) {
			$user = \App\Modules\Users\Models\Record::getCurrentUserId();
		}
		return $user === 'all' ? $url : $url .= '&search_params=[[["assigned_user_id","e","' . $user . '"]]]';
```

**Obserwacje**:
- Używa się `getCurrentUserId()` (wystarczy ID)
- Występuje w widgetach, listach, dashboardach
- Często jako wartość domyślna gdy parametr `$user` nie jest podany

---

### 4. Sprawdzanie własności i uprawnień

**Cel**: Sprawdzanie czy rekord/widok należy do obecnego użytkownika

**Przykłady**:

```118:122:src/Modules/CustomView/Models/Record.php
	public function isMine()
	{
		$userModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		return ($this->get('status') == \App\CustomView::CV_STATUS_DEFAULT || $this->get('userid') == $userModel->getId());
	}
```

```90:107:src/Modules/CustomView/Models/Record.php
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

```1301:1311:src/Modules/Base/Models/Record.php
			array_key_exists(\App\Modules\Users\Models\Record::getCurrentUserId(), \App\Fields\Owner::getInstance($this->getModuleName())->getAccessibleUsers('', 'owner'));
		}
		if ($this->has('assigned_user_id')) {
			$assignedUserId = $this->get('assigned_user_id');
			if ($assignedUserId) {
				$userModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
```

**Obserwacje**:
- Często używa się `getCurrentUserModel()` gdy potrzebny jest pełny obiekt
- Porównywanie ID użytkownika z polami rekordów
- Sprawdzanie uprawnień dostępu

---

### 5. Klucze cache

**Cel**: Tworzenie unikalnych kluczy cache na podstawie ID użytkownika

**Przykłady**:

```1087:1093:src/Modules/Base/Models/Record.php
		$userId = \App\Modules\Users\Models\Record::getCurrentUserId();
		$date = date('Y-m-d H:i:s');

		//to get the owner id
		$ownerid = $this->get('assigned_user_id');
		if (!isset($ownerid) || $ownerid === '')
			$ownerid = $userId;
```

**Obserwacje**:
- Używa się `getCurrentUserId()` (wystarczy ID)
- Cache jest często per-user (np. `Cache::has(__METHOD__ . $userId)`)

---

### 6. Ustawianie domyślnych wartości

**Cel**: Ustawianie domyślnych wartości pól na podstawie preferencji użytkownika

**Przykłady**:

```1087:1093:src/Modules/Base/Models/Record.php
		$userId = \App\Modules\Users\Models\Record::getCurrentUserId();
		$date = date('Y-m-d H:i:s');

		//to get the owner id
		$ownerid = $this->get('assigned_user_id');
		if (!isset($ownerid) || $ownerid === '')
			$ownerid = $userId;
```

**Obserwacje**:
- Gdy pole `assigned_user_id` jest puste, ustawia się na obecnego użytkownika
- Występuje w metodach zapisu i tworzenia rekordów

---

### 7. Sprawdzanie uprawnień i ról

**Cel**: Sprawdzanie czy użytkownik ma określone uprawnienia lub role

**Przykłady**:

```266:266:src/Modules/Settings/Base/Models/MenuItem.php
		$dataReader = $query->andWhere(['and', ['NOT IN', 'name', $skipMenuItemList], ['or', ['like', 'admin_access', ',' . \App\Modules\Users\Models\Record::getCurrentUserId() . ','], ['admin_access' => null]]])
```

**Obserwacje**:
- Sprawdzanie czy użytkownik jest w liście uprawnionych (np. `admin_access`)
- Często używa się formatu `,userId,` dla sprawdzania w stringach

---

## Wzorce użycia

### Wzorzec 1: Zapisywanie metadanych
```php
// W getValuesForSave() lub getEntityDataForSave()
$forSave['table']['field'] = \App\Modules\Users\Models\Record::getCurrentUserRealId();
// lub
$forSave['table']['userid'] = \App\Modules\Users\Models\Record::getCurrentUserId();
```

### Wzorzec 2: Formatowanie z preferencjami
```php
$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
$dateFormat = $currentUser->get('date_format');
$hourFormat = $currentUser->get('hour_format');
```

### Wzorzec 3: Filtrowanie zapytań
```php
if (!$user) {
    $user = \App\Modules\Users\Models\Record::getCurrentUserId();
}
$query->where(['smownerid' => $user]);
```

### Wzorzec 4: Sprawdzanie własności
```php
$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
return $this->get('userid') == $currentUser->getId();
```

### Wzorzec 5: Domyślne wartości
```php
$ownerid = $this->get('assigned_user_id');
if (empty($ownerid)) {
    $ownerid = \App\Modules\Users\Models\Record::getCurrentUserId();
}
```

---

## Problemy i wyzwania

### 1. Zależność od globalnego stanu
- Modele bezpośrednio odczytują sesję (`Vtiger_Session::get('authenticated_user_id')`)
- Trudne testowanie - wymaga mockowania sesji
- Nie można używać modeli w kontekstach bez sesji (CLI, batch jobs)

### 2. Brak separacji MVC
- Modele mają wiedzę o kontekście sesji
- Powinny otrzymywać użytkownika jako parametr z kontrolera

### 3. Mieszane podejścia
- Część kodu używa `Record::getCurrentUserId()`
- Część używa `CurrentUser::get()` (deprecated)
- Część bezpośrednio odczytuje sesję

### 4. Problemy z impersonacją
- `getCurrentUserRealId()` obsługuje impersonację, ale nie zawsze jest używane
- Może prowadzić do błędów w logowaniu zmian

---

## Rekomendacje

### 1. Przekazywanie użytkownika jako parametru
Zamiast:
```php
$userId = \App\Modules\Users\Models\Record::getCurrentUserId();
```

Powinno być:
```php
public function save($request = null) {
    $currentUser = $request ? $request->getUser() : \App\Modules\Users\Models\Record::getCurrentUserModel();
    // ...
}
```

### 2. Użycie Vtiger_Request
- `Vtiger_Request` ma już metody: `setUser()`, `getUser()`, `getUserId()`, `hasUser()`
- Niektóre kontrolery już ustawiają użytkownika w request (`Save::preProcess()`)

### 3. Stopniowa migracja
- Zaczynać od nowych funkcji - zawsze przekazywać użytkownika jako parametr
- Refaktoryzować istniejące funkcje stopniowo
- Zachować backward compatibility przez deprecation warnings

### 4. Zawsze przekazywać User object
- User object jest już załadowany w `Vtiger_Request` podczas inicjalizacji
- Nie ma uzasadnienia wydajnościowego dla przekazywania userId zamiast User object
- W PHP obiekty są przekazywane przez referencję

---

## Pliki wymagające szczególnej uwagi

### Najczęściej używane:
1. `src/Modules/Base/Models/Record.php` - 7 wystąpień (metody zapisu)
2. `src/Modules/CustomView/Models/Record.php` - 9 wystąpień (sprawdzanie własności)
3. `src/Modules/Base/Helpers/Util.php` - 4 wystąpienia (formatowanie)
4. `src/Modules/Base/Models/MiniList.php` - 3 wystąpienia (filtrowanie)
5. `src/Modules/Base/Models/DashBoard.php` - 3 wystąpienia (widgety)

### Moduły z wieloma wystąpieniami:
- Reports (8+ wystąpień)
- Calendar (5+ wystąpień)
- ModTracker (15+ wystąpień)
- OSSMail (5+ wystąpień)
- Products (3+ wystąpień)

---

## Podsumowanie

Funkcje odwołujące się do obecnego użytkownika są używane w modelach w następujących celach:

1. **Zapisywanie metadanych** (najczęściej) - `smcreatorid`, `modified_user_id`, `userid`
2. **Formatowanie dat/czasu** - zgodnie z preferencjami użytkownika
3. **Filtrowanie zapytań** - rekordy przypisane do użytkownika
4. **Sprawdzanie własności** - czy rekord/widok należy do użytkownika
5. **Klucze cache** - cache per-user
6. **Domyślne wartości** - ustawianie `assigned_user_id` na obecnego użytkownika
7. **Sprawdzanie uprawnień** - role i uprawnienia użytkownika

**Główne problemy**:
- Zależność od globalnego stanu (sesja)
- Brak separacji MVC
- Trudne testowanie
- Nie można używać w kontekstach bez sesji

**Rekomendowane rozwiązanie**:
- Przekazywanie użytkownika jako parametru przez kontrolery
- Użycie `Vtiger_Request->getUser()` zamiast statycznych metod
- Stopniowa migracja z zachowaniem backward compatibility

