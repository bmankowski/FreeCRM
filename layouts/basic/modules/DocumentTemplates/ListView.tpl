{*<!--
/*********************************************************************************
** FreeCRM document templates list (configuration table).
********************************************************************************/
-->*}
{strip}
	<!-- layouts/basic/modules/DocumentTemplates/ListView.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				{include file="ListViewHeader.tpl"|vtemplate_path:$MODULE}
				{include file="ListViewContent.tpl"|vtemplate_path:$MODULE}
				</div>
			</div>
			</div>
		</div>
	{/block}
	<!--/layouts/basic/modules/DocumentTemplates/ListView.tpl -->
{/strip}
