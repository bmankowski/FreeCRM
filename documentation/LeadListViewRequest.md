# Leads ListView Request Flow Documentation

## Request URL
```
http://localhost/index.php?module=Leads&view=ListView
```

## Complete Request Lifecycle

This document traces the complete request flow from when a user accesses the Leads ListView URL through to final template rendering.

---

## 1. Entry Point: `public/index.php` or `index.php`

**File:** `/public/index.php` or `/index.php`

### Purpose
- Front controller that bootstraps the application
- Handles both direct access (`index.php`) and public directory access (`public/index.php`)

### Process Flow:

1. **Directory Setup**
   - If accessed via `public/index.php`, changes directory to project root
   - Cleans up `$_SERVER` paths to remove `/public/` references
   - Sets `ROOT_DIRECTORY` constant

2. **Bootstrap Phase** (`index.php` lines 37-45)
   ```php
   define('REQUEST_MODE', 'WebUI');
   require_once ROOT_DIRECTORY . '/vendor/autoload.php';  // Composer PSR-4
   require_once ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
   require_once ROOT_DIRECTORY . '/config/api.php';
   require_once ROOT_DIRECTORY . '/config/config.php';
   \App\AppConfig::init($API_CONFIG);
   \App\Loader::register();  // Legacy autoloader
   ```

3. **Initialize Services** (line 48)
   ```php
   \App\EntryPoint\WebUI::initialize();
   ```
   - Configures error reporting
   - Initializes cache and debugger
   - Registers error handlers

4. **Create WebUI Instance and Process Request** (lines 51-57)
   ```php
   $webUI = new \App\EntryPoint\WebUI();
   $request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
   $webUI->process($request);
   ```

---

## 2. WebUI Request Processing

**File:** `/src/EntryPoint/WebUI.php`

### WebUI::process() Method (lines 266-284)

```php
public function process(\App\Http\Vtiger_Request $request)
{
    $this->enforceSecurityPolicies($request);      // SSL, installation check
    $this->initializeSession($request);            // Session management
    $currentUser = $this->initializeGlobals($request);  // User context
    
    try {
        $response = $this->handleRequest($request, $currentUser);
    } catch (\Exception $exception) {
        $this->handleException($exception, $request);
    }
    
    if ($response) {
        $response->emit();
    }
}
```

### handleRequest() Method (lines 470-486)

For our Leads ListView request:

1. **Resolve Module and View** (line 471)
   - Gets `module=Leads` and `view=ListView` from request
   - Module: `Leads`
   - View: `ListView`

2. **Check Settings Access** (line 476)
   - Not applicable (not a Settings module)

3. **Resolve Component** (line 478)
   ```php
   list($componentType, $componentName) = $this->resolveComponent($request);
   // Returns: ['View', 'ListView']
   ```

4. **Create Handler** (line 483)
   ```php
   $handler = $this->createHandler('View', 'ListView', 'Leads');
   ```

---

## 3. Component Class Resolution

**File:** `/src/Loader.php`

### Loader::getComponentClassName() Method (lines 53-143)

**Input:** `getComponentClassName('View', 'ListView', 'Leads')`

**Resolution Process:**

1. **Check Cache** (lines 60-63)
   - Cache key: `Leads_View_ListView`
   - If not cached, proceed with resolution

2. **Build PSR-4 Class Name** (lines 86-94)
   ```php
   // Convert type to plural: View → Views
   $typeDir = 'Views';
   
   // Build class name
   $className = "App\\Modules\\Leads\\Views\\ListView";
   $filePath = "src/Modules/Leads/Views/ListView.php";
   ```

3. **Check File Existence**
   - For Leads: File doesn't exist (Leads uses base class)
   - Falls back to: `App\\Modules\\Base\\Views\\ListView`
   - File: `src/Modules/Base/Views/ListView.php` ✓ EXISTS

4. **Return Resolved Class**
   ```php
   return "App\\Modules\\Base\\Views\\ListView";
   ```

5. **Cache Result**
   - Stores in `self::$componentClassCache` for future requests

---

## 4. Handler Instantiation and Execution

**File:** `/src/EntryPoint/WebUI.php`

### createHandler() Method (lines 597-613)

```php
$handlerClass = "App\\Modules\\Base\\Views\\ListView";
$handler = new $handlerClass();
return $handler;
```

### executeHandler() Method (lines 624-637)

**Execution Lifecycle:**

1. **Set Global Module** (line 626)
   ```php
   vglobal('currentModule', 'Leads');
   ```

