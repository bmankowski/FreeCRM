{*<!--
/*+***********************************************************************************************************************************
 * The contents of this file are subject to the FreeCRM
 *************************************************************************************************************************************/
-->*}
{strip}
	<!-- layouts/basic/modules/Settings/Widgets/Index.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				{include file='IndexContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
	{/block}
	<!--/layouts/basic/modules/Settings/Widgets/Index.tpl -->
{/strip}