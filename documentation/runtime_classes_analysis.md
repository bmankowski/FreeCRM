# Runtime Folder Classes Analysis

## Overview
The `src/Runtime/` directory contains foundational classes inherited by legacy Vtiger code. This analysis documents current inheritance patterns to support refactoring efforts.

## Runtime Classes Inventory

### Core Classes
1. **BaseModel** - Data container with get/set methods
2. **Vtiger_Controller** - Abstract controller base
3. **Vtiger_Action_Controller** - AJAX/action handler base
4. **Vtiger_View_Controller** - View/page renderer base
5. **Vtiger_JavaScript** - JS asset management
6. **Vtiger_JsScript_Model** - JS script model
7. **Vtiger_CssScript_Model** - CSS script model
8. **Vtiger_Language_Handler** - Translation system
9. **Vtiger_Theme** - Theme management
10. **CRM_Viewer** - Template engine wrapper
11. **Yeti_Layout** - Layout utilities
12. **TemplateHelpers** - Template helper functions
13. **Globals** - Global variable helper (`vglobal()`)

## Inheritance Hierarchy

### 1. BaseModel Hierarchy
```
App\Runtime\BaseModel (13 direct inheritors)
  ├─ App\Modules\Vtiger\Models\Record (70+ modules inherit)
  │   ├─ App\Modules\Users\Models\Record
  │   ├─ App\Modules\Leads\Models\Record
  │   ├─ App\Modules\Accounts\Models\Record
  │   └─ ... (67+ other modules)
  │
  ├─ App\Modules\Vtiger\Models\Relation
  ├─ App\Modules\Calendar\Models\Export
  ├─ App\Modules\Events\Models\RecuringEvents
  ├─ App\Modules\Reports\Models\Folder
  ├─ App\Modules\Import\Models\Map
  ├─ App\Modules\Project\Models\RelationListView
  ├─ App\Modules\Campaigns\Models\RelationListView
  ├─ App\Modules\Products\Models\RelationListView
  ├─ App\Modules\IStorages\Models\RelationListView
  ├─ App\Modules\KnowledgeBase\Models\Tree
  ├─ App\Modules\HelpDesk\Models\DashBoard
  └─ App\Modules\OSSTimeControl\Models\Calendar
```

**Usage**: Data models for records, relations, and various utilities

### 2. Controller Hierarchy
```
App\Runtime\Vtiger_Controller
  └─ App\Runtime\Vtiger_Action_Controller (245 action classes)
      ├─ App\Modules\Vtiger\Actions\* (base actions)
      │   ├─ Save, Delete, MassDelete, BasicAjax
      │   ├─ RelationAjax, ExportData, Workflow
      │   └─ ... (30+ base actions)
      │
      ├─ App\Runtime\Vtiger_View_Controller (506 view classes)
      │   ├─ App\Modules\Vtiger\Views\* (base views)
      │   │   ├─ Detail, Edit, ListView, Index
      │   │   ├─ Popup, BasicModal, QuickCreateAjax
      │   │   └─ ... (40+ base views)
      │   │
      │   └─ Module-specific views (466+ inheriting from Vtiger views)
      │
      └─ Module-specific actions (215+ inheriting from Vtiger actions)
```

**Usage**: Request handling - Actions for AJAX, Views for page rendering

### 3. Settings Module Inheritance
```
App\Base
  └─ App\Modules\Settings\Vtiger\Models\Record (16+ settings modules)
      ├─ App\Modules\Settings\Currency\Models\Record
      ├─ App\Modules\Settings\Roles\Models\Record
      ├─ App\Modules\Settings\Groups\Models\Record
      └─ ... (13+ other settings modules)
```

**Usage**: Settings administration models

### 4. Script/Asset Hierarchy
```
App\Modules\Vtiger\Models\JsScript
  └─ App\Runtime\Vtiger_JavaScript
      ├─ App\Runtime\Vtiger_JsScript_Model
      └─ App\Runtime\Vtiger_CssScript_Model
```

**Usage**: JS/CSS asset path resolution

### 5. Standalone Classes
- **Vtiger_Language_Handler**: Translation system (static methods, no inheritance)
- **Vtiger_Theme**: Theme management utilities
- **CRM_Viewer**: Smarty template wrapper
- **Yeti_Layout**: Layout utilities
- **TemplateHelpers**: Template helpers
- **Globals**: `vglobal()` function

## Inheritance Statistics

