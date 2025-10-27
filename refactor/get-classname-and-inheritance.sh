#!/bin/bash

# Script to extract full class names with namespace and inheritance from PHP files
# Output format: \Full\Class\Name \Extended\Class\Name

find /home/bmankowski/projects/FreeCRM/src -name "*.php" -type f | while read -r file; do
    # Read only first 100 lines of the file
    content=$(head -100 "$file")
    
    # Extract namespace from first 100 lines
    namespace=$(echo "$content" | grep -m 1 "^namespace " | sed 's/namespace //; s/;//; s/^[ \t]*//; s/[ \t]*$//')
    
    # Extract class declaration with extends from first 100 lines (skip traits for now, focus on classes and interfaces)
    class_info=$(echo "$content" | grep -E "^(class|abstract class|final class|interface) " | grep -v "//" | head -n 1)
    
    # Skip files without class, abstract class, or interface
    if [ -z "$class_info" ]; then
        continue
    fi
    
    # Verify we actually found a class/interface declaration
    if ! echo "$class_info" | grep -qE "^(abstract |final )?(class|interface) "; then
        continue
    fi
    
    # Extract class name
    class_name=$(echo "$class_info" | sed -E 's/^(abstract |final )?(class|trait|interface) +([a-zA-Z0-9_]+).*/\3/')
    
    # Check if class extends something
    if echo "$class_info" | grep -q "extends"; then
        # Extract what it extends
        extends=$(echo "$class_info" | sed -E 's/.*extends +([\\a-zA-Z0-9_]+).*/\1/')
        
        # Build full class name with namespace
        if [ -n "$namespace" ]; then
            full_class="\\$namespace\\$class_name"
        else
            full_class="\\$class_name"
        fi
        
        # If extends doesn't start with backslash, add namespace prefix
        if [[ "$extends" != \\* ]]; then
            # Check if extends uses a fully qualified name or relative
            if [ -n "$namespace" ]; then
                extends="\\$extends"
            else
                extends="\\$extends"
            fi
        fi
        
        echo "$full_class $extends"
    else
        # No extends clause - just output class name
        if [ -n "$namespace" ]; then
            full_class="\\$namespace\\$class_name"
        else
            full_class="\\$class_name"
        fi
        
        echo "$full_class"
    fi
done 

