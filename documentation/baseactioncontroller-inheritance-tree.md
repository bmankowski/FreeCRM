# Struktura Dziedziczenia BaseActionController

Dokumentacja przedstawiająca pełną hierarchię dziedziczenia wszystkich klas potomnych po `BaseActionController` w projekcie FreeCRM.

**Data utworzenia:** 2024  
**Ostatnia aktualizacja:** 2024

---

## Przegląd Hierarchii

```
BaseActionController (abstract)
├── [Klasy bezpośrednio dziedziczące] (~150+ klas)
├── BaseViewController (abstract)
│   ├── [Klasy bezpośrednio dziedziczące] (~10 klas)
│   └── Basic (abstract)
│       ├── Index
│       │   └── [Wszystkie widoki modułów] (~100+ klas)
│       ├── Settings\Base\Views\Index
│       │   └── [Wszystkie widoki Settings]
│       └── Settings\Roles\Views\Popup
└── Mass (abstract)
    └── [Klasy akcji masowych] (4 klasy)
```

---

## 1. BaseActionController (Klasa Bazowa)

**Ścieżka:** `src/Base/Controllers/BaseActionController.php`  
**Namespace:** `App\Base\Controllers`  
**Typ:** Abstract class

### Opis
Abstrakcyjna klasa bazowa dla wszystkich kontrolerów akcji i widoków w FreeCRM. Definiuje podstawowe metody i mechanizmy obsługi żądań HTTP.

### Kluczowe metody:
- `process()` - abstrakcyjna metoda wymagana do implementacji
- `checkPermission()` - sprawdzanie uprawnień
- `validateRequest()` - walidacja żądania
- `preProcess()` / `postProcess()` - przetwarzanie przed/po akcji
- `exposeMethod()` / `invokeExposedMethod()` - mechanizm ekspozycji metod

---

## 2. Klasy Bezpośrednio Dziedziczące po BaseActionController

### 2.1. BaseViewController (Abstract)

**Ścieżka:** `src/Base/Controllers/BaseViewController.php`  
**Namespace:** `App\Base\Controllers`  
**Typ:** Abstract class

Kontroler bazowy dla wszystkich widoków. Rozszerza funkcjonalność BaseActionController o zarządzanie widokami, breadcrumbs, skryptami CSS/JS.

**Dzieci:**
- `Basic` (abstract) - patrz sekcja 3.1
- `Header` (abstract) - patrz sekcja 3.2
- `VariablePanel` - patrz sekcja 3.3
- Inne klasy bezpośrednie - patrz sekcja 3.4

---

### 2.2. Mass (Abstract)

**Ścieżka:** `src/Modules/Base/Actions/Mass.php`  
**Namespace:** `App\Modules\Base\Actions`  
**Typ:** Abstract class

Klasa bazowa dla akcji masowych (operacje na wielu rekordach jednocześnie).

**Dzieci:**
- `Settings\Mail\Actions\MassSend`
- `Settings\Mail\Actions\MassDelete`
- `Settings\Mail\Actions\DownloadAttachment`
- `Settings\Mail\Actions\MassAccept`

---

### 2.3. Akcje Bezpośrednie (Action Classes)

Wszystkie klasy akcji, które bezpośrednio dziedziczą po `BaseActionController`:

#### Moduł: Base
- `App\Modules\Base\Actions\Delete`
- `App\Modules\Base\Actions\DeleteAjax`
- `App\Modules\Base\Actions\DeleteImage`
- `App\Modules\Base\Actions\EditFieldByModal`
- `App\Modules\Base\Actions\ExportData`
- `App\Modules\Base\Actions\Fields`
- `App\Modules\Base\Actions\GenerateRecords`
- `App\Modules\Base\Actions\Inventory`
- `App\Modules\Base\Actions\Mail`
- `App\Modules\Base\Actions\NoteBook`
- `App\Modules\Base\Actions\PDF`
- `App\Modules\Base\Actions\Pagination`
- `App\Modules\Base\Actions\ProcessDuplicates`
- `App\Modules\Base\Actions\QuickExport`
- `App\Modules\Base\Actions\RelatedCommentModal`
- `App\Modules\Base\Actions\RelationAjax`
- `App\Modules\Base\Actions\Save`
- `App\Modules\Base\Actions\SetReadRecord`
- `App\Modules\Base\Actions\TransferOwnership`
- `App\Modules\Base\Actions\UpdateField`
- `App\Modules\Base\Actions\Watchdog`
- `App\Modules\Base\Actions\Workflow`

