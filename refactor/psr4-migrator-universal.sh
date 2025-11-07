#!/bin/bash

# FreeCRM Universal PSR-4 Migration Script
# Author: AI Assistant
# Description: Universal migration script with basic and advanced features
# Version: 2.0

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR"
CONFIG_FILE="$PROJECT_ROOT/psr4-migrator.config"
BACKUP_DIR="$PROJECT_ROOT/migration_backups"
LOG_FILE="$PROJECT_ROOT/migration.log"
ALIAS_FILE="$PROJECT_ROOT/include/LegacyAliases.php"

# Mode configuration
MODE="basic"  # basic | advanced
DRY_RUN=false
VERBOSE=false

# Advanced features (enabled in advanced mode)
CREATE_ALIASES=false
UPDATE_COMPOSER=false
ANALYZE_DEPS=false
VALIDATE_MIGRATION=false

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Migration tracking (advanced mode)
declare -A MIGRATED_CLASSES=()
declare -A CLASS_DEPENDENCIES=()
declare -A NAMESPACE_MAP=()

# Namespace mapping configuration (basic mode)
declare -A BASIC_NAMESPACE_MAP=(
    # Core Vtiger classes
    ["Vtiger_"]="Vtiger\\Core"
    ["CRMEntity"]="Vtiger\\Core\\Entity"
    ["AppConfig"]="Vtiger\\Config"
    ["PearDatabase"]="Vtiger\\Database"
    ["LanguageTranslator"]="Vtiger\\Language"
    
    # Module patterns
    ["Users"]="Vtiger\\Modules\\Users"
    ["Reports"]="Vtiger\\Modules\\Reports"
    ["Calendar"]="Vtiger\\Modules\\Calendar"
    ["Leads"]="Vtiger\\Modules\\Leads"
    ["Accounts"]="Vtiger\\Modules\\Accounts"
    ["Contacts"]="Vtiger\\Modules\\Contacts"
    ["HelpDesk"]="Vtiger\\Modules\\HelpDesk"
    ["Documents"]="Vtiger\\Modules\\Documents"
    ["Products"]="Vtiger\\Modules\\Products"
    ["Campaigns"]="Vtiger\\Modules\\Campaigns"
    
    # Settings modules
    ["Settings_"]="Vtiger\\Modules\\Settings"
    
    # OSS modules
    ["OSSMail"]="Vtiger\\Modules\\OSSMail"
    ["OSSTimeControl"]="Vtiger\\Modules\\OSSTimeControl"
    ["OSSEmployees"]="Vtiger\\Modules\\OSSEmployees"
    ["OSSPasswords"]="Vtiger\\Modules\\OSSPasswords"
    
    # Other modules
    ["PBXManager"]="Vtiger\\Modules\\PBXManager"
    ["SMSNotifier"]="Vtiger\\Modules\\SMSNotifier"
    ["ModComments"]="Vtiger\\Modules\\ModComments"
    ["Integration"]="Vtiger\\Modules\\Integration"
    ["DashBoard"]="Vtiger\\Modules\\Dashboard"
    ["CustomView"]="Vtiger\\Modules\\CustomView"
    ["Import"]="Vtiger\\Modules\\Import"
    ["Export"]="Vtiger\\Modules\\Export"
    ["Inventory"]="Vtiger\\Modules\\Inventory"
    ["Migration"]="Vtiger\\Modules\\Migration"
    ["Install"]="Vtiger\\Modules\\Install"
    ["WSAPP"]="Vtiger\\Modules\\WSAPP"
)

# File type mappings
declare -A FILE_TYPE_MAP=(
    ["models"]="Models"
    ["views"]="Views"
    ["actions"]="Actions"
    ["dashboards"]="Dashboards"
    ["textparsers"]="TextParsers"
    ["uitypes"]="UiTypes"
    ["handlers"]="Handlers"
    ["helpers"]="Helpers"
    ["data_access"]="DataAccess"
)

