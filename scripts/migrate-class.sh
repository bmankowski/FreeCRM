#!/bin/bash
#
# FreeCRM Class Migration Script
# Author: bmankowski@gmail.com
# License: FreeCRM Public License 1.1
#
# This script migrates a class from src/ to a subdirectory with proper namespace update
# and updates all references throughout the codebase.
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# Configuration
PROJECT_ROOT="/home/bmankowski/projects/FreeCRM"
DRY_RUN=false
VERBOSE=false
BACKUP_DIR="${PROJECT_ROOT}/migration/backups/$(date +%Y%m%d_%H%M%S)"

# Print functions
print_header() {
    echo -e "${BOLD}${BLUE}==================================================${NC}"
    echo -e "${BOLD}${BLUE}  FreeCRM Class Migration Script${NC}"
    echo -e "${BOLD}${BLUE}==================================================${NC}"
    echo ""
}

print_info() {
    echo -e "${CYAN}ℹ ${NC}$1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_step() {
    echo -e "\n${BOLD}${BLUE}→ $1${NC}"
}

# Usage
usage() {
    cat << EOF
Usage: $0 [OPTIONS] <class-name> <target-directory>

Migrate a class from src/ to a subdirectory with namespace updates.

Arguments:
    class-name          Name of the class file (e.g., AppConfig.php)
    target-directory    Target subdirectory in src/ (e.g., Config)

Options:
    --dry-run          Show what would be done without making changes
    --verbose          Show detailed output
    -h, --help         Show this help message

Examples:
    # Dry run to see what would happen
    $0 --dry-run AppConfig.php Config

    # Actually migrate the class
    $0 AppConfig.php Config

    # Verbose dry run
    $0 --dry-run --verbose Db.php Database

EOF
    exit 1
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        -h|--help)
            usage
            ;;
        *)
            if [[ -z "$CLASS_FILE" ]]; then
                CLASS_FILE="$1"
            elif [[ -z "$TARGET_DIR" ]]; then
                TARGET_DIR="$1"
            else
                print_error "Too many arguments"
                usage
            fi
            shift
            ;;
    esac
done

# Validate arguments
if [[ -z "$CLASS_FILE" ]] || [[ -z "$TARGET_DIR" ]]; then
    print_error "Missing required arguments"
    usage
fi

# Extract class name without .php extension
CLASS_NAME="${CLASS_FILE%.php}"

# Paths
SOURCE_FILE="${PROJECT_ROOT}/src/${CLASS_FILE}"
TARGET_PATH="${PROJECT_ROOT}/src/${TARGET_DIR}"
TARGET_FILE="${TARGET_PATH}/${CLASS_FILE}"

# Old and new namespaces
OLD_NAMESPACE="App"
NEW_NAMESPACE="App\\${TARGET_DIR}"

# Old and new fully qualified class names
OLD_FQN="App\\${CLASS_NAME}"
NEW_FQN="App\\${TARGET_DIR}\\${CLASS_NAME}"

print_header

if [[ "$DRY_RUN" == true ]]; then
    print_warning "DRY RUN MODE - No changes will be made"
    echo ""
fi

# Step 1: Validate source file exists
print_step "Step 1: Validating source file"
if [[ ! -f "$SOURCE_FILE" ]]; then
    print_error "Source file does not exist: $SOURCE_FILE"
    exit 1
fi
print_success "Source file exists: $SOURCE_FILE"

# Step 2: Check current namespace
print_step "Step 2: Checking current namespace"
CURRENT_NAMESPACE=$(grep -m 1 "^namespace " "$SOURCE_FILE" | sed 's/namespace \(.*\);/\1/')
if [[ "$CURRENT_NAMESPACE" != "$OLD_NAMESPACE" ]]; then
    print_warning "Current namespace is '$CURRENT_NAMESPACE', expected '$OLD_NAMESPACE'"
    print_warning "This class may have already been migrated or has a different structure"
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Migration cancelled"
        exit 1
    fi
fi
print_success "Current namespace: $CURRENT_NAMESPACE"

# Step 3: Check if target directory exists
print_step "Step 3: Checking target directory"
if [[ ! -d "$TARGET_PATH" ]]; then
    print_warning "Target directory does not exist: $TARGET_PATH"
    if [[ "$DRY_RUN" == true ]]; then
        print_info "Would create directory: $TARGET_PATH"
    else
        read -p "Create it? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            mkdir -p "$TARGET_PATH"
            print_success "Created directory: $TARGET_PATH"
        else
            print_error "Migration cancelled"
            exit 1
        fi
    fi
else
    print_success "Target directory exists: $TARGET_PATH"
fi

