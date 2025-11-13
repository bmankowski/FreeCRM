# Plan refaktoryzacji Users/ListView - migracja z ListAjax do ListView

## Problem

`Users/ListView` dziedziczy z `Base\Views\ListAjax`, ale używa go w sposób niezgodny z przeznaczeniem:
- `ListAjax` jest przeznaczony tylko dla endpointów AJAX (getListViewCount, getRecordsCount, getPageCount)
- `Users/ListView` nadpisuje `process()` aby renderować pełny widok strony
- Ma pusty `preProcess()` z `ListAjax`, więc wszystkie przygotowania danych są w `process()`
- To jest niespójne z resztą architektury (RecycleBin, Reports, OSSPasswords używają `Base\Views\Index` lub `Base\Views\ListView`)

## Cel refaktoryzacji

Zmienić `Users/ListView` aby dziedziczył z `Base\Views\ListView` i był spójny z resztą architektury.

---

## Analiza obecnej implementacji

### Obecna struktura:
```php
Users/ListView extends Base/Views/ListAjax
├── checkPermission() - sprawdza admin
├── getFooterScripts() - dodaje Users.resources.List
├── process() - pełna logika przygotowania i renderowania (180 linii!)
├── preProcess() - pusty (return true)
├── postProcess() - pusty (return true)
├── getRecordsCount() - endpoint AJAX
├── getListViewCount() - endpoint AJAX
└── getPageCount() - endpoint AJAX
```

### Specjalne wymagania Users/ListView:
1. **Status filtering** - domyślnie 'Active' (linia 65-67)
2. **Users/Models/ListView** - używa własnego modelu zamiast Base/Models/ListView (linia 70)
3. **QUALIFIED_MODULE** - używa `$request->getModule(false)` w view() (linia 180)
4. **ALPHABET_VALUE** - warunkowe przypisanie tylko jeśli searchKey != 'status' (linia 97-98)
5. **LISTVIEW_LINKS** - specjalna walidacja struktury (linia 136-145)
6. **AJAX endpoints** - getListViewCount, getRecordsCount, getPageCount są używane przez JavaScript

---

## Plan migracji

### KROK 1: Zmiana dziedziczenia

**Plik:** `src/Modules/Users/Views/ListView.php`

**Zmiana:**
```php
// PRZED:
class ListView extends \App\Modules\Base\Views\ListAjax

// PO:
class ListView extends \App\Modules\Base\Views\ListView
```

**Uzasadnienie:** `Base\Views\ListView` obsługuje zarówno pełne strony jak i AJAX.

---

### KROK 2: Dodanie exposeMethod dla endpointów AJAX

**Plik:** `src/Modules/Users/Views/ListView.php`

**Dodaj w konstruktorze:**
```php
public function __construct()
{
    parent::__construct();
    $this->exposeMethod('getListViewCount');
    $this->exposeMethod('getRecordsCount');
    $this->exposeMethod('getPageCount');
}
```

**Uzasadnienie:** `Base\Views\ListView` nie ma automatycznego exposeMethod dla tych metod, trzeba je dodać ręcznie.

---

### KROK 3: Implementacja preProcess()

**Plik:** `src/Modules/Users/Views/ListView.php`

**Zastąp pusty preProcess() nową implementacją:**

```php
public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
{
    parent::preProcess($request, false);
    
    if ($request->isAjax()) {
        // AJAX requests - przygotuj dane dla ListViewContents
        $this->prepareAjaxListViewData($request);
        return;
    }
    
    // Non-AJAX requests - pełne przygotowanie danych
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();
    
    // Inicjalizuj listViewModel z Users/Models/ListView
    $cvId = $request->get('viewname');
    if (empty($cvId)) {
        $cvId = \App\CustomView::getInstance($moduleName)->getViewId();
    }
    $this->viewName = $cvId;
    $this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $cvId);
    
    // Status filtering - specjalne dla Users
    $status = $request->get('status');
    if (empty($status)) {
        $status = 'Active';
    }
    $this->listViewModel->set('status', $status);
    
    // Inicjalizuj zawartość listy
    $this->initializeListViewContents($request, $viewer);
    
    // Dodatkowe przypisania specyficzne dla Users
    $linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $cvId);
    $viewer->assign('HEADER_LINKS', $this->listViewModel->getHederLinks($linkParams));
    $viewer->assign('VIEWID', $this->viewName);
    $viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
    
    // CUSTOM_VIEWS - Users może nie mieć custom views, ale Base\Views\ListView tego oczekuje
    // Sprawdź czy Users ma custom views, jeśli nie - przypisz pustą tablicę
    try {
        $customViews = \App\Modules\CustomView\Models\Record::getAllByGroup($moduleName);
        $viewer->assign('CUSTOM_VIEWS', $customViews);
    } catch (\Exception $e) {
        $viewer->assign('CUSTOM_VIEWS', []);
    }
}
```

