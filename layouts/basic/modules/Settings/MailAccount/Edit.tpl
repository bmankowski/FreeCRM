{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}
{block name="content"}
<div class="mainContainer">
	<div class="contentsDiv">
		{include file='modules/Settings/MailAccount/EditContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
	</div>
</div>
{/block}
{/strip}
