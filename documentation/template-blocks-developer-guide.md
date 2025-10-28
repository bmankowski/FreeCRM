# FreeCRM Template Blocks Developer Guide

## Overview

FreeCRM now uses Smarty's template inheritance system with `{extends}` and `{block}` to ensure all opened divs are closed within the same template file. This eliminates cross-file dependencies and improves maintainability.

## Architecture

### MainLayout.tpl

The master layout template (`layouts/basic/modules/Base/MainLayout.tpl`) defines the complete HTML structure with strategic block insertion points:

```smarty
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
  <!-- Your view-specific content here -->
{/block}
```

### Key Blocks Available

- **`head`** - Complete head section with meta, styles, scripts
- **`title`** - Page title
- **`meta`** - Meta tags
- **`styles`** - CSS stylesheets
- **`header_scripts`** - JavaScript files in head
- **`body_attrs`** - Body tag attributes
- **`body_start`** - Hidden configuration divs
- **`announcements`** - Announcement banner
- **`navigation`** - Left panel and header navigation
- **`main_content`** - Wrapper containing basePanel, mainBody, bodyContent
- **`content`** - Main content area (most commonly overridden)
- **`footer`** - Footer section
- **`footer_scripts`** - JavaScript resources at end of body

## Creating a New View

### 1. Template File

Create a template that extends `MainLayout.tpl` and overrides the `content` block:

**File**: `layouts/basic/modules/YourModule/YourView.tpl`

```smarty
{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
  <div id="page">
    <div id="pjaxContainer" class="hide noprint"></div>
    <div class="bodyContents">
      <div class="mainContainer">
        <div class="contentsDiv">
          
          {* Your view content here *}
          <div class="yourViewContent">
            <!-- Content -->
          </div>
          
        </div> <!-- close contentsDiv -->
      </div> <!-- close mainContainer -->
    </div> <!-- close bodyContents -->
  </div> <!-- close page -->
{/block}
{/strip}
```

**Important**: Every opened div MUST be closed in the same template!

### 2. PHP View Class

Update your view class to render the single template:

```php
class YourView extends \App\Modules\Base\Views\Index
{
    public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request, false);
        // Assign all your data here
        $viewer = $this->getViewer($request);
        $viewer->assign('YOUR_DATA', $this->getData($request));
        // MainLayout handles rendering, no separate preProcess template needed
    }

    public function process(\App\Http\Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        // Render single template that extends MainLayout
        $viewer->view('YourView.tpl', $moduleName);
    }

    public function postProcess(\App\Http\Vtiger_Request $request)
    {
        // MainLayout handles footer rendering, no separate postProcess template needed
        parent::postProcess($request);
    }
}
```

## Pattern Examples

### Example 1: Simple List View

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
                {* Action buttons *}
              </div>
            </div>
          </div>
          
          {* List content *}
          {include file="ListViewHeader.tpl"|vtemplate_path:$MODULE}
          {include file="ListViewContents.tpl"|vtemplate_path:$MODULE}
          
        </div>
      </div>
    </div>
  </div>
{/block}
```

### Example 2: Dashboard View

```smarty
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
  <div id="page">
    <div id="pjaxContainer" class="hide noprint"></div>
    <div class="bodyContents">
      <div class="mainContainer">
        <div class="contentsDiv col-md-12 marginLeftZero" id="centerPanel">
          
          {include file="dashboards/DashBoardHeader.tpl"|vtemplate_path:$MODULE_NAME}
          
          <div class="dashboardViewContainer">
            {* Dashboard tabs *}
            {if count($DASHBOARD_TYPES) > 1}
              <ul class="nav nav-tabs massEditTabs selectDashboard">
                {foreach from=$DASHBOARD_TYPES item=DASHBOARD}
                  <li {if $CURRENT_DASHBOARD eq $DASHBOARD['dashboard_id']}class="active"{/if}>
                    <a data-toggle="tab"><strong>{$DASHBOARD['name']|t}</strong></a>
                  </li>
                {/foreach}
              </ul>
            {/if}
            
            {* Dashboard widgets *}
            {include file='dashboards/DashBoardButtons.tpl'|@vtemplate_path:$MODULE}
            {include file='dashboards/DashBoardContents.tpl'|@vtemplate_path:$MODULE}
            
          </div> <!-- close dashboardViewContainer -->
        </div> <!-- close contentsDiv -->
      </div> <!-- close mainContainer -->
    </div> <!-- close bodyContents -->
  </div> <!-- close page -->
{/block}
```

## Handling AJAX Requests

For views that have AJAX modes (like DetailView), check for AJAX requests and return partial content:

```php
public function process(\App\Http\Vtiger_Request $request)
{
    $mode = $request->getMode();
    $viewer = $this->getViewer($request);
    $moduleName = $request->getModule();
    
    if (!empty($mode)) {
        // AJAX mode requests - return partial content
        echo $this->invokeExposedMethod($mode, $request);
        return;
    }
    
    if ($request->isAjax()) {
        // AJAX list refresh - return only contents
        $viewer->view('YourViewContents.tpl', $moduleName);
    } else {
        // Initial page load - render full page with MainLayout
        $viewer->view('YourView.tpl', $moduleName);
    }
}
```

## Module-Specific Overrides

Modules can override specific blocks without duplicating the entire layout:

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
```

