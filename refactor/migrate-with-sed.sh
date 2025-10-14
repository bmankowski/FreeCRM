#!/bin/bash
# Fast alias migration using sed
# Usage: ./migrate-with-sed.sh "AliasName" "Full\Namespace\Path"

if [ $# -lt 2 ]; then
    echo "Usage: $0 <AliasName> <FullNamespacePath>"
    echo "Example: $0 Settings_Menu_Model 'FreeCRM\Modules\Settings\Menu\Models\Menu'"
    exit 1
fi

ALIAS="$1"
NAMESPACE="$2"
ROOT="/home/bmankowski/projects/FreeCRM"

echo "=== Migrating: $ALIAS → $NAMESPACE ==="
echo ""

# Find PHP files using this alias (excluding old_modules, vendor, GlobalAliases)
FILES=$(grep -rl "$ALIAS" "$ROOT/src" --include="*.php" 2>/dev/null | grep -v "old_modules\|GlobalAliases.php")

if [ -z "$FILES" ]; then
    echo "No files found using $ALIAS"
    exit 0
fi

COUNT=0
for FILE in $FILES; do
    # Check if file actually uses the alias in code (not just comments)
    if ! grep -q "[^a-zA-Z_]${ALIAS}[^a-zA-Z_0-9]" "$FILE"; then
        continue
    fi
    
    # Check if use statement already exists
    if grep -q "use ${NAMESPACE} as ${ALIAS};" "$FILE"; then
        continue
    fi
    
    # Add use statement after namespace declaration
    if grep -q "^namespace " "$FILE"; then
        # File has namespace - add after it
        sed -i "/^namespace /a\\use ${NAMESPACE} as ${ALIAS};" "$FILE"
    else
        # File is in global namespace - add after opening php tag and comments
        sed -i "/^<?php/,/^[^\/\*]/ {
            /^[^\/\*]/ i\\use ${NAMESPACE} as ${ALIAS};
        }" "$FILE"
    fi
    
    echo "✓ $(basename $FILE)"
    COUNT=$((COUNT + 1))
done

echo ""
echo "Files modified: $COUNT"

