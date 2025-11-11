{*<!--
/*********************************************************************************
 * FreeCRM - Open Source CRM
 * This template is part of FreeCRM.
 ********************************************************************************/
-->*}
{strip}
	<!-- layouts/basic/modules/Settings/Users/ListViewContent.tpl -->

	{* List view actions and filters *}
	<div class="listViewActionsDiv row marginBottom10px">
		<div class="col-md-4 btn-toolbar">
			<span class="btn-group listViewMassActions">
				{if count($LISTVIEW_MASSACTIONS) gt 0 || $LISTVIEW_LINKS['LISTVIEW']|@count gt 0}
					<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
						<strong>{"LBL_ACTIONS"|t:$MODULE}</strong>&nbsp;&nbsp;<span class="caret"></span>
					</button>
					<ul class="dropdown-menu">
						{foreach item="LISTVIEW_MASSACTION" from=$LISTVIEW_MASSACTIONS name=actionCount}
							<li
								id="{$MODULE}_listView_massAction_{$MASS_ACTION_IDS[$LISTVIEW_MASSACTION->getLabel()]}">
								<a href="javascript:void(0);" {if stripos($LISTVIEW_MASSACTION->getUrl(), 'javascript:')===0}
									onclick='{$LISTVIEW_MASSACTION->getUrl()|substr:strlen("javascript:")};' {else}
									onclick="Vtiger_ListView_Js.triggerMassAction('{$LISTVIEW_MASSACTION->getUrl()}')" {/if}>
									{$LISTVIEW_MASSACTION->getLabel()|t:$MODULE}
								</a>
							</li>
							{if $smarty.foreach.actionCount.last eq true}
								<li class="divider"></li>
							{/if}
						{/foreach}
						{foreach item=LISTVIEW_ADVANCEDACTIONS from=$LISTVIEW_LINKS['LISTVIEW']}
							<li
								id="{$MODULE}_listView_advancedAction_{$ADVANCED_ACTION_IDS[$LISTVIEW_ADVANCEDACTIONS->getLabel()]}">
								<a {if stripos($LISTVIEW_ADVANCEDACTIONS->getUrl(), 'javascript:')===0} href="javascript:void(0);"
									onclick='{$LISTVIEW_ADVANCEDACTIONS->getUrl()|substr:strlen("javascript:")};' {else}
									href='{$LISTVIEW_ADVANCEDACTIONS->getUrl()}' {/if}>
									{$LISTVIEW_ADVANCEDACTIONS->getLabel()|t:$MODULE}
								</a>
							</li>
						{/foreach}
					</ul>
				{/if}
			</span>
			{foreach item=LISTVIEW_BASICACTION from=$LISTVIEW_LINKS['LISTVIEWBASIC']}
				<span class="btn-group">
					<button class="btn btn-default addButton" {if stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0}
						onclick='{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")};' {else}
						onclick='window.location.href="{$LISTVIEW_BASICACTION->getUrl()}"' {/if}>
						<span class="glyphicon glyphicon-plus"></span>&nbsp;
						<strong>{"LBL_ADD_RECORD"|t:$QUALIFIED_MODULE}</strong>
					</button>
				</span>
			{/foreach}
		</div>
		<div class="col-md-4 btn-toolbar marginLeftZero">
			{assign var="CURRENT_STATUS_FILTER" value=$smarty.get.search_value|default:'Active'}
			<select class="select2" id="usersFilter" name="status" style="min-width:350px;">
				<option value="Active" {if $CURRENT_STATUS_FILTER eq 'Active'}selected{/if}>
					{"LBL_ACTIVE_USERS"|t:$QUALIFIED_MODULE}</option>
				<option value="Inactive" {if $CURRENT_STATUS_FILTER eq 'Inactive'}selected{/if}>
					{"LBL_INACTIVE_USERS"|t:$QUALIFIED_MODULE}</option>
			</select>
		</div>
		<div class="col-md-4">
			{include file='ListViewActions.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
		</div>
	</div>

	{* List view table - inline from Settings/Base/ListViewContent.tpl *}
	<div class="listViewContentDiv" id="listViewContents">
		<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
		<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
		<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
		<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
		<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
		<input type="hidden" value="{$ORDER_BY}" id="orderBy">
		<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
		<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
		<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
		<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
		<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">

		<div class="listViewEntriesDiv">
			<span class="listViewLoadingImageBlock hide modal" id="loadingListViewModal">
				<img class="listViewLoadingImage" src="{vimage_path('loading.gif')}" alt="no-image"
					title="{'LBL_LOADING'|t}" />
				<p class="listViewLoadingMsg">{'LBL_LOADING_LISTVIEW_CONTENTS'|t}........</p>
			</span>
			{assign var="NAME_FIELDS" value=$MODULE_MODEL->getNameFields()}
			{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
			<table class="table tableRWD table-bordered table-condensed listViewEntriesTable">
				<thead>
					<tr class="listViewHeaders">
						<th width="1%" class="{$WIDTHTYPE}"></th>
						{assign var=WIDTH value={99/(count($LISTVIEW_HEADERS))}}
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
							<th width="{$WIDTH}%" nowrap {if $LISTVIEW_HEADER@last}colspan="2" {/if} class="{$WIDTHTYPE}">
								<a {if $LISTVIEW_HEADER->isListviewSortable()} class="listViewHeaderValues cursorPointer"
										data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}{$NEXT_SORT_ORDER}{else}ASC{/if}"
										data-columnname="{$LISTVIEW_HEADER->get('name')}"
									{/if}>{$LISTVIEW_HEADER->get('label')|t:$QUALIFIED_MODULE}
									{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}&nbsp;&nbsp;<span
										class="{$SORT_IMAGE}"></span>{/if}</a>
							</th>
						{/foreach}
					</tr>
				</thead>
				<tbody>
					{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
						<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}"
							{if method_exists($LISTVIEW_ENTRY,'getDetailViewUrl')}data-recordurl="{$LISTVIEW_ENTRY->getDetailViewUrl()}"
							{/if}>
							<td data-recordurl='' width="1%" nowrap class="{$WIDTHTYPE}">

							</td>
							{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
								{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
								{assign var=LAST_COLUMN value=$LISTVIEW_HEADER@last}
								<td class="listViewEntryValue {$WIDTHTYPE}" width="{$WIDTH}%" nowrap>
									&nbsp;{$LISTVIEW_ENTRY->getDisplayValue($LISTVIEW_HEADERNAME)}
									{if $LAST_COLUMN && $LISTVIEW_ENTRY->getRecordLinks()}
									</td>
									<td nowrap class="{$WIDTHTYPE} rightRecordActions">
										{assign var=LINKS value=$LISTVIEW_ENTRY->getRecordLinks()}
										{if count($LINKS) > 0}
											<div class="actions">
												<div class="pull-right">
													{foreach from=$LINKS item=LINK}
														{include file='ButtonLink.tpl'|@vtemplate_path:$QUALIFIED_MODULE BUTTON_VIEW='listViewBasic' MODULE=$QUALIFIED_MODULE}
													{/foreach}
												</div>
											</div>
										{/if}
									</td>
								{/if}
								</td>
							{/foreach}
						</tr>
					{/foreach}
				</tbody>
			</table>

			{if $LISTVIEW_ENTRIES_COUNT eq '0'}
				<table class="emptyRecordsDiv">
					<tbody>
						<tr>
							<td>
								{"LBL_NO_RECORDS_FOUND"|t:$QUALIFIED_MODULE}
							</td>
						</tr>
					</tbody>
				</table>
			{/if}
		</div>
	</div>

	<!--/layouts/basic/modules/Settings/Users/ListViewContent.tpl -->
{/strip}