#!/bin/bash

# Simple URL tester - checks URLs for errors
# Usage: ./tests/testurls.sh

URLS_FILE="tests/urls.txt"
COOKIE_FILE="/tmp/test_cookies.txt"
BASE_URL="${FREECRM_BASE_URL:-https://dev.itconnect.pl}"

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
  -X POST "$BASE_URL/index.php?module=Users&action=Login" -o /dev/null)

if [ $? -ne 0 ] || [ "$login_response" != "302" ]; then
    echo -e "${RED}Failed to login (HTTP $login_response)${NC}"
    exit 1
fi

echo -e "${GREEN}Logged in successfully${NC}\n"

# Count total URLs first (excluding empty lines and comments)
total_urls=$(grep -vE '^[[:space:]]*$|^[[:space:]]*#' "$URLS_FILE" | wc -l | tr -d ' ')

if [ "$total_urls" -eq 0 ]; then
    echo -e "${RED}No URLs found in $URLS_FILE${NC}"
    exit 1
fi

echo -e "${YELLOW}Total URLs to test: $total_urls${NC}\n"

# Read URLs and test each one
url_count=0
while IFS= read -r url || [ -n "$url" ]; do
    # Skip empty lines and comments
    if [[ -z "$url" ]] || [[ "$url" =~ ^[[:space:]]*# ]]; then
        continue
    fi
    
    url_count=$((url_count + 1))
    target_url=$(echo "$url" | sed -E "s#^https?://[^/]+#$BASE_URL#")
    echo -e "${YELLOW}Testing [${url_count}/${total_urls}]:${NC} $target_url"
    
    # Fetch URL and check for errors (with timeout to prevent hanging)
    response=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L --max-time 30 "$target_url")
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ Failed to fetch URL (curl error)${NC}\n"
        exit 1
    fi
    
    # Check for PHP errors - extract only clean error messages
    # PHP errors typically appear as: "Warning: message in /path/to/file.php on line X"
    # Match errors that appear as plain text (not in HTML tags or JS strings)
    php_errors=$(echo "$response" | grep -iE "(Warning|Notice|Fatal error|Parse error|Deprecated):[^<]*in [^<]*on line [0-9]+" | \
        grep -vE "^[[:space:]]*<" | head -10)
    
    if [ -n "$php_errors" ]; then
        echo -e "${RED}✗ ERRORS FOUND:${NC}"
        echo -e "\n${YELLOW}=== PHP ERRORS ===${NC}\n"
        # Show only clean PHP error messages, removing HTML tags and entities
        echo "$php_errors" | while IFS= read -r error_line; do
            # Remove HTML tags and clean up entities
            cleaned=$(echo "$error_line" | sed 's/<[^>]*>//g' | sed 's/&nbsp;/ /g' | sed 's/&lt;/</g' | sed 's/&gt;/>/g' | sed 's/&amp;/\&/g' | sed 's/^[[:space:]]*//' | sed 's/[[:space:]]*$//')
            if [ -n "$cleaned" ]; then
                echo "$cleaned"
            fi
        done
        echo -e "\n${RED}Testing stopped due to errors${NC}"
        exit 1
    fi
    
    # Also check for other critical errors (without file paths)
    other_errors=$(echo "$response" | grep -iE "(Cannot redeclare|Call to undefined|Uncaught Exception)" | \
        sed 's/<[^>]*>//g' | sed 's/&nbsp;/ /g' | sed 's/&lt;/</g' | sed 's/&gt;/>/g' | head -5)
    
    if [ -n "$other_errors" ]; then
        echo -e "${RED}✗ ERRORS FOUND:${NC}"
        echo -e "\n${YELLOW}=== ERROR OUTPUT ===${NC}\n"
        echo "$other_errors"
        echo -e "\n${RED}Testing stopped due to errors${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ OK${NC}\n"
    
done < "$URLS_FILE"

# Clean up
rm -f "$COOKIE_FILE"

echo -e "${GREEN}All URLs tested successfully!${NC}"
exit 0

