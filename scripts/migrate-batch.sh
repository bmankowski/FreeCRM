#!/bin/bash
#
# FreeCRM Batch Class Migration Script
# Author: bmankowski@gmail.com
# License: FreeCRM Public License 1.1
#
# This script migrates multiple classes based on migration-plan.json
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
MIGRATE_SCRIPT="${PROJECT_ROOT}/scripts/migrate-class.sh"
MIGRATION_PLAN="${PROJECT_ROOT}/migration/migration-plan.json"
DRY_RUN=false
PHASE=""

print_header() {
    echo -e "${BOLD}${BLUE}==================================================${NC}"
    echo -e "${BOLD}${BLUE}  FreeCRM Batch Migration Script${NC}"
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
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

usage() {
    cat << EOF
Usage: $0 [OPTIONS] [PHASE]

Migrate multiple classes based on migration-plan.json

Arguments:
    PHASE              Phase number to migrate (1-9, or 'all')

Options:
    --dry-run         Show what would be done without making changes
    --list-phases     List all available phases
    -h, --help        Show this help message

Examples:
    # Show all phases
    $0 --list-phases

    # Dry run for phase 1
    $0 --dry-run 1

    # Actually migrate phase 1
    $0 1

    # Migrate all phases (careful!)
    $0 all

EOF
    exit 1
}

list_phases() {
    print_header
    print_info "Available migration phases:"
    echo ""
    
    # Parse JSON and display phases
    # Note: requires jq for proper JSON parsing
    if command -v jq &> /dev/null; then
        jq -r '.migration_plan.phases[] | "Phase \(.phase): \(.name) (\(.classes | length) classes)"' "$MIGRATION_PLAN"
        echo ""
        echo "To see detailed class list, view: $MIGRATION_PLAN"
    else
        print_warning "jq not installed - showing raw plan"
        cat "$MIGRATION_PLAN"
    fi
    
    exit 0
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --list-phases)
            list_phases
            ;;
        -h|--help)
            usage
            ;;
        *)
            PHASE="$1"
            shift
            ;;
    esac
done

if [[ -z "$PHASE" ]]; then
    print_error "Missing phase argument"
    usage
fi

# Check if jq is available
if ! command -v jq &> /dev/null; then
    print_error "jq is required for this script"
    print_info "Install with: sudo apt-get install jq"
    exit 1
fi

# Check if migration plan exists
if [[ ! -f "$MIGRATION_PLAN" ]]; then
    print_error "Migration plan not found: $MIGRATION_PLAN"
    exit 1
fi

# Check if migrate script exists
if [[ ! -x "$MIGRATE_SCRIPT" ]]; then
    print_error "Migration script not found or not executable: $MIGRATE_SCRIPT"
    exit 1
fi

print_header

if [[ "$DRY_RUN" == true ]]; then
    print_warning "DRY RUN MODE - No changes will be made"
    echo ""
fi

# Get classes to migrate
if [[ "$PHASE" == "all" ]]; then
    print_warning "Migrating ALL phases - this is risky!"
    echo ""
    CLASSES_JSON=$(jq -c '.migration_plan.phases[].classes[]' "$MIGRATION_PLAN")
else
    # Validate phase number
    PHASE_COUNT=$(jq '.migration_plan.phases | length' "$MIGRATION_PLAN")
    if [[ $PHASE -lt 1 ]] || [[ $PHASE -gt $PHASE_COUNT ]]; then
        print_error "Invalid phase: $PHASE (must be 1-$PHASE_COUNT or 'all')"
        exit 1
    fi
    
    PHASE_NAME=$(jq -r ".migration_plan.phases[$((PHASE-1))].name" "$MIGRATION_PLAN")
    print_info "Migrating Phase $PHASE: $PHASE_NAME"
    echo ""
    
    CLASSES_JSON=$(jq -c ".migration_plan.phases[$((PHASE-1))].classes[]" "$MIGRATION_PLAN")
fi

# Count classes
CLASS_COUNT=$(echo "$CLASSES_JSON" | wc -l)
print_info "Found $CLASS_COUNT classes to migrate"
echo ""

# Process each class
CURRENT=0
SUCCESS=0
FAILED=0

while IFS= read -r class_info; do
    CURRENT=$((CURRENT + 1))
    
    CLASS_FILE=$(echo "$class_info" | jq -r '.file')
    TARGET_DIR=$(echo "$class_info" | jq -r '.target')
    RISK=$(echo "$class_info" | jq -r '.risk')
    NOTES=$(echo "$class_info" | jq -r '.notes')
    
    echo -e "${BOLD}[$CURRENT/$CLASS_COUNT]${NC} Migrating: $CLASS_FILE → $TARGET_DIR/"
    echo -e "  Risk: ${YELLOW}$RISK${NC}"
    echo -e "  Notes: $NOTES"
    echo ""
    
    # Build command
    CMD="$MIGRATE_SCRIPT"
    if [[ "$DRY_RUN" == true ]]; then
        CMD="$CMD --dry-run"
    fi
    CMD="$CMD $CLASS_FILE $TARGET_DIR"
    
    # Execute migration
    if $CMD; then
        SUCCESS=$((SUCCESS + 1))
        print_success "Migration completed for $CLASS_FILE"
    else
        FAILED=$((FAILED + 1))
        print_error "Migration failed for $CLASS_FILE"
        
        if [[ "$DRY_RUN" == false ]]; then
            print_warning "Stopping batch migration due to failure"
            break
        fi
    fi
    
    echo ""
    echo "---"
    echo ""
    
    # Small delay to allow user to see output
    sleep 1
done <<< "$CLASSES_JSON"

# Summary
echo ""
echo -e "${BOLD}${BLUE}Migration Summary${NC}"
echo "================="
echo "Total classes: $CLASS_COUNT"
echo -e "${GREEN}Successful: $SUCCESS${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [[ $FAILED -eq 0 ]]; then
    print_success "All migrations completed successfully!"
    
    if [[ "$DRY_RUN" == false ]]; then
        echo ""
        print_warning "Next steps:"
        echo "  1. Test the application thoroughly"
        echo "  2. Check logs: tail -f cache/logs/system.log"
        echo "  3. Run automated tests if available"
        echo "  4. Commit changes: git add -A && git commit -m 'refactor: migrate phase $PHASE classes'"
    fi
else
    print_error "Some migrations failed"
    if [[ "$DRY_RUN" == false ]]; then
        print_warning "Check the error messages above and restore from backups if needed"
    fi
    exit 1
fi

