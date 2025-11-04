{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/Users/Locks.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div class="mainContainer">
		<div class="contentsDiv">
			{include file='LocksContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
		</div> <!-- close contentsDiv -->
	</div> <!-- close mainContainer -->
{/block}
<!--/layouts/basic/modules/Settings/Users/Locks.tpl -->
{/strip}

