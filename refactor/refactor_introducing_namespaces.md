# Comprehensive PHP Namespace Migration Strategy for FreeCRM

Based on my analysis of your codebase, here's the complete strategy:

## Current State Analysis

**Legacy Patterns Identified:**
- Class naming: `ModuleName_ComponentName_Type` (e.g., `Users_Record_Model`, `Vtiger_Module_Model`)
- Custom autoloader in `Vtiger_Loader` handles `Module_Class_Type` pattern
- Directory structure: `modules/{Module}/{types}/{Class}.php`
- Settings modules use colon notation: `Settings:Vtiger` → `Settings_Vtiger_*_*`
- Already has partial PSR-4 in composer.json for `App\`, `Api\`, `Exception\`, `vtlib\`
- Rector already installed and configured

**Key Challenges:**
1. **Massive codebase** - 90+ modules with models/views/actions/handlers/uitypes/dashboards
2. **Runtime class name construction** - `Vtiger_Loader::getComponentClassName()` builds class names dynamically
3. **Backward compatibility** - Can't break existing customizations/extensions
4. **String-based class references** - Many places use string interpolation to build class names
5. **Mixed autoloading** - Composer PSR-4 + custom Vtiger_Loader

## Recommended Namespace Structure

### Target Namespace Hierarchy

```
FreeCRM\
├── Core\                      # Core framework classes
│   ├── Loader
│   ├── EntryPoint
│   ├── WebUI
│   └── Entity\
│       └── CRMEntity
├── Models\                    # Base model classes
│   ├── Base
│   ├── Record
│   ├── Module
│   └── Field
├── Controllers\
│   ├── Action
│   └── View
├── Http\
│   ├── Request
│   ├── Response
│   └── Session
├── Config\
│   └── AppConfig
├── Database\
│   └── PearDatabase
├── Language\
│   ├── Handler
│   └── Translator
└── Modules\                   # All business modules
    ├── Users\
    │   ├── Models\
    │   │   ├── Record
    │   │   ├── Module
    │   │   └── Privileges
    │   ├── Views\
    │   │   ├── List
    │   │   └── Login
    │   ├── Actions\
    │   │   ├── Save
    │   │   └── Login
    │   └── Handlers\
    ├── Accounts\
    │   ├── Models\
    │   ├── Views\
    │   └── Actions\
    ├── Settings\              # Settings submodules
    │   ├── Vtiger\
    │   ├── Companies\
    │   └── Workflows\
    └── ... (90+ modules)
```

## Migration Strategy: **Incremental Hybrid Approach**

### Phase 1: Foundation (Weeks 1-2)

**1.1 Update Composer Autoload**
```json
"autoload": {
    "psr-4": {
        "FreeCRM\\": "src/",
        "FreeCRM\\Core\\": "include/core/",
        "FreeCRM\\Models\\": "include/models/",
        "FreeCRM\\Controllers\\": "include/controllers/",
        "FreeCRM\\Http\\": "include/http/",
        "FreeCRM\\Modules\\": "modules/",
        "App\\": "vendor/yetiforce/",
        "Api\\": "api/webservice/",
        "Exception\\": "include/exceptions/"
    },
    "classmap": [
        "include/CRMEntity.php",
        "include/Loader.php"
    ]
}
```

**1.2 Create Namespace Alias Layer**
Create `include/NamespaceAliases.php`:
```php
<?php
// Legacy class aliases for backward compatibility
class_alias('FreeCRM\\Core\\Loader', 'Vtiger_Loader');
class_alias('FreeCRM\\Models\\Record', 'Vtiger_Record_Model');
class_alias('FreeCRM\\Modules\\Users\\Models\\Record', 'Users_Record_Model');
// ... hundreds more
```

**1.3 Enhanced Autoloader**
Modify `Vtiger_Loader::autoLoad()` to:
- First check if namespaced class exists
- Fall back to legacy pattern
- Auto-register aliases dynamically

### Phase 2: Tooling Setup (Week 2)

**2.1 Rector Custom Rules**
Create `src/Rector/AddNamespaceRector.php`:
```php
<?php
namespace App\Rector;

use Rector\Core\Rector\AbstractRector;
use PhpParser\Node;

