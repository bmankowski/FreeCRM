# Lista miejsc używających logiki "yes/no" zamiast bool

**Data utworzenia:** 2025-01-27  
**Status:** Do migracji  
**Priorytet:** Wysoki

---

## 📋 Podsumowanie

Znaleziono **52 miejsca** w kodzie, które używają logiki `'yes'/'no'` zamiast wartości bool. Wszystkie te miejsca wymagają migracji do `\App\Privilege::isPermitted()`, które zwraca `bool`.

### Statystyki

- **Bezpośrednie użycia `UserInfoUtil::isPermitted()`**: ~30 miejsc
- **Porównania z `'yes'`**: ~20 miejsc
- **Porównania z `'no'`**: ~5 miejsc
- **Konwersje `'yes'/'no'` → bool**: ~3 miejsca
- **Metody sharing zwracające `'yes'/'no'`**: ~4 miejsca

---

## 🔍 Szczegółowa lista miejsc do migracji

### Kategoria 1: Bezpośrednie użycia `UserInfoUtil::isPermitted()` z porównaniem `== 'yes'`

#### 1.1. Moduły CRM

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `src/CRMEntity.php` | 1250 | `\App\Utils\UserInfoUtil::isPermitted($module, 'EditView', '') == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/Accounts/Accounts.php` | 257 | `\App\Utils\UserInfoUtil::isPermitted('Accounts', 'DetailView', $accountId) == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/OSSEmployees/OSSEmployees.php` | 133 | `\App\Utils\UserInfoUtil::isPermitted('OSSEmployees', 'DetailView', $employees_id) == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/IStorages/IStorages.php` | 162 | `\App\Utils\UserInfoUtil::isPermitted('IStorages', 'DetailView', $iStorageId) == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/ModComments/ModComments.php` | 55 | `\App\Utils\UserInfoUtil::isPermitted('ModComments', 'DetailView') == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/ModTracker/ModTracker.php` | 316 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, 'DetailView', $recordId) == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/ProjectTask/ProjectTask.php` | 367 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, 'EditView') == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/Documents/Documents.php` | 374 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, 'EditView') == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |

#### 1.2. Moduł Reports (5 miejsc)

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `src/Modules/Reports/Models/Folder.php` | 329 | `\App\Utils\UserInfoUtil::isPermitted($primaryModule, 'index') == "yes"` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/Reports/Models/Report.php` | 124 | `\App\Utils\UserInfoUtil::isPermitted($key, 'index') == "yes"` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/Reports/Reports.php` | 371 | `\App\Utils\UserInfoUtil::isPermitted($report["primarymodule"], 'index') == "yes"` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/Reports/Reports.php` | 458 | `\App\Utils\UserInfoUtil::isPermitted($report["primarymodule"], 'index') == "yes"` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/Reports/Reports.php` | 1145 | `\App\Utils\UserInfoUtil::isPermitted($key, 'index') == "yes"` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Modules/Reports/Reports.php` | 1164 | `\App\Utils\UserInfoUtil::isPermitted($rel_modules, 'index') == "yes"` | Zamienić na `\App\Privilege::isPermitted()` |

**Uwaga:** W `Reports.php` linia 263 jest już poprawiona (bez porównania z `'yes'`).

#### 1.3. Moduł Calendar (4 miejsca)

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `src/Modules/Calendar/Actions/DragDropAjax.php` | 42 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, $actionname, $recordId) === 'no'` | Zamienić na `!\App\Privilege::isPermitted()` |
| `src/Modules/Calendar/Actions/DragDropAjax.php` | 89 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, $actionname, $recordId) === 'no'` | Zamienić na `!\App\Privilege::isPermitted()` |
| `src/Modules/Calendar/Actions/Calendar.php` | 74 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, $actionname, $recordId) === 'no'` | Zamienić na `!\App\Privilege::isPermitted()` |
| `src/Modules/Calendar/Models/DetailView.php` | 69 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, 'ActivityComplete', $recordId) == 'yes' && ...` | Zamienić na `\App\Privilege::isPermitted()` (3 wywołania) |

#### 1.4. Inne moduły

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `src/Modules/Reservations/Actions/Calendar.php` | 61 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, 'EditView', $recordId) === 'no'` | Zamienić na `!\App\Privilege::isPermitted()` |
| `src/Modules/OSSTimeControl/Actions/Calendar.php` | 59 | `\App\Utils\UserInfoUtil::isPermitted($moduleName, 'EditView', $recordId) === 'no'` | Zamienić na `!\App\Privilege::isPermitted()` |

