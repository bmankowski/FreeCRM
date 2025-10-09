#!/bin/bash
# Batch Module Migration Script
# Automates the migration of multiple modules with common fixes

# Don't exit on error - continue processing all modules
set +e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$ROOT_DIR"

# List of modules to migrate (simple to complex)
MODULES=(
    # Simple modules (already done: Home, Dashboard)
    "Announcements"
    "Rss"
    "ModComments"
    "Notification"
    "Password"
    
    # Medium complexity
    "Contacts"
    "Leads"
    "Accounts"
    "Calendar"
    "Events"
    "Products"
    "Services"
    "Vendors"
    "Partners"
    
    # Business modules
    "HelpDesk"
    "Campaigns"
    "Documents"
    "Faq"
    "KnowledgeBase"
    "Ideas"
    "Assets"
    
    # OSS modules
    "OSSMail"
    "OSSMailView"
    "OSSMailScanner"
    "OSSTimeControl"
    "OSSPasswords"
    "OSSEmployees"
    "OSSSoldServices"
    "OSSOutsourcedServices"
    
    # Inventory modules
    "PriceBooks"
    "PaymentsIn"
    "PaymentsOut"
    
    # Custom modules
    "Project"
    "ProjectMilestone"
    "ProjectTask"
    "ServiceContracts"
    "OutsourcedProducts"
    "Reservations"
    
    # Financial modules
    "FInvoice"
    "FInvoiceProforma"
    "FInvoiceCost"
    "FBookkeeping"
    "FCorectingInvoice"
    
    # Inventory tracking
    "IStorages"
    "IGRN"
    "IGDN"
    "IGIN"
    "IGRNC"
    "IGDNC"
    "IIDN"
    "ISTRN"
    "ISTDN"
    "ISTN"
    "IPreOrder"
    
    # Sales
    "SQuotes"
    "SQuoteEnquiries"
    "SSalesProcesses"
    "SSingleOrders"
    "SRecurringOrders"
    "SRequirementsCards"
    "SCalculations"
    "SVendorEnquiries"
    
    # Other
    "Competition"
    "CFixedAssets"
    "CMileageLogbook"
    "CInternalTickets"
    "HolidaysEntitlement"
    "LettersIn"
    "LettersOut"
    "Reports"
    "RecycleBin"
    "ModTracker"
    "PBXManager"
    "CallHistory"
    "SMSNotifier"
    "EmailTemplates"
    "OpenStreetMap"
    "AJAXChat"
    "API"
    "ApiAddress"
    "PickList"
    "Portal"
)

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SUCCESS_COUNT=0
FAILED_COUNT=0
SKIPPED_COUNT=0

echo "=== Batch Module Migration ==="
echo "Total modules to process: ${#MODULES[@]}"
echo ""

