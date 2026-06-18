{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1]} -->*}
{strip}
<!-- layouts/basic/modules/Base/uitypes/MultiReference.tpl -->
	{assign var=FIELD_NAME value=$FIELD_MODEL->getFieldName()}
	{assign var=REFERENCE_LIST value=$FIELD_MODEL->getReferenceList()}
	{assign var=FIELD_INFO value=\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($FIELD_MODEL->getFieldInfo()))}
	{assign var=SPECIAL_VALIDATOR value=$FIELD_MODEL->getValidator()}
	{assign var=SELECTED_RECORDS value=$FIELD_MODEL->getUITypeModel()->getEditViewDisplayValue($FIELD_MODEL->get('fieldvalue'))}
	{assign var=REFERENCE_MODULE value=$REFERENCE_LIST[0]|default:''}
	{assign var=REFERENCE_MODULE_MODEL value=\App\Modules\Base\Models\Module::getInstance($REFERENCE_MODULE)}
	<div class="js-multi-reference-field">
		<input name="popupReferenceModule" type="hidden" value="{$REFERENCE_MODULE}" title="{$REFERENCE_MODULE|t:$REFERENCE_MODULE}" />
		<input name="{$FIELD_NAME}" type="hidden" value="{$FIELD_MODEL->get('fieldvalue')|escape}" title="{$FIELD_MODEL->get('fieldvalue')|escape}"
			class="sourceField" data-type="entity" data-fieldtype="multiReference" data-multiple="true"
			data-fieldinfo='{$FIELD_INFO}'
			{if !empty($SPECIAL_VALIDATOR)}data-validator='{\App\Utils\Json::encode($SPECIAL_VALIDATOR)}'{/if}
			{if $FIELD_MODEL->isMandatory() eq true}data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"{/if}
			{if $FIELD_MODEL->isEditableReadOnly()}readonly="readonly"{/if} />
		<select id="{$MODULE}_{$VIEW}_fieldName_{$FIELD_NAME}" title="{$FIELD_MODEL->get('label')|t:$MODULE}" multiple class="chzn-select form-control col-md-12 js-multi-reference-select"
			data-fieldinfo='{$FIELD_INFO}'
			{if $FIELD_MODEL->isMandatory() eq true}data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"{/if}
			{if !empty($SPECIAL_VALIDATOR)}data-validator='{\App\Utils\Json::encode($SPECIAL_VALIDATOR)}'{/if}
			{if $REFERENCE_MODULE_MODEL == false || $FIELD_MODEL->isEditableReadOnly()}disabled="disabled"{/if}>
			{foreach from=$SELECTED_RECORDS item=REC}
				<option value="{$REC.id}" selected="selected" class="moduleColor_{$REC.module}">{\App\Modules\Base\Helpers\Util::toSafeHTML($REC.label)}</option>
			{/foreach}
		</select>
	</div>
<!--/layouts/basic/modules/Base/uitypes/MultiReference.tpl -->
{/strip}