| Pattern | Count | Purpose |
|---------|-------|---------|
| Classes extending BaseModel | 13 direct + 70+ via Vtiger\Record | Data models |
| Classes extending Vtiger_Action_Controller | 245 | AJAX/Action handlers |
| Classes extending Vtiger_View_Controller | 506 | Page views |
| Classes extending Vtiger module Actions | 215+ | Module actions |
| Classes extending Vtiger module Views | 466+ | Module views |
| Classes extending Vtiger module Models | 317 | Module models |
| Settings modules extending Settings\Vtiger | 16+ | Settings records |

**Total**: ~1,800+ classes depend on Runtime or Vtiger base classes

## Key Observations

1. **Deep Vtiger Dependencies**: Almost all modules inherit from `Vtiger` namespace classes as base implementation
2. **Dual Inheritance Pattern**: 
   - Runtime provides foundational abstracts (BaseModel, Controllers)
   - Vtiger modules provide default implementations
   - Specific modules extend Vtiger implementations
3. **Naming Confusion**: "Vtiger" appears both as:
   - Legacy vendor name in Runtime (`Vtiger_Controller`)
   - Module name (`App\Modules\Vtiger\Models\Record`)
4. **Static Utility Classes**: Language_Handler, Theme use static methods - not really "runtime" architecture
5. **Runtime Misuse**: Directory named "Runtime" but contains base models and controllers (architectural concerns)

## Refactoring Implications

### Primary Changes Needed
1. Move `Vtiger\Models\Record` → `Base\Models\Record`
2. Move `Settings\Vtiger\Models\Record` → `Settings\Base\Models\Record`
3. Move other Vtiger base implementations to Base namespace
4. Rename Runtime controllers to reflect purpose (not vendor)

### Affected Files
- **Module Records**: 70+ files extending `Vtiger\Models\Record`
- **Settings Records**: 16+ files extending `Settings\Vtiger\Models\Record`
- **Module Models**: 317+ files extending various Vtiger models
- **Actions**: 245 files extending action controllers
- **Views**: 506 files extending view controllers

### Migration Strategy
1. Create new namespace structure (`Base`, `Core`, etc.)
2. Maintain backward compatibility via class aliases
3. Gradually migrate Runtime classes to better-named locations
4. Update all inheritance references
5. Remove Vtiger naming where it refers to base implementation

## JavaScript/CSS Asset Management Classes - Detailed Analysis

### Class Hierarchy
```
App\Runtime\BaseModel
  └─ App\Modules\Vtiger\Models\JsScript
      └─ App\Runtime\Vtiger_JavaScript (path utilities)
          ├─ App\Runtime\Vtiger_JsScript_Model (JS model with data container)
          └─ App\Runtime\Vtiger_CssScript_Model (CSS model with data container)
```

### Purpose & Functionality

#### 1. `App\Modules\Vtiger\Models\JsScript` (Base Class)
**Location**: `src/Modules/Vtiger/Models/JsScript.php`
**Purpose**: Base data model for JavaScript includes
**Methods**:
- `getType()` - Returns script type (default: "text/javascript")
- `getSrc()` - Returns script source URL
- `getInstanceFromLinkObject()` - Factory method from \vtlib\Link

#### 2. `App\Runtime\Vtiger_JavaScript` (Path Resolution)
**Location**: `src/Runtime/Vtiger_JavaScript.php`
**Purpose**: Static utilities for finding JavaScript file paths
**Methods**:
- `getFilePath($fileName)` - Resolves JS file path
- `getBaseJavaScriptPath()` - Returns layout path (e.g., "layouts/basic")
**Extends**: `App\Modules\Vtiger\Models\JsScript`

#### 3. `App\Runtime\Vtiger_JsScript_Model` (JS Data Model)
**Location**: `src/Runtime/Vtiger_JsScript_Model.php`
**Purpose**: Chainable data container for JavaScript asset properties
**Properties**: `$data` array (src, type, etc.)
**Methods**:
- `set($key, $value)` - Chainable setter
- `get($key)` - Getter
- `getSrc()` - Returns src URL
- `getType()` - Returns script type
**Used in**: Template variables `$HEADER_SCRIPTS`, `$FOOTER_SCRIPTS`
**Usage**: ~50 occurrences across views

#### 4. `App\Runtime\Vtiger_CssScript_Model` (CSS Data Model)
**Location**: `src/Runtime/Vtiger_CssScript_Model.php`
**Purpose**: Chainable data container for CSS asset properties
**Properties**: `$data` array (href, rel, media, etc.)
**Methods**:
- `set($key, $value)` - Chainable setter
- `get($key)` - Getter
- `getHref()` - Returns CSS URL
- `getRel()` - Returns rel attribute (default: "stylesheet")
- `getMedia()` - Returns media attribute (default: "screen")
**Used in**: Template variable `$STYLES`
**Extends**: `App\Runtime\Vtiger_JavaScript` (for path utilities)

