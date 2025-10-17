#!/bin/bash
# Batch migrate multiple aliases using sed
# Processes all Settings aliases with zero template usage

ROOT="/home/bmankowski/projects/FreeCRM"
cd "$ROOT"

echo "=== BATCH 6 MIGRATION WITH SED ==="
echo ""

# Declare aliases (alias:namespace pairs)
declare -A ALIASES=(
    ["Settings_LangManagement_Module_Model"]="FreeCRM\\Modules\\Settings\\LangManagement\\Models\\Module"
    ["Settings_CurrencyUpdate_Module_Model"]="FreeCRM\\Modules\\Settings\\CurrencyUpdate\\Models\\Module"
    ["Settings_MappedFields_Field_Model"]="FreeCRM\\Modules\\Settings\\MappedFields\\Models\\Field"
    ["Settings_LayoutEditor_Field_Model"]="FreeCRM\\Modules\\Settings\\LayoutEditor\\Models\\Field"
    ["Settings_Menu_Record_Model"]="FreeCRM\\Modules\\Settings\\Menu\\Models\\Record"
    ["Settings_CustomView_Module_Model"]="FreeCRM\\Modules\\Settings\\CustomView\\Models\\Module"
    ["Settings_Inventory_Record_Model"]="FreeCRM\\Modules\\Settings\\Inventory\\Models\\Record"
    ["Settings_Menu_Module_Model"]="FreeCRM\\Modules\\Settings\\Menu\\Models\\Module"
    ["Settings_HideBlocks_Record_Model"]="FreeCRM\\Modules\\Settings\\HideBlocks\\Models\\Record"
    ["Settings_LayoutEditor_Block_Model"]="FreeCRM\\Modules\\Settings\\LayoutEditor\\Models\\Block"
    ["Settings_Leads_Field_Model"]="FreeCRM\\Modules\\Settings\\Leads\\Models\\Field"
    ["\App\Modules\Settings\MarketingProcesses\Models\Module"]="FreeCRM\\Modules\\Settings\\MarketingProcesses\\Models\\Module"
    ["Settings_CurrencyUpdate_AbstractBank_Model"]="FreeCRM\\Modules\\Settings\\CurrencyUpdate\\Models\\AbstractBank"
    ["Settings_Github_Client_Model"]="FreeCRM\\Modules\\Settings\\Github\\Models\\Client"
    ["Settings_ApiAddress_Module_Model"]="FreeCRM\\Modules\\Settings\\ApiAddress\\Models\\Module"
)

TOTAL=0

for ALIAS in "${!ALIASES[@]}"; do
    NAMESPACE="${ALIASES[$ALIAS]}"
    
    # Find files using this alias
    FILES=$(grep -rl "$ALIAS" src/ --include="*.php" 2>/dev/null | grep -v "old_modules\|GlobalAliases.php")
    
    if [ -z "$FILES" ]; then
        continue
    fi
    
    for FILE in $FILES; do
        # Skip if use statement already exists
        if grep -q "use ${NAMESPACE} as ${ALIAS};" "$FILE"; then
            continue
        fi
        
        # Add use statement after namespace
        if grep -q "^namespace " "$FILE"; then
            sed -i "/^namespace /a\\use ${NAMESPACE} as ${ALIAS};" "$FILE"
            echo "✓ $ALIAS → $FILE"
            TOTAL=$((TOTAL + 1))
        fi
    done
done

echo ""
echo "=== Summary ==="
echo "Total files modified: $TOTAL"
echo ""
echo "Now fixing leading backslashes..."

# Remove leading backslashes for all migrated aliases
for ALIAS in "${!ALIASES[@]}"; do
    find src/ -name "*.php" -type f ! -path "*/old_modules/*" -exec sed -i "s/\\\\${ALIAS}\\([^a-zA-Z_0-9]\\)/${ALIAS}\\1/g" {} \;
done

echo "✓ Fixed all leading backslashes"