#### 1.5. Web Services

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `src/Webservices/VtigerModuleOperation.php` | 203 | `strcasecmp(\App\Utils\UserInfoUtil::isPermitted($elementType, EntityMeta::$CREATE), 'yes') === 0` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Webservices/VtigerModuleOperation.php` | 204 | `strcasecmp(\App\Utils\UserInfoUtil::isPermitted($elementType, EntityMeta::$UPDATE), 'yes') === 0` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Webservices/VtigerCRMObjectMeta.php` | 206 | `\App\Utils\UserInfoUtil::isPermitted($this->getTabName(), $operation, $id)` | Sprawdzić zwracaną wartość (może być już bool) |

#### 1.6. Utilities

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `src/Utils/EmailTemplate.php` | 244 | `isPermitted($module, 'EditView') == 'yes'` | Zamienić na `\App\Privilege::isPermitted()` |
| `src/Utils/Utils.php` | 430 | `isPermitted('PBXManager', 'index') == 'no'` | Zamienić na `!\App\Privilege::isPermitted()` |

#### 1.7. Szablony Smarty

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `layouts/basic/modules/Base/RecentActivities.tpl` | 106 | `{if isPermitted('Calendar', 'DetailView', $RELATION->getLinkedRecord()->getId()) eq 'yes'}` | Zamienić na funkcję zwracającą bool |
| `layouts/basic/modules/Base/dashboards/HistoryContents.tpl` | 98 | `{if isPermitted('Calendar', 'DetailView', $RELATION->getLinkedRecord()->getId()) eq 'yes'}` | Zamienić na funkcję zwracającą bool |

---

### Kategoria 2: Konwersje `'yes'/'no'` → bool

#### 2.1. Wewnętrzne konwersje w `UserInfoUtil`

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `src/Utils/UserInfoUtil.php` | 263 | `$relatedPermission = $permission == 'yes' ? true : false;` | Zamienić na bezpośrednie użycie bool |

#### 2.2. Konwersje w `Privileges` model

| Plik | Linia | Kod | Akcja |
|------|-------|-----|-------|
| `src/Modules/Users/Models/Privileges.php` | 462 | `$relatedPermission = $permission == 'yes' ? true : false;` | Zamienić na bezpośrednie użycie bool |
| `src/Modules/Users/Models/Privileges.php` | 499 | `$relatedPermission = $permission == 'yes' ? true : false;` | Zamienić na bezpośrednie użycie bool |

---

### Kategoria 3: Metody sharing zwracające `'yes'/'no'`

#### 3.1. Metody w `UserInfoUtil`

| Plik | Metoda | Zwraca | Akcja |
|------|--------|--------|-------|
| `src/Utils/UserInfoUtil.php` | `isPermittedBySharing()` | `'yes'/'no'` | Zmienić na `bool` |
| `src/Utils/UserInfoUtil.php` | `isReadPermittedBySharing()` | `'yes'/'no'` | Zmienić na `bool` |
| `src/Utils/UserInfoUtil.php` | `isReadWritePermittedBySharing()` | `'yes'/'no'` | Zmienić na `bool` |

**Uwaga:** Metody w `\App\Privilege` już zwracają `bool`, ale są wywoływane przez `UserInfoUtil::isPermittedBySharing()`.

---

## 🔄 Przykłady migracji

### Przykład 1: Proste porównanie z `'yes'`

