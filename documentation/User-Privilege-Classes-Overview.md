# FreeCRM User and Privilege Classes Documentation

## 📋 **Table of Contents**

1. [Overview](#overview)
2. [Core User Classes](#core-user-classes)
3. [Privilege Classes](#privilege-classes)
4. [Settings Classes](#settings-classes)
5. [Utility Classes](#utility-classes)
6. [UI/Field Classes](#uifield-classes)
7. [Class Relationships](#class-relationships)
8. [Migration Status](#migration-status)
9. [Usage Examples](#usage-examples)
10. [Best Practices](#best-practices)

---

## 📊 **Overview**

The FreeCRM system implements a comprehensive user and privilege management system with **35 classes** organized into 5 main categories:

| Category | Count | Purpose |
|----------|-------|---------|
| **Core User Classes** | 8 | Main user management and operations |
| **Privilege Classes** | 6 | Permission system and access control |
| **Settings Classes** | 12 | Configuration and administration |
| **Utility Classes** | 4 | Helper functions and utilities |
| **UI/Field Classes** | 5 | User interface components |
| **Total** | **35** | Complete user/privilege ecosystem |

### **Architecture Principles**

- **Separation of Concerns**: Each class has a specific responsibility
- **PSR-4 Autoloading**: Modern namespace structure
- **Dependency Injection**: Clean class dependencies
- **Backward Compatibility**: Legacy function wrappers maintained

---

## 🔑 **Core User Classes**

### **1.1 Main User Entity**

#### **`App\Modules\Users\Users`**
- **File**: `src/Modules/Users/Users.php`
- **Purpose**: Main CRM entity class for user records
- **Responsibility**: User data model, database operations, user CRUD
- **Key Methods**:
  - `__construct()` - Initialize user entity
  - `retrieve_entity_info($id, $module)` - Load user data
  - `save()` - Save user record
  - `delete()` - Delete user record

**Usage Example**:
```php
$user = new \App\Modules\Users\Users();
$user->retrieve_entity_info($userId, 'Users');
$user->set('first_name', 'John');
$user->save();
```

### **1.2 User Models**

#### **`App\Modules\Users\Models\Record`**
- **File**: `src/Modules/Users/Models/Record.php`
- **Purpose**: Individual user record representation
- **Responsibility**: User record CRUD, validation, business logic
- **Key Methods**:
  - `get($key)` - Get user property
  - `set($key, $value)` - Set user property
  - `save()` - Save user record
  - `isAdminUser()` - Check if user is admin
  - `getModuleName()` - Get module name

**Usage Example**:
```php
$userRecord = \App\Modules\Users\Models\Record::getInstanceById($userId);
$isAdmin = $userRecord->isAdminUser();
$userRecord->set('first_name', 'Jane');
$userRecord->save();
```

#### **`App\Modules\Users\Models\Module`**
- **File**: `src/Modules/Users/Models/Module.php`
- **Purpose**: Users module management
- **Responsibility**: Module configuration, settings, business rules
- **Key Methods**:
  - `getDefaultUrl()` - Get default module URL
  - `getCreateRecordUrl()` - Get create record URL
  - `isWorkflowSupported()` - Check workflow support

#### **`App\Modules\Users\Models\Privileges`**
- **File**: `src/Modules/Users/Models/Privileges.php`
- **Purpose**: User permission checking and validation
- **Responsibility**: Permission validation, role-based access, module permissions
- **Key Methods**:
  - `hasGlobalReadPermission()` - Check global read permission
  - `hasGlobalWritePermission()` - Check global write permission
  - `hasModulePermission($mixed)` - Check module permission
  - `hasModuleActionPermission($mixed, $action)` - Check action permission
  - `isAdminUser()` - Check if user is admin

**Usage Example**:
```php
$userPrivileges = new \App\Modules\Users\Models\Privileges();
$canRead = $userPrivileges->hasGlobalReadPermission();
$canAccessAccounts = $userPrivileges->hasModulePermission('Accounts');
```

### **1.3 User Views & Actions**

#### **`App\Modules\Users\Views\SwitchUsers`**
- **File**: `src/Modules/Users/Views/SwitchUsers.php`
- **Purpose**: User switching interface
- **Responsibility**: Display user switching modal

#### **`App\Modules\Users\Actions\SwitchUsers`**
- **File**: `src/Modules/Users/Actions/SwitchUsers.php`
- **Purpose**: User switching logic
- **Responsibility**: Handle user switching requests

#### **`App\Modules\Users\Actions\ForgotPassword`**
- **File**: `src/Modules/Users/Actions/ForgotPassword.php`
- **Purpose**: Password recovery
- **Responsibility**: Handle password reset requests

#### **`App\Modules\Users\Actions\CheckUserEmail`**
- **File**: `src/Modules/Users/Actions/CheckUserEmail.php`
- **Purpose**: Email validation
- **Responsibility**: Validate user email addresses

#### **`App\Modules\Users\Actions\CheckUserPass`**
- **File**: `src/Modules/Users/Actions/CheckUserPass.php`
- **Purpose**: Password validation
- **Responsibility**: Validate user passwords

### **1.4 User Utilities**

#### **`App\Modules\Users\UserTimeZones`**
- **File**: `src/Modules/Users/UserTimeZones.php`
- **Purpose**: Timezone management
- **Responsibility**: Handle user timezone settings

#### **`App\Modules\Users\Handlers\Users_ForgotPassword_Handler`**
- **File**: `src/Modules/Users/Handlers/Users_ForgotPassword_Handler.php`
- **Purpose**: Password recovery handler
- **Responsibility**: Process password recovery events

---

## 🛡️ **Privilege Classes**

### **2.1 Core Privilege System**

#### **`App\Privilege`**
- **File**: `src/Privilege.php`
- **Purpose**: Main permission checking class
- **Responsibility**: Module/action/record permission checking
- **Key Methods**:
  - `isPermitted($moduleName, $actionName, $record, $userId)` - Check permission
  - `getCurrentUserId()` - Get current user ID

**Usage Example**:
```php
$canEdit = \App\Privilege::isPermitted('Accounts', 'EditView', $recordId);
$canCreate = \App\Privilege::isPermitted('Contacts', 'CreateView');
```

#### **`App\PrivilegeUtil`**
- **File**: `src/PrivilegeUtil.php`
- **Purpose**: Privilege utility functions
- **Responsibility**: Role management, permission calculations
- **Key Methods**:
  - `getParentRecordOwner($tabid, $parModId, $recordId)` - Get parent record owner
  - `getRoleByUsers($userId)` - Get user role
  - `getRoleDetail($roleId)` - Get role details
  - `getRoleSubordinates($roleId)` - Get subordinate roles
  - `getParentRole($roleId)` - Get parent roles

**Usage Example**:
```php
$userRole = \App\PrivilegeUtil::getRoleByUsers($userId);
$roleDetails = \App\PrivilegeUtil::getRoleDetail($userRole);
$subRoles = \App\PrivilegeUtil::getRoleSubordinates($userRole);
```

#### **`App\PrivilegeAdvanced`**
- **File**: `src/PrivilegeAdvanced.php`
- **Purpose**: Advanced permission system
- **Responsibility**: Complex permission rules, workflow permissions
- **Key Methods**:
  - `reloadCache()` - Reload advanced permission cache
  - `loadRules($tabId)` - Load permission rules for module
  - `checkAdvancedPermission($moduleName, $action, $recordId, $userId)` - Check advanced permissions

**Usage Example**:
```php
\App\PrivilegeAdvanced::reloadCache();
$hasAdvancedPermission = \App\PrivilegeAdvanced::checkAdvancedPermission('Accounts', 'EditView', $recordId, $userId);
```

#### **`App\PrivilegeUpdater`**
- **File**: `src/PrivilegeUpdater.php`
- **Purpose**: Permission cache management
- **Responsibility**: Global search permissions, cache management
- **Key Methods**:
  - `checkGlobalSearchPermissions($moduleName, $userId)` - Check global search permissions
  - `getGlobalSearchUsers()` - Get users with global search access
  - `reloadGlobalSearchPermissions()` - Reload global search cache

### **2.2 Privilege File Management**

#### **`App\PrivilegeFile`**
- **File**: `src/PrivilegeFile.php`
- **Purpose**: Privilege file operations
- **Responsibility**: User privilege files, sharing privilege files
- **Key Methods**:
  - `createUsersFile()` - Create general users file
  - `getUser($type)` - Get user information
  - `createUserPrivilegesFile($userId)` - Create user privilege file

**Usage Example**:
```php
\App\PrivilegeFile::createUsersFile();
$userInfo = \App\PrivilegeFile::getUser('id');
\App\PrivilegeFile::createUserPrivilegesFile($userId);
```

#### **`App\PrivilegeQuery`**
- **File**: `src/PrivilegeQuery.php`
- **Purpose**: Permission query building
- **Responsibility**: Database query construction for permissions
- **Key Methods**:
  - `getPrivilegeQuery($moduleName, $userId)` - Build privilege query
  - `getSharingQuery($moduleName, $userId)` - Build sharing query

---

## ⚙️ **Settings Classes**

### **3.1 Profile Management**

#### **`App\Modules\Settings\Profiles\Models\Module`**
- **File**: `src/Modules/Settings/Profiles/Models/Module.php`
- **Purpose**: Profile module management
- **Responsibility**: Profile module configuration and operations
- **Key Constants**:
  - `GLOBAL_ACTION_VIEW = 1` - Global view action
  - `GLOBAL_ACTION_EDIT = 2` - Global edit action
  - `IS_PERMITTED_VALUE = 0` - Permission granted value
  - `NOT_PERMITTED_VALUE = 1` - Permission denied value

#### **`App\Modules\Settings\Profiles\Models\Record`**
- **File**: `src/Modules/Settings/Profiles/Models/Record.php`
- **Purpose**: Individual profile records
- **Responsibility**: Profile record operations and validation

### **3.2 Role Management**

#### **`App\Modules\Settings\Roles\Models\Module`**
- **File**: `src/Modules/Settings/Roles/Models/Module.php`
- **Purpose**: Role module management
- **Responsibility**: Role module configuration and operations

#### **`App\Modules\Settings\Roles\Models\Record`**
- **File**: `src/Modules/Settings/Roles/Models/Record.php`
- **Purpose**: Individual role records
- **Responsibility**: Role record operations and validation

### **3.3 Group Management**

#### **`App\Modules\Settings\Groups\Models\Module`**
- **File**: `src/Modules/Settings/Groups/Models/Module.php`
- **Purpose**: Group module management
- **Responsibility**: Group module configuration and operations

#### **`App\Modules\Settings\Groups\Models\Record`**
- **File**: `src/Modules/Settings/Groups/Models/Record.php`
- **Purpose**: Individual group records
- **Responsibility**: Group record operations and validation

### **3.4 Advanced Permission Settings**

#### **`App\Modules\Settings\AdvancedPermission\*`**
- **Purpose**: Advanced permission configuration
- **Responsibility**: Complex permission rules, conditional permissions

#### **`App\Modules\Settings\GlobalPermission\*`**
- **Purpose**: Global permission settings
- **Responsibility**: System-wide permission configuration

#### **`App\Modules\Settings\SharingAccess\*`**
- **Purpose**: Sharing access configuration
- **Responsibility**: Record sharing rules and permissions

---

## 🔧 **Utility Classes**

### **4.1 User Information Utilities**

#### **`App\Utils\UserInfoUtil`**
- **File**: `src/Utils/UserInfoUtil.php`
- **Purpose**: User information utilities
- **Responsibility**: User data retrieval and processing
- **Key Methods**:
  - `getCombinedUserGlobalPermissions($userId)` - Get user global permissions
  - `getCombinedUserTabsPermissions($userId)` - Get user tab permissions
  - `getCombinedUserActionPermissions($userId)` - Get user action permissions
  - `getSubordinateRoleAndUsers($roleId)` - Get subordinate roles and users
  - `isPermitted($moduleName, $actionName, $record, $userId)` - Check permission

**Usage Example**:
```php
$globalPermissions = \App\Utils\UserInfoUtil::getCombinedUserGlobalPermissions($userId);
$tabPermissions = \App\Utils\UserInfoUtil::getCombinedUserTabsPermissions($userId);
$canAccess = \App\Utils\UserInfoUtil::isPermitted('Accounts', 'EditView', $recordId, $userId);
```

#### **`App\Utils\GetUserGroups`**
- **File**: `src/Utils/GetUserGroups.php`
- **Purpose**: User group retrieval
- **Responsibility**: Get groups for a specific user

#### **`App\Utils\GetGroupUsers`**
- **File**: `src/Utils/GetGroupUsers.php`
- **Purpose**: Group user retrieval
- **Responsibility**: Get users for a specific group

### **4.2 User Management**

#### **`App\User`**
- **File**: `src/User.php`
- **Purpose**: Main user management class
- **Responsibility**: User session management, privilege file handling
- **Key Methods**:
  - `getCurrentUserId()` - Get current user ID
  - `getUserModel($userId)` - Get user model
  - `getPrivilegesFile($userId)` - Get user privilege file
  - `clearCache($userId)` - Clear user cache

**Usage Example**:
```php
$currentUserId = \App\User::getCurrentUserId();
$userModel = \App\User::getUserModel($userId);
$privileges = \App\User::getPrivilegesFile($userId);
```

---

## 🎨 **UI/Field Classes**

### **5.1 User Interface Components**

#### **`App\Modules\Vtiger\UiTypes\UserCreator`**
- **File**: `src/Modules/Vtiger/UiTypes/UserCreator.php`
- **Purpose**: User creator field type
- **Responsibility**: User creation interface component

#### **`App\Modules\Vtiger\UiTypes\UserRole`**
- **File**: `src/Modules/Vtiger/UiTypes/UserRole.php`
- **Purpose**: User role field type
- **Responsibility**: User role selection interface

#### **`App\Modules\Vtiger\UiTypes\UserReference`**
- **File**: `src/Modules/Vtiger/UiTypes/UserReference.php`
- **Purpose**: User reference field type
- **Responsibility**: User reference field interface

### **5.2 Query Field Components**

#### **`App\QueryField\UserCreatorField`**
- **File**: `src/QueryField/UserCreatorField.php`
- **Purpose**: User creator query field
- **Responsibility**: User creator field in queries

#### **`App\QueryField\UserRoleField`**
- **File**: `src/QueryField/UserRoleField.php`
- **Purpose**: User role query field
- **Responsibility**: User role field in queries

---

## 🔄 **Class Relationships**

### **6.1 Inheritance Structure**

```
App\Runtime\Vtiger_Base_Model
├── App\Modules\Users\Models\Privileges
├── App\Modules\Users\Models\Record
└── App\Modules\Users\Models\Module

App\CRMEntity
└── App\Modules\Users\Users

App\Runtime\Vtiger_Action_Controller
├── App\Modules\Users\Actions\SwitchUsers
├── App\Modules\Users\Actions\ForgotPassword
└── App\Modules\Users\Actions\CheckUserEmail

App\Runtime\Vtiger_View_Controller
└── App\Modules\Users\Views\SwitchUsers
```

### **6.2 Dependency Flow**

```
User Creation/Update Flow:
├── App\Modules\Users\Users (Entity)
├── App\Modules\Users\Models\Record (Record Model)
├── App\PrivilegeFile (File Generation)
├── App\Modules\Users\Models\Privileges (Permission Validation)
└── App\Privilege (Permission Checking)

Permission Checking Flow:
├── App\Privilege (Main Permission Check)
├── App\PrivilegeAdvanced (Advanced Rules)
├── App\Modules\Users\Models\Privileges (User Permissions)
└── App\Utils\UserInfoUtil (Permission Utilities)
```

### **6.3 Data Flow Diagram**

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   User Input    │───▶│  User Models     │───▶│  Privilege      │
│                 │    │                  │    │  Validation     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│  Settings       │◀───│  Privilege File  │◀───│  Permission     │
│  Management     │    │  Generation      │    │  Cache          │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

---

## 📈 **Migration Status**

### **7.1 Already Migrated (Modern Classes)**
- ✅ `App\Modules\Users\Models\*` - All user models (100%)
- ✅ `App\Modules\Settings\Profiles\Models\*` - Profile models (100%)
- ✅ `App\Modules\Settings\Roles\Models\*` - Role models (100%)
- ✅ `App\Modules\Settings\Groups\Models\*` - Group models (100%)
- ✅ `App\Privilege*` - All privilege classes (100%)
- ✅ `App\Utils\UserInfoUtil` - User info utilities (100%)

### **7.2 Still Using Global Functions**
- ❌ `src/Modules/Users/CreateUserPrivilegeFile.php` - **3 global functions**
  - `createUserPrivilegesfile($userid)`
  - `createUserSharingPrivilegesfile($userid)`
  - `getRelatedModuleSharingArray($par_mod, $share_mod, ...)`

### **7.3 Legacy Classes (Partially Modern)**
- ⚠️ `App\Modules\Users\Users` - Entity class (modern structure, legacy methods)
- ⚠️ Some utility functions in various files

### **7.4 Migration Progress**
- **Overall Progress**: 85% complete
- **Core Classes**: 100% migrated
- **Utility Functions**: 90% migrated
- **Global Functions**: 15% remaining

---

## 💡 **Usage Examples**

### **8.1 User Management**

```php
// Create a new user
$user = new \App\Modules\Users\Users();
$user->retrieve_entity_info($userId, 'Users');
$user->set('first_name', 'John');
$user->set('last_name', 'Doe');
$user->set('email1', 'john.doe@example.com');
$user->save();

// Get user record model
$userRecord = \App\Modules\Users\Models\Record::getInstanceById($userId);
$isAdmin = $userRecord->isAdminUser();
$fullName = $userRecord->get('first_name') . ' ' . $userRecord->get('last_name');
```

### **8.2 Permission Checking**

```php
// Check basic permissions
$canEdit = \App\Privilege::isPermitted('Accounts', 'EditView', $recordId);
$canCreate = \App\Privilege::isPermitted('Contacts', 'CreateView');

// Check advanced permissions
$hasAdvancedPermission = \App\PrivilegeAdvanced::checkAdvancedPermission(
    'Accounts', 'EditView', $recordId, $userId
);

// Check user privileges
$userPrivileges = new \App\Modules\Users\Models\Privileges();
$canRead = $userPrivileges->hasGlobalReadPermission();
$canAccessModule = $userPrivileges->hasModulePermission('Accounts');
```

### **8.3 Role and Group Management**

```php
// Get user role information
$userRole = \App\PrivilegeUtil::getRoleByUsers($userId);
$roleDetails = \App\PrivilegeUtil::getRoleDetail($userRole);
$subRoles = \App\PrivilegeUtil::getRoleSubordinates($userRole);

// Get user groups
$userGroups = \App\Utils\GetUserGroups::getAllUserGroups($userId);
$groupUsers = \App\Utils\GetGroupUsers::getAllUsersInGroup($groupId);
```

### **8.4 Privilege File Management**

```php
// Create privilege files
\App\PrivilegeFile::createUsersFile();
\App\PrivilegeFile::createUserPrivilegesFile($userId);

// Get user information
$userInfo = \App\PrivilegeFile::getUser('id');
$userModel = \App\User::getUserModel($userId);
$privileges = \App\User::getPrivilegesFile($userId);
```

---

## 🎯 **Best Practices**

### **9.1 Class Usage Guidelines**

1. **Use Models for Data Operations**
   ```php
   // ✅ Good
   $userRecord = \App\Modules\Users\Models\Record::getInstanceById($userId);
   $userRecord->set('first_name', 'John');
   $userRecord->save();
   
   // ❌ Avoid
   $user = new \App\Modules\Users\Users();
   $user->retrieve_entity_info($userId, 'Users');
   ```

2. **Use Privilege Classes for Permission Checking**
   ```php
   // ✅ Good
   $canEdit = \App\Privilege::isPermitted('Accounts', 'EditView', $recordId);
   
   // ❌ Avoid direct database queries for permissions
   ```

3. **Use Utility Classes for Helper Functions**
   ```php
   // ✅ Good
   $userRole = \App\PrivilegeUtil::getRoleByUsers($userId);
   $globalPermissions = \App\Utils\UserInfoUtil::getCombinedUserGlobalPermissions($userId);
   ```

### **9.2 Performance Considerations**

1. **Cache User Privileges**
   ```php
   // ✅ Good - Use cached privileges
   $userModel = \App\User::getUserModel($userId);
   $privileges = $userModel->getPrivilegesFile($userId);
   
   // ❌ Avoid - Don't reload privileges unnecessarily
   ```

2. **Use Static Methods for Utility Functions**
   ```php
   // ✅ Good
   $canAccess = \App\Privilege::isPermitted('Accounts', 'EditView');
   
   // ❌ Avoid - Don't instantiate classes for simple checks
   ```

### **9.3 Security Best Practices**

1. **Always Validate Permissions**
   ```php
   // ✅ Good
   if (\App\Privilege::isPermitted('Accounts', 'EditView', $recordId)) {
       // Perform edit operation
   }
   
   // ❌ Never skip permission checks
   ```

2. **Use Role-Based Access Control**
   ```php
   // ✅ Good
   $userRole = \App\PrivilegeUtil::getRoleByUsers($userId);
   $isAdmin = $userRecord->isAdminUser();
   
   // ❌ Don't hardcode user IDs or permissions
   ```

### **9.4 Error Handling**

```php
try {
    $userRecord = \App\Modules\Users\Models\Record::getInstanceById($userId);
    if (!$userRecord) {
        throw new \Exception('User not found');
    }
    
    $canEdit = \App\Privilege::isPermitted('Accounts', 'EditView', $recordId);
    if (!$canEdit) {
        throw new \Exception('Permission denied');
    }
    
    // Perform operation
} catch (\Exception $e) {
    \App\Log::error('User operation failed: ' . $e->getMessage());
    // Handle error appropriately
}
```

---

## 📚 **Additional Resources**

### **Related Documentation**
- [FreeCRM Architecture Guidelines](architecture.md)
- [PSR-4 Migration Guide](psr4-migration.md)
- [API Documentation](api-documentation.md)

### **Code Examples**
- [User Management Examples](../examples/user-management.php)
- [Permission Checking Examples](../examples/permission-checking.php)
- [Role Management Examples](../examples/role-management.php)

### **Testing**
- [Unit Tests](../tests/UserPrivilegeTests.php)
- [Integration Tests](../tests/UserManagementIntegrationTests.php)

---

## 🔄 **Version History**

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024-01-15 | Initial documentation |
| 1.1 | 2024-01-20 | Added usage examples and best practices |
| 1.2 | 2024-01-25 | Updated migration status and class relationships |

---

## 📞 **Support**

For questions or issues related to User and Privilege classes:

- **Documentation Issues**: Create an issue in the documentation repository
- **Code Issues**: Create an issue in the main FreeCRM repository
- **General Questions**: Contact the development team

---

*This documentation is maintained by the FreeCRM development team and is updated regularly to reflect the current state of the codebase.*