#### Moduł: Accounts
- `App\Modules\Accounts\Actions\RelationAjax`

#### Moduł: Calendar
- `App\Modules\Calendar\Actions\ActivityReminder`
- `App\Modules\Calendar\Actions\ActivityStateAjax`
- `App\Modules\Calendar\Actions\Calendar`
- `App\Modules\Calendar\Actions\CalendarUserActions`
- `App\Modules\Calendar\Actions\Delete`
- `App\Modules\Calendar\Actions\DeleteAjax`
- `App\Modules\Calendar\Actions\DragDropAjax`
- `App\Modules\Calendar\Actions\Feed`
- `App\Modules\Calendar\Actions\GetFreeTime`
- `App\Modules\Calendar\Actions\ImportICS`
- `App\Modules\Calendar\Actions\Invitees`
- `App\Modules\Calendar\Actions\Save`

#### Moduł: Campaigns
- `App\Modules\Campaigns\Actions\DetailAjax`
- `App\Modules\Campaigns\Actions\RelationAjax`

#### Moduł: CustomView
- `App\Modules\CustomView\Actions\Approve`
- `App\Modules\CustomView\Actions\Delete`
- `App\Modules\CustomView\Actions\DeleteAjax`
- `App\Modules\CustomView\Actions\Deny`
- `App\Modules\CustomView\Actions\Save`

#### Moduł: Documents
- `App\Modules\Documents\Actions\CheckFileIntegrity`
- `App\Modules\Documents\Actions\DownloadFile`
- `App\Modules\Documents\Actions\MoveDocuments`

#### Moduł: Events
- `App\Modules\Events\Actions\Save`

#### Moduł: HelpDesk
- `App\Modules\HelpDesk\Actions\ConvertFAQ`

#### Moduł: IStorages
- `App\Modules\IStorages\Actions\RelationAjax`

#### Moduł: Import
- `App\Modules\Import\Actions\Data`
- `App\Modules\Import\Actions\Lock`
- `App\Modules\Import\Actions\Queue`

#### Moduł: KnowledgeBase
- `App\Modules\KnowledgeBase\Actions\DataTreeAjax`
- `App\Modules\KnowledgeBase\Actions\ImageUploadAjax`

#### Moduł: ModComments
- `App\Modules\ModComments\Actions\Save`

#### Moduł: ModTracker
- `App\Modules\ModTracker\Actions\ChangesReviewedOn`
- `App\Modules\ModTracker\Actions\LastRelation`

#### Moduł: Notification
- `App\Modules\Notification\Actions\Notification`

#### Moduł: OSSEmployees
- `App\Modules\OSSEmployees\Actions\GetHoliday`
- `App\Modules\OSSEmployees\Actions\UniqueUser`

#### Moduł: OSSMail
- `App\Modules\OSSMail\Actions\ImportMail`
- `App\Modules\OSSMail\Actions\SaveRcConfig`
- `App\Modules\OSSMail\Actions\SetUser`
- `App\Modules\OSSMail\Actions\checkMails`
- `App\Modules\OSSMail\Actions\executeActions`
- `App\Modules\OSSMail\Actions\getContactMail`

#### Moduł: OSSMailScanner
- `App\Modules\OSSMailScanner\Actions\AccontRemove`
- `App\Modules\OSSMailScanner\Actions\GetLog`
- `App\Modules\OSSMailScanner\Actions\IdentitiesDel`
- `App\Modules\OSSMailScanner\Actions\SaveActions`
- `App\Modules\OSSMailScanner\Actions\SaveCRMuser`
- `App\Modules\OSSMailScanner\Actions\SaveRcConfig`
- `App\Modules\OSSMailScanner\Actions\SaveWidgetConfig`
- `App\Modules\OSSMailScanner\Actions\cron`
- `App\Modules\OSSMailScanner\Actions\getConfig`
- `App\Modules\OSSMailScanner\Actions\restartCron`
- `App\Modules\OSSMailScanner\Actions\saveEmailSearchList`

#### Moduł: OSSMailView
- `App\Modules\OSSMailView\Actions\BindMails`
- `App\Modules\OSSMailView\Actions\ChangeType`
- `App\Modules\OSSMailView\Actions\Relation`

