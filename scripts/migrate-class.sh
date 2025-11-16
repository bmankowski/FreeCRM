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
# Search in src/, modules/, and layouts/ directories
# Pattern 1: use App\ClassName; (only in PHP files)
grep -rn "use ${OLD_FQN};" "$PROJECT_ROOT/src" "$PROJECT_ROOT/modules" 2>/dev/null | grep "\.php:" | grep -v ".swp" > "$TEMP_USE_STATEMENTS" || true

# Pattern 2: new ClassName( or ClassName:: (in PHP and TPL files)
grep -rn "\(new ${CLASS_NAME}\|${CLASS_NAME}::\|extends ${CLASS_NAME}\|implements ${CLASS_NAME}\)" "$PROJECT_ROOT/src" "$PROJECT_ROOT/modules" "$PROJECT_ROOT/layouts" 2>/dev/null | grep "\.\(php\|tpl\):" | grep -v ".swp" > "$TEMP_USAGE_FILE" || true

# Pattern 3: \App\ClassName (in PHP and TPL files)
# Use perl for reliable backslash matching instead of grep
# Perl escapes: OLD_FQN has single backslashes, we need to match literal backslash in files
PERL_SEARCH_FQN=$(echo "$OLD_FQN" | sed 's/\\/\\\\/g')
find "$PROJECT_ROOT/src" "$PROJECT_ROOT/modules" "$PROJECT_ROOT/layouts" -type f \( -name "*.php" -o -name "*.tpl" \) ! -name "*.swp" -exec perl -ne "print \"$ARGV:$.:$_\" if /\\\\${PERL_SEARCH_FQN}([^a-zA-Z0-9_]|$)/" {} \; > "$TEMP_QUALIFIED_USAGE" 2>/dev/null || true

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
echo "  All references will be updated to fully qualified class names (FQCN)"
echo "  Updates will include: PHP files (.php) and Smarty templates (.tpl)"
echo "  No 'use' statements will be added - we use explicit FQCN everywhere"
echo ""
echo "  Pattern 1 - Existing use statements (PHP only):"
echo "    FROM: use ${OLD_FQN};"
echo "    TO:   (removed - using FQCN instead)"
echo ""
echo "  Pattern 2 - Fully qualified names (PHP + TPL):"
echo "    FROM: \\${OLD_FQN}"
echo "    TO:   \\${NEW_FQN}"
echo ""
echo "  Pattern 3 - Unqualified class names (PHP + TPL):"
echo "    FROM: new ${CLASS_NAME}( or ${CLASS_NAME}::"
echo "    TO:   new \\${NEW_FQN}( or \\${NEW_FQN}::"
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

# Step 8: Check git status
print_step "Step 8: Checking git status"
cd "$PROJECT_ROOT"

# Check if there are uncommitted changes
if ! git diff-index --quiet HEAD -- 2>/dev/null; then
    print_warning "You have uncommitted changes in git"
    print_info "Recommendation: commit or stash changes before migration"
    echo ""
fi

# Show current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
print_info "Current branch: $CURRENT_BRANCH"
echo ""

# Step 9: Confirm before proceeding
print_step "Step 9: Confirmation"
echo ""
print_warning "This will modify $TOTAL_REFS file(s) in the codebase"
print_info "Use git to rollback if needed: git checkout ."
echo ""
read -p "Do you want to proceed with the migration? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_error "Migration cancelled by user"
    rm -f "$TEMP_USAGE_FILE" "$TEMP_USE_STATEMENTS" "$TEMP_NEW_STATEMENTS" "$TEMP_QUALIFIED_USAGE"
    exit 1
fi

# Step 10: Update namespace in the source file
print_step "Step 10: Updating namespace in source file"

# Use a temporary file to avoid sed issues
TEMP_SOURCE=$(mktemp)
cp "$SOURCE_FILE" "$TEMP_SOURCE"

# Escape backslashes for sed
SED_NEW_NAMESPACE=$(echo "$NEW_NAMESPACE" | sed 's/\\/\\\\/g')

# Update namespace - properly escaped for sed
sed -i "s|^namespace ${OLD_NAMESPACE};|namespace ${SED_NEW_NAMESPACE};|g" "$TEMP_SOURCE"

print_success "Updated namespace in file"

# Step 11: Move the file
print_step "Step 11: Moving file to new location"
mv "$TEMP_SOURCE" "$TARGET_FILE"
rm -f "$SOURCE_FILE"
print_success "Moved file to: $TARGET_FILE"

# Step 12: Remove existing use statements (we use FQCN everywhere)
print_step "Step 12: Removing old use statements"
UPDATED_USE=0

if [[ $USE_COUNT -gt 0 ]]; then
    while IFS=: read -r file line content; do
        if [[ -f "$file" ]]; then
            # Remove old use statement completely
            sed -i "/^use ${OLD_FQN};/d" "$file"
            UPDATED_USE=$((UPDATED_USE + 1))
            if [[ "$VERBOSE" == true ]]; then
                print_info "Removed use statement from: $file"
            fi
        fi
    done < "$TEMP_USE_STATEMENTS"
