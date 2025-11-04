# Converting FreeCRM Settings Pages to MainLayout.tpl

## Overview

This document describes the process of converting FreeCRM Settings module views to use the MainLayout.tpl template system. This ensures consistent page structure with proper header, navigation, and footer across all Settings pages.

## Problem Statement

Some Settings pages were rendering without the main application layout (header, left navigation, footer), resulting in:
- Inconsistent UI/UX
- Missing navigation elements
- Broken layout when AJAX updates inject full page HTML into content areas

## Architecture

### Template Hierarchy

```
MainLayout.tpl (base template)
├── Header/Navigation/Footer
└── {block name="content"}
    └── [Module]Index.tpl (wrapper template)
        └── mainContainer
            └── contentsDiv
                └── {include file='[Module]Content.tpl'}
                    └── Actual page content
```

### Request Flow

**Initial Page Load (Non-AJAX):**
1. User requests page
2. View PHP class detects `!$request->isAjax()`
3. Returns `[Module]Index.tpl` which extends `MainLayout.tpl`
4. Full page rendered with header, navigation, footer

**AJAX Update:**
1. JavaScript makes AJAX request
2. View PHP class detects `$request->isAjax()`
3. Returns `[Module]Content.tpl` (content only, no layout wrapper)
4. JavaScript injects content into existing page's `contentsDiv`

## Conversion Process

### Step 1: Analyze Current Structure

Check the current View class to understand:
- Which template is being rendered
- If there's AJAX functionality
- If the template has layout wrappers

**Example locations:**
```
View: src/Modules/Settings/[Module]/Views/Index.php
Template: layouts/basic/modules/Settings/[Module]/Index.tpl
```

### Step 2: Update View PHP Class

Modify the `process()` method (or specific exposed methods) to detect AJAX and route appropriately:

```php
public function process(\App\Http\Vtiger_Request $request)
{
    // ... existing data preparation code ...
    
    $viewer = $this->getViewer($request);
    // ... viewer->assign() calls ...
    
    // Add AJAX detection
    if ($request->isAjax()) {
        // AJAX request - return content only
        $viewer->view('ContentTemplate.tpl', $qualifiedModuleName);
    } else {
        // Initial page load - return full page with MainLayout
        $viewer->view('IndexTemplate.tpl', $qualifiedModuleName);
    }
}
```

### Step 3: Create/Update Templates

#### Option A: Create New Content Template (Recommended)

If the existing template doesn't extend MainLayout:

**Create `[Module]Content.tpl`** - Content only, no MainLayout:
```smarty
{strip}
<!-- layouts/basic/modules/Settings/[Module]/[Module]Content.tpl -->
<div class="[module-specific-class]">
    <!-- All your existing content here -->
    <!-- Include breadcrumbs, forms, tables, etc. -->
</div>
<!--/layouts/basic/modules/Settings/[Module]/[Module]Content.tpl -->
{/strip}
```

**Create/Update `[Module]Index.tpl`** - Wrapper with MainLayout:
```smarty
{strip}
<!-- layouts/basic/modules/Settings/[Module]/[Module]Index.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
    <div class="mainContainer">
        <div class="contentsDiv">
            {include file='[Module]Content.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
        </div> <!-- close contentsDiv -->
    </div> <!-- close mainContainer -->
{/block}
<!--/layouts/basic/modules/Settings/[Module]/[Module]Index.tpl -->
{/strip}
```

#### Option B: Refactor Existing Template

If you already have content in the existing template:

1. Extract the content portion to `[Module]Content.tpl`
2. Update the original to extend MainLayout and include the content
3. Follow the structure from Option A

### Step 4: Clear Template Cache

After making template changes:
```bash
cd /home/bmankowski/projects/FreeCRM
rm -rf cache/templates_c/*
```

This is **CRITICAL** - Smarty caches compiled templates and won't pick up changes without clearing.

### Step 5: Test

1. **Test initial page load**: Visit the page directly - should show full layout
2. **Test AJAX updates**: If the page has dropdowns or dynamic content, test that AJAX updates work correctly
3. **Check browser console**: Look for JavaScript errors
4. **Check system logs**: `cache/logs/system.log` for PHP/template errors

## Common Patterns

### Pattern 1: Simple Single-View Module

**File Structure:**
```
src/Modules/Settings/[Module]/Views/Index.php
layouts/basic/modules/Settings/[Module]/Index.tpl         (extends MainLayout)
layouts/basic/modules/Settings/[Module]/Content.tpl       (content only)
```

**View PHP:**
```php
public function process(\App\Http\Vtiger_Request $request)
{
    // Data preparation
    $viewer = $this->getViewer($request);
    $viewer->assign('DATA', $data);
    
    if ($request->isAjax()) {
        $viewer->view('Content.tpl', $qualifiedModuleName);
    } else {
        $viewer->view('Index.tpl', $qualifiedModuleName);
    }
}
```

### Pattern 2: Multi-Mode Module (Like LayoutEditor)

Module with different modes (e.g., field layout, related list layout):