#### Moduł: OSSPasswords
- `App\Modules\OSSPasswords\Actions\CheckPass`
- `App\Modules\OSSPasswords\Actions\GetPass`
- `App\Modules\OSSPasswords\Actions\Save`

#### Moduł: OSSTimeControl
- `App\Modules\OSSTimeControl\Actions\Calendar`
- `App\Modules\OSSTimeControl\Actions\GetTCInfo`

#### Moduł: OpenStreetMap
- `App\Modules\OpenStreetMap\Actions\ClipBoard`
- `App\Modules\OpenStreetMap\Actions\GetMarkers`
- `App\Modules\OpenStreetMap\Actions\GetRoute`

#### Moduł: PBXManager
- `App\Modules\PBXManager\Actions\IncomingCallPoll`
- `App\Modules\PBXManager\Actions\OutgoingCall`

#### Moduł: PaymentsIn
- `App\Modules\PaymentsIn\Actions\GenerateRecords`

#### Moduł: PaymentsOut
- `App\Modules\PaymentsOut\Actions\GenerateRecords`

#### Moduł: Portal
- `App\Modules\Portal\Actions\DeleteAjax`

#### Moduł: PriceBooks
- `App\Modules\PriceBooks\Actions\ProductListPrice`
- `App\Modules\PriceBooks\Actions\RelationAjax`
- `App\Modules\PriceBooks\Actions\Save`

#### Moduł: Products
- `App\Modules\Products\Actions\RelationAjax`
- `App\Modules\Products\Actions\SubProducts`

#### Moduł: RecycleBin
- `App\Modules\RecycleBin\Actions\EmptyRecordBin`
- `App\Modules\RecycleBin\Actions\RecycleBinAjax`

#### Moduł: Reports
- `App\Modules\Reports\Actions\ChartSave`
- `App\Modules\Reports\Actions\CheckDuplicate`
- `App\Modules\Reports\Actions\DeleteAjax`
- `App\Modules\Reports\Actions\DetailAjax`
- `App\Modules\Reports\Actions\Folder`
- `App\Modules\Reports\Actions\MoveReports`
- `App\Modules\Reports\Actions\Save`

#### Moduł: Reservations
- `App\Modules\Reservations\Actions\Calendar`

#### Moduł: Rss
- `App\Modules\Rss\Actions\DeleteAjax`
- `App\Modules\Rss\Actions\GetHtml`
- `App\Modules\Rss\Actions\MakeDefaultAjax`
- `App\Modules\Rss\Actions\Save`

#### Moduł: SCalculations
- `App\Modules\SCalculations\Actions\EditFieldByModal`

#### Moduł: SMSNotifier
- `App\Modules\SMSNotifier\Actions\CheckServerInfo`

#### Moduł: SQuoteEnquiries
- `App\Modules\SQuoteEnquiries\Actions\EditFieldByModal`

#### Moduł: SQuotes
- `App\Modules\SQuotes\Actions\EditFieldByModal`

#### Moduł: SRecurringOrders
- `App\Modules\SRecurringOrders\Actions\EditFieldByModal`

#### Moduł: SRequirementsCards
- `App\Modules\SRequirementsCards\Actions\EditFieldByModal`

#### Moduł: SSalesProcesses
- `App\Modules\SSalesProcesses\Actions\RelationAjax`

#### Moduł: SSingleOrders
- `App\Modules\SSingleOrders\Actions\EditFieldByModal`

#### Moduł: SVendorEnquiries
- `App\Modules\SVendorEnquiries\Actions\EditFieldByModal`

#### Moduł: Services
- `App\Modules\Services\Actions\RelationAjax`

#### Moduł: Settings
- `App\Modules\Settings\CurrencyUpdate\Actions\GetBankCurrencies`
- `App\Modules\Settings\CurrencyUpdate\Actions\SaveActiveBank`
- `App\Modules\Settings\Profiles\Actions\Save`
- `App\Modules\Settings\Roles\Actions\Save`
- `App\Modules\Settings\TreesManager\Actions\Save`
- `App\Modules\Settings\Users\Actions\Logout`

#### Moduł: Users
- `App\Modules\Users\Actions\CheckUserEmail`
- `App\Modules\Users\Actions\CheckUserPass`
- `App\Modules\Users\Actions\DeleteAjax`
- `App\Modules\Users\Actions\DeleteImage`
- `App\Modules\Users\Actions\ExportData`
- `App\Modules\Users\Actions\ListAjax`
- `App\Modules\Users\Actions\Login`
- `App\Modules\Users\Actions\Logout`
- `App\Modules\Users\Actions\SwitchUsers`
- `App\Modules\Users\Actions\UserSetupSave`