2. **Validate Handler** (line 628)
   - Checks if handler implements required interface

3. **Check Login** (line 629)
   - Verifies user is authenticated

4. **Check Permissions** (line 630)
   - **Class:** `App\Modules\Base\Views\ListView` (extends Index extends Basic)
   - **Method:** `checkPermission()`
   - **File:** `src/Modules/Base/Views/Index.php` (lines 24-35)
   - Verifies user has permission to access Leads module

5. **Trigger PreProcess** (line 632)
   ```php
   $this->triggerPreProcess($handler, $request);
   ```

6. **Execute Process** (line 633)
   ```php
   $response = $handler->process($request);
   ```

7. **Trigger PostProcess** (line 634)
   ```php
   $this->triggerPostProcess($handler, $request);
   ```

---

## 5. View Handler Execution

### Class Hierarchy

```
App\Modules\Base\Views\ListView
    ↓ extends
App\Modules\Base\Views\Index
    ↓ extends
App\Modules\Base\Views\Basic
    ↓ extends
App\Base\Controllers\BaseViewController
```

---

### 5.1 PreProcess Phase

**Triggered by:** `WebUI::triggerPreProcess()` (WebUI.php line 632)

#### Basic::preProcess() 
**File:** `src/Modules/Base/Views/Basic.php` (lines 120-163)

**Actions:**
- Calls parent `BaseViewController::preProcess()`
- Gets viewer instance
- Assigns common variables:
  ```php
  $viewer->assign('MODULE', 'Leads');
  $viewer->assign('QUALIFIED_MODULE', 'Leads');
  $viewer->assign('MENUS', $this->getMenu());
  $viewer->assign('VIEW', 'ListView');
  $viewer->assign('COMPANY_LOGO', $companyLogo);
  $viewer->assign('MENU_HEADER_LINKS', ...);
  ```

#### Index::preProcess()
**File:** `src/Modules/Base/Views/Index.php` (lines 37-68)

**Actions:**
- Calls `parent::preProcess($request, false)`
- Gets module model for Leads
- Checks module permissions
- Gets sidebar links:
  ```php
  $linkModels = $moduleModel->getSideBarLinks(['MODULE' => 'Leads', 'ACTION' => 'ListView']);
  $viewer->assign('QUICK_LINKS', $linkModels);
  ```

#### ListView::preProcess()
**File:** `src/Modules/Base/Views/ListView.php` (lines 62-83)

**Actions:**
- Calls `parent::preProcess($request, false)`
- Gets custom views for the module:
  ```php
  $viewer->assign('CUSTOM_VIEWS', \App\Modules\CustomView\Models\Record::getAllByGroup('Leads', $mid));
  ```
- Creates ListView model:
  ```php
  $this->viewName = \App\CustomView::getInstance('Leads')->getViewId();
  $this->listViewModel = \App\Modules\Base\Models\ListView::getInstance('Leads', $this->viewName);
  ```
- Gets header links and assigns data
- Calls `initializeListViewContents()` to prepare list data
- **Note:** Does NOT render any template (MainLayout handles it)

---

### 5.2 Process Phase

**File:** `src/Modules/Base/Views/ListView.php` (lines 101-133)

#### ListView::process() Method

**For non-AJAX request:**

```php
public function process(\App\Http\Vtiger_Request $request)
{
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();  // 'Leads'
    
    if ($request->isAjax()) {
        // AJAX handling (not our case)
        $viewer->view('ListViewContents.tpl', $moduleName);
    } else {
        // Full page rendering
        $viewer->assign('VIEW', $request->get('view'));  // 'ListView'
        $viewer->view('ListView.tpl', $moduleName);
    }
}
```

**Template Resolution:**
- Looks for: `layouts/basic/modules/Leads/ListView.tpl`
- If not found, falls back to: `layouts/basic/modules/Base/ListView.tpl` ✓

---

### 5.3 PostProcess Phase

**File:** `src/Modules/Base/Views/ListView.php` (lines 135-139)

```php
public function postProcess(\App\Http\Vtiger_Request $request)
{
    parent::postProcess($request);
}
```

**Actions:**
- Calls parent postProcess
- MainLayout handles footer rendering

---

## 6. Template Rendering

### Template Hierarchy and Inheritance

**File:** `layouts/basic/modules/Base/ListView.tpl`

```smarty
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
    <!-- ListView specific content -->
{/block}
```

This template **extends** MainLayout.tpl, which provides the complete page structure.

---

### 6.1 Main Layout Template

