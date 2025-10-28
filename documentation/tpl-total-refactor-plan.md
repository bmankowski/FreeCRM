# Smarty Template Blocks Architecture - Complete Implementation Guide

## Current Problem

The FreeCRM template system has divs opened in one template file and closed in different files, violating the principle: **"every opened div must be closed within the same template"**.

### Current Broken Flow

```
Header.tpl:
  <html><body>
  <div baseContainer>        ← OPENS

Body.tpl (navigation):
  <div leftPanel>...</div>   ← Self-contained ✓

PreProcess.tpl:
  <div basePanel>            ← OPENS
    <div mainBody>           ← OPENS

[View Content Renders]

PostProcess.tpl:
    </div>                   ← Closes mainBody from PreProcess
  </div>                     ← Closes basePanel from PreProcess
</div>                       ← Closes baseContainer from Header!

Footer.tpl:
  </body></html>
```

**Problems:**
- PreProcess opens divs, PostProcess closes them (cross-file dependency)
- Multiple PostProcess templates (ListView, Dashboard, Detail) all call IndexPostProcess, creating complex inheritance
- IDE cannot validate structure
- Hard to maintain - must count divs across multiple files
- Breaking one template breaks the entire layout

## Solution: Smarty Template Inheritance

Use Smarty's native `{extends}` and `{block}` features to create a master layout that child templates extend.

### How Smarty Inheritance Works

**Parent Template** defines structure with blocks:
```smarty
{* BaseLayout.tpl *}
<html>
<head>
  {block name="title"}Default Title{/block}
</head>
<body>
  <div class="wrapper">
    {block name="content"}
      Default content
    {/block}
  </div>
</body>
</html>
```

**Child Template** extends and fills blocks:
```smarty
{* MyPage.tpl *}
{extends file="BaseLayout.tpl"}

{block name="title"}My Page Title{/block}

{block name="content"}
  <div class="myContent">
    My specific content here
  </div>
{/block}
```

**Rendered Output:**
```html
<html>
<head>
  <title>My Page Title</title>
</head>
<body>
  <div class="wrapper">
    <div class="myContent">
      My specific content here
    </div>
  </div>
</body>
</html>
```

**Key Point**: Child template ONLY defines block contents - all structure comes from parent. Each file is complete and self-contained.

---

## Proposed Architecture for FreeCRM

### 1. Create Master Layout Template

**File**: `/home/bmankowski/projects/FreeCRM/layouts/basic/modules/Base/MainLayout.tpl`

