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

cat aliases_waiting_to_be_changed.txt | cut -f2 aliases_waiting_to_be_changed.txt 

