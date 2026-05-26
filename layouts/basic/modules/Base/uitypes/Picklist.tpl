{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Base/uitypes/Picklist.tpl -->
	{assign var="FIELD_INFO" value=\App\Utils\Json::encode($FIELD_MODEL->getFieldInfo())}
	{assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
	{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
	{if $FIELD_MODEL->isEditableReadOnly()}
	{assign var=RAW_VALUE value=$FIELD_MODEL->get('fieldvalue')}
	{assign var=DISPLAY_VALUE value=$PICKLIST_VALUES[$RAW_VALUE]|default:$RAW_VALUE}
	<input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value="{\App\Modules\Base\Helpers\Util::toSafeHTML($RAW_VALUE)}" />
	<input id="{$MODULE}_editView_fieldName_{$FIELD_MODEL->getFieldName()}" type="text" title="{$FIELD_MODEL->get('label')|t:$MODULE}" class="form-control" readonly="readonly" value="{\App\Modules\Base\Helpers\Util::toSafeHTML($DISPLAY_VALUE)}" data-fieldinfo='{$FIELD_INFO|escape}' />
	{else}
	<select class="chzn-select form-control" title="{$FIELD_MODEL->get('label')|t:$MODULE}" name="{$FIELD_MODEL->getFieldName()}" data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_INFO|escape}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{\App\Utils\Json::encode($SPECIAL_VALIDATOR)}'{/if} data-selected-value='{$FIELD_MODEL->get('fieldvalue')}'>
		{if $FIELD_MODEL->isEmptyPicklistOptionAllowed()}<option value="" {if $FIELD_MODEL->isMandatory() eq true && $FIELD_MODEL->get('fieldvalue') neq ''} disabled{/if}>{"LBL_SELECT_OPTION"|t:"Vtiger"}</option>{/if}
		{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
			<option value="{\App\Modules\Base\Helpers\Util::toSafeHTML($PICKLIST_NAME)}" title="{\App\Modules\Base\Helpers\Util::toSafeHTML($PICKLIST_VALUE)}" {if trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($PICKLIST_NAME)} selected {/if}>{\App\Modules\Base\Helpers\Util::toSafeHTML($PICKLIST_VALUE)}</option>
		{/foreach}
	</select>
	{/if}
<!--/layouts/basic/modules/Base/uitypes/Picklist.tpl -->
{/strip}