# Step 4: Check if target file already exists
print_step "Step 4: Checking target file"
if [[ -f "$TARGET_FILE" ]] && [[ "$DRY_RUN" == false ]]; then
    print_error "Target file already exists: $TARGET_FILE"
    exit 1
fi
print_success "Target file path available: $TARGET_FILE"

# Step 5: Find all usages in the codebase
print_step "Step 5: Analyzing usage in codebase"
print_info "Searching for references to: $CLASS_NAME"

# Create temporary files for storing results
TEMP_USAGE_FILE=$(mktemp)
TEMP_USE_STATEMENTS=$(mktemp)
TEMP_NEW_STATEMENTS=$(mktemp)
TEMP_QUALIFIED_USAGE=$(mktemp)

# Search patterns (avoiding false positives)
# Pattern 1: use App\ClassName;
grep -rn "use ${OLD_FQN};" "$PROJECT_ROOT/src" "$PROJECT_ROOT/modules" 2>/dev/null | grep -v ".swp" > "$TEMP_USE_STATEMENTS" || true

# Pattern 2: new ClassName( or ClassName::
grep -rn "\(new ${CLASS_NAME}\|${CLASS_NAME}::\|extends ${CLASS_NAME}\|implements ${CLASS_NAME}\)" "$PROJECT_ROOT/src" "$PROJECT_ROOT/modules" 2>/dev/null | grep "\.php:" | grep -v ".swp" > "$TEMP_USAGE_FILE" || true

# Pattern 3: \App\ClassName
grep -rn "\\\\${OLD_FQN}" "$PROJECT_ROOT/src" "$PROJECT_ROOT/modules" 2>/dev/null | grep "\.php:" | grep -v ".swp" > "$TEMP_QUALIFIED_USAGE" || true

USE_COUNT=$(wc -l < "$TEMP_USE_STATEMENTS")
USAGE_COUNT=$(wc -l < "$TEMP_USAGE_FILE")
QUALIFIED_COUNT=$(wc -l < "$TEMP_QUALIFIED_USAGE")
TOTAL_REFS=$((USE_COUNT + USAGE_COUNT + QUALIFIED_COUNT))

echo ""
print_info "Found references:"
echo "  - use statements: $USE_COUNT"
echo "  - Class usage: $USAGE_COUNT"
echo "  - Qualified usage: $QUALIFIED_COUNT"
echo "  ${BOLD}Total: $TOTAL_REFS${NC}"

if [[ "$VERBOSE" == true ]] && [[ $TOTAL_REFS -gt 0 ]]; then
    echo ""
    print_info "Detailed usage locations:"
    echo ""
    
    if [[ $USE_COUNT -gt 0 ]]; then
        echo -e "${YELLOW}use statements:${NC}"
        cat "$TEMP_USE_STATEMENTS" | head -20
        if [[ $USE_COUNT -gt 20 ]]; then
            echo "... and $((USE_COUNT - 20)) more"
        fi
        echo ""
    fi
    
    if [[ $USAGE_COUNT -gt 0 ]]; then
        echo -e "${YELLOW}Class usage:${NC}"
        cat "$TEMP_USAGE_FILE" | head -20
        if [[ $USAGE_COUNT -gt 20 ]]; then
            echo "... and $((USAGE_COUNT - 20)) more"
        fi
        echo ""
    fi
    
    if [[ $QUALIFIED_COUNT -gt 0 ]]; then
        echo -e "${YELLOW}Qualified usage:${NC}"
        cat "$TEMP_QUALIFIED_USAGE" | head -20
        if [[ $QUALIFIED_COUNT -gt 20 ]]; then
            echo "... and $((QUALIFIED_COUNT - 20)) more"
        fi
        echo ""
    fi
fi

# Step 6: Show the proposed changes
print_step "Step 6: Proposed changes"
echo ""
print_info "File movement:"
echo "  FROM: $SOURCE_FILE"
echo "  TO:   $TARGET_FILE"
echo ""

print_info "Namespace update in moved file:"
echo "  FROM: namespace $OLD_NAMESPACE;"
echo "  TO:   namespace $NEW_NAMESPACE;"
echo ""

print_info "References update throughout codebase:"
echo "  Pattern 1 - use statements:"
echo "    FROM: use ${OLD_FQN};"
echo "    TO:   use ${NEW_FQN};"
echo ""
echo "  Pattern 2 - Fully qualified names (with leading backslash):"
echo "    FROM: \\${OLD_FQN}"
echo "    TO:   \\${NEW_FQN}"
echo ""
echo "  Pattern 3 - Fully qualified names (without leading backslash in namespace context):"
echo "    FROM: ${OLD_FQN} (when not preceded by \\)"
echo "    TO:   ${NEW_FQN}"
echo ""

