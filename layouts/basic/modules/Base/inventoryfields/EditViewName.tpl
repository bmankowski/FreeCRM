{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/inventoryfields/EditViewName.tpl -->
{if $REFERENCE_MODULE}
	<div class="rowName">
		{assign var="FIELD_NAME" value={$FIELD->getColumnName()}|cat:$ROW_NO}
		{assign var="FIELD_INFO" value=\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode(['mandatory'=>true]))}
		{assign var="CRMEntity" value=CRMEntity::getInstance($REFERENCE_MODULE)}
		<div class="input-group">
			<input name="popupReferenceModule" type="hidden" data-multi-reference="1" data-field="{$CRMEntity->table_index}" value="{$REFERENCE_MODULE}" />
			<input name="{$FIELD_NAME}" type="hidden" value="{$ITEM_VALUE}" title="{$ITEM_VALUE}" class="sourceField" data-type="inventory" data-displayvalue='{$FIELD->getEditValue($ITEM_VALUE)}' data-fieldinfo='{$FIELD_INFO}' data-columnname="{$FIELD->getColumnName()}" {if $FIELD->get('displaytype') == 10}readonly="readonly"{/if} />
			{assign var="displayId" value=$ITEM_VALUE}
			{if $FIELD->get('displaytype') != 10}
				<span class="input-group-addon clearReferenceSelection cursorPointer">
					<span id="{$MODULE}_editView_fieldName_{$FIELD_NAME}_clear" class="glyphicon glyphicon-remove-sign" title="{"LBL_CLEAR"|t:$MODULE}"></span>
				</span>
			{/if}
			<input id="{$FIELD_NAME}_display" name="{$FIELD_NAME}_display" type="text" title="{$FIELD->getEditValue($ITEM_VALUE)}" class="marginLeftZero input-sm form-control autoComplete recordLabel" {if !empty($ITEM_VALUE)}readonly="true"{/if}
				   value="{\App\Modules\Base\Helpers\Util::toSafeHTML($FIELD->getEditValue($ITEM_VALUE))}" data-validation-engine="validate[{if $FIELD->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"
				   data-fieldinfo="{$FIELD_INFO}" {if $FIELD->get('displaytype') != 10}placeholder="{"LBL_TYPE_SEARCH"|t:$MODULE}"{/if}
				   {if $FIELD->get('displaytype') == 10}readonly="readonly"{/if}/>

			{if $FIELD->get('displaytype') != 10}
				<span class="input-group-addon relatedPopup cursorPointer">
					<span id="{$MODULE}_editView_fieldName_{$FIELD_NAME}_select" class="glyphicon glyphicon-search relatedPopup" title="{"LBL_SELECT"|t:$MODULE}" ></span>
				</span>
			{/if}
			{assign var=REFERENCE_MODULE_MODEL value=\App\Modules\Base\Models\Module::getInstance($REFERENCE_MODULE)}
			{if $REFERENCE_MODULE_MODEL->isQuickCreateSupported() && $FIELD->get('displaytype') != 10}
				<span class="input-group-addon cursorPointer createReferenceRecord">
					<span id="{$MODULE}_editView_fieldName_{$FIELD_NAME}_create" class="glyphicon glyphicon-plus" title="{"LBL_CREATE"|t:$MODULE}"></span>
				</span>
			{/if}
		</div>
		<div class="subProductsContainer">
			<ul class="pull-left">
			</ul>
		</div>
	</div>
{/if}
<!--/layouts/basic/modules/Base/inventoryfields/EditViewName.tpl -->
{/strip}
