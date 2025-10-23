#!/bin/bash

set -e  # Exit on error

# Check if input file exists
if [[ ! -f "found_aliases.txt" ]]; then
    echo "Error: found_aliases.txt not found"
    exit 1
fi

# Create a temporary sed script file for all replacements
sed_script=$(mktemp)

echo "Building replacement rules..."

# Read found_aliases.txt and build sed script
while IFS=$'\t' read -r legacy new; do
    # Skip empty lines
    [[ -z "$legacy" ]] && continue
    
    echo "Adding rule: $legacy -> $new"
    
    # Escape special regex characters in legacy (for pattern matching)
    # Escape: . * [ ] ^ $ \ /
    legacy_escaped=$(printf '%s\n' "$legacy" | sed 's/[.*[\^$\/]/\\&/g')
    
    # Escape special replacement characters in new (for replacement string)
    # In sed replacement string: each \ must become \\ to produce single \
    # Also escape & (means "whole match") and / (delimiter)
    new_escaped=$(printf '%s\n' "$new" | sed 's/\\/\\\\/g; s/&/\\&/g; s/\//\\\//g')
    
    # Add two patterns to the sed script:
    # 1. Match \ClassName (with leading backslash) - need \\ in sed pattern to match single \
    echo "s/\\\\${legacy_escaped}\\b/${new_escaped}/g" >> "$sed_script"
    # 2. Match ClassName (without backslash)
    echo "s/\\b${legacy_escaped}\\b/${new_escaped}/g" >> "$sed_script"
    
done < found_aliases.txt

echo ""
echo "Applying replacements to PHP files..."

# Single pass through all PHP files
file_count=0
while IFS= read -r -d '' file; do
    sed -i -f "$sed_script" "$file" || true
    file_count=$((file_count + 1))
    if [ $((file_count % 100)) -eq 0 ]; then
        echo "Processed $file_count files..."
    fi
done < <(find . -type f -name "*.php" -not -path "*/vendor/*" -print0)

echo "Total files processed: $file_count"

# Clean up
rm "$sed_script"

echo ""
echo "Done! Processed all PHP files."
echo "Tip: Use 'git diff' to review changes before committing."

