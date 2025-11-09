# vtlib Refactoring Implementation Plan

**Document Version:** 1.0  
**Created:** 2025-01-XX  
**Status:** Planning Phase  
**Goal:** Replace vtlib with modern, PSR-compliant ModuleManagement library

---

## Table of Contents

1. [Overview](#overview)
2. [Simplified Architecture](#simplified-architecture)
3. [Implementation Phases](#implementation-phases)
4. [Configuration Requirements](#configuration-requirements)
5. [Testing Strategy](#testing-strategy)
6. [Migration Checklist](#migration-checklist)
7. [Risk Mitigation](#risk-mitigation)
8. [Success Criteria](#success-criteria)

---

## Overview

### Goals

1. **Replace vtlib** with modern PSR-compliant library
2. **Maintain backward compatibility** during transition
3. **Improve code quality** with proper separation of concerns
4. **Add transaction safety** for all operations
5. **Enable dependency injection** for better testability
6. **Keep it simple** - avoid over-engineering

### Scope

- **Module lifecycle management** (create, update, delete, enable/disable)
- **Field management** (create, update, delete, picklists)
- **Block management** (create, update, delete)
- **Relationship management** (related lists, many-to-many)
- **Package management** (import/export)
- **Event system** (vtlib_handler compatibility)

### Out of Scope (for now)

- Complete rewrite of package import/export format
- Migration of existing modules to new structure
- **vtlib directory will be deleted after migration is complete** - all classes must be migrated

---

## Simplified Architecture

### Directory Structure

```
src/ModuleManagement/
├── Services/              # Business logic layer
│   ├── ModuleService.php
│   ├── FieldService.php
│   ├── BlockService.php
│   ├── RelationService.php
│   └── PackageService.php
│
├── Models/                # Value objects (immutable data structures)
│   ├── Module.php
│   ├── Field.php
│   ├── Block.php
│   └── Relation.php
│
├── Adapters/              # Backward compatibility facades
│   ├── Module.php         # vtlib\Module facade
│   ├── Field.php          # vtlib\Field facade
│   ├── Block.php          # vtlib\Block facade
│   └── Filter.php         # vtlib\Filter facade
│
└── Events/                # Event dispatcher
    └── Dispatcher.php     # Handles vtlib_handler calls
```

### Design Principles

1. **Services handle business logic** - No separate repository layer
2. **Services handle validation** - No separate validator classes
3. **Direct DB access in services** - Like vtlib, but with transactions
4. **Value objects are immutable** - Simple data containers
5. **Adapters maintain API compatibility** - Delegate to services
6. **Event dispatcher is simple** - Maintains vtlib_handler pattern

### Key Classes

#### ModuleService
- `create(Module $module): int`
- `update(int $moduleId, Module $module): void`
- `delete(int $moduleId): void`
- `getInstance($nameOrId): ?Module`
- `toggleAccess(string $moduleName, bool $enable): void`
- `initTables(int $moduleId, Module $module): void`
- `initWebservice(int $moduleId): void`

#### FieldService
- `create(int $moduleId, int $blockId, Field $field): int`
- `update(int $fieldId, Field $field): void`
- `delete(int $fieldId): void`
- `getInstance($fieldIdOrName, ?Module $module): ?Field`
- `setPicklistValues(int $fieldId, array $values): void`
- `setRelatedModules(int $fieldId, array $moduleNames): void`

#### BlockService
- `create(int $moduleId, Block $block): int`
- `update(int $blockId, Block $block): void`
- `delete(int $blockId): void`
- `getAllForModule(int $moduleId): array`

#### RelationService
- `setRelatedList(int $sourceModuleId, int $targetModuleId, string $label, array $actions, string $functionName): void`
- `unsetRelatedList(int $sourceModuleId, int $targetModuleId, string $label, string $functionName): void`

#### PackageService
- `import(string $packagePath): void`
- `export(int $moduleId, string $outputPath): void`
- `update(int $moduleId, string $packagePath): void`

#### EventDispatcher
- `fire(string $moduleName, string $eventType): bool`
- `registerListener(string $eventType, callable $listener): void`

---

## Implementation Phases

### Phase 1: Foundation (Week 1-2)

**Goal:** Set up basic structure and core services

#### Tasks

1. **Create directory structure**
   - Create `src/ModuleManagement/` directories
   - Set up PSR-4 autoloading in `composer.json`

2. **Create value objects (Models)**
   - `Module.php` - Immutable module definition
   - `Field.php` - Immutable field definition
   - `Block.php` - Immutable block definition
   - `Relation.php` - Relation definition

3. **Create core services (empty implementations)**
   - `ModuleService.php` - Stub methods
   - `FieldService.php` - Stub methods
   - `BlockService.php` - Stub methods
   - `RelationService.php` - Stub methods

4. **Create event dispatcher**
   - `EventDispatcher.php` - Basic implementation
   - Maintain `vtlib_handler` compatibility

5. **Set up dependency injection**
   - Register services in DI container
   - Create service factory if needed

#### Deliverables

- [ ] Directory structure created
- [ ] Value objects implemented
- [ ] Service stubs created
- [ ] Event dispatcher working
- [ ] DI container configured
- [ ] Basic unit tests passing

#### Configuration Needed

- Update `composer.json` autoload section
- Create service registration file (if needed)
- Add to application bootstrap

---

### Phase 2: Core Service Implementation (Week 3-5)

**Goal:** Implement core business logic in services

#### Tasks

1. **Implement ModuleService**
   - `create()` - Create module with transaction
   - `update()` - Update module metadata
   - `delete()` - Delete module (cascade)
   - `getInstance()` - Get by ID or name
   - `toggleAccess()` - Enable/disable module
   - `initTables()` - Create database tables
   - `initWebservice()` - Initialize webservice

2. **Implement FieldService**
   - `create()` - Create field with validation
   - `update()` - Update field properties
   - `delete()` - Delete field (cleanup picklists, etc.)
   - `getInstance()` - Get by ID or name
   - `setPicklistValues()` - Manage picklist values
   - `setRelatedModules()` - Set UIType 10 relations

3. **Implement BlockService**
   - `create()` - Create block
   - `update()` - Update block
   - `delete()` - Delete block
   - `getAllForModule()` - Get all blocks for module

4. **Implement RelationService**
   - `setRelatedList()` - Create module relationship
   - `unsetRelatedList()` - Remove relationship
   - Handle many-to-many table creation

5. **Add transaction management**
   - Wrap all multi-step operations
   - Implement rollback on failure
   - Add transaction logging

#### Deliverables

- [ ] ModuleService fully implemented
- [ ] FieldService fully implemented
- [ ] BlockService fully implemented
- [ ] RelationService fully implemented
- [ ] All operations transactional
- [ ] Unit tests for all services (80%+ coverage)

#### Configuration Needed

- Database connection configuration (already exists)
- Transaction isolation level settings
- Logging configuration for transactions

---

### Phase 3: Package Service (Week 6-7)

**Goal:** Implement package import/export functionality

#### Tasks

1. **Implement PackageService**
   - `import()` - Import module package
   - `export()` - Export module package
   - `update()` - Update existing module
   - Parse manifest files
   - Handle ZIP operations

2. **Maintain package format compatibility**
   - Support existing package format
   - Ensure backward compatibility
   - Validate package structure

3. **Integration with other services**
   - Use ModuleService for module creation
   - Use FieldService for field creation
   - Use BlockService for block creation
   - Use RelationService for relationships

#### Deliverables

- [ ] PackageService implemented
- [ ] Import functionality working
- [ ] Export functionality working
- [ ] Update functionality working
- [ ] Package format validation
- [ ] Integration tests passing

#### Configuration Needed

- Package upload directory configuration
- Package validation rules
- ZIP library configuration

---

### Phase 4: Adapter Layer (Week 8-9)

**Goal:** Create backward-compatible facades

#### Tasks

1. **Create vtlib\Module adapter**
   - Implement all public methods
   - Delegate to ModuleService
   - Maintain same API signature
   - Add deprecation warnings

2. **Create vtlib\Field adapter**
   - Implement all public methods
   - Delegate to FieldService
   - Maintain same API signature
   - Add deprecation warnings

3. **Create vtlib\Block adapter**
   - Implement all public methods
   - Delegate to BlockService
   - Maintain same API signature
   - Add deprecation warnings

4. **Create vtlib\Filter adapter**
   - Implement filter operations
   - Delegate to appropriate service
   - Maintain compatibility

5. **Update composer autoload**
   - Map `vtlib\` namespace to adapters
   - Ensure seamless transition

#### Deliverables

- [ ] All adapters implemented
- [ ] API compatibility maintained
- [ ] Deprecation warnings added
- [ ] Autoloading configured
- [ ] Backward compatibility tests passing

#### Configuration Needed

- Update `composer.json` autoload mapping
- Configure deprecation warning levels
- Set up compatibility test suite

---

### Phase 5: Integration & Testing (Week 10-11)

**Goal:** Integrate with existing codebase and comprehensive testing

#### Tasks

1. **Integration testing**
   - Test Module Manager UI integration
   - Test Layout Editor integration
   - Test package import/export UI
   - Test module installation scripts

2. **Backward compatibility testing**
   - Test all 100+ vtlib usage points
   - Test all 81+ vtlib_handler methods
   - Verify event firing works correctly
   - Test module creation workflow

3. **Performance testing**
   - Benchmark module creation
   - Benchmark field creation
   - Benchmark package import
   - Compare with old vtlib performance

4. **Error handling testing**
   - Test transaction rollback
   - Test partial failure scenarios
   - Test validation errors
   - Test database constraint violations

5. **Documentation**
   - API documentation
   - Migration guide
   - Developer guide
   - Code examples

#### Deliverables

- [ ] All integration tests passing
- [ ] Backward compatibility verified
- [ ] Performance benchmarks acceptable
- [ ] Error handling tested
- [ ] Documentation complete

#### Configuration Needed

- Test database setup
- Performance monitoring configuration
- Error logging configuration

---

### Phase 6: Cutover & Deployment (Week 12)

**Goal:** Deploy new library and verify production

#### Tasks

1. **Pre-deployment checks**
   - Final code review
   - All tests passing
   - Documentation reviewed
   - Rollback plan prepared

2. **Deployment**
   - Deploy code changes
   - Update autoloader
   - Clear caches
   - Monitor logs

3. **Post-deployment verification**
   - Test module creation in production
   - Test package import
   - Monitor error logs
   - Verify event handlers work

4. **Monitoring**
   - Monitor for errors
   - Check performance metrics
   - Verify transaction behavior
   - Watch for deprecation warnings

#### Deliverables

- [ ] Code deployed
- [ ] Production verified
- [ ] Monitoring in place
- [ ] Rollback plan ready

#### Configuration Needed

- Production deployment configuration
- Monitoring and alerting setup
- Log aggregation configuration

---

## Configuration Requirements

### 1. Composer Autoload Configuration

**File:** `composer.json`

```json
{
  "autoload": {
    "psr-4": {
      "App\\ModuleManagement\\": "src/ModuleManagement/",
      "vtlib\\": "src/ModuleManagement/Adapters/"
    }
  }
}
```

**Action:** Update autoload section to map:
- `App\ModuleManagement\` → `src/ModuleManagement/`
- `vtlib\` → `src/ModuleManagement/Adapters/` (for backward compatibility)

---

### 2. Dependency Injection Configuration

**Option A: Yii2 Container (Recommended)**

Create `config/di-container.php`:

```php
<?php
return [
    'singletons' => [
        \App\ModuleManagement\Services\ModuleService::class => [
            'class' => \App\ModuleManagement\Services\ModuleService::class,
            '__construct()' => [
                \App\Db::getInstance(),
                \Yii::$container->get(\App\ModuleManagement\Events\Dispatcher::class),
            ],
        ],
        \App\ModuleManagement\Services\FieldService::class => [
            'class' => \App\ModuleManagement\Services\FieldService::class,
            '__construct()' => [
                \App\Db::getInstance(),
            ],
        ],
        // ... other services
    ],
];
```

**Option B: Service Locator Pattern**

Create `src/ModuleManagement/ServiceLocator.php`:

```php
namespace App\ModuleManagement;

class ServiceLocator
{
    private static array $services = [];
    
    public static function getModuleService(): Services\ModuleService
    {
        if (!isset(self::$services['module'])) {
            self::$services['module'] = new Services\ModuleService(
                \App\Db::getInstance(),
                self::getEventDispatcher()
            );
        }
        return self::$services['module'];
    }
    
    // ... other service getters
}
```

**Action:** Choose DI approach and implement service registration

---

### 3. Database Transaction Configuration

**File:** `config/config.php` or database config

```php
// Transaction isolation level
'db' => [
    'transactionIsolationLevel' => \PDO::TRANSACTION_READ_COMMITTED,
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
],
```

**Action:** Configure transaction settings if needed

---

### 4. Logging Configuration

**File:** `config/config.php`

```php
'logging' => [
    'moduleManagement' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'file' => 'cache/logs/module-management.log',
    ],
],
```

**Action:** Configure logging for ModuleManagement operations

---

### 5. Package Management Configuration

**File:** `config/config.php`

```php
'packageManagement' => [
    'uploadDirectory' => 'cache/upload/',
    'maxPackageSize' => 50 * 1024 * 1024, // 50MB
    'allowedExtensions' => ['zip'],
    'validateManifest' => true,
],
```

**Action:** Configure package upload and validation settings

---

### 6. Event System Configuration

**File:** `config/config.php` (optional)

```php
'events' => [
    'moduleManagement' => [
        'enableModernListeners' => false, // Start with false for compatibility
        'logEvents' => true,
    ],
],
```

**Action:** Configure event system behavior

---

### 7. Cache Configuration

**File:** `config/config.php` (if needed)

```php
'cache' => [
    'moduleManagement' => [
        'enabled' => true,
        'duration' => 3600, // 1 hour
        'keyPrefix' => 'module_mgmt_',
    ],
],
```

**Action:** Configure caching for module/field definitions if needed

---

## Testing Strategy

### Unit Tests

**Coverage Target:** 80%+

**Test Files:**
- `tests/Unit/ModuleManagement/Services/ModuleServiceTest.php`
- `tests/Unit/ModuleManagement/Services/FieldServiceTest.php`
- `tests/Unit/ModuleManagement/Services/BlockServiceTest.php`
- `tests/Unit/ModuleManagement/Services/RelationServiceTest.php`
- `tests/Unit/ModuleManagement/Services/PackageServiceTest.php`
- `tests/Unit/ModuleManagement/Models/ModuleTest.php`
- `tests/Unit/ModuleManagement/Models/FieldTest.php`
- `tests/Unit/ModuleManagement/Events/DispatcherTest.php`

**Test Scenarios:**
- Service method calls
- Validation logic
- Transaction rollback
- Error handling
- Value object immutability

---

### Integration Tests

**Test Files:**
- `tests/Integration/ModuleManagement/ModuleCreationTest.php`
- `tests/Integration/ModuleManagement/FieldCreationTest.php`
- `tests/Integration/ModuleManagement/PackageImportTest.php`
- `tests/Integration/ModuleManagement/EventFiringTest.php`

**Test Scenarios:**
- Full module creation workflow
- Field creation with picklists
- Package import/export
- Event handler execution
- Database state verification

---

### Backward Compatibility Tests

**Test Files:**
- `tests/Compatibility/VtlibAdapterTest.php`
- `tests/Compatibility/VtlibHandlerTest.php`
- `tests/Compatibility/ModuleManagerIntegrationTest.php`

**Test Scenarios:**
- All vtlib API calls work
- All vtlib_handler methods fire
- Module Manager UI works
- Layout Editor works
- Package import/export works

---

### Performance Tests

**Test Scenarios:**
- Module creation time
- Field creation time
- Package import time
- Memory usage
- Database query count

**Benchmarks:**
- Compare with old vtlib performance
- Target: Same or better performance

---

## Migration Checklist

### Pre-Implementation

- [ ] Review and approve architecture
- [ ] Set up development environment
- [ ] Create feature branch
- [ ] Set up test database
- [ ] Configure CI/CD for tests

### Phase 1: Foundation

- [ ] Create directory structure
- [ ] Implement value objects
- [ ] Create service stubs
- [ ] Implement event dispatcher
- [ ] Configure DI container
- [ ] Write basic unit tests

### Phase 2: Core Services

- [ ] Implement ModuleService
- [ ] Implement FieldService
- [ ] Implement BlockService
- [ ] Implement RelationService
- [ ] Add transaction management
- [ ] Write comprehensive unit tests

### Phase 3: Package Service

- [ ] Implement PackageService
- [ ] Test package import
- [ ] Test package export
- [ ] Test package update
- [ ] Write integration tests

### Phase 4: Adapters

- [ ] Create vtlib\Module adapter
- [ ] Create vtlib\Field adapter
- [ ] Create vtlib\Block adapter
- [ ] Create vtlib\Filter adapter
- [ ] Update composer autoload
- [ ] Write compatibility tests

### Phase 5: Integration

- [ ] Test Module Manager integration
- [ ] Test Layout Editor integration
- [ ] Test all vtlib usage points
- [ ] Test all vtlib_handler methods
- [ ] Performance benchmarking
- [ ] Write documentation

### Phase 6: Deployment

- [ ] Code review
- [ ] Final testing
- [ ] Deploy to staging
- [ ] Verify staging
- [ ] Deploy to production
- [ ] Monitor production

### Post-Deployment

- [ ] Monitor error logs
- [ ] Verify performance
- [ ] Collect user feedback
- [ ] Plan vtlib removal (future)

---

## Risk Mitigation

### High-Risk Areas

#### 1. Package Import/Export

**Risk:** Third-party modules depend on package format

**Mitigation:**
- Maintain backward-compatible package format
- Test with existing packages
- Provide migration guide if format changes

**Rollback Plan:**
- Keep old Package classes as fallback
- Feature flag to switch implementations

---

#### 2. Event System

**Risk:** 81+ modules use vtlib_handler pattern

**Mitigation:**
- Maintain exact vtlib_handler compatibility
- Test all module installation scripts
- Verify event firing order

**Rollback Plan:**
- Keep old event firing code
- Feature flag for event system

---

#### 3. Database Transactions

**Risk:** Transaction failures could corrupt data

**Mitigation:**
- Comprehensive transaction testing
- Rollback testing
- Database backup before operations
- Transaction logging

**Rollback Plan:**
- Manual database restore
- Transaction log analysis

---

#### 4. Performance

**Risk:** New abstraction layers might slow operations

**Mitigation:**
- Performance benchmarking
- Optimize hot paths
- Cache where appropriate
- Monitor production metrics

**Rollback Plan:**
- Feature flag to disable new code
- Performance monitoring alerts

---

### Medium-Risk Areas

#### 1. API Compatibility

**Risk:** Adapter layer might not match vtlib exactly

**Mitigation:**
- Comprehensive compatibility tests
- Code review of all adapter methods
- Test all usage points

---

#### 2. Module Creation

**Risk:** Complex workflow with many steps

**Mitigation:**
- Transaction wrapping
- Comprehensive testing
- Step-by-step validation

---

### Low-Risk Areas

#### 1. Value Objects

**Risk:** Minimal - simple data structures

**Mitigation:**
- Immutability testing
- Type validation

---

## Success Criteria

### Functional Requirements

- [ ] All vtlib API calls work through adapters
- [ ] All module creation workflows work
- [ ] All field creation workflows work
- [ ] Package import/export works
- [ ] All event handlers fire correctly
- [ ] Module Manager UI works
- [ ] Layout Editor works

### Non-Functional Requirements

- [ ] Performance matches or exceeds vtlib
- [ ] Code coverage ≥ 80%
- [ ] All tests passing
- [ ] No breaking changes
- [ ] Documentation complete
- [ ] Backward compatibility maintained

### Quality Metrics

- [ ] PSR-4 compliant
- [ ] Type hints on all methods
- [ ] Return types specified
- [ ] Proper error handling
- [ ] Transaction safety
- [ ] Code review approved

---

## Timeline Summary

| Phase | Duration | Key Deliverables |
|-------|----------|------------------|
| Phase 1: Foundation | 2 weeks | Structure, value objects, service stubs |
| Phase 2: Core Services | 3 weeks | ModuleService, FieldService, BlockService, RelationService |
| Phase 3: Package Service | 2 weeks | PackageService implementation |
| Phase 4: Adapters | 2 weeks | Backward compatibility facades |
| Phase 5: Integration | 2 weeks | Testing, documentation |
| Phase 6: Deployment | 1 week | Production deployment |
| **Total** | **12 weeks** | **Complete replacement** |

---

## Questions & Answers (Final Decisions)

**Important:** Since vtlib will be completely deleted, all classes must be migrated to ModuleManagement.

### 1. Supporting Classes Scope

**Question:** How should we handle these vtlib classes that are used but not core to module/field management?

**Classes Identified:**
- `vtlib\Profile` - Used for permission initialization (`Profile::initForModule()`, `Profile::initForField()`)
- `vtlib\Access` - Used for sharing access (`Access::setDefaultSharing()`, `Access::initSharing()`)
- `vtlib\Cron` - Used for cron task management (`Cron::register()`, `Cron::deleteForModule()`)
- `vtlib\Link` - Used for custom links (`Link::addLink()`, `Link::deleteLink()`)
- `vtlib\Filter` - Used for custom views (`Filter::deleteForModule()`)
- `vtlib\Webservice` - Used for webservice initialization (`Webservice::initialize()`)
- `vtlib\Menu` - Used for menu management (`Menu::deleteForModule()`)
- `vtlib\Language` - Used in package import/export
- `vtlib\Layout` - Used in package import/export

**Answer:** **Option C - Integrate functionality into services**

**Rationale:**
- Since vtlib will be deleted, these classes must be migrated
- Profile/Access operations are tightly coupled with module/field lifecycle → integrate into services
- Link/Filter/Webservice/Menu are simple utilities → create lightweight adapters in ModuleManagement
- Cron/Language/Layout are separate concerns → migrate to appropriate locations or keep simple adapters
- All adapters will be in `App\ModuleManagement\Adapters\` but use `vtlib\` namespace via composer autoload

---

### 2. ModuleBasic/FieldBasic Inheritance

**Question:** How should adapters handle the inheritance hierarchy?

**Current Structure:**
- `vtlib\Module` extends `vtlib\ModuleBasic`
- `vtlib\Field` extends `vtlib\FieldBasic`
- `App\Modules\Base\Models\Module` extends `vtlib\Module`
- `App\Modules\Base\Models\Field` extends `vtlib\Field`

**Answer:** **Option C - Create new Basic classes in ModuleManagement**

**Rationale:**
- Since vtlib will be deleted, ModuleBasic/FieldBasic must be recreated
- Create `App\ModuleManagement\Adapters\ModuleBasic` and `App\ModuleManagement\Adapters\FieldBasic`
- These will contain all public properties and basic methods
- Adapters (`Module`, `Field`) extend these Basic classes
- `App\Modules\Base\Models\Module` and `App\Modules\Base\Models\Field` will continue to extend adapters
- All classes use `vtlib\` namespace via composer autoload mapping for backward compatibility

---

### 3. Public Properties Compatibility

**Question:** ModuleBasic and FieldBasic have many public properties. How should adapters handle these?

**ModuleBasic Properties:** `id`, `name`, `label`, `version`, `minversion`, `maxversion`, `presence`, `ownedby`, `tabsequence`, `parent`, `customized`, `isentitytype`, `entityidcolumn`, `entityidfield`, `basetable`, `basetableid`, `customtable`, `grouptable`, `type`, `tableName`

**FieldBasic Properties:** `id`, `name`, `tabid`, `label`, `table`, `column`, `columntype`, `helpinfo`, `summaryfield`, `header_field`, `maxlengthtext`, `maxwidthcolumn`, `masseditable`, `uitype`, `typeofdata`, `displaytype`, `generatedtype`, `readonly`, `presence`, `defaultvalue`, `maximumlength`, `sequence`, `quickcreate`, `quicksequence`, `info_type`, `block`, `fieldparams`

**Answer:** **Option A - Maintain all public properties**

**Rationale:**
- All properties must be public and mutable for backward compatibility
- Code extensively uses direct property access (e.g., `$module->name`, `$field->uitype`)
- Basic classes will have all properties declared
- Adapters sync properties with internal value objects when needed
- Simpler than magic methods - direct property access is faster and clearer

---

### 4. Module File Generation (createFiles)

**Question:** Should `createFiles()` be part of ModuleService or separate service?

**Current:** `$module->createFiles($entityField)` creates module files from templates

**Answer:** **Option B - Separate FileGeneratorService**

**Rationale:**
- File generation is a distinct concern from database operations
- Keeps ModuleService focused on module lifecycle
- FileGeneratorService can be tested independently
- Can be reused for other file generation needs
- Adapter delegates to FileGeneratorService

---

### 5. Entity Identifier Setup

**Question:** Should `setEntityIdentifier()` be part of ModuleService or FieldService?

**Current:** `$module->setEntityIdentifier($fieldInstance)` sets entity identifier field

**Answer:** **Option A - ModuleService method**

**Rationale:**
- Entity identifier is a module-level configuration
- ModuleService already manages module metadata
- FieldService focuses on field operations, not module configuration
- Keeps concerns properly separated

---

### 6. Inventory Module Handling

**Question:** How should we handle inventory modules (type=1) with special tables?

**Current:** `initTables()` creates `_invfield`, `_inventory`, `_invmap` tables for type=1 modules

**Answer:** **Option A - Special handling in ModuleService::initTables()**

**Rationale:**
- Inventory modules are a variant of entity modules, not a separate type
- Conditional logic is simpler than strategy pattern for this case
- Keeps table creation logic in one place
- Easy to understand and maintain

---

### 7. Module Deletion Cascade

**Question:** The `delete()` method has extensive cascade operations. Should this be in ModuleService or separate?

**Current Cascade Operations:**
- Delete from CRMEntity
- Delete tools (Access)
- Delete filters
- Delete blocks
- Deinit webservice
- Delete icons
- Unset all related lists
- Delete ModComments
- Delete language files
- Delete sharing access
- Delete modentity_num
- Delete cron tasks
- Delete profiles
- Delete workflows
- Delete menu
- Delete group2modules
- Delete tables
- Delete CRMEntityRel
- Delete links
- Delete settings fields
- Delete directory

**Answer:** **Option A - All in ModuleService::delete()**

**Rationale:**
- Deletion is core module lifecycle operation
- All operations must be in single transaction
- Organize into private methods for maintainability:
  - `deleteRecords()` - Delete CRMEntity records
  - `deleteMetadata()` - Delete filters, blocks, links, etc.
  - `deleteTables()` - Drop database tables
  - `deleteFiles()` - Remove files and directories
  - `deleteDependencies()` - Delete workflows, profiles, etc.
- Single service keeps transaction management simple

---

### 8. Profile Initialization

**Question:** Profile initialization happens automatically. Should this be explicit or automatic?

**Current:** `Profile::initForModule()` and `Profile::initForField()` called automatically during creation

**Answer:** **Option A - Automatic in ModuleService/FieldService**

**Rationale:**
- Maintains current behavior - no breaking changes
- Reduces errors - developers don't need to remember to initialize
- Profile initialization is always required for new modules/fields
- ModuleService/FieldService will handle Profile operations internally (migrated from vtlib\Profile)

---

### 9. Access/Sharing Initialization

**Question:** Should sharing access initialization be automatic or explicit?

**Current:** `Access::initSharing()` called during module creation, `Access::setDefaultSharing()` called manually

**Answer:** **Option A - Automatic basic sharing, explicit for custom sharing**

**Rationale:**
- Basic sharing initialization is always needed
- Custom sharing settings vary by module
- Maintains current pattern - basic auto, custom explicit
- ModuleService will handle Access operations internally (migrated from vtlib\Access)

---

### 10. Package Import/Export Dependencies

**Question:** Package import/export uses Language, Layout, Menu classes. How should these integrate?

**Current:** Package import/export handles language files, layouts, menus

**Answer:** **Option B - Create lightweight adapters for Language/Layout/Menu**

**Rationale:**
- Since vtlib will be deleted, these classes must be migrated
- Create simple adapters in `App\ModuleManagement\Adapters\`
- These are utility classes - adapters can be thin wrappers
- PackageService uses adapters, adapters delegate to appropriate services or handle directly
- Keep functionality simple - these are file operations, not complex business logic

---

### 11. Cron Task Management

**Question:** Should cron task registration be part of ModuleService or separate?

**Current:** Modules register cron tasks in `vtlib_handler('module.postinstall')`

**Answer:** **Option C - Create lightweight Cron adapter**

**Rationale:**
- Since vtlib will be deleted, Cron class must be migrated
- Create adapter in `App\ModuleManagement\Adapters\Cron.php`
- Keep current pattern - modules register cron tasks in event handlers
- Adapter provides same API as vtlib\Cron
- Cron operations are simple - adapter can handle directly or delegate to a simple service

---

### 12. Menu Management

**Question:** Should menu management be part of ModuleService or separate?

**Current:** `Menu::deleteForModule()` called during module deletion

**Answer:** **Option B - Create lightweight Menu adapter**

**Rationale:**
- Since vtlib will be deleted, Menu class must be migrated
- Create adapter in `App\ModuleManagement\Adapters\Menu.php`
- Menu operations are simple - adapter can handle directly
- ModuleService calls `Menu::deleteForModule()` during deletion (same API)
- Menu is a separate concern - adapter keeps it isolated

---

## Decision Summary (Final)

Since vtlib will be completely deleted, all decisions account for full migration:

1. **Supporting Classes:** Migrate all - Profile/Access integrated into services, others as lightweight adapters
2. **Inheritance:** Create new ModuleBasic/FieldBasic in ModuleManagement, adapters extend them
3. **Properties:** Maintain all public properties in Basic classes and adapters
4. **File Generation:** Separate FileGeneratorService
5. **Entity Identifier:** ModuleService method
6. **Inventory Modules:** Handle in ModuleService::initTables()
7. **Module Deletion:** All in ModuleService, organized into private methods
8. **Profile Init:** Automatic in ModuleService/FieldService (integrated functionality)
9. **Access Init:** Automatic basic, explicit custom (integrated into ModuleService)
10. **Package Dependencies:** Create lightweight adapters for Language/Layout/Menu
11. **Cron Management:** Create lightweight Cron adapter, keep current event handler pattern
12. **Menu Management:** Create lightweight Menu adapter

---

## Updated Architecture (Final)

### Complete Directory Structure

```
src/ModuleManagement/
├── Services/              # Business logic layer
│   ├── ModuleService.php  # Includes Profile/Access operations
│   ├── FieldService.php   # Includes Profile operations
│   ├── BlockService.php
│   ├── RelationService.php
│   ├── PackageService.php
│   └── FileGeneratorService.php
│
├── Models/                # Value objects (immutable)
│   ├── Module.php
│   ├── Field.php
│   ├── Block.php
│   └── Relation.php
│
├── Adapters/              # Backward compatibility (vtlib\ namespace)
│   ├── ModuleBasic.php    # NEW: Base class with all properties
│   ├── FieldBasic.php     # NEW: Base class with all properties
│   ├── Module.php         # Extends ModuleBasic, delegates to ModuleService
│   ├── Field.php          # Extends FieldBasic, delegates to FieldService
│   ├── Block.php          # Delegates to BlockService
│   ├── Filter.php         # Lightweight adapter
│   ├── Link.php           # Lightweight adapter
│   ├── Webservice.php     # Lightweight adapter
│   ├── Profile.php        # Lightweight adapter (delegates to services)
│   ├── Access.php         # Lightweight adapter (delegates to ModuleService)
│   ├── Cron.php           # Lightweight adapter
│   ├── Menu.php           # Lightweight adapter
│   ├── Language.php       # Lightweight adapter
│   └── Layout.php         # Lightweight adapter
│
└── Events/                # Event dispatcher
    └── Dispatcher.php     # Handles vtlib_handler calls
```

### Updated ModuleService Methods

```php
class ModuleService
{
    // Core operations
    public function create(Module $module): int
    public function update(int $moduleId, Module $module): void
    public function delete(int $moduleId): void
    public function getInstance($nameOrId): ?Module
    
    // Lifecycle operations
    public function initTables(int $moduleId, Module $module): void
    public function initWebservice(int $moduleId): void
    public function setEntityIdentifier(int $moduleId, int $fieldId): void
    public function toggleAccess(string $moduleName, bool $enable): void
    
    // File operations (delegates to FileGeneratorService)
    public function createFiles(int $moduleId, Field $entityField): void
    
    // Profile operations (migrated from vtlib\Profile)
    private function initProfile(int $moduleId): void
    
    // Access operations (migrated from vtlib\Access)
    private function initSharing(int $moduleId): void
    public function setDefaultSharing(int $moduleId, string $permission): void
    
    // Deletion cascade
    private function deleteCascade(int $moduleId): void
}
```

### Composer Autoload Mapping

```json
{
  "autoload": {
    "psr-4": {
      "App\\ModuleManagement\\": "src/ModuleManagement/",
      "vtlib\\": "src/ModuleManagement/Adapters/"
    }
  }
}
```

**Key Point:** All adapters use `vtlib\` namespace but are located in `src/ModuleManagement/Adapters/`. This maintains backward compatibility while allowing vtlib directory deletion.

---

## Phase 7: vtlib Directory Removal (After Phase 6)

**Goal:** Remove vtlib directory completely after verifying everything works

### Prerequisites

Before deleting vtlib directory, ensure:

- [ ] All adapters implemented and tested
- [ ] All vtlib classes have equivalents in ModuleManagement
- [ ] All usage points migrated or using adapters
- [ ] All tests passing
- [ ] Production verified for at least 2 weeks
- [ ] No errors in logs related to vtlib
- [ ] Composer autoload updated to map `vtlib\` to adapters

### Tasks

1. **Final verification**
   - Run full test suite
   - Verify all module operations work
   - Check all vtlib usage points
   - Verify package import/export
   - Test module creation/deletion

2. **Update composer autoload**
   - Ensure `vtlib\` maps to `src/ModuleManagement/Adapters/`
   - Run `composer dump-autoload`

3. **Remove vtlib directory**
   - Delete `/vtlib/` directory
   - Update `.gitignore` if needed
   - Remove from composer autoload (if separate entry exists)

4. **Update documentation**
   - Remove references to vtlib directory
   - Update developer guides
   - Update migration documentation

### Deliverables

- [ ] vtlib directory removed
- [ ] All functionality working
- [ ] Documentation updated
- [ ] Clean codebase

### Risk

**Low** - Adapters provide complete backward compatibility, vtlib directory is no longer needed.

---

## Next Steps

1. **Review this plan** - All questions answered
2. **Approve architecture** and timeline
3. **Set up development environment**
4. **Create feature branch**
5. **Begin Phase 1 implementation**

---

## References

- [vtlib Migration Principles](./vtlib-migration-principles.md)
- [FreeCRM Architecture Rules](../.cursor/rules/architecture.mdc)
- [PHP Namespace Conventions](../.cursor/rules/php-namespaces.mdc)

---

**Document Status:** Approved - All Questions Answered  
**Last Updated:** 2025-01-XX  
**Next Review:** After Phase 1 completion

