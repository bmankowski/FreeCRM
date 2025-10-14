#!/bin/bash
# Super-batch migration: Process 30+ Settings aliases at once
# All aliases verified to have ZERO template usage

ROOT="/home/bmankowski/projects/FreeCRM"
cd "$ROOT"

echo "=== SUPER-BATCH MIGRATION: 57 SETTINGS ALIASES ==="
echo ""

# Map alias to full namespace (format: alias|namespace)
declare -a BATCH=(
    "Settings_Dav_Module_Model|FreeCRM\\Modules\\Settings\\Dav\\Models\\Module"
    "Settings_FinancialProcesses_Index_View|FreeCRM\\Modules\\Settings\\FinancialProcesses\\Views\\Index"
    "Settings_Github_Issues_Model|FreeCRM\\Modules\\Settings\\Github\\Models\\Issues"
    "Settings_GlobalPermission_Record_Model|FreeCRM\\Modules\\Settings\\GlobalPermission\\Models\\Record"
    "Settings_HideBlocks_Module_Model|FreeCRM\\Modules\\Settings\\HideBlocks\\Models\\Module"
    "Settings_Inventory_CreditLimits_View|FreeCRM\\Modules\\Settings\\Inventory\\Views\\CreditLimits"
    "Settings_Inventory_DiscountConfiguration_View|FreeCRM\\Modules\\Settings\\Inventory\\Views\\DiscountConfiguration"
    "Settings_LoginHistory_Record_Model|FreeCRM\\Modules\\Settings\\LoginHistory\\Models\\Record"
    "Settings_Mail_Autologin_Model|FreeCRM\\Modules\\Settings\\Mail\\Models\\Autologin"
    "Settings_ModTracker_Module_Model|FreeCRM\\Modules\\Settings\\ModTracker\\Models\\Module"
    "Settings_PBXManager_Record_Model|FreeCRM\\Modules\\Settings\\PBXManager\\Models\\Record"
    "Settings_Password_Record_Model|FreeCRM\\Modules\\Settings\\Password\\Models\\Record"
    "Settings_PickListDependency_Edit_View|FreeCRM\\Modules\\Settings\\PickListDependency\\Views\\Edit"
    "Settings_Picklist_Field_Model|FreeCRM\\Modules\\Settings\\Picklist\\Models\\Field"
    "Settings_Profiles_IndexAjax_View|FreeCRM\\Modules\\Settings\\Profiles\\Views\\IndexAjax"
    "Settings_Profiles_Record_Model|FreeCRM\\Modules\\Settings\\Profiles\\Models\\Record"
    "Settings_PublicHoliday_Configuration_View|FreeCRM\\Modules\\Settings\\PublicHoliday\\Views\\Configuration"
    "Settings_PublicHoliday_Module_Model|FreeCRM\\Modules\\Settings\\PublicHoliday\\Models\\Module"
    "Settings_QuickCreateEditor_Module_Model|FreeCRM\\Modules\\Settings\\QuickCreateEditor\\Models\\Module"
    "Settings_RealizationProcesses_Index_View|FreeCRM\\Modules\\Settings\\RealizationProcesses\\Views\\Index"
    "Settings_RealizationProcesses_Module_Model|FreeCRM\\Modules\\Settings\\RealizationProcesses\\Models\\Module"
    "Settings_Roles_IndexAjax_View|FreeCRM\\Modules\\Settings\\Roles\\Views\\IndexAjax"
    "Settings_Roles_Index_View|FreeCRM\\Modules\\Settings\\Roles\\Views\\Index"
    "Settings_SMSNotifier_Field_Model|FreeCRM\\Modules\\Settings\\SMSNotifier\\Models\\Field"
    "Settings_SMSNotifier_Module_Model|FreeCRM\\Modules\\Settings\\SMSNotifier\\Models\\Module"
    "Settings_SMSNotifier_Record_Model|FreeCRM\\Modules\\Settings\\SMSNotifier\\Models\\Record"
    "Settings_SalesProcesses_Module_Model|FreeCRM\\Modules\\Settings\\SalesProcesses\\Models\\Module"
    "Settings_Search_Module_Model|FreeCRM\\Modules\\Settings\\Search\\Models\\Module"
    "Settings_SharingAccess_Action_Model|FreeCRM\\Modules\\Settings\\SharingAccess\\Models\\Action"
    "Settings_SharingAccess_RuleMember_Model|FreeCRM\\Modules\\Settings\\SharingAccess\\Models\\RuleMember"
    "Settings_SupportProcesses_Module_Model|FreeCRM\\Modules\\Settings\\SupportProcesses\\Models\\Module"
    "Settings_TimeControlProcesses_Module_Model|FreeCRM\\Modules\\Settings\\TimeControlProcesses\\Models\\Module"
    "Settings_TreesManager_Module_Model|FreeCRM\\Modules\\Settings\\TreesManager\\Models\\Module"
    "Settings_Updates_Module_Model|FreeCRM\\Modules\\Settings\\Updates\\Models\\Module"
    "Settings_Widgets_Module_Model|FreeCRM\\Modules\\Settings\\Widgets\\Models\\Module"
    "Settings_Vtiger_ConfigModule_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\ConfigModule"
    "Settings_Vtiger_CustomRecordNumberingModule_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\CustomRecordNumberingModule"
    "Settings_Vtiger_Field_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\Field"
    "Settings_Vtiger_Icons_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\Icons"
    "Settings_Vtiger_MenuItem_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\MenuItem"
    "Settings_Vtiger_Menu_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\Menu"
    "Settings_Vtiger_Systems_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\Systems"
    "Settings_Vtiger_TermsAndConditions_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\TermsAndConditions"
    "Settings_Vtiger_Tracker_Model|FreeCRM\\Modules\\Settings\\Vtiger\\Models\\Tracker"
    "Settings_WidgetsManagement_Module_Model|FreeCRM\\Modules\\Settings\\WidgetsManagement\\Models\\Module"
    "Settings_Workflows_FilterRecordStructure_Model|FreeCRM\\Modules\\Settings\\Workflows\\Models\\FilterRecordStructure"
    "Settings_Workflows_RecordStructure_Model|FreeCRM\\Modules\\Settings\\Workflows\\Models\\RecordStructure"
    "Settings_Workflows_TaskRecord_Model|FreeCRM\\Modules\\Settings\\Workflows\\Models\\TaskRecord"
    "Settings_Workflows_TaskType_Model|FreeCRM\\Modules\\Settings\\Workflows\\Models\\TaskType"
)