---

## 3. Hierarchia BaseViewController

### 3.1. Basic (Abstract)

**Ścieżka:** `src/Modules/Base/Views/Basic.php`  
**Namespace:** `App\Modules\Base\Views`  
**Typ:** Abstract class

Klasa bazowa dla wszystkich widoków podstawowych. Rozszerza BaseViewController o dodatkową funkcjonalność widoków.

#### Cel i Rola Klasy Basic

Klasa `Basic` jest warstwą pośrednią między `BaseViewController` a `Index`, która dodaje funkcjonalność specyficzną dla widoków modułów biznesowych. Pełni następujące funkcje:

**1. Zarządzanie Menu Nagłówka (`getMenuHeaderLinks()`)**
   - Linki w nagłówku: Switch Users, My Preferences, Sign Out
   - Link do System Settings dla administratorów
   - Obsługa przełączania między użytkownikami
   - Menu użytkownika z dropdown (preferencje, wylogowanie)

**2. Przygotowanie Widoku (`preProcess()`)**
   - Ładowanie menu nawigacyjnego modułów (`getMenu()`)
   - Przypisanie logo firmy (`COMPANY_LOGO`)
   - Przypisanie aktualnej daty (`CURRENTDATE`)
   - Konfiguracja modułów wyszukiwania globalnego (`SEARCHABLE_MODULES`)
   - Informacje o aktywności modułów (Calendar reminders, Chat)
   - Przygotowanie wszystkich zmiennych potrzebnych do renderowania widoku

**3. Zarządzanie Skryptami JavaScript (`getFooterScripts()`)**
   - Dodaje skrypty specyficzne dla modułów biznesowych:
     - `Edit` - edycja rekordów
     - `Popup` - okna popup do wyboru rekordów
     - `Field` - zarządzanie polami formularzy
     - `validator` - walidacja formularzy
     - `BasicSearch`, `AdvanceFilter`, `AdvanceSearch` - systemy wyszukiwania
   - Ładuje skrypty z linków HEADERSCRIPT
   - Datepicker, timepicker, inputmask i inne biblioteki UI

**4. Zarządzanie Arkuszami Stylów (`getHeaderCss()`)**
   - Dodaje style CSS specyficzne dla modułów
   - Ładuje style z linków HEADERCSS
   - Zarządza stylami motywu

**5. Funkcje Pomocnicze**
   - `checkFileUriInRelocatedMouldesFolder()` - sprawdzanie istnienia plików w relokowanych folderach modułów (kompatybilność wsteczna)

#### Dlaczego Basic jest potrzebna?

- **Separacja odpowiedzialności**: `BaseViewController` jest ogólny dla wszystkich widoków (w tym Settings, Header, VariablePanel), podczas gdy `Basic` dodaje funkcjonalność specyficzną dla modułów biznesowych
- **Reużywalność**: Wszystkie widoki modułów biznesowych dziedziczą po `Basic` i automatycznie otrzymują menu, linki nagłówka, skrypty i style
- **Elastyczność**: Widoki specjalne (jak `Header`, `VariablePanel`) mogą dziedziczyć bezpośrednio po `BaseViewController`, omijając funkcjonalność specyficzną dla modułów
- **Organizacja kodu**: Centralizuje logikę wspólną dla widoków modułów biznesowych w jednym miejscu

**Dzieci:**
- `Index` - patrz sekcja 3.1.1
- `Settings\Base\Views\Index` - patrz sekcja 3.1.2
- `Settings\Roles\Views\Popup` - patrz sekcja 3.1.3

#### 3.1.1. Index

**Ścieżka:** `src/Modules/Base/Views/Index.php`  
**Namespace:** `App\Modules\Base\Views`

Główna klasa widoków dla wszystkich modułów. Wszystkie widoki modułów dziedziczą po tej klasie.

**Główne klasy potomne:**
- `Detail` - widok szczegółów rekordu
- `Edit` - widok edycji rekordu
- `ListView` - widok listy rekordów
- `Popup` - widok popup do wyboru rekordów
- `BasicModal` - widok modala podstawowego
- `IndexAjax` - widok AJAX
- `ListAjax` - widok listy AJAX
- `Export` - widok eksportu
- `Import` - widok importu
- `PDF` - widok PDF
- `FindDuplicates` - widok znajdowania duplikatów
- `MergeRecord` - widok scalania rekordów
- `Inventory` - widok inwentarza
- `DashBoard` - widok dashboardu
- I wiele innych...

