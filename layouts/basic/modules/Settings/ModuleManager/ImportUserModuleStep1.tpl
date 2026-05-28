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
<!-- layouts/basic/modules/Settings/ModuleManager/ImportUserModuleStep1.tpl -->
	<div class="" id="importModules">
		<div class="widget_header row">
			<div class="col-xs-12">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
				{if isset($SELECTED_PAGE)}
					{$SELECTED_PAGE->get('description')|t:$QUALIFIED_MODULE}
				{/if}
			</div>
		</div>
		{if $MAX_UPLOAD_SIZE_TOO_SMALL}
			<div class="alert alert-block alert-danger fade in">
				<button type="button" class="close" data-dismiss="alert">×</button>
				<h4 class="alert-heading">{"LBL_TOO_SMALL_UPLOAD_LIMIT"|t:$QUALIFIED_MODULE}</h4>
				<p>{'LBL_TOO_SMALL_UPLOAD_LIMIT_DESC'|t:$QUALIFIED_MODULE:$MAX_UPLOAD_SIZE_HUMAN}</p>
			</div>	
		{/if}
		<div class="contents">
			<div>
				<form class="form-horizontal contentsBackground" id="importUserModule" name="importUserModule" action='index.php' method="POST" enctype="multipart/form-data">
					<input type="hidden" name="module" value="ModuleManager" />
					<input type="hidden" name="moduleAction" value="Import"/>
					<input type="hidden" name="parent" value="Settings" />
					<input type="hidden" name="view" value="ModuleImport" />
					<input type="hidden" name="mode" value="importUserModuleStep2" />
					<div name='uploadUserModule'>
						<div class="modal-body tabbable">
							<div class="tab-content massEditContent">
								<table class="massEditTable table table-bordered">
									<tr>
										<td class="fieldLabel alignMiddle">{"LBL_IMPORT_MODULE_FROM_FILE"|t:$QUALIFIED_MODULE}</td>
										<td class="fieldValue">
											<input type="file" name="moduleZip" id="moduleZip" size="80px" 
												   data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"
												   data-validator={$UPLOAD_MODULE_ZIP_VALIDATOR}
												   />
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<div class="col-md-1 pull-right cancelLinkContainer">
							<a class="cancelLink btn btn-warning" href="index.php?module=ModuleManager&parent=Settings&view=ListView">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</a>
						</div>
						<button class="btn btn-success" type="submit" name="saveButton"><strong>{"LBL_IMPORT"|t:$QUALIFIED_MODULE}</strong></button>
					</div>
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/ModuleManager/ImportUserModuleStep1.tpl -->
{/strip}
