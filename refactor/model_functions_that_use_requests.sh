#!/bin/bash

# Find model functions that use request parameter and show usage
# One-time analysis script
# ./refactor/model_functions_that_use_requests.sh | ./refactor/model_functions_that_have_requests_but_not_use_it.sh | ./refactor/check_if_request_param_is_used_in_calls.sh


echo "Use it: ./refactor/model_functions_that_use_requests.sh | ./refactor/model_functions_that_have_requests_but_not_use_it.sh | ./refactor/check_if_request_param_is_used_in_calls.sh"
echo "--------------------------------"
echo ""

find src/Modules -type f -path "*/Models/*.php" | while read -r file; do
    # Find functions with $request parameter
    grep -n "function.*\$request" "$file" | while IFS=: read -r linenum funcline; do
        echo "=== $file:$linenum ==="
        echo "$funcline"
        
        # Extract function name to find its end
        funcname=$(echo "$funcline" | sed -n 's/.*function \+\([a-zA-Z_][a-zA-Z0-9_]*\).*/\1/p')
        
        # Show lines using $request within next 100 lines (simple heuristic)
        sed -n "${linenum},$((linenum + 100))p" "$file" | grep -n "\$request->" | head -20
        
        echo ""
    done
done