```smarty
{strip}
<!DOCTYPE html>
<html lang="{$HTMLLANG}">

<head>
  {block name="head"}
    <title>{block name="title"}{$PAGETITLE|t:$QUALIFIED_MODULE}{/block}</title>
    
    {block name="meta"}
      <link REL="SHORTCUT ICON" HREF="{vimage_path('favicon.ico')}">
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <meta name="robots" content="noindex" />
    {/block}
    
    {block name="styles"}
      {foreach key=index item=cssModel from=$STYLES}
        <link rel="{$cssModel->getRel()}" href="{vresource_url($cssModel->getHref())}" />
      {/foreach}
    {/block}
    
    {block name="header_scripts"}
      {foreach key=index item=jsModel from=$HEADER_SCRIPTS}
        <script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}"></script>
      {/foreach}
    {/block}
    
    {block name="head_custom"}
      {if $USER_MODEL}
        {assign var="HEAD_LOCKS" value=$USER_MODEL->getHeadLocks()}
        {if $HEAD_LOCKS}
          <script type="text/javascript">{$HEAD_LOCKS}</script>
        {/if}
      {/if}
    {/block}
  {/block}
</head>

<body {block name="body_attrs"}data-language="{$LANGUAGE}" data-skinpath="{$SKIN_PATH}" data-layoutpath="{$LAYOUT_PATH}"{/block}>
  
  {block name="body_start"}
    <div id="js_strings" class="hide noprint">{\App\Json::encode($LANGUAGE_STRINGS)}</div>
    <div id="configuration">
      {if $USER_MODEL}
        <input type="hidden" id="current_user_id" value="{$USER_MODEL->get('id')}" />
        <input type="hidden" id="userDateFormat" value="{$USER_MODEL->get('date_format')}" />
        {* ... all other configuration inputs ... *}
      {/if}
    </div>
  {/block}
  
  {block name="announcements"}
    {assign var="ANNOUNCEMENTS" value=\App\Modules\Base\Models\Module::getInstance('Announcements')}
    {if $ANNOUNCEMENTS && $ANNOUNCEMENTS->checkActive($VIEW)}
      {include file='Announcement.tpl'|@vtemplate_path:$MODULE}
    {/if}
  {/block}
  
  <div class="container-fluid container-fluid-main">
    <div class="baseContainer {if AppConfig::module('Users','IS_VISIBLE_USER_INFO_FOOTER')}userInfoFooter{/if}">
      
      {block name="navigation"}
        {if $SHOW_BODY_HEADER}
          {assign var=LEFTPANELHIDE value=$USER_MODEL->get('leftpanelhide')}
          {include file='BodyHeaderMobile.tpl'|@vtemplate_path:$MODULE}
          <div class="mobileLeftPanel noSpaces">
            {include file='BodyLeft.tpl'|@vtemplate_path:$MODULE DEVICE=Mobile}
          </div>
          <div class="leftPanel noSpaces">
            {include file='BodyLeft.tpl'|@vtemplate_path:$MODULE DEVICE=Desktop}
          </div>
          {include file='BodyHeader.tpl'|@vtemplate_path:$MODULE}
        {/if}
      {/block}
      
      {block name="main_content"}
        <div class="basePanel noSpaces {if $LEFTPANELHIDE} menuOpen{/if}">
          <div class="mainBody {if AppConfig::module('Users','IS_VISIBLE_USER_INFO_FOOTER')}userInfoFooter{/if}">
            <div class="container-fluid bodyContent noSpaces">
              {block name="content"}
                <!-- Child templates define their content here -->
              {/block}
            </div> <!-- close bodyContent -->
          </div> <!-- close mainBody -->
        </div> <!-- close basePanel -->
      {/block}
      
    </div> <!-- close baseContainer -->
  </div> <!-- close container-fluid-main -->
  
  {block name="footer"}
    <div class="clearfix"></div>
    <input id="activityReminder" class="hide noprint" type="hidden" value="{$ACTIVITY_REMINDER}"/>
    {include file='Footer.tpl'}
  {/block}
  
  {block name="footer_scripts"}
    {include file='JSResources.tpl'|@vtemplate_path}
    {if \App\Debugger::isDebugBar()}
      {\App\Debugger::getDebugBar()->getJavascriptRenderer()->render()}
    {/if}
  {/block}
  
</body>
</html>
{/strip}
```

**Key Points:**
- Every `<div>` opened is closed in the SAME template ✓
- Blocks define insertion points for child templates
- Navigation is included but self-contained
- Structure is clear and maintainable

---

### 2. Child Template Examples

#### Dashboard View

**File**: `layouts/basic/modules/Home/DashBoard.tpl`

```smarty
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
  <div id="page">
    <div id="pjaxContainer" class="hide noprint"></div>
    <div class="bodyContents">
      <div class="mainContainer">
        <div class="contentsDiv col-md-12 marginLeftZero dashboardContainer">
          
          {include file="dashboards/DashBoardHeader.tpl"|vtemplate_path:$MODULE_NAME}
          
          <div class="dashboardViewContainer">
            {if count($DASHBOARD_TYPES) > 1}
              <ul class="nav nav-tabs massEditTabs selectDashboard">
                {foreach from=$DASHBOARD_TYPES item=DASHBOARD}
                  <li {if $CURRENT_DASHBOARD eq $DASHBOARD['dashboard_id']}class="active"{/if} data-id="{$DASHBOARD['dashboard_id']}">
                    <a data-toggle="tab"><strong>{$DASHBOARD['name']|t}</strong></a>
                  </li>
                {/foreach}
              </ul>
            {/if}
            
            {include file='dashboards/DashBoardButtons.tpl'|@vtemplate_path:$MODULE}
            {include file='dashboards/DashBoardContents.tpl'}
            
          </div> <!-- close dashboardViewContainer -->
        </div> <!-- close contentsDiv -->
      </div> <!-- close mainContainer -->
    </div> <!-- close bodyContents -->
  </div> <!-- close page -->
{/block}
```

