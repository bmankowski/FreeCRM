{*<!--
/*+***********************************************************************************************************************************
FreeCRM - Customer Relationship Management System
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 *************************************************************************************************************************************/
-->*}
{strip}
	<!-- layouts/basic/modules/Settings/Users/EditView.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				{include file='../Users/EditView.tpl'|@vtemplate_path:'Users'}
			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
	{/block}
	<!--/layouts/basic/modules/Settings/Users/EditView.tpl -->
{/strip}





