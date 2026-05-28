{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Accounts/dashboards/NewAccountsContents.tpl -->
	{if count($NEW_ACCOUNTS) > 0}
		{if $PAGING_MODEL->getCurrentPage() eq 1}
			<div class="col-xs-4">
				<h6><b>{"Account Name"|t:$MODULE_NAME}</b></h6>
			</div>
			<div class="col-xs-4">
				<h6><b>{"LBL_ASSIGNED_TO"|t:$MODULE_NAME}</b></h6>
			</div>
			<div class="col-xs-4">
				<h6><b>{"Created Time"|t:$MODULE_NAME}</b></h6>
			</div>
			<div class="col-xs-12"><hr></div>
			{/if}
			{foreach from=$NEW_ACCOUNTS key=RECORD_ID item=ACCOUNTS_MODEL}
				<div class="col-xs-12 paddingLRZero">
					<div class="col-xs-4">
						{if $ACCOUNT_PERMISSIONS[$RECORD_ID]}
							<a href="index.php?module=Accounts&view=Detail&record={$RECORD_ID}">
								<b>{$ACCOUNTS_MODEL['accountname']}</b>
							</a>
						{else}
							{$ACCOUNTS_MODEL['accountname']}
						{/if}
					</div>
					<div class="col-xs-4">
						{$ACCOUNTS_MODEL['userModel']->getName()}
					</div>
					<div class="col-xs-4">
						<span title="{$ACCOUNTS_MODEL['createdtime']}">
							{$FORMATTED_DATES[$RECORD_ID]}
						</span>
					</div>
				</div>
			{/foreach}
		{if count($NEW_ACCOUNTS) eq $PAGING_MODEL->getPageLimit()}
			<div class="pull-right padding5">
				<button type="button" class="btn btn-xs btn-primary showMoreHistory" data-url="{$WIDGET->getUrl()}&page={$PAGING_MODEL->getNextPage()}&time[start]={$DTIME['start']}&time[end]={$DTIME['end']}">{"LBL_MORE"|t:$MODULE_NAME}</button>
			</div>
		{/if}
	{else}
		{if $PAGING_MODEL->getCurrentPage() eq 1}
			<span class="noDataMsg">
				{"LBL_NO_RECORDS_MATCHED_THIS_CRITERIA"|t}
			</span>
		{/if}
	{/if}
<!--/layouts/basic/modules/Accounts/dashboards/NewAccountsContents.tpl -->
{/strip}
