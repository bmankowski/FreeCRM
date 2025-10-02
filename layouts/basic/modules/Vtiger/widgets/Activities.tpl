{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<div  class="summaryWidgetContainer activityWidgetContainer">
	<div class="widget_header row">
		<div class="col-xs-5">
			<h4 class="widgetTitle textOverflowEllipsis">
				{if $WIDGET['label'] eq ''}
					{'LBL_ACTIVITIES'|t:$MODULE_NAME}
				{else}	
					{$WIDGET['label']|t:$MODULE_NAME}
				{/if}
			</h4>
		</div>
		<div class="col-xs-5">
			<span class="pull-right">
				<input class="switchBtn" title="{'LBL_CHANGE_ACTIVITY_TYPE'|t}" type="checkbox" checked data-size="small" data-label-width="5" data-handle-width="100" data-on-text="{'LBL_CURRENT'|t}" data-off-text="{'LBL_HISTORY'|t}" data-basic-texton="{'LBL_CURRENT'|t}" data-basic-textoff="{'LBL_HISTORY'|t}">
			</span>
		</div>
		<div class="col-xs-2">
			<button class="btn btn-sm btn-default pull-right addButton createActivity" data-url="sourceModule={$RECORD->getModuleName()}&sourceRecord={$RECORD->getId()}&relationOperation=true" type="button"
					 title="{'LBL_ADD'|t:$MODULE_NAME}">
				<span class="glyphicon glyphicon-plus"></span>
			</button>
		</div>
	</div>
	<hr class="widgetHr">
	<div class="widgetContainer_{$key} widgetContentBlock" data-url="{$WIDGET['url']}" data-name="{$WIDGET['label']}">
		<div class="widget_contents">
		</div>
	</div>
</div>
{/strip}
