#!/bin/bash

# Check if class definitions exist for each class in aliases_classnames.txt

echo "Checking class definitions..."
echo "=============================="
echo ""

found=0
missing=0

while IFS= read -r classname; do
    # Skip empty lines
    [ -z "$classname" ] && continue
    
    # Search for "class ClassName" in the codebase
    if grep -r "^class $classname" --include="*.php" . > /dev/null 2>&1; then
        ((found++))
    else
        echo "$classname"
        ((missing++))
    fi
done < aliases_classnames.txt

echo ""
echo "=============================="
echo "Summary:"
echo "  Found: $found"
echo "  Missing: $missing"
echo "  Total: $((found + missing))"

