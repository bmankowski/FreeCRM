{*<!--/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */-->*}
{strip}
<!-- layouts/basic/modules/Users/ChangePasswordUser.tpl -->
	<div class="modal-header">
		<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">&times;</button>
		<h3 id="massEditHeader" class="modal-title">{"LBL_CHANGE_PASSWORD"|t:$MODULE}</h3>
	</div>
	<form class="form-horizontal" id="changePassword" name="changePassword" method="post" >
		<input type="hidden" name="module" value="{$MODULE}" />
		<input type="hidden" name="userid" value="{$USER_MODEL->getId()}" />
		<div name='massEditContent'>
			<div class="modal-body">
				<div class="form-group">
					{if !$USER_MODEL->isAdminUser()}
						<label class="control-label col-sm-4" >{"LBL_OLD_PASSWORD"|t:$MODULE}</label>
						<div class="controls col-sm-6">
							<input type="password" name="old_password" class="form-control" data-validation-engine="validate[required]"/>
						</div>
					{/if}
				</div>
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
<!--/layouts/basic/modules/Users/ChangePasswordUser.tpl -->
{/strip}
