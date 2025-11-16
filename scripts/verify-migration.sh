#!/bin/bash
#
# FreeCRM Migration Verification Script
# Author: bmankowski@gmail.com
# License: FreeCRM Public License 1.1
#
# This script verifies that a migration was successful
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

PROJECT_ROOT="/home/bmankowski/projects/FreeCRM"
ERRORS_FOUND=0
WARNINGS_FOUND=0

print_header() {
    echo -e "${BOLD}${BLUE}==================================================${NC}"
    echo -e "${BOLD}${BLUE}  FreeCRM Migration Verification${NC}"
    echo -e "${BOLD}${BLUE}==================================================${NC}"
    echo ""
}

print_info() {
    echo -e "${CYAN}ℹ ${NC}$1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
    ERRORS_FOUND=$((ERRORS_FOUND + 1))
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
    WARNINGS_FOUND=$((WARNINGS_FOUND + 1))
}

print_step() {
    echo -e "\n${BOLD}${BLUE}→ $1${NC}"
}

print_header

# Step 1: Check for old namespace references
print_step "Step 1: Checking for old namespace references in src/"

OLD_REFS=$(grep -rn "^namespace App;" "$PROJECT_ROOT/src" 2>/dev/null | grep -v "src/App/" | wc -l)

if [[ $OLD_REFS -gt 0 ]]; then
    print_warning "Found $OLD_REFS files still with 'namespace App;' in src/"
    echo "  Files:"
    grep -rn "^namespace App;" "$PROJECT_ROOT/src" 2>/dev/null | grep -v "src/App/" | head -5
    if [[ $OLD_REFS -gt 5 ]]; then
        echo "  ... and $((OLD_REFS - 5)) more"
    fi
else
    print_success "No files with old namespace found"
fi

# Step 2: Check for syntax errors
print_step "Step 2: Checking for PHP syntax errors"

SYNTAX_ERRORS=0
while IFS= read -r file; do
    if ! php -l "$file" > /dev/null 2>&1; then
        print_error "Syntax error in: $file"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
    fi
done < <(find "$PROJECT_ROOT/src" -name "*.php" -type f)

if [[ $SYNTAX_ERRORS -eq 0 ]]; then
    print_success "No syntax errors found"
else
    print_error "Found $SYNTAX_ERRORS files with syntax errors"
fi

# Step 3: Check autoloader
print_step "Step 3: Verifying autoloader"

cd "$PROJECT_ROOT"
if composer dump-autoload --quiet 2>&1; then
    print_success "Autoloader regenerated successfully"
else
    print_error "Failed to regenerate autoloader"
fi

# Step 4: Check for class_alias (should not exist per project rules)
print_step "Step 4: Checking for class aliases (should be none)"

ALIASES=$(grep -rn "class_alias" "$PROJECT_ROOT/src" 2>/dev/null | wc -l)

if [[ $ALIASES -gt 0 ]]; then
    print_error "Found $ALIASES class_alias declarations (project rules forbid aliases)"
    grep -rn "class_alias" "$PROJECT_ROOT/src" 2>/dev/null
else
    print_success "No class aliases found (good!)"
fi

# Step 5: Check system log for new errors
print_step "Step 5: Checking system log for recent errors"

LOG_FILE="$PROJECT_ROOT/cache/logs/system.log"

if [[ -f "$LOG_FILE" ]]; then
    # Check last 50 lines for errors
    RECENT_ERRORS=$(tail -50 "$LOG_FILE" 2>/dev/null | grep -i "error\|fatal\|exception" | wc -l)
    
    if [[ $RECENT_ERRORS -gt 0 ]]; then
        print_warning "Found $RECENT_ERRORS recent error entries in system log"
        echo "  Last few errors:"
        tail -50 "$LOG_FILE" 2>/dev/null | grep -i "error\|fatal\|exception" | tail -3
        echo ""
        echo "  Review full log: tail -50 $LOG_FILE"
    else
        print_success "No recent errors in system log"
    fi
else
    print_info "System log not found (may not have been created yet)"
fi

# Step 6: Check for missing use statements
print_step "Step 6: Checking for potential missing use statements"

# Look for files that might have unqualified class names
# This is a heuristic check - may have false positives
POTENTIAL_ISSUES=0

while IFS= read -r file; do
    # Skip files in global namespace
    if ! grep -q "^namespace " "$file" 2>/dev/null; then
        continue
    fi
    
    # Check if file uses common class names without qualification
    # and doesn't have use statement for them
    if grep -q "new Record\|Record::" "$file" 2>/dev/null; then
        if ! grep -q "use.*Record" "$file" 2>/dev/null; then
            print_warning "Potential missing use statement in: $file (uses 'Record')"
            POTENTIAL_ISSUES=$((POTENTIAL_ISSUES + 1))
        fi
    fi
done < <(find "$PROJECT_ROOT/src" "$PROJECT_ROOT/modules" -name "*.php" -type f 2>/dev/null)

if [[ $POTENTIAL_ISSUES -eq 0 ]]; then
    print_success "No obvious missing use statements detected"
fi

# Step 7: Check if migrated files were properly moved
print_step "Step 7: Checking for orphaned files"

# Check if there are any .backup files left over
BACKUP_FILES=$(find "$PROJECT_ROOT/src" -name "*.backup" 2>/dev/null | wc -l)

if [[ $BACKUP_FILES -gt 0 ]]; then
    print_warning "Found $BACKUP_FILES .backup files in src/"
    find "$PROJECT_ROOT/src" -name "*.backup" 2>/dev/null
else
    print_success "No backup files found in src/"
fi

# Step 8: Summary and recommendations
print_step "Step 8: Verification Summary"
echo ""

if [[ $ERRORS_FOUND -eq 0 ]] && [[ $WARNINGS_FOUND -eq 0 ]]; then
    print_success "Migration verification completed - NO ISSUES FOUND!"
    echo ""
    print_info "Recommended next steps:"
    echo "  1. Test application in browser: http://localhost:8080"
    echo "  2. Run functional tests if available"
    echo "  3. Commit changes: git add -A && git commit"
elif [[ $ERRORS_FOUND -eq 0 ]]; then
    print_warning "Migration verification completed with $WARNINGS_FOUND WARNINGS"
    echo ""
    print_info "Review warnings above before proceeding"
    echo "  1. Check system logs: tail -f $LOG_FILE"
    echo "  2. Test application thoroughly"
    echo "  3. Fix any issues found"
else
    print_error "Migration verification FAILED with $ERRORS_FOUND ERRORS and $WARNINGS_FOUND WARNINGS"
    echo ""
    print_error "DO NOT COMMIT - Fix errors first!"
    echo ""
    print_info "Recommended actions:"
    echo "  1. Review errors above"
    echo "  2. Restore from backup if needed (migration/backups/)"
    echo "  3. Fix syntax errors"
    echo "  4. Re-run this verification script"
    exit 1
fi

echo ""

