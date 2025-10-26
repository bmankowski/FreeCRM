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

## Recommendations

### For Runtime Directory
- **Rename to**: `src/Foundation/` or `src/Core/`
- **BaseModel** → Move to `src/Foundation/Model.php`
- **Controllers** → Move to `src/Http/Controllers/`
- **Language_Handler** → Move to `src/Localization/Translator.php`
- **CRM_Viewer** → Move to `src/View/TemplateEngine.php`

### For Vtiger Module Base Classes
- **Current**: `App\Modules\Vtiger\Models\Record`
- **Proposed**: `App\Modules\Base\Models\Record`
- **Rationale**: Vtiger is vendor name, not architectural meaning

### For Settings Base Classes
- **Current**: `App\Modules\Settings\Vtiger\Models\Record`
- **Proposed**: `App\Modules\Settings\Base\Models\Record`
- **Rationale**: Consistency with module base pattern

