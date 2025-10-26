#!/bin/bash

# Filter to show only functions that have $request parameter but don't use it
# Usage: ./model_functions_that_use_requests.sh | ./model_functions_that_have_requests_but_not_use_it.sh

header=""
func=""
has_usage=0

while IFS= read -r line; do
    if [[ "$line" == "=== "* ]]; then
        # Print previous section if no usage found
        if [[ -n "$header" && $has_usage -eq 0 ]]; then
            echo "$header"
            echo "$func"
            echo ""
        fi
        # Start new section
        header="$line"
        read -r func
        has_usage=0
    elif [[ "$line" =~ ^[0-9]+:.*\$request ]]; then
        # Found usage
        has_usage=1
    elif [[ -z "$line" ]]; then
        # Empty line - end of section
        if [[ -n "$header" && $has_usage -eq 0 ]]; then
            echo "$header"
            echo "$func"
            echo ""
        fi
        header=""
        func=""
        has_usage=0
    fi
done

# Handle last section
if [[ -n "$header" && $has_usage -eq 0 ]]; then
    echo "$header"
    echo "$func"
    echo ""
fi
