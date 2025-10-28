#!/bin/bash

# Script to check if all opened divs are closed within the same .tpl file
# Author: Template Refactor Team
# Usage: ./refactor/check-div-balance.sh [directory]

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default to layouts directory if no argument provided
SEARCH_DIR="${1:-layouts/basic}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Checking DIV Balance in TPL Files${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "Searching in: $SEARCH_DIR"
echo ""

# Counters
total_files=0
balanced_files=0
unbalanced_files=0

# Arrays to store results
declare -a unbalanced_list

# Find all .tpl files and check them
while IFS= read -r file; do
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
done < <(find "$SEARCH_DIR" -name "*.tpl" -type f | sort)

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

