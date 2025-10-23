{strip}
<!-- layouts/basic/modules/Settings/AdvancedPermission/EditViewS1.tpl -->
	{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div class="row widget_header">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{"LBL_ADVANCED_PERMISSION_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
	</div>
	<div class="editViewContainer">
		<form name="EditAdvPermission" action="index.php" method="post" id="EditView" class="form-horizontal">
			<input type="hidden" name="module" value="AdvancedPermission">
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" name="action" value="Save">
			<input type="hidden" name="record" value="{$RECORD_ID}">
			<input type="hidden" name="mode" value="step1">
			<div class="form-group">
				<label class="col-sm-2 control-label">
					<span class="redColor">*</span> {"LBL_NAME"|t:$QUALIFIED_MODULE}
				</label>
				<div class="col-sm-10">
					<input class="form-control" name="name" value="{$RECORD_MODEL->getName()}" data-validation-engine="validate[required]">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">
					<span class="redColor">*</span> {"LBL_ACTION"|t:$QUALIFIED_MODULE}
				</label>
				<div class="col-sm-10">
					<select id="modulesList" class="row modules select2 form-control" name="actions" data-validation-engine="validate[required]">
						{foreach from=\App\Modules\Settings\AdvancedPermission\Models\Module::$action key=ID item=NAME}
							<option value="{$ID}" {if $RECORD_MODEL->get('action') == $ID}selected="true"{/if}>{$NAME|t:$QUALIFIED_MODULE}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">
					<span class="redColor">*</span> {"LBL_STATUS"|t:$QUALIFIED_MODULE}
				</label>
				<div class="col-sm-10">
					<select id="modulesList" class="row modules select2 form-control" name="status" data-validation-engine="validate[required]">
						{foreach from=\App\Modules\Settings\AdvancedPermission\Models\Module::$status key=ID item=NAME}
							<option value="{$ID}" {if $RECORD_MODEL->get('status') == $ID}selected="true"{/if}>{$NAME|t:$QUALIFIED_MODULE}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">
					<span class="redColor">*</span> {"LBL_PRIORITY"|t:$QUALIFIED_MODULE}
				</label>
				<div class="col-sm-10">
					<select id="modulesList" class="row modules select2 form-control" name="priority" data-validation-engine="validate[required]">
						{foreach from=\App\Modules\Settings\AdvancedPermission\Models\Module::$priority key=ID item=NAME}
							<option value="{$ID}" {if $RECORD_MODEL->get('priority') == $ID}selected="true"{/if}>{$NAME|t}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">
					<span class="redColor">*</span> {"LBL_MODULE"|t:$QUALIFIED_MODULE}
				</label>
				<div class="col-sm-10">
					<select id="modulesList" class="row modules select2 form-control" name="tabid" data-validation-engine="validate[required]">
						{foreach from=\App\Modules\Vtiger\Models\Module::getAll([0],[],true) key=TABID item=MODULE_MODEL}
							<option value="{$TABID}" {if $RECORD_MODEL->get('tabid') == $TABID}selected="true"{/if}>{$MODULE_MODEL->getName()|t:$MODULE_MODEL->getName()}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-2"></div>
				<div class="col-sm-10 groupMembersColors">
					<ul class="nav nav-pills">
						<li class="Users padding5per textAlignCenter"><strong>{"LBL_USERS"|t:$QUALIFIED_MODULE}</strong></li>
						<li class="Groups padding5per textAlignCenter"><strong>{"LBL_GROUPS"|t:$QUALIFIED_MODULE}</strong></li>
						<li class="Roles padding5per textAlignCenter"><strong>{"LBL_ROLES"|t:$QUALIFIED_MODULE}</strong></li>
						<li class="RoleAndSubordinates padding5per textAlignCenter"><strong>{"RoleAndSubordinates"|t:$QUALIFIED_MODULE}</strong></li>
					</ul>
				</div>
				<label class="col-sm-2 control-label">
					<span class="redColor">*</span> {"LBL_MEMBERS"|t:$QUALIFIED_MODULE}
				</label>
				<div class="col-sm-10">
					<select id="modulesList" class="row modules select2 form-control groupMembersColors"  multiple="true" name="members[]" data-validation-engine="validate[required]">
						{foreach from=\App\PrivilegeUtil::getMembers() key=GROUP_LABEL item=ALL_GROUP_MEMBERS}
							<optgroup label="{$GROUP_LABEL|t}">
								{foreach from=$ALL_GROUP_MEMBERS key=MEMBER_ID item=MEMBER}
									<option class="{$MEMBER['type']}" value="{$MEMBER_ID}" {if $RECORD_MODEL->get('members') && in_array($MEMBER_ID, $RECORD_MODEL->get('members'))}selected="true"{/if}>{$MEMBER['name']|t}</option>
								{/foreach}
							</optgroup>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="row">
				<div class="col-md-5 pull-right">
					<span class="pull-right">
						<button class="btn btn-success" type="submit"><strong>{"LBL_SAVE_AND_CONDITIONS"|t:$QUALIFIED_MODULE}</strong></button>
						<button class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
					</span>
				</div>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Settings/AdvancedPermission/EditViewS1.tpl -->
{/strip}
