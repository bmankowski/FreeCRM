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
<!-- layouts/basic/modules/Settings/Picklist/AssignValueToRole.tpl -->
<div class='modelContainer modal fade basicAssignValueToRoleView' tabindex="-1">
	<div class="modal-dialog">
        <div class="modal-content">
			<div class="modal-header">
				<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">x</button>
				<h3 class="modal-title">{"LBL_ASSIGN_VALUES_TO_ROLES"|t:$QUALIFIED_MODULE}</h3>
			</div>
			<form id="assignValueToRoleForm" class="form-horizontal" method="post" action="index.php">
				<input type="hidden" name="module" value="{$MODULE}" />
				<input type="hidden" name="parent" value="Settings" />
				<input type="hidden" name="source_module" value="{$SELECTED_MODULE_NAME}" />
				<input type="hidden" name="action" value="SaveAjax" />
				<input type="hidden" name="mode" value="assignValueToRole" />
				<input type="hidden" name="picklistName" value="{$SELECTED_PICKLIST_FIELDMODEL->get('name')}" />
				<input type="hidden" name="pickListValues" value='{\App\Json::encode($SELECTED_PICKLISTFIELD_ALL_VALUES)}' />
				<div class="modal-body tabbable">
					<div class="form-group">
						<div class="col-md-3 control-label"><span class="redColor">*</span>{"LBL_ITEM_VALUE"|t:$QUALIFIED_MODULE}</div>
						<div class="col-md-9 controls">
							<select multiple class="select2 form-control" id="assignValues" name="assign_values[]">
								{foreach key=PICKLIST_KEY item=PICKLIST_VALUE from=$SELECTED_PICKLISTFIELD_ALL_VALUES}
									<option value="{$PICKLIST_KEY}">{$PICKLIST_VALUE|t:$SELECTED_MODULE_NAME}</option>
								{/foreach}
							</select>
						</div>
					</div>		
					{if $SELECTED_PICKLIST_FIELDMODEL->isRoleBased()}
						<div class="form-group">	
							<div class="col-md-3 control-label"><span class="redColor">*</span>{"LBL_ASSIGN_TO_ROLE"|t:$QUALIFIED_MODULE}</div>
							<div class="col-md-9 controls">
								<select class="rolesList select2 form-control" id="rolesSelected" name="rolesSelected[]" multiple data-placeholder="{"LBL_CHOOSE_ROLES"|t:$QUALIFIED_MODULE}">
									<option value="all" selected>{"LBL_ALL_ROLES"|t:$QUALIFIED_MODULE}</option>
									{foreach from=$ROLES_LIST item=ROLE}
										<option value="{$ROLE->get('roleid')}">{$ROLE->get('rolename')|t}</option>
									{/foreach}
								</select>	
							</div>
						</div>
					{/if}
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$qualifiedName}
			</form>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/Picklist/AssignValueToRole.tpl -->
{/strip}
