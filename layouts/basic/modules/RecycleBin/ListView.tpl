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
{extends file='MainLayout.tpl'|@vtemplate_path:$MODULE}

{block name="content"}
<!-- layouts/basic/modules/RecycleBin/ListView.tpl -->
<div class="listViewTopMenuDiv">
	<div class="listViewActionsDiv row">
		<span class="btn-toolbar col-md-4">
			{if $LISTVIEW_MASSACTIONS}
			<span class="btn-group listViewMassActions">
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><strong>{"LBL_ACTIONS"|t:$MODULE}</strong>&nbsp;&nbsp;<i class="caret"></i></button>
				<ul class="dropdown-menu">
					{foreach item="LISTVIEW_MASSACTION" from=$LISTVIEW_MASSACTIONS}
						<li id="{$MODULE}_listView_massAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($LISTVIEW_MASSACTION->getLabel())}">
							<a href="javascript:void(0);" {if stripos($LISTVIEW_MASSACTION->getUrl(), 'javascript:')===0}onclick='{$LISTVIEW_MASSACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick="Vtiger_ListView_Js.triggerMassAction('{$LISTVIEW_MASSACTION->getUrl()}')"{/if} >{$LISTVIEW_MASSACTION->getLabel()|t:$MODULE}</a>
						</li>
					{/foreach}
				</ul>
			</span>
			{/if}
			{* Fix for empty Recycle bin Button *} 
			{foreach item=LISTVIEW_BASICACTION from=$LISTVIEW_LINKS['LISTVIEWBASIC']} 
				<span class="btn-group">  
					<button id="{$MODULE}_listView_basicAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($LISTVIEW_BASICACTION->getLabel())}" class="btn btn-default clearRecycleBin" {if stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0} onclick='{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick='window.location.href="{$LISTVIEW_BASICACTION->getUrl()}"'{/if} {if $DELETED_RECORDS_TOTAL_COUNT eq 0} disabled="disabled" {/if}>&nbsp;<strong>{$LISTVIEW_BASICACTION->getLabel()|t:$MODULE}</strong></button> 
				</span> 
			{/foreach} 
		</span>
		<span class="btn-toolbar col-md-4">
			<span class="customFilterMainSpan btn-group">
				{if $MODULE_LIST|@count gt 0}
					<select id="customFilter" style="width:350px;">
						{foreach item=MODULEMODEL from=$MODULE_LIST}
							{if $SOURCE_MODULE eq $MODULEMODEL->get('name')}
								<option  value="{$MODULEMODEL->get('name')}" selected="">{$MODULEMODEL->get('name')|t:$MODULEMODEL->get('name')}</option>
							{else if $MODULEMODEL->get('name') neq 'Events'}
								<option  value="{$MODULEMODEL->get('name')}">{$MODULEMODEL->get('name')|t:$MODULEMODEL->get('name')}</option>
							{/if}
						{/foreach}
					</select>
				{/if}
			</span>
		</span>
		<span class="col-md-4 btn-toolbar">
			{include file='ListViewActions.tpl'|@vtemplate_path}
		</span>
	</div>
	<div class="listViewContentDiv" id="listViewContents">
		<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
		<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
		<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
		<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
		<input type="hidden" id="numberOfEntries" value= "{$LISTVIEW_ENTRIES_COUNT}" />
		<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
		<input type="hidden" id="sourceModule" value="{$SOURCE_MODULE}" />
		<input type='hidden' id='pageNumber' value="{$PAGE_NUMBER}">
		<input type='hidden' id='pageLimit' value="{$PAGING_MODEL->getPageLimit()}">
		<input type="hidden" id="noOfEntries" value="{$LISTVIEW_ENTRIES_COUNT}">
		<input type="hidden" id="deletedRecordsTotalCount" value="{$DELETED_RECORDS_TOTAL_COUNT}">  
		<input type="hidden" id="listMaxEntriesMassEdit" value="{$LIST_MAX_ENTRIES_MASS_EDIT}" />

	<div id="selectAllMsgDiv" class="alert-block msgDiv hide">
		<strong><a id="selectAllMsg">{"LBL_SELECT_ALL"|t:$MODULE}&nbsp;{$SOURCE_MODULE|t:$SOURCE_MODULE}&nbsp;(<span id="totalRecordsCount"></span>)</a></strong>
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
				<span class="listViewLoadingImageBlock hide modal" id="loadingListViewModal">
					<img class="listViewLoadingImage" src="{vimage_path('loading.gif')}" alt="no-image" title="{'LBL_LOADING'|t}"/>
					<p class="listViewLoadingMsg">{'LBL_LOADING_LISTVIEW_CONTENTS'|t}........</p>
				</span>
				{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
				<table class="table table-bordered listViewEntriesTable {$WIDTHTYPE}">
					<thead>
						<tr class="listViewHeaders">
							<th width="5%">
								<input type="checkbox" title="{"LBL_SELECT_ALL"|t}" id="listViewEntriesMainCheckBox" />
							</th>
							{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
							<th {if $LISTVIEW_HEADER@last}colspan="2"{/if} class="noWrap {if $COLUMN_NAME eq $LISTVIEW_HEADER->get('column')}columnSorted{/if}">
								<a href="javascript:void(0);" class="listViewHeaderValues" {if $LISTVIEW_HEADER->isListviewSortable()}data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}"{/if} data-columnname="{$LISTVIEW_HEADER->get('column')}">
									{$LISTVIEW_HEADER->get('label')|t:$SOURCE_MODULE}
									&nbsp;&nbsp;
									{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('column')}
										<span class="{$SORT_IMAGE}"></span>
									{/if}
								</a>
							</th>
							{/foreach}
						</tr>
					</thead>
					{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
					<tr class="listViewEntries" data-id='{$LISTVIEW_ENTRY->getId()}' id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}">
						<td  width="5%" class="{$WIDTHTYPE}">
							<input type="checkbox" value="{$LISTVIEW_ENTRY->getId()}" title="{"LBL_SELECT_SINGLE_ROW"|t}" class="listViewEntriesCheckBox"/>
						</td>
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
						{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
						<td class="listViewEntryValue {$WIDTHTYPE}" nowrap>
							{if $LISTVIEW_HEADER->isNameField() eq true or $LISTVIEW_HEADER->get('uitype') eq '4'}
								<a {if $LISTVIEW_HEADER->isNameField() eq true}class="moduleColor_{$MODULE}"{/if} href="{$LISTVIEW_ENTRY->getDetailViewUrl()}">{$LISTVIEW_ENTRY->getListViewDisplayValue($LISTVIEW_HEADERNAME)}</a>
							{else}
								{$LISTVIEW_ENTRY->getListViewDisplayValue($LISTVIEW_HEADERNAME)}
							{/if}
							{if $LISTVIEW_HEADER@last}
							</td><td nowrap class="{$WIDTHTYPE}">
							<div class="pull-right actions">
								<span class="actionImages">
									<a class="restoreRecordButton"><i title="{"LBL_RESTORE"|t:$MODULE}" class="glyphicon glyphicon-refresh alignMiddle"></i></a>&nbsp;
									<a class="deleteRecordButton"><i title="{"LBL_DELETE"|t:$MODULE}" class="glyphicon glyphicon-trash alignMiddle"></i></a>
								</span>
							</div>
							</td>
							{/if}
						</td>
						{/foreach}
					</tr>
					{/foreach}
				</table>

				{if $LISTVIEW_ENTRIES_COUNT eq '0'}
					<table class="emptyRecordsDiv">
						<tbody>
							<tr>
								<td>
									{"LBL_NO_RECORDS_FOUND"|t:$MODULE} {$SOURCE_MODULE|t:$SOURCE_MODULE}.
								</td>
							</tr>
						</tbody>
					</table>
				{/if}
			</div>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/RecycleBin/ListView.tpl -->
{/block}
{/strip}

