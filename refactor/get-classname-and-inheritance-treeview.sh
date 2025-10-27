#!/bin/bash

# Script to convert classes-in-codebase.txt to a tree view format
# Input: documentation/classes-in-codebase.txt
# Output: documentation/classes-in-codebase-treeview.txt

INPUT_FILE="/home/bmankowski/projects/FreeCRM/documentation/classes-in-codebase.txt"
OUTPUT_FILE="/home/bmankowski/projects/FreeCRM/documentation/classes-in-codebase-treeview.txt"

# Check if input file exists
if [ ! -f "$INPUT_FILE" ]; then
    echo "Error: Input file $INPUT_FILE not found"
    exit 1
fi

# Create temporary file to store processed data
TEMP_FILE=$(mktemp)

# Process the input file
# Extract just the class name (first part before space) and normalize
while IFS= read -r line; do
    # Skip empty lines
    [ -z "$line" ] && continue
    
    # Extract class name (everything before first space or whole line if no space)
    class_name=$(echo "$line" | awk '{print $1}')
    
    # Extract parent class if exists (everything after first space)
    parent_class=$(echo "$line" | cut -d' ' -f2- -s)
    
    # Remove leading backslash and convert backslashes to forward slashes for processing
    class_path=$(echo "$class_name" | sed 's/^\\//' | tr '\\' '/')
    
    # Store in temp file with tab-separated format: path|parent
    if [ -n "$parent_class" ]; then
        echo "$class_path|$parent_class" >> "$TEMP_FILE"
    else
        echo "$class_path|" >> "$TEMP_FILE"
    fi
done < "$INPUT_FILE"

# Sort the temp file
sort -o "$TEMP_FILE" "$TEMP_FILE"

# Generate tree view
{
    echo "FreeCRM Class Hierarchy Tree"
    echo "============================="
    echo ""
    echo "Legend:"
    echo "  [C] - Class extends another class"
    echo "  [I] - Interface or standalone class"
    echo ""
    
    # Track previous path components to know when to close branches
    declare -a prev_parts=()
    declare -a is_last=()
    
    while IFS='|' read -r class_path parent_class; do
        # Skip empty lines
        [ -z "$class_path" ] && continue
        
        # Split path into components
        IFS='/' read -ra parts <<< "$class_path"
        parts_count=${#parts[@]}
        
        # Calculate depth (number of namespace levels)
        depth=$((parts_count - 1))
        
        # Determine the tree characters to use
        prefix=""
        
        for ((i=0; i<depth; i++)); do
            if [ $i -lt ${#prev_parts[@]} ] && [ "${parts[$i]}" != "${prev_parts[$i]}" ]; then
                # Path diverged, we're in a new branch
                prev_parts=("${parts[@]:0:$i}")
                break
            fi
            
            if [ $i -eq $((depth-1)) ]; then
                # Last level before class name
                prefix+="├── "
            elif [ $i -lt ${#prev_parts[@]} ]; then
                # Continuing from previous path
                prefix+="│   "
            else
                prefix+="    "
            fi
        done
        
        # Get the class name (last component)
        class_name="${parts[$((parts_count-1))]}"
        
        # Add marker for inheritance
        if [ -n "$parent_class" ]; then
            marker="[C]"
            inheritance=" → extends $parent_class"
        else
            marker="[I]"
            inheritance=""
        fi
        
        # Print the line
        echo "${prefix}${marker} ${class_name}${inheritance}"
        
        # Update previous parts for next iteration
        prev_parts=("${parts[@]}")
        
    done < "$TEMP_FILE"
    
    echo ""
    echo "============================="
    echo "Total classes: $(wc -l < "$TEMP_FILE")"
    
} > "$OUTPUT_FILE"

# Clean up
rm -f "$TEMP_FILE"

echo "Tree view generated successfully: $OUTPUT_FILE"
echo "Total classes processed: $(wc -l < "$INPUT_FILE")"