**File Structure:**
```
src/Modules/Settings/LayoutEditor/Views/Index.php
layouts/basic/modules/Settings/LayoutEditor/Index.tpl              (wrapper for FieldLayout)
layouts/basic/modules/Settings/LayoutEditor/FieldLayout.tpl        (content)
layouts/basic/modules/Settings/LayoutEditor/RelatedListIndex.tpl   (wrapper for RelatedList)
layouts/basic/modules/Settings/LayoutEditor/RelatedList.tpl        (content)
```

**View PHP with Exposed Methods:**
```php
public function __construct()
{
    $this->exposeMethod('showFieldLayout');
    $this->exposeMethod('showRelatedListLayout');
}

public function showFieldLayout(\App\Http\Vtiger_Request $request)
{
    // Data preparation
    $viewer = $this->getViewer($request);
    $viewer->assign('DATA', $data);
    
    if ($request->isAjax()) {
        $viewer->view('FieldLayout.tpl', $qualifiedModule);
    } else {
        $viewer->view('Index.tpl', $qualifiedModule);
    }
}

public function showRelatedListLayout(\App\Http\Vtiger_Request $request)
{
    // Data preparation
    $viewer = $this->getViewer($request);
    $viewer->assign('DATA', $data);
    
    if ($request->isAjax()) {
        $viewer->view('RelatedList.tpl', $qualifiedModule);
    } else {
        $viewer->view('RelatedListIndex.tpl', $qualifiedModule);
    }
}
```

### Pattern 3: Module with JavaScript AJAX Reloading

When JavaScript reloads content via AJAX (common pattern):

**JavaScript (example from Widgets.js):**
```javascript
reloadWidgets: function () {
    var thisInstance = this;
    var params = {
        module: 'Widgets',
        view: 'Index',
        parent: 'Settings',
        source: $("input[name='tabid']").val()
    };
    
    AppConnector.request(params).then(function (data) {
        // IMPORTANT: Inject into contentsDiv, not the whole page
        var container = jQuery('div.contentsDiv').html(data);
        thisInstance.registerEvents(container);
    });
}
```

**Key Points:**
- AJAX response should contain only content (from `Content.tpl`)
- Inject into `div.contentsDiv` selector
- Re-register event handlers after injection

## Completed Conversions

### Example 1: Settings Widgets

**Files Modified:**
- `src/Modules/Settings/Widgets/Views/Index.php`
- `layouts/basic/modules/Settings/Widgets/Index.tpl` (extends MainLayout)
- `layouts/basic/modules/Settings/Widgets/IndexContent.tpl` (created - content only)

**URL:** `http://localhost/index.php?module=Widgets&parent=Settings&view=Index&block=2&fieldid=58`

**Features:**
- Initial page load: Full MainLayout
- Module selector dropdown: AJAX reloads content only

### Example 2: Settings LayoutEditor

**Files Modified:**
- `src/Modules/Settings/LayoutEditor/Views/Index.php`
- `layouts/basic/modules/Settings/LayoutEditor/Index.tpl` (already extended MainLayout)
- `layouts/basic/modules/Settings/LayoutEditor/RelatedListIndex.tpl` (created)

**URLs:**
- Field Layout: `http://localhost/index.php?module=LayoutEditor&parent=Settings&view=Index`
- Related List: `http://localhost/index.php?module=LayoutEditor&parent=Settings&view=Index&mode=showRelatedListLayout&block=2&fieldid=41`

**Features:**
- Multiple modes (field layout, related list)
- Module selector: AJAX reloads content

### Example 3: Custom Record Numbering

**Files Modified:**
- `src/Modules/Settings/Base/Views/CustomRecordNumbering.php`
- `layouts/basic/modules/Settings/Base/CustomRecordNumberingIndex.tpl` (created)
- `layouts/basic/modules/Settings/Base/CustomRecordNumbering.tpl` (kept as content)

**URL:** `http://localhost/index.php?module=Vtiger&parent=Settings&view=CustomRecordNumbering&block=2&fieldid=22`

**Features:**
- Simple single-view page
- Module selector: Potential AJAX updates

## Troubleshooting

### Issue: Page Shows Duplicate Headers/Navigation

**Symptom:** Header and navigation appear twice, or nested inside each other.

**Cause:** AJAX response is returning full page (with MainLayout) instead of content only.

**Solution:**
1. Verify `$request->isAjax()` check is in place
2. Ensure AJAX requests return the content template, not the index template
3. Clear template cache

### Issue: No Layout on Initial Page Load

**Symptom:** Page loads without header, navigation, or footer.

**Cause:** Non-AJAX request is returning content template instead of index template.

**Solution:**
1. Verify the logic: `if ($request->isAjax())` should return content, `else` should return index
2. Ensure Index.tpl extends MainLayout.tpl
3. Clear template cache

### Issue: AJAX Updates Break Layout

**Symptom:** After AJAX update, layout is broken or content appears in wrong place.

**Cause:** JavaScript is injecting content into wrong DOM element, or AJAX response contains full page.

