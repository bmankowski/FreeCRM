{strip}
	{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/FreeCRMLicense.html]} --!>*}
<!-- layouts/basic/modules/Base/HeaderReminderButton.tpl -->
	{if $REMINDER_ACTIVE}
		<a class="btn btn-default btn-sm isBadge remindersNotice popoverTooltip {if \App\Core\AppConfig::module('Calendar', 'AUTO_REFRESH_REMINDERS')}autoRefreshing{/if}" data-content="{'LBL_REMINDER'|t}">
			<span class="glyphicon glyphicon-calendar" aria-hidden="true"></span>
			<span class="badge bgDanger hide">0</span>
		</a>
	{/if}
<!--/layouts/basic/modules/Base/HeaderReminderButton.tpl -->
{/strip}

