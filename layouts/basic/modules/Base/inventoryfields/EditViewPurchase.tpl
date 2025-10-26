{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/inventoryfields/EditViewPurchase.tpl -->
	{assign var=VALUE value=$FIELD->getValue($ITEM_VALUE)}
	<input name="purchase{$ROW_NO}" value="{$FIELD->getEditValue($VALUE)}" type="text" 
		   data-validation-engine="validate[required,funcCall[Vtiger_PositiveNumber_Validator_Js.invokeValidation]]" 
		   class="purchase form-control input-sm" {if $FIELD->get('displaytype') == 10}readonly="readonly"{/if}/>
<!--/layouts/basic/modules/Base/inventoryfields/EditViewPurchase.tpl -->
{/strip}
