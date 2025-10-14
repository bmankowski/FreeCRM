#!/bin/bash

# Script: vtranslate_to_modifier_t.sh
# Description: Automatically replace vtranslate() calls with Smarty modifier 't' in .tpl files
# Usage: ./vtranslate_to_modifier_t.sh [file_or_directory]
# Author: FreeCRM Refactoring Tool

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counter for statistics
TOTAL_FILES=0
MODIFIED_FILES=0
TOTAL_REPLACEMENTS=0

# Function to print colored output
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS] [FILE_OR_DIRECTORY]"
    echo ""
    echo "Options:"
    echo "  -h, --help     Show this help message"
    echo "  -d, --dry-run  Show what would be changed without making changes"
    echo "  -v, --verbose  Show detailed output"
    echo "  --tpl-only     Only process .tpl files (default)"
    echo "  --php-only     Only process .php files"
    echo "  --all-files    Process all file types"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Process current directory"
    echo "  $0 layouts/basic/modules/Vtiger/      # Process specific directory"
    echo "  $0 layouts/basic/modules/Vtiger/DetailViewBlockView.tpl  # Process specific file"
    echo "  $0 --dry-run layouts/                 # Preview changes without applying"
    echo ""
}

# Function to escape special regex characters
escape_regex() {
    echo "$1" | sed 's/[[\.*^$()+?{|]/\\&/g'
}

# Function to process a single file
process_file() {
    local file_path="$1"
    local dry_run="$2"
    local verbose="$3"
    
    if [ ! -f "$file_path" ]; then
        return 0
    fi
    
    # Check if file contains vtranslate
    if ! grep -q "vtranslate(" "$file_path"; then
        return 0
    fi
    
    ((TOTAL_FILES++))
    local file_replacements=0
    local temp_file=$(mktemp)
    
    # Copy original content
    cp "$file_path" "$temp_file"
    
    if [ "$verbose" = "true" ]; then
        print_status "$BLUE" "Processing: $file_path"
    fi
    
    # Pattern 1: Simple vtranslate('key') -> 'key'|t
    # Handle both single and double quotes
    sed -i 's/{vtranslate('\''\([^'\'']*\)'\'')}/{'\1'|t}/g' "$temp_file"
    sed -i 's/{vtranslate("\([^"]*\)")}/{"\1"|t}/g' "$temp_file"
    
    # Pattern 2: vtranslate('key', 'module') -> 'key'|t:'module'
    sed -i 's/{vtranslate('\''\([^'\'']*\)'\'','\''\([^'\'']*\)'\'')}/{'\1'|t:'\2'}/g' "$temp_file"
    sed -i 's/{vtranslate('\''\([^'\'']*\)'\'',"\([^"]*\)")}/{'\1'|t:"\2"}/g' "$temp_file"
    sed -i 's/{vtranslate("\([^"]*\)",'\''\([^'\'']*\)'\'')}/{"\1"|t:'\2'}/g' "$temp_file"
    sed -i 's/{vtranslate("\([^"]*\)","\([^"]*\)")}/{"\1"|t:"\2"}/g' "$temp_file"
    
    # Pattern 3: vtranslate(variable, 'module') -> variable|t:'module'
    sed -i 's/{vtranslate({\([^}]*\)},'\''\([^'\'']*\)'\'')}/{{\1}|t:'\2'}/g' "$temp_file"
    sed -i 's/{vtranslate({\([^}]*\)},"\([^"]*\)")}/{{\1}|t:"\2"}/g' "$temp_file"
    
    # Pattern 4: vtranslate(variable) -> variable|t
    sed -i 's/{vtranslate({\([^}]*\)})}/{{\1}|t}/g' "$temp_file"
    
    # Pattern 5: vtranslate with complex expressions (like cat operations)
    # Handle: vtranslate($MODULE_NAME|cat:'|'|cat:$FIELD_MODEL->get('label'), 'HelpInfo')
    sed -i 's/{vtranslate(\([^,]*\),'\''\([^'\'']*\)'\'')}/{{\1}|t:'\2'}/g' "$temp_file"
    sed -i 's/{vtranslate(\([^,]*\),"\([^"]*\)")}/{{\1}|t:"\2"}/g' "$temp_file"
    
    # Pattern 6: vtranslate with sprintf parameters
    # Handle: vtranslate('key', 'module', param1, param2)
    sed -i 's/{vtranslate('\''\([^'\'']*\)'\'','\''\([^'\'']*\)'\'',\([^)]*\))}/{'\1'|t:'\2':\3}/g' "$temp_file"
    sed -i 's/{vtranslate('\''\([^'\'']*\)'\'',"\([^"]*\)",\([^)]*\))}/{'\1'|t:"\2":\3}/g' "$temp_file"
    
    # Count replacements made
    if [ -f "$temp_file" ]; then
        # Compare original and modified files
        if ! cmp -s "$file_path" "$temp_file"; then
            local diff_count=$(diff "$file_path" "$temp_file" | grep -c "^<\|^>" || true)
            file_replacements=$((diff_count / 2))  # Each replacement shows as 2 lines in diff
            ((TOTAL_REPLACEMENTS += file_replacements))
            
            if [ "$dry_run" = "true" ]; then
                print_status "$YELLOW" "  Would modify $file_path ($file_replacements changes)"
                if [ "$verbose" = "true" ]; then
                    echo "  Changes:"
                    diff "$file_path" "$temp_file" | grep -E "^<|^>" | head -10
                    echo ""
                fi
            else
                # Apply changes
                mv "$temp_file" "$file_path"
                ((MODIFIED_FILES++))
                print_status "$GREEN" "  ✓ Modified $file_path ($file_replacements changes)"
            fi
        else
            rm -f "$temp_file"
        fi
    fi
}

