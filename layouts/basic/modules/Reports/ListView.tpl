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
<!-- layouts/basic/modules/Reports/ListView.tpl -->
<div class="listViewTopMenuDiv">
	<div class="listViewActionsDiv row">
		<span class="btn-toolbar col-md-4">
			<span class="btn-group listViewMassActions">
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><strong>{"LBL_ACTIONS"|t:$MODULE}</strong>&nbsp;&nbsp;<span class="caret"></span></button>
				<ul class="dropdown-menu">
					{foreach item="LISTVIEW_MASSACTION" from=$LISTVIEW_MASSACTIONS}
						<li id="{$MODULE}_listView_massAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($LISTVIEW_MASSACTION->getLabel())}"><a href="javascript:void(0);" {if stripos($LISTVIEW_MASSACTION->getUrl(), 'javascript:')===0}onclick='{$LISTVIEW_MASSACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick="Vtiger_ListView_Js.triggerMassAction('{$LISTVIEW_MASSACTION->getUrl()}')"{/if} >{$LISTVIEW_MASSACTION->getLabel()|t:$MODULE}</a></li>
					{/foreach}
					{if $LISTVIEW_LINKS['LISTVIEW']|@count gt 0}
						<li class="divider"></li>
						{foreach item=LISTVIEW_ADVANCEDACTIONS from=$LISTVIEW_LINKS['LISTVIEW']}
							<li id="{$MODULE}_listView_advancedAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($LISTVIEW_ADVANCEDACTIONS->getLabel())}"><a {if stripos($LISTVIEW_ADVANCEDACTIONS->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$LISTVIEW_ADVANCEDACTIONS->getUrl()|substr:strlen("javascript:")};'{else} href='{$LISTVIEW_ADVANCEDACTIONS->getUrl()}' {/if}>{$LISTVIEW_ADVANCEDACTIONS->getLabel()|t:$MODULE}</a></li>
						{/foreach}
					{/if}
				</ul>
			</span>
			{foreach item=LISTVIEW_BASICACTION from=$LISTVIEW_LINKS['LISTVIEWBASIC']}
				{if $LISTVIEW_BASICACTION->getLabel() eq 'LBL_ADD_RECORD'}
					{assign var="childLinks" value=$LISTVIEW_BASICACTION->getChildLinks()}
					<span class="btn-group">
						<button class="btn btn-default dropdown-toggle addButton" data-toggle="dropdown" id="{$MODULE}_listView_basicAction_Add">
							<span class="glyphicon glyphicon-plus"></span>&nbsp;
							<strong>{$LISTVIEW_BASICACTION->getLabel()|t:$MODULE}</strong>&nbsp;
							<span class="caret icon-white"></span></button>
						<ul class="dropdown-menu">
							{foreach item="childLink" from=$childLinks}
								<li id="{$MODULE}_listView_basicAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($childLink->getLabel())}">
									<a href="javascript:void(0);" onclick='{$childLink->getUrl()|substr:strlen("javascript:")};'>{$childLink->getLabel()|t:$MODULE}</a>
								</li>
							{/foreach}
						</ul>
					</span>
				{else}
					<span class="btn-group">
						<button id="{$MODULE}_listView_basicAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($LISTVIEW_BASICACTION->getLabel())}" class="btn addButton btn-default" {if stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0}onclick='{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick='window.location.href="{$LISTVIEW_BASICACTION->getUrl()}"'{/if}><span class="glyphicon glyphicon-plus"></span>&nbsp;<strong>{$LISTVIEW_BASICACTION->getLabel()|t:$MODULE}</strong></button>
					</span>
				{/if}
			{/foreach}
		</span>
		<span class="foldersContainer btn-toolbar col-md-4">{include file='ListViewFolders.tpl'|@vtemplate_path:$MODULE}</span>
		<span class="col-md-4 btn-toolbar">
			{include file='ListViewActions.tpl'|@vtemplate_path:$MODULE}
		</span>
	</div>
	<div class="listViewContentDiv" id="listViewContents">
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
			<strong><a id="selectAllMsg">{"LBL_SELECT_ALL"|t:$MODULE}&nbsp;{$MODULE|t:$MODULE}&nbsp;(<span id="totalRecordsCount"></span>)</a></strong>
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
<!--/layouts/basic/modules/Reports/ListView.tpl -->
{/block}
{/strip}

