#!/bin/bash

grep -r -oP '\b[A-Z][a-z]+[A-Za-z0-9]*_[A-Z][a-z]+[A-Za-z0-9]*_[A-Z][A-Za-z0-9_]*\b' --include="*.php" . --exclude-dir=vendor | tr ':' '\t' | sort -t$'\t' -k2 -u > aliases_waiting_to_be_changed.txt
sed -i '/GlobalAliases/d' aliases_waiting_to_be_changed.txt
sed -i '/Base2.php/d' aliases_waiting_to_be_changed.txt
sed -i '/Vtiger_Language_Handler/d' aliases_waiting_to_be_changed.txt
sed -i '/Vtiger_Action_Controller/d' aliases_waiting_to_be_changed.txt
sed -i '/Smarty_Internal/d' aliases_waiting_to_be_changed.txt
sed -i '/Vtiger_Base_Validator_Js/d' aliases_waiting_to_be_changed.txt
sed -i '/InventoryField/d' aliases_waiting_to_be_changed.txt
sed -i '/Vtiger_Action_Model/d' aliases_waiting_to_be_changed.txt
sed -i '/alias_replacer/d' aliases_waiting_to_be_changed.txt
sed -i '/refactor/d' aliases_waiting_to_be_changed.txt
sed -i '/GlobalAliases/d' aliases_waiting_to_be_changed.txt
sed -i '/Vtiger_CssScript_Model/d' aliases_waiting_to_be_changed.txt
sed -i '/Vtiger_JavaScript/d' aliases_waiting_to_be_changed.txt
sed -i '/Vtiger_Detail_Js/d' aliases_waiting_to_be_changed.txt
sed -i '/_Js/d' aliases_waiting_to_be_changed.txt
sed -i '/Vtiger_View_Controller/d' aliases_waiting_to_be_changed.txt

echo "Checking which aliases are proper class names..."
echo "================================================"

found=0
missing=0

while IFS=$'\t' read -r filepath classname; do
    # Skip empty lines
    [ -z "$classname" ] && continue
    
    # Search for "class ClassName" in the codebase
    if grep -r "^class $classname" --include="*.php" . > /dev/null 2>&1; then
        ((found++))
        echo "FOUND (removing): $classname"
        # Delete this line from the file using sed
        sed -i "/\t$classname$/d" aliases_waiting_to_be_changed.txt
    else
        echo "MISSING (keeping): $classname"
        ((missing++))
    fi
done < aliases_waiting_to_be_changed.txt

echo ""
echo "================================================"
echo "Summary:"
echo "  Missing classes kept in file: $missing"
echo ""

cat aliases_waiting_to_be_changed.txt
cat aliases_waiting_to_be_changed.txt | wc -l