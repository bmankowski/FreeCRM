#!/bin/bash
# Convert all Action classes from vglobal('current_user') to $request->getUser()

count=0
converted=0

find src/Modules/*/Actions -name "*.php" -type f 2>/dev/null | while read file; do
    count=$((count + 1))
    
    # Skip if already converted
    if grep -q '$request->getUser()' "$file" 2>/dev/null; then
        echo "SKIP (already converted): $file"
        continue
    fi
    
    # Check if file contains vglobal('current_user')
    if ! grep -q "vglobal('current_user')" "$file" 2>/dev/null; then
        continue
    fi
    
    # Replace vglobal('current_user') with $request->getUser()
    sed -i "s/\$current_user = vglobal('current_user');/\$currentUser = \$request->getUser();/g" "$file"
    sed -i "s/\$current_user = vglobal(\"current_user\");/\$currentUser = \$request->getUser();/g" "$file"
    
    # Update variable references from $current_user to $currentUser
    sed -i 's/\$current_user->/\$currentUser->/g' "$file"
    sed -i 's/\$current_user->id/\$currentUser->getId()/g' "$file"
    
    converted=$((converted + 1))
    echo "CONVERTED: $file"
done

echo ""
echo "Conversion complete!"
echo "Files converted: $converted"

