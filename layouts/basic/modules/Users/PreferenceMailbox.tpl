{strip}
<!-- layouts/basic/modules/Users/PreferenceMailbox.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div class="mainContainer">
		<div class="contentsDiv">
			{include file='PreferenceMailboxContent.tpl'|@vtemplate_path:$MODULE}
		</div>
	</div>
{/block}
<!--/layouts/basic/modules/Users/PreferenceMailbox.tpl -->
{/strip}
