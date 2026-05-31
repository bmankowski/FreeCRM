{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce.com
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/LayoutEditor/CreateFieldModal.tpl -->
	<div class="modal createFieldModal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3 class="modal-title">{'LBL_CREATE_CUSTOM_FIELD'|t:$QUALIFIED_MODULE}</h3>
				</div>
				<form class="form-horizontal createCustomFieldForm"  method="POST">
					<div class="modal-body">
						<div class="form-group">
							<div class="col-md-3 control-label">
								{'LBL_SELECT_FIELD_TYPE'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select class="fieldTypesList form-control" name="fieldType">
									{foreach item=FIELD_TYPE from=$ADD_SUPPORTED_FIELD_TYPES}
										<option value="{$FIELD_TYPE}"
												{foreach key=TYPE_INFO item=TYPE_INFO_VALUE from=$FIELD_TYPE_INFO[$FIELD_TYPE]}
													data-{$TYPE_INFO}="{$TYPE_INFO_VALUE}"
												{/foreach}>
											{$FIELD_TYPE|t:$QUALIFIED_MODULE}
										</option>
									{/foreach}
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_LABEL_NAME'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<input type="text" maxlength="50" name="fieldLabel" value="" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" class="form-control"
									   data-validator={$FIELD_LABEL_VALIDATOR_JSON} />
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_FIELD_NAME'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<input type="text" maxlength="30" name="fieldName" value="" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" class="form-control"
									   data-validator={$FIELD_NAME_VALIDATOR_JSON} />
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_FIELD_TYPE'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select class="marginLeftZero form-control" name="fieldTypeList">
									<option value="0">{'LBL_FIELD_TYPE0'|t:$QUALIFIED_MODULE}</option>
									<option value="1">{'LBL_FIELD_TYPE1'|t:$QUALIFIED_MODULE}</option>
								</select>
							</div>
						</div>
						<div class="form-group supportedType lengthsupported">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_LENGTH'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<input type="text" name="fieldLength" value="" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" class="form-control"/>
							</div>
						</div>
						<div class="form-group supportedType decimalsupported hide">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_DECIMALS'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<input type="text" name="decimal" value="" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" class="form-control"/>
							</div>
						</div>
						<div class="form-group supportedType preDefinedValueExists hide">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_PICKLIST_VALUES'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select id="picklistUi" class="form-control" name="pickListValues" multiple="" tabindex="-1" aria-hidden="true" placeholder="{'LBL_ENTER_PICKLIST_VALUES'|t:$QUALIFIED_MODULE}" 
										data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-validator={$PICKLIST_FIELD_VALUES_VALIDATOR_JSON}>
								</select>
							</div>
						</div>
						<div class="form-group supportedType preDefinedModuleList hide">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_RELATION_VALUES'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select {if $FIELD_TYPE_INFO['Related1M']['ModuleListMultiple'] eq true}multiple{/if} class="referenceModule form-control" name="referenceModule">
									{foreach item=MODULE_NAME from=$SUPPORTED_MODULES}
										<option value="{$MODULE_NAME}">{$MODULE_NAME|t:$MODULE_NAME}</option>
									{/foreach}
								</select>
							</div>
						</div>
						<div class="form-group supportedType preMultiReferenceValue hide">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_MULTI_REFERENCE_VALUE_MODULES'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select class="MRVModule form-control" name="MRVModule">
									{foreach item=RELATION from=$SELECTED_MODULE_MODEL->getRelations()}
										<option value="{$RELATION->get('modulename')}">{$RELATION->get('label')|t:$RELATION->get('modulename')}</option>
									{/foreach}
								</select>
							</div>
						</div>
						<div class="form-group supportedType preMultiReferenceValue hide">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_MULTI_REFERENCE_VALUE_FIELDS'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select class="MRVField form-control" name="MRVField">
									{foreach item=RELATION from=$SELECTED_MODULE_MODEL->getRelations()}
										{assign var=COUNT_FIELDS value=count($RELATION->getFields())}
										{foreach item=FIELD key=KEY from=$RELATION->getFields()}
											{if !isset($LAST_BLOCK) || $LAST_BLOCK != $FIELD->getBlockId()}
												<optgroup label="{$FIELD->getBlockName()|t:$RELATION->get('modulename')}" data-module="{$RELATION->get('modulename')}">
												{/if} 
												<option value="{$FIELD->getId()}" >{$FIELD->get('label')|t:$RELATION->get('modulename')}</option>
												{if $COUNT_FIELDS == ($KEY - 1)}
												</optgroup>
											{/if} 
											{assign var=LAST_BLOCK value=$FIELD->getBlockId()}
										{/foreach}
									{/foreach}
								</select>
							</div>
						</div>
						<div class="form-group supportedType preMultiReferenceValue hide">
							<div class="col-md-3 control-label">
								{'LBL_MULTI_REFERENCE_VALUE_FILTER_FIELD'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select class="filterField form-control" name="MRVFilterField">
									{foreach item=RELATION from=$SELECTED_MODULE_MODEL->getRelations()}
										<option value="-" data-module="{$RELATION->get('modulename')}">{'--None--'|t}</option>
										{foreach item=FIELD key=KEY from=$RELATION->getFields('picklist')}
											<option value="{$FIELD->getName()}" data-module="{$RELATION->get('modulename')}">{$FIELD->get('label')|t:$RELATION->get('modulename')}</option>
										{/foreach}
									{/foreach}
								</select>
							</div>
						</div>
						<div class="form-group supportedType preMultiReferenceValue hide">
							<div class="col-md-3 control-label">
								{'LBL_MULTI_REFERENCE_VALUE_FILTER_VALUE'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select class="MRVModule form-control" name="MRVFilterValue">
								</select>
							</div>
						</div>
						<div class="form-group supportedType picklistOption hide">
							<div class="col-md-3 control-label">
								&nbsp;
							</div>
							<div class="col-md-8 controls">
								<label class="checkbox">
									<input type="checkbox" class="checkbox" name="isRoleBasedPickList" value="1" >&nbsp;{'LBL_ROLE_BASED_PICKLIST'|t:$QUALIFIED_MODULE}
								</label>
							</div>
						</div>
						<div class="form-group supportedType preDefinedTreeList hide">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>&nbsp;
								{'LBL_TREE_TEMPLATE'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select class="TreeList form-control" name="tree">
									{foreach key=key item=item from=$SELECTED_MODULE_MODEL->getTreeTemplates($SELECTED_MODULE_NAME)}
										<option value="{$key}">{$item|t:$SELECTED_MODULE_NAME}</option>
									{foreachelse}
										<option value="-">{'LBL_NONE'|t}</option>
									{/foreach}
								</select>
							</div>
						</div>
					</div>
					{include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/LayoutEditor/CreateFieldModal.tpl -->
{/strip}