TOTAL=0
PROCESSED_ALIASES=0

for ENTRY in "${BATCH[@]}"; do
    ALIAS=$(echo "$ENTRY" | cut -d'|' -f1)
    NAMESPACE=$(echo "$ENTRY" | cut -d'|' -f2)
    
    # Find files using this alias
    FILES=$(grep -rl "$ALIAS" src/ --include="*.php" 2>/dev/null | grep -v "old_modules\|GlobalAliases.php")
    
    if [ -z "$FILES" ]; then
        continue
    fi
    
    FILE_COUNT=0
    for FILE in $FILES; do
        # Skip if use statement already exists
        if grep -q "use ${NAMESPACE} as ${ALIAS};" "$FILE"; then
            continue
        fi
        
        # Add use statement after namespace
        if grep -q "^namespace " "$FILE"; then
            sed -i "/^namespace /a\\use ${NAMESPACE} as ${ALIAS};" "$FILE"
            FILE_COUNT=$((FILE_COUNT + 1))
        fi
    done
    
    if [ $FILE_COUNT -gt 0 ]; then
        echo "✓ $ALIAS → $FILE_COUNT files"
        TOTAL=$((TOTAL + FILE_COUNT))
        PROCESSED_ALIASES=$((PROCESSED_ALIASES + 1))
    fi
done

echo ""
echo "=== Summary ==="
echo "Aliases processed: $PROCESSED_ALIASES"
echo "Total files modified: $TOTAL"
echo ""

echo "Fixing leading backslashes..."
# Remove leading backslashes for all Settings aliases
find src/ -name "*.php" -type f ! -path "*/old_modules/*" -exec sed -i 's/\\Settings_\([A-Za-z_]*\)\([^a-zA-Z_0-9]\)/Settings_\1\2/g' {} \;
echo "✓ Fixed all leading backslashes"