# Function to process directory recursively
process_directory() {
    local dir_path="$1"
    local dry_run="$2"
    local verbose="$3"
    local file_pattern="$4"
    
    if [ ! -d "$dir_path" ]; then
        print_status "$RED" "Error: Directory '$dir_path' does not exist"
        exit 1
    fi
    
    print_status "$BLUE" "Scanning directory: $dir_path"
    
    # Find files based on pattern
    while IFS= read -r -d '' file; do
        process_file "$file" "$dry_run" "$verbose"
    done < <(find "$dir_path" -name "$file_pattern" -type f -print0)
}

# Main function
main() {
    local target="."
    local dry_run="false"
    local verbose="false"
    local file_pattern="*.tpl"
    local file_type="tpl"
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_usage
                exit 0
                ;;
            -d|--dry-run)
                dry_run="true"
                shift
                ;;
            -v|--verbose)
                verbose="true"
                shift
                ;;
            --tpl-only)
                file_pattern="*.tpl"
                file_type="tpl"
                shift
                ;;
            --php-only)
                file_pattern="*.php"
                file_type="php"
                shift
                ;;
            --all-files)
                file_pattern="*.{tpl,php}"
                file_type="all"
                shift
                ;;
            -*)
                print_status "$RED" "Unknown option: $1"
                show_usage
                exit 1
                ;;
            *)
                target="$1"
                shift
                ;;
        esac
    done
    
    # Show configuration
    print_status "$BLUE" "=== vtranslate to modifier 't' refactoring tool ==="
    if [ "$dry_run" = "true" ]; then
        print_status "$YELLOW" "Mode: DRY RUN (no changes will be made)"
    else
        print_status "$GREEN" "Mode: LIVE (changes will be applied)"
    fi
    print_status "$BLUE" "Target: $target"
    print_status "$BLUE" "File pattern: $file_pattern"
    echo ""
    
    # Process target
    if [ -f "$target" ]; then
        # Single file
        process_file "$target" "$dry_run" "$verbose"
    elif [ -d "$target" ]; then
        # Directory
        process_directory "$target" "$dry_run" "$verbose" "$file_pattern"
    else
        print_status "$RED" "Error: '$target' is neither a file nor a directory"
        exit 1
    fi
    
    # Show summary
    echo ""
    print_status "$BLUE" "=== Summary ==="
    print_status "$BLUE" "Files scanned: $TOTAL_FILES"
    
    if [ "$dry_run" = "true" ]; then
        print_status "$YELLOW" "Files that would be modified: $MODIFIED_FILES"
        print_status "$YELLOW" "Total replacements that would be made: $TOTAL_REPLACEMENTS"
    else
        print_status "$GREEN" "Files modified: $MODIFIED_FILES"
        print_status "$GREEN" "Total replacements made: $TOTAL_REPLACEMENTS"
    fi
    
    if [ "$TOTAL_FILES" -eq 0 ]; then
        print_status "$YELLOW" "No files with vtranslate() found"
    fi
    
    echo ""
    if [ "$dry_run" = "true" ]; then
        print_status "$YELLOW" "This was a dry run. Use without --dry-run to apply changes."
    else
        print_status "$GREEN" "Refactoring completed successfully!"
        print_status "$BLUE" "Remember to test your application after these changes."
    fi
}

# Run main function with all arguments
main "$@"
