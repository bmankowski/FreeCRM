#!/bin/bash

# Script to check if all opened divs are closed within the same .tpl file
# Author: Template Refactor Team
# Usage: ./refactor/check-div-balance.sh [file|directory]

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default to layouts directory if no argument provided
SEARCH_PATH="${1:-layouts/basic}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Checking DIV Balance in TPL Files${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Counters
total_files=0
balanced_files=0
unbalanced_files=0

# Arrays to store results
declare -a unbalanced_list
declare -a files_to_check

# Determine if argument is a file or directory
if [ -f "$SEARCH_PATH" ]; then
    # Single file
    echo "Checking file: $SEARCH_PATH"
    echo ""
    files_to_check=("$SEARCH_PATH")
elif [ -d "$SEARCH_PATH" ]; then
    # Directory - find all .tpl files
    echo "Searching in directory: $SEARCH_PATH"
    echo ""
    while IFS= read -r file; do
        files_to_check+=("$file")
    done < <(find "$SEARCH_PATH" -name "*.tpl" -type f | sort)
else
    echo -e "${RED}Error: '$SEARCH_PATH' is not a valid file or directory${NC}"
    exit 1
fi

# Check all files
for file in "${files_to_check[@]}"; do
    ((total_files++))
    
    # Count opening and closing divs
    opened=$(grep -o "<div" "$file" | wc -l)
    closed=$(grep -o "</div>" "$file" | wc -l)
    balance=$((opened - closed))
    
    if [ $balance -eq 0 ]; then
        ((balanced_files++))
        echo -e "${GREEN}✓${NC} BALANCED - $file (${opened} divs)"
    else
        ((unbalanced_files++))
        unbalanced_list+=("$file:$opened:$closed:$balance")
        
        if [ $balance -gt 0 ]; then
            echo -e "${RED}✗${NC} UNBALANCED - $file (${opened} opened, ${closed} closed, ${RED}${balance} NOT CLOSED${NC})"
        else
            echo -e "${YELLOW}⚠${NC} UNBALANCED - $file (${opened} opened, ${closed} closed, ${YELLOW}${balance#-} EXTRA CLOSES${NC})"
        fi
    fi
done

# Summary
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}              SUMMARY${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "Total files checked: ${total_files}"
echo -e "${GREEN}Balanced files: ${balanced_files}${NC}"

if [ $unbalanced_files -gt 0 ]; then
    echo -e "${RED}Unbalanced files: ${unbalanced_files}${NC}"
    echo ""
    echo -e "${YELLOW}Files requiring attention:${NC}"
    for item in "${unbalanced_list[@]}"; do
        IFS=':' read -r file opened closed balance <<< "$item"
        echo "  - $file (balance: $balance)"
    done
    echo ""
    echo -e "${RED}FAIL: Some files have unbalanced divs${NC}"
    exit 1
else
    echo -e "${GREEN}Unbalanced files: 0${NC}"
    echo ""
    echo -e "${GREEN}SUCCESS: All templates have balanced divs!${NC}"
    exit 0
fi

