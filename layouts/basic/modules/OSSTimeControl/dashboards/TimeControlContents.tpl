{*<!--
/*********************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 ********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/OSSTimeControl/dashboards/TimeControlContents.tpl -->
{if count($DATA) gt 0 }
	{assign var=SHOWING_ICON value=$TCPMODULE_MODEL->get('timeControlWidget')}
	<div class="summary-left pull-left" style="text-align:center;margin-left:2%;">
		{*if $SHOWING_ICON.workingDays eq 'true'}
			<span class="summary-detail">
				<img class=" summary-img" src="{vimage_path('all_days.png')}" alt="All days" title="{"LBL_ALLDAYS_INFO"|t:$MODULE_NAME}"/>
				<span class="summary-text">{$ALLDAYS}</span>
			</span>
			<span class="summary-detail">
				<span>
					<span style="margin-top:6px; vertical-align:top;" class="glyphicon glyphicon-calendar " title="{"LBL_WORKDAYS_INFO"|t:$MODULE_NAME}"></span>
				</span>
				<span class="summary-text">{$WORKDAYS}</span>
			</span>
			<span class="summary-detail">
				<img class=" summary-img" src="{vimage_path('weekend_days.png')}" alt="Weekend days" title="{"LBL_WEEKENDDAYS_INFO"|t:$MODULE_NAME}" />
				<span class="summary-text">
				{$WEEKENDDAYS}
				</span>
			</span>
		{/if}
		{if $SHOWING_ICON.holidays eq 'true'}
			<span class="summary-detail">
				<img class=" summary-img" src="{vimage_path('ecclesiastical.png')}" alt="Ecclesiastical" title="{"LBL_ECCLESIASTICAL_INFO"|t:$MODULE_NAME}"/>
				<span class="summary-text">
					{if $ECCLESIASTICAL}
						{$ECCLESIASTICAL}
					{else}
						0
					{/if}
				</span>
			</span>
			<span class="summary-detail">
				<img class=" summary-img"  src="{vimage_path('national.png')}" alt="National" title="{"LBL_NATIONAL_INFO"|t:$MODULE_NAME}"/>
				<span class="summary-text">
					{if $NATIONAL}
						{$NATIONAL}
					{else}
						0
					{/if}
				</span>
			</span>
		{/if*}

	</div>
	{*if $SHOWING_ICON.workingTime eq 'true'}
		<div class="summary-right pull-right" style="text-align:center;">
			<span class="summary-detail">
				<img class=" summary-img" src="{vimage_path('worked_days.png')}" alt="Worked days" title="{"LBL_WORKEDDAYS_INFO"|t:$MODULE_NAME}"/>
				<span class="summary-text">
					{if $WORKEDDAYS}
						{$WORKEDDAYS}
					{else}
						0
					{/if}
				</span>
			</span>
			<span class="summary-detail">
				<img class=" summary-img" src="{vimage_path('holiday_days.png')}" alt="Holiday days" title="{"LBL_HOLIDAYDAYS_INFO"|t:$MODULE_NAME}"/>
				<span class="summary-text">
					{if $HOLIDAYDAYS}
						{$HOLIDAYDAYS}
					{else}
						0
					{/if}
				</span>
			</span>
			<span class="summary-detail">
				<img class=" summary-img" src="{vimage_path('average_working_time.png')}" alt="Average working time" title="{"LBL_AVERAGEWORKTIME_INFO"|t:$MODULE_NAME}"/>
				<span class="summary-text">
					{if $AVERAGEWORKTIME}
						{$AVERAGEWORKTIME}
					{else}
						0
					{/if}
				</span>
			</span>
			<span class="summary-detail">
				<img class=" summary-img" src="{vimage_path('average_break_time.png')}" alt="Average breaking time" title="{"LBL_AVERAGEBREAKTIME_INFO"|t:$MODULE_NAME}"/>
				<span class="summary-text">
					{$AVERAGEBREAKTIME}
				</span>
			</span>
		</div>
	{/if*}
	<div class="clearfix"></div>
	<div class="widgetChartContainer" style="height:100%;width:98%"></div>
{else}
	<span class="noDataMsg">
		{"LBL_NO_RECORDS_MATCHED_THIS_CRITERIA"|t}
	</span>
{/if}
<input class="widgetData" type="hidden" value='{Vtiger_Util_Helper::toSafeHTML(\App\Json::encode($DATA))}' />
<style>
.summary-text{
	font-size: 20px;
	vertical-align: super;
}
.summary-img{
	margin-right: 3px;
}
.summary-detail{
	margin-right: 7px;
}
</style>
<!--/layouts/basic/modules/OSSTimeControl/dashboards/TimeControlContents.tpl -->
{/strip}