**Every div opened is closed in this file ✓**

#### List View

**File**: `layouts/basic/modules/Base/ListView.tpl`

```smarty
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
  <div id="page">
    <div id="pjaxContainer" class="hide noprint"></div>
    <div class="bodyContents">
      <div class="mainContainer">
        <div class="contentsDiv">
          
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
          
          {include file="ListViewHeader.tpl"|vtemplate_path:$MODULE}
          {include file="ListViewContents.tpl"|vtemplate_path:$MODULE}
          
        </div> <!-- close contentsDiv -->
      </div> <!-- close mainContainer -->
    </div> <!-- close bodyContents -->
  </div> <!-- close page -->
{/block}
```

---

### 3. PHP View Class Changes

#### Before (Current):

```php
class DashBoard extends Index {
    
    public function preProcess(\App\Http\Vtiger_Request $request, $display = true) {
        parent::preProcess($request);
        $viewer = $this->getViewer($request);
        // Assign dashboard-specific data
        $viewer->assign('DASHBOARD_TYPES', $types);
        $viewer->view('dashboards/DashBoardPreProcess.tpl', $moduleName);
    }
    
    public function process(\App\Http\Vtiger_Request $request) {
        $viewer = $this->getViewer($request);
        $viewer->view('dashboards/DashBoardContents.tpl', $moduleName);
    }
    
    public function postProcess(\App\Http\Vtiger_Request $request) {
        parent::postProcess($request);
    }
}
```

#### After (With Blocks):

```php
class DashBoard extends Index {
    
    public function preProcess(\App\Http\Vtiger_Request $request, $display = true) {
        parent::preProcess($request, false); // Don't display yet
        $this->assignDashboardData($request);
    }
    
    protected function assignDashboardData(\App\Http\Vtiger_Request $request) {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        
        // All data assignment here
        $viewer->assign('DASHBOARD_TYPES', $this->getDashboardTypes());
        $viewer->assign('CURRENT_DASHBOARD', $this->getCurrentDashboard());
        $viewer->assign('WIDGETS', $this->getWidgets($request));
    }
    
    public function process(\App\Http\Vtiger_Request $request) {
        // Render single template that extends MainLayout
        $viewer = $this->getViewer($request);
        $viewer->view('DashBoard.tpl', 'Home');
    }
    
    public function postProcess(\App\Http\Vtiger_Request $request) {
        // Not needed - MainLayout handles footer
        // Just assign footer-specific data if needed
        $viewer = $this->getViewer($request);
        $viewer->assign('ACTIVITY_REMINDER', $request->getUser()->getCurrentUserActivityReminderInSeconds());
    }
}
```

**Key Changes:**
- `preProcess()`: Assigns data but doesn't render template (sets `$display = false`)
- `process()`: Renders ONE template that extends MainLayout
- `postProcess()`: Only assigns footer data, doesn't render (MainLayout handles it)

---

## Migration Strategy

### Phase 1: Setup (1-2 hours)

1. **Create MainLayout.tpl** - Extract all common structure from Header.tpl + Footer.tpl
2. **Update Body.tpl** - Ensure it's self-contained (already done)
3. **Test Smarty inheritance** - Create simple test template to verify {extends} works

### Phase 2: Proof of Concept (2-4 hours)

1. **Convert Dashboard view**:
   - Create `layouts/basic/modules/Home/DashBoard.tpl` extending MainLayout
   - Modify `src/Modules/Base/Views/DashBoard.php`
   - Test thoroughly
   
2. **Verify**:
   - Navigation renders correctly
   - Content displays properly
   - All divs balanced
   - No spacing issues
   - JavaScript works

### Phase 3: Convert Core Views (1-2 days)

