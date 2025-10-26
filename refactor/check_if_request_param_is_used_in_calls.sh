#!/bin/bash

# Check if functions with unused $request params are actually called with arguments
# More precise version that checks for actual parameters in calls

echo "Checking which model functions are called WITH request parameter..."
echo "===================================================================="
echo ""

while IFS= read -r line; do
    if [[ "$line" == "=== "* ]]; then
        # Extract file path
        filepath=$(echo "$line" | sed 's/=== \(.*\):[0-9]* ===$/\1/')
        
        # Extract class name from path (e.g., src/Modules/Settings/Currency/Models/Record.php -> Currency)
        # For Settings modules
        if [[ "$filepath" =~ src/Modules/Settings/([^/]+)/Models/([^/]+)\.php ]]; then
            settingsmodule="${BASH_REMATCH[1]}"
            classname="${BASH_REMATCH[2]}"
            search_pattern="${settingsmodule}.*${classname}"
        # For regular modules  
        elif [[ "$filepath" =~ src/Modules/([^/]+)/Models/([^/]+)\.php ]]; then
            module="${BASH_REMATCH[1]}"
            classname="${BASH_REMATCH[2]}"
            search_pattern="${module}.*${classname}"
        else
            search_pattern=""
        fi
        
        # Read function signature
        read -r funcline
        
        # Extract function name
        funcname=$(echo "$funcline" | sed -n 's/.*function \+\([a-zA-Z_][a-zA-Z0-9_]*\).*/\1/p')
        
        if [[ -n "$funcname" && -n "$search_pattern" ]]; then
            echo "File: $filepath"
            echo "Function: $funcname()"
            
            # Search for calls to ->functionName($request or with any parameter
            # Focus on the actual file and related views/actions
            matches=$(grep -rn "->$funcname\s*(\s*\$" src/ --include="*.php" 2>/dev/null | \
                     grep -v "function $funcname" | \
                     grep -v "^\s*//" | \
                     grep -v "^\s*\*")
            
            if [[ -n "$matches" ]]; then
                echo "  Found call(s) with parameters:"
                echo "$matches" | head -5 | sed 's/^/    /'
            else
                echo "  ✓ No calls with parameters found - safe to remove \$request param"
            fi
            echo ""
        fi
        
        # Skip the empty line
        read -r
    fi
done < refactor/unused_request_params.txt

echo ""
echo "===================================================================="
echo "Analysis complete"
