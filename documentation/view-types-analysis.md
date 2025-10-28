# FreeCRM View Types - Deep Analysis

## Overview
FreeCRM uses a sophisticated view hierarchy based on Vtiger/YetiForce architecture. Views are separated from Actions (which handle AJAX/data operations) and follow a clear inheritance pattern.

## View Inheritance Hierarchy

```
BaseActionController (abstract)
    └── BaseViewController (abstract)
        └── Header (abstract)
            └── Footer (abstract)
                └── Basic (abstract)
                    ├── Index (main views)
                    │   ├── Standard Views (Detail, Edit, ListView, etc.)
                    │   ├── Ajax Views (skip pre/post process)
                    │   ├── Modal Views (inject modal wrappers)
                    │   └── Specialized Views
                    │
                    └── Settings\Base\Views\Index (settings area)
                        └── Settings Views
```

## Core View Categories

### 1. **Standard Full-Page Views**
Views that render complete pages with header, navigation, and footer.

**Characteristics:**
- Extend `Index` or `Basic`
- Include full `preProcess()` and `postProcess()`
- Render complete HTML page structure
- Load all JS/CSS assets
- Include navigation, breadcrumbs, sidebar

**Types:**

#### 1.1 **ListView** - List/table views of records
- **Location:** `src/Modules/Base/Views/ListView.php`
- **Purpose:** Display paginated record lists with filters
- **Features:** Sorting, filtering, mass actions, custom views
- **Template:** `ListView.tpl`
- **Key Methods:** `getListViewEntries()`, `getListViewHeaders()`

#### 1.2 **Detail** - Single record detail view
- **Location:** `src/Modules/Base/Views/Detail.php`
- **Purpose:** Display complete record information
- **Features:** Widgets, related lists, summary/detail modes
- **Template:** `DetailView.tpl`, `DetailViewPreProcess.tpl`
- **Modes:** 
  - `showDetailViewByMode` - Smart mode (summary or detail based on user prefs)
  - `showModuleDetailView` - Full detail view
  - `showModuleBasicView` - Basic view
  - `showRecentActivities` - Activity timeline
  - `showRecentComments` - Comments
  - `showRelatedRecords` - Related lists

#### 1.3 **Edit** - Record creation/editing view
- **Location:** `src/Modules/Base/Views/Edit.php`
- **Purpose:** Create or edit records with full form
- **Features:** Field blocks, validation, picklist dependencies
- **Template:** `EditView.tpl`
- **Modes:** Create (no record ID) or Edit (with record ID)

#### 1.4 **DashBoard** - Dashboard with widgets
- **Location:** `src/Modules/Base/Views/DashBoard.php`
- **Purpose:** Display configurable dashboard with widgets
- **Features:** Draggable widgets, multiple dashboard types, charts
- **Template:** `dashboards/DashBoardContents.tpl`
- **Special:** Loads gridster, flot, jqplot libraries

#### 1.5 **Import** - Data import wizard
- **Location:** `src/Modules/Base/Views/Import.php`
- **Purpose:** Multi-step import process
- **Features:** File upload, field mapping, validation
- **Template:** `Import.tpl`

---

### 2. **Ajax Views**
Views that return HTML fragments without full page structure (no header/footer).

**Characteristics:**
- Extend from `Index` but override `preProcess()` and `postProcess()` to return early
- Return HTML snippets for dynamic loading
- Used for partial page updates
- Use `invokeExposedMethod()` pattern with `mode` parameter

**Types:**

#### 2.1 **BasicAjax** - Generic Ajax operations
- **Location:** `src/Modules/Base/Views/BasicAjax.php`
- **Purpose:** Generic Ajax view handlers
- **Methods Exposed:**
  - `showAdvancedSearch` - Advanced search form
  - `showSearchResults` - Search results
- **Pre/Post Process:** Empty (returns true)

#### 2.2 **ListAjax** - List view Ajax operations
- **Location:** `src/Modules/Base/Views/ListAjax.php`
- **Purpose:** Ajax operations for list views
- **Methods Exposed:**
  - `getListViewCount` - Count records
  - `getRecordsCount` - Filtered record count
  - `getPageCount` - Calculate pagination
- **Pre/Post Process:** Empty

