#!/bin/bash

# Batch process all PHP files in a directory to add missing properties
# Usage: ./batch-add-missing-properties.sh [--dry-run] <directory>

DRY_RUN=""
DIRECTORY=""

# Parse arguments
for arg in "$@"; do
    if [ "$arg" = "--dry-run" ]; then
        DRY_RUN="--dry-run"
    else
        DIRECTORY="$arg"
    fi
done

if [ -z "$DIRECTORY" ]; then
    echo "Usage: ./batch-add-missing-properties.sh [--dry-run] <directory>"
    echo ""
    echo "Examples:"
    echo "  ./batch-add-missing-properties.sh --dry-run src/events/"
    echo "  ./batch-add-missing-properties.sh src/events/"
    exit 1
fi

if [ ! -d "$DIRECTORY" ]; then
    echo "Error: Directory not found: $DIRECTORY"
    exit 1
fi

echo "=================================================="
if [ -n "$DRY_RUN" ]; then
    echo "DRY RUN MODE - No files will be modified"
else
    echo "PROCESSING MODE - Files will be modified"
fi
echo "=================================================="
echo ""
echo "Processing directory: $DIRECTORY"
echo ""

# Count total files
TOTAL_FILES=$(find "$DIRECTORY" -name "*.php" -type f | wc -l)
CURRENT=0
FILES_WITH_CHANGES=0
TOTAL_PROPERTIES=0

echo "Found $TOTAL_FILES PHP files to process"
echo ""

# Process each PHP file
find "$DIRECTORY" -name "*.php" -type f | while read -r file; do
    CURRENT=$((CURRENT + 1))
    echo "[$CURRENT/$TOTAL_FILES] Processing: $file"
    
    # Run the add-missing-properties script
    OUTPUT=$(php refactor/add-missing-properties.php $DRY_RUN "$file" 2>&1)
    
    # Check if properties were found
    if echo "$OUTPUT" | grep -q "Found.*undefined properties"; then
        FILES_WITH_CHANGES=$((FILES_WITH_CHANGES + 1))
        PROPS=$(echo "$OUTPUT" | grep "Found.*undefined properties" | grep -oP '\d+' | head -1)
        TOTAL_PROPERTIES=$((TOTAL_PROPERTIES + PROPS))
        echo "  ✅ Found $PROPS undefined properties"
        echo "$OUTPUT" | grep -A 100 "Properties to add:" | grep "protected"
    else
        echo "  ✓ No undefined properties"
    fi
    echo ""
done

echo "=================================================="
echo "SUMMARY"
echo "=================================================="
echo "Total files processed: $TOTAL_FILES"
echo "Files with undefined properties: $FILES_WITH_CHANGES"
echo "Total properties added: $TOTAL_PROPERTIES"

if [ -n "$DRY_RUN" ]; then
    echo ""
    echo "This was a DRY RUN. Run without --dry-run to apply changes."
fi

