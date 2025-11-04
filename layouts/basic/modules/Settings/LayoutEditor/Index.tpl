{*<!--
/*********************************************************************************
** FreeCRM
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/LayoutEditor/Index.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				{include file='FieldLayout.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
{/block}
<!--/layouts/basic/modules/Settings/LayoutEditor/Index.tpl -->
{/strip}

