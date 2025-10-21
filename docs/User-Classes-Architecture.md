# User Classes Architecture Documentation

## Overview

The FreeCRM user management system has been refactored to follow clean architecture principles with proper separation of concerns. This document describes the new architecture and how to use the various user-related classes.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    User Management Architecture              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │   \App\User     │    │ \App\Modules\Users\Models\Record │ │
│  │ (Context Mgr)   │◄──►│     (Full User Model)           │ │
│  │   ~100 lines    │    │        ~960 lines               │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
│           │                           │                     │
│           │                           │                     │
│           ▼                           ▼                     │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │ \App\Modules\   │    │     Service Layer               │ │
│  │ Users\Users     │    │  ┌─────────────────────────────┐ │ │
│  │ (CRMEntity)     │◄──►│  │ AuthenticationService       │ │ │
│  │   ~460 lines    │    │  │ UserPreferencesService      │ │ │
│  └─────────────────┘    │  │ UserFileService             │ │ │
│                         │  │ DashboardService             │ │ │
│                         │  │ UserLifecycleService         │ │ │
│                         │  └─────────────────────────────┘ │ │
│                         └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Class Responsibilities

### 1. `\App\User` - Session & Context Manager

**Purpose**: Lightweight session and context management  
**Size**: ~100 lines  
**Responsibilities**:
- Current user session management
- User ID tracking
- Basic user context operations
- Cache management

**Key Methods**:
```php
// Get current user model (returns Record model)
public static function getCurrentUserModel(): \App\Modules\Users\Models\Record

// Get lightweight user context
public static function getCurrentUserContext(): \App\User

// Session management
public static function getId(): int
public static function setCurrentUserId(int $userId): void
public static function getCurrentUserRealId(): int

// Cache management
public static function clearCache(int $userId = false): void
```

### 2. `\App\Modules\Users\Users` - CRMEntity Implementation

**Purpose**: Database operations and legacy compatibility  
**Size**: ~460 lines (reduced from 943)  
**Responsibilities**:
- Database table definitions
- Field mappings
- Data retrieval operations
- Backward compatibility adapters

**Key Methods**:
```php
// Database operations
public function retrieve_entity_info(int $record, string $module): $this
public function retrieveCurrentUserInfoFromFile(int $userid): $this

// Adapter methods (delegating to services)
public function doLogin(string $userPassword): bool
public function change_password(string $userPassword, string $newPassword): bool
public function setPreference(string $name, mixed $value): bool
public function uploadAndSaveFile(int $id, string $module, array $fileDetails): bool
```

### 3. `\App\Modules\Users\Models\Record` - Full User Model

**Purpose**: Business logic and UI integration  
**Size**: ~960 lines  
**Responsibilities**:
- User business logic
- UI integration
- Service integration
- Record operations

**Key Methods**:
```php
// User data access
public function getDetail(string $fieldName): mixed
public function isAdmin(): bool
public function getProfiles(): array
public function getGroups(): array

// Service integration
public function changePassword(string $oldPassword, string $newPassword): bool
public function setPreference(string $name, mixed $value): bool
public function uploadImage(array $fileDetails): bool
public function getDashboardWidgets(): array
```

## Service Layer

### 1. AuthenticationService

**File**: `src/Modules/Users/Services/AuthenticationService.php`  
**Purpose**: Handle user authentication and password management

**Key Methods**:
```php
public function doLogin(object $userEntity, string $userPassword): bool
public function encryptPassword(string $user_password, string $crypt_type = '', string $user_name = ''): string
public function verifyPassword(object $userEntity, string $password): bool
public function changePassword(object $userEntity, string $userPassword, string $newPassword, bool $dieOnError = true): bool
public function getCryptType(int $userId = null, string $userName = ''): string
```

**Features**:
- LDAP authentication support
- Multiple password encryption types (MD5, BLOWFISH, PHP5.3MD5)
- Password verification and change operations

### 2. UserPreferencesService

**File**: `src/Modules/Users/Services/UserPreferencesService.php`  
**Purpose**: Manage user preferences and settings

**Key Methods**:
```php
public function setPreference(int $userId, string $name, mixed $value): bool
public function savePreferencesToDB(int $userId, array $preferences = null): bool
public function loadPreferencesFromDB(int $userId, string $preferencesData = null): array
public function getPreference(int $userId, string $name, mixed $defaultValue = null): mixed
```

**Features**:
- Preference serialization/deserialization
- Database persistence
- Session integration

### 3. UserFileService

**File**: `src/Modules/Users/Services/UserFileService.php`  
**Purpose**: Handle user file uploads and image management

**Key Methods**:
```php
public function uploadAndSaveFile(int $userId, string $module, array $fileDetails): bool
public function insertIntoAttachment(int $userId, string $module): bool
public function deleteImage(int $userId): bool
public function getUserImage(int $userId): array|false
public function hasUserImage(int $userId): bool
```

**Features**:
- File validation
- Image upload handling
- Attachment management
- File deletion operations

### 4. DashboardService

**File**: `src/Modules/Users/Services/DashboardService.php`  
**Purpose**: Manage user dashboard and home page widgets

**Key Methods**:
```php
public function getHomeStuffOrder(int $userId): array
public function saveHomeStuffOrder(int $userId, string $mode = 'edit'): bool
public function insertUserdetails(int $userId, string $inVal): bool
public function getUserDashboardWidgets(int $userId): array
public function updateWidgetVisibility(int $userId, string $widgetType, int $visible): bool
```