# Load configuration (advanced mode)
load_config() {
    if [ "$MODE" = "advanced" ] && [ -f "$CONFIG_FILE" ]; then
        log "Loading configuration from: $CONFIG_FILE"
        
        # Parse namespace mappings
        while IFS='=' read -r key value; do
            if [[ "$key" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]] && [[ "$value" =~ ^\"[^\"]*\"$ ]]; then
                # Remove quotes from value
                value="${value%\"}"
                value="${value#\"}"
                NAMESPACE_MAP["$key"]="$value"
            fi
        done < <(grep -A 1000 '^\[NAMESPACE_MAPPINGS\]' "$CONFIG_FILE" | grep -B 1000 '^\[.*\]' | grep -v '^\[.*\]')
    fi
}

# Logging functions
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

# Help function
show_help() {
    cat << EOF
FreeCRM Universal PSR-4 Migration Script v2.0

Usage: $0 [OPTIONS] COMMAND [ARGS]

Mode Options:
    --basic                 Use basic migration features (default)
    --advanced              Use advanced migration features

Commands:
    scan                    Scan for legacy classes
    migrate [PATH]          Migrate specific file or directory
    migrate-module [MODULE] Migrate entire module
    migrate-core           Migrate core classes (include/)
    migrate-all            Migrate all classes (advanced mode only)
    dry-run [PATH]         Show what would be migrated without making changes
    backup                 Create backup of current state
    restore [BACKUP_ID]    Restore from backup
    status                 Show migration status
    help                   Show this help

Advanced Commands (--advanced mode only):
    analyze-dependencies   Analyze class dependencies
    create-aliases         Create class aliases for backward compatibility
    update-composer        Update composer.json autoload section
    validate               Validate migrated classes

Options:
    -v, --verbose          Verbose output
    -d, --dry-run          Show changes without applying them
    -h, --help             Show help

Examples:
    # Basic mode
    $0 scan                                    # Scan all legacy classes
    $0 migrate modules/Users/Users.php         # Migrate specific file
    $0 migrate-module Users                    # Migrate entire Users module
    $0 migrate-core                            # Migrate core classes
    
    # Advanced mode
    $0 --advanced scan                         # Scan with dependency analysis
    $0 --advanced create-aliases               # Create backward compatibility aliases
    $0 --advanced update-composer              # Update composer.json
    $0 --advanced validate                     # Validate migration

EOF
}

# Set mode and enable advanced features
set_mode() {
    if [ "$1" = "advanced" ]; then
        MODE="advanced"
        CREATE_ALIASES=true
        UPDATE_COMPOSER=true
        ANALYZE_DEPS=true
        VALIDATE_MIGRATION=true
        log "Advanced mode enabled"
    else
        MODE="basic"
        CREATE_ALIASES=false
        UPDATE_COMPOSER=false
        ANALYZE_DEPS=false
        VALIDATE_MIGRATION=false
        log "Basic mode enabled"
    fi
}

# Create backup with metadata
create_backup() {
    local backup_id="backup_$(date +%Y%m%d_%H%M%S)"
    local backup_path="$BACKUP_DIR/$backup_id"
    
    log "Creating backup: $backup_id"
    
    mkdir -p "$backup_path"
    
    # Copy modified files
    if [ -f "$PROJECT_ROOT/.migration_status" ]; then
        while IFS= read -r file; do
            if [ -f "$file" ]; then
                local dir_path=$(dirname "$file")
                mkdir -p "$backup_path/$dir_path"
                cp "$file" "$backup_path/$file"
            fi
        done < "$PROJECT_ROOT/.migration_status"
    fi
    
    # Save migration metadata (advanced mode)
    if [ "$MODE" = "advanced" ]; then
        cat > "$backup_path/metadata.json" << EOF
{
    "backup_id": "$backup_id",
    "timestamp": "$(date -Iseconds)",
    "migrated_files": $(wc -l < "$PROJECT_ROOT/.migration_status" 2>/dev/null || echo 0),
    "migrated_classes": ${#MIGRATED_CLASSES[@]},
    "mode": "$MODE",
    "version": "2.0"
}
EOF
    fi
    
    log_success "Backup created: $backup_id"
    echo "$backup_id"
}

# Restore from backup
restore_backup() {
    local backup_id="$1"
    local backup_path="$BACKUP_DIR/$backup_id"
    
    if [ ! -d "$backup_path" ]; then
        log_error "Backup not found: $backup_id"
        exit 1
    fi
    
    log "Restoring from backup: $backup_id"
    
    # Restore files
    find "$backup_path" -type f -name "*.php" | while read -r backup_file; do
        local relative_path="${backup_file#$backup_path/}"
        local target_file="$PROJECT_ROOT/$relative_path"
        
        if [ -f "$target_file" ]; then
            cp "$backup_file" "$target_file"
            log "Restored: $relative_path"
        fi
    done
    
    log_success "Restore completed"
}

# Scan for legacy classes
scan_legacy_classes() {
    log "Scanning for legacy classes..."
    
    local total_files=0
    local legacy_files=0
    
    # Scan include/ directory
    log "Scanning include/ directory..."
    while IFS= read -r -d '' file; do
        if grep -q "^class [A-Z][a-zA-Z_]*[^\\]" "$file" 2>/dev/null; then
            echo "INCLUDE: $file"
            ((legacy_files++))
        fi
        ((total_files++))
    done < <(find "$PROJECT_ROOT/include" -name "*.php" -type f -print0)
    
    # Scan modules/ directory
    log "Scanning modules/ directory..."
    while IFS= read -r -d '' file; do
        if grep -q "^class [A-Z][a-zA-Z_]*[^\\]" "$file" 2>/dev/null; then
            echo "MODULE: $file"
            ((legacy_files++))
        fi
        ((total_files++))
    done < <(find "$PROJECT_ROOT/modules" -name "*.php" -type f -print0)
    
    log "Scan complete: $legacy_files legacy files found out of $total_files total PHP files"
    
    if [ $legacy_files -gt 0 ]; then
        log_warning "Found $legacy_files files with legacy class definitions"
        return 1
    else
        log_success "No legacy classes found"
        return 0
    fi
}

# Analyze class dependencies (advanced mode)
analyze_dependencies() {
    if [ "$MODE" != "advanced" ]; then
        log_error "Dependency analysis requires --advanced mode"
        exit 1
    fi
    
    log "Analyzing class dependencies..."
    
    # Find all class usages
    find "$PROJECT_ROOT" -name "*.php" -type f | while read -r file; do
        # Skip vendor and cache directories
        if [[ "$file" =~ /vendor/ ]] || [[ "$file" =~ /cache/ ]]; then
            continue
        fi
        
        # Extract class names from file
        while IFS= read -r line; do
            if [[ "$line" =~ (new\s+([A-Z][a-zA-Z0-9_]*)|extends\s+([A-Z][a-zA-Z0-9_]*)|implements\s+([A-Z][a-zA-Z0-9_]*)) ]]; then
                local class_name="${BASH_REMATCH[2]:-${BASH_REMATCH[3]:-${BASH_REMATCH[4]}}}"
                if [ -n "$class_name" ]; then
                    CLASS_DEPENDENCIES["$class_name"]="${CLASS_DEPENDENCIES[$class_name]:-} $file"
                fi
            fi
        done < "$file"
    done
    
    log "Dependency analysis complete"
    
    if [ "$VERBOSE" = true ]; then
        log "Class dependencies:"
        for class in "${!CLASS_DEPENDENCIES[@]}"; do
            log "  $class: ${CLASS_DEPENDENCIES[$class]}"
        done
    fi
}

# Determine namespace for a class
get_namespace_for_class() {
    local class_name="$1"
    local file_path="$2"
    
    # Use advanced namespace map if available, otherwise fallback to basic
    if [ "$MODE" = "advanced" ] && [ ${#NAMESPACE_MAP[@]} -gt 0 ]; then
        # Check direct mappings first
        for prefix in "${!NAMESPACE_MAP[@]}"; do
            if [[ "$class_name" == "$prefix"* ]]; then
                echo "${NAMESPACE_MAP[$prefix]}"
                return 0
            fi
        done
    fi
    
    # Check basic mappings
    for prefix in "${!BASIC_NAMESPACE_MAP[@]}"; do
        if [[ "$class_name" == "$prefix"* ]]; then
            echo "${BASIC_NAMESPACE_MAP[$prefix]}"
            return 0
        fi
    done
    
    # Extract module name from file path
    if [[ "$file_path" =~ modules/([^/]+) ]]; then
        local module_name="${BASH_REMATCH[1]}"
        
        # Handle special cases
        case "$module_name" in
            "Vtiger")
                if [[ "$file_path" =~ models/([^/]+)\.php$ ]]; then
                    echo "Vtiger\\Core\\Models"
                elif [[ "$file_path" =~ views/([^/]+)\.php$ ]]; then
                    echo "Vtiger\\Core\\Views"
                elif [[ "$file_path" =~ actions/([^/]+)\.php$ ]]; then
                    echo "Vtiger\\Core\\Actions"
                else
                    echo "Vtiger\\Core"
                fi
                ;;
            *)
                # Extract file type from path
                if [[ "$file_path" =~ ([^/]+)/([^/]+)\.php$ ]]; then
                    local file_type="${BASH_REMATCH[1]}"
                    local file_class="${BASH_REMATCH[2]}"
                    
                    if [[ -n "${FILE_TYPE_MAP[$file_type]:-}" ]]; then
                        echo "Vtiger\\Modules\\${module_name}\\${FILE_TYPE_MAP[$file_type]}"
                    else
                        echo "Vtiger\\Modules\\${module_name}"
                    fi
                else
                    echo "Vtiger\\Modules\\${module_name}"
                fi
                ;;
        esac
        return 0
    fi
    
    # Default namespace for include/ files
    if [[ "$file_path" =~ include/ ]]; then
        if [[ "$file_path" =~ include/runtime/ ]]; then
            echo "Vtiger\\Runtime"
        elif [[ "$file_path" =~ include/http/ ]]; then
            echo "Vtiger\\Http"
        elif [[ "$file_path" =~ include/utils/ ]]; then
            echo "Vtiger\\Utils"
        elif [[ "$file_path" =~ include/fields/ ]]; then
            echo "Vtiger\\Fields"
        elif [[ "$file_path" =~ include/Webservices/ ]]; then
            echo "Vtiger\\Webservices"
        else
            echo "Vtiger\\Core"
        fi
        return 0
    fi
    
    # Fallback
    echo "Vtiger\\Legacy"
}

# Migrate a single file
migrate_file() {
    local file_path="$1"
    local temp_file="${file_path}.tmp"
    
    if [ ! -f "$file_path" ]; then
        log_error "File not found: $file_path"
        return 1
    fi
    
    log "Migrating: $file_path"
    
    # Create backup of original file
    cp "$file_path" "${file_path}.backup"
    
    # Extract class name
    local class_name=$(grep -o "^class [A-Z][a-zA-Z_]*[^\\]" "$file_path" | sed 's/^class //')
    
    if [ -z "$class_name" ]; then
        log_warning "No class found in: $file_path"
        return 0
    fi
    
    # Get namespace
    local namespace=$(get_namespace_for_class "$class_name" "$file_path")
    local new_class_name="$class_name"
    
    # Remove module prefix if present
    if [[ "$class_name" =~ ^[A-Z][a-zA-Z_]*_[A-Z] ]]; then
        new_class_name=$(echo "$class_name" | sed 's/^[A-Z][a-zA-Z_]*_//')
    fi
    
    log "Class: $class_name -> Namespace: $namespace, New name: $new_class_name"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would migrate $class_name to $namespace\\$new_class_name"
        return 0
    fi
    
    # Create temporary file with namespace
    {
        # Add namespace declaration
        echo "<?php"
        echo "namespace $namespace;"
        echo ""
        
        # Copy file content, skipping opening PHP tag and class declaration
        sed -n '2,$p' "$file_path" | sed "s/^class $class_name/class $new_class_name/"
        
    } > "$temp_file"
    
    # Replace original file
    mv "$temp_file" "$file_path"
    
    # Track migrated class (advanced mode)
    if [ "$MODE" = "advanced" ]; then
        MIGRATED_CLASSES["$class_name"]="$namespace\\$new_class_name"
    fi
    
    # Track migrated file
    echo "$file_path" >> "$PROJECT_ROOT/.migration_status"
    
    log_success "Migrated: $file_path"
}

# Migrate directory
migrate_directory() {
    local dir_path="$1"
    
    if [ ! -d "$dir_path" ]; then
        log_error "Directory not found: $dir_path"
        return 1
    fi
    
    log "Migrating directory: $dir_path"
    
    find "$dir_path" -name "*.php" -type f | while read -r file; do
        migrate_file "$file"
    done
    
    log_success "Directory migration completed: $dir_path"
}

# Migrate specific module
migrate_module() {
    local module_name="$1"
    local module_path="$PROJECT_ROOT/modules/$module_name"
    
    if [ ! -d "$module_path" ]; then
        log_error "Module not found: $module_name"
        return 1
    fi
    
    log "Migrating module: $module_name"
    
    migrate_directory "$module_path"
    
    log_success "Module migration completed: $module_name"
}

# Migrate core classes
migrate_core() {
    log "Migrating core classes..."
    
    migrate_directory "$PROJECT_ROOT/include"
    
    log_success "Core migration completed"
}

# Migrate all classes (advanced mode only)
migrate_all() {
    if [ "$MODE" != "advanced" ]; then
        log_error "Full migration requires --advanced mode"
        exit 1
    fi
    
    log "Migrating all classes (WARNING: This is a major operation)"
    log_warning "This will migrate ALL legacy classes. Make sure you have a backup!"
    
    read -p "Are you sure you want to continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Migration cancelled"
        exit 0
    fi
    
    # Migrate core first
    migrate_core
    
    # Migrate all modules
    find "$PROJECT_ROOT/modules" -maxdepth 1 -type d | while read -r module_dir; do
        local module_name=$(basename "$module_dir")
        if [ "$module_name" != "modules" ] && [ "$module_name" != "." ]; then
            migrate_module "$module_name"
        fi
    done
    
    log_success "Full migration completed"
}

# Create class aliases for backward compatibility (advanced mode)
create_aliases() {
    if [ "$MODE" != "advanced" ]; then
        log_error "Class aliases require --advanced mode"
        exit 1
    fi
    
    log "Creating class aliases for backward compatibility..."
    
    cat > "$ALIAS_FILE" << 'EOF'
<?php
/* +**********************************************************************************
 * Legacy Class Aliases for PSR-4 Migration
 * This file provides backward compatibility for migrated classes
 * Generated automatically by PSR-4 Migration Script v2.0
 * ********************************************************************************** */

// Core aliases
if (!class_exists('Vtiger_WebUI')) {
    class_alias('Vtiger\Core\WebUI', 'Vtiger_WebUI');
}

if (!class_exists('Vtiger_EntryPoint')) {
    class_alias('Vtiger\Core\EntryPoint', 'Vtiger_EntryPoint');
}

if (!class_exists('Vtiger_Session')) {
    class_alias('Vtiger\Http\Session', 'Vtiger_Session');
}

if (!class_exists('Vtiger_Request')) {
    class_alias('Vtiger\Http\Request', 'Vtiger_Request');
}

if (!class_exists('Vtiger_Response')) {
    class_alias('Vtiger\Http\Response', 'Vtiger_Response');
}

if (!class_exists('Vtiger_Loader')) {
    class_alias('Vtiger\Core\Loader', 'Vtiger_Loader');
}

if (!class_exists('Vtiger_Language_Handler')) {
    class_alias('Vtiger\Language\Handler', 'Vtiger_Language_Handler');
}

if (!class_exists('FreeCRM_Viewer')) {
    class_alias('Vtiger\Runtime\Viewer', 'FreeCRM_Viewer');
}

if (!class_exists('Vtiger_Theme')) {
    class_alias('Vtiger\Runtime\Theme', 'Vtiger_Theme');
}

if (!class_exists('Vtiger_JavaScript')) {
    class_alias('Vtiger\Runtime\JavaScript', 'Vtiger_JavaScript');
}

if (!class_exists('Vtiger_Controller')) {
    class_alias('Vtiger\Runtime\Controller', 'Vtiger_Controller');
}

if (!class_exists('Vtiger_Base_Model')) {
    class_alias('Vtiger\Core\Models\BaseModel', 'Vtiger_Base_Model');
}

if (!class_exists('Vtiger_Record_Model')) {
    class_alias('Vtiger\Core\Models\RecordModel', 'Vtiger_Record_Model');
}

if (!class_exists('Vtiger_Module_Model')) {
    class_alias('Vtiger\Core\Models\ModuleModel', 'Vtiger_Module_Model');
}

if (!class_exists('Vtiger_Field_Model')) {
    class_alias('Vtiger\Core\Models\FieldModel', 'Vtiger_Field_Model');
}

// Entity aliases
if (!class_exists('CRMEntity')) {
    class_alias('Vtiger\Core\Entity\CRMEntity', 'CRMEntity');
}

if (!class_exists('Users')) {
    class_alias('Vtiger\Modules\Users\Users', 'Users');
}

if (!class_exists('Reports')) {
    class_alias('Vtiger\Modules\Reports\Reports', 'Reports');
}

if (!class_exists('Calendar')) {
    class_alias('Vtiger\Modules\Calendar\Calendar', 'Calendar');
}

if (!class_exists('Leads')) {
    class_alias('Vtiger\Modules\Leads\Leads', 'Leads');
}

if (!class_exists('Accounts')) {
    class_alias('Vtiger\Modules\Accounts\Accounts', 'Accounts');
}

if (!class_exists('Contacts')) {
    class_alias('Vtiger\Modules\Contacts\Contacts', 'Contacts');
}

if (!class_exists('HelpDesk')) {
    class_alias('Vtiger\Modules\HelpDesk\HelpDesk', 'HelpDesk');
}

if (!class_exists('Documents')) {
    class_alias('Vtiger\Modules\Documents\Documents', 'Documents');
}

if (!class_exists('Products')) {
    class_alias('Vtiger\Modules\Products\Products', 'Products');
}

if (!class_exists('Campaigns')) {
    class_alias('Vtiger\Modules\Campaigns\Campaigns', 'Campaigns');
}

// Utility aliases
if (!class_exists('EmailTemplate')) {
    class_alias('Vtiger\Utils\EmailTemplate', 'EmailTemplate');
}

if (!class_exists('LanguageTranslator')) {
    class_alias('Vtiger\Language\Translator', 'LanguageTranslator');
}

if (!class_exists('AppConfig')) {
    class_alias('Vtiger\Config\AppConfig', 'AppConfig');
}

if (!class_exists('PearDatabase')) {
    class_alias('Vtiger\Database\PearDatabase', 'PearDatabase');
}

EOF
    
    log_success "Class aliases created: $ALIAS_FILE"
}

# Update composer.json autoload section (advanced mode)
update_composer() {
    if [ "$MODE" != "advanced" ]; then
        log_error "Composer update requires --advanced mode"
        exit 1
    fi
    
    log "Updating composer.json autoload section..."
    
    local composer_file="$PROJECT_ROOT/composer.json"
    
    if [ ! -f "$composer_file" ]; then
        log_error "composer.json not found"
        return 1
    fi
    
    # Create backup
    cp "$composer_file" "${composer_file}.backup"
    
    # Update autoload section
    cat > "$composer_file.tmp" << EOF
{
	"name": "yetiforce/yetiforce-crm",
	"description": "An open and innovative CRM system.",
	"keywords": ["YetiForceCRM", "CRM", "open source crm", "best crm"],
	"license": "YetiForce Public License 1.1",
	"homepage": "https://yetiforce.com/",
	"type": "project",
	"support": {
		"issues": "https://github.com/YetiForceCompany/YetiForceCRM/issues",
		"wiki": "https://yetiforce.com/en/documentation.html",
		"source": "https://github.com/YetiForceCompany/YetiForceCRM"
	},
	"authors": [
		{
			"name": "YetiForce team",
			"email": "info@titantech.pl",
			"homepage": "https://yetiforce.com/"
		}
	],
	"require": {
		"php": ">=5.6.0",
		"ext-imap": "*",
		"ext-openssl": "*",
		"ext-mbstring": "*",
		"ext-spl": "*",
		"ext-json": "*",
		"ext-dom": "*",
		"ext-zlib": "*",
		"ext-pdo": "*",
		"ext-curl": "*",
		"ext-gd": "*",
		"ext-pcre": "*",
		"ext-session": "*",
		"ext-soap": "*",
		"ext-zip": "*",
		"ext-xml": "*",
		"rmccue/requests": ">=1.7",
		"smarty/smarty": "^4",
		"phpmailer/phpmailer": ">=v5.2.22",
		"ezyang/htmlpurifier": ">=v4.8.0",
		"symfony/var-dumper": "^7.3",
		"simshaun/recurr": "^2.2",
		"linfo/linfo": "v3.0.1",
		"yiisoft/yii2": "~2.0.53",
		"antlr/antlr4-php-runtime": "^0.9.1",
		"monolog/monolog": "^3.9",
		"filp/whoops": "^2.18"
	},
	"archive": {
		"exclude": [
			"tests"
		]
	},
	"config": {
		"autoloader-suffix": "YT",
		"allow-plugins": {
			"yiisoft/yii2-composer": true
		}
	},
	"autoload": {
		"psr-4": {
			"vtlib\\": "vtlib/Vtiger/",
			"includes\\": "include/",
			"App\\": "vendor/yetiforce/",
			"App\\Api\\": "src/Api/",
			"Exception\\": "include/exceptions/",
			"DebugBar\\": "vendor/php-debugbar/src/DebugBar/",
			"Vtiger\\Core\\": "include/core/",
			"Vtiger\\Runtime\\": "include/runtime/",
			"Vtiger\\Http\\": "include/http/",
			"Vtiger\\Utils\\": "include/utils/",
			"Vtiger\\Fields\\": "include/fields/",
			"Vtiger\\Webservices\\": "include/Webservices/",
			"Vtiger\\Language\\": "include/language/",
			"Vtiger\\Config\\": "include/config/",
			"Vtiger\\Database\\": "include/database/",
			"Vtiger\\Modules\\Users\\": "modules/Users/",
			"Vtiger\\Modules\\Reports\\": "modules/Reports/",
			"Vtiger\\Modules\\Calendar\\": "modules/Calendar/",
			"Vtiger\\Modules\\Leads\\": "modules/Leads/",
			"Vtiger\\Modules\\Accounts\\": "modules/Accounts/",
			"Vtiger\\Modules\\Contacts\\": "modules/Contacts/",
			"Vtiger\\Modules\\HelpDesk\\": "modules/HelpDesk/",
			"Vtiger\\Modules\\Documents\\": "modules/Documents/",
			"Vtiger\\Modules\\Products\\": "modules/Products/",
			"Vtiger\\Modules\\Campaigns\\": "modules/Campaigns/",
			"Vtiger\\Modules\\Settings\\": "modules/Settings/",
			"Vtiger\\Modules\\OSSMail\\": "modules/OSSMail/",
			"Vtiger\\Modules\\OSSTimeControl\\": "modules/OSSTimeControl/",
			"Vtiger\\Modules\\OSSEmployees\\": "modules/OSSEmployees/",
			"Vtiger\\Modules\\OSSPasswords\\": "modules/OSSPasswords/",
			"Vtiger\\Modules\\PBXManager\\": "modules/PBXManager/",
			"Vtiger\\Modules\\SMSNotifier\\": "modules/SMSNotifier/",
			"Vtiger\\Modules\\ModComments\\": "modules/ModComments/",
			"Vtiger\\Modules\\Integration\\": "modules/Integration/",
			"Vtiger\\Modules\\Dashboard\\": "modules/Dashboard/",
			"Vtiger\\Modules\\CustomView\\": "modules/CustomView/",
			"Vtiger\\Modules\\Import\\": "modules/Import/",
			"Vtiger\\Modules\\Export\\": "modules/Export/",
			"Vtiger\\Modules\\Inventory\\": "modules/Inventory/",
			"Vtiger\\Modules\\Migration\\": "modules/Migration/",
			"Vtiger\\Modules\\Install\\": "modules/Install/",
			"Vtiger\\Modules\\WSAPP\\": "modules/WSAPP/"
		}
	},
	"repositories": {
		"asset-packagist": {
			"type": "composer",
			"url": "https://asset-packagist.org"
		}
	}
}
EOF
    
    mv "$composer_file.tmp" "$composer_file"
    
    log_success "composer.json updated"
}

# Validate migrated classes (advanced mode)
validate_migration() {
    if [ "$MODE" != "advanced" ]; then
        log_error "Validation requires --advanced mode"
        exit 1
    fi
    
    log "Validating migrated classes..."
    
    local errors=0
    
    # Check for syntax errors
    find "$PROJECT_ROOT" -name "*.php" -type f | while read -r file; do
        if php -l "$file" >/dev/null 2>&1; then
            if [ "$VERBOSE" = true ]; then
                log "✓ $file"
            fi
        else
            log_error "Syntax error in: $file"
            ((errors++))
        fi
    done
    
    # Check for missing classes
    if [ -f "$ALIAS_FILE" ]; then
        if php -l "$ALIAS_FILE" >/dev/null 2>&1; then
            log_success "Alias file syntax is valid"
        else
            log_error "Syntax error in alias file: $ALIAS_FILE"
            ((errors++))
        fi
    fi
    
    if [ $errors -eq 0 ]; then
        log_success "Validation completed successfully"
    else
        log_error "Validation failed with $errors errors"
        return 1
    fi
}

# Show migration status
show_status() {
    log "Migration Status:"
    log "Mode: $MODE"
    
    if [ -f "$PROJECT_ROOT/.migration_status" ]; then
        local migrated_count=$(wc -l < "$PROJECT_ROOT/.migration_status")
        log "Migrated files: $migrated_count"
        
        if [ "$VERBOSE" = true ]; then
            log "Migrated files list:"
            cat "$PROJECT_ROOT/.migration_status"
        fi
    else
        log "No migration status file found"
    fi
    
    if [ "$MODE" = "advanced" ] && [ ${#MIGRATED_CLASSES[@]} -gt 0 ]; then
        log "Migrated classes: ${#MIGRATED_CLASSES[@]}"
    fi
    
    # Show backup list
    if [ -d "$BACKUP_DIR" ]; then
        log "Available backups:"
        ls -la "$BACKUP_DIR" | grep "^d" | awk '{print $9}' | grep -v "^\.$\|^\.\.$" | head -10
    fi
}

# Main function
main() {
    # Create necessary directories
    mkdir -p "$BACKUP_DIR"
    
    # Initialize log
    echo "=== FreeCRM Universal PSR-4 Migration Log v2.0 ===" > "$LOG_FILE"
    
    # Parse mode and arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --basic)
                set_mode "basic"
                shift
                ;;
            --advanced)
                set_mode "advanced"
                shift
                ;;
            -v|--verbose)
                VERBOSE=true
                shift
                ;;
            -d|--dry-run)
                DRY_RUN=true
                shift
                ;;
            -h|--help)
                show_help
                exit 0
                ;;
            scan)
                if [ "$MODE" = "advanced" ]; then
                    load_config
                    analyze_dependencies
                fi
                scan_legacy_classes
                exit $?
                ;;
            migrate)
                if [ -z "${2:-}" ]; then
                    log_error "Path required for migrate command"
                    exit 1
                fi
                if [ "$MODE" = "advanced" ]; then
                    load_config
                fi
                if [ -f "$2" ]; then
                    migrate_file "$2"
                elif [ -d "$2" ]; then
                    migrate_directory "$2"
                else
                    log_error "Path not found: $2"
                    exit 1
                fi
                exit 0
                ;;
            migrate-module)
                if [ -z "${2:-}" ]; then
                    log_error "Module name required for migrate-module command"
                    exit 1
                fi
                if [ "$MODE" = "advanced" ]; then
                    load_config
                fi
                migrate_module "$2"
                exit 0
                ;;
            migrate-core)
                if [ "$MODE" = "advanced" ]; then
                    load_config
                fi
                migrate_core
                exit 0
                ;;
            migrate-all)
                if [ "$MODE" = "advanced" ]; then
                    load_config
                    migrate_all
                else
                    log_error "Full migration requires --advanced mode"
                    exit 1
                fi
                exit 0
                ;;
            dry-run)
                if [ -z "${2:-}" ]; then
                    log_error "Path required for dry-run command"
                    exit 1
                fi
                DRY_RUN=true
                if [ "$MODE" = "advanced" ]; then
                    load_config
                fi
                if [ -f "$2" ]; then
                    migrate_file "$2"
                elif [ -d "$2" ]; then
                    migrate_directory "$2"
                else
                    log_error "Path not found: $2"
                    exit 1
                fi
                exit 0
                ;;
            analyze-dependencies)
                if [ "$MODE" = "advanced" ]; then
                    analyze_dependencies
                else
                    log_error "Dependency analysis requires --advanced mode"
                    exit 1
                fi
                exit 0
                ;;
            create-aliases)
                if [ "$MODE" = "advanced" ]; then
                    create_aliases
                else
                    log_error "Class aliases require --advanced mode"
                    exit 1
                fi
                exit 0
                ;;
            update-composer)
                if [ "$MODE" = "advanced" ]; then
                    update_composer
                else
                    log_error "Composer update requires --advanced mode"
                    exit 1
                fi
                exit 0
                ;;
            validate)
                if [ "$MODE" = "advanced" ]; then
                    validate_migration
                else
                    log_error "Validation requires --advanced mode"
                    exit 1
                fi
                exit $?
                ;;
            backup)
                create_backup
                exit 0
                ;;
            restore)
                if [ -z "${2:-}" ]; then
                    log_error "Backup ID required for restore command"
                    exit 1
                fi
                restore_backup "$2"
                exit 0
                ;;
            status)
                show_status
                exit 0
                ;;
            help)
                show_help
                exit 0
                ;;
            *)
                log_error "Unknown command: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    # No command provided
    show_help
    exit 1
}

# Run main function
main "$@"