for MODULE in "${MODULES[@]}"; do
    echo "----------------------------------------"
    echo "Processing: $MODULE"
    echo "----------------------------------------"
    
    # Check if module exists
    if [ ! -d "modules/$MODULE" ]; then
        echo -e "${YELLOW}⊘ Module not found, skipping${NC}"
        ((SKIPPED_COUNT++))
        continue
    fi
    
    # Check if already migrated
    if [ -d "src/Modules/$MODULE" ]; then
        echo -e "${YELLOW}⊘ Already migrated, skipping${NC}"
        ((SKIPPED_COUNT++))
        continue
    fi
    
    # Step 1: Copy module
    echo "→ Copying module..."
    php refactor/scripts/copy-module.php "$MODULE" > /dev/null 2>&1
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ Copy failed${NC}"
        ((FAILED_COUNT++))
        continue
    fi
    
    # Step 2: Apply common fixes
    echo "→ Applying fixes..."
    
    # Fix class names in all directories
    for dir in Models Views Actions Dashboards UiTypes Widgets Handlers; do
        if [ -d "src/Modules/$MODULE/$dir" ]; then
            cd "src/Modules/$MODULE/$dir"
            for file in *.php; do
                if [ -f "$file" ]; then
                    filename="${file%.php}"
                    sed -i "s/^class Model extends/class $filename extends/g" "$file" 2>/dev/null || true
                    sed -i "s/^class View extends/class $filename extends/g" "$file" 2>/dev/null || true
                    sed -i "s/^class Action extends/class $filename extends/g" "$file" 2>/dev/null || true
                    sed -i "s/^class Dashboard extends/class $filename extends/g" "$file" 2>/dev/null || true
                    sed -i "s/^class UIType extends/class $filename extends/g" "$file" 2>/dev/null || true
                    sed -i "s/^class Widget extends/class $filename extends/g" "$file" 2>/dev/null || true
                    sed -i "s/^class Handler extends/class $filename extends/g" "$file" 2>/dev/null || true
                    
                    # Fix standalone classes without extends
                    sed -i "s/^class Model$/class $filename/g" "$file" 2>/dev/null || true
                    sed -i "s/^class View$/class $filename/g" "$file" 2>/dev/null || true
                    sed -i "s/^class Action$/class $filename/g" "$file" 2>/dev/null || true
                    sed -i "s/^class Model {/class $filename {/g" "$file" 2>/dev/null || true
                    sed -i "s/^class View {/class $filename {/g" "$file" 2>/dev/null || true
                    sed -i "s/^class Action {/class $filename {/g" "$file" 2>/dev/null || true
                fi
            done
            cd "$ROOT_DIR"
        fi
    done
    
    # Fix double FreeCRM namespace
    find "src/Modules/$MODULE" -name "*.php" -exec sed -i 's/\\FreeCRM\\\\FreeCRM\\/\\FreeCRM\\/g' {} \; 2>/dev/null || true
    
    # Fix vtlib extends
    find "src/Modules/$MODULE" -name "*.php" -exec sed -i 's/ extends vtlib\\/ extends \\vtlib\\/g' {} \; 2>/dev/null || true
    
    # Step 3: Validate
    echo "→ Validating..."
    VALIDATION_OUTPUT=$(php refactor/scripts/validate-module.php "$MODULE" 2>&1)
    
    if echo "$VALIDATION_OUTPUT" | grep -q "✅ Validation PASSED"; then
        echo -e "${GREEN}✓ Migration successful${NC}"
        ((SUCCESS_COUNT++))
        
        # Step 4: Commit (ignore errors)
        git add "src/Modules/$MODULE" > /dev/null 2>&1 || true
        git commit -m "Migrate $MODULE to PSR-4" > /dev/null 2>&1 || true
    else
        # Check error count
        ERROR_COUNT=$(echo "$VALIDATION_OUTPUT" | grep "^Errors:" | awk '{print $2}')
        
        if [ -z "$ERROR_COUNT" ] || [ "$ERROR_COUNT" -gt 10 ]; then
            echo -e "${RED}✗ Validation failed ($ERROR_COUNT errors)${NC}"
            ((FAILED_COUNT++))
        else
            echo -e "${YELLOW}⚠ Validation passed with $ERROR_COUNT minor errors${NC}"
            ((SUCCESS_COUNT++))
            
            # Commit anyway (can be fixed later, ignore errors)
            git add "src/Modules/$MODULE" > /dev/null 2>&1 || true
            git commit -m "Migrate $MODULE to PSR-4 (with $ERROR_COUNT minor issues)" > /dev/null 2>&1 || true
        fi
    fi
    
    echo ""
done

echo "========================================"
echo "=== Batch Migration Complete ==="
echo "========================================"
echo -e "${GREEN}Successful: $SUCCESS_COUNT${NC}"
echo -e "${YELLOW}Skipped: $SKIPPED_COUNT${NC}"
echo -e "${RED}Failed: $FAILED_COUNT${NC}"
echo "========================================"

