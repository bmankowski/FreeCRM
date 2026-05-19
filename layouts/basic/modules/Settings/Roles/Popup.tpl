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
<!-- layouts/basic/modules/Settings/Roles/Popup.tpl -->
{extends file='PopupLayout.tpl'|@vtemplate_path}

{block name="content"}
<div id="popupPageContainer" class="popupContainer" style="min-height: 600px">
	<div class="popupContainer padding1per">
		<div class="row">
			<div class="col-md-6">
				<span><h3>{"LBL_ASSIGN_ROLE"|t:"Settings:Roles"}</h3></span>
			</div>
		</div>
		<hr>
	</div>
	<div class="popupContainer row">
		<div class="clearfix treeView">
			<ul>
				<li data-role="{$ROOT_ROLE->getParentRoleString()}" data-roleid="{$ROOT_ROLE->getId()}">
					<div class="toolbar-handle">
						<div>
							{if $TYPE == 'Transfer'}
								<a href="javascript:;" class="btn btn-default roleEle">{$ROOT_ROLE->getName()|t:$QUALIFIED_MODULE}</a>
							{else}
								<a href="javascript:;" class="btn btn-default">{$ROOT_ROLE->getName()|t:$QUALIFIED_MODULE}</a>
							{/if}
						</div>
					</div>
					{assign var="ROLE" value=$ROOT_ROLE}
					{include file=vtemplate_path("RoleTree.tpl", "Settings:Roles")}
				</li>
			</ul>
		</div>
	</div>
</div>
{/block}
<!--/layouts/basic/modules/Settings/Roles/Popup.tpl -->
{/strip}