**Przed:**
```php
if (\App\Utils\UserInfoUtil::isPermitted('Accounts', 'DetailView', $accountId) == 'yes') {
    // kod
}
```

**Po:**
```php
if (\App\Privilege::isPermitted('Accounts', 'DetailView', $accountId)) {
    // kod
}
```

---

### Przykład 2: Porównanie z `'no'`

**Przed:**
```php
if (\App\Utils\UserInfoUtil::isPermitted($moduleName, 'EditView', $recordId) === 'no') {
    return;
}
```

**Po:**
```php
if (!\App\Privilege::isPermitted($moduleName, 'EditView', $recordId)) {
    return;
}
```

---

### Przykład 3: Konwersja `'yes'/'no'` → bool

**Przed:**
```php
$permission = \App\Utils\UserInfoUtil::isPermittedBySharing($module, $tabid, $actionid, $record_id);
$relatedPermission = $permission == 'yes' ? true : false;
```

**Po:**
```php
$relatedPermission = \App\Privilege::isPermittedBySharing($module, $tabid, $actionid, $record_id);
```

---

### Przykład 4: `strcasecmp` w Web Services

**Przed:**
```php
$createable = (strcasecmp(\App\Utils\UserInfoUtil::isPermitted($elementType, EntityMeta::$CREATE), 'yes') === 0) ? true : false;
```

**Po:**
```php
$createable = \App\Privilege::isPermitted($elementType, EntityMeta::$CREATE);
```

---

### Przykład 5: Szablon Smarty

**Przed:**
```smarty
{if isPermitted('Calendar', 'DetailView', $RELATION->getLinkedRecord()->getId()) eq 'yes'}
```

**Po:**
```smarty
{if \App\Privilege::isPermitted('Calendar', 'DetailView', $RELATION->getLinkedRecord()->getId())}
```

**Uwaga:** Wymaga sprawdzenia, czy Smarty ma dostęp do klas PHP. Może być potrzebna funkcja wrapper.

---

## 📊 Priorytetyzacja migracji

### 🔴 Wysoki priorytet (krytyczne miejsca)

1. **Web Services** (`VtigerModuleOperation.php`, `VtigerCRMObjectMeta.php`)
   - Używane przez API zewnętrzne
   - Wpływ na integracje

2. **Moduł Reports** (6 miejsc)
   - Często używany przez użytkowników
   - Wpływ na wydajność

3. **Moduł Calendar** (4 miejsca)
   - Często używany
   - Wpływ na UX

### 🟡 Średni priorytet

4. **Moduły CRM** (Accounts, OSSEmployees, IStorages, ModComments, ModTracker, ProjectTask, Documents)
   - Używane regularnie
   - Średni wpływ na wydajność

5. **Utilities** (`EmailTemplate.php`, `Utils.php`)
   - Używane w wielu miejscach
   - Wpływ na funkcjonalność

### 🟢 Niski priorytet

6. **Szablony Smarty**
   - Wymaga dodatkowej pracy (funkcja wrapper)
   - Mniejszy wpływ na wydajność

7. **Metody sharing w `UserInfoUtil`**
   - Wewnętrzne użycie
   - Można zrobić razem z refaktoringiem `UserInfoUtil::isPermitted()`

---

## ✅ Plan migracji

### Faza 1: Web Services i Reports (Priorytet 1-2)
- [ ] `src/Webservices/VtigerModuleOperation.php` (2 miejsca)
- [ ] `src/Webservices/VtigerCRMObjectMeta.php` (1 miejsce)
- [ ] `src/Modules/Reports/Models/Folder.php` (1 miejsce)
- [ ] `src/Modules/Reports/Models/Report.php` (1 miejsce)
- [ ] `src/Modules/Reports/Reports.php` (4 miejsca)

### Faza 2: Calendar i inne moduły (Priorytet 3-4)
- [ ] `src/Modules/Calendar/Actions/DragDropAjax.php` (2 miejsca)
- [ ] `src/Modules/Calendar/Actions/Calendar.php` (1 miejsce)
- [ ] `src/Modules/Calendar/Models/DetailView.php` (1 miejsce - 3 wywołania)
- [ ] `src/Modules/Reservations/Actions/Calendar.php` (1 miejsce)
- [ ] `src/Modules/OSSTimeControl/Actions/Calendar.php` (1 miejsce)

