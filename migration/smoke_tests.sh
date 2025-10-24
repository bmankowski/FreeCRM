#!/bin/bash
# Smoke tests for user authentication migration

echo "============================================"
echo "  User Authentication Migration Smoke Tests"
echo "============================================"
echo ""

BASEURL="http://localhost"
COOKIES="/tmp/cookies.txt"
FAIL_COUNT=0

# Clean cookies
rm -f $COOKIES

echo "Test 1: Login..."
RESPONSE=$(curl -s -c $COOKIES -b $COOKIES -L \
  -d "username=admin&password=admin" -X POST \
  "$BASEURL/index.php?module=Users&action=Login")

if echo "$RESPONSE" | grep -q "Home\|Dashboard\|Strona"; then
    echo "✓ Login successful"
else
    echo "✗ Login failed"
    FAIL_COUNT=$((FAIL_COUNT + 1))
fi

echo ""
echo "Test 2: Home Dashboard..."
RESPONSE=$(curl -s -c $COOKIES -b $COOKIES -L \
  "$BASEURL/index.php?module=Home&view=DashBoard")

if echo "$RESPONSE" | grep -q "Dashboard\|Strona"; then
    echo "✓ Dashboard loaded"
else
    echo "✗ Dashboard failed"
    FAIL_COUNT=$((FAIL_COUNT + 1))
fi

echo ""
echo "Test 3: Leads Module..."
RESPONSE=$(curl -s -c $COOKIES -b $COOKIES -L \
  "$BASEURL/index.php?module=Leads&view=List")

if echo "$RESPONSE" | grep -q "Leads\|Lead\|Lista"; then
    echo "✓ Leads module accessible"
else
    echo "✗ Leads module failed"
    FAIL_COUNT=$((FAIL_COUNT + 1))
fi

echo ""
echo "Test 4: Accounts Module..."
RESPONSE=$(curl -s -c $COOKIES -b $COOKIES -L \
  "$BASEURL/index.php?module=Accounts&view=List")

if echo "$RESPONSE" | grep -q "Accounts\|Kontrahent\|Lista"; then
    echo "✓ Accounts module accessible"
else
    echo "✗ Accounts module failed"
    FAIL_COUNT=$((FAIL_COUNT + 1))
fi

echo ""
echo "Test 5: Settings (Admin Access)..."
RESPONSE=$(curl -s -c $COOKIES -b $COOKIES -L \
  "$BASEURL/index.php?module=Vtiger&parent=Settings&view=Index")

if echo "$RESPONSE" | grep -q "Settings\|Ustawienia"; then
    echo "✓ Settings accessible"
else
    echo "✗ Settings failed"
    FAIL_COUNT=$((FAIL_COUNT + 1))
fi

echo ""
echo "Test 6: Calendar Module..."
RESPONSE=$(curl -s -c $COOKIES -b $COOKIES -L \
  "$BASEURL/index.php?module=Calendar&view=Calendar")

if echo "$RESPONSE" | grep -q "Calendar\|Kalendarz"; then
    echo "✓ Calendar accessible"
else
    echo "✗ Calendar failed"
    FAIL_COUNT=$((FAIL_COUNT + 1))
fi

echo ""
echo "============================================"
if [ $FAIL_COUNT -eq 0 ]; then
    echo "✓ ALL TESTS PASSED!"
    echo "============================================"
    exit 0
else
    echo "✗ $FAIL_COUNT TEST(S) FAILED"
    echo "============================================"
    echo "Check cache/logs/system.log for details"
    exit 1
fi

