{strip}
<!-- layouts/basic/modules/Calendar/Reminders.tpl -->
	<style>
	{if empty($COLOR_LIST)}	
		{foreach item=ITEM from=\App\Modules\Settings\Calendar\Models\Module::getCalendarConfig('colors')}
			.borderColor{$ITEM['name']}{
				border-color: {$ITEM['value']};
			}
			.headingColor{$ITEM['name']}{
				background-color: {$ITEM['value']} !important;
				border-color: {$ITEM['value']};
			}
		{/foreach}
	{/if}
	</style>
	<div class="remindersContent">
		{foreach item=RECORD from=$RECORDS}
			{assign var=START_DATE value=$RECORD->get('date_start')}
			{assign var=START_TIME value=$RECORD->get('time_start')}
			{assign var=END_DATE value=$RECORD->get('due_date')}
			{assign var=END_TIME value=$RECORD->get('time_end')}
			{assign var=RECORD_ID value=$RECORD->getId()}
			<div class="panel borderColor{$RECORD->get('activitytype')}" data-record="{$RECORD_ID}">
				<div class="panel-heading headingColor{$RECORD->get('activitytype')}" 
					 {if !empty($COLOR_LIST[$RECORD_ID])}
					 style="background: {$COLOR_LIST[$RECORD_ID]['background']}; color: {$COLOR_LIST[$RECORD_ID]['text']};"
					 {/if}>
					<button class="btn btn-success btn-xs pull-right showModal" data-url="index.php?module=Calendar&view=ActivityStateModal&trigger=Reminders&record={$RECORD->getId()}">
						<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
					</button>
					<img class="activityTypeIcon" src="{vimage_path($RECORD->getActivityTypeIcon())}" />&nbsp;
					<a target="_blank" href="index.php?module=Calendar&view=Detail&record={$RECORD_ID}">
						{$RECORD->get('subject')}
					</a>
				</div>
				<div class="panel-body">
					<div>
						{'Start Date & Time'|t:$MODULE_NAME}: <strong>{\App\Modules\Base\Helpers\Util::formatDateTimeIntoDayString("$START_DATE $START_TIME",$RECORD->get('allday'))}</strong>
					</div>
					<div>
						{"Due Date"|t:$MODULE_NAME}: <strong>{\App\Modules\Base\Helpers\Util::formatDateTimeIntoDayString("$END_DATE $END_TIME",$RECORD->get('allday'))}</strong>
					</div>
					{if $RECORD->get('activitystatus') neq '' }
						<div>
							{"Status"|t:$MODULE_NAME}: <strong>{$RECORD->getDisplayValue('activitystatus')}</strong>
						</div>
					{/if}
					{if $RECORD->get('link') neq ''}
						<div>
							{"FL_RELATION"|t:$MODULE_NAME}: <strong>{$RECORD->getDisplayValue('link')}</strong>
							{if $PERMISSION_TO_SENDE_MAIL}
								{if $USER_MODEL->get('internal_mailer') == 1}
									{assign var=COMPOSE_URL value=OSSMail_Module_Model::getComposeUrl(\vtlib\Functions::getCRMRecordType($RECORD->get('link')), $RECORD->get('link'), 'Detail', 'new')}
									<a target="_blank" class="pull-right btn btn-default btn-xs" href="{$COMPOSE_URL}" title="{"LBL_SEND_EMAIL"|t}">
										<span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
									</a>
								{else}
									{assign var=URLDATA value=OSSMail_Module_Model::getExternalUrl(\vtlib\Functions::getCRMRecordType($RECORD->get('link')), $RECORD->get('link'), 'Detail', 'new')}
									{if $URLDATA && $URLDATA != 'mailto:?'}
										<a class="pull-right btn btn-default btn-xs" href="{$URLDATA}" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}">
											<span class="glyphicon glyphicon-envelope" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}"></span>
										</a>
									{/if}
								{/if}
							{/if}
						</div>
					{/if}
					{if $RECORD->get('process') neq '' }
						<div>
							{"FL_PROCESS"|t:$MODULE_NAME}: <strong>{$RECORD->getDisplayValue('process')}</strong>
						</div>
					{/if}
					{if $RECORD->get('subprocess') neq '' }
						<div>
							{"FL_SUB_PROCESS"|t:$MODULE_NAME}: <strong>{$RECORD->getDisplayValue('subprocess')}</strong>
						</div>
					{/if}
					{if $RECORD->get('location') neq '' }
						<div>
							{"Location"|t:$MODULE_NAME}:&nbsp;
							<strong>
								{$RECORD->get('location')}
							</strong>
							{if App\Privilege::isPermitted('OpenStreetMap')}
								<a class="pull-right btn btn-default btn-xs " onclick="Vtiger_Index_Js.showLocation('{$RECORD->get('location')}')">
									<span class="glyphicon glyphicon-map-marker" aria-hidden="true"></span>
								</a>
							{/if}
						</div>
					{/if}
					<hr />
					<div class="actionRow text-center">
						<a class="btn btn-default btn-sm btn-success showModal" data-url="index.php?module=Calendar&view=ActivityStateModal&trigger=Reminders&record={$RECORD->getId()}"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></a>
						<a class="btn btn-default btn-sm btn-primary reminderPostpone" data-time="15m">15{"LBL_M"|t:$MODULE_NAME}</a>
						<a class="btn btn-default btn-sm btn-primary reminderPostpone" data-time="30m">30{"LBL_M"|t:$MODULE_NAME}</a>
						<a class="btn btn-default btn-sm btn-primary reminderPostpone" data-time="1h">1{"LBL_H"|t:$MODULE_NAME}</a>
						<a class="btn btn-default btn-sm btn-primary reminderPostpone" data-time="2h">2{"LBL_H"|t:$MODULE_NAME}</a>
						<a class="btn btn-default btn-sm btn-primary reminderPostpone" data-time="6h">6{"LBL_H"|t:$MODULE_NAME}</a>
						<a class="btn btn-default btn-sm btn-primary reminderPostpone" data-time="1d">1{"LBL_D"|t:$MODULE_NAME}</a>
					</div>
				</div>
			</div>
		{foreachelse}
			<div class="alert alert-info">
				{"LBL_NO_CURRENT_ACTIVITIES"|t:$MODULE_NAME}
			</div>
		{/foreach}
	</div>
<!--/layouts/basic/modules/Calendar/Reminders.tpl -->
{/strip}
