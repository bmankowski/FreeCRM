# vtlib Migration Principles

## Executive Summary

This document outlines the architectural principles and migration strategy for refactoring **vtlib** in FreeCRM. vtlib is the legacy module/field/schema management library inherited from Vtiger CRM. While functional, it represents technical debt that conflicts with modern architecture patterns.

**Document Version:** 1.0  
**Analysis Date:** October 25, 2025  
**Status:** Planning Phase

---

## Table of Contents

1. [Current Architecture Analysis](#current-architecture-analysis)
2. [Problems with Current vtlib](#problems-with-current-vtlib)
3. [Modern Architecture Vision](#modern-architecture-vision)
4. [Migration Strategy](#migration-strategy)
5. [Proposed New Architecture](#proposed-new-architecture)
6. [Phase-by-Phase Implementation Plan](#phase-by-phase-implementation-plan)
7. [Risk Assessment](#risk-assessment)
8. [Decision Tree](#decision-tree)
9. [Open Questions](#open-questions)

---

## Current Architecture Analysis

### What is vtlib?

vtlib is a **module lifecycle management library** that provides APIs for:

1. **Module Management**
   - Creating, updating, deleting modules
   - Module activation/deactivation
   - Module metadata (name, label, version, type)

2. **Field Management**
   - Adding/removing fields
   - Field types (uitypes)
   - Picklist management
   - Field relationships (UIType 10)

3. **Block Management**
   - Grouping fields into logical blocks
   - Block sequences

4. **Schema Management**
   - Creating database tables
   - Managing table relationships
   - Entity tables setup

5. **Package Management**
   - Module import/export
   - ZIP package handling
   - Manifest files

6. **Relationship Management**
   - Related lists
   - Many-to-many relations
   - Module dependencies

7. **System Integration**
   - Link management (LISTVIEW, DETAILVIEW)
   - Filter/CustomView setup
   - Webservice initialization
   - Profile/permissions setup

### Current File Structure

```
/vtlib/
├── Vtiger/
│   ├── Module.php          # Main module API
│   ├── ModuleBasic.php     # Core module logic
│   ├── Field.php           # Field API
│   ├── FieldBasic.php      # Core field logic
│   ├── Block.php           # Block management
│   ├── Filter.php          # Custom filters
│   ├── Link.php            # UI links
│   ├── Cron.php            # Cron task management
│   ├── PackageImport.php   # Module import
│   ├── PackageExport.php   # Module export
│   ├── PackageUpdate.php   # Module updates
│   ├── Functions.php       # Utility functions
│   ├── Utils.php           # Additional utilities
│   ├── Deprecated.php      # Legacy compatibility
│   └── ...
├── ModuleDir/              # Template for new modules
└── thirdparty/             # ZIP/Unzip utilities
```

### Current Usage Pattern

```php
// Creating a module (Settings -> Module Manager)
$module = new vtlib\Module();
$module->name = 'CustomModule';
$module->label = 'Custom Module';
$module->save();
$module->initTables();

// Adding a block
$block = new vtlib\Block();
$block->label = 'LBL_BASIC_INFORMATION';
$module->addBlock($block);

// Adding a field
$field = new vtlib\Field();
$field->name = 'fieldname';
$field->label = 'Field Label';
$field->uitype = 1;
$field->typeofdata = 'V~M';
$block->addField($field);

// Setting up relationships
$module->setRelatedList($otherModule, 'Related Items', ['ADD', 'SELECT']);

// Initializing webservice
$module->initWebservice();
```

### Current Integration Points

vtlib is used in:

1. **Module Manager** (`src/Modules/Settings/ModuleManager/`)
   - Creating new modules
   - Importing/exporting modules
   - Module activation/deactivation

2. **Layout Editor** (`src/Modules/Settings/LayoutEditor/`)
   - Adding/editing fields
   - Managing blocks
   - Field relationships

3. **Module Installation Scripts**
   - Many modules have `ModuleName.php` files with `vtlib_handler` method
   - Event hooks: `module.postinstall`, `module.preuninstall`, etc.

4. **Import/Export**
   - Package management
   - Manifest handling

5. **Direct Database Access**
   - Uses raw SQL/Yii2 DB queries
   - Modifies core tables directly

### Database Schema

vtlib manages these core tables:

```sql
-- Module metadata
vtiger_tab                  -- Modules registry
vtiger_tab_info            -- Module version info

-- Field metadata
vtiger_field               -- Fields registry
vtiger_fieldmodulerel      -- Field-module relationships (UIType 10)
vtiger_blocks              -- Field blocks

-- Relationships
vtiger_relatedlists        -- Module relationships

-- Filters
vtiger_customview          -- Custom filters

-- Links
vtiger_links               -- UI links

-- Picklists
vtiger_picklist            -- Picklist registry
vtiger_role2picklist       -- Role-based picklist access
vtiger_<fieldname>         -- Individual picklist tables

-- Webservices
vtiger_ws_entity           -- Webservice entities
vtiger_ws_entity_tables    -- Entity table mappings

-- Profiles
vtiger_profile2field       -- Field-profile permissions
vtiger_profile2standardpermissions -- Module permissions
```

---

## Problems with Current vtlib

### 1. **Architectural Issues**

#### A. **Violates Single Responsibility Principle**
- Module class does: module management, field management, DB schema, events, webservices, permissions
- 300+ lines of complex logic in single classes

#### B. **Tight Coupling**
- Direct database manipulation
- Hard-coded table/column names
- Global state dependencies
- No dependency injection

#### C. **Namespace Conflicts**
- Lives in `vtlib\` namespace
- Extends to modern `\App\Modules\Vtiger\Models\Module`
- Creates inheritance confusion

#### D. **Mixed Concerns**
```php
// vtlib\Module does too much:
$module->save();                    // Persistence
$module->initTables();              // Schema management
$module->setRelatedList();          // Relationships
$module->addLink();                 // UI configuration
$module->initWebservice();          // API setup
$module->setDefaultSharing();       // Security
Module::fireEvent();                // Events
```

### 2. **Code Quality Issues**

#### A. **Poor Testability**
- Heavy database dependencies
- Static methods everywhere
- No interfaces/contracts
- Hard to mock

#### B. **Limited Error Handling**
```php
// Current pattern - minimal error handling
public function save() {
    $db->createCommand()->insert('vtiger_tab', $data)->execute();
    // No validation, no rollback on partial failure
}
```

#### C. **No Transactions**
- Multi-step operations not atomic
- Partial failures leave database in inconsistent state

#### D. **Legacy Code Patterns**
```php
// Direct SQL in business logic
$db->createCommand()->insert('vtiger_tab', [...])->execute();

// Magic methods without documentation
public function __getNextRelatedListSequence()

// Hard-coded strings
if ($modulename == 'Calendar') $modulename = 'Activity';
```

### 3. **Maintenance Issues**

#### A. **Duplicate Logic**
- `ModuleBasic` and `Module` split
- `FieldBasic` and `Field` split
- Similar logic in `\App\Module` class
- Similar logic in `\App\Modules\Vtiger\Models\Module`

#### B. **Unclear Responsibilities**
- When to use `vtlib\Module` vs `\App\Modules\Vtiger\Models\Module`?
- When to use `\App\Module` vs `vtlib\Module`?
- Three different Module classes!

#### C. **Documentation Gaps**
- No clear API documentation
- Event system undocumented
- Unclear extension points

### 4. **Modern Development Conflicts**

#### A. **PSR-4 Namespace Issues**
```php
// Modern code uses:
\App\Modules\Vtiger\Models\Module

// But extends:
\vtlib\Module

// Which uses:
\vtlib\Field
```

#### B. **Type Safety**
- No type hints
- No return types
- Mixed return values (bool/array/null)

#### C. **No Validation Layer**
```php
// Current - no validation
$module->name = 'anything!@#$%'; // No sanitization
$field->uitype = 999; // No validation
```

#### D. **Event System Primitive**
```php
// Hard-coded event checking
if (method_exists($instance, 'vtlib_handler')) {
    $instance->vtlib_handler($modulename, $eventType);
}
```

### 5. **Security Concerns**

#### A. **SQL Injection Risks**
While using prepared statements, some dynamic query building is risky

#### B. **No Permission Checks**
```php
// vtlib operations don't check user permissions
$module->delete(); // No admin check!
```

#### C. **File System Access**
```php
// Direct file operations
file_put_contents($targetPath, $fileContent);
// No validation, no sandboxing
```

---

## Modern Architecture Vision

### Goals

1. **Separation of Concerns**
   - Module management
   - Schema management
   - Field management
   - Relationship management
   - Package management

2. **SOLID Principles**
   - Single responsibility
   - Open/closed
   - Liskov substitution
   - Interface segregation
   - Dependency inversion

3. **Modern PHP Practices**
   - Type hints
   - Return types
   - Interfaces
   - Dependency injection
   - Event dispatching

4. **Testability**
   - Unit testable
   - Mockable dependencies
   - No static dependencies

5. **Backward Compatibility**
   - Gradual migration
   - Facade pattern
   - Deprecation warnings

---

## Migration Strategy

**Approach:** Create modern API that wraps vtlib internally, gradually migrate

## Proposed New Architecture

### Directory Structure

```
/src/
├── ModuleManagement/              # New namespace
│   ├── Services/
│   │   ├── ModuleService.php      # Main service
│   │   ├── FieldService.php       # Field operations
│   │   ├── BlockService.php       # Block operations
│   │   ├── RelationService.php    # Relationships
│   │   └── SchemaService.php      # Database schema
│   ├── Repositories/
│   │   ├── ModuleRepository.php   # Data access
│   │   ├── FieldRepository.php    # Field data
│   │   └── BlockRepository.php    # Block data
│   ├── Models/
│   │   ├── ModuleDefinition.php   # Value objects
│   │   ├── FieldDefinition.php
│   │   └── BlockDefinition.php
│   ├── Validators/
│   │   ├── ModuleValidator.php
│   │   └── FieldValidator.php
│   ├── Events/
│   │   ├── ModuleCreated.php      # Event classes
│   │   ├── ModuleDeleted.php
│   │   ├── FieldAdded.php
│   │   └── ...
│   ├── Contracts/                 # Interfaces
│   │   ├── ModuleServiceInterface.php
│   │   ├── FieldServiceInterface.php
│   │   └── SchemaManagerInterface.php
│   └── Facades/
│       └── VtlibFacade.php        # Backward compatibility
│
├── PackageManagement/             # Module packages
│   ├── Importers/
│   │   ├── ModuleImporter.php
│   │   └── ManifestParser.php
│   ├── Exporters/
│   │   └── ModuleExporter.php
│   └── Templates/
│       └── ModuleTemplate.php
│
└── Schema/                        # Database abstraction
    ├── Managers/
    │   └── SchemaManager.php
    ├── Builders/
    │   ├── TableBuilder.php
    │   └── ColumnBuilder.php
    └── Migrations/
        └── ModuleMigration.php
```

### Service Layer Example

```php
<?php
namespace App\ModuleManagement\Services;

use App\ModuleManagement\Contracts\ModuleServiceInterface;
use App\ModuleManagement\Repositories\ModuleRepository;
use App\ModuleManagement\Validators\ModuleValidator;
use App\ModuleManagement\Models\ModuleDefinition;
use App\ModuleManagement\Events\ModuleCreated;
use App\EventHandler;

class ModuleService implements ModuleServiceInterface
{
    private ModuleRepository $repository;
    private ModuleValidator $validator;
    private EventHandler $events;
    private SchemaService $schemaService;

    public function __construct(
        ModuleRepository $repository,
        ModuleValidator $validator,
        EventHandler $events,
        SchemaService $schemaService
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->events = $events;
        $this->schemaService = $schemaService;
    }

    /**
     * Create a new module
     *
     * @param ModuleDefinition $definition
     * @return int Module ID
     * @throws ValidationException
     */
    public function createModule(ModuleDefinition $definition): int
    {
        // Validate
        $this->validator->validate($definition);

        // Begin transaction
        $db = \App\Db::getInstance();
        $transaction = $db->beginTransaction();

        try {
            // Create module record
            $moduleId = $this->repository->create($definition);

            // Create schema
            if ($definition->isEntityType()) {
                $this->schemaService->createModuleTables($moduleId, $definition);
            }

            // Dispatch event
            $this->events->dispatch(new ModuleCreated($moduleId, $definition));

            $transaction->commit();

            return $moduleId;

        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Update module
     */
    public function updateModule(int $moduleId, ModuleDefinition $definition): bool
    {
        $this->validator->validate($definition);
        return $this->repository->update($moduleId, $definition);
    }

    /**
     * Delete module
     */
    public function deleteModule(int $moduleId): bool
    {
        $transaction = \App\Db::getInstance()->beginTransaction();

        try {
            // Fire pre-delete event
            $this->events->dispatch(new ModuleDeleting($moduleId));

            // Delete dependencies
            $this->schemaService->dropModuleTables($moduleId);

            // Delete module
            $this->repository->delete($moduleId);

            // Fire post-delete event
            $this->events->dispatch(new ModuleDeleted($moduleId));

            $transaction->commit();
            return true;

        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
```

### Backward Compatibility Facade

```php
<?php
namespace vtlib;

use App\ModuleManagement\Services\ModuleService;
use App\ModuleManagement\Models\ModuleDefinition;

/**
 * Backward compatibility facade for vtlib\Module
 *
 * @deprecated Use App\ModuleManagement\Services\ModuleService instead
 */
class Module extends ModuleBasic
{
    private static ?ModuleService $service = null;

    private static function getService(): ModuleService
    {
        if (self::$service === null) {
            self::$service = \App\Container::getInstance()->get(ModuleService::class);
        }
        return self::$service;
    }

    /**
     * @deprecated Use ModuleService::createModule() instead
     */
    public function save()
    {
        trigger_error(
            'vtlib\Module::save() is deprecated. Use ModuleService::createModule() instead.',
            E_USER_DEPRECATED
        );

        $definition = new ModuleDefinition(
            name: $this->name,
            label: $this->label,
            isEntityType: $this->isentitytype,
            type: $this->type
        );

        $this->id = self::getService()->createModule($definition);

        return $this;
    }

    // ... more backward compatibility methods
}
```

---

## Phase-by-Phase Implementation Plan

### Phase 1: Foundation (Weeks 1-2) ✅ DO FIRST

**Goal:** Set up new architecture without breaking anything

**Tasks:**
1. Create directory structure
2. Define interfaces
3. Create service classes (empty implementations)
4. Set up dependency injection container
5. Write unit tests for new services

**Deliverables:**
- `/src/ModuleManagement/` structure
- Interface contracts
- Empty service implementations
- Test framework

**Risk:** Low - no production code changed

---

### Phase 2: Service Implementation (Weeks 3-5) 🔨 IMPLEMENTATION

**Goal:** Implement services using vtlib internally

**Tasks:**
1. Implement `ModuleService` wrapping `vtlib\Module`
2. Implement `FieldService` wrapping `vtlib\Field`
3. Implement `BlockService` wrapping `vtlib\Block`
4. Add validation layer
5. Add event dispatching
6. Write comprehensive tests

**Deliverables:**
- Working services
- Validation layer
- Event system
- 80%+ test coverage

**Risk:** Low-Medium - vtlib still handles heavy lifting

---

### Phase 3: Facade Migration (Weeks 6-7) 🔄 GRADUAL MIGRATION

**Goal:** Start using new services in new code

**Tasks:**
1. Update Module Manager to use new services
2. Create facade for backward compatibility
3. Add deprecation warnings to vtlib
4. Update documentation
5. Monitor production logs

**Deliverables:**
- Module Manager using new API
- Deprecation warnings in place
- Migration guide

**Risk:** Medium - touching production code

---

### Phase 4: Schema Abstraction (Weeks 8-10) 📊 DATABASE LAYER

**Goal:** Abstract database operations

**Tasks:**
1. Create `SchemaService`
2. Create table/column builders
3. Replace direct SQL in services
4. Add migration support
5. Add rollback capability

**Deliverables:**
- Schema abstraction layer
- Migration system
- Rollback support

**Risk:** Medium - database operations critical

---

### Phase 5: Package Management (Weeks 11-12) 📦 IMPORT/EXPORT

**Goal:** Modernize module packaging

**Tasks:**
1. Create `ModuleImporter`
2. Create `ModuleExporter`
3. Update manifest format (backward compatible)
4. Add package validation
5. Improve error reporting

**Deliverables:**
- Modern importer
- Modern exporter
- Validated packages

**Risk:** Medium - affects 3rd party modules

---

### Phase 6: Event System Enhancement (Weeks 13-14) 🎯 EVENTS

**Goal:** Replace vtlib_handler with event system

**Tasks:**
1. Create event classes
2. Replace `vtlib_handler` checks with events
3. Add event documentation
4. Migrate existing handlers
5. Add event subscribers

**Deliverables:**
- Modern event system
- Migrated handlers
- Event documentation

**Risk:** Medium - affects module extensions

---

### Phase 7: Deprecation & Migration (Weeks 15-20) ⚠️ DEPRECATION

**Goal:** Migrate all internal code to new API

**Tasks:**
1. Migrate Settings modules
2. Migrate module installation scripts
3. Add loud deprecation warnings
4. Create migration tools
5. Update all documentation
6. Communicate to community

**Deliverables:**
- All internal code migrated
- Migration tools
- Community communication

**Risk:** High - affects entire codebase

---

### Phase 8: vtlib Removal (Version 2.0) 🗑️ FINAL STEP

**Goal:** Remove vtlib completely

**Tasks:**
1. Remove vtlib facade
2. Remove vtlib directory
3. Update composer autoload
4. Final testing
5. Release notes

**Deliverables:**
- vtlib removed
- Clean architecture
- v2.0 release

**Risk:** High - breaking change

**Timeline:** 6+ months after Phase 7

---

## Risk Assessment

### High Risk Areas

1. **Module Import/Export**
   - 3rd party modules depend on it
   - Mitigation: Backward compatible manifest format

2. **Database Schema Changes**
   - Critical for data integrity
   - Mitigation: Comprehensive testing, transactions, rollback

3. **Event System**
   - Many modules use `vtlib_handler`
   - Mitigation: Facade maintains compatibility

4. **Field Management**
   - Complex uitype logic
   - Mitigation: Wrap existing logic initially

### Mitigation Strategies

1. **Feature Flags**
   ```php
   if (\App\Config::get('use_new_module_service', false)) {
       // Use new service
   } else {
       // Use vtlib
   }
   ```

2. **Parallel Testing**
   - Run both old and new code
   - Compare results
   - Log differences

3. **Gradual Rollout**
   - Internal modules first
   - Community modules later
   - 6-month deprecation period

4. **Comprehensive Testing**
   - Unit tests
   - Integration tests
   - End-to-end tests
   - Production monitoring

---

## Decision Tree

### When to Use New API vs vtlib?

```
START
  |
  ├─ Writing NEW code?
  │   └─> Use NEW ModuleService API
  |
  ├─ Maintaining EXISTING code?
  │   ├─ Simple fix?
  │   │   └─> Keep vtlib (for now)
  │   └─ Major refactor?
  │       └─> Migrate to new API
  |
  ├─ 3rd Party Module?
  │   └─> Use vtlib facade (for compatibility)
  |
  └─ Core System Module?
      └─> Migrate to new API during Phase 7
```

### Should You Refactor vtlib Usage?

```
Decision Factors:
1. Is this new development? → YES → Use new API
2. Is this a bug fix? → NO → Keep vtlib
3. Is this a major feature? → YES → Use new API
4. Is there time pressure? → YES → Keep vtlib, add TODO
5. Is there technical debt? → YES → Refactor to new API
```

---

## Open Questions

### For Discussion

1. **Namespace Strategy**
   - `App\ModuleManagement\` vs `FreeCRM\ModuleManagement\` vs `App\Modules\Management\`?
   - Decision needed before Phase 1

2. **Backward Compatibility Timeline**
   - How long to maintain vtlib facade?
   - Recommendation: 6-12 months

3. **3rd Party Modules**
   - How to support during transition?
   - Provide migration guide?
   - Offer migration tools?

4. **Database Migrations**
   - Should we migrate existing modules?
   - Or just handle new ones?

5. **Event System**
   - Use Yii2 events or custom?
   - Integration with existing EventHandler?

6. **Testing Strategy**
   - What's acceptable test coverage?
   - Recommendation: 80% for new code

7. **Documentation**
   - Developer guide?
   - API reference?
   - Migration cookbook?

8. **Performance**
   - Any performance implications?
   - Need benchmarks?

### Validation Needed

1. Review proposed architecture with team
2. Validate service layer design
3. Confirm database abstraction approach
4. Agree on timeline
5. Identify resource requirements

---

## Next Steps

### Immediate Actions

1. **Review this document** with technical team
2. **Answer open questions** above
3. **Get stakeholder approval** for approach
4. **Assign resources** for Phase 1
5. **Set up project tracking** (JIRA/GitHub Projects)

### Before Starting

- [ ] Architecture approved by lead developers
- [ ] Timeline approved by product owner
- [ ] Resources allocated
- [ ] Test environment prepared
- [ ] Documentation plan created
- [ ] Communication plan for community

### Success Criteria

1. ✅ No backward compatibility breaks during migration
2. ✅ All tests passing
3. ✅ Performance maintained or improved
4. ✅ Documentation complete
5. ✅ Community informed and supported
6. ✅ Clean, maintainable architecture

---

## Conclusion

vtlib refactoring is **essential** for FreeCRM's long-term maintainability, but must be done **carefully and incrementally**. 

The **Facade Wrapper** approach (Option 2) provides the best balance of:
- ✅ Low risk
- ✅ Backward compatibility
- ✅ Incremental value
- ✅ Modern architecture

**Estimated Timeline:** 5-6 months for complete migration
**Recommended Approach:** Phase-by-phase implementation
**Key Success Factor:** Comprehensive testing and backward compatibility

---

## Appendix

### A. Current vtlib API Reference

```php
// Module Operations
$module = new vtlib\Module();
$module->name = 'ModuleName';
$module->save();
$module->initTables();
$module->delete();
$module::getInstance($nameOrId);
$module::getClassInstance($name);

// Field Operations
$field = new vtlib\Field();
$field->name = 'fieldname';
$block->addField($field);
$field->setPicklistValues(['value1', 'value2']);
$field->setRelatedModules(['Module1', 'Module2']);
$field::getInstance($fieldId, $moduleInstance);
$field::getAllForModule($moduleInstance);

// Block Operations
$block = new vtlib\Block();
$block->label = 'Block Label';
$module->addBlock($block);

// Relationship Operations
$module->setRelatedList($targetModule, $label, ['ADD']);
$module->unsetRelatedList($targetModule, $label);

// Link Operations
$module->addLink($type, $label, $url);
$module->deleteLink($type, $label);

// Events
$module::fireEvent($moduleName, $eventType);
```

### B. Proposed New API Reference

```php
// Module Operations
use App\ModuleManagement\Services\ModuleService;
use App\ModuleManagement\Models\ModuleDefinition;

$service = app(ModuleService::class);
$definition = new ModuleDefinition(
    name: 'ModuleName',
    label: 'Module Label',
    isEntityType: true
);
$moduleId = $service->createModule($definition);
$service->updateModule($moduleId, $definition);
$service->deleteModule($moduleId);

// Field Operations
use App\ModuleManagement\Services\FieldService;
use App\ModuleManagement\Models\FieldDefinition;

$fieldService = app(FieldService::class);
$fieldDef = new FieldDefinition(
    name: 'fieldname',
    label: 'Field Label',
    uitype: 1,
    typeofdata: 'V~M'
);
$fieldId = $fieldService->createField($moduleId, $blockId, $fieldDef);
```

### C. Resources

- **vtlib Documentation:** https://code.vtiger.com/vtiger/vtigercrm-manual/wikis/home
- **Yii2 Database:** https://www.yiiframework.com/doc/guide/2.0/en/db-dao
- **PSR-4:** https://www.php-fig.org/psr/psr-4/
- **SOLID Principles:** https://en.wikipedia.org/wiki/SOLID

---

**Document End**

