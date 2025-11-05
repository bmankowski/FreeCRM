#!/bin/bash

# Simple URL tester - checks URLs for errors
# Usage: ./tests/testurls.sh

URLS_FILE="tests/urls.txt"
COOKIE_FILE="/tmp/test_cookies.txt"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting URL tests...${NC}\n"

# Login first to establish session
echo -e "${YELLOW}Logging in...${NC}"
curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=Users&action=Login" > /dev/null

if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to login${NC}"
    exit 1
fi

echo -e "${GREEN}Logged in successfully${NC}\n"

# Read URLs and test each one
line_num=0
while IFS= read -r url || [ -n "$url" ]; do
    line_num=$((line_num + 1))
    
    # Skip empty lines and comments
    if [[ -z "$url" ]] || [[ "$url" =~ ^[[:space:]]*# ]]; then
        continue
    fi
    
    echo -e "${YELLOW}Testing:${NC} $url"
    
    # Fetch URL and check for errors
    response=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L "$url")
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ Failed to fetch URL (curl error)${NC}\n"
        exit 1
    fi
    
    # Check for common PHP error patterns (both HTML and plain text formats)
    
    # Check for HTML-formatted errors (most common in web responses)
    if echo "$response" | grep -qE "^(Fatal error|Parse error|Warning|Notice):|Cannot redeclare|Call to undefined"; then
        echo -e "${RED}✗ ERRORS FOUND:${NC}"
        echo "$response" | grep -E "<b>(Fatal error|Parse error|Warning|Notice)</b>|Cannot redeclare|Call to undefined" | head -5
        echo -e "\n${RED}Testing stopped due to errors${NC}"
        exit 1
    fi
    
    # Check for plain text errors (appear when PHP fails before rendering)
    if echo "$response" | grep -qE "^(Fatal error|Parse error|Warning|Notice):|Uncaught Exception|Stack trace:"; then
        echo -e "${RED}✗ ERRORS FOUND:${NC}"
        echo "$response" | grep -E "^(Fatal error|Parse error|Warning|Notice):|Uncaught Exception|Stack trace:" | head -5
        echo -e "\n${RED}Testing stopped due to errors${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ OK${NC}\n"
    
done < "$URLS_FILE"

# Clean up
rm -f "$COOKIE_FILE"

echo -e "${GREEN}All URLs tested successfully!${NC}"
exit 0

