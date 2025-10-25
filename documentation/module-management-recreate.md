# Complete ModuleManagement Recreation Plan

## Executive Summary

This document outlines a **complete recreation** of the legacy vtlib library as a modern, clean-architecture **ModuleManagement** module for FreeCRM. This is an ambitious undertaking that will replace 36+ legacy files with a modern, testable, maintainable system.

**Approach:** Build a complete replacement in parallel, test thoroughly, then switch over  
**Timeline:** 12-16 weeks for initial implementation + 4-8 weeks for migration  
**Risk Level:** High (complete rewrite) - mitigated by parallel development  
**Reward:** Clean architecture, modern patterns, full test coverage

---

## Table of Contents

1. [Vision & Principles](#vision--principles)
2. [Complete Architecture Design](#complete-architecture-design)
3. [Detailed Implementation Steps](#detailed-implementation-steps)
4. [Testing Strategy](#testing-strategy)
5. [Migration & Rollout Plan](#migration--rollout-plan)
6. [Risk Mitigation](#risk-mitigation)
7. [Success Criteria](#success-criteria)

---

## Vision & Principles

### What We're Building

A **modern, enterprise-grade module management system** that:

1. **Manages CRM modules** - creation, updates, deletion, activation
2. **Handles database schema** - automated table/field creation
3. **Manages metadata** - fields, blocks, relationships, filters
4. **Supports packages** - import/export module definitions
5. **Provides events** - extensible lifecycle hooks
6. **Ensures integrity** - validation, transactions, rollback
7. **Maintains audit trail** - who changed what when

### Core Principles

#### 1. **Clean Architecture** (Uncle Bob)
```
┌─────────────────────────────────────┐
│         Entry Points                │ ← Controllers/CLI/API
├─────────────────────────────────────┤
│         Use Cases                   │ ← Application logic
├─────────────────────────────────────┤
│         Domain Models               │ ← Business rules
├─────────────────────────────────────┤
│         Infrastructure              │ ← DB/Files/External
└─────────────────────────────────────┘
```

**Dependencies point inward** - Domain never depends on infrastructure

#### 2. **SOLID Principles**
- **S**ingle Responsibility - each class does one thing
- **O**pen/Closed - extend behavior without modifying code
- **L**iskov Substitution - interfaces are contracts
- **I**nterface Segregation - small, focused interfaces
- **D**ependency Inversion - depend on abstractions

#### 3. **Domain-Driven Design**
- **Ubiquitous Language** - use business terms
- **Bounded Contexts** - clear module boundaries
- **Aggregates** - consistency boundaries
- **Value Objects** - immutable data structures
- **Domain Events** - communicate state changes

#### 4. **Modern PHP Standards**
- **PSR-4** autoloading
- **PSR-12** coding style
- **Strict types** everywhere
- **Type hints** on all parameters
- **Return types** on all methods
- **Readonly properties** (PHP 8.1+)
- **Constructor property promotion**
- **Named arguments**

#### 5. **Test-Driven Development**
- Write tests **first**
- 100% coverage of domain logic
- Integration tests for infrastructure
- End-to-end tests for critical paths

---

## Complete Architecture Design

### Directory Structure

```
/src/ModuleManagement/
│
├── Domain/                           # Business logic (zero dependencies)
│   ├── Module/
│   │   ├── Module.php                # Aggregate root
│   │   ├── ModuleId.php              # Value object
│   │   ├── ModuleName.php            # Value object
│   │   ├── ModuleType.php            # Enum/Value object
│   │   ├── ModuleStatus.php          # Enum
│   │   └── Events/
│   │       ├── ModuleCreated.php
│   │       ├── ModuleActivated.php
│   │       ├── ModuleDeactivated.php
│   │       └── ModuleDeleted.php
│   │
│   ├── Field/
│   │   ├── Field.php                 # Aggregate root
│   │   ├── FieldId.php
│   │   ├── FieldName.php
│   │   ├── FieldType.php             # Maps to uitype
│   │   ├── FieldTypeRegistry.php     # All uitype definitions
│   │   ├── DataType.php
│   │   ├── FieldValidation.php
│   │   ├── DisplayType.php
│   │   └── Events/
│   │       ├── FieldCreated.php
│   │       ├── FieldUpdated.php
│   │       └── FieldDeleted.php
│   │
│   ├── Block/
│   │   ├── Block.php                 # Aggregate root
│   │   ├── BlockId.php
│   │   ├── BlockLabel.php
│   │   ├── BlockSequence.php
│   │   └── Events/
│   │       ├── BlockCreated.php
│   │       └── BlockDeleted.php
│   │
│   ├── Relation/
│   │   ├── Relation.php              # Aggregate root
│   │   ├── RelationType.php          # OneToMany, ManyToMany
│   │   ├── RelationActions.php       # ADD, SELECT
│   │   └── Events/
│   │       ├── RelationCreated.php
│   │       └── RelationDeleted.php
│   │
│   ├── Schema/
│   │   ├── TableDefinition.php
│   │   ├── ColumnDefinition.php
│   │   ├── IndexDefinition.php
│   │   ├── ForeignKeyDefinition.php
│   │   └── DataTypeMapper.php        # Maps Field types to SQL types
│   │
│   ├── Picklist/
│   │   ├── Picklist.php
│   │   ├── PicklistValue.php
│   │   ├── PicklistRole.php          # Role-based access
│   │   └── Events/
│   │       └── PicklistValueAdded.php
│   │
│   ├── Filter/
│   │   ├── CustomFilter.php
│   │   ├── FilterColumn.php
│   │   └── FilterCondition.php
│   │
│   ├── Link/
│   │   ├── ModuleLink.php
│   │   ├── LinkType.php              # LISTVIEW, DETAILVIEW, etc.
│   │   └── LinkLocation.php
│   │
│   ├── Package/
│   │   ├── ModulePackage.php
│   │   ├── Manifest.php
│   │   ├── PackageVersion.php
│   │   └── Dependency.php
│   │
│   ├── Contracts/                    # Domain interfaces
│   │   ├── ModuleRepositoryInterface.php
│   │   ├── FieldRepositoryInterface.php
│   │   ├── BlockRepositoryInterface.php
│   │   ├── EventDispatcherInterface.php
│   │   └── SchemaManagerInterface.php
│   │
│   └── Exceptions/                   # Domain exceptions
│       ├── ModuleException.php
│       ├── ModuleAlreadyExistsException.php
│       ├── ModuleNotFoundException.php
│       ├── InvalidModuleNameException.php
│       ├── FieldException.php
│       └── SchemaException.php
│
├── Application/                      # Use cases (depends on Domain)
│   ├── Commands/                     # Write operations
│   │   ├── CreateModule/
│   │   │   ├── CreateModuleCommand.php
│   │   │   ├── CreateModuleHandler.php
│   │   │   └── CreateModuleValidator.php
│   │   ├── UpdateModule/
│   │   │   ├── UpdateModuleCommand.php
│   │   │   └── UpdateModuleHandler.php
│   │   ├── DeleteModule/
│   │   │   ├── DeleteModuleCommand.php
│   │   │   └── DeleteModuleHandler.php
│   │   ├── ActivateModule/
│   │   │   ├── ActivateModuleCommand.php
│   │   │   └── ActivateModuleHandler.php
│   │   ├── CreateField/
│   │   │   ├── CreateFieldCommand.php
│   │   │   ├── CreateFieldHandler.php
│   │   │   └── CreateFieldValidator.php
│   │   ├── CreateBlock/
│   │   │   ├── CreateBlockCommand.php
│   │   │   └── CreateBlockHandler.php
│   │   ├── CreateRelation/
│   │   │   ├── CreateRelationCommand.php
│   │   │   └── CreateRelationHandler.php
│   │   ├── ImportPackage/
│   │   │   ├── ImportPackageCommand.php
│   │   │   └── ImportPackageHandler.php
│   │   └── ExportPackage/
│   │       ├── ExportPackageCommand.php
│   │       └── ExportPackageHandler.php
│   │
│   ├── Queries/                      # Read operations
│   │   ├── GetModule/
│   │   │   ├── GetModuleQuery.php
│   │   │   └── GetModuleHandler.php
│   │   ├── ListModules/
│   │   │   ├── ListModulesQuery.php
│   │   │   └── ListModulesHandler.php
│   │   ├── GetModuleFields/
│   │   │   ├── GetModuleFieldsQuery.php
│   │   │   └── GetModuleFieldsHandler.php
│   │   └── GetModuleSchema/
│   │       ├── GetModuleSchemaQuery.php
│   │       └── GetModuleSchemaHandler.php
│   │
│   ├── Services/                     # Application services
│   │   ├── ModuleLifecycleService.php
│   │   ├── SchemaGenerationService.php
│   │   ├── PackageService.php
│   │   └── ValidationService.php
│   │
│   └── DTOs/                         # Data Transfer Objects
│       ├── ModuleData.php
│       ├── FieldData.php
│       ├── BlockData.php
│       └── RelationData.php
│
├── Infrastructure/                   # External dependencies
│   ├── Persistence/                  # Database
│   │   ├── Repositories/
│   │   │   ├── ModuleRepository.php
│   │   │   ├── FieldRepository.php
│   │   │   ├── BlockRepository.php
│   │   │   ├── RelationRepository.php
│   │   │   ├── PicklistRepository.php
│   │   │   ├── FilterRepository.php
│   │   │   └── LinkRepository.php
│   │   ├── Mappers/                  # ORM mapping
│   │   │   ├── ModuleMapper.php
│   │   │   ├── FieldMapper.php
│   │   │   └── BlockMapper.php
│   │   └── Migrations/               # Schema migrations
│   │       ├── ModuleTables.php
│   │       └── FieldTables.php
│   │
│   ├── Schema/                       # Database schema management
│   │   ├── SchemaManager.php
│   │   ├── TableBuilder.php
│   │   ├── ColumnBuilder.php
│   │   ├── IndexBuilder.php
│   │   ├── ForeignKeyBuilder.php
│   │   ├── Generators/
│   │   │   ├── EntityTableGenerator.php
│   │   │   ├── CustomTableGenerator.php
│   │   │   └── PicklistTableGenerator.php
│   │   └── Strategies/
│   │       ├── CreateTableStrategy.php
│   │       ├── AlterTableStrategy.php
│   │       └── DropTableStrategy.php
│   │
│   ├── Package/                      # Import/Export
│   │   ├── Readers/
│   │   │   ├── ZipPackageReader.php
│   │   │   ├── ManifestReader.php
│   │   │   └── XmlManifestParser.php
│   │   ├── Writers/
│   │   │   ├── ZipPackageWriter.php
│   │   │   └── ManifestWriter.php
│   │   └── Templates/
│   │       ├── ModuleTemplate.php
│   │       └── TemplateEngine.php
│   │
│   ├── Events/                       # Event infrastructure
│   │   ├── EventDispatcher.php
│   │   ├── EventSubscriberRegistry.php
│   │   └── Subscribers/
│   │       ├── AuditLogSubscriber.php
│   │       ├── CacheClearSubscriber.php
│   │       └── PrivilegeFileSubscriber.php
│   │
│   ├── Cache/                        # Caching layer
│   │   ├── ModuleCacheManager.php
│   │   └── FieldCacheManager.php
│   │
│   └── Legacy/                       # Backward compatibility
│       ├── VtlibAdapter.php          # Wraps new API for old code
│       ├── ModuleAdapter.php
│       └── FieldAdapter.php
│
├── Presentation/                     # UI layer
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ModuleController.php
│   │   │   ├── FieldController.php
│   │   │   └── PackageController.php
│   │   ├── Requests/
│   │   │   ├── CreateModuleRequest.php
│   │   │   ├── CreateFieldRequest.php
│   │   │   └── ImportPackageRequest.php
│   │   └── Resources/                # API responses
│   │       ├── ModuleResource.php
│   │       └── FieldResource.php
│   │
│   └── CLI/
│       ├── Commands/
│       │   ├── CreateModuleCommand.php
│       │   ├── ImportModuleCommand.php
│       │   └── ExportModuleCommand.php
│       └── Output/
│           └── TableFormatter.php
│
├── Config/                           # Configuration
│   ├── module_management.php         # Module config
│   ├── field_types.php               # UIType definitions
│   └── validation_rules.php          # Validation config
│
└── Tests/                            # Tests mirror src structure
    ├── Unit/
    │   ├── Domain/
    │   │   ├── Module/
    │   │   │   ├── ModuleTest.php
    │   │   │   ├── ModuleNameTest.php
    │   │   │   └── ModuleTypeTest.php
    │   │   ├── Field/
    │   │   │   └── FieldTest.php
    │   │   └── Schema/
    │   │       └── TableDefinitionTest.php
    │   └── Application/
    │       ├── CreateModuleHandlerTest.php
    │       └── ImportPackageHandlerTest.php
    │
    ├── Integration/
    │   ├── Persistence/
    │   │   ├── ModuleRepositoryTest.php
    │   │   └── FieldRepositoryTest.php
    │   └── Schema/
    │       └── SchemaManagerTest.php
    │
    ├── Feature/                      # End-to-end tests
    │   ├── CreateModuleTest.php
    │   ├── ImportModuleTest.php
    │   └── DeleteModuleTest.php
    │
    └── Fixtures/                     # Test data
        ├── Modules/
        ├── Fields/
        └── Packages/
```

---

## Detailed Implementation Steps

### STEP 1: Project Setup & Foundation (Week 1)

#### 1.1 Create Directory Structure
```bash
mkdir -p src/ModuleManagement/{Domain,Application,Infrastructure,Presentation,Config,Tests}
mkdir -p src/ModuleManagement/Domain/{Module,Field,Block,Relation,Schema,Picklist,Filter,Link,Package,Contracts,Exceptions}
# ... (complete structure from above)
```

#### 1.2 Setup Composer Dependencies
```json
{
  "require": {
    "ramsey/uuid": "^4.7",
    "symfony/console": "^6.0",
    "symfony/validator": "^6.0",
    "symfony/event-dispatcher": "^6.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0",
    "mockery/mockery": "^1.5",
    "phpstan/phpstan": "^1.10",
    "squizlabs/php_codesniffer": "^3.7"
  },
  "autoload": {
    "psr-4": {
      "App\\ModuleManagement\\": "src/ModuleManagement/"
    }
  }
}
```

#### 1.3 Create Base Interfaces
```php
<?php
// src/ModuleManagement/Domain/Contracts/ModuleRepositoryInterface.php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Contracts;

use App\ModuleManagement\Domain\Module\Module;
use App\ModuleManagement\Domain\Module\ModuleId;
use App\ModuleManagement\Domain\Module\ModuleName;

interface ModuleRepositoryInterface
{
    public function save(Module $module): void;
    public function findById(ModuleId $id): ?Module;
    public function findByName(ModuleName $name): ?Module;
    public function exists(ModuleName $name): bool;
    public function delete(ModuleId $id): void;
    public function all(): array;
}
```

#### 1.4 Setup Testing Framework
```php
<?php
// tests/TestCase.php

declare(strict_types=1);

namespace App\ModuleManagement\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Setup test database
        // Clear caches
    }

    protected function tearDown(): void
    {
        // Cleanup
        parent::tearDown();
    }
}
```

**Deliverables:**
- ✅ Complete directory structure
- ✅ Composer configured
- ✅ Base interfaces defined
- ✅ Test framework ready

---

### STEP 2: Domain Model - Module Aggregate (Week 2)

#### 2.1 Create Value Objects

**ModuleId.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Module;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class ModuleId
{
    private function __construct(
        private UuidInterface $value
    ) {}

    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public static function fromInt(int $id): self
    {
        // For compatibility with legacy integer IDs
        return new self(Uuid::fromInteger($id));
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function toInt(): int
    {
        // Extract integer representation for legacy compatibility
        return (int) hexdec(substr($this->value->toString(), 0, 8));
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }
}
```

**ModuleName.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Module;

use App\ModuleManagement\Domain\Exceptions\InvalidModuleNameException;

final readonly class ModuleName
{
    private const PATTERN = '/^[A-Z][A-Za-z0-9_]{2,63}$/';
    private const RESERVED = [
        'Admin', 'System', 'Root', 'Config', 'Database', 'Session'
    ];

    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    private function validate(): void
    {
        if (!preg_match(self::PATTERN, $this->value)) {
            throw new InvalidModuleNameException(
                "Module name must start with uppercase letter and contain only alphanumeric characters and underscores: {$this->value}"
            );
        }

        if (in_array($this->value, self::RESERVED, true)) {
            throw new InvalidModuleNameException(
                "Module name '{$this->value}' is reserved"
            );
        }

        if (strlen($this->value) < 3 || strlen($this->value) > 64) {
            throw new InvalidModuleNameException(
                "Module name must be between 3 and 64 characters"
            );
        }
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

**ModuleType.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Module;

enum ModuleType: int
{
    case STANDARD = 0;      // Regular entity module
    case INVENTORY = 1;     // Inventory module (Quotes, Invoices)
    case EXTENSION = 2;     // Non-entity extension
    case UTILITY = 3;       // System utility module

    public function isEntityType(): bool
    {
        return $this === self::STANDARD || $this === self::INVENTORY;
    }

    public function requiresSchema(): bool
    {
        return $this->isEntityType();
    }

    public function label(): string
    {
        return match($this) {
            self::STANDARD => 'Standard Entity Module',
            self::INVENTORY => 'Inventory Module',
            self::EXTENSION => 'Extension Module',
            self::UTILITY => 'Utility Module',
        };
    }
}
```

**ModuleStatus.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Module;

enum ModuleStatus: int
{
    case ACTIVE = 0;
    case INACTIVE = 1;
    case HIDDEN = 2;

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isVisible(): bool
    {
        return $this === self::ACTIVE || $this === self::INACTIVE;
    }
}
```

#### 2.2 Create Aggregate Root

**Module.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Module;

use App\ModuleManagement\Domain\Exceptions\ModuleException;
use App\ModuleManagement\Domain\Module\Events\ModuleCreated;
use App\ModuleManagement\Domain\Module\Events\ModuleActivated;
use App\ModuleManagement\Domain\Module\Events\ModuleDeactivated;
use App\ModuleManagement\Domain\Module\Events\ModuleDeleted;
use DateTimeImmutable;

final class Module
{
    private array $domainEvents = [];

    private function __construct(
        private readonly ModuleId $id,
        private ModuleName $name,
        private string $label,
        private ModuleType $type,
        private ModuleStatus $status,
        private ?string $version,
        private readonly ?int $ownedBy,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $modifiedAt = null,
    ) {}

    public static function create(
        ModuleName $name,
        string $label,
        ModuleType $type,
        ?string $version = null,
        ?int $ownedBy = null
    ): self {
        $module = new self(
            id: ModuleId::generate(),
            name: $name,
            label: $label,
            type: $type,
            status: ModuleStatus::ACTIVE,
            version: $version ?? '1.0.0',
            ownedBy: $ownedBy ?? 0,
            createdAt: new DateTimeImmutable(),
        );

        $module->recordEvent(new ModuleCreated(
            moduleId: $module->id,
            moduleName: $module->name,
            moduleType: $module->type,
            occurredAt: new DateTimeImmutable()
        ));

        return $module;
    }

    public function activate(): void
    {
        if ($this->status->isActive()) {
            throw new ModuleException("Module {$this->name} is already active");
        }

        $this->status = ModuleStatus::ACTIVE;
        $this->modifiedAt = new DateTimeImmutable();

        $this->recordEvent(new ModuleActivated(
            moduleId: $this->id,
            occurredAt: new DateTimeImmutable()
        ));
    }

    public function deactivate(): void
    {
        if (!$this->status->isActive()) {
            throw new ModuleException("Module {$this->name} is not active");
        }

        $this->status = ModuleStatus::INACTIVE;
        $this->modifiedAt = new DateTimeImmutable();

        $this->recordEvent(new ModuleDeactivated(
            moduleId: $this->id,
            occurredAt: new DateTimeImmutable()
        ));
    }

    public function updateLabel(string $label): void
    {
        $this->label = $label;
        $this->modifiedAt = new DateTimeImmutable();
    }

    public function updateVersion(string $version): void
    {
        $this->version = $version;
        $this->modifiedAt = new DateTimeImmutable();
    }

    // Getters
    public function id(): ModuleId { return $this->id; }
    public function name(): ModuleName { return $this->name; }
    public function label(): string { return $this->label; }
    public function type(): ModuleType { return $this->type; }
    public function status(): ModuleStatus { return $this->status; }
    public function version(): ?string { return $this->version; }
    public function isActive(): bool { return $this->status->isActive(); }
    public function isEntityType(): bool { return $this->type->isEntityType(); }

    // Event handling
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
```

#### 2.3 Create Domain Events

**ModuleCreated.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Module\Events;

use App\ModuleManagement\Domain\Module\ModuleId;
use App\ModuleManagement\Domain\Module\ModuleName;
use App\ModuleManagement\Domain\Module\ModuleType;
use DateTimeImmutable;

final readonly class ModuleCreated
{
    public function __construct(
        public ModuleId $moduleId,
        public ModuleName $moduleName,
        public ModuleType $moduleType,
        public DateTimeImmutable $occurredAt,
    ) {}
}
```

#### 2.4 Write Tests

**ModuleTest.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Tests\Unit\Domain\Module;

use App\ModuleManagement\Domain\Module\Module;
use App\ModuleManagement\Domain\Module\ModuleName;
use App\ModuleManagement\Domain\Module\ModuleType;
use App\ModuleManagement\Domain\Module\ModuleStatus;
use App\ModuleManagement\Domain\Module\Events\ModuleCreated;
use App\ModuleManagement\Tests\TestCase;

final class ModuleTest extends TestCase
{
    public function test_can_create_module(): void
    {
        $module = Module::create(
            name: ModuleName::fromString('TestModule'),
            label: 'Test Module',
            type: ModuleType::STANDARD
        );

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals('TestModule', $module->name()->toString());
        $this->assertEquals('Test Module', $module->label());
        $this->assertEquals(ModuleType::STANDARD, $module->type());
        $this->assertTrue($module->isActive());
    }

    public function test_module_creation_records_event(): void
    {
        $module = Module::create(
            name: ModuleName::fromString('TestModule'),
            label: 'Test Module',
            type: ModuleType::STANDARD
        );

        $events = $module->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ModuleCreated::class, $events[0]);
    }

    public function test_can_activate_inactive_module(): void
    {
        $module = Module::create(
            name: ModuleName::fromString('TestModule'),
            label: 'Test Module',
            type: ModuleType::STANDARD
        );

        $module->deactivate();
        $module->activate();

        $this->assertTrue($module->isActive());
    }

    public function test_cannot_activate_already_active_module(): void
    {
        $this->expectException(\App\ModuleManagement\Domain\Exceptions\ModuleException::class);

        $module = Module::create(
            name: ModuleName::fromString('TestModule'),
            label: 'Test Module',
            type: ModuleType::STANDARD
        );

        $module->activate(); // Should throw
    }
}
```

**Deliverables:**
- ✅ Complete Module aggregate
- ✅ All value objects
- ✅ Domain events
- ✅ 100% test coverage

---

### STEP 3: Domain Model - Field Aggregate (Week 3)

#### 3.1 Create Field Value Objects

**FieldType.php** - Maps to legacy uitype
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Field;

enum FieldType: int
{
    case TEXT = 1;
    case TEXTAREA = 19;
    case EMAIL = 13;
    case PHONE = 11;
    case URL = 17;
    case NUMBER = 7;
    case DECIMAL = 71;
    case CURRENCY = 72;
    case PERCENT = 9;
    case DATE = 5;
    case DATETIME = 50;
    case TIME = 14;
    case CHECKBOX = 56;
    case PICKLIST = 15;
    case MULTI_PICKLIST = 33;
    case REFERENCE = 10;
    case OWNER = 53;
    case AUTO_NUMBER = 4;
    case IMAGE = 69;
    case FILE = 28;
    // ... all 100+ uitypes

    public function requiresPicklist(): bool
    {
        return match($this) {
            self::PICKLIST, self::MULTI_PICKLIST => true,
            default => false,
        };
    }

    public function requiresRelationship(): bool
    {
        return $this === self::REFERENCE;
    }

    public function isEditable(): bool
    {
        return match($this) {
            self::AUTO_NUMBER => false,
            default => true,
        };
    }

    public function getSqlDataType(): string
    {
        return match($this) {
            self::TEXT => 'VARCHAR(255)',
            self::TEXTAREA => 'TEXT',
            self::NUMBER => 'INT',
            self::DECIMAL, self::CURRENCY => 'DECIMAL(10,2)',
            self::DATE => 'DATE',
            self::DATETIME => 'DATETIME',
            self::CHECKBOX => 'TINYINT(1)',
            // ... complete mapping
            default => 'VARCHAR(255)',
        };
    }
}
```

**Field.php** - Aggregate Root
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Field;

use App\ModuleManagement\Domain\Module\ModuleId;
use App\ModuleManagement\Domain\Block\BlockId;
use App\ModuleManagement\Domain\Field\Events\FieldCreated;
use DateTimeImmutable;

final class Field
{
    private array $domainEvents = [];

    private function __construct(
        private readonly FieldId $id,
        private readonly ModuleId $moduleId,
        private readonly BlockId $blockId,
        private FieldName $name,
        private string $label,
        private FieldType $type,
        private string $column,
        private ?string $table,
        private DataType $dataType,
        private DisplayType $displayType,
        private int $sequence,
        private bool $mandatory,
        private ?array $picklist Values = null,
        private ?array $relatedModules = null,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        ModuleId $moduleId,
        BlockId $blockId,
        FieldName $name,
        string $label,
        FieldType $type,
        string $column,
        DataType $dataType,
        ?string $table = null,
    ): self {
        $field = new self(
            id: FieldId::generate(),
            moduleId: $moduleId,
            blockId: $blockId,
            name: $name,
            label: $label,
            type: $type,
            column: $column,
            table: $table,
            dataType: $dataType,
            displayType: DisplayType::EDITABLE,
            sequence: 0,
            mandatory: false,
            createdAt: new DateTimeImmutable(),
        );

        $field->recordEvent(new FieldCreated(
            fieldId: $field->id,
            moduleId: $moduleId,
            fieldName: $name,
            fieldType: $type,
            occurredAt: new DateTimeImmutable()
        ));

        return $field;
    }

    public function setPicklistValues(array $values): void
    {
        if (!$this->type->requiresPicklist()) {
            throw new \DomainException("Field type {$this->type->name} does not support picklist");
        }

        $this->picklistValues = $values;
    }

    public function setRelatedModules(array $modules): void
    {
        if (!$this->type->requiresRelationship()) {
            throw new \DomainException("Field type {$this->type->name} does not support relationships");
        }

        $this->relatedModules = $modules;
    }

    public function makeMandatory(): void
    {
        $this->mandatory = true;
    }

    public function makeOptional(): void
    {
        $this->mandatory = false;
    }

    // Getters
    public function id(): FieldId { return $this->id; }
    public function moduleId(): ModuleId { return $this->moduleId; }
    public function name(): FieldName { return $this->name; }
    public function type(): FieldType { return $this->type; }
    public function isMandatory(): bool { return $this->mandatory; }

    // Event handling
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
```

**Deliverables:**
- ✅ Complete Field aggregate
- ✅ All 100+ FieldType enum values
- ✅ Field validation
- ✅ 100% test coverage

---

### STEP 4: Domain Model - Schema Management (Week 4)

#### 4.1 Create Schema Value Objects

**TableDefinition.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Schema;

final readonly class TableDefinition
{
    /**
     * @param ColumnDefinition[] $columns
     * @param IndexDefinition[] $indexes
     * @param ForeignKeyDefinition[] $foreignKeys
     */
    public function __construct(
        private string $name,
        private array $columns,
        private array $indexes = [],
        private array $foreignKeys = [],
        private ?string $engine = 'InnoDB',
        private ?string $charset = 'utf8mb4',
        private ?string $collation = 'utf8mb4_unicode_ci',
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function toSql(): string
    {
        $sql = "CREATE TABLE `{$this->name}` (\n";

        // Columns
        $columnsSql = array_map(
            fn(ColumnDefinition $col) => "  " . $col->toSql(),
            $this->columns
        );
        $sql .= implode(",\n", $columnsSql);

        // Indexes
        if (!empty($this->indexes)) {
            $indexesSql = array_map(
                fn(IndexDefinition $idx) => "  " . $idx->toSql(),
                $this->indexes
            );
            $sql .= ",\n" . implode(",\n", $indexesSql);
        }

        // Foreign keys
        if (!empty($this->foreignKeys)) {
            $fksSql = array_map(
                fn(ForeignKeyDefinition $fk) => "  " . $fk->toSql(),
                $this->foreignKeys
            );
            $sql .= ",\n" . implode(",\n", $fksSql);
        }

        $sql .= "\n)";
        $sql .= " ENGINE={$this->engine}";
        $sql .= " DEFAULT CHARSET={$this->charset}";
        $sql .= " COLLATE={$this->collation}";

        return $sql;
    }
}
```

**ColumnDefinition.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Schema;

final readonly class ColumnDefinition
{
    public function __construct(
        private string $name,
        private string $type,
        private bool $nullable = false,
        private ?string $default = null,
        private bool $autoIncrement = false,
        private bool $primaryKey = false,
        private ?string $comment = null,
    ) {}

    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";

        if (!$this->nullable) {
            $sql .= " NOT NULL";
        }

        if ($this->default !== null) {
            $sql .= " DEFAULT {$this->default}";
        }

        if ($this->autoIncrement) {
            $sql .= " AUTO_INCREMENT";
        }

        if ($this->primaryKey) {
            $sql .= " PRIMARY KEY";
        }

        if ($this->comment !== null) {
            $sql .= " COMMENT '{$this->comment}'";
        }

        return $sql;
    }
}
```

#### 4.2 Create Schema Service Interface

```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Domain\Contracts;

use App\ModuleManagement\Domain\Module\Module;
use App\ModuleManagement\Domain\Field\Field;
use App\ModuleManagement\Domain\Schema\TableDefinition;

interface SchemaManagerInterface
{
    /**
     * Create all tables required for a module
     */
    public function createModuleTables(Module $module): void;

    /**
     * Add a column for a new field
     */
    public function addColumn(Field $field): void;

    /**
     * Drop all tables for a module
     */
    public function dropModuleTables(Module $module): void;

    /**
     * Create a custom table
     */
    public function createTable(TableDefinition $definition): void;

    /**
     * Check if table exists
     */
    public function tableExists(string $tableName): bool;

    /**
     * Rollback schema changes (for failed operations)
     */
    public function rollback(): void;
}
```

**Deliverables:**
- ✅ Schema domain models
- ✅ SQL generation
- ✅ Schema validation
- ✅ Tests

---

### STEP 5: Application Layer - Commands (Week 5-6)

#### 5.1 Create Command Pattern

**CreateModuleCommand.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Application\Commands\CreateModule;

final readonly class CreateModuleCommand
{
    public function __construct(
        public string $name,
        public string $label,
        public int $type,
        public ?string $version = null,
        public ?int $ownedBy = null,
    ) {}
}
```

**CreateModuleHandler.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Application\Commands\CreateModule;

use App\ModuleManagement\Domain\Module\Module;
use App\ModuleManagement\Domain\Module\ModuleName;
use App\ModuleManagement\Domain\Module\ModuleType;
use App\ModuleManagement\Domain\Contracts\ModuleRepositoryInterface;
use App\ModuleManagement\Domain\Contracts\SchemaManagerInterface;
use App\ModuleManagement\Domain\Contracts\EventDispatcherInterface;
use App\ModuleManagement\Domain\Exceptions\ModuleAlreadyExistsException;

final readonly class CreateModuleHandler
{
    public function __construct(
        private ModuleRepositoryInterface $moduleRepository,
        private SchemaManagerInterface $schemaManager,
        private EventDispatcherInterface $eventDispatcher,
        private CreateModuleValidator $validator,
    ) {}

    public function handle(CreateModuleCommand $command): Module
    {
        // Validate
        $this->validator->validate($command);

        // Create domain object
        $moduleName = ModuleName::fromString($command->name);
        
        // Check existence
        if ($this->moduleRepository->exists($moduleName)) {
            throw new ModuleAlreadyExistsException(
                "Module '{$command->name}' already exists"
            );
        }

        // Create module
        $module = Module::create(
            name: $moduleName,
            label: $command->label,
            type: ModuleType::from($command->type),
            version: $command->version,
            ownedBy: $command->ownedBy,
        );

        // Begin transaction
        \App\Db::getInstance()->beginTransaction();

        try {
            // Save to repository
            $this->moduleRepository->save($module);

            // Create schema if needed
            if ($module->type()->requiresSchema()) {
                $this->schemaManager->createModuleTables($module);
            }

            // Dispatch events
            foreach ($module->pullDomainEvents() as $event) {
                $this->eventDispatcher->dispatch($event);
            }

            \App\Db::getInstance()->commit();

        } catch (\Exception $e) {
            \App\Db::getInstance()->rollBack();
            $this->schemaManager->rollback();
            throw $e;
        }

        return $module;
    }
}
```

**CreateModuleValidator.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Application\Commands\CreateModule;

use App\ModuleManagement\Domain\Exceptions\ValidationException;

final class CreateModuleValidator
{
    public function validate(CreateModuleCommand $command): void
    {
        $errors = [];

        // Validate name
        if (empty($command->name)) {
            $errors['name'] = 'Module name is required';
        }

        // Validate label
        if (empty($command->label)) {
            $errors['label'] = 'Module label is required';
        }

        // Validate type
        if (!in_array($command->type, [0, 1, 2, 3], true)) {
            $errors['type'] = 'Invalid module type';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
```

#### 5.2 Create Other Commands

- **UpdateModuleCommand/Handler**
- **DeleteModuleCommand/Handler**
- **ActivateModuleCommand/Handler**
- **CreateFieldCommand/Handler**
- **CreateBlockCommand/Handler**
- **CreateRelationCommand/Handler**

Similar pattern for all

**Deliverables:**
- ✅ All command handlers
- ✅ Validation layer
- ✅ Transaction management
- ✅ Event dispatching
- ✅ Tests for each handler

---

### STEP 6: Infrastructure - Persistence (Week 7-8)

#### 6.1 Create Repository Implementation

**ModuleRepository.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Infrastructure\Persistence\Repositories;

use App\ModuleManagement\Domain\Module\Module;
use App\ModuleManagement\Domain\Module\ModuleId;
use App\ModuleManagement\Domain\Module\ModuleName;
use App\ModuleManagement\Domain\Contracts\ModuleRepositoryInterface;
use App\ModuleManagement\Infrastructure\Persistence\Mappers\ModuleMapper;
use App\Db;

final class ModuleRepository implements ModuleRepositoryInterface
{
    public function __construct(
        private readonly ModuleMapper $mapper
    ) {}

    public function save(Module $module): void
    {
        $data = $this->mapper->toDatabase($module);

        $exists = (new \App\Db\Query())
            ->from('vtiger_tab')
            ->where(['tabid' => $data['tabid']])
            ->exists();

        if ($exists) {
            Db::getInstance()
                ->createCommand()
                ->update('vtiger_tab', $data, ['tabid' => $data['tabid']])
                ->execute();
        } else {
            Db::getInstance()
                ->createCommand()
                ->insert('vtiger_tab', $data)
                ->execute();
        }
    }

    public function findById(ModuleId $id): ?Module
    {
        $row = (new \App\Db\Query())
            ->from('vtiger_tab')
            ->where(['tabid' => $id->toInt()])
            ->one();

        if (!$row) {
            return null;
        }

        return $this->mapper->toDomain($row);
    }

    public function findByName(ModuleName $name): ?Module
    {
        $row = (new \App\Db\Query())
            ->from('vtiger_tab')
            ->where(['name' => $name->toString()])
            ->one();

        if (!$row) {
            return null;
        }

        return $this->mapper->toDomain($row);
    }

    public function exists(ModuleName $name): bool
    {
        return (new \App\Db\Query())
            ->from('vtiger_tab')
            ->where(['name' => $name->toString()])
            ->exists();
    }

    public function delete(ModuleId $id): void
    {
        Db::getInstance()
            ->createCommand()
            ->delete('vtiger_tab', ['tabid' => $id->toInt()])
            ->execute();
    }

    public function all(): array
    {
        $rows = (new \App\Db\Query())
            ->from('vtiger_tab')
            ->all();

        return array_map(
            fn($row) => $this->mapper->toDomain($row),
            $rows
        );
    }
}
```

#### 6.2 Create Mapper

**ModuleMapper.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Infrastructure\Persistence\Mappers;

use App\ModuleManagement\Domain\Module\Module;
use App\ModuleManagement\Domain\Module\ModuleId;
use App\ModuleManagement\Domain\Module\ModuleName;
use App\ModuleManagement\Domain\Module\ModuleType;
use App\ModuleManagement\Domain\Module\ModuleStatus;

final class ModuleMapper
{
    public function toDomain(array $row): Module
    {
        // Use reflection to create Module without calling constructor
        $reflection = new \ReflectionClass(Module::class);
        $module = $reflection->newInstanceWithoutConstructor();

        // Set properties via reflection
        $this->setProperty($module, 'id', ModuleId::fromInt((int)$row['tabid']));
        $this->setProperty($module, 'name', ModuleName::fromString($row['name']));
        $this->setProperty($module, 'label', $row['tablabel']);
        $this->setProperty($module, 'type', ModuleType::from((int)$row['type']));
        $this->setProperty($module, 'status', ModuleStatus::from((int)$row['presence']));
        $this->setProperty($module, 'version', $row['version'] ?? null);
        $this->setProperty($module, 'ownedBy', (int)$row['ownedby']);
        
        // Set dates
        $this->setProperty($module, 'createdAt', new \DateTimeImmutable());
        if ($row['modifiedtime']) {
            $this->setProperty($module, 'modifiedAt', new \DateTimeImmutable($row['modifiedtime']));
        }

        return $module;
    }

    public function toDatabase(Module $module): array
    {
        return [
            'tabid' => $module->id()->toInt(),
            'name' => $module->name()->toString(),
            'tablabel' => $module->label(),
            'type' => $module->type()->value,
            'presence' => $module->status()->value,
            'version' => $module->version(),
            'ownedby' => 0, // Get from module
            'customized' => 1,
            'isentitytype' => $module->isEntityType() ? 1 : 0,
        ];
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
```

**Deliverables:**
- ✅ All repository implementations
- ✅ Mappers for all aggregates
- ✅ Database access abstraction
- ✅ Integration tests

---

### STEP 7: Infrastructure - Schema Manager (Week 9)

**SchemaManager.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Infrastructure\Schema;

use App\ModuleManagement\Domain\Module\Module;
use App\ModuleManagement\Domain\Field\Field;
use App\ModuleManagement\Domain\Schema\TableDefinition;
use App\ModuleManagement\Domain\Contracts\SchemaManagerInterface;
use App\ModuleManagement\Infrastructure\Schema\Generators\EntityTableGenerator;
use App\ModuleManagement\Infrastructure\Schema\Generators\CustomTableGenerator;
use App\Db;

final class SchemaManager implements SchemaManagerInterface
{
    private array $executedStatements = [];

    public function __construct(
        private readonly EntityTableGenerator $entityTableGenerator,
        private readonly CustomTableGenerator $customTableGenerator,
    ) {}

    public function createModuleTables(Module $module): void
    {
        if (!$module->type()->requiresSchema()) {
            return;
        }

        $tables = [
            // Entity table (e.g., vtiger_accounts)
            $this->entityTableGenerator->generateEntityTable($module),
            
            // Custom fields table (e.g., vtiger_accountscf)
            $this->customTableGenerator->generateCustomTable($module),
        ];

        foreach ($tables as $table) {
            $this->createTable($table);
        }

        // Register in vtiger_entityname
        $this->registerEntityName($module);
    }

    public function createTable(TableDefinition $definition): void
    {
        if ($this->tableExists($definition->name())) {
            throw new \RuntimeException("Table {$definition->name()} already exists");
        }

        $sql = $definition->toSql();
        
        Db::getInstance()->createCommand($sql)->execute();
        
        $this->executedStatements[] = [
            'type' => 'CREATE_TABLE',
            'table' => $definition->name(),
            'sql' => $sql,
        ];
    }

    public function addColumn(Field $field): void
    {
        // Implementation for adding columns
        $table = $field->table() ?? $this->getDefaultTableForModule($field->moduleId());
        $column = $field->column();
        $type = $field->type()->getSqlDataType();

        $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$type}";
        
        if ($field->isMandatory()) {
            $sql .= " NOT NULL";
        }

        Db::getInstance()->createCommand($sql)->execute();
        
        $this->executedStatements[] = [
            'type' => 'ADD_COLUMN',
            'table' => $table,
            'column' => $column,
            'sql' => $sql,
        ];
    }

    public function dropModuleTables(Module $module): void
    {
        $tableName = 'vtiger_' . strtolower($module->name()->toString());
        $customTableName = $tableName . 'cf';

        $tables = [$customTableName, $tableName];

        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                Db::getInstance()
                    ->createCommand()
                    ->dropTable($table)
                    ->execute();
            }
        }

        // Remove from vtiger_entityname
        Db::getInstance()
            ->createCommand()
            ->delete('vtiger_entityname', ['modulename' => $module->name()->toString()])
            ->execute();
    }

    public function tableExists(string $tableName): bool
    {
        return Db::getInstance()
            ->getSchema()
            ->getTableSchema($tableName) !== null;
    }

    public function rollback(): void
    {
        // Rollback in reverse order
        foreach (array_reverse($this->executedStatements) as $statement) {
            try {
                match($statement['type']) {
                    'CREATE_TABLE' => $this->dropTable($statement['table']),
                    'ADD_COLUMN' => $this->dropColumn($statement['table'], $statement['column']),
                    default => null,
                };
            } catch (\Exception $e) {
                // Log but continue rollback
                \App\Log::error("Rollback failed for {$statement['type']}: " . $e->getMessage());
            }
        }

        $this->executedStatements = [];
    }

    private function dropTable(string $tableName): void
    {
        if ($this->tableExists($tableName)) {
            Db::getInstance()
                ->createCommand()
                ->dropTable($tableName)
                ->execute();
        }
    }

    private function dropColumn(string $table, string $column): void
    {
        Db::getInstance()
            ->createCommand("ALTER TABLE `{$table}` DROP COLUMN `{$column}`")
            ->execute();
    }

    private function registerEntityName(Module $module): void
    {
        $tableName = 'vtiger_' . strtolower($module->name()->toString());
        
        Db::getInstance()->createCommand()->insert('vtiger_entityname', [
            'modulename' => $module->name()->toString(),
            'tablename' => $tableName,
            'entityidfield' => $tableName . 'id',
            'entityidcolumn' => $tableName . 'id',
            'fieldname' => $tableName . 'name',
            'searchcolumn' => $tableName . 'name',
            'turn_off' => 1,
        ])->execute();
    }
}
```

**Deliverables:**
- ✅ Complete schema manager
- ✅ Table generators
- ✅ Rollback capability
- ✅ Tests

---

### STEP 8: Infrastructure - Package Management (Week 10)

**ModuleImporter.php**
```php
<?php

declare(strict_types=1);

namespace App\ModuleManagement\Infrastructure\Package\Readers;

use App\ModuleManagement\Application\Commands\CreateModule\CreateModuleCommand;
use App\ModuleManagement\Application\Commands\CreateModule\CreateModuleHandler;
use App\ModuleManagement\Domain\Module\Module;

final class ModuleImporter
{
    public function __construct(
        private readonly ZipPackageReader $zipReader,
        private readonly ManifestReader $manifestReader,
        private readonly CreateModuleHandler $createModuleHandler,
        // ... other handlers
    ) {}

    public function import(string $zipPath): Module
    {
        // Extract package
        $package = $this->zipReader->read($zipPath);
        
        // Parse manifest
        $manifest = $this->manifestReader->read($package->getManifestPath());
        
        // Create module
        $command = new CreateModuleCommand(
            name: $manifest->getModuleName(),
            label: $manifest->getModuleLabel(),
            type: $manifest->getModuleType(),
            version: $manifest->getVersion(),
        );
        
        $module = $this->createModuleHandler->handle($command);
        
        // Import blocks, fields, filters, etc.
        // ... (similar pattern)
        
        return $module;
    }
}
```

**Deliverables:**
- ✅ Package importer
- ✅ Package exporter
- ✅ Manifest parser
- ✅ Backward compatible format
- ✅ Tests

---

### STEP 9: Legacy Compatibility Layer (Week 11)

**VtlibFacade.php** - Main adapter
```php
<?php
// Keep vtlib namespace for backward compatibility
namespace vtlib;

use App\ModuleManagement\Application\Commands\CreateModule\CreateModuleCommand;
use App\ModuleManagement\Application\Commands\CreateModule\CreateModuleHandler;
use App\ModuleManagement\Infrastructure\Legacy\ModuleAdapter;

/**
 * @deprecated Use App\ModuleManagement\ classes instead
 */
class Module extends ModuleBasic
{
    public function save()
    {
        @trigger_error(
            'vtlib\Module is deprecated. Use ModuleManagement services instead.',
            E_USER_DEPRECATED
        );

        // Delegate to new system
        $handler = app(CreateModuleHandler::class);
        
        $command = new CreateModuleCommand(
            name: $this->name,
            label: $this->label,
            type: $this->type ?? 0,
        );
        
        $module = $handler->handle($command);
        
        // Update this object with new IDs
        $this->id = $module->id()->toInt();
        
        return $this;
    }

    // ... all other methods delegate to new system
}
```

**Key Principles for Facade:**
1. Keep exact same public API
2. Deprecation warnings
3. Delegate to new system
4. No new features

**Deliverables:**
- ✅ Complete facade for vtlib\Module
- ✅ Complete facade for vtlib\Field
- ✅ Complete facade for vtlib\Block
- ✅ Deprecation warnings
- ✅ Tests proving compatibility

---

### STEP 10: Testing (Week 12-13)

#### 10.1 Unit Tests (100% Domain Coverage)

```bash
./vendor/bin/phpunit tests/Unit/
```

All domain models, value objects, events

#### 10.2 Integration Tests

**ModuleRepositoryTest.php**
```php
<?php

namespace App\ModuleManagement\Tests\Integration;

use App\ModuleManagement\Infrastructure\Persistence\Repositories\ModuleRepository;
use App\ModuleManagement\Domain\Module\Module;
use App\ModuleManagement\Domain\Module\ModuleName;
use App\ModuleManagement\Domain\Module\ModuleType;

final class ModuleRepositoryTest extends IntegrationTestCase
{
    private ModuleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ModuleRepository::class);
        $this->cleanDatabase();
    }

    public function test_can_save_and_retrieve_module(): void
    {
        $module = Module::create(
            name: ModuleName::fromString('TestModule'),
            label: 'Test Module',
            type: ModuleType::STANDARD
        );

        $this->repository->save($module);

        $retrieved = $this->repository->findById($module->id());

        $this->assertNotNull($retrieved);
        $this->assertEquals($module->name()->toString(), $retrieved->name()->toString());
    }
}
```

#### 10.3 Feature Tests (End-to-End)

**CreateModuleTest.php**
```php
<?php

namespace App\ModuleManagement\Tests\Feature;

use App\ModuleManagement\Application\Commands\CreateModule\CreateModuleCommand;
use App\ModuleManagement\Application\Commands\CreateModule\CreateModuleHandler;

final class CreateModuleTest extends FeatureTestCase
{
    public function test_complete_module_creation_workflow(): void
    {
        $handler = app(CreateModuleHandler::class);
        
        $command = new CreateModuleCommand(
            name: 'Products',
            label: 'Products',
            type: 0, // STANDARD
        );

        $module = $handler->handle($command);

        // Verify module exists in database
        $this->assertDatabaseHas('vtiger_tab', [
            'name' => 'Products',
            'tablabel' => 'Products',
        ]);

        // Verify tables created
        $this->assertTrue(
            \App\Db::getInstance()->getSchema()->getTableSchema('vtiger_products') !== null
        );

        // Verify entity registered
        $this->assertDatabaseHas('vtiger_entityname', [
            'modulename' => 'Products',
        ]);
    }
}
```

#### 10.4 Backward Compatibility Tests

**VtlibCompatibilityTest.php**
```php
<?php

namespace App\ModuleManagement\Tests\Feature;

final class VtlibCompatibilityTest extends FeatureTestCase
{
    public function test_old_vtlib_code_still_works(): void
    {
        // This is OLD CODE pattern - must still work!
        $module = new \vtlib\Module();
        $module->name = 'LegacyModule';
        $module->label = 'Legacy Module';
        $module->save();

        // Verify it actually created the module
        $this->assertDatabaseHas('vtiger_tab', [
            'name' => 'LegacyModule',
        ]);
    }
}
```

**Test Coverage Goals:**
- ✅ Domain: 100%
- ✅ Application: 95%
- ✅ Infrastructure: 85%
- ✅ Overall: 90%+

**Deliverables:**
- ✅ 200+ unit tests
- ✅ 50+ integration tests
- ✅ 20+ feature tests
- ✅ 10+ compatibility tests
- ✅ Coverage reports

---

### STEP 11: Documentation (Week 14)

#### 11.1 Architecture Documentation

Create `/docs/ModuleManagement/`:
- `architecture.md` - System overview
- `domain-model.md` - Domain design
- `api-reference.md` - API documentation
- `migration-guide.md` - How to migrate from vtlib

#### 11.2 API Documentation

Use PHPDoc with examples:

```php
/**
 * Create a new CRM module
 *
 * @example
 * ```php
 * $handler = app(CreateModuleHandler::class);
 * $command = new CreateModuleCommand(
 *     name: 'Products',
 *     label: 'Products',
 *     type: 0
 * );
 * $module = $handler->handle($command);
 * ```
 *
 * @throws ModuleAlreadyExistsException
 * @throws ValidationException
 */
public function handle(CreateModuleCommand $command): Module
```

#### 11.3 Migration Guide

**From:**
```php
$module = new vtlib\Module();
$module->name = 'Products';
$module->save();
```

**To:**
```php
$handler = app(CreateModuleHandler::class);
$module = $handler->handle(new CreateModuleCommand(
    name: 'Products',
    label: 'Products',
    type: ModuleType::STANDARD
));
```

**Deliverables:**
- ✅ Complete architecture docs
- ✅ API reference
- ✅ Migration guide
- ✅ Examples
- ✅ Diagrams

---

### STEP 12: Integration & Migration (Week 15-16)

#### 12.1 Update Settings Module

Replace vtlib usage in:
- `src/Modules/Settings/ModuleManager/`
- `src/Modules/Settings/LayoutEditor/`

**Before:**
```php
public static function createModule($moduleInformation)
{
    $module = new vtlib\Module();
    $module->name = ucfirst($moduleInformation['module_name']);
    $module->save();
    // ...
}
```

**After:**
```php
public static function createModule($moduleInformation)
{
    $handler = app(CreateModuleHandler::class);
    $module = $handler->handle(new CreateModuleCommand(
        name: ucfirst($moduleInformation['module_name']),
        label: $moduleInformation['module_label'],
        type: (int) $moduleInformation['entitytype']
    ));
    // ...
}
```

#### 12.2 Feature Flags

```php
// config/module_management.php
return [
    'use_new_system' => env('USE_NEW_MODULE_MANAGEMENT', true),
    'enable_deprecation_warnings' => true,
    'legacy_compatibility_mode' => false,
];
```

#### 12.3 Gradual Rollout

1. **Week 15:** Internal testing with new system
2. **Week 16:** Enable for new modules only
3. **Week 17:** Migrate Settings modules
4. **Week 18:** Enable globally

**Deliverables:**
- ✅ Settings modules migrated
- ✅ Feature flags in place
- ✅ Monitoring enabled
- ✅ Rollback plan ready

---

## Testing Strategy

### Test Pyramid

```
        /\
       /  \      10% - E2E Tests (Feature)
      /    \
     /------\    20% - Integration Tests
    /        \
   /          \  70% - Unit Tests
  /____________\
```

### Test Types

#### 1. **Unit Tests** (Domain Logic)
- Test each domain class in isolation
- Mock all dependencies
- Fast execution (< 100ms total)
- Run on every commit

#### 2. **Integration Tests** (Infrastructure)
- Test repository implementations
- Test schema manager
- Use test database
- Run before merge

#### 3. **Feature Tests** (End-to-End)
- Test complete workflows
- Real database
- Test backward compatibility
- Run before release

### Test Data Strategy

Use **fixtures** for consistent test data:

```php
// tests/Fixtures/ModuleFixtures.php
class ModuleFixtures
{
    public static function standardModule(): Module
    {
        return Module::create(
            name: ModuleName::fromString('TestModule'),
            label: 'Test Module',
            type: ModuleType::STANDARD
        );
    }
}
```

### Continuous Integration

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: php-actions/composer@v6
      - name: Run tests
        run: ./vendor/bin/phpunit
      - name: Coverage
        run: ./vendor/bin/phpunit --coverage-html coverage
```

---

## Migration & Rollout Plan

### Phase 1: Parallel Development (Week 1-13)
- Build new system
- No production impact
- Test thoroughly
- **Risk: LOW**

### Phase 2: Soft Launch (Week 14-15)
- Deploy with feature flags OFF
- Internal testing only
- Monitor performance
- **Risk: LOW**

### Phase 3: Gradual Rollout (Week 16-18)
- Enable for new modules
- Migrate Settings modules
- Monitor logs for deprecation warnings
- **Risk: MEDIUM**

### Phase 4: Full Migration (Week 19-20)
- Enable globally
- Loud deprecation warnings
- Communicate to community
- **Risk: MEDIUM-HIGH**

### Phase 5: Cleanup (6+ months later)
- Remove vtlib facade
- Remove legacy code
- Version 2.0 release
- **Risk: HIGH (breaking change)**

### Rollback Strategy

At each phase, maintain ability to rollback:

```php
// Can switch back instantly via config
'use_new_module_management' => false,
```

Rollback triggers:
- Performance degradation > 20%
- Critical bugs in production
- Data integrity issues
- Community pushback

---

## Risk Mitigation

### Risk 1: Data Corruption
**Mitigation:**
- Transaction wrapping
- Rollback capability
- Database backups before migration
- Validation before all operations
- Integration tests with real database

### Risk 2: Performance Degradation
**Mitigation:**
- Benchmark before/after
- Query optimization
- Caching strategy
- Load testing
- Monitoring dashboards

### Risk 3: Breaking 3rd Party Modules
**Mitigation:**
- vtlib facade maintains compatibility
- Deprecation warnings, not errors
- 6+ month deprecation period
- Community communication
- Migration tools

### Risk 4: Development Timeline
**Mitigation:**
- Detailed milestones
- Weekly progress tracking
- Parallel work streams
- Buffer time in estimates
- Scope management

### Risk 5: Incomplete Migration
**Mitigation:**
- Feature flags allow partial rollout
- Old system stays operational
- Can run both systems in parallel
- Clear migration checklist
- Automated migration tools

---

## Success Criteria

### Technical Success
- ✅ 100% feature parity with vtlib
- ✅ 90%+ test coverage
- ✅ No performance regression
- ✅ Zero data loss
- ✅ All integration tests passing

### Quality Success
- ✅ Clean architecture principles
- ✅ SOLID principles followed
- ✅ Full documentation
- ✅ Zero critical bugs
- ✅ Type-safe codebase

### Business Success
- ✅ No production incidents
- ✅ Backward compatibility maintained
- ✅ Community adoption
- ✅ Improved developer experience
- ✅ Reduced technical debt

---

## Timeline Summary

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| **1. Foundation** | Week 1 | Structure, interfaces, tests |
| **2. Module Domain** | Week 2 | Module aggregate + tests |
| **3. Field Domain** | Week 3 | Field aggregate + tests |
| **4. Schema Domain** | Week 4 | Schema models + tests |
| **5. Application Layer** | Week 5-6 | Commands/handlers + tests |
| **6. Persistence** | Week 7-8 | Repositories + tests |
| **7. Schema Manager** | Week 9 | Schema implementation |
| **8. Package Management** | Week 10 | Import/export |
| **9. Legacy Facade** | Week 11 | Backward compatibility |
| **10. Testing** | Week 12-13 | Comprehensive tests |
| **11. Documentation** | Week 14 | Docs + migration guide |
| **12. Integration** | Week 15-16 | Production integration |
| **TOTAL** | **16 weeks** | **Complete system** |

---

## Resource Requirements

### Development Team
- **1 Senior Developer** (Architecture, complex features)
- **1-2 Mid-level Developers** (Implementation)
- **1 QA Engineer** (Testing, validation)
- **Part-time Tech Lead** (Code review, guidance)

### Tools & Infrastructure
- Development environment
- Test database
- CI/CD pipeline
- Monitoring tools
- Documentation platform

### Time Commitment
- **Full-time:** 16 weeks
- **Part-time (50%):** 32 weeks
- **Mixed team:** 20-24 weeks

---

## Conclusion

This complete recreation of vtlib as ModuleManagement is an ambitious but achievable goal. Success depends on:

1. **Disciplined execution** of each phase
2. **Comprehensive testing** at every step
3. **Careful migration** with rollback capability
4. **Clear communication** with stakeholders
5. **Strong architectural foundation**

The result will be a **modern, maintainable, testable** module management system that serves FreeCRM for years to come.

---

## Next Steps

1. **Approval:** Get stakeholder buy-in for this plan
2. **Team:** Assign development resources
3. **Timeline:** Commit to start date
4. **Setup:** Prepare development environment
5. **Execute:** Begin Step 1

**Ready to proceed?** Let's build the future of FreeCRM module management! 🚀


