{*<!--
/*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce.com
************************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Users/ListViewContents.tpl -->
	<input type="hidden" id="listViewEntriesCount" value="{$LISTVIEW_ENTRIES_COUNT}" />
	<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
	<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
	<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
	<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
	<input type="hidden" id="pageNumberValue" value= "{$PAGE_NUMBER}"/>
	<input type="hidden" id="pageLimitValue" value= "{$PAGING_MODEL->getPageLimit()}" />
	<input type="hidden" id="numberOfEntries" value= "{$LISTVIEW_ENTRIES_COUNT}" />
	<input type="hidden" id="alphabetSearchKey" value= "{$MODULE_MODEL->getAlphabetSearchField()}" />
	<input type="hidden" id="Operator" value="{$OPERATOR}" />
	<input type="hidden" id="alphabetValue" value="{$ALPHABET_VALUE}" />
	<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type="hidden" id="listMaxEntriesMassEdit" value="{$LIST_MAX_ENTRIES_MASS_EDIT}" />
	<input type="hidden" id="autoRefreshListOnChange" value="{AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')}" />
	<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
	<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
	<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">

	{include file=vtemplate_path('ListViewAlphabet.tpl',$MODULE)}
	<div id="selectAllMsgDiv" class="alert-block msgDiv noprint hide">
		<strong><a id="selectAllMsg">{"LBL_SELECT_ALL"|t:$MODULE}&nbsp;{$MODULE|t:$MODULE}&nbsp;</a></strong>
	</div>
	<div id="deSelectAllMsgDiv" class="alert-block msgDiv noprint hide">
		<strong><a id="deSelectAllMsg">{"LBL_DESELECT_ALL_RECORDS"|t:$MODULE}</a></strong>
	</div>
	<div class="listViewEntriesDiv" >
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
					<th width="2%">
						<input type="checkbox" id="listViewEntriesMainCheckBox" title="{"LBL_SELECT_ALL"|t}" />
					</th>
					<th width="5%" nowrap>
						<a href="javascript:void(0);" class="listViewHeaderValues">{"LBL_USER_LIST_DETAILS"|t:$MODULE}</a>
					</th>
					{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
						<th class="noWrap {if $COLUMN_NAME eq $LISTVIEW_HEADER->get('column')}columnSorted{/if}">
							<a href="javascript:void(0);" class="listViewHeaderValues" {if $LISTVIEW_HEADER->isListviewSortable()}data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}"{/if} data-columnname="{$LISTVIEW_HEADER->get('column')}">{$LISTVIEW_HEADER->get('label')|t:$MODULE}
								&nbsp;&nbsp;{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('column')}&nbsp;&nbsp;<span class="{$SORT_IMAGE}"></span>{/if}</a>
						</th>
					{/foreach}
					<th width="5%"></th>
				</tr>
			</thead>
			<tbody>
			{if $MODULE_MODEL->isQuickSearchEnabled()}
				<tr>
					<td><a class="btn btn-default" href="javascript:void(0);"><span class="glyphicon glyphicon-search"></span></a></td><td></td>
							{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS name=SEARCH_HEADERS}
						<td>
							{assign var=FIELD_UI_TYPE_MODEL value=$LISTVIEW_HEADER->getUITypeModel()}
							{assign var=SEARCH_INFO value=null}
							{if isset($SEARCH_DETAILS[$LISTVIEW_HEADER->getName()])}
								{assign var=SEARCH_INFO value=$SEARCH_DETAILS[$LISTVIEW_HEADER->getName()]}
							{/if}
							{include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(),$MODULE_NAME)
                    FIELD_MODEL= $LISTVIEW_HEADER SEARCH_INFO=$SEARCH_INFO USER_MODEL=$USER_MODEL}
						</td>
					{/foreach}
					<td width="5%">
						<button class="btn btn-default" data-trigger="listSearch">{'LBL_SEARCH'|t:$MODULE }</button>
					</td>
				</tr>
			{/if}
			{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
				<tr class="listViewEntries" data-id='{$LISTVIEW_ENTRY->getId()}' data-recordUrl='{$LISTVIEW_ENTRY->getDetailViewUrl()}' id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}" {if $LISTVIEW_ENTRY->colorList}style="background-color: {$LISTVIEW_ENTRY->colorList['background']};color: {$LISTVIEW_ENTRY->colorList['text']};"{/if}>
				<td  width="2%" class="{$WIDTHTYPE}">
					<input type="hidden" name="deleteActionUrl" value="{$LISTVIEW_ENTRY->getDeleteUrl()}">
					<input type="checkbox" value="{$LISTVIEW_ENTRY->getId()}" title="{"LBL_SELECT_SINGLE_ROW"|t}" class="listViewEntriesCheckBox"/>
				</td>
				<td width="5%" class="{$WIDTHTYPE}">
					<div class="row">
						<div class="col-md-6">
							<img class="list-user-img" alt="" src="{$LISTVIEW_ENTRY->getImageWebUrl()}">
						</div>
					</div>
				</td>
				{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
					{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
					<td class="{$WIDTHTYPE}" nowrap>
						{$LISTVIEW_ENTRY->getListViewDisplayValue($LISTVIEW_HEADERNAME)}
					</td>
				{/foreach}
				<td width="5%">
					{if $LISTVIEW_HEADER@last}
						<div class="pull-right actions">
							<span class="actionImages">
								<a href='{$LISTVIEW_ENTRY->getDuplicateRecordUrl()}'><span title="{"LBL_DUPLICATE"|t:$MODULE}" class="glyphicon glyphicon-retweet alignMiddle"></span></a>&nbsp;
									{if $IS_MODULE_EDITABLE && $LISTVIEW_ENTRY->get('status') eq 'Active'}
									<a id="{$MODULE}_LISTVIEW_ROW_{$LISTVIEW_ENTRY->getId()}_EDIT" href='{$LISTVIEW_ENTRY->getEditViewUrl()}'><span title="{"LBL_EDIT"|t:$MODULE}" class="glyphicon glyphicon-pencil alignMiddle"></span></a>&nbsp;
									{/if}
									{if $IS_MODULE_DELETABLE && $LISTVIEW_ENTRY->getId() != $USER_MODEL->getId()}
										{if $LISTVIEW_ENTRY->get('status') eq 'Active'}
										<a id="{$MODULE}_LISTVIEW_ROW_{$LISTVIEW_ENTRY->getId()}_DELETE" class="deleteRecordButton"><span title="{"LBL_DELETE"|t:$MODULE}" class="glyphicon glyphicon-trash alignMiddle"></span></a>
										{else}
										<a onclick="Settings_Users_ListView_Js.restoreUser({$LISTVIEW_ENTRY->getId()}, event);"><span title="{"LBL_RESTORE"|t:$MODULE}" class="glyphicon glyphicon-refresh alignMiddle"></span></a>&nbsp;
										<a onclick="Settings_Users_ListView_Js.deleteUserPermanently({$LISTVIEW_ENTRY->getId()}, event);"><span title="{"LBL_DELETE"|t:$MODULE}" class="glyphicon glyphicon-trash alignMiddle"></span></a>
										{/if}
									{/if}
							</span>
						</div>
					{/if}
				</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
		{if $LISTVIEW_ENTRIES_COUNT eq '0'}
			<table class="emptyRecordsDiv">
				<tbody>
					<tr>
						<td>
							{assign var=SINGLE_MODULE value="SINGLE_$MODULE"}
							{"LBL_NO_RECORDS_MATCHED_THIS_CRITERIA"|t:$MODULE} <!--{if $IS_MODULE_EDITABLE} {"LBL_CREATE"|t} <a href="{$MODULE_MODEL->getCreateRecordUrl()}">{$SINGLE_MODULE|t:$MODULE}</a>-->{/if}
						</td>
					</tr>
				</tbody>
			</table>
		{/if}
	</div>
<!--/layouts/basic/modules/Users/ListViewContents.tpl -->
{/strip}
