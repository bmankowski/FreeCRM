<?php
/**
 * Example: Refactoring FreeCRM Privilege System
 * 
 * This file demonstrates how to improve the current privilege architecture
 * while maintaining backward compatibility.
 */

namespace App\Services\Privilege;

// ============================================================================
// CURRENT SYSTEM (Problems)
// ============================================================================

/*
// Static methods everywhere - hard to test, tightly coupled
$userId = \App\User::getCurrentUserId();
$privileges = \App\User::getPrivilegesFile($userId);  // Loads from file
$allowed = \App\Privilege::isPermitted('Leads', 'DetailView', 123);

Problems:
1. Static methods cannot be mocked for testing
2. File-based cache has race conditions
3. No clear return value (boolean only, reason in static var)
4. Complex 279-line method with nested conditions
5. Hard to extend with new permission types
*/

// ============================================================================
// PROPOSED SYSTEM (Solution)
// ============================================================================

/**
 * Permission Result - Structured return value instead of boolean
 */
class PermissionResult
{
    private bool $allowed;
    private string $reason;
    private string $checkerName;
    private array $metadata;
    
    private function __construct(bool $allowed, string $reason, string $checkerName = '', array $metadata = [])
    {
        $this->allowed = $allowed;
        $this->reason = $reason;
        $this->checkerName = $checkerName;
        $this->metadata = $metadata;
    }
    
    public static function allow(string $reason, string $checker = '', array $metadata = []): self
    {
        return new self(true, $reason, $checker, $metadata);
    }
    
    public static function deny(string $reason, string $checker = '', array $metadata = []): self
    {
        return new self(false, $reason, $checker, $metadata);
    }
    
    public function isAllowed(): bool 
    { 
        return $this->allowed; 
    }
    
    public function getReason(): string 
    { 
        return $this->reason; 
    }
    
    public function getCheckerName(): string 
    { 
        return $this->checkerName; 
    }
    
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'reason' => $this->reason,
            'checker' => $this->checkerName,
            'metadata' => $this->metadata,
        ];
    }
    
    public function isFinal(): bool
    {
        // Some results should stop the chain (e.g., admin always allowed)
        return $this->metadata['final'] ?? false;
    }
}

/**
 * Permission Context - All information needed for a permission check
 */
class PermissionContext
{
    private int $userId;
    private string $moduleName;
    private ?string $actionName;
    private ?int $recordId;
    private array $userPrivileges;
    
    public function __construct(
        int $userId,
        string $moduleName,
        ?string $actionName = null,
        ?int $recordId = null,
        array $userPrivileges = []
    ) {
        $this->userId = $userId;
        $this->moduleName = $moduleName;
        $this->actionName = $actionName;
        $this->recordId = $recordId;
        $this->userPrivileges = $userPrivileges;
    }
    
    public function getUserId(): int { return $this->userId; }
    public function getModuleName(): string { return $this->moduleName; }
    public function getActionName(): ?string { return $this->actionName; }
    public function getRecordId(): ?int { return $this->recordId; }
    public function getUserPrivileges(): array { return $this->userPrivileges; }
    public function isAdmin(): bool { return $this->userPrivileges['is_admin'] ?? false; }
}

/**
 * Permission Checker Interface
 */
interface PermissionChecker
{
    /**
     * Check permission based on context
     * Returns PermissionResult with decision and reason
     */
    public function check(PermissionContext $context): PermissionResult;
    
    /**
     * Get checker name for logging
     */
    public function getName(): string;
}

/**
 * Admin Permission Checker - First in chain, admins get access to everything
 */
class AdminPermissionChecker implements PermissionChecker
{
    public function check(PermissionContext $context): PermissionResult
    {
        if ($context->isAdmin()) {
            return PermissionResult::allow(
                'User is administrator',
                $this->getName(),
                ['final' => true]  // Admin check is final, no need to check further
            );
        }
        
        // Not admin, continue to next checker
        return PermissionResult::deny('Not an admin', $this->getName());
    }
    
    public function getName(): string
    {
        return 'AdminChecker';
    }
}

/**
 * Module Permission Checker - Check if user has access to module
 */
class ModulePermissionChecker implements PermissionChecker
{
    public function check(PermissionContext $context): PermissionResult
    {
        $privileges = $context->getUserPrivileges();
        $tabId = \App\Module::getModuleId($context->getModuleName());
        
        // Check if module is active
        if (!\App\Module::isModuleActive($context->getModuleName())) {
            return PermissionResult::deny(
                'Module is not active',
                $this->getName(),
                ['final' => true]
            );
        }
        
        // Check tab permission
        if (!isset($privileges['profile_tabs_permission'][$tabId]) || 
            $privileges['profile_tabs_permission'][$tabId] != 0) {
            return PermissionResult::deny(
                'No module access',
                $this->getName(),
                ['final' => true, 'tabId' => $tabId]
            );
        }
        
        return PermissionResult::allow('Has module access', $this->getName());
    }
    
    public function getName(): string
    {
        return 'ModuleChecker';
    }
}

/**
 * Record Ownership Checker - Check if user owns the record
 */
