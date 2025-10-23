#!/bin/bash

# Read found_aliases.txt and replace legacy classnames with new ones
while IFS=$'\t' read -r legacy new; do
    # Skip empty lines
    [[ -z "$legacy" ]] && continue
    
    echo "Replacing: $legacy -> $new"
    
    # Find all PHP files and replace
    # First: replace \legacy with new (new already has \)
    # Second: replace legacy (without \) with new
    find . -type f -name "*.php" -not -path "*/vendor/*" -exec sed -i \
        -e "s/\\\\${legacy}\\b/${new}/g" \
        -e "s/\\b${legacy}\\b/${new}/g" \
        {} +
done < found_aliases.txt

echo "Done!"

