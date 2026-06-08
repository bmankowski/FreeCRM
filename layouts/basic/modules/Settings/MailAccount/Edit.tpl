{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}
{block name="content"}
<div class="mainContainer">
	<div class="contentsDiv">
		<div class="row widget_header">
			<div class="col-xs-12">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			</div>
		</div>
		{include file='EditContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
	</div>
</div>
{/block}
{/strip}
