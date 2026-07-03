{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} -->*}
{strip}
<!-- layouts/basic/modules/Base/uitypes/ModulesMultipicklist.tpl -->
{assign var="FIELD_INFO" value=\App\Utils\Json::encode($FIELD_MODEL->getFieldInfo())}
{assign var=PICKLIST_VALUES value=$FIELD_MODEL->getModulesListValues()}
{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
{assign var="FIELD_VALUE_LIST" value=$FIELD_MODEL->getUITypeModel()->getEditViewDisplayValue($FIELD_MODEL->get('fieldvalue'))}
<input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value="" />
<select id="{$MODULE}_{$VIEW}_fieldName_{$FIELD_MODEL->get('name')}" title="{$FIELD_MODEL->get('label')|t:$MODULE}" multiple class="chzn-select form-control col-md-12" name="{$FIELD_MODEL->getFieldName()}[]" data-fieldinfo='{$FIELD_INFO|escape}' {if $FIELD_MODEL->isMandatory() eq true} data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !empty($SPECIAL_VALIDATOR)}data-validator='{\App\Utils\Json::encode($SPECIAL_VALIDATOR)}'{/if} {/if} {if $FIELD_MODEL->isEditableReadOnly()}readonly="readonly"{/if}>
	{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
		<option value="{$PICKLIST_VALUE.name}" {if in_array($PICKLIST_VALUE.name, $FIELD_VALUE_LIST)} selected {/if}>{$PICKLIST_VALUE.label}</option>
	{/foreach}
</select>
<!--/layouts/basic/modules/Base/uitypes/ModulesMultipicklist.tpl -->
{/strip}