**File:** `layouts/basic/modules/Base/MainLayout.tpl`

**Complete Page Structure:**

```html
<!DOCTYPE html>
<html lang="{$HTMLLANG}">
<head>
    {block name="head"}
        {block name="title"}<title>...</title>{/block}
        {block name="meta"}<!-- Meta tags -->{/block}
        {block name="styles"}<!-- CSS files -->{/block}
        {block name="header_scripts"}<!-- Header JS -->{/block}
        {block name="head_custom"}<!-- Custom head content -->{/block}
    {/block}
</head>
<body>
    {block name="body_start"}
        <!-- Configuration and language strings -->
    {/block}
    
    {block name="announcements"}
        <!-- System announcements -->
    {/block}
    
    {block name="main_container"}
        <div class="container-fluid container-fluid-main">
            <div class="baseContainer">
                {block name="navigation"}
                    <!-- Mobile header -->
                    <!-- Left panel (menu) -->
                    <!-- Top header -->
                {/block}
                
                {block name="main_content"}
                    <div class="basePanel">
                        <div class="mainBody">
                            <div class="bodyContent">
                                {block name="content"}
                                    <!-- CONTENT GOES HERE -->
                                {/block}
                            </div>
                        </div>
                    </div>
                {/block}
            </div>
        </div>
    {/block}
    
    {block name="footer"}
        <!-- Footer template -->
    {/block}
</body>
</html>
```

---

### 6.2 ListView.tpl Content Block

**File:** `layouts/basic/modules/Base/ListView.tpl`

**Overrides the `{block name="content"}` from MainLayout:**

```smarty
{block name="content"}
    <div id="page">
        <div id="pjaxContainer" class="hide noprint"></div>
        <div class="mainContainer">
            <div class="contentsDiv">
                
                {* Header with breadcrumbs and action buttons *}
                <div class="widget_header row marginBottom10px">
                    <div class="col-sm-6 col-xs-12">
                        {include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
                    </div>
                    <div class="col-sm-6 col-xs-12">
                        <div class="pull-right">
                            {foreach item=LINK from=$HEADER_LINKS['LIST_VIEW_HEADER']}
                                {include file='ButtonLink.tpl'|@vtemplate_path:$MODULE BUTTON_VIEW='listViewHeader'}
                            {/foreach}
                        </div>
                    </div>
                </div>
                
                {* List view table and controls *}
                {include file="ListViewHeader.tpl"|vtemplate_path:$MODULE}
                {include file="ListViewContents.tpl"|vtemplate_path:$MODULE}
                
            </div>
        </div>
    </div>
{/block}
```

---

## 7. All Templates Included (Complete List)

### Primary Template Chain

1. **MainLayout.tpl** (`layouts/basic/modules/Base/MainLayout.tpl`)
   - Root template providing full HTML structure
   - Defines all blocks (head, navigation, content, footer)

2. **ListView.tpl** (`layouts/basic/modules/Base/ListView.tpl`)
   - Extends MainLayout.tpl
   - Overrides `{block name="content"}`

### Templates Included by MainLayout.tpl

**In `{block name="navigation"}`:**

3. **BodyHeaderMobile.tpl** (`layouts/basic/modules/Base/BodyHeaderMobile.tpl`)
   - Mobile navigation header
   - Hamburger menu, search icon, notifications

4. **BodyLeft.tpl** (Mobile) (`layouts/basic/modules/Base/BodyLeft.tpl`)
   - Left sidebar menu for mobile
   - Assigned as: `DEVICE=Mobile`

5. **BodyLeft.tpl** (Desktop) (`layouts/basic/modules/Base/BodyLeft.tpl`)
   - Left sidebar menu for desktop
   - Assigned as: `DEVICE=Desktop`

6. **BodyHeader.tpl** (`layouts/basic/modules/Base/BodyHeader.tpl`)
   - Main top navigation bar
   - Company logo, module tabs, global search, user menu

**In `{block name="footer"}`:**

7. **Footer.tpl** (`layouts/basic/modules/Base/Footer.tpl`)
   - Footer scripts
   - Performance tracking
   - Debug bar (if enabled)

### Templates Included by ListView.tpl

**In content block:**

8. **BreadCrumbs.tpl** (`layouts/basic/modules/Base/BreadCrumbs.tpl` or `layouts/basic/modules/Leads/BreadCrumbs.tpl`)
   - Navigation breadcrumbs
   - Shows current location hierarchy

9. **ButtonLink.tpl** (`layouts/basic/modules/Base/ButtonLink.tpl`)
   - Renders action buttons
   - Used for header action buttons (Add Record, etc.)
   - Included in foreach loop

