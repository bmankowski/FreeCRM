{*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}
{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				
<!-- layouts/basic/modules/Settings/Groups/EditView.tpl -->
	<div class="editViewContainer">
		<form name="EditGroup" action="index.php" method="post" id="EditView" class="form-horizontal">
			<input type="hidden" name="module" value="Groups">
			<input type="hidden" name="action" value="Save">
			<input type="hidden" name="parent" value="Settings">
			<input type="hidden" name="record" value="{$RECORD_MODEL->getId()}">
			<input type="hidden" name="mode" value="{$MODE}">

			<div class="widget_header row">
				<div class="col-xs-12">
					{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					{if isset($SELECTED_PAGE)}
						{$SELECTED_PAGE->get('description')|t:$QUALIFIED_MODULE}
					{/if}
				</div>
			</div>
			<hr>
			<div class="form-group">
				<div class="col-md-2 description-field">
					<span class="redColor">*</span> {"LBL_GROUP_NAME"|t:$QUALIFIED_MODULE}
				</div>
				<div class="col-md-6 controls">
					<input class="form-control" name="groupname" value="{$RECORD_MODEL->getName()}" data-validation-engine="validate[required]">
				</div>
			</div>
			<div class="form-group">
				<div class="col-md-2 description-field">
					{"LBL_DESCRIPTION"|t:$QUALIFIED_MODULE}
				</div>
				<div class="col-md-6 controls">
					<input class="form-control" name="description" id="description" value="{$RECORD_MODEL->getDescription()}" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-md-2 description-field">
					<span class="redColor">*</span> {"LBL_MODULES"|t:$QUALIFIED_MODULE}
				</div>
				<div class="col-md-6 controls">
					<select id="modulesList" class="row modules select2 form-control" multiple="true" name="modules[]" data-validation-engine="validate[required]">
						{foreach from=\App\Modules\Base\Models\Module::getAll([0],[],true) key=TABID item=MODULE_MODEL}
							<option value="{$TABID}" {if array_key_exists($TABID, $RECORD_MODEL->getModules())}selected="true"{/if}>{$MODULE_MODEL->getName()|t:$MODULE_MODEL->getName()}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group">
				<div class="col-md-2 description-field">
					<span class="redColor">*</span> {"LBL_GROUP_MEMBERS"|t:$QUALIFIED_MODULE}
				</div>
				<div class="col-md-6 controls">
					<div class="row">
						<div class="col-md-6">
							{assign var="GROUP_MEMBERS" value=$RECORD_MODEL->getMembers()}
							<select id="memberList" class="members form-control select2 groupMembersColors" multiple="true" name="members[]" data-placeholder="{"LBL_ADD_USERS_ROLES"|t:$QUALIFIED_MODULE}" data-validation-engine="validate[required]">
								{foreach from=$MEMBER_GROUPS key=GROUP_LABEL item=ALL_GROUP_MEMBERS}
									<optgroup label="{$GROUP_LABEL|t:$QUALIFIED_MODULE}">
										{foreach from=$ALL_GROUP_MEMBERS item=MEMBER}
											{if $MEMBER->getName() neq $RECORD_MODEL->getName()}
												<option class="{$GROUP_LABEL}" value="{$MEMBER->getId()}"  data-member-type="{$GROUP_LABEL}" {if isset($GROUP_MEMBERS[$GROUP_LABEL][$MEMBER->getId()])}selected="true"{/if}>{$MEMBER->getName()|t:$QUALIFIED_MODULE}</option>
											{/if}
										{/foreach}
									</optgroup>
								{/foreach}
							</select>
						</div>
						<div class="col-md-2">
							<span class="pull-right groupMembersColors">
								<ul class="liStyleNone">
									<li class="Users padding5per textAlignCenter"><strong>{"LBL_USERS"|t:$QUALIFIED_MODULE}</strong></li>
									<li class="Groups padding5per textAlignCenter"><strong>{"LBL_GROUPS"|t:$QUALIFIED_MODULE}</strong></li>
									<li class="Roles padding5per textAlignCenter"><strong>{"LBL_ROLES"|t:$QUALIFIED_MODULE}</strong></li>
									<li class="RoleAndSubordinates padding5per textAlignCenter"><strong>{"LBL_ROLEANDSUBORDINATE"|t:$QUALIFIED_MODULE}</strong></li>
								</ul>
							</span>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-5 pull-right">
					<span class="pull-right">
						<button class="btn btn-success" type="submit"><strong>{"LBL_SAVE"|t:$QUALIFIED_MODULE}</strong></button>
						<button class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
					</span>
				</div>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Settings/Groups/EditView.tpl -->
			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
{/block}
{/strip}