**Uwagi:**
- Używa `Users/Models/ListView` zamiast `Base/Models/ListView`
- Dodaje status filtering ('Active' domyślnie)
- Obsługuje zarówno AJAX jak i non-AJAX

---

### KROK 4: Nadpisanie initializeListViewContents()

**Plik:** `src/Modules/Users/Views/ListView.php`

**Dodaj metodę nadpisującą Base\Views\ListView::initializeListViewContents():**

```php
public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
{
    $moduleName = $request->getModule();
    $pageNumber = $request->get('page');
    $orderBy = $request->get('orderby');
    $sortOrder = $request->get('sortorder');
    $searchResult = $request->get('searchResult');
    
    // Użyj Users/Models/ListView jeśli nie został jeszcze zainicjalizowany
    if (!$this->listViewModel) {
        $cvId = $request->get('viewname');
        if (empty($cvId)) {
            $cvId = \App\CustomView::getInstance($moduleName)->getViewId();
        }
        $this->viewName = $cvId;
        $this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $cvId);
    }
    
    // Status filtering - specjalne dla Users
    $status = $request->get('status');
    if (empty($status)) {
        $status = 'Active';
    }
    $this->listViewModel->set('status', $status);
    
    // Reszta logiki z Base\Views\ListView::initializeListViewContents()
    // ale z modyfikacjami dla Users:
    
    if (empty($orderBy) && empty($sortOrder)) {
        $orderBy = \App\CustomView::getSortby($moduleName);
        $sortOrder = \App\CustomView::getSorder($moduleName);
        if (empty($orderBy)) {
            $moduleInstance = \App\CRMEntity::getInstance($moduleName);
            $orderBy = $moduleInstance->default_order_by;
            $sortOrder = $moduleInstance->default_sort_order;
        }
    }
    
    if ($sortOrder === 'ASC') {
        $nextSortOrder = 'DESC';
        $sortImage = 'glyphicon glyphicon-chevron-down';
    } else {
        $nextSortOrder = 'ASC';
        $sortImage = 'glyphicon glyphicon-chevron-up';
    }
    
    if (empty($pageNumber)) {
        $pageNumber = \App\CustomView::getCurrentPage($moduleName, $this->viewName);
        if (empty($pageNumber)) {
            $pageNumber = '1';
        }
    }
    
    if (!empty($searchResult) && is_array($searchResult)) {
        $this->listViewModel->set('searchResult', $searchResult);
    }
    
    $currentUser = $request->getUser();
    $cvId = $this->viewName ?? \App\CustomView::getInstance($moduleName)->getViewId();
    $linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $cvId);
    $linkModels = $this->listViewModel->getListViewMassActions($linkParams);
    
    // Ensure LISTVIEWMASSACTION is always an array
    if (!isset($linkModels['LISTVIEWMASSACTION'])) {
        $linkModels['LISTVIEWMASSACTION'] = [];
    }
    
    $pagingModel = new \App\Modules\Base\Models\Paging();
    $pagingModel->set('page', $pageNumber);
    $pagingModel->set('viewid', $cvId);
    
    if (!empty($orderBy)) {
        $this->listViewModel->set('orderby', $orderBy);
        $this->listViewModel->set('sortorder', $sortOrder);
    }
    
    $searchKey = $request->get('search_key');
    $searchValue = $request->get('search_value');
    $operator = $request->get('operator');
    if (!empty($operator)) {
        $this->listViewModel->set('operator', $operator);
    }
    $viewer->assign('OPERATOR', $operator);
    
    // Specjalna logika dla Users - ALPHABET_VALUE tylko jeśli searchKey != 'status'
    if ('status' != $searchKey) {
        $viewer->assign('ALPHABET_VALUE', $searchValue);
    }
    
    if (!empty($searchKey) && !empty($searchValue)) {
        $this->listViewModel->set('search_key', $searchKey);
        $this->listViewModel->set('search_value', $searchValue);
    }
    
    $searchParams = $request->get('search_params');
    if (!empty($searchParams) && is_array($searchParams)) {
        $transformedSearchParams = $this->listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
        $this->listViewModel->set('search_params', $transformedSearchParams);
        //To make smarty to get the details easily accesible
        foreach ($searchParams as $fieldListGroup) {
            foreach ($fieldListGroup as $fieldSearchInfo) {
                $fieldSearchInfo['searchValue'] = isset($fieldSearchInfo[2]) ? $fieldSearchInfo[2] : '';
                $fieldSearchInfo['fieldName'] = $fieldName = isset($fieldSearchInfo[0]) ? $fieldSearchInfo[0] : '';
                $fieldSearchInfo['specialOption'] = isset($fieldSearchInfo[3]) ? $fieldSearchInfo[3] : '';
                $searchParams[$fieldName] = $fieldSearchInfo;
            }
        }
    } else {
        $searchParams = [];
    }
    
    if (!$this->listViewHeaders) {
        $this->listViewHeaders = $this->listViewModel->getListViewHeaders();
    }
    if (!$this->listViewEntries) {
        $this->listViewEntries = $this->listViewModel->getListViewEntries($pagingModel);
    }
    $noOfEntries = count($this->listViewEntries);
    
    $viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
    $viewer->assign('MODULE', $moduleName);
    
    if (!isset($this->listViewLinks)) {
        $this->listViewLinks = $this->listViewModel->getListViewLinks($linkParams);
    }
    
    // Ensure LISTVIEW_LINKS is always an array with required keys (specjalne dla Users)
    if (!is_array($this->listViewLinks)) {
        $this->listViewLinks = [];
    }
    if (!isset($this->listViewLinks['LISTVIEW'])) {
        $this->listViewLinks['LISTVIEW'] = [];
    }
    if (!isset($this->listViewLinks['LISTVIEWBASIC'])) {
        $this->listViewLinks['LISTVIEWBASIC'] = [];
    }
    
    $viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);
    $viewer->assign('LISTVIEW_MASSACTIONS', $linkModels['LISTVIEWMASSACTION']);
    $viewer->assign('PAGING_MODEL', $pagingModel);
    $viewer->assign('PAGE_NUMBER', $pageNumber);
    $viewer->assign('ORDER_BY', $orderBy);
    $viewer->assign('SORT_ORDER', $sortOrder);
    $viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
    $viewer->assign('SORT_IMAGE', $sortImage);
    $viewer->assign('COLUMN_NAME', $orderBy);
    $viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
    $viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
    $viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
    
    $totalCount = false;
    if (\App\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')) {
        if (!$this->listViewCount) {
            $this->listViewCount = $this->listViewModel->getListViewCount();
        }
        $pagingModel->set('totalCount', (int) $this->listViewCount);
        $totalCount = (int) $this->listViewCount;
    }
    $viewer->assign('LISTVIEW_COUNT', $totalCount);
    $viewer->assign('PAGE_COUNT', $pagingModel->getPageCount());
    $viewer->assign('START_PAGIN_FROM', $pagingModel->getStartPagingFrom());
    $viewer->assign('LIST_VIEW_MODEL', $this->listViewModel);
    $viewer->assign('IS_MODULE_EDITABLE', $this->listViewModel->getModule()->isPermitted('EditView'));
    $viewer->assign('IS_MODULE_DELETABLE', $this->listViewModel->getModule()->isPermitted('Delete'));
    $viewer->assign('SEARCH_DETAILS', $searchParams);
    
    // Users-specific assignments
    $viewer->assign('QUALIFIED_MODULE', $moduleName);
    $viewer->assign('USER_MODEL', $request->getUser());
    
    // Dodaj LIST_MAX_ENTRIES_MASS_EDIT dla refaktoryzacji vglobal
    $viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
}
```

