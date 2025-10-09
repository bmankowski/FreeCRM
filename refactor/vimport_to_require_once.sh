#!/bin/bash

# Script to replace vimport(xxx) calls with require_once xxx;
# Usage: ./vimport_to_require_once.sh [file_or_directory]
# If no argument provided, processes all PHP files in the current directory and subdirectories

# Function to process a single file
process_file() {
    local file="$1"
    echo "Processing: $file"
    
    # Create a temporary file for processing
    local temp_file=$(mktemp)
    
    # Process each line
    while IFS= read -r line; do
        # Check if line contains vimport call
        if echo "$line" | grep -q "^[[:space:]]*vimport("; then
            # Extract the path from vimport('path') or vimport("path")
            path=$(echo "$line" | sed -n "s/^[[:space:]]*vimport(['\"]\([^'\"]*\)['\"]);*.*$/\1/p")
            if [ -n "$path" ]; then
                # Convert path format
                if [[ "$path" == ~* ]]; then
                    # Handle ~ prefix (vtiger files) - remove ~ and keep as is
                    converted_path=$(echo "$path" | sed 's/^~//')
                else
                    # Convert dots to directory separators and add .php extension
                    converted_path=$(echo "$path" | sed 's/\./\//g').php
                fi
                
                # Get the indentation from the original line
                indent=$(echo "$line" | sed 's/^\([[:space:]]*\).*/\1/')
                
                # Create the new line with proper indentation
                new_line="${indent}require_once '$converted_path';"
                
                echo "$new_line" >> "$temp_file"
            else
                echo "$line" >> "$temp_file"
            fi
        else
            echo "$line" >> "$temp_file"
        fi
    done < "$file"
    
    # Replace original file with processed content
    mv "$temp_file" "$file"
    
    echo "  -> Modified: $file"
}

# Function to find and process all PHP files
process_directory() {
    local dir="$1"
    echo "Scanning directory: $dir"
    
    # Find all PHP files and process them
    find "$dir" -type f -name "*.php" -print0 | while IFS= read -r -d '' file; do
        # Check if file contains vimport calls
        if grep -q "vimport(" "$file"; then
            echo "Found vimport calls in: $file"
            process_file "$file"
        fi
    done
}

# Main execution
if [ $# -eq 0 ]; then
    # No arguments provided, process current directory
    echo "No arguments provided. Processing all PHP files in current directory and subdirectories..."
    process_directory "."
else
    # Arguments provided
    for arg in "$@"; do
        if [ -f "$arg" ]; then
            # It's a file
            if [[ "$arg" == *.php ]]; then
                if grep -q "vimport(" "$arg"; then
                    process_file "$arg"
                else
                    echo "No vimport calls found in: $arg"
                fi
            else
                echo "Warning: $arg is not a PHP file, skipping..."
            fi
        elif [ -d "$arg" ]; then
            # It's a directory
            process_directory "$arg"
        else
            echo "Error: $arg is neither a file nor a directory"
        fi
    done
fi

echo "Script completed!"
echo ""
echo "IMPORTANT NOTES:"
echo "1. Changes have been made to the files"
echo "2. Please review the changes before committing"
echo "3. Test your application thoroughly after the changes"
echo "4. Use 'git diff' to review all changes made"
echo "5. Use 'git checkout -- <file>' to revert specific files if needed"