class AddNamespaceRector extends AbstractRector
{
    public function refactor(Node $node): ?Node
    {
        // Convert Module_Class_Type to FreeCRM\Modules\Module\Type\Class
        // Handle Settings:Module pattern
        // Add use statements
        // Update class instantiation
    }
}
```

**2.2 Custom Migration Script**
Create `refactor/namespace-migrator.php`:
- Scans files in batches
- Applies transformations
- Generates alias mappings
- Validates syntax
- Creates git commits per batch

**2.3 Analysis Tool**
Create `refactor/analyze-dependencies.php`:
- Maps all class dependencies
- Identifies string-based class references
- Finds dynamic class construction
- Generates migration order (dependency graph)

### Phase 3: Core Classes Migration (Weeks 3-4)

**Priority Order:**
1. **Core infrastructure** (Loader, EntryPoint, WebUI)
2. **Base models** (Vtiger_Record_Model, Vtiger_Module_Model)
3. **HTTP layer** (Request, Response, Session)
4. **Database layer**
5. **Language/Config utilities**

**Process per class:**
1. Run analysis to find all usages
2. Create namespaced version in new location
3. Add class_alias in NamespaceAliases.php
4. Update direct references gradually
5. Run tests
6. Commit

### Phase 4: Module Migration (Weeks 5-12)

**Automated Approach:**

```bash
# For each module:
php refactor/migrate-module.php --module=Users --dry-run
php refactor/migrate-module.php --module=Users --execute
php refactor/migrate-module.php --module=Users --verify
```

**Module Migration Order:**
1. **Vtiger** (base module) - FIRST
2. **Users** (critical, well-tested)
3. **Home, Dashboard** (simple)
4. **Settings modules** (complex hierarchy)
5. **Business modules** (Accounts, Contacts, Leads, etc.)
6. **OSS modules**
7. **Custom modules** (documented separately)

### Phase 5: Dynamic References (Weeks 13-14)

**Challenge:** Code like this:
```php
$className = $moduleName . '_' . $componentName . '_' . $type;
$instance = new $className();
```

**Solution:**
```php
// Create mapping service
class ClassNameResolver {
    public static function resolve($moduleName, $componentName, $type) {
        // Try namespaced first
        $ns = "FreeCRM\\Modules\\{$moduleName}\\{$type}s\\{$componentName}";
        if (class_exists($ns)) return $ns;
        
        // Fall back to legacy
        return "{$moduleName}_{$componentName}_{$type}";
    }
}
```

Update `Vtiger_Loader::getComponentClassName()` to return FQCNs.

### Phase 6: Cleanup (Weeks 15-16)

1. Remove unnecessary aliases (gradually)
2. Update all use statements
3. Remove legacy autoloader (optional, keep for extensions)
4. Run full Rector cleanup
5. Update documentation

## Automation Tools Recommendation

### Primary: **Rector** (Already Installed ✓)

**Custom Rector Rules Needed:**

1. **`ModuleClassToNamespaceRector`**
   - Converts `Users_Record_Model` → `FreeCRM\Modules\Users\Models\Record`
   - Handles Settings:Module pattern

2. **`AddNamespaceToFileRector`**
   - Adds namespace declaration based on file path
   - Follows PSR-4 structure

3. **`UpdateClassInstantiationRector`**
   - Updates `new Users_Record_Model()` → `new Record()` with use statement
   - Or keeps FQCN

4. **`UpdateStaticCallsRector`**
   - `Users_Record_Model::getInstance()` → `Record::getInstance()`

5. **`StringClassReferenceRector`**
   - Finds `'Users_Record_Model'` strings
   - Adds `::class` where safe
   - Flags dynamic construction for manual review

**Rector Configuration:**
```php
// rector.php additions
use App\Rector\ModuleClassToNamespaceRector;
use App\Rector\AddNamespaceToFileRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/modules/Users']) // Start with one module
    ->withRules([
        ModuleClassToNamespaceRector::class,
        AddNamespaceToFileRector::class,
    ])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/libraries',
    ]);
```

### Secondary: **PHP-CS-Fixer**

For code style consistency after namespace changes:

```bash
composer require --dev friendsofphp/php-cs-fixer
```

**`.php-cs-fixer.php`:**
```php
<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/modules')
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->setRules([
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        'no_unused_imports' => true,
        'global_namespace_import' => ['import_classes' => true],
    ])
    ->setFinder($finder);
