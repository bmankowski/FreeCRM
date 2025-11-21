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

# Check if curl is installed
if ! command -v curl &> /dev/null; then
    echo -e "${RED}Error: curl is not installed${NC}"
    exit 1
fi

# Check if URLs file exists
if [ ! -f "$URLS_FILE" ]; then
    echo -e "${RED}Error: URLs file not found: $URLS_FILE${NC}"
    exit 1
fi

echo -e "${YELLOW}Starting URL tests...${NC}\n"

# Login first to establish session
echo -e "${YELLOW}Logging in...${NC}"
# Don't follow redirects for login - just check for successful redirect response
login_response=$(curl -s -w "%{http_code}" -c "$COOKIE_FILE" -b "$COOKIE_FILE" --max-time 10 \
  -d "username=admin&password=admin" \
  -X POST "http://localhost/index.php?module=Users&action=Login" -o /dev/null)

if [ $? -ne 0 ] || [ "$login_response" != "302" ]; then
    echo -e "${RED}Failed to login (HTTP $login_response)${NC}"
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
    
    # Fetch URL and check for errors (with timeout to prevent hanging)
    response=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L --max-time 30 "$url")
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ Failed to fetch URL (curl error)${NC}\n"
        exit 1
    fi
    
    # Check for common PHP error patterns (both HTML and plain text formats)
    # Remove the ^ anchor to match errors anywhere in the response
    
    # Check for HTML-formatted errors (most common in web responses)
    if echo "$response" | grep -qiE "(<b>)?(Fatal error|Parse error|Warning|Notice)(</b>)?:|Cannot redeclare|Call to undefined|Uncaught Exception|Stack trace:"; then
        echo -e "${RED}✗ ERRORS FOUND:${NC}"
        echo -e "\n${YELLOW}=== ERROR OUTPUT ===${NC}\n"
        # Show error lines with context
        echo "$response" | grep -iE "(Fatal error|Parse error|Warning|Notice|Cannot redeclare|Call to undefined|Uncaught Exception|Stack trace:)" | head -20
        echo -e "\n${RED}Testing stopped due to errors${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ OK${NC}\n"
    
done < "$URLS_FILE"

# Clean up
rm -f "$COOKIE_FILE"

echo -e "${GREEN}All URLs tested successfully!${NC}"
exit 0

