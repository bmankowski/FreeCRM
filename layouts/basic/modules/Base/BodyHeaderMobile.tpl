{strip}
<!-- layouts/basic/modules/Base/BodyHeaderMobile.tpl -->
{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div class="actionMenu" aria-hidden="true">
		<div class="row">
			<div class="dropdown quickAction historyBtn">
				<div class="pull-left">
					{'LBL_PAGES_HISTORY'|t}
				</div>						
				<div class="pull-right">
					<a data-placement="left" data-toggle="dropdown" class="btn btn-default btn-sm showHistoryBtn" aria-expanded="false" href="#">
						<img class='alignMiddle popoverTooltip dropdown-toggle' src="{vimage_path('history.png')}" alt="{'LBL_PAGES_HISTORY'|t}" data-content="{"LBL_PAGES_HISTORY"|t}" />
					</a>
				</div>
			</div>
		</div>
		{if $REMINDER_ACTIVE}
			<div class="row">
				<div class="remindersNotice quickAction{if \App\Core\AppConfig::module('Calendar', 'AUTO_REFRESH_REMINDERS')} autoRefreshing{/if}">
					<div class="pull-left">
						{'LBL_REMINDER'|t}
					</div>	
					<div class="pull-right">
						<a class="btn btn-default" title="{'LBL_REMINDER'|t}" href="#">
							<span class="glyphicon glyphicon-calendar" aria-hidden="true"></span>
							<span class="badge hide bgDanger">0</span>
						</a>
					</div>
				</div>
			</div>
		{/if}
		{if $CHAT_ACTIVE}
			<div class="row">
				<div class="headerLinksAJAXChat quickAction">
					<div class="pull-left">
						{'LBL_CHAT'|t}
					</div>
					<div class="pull-right">
						<a class="btn btn-default ChatIcon" title="{'LBL_CHAT'|t}" href="#">
							<span class="glyphicon glyphicon-comment" aria-hidden="true"></span>
						</a>
					</div>
				</div>
			</div>
		{/if}
			{if \App\Modules\Users\Models\Privileges::isPermitted('Notification', 'DetailView')}
			<div class="row">
				<div class="notificationsNotice quickAction{if \App\Core\AppConfig::module('Notification', 'AUTO_REFRESH_REMINDERS')} autoRefreshing{/if}">
					<div class="pull-left">
						{'LBL_NOTIFICATIONS'|t}
					</div>
 					<div class="pull-right">
 						<a class="btn btn-default isBadge" title="{'LBL_NOTIFICATIONS'|t}" href="index.php?module=Notification&view=ListView">
							<span class="glyphicon glyphicon-bell" aria-hidden="true"></span>
							<span class="badge hide">0</span>
						</a>
					</div>
				</div>
			</div>
		{/if}
		<div class='row'>
			<div class="dropdown quickAction">
				<div class='pull-left'>
					{'LBL_QUICK_CREATE'|t}
				</div>
				<div class='pull-right'>
					<a id="mobile_menubar_quickCreate" class="dropdown-toggle btn btn-default" data-toggle="dropdown" title="{'LBL_QUICK_CREATE'|t}" href="#">
						<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
					</a>
					<ul class="dropdown-menu dropdown-menu-right commonActionsButtonDropDown">
						<li class="quickCreateModules">
							<div class="panel-default">
								<div class="panel-heading">
									<h4 class="panel-title"><strong>{'LBL_QUICK_CREATE'|t}</strong></h4>
								</div>
								<div class="panel-body paddingLRZero">
									{assign var='count' value=0}
									{foreach key=NAME item=MODULEMODEL from=\App\Modules\Base\Models\Module::getQuickCreateModules(true)}
										{assign var='quickCreateModule' value=$MODULEMODEL->isQuickCreateSupported()}
										{assign var='singularLabel' value=$MODULEMODEL->getSingularLabelKey()}
										{if $singularLabel == 'SINGLE_Calendar'}
											{assign var='singularLabel' value='LBL_EVENT_OR_TASK'}
										{/if}	
										{if $quickCreateModule == '1'}
											{if $count % 3 == 0}
												<div class="rows">
												{/if}
												<div class="col-xs-4{if $count % 3 != 2} paddingRightZero{/if}">
													<a class="quickCreateModule list-group-item" data-name="{$NAME}" data-url="{$MODULEMODEL->getQuickCreateUrl()}" href="javascript:void(0)" title="{$singularLabel|t:$NAME}">
														<span>{$singularLabel|t:$NAME}</span>
													</a>
												</div>
												{if $count % 3 == 2}
												</div>
											{/if}
											{assign var='count' value=$count+1}
										{/if}
									{/foreach}
									{if $count % 3 >= 1}
									</div>
								{/if}
							</div>
							</div>
						</li>
					</ul>
				</div>						
			</div>
		</div>
	</div>
	{if \App\Core\AppConfig::performance('GLOBAL_SEARCH')}
		<div class="searchMenu globalSearchInput">
			<div class="input-group">
				<select class="chzn-select basicSearchModulesList form-control col-md-5" title="{'LBL_SEARCH_MODULE'|t}">
					<option value="" class="globalSearch_module_All">{'LBL_ALL_RECORDS'|t}</option>
					{foreach key=MODULE_NAME item=fieldObject from=$SEARCHABLE_MODULES}
						{if isset($SEARCHED_MODULE) && $SEARCHED_MODULE eq $MODULE_NAME && $SEARCHED_MODULE !== 'All'}
							<option value="{$MODULE_NAME}" selected>{$MODULE_NAME|t:$MODULE_NAME}</option>
						{else}
							<option value="{$MODULE_NAME}" >{$MODULE_NAME|t:$MODULE_NAME}</option>
						{/if}
					{/foreach}
				</select>
				<div class="input-group-btn">
					<div class="pull-right">
						<button class="btn btn-default globalSearch " title="{"LBL_ADVANCE_SEARCH"|t}" type="button">
							<span class="glyphicon glyphicon-th-large"></span>
						</button>
					</div>
				</div>
			</div>
			<div class="input-group">
				<input type="text" class="form-control globalSearchValue" title="{"LBL_GLOBAL_SEARCH"|t}" placeholder="{"LBL_GLOBAL_SEARCH"|t}" results="10" />
				<div class="input-group-btn">
					<div class="pull-right">
						<button class="btn btn-default searchIcon" type="button">
							<span class="glyphicon glyphicon-search"></span>
						</button>
					</div>
				</div>
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/Base/BodyHeaderMobile.tpl -->
{/strip}