### Faza 3: Moduły CRM (Priorytet 4)
- [ ] `src/CRMEntity.php` (1 miejsce)
- [ ] `src/Modules/Accounts/Accounts.php` (1 miejsce)
- [ ] `src/Modules/OSSEmployees/OSSEmployees.php` (1 miejsce)
- [ ] `src/Modules/IStorages/IStorages.php` (1 miejsce)
- [ ] `src/Modules/ModComments/ModComments.php` (1 miejsce)
- [ ] `src/Modules/ModTracker/ModTracker.php` (1 miejsce)
- [ ] `src/Modules/ProjectTask/ProjectTask.php` (1 miejsce)
- [ ] `src/Modules/Documents/Documents.php` (1 miejsce)

### Faza 4: Utilities (Priorytet 5)
- [ ] `src/Utils/EmailTemplate.php` (1 miejsce)
- [ ] `src/Utils/Utils.php` (1 miejsce)

### Faza 5: Konwersje wewnętrzne (Priorytet 7)
- [ ] `src/Utils/UserInfoUtil.php` (1 miejsce - linia 263)
- [ ] `src/Modules/Users/Models/Privileges.php` (2 miejsca - linie 462, 499)

### Faza 6: Szablony Smarty (Priorytet 6)
- [ ] `layouts/basic/modules/Base/RecentActivities.tpl` (1 miejsce)
- [ ] `layouts/basic/modules/Base/dashboards/HistoryContents.tpl` (1 miejsce)
- [ ] Utworzyć funkcję wrapper dla Smarty

---

## 🧪 Testowanie

Po każdej fazie migracji należy:

1. **Testy jednostkowe** - Sprawdzić, czy zmienione metody działają poprawnie
2. **Testy integracyjne** - Sprawdzić funkcjonalność modułów
3. **Testy regresyjne** - Sprawdzić, czy nic się nie zepsuło
4. **Testy wydajnościowe** - Sprawdzić, czy nie ma degradacji wydajności

---

## 📝 Uwagi techniczne

### 1. Funkcja `isPermitted()` w szablonach Smarty

Obecnie w szablonach używana jest funkcja `isPermitted()`, która prawdopodobnie jest wrapperem. Należy sprawdzić:

- Gdzie jest zdefiniowana ta funkcja?
- Czy można ją zmienić, aby zwracała bool?
- Czy Smarty ma dostęp do klas PHP?

**Rozwiązanie:** Utworzyć funkcję wrapper w `src/Utils/SmartyHelpers.php`:

```php
function isPermitted($module, $action, $record = false) {
    return \App\Privilege::isPermitted($module, $action, $record);
}
```

### 2. Metody sharing w `UserInfoUtil`

Metody `isPermittedBySharing()`, `isReadPermittedBySharing()`, `isReadWritePermittedBySharing()` w `UserInfoUtil` zwracają `'yes'/'no'`, ale są używane tylko wewnętrznie. Można je zmienić na bool razem z refaktoringiem `UserInfoUtil::isPermitted()`.

### 3. Backward compatibility

Podczas migracji należy zachować backward compatibility:

1. Dodać deprecation warning do `UserInfoUtil::isPermitted()`
2. Dodać wrapper, który konwertuje bool → `'yes'/'no'` dla starych miejsc (tymczasowo)
3. Stopniowo migrować miejsca

---

## 🔗 Powiązane dokumenty

- `documentation/privilege-refactoring.md` - Plan refaktoringu systemu uprawnień
- `documentation/privileges.md` - Dokumentacja systemu uprawnień
- `documentation/PRIVILEGE_SYSTEM_ANALYSIS.md` - Analiza architektury systemu uprawnień

---

**Ostatnia aktualizacja:** 2025-01-27

