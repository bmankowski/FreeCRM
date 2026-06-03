{strip}
<!-- layouts/basic/modules/Base/dashboards/MailsList.tpl -->
<div class="dashboardWidgetHeader">
	{foreach key=index item=cssModel from=$STYLES}
		<link rel="{$cssModel->getRel()}" href="{$cssModel->getHref()}" type="{$cssModel->getType()}" media="{$cssModel->getMedia()}" />
	{/foreach}
	{foreach key=index item=jsModel from=$SCRIPTS}
		<script type="{$jsModel->getType()}" src="{$jsModel->getSrc()}"></script>
	{/foreach}
	<div class="row">
		<div class="col-md-8">
			<div class="dashboardTitle" title="{$WIDGET->getTitle()|t:'Mail'}"><strong>&nbsp;&nbsp;{$WIDGET->getTitle()|t:'Mail'}</strong></div>
		</div>
		<div class="col-md-4">
			<div class="box pull-right">
				{if !$WIDGET->isDefault()}
					<a name="dclose" class="btn btn-xs btn-default widget" data-url="{$WIDGET->getDeleteUrl()}">
						<span class="glyphicon glyphicon-remove" hspace="2" border="0" align="absmiddle" title="{"LBL_CLOSE"|t}" alt="{"LBL_CLOSE"|t}"></span>
					</a>
				{/if}
			</div>
		</div>
	</div>
	<hr class="widgetHr"/>
</div>
<div class="dashboardWidgetContent">
	{include file="dashboards/MailsListContents.tpl"|@vtemplate_path:$MODULE_NAME}
</div>
<!--/layouts/basic/modules/Base/dashboards/MailsList.tpl -->
{/strip}