**Uwagi:**
- Używa `Users/Models/ListView` zamiast `Base/Models/ListView`
- Zachowuje specjalną logikę dla Users (status, ALPHABET_VALUE, LISTVIEW_LINKS validation)
- Dodaje `LIST_MAX_ENTRIES_MASS_EDIT` dla refaktoryzacji vglobal

---

### KROK 5: Uproszczenie process()

**Plik:** `src/Modules/Users/Views/ListView.php`

**Zastąp obecny process() prostszą wersją:**

```php
public function process(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();
    
    if ($request->isAjax()) {
        // AJAX requests - sprawdź czy to endpoint (mode) czy renderowanie ListViewContents
        $mode = $request->get('mode');
        if (!empty($mode)) {
            // To jest endpoint AJAX (getListViewCount, getRecordsCount, getPageCount)
            $this->invokeExposedMethod($mode, $request);
            return;
        }
        
        // To jest renderowanie ListViewContents przez AJAX
        // Dane już przygotowane w preProcess()->prepareAjaxListViewData()
        $viewer->view('ListViewContents.tpl', $moduleName);
    } else {
        // Non-AJAX requests - pełna strona
        // Dane już przygotowane w preProcess()
        $viewer->view('ListView.tpl', $request->getModule(false));
    }
}
```

**Uzasadnienie:** 
- Dane są przygotowywane w `preProcess()`, więc `process()` tylko renderuje
- Obsługuje zarówno endpointy AJAX (mode) jak i renderowanie przez AJAX
- Używa `QUALIFIED_MODULE` dla ListView.tpl (jak w oryginalnej implementacji)

---

### KROK 6: Nadpisanie prepareAjaxListViewData()

**Plik:** `src/Modules/Users/Views/ListView.php`

