{strip}
<!-- layouts/basic/modules/Settings/Base/Credits.tpl -->
	<div class="settingsIndexPage">
		<div class="widget_header row">
			<div class="col-xs-12">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
			</div>
			<div class="col-xs-12">
				{"LBL_CREDITS_DESCRIPTION"|t:$QUALIFIED_MODULE}
			</div>
		</div>
		{include file="licenses/Credits.html"}
	</div>
<!--/layouts/basic/modules/Settings/Base/Credits.tpl -->
{/strip}