class RecordOwnershipChecker implements PermissionChecker
{
    public function check(PermissionContext $context): PermissionResult
    {
        $recordId = $context->getRecordId();
        
        // If no record, skip this check
        if ($recordId === null) {
            return PermissionResult::allow('No record to check', $this->getName());
        }
        
        $recordMetaData = \vtlib\Functions::getCRMRecordMetadata($recordId);
        
        if (!$recordMetaData) {
            return PermissionResult::deny(
                'Record does not exist',
                $this->getName(),
                ['final' => true, 'recordId' => $recordId]
            );
        }
        
        // Check if user owns the record
        if ($recordMetaData['smownerid'] == $context->getUserId()) {
            return PermissionResult::allow(
                'User owns the record',
                $this->getName(),
                ['final' => true, 'ownerId' => $recordMetaData['smownerid']]
            );
        }
        
        // User doesn't own it, but may still have access via sharing
        return PermissionResult::deny('User does not own record', $this->getName());
    }
    
    public function getName(): string
    {
        return 'OwnershipChecker';
    }
}

/**
 * Permission Chain - Executes checkers in order
 */
class PermissionChain
{
    /** @var PermissionChecker[] */
    private array $checkers = [];
    private bool $enableLogging = false;
    
    public function addChecker(PermissionChecker $checker): self
    {
        $this->checkers[] = $checker;
        return $this;
    }
    
    public function setLogging(bool $enable): self
    {
        $this->enableLogging = $enable;
        return $this;
    }
    
    public function check(PermissionContext $context): PermissionResult
    {
        $lastResult = PermissionResult::deny('No checkers executed', 'PermissionChain');
        
        foreach ($this->checkers as $checker) {
            $result = $checker->check($context);
            
            if ($this->enableLogging) {
                $this->log($context, $checker, $result);
            }
            
            // If allowed and final, we're done
            if ($result->isAllowed() && $result->isFinal()) {
                return $result;
            }
            
            // If denied and final, we're done
            if (!$result->isAllowed() && $result->isFinal()) {
                return $result;
            }
            
            // Continue to next checker
            $lastResult = $result;
        }
        
        return $lastResult;
    }
    
    private function log(PermissionContext $context, PermissionChecker $checker, PermissionResult $result): void
    {
        \App\Log::trace(sprintf(
            'Permission check [%s]: User=%d, Module=%s, Action=%s, Record=%s, Result=%s, Reason=%s',
            $checker->getName(),
            $context->getUserId(),
            $context->getModuleName(),
            $context->getActionName() ?? 'N/A',
            $context->getRecordId() ?? 'N/A',
            $result->isAllowed() ? 'ALLOW' : 'DENY',
            $result->getReason()
        ));
    }
}

/**
 * Privilege Repository Interface - Abstract storage
 */
interface PrivilegeRepository
{
    /**
     * Get user privileges
     * @return array|null
     */
    public function getUserPrivileges(int $userId): ?array;
    
    /**
     * Save user privileges
     */
    public function saveUserPrivileges(int $userId, array $privileges): void;
    
    /**
     * Invalidate user privileges cache
     */
    public function invalidate(int $userId): void;
}

/**
 * File-Based Repository - Wraps current file system (for compatibility)
 */
class FilePrivilegeRepository implements PrivilegeRepository
{
    public function getUserPrivileges(int $userId): ?array
    {
        // Delegate to existing implementation
        return \App\User::getPrivilegesFile($userId);
    }
    
    public function saveUserPrivileges(int $userId, array $privileges): void
    {
        \App\PrivilegeFile::createUserPrivilegesFile($userId);
    }
    
    public function invalidate(int $userId): void
    {
        \App\User::clearCache($userId);
    }
}

/**
 * Database/Redis Repository - Future implementation
 */
class CachedPrivilegeRepository implements PrivilegeRepository
{
    private \Redis $redis;
    private int $ttl = 3600; // 1 hour
    
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }
    
    public function getUserPrivileges(int $userId): ?array
    {
        $key = "user:privileges:$userId";
        $cached = $this->redis->get($key);
        
        if ($cached !== false) {
            return json_decode($cached, true);
        }
        
        // Load from database if not cached
        $privileges = $this->loadFromDatabase($userId);
        
        if ($privileges) {
            $this->redis->setex($key, $this->ttl, json_encode($privileges));
        }
        
        return $privileges;
    }
    
    public function saveUserPrivileges(int $userId, array $privileges): void
    {
        $key = "user:privileges:$userId";
        $this->redis->setex($key, $this->ttl, json_encode($privileges));
    }
    
    public function invalidate(int $userId): void
    {
        $key = "user:privileges:$userId";
        $this->redis->del($key);
    }
    
    private function loadFromDatabase(int $userId): ?array
    {
        // Implementation would load from vtiger_users, roles, profiles, etc.
        return null;
    }
}

/**
 * Main Privilege Service - Entry point for all permission checks
 */
class PrivilegeService
{
    private PrivilegeRepository $repository;
    private PermissionChain $chain;
    