**Features**:
- Widget configuration
- Dashboard layout management
- Default widget setup
- Visibility controls

### 5. UserLifecycleService

**File**: `src/Modules/Users/Services/UserLifecycleService.php`  
**Purpose**: Handle user lifecycle operations

**Key Methods**:
```php
public function transformOwnershipAndDelete(int $userId, int $transformToUserId): bool
public function markDeleted(int $userId): bool
public function trash(string $module, int $userId): bool
public function canDeleteUser(int $userId): bool
public function getUsersForOwnershipTransfer(int $excludeUserId = null): array
public function transferOwnership(int $fromUserId, int $toUserId, array $modules = []): bool
```

**Features**:
- User deletion with ownership transfer
- Soft delete operations
- Ownership transfer validation
- User lifecycle statistics

## Usage Examples

### Getting Current User

```php
// Get full user model with all functionality
$user = \App\User::getCurrentUserModel();
echo $user->getDetail('first_name');
echo $user->isAdmin() ? 'Admin' : 'User';

// Get lightweight user context
$userContext = \App\User::getCurrentUserContext();
echo $userContext->getId();
```

### Authentication

```php
// Using the service directly
$authService = new \App\Modules\Users\Services\AuthenticationService();
$userEntity = \App\CRMEntity::getInstance('Users');
$userEntity->column_fields['user_name'] = 'admin';
$isAuthenticated = $authService->doLogin($userEntity, 'password');

// Using the adapter (backward compatible)
$userEntity = \App\CRMEntity::getInstance('Users');
$userEntity->column_fields['user_name'] = 'admin';
$isAuthenticated = $userEntity->doLogin('password');
```

### User Preferences

```php
// Using the service directly
$prefService = new \App\Modules\Users\Services\UserPreferencesService();
$prefService->setPreference(1, 'theme', 'dark');
$theme = $prefService->getPreference(1, 'theme', 'light');

// Using the Record model
$user = \App\Modules\Users\Models\Record::getInstanceById(1);
$user->setPreference('theme', 'dark');
$theme = $user->getPreference('theme', 'light');
```

### File Operations

```php
// Upload user image
$fileService = new \App\Modules\Users\Services\UserFileService();
$fileDetails = $_FILES['user_image'];
$success = $fileService->uploadAndSaveFile(1, 'Users', $fileDetails);

// Delete user image
$fileService->deleteImage(1);
```

### Dashboard Management

```php
// Get user dashboard widgets
$dashboardService = new \App\Modules\Users\Services\DashboardService();
$widgets = $dashboardService->getUserDashboardWidgets(1);

// Save dashboard order
$dashboardService->saveHomeStuffOrder(1, 'edit');
```

## Migration Guide

### For Existing Code

**Before (Old Way)**:
```php
$user = \App\User::getCurrentUserModel();
$user->setPreference('theme', 'dark');
$user->doLogin('password');
```

**After (New Way)**:
```php
// Option 1: Use Record model (recommended)
$user = \App\User::getCurrentUserModel(); // Now returns Record model
$user->setPreference('theme', 'dark');
$user->changePassword('old', 'new');

// Option 2: Use services directly
$prefService = new \App\Modules\Users\Services\UserPreferencesService();
$prefService->setPreference($userId, 'theme', 'dark');

// Option 3: Use adapters (backward compatible)
$userEntity = \App\CRMEntity::getInstance('Users');
$userEntity->setPreference('theme', 'dark'); // Still works via adapter
```

### Deprecated Methods

All methods in `\App\Modules\Users\Users` that delegate to services are marked with `@deprecated`:

```php
/**
 * @deprecated Use AuthenticationService::doLogin()
 */
public function doLogin($userPassword) { ... }

/**
 * @deprecated Use UserPreferencesService::setPreference()
 */
public function setPreference($name, $value) { ... }
```

## Benefits

1. **Clean Architecture**: Each class has a single, well-defined responsibility
2. **Maintainability**: Changes to authentication logic don't affect file operations
3. **Testability**: Services can be unit tested independently
4. **Backward Compatibility**: Existing code continues to work without changes
5. **Modern Design**: Service-based architecture with dependency injection
6. **Documentation**: All methods have proper PHPDoc comments
7. **Migration Path**: Clear deprecation warnings guide future refactoring

## Best Practices

1. **Use Record Model**: Prefer `\App\User::getCurrentUserModel()` for user operations
2. **Service Integration**: Use service integration methods on Record model
3. **Direct Service Access**: For complex operations, use services directly
4. **Avoid Deprecated Methods**: Gradually migrate from adapter methods to services
5. **Error Handling**: Services return boolean success/failure indicators
6. **Caching**: User context is cached automatically for performance

## Troubleshooting

### Common Issues

1. **Circular Dependency**: Fixed by using `getCurrentUserContext()` internally
2. **Missing Methods**: Check if method exists in Record model or services
3. **Authentication Failures**: Verify user exists and is active
4. **File Upload Issues**: Check file validation and permissions

### Debug Information

- Check `cache/logs/system.log` for errors
- Use `\App\Log::trace()` for debugging service operations
- Verify user privilege files exist for full functionality

## Future Enhancements

1. **Dependency Injection**: Consider using a DI container for services
2. **Interface Definitions**: Create interfaces for better testability
3. **Event System**: Add events for user lifecycle operations
4. **Caching Layer**: Implement service-level caching
5. **API Layer**: Create REST API endpoints using services