Or override the entire content block:

```smarty
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
  <div id="page">
    <div class="contacts-custom-layout">
      {* Completely custom layout for Contacts *}
    </div>
  </div>
{/block}
```

## Common Patterns

### Self-Contained Divs

✅ **CORRECT** - All divs closed in same file:
```smarty
{block name="content"}
  <div id="page">
    <div class="bodyContents">
      <div class="mainContainer">
        <div class="contentsDiv">
          <!-- Content -->
        </div> <!-- close contentsDiv -->
      </div> <!-- close mainContainer -->
    </div> <!-- close bodyContents -->
  </div> <!-- close page -->
{/block}
```

❌ **WRONG** - Divs opened but not closed:
```smarty
{block name="content"}
  <div id="page">
    <div class="bodyContents">
      <div class="mainContainer">
        <div class="contentsDiv">
          <!-- Content -->
          {* Missing closing divs! *}
{/block}
```

### Including Sub-Templates

Sub-templates should also be self-contained:

```smarty
{* HeaderSection.tpl *}
<div class="widget_header row">
  <div class="col-sm-6">
    {include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
  </div>
  <div class="col-sm-6">
    {* Actions *}
  </div>
</div> <!-- close widget_header -->
```

Then include it:

```smarty
{block name="content"}
  <div id="page">
    {include file="HeaderSection.tpl"|vtemplate_path:$MODULE}
    {* More content *}
  </div>
{/block}
```

## Benefits

1. **Self-contained templates** - Every file manages its own div structure
2. **IDE validation** - Full HTML validation and autocomplete
3. **Clear inheritance** - Easy to trace which template provides what
4. **No div hunting** - Structure visible in single file
5. **Single source of truth** - MainLayout defines structure once
6. **DRY principle** - No duplication of HTML structure
7. **Easy refactoring** - Change MainLayout, all views update
8. **Module overrides** - Can override specific sections without full duplication

## Migration Checklist

When migrating an existing view:

- [ ] Create new template extending `MainLayout.tpl`
- [ ] Override `{block name="content"}` with complete structure
- [ ] Ensure all opened divs are closed in same file
- [ ] Update PHP view class `preProcess()` to prevent old template rendering
- [ ] Update PHP view class `process()` to render new template
- [ ] Update PHP view class `postProcess()` to not render old template
- [ ] Test view thoroughly (navigation, content, JavaScript, AJAX)
- [ ] Remove old PreProcess/PostProcess templates

## Troubleshooting

### Problem: Navigation not showing

**Solution**: Check that `$SHOW_BODY_HEADER` is set to true in the viewer.

### Problem: Divs not balanced

**Solution**: Use your IDE's HTML validator or browser dev tools to check for unclosed divs. Every `<div>` must have a corresponding `</div>` in the same template file.

### Problem: AJAX not working

**Solution**: Check that your `process()` method distinguishes between AJAX and non-AJAX requests and returns appropriate content.

### Problem: Styles not loading

**Solution**: Ensure your view class's `getHeaderCss()` method returns all necessary CSS files.

## Further Reading

- Smarty Template Inheritance: https://www.smarty.net/docs/en/advanced.features.template.inheritance.tpl
- FreeCRM Template System: `/documentation/tpl-total-refactor-plan.md`