    public function __construct(PrivilegeRepository $repository)
    {
        $this->repository = $repository;
        $this->chain = $this->createDefaultChain();
    }
    
    /**
     * Create default permission chain
     */
    private function createDefaultChain(): PermissionChain
    {
        $chain = new PermissionChain();
        $chain->addChecker(new AdminPermissionChecker());
        $chain->addChecker(new ModulePermissionChecker());
        $chain->addChecker(new RecordOwnershipChecker());
        // Add more checkers as needed
        return $chain;
    }
    
    /**
     * Check if user has permission
     */
    public function isPermitted(
        int $userId,
        string $moduleName,
        ?string $actionName = null,
        ?int $recordId = null
    ): PermissionResult {
        // Load user privileges
        $privileges = $this->repository->getUserPrivileges($userId);
        
        if ($privileges === null) {
            return PermissionResult::deny(
                'User privileges not found',
                'PrivilegeService',
                ['final' => true, 'userId' => $userId]
            );
        }
        
        // Create context
        $context = new PermissionContext(
            $userId,
            $moduleName,
            $actionName,
            $recordId,
            $privileges
        );
        
        // Execute permission chain
        return $this->chain->check($context);
    }
    
    /**
     * Get user privileges
     */
    public function getUserPrivileges(int $userId): ?array
    {
        return $this->repository->getUserPrivileges($userId);
    }
}

// ============================================================================
// USAGE EXAMPLE
// ============================================================================

/*
// Initialize service (in bootstrap or service container)
$repository = new FilePrivilegeRepository(); // Or CachedPrivilegeRepository
$privilegeService = new PrivilegeService($repository);

// Check permission - Clean API
$result = $privilegeService->isPermitted(
    userId: 5,
    moduleName: 'Leads',
    actionName: 'DetailView',
    recordId: 123
);

// Use result
if ($result->isAllowed()) {
    echo "Access granted: " . $result->getReason();
} else {
    echo "Access denied: " . $result->getReason();
    
    // Log for audit
    \App\Log::warning('Permission denied', [
        'user' => 5,
        'module' => 'Leads',
        'action' => 'DetailView',
        'record' => 123,
        'reason' => $result->getReason(),
        'checker' => $result->getCheckerName(),
    ]);
}

// API response
return json_encode([
    'success' => $result->isAllowed(),
    'permission' => $result->toArray(),
]);

*/

// ============================================================================
// BACKWARD COMPATIBILITY WRAPPER
// ============================================================================

/**
 * Wrapper to maintain compatibility with existing code
 * Allows gradual migration
 */
class PrivilegeFacade
{
    private static ?PrivilegeService $service = null;
    private static bool $useNewSystem = false;
    
    public static function setService(PrivilegeService $service): void
    {
        self::$service = $service;
    }
    
    public static function enableNewSystem(bool $enable = true): void
    {
        self::$useNewSystem = $enable;
    }
    
    /**
     * Backward compatible isPermitted method
     * Can switch between old and new implementation
     */
    public static function isPermitted(
        string $moduleName,
        ?string $actionName = null,
        $record = false,
        $userId = false
    ): bool {
        if (!$userId) {
            $userId = \App\User::getCurrentUserId();
        }
        
        if (self::$useNewSystem && self::$service !== null) {
            // Use new system
            $result = self::$service->isPermitted(
                $userId,
                $moduleName,
                $actionName,
                $record !== false ? (int)$record : null
            );
            
            // Store reason for backward compatibility
            \App\Privilege::$isPermittedLevel = $result->getReason();
            
            return $result->isAllowed();
        } else {
            // Use old system
            return \App\Privilege::isPermitted($moduleName, $actionName, $record, $userId);
        }
    }
}

// ============================================================================
// TESTING EXAMPLE
// ============================================================================

/**
 * Now the system is testable!
 */
class PrivilegeServiceTest // extends PHPUnit\Framework\TestCase
{
    public function testAdminUserHasAllPermissions()
    {
        // Mock repository
        $mockRepo = $this->createMock(PrivilegeRepository::class);
        $mockRepo->method('getUserPrivileges')
            ->willReturn([
                'is_admin' => true,
                'profile_tabs_permission' => [],
            ]);
        
        $service = new PrivilegeService($mockRepo);
        
        $result = $service->isPermitted(1, 'Leads', 'Delete', 123);
        
        $this->assertTrue($result->isAllowed());
        $this->assertEquals('User is administrator', $result->getReason());
        $this->assertEquals('AdminChecker', $result->getCheckerName());
    }
    
    public function testNonAdminWithoutModuleAccessDenied()
    {
        $mockRepo = $this->createMock(PrivilegeRepository::class);
        $mockRepo->method('getUserPrivileges')
            ->willReturn([
                'is_admin' => false,
                'profile_tabs_permission' => [7 => 1], // No access to Leads (tabid 7)
            ]);
        
        $service = new PrivilegeService($mockRepo);
        
        $result = $service->isPermitted(2, 'Leads', 'DetailView', 123);
        
        $this->assertFalse($result->isAllowed());
        $this->assertEquals('No module access', $result->getReason());
    }
}