### How It Works (Asset Loading Flow)

#### Step 1: View Controller Defines Assets
```php
// In Vtiger_View_Controller::preProcessDisplay()
public function getHeaderScripts($request) {
    return $this->checkAndConvertJsScripts([
        'libraries.jquery.jquery',
        'libraries.bootstrap.bootstrap',
        // ... more scripts
    ]);
}

public function getHeaderCss($request) {
    return $this->checkAndConvertCssStyles([
        '~libraries/bootstrap3/css/bootstrap.css',
        'libraries.resources.styles',
        // ... more styles
    ]);
}
```

#### Step 2: Controller Creates Model Instances
```php
// checkAndConvertJsScripts() creates instances:
$jsScript = new \App\Runtime\Vtiger_JsScript_Model();
$jsScriptInstances[] = $jsScript->set('src', $layoutPath . '/' . $filePath);

// checkAndConvertCssStyles() creates instances:
$cssScriptModel = new \App\Runtime\Vtiger_CssScript_Model();
$cssStyleInstances[] = $cssScriptModel->set('href', $filePath);
```

#### Step 3: Models Assigned to Template
```php
// In Vtiger_View_Controller::preProcessDisplay()
$viewer->assign('HEADER_SCRIPTS', $this->getHeaderScripts($request)); // Array of JsScript_Model
$viewer->assign('STYLES', $this->getHeaderCss($request));              // Array of CssScript_Model
$viewer->assign('FOOTER_SCRIPTS', $this->getFooterScripts($request));  // Array of JsScript_Model
```

#### Step 4: Template Renders Assets
```smarty
{* In Header.tpl *}
{foreach item=cssModel from=$STYLES}
    <link rel="{$cssModel->getRel()}" href="{vresource_url($cssModel->getHref())}" />
{/foreach}

{foreach item=jsModel from=$HEADER_SCRIPTS}
    <script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}"></script>
{/foreach}
```

### Path Resolution Logic

The system supports multiple path formats:
1. **Literal path** (prefixed with `~`): `~libraries/jquery/jquery.js` → Direct file path
2. **Dotted notation**: `libraries.jquery.jquery` → Converted to `libraries/jquery/jquery.js`
3. **Layout fallback**: Checks current layout, then default layout
4. **Minification**: Auto-detects `.min.js` / `.min.css` versions when enabled

### Problems with Current Design

1. **Confusing Inheritance**: CSS model extends JavaScript utilities class (no logical reason)
2. **Naming Inconsistency**: 
   - `Vtiger_JavaScript` (static utilities) vs `Vtiger_JsScript_Model` (data model)
   - One has underscore naming, extends a namespaced class
3. **Redundant Hierarchy**: JsScript_Model re-implements get/set that already exists in BaseModel
4. **Misplaced Classes**: Asset models in Runtime instead of View/Asset namespace
5. **Poor Separation**: Path resolution mixed with data modeling

### Usage Statistics
- **Vtiger_JsScript_Model**: Referenced in ~50 files (all views)
- **Vtiger_CssScript_Model**: Referenced in ~40 files (mostly views, some dashboards)
- **Called by**: Every view controller that loads JS/CSS assets
- **Template usage**: Every page header/footer template

## Recommendations

### For JavaScript/CSS Classes
**Current Structure**:
```
Runtime/Vtiger_JavaScript.php (path utils)
Runtime/Vtiger_JsScript_Model.php (JS model)
Runtime/Vtiger_CssScript_Model.php (CSS model)
Modules/Vtiger/Models/JsScript.php (base model)
```

**Proposed Structure**:
```
View/Assets/AssetPathResolver.php (path utilities)
View/Assets/ScriptAsset.php (JS model)
View/Assets/StyleAsset.php (CSS model)
```

**Benefits**: Clear separation, better naming, logical namespace

### For Runtime Directory
- **Rename to**: `src/Foundation/` or `src/Core/`
- **BaseModel** → Move to `src/Foundation/Model.php`
- **Controllers** → Move to `src/Http/Controllers/`
- **Language_Handler** → Move to `src/Localization/Translator.php`
- **CRM_Viewer** → Move to `src/View/TemplateEngine.php`
- **Asset Classes** → Move to `src/View/Assets/`

### For Vtiger Module Base Classes
- **Current**: `App\Modules\Vtiger\Models\Record`
- **Proposed**: `App\Modules\Base\Models\Record`
- **Rationale**: Vtiger is vendor name, not architectural meaning

### For Settings Base Classes
- **Current**: `App\Modules\Settings\Vtiger\Models\Record`
- **Proposed**: `App\Modules\Settings\Base\Models\Record`
- **Rationale**: Consistency with module base pattern

