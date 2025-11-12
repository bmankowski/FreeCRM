{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/FreeCRMLicense.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/inventoryfields/EditViewBase.tpl -->
	{if isset($FIELD) && $FIELD}
		{assign var="ITEM_VAL" value=$ITEM_VALUE|default:""}
		{assign var="RAW_VALUE" value=$FIELD->getValue($ITEM_VAL)}
		{assign var="VALUE" value=$RAW_VALUE|default:""}
		{assign var="INPUT_TYPE" value='text'}
		{assign var="DISPLAY_TYPE" value=$FIELD->get('displaytype')|default:0}
		{if $DISPLAY_TYPE == 10}
			{assign var="INPUT_TYPE" value='hidden'}
			<span class="{$FIELD->getColumnName()}Text valueText">
				{assign var="DISPLAY_VAL" value=$FIELD->getDisplayValue($VALUE)}
				{$DISPLAY_VAL|default:""}
			</span>
		{/if}
		{assign var="EDIT_VAL" value=$FIELD->getEditValue($VALUE)}
		<input name="{$FIELD->getColumnName()}{$ROW_NO}" type="{$INPUT_TYPE}" class="form-control {$FIELD->getColumnName()} valueVal" value="{$EDIT_VAL|default:""}" {if $DISPLAY_TYPE == 10}readonly="readonly"{/if}/>
	{/if}
<!--/layouts/basic/modules/Base/inventoryfields/EditViewBase.tpl -->
{/strip}