```

### Tertiary: **Custom Scripts**

**`refactor/namespace-migrator.php`** - Orchestration script
**`refactor/class-mapper.php`** - Generates complete mapping
**`refactor/alias-generator.php`** - Creates class_alias declarations
**`refactor/dependency-analyzer.php`** - Analyzes migration safety

## Naming Conventions

### Classes
- **Models:** `FreeCRM\Modules\Users\Models\Record`
- **Views:** `FreeCRM\Modules\Users\Views\List`
- **Actions:** `FreeCRM\Modules\Users\Actions\Save`
- **Handlers:** `FreeCRM\Modules\Users\Handlers\ForgotPassword`
- **UiTypes:** `FreeCRM\Modules\Users\UiTypes\Boolean`

### Directory Structure
```
modules/Users/
├── Models/
│   └── Record.php          # FreeCRM\Modules\Users\Models\Record
├── Views/
│   └── List.php            # FreeCRM\Modules\Users\Views\List
├── Actions/
│   └── Save.php            # FreeCRM\Modules\Users\Actions\Save
└── Users.php               # FreeCRM\Modules\Users\Users (entity class)
```

### File Naming
- **PascalCase** for class names
- Match class name exactly: `Record.php` for class `Record`
- One class per file (PSR-4 requirement)

## Backward Compatibility Strategy

### 1. **Dual Autoloading Period**
- Keep `Vtiger_Loader` active
- Add Composer PSR-4
- Both work simultaneously for 6-12 months

### 2. **Class Aliases**
```php
// Loaded early in bootstrap
class_alias('FreeCRM\\Modules\\Users\\Models\\Record', 'Users_Record_Model');
```

### 3. **Deprecation Notices**
```php
class Users_Record_Model extends \App\Modules\Users\Models\Record {
    public function __construct() {
        trigger_error('Users_Record_Model is deprecated, use FreeCRM\Modules\Users\Models\Record', E_USER_DEPRECATED);
        parent::__construct();
    }
}
```

### 4. **Migration Guide for Extensions**
Document for third-party developers:
- How to update their modules
- Compatibility layer availability
- Timeline for alias removal

### 5. **Version Strategy**
- **v1.0:** Full backward compatibility, both systems work
- **v1.5:** Deprecation warnings for legacy
- **v2.0:** Legacy removed (12+ months later)

## Potential Pitfalls & Solutions

### Pitfall 1: Dynamic Class Construction
**Problem:** `$class = "{$module}_{$name}_Model"; new $class();`

**Solution:**
- Create `ClassResolver` service
- Update `Vtiger_Loader::getComponentClassName()` to return FQCNs
- Use Rector to find all instances
- Manual review flagged cases

### Pitfall 2: String-Based Class References
**Problem:** Database stores class names, config files have class strings

**Solution:**
- Database migration script to update stored class names
- Maintain mapping table: legacy → namespaced
- Add runtime translation layer

### Pitfall 3: Reflection & class_exists()
**Problem:** Code using `class_exists('Users_Record_Model')`

**Solution:**
- Class aliases make this work
- Rector rule to update to `::class` constant
- Runtime fallback in autoloader

### Pitfall 4: Serialized Objects
**Problem:** Sessions/cache contain serialized objects with old class names

**Solution:**
- PHP's `unserialize_callback_func`
- Session migration on login
- Cache flush during deployment

### Pitfall 5: Settings Module Hierarchy
**Problem:** `Settings:Vtiger:Companies` → `Settings_Vtiger_Companies_*_*`

**Solution:**
- Map to `FreeCRM\Modules\Settings\Vtiger\Companies\*\*`
- Update path parsing in `Vtiger_Loader`
- Test extensively

### Pitfall 6: Third-Party Libraries
**Problem:** Libraries in `/libraries/` may break

**Solution:**
- Keep in global namespace
- Skip from migration
- Document separately if needed

### Pitfall 7: JavaScript Callbacks
**Problem:** AJAX calls reference PHP class names

**Solution:**
- Create API endpoint mapping
- Frontend uses endpoints, not class names
- If unavoidable, use class aliases

### Pitfall 8: Performance Impact
**Problem:** Additional autoloader checks

**Solution:**
- Use APCu/OPcache for class map
- Generate optimized Composer autoloader: `composer dump-autoload -o`
- Benchmark before/after

## Testing Procedures

### 1. **Pre-Migration Testing**
```bash
# Create comprehensive test suite
php vendor/bin/phpunit tests/
# Baseline coverage report
php vendor/bin/phpunit --coverage-html coverage/
```

### 2. **Per-Module Testing**
```bash
# After each module migration:
php vendor/bin/phpunit tests/modules/Users/
# Integration tests
php refactor/test-module.php Users
```

### 3. **Automated Validation**
```bash
# Syntax check
find modules/Users -name "*.php" -exec php -l {} \;
# Psalm/PHPStan
vendor/bin/psalm modules/Users/
# Custom validator
php refactor/validate-namespaces.php Users
```

### 4. **Manual Testing Checklist**
Per module:
- [ ] List view loads
- [ ] Detail view loads
- [ ] Create new record
- [ ] Edit record
- [ ] Delete record
- [ ] Module-specific features
- [ ] Related modules work
- [ ] AJAX actions work
- [ ] Reports work (if applicable)
- [ ] Workflow triggers work
- [ ] API endpoints work

### 5. **Regression Testing**
```bash
# Full application test
php tests/run-all-tests.php
# Load test (performance)
ab -n 1000 -c 10 https://freecrm.local/index.php?module=Users&view=List
```

### 6. **Staging Deployment**
- Deploy to staging environment
- Run production-like workload
- Monitor error logs
- Performance profiling
- 1-2 week bake time

### 7. **Production Rollout**
- Feature flag for namespace system
- Gradual rollout: 10% → 50% → 100% users
- Monitor errors closely
- Rollback plan ready

## Implementation Timeline

### Overview: **16-20 weeks** for complete migration

**Week 1-2:** Foundation & Planning
- Set up tooling (Rector rules, scripts)
- Analyze dependencies
- Update composer.json
- Create alias infrastructure
- Document process

**Week 3-4:** Core Classes (Critical Path)
- Migrate Loader, EntryPoint, WebUI
- Migrate base Models, Controllers
- Migrate HTTP layer
- Extensive testing

**Week 5-6:** Users & Vtiger Modules
- Migrate Users module (most critical)
- Migrate Vtiger base module
- Update authentication flow
- Test thoroughly

**Week 7-8:** Core Business Modules
- Accounts, Contacts, Leads
- Calendar, Events
- Testing

**Week 9-11:** Extended Modules (Batch 1)
- HelpDesk, Documents, Products
- Campaigns, Reports
- Settings modules (subset)

**Week 12-14:** Extended Modules (Batch 2)
- OSS modules
- Custom modules
- Remaining Settings modules

**Week 15-16:** Dynamic References & Cleanup
- Update dynamic class construction
- Remove legacy patterns
- Full Rector cleanup
- Performance optimization

**Week 17-18:** Testing & Documentation
- Comprehensive testing
- Update developer documentation
- Create migration guide for extensions

**Week 19-20:** Deployment & Monitoring
- Staging deployment
- Production rollout
- Monitor and fix issues

## Success Metrics

1. **Code Coverage:** Maintain >80% test coverage
2. **Performance:** <5% performance degradation
3. **Errors:** Zero fatal errors in production
4. **Compatibility:** All extensions continue working
5. **Code Quality:** PHPStan level 5+ passes
6. **Developer Experience:** Faster IDE autocomplete, better type hints

## Rollback Plan

1. **Git Strategy:** Feature branch per phase, tags per module
2. **Database:** Maintain backward compatibility, no destructive changes
3. **Deployment:** Blue-green deployment for instant rollback
4. **Aliases:** Keep all aliases until v2.0 (remove gradually)
5. **Monitoring:** Alert on error rate >1%, auto-rollback if >5%

## Long-Term Benefits

1. **IDE Support:** Full autocomplete, go-to-definition
2. **Type Safety:** Better static analysis, fewer runtime errors
3. **Code Organization:** Clear structure, easier onboarding
4. **Modern PHP:** Enables PHP 8+ features (attributes, enums, etc.)
5. **Performance:** Better OPcache optimization
6. **Testing:** Easier mocking, dependency injection
7. **Standards Compliance:** PSR-4, PSR-12, modern practices

---

## Next Immediate Steps

1. **Create working group** - assign team members
2. **Set up development environment** - isolated namespace branch
3. **Install additional tools** - Psalm, PHP-CS-Fixer
4. **Build Rector custom rules** - start with simple transformations
5. **Migrate ONE test module** - pick smallest module as POC
6. **Document learnings** - refine process
7. **Get stakeholder approval** - timeline and resource allocation
