{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
{extends file='MainLayout.tpl'|@vtemplate_path:$MODULE}

{block name="content"}
<!-- layouts/basic/modules/OSSTimeControl/CalendarView.tpl -->
<input type="hidden" id="currentView" value="{$VIEW}" />
<input type="hidden" id="activity_view" value="{$CURRENT_USER->get('activity_view')}" />
<input type="hidden" id="time_format" value="{$CURRENT_USER->get('hour_format')}" />
<input type="hidden" id="start_hour" value="{$CURRENT_USER->get('start_hour')}" />
<input type="hidden" id="end_hour" value="{$CURRENT_USER->get('end_hour')}" />
<input type="hidden" id="date_format" value="{$CURRENT_USER->get('date_format')}" />
<input type="hidden" id="eventLimit" value="{$EVENT_LIMIT}" />
<input type="hidden" id="weekView" value="{$WEEK_VIEW}" />
<input type="hidden" id="dayView" value="{$DAY_VIEW}" />
<style>
{foreach from=\App\Modules\Settings\Calendar\Models\Module::getCalendarConfig('colors') item=ITEM}
	.calCol_{$ITEM.label}{ border: 1px solid {$ITEM.value}!important; }
	.listCol_{$ITEM.label}{ background: {$ITEM.value}!important; }
{/foreach}
{foreach from=\App\Modules\Settings\Calendar\Models\Module::getUserColors('colors') item=ITEM}
	.userCol_{$ITEM.id}{ background: {$ITEM.color}!important; }
{/foreach}
</style>
<div class="rowContent paddingLRZero col-xs-12">
	<div class="widget_header row marginbottomZero marginRightMinus20">
		<div class="pull-left paddingLeftMd">
			{include file='ButtonViewLinks.tpl'|@vtemplate_path LINKS=$QUICK_LINKS['SIDEBARLINK'] CLASS='listViewMassActions pull-left paddingLeftMd'}
		</div>
		<div class="col-xs-10">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE_NAME}
		</div>
	</div>
	<div class="bottom_margin paddingRight15">
		<p><!-- Divider --></p>
		<div id="calendarview"></div>
	</div>
</div>
<!--/layouts/basic/modules/OSSTimeControl/CalendarView.tpl -->
{/block}
{/strip}