**Solution:**
1. Check JavaScript injection target - should be `.contentsDiv` or similar content container
2. Verify AJAX response contains only content (no MainLayout wrapper)
3. Check browser console for JavaScript errors
4. Inspect Network tab to see what HTML is returned

### Issue: Template Not Found Error

**Symptom:** Error like "Unable to load template file 'IndexContent.tpl'"

**Cause:** Template file doesn't exist or is in wrong location.

**Solution:**
1. Verify file exists at correct path: `layouts/basic/modules/Settings/[Module]/[Template].tpl`
2. Check file permissions (should be readable)
3. Verify `$qualifiedModuleName` variable is correctly set
4. Clear template cache

### Issue: Changes Not Appearing

**Symptom:** Made changes to template but they don't show up.

**Cause:** Smarty template cache is stale.

**Solution:**
```bash
rm -rf cache/templates_c/*
```

Always clear cache after template changes!

## Template Best Practices

### 1. Consistent Naming Convention

- **Index templates** (with MainLayout): `[Feature]Index.tpl`
- **Content templates** (AJAX-ready): `[Feature]Content.tpl` or just `[Feature].tpl`

### 2. Always Include Comments

Mark your templates clearly:
```smarty
{strip}
<!-- layouts/basic/modules/Settings/[Module]/[Template].tpl -->
{* Template content *}
<!--/layouts/basic/modules/Settings/[Module]/[Template].tpl -->
{/strip}
```

### 3. DRY Principle - Don't Repeat Content

Use includes to avoid duplication:
```smarty
{* Good: Single source of truth *}
{include file='Content.tpl'|@vtemplate_path:$QUALIFIED_MODULE}

{* Bad: Duplicating content in multiple templates *}
```

### 4. Preserve Existing Structure

When extracting content:
- Keep all existing classes and IDs
- Maintain HTML structure
- Preserve all Smarty variables and logic
- Don't change indentation unnecessarily (makes diffs cleaner)

## Checklist for Conversion

Use this checklist when converting a Settings page:

- [ ] Identify current View class and templates
- [ ] Backup original files (if needed)
- [ ] Update View PHP class with `isAjax()` check
- [ ] Create/update Index template to extend MainLayout
- [ ] Create/update Content template with actual content
- [ ] Index template includes Content template (DRY)
- [ ] Clear template cache (`rm -rf cache/templates_c/*`)
- [ ] Test initial page load (non-AJAX)
- [ ] Test AJAX updates (if applicable)
- [ ] Check browser console for errors
- [ ] Check system logs for errors
- [ ] Verify breadcrumbs work correctly
- [ ] Verify navigation menu highlights correct item
- [ ] Test on different screen sizes (responsive)

## File Naming Reference

For a module named `Example`:

```
View Controller:
  src/Modules/Settings/Example/Views/Index.php

Templates:
  layouts/basic/modules/Settings/Example/Index.tpl           (extends MainLayout)
  layouts/basic/modules/Settings/Example/Content.tpl         (content only)
  
For multi-mode views:
  layouts/basic/modules/Settings/Example/FeatureAIndex.tpl   (wrapper)
  layouts/basic/modules/Settings/Example/FeatureA.tpl        (content)
  layouts/basic/modules/Settings/Example/FeatureBIndex.tpl   (wrapper)
  layouts/basic/modules/Settings/Example/FeatureB.tpl        (content)
```

## Additional Notes

### Parent Class

Most Settings views extend `\App\Modules\Settings\Base\Views\Index`, which provides:
- Permission checking (`checkPermission()`)
- Breadcrumb building (`buildBreadcrumbs()`)
- Settings menu setup (`preProcess()`)
- Common asset loading

### JavaScript Considerations

When AJAX reloads content:
1. Event handlers need to be re-registered
2. Select2 dropdowns need re-initialization
3. Custom widgets need re-initialization

Example pattern:
```javascript
registerEvents: function(container) {
    var thisInstance = this;
    
    // Re-initialize select2
    app.showSelect2ElementView(container.find('.select2'));
    
    // Re-bind event handlers
    container.find('.someButton').click(function() {
        // handler
    });
}
```

### CSS Considerations

The MainLayout includes standard CSS for:
- `.mainContainer` - Main content wrapper
- `.contentsDiv` - Content area where AJAX injects
- `.widget_header` - Standard header with breadcrumbs

Ensure your content templates use these classes appropriately.

## Future Improvements

Consider these enhancements:

1. **Create a base Settings view helper** that automatically handles AJAX detection
2. **Standardize naming conventions** across all Settings modules
3. **Create template generator** script to automate conversion
4. **Add automated tests** to verify layouts render correctly
5. **Document all Settings pages** that need conversion

## References

- Main Layout: `layouts/basic/modules/Base/MainLayout.tpl`
- Settings Base View: `src/Modules/Settings/Base/Views/Index.php`
- Settings Base Layout: `layouts/basic/modules/Settings/Base/Index.tpl`
- Completed Examples: Widgets, LayoutEditor, CustomRecordNumbering (see above)

---

**Last Updated:** 2025-11-04  
**Version:** 1.0  
**Author:** FreeCRM Development Team

