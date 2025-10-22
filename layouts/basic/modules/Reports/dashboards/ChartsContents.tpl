{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Reports/dashboards/ChartsContents.tpl -->
	<input type="hidden" name="typeChart" value="{$CHART_TYPE}">
	<input class="widgetData" name="data" type="hidden" value="{\App\Modules\Vtiger\Helpers\Util::toSafeHTML(\App\Json::encode($DATA))}" />
	{if count($DATA['values']) gt 0 }
		<div class="widgetChartContainer chartcontent" style="height:100%;width:98%"></div>
	{else}
		<span class="noDataMsg">
			{"LBL_NO_RECORDS_MATCHED_THIS_CRITERIA"|t}
		</span>
	{/if}
<!--/layouts/basic/modules/Reports/dashboards/ChartsContents.tpl -->
{/strip}
