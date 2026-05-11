{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
********************************************************************************/
-->*}
{strip}
	<!-- layouts/basic/modules/Base/ListView.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				{* Header with breadcrumbs and action buttons *}
				<div class="widget_header row marginBottom10px">
					<div class="col-sm-6 col-xs-12">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					</div>
					<div class="col-sm-6 col-xs-12">
						<div class="pull-right">
							{foreach item=LINK from=$HEADER_LINKS['LIST_VIEW_HEADER']}
								{include file='ButtonLink.tpl'|@vtemplate_path:$MODULE BUTTON_VIEW='listViewHeader'}
							{/foreach}
						</div>
					</div>
				</div>
				{* Wrap list view header and contents in listViewPageDiv *}
				<div class="listViewPageDiv">
					{include file="ListViewHeader.tpl"|vtemplate_path:$QUALIFIED_MODULE}
					{if ($LIST_PREVIEW_MODE|default:false)}
						<input type="hidden" id="listViewMode" value="preview" />
						<div id="listPreviewSplit" style="display:flex; gap:0; align-items:stretch;">
							<div id="listPreviewLeft" style="flex: 0 0 50%; min-width: 320px;">
								{include file="ListViewContents.tpl"|vtemplate_path:$QUALIFIED_MODULE}
							</div>
							<div id="listPreviewResizer"
								title="{"LBL_PREVIEW"|t:$MODULE}"
								style="flex: 0 0 6px; cursor: col-resize; background: #e5e5e5; border-left: 1px solid #d0d0d0; border-right: 1px solid #d0d0d0;">
							</div>
							<div id="listPreviewRight" style="flex: 1 1 auto; min-width: 320px;">
								<div id="listPreviewContainer" class="panel panel-default" style="height: 100%;">
									<div class="panel-heading">
										<strong>{"LBL_PREVIEW"|t:$MODULE}</strong>
									</div>
									<div class="listPreviewFrameBody p-0" style="height: calc(100vh - 260px); min-height: 320px; overflow: hidden;">
										<iframe id="listPreviewFrame"
												title="{"LBL_PREVIEW"|t:$MODULE}"
												src="about:blank"
												style="width: 100%; height: 100%; border: 0; background: #fff;"></iframe>
									</div>
								</div>
							</div>
						</div>
					{else}
						{include file="ListViewContents.tpl"|vtemplate_path:$QUALIFIED_MODULE}
					{/if}
				</div> <!-- close listViewPageDiv -->

			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
	{/block}
	<!--/layouts/basic/modules/Base/ListView.tpl -->
{/strip}