#### 2.3 **PopupAjax** - Popup list Ajax operations
- **Location:** `src/Modules/Base/Views/PopupAjax.php`
- **Purpose:** Ajax operations for popup selection
- **Methods Exposed:**
  - `getListViewCount`
  - `getRecordsCount`
  - `getPageCount`
- **Pre/Post Process:** Empty
- **Template:** `PopupContents.tpl`

#### 2.4 **IndexAjax** - Module index Ajax operations
- **Location:** `src/Modules/Base/Views/IndexAjax.php`
- **Purpose:** Ajax handlers for module operations
- **Pre/Post Process:** Empty

#### 2.5 **MassActionAjax** - Mass action forms
- **Location:** `src/Modules/Base/Views/MassActionAjax.php`
- **Purpose:** Display forms for mass operations on records
- **Methods Exposed:**
  - `showMassEditForm` - Mass edit form
  - `showAddCommentForm` - Add comment to multiple records
  - `showSendSMSForm` - Send SMS to multiple records
  - `showDuplicatesSearchForm` - Find duplicates
  - `transferOwnership` - Transfer ownership form
- **Returns:** HTML form snippets

#### 2.6 **QuickCreateAjax** - Quick create form
- **Location:** `src/Modules/Base/Views/QuickCreateAjax.php`
- **Purpose:** Display quick create modal content
- **Returns:** Quick create form HTML
- **Template:** `QuickCreate.tpl`
- **Features:** Pre-filled fields from source, picklist dependencies

#### 2.7 **TooltipAjax** - Record preview tooltips
- **Location:** `src/Modules/Base/Views/TooltipAjax.php`
- **Purpose:** Display record information in tooltips
- **Returns:** Tooltip HTML content

#### 2.8 **FindDuplicatesAjax** - Duplicate detection Ajax
- **Location:** `src/Modules/Base/Views/FindDuplicatesAjax.php`
- **Purpose:** Ajax operations for duplicate detection
- **Pre/Post Process:** Empty

---

### 3. **Modal Views**
Views that render content wrapped in Bootstrap modal markup.

**Characteristics:**
- Extend from base view (usually `Index`)
- Override `preProcess()` to inject modal opening tags
- Override `postProcess()` to inject modal closing tags
- Include modal-specific CSS/JS
- Method `getSize()` defines modal size classes

**Types:**

#### 3.1 **BasicModal** - Base modal view
- **Location:** `src/Modules/Base/Views/BasicModal.php`
- **Purpose:** Base class for all modal views
- **Structure:**
```html
<div class="modal fade modal{Module}{View}" id="modal{View}">
  <div class="modal-dialog {SIZE}">
    <div class="modal-content">
      <!-- Modal CSS -->
      <!-- Content from process() -->
      <!-- Modal JS -->
    </div>
  </div>
</div>
```
- **Methods:**
  - `getSize()` - Return modal size class (empty, modal-lg, modal-sm, modal-full)
  - `getModalScripts()` - JS files for modal
  - `getModalCss()` - CSS files for modal
- **Usage:** Extended by specific modal views

#### 3.2 **QuickDetailModal** - Quick record preview
- **Location:** `src/Modules/Base/Views/QuickDetailModal.php`
- **Purpose:** Show record details in modal
- **Size:** Usually large (`modal-lg`)

#### 3.3 **SendMailModal** - Email composition modal
- **Location:** `src/Modules/Base/Views/SendMailModal.php`
- **Purpose:** Send email from modal
- **Size:** Full screen (`modal-full`)

#### 3.4 **TimeLineModal** - Timeline view modal
- **Location:** `src/Modules/Base/Views/TimeLineModal.php`
- **Purpose:** Display record timeline in modal

#### 3.5 **RelatedCommentModal** - Comments modal
- **Location:** `src/Modules/Base/Views/RelatedCommentModal.php`
- **Purpose:** Show/add comments in modal

#### 3.6 **EditFieldByModal** - Field editor modal
- **Location:** `src/Modules/Base/Views/EditFieldByModal.php`
- **Purpose:** Edit single field value in modal
- **Module Specific:** Many modules override this (Assets, Quotes, etc.)

#### 3.7 **GenerateModal** - Generic content modal
- **Location:** `src/Modules/Base/Views/GenerateModal.php`
- **Purpose:** Generic modal for custom content

#### 3.8 **TreeCategoryModal** - Tree category selector
- **Location:** `src/Modules/Base/Views/TreeCategoryModal.php`
- **Purpose:** Select categories in tree structure

