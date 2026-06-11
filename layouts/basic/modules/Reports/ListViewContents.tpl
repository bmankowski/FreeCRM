{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Reports/ListViewContents.tpl -->
<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
<input type="hidden" id="numberOfEntries" value= "{$LISTVIEW_ENTRIES_COUNT}" />
<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
<input type='hidden' id='pageNumber' value="{$PAGE_NUMBER}" >
<input type='hidden' id='pageLimit' value="{$PAGING_MODEL->getPageLimit()}">
<input type="hidden" id="noOfEntries" value="{$LISTVIEW_ENTRIES_COUNT}">
<input type="hidden" id="listMaxEntriesMassEdit" value="{$LIST_MAX_ENTRIES_MASS_EDIT}" />

<div id="selectAllMsgDiv" class="alert-block msgDiv hide">
	<strong><a id="selectAllMsg">{"LBL_SELECT_ALL"|t:$MODULE}&nbsp;{$MODULE|t:$MODULE)}&nbsp;(<span id="totalRecordsCount"></span>)</a></strong>
</div>
<div id="deSelectAllMsgDiv" class="alert-block msgDiv hide">
	<strong><a id="deSelectAllMsg">{"LBL_DESELECT_ALL_RECORDS"|t:$MODULE}</a></strong>
</div>

<div class="contents-topscroll noprint stick" data-position="top">
	<div class="topscroll-div"></div>
</div>
<div class="listViewEntriesDiv contents-bottomscroll">
	<div class="bottomscroll-div">
	<input type="hidden" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
	<p class="listViewLoadingMsg hide">{"LBL_LOADING_LISTVIEW_CONTENTS"|t:$MODULE}........</p>
	{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
	<table class="table table-bordered listViewEntriesTable {$WIDTHTYPE}">
		<thead>
			<tr class="listViewHeaders">
				<th><input type="checkbox" title="{"LBL_SELECT_ALL"|t}" id="listViewEntriesMainCheckBox"></th>
				{foreach key=LISTVIEW_HEADER_KEY item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
					<th {if $LISTVIEW_HEADER@last}colspan="2"{/if} class="noWrap">
						<a href="javascript:void(0);" class="listViewHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER_KEY}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER_KEY}">
							{$LISTVIEW_HEADERS[$LISTVIEW_HEADER_KEY]|t:$MODULE}
							&nbsp;&nbsp;
							{if $COLUMN_NAME eq $LISTVIEW_HEADER_KEY}
								<span class="{$SORT_IMAGE}"></span>
							{/if}
						</a>
					</th>
				{/foreach}
			</tr>
		</thead>
		{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
		<tr class="listViewEntries" data-id={$LISTVIEW_ENTRY->getId()} data-recordUrl='{$LISTVIEW_ENTRY->getDetailViewUrl()}' id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}">
			<td class="{$WIDTHTYPE}"><input type="checkbox" value="{$LISTVIEW_ENTRY->getId()}" title="{"LBL_SELECT_SINGLE_ROW"|t}" class="listViewEntriesCheckBox"></td>
			{foreach key=LISTVIEW_HEADER_KEY item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
				<td nowrap class="{$WIDTHTYPE}">
					<a href="{$LISTVIEW_ENTRY->getDetailViewUrl()}">{$LISTVIEW_ENTRY->get($LISTVIEW_HEADER_KEY)|t:$MODULE}</a>
					{if $LISTVIEW_HEADER@last}
						</td><td nowrap class="{$WIDTHTYPE}">
						<div class="pull-right actions">
							<span class="actionImages">
								{if $LISTVIEW_ENTRY->isEditable()}
									<a href='{$LISTVIEW_ENTRY->getEditViewUrl()}'><span title="{"LBL_EDIT"|t:$MODULE}" class="glyphicon glyphicon-pencil alignMiddle"></span></a>&nbsp;
									{if $LISTVIEW_ENTRY->isDefault() eq false}
										<a class="deleteRecordButton"><span title="{"LBL_DELETE"|t:$MODULE}" class="glyphicon glyphicon-trash alignMiddle"></span></a>
									{/if}
								{/if}
							</span>
						</div>
						</td>
					{/if}
				</td>
			{/foreach}
		</tr>
		{/foreach}
	</table>

<!--added this div for Temporarily -->
{if $LISTVIEW_ENTRIES_COUNT eq '0'}
	<table class="emptyRecordsDiv">
		<tbody>
			<tr>
				<td>
					{assign var=SINGLE_MODULE value="SINGLE_$MODULE"}
					{"LBL_RECORDS_NO_FOUND"|t}. {"LBL_CREATE"|t} <a href="{$MODULE_MODEL->getCreateRecordUrl()}&folderid={$VIEWNAME}">{$SINGLE_MODULE|t:$MODULE}</a>
				</td>
			</tr>
		</tbody>
	</table>
{/if}
</div>
</div>

</div>
</div>
</div>
<!--/layouts/basic/modules/Reports/ListViewContents.tpl -->
{/strip}
