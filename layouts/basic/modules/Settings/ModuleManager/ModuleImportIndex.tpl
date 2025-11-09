{*<!--
/*********************************************************************************
* FreeCRM - Customer Relationship Management System
* @project FreeCRM
* @author bmankowski@gmail.com
* @copyright (c) FreeCRM
* @license FreeCRM Public License 1.1
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/ModuleManager/ModuleImportIndex.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div class="mainContainer">
		<div class="contentsDiv">
			{if isset($CURRENT_VIEW_TEMPLATE) && $CURRENT_VIEW_TEMPLATE}
				{include file=$CURRENT_VIEW_TEMPLATE|@vtemplate_path:$QUALIFIED_MODULE}
			{else}
				{include file='ImportUserModuleStep1.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
			{/if}
		</div>
	</div>
{/block}
<!--/layouts/basic/modules/Settings/ModuleManager/ModuleImportIndex.tpl -->
{/strip}

