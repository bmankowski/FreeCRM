{*<!--
/*+***********************************************************************************************************************************
FreeCRM - Customer Relationship Management System
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 *************************************************************************************************************************************/
-->*}
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				{include file='IndexContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
	{/block}
	<!--/layouts/basic/modules/Settings/Inventory/IndexView.tpl -->
{/strip}