10. **ListViewHeader.tpl** (`layouts/basic/modules/Base/ListViewHeader.tpl` or `layouts/basic/modules/Leads/ListViewHeader.tpl`)
    - List view controls
    - Mass action dropdown
    - Add record button
    - Custom view selector
    - Filters and search

11. **ListViewContents.tpl** (`layouts/basic/modules/Base/ListViewContents.tpl` or `layouts/basic/modules/Leads/ListViewContents.tpl`)
    - Actual data table
    - Column headers
    - Record rows
    - Pagination
    - Includes sub-templates:
      - **ListViewAlphabet.tpl** - Alphabetic filter
      - **ListViewActions.tpl** - Per-row actions (if present)

### Additional Included Templates (from ListViewHeader.tpl)

12. **ListViewActions.tpl** (if exists for module)
    - Advanced list actions
    - Export, mass operations

### Template Resolution Order

For each template, Smarty searches in this order:

1. `layouts/basic/modules/Leads/TemplateName.tpl` (module-specific)
2. `layouts/basic/modules/Base/TemplateName.tpl` (base/default)

---

## 8. Data Preparation

### initializeListViewContents() Method

**File:** `src/Modules/Base/Views/ListView.php` (lines 186-296)

**Prepares data for ListView rendering:**

```php
public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
{
    $moduleName = $request->getModule();  // 'Leads'
    $pageNumber = $request->get('page');
    $orderBy = $request->get('orderby');
    $sortOrder = $request->get('sortorder');
    
    // Get or create ListView model
    if (!$this->listViewModel) {
        $this->listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, $this->viewName);
    }
    
    // Create paging model
    $pagingModel = new \App\Modules\Base\Models\Paging();
    $pagingModel->set('page', $pageNumber);
    
    // Get list view data
    $this->listViewHeaders = $this->listViewModel->getListViewHeaders();
    $this->listViewEntries = $this->listViewModel->getListViewEntries($pagingModel);
    $this->listViewLinks = $this->listViewModel->getListViewLinks($linkParams);
    
    // Assign to viewer
    $viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
    $viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
    $viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);
    $viewer->assign('PAGING_MODEL', $pagingModel);
    $viewer->assign('ORDER_BY', $orderBy);
    $viewer->assign('SORT_ORDER', $sortOrder);
    // ... more assignments
}
```

---

## 9. Complete File List Summary

### PHP Files (in order of execution)

1. `/public/index.php` or `/index.php` - Entry point
2. `/src/EntryPoint/WebUI.php` - Request processing
3. `/src/Loader.php` - Class resolution
4. `/src/Modules/Base/Views/ListView.php` - Main view handler
5. `/src/Modules/Base/Views/Index.php` - Parent view class
6. `/src/Modules/Base/Views/Basic.php` - Base view class
7. `/src/Base/Controllers/BaseViewController.php` - Controller base
8. `/src/Modules/Base/Models/ListView.php` - ListView model (data)
9. `/src/Modules/Base/Models/Module.php` - Module model
10. `/src/Modules/CustomView/Models/Record.php` - Custom view model
11. `/src/Runtime/CRM_Viewer.php` - Template viewer/renderer

### Template Files (in render order)

1. `layouts/basic/modules/Base/MainLayout.tpl` - Root layout
2. `layouts/basic/modules/Base/BodyHeaderMobile.tpl` - Mobile header
3. `layouts/basic/modules/Base/BodyLeft.tpl` - Sidebar (mobile & desktop)
4. `layouts/basic/modules/Base/BodyHeader.tpl` - Top navigation
5. `layouts/basic/modules/Base/ListView.tpl` - Main content wrapper
6. `layouts/basic/modules/Base/BreadCrumbs.tpl` - Breadcrumbs
7. `layouts/basic/modules/Base/ButtonLink.tpl` - Action buttons
8. `layouts/basic/modules/Base/ListViewHeader.tpl` - List controls
9. `layouts/basic/modules/Base/ListViewContents.tpl` - Data table
10. `layouts/basic/modules/Base/ListViewAlphabet.tpl` - Alphabet filter
11. `layouts/basic/modules/Base/Footer.tpl` - Footer

**Note:** All templates can have module-specific overrides in `layouts/basic/modules/Leads/` directory.

---

## 10. Request Flow Diagram (ASCII)

