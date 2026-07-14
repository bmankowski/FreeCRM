{strip}
	<!-- layouts/basic/modules/DelayedEmails/ListView.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				<div class="widget_header row marginBottom10px">
					<div class="col-xs-12">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					</div>
				</div>
				<div class="listViewContentDiv" id="listViewContents">
					{include file='ListViewContent.tpl'|@vtemplate_path:$MODULE}
				</div>
			</div>
		</div>
	{/block}
{/strip}
