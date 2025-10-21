{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/dashboards/ChartFilterContents.tpl -->
	<input type="hidden" name="typeChart" value="{$CHART_TYPE}">
	<input type="hidden" class="color" value="{$COLOR}">
	<input class="widgetData" name="data" type="hidden" value="{\App\Modules\Vtiger\helpers\Util::toSafeHTML(\App\Json::encode($DATA_CHART))}" />
	{if count($CHART_TYPE) gt 0 }
		<div class="widgetChartContainer chartcontent"></div>
	{else}
		<span class="noDataMsg">
			{"LBL_NO_RECORDS_MATCHED_THIS_CRITERIA"|t}
		</span>
	{/if}
<!--/layouts/basic/modules/Vtiger/dashboards/ChartFilterContents.tpl -->
{/strip}