Convert in this order (simple → complex):

1. **Index view** - Simple home page
2. **ListView** - Most common view type
3. **DetailView** - Record detail pages
4. **TreeRecords** - Tree-based lists
5. **FindDuplicates** - Specialized view

For each:
- Create `[ViewName]Layout.tpl` or `[ViewName].tpl` extending MainLayout
- Update PHP view class to render single template
- Test before moving to next

### Phase 4: Cleanup (2-4 hours)

1. Remove old templates:
   - `*PreProcess.tpl` files
   - `*PostProcess.tpl` files
   - Old `Header.tpl` (replaced by MainLayout)
   
2. Update module-specific overrides (Users/, Settings/, etc.)

3. Final comprehensive testing

### Phase 5: Documentation (1 hour)

Document the new pattern:
- How to create new views
- How to override blocks in modules
- Examples
- Update developer guide

---

## Detailed Example: ListView Conversion

### Step-by-Step ListView Migration

#### 1. Current Files (Before)

**ListViewPreProcess.tpl** (opens divs):
```smarty
{include file="Header.tpl"}
{assign var=LEFTPANELHIDE value=$USER_MODEL->get('leftpanelhide')}
<div class="basePanel noSpaces">
  <div class="mainBody">
    <div class="container-fluid bodyContent">
      <div id="page">
        <div class="bodyContents">
          <div class="mainContainer">
            <div class="contentsDiv">
              <div class="widget_header">...</div>
              {include file="ListViewHeader.tpl"}
```

**ListViewPostProcess.tpl** (empty, relies on IndexPostProcess)

**IndexPostProcess.tpl** (closes divs):
```smarty
            </div> <!-- contentsDiv -->
          </div> <!-- mainContainer -->
        </div> <!-- bodyContents -->
      </div> <!-- page -->
    </div> <!-- bodyContent -->
  </div> <!-- mainBody -->
</div> <!-- basePanel -->
```

#### 2. New Single File (After)

**ListView.tpl**:
```smarty
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
  <div id="page">
    <div id="pjaxContainer" class="hide noprint"></div>
    <div class="bodyContents">
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
          
        </div> <!-- close contentsDiv -->
      </div> <!-- close mainContainer -->
    </div> <!-- close bodyContents -->
  </div> <!-- close page -->
{/block}
```

**Result**: Every div opened is closed in the SAME file ✓

#### 3. PHP Changes

**Before** (`src/Modules/Base/Views/ListView.php`):
```php
public function preProcess(\App\Http\Vtiger_Request $request, $display = true) {
    parent::preProcess($request);
    // Data assignment
    $viewer = $this->getViewer($request);
    $viewer->assign('HEADER_LINKS', $this->getLinks($request));
    if ($display) {
        $viewer->view('ListViewPreProcess.tpl', $moduleName);
    }
}

public function process(\App\Http\Vtiger_Request $request) {
    $viewer = $this->getViewer($request);
    $viewer->view('ListViewContents.tpl', $moduleName);
}

public function postProcess(\App\Http\Vtiger_Request $request) {
    $viewer = $this->getViewer($request);
    $viewer->view('ListViewPostProcess.tpl', $moduleName);
    parent::postProcess($request);
}
```

**After**:
```php
public function preProcess(\App\Http\Vtiger_Request $request, $display = true) {
    parent::preProcess($request, false); // Assign common data, don't render
    $this->assignListViewData($request);
}

protected function assignListViewData(\App\Http\Vtiger_Request $request) {
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();
    
    // All list view specific data
    $viewer->assign('HEADER_LINKS', $this->getLinks($request));
    $viewer->assign('LISTVIEW_ENTRIES', $this->getListViewEntries($request));
    $viewer->assign('LISTVIEW_HEADERS', $this->getListViewHeaders($request));
    // ... etc
}

public function process(\App\Http\Vtiger_Request $request) {
    // Render single template that extends MainLayout
    $viewer = $this->getViewer($request);
    $viewer->view('ListView.tpl', $request->getModule());
}

public function postProcess(\App\Http\Vtiger_Request $request) {
    // Only assign footer data
    $viewer = $this->getViewer($request);
    $viewer->assign('ACTIVITY_REMINDER', $request->getUser()->getCurrentUserActivityReminderInSeconds());
}
```

