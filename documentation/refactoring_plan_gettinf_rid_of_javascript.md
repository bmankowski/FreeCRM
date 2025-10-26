# Refactor JavaScript/CSS Asset Classes

## Current Problems

The asset management has a confusing 4-level inheritance:

```
BaseModel → Vtiger\Models\JsScript → Vtiger_JavaScript → JsScript_Model/CssScript_Model
```

Issues:

- CSS model extends JavaScript utilities (illogical)
- Mixed naming: `Vtiger_JavaScript`, `Vtiger_JsScript_Model`
- Path resolution mixed with data modeling
- Located in Runtime instead of View namespace

## Solution

Replace with 3 simple, standalone classes in `src/View/Assets/`:

### 1. AssetPathResolver

**Purpose**: Static utilities for resolving asset file paths

**Methods**:

- `getFilePath($fileName)` - Find JS/CSS file path
- `getBaseLayoutPath()` - Get layout directory

### 2. ScriptAsset

**Purpose**: Data model for JS assets

**Properties**: src, type

**Methods**: `getSrc()`, `getType()`, chainable `set()`

### 3. StyleAsset

**Purpose**: Data model for CSS assets

**Properties**: href, rel, media

**Methods**: `getHref()`, `getRel()`, `getMedia()`, chainable `set()`

## Implementation Steps

### Create New Classes

1. Create `src/View/Assets/AssetPathResolver.php` with static path resolution methods
2. Create `src/View/Assets/ScriptAsset.php` as simple data container
3. Create `src/View/Assets/StyleAsset.php` as simple data container

### Update View Controller

4. Update `src/Runtime/Vtiger_View_Controller.php`:

   - Replace `new \App\Runtime\Vtiger_JsScript_Model()` with `new \App\View\Assets\ScriptAsset()`
   - Replace `new \App\Runtime\Vtiger_CssScript_Model()` with `new \App\View\Assets\StyleAsset()`
   - Use `AssetPathResolver::getFilePath()` for path resolution

### Update Templates

5. Update Smarty templates (headers/footers) if they call old class methods

### Find and Replace References

6. Search codebase for all references to:

   - `Vtiger_JsScript_Model` (~50 files)
   - `Vtiger_CssScript_Model` (~40 files)
   - `Vtiger_JavaScript` 

7. Replace with new class names using sed

### Test and Clean Up

8. Test application with curl commands to verify assets load correctly
9. Delete old classes after confirming all references updated:

   - `src/Runtime/Vtiger_JavaScript.php`
   - `src/Runtime/Vtiger_JsScript_Model.php`
   - `src/Runtime/Vtiger_CssScript_Model.php`
   - `src/Modules/Vtiger/Models/JsScript.php`

## Files Changed

**New files**:

- `src/View/Assets/AssetPathResolver.php`
- `src/View/Assets/ScriptAsset.php`
- `src/View/Assets/StyleAsset.php`

**Modified**:

- `src/Runtime/Vtiger_View_Controller.php` (main usage point)
- ~50 view files referencing JsScript_Model
- ~40 view files referencing CssScript_Model
- Template files in `layouts/*/modules/`

## Benefits

- Clear, simple class names
- No confusing inheritance
- Logical namespace (View/Assets)
- Separated concerns (path resolution vs data)
- Modern, maintainable code