**Wszystkie moduły mają swoje własne klasy widoków dziedziczące po Index:**
- `App\Modules\{ModuleName}\Views\Detail`
- `App\Modules\{ModuleName}\Views\Edit`
- `App\Modules\{ModuleName}\Views\ListView`
- `App\Modules\{ModuleName}\Views\Popup`
- itd.

#### 3.1.2. Settings\Base\Views\Index

**Ścieżka:** `src/Modules/Settings/Base/Views/Index.php`  
**Namespace:** `App\Modules\Settings\Base\Views`

Klasa bazowa dla wszystkich widoków w sekcji Settings.

**Dzieci:**
- Wszystkie widoki modułów Settings dziedziczą po tej klasie

#### 3.1.3. Settings\Roles\Views\Popup

**Ścieżka:** `src/Modules/Settings/Roles/Views/Popup.php`  
**Namespace:** `App\Modules\Settings\Roles\Views`

Widok popup do wyboru ról w sekcji Settings. Dziedziczy bezpośrednio po `Basic`, ponieważ potrzebuje funkcjonalności modułów biznesowych (menu nagłówka, skrypty, style), ale jest częścią sekcji Settings.

**Charakterystyka:**
- Dziedziczy po `Basic` (nie po `Settings\Base\Views\Index`)
- Nadpisuje `showBodyHeader()` zwracając `false` (brak nagłówka strony)
- Używa funkcjonalności z `Basic` (getFooterScripts, getMenuHeaderLinks)

---

### 3.2. Header (Abstract)

**Ścieżka:** `src/Modules/Base/Views/Header.php`  
**Namespace:** `App\Modules\Base\Views`  
**Typ:** Abstract class

Klasa odpowiedzialna za renderowanie nagłówka strony.

**Dzieci:**
- `Footer` - patrz sekcja 3.2.1

#### 3.2.1. Footer

**Ścieżka:** `src/Modules/Base/Views/Footer.php`  
**Namespace:** `App\Modules\Base\Views`

Klasa odpowiedzialna za renderowanie stopki strony. Dziedziczy po Header, który dziedziczy po BaseViewController.

---

### 3.3. VariablePanel

**Ścieżka:** `src/Modules/Base/Views/VariablePanel.php`  
**Namespace:** `App\Modules\Base\Views`

Widok panelu zmiennych.

---

### 3.4. Inne Klasy Bezpośrednio Dziedziczące po BaseViewController

- `App\Modules\OSSEmployees\Views\EmployeeHierarchy`
- `App\Modules\IStorages\Views\Hierarchy`
- `App\Modules\Import\Views\Main`
- `App\Modules\Reports\Views\ExportReport`
- `App\Modules\Leads\Views\SaveConvertLead`
- `App\Modules\Accounts\Views\AccountHierarchy`
- `App\Modules\Users\Views\Login`
- `App\Modules\SSalesProcesses\Views\Hierarchy`

---

## 4. Hierarchia Mass

### 4.1. Klasy Dziedziczące po Mass

Wszystkie klasy akcji masowych w module Settings\Mail:

- `App\Modules\Settings\Mail\Actions\MassSend`
- `App\Modules\Settings\Mail\Actions\MassDelete`
- `App\Modules\Settings\Mail\Actions\DownloadAttachment`
- `App\Modules\Settings\Mail\Actions\MassAccept`

---

## 5. Statystyki

### Liczba klas według poziomu dziedziczenia:

- **Poziom 1** (bezpośrednio po BaseActionController): ~150+ klas
- **Poziom 2** (po BaseViewController): ~10 klas
- **Poziom 3** (po Basic): ~2 klasy główne (Index, Settings\Base\Views\Index)
- **Poziom 4+** (po Index): ~100+ klas widoków modułów

### Podział według typu:

- **Akcje (Actions)**: ~150 klas
- **Widoki (Views)**: ~100+ klas
- **Klasy abstrakcyjne**: 4 klasy (BaseActionController, BaseViewController, Basic, Mass)

---

## 6. Diagram Hierarchii (Tekstowy)

