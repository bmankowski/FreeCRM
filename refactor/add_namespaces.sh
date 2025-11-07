#!/bin/bash

# Script to add namespaces to PHP files based on directory structure
# Usage: ./add_namespaces.sh [directory]
# If no directory specified, processes the entire src/ directory

set -e

# Color codes for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to get namespace from file path
get_namespace() {
    local filepath="$1"
    local namespace=""
    
    # Remove leading ./
    filepath="${filepath#./}"
    
    # Determine base namespace based on directory
    if [[ "$filepath" =~ ^src/ ]]; then
        # Remove src/ prefix
        local subpath="${filepath#src/}"
        # Get directory path without filename
        local dirpath=$(dirname "$subpath")
        
        if [ "$dirpath" = "." ]; then
            namespace="FreeCRM"
        else
            # Convert directory separators to namespace separators
            # Replace / with \\ (double backslash for proper escaping)
            namespace="FreeCRM\\\\${dirpath//\//\\\\}"
        fi
        
    elif [[ "$filepath" =~ ^vtlib/Vtiger/ ]]; then
        local subpath="${filepath#vtlib/Vtiger/}"
        local dirpath=$(dirname "$subpath")
        
        if [ "$dirpath" = "." ]; then
            namespace="vtlib"
        else
            namespace="vtlib\\\\${dirpath//\//\\\\}"
        fi
        
    elif [[ "$filepath" =~ ^src/Api/ ]]; then
        local subpath="${filepath#src/Api/}"
        local dirpath=$(dirname "$subpath")
        
        if [ "$dirpath" = "." ]; then
            namespace="App\\Api"
        else
            namespace="App\\Api\\${dirpath//\//\\}"
        fi
    fi
    
    echo "$namespace"
}

# Function to check if file already has a namespace
has_namespace() {
    local file="$1"
    grep -q "^namespace " "$file" && return 0
    grep -q "^<?php.*namespace " "$file" && return 0
    return 1
}

# Function to add namespace to file
add_namespace_to_file() {
    local file="$1"
    local namespace="$2"
    
    # Check if file already has namespace
    if has_namespace "$file"; then
        echo -e "${YELLOW}SKIP${NC} $file (already has namespace)"
        return
    fi
    
    # Check if namespace is empty
    if [ -z "$namespace" ]; then
        echo -e "${YELLOW}SKIP${NC} $file (could not determine namespace)"
        return
    fi
    
    # Create backup
    cp "$file" "$file.bak"
    
    # Add namespace after <?php tag and any comments/docblocks
    # We'll use perl for better handling of backslashes
    perl -i -pe '
        BEGIN { $added = 0; }
        
        # If we see a class/interface/trait declaration and havent added namespace yet
        if (!$added && /^\s*(abstract\s+)?(class|interface|trait)\s+/) {
            # Add namespace before the class declaration
            print "\nnamespace '"$namespace"';\n\n";
            $added = 1;
        }
    ' "$file"
    
    echo -e "${GREEN}ADDED${NC} $file -> namespace $namespace"
}

# Main script
TARGET_DIR="${1:-src}"

if [ ! -d "$TARGET_DIR" ]; then
    echo -e "${RED}Error: Directory $TARGET_DIR does not exist${NC}"
    exit 1
fi

echo "Processing PHP files in $TARGET_DIR..."
echo "=================================="

# Find all PHP files and process them
find "$TARGET_DIR" -type f -name "*.php" | while read -r file; do
    namespace=$(get_namespace "$file")
    add_namespace_to_file "$file" "$namespace"
done

echo "=================================="
echo -e "${GREEN}Done!${NC}"
echo ""
echo "Backup files created with .bak extension"
echo "To remove backups: find $TARGET_DIR -name '*.bak' -delete"