fi

print_success "Removed $UPDATED_USE old use statements"

# Step 13: Update fully qualified class names (with leading backslash)
print_step "Step 13: Updating fully qualified class names"
UPDATED_QUALIFIED=0

if [[ $QUALIFIED_COUNT -gt 0 ]]; then
    # Get unique files from qualified usage
    UNIQUE_QUALIFIED_FILES=$(cat "$TEMP_QUALIFIED_USAGE" | cut -d: -f1 | sort -u)
    
    # Escape backslashes for perl regex
    PERL_OLD_FQN=$(echo "$OLD_FQN" | sed 's/\\/\\\\/g')
    PERL_NEW_FQN=$(echo "$NEW_FQN" | sed 's/\\/\\\\/g')
    
    for file in $UNIQUE_QUALIFIED_FILES; do
        if [[ -f "$file" ]]; then
            # Use perl for reliable backslash replacement
            # Pattern: \App\ClassName → \App\SubDir\ClassName
            # Match with word boundary or non-word character after to avoid partial matches
            perl -pi -e "s/\\\\${PERL_OLD_FQN}([^a-zA-Z0-9_]|$)/\\\\${PERL_NEW_FQN}\$1/g" "$file"
            UPDATED_QUALIFIED=$((UPDATED_QUALIFIED + 1))
            if [[ "$VERBOSE" == true ]]; then
                print_info "Updated FQCN: $file"
            fi
        fi
    done
fi

print_success "Updated $UPDATED_QUALIFIED fully qualified references"

# Step 14: Update other class usages to FQCN (no use statements)
print_step "Step 14: Updating class usages to fully qualified names"
UPDATED_OTHER=0

# Get unique files from usage
UNIQUE_FILES=$(cat "$TEMP_USAGE_FILE" | cut -d: -f1 | sort -u)

# Escape backslashes for perl regex
PERL_NEW_FQN=$(echo "$NEW_FQN" | sed 's/\\/\\\\/g')

for file in $UNIQUE_FILES; do
    if [[ -f "$file" ]]; then
        # Always use fully qualified class names (FQCN) - no use statements
        # Use perl for reliable regex replacement with backslashes
        # Works for both .php and .tpl files
        
        # Pattern 1: new ClassName( -> new \App\SubDir\ClassName(
        perl -pi -e "s/(new\\s+)${CLASS_NAME}(\\s*\\()/\$1\\\\${PERL_NEW_FQN}\$2/g" "$file"
        
        # Pattern 2: ClassName:: -> \App\SubDir\ClassName::
        perl -pi -e "s/([^\\\\])${CLASS_NAME}::/\$1\\\\${PERL_NEW_FQN}::/g" "$file"
        # Handle start of line
        perl -pi -e "s/^${CLASS_NAME}::/\\\\${PERL_NEW_FQN}::/g" "$file"
        
        # Pattern 3: extends ClassName -> extends \App\SubDir\ClassName
        perl -pi -e "s/(extends\\s+)${CLASS_NAME}(\\s|\\{)/\$1\\\\${PERL_NEW_FQN}\$2/g" "$file"
        
        # Pattern 4: implements ClassName -> implements \App\SubDir\ClassName
        perl -pi -e "s/(implements\\s+)${CLASS_NAME}(\\s|,|\\{)/\$1\\\\${PERL_NEW_FQN}\$2/g" "$file"
        
        UPDATED_OTHER=$((UPDATED_OTHER + 1))
        if [[ "$VERBOSE" == true ]]; then
            FILE_EXT="${file##*.}"
            print_info "Updated to FQCN: $file (.${FILE_EXT})"
        fi
    fi
done

print_success "Updated $UPDATED_OTHER files to use fully qualified class names"

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
echo "  - Removed old use statements: $UPDATED_USE files"
echo "  - Updated fully qualified names: $UPDATED_QUALIFIED files"
echo "  - Updated to FQCN (no use): $UPDATED_OTHER files"
echo "  ${BOLD}Total files modified: $((UPDATED_USE + UPDATED_QUALIFIED + UPDATED_OTHER + 1))${NC}"
echo "  ${BOLD}Strategy: All references use FQCN (\\${NEW_FQN})${NC}"
echo ""

print_warning "Next steps:"
echo "  1. Test the application: curl http://localhost:8080/index.php"
echo "  2. Check logs: tail -f cache/logs/system.log"
echo "  3. Run verification: ./scripts/verify-migration.sh"
echo "  4. Review changes: git diff --stat"
echo "  5. If something broke: git checkout . (or git restore .)"
echo ""
print_info "Commit when ready (after completing phase or batch)"
echo ""

# Cleanup
rm -f "$TEMP_USAGE_FILE" "$TEMP_USE_STATEMENTS" "$TEMP_NEW_STATEMENTS" "$TEMP_QUALIFIED_USAGE"

print_success "Done!"

