{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} -->*}
{strip}
<!-- layouts/basic/modules/Vtiger/dashboards/ChartFilterFooter.tpl -->
	<div class="widgetFooterContent">
		<div class="row no-margin">
			<div class="col-md-4">
				<button class="btn btn-xs btn-default recordCount" data-url="{\App\Modules\Vtiger\helpers\Util::toSafeHTML($CHART_MODEL->getGetTotalCountURL($OWNER))}">
					<span class="glyphicon glyphicon-equalizer" title="{"LBL_WIDGET_FILTER_TOTAL_COUNT_INFO"|t}"></span>
					<a class="pull-left hide" href="{\App\Modules\Vtiger\helpers\Util::toSafeHTML($CHART_MODEL->getListViewURL())}">
						<span class="count badge pull-left"></span>
					</a>
				</button>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Vtiger/dashboards/ChartFilterFooter.tpl -->
{/strip}