```
BaseActionController (abstract)
│
├── BaseViewController (abstract)
│   │
│   ├── Basic (abstract)
│   │   │
│   │   ├── Index
│   │   │   ├── Detail
│   │   │   ├── Edit
│   │   │   ├── ListView
│   │   │   ├── Popup
│   │   │   ├── BasicModal
│   │   │   ├── IndexAjax
│   │   │   ├── ListAjax
│   │   │   ├── Export
│   │   │   ├── Import
│   │   │   ├── PDF
│   │   │   ├── FindDuplicates
│   │   │   ├── MergeRecord
│   │   │   ├── Inventory
│   │   │   ├── DashBoard
│   │   │   └── [Wszystkie widoki modułów...]
│   │   │
│   │   ├── Settings\Base\Views\Index
│   │   │   └── [Wszystkie widoki Settings...]
│   │   │
│   │   └── Settings\Roles\Views\Popup
│   │
│   ├── Header (abstract)
│   │   └── Footer
│   │
│   ├── VariablePanel
│   │
│   ├── EmployeeHierarchy
│   ├── Hierarchy (IStorages)
│   ├── Main (Import)
│   ├── ExportReport
│   ├── SaveConvertLead
│   ├── AccountHierarchy
│   ├── Login
│   └── Hierarchy (SSalesProcesses)
│
├── Mass (abstract)
│   ├── MassSend
│   ├── MassDelete
│   ├── DownloadAttachment
│   └── MassAccept
│
└── [~150+ klas akcji bezpośrednich]
    ├── Base Actions (Delete, Save, ExportData, etc.)
    ├── Calendar Actions
    ├── Campaigns Actions
    ├── CustomView Actions
    ├── Documents Actions
    ├── HelpDesk Actions
    ├── Import Actions
    ├── KnowledgeBase Actions
    ├── ModComments Actions
    ├── ModTracker Actions
    ├── Notification Actions
    ├── OSSMail Actions
    ├── OSSMailScanner Actions
    ├── OSSMailView Actions
    ├── OSSPasswords Actions
    ├── OSSTimeControl Actions
    ├── OpenStreetMap Actions
    ├── PBXManager Actions
    ├── PaymentsIn/Out Actions
    ├── PriceBooks Actions
    ├── Products Actions
    ├── RecycleBin Actions
    ├── Reports Actions
    ├── Reservations Actions
    ├── Rss Actions
    ├── SMSNotifier Actions
    ├── Settings Actions
    ├── Users Actions
    └── [Wiele innych...]
```

---

## 7. Uwagi Techniczne

### 7.1. Wzorce Projektowe

- **Template Method Pattern**: BaseActionController definiuje szkielet algorytmu (preProcess → process → postProcess)
- **Strategy Pattern**: Różne klasy akcji implementują różne strategie przetwarzania
- **Factory Pattern**: Klasy widoków są tworzone dynamicznie na podstawie żądania

### 7.2. Kluczowe Mechanizmy

1. **Exposed Methods**: Mechanizm ekspozycji metod dla wywołań zewnętrznych
2. **Request Validation**: Walidacja żądań przed przetworzeniem
3. **Permission Checking**: Sprawdzanie uprawnień użytkownika
4. **View Rendering**: System renderowania widoków z użyciem Smarty

### 7.3. Zależności

- Wszystkie klasy wymagają `App\Http\Vtiger_Request` do działania
- Widoki używają `App\Runtime\CRM_Viewer` do renderowania
- Akcje mogą używać różnych modeli danych z namespace `App\Modules\{Module}\Models\`

---

## 8. Przyszłe Rozszerzenia

Aby dodać nową klasę dziedziczącą po BaseActionController:

1. **Dla akcji**: Utwórz klasę w `src/Modules/{Module}/Actions/{ActionName}.php`
2. **Dla widoku**: Utwórz klasę w `src/Modules/{Module}/Views/{ViewName}.php` dziedziczącą po odpowiedniej klasie bazowej (Index, BasicModal, etc.)

---

## 9. Zobacz Także

- `documentation/view-types-analysis.md` - Analiza typów widoków
- `documentation/runtime_classes_analysis.md` - Analiza klas runtime
- `src/Base/Controllers/BaseActionController.php` - Kod źródłowy klasy bazowej
- `src/Base/Controllers/BaseViewController.php` - Kod źródłowy kontrolera widoków

---

**Autor:** FreeCRM Development Team  
**Licencja:** FreeCRM Public License 1.1

