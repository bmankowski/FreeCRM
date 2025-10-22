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
<!-- layouts/basic/modules/Settings/Picklist/EditView.tpl -->
<div class='modelContainer modal fade' tabindex="-1">
	<div class="modal-dialog">
        <div class="modal-content">
			<div class="modal-header">
				<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">x</button>
				<h3 class="modal-title">{"LBL_RENAME_PICKLIST_ITEM"|t:$QUALIFIED_MODULE}</h3>
			</div>
			<form id="renameItemForm" class="form-horizontal" method="post" action="index.php">
				<input type="hidden" name="module" value="{$MODULE}" />
				<input type="hidden" name="parent" value="Settings" />
				<input type="hidden" name="source_module" value="{$SOURCE_MODULE}" />
				<input type="hidden" name="action" value="SaveAjax" />
				<input type="hidden" name="mode" value="rename" />
				<input type="hidden" name="picklistName" value="{$FIELD_MODEL->get('name')}" />
				<input type="hidden" name="pickListValues" value='{\App\Modules\Vtiger\Helpers\Util::toSafeHTML(\App\Json::encode($SELECTED_PICKLISTFIELD_EDITABLE_VALUES))}' />
				<div class="modal-body tabbable">
					<div class="form-group">
						<div class="col-md-3 control-label">{"LBL_ITEM_TO_RENAME"|t:$QUALIFIED_MODULE}</div>
						<div class="col-md-9 controls">
							{assign var=PICKLIST_VALUES value=$SELECTED_PICKLISTFIELD_EDITABLE_VALUES}
							<select class="chzn-select form-control" name="oldValue">
								<optgroup>
									{foreach from=$PICKLIST_VALUES key=PICKLIST_VALUE_KEY item=PICKLIST_VALUE}
										<option {if $FIELD_VALUE eq $PICKLIST_VALUE} selected="" {/if}value="{\App\Modules\Vtiger\Helpers\Util::toSafeHTML($PICKLIST_VALUE)}" data-id={$PICKLIST_VALUE_KEY}>{$PICKLIST_VALUE|t:$SOURCE_MODULE}</option>
									{/foreach}	
								</optgroup>
							</select>	
						</div>
					</div>
					<div class="form-group">
						<div class="col-md-3 control-label"><span class="redColor">*</span>{"LBL_ENTER_NEW_NAME"|t:$QUALIFIED_MODULE}</div>
						<div class="col-md-9 controls"><input type="text" class="form-control" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-validator={\App\Json::encode([['name'=>'FieldLabel']])} name="newValue"></div>
					</div>
					{if $SELECTED_PICKLISTFIELD_NON_EDITABLE_VALUES}
					<div class="form-group">
						<div class="col-md-3 control-label">{"LBL_NON_EDITABLE_PICKLIST_VALUES"|t:$QUALIFIED_MODULE}</div>
						<div class="col-md-9 controls nonEditableValuesDiv">
							<ul class="nonEditablePicklistValues list-unstyled">
							{foreach from=$SELECTED_PICKLISTFIELD_NON_EDITABLE_VALUES key=NON_EDITABLE_VALUE_KEY item=NON_EDITABLE_VALUE}
								<li>{$NON_EDITABLE_VALUE|t:$SOURCE_MODULE}</li>
							{/foreach}
							</ul>
						</div>
					</div>
					{/if}
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$qualifiedName}
			</form>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/Picklist/EditView.tpl -->
{/strip}
