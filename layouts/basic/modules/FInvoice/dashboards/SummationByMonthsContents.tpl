{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/FInvoice/dashboards/SummationByMonthsContents.tpl -->
	{if count($DATA['chart']) gt 0 }
		<div class="clearfix"></div>
		<div class="widgetChartContainer" style="height:100%;width:98%"></div>
	{else}
		<span class="noDataMsg">
			{"LBL_NO_RECORDS_MATCHED_THIS_CRITERIA"|t}
		</span>
	{/if}
	<input class="widgetData" type="hidden" value="{\App\Modules\Vtiger\helpers\Util::toSafeHTML(\App\Json::encode($DATA))}"/>
<!--/layouts/basic/modules/FInvoice/dashboards/SummationByMonthsContents.tpl -->
{/strip}