---

## Advanced: Module-Specific Overrides

Modules can override specific blocks without duplicating entire layout:

**File**: `layouts/basic/modules/Contacts/ListView.tpl`

```smarty
{extends file="../../Base/ListView.tpl"}

{* Override just the navigation for Contacts *}
{block name="navigation"}
  {$smarty.block.parent}  {* Include parent block content *}
  <div class="contacts-specific-widget">
    Custom Contacts Navigation Widget
  </div>
{/block}

{* Or override entire content block *}
{block name="content"}
  <div id="page">
    <div class="contacts-custom-layout">
      {include file="ContactsListViewContents.tpl"}
    </div>
  </div>
{/block}
```

---

## Benefits Summary

### Code Quality
- ✅ **Self-contained templates**: Every file manages its own div structure
- ✅ **IDE validation**: Full HTML validation and autocomplete
- ✅ **Clear inheritance**: Easy to trace which template provides what
- ✅ **No div hunting**: Structure visible in single file

### Maintainability
- ✅ **Single source of truth**: MainLayout defines structure once
- ✅ **DRY principle**: No duplication of HTML structure
- ✅ **Easy refactoring**: Change MainLayout, all views update
- ✅ **Module overrides**: Can override specific sections without full duplication

### Development
- ✅ **Incremental migration**: Convert one view at a time
- ✅ **Backward compatible**: Old and new can coexist during migration
- ✅ **Easy testing**: Each template can be validated independently
- ✅ **Clear patterns**: New developers understand structure quickly

---

## Comparison vs Option 1 (Component Rendering)

| Aspect | Option 1: PHP Components | Option 2: Template Blocks |
|--------|-------------------------|---------------------------|
| **Template Changes** | Moderate | Extensive |
| **PHP Changes** | Extensive (all views) | Moderate (mostly data assignment) |
| **Migration** | All at once | Incremental |
| **Risk** | High | Medium |
| **Learning Curve** | Medium | Low |
| **Native to Framework** | No (custom system) | Yes (Smarty built-in) |
| **IDE Support** | Good | Excellent |
| **Flexibility** | Very High | High |
| **Performance** | Multiple render passes | Single pass (faster) |
| **Third-Party Compatibility** | Breaks | Can fallback |

**Winner**: Option 2 for incremental migration, native Smarty support, and lower risk

---

## Implementation Checklist

### Prerequisites
- [ ] Verify Smarty version supports {extends} (Smarty 3+)
- [ ] Backup current templates
- [ ] Create git branch for migration

### Core Implementation
- [ ] Create MainLayout.tpl with all blocks
- [ ] Convert Dashboard (proof of concept)
- [ ] Test Dashboard thoroughly
- [ ] Convert Index view
- [ ] Convert ListView
- [ ] Convert DetailView
- [ ] Convert remaining views

### Cleanup
- [ ] Remove old PreProcess templates
- [ ] Remove old PostProcess templates
- [ ] Update Body.tpl to be included component
- [ ] Update module overrides

### Testing
- [ ] All views render correctly
- [ ] Navigation works
- [ ] No JavaScript errors
- [ ] Responsive layouts work
- [ ] AJAX updates work (PJAX)

---

## Expected Effort

- **MainLayout creation**: 2-3 hours
- **Dashboard POC**: 2-3 hours
- **Each additional view**: 1-2 hours
- **Testing per view**: 30 minutes
- **Cleanup**: 2-3 hours
- **Total**: ~2-3 days for core views

---

## Final Result

After migration, adding a new view requires ONE self-contained template:

```smarty
{* MyNewView.tpl *}
{extends file="MainLayout.tpl"}

{block name="content"}
  <div id="page">
    {* My complete view structure *}
    <div class="myView">
      Content here
    </div>
  </div>
{/block}
```

**Every div opened is closed in the same file. Perfect maintainability.** ✓