#### 3.9 **MapModal** - Map display modal
- **Location:** `src/Modules/OpenStreetMap/Views/MapModal.php`
- **Purpose:** Display OpenStreetMap in modal
- **Module Specific:** OpenStreetMap module

#### 3.10 **ActivityStateModal** - Activity state editor
- **Location:** `src/Modules/Calendar/Views/ActivityStateModal.php`
- **Purpose:** Edit calendar activity state
- **Module Specific:** Calendar module

---

### 4. **Popup Views**
Views for record selection in popup windows.

**Characteristics:**
- Extend from `Index`
- Used for selecting related records
- Support single and multi-select modes
- Include search and filtering
- Can be related to parent record

**Types:**

#### 4.1 **Popup** - Record selection popup
- **Location:** `src/Modules/Base/Views/Popup.php`
- **Purpose:** Select records from a popup list
- **Features:**
  - Single/multi-select mode
  - Search and filtering
  - Related parent filtering
  - Custom views (cvid)
  - Source module context
- **Template:** `Popup.tpl`, `PopupFooter.tpl`
- **Parameters:**
  - `src_module` - Source module requesting popup
  - `src_field` - Field being populated
  - `src_record` - Source record ID
  - `multi_select` - Enable multi-select
  - `related_parent_module` - Filter by parent
  - `related_parent_id` - Parent record ID
- **Module Overrides:** Products, Services, Contacts, OSSPasswords, etc.

#### 4.2 **TreePopup** - Tree-based selection
- **Location:** `src/Modules/Base/Views/TreePopup.php`
- **Purpose:** Select records with tree/hierarchy structure
- **Used For:** Categories, organizational structures

---

### 5. **Specialized Views**

#### 5.1 **Export** - Export data view
- **Location:** `src/Modules/Base/Views/Export.php`
- **Purpose:** Display export options
- **Template:** `Export.tpl`
- **Features:** CSV/XML export, template selection
- **Export Modes:**
  - `ExportAllData` - All records (limited by config)
  - `ExportCurrentPage` - Current page only
  - `ExportSelectedRecords` - Selected records

#### 5.2 **PDF** - PDF generation view
- **Location:** `src/Modules/Base/Views/PDF.php`
- **Purpose:** Generate PDF documents
- **Used For:** Invoices, quotes, reports

#### 5.3 **MergeRecord** - Record merge view
- **Location:** `src/Modules/Base/Views/MergeRecord.php`
- **Purpose:** Merge duplicate records
- **Template:** `MergeRecord.tpl`

#### 5.4 **FindDuplicates** - Duplicate detection view
- **Location:** `src/Modules/Base/Views/FindDuplicates.php`
- **Purpose:** Find and manage duplicate records
- **Template:** `FindDuplicates.tpl`

#### 5.5 **ShowWidget** - Widget renderer
- **Location:** `src/Modules/Base/Views/ShowWidget.php`
- **Purpose:** Render individual dashboard widgets
- **Used By:** DashBoard view
- **Returns:** Widget HTML/JSON

#### 5.6 **WidgetFullscreen** - Fullscreen widget
- **Location:** `src/Modules/Base/Views/WidgetFullscreen.php`
- **Purpose:** Display widget in fullscreen mode

#### 5.7 **ChartFilter** - Chart filter view
- **Location:** `src/Modules/Base/Views/ChartFilter.php`
- **Purpose:** Filter options for charts

#### 5.8 **RelatedList** - Related records view
- **Location:** `src/Modules/Base/Views/RelatedList.php`
- **Purpose:** Display related records in detail view
- **Template:** `RelatedList.tpl`

#### 5.9 **Inventory** - Inventory lines view
- **Location:** `src/Modules/Base/Views/Inventory.php`
- **Purpose:** Manage inventory lines (products/services)
- **Used In:** Quotes, Invoices, Orders, etc.

#### 5.10 **SmartDetail** - Smart detail modal
- **Location:** `src/Modules/Base/Views/SmartDetail.php`
- **Purpose:** Quick detail view in modal
- **Template:** `SmartDetail.tpl`

#### 5.11 **WorkflowTrigger** - Workflow trigger view
- **Location:** `src/Modules/Base/Views/WorkflowTrigger.php`
- **Purpose:** Trigger workflows manually