**Dodaj metodę dla AJAX requests:**

```php
protected function prepareAjaxListViewData(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();
    
    // Inicjalizuj listViewModel jeśli jeszcze nie został
    if (!isset($this->viewName)) {
        $cvId = $request->get('viewname');
        if (empty($cvId)) {
            $cvId = \App\CustomView::getInstance($moduleName)->getViewId();
        }
        $this->viewName = $cvId;
    }
    
    if (!$this->listViewModel) {
        $this->listViewModel = \App\Modules\Users\Models\ListView::getInstance($moduleName, $this->viewName);
        
        // Status filtering - specjalne dla Users
        $status = $request->get('status');
        if (empty($status)) {
            $status = 'Active';
        }
        $this->listViewModel->set('status', $status);
    }
    
    // Użyj initializeListViewContents() z nadpisaniem dla Users
    $this->initializeListViewContents($request, $viewer);
    
    // Dodatkowe przypisania dla AJAX
    $viewer->assign('USER_MODEL', $request->getUser());
    $viewer->assign('MODULE_NAME', $moduleName);
    $viewer->assign('MODULE_MODEL', $this->listViewModel->getModule());
    $viewer->assign('VIEWID', $this->viewName);
}
```

---

### KROK 7: Zachowanie endpointów AJAX

**Plik:** `src/Modules/Users/Views/ListView.php`

**Zachowaj metody getListViewCount(), getRecordsCount(), getPageCount() bez zmian** - są używane przez JavaScript.

**Uwaga:** Te metody mogą wymagać drobnych modyfikacji jeśli używały właściwości z `process()`, ale powinny działać bez zmian.

---

### KROK 8: Usunięcie pustych metod

**Plik:** `src/Modules/Users/Views/ListView.php`

**Usuń:**
```php
public function preProcess(\App\Http\Vtiger_Request $request, $display = true){
    return true;
}
public function postProcess(\App\Http\Vtiger_Request $request, $display = true){
    return true;
}
```

**Uzasadnienie:** Zastąpione przez prawdziwe implementacje.

---

## Podsumowanie zmian

### Plik: `src/Modules/Users/Views/ListView.php`

**Zmiany:**
1. ✅ Zmiana dziedziczenia: `ListAjax` → `ListView`
2. ✅ Dodanie `__construct()` z `exposeMethod()`
3. ✅ Implementacja `preProcess()` z obsługą AJAX i non-AJAX
4. ✅ Nadpisanie `initializeListViewContents()` z logiką Users
5. ✅ Uproszczenie `process()` - tylko renderowanie
6. ✅ Dodanie `prepareAjaxListViewData()` dla AJAX
7. ✅ Zachowanie endpointów AJAX (getListViewCount, getRecordsCount, getPageCount)
8. ✅ Usunięcie pustych metod preProcess/postProcess

**Zachowane funkcjonalności:**
- ✅ Status filtering ('Active' domyślnie)
- ✅ Users/Models/ListView
- ✅ QUALIFIED_MODULE w view()
- ✅ Specjalna logika ALPHABET_VALUE
- ✅ Walidacja LISTVIEW_LINKS
- ✅ Endpointy AJAX
- ✅ checkPermission()
- ✅ getFooterScripts()

---

## Testowanie

Po refaktoryzacji należy przetestować:

1. **Pełna strona ListView** - czy renderuje się poprawnie
2. **AJAX ListViewContents** - czy działa paginacja przez AJAX
3. **Endpointy AJAX** - getListViewCount, getRecordsCount, getPageCount
4. **Status filtering** - czy domyślnie pokazuje 'Active'
5. **Search** - czy wyszukiwanie działa poprawnie
6. **Sortowanie** - czy sortowanie działa
7. **Mass actions** - czy akcje masowe działają
8. **Permissions** - czy checkPermission działa

---

## Korzyści z refaktoryzacji

1. ✅ **Spójność** - zgodność z resztą architektury
2. ✅ **Separacja odpowiedzialności** - preProcess przygotowuje, process renderuje
3. ✅ **Obsługa AJAX** - automatyczna przez Base\Views\ListView
4. ✅ **Łatwiejsze utrzymanie** - zgodność z wzorcem MVC
5. ✅ **Refaktoryzacja vglobal** - łatwiejsze dodanie LIST_MAX_ENTRIES_MASS_EDIT w initializeListViewContents()

---

## Uwagi końcowe

- Refaktoryzacja powinna być wykonana **przed** dodaniem `LIST_MAX_ENTRIES_MASS_EDIT` w planie vglobal
- Po refaktoryzacji `LIST_MAX_ENTRIES_MASS_EDIT` można dodać w `initializeListViewContents()` zamiast w `process()`
- Wszystkie zmiany są backward compatible - zachowują istniejącą funkcjonalność