```
HTTP Request: ?module=Leads&view=ListView
          ↓
    public/index.php
          ↓
      index.php (bootstrap)
          ↓
    WebUI::initialize()
          ↓
    WebUI::process($request)
          ├─→ enforceSecurityPolicies()
          ├─→ initializeSession()
          ├─→ initializeGlobals()
          └─→ handleRequest()
                    ├─→ resolveModuleAndView()
                    ├─→ resolveComponent() → ['View', 'ListView']
                    └─→ createHandler()
                              ↓
                    Loader::getComponentClassName('View', 'ListView', 'Leads')
                              ↓
                    Returns: App\Modules\Base\Views\ListView
                              ↓
                    executeHandler($handler, $request)
                         ├─→ checkPermission()
                         ├─→ triggerPreProcess()
                         │        ├─→ Basic::preProcess()
                         │        ├─→ Index::preProcess()
                         │        └─→ ListView::preProcess()
                         │                 └─→ initializeListViewContents()
                         ├─→ process()
                         │        └─→ ListView::process()
                         │                 └─→ $viewer->view('ListView.tpl')
                         │                          ↓
                         │                   Template Rendering
                         │                          ↓
                         │                   MainLayout.tpl
                         │                    ├─→ Navigation templates
                         │                    ├─→ ListView.tpl {block content}
                         │                    │    ├─→ BreadCrumbs.tpl
                         │                    │    ├─→ ButtonLink.tpl
                         │                    │    ├─→ ListViewHeader.tpl
                         │                    │    └─→ ListViewContents.tpl
                         │                    └─→ Footer.tpl
                         └─→ triggerPostProcess()
                                  ↓
                            HTML Response
```

---

## 11. Key Smarty Variables Available in Templates

### From Basic::preProcess()
- `$MODULE` - 'Leads'
- `$QUALIFIED_MODULE` - 'Leads'
- `$VIEW` - 'ListView'
- `$MENUS` - Module menu structure
- `$MENU_HEADER_LINKS` - User menu (preferences, logout, etc.)
- `$COMPANY_LOGO` - Company logo path
- `$CURRENTDATE` - Current date formatted

### From Index::preProcess()
- `$QUICK_LINKS` - Sidebar quick links
- `$ACTIVE_SIDEBAR_LINK` - Currently active sidebar item

### From ListView::preProcess()
- `$CUSTOM_VIEWS` - Available list filters/views
- `$HEADER_LINKS` - Action buttons for header
- `$VIEWID` - Current custom view ID
- `$MODULE_MODEL` - Module model instance

### From initializeListViewContents()
- `$LISTVIEW_HEADERS` - Column headers configuration
- `$LISTVIEW_ENTRIES` - Array of record data
- `$LISTVIEW_LINKS` - Mass actions and list actions
- `$LISTVIEW_MASSACTIONS` - Mass action options
- `$PAGING_MODEL` - Pagination data
- `$ORDER_BY` - Current sort column
- `$SORT_ORDER` - ASC/DESC
- `$PAGE_NUMBER` - Current page
- `$LISTVIEW_COUNT` - Total record count
- `$USER_MODEL` - Current user model

---

## 12. Important Notes

### Template Resolution
- Smarty looks for module-specific templates first: `layouts/basic/modules/Leads/*.tpl`
- Falls back to base templates: `layouts/basic/modules/Base/*.tpl`
- Uses `vtemplate_path()` function for resolution

### Caching
- Class name resolution is cached in `Loader::$componentClassCache`
- Significantly improves performance on subsequent requests

### AJAX vs Full Page
- `$request->isAjax()` determines rendering mode
- AJAX: Returns only `ListViewContents.tpl`
- Full page: Returns complete `ListView.tpl` (with MainLayout)

### Settings Modules
- Settings modules use `Settings:ModuleName` pattern
- Resolved to `App\Modules\Settings\ModuleName` namespace
- Use separate template directory: `layouts/basic/modules/Settings/ModuleName/`

### Performance
- ListView uses pagination to limit records
- Custom views apply filters at database level
- Templates compiled and cached by Smarty

---

## Conclusion

The Leads ListView request follows a well-structured MVC pattern with clear separation of concerns:

1. **Routing**: WebUI handles request routing and security
2. **Controller**: ListView view class handles business logic
3. **Model**: ListView model retrieves and processes data
4. **View**: Smarty templates render the HTML output
5. **Layout**: MainLayout provides consistent page structure

This architecture allows for:
- Easy module customization (override templates or classes)
- Reusable components (base classes and templates)
- Performance optimization (caching, lazy loading)
- Clear request lifecycle tracking