#### 5.12 **TreeRecords** - Tree structure view
- **Location:** `src/Modules/Base/Views/TreeRecords.php`
- **Purpose:** Display records in tree hierarchy

#### 5.13 **VariablePanel** - Variable panel view
- **Location:** `src/Modules/Base/Views/VariablePanel.php`
- **Purpose:** Variable/placeholder selection panel

#### 5.14 **MiniListWizard** - Mini list wizard
- **Location:** `src/Modules/Base/Views/MiniListWizard.php`
- **Purpose:** Wizard-style mini list

#### 5.15 **AddNotePad** - Notepad addition
- **Location:** `src/Modules/Base/Views/AddNotePad.php`
- **Purpose:** Add notepad entries

#### 5.16 **FileUpload** - File upload view
- **Location:** `src/Modules/Base/Views/FileUpload.php`
- **Purpose:** Handle file uploads

#### 5.17 **Pagination** - Pagination component
- **Location:** `src/Modules/Base/Views/Pagination.php`
- **Purpose:** Render pagination controls

#### 5.18 **AutoAssignRecord** - Auto-assignment view
- **Location:** `src/Modules/Base/Views/AutoAssignRecord.php`
- **Purpose:** Auto-assign record functionality

---

### 6. **Settings Views**
Views for system administration and settings.

**Characteristics:**
- Located in `src/Modules/Settings/{Module}/Views/`
- Extend `Settings\Base\Views\Index` or `Settings\Base\Views\ListView`
- Admin-only access
- Special breadcrumb handling
- Settings menu sidebar

**Types:**

#### 6.1 **Settings Index** - Settings home
- **Location:** `src/Modules/Settings/Base/Views/Index.php`
- **Purpose:** Settings dashboard/landing page
- **Features:** System warnings, module counts, shortcuts

#### 6.2 **Settings ListView** - Settings list views
- **Location:** `src/Modules/Settings/Base/Views/ListView.php`
- **Purpose:** List settings records (users, roles, fields, etc.)

#### 6.3 **Settings BasicModal** - Settings modals
- **Location:** `src/Modules/Settings/Base/Views/BasicModal.php`
- **Purpose:** Modal views in settings area

#### 6.4 **Settings ListAjax** - Settings Ajax lists
- **Location:** `src/Modules/Settings/Base/Views/ListAjax.php`
- **Purpose:** Ajax operations for settings lists

#### 6.5 **Settings IndexAjax** - Settings Ajax operations
- **Location:** `src/Modules/Settings/Base/Views/IndexAjax.php`
- **Purpose:** Generic settings Ajax handlers

#### 6.6 **ConfigEditor** - Configuration editor
- **Location:** Settings modules
- **Purpose:** Edit configuration files
- **Types:** Detail, Edit views for configs

#### 6.7 **Settings Specialized Views**
- **ListMenu** - Menu configuration
- **IconsModal** - Icon selection
- **ModalAjax** - Inventory settings Ajax
- **Policy** - Privacy policy editor
- **TermsAndConditionsEdit** - Terms editor
- **License** - License information
- **Credits** - Credits page

---

## View Processing Flow

### Standard View Lifecycle
```
1. __construct() - Initialize, expose methods
2. checkPermission($request) - Verify access
3. preProcess($request) - Render header, navigation, sidebar
   └── preProcessDisplay($request) - Display the template
4. process($request) - Main content rendering
5. postProcess($request) - Render footer, scripts
```

### Ajax View Lifecycle
```
1. __construct() - Initialize, expose methods
2. checkPermission($request) - Verify access
3. preProcess($request) - Returns true (no rendering)
4. process($request)
   └── invokeExposedMethod($mode, $request) - Call specific method
5. postProcess($request) - Returns true (no rendering)
```

### Modal View Lifecycle
```
1. __construct() - Initialize
2. checkPermission($request) - Verify access
3. preProcess($request)
   └── Echo modal opening tags + CSS links
4. process($request) - Render modal content
5. postProcess($request)
   └── Echo modal closing tags + JS scripts
```

---

## Key Patterns

### 1. **Exposed Methods Pattern**
Views use the exposed methods pattern for mode-based processing:

