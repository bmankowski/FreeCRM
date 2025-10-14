{*<!--
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
-->*}
{strip}
<!-- layouts/basic/modules/Users/SwitchUsers.tpl -->
	<div class="modal-header">
		<button class="close" data-dismiss="modal" title="{"LBL_CLOSE"|t}">x</button>
		<h4 class="modal-title">{"LBL_SWITCH_USER"|t:$MODULE_NAME}</h4>
	</div>
	<form name="switchUsersForm" action="index.php" method="post">
		<input type="hidden" name="module" value="{$MODULE_NAME}" />
		<input type="hidden" name="action" value="SwitchUsers" />
		<input type="hidden" name="id" value="{$BASE_USER_ID}" />
		{if count($SWITCH_USERS) neq 0}
			<div class="modal-body text-center">
				<div class="form-group">
					<select class="select2 form-control" name="user" id="user">
						{foreach item=ROW key=USER_ID from=$SWITCH_USERS}
							<option value="{$USER_ID}">{$ROW['userName']} ({$ROW['roleName']|t})</option>
						{/foreach}
					</select>
				</div>
				<button type="button" class="btn btn-success">{"LBL_SWITCH"|t:$MODULE_NAME}</button>	
			</div>
		{/if}
		<div class="modal-footer">
			{if $BASE_USER_ID neq $USER_MODEL->getId()}
				<div class="pull-left">
					<div class="btn-toolbar">
						<button class="btn btn-primary getYourself" type="submit"><strong>{"LBL_SWITCH_TO_YOURSELF"|t:$MODULE_NAME}</strong></button>
					</div>
				</div>
			{/if}
			<button type="button" class="btn btn-warning dismiss" data-dismiss="modal">{"LBL_CLOSE"|t:$MODULE_NAME}</button>
		</div>
	</form>		
<!--/layouts/basic/modules/Users/SwitchUsers.tpl -->
{/strip}
