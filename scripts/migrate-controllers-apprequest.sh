#!/bin/bash
# migrate-controllers-apprequest.sh
# Migrate controllers from AppRequest to $request parameter

set -e

echo "======================================"
echo "AppRequest Controller Migration"
echo "======================================"
echo ""

# Change to project root
cd "$(dirname "$0")/.."

# Backup files first
echo "Creating backups..."
cp src/Modules/Products/Actions/SaveAjax.php src/Modules/Products/Actions/SaveAjax.php.bak
cp src/Modules/Users/Actions/ForgotPassword.php src/Modules/Users/Actions/ForgotPassword.php.bak
echo "✓ Backups created (.bak files)"
echo ""

# Fix 1: Products/Actions/SaveAjax.php
echo "Migrating: Products/Actions/SaveAjax.php"
sed -i 's/\\App\\Http\\AppRequest::set/$request->set/g' \
  src/Modules/Products/Actions/SaveAjax.php
echo "✓ Replaced AppRequest::set with \$request->set"
echo ""

# Fix 2: Users/Actions/ForgotPassword.php
echo "Migrating: Users/Actions/ForgotPassword.php"
# This is more complex, let's show the line that needs manual fixing
echo "⚠️  Line 107 needs manual update:"
echo "   OLD: \\App\\Modules\\Users\\Actions\\ForgotPassword::run(\\App\\Http\\AppRequest::init());"
echo "   NEW: \$request = new \\App\\Http\\Vtiger_Request(\$_REQUEST, \$_REQUEST);"
echo "        \\App\\Modules\\Users\\Actions\\ForgotPassword::run(\$request);"
echo ""

# Verification
echo "Verifying changes..."
REMAINING=$(grep -r "AppRequest::" src/Modules/*/Actions/*.php src/Modules/*/Views/*.php 2>/dev/null | wc -l)

if [ "$REMAINING" -eq 1 ]; then
    echo "✓ SaveAjax.php migrated successfully"
    echo "⚠️  ForgotPassword.php still needs manual fix (line 107)"
elif [ "$REMAINING" -eq 0 ]; then
    echo "✓ All controllers migrated successfully!"
else
    echo "⚠️  $REMAINING references to AppRequest still found in controllers"
fi

echo ""
echo "======================================"
echo "Review the changes:"
echo "  git diff src/Modules/Products/Actions/SaveAjax.php"
echo "  git diff src/Modules/Users/Actions/ForgotPassword.php"
echo ""
echo "Backups available at:"
echo "  src/Modules/Products/Actions/SaveAjax.php.bak"
echo "  src/Modules/Users/Actions/ForgotPassword.php.bak"
echo "======================================"