```php
public function __construct() {
    parent::__construct();
    $this->exposeMethod('methodName');
}

public function process(\App\Http\Vtiger_Request $request) {
    $mode = $request->get('mode');
    if (!empty($mode)) {
        $this->invokeExposedMethod($mode, $request);
        return;
    }
    // Default behavior
}

public function methodName(\App\Http\Vtiger_Request $request) {
    // Handle specific mode
}
```

### 2. **Template Loading**
Views use Smarty templates via the viewer:

```php
$viewer = $this->getViewer($request);
$viewer->assign('VARIABLE', $value);
$viewer->view('Template.tpl', $moduleName);
// OR for returning string:
echo $viewer->view('Template.tpl', $moduleName, true);
```

### 3. **Asset Loading**
Views define required JS/CSS:

```php
public function getFooterScripts(\App\Http\Vtiger_Request $request) {
    $scripts = parent::getFooterScripts($request);
    $jsFileNames = [
        'modules.Base.resources.ViewName',
        "modules.{$moduleName}.resources.ViewName"
    ];
    return array_merge($scripts, $this->checkAndConvertJsScripts($jsFileNames));
}
```

### 4. **Pre/Post Process Control**
Ajax and Modal views control rendering:

```php
// Ajax: No wrapper
public function preProcess(\App\Http\Vtiger_Request $request, $display = true) {
    return true; // Skip header
}

// Modal: Custom wrapper
public function preProcess(\App\Http\Vtiger_Request $request, $display = true) {
    echo '<div class="modal fade">...';
}
```

---

## View Type Decision Matrix

| Requirement | View Type | Example |
|-------------|-----------|---------|
| Full page with navigation | Standard View | Detail, Edit, ListView |
| Partial page update | Ajax View | ListAjax, BasicAjax |
| Popup dialog | Modal View | QuickDetailModal, SendMailModal |
| Record selection | Popup View | Popup, TreePopup |
| Data export | Specialized | Export |
| Widget display | Specialized | ShowWidget |
| Settings page | Settings View | Settings\Base\Views\Index |

---

## Module-Specific View Overrides

Modules can override base views to customize behavior:

**Common Overrides:**
- `Detail` - Custom detail view layout
- `Edit` - Custom edit form
- `ListView` - Custom list display
- `Popup` - Custom record selection
- `QuickCreateAjax` - Custom quick create
- `EditFieldByModal` - Custom field editing

**Example Locations:**
- `src/Modules/Calendar/Views/QuickCreateAjax.php`
- `src/Modules/Products/Views/Popup.php`
- `src/Modules/Accounts/Views/Detail.php`

---

## Summary Table

| Category | Count | Purpose | Pre/Post Process | Modal Wrapper |
|----------|-------|---------|------------------|---------------|
| Standard Views | ~10 | Full page rendering | Yes | No |
| Ajax Views | ~8 | HTML fragments | No (return true) | No |
| Modal Views | ~10 | Popup dialogs | Custom (modal tags) | Yes |
| Popup Views | ~2 | Record selection | Yes | No |
| Specialized Views | ~18 | Specific features | Varies | Varies |
| Settings Views | ~15+ | Administration | Yes (settings layout) | No |

**Total View Types: ~60+ base views** (not counting module-specific overrides)

---

## Best Practices

1. **Extend Appropriate Base**
   - Full page → extend `Index`
   - Ajax → extend `Index`, override pre/post to return true
   - Modal → extend `BasicModal`
   - Settings → extend `Settings\Base\Views\Index`

2. **Use Exposed Methods**
   - For multi-mode views, use `exposeMethod()` pattern
   - Check mode in `process()` and invoke method

3. **Control Asset Loading**
   - Override `getFooterScripts()` for JS
   - Override `getHeaderCss()` for CSS
   - Use parent methods to preserve base assets

4. **Respect Permissions**
   - Override `checkPermission()` for custom checks
   - Use privilege models for module/record access

5. **Template Organization**
   - Store in `layouts/{layout}/modules/{Module}/`
   - Use consistent naming (Detail.tpl, Edit.tpl, etc.)

---

## Conclusion

The FreeCRM view system is highly modular with clear separation of concerns:
- **Standard Views** for full pages
- **Ajax Views** for dynamic content
- **Modal Views** for dialogs
- **Popup Views** for selection
- **Specialized Views** for unique features
- **Settings Views** for administration

Each type has specific characteristics around pre/post processing, template rendering, and asset loading, making it easy to determine the right base class for new views.

