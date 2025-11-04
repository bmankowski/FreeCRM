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
					{include file="ListViewHeader.tpl"|vtemplate_path:$MODULE}
					{include file="ListViewContents.tpl"|vtemplate_path:$MODULE}
				</div> <!-- close listViewPageDiv -->

			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
	{/block}
	<!--/layouts/basic/modules/Base/ListView.tpl -->
{/strip}