# Step 7: Preview file changes
if [[ "$VERBOSE" == true ]]; then
    print_step "Step 7: Preview of file content changes"
    echo ""
    print_info "Changes to the migrated file:"
    echo ""
    
    # Show namespace line that will be changed
    grep -n "^namespace " "$SOURCE_FILE" || true
    echo "  ↓ will become ↓"
    echo "namespace ${NEW_NAMESPACE};"
    echo ""
fi

# If dry run, stop here
if [[ "$DRY_RUN" == true ]]; then
    echo ""
    print_success "DRY RUN completed successfully"
    print_info "To perform the actual migration, run without --dry-run flag"
    
    # Cleanup
    rm -f "$TEMP_USAGE_FILE" "$TEMP_USE_STATEMENTS" "$TEMP_NEW_STATEMENTS" "$TEMP_QUALIFIED_USAGE"
    exit 0
fi

# Step 8: Confirm before proceeding
print_step "Step 8: Confirmation"
echo ""
print_warning "This will modify $TOTAL_REFS file(s) in the codebase"
print_warning "A backup will be created in: $BACKUP_DIR"
echo ""
read -p "Do you want to proceed with the migration? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_error "Migration cancelled by user"
    rm -f "$TEMP_USAGE_FILE" "$TEMP_USE_STATEMENTS" "$TEMP_NEW_STATEMENTS" "$TEMP_QUALIFIED_USAGE"
    exit 1
fi

# Step 9: Create backup
print_step "Step 9: Creating backup"
mkdir -p "$BACKUP_DIR"

# Backup the source file
cp "$SOURCE_FILE" "$BACKUP_DIR/"
print_success "Backed up: $CLASS_FILE"

# Backup all files that will be modified
if [[ $USE_COUNT -gt 0 ]]; then
    while IFS=: read -r file line content; do
        if [[ -f "$file" ]]; then
            backup_path="$BACKUP_DIR/$(basename "$file").backup"
            cp "$file" "$backup_path" 2>/dev/null || true
        fi
    done < "$TEMP_USE_STATEMENTS"
fi

if [[ $USAGE_COUNT -gt 0 ]]; then
    while IFS=: read -r file line content; do
        if [[ -f "$file" ]]; then
            backup_path="$BACKUP_DIR/$(basename "$file").backup"
            cp "$file" "$backup_path" 2>/dev/null || true
        fi
    done < "$TEMP_USAGE_FILE"
fi

if [[ $QUALIFIED_COUNT -gt 0 ]]; then
    while IFS=: read -r file line content; do
        if [[ -f "$file" ]]; then
            backup_path="$BACKUP_DIR/$(basename "$file").backup"
            cp "$file" "$backup_path" 2>/dev/null || true
        fi
    done < "$TEMP_QUALIFIED_USAGE"
fi

print_success "Backup created in: $BACKUP_DIR"

# Step 10: Update namespace in the source file
print_step "Step 10: Updating namespace in source file"

# Use a temporary file to avoid sed issues
TEMP_SOURCE=$(mktemp)
cp "$SOURCE_FILE" "$TEMP_SOURCE"

# Update namespace - using single quotes to avoid shell interpretation
sed -i "s|^namespace ${OLD_NAMESPACE};|namespace ${NEW_NAMESPACE};|g" "$TEMP_SOURCE"

print_success "Updated namespace in file"

# Step 11: Move the file
print_step "Step 11: Moving file to new location"
mv "$TEMP_SOURCE" "$TARGET_FILE"
rm -f "$SOURCE_FILE"
print_success "Moved file to: $TARGET_FILE"

# Step 12: Update use statements throughout codebase
print_step "Step 12: Updating use statements throughout codebase"
UPDATED_USE=0

if [[ $USE_COUNT -gt 0 ]]; then
    while IFS=: read -r file line content; do
        if [[ -f "$file" ]]; then
            # Update use statement - careful with backslashes
            # Use single quotes and proper escaping
            sed -i "s|use ${OLD_FQN};|use ${NEW_FQN};|g" "$file"
            UPDATED_USE=$((UPDATED_USE + 1))
            if [[ "$VERBOSE" == true ]]; then
                print_info "Updated: $file"
            fi
        fi
    done < "$TEMP_USE_STATEMENTS"
fi

print_success "Updated $UPDATED_USE use statements"

# Step 13: Update fully qualified class names (with leading backslash)
print_step "Step 13: Updating fully qualified class names"
UPDATED_QUALIFIED=0

