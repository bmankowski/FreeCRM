{extends file="MainLayout.tpl"|@vtemplate_path}
{block name="content"}
	<div class="mainContainer">
		<div class="contentsDiv">
			{include file='WorkflowsContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
		</div>
	</div>
{/block}
