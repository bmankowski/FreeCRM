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
<!-- layouts/basic/modules/Users/EditPasswords.tpl -->
	<div id="massEditContainer" class='modelContainer modal fade' tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header contentsBackground">
					<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">&times;</button>
					<h3 id="massEditHeader">{"LBL_MASS_PASSWORD_EDITING"|t:$MODULE}</h3>
				</div>
				<form class="form-horizontal" id="changePassword" name="changePassword" method="post" action="index.php">
					<input type="hidden" name="module" value="{$MODULE}" />
					<input type="hidden" name="userids" value="{htmlspecialchars($USERIDS)}" />
					<div name='massEditContent'>
						<div class="modal-body">

							<div class="form-group">
								<label class="col-sm-4 control-label">{"LBL_NEW_PASSWORD"|t:$MODULE}</label>
								<div class="col-sm-6 controls">
									<input type="password" name="new_password" title="{"LBL_NEW_PASSWORD"|t:$MODULE}" class="form-control" data-validation-engine="validate[required]"/>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-4 control-label">{"LBL_CONFIRM_PASSWORD"|t:$MODULE}</label>
								<div class="col-sm-6 controls">
									<input type="password" name="confirm_password" title="{"LBL_CONFIRM_PASSWORD"|t:$MODULE}" class="form-control" data-validation-engine="validate[required]"/>
								</div>
							</div>

						</div>
					</div>
					{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Users/EditPasswords.tpl -->
{/strip}
