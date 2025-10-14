{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/inventoryfields/DetailViewBase.tpl -->
	{if $FIELD->get('columnname') === 'qty' && $INVENTORY_ROW['unit'] === 'pack' && $INVENTORY_ROW['qtyparam']}({vtranslate('pcs','Products')}){/if} {$FIELD->getDisplayValue($ITEM_VALUE)}
<!--/layouts/basic/modules/Vtiger/inventoryfields/DetailViewBase.tpl -->
{/strip}
