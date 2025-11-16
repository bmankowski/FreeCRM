{strip}
	{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/FreeCRMLicense.html]} --!>*}
<!-- layouts/basic/modules/Base/HeaderNotificationButton.tpl -->
	{if \App\Security\Privilege::isPermitted('Notification', 'DetailView')}
		<a type="button" class="btn btn-default btn-sm isBadge notificationsNotice popoverTooltip {if \App\Core\AppConfig::module('Notification', 'AUTO_REFRESH_REMINDERS')}autoRefreshing{/if}" data-content="{'LBL_NOTIFICATIONS'|t}">
			<span class="glyphicon glyphicon-bell" aria-hidden="true"></span>
			<span class="badge hide">0</span>
		</a>
	{/if}
<!--/layouts/basic/modules/Base/HeaderNotificationButton.tpl -->
{/strip}

