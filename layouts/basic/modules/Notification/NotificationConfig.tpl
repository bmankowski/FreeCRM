{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Notification/NotificationConfig.tpl -->
	{assign var="CRON_ACTIVE" value=$CRON_INFO->getStatus()}
	{assign var="IS_PERMITTED" value=\App\Security\Privilege::isPermitted($MODULE, 'ReceivingMailNotifications')}
	<div class="modal-header row no-margin">
		<div class="col-xs-12 paddingLRZero">
			<div class="col-xs-8 paddingLRZero">
				<h4>{"LBL_WATCHING_MODULES"|t:$MODULE}</h4>
			</div>
			<div class="pull-right">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" aria-hidden="true">&times;</button>
			</div>
		</div>
	</div>
	<div class="modal-body paddingBottomZero">
		<form id="sortingCustomView">
			<div class="row">
				<div class="table-responsive padding10">
					<div class="col-xs-12">
						<table class="table table-bordered table-condensed modalDataTable">
							<thead>
								<tr>
									<th>
										<strong>{"LBL_MODULES"|t:$MODULE}</strong>
										<div class="pull-right">
											{if $CRON_ACTIVE && $IS_PERMITTED}
												<span title="{"LBL_SELECT_ALL"|t}" class="fa {if $IS_ALL_EMAIL_NOTICE}fa-envelope sandNoticeOn{else}fa-envelope-o sandNoticeOff{/if} fa-lg marginTB3 cursorPointer sentNotice"></span>
											{/if}
											<span class="pull-right marginIcon">
												<input type="checkbox" {if $SELECT_ALL_MODULES} checked {/if} class="selectAllModules" title="{"LBL_SELECT_ALL"|t}">
											</span>
										</div>
									</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$MODULE_LIST key=MODULE_ID item=MODULE_INFO name="modules"}
									{assign var="INDEX" value=$smarty.foreach.modules.iteration}
									<tr data-id="{$MODULE_ID}">
										<td><strong>{$MODULE_INFO->getName()|t:$MODULE_INFO->getName()}</strong>
											<span class="pull-right marginIcon">
												<input type="checkbox" {if in_array($MODULE_ID, $WATCHING_MODULES)}checked {/if} name="modules" class="watchingModule" {if $WATCHING_MODEL->isLock($MODULE_ID)}disabled{/if} value="{$MODULE_ID}">
											</span>
											{if $CRON_ACTIVE && $IS_PERMITTED}
												<span title="{"LBL_SENT_NOTIFICATIONS"|t:$MODULE}" class="fa {if in_array($MODULE_ID, $SCHEDULE_DATA.modules)}fa-envelope sandNoticeOn{else}fa-envelope-o sandNoticeOff{/if} fa-lg pull-right marginTB3 cursorPointer" data-val=""></span>
											{/if}
										</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</form>
	</div>
	<div class="modal-footer">
		{if $CRON_ACTIVE && \App\Security\Privilege::isPermitted($MODULE, 'ReceivingMailNotifications')}
			<div class="col-md-3 col-sm-4 schedule pull-left paddingRightZero">
				{assign var="POPOVER_CONTENT" value='LBL_CRON_LAUNCHING_FREQUENCY'|t:$MODULE|cat:': '|cat:$CRON_INFO->getFrequency()/60|cat:'LBL_MINUTES'|t}
				<select class="select2 form-control" name="frequency" title="{"LBL_SCHEDULE"|t:$MODULE}">
					<option value="5" {if $FREQUENCY eq 5} selected{/if}>{"PLL_5_MIN"|t:$MODULE}</option>
					<option value="15" {if $FREQUENCY eq '15'} selected{/if}>{"PLL_15_MIN"|t:$MODULE}</option>
					<option value="30" {if $FREQUENCY eq '30'} selected{/if}>{"PLL_30_MIN"|t:$MODULE}</option>
					<option value="60" {if $FREQUENCY eq '60'} selected{/if}>{"PLL_60_MIN"|t:$MODULE}</option>
					<option value="180" {if $FREQUENCY eq '180'} selected{/if}>{"PLL_3_H"|t:$MODULE}</option>
					<option value="720" {if $FREQUENCY eq '720'} selected{/if}>{"PLL_12_H"|t:$MODULE}</option>
					<option value="1440" {if $FREQUENCY eq '1440'} selected{/if}>{"PLL_24_H"|t:$MODULE}</option>
				</select>
			</div>
			<div class="pull-left col-xs-1 paddingLRZero">
				<a href="#" class="infoPopover pull-left" title="" data-placement="top" data-original-title="{"LBL_RECEIVING_MAIL_NOTIFICATIONS"|t:$MODULE}" data-content="{\App\Modules\Base\Helpers\Util::toSafeHTML($POPOVER_CONTENT)}">&nbsp;<span class="glyphicon glyphicon-info-sign"></span></a>
			</div>
		{/if}
		<div class="col-md-6 col-sm-6 pull-right">
			<button type="button" name="saveButton" class="btn btn-success">{"LBL_SAVE"|t:$MODULE_NAME}</button>
			<button type="button" class="btn btn-warning dismiss" data-dismiss="modal">{"LBL_CLOSE"|t:$MODULE_NAME}</button>
		</div>
	</div>
<!--/layouts/basic/modules/Notification/NotificationConfig.tpl -->
{/strip}