if [[ $QUALIFIED_COUNT -gt 0 ]]; then
    while IFS=: read -r file line content; do
        if [[ -f "$file" ]]; then
            # Update \\App\\ClassName to \\App\\SubDir\\ClassName
            # This is tricky with sed - use single quotes and escape backslashes properly
            # In sed pattern: \\ becomes \\\\ (4 backslashes to match 2 literal backslashes)
            # In sed replacement: \\ becomes \\\\ as well
            
            # Create the sed pattern carefully
            # We're looking for: \App\ClassName
            # And replacing with: \App\SubDir\ClassName
            
            sed -i "s|\\\\${OLD_FQN//\\/\\\\}|\\\\${NEW_FQN//\\/\\\\}|g" "$file"
            UPDATED_QUALIFIED=$((UPDATED_QUALIFIED + 1))
            if [[ "$VERBOSE" == true ]]; then
                print_info "Updated: $file"
            fi
        fi
    done < "$TEMP_QUALIFIED_USAGE"
fi

print_success "Updated $UPDATED_QUALIFIED fully qualified references"

# Step 14: Update other class usages (new statements, static calls, etc.)
print_step "Step 14: Updating other class usages"
UPDATED_OTHER=0

# Get unique files from usage
UNIQUE_FILES=$(cat "$TEMP_USAGE_FILE" | cut -d: -f1 | sort -u)

for file in $UNIQUE_FILES; do
    if [[ -f "$file" ]]; then
        # Check if file has "use App\ClassName;" - if yes, skip (already handled in step 12)
        if grep -q "use ${NEW_FQN};" "$file" 2>/dev/null; then
            # File has proper use statement, class name doesn't need qualification
            if [[ "$VERBOSE" == true ]]; then
                print_info "Skipped (has use statement): $file"
            fi
            continue
        fi
        
        # Check if file is in global namespace (no namespace declaration)
        if ! grep -q "^namespace " "$file" 2>/dev/null; then
            # Global namespace - needs leading backslash
            # Replace: new ClassName( with new \App\SubDir\ClassName(
            # Replace: ClassName:: with \App\SubDir\ClassName::
            
            # Use perl for more reliable regex replacement
            perl -pi -e "s/(new )${CLASS_NAME}(\s*\()/\${1}\\${NEW_FQN//\\/\\\\}\${2}/g" "$file"
            perl -pi -e "s/${CLASS_NAME}(::|\\s)/\\${NEW_FQN//\\/\\\\}\${1}/g" "$file"
            
            UPDATED_OTHER=$((UPDATED_OTHER + 1))
            if [[ "$VERBOSE" == true ]]; then
                print_info "Updated (global namespace): $file"
            fi
        else
            # File has namespace but no use statement
            # Add use statement at the top after namespace
            FILE_NAMESPACE=$(grep -m 1 "^namespace " "$file" | sed 's/namespace \(.*\);/\1/')
            
            # Find the line number of the namespace declaration
            NAMESPACE_LINE=$(grep -n "^namespace " "$file" | head -1 | cut -d: -f1)
            
            # Insert use statement after namespace
            sed -i "${NAMESPACE_LINE}a\\use ${NEW_FQN};" "$file"
            
            UPDATED_OTHER=$((UPDATED_OTHER + 1))
            if [[ "$VERBOSE" == true ]]; then
                print_info "Added use statement to: $file"
            fi
        fi
    fi
done

print_success "Updated $UPDATED_OTHER other usages"

# Step 15: Run composer dump-autoload
print_step "Step 15: Regenerating autoloader"
cd "$PROJECT_ROOT"
composer dump-autoload --quiet
print_success "Autoloader regenerated"

# Step 16: Summary
print_step "Step 16: Migration Summary"
echo ""
print_success "Migration completed successfully!"
echo ""
print_info "Summary of changes:"
echo "  - Moved file: $CLASS_FILE → $TARGET_DIR/$CLASS_FILE"
echo "  - Updated namespace: $OLD_NAMESPACE → $NEW_NAMESPACE"
echo "  - Updated use statements: $UPDATED_USE files"
echo "  - Updated qualified names: $UPDATED_QUALIFIED files"
echo "  - Updated other usages: $UPDATED_OTHER files"
echo "  ${BOLD}Total files modified: $((UPDATED_USE + UPDATED_QUALIFIED + UPDATED_OTHER + 1))${NC}"
echo ""
print_info "Backup location: $BACKUP_DIR"
echo ""

print_warning "Next steps:"
echo "  1. Test the application: curl http://localhost:8080/index.php"
echo "  2. Check logs: tail -f cache/logs/system.log"
echo "  3. Run tests: composer test"
echo "  4. If everything works: git add -A && git commit -m 'refactor: migrate ${CLASS_NAME} to ${TARGET_DIR}/'"
echo "  5. If something broke: restore from backup in $BACKUP_DIR"
echo ""

# Cleanup
rm -f "$TEMP_USAGE_FILE" "$TEMP_USE_STATEMENTS" "$TEMP_NEW_STATEMENTS" "$TEMP_QUALIFIED_USAGE"

print_success "Done!"

