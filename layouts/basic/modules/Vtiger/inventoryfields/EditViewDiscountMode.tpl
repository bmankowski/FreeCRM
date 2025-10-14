{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/inventoryfields/EditViewDiscountMode.tpl -->
	<select class="select2 discountMode" title="{"LBL_TAX_MODE"|t:$MODULE}" name="{$FIELD->getColumnName()}{$ROW_NO}" {if $FIELD->get('displaytype') == 10}readonly="readonly"{/if}>
		<option value="0" {if $ITEM_VALUE == '0'}selected{/if}>{"LBL_GROUP"|t:$MODULE}</option>
		<option value="1" {if $ITEM_VALUE == '1'}selected{/if}>{"LBL_INDIVIDUAL"|t:$MODULE}</option>
	</select>
<!--/layouts/basic/modules/Vtiger/inventoryfields/EditViewDiscountMode.tpl -->
{/strip}
