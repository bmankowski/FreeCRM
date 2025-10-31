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
<!-- layouts/basic/modules/Settings/Groups/DetailView.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div id="page">
		<div class="mainContainer">
			<div class="contentsDiv">
				
				<div class="widget_header row">
		<div class="col-md-8">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
				{if isset($SELECTED_PAGE)}
					{$SELECTED_PAGE->get('description')|t:$QUALIFIED_MODULE}
				{/if}
		</div>
		<div class="col-md-4 ">
			<a href="{$RECORD_MODEL->getEditViewUrl()}" class="btn btn-info pull-right">
				<strong>{"LBL_EDIT_RECORD"|t:$MODULE}</strong>
			</a>
		</div>
	</div>
	<div class="detailViewInfo" id="groupsDetailContainer">
		<div class="">
			<form id="detailView" class="form-horizontal" method="POST">
				<div class="form-group">
					<div class="col-md-2 control-label">
						<span class="redColor">*</span>{"LBL_GROUP_NAME"|t:$QUALIFIED_MODULE} 
					</div>
					<div class="controls pushDown">
						<strong>{$RECORD_MODEL->getName()}</strong>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 control-label">
						{"LBL_DESCRIPTION"|t:$QUALIFIED_MODULE}  
					</div>
					<div class="controls pushDown">
						<strong>{$RECORD_MODEL->getDescription()}</strong>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 control-label">
						 <span class="redColor">*</span>{"LBL_MODULES"|t:$QUALIFIED_MODULE} 
					</div>
					<div class="controls pushDown">
						<div class="row">
							<div class="col-md-9 paddingLRZero">
								{foreach key=TABID item=MODULE from=$RECORD_MODEL->getModules() name=modules}
									{if  $smarty.foreach.modules.last}
										<strong>{$MODULE|t:$MODULE} </strong>
									{else}
										<strong>{$MODULE|t:$MODULE}, </strong>
									{/if} 
								{/foreach}
							</div>
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 control-label">
						<span class="redColor">*</span>{"LBL_GROUP_MEMBERS"|t:$QUALIFIED_MODULE} 
					</div>
					<div class="col-md-5 controls pushDown">
						<div class="row">
							<div class="collectiveGroupMembers">
								<ul class="nav list-group">
									{assign var="GROUPS" value=$RECORD_MODEL->getMembers()}
									{foreach key=GROUP_LABEL item=GROUP_MEMBERS from=$GROUPS}
										{if !empty($GROUP_MEMBERS)}
											<li class="row groupLabel nav-header">
												{$GROUP_LABEL|t:$QUALIFIED_MODULE}
											</li>
											{foreach item=GROUP_MEMBER_INFO from=$GROUP_MEMBERS}
												<li class="">
													<a href="{$GROUP_MEMBER_INFO->getDetailViewUrl()}">{$GROUP_MEMBER_INFO->get('name')|t:$QUALIFIED_MODULE}</a>
												</li>
											{/foreach}
										{/if}
									{/foreach}
								</ul>
							</div>
						</div>
					</div>
				</div>
			</form>
				</div>
				</div>
				
			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
	</div> <!-- close page -->
{/block}
<!--/layouts/basic/modules/Settings/Groups/DetailView.tpl -->
{/strip}
