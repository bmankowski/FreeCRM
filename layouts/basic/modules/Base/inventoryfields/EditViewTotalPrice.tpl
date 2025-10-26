{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/inventoryfields/EditViewTotalPrice.tpl -->
	{assign var=VALUE value=$FIELD->getValue($ITEM_VALUE)}
	<input name="{$FIELD->getColumnName()}{$ROW_NO}" type="hidden" value="{$FIELD->getEditValue($VALUE)}" class="totalPrice" {if $FIELD->get('displaytype') == 10}readonly="readonly"{/if} />
	<span class="totalPriceText">{$FIELD->getEditValue($VALUE)}</span>
<!--/layouts/basic/modules/Base/inventoryfields/EditViewTotalPrice.tpl -->
{